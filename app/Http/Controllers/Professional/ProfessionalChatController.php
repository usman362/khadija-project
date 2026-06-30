<?php

namespace App\Http\Controllers\Professional;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Conversation;
use App\Models\Event;
use App\Models\Message;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

/**
 * Professional Messages — Inbox. Server-rendered messaging workspace: a
 * conversation list (with derived compliance / escrow / verification tags),
 * the selected thread (real messages + attachments), inbox stats, and a
 * compose box wired to the existing conversations.messages endpoint.
 *
 * REAL data: conversations the pro participates in, their messages and
 * attachments, unread counts, and stats derived from bookings/agreements.
 * Response-time + a couple of badges are derived/illustrative where the data
 * model has no direct field.
 */
class ProfessionalChatController extends Controller
{
    public function index(Request $request): View
    {
        return view('professional.chat.index', $this->viewData($request, null));
    }

    public function show(Request $request, Conversation $conversation): View
    {
        $this->authorize('view', $conversation);

        return view('professional.chat.index', $this->viewData($request, $conversation->id));
    }

    private function viewData(Request $request, ?int $activeId): array
    {
        $user = $request->user();

        $conversations = Conversation::query()
            ->whereHas('participants', fn ($q) => $q->where('users.id', $user->id))
            ->with([
                'participants:id,name,email',
                'booking:id,event_id,status,price',
                'booking.event:id,title,starts_at',
                'event:id,title,starts_at',
                'messages' => fn ($q) => $q->latest()->limit(1),
                'messages.sender:id,name',
            ])
            ->get()
            ->sortByDesc(fn ($c) => optional($c->messages->first())->created_at ?? $c->updated_at)
            ->values();

        $list = $conversations->map(fn ($c) => $this->summarize($c, $user))->all();

        // Pick the active conversation.
        $activeConv = $activeId
            ? $conversations->firstWhere('id', $activeId)
            : $conversations->first();

        $thread = null;
        if ($activeConv) {
            $activeConv->load(['messages.sender:id,name', 'messages.attachments', 'participants:id,name,email']);
            $thread = $this->thread($activeConv, $user);
        }

        // Message Center category cards (counts).
        $categories = [
            'inbox'    => count($list),
            'chats'    => collect($list)->where('category', 'chats')->count(),
            'bidding'  => collect($list)->where('category', 'bidding')->count(),
            'packages' => collect($list)->where('category', 'packages')->count(),
            'offers'   => collect($list)->where('category', 'offers')->count(),
        ];

        // Conversation Info rail (client + related order) for the active thread.
        $info = $activeConv ? $this->info($activeConv, $user) : null;

        return [
            'currentUser' => $user,
            'conversations' => $list,
            'thread' => $thread,
            'categories' => $categories,
            'info' => $info,
            'stats' => $this->stats($conversations, $user),
            'tabCounts' => [
                'inbox' => count($list),
                'unread' => collect($list)->where('unread', '>', 0)->count(),
                'sent' => collect($list)->where('lastFromMe', true)->count(),
                'drafts' => 0,
            ],
            // retained for the "Create Message" modal
            'users' => User::where('id', '!=', $user->id)->select('id', 'name', 'email')->orderBy('name')->get(),
            'bookings' => Booking::with('event:id,title')
                ->where(fn ($q) => $q->where('client_id', $user->id)->orWhere('supplier_id', $user->id))
                ->whereIn('status', ['requested', 'confirmed'])->latest()->get(['id', 'event_id', 'client_id', 'supplier_id', 'status']),
            'events' => Event::where(fn ($q) => $q->where('client_id', $user->id)->orWhere('supplier_id', $user->id)->orWhere('created_by', $user->id))
                ->where('is_published', true)->latest()->get(['id', 'title']),
        ];
    }

    /** One conversation row for the list. */
    private function summarize(Conversation $c, User $user): array
    {
        $other = $c->participants->firstWhere('id', '!=', $user->id) ?? $c->participants->first();
        $last  = $c->messages->first();
        $event = $c->event ?? $c->booking?->event;
        $unread = $c->messages()->where('sender_id', '!=', $user->id)
            ->whereDoesntHave('reads', fn ($q) => $q->where('user_id', $user->id))->count();

        // Derived tags from the linked booking / agreement state.
        $tags = [];
        $booking = $c->booking;
        if ($booking) {
            $tags[] = $booking->status === 'confirmed'
                ? ['Escrow Active', 'green']
                : ['Deposit Pending', 'amber'];
        }
        $verified = optional($other?->profile)->trade_license_verified_at ?? null;
        $tags[] = $verified ? ['W-9 Verified', 'green'] : ['W-9 Missing', 'red'];

        return [
            'id'      => $c->id,
            'name'    => $other?->name ?? 'Conversation',
            'role'    => Str::headline($c->type ?? 'direct'),
            'subject' => $event?->title ?? ($c->type === 'booking' ? 'Booking discussion' : 'Direct message'),
            'preview' => $last ? Str::limit($last->body, 38) : 'No messages yet',
            'time'    => optional($last?->created_at)->diffForHumans(null, true) ?? '',
            'unread'  => $unread,
            'type'    => $c->type ?? 'direct',
            'tags'    => $tags,
            'initials' => $this->initials($other?->name ?? 'C'),
            'lastFromMe' => $last && $last->sender_id === $user->id,
            // Message Center category: 1-on-1 chat, gig bid (event-linked), or a direct offer/request.
            'category' => ($c->type ?? 'direct') === 'direct' ? 'chats' : ($event ? 'bidding' : 'offers'),
        ];
    }

    /** Conversation Info rail: the client's contact + the related order/proposal. */
    private function info(Conversation $c, User $user): array
    {
        $client  = $c->participants->firstWhere('id', '!=', $user->id) ?? $c->participants->first();
        $booking = $c->booking;
        $event   = $c->event ?? $booking?->event;

        $clientBookings = $client ? Booking::where('client_id', $client->id) : null;

        return [
            'name'         => $client?->name ?? 'Client',
            'initials'     => $this->initials($client?->name ?? 'C'),
            'email'        => $client?->email,
            'phone'        => $client?->phone ?? optional($client?->profile)->phone,
            'location'     => optional($client?->profile)->address ?? optional($client?->profile)->city,
            'member_since' => optional($client?->created_at)->format('M d, Y'),
            'total_orders' => $clientBookings ? (clone $clientBookings)->count() : 0,
            'total_spent'  => $clientBookings ? (float) (clone $clientBookings)->whereIn('status', ['confirmed', 'completed'])->sum('price') : 0,
            'order'        => $booking ? [
                'title'    => $event?->title ?? 'Booking',
                'proposal' => 'PR-' . str_pad((string) $booking->id, 4, '0', STR_PAD_LEFT),
                'price'    => (float) $booking->price,
                'status'   => $booking->status,
                'date'     => optional($booking->created_at)->format('M d, Y'),
            ] : null,
        ];
    }

    /** Full thread payload for the active conversation. */
    private function thread(Conversation $c, User $user): array
    {
        $other = $c->participants->firstWhere('id', '!=', $user->id) ?? $c->participants->first();
        $event = $c->event ?? $c->booking?->event;

        $messages = $c->messages->sortBy('created_at')->map(function ($m) use ($user) {
            return [
                'id'     => $m->id,
                'mine'   => $m->sender_id === $user->id,
                'sender' => $m->sender?->name ?? 'User',
                'body'   => $m->body,
                'time'   => optional($m->created_at)->format('M d, Y · g:i A'),
                'attachments' => $m->attachments->map(fn ($a) => [
                    'name' => $a->file_name,
                    'size' => $this->size($a->file_size),
                ])->all(),
            ];
        })->values()->all();

        return [
            'id'       => $c->id,
            'name'     => $other?->name ?? 'Conversation',
            'role'     => Str::headline($c->type ?? 'direct'),
            'subject'  => $event?->title ?? 'Conversation',
            'date'     => optional($event?->starts_at)->format('M d, Y'),
            'initials' => $this->initials($other?->name ?? 'C'),
            'messages' => $messages,
            'sendUrl'  => route('conversations.messages.store', $c->id),
            'showUrl'  => route('conversations.show', $c->id),
            'readUrl'  => route('conversations.mark-read', $c->id),
            'meId'     => $user->id,
        ];
    }

    /** Inbox stat tiles. */
    private function stats($conversations, User $user): array
    {
        $unread = $conversations->sum(fn ($c) => $c->messages()->where('sender_id', '!=', $user->id)
            ->whereDoesntHave('reads', fn ($q) => $q->where('user_id', $user->id))->count());

        $escrow = (float) Booking::where('supplier_id', $user->id)->where('status', 'confirmed')->sum('price');
        $needContract = Booking::where('supplier_id', $user->id)->where('status', 'confirmed')
            ->whereDoesntHave('agreements')->count();

        return [
            'unread'     => $unread,
            'total'      => $conversations->count(),
            'priority'   => $conversations->filter(fn ($c) => $c->booking?->status === 'requested')->count(),
            'response'   => $this->avgResponseTime($conversations, $user),
            'compliance' => $needContract,
            'escrow'     => $escrow,
            'escrow_convos' => Booking::where('supplier_id', $user->id)->where('status', 'confirmed')->count(),
        ];
    }

    /** Average reply latency: mean gap from an inbound message to the pro's next reply. */
    private function avgResponseTime($conversations, User $user): string
    {
        $ids = $conversations->pluck('id');
        if ($ids->isEmpty()) {
            return '—';
        }
        $byConv = Message::whereIn('conversation_id', $ids)
            ->orderBy('conversation_id')->orderBy('created_at')
            ->get(['conversation_id', 'sender_id', 'created_at'])
            ->groupBy('conversation_id');

        $gaps = [];
        foreach ($byConv as $list) {
            $pending = null;
            foreach ($list as $m) {
                if ((int) $m->sender_id !== (int) $user->id) {
                    $pending ??= $m->created_at;
                } elseif ($pending !== null) {
                    $gaps[] = $pending->diffInMinutes($m->created_at);
                    $pending = null;
                }
            }
        }
        if (empty($gaps)) {
            return '—';
        }
        $avg = (int) round(array_sum($gaps) / count($gaps));
        return $avg >= 60 ? round($avg / 60, 1) . 'h' : $avg . 'm';
    }

    private function initials(string $name): string
    {
        $w = preg_split('/\s+/', trim($name));
        return Str::upper(substr(($w[0] ?? 'C') ?: 'C', 0, 1) . (count($w) > 1 ? substr(end($w), 0, 1) : ''));
    }

    private function size(?int $bytes): string
    {
        $bytes = (int) $bytes;
        if ($bytes >= 1048576) {
            return round($bytes / 1048576, 1) . ' MB';
        }
        if ($bytes >= 1024) {
            return round($bytes / 1024) . ' KB';
        }
        return $bytes . ' B';
    }
}
