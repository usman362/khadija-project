<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

/**
 * Client Messages — Inbox. Server-rendered messaging workspace (mirrors the
 * professional one, orange client theme): conversation list, the selected
 * thread, inbox stats, and a compose box wired to conversations.messages.store
 * with live send + polling + read receipts.
 */
class ClientChatController extends Controller
{
    public function index(Request $request): View
    {
        return view('client.chat.index', $this->viewData($request, null));
    }

    public function show(Request $request, Conversation $conversation): View
    {
        $this->authorize('view', $conversation);

        return view('client.chat.index', $this->viewData($request, $conversation->id));
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

        $activeConv = $activeId ? $conversations->firstWhere('id', $activeId) : $conversations->first();
        $thread = null;
        if ($activeConv) {
            $activeConv->load(['messages.sender:id,name', 'messages.attachments', 'participants:id,name,email']);
            $thread = $this->thread($activeConv, $user);
        }

        return [
            'currentUser' => $user,
            'conversations' => $list,
            'thread' => $thread,
            'stats' => $this->stats($conversations, $user),
            'tabCounts' => [
                'inbox' => count($list),
                'unread' => collect($list)->where('unread', '>', 0)->count(),
                'sent' => collect($list)->where('lastFromMe', true)->count(),
            ],
            'recipients' => User::where('id', '!=', $user->id)->select('id', 'name')->orderBy('name')->get(),
        ];
    }

    private function summarize(Conversation $c, $user): array
    {
        $other = $c->participants->firstWhere('id', '!=', $user->id) ?? $c->participants->first();
        $last  = $c->messages->first();
        $event = $c->event ?? $c->booking?->event;
        $unread = $c->messages()->where('sender_id', '!=', $user->id)
            ->whereDoesntHave('reads', fn ($q) => $q->where('user_id', $user->id))->count();

        $tags = [];
        if ($c->booking) {
            $tags[] = $c->booking->status === 'confirmed' ? ['Booking Confirmed', 'green'] : ['Awaiting Confirmation', 'amber'];
        }
        if ($event) {
            $tags[] = ['Event Linked', 'blue'];
        }

        return [
            'id' => $c->id,
            'name' => $other?->name ?? 'Conversation',
            'role' => Str::headline($c->type ?? 'direct'),
            'subject' => $event?->title ?? ($c->type === 'booking' ? 'Booking discussion' : 'Direct message'),
            'preview' => $last ? Str::limit($last->body, 38) : 'No messages yet',
            'time' => optional($last?->created_at)->diffForHumans(null, true) ?? '',
            'unread' => $unread,
            'type' => $c->type ?? 'direct',
            'tags' => $tags,
            'initials' => $this->initials($other?->name ?? 'C'),
            'lastFromMe' => $last && $last->sender_id === $user->id,
        ];
    }

    private function thread(Conversation $c, $user): array
    {
        $other = $c->participants->firstWhere('id', '!=', $user->id) ?? $c->participants->first();
        $event = $c->event ?? $c->booking?->event;

        $messages = $c->messages->sortBy('created_at')->map(fn ($m) => [
            'id' => $m->id,
            'mine' => $m->sender_id === $user->id,
            'sender' => $m->sender?->name ?? 'User',
            'body' => $m->body,
            'time' => optional($m->created_at)->format('M d, Y · g:i A'),
            'attachments' => $m->attachments->map(fn ($a) => ['name' => $a->file_name, 'size' => $this->size($a->file_size)])->all(),
        ])->values()->all();

        return [
            'id' => $c->id,
            'name' => $other?->name ?? 'Conversation',
            'role' => Str::headline($c->type ?? 'direct'),
            'subject' => $event?->title ?? 'Conversation',
            'date' => optional($event?->starts_at)->format('M d, Y'),
            'initials' => $this->initials($other?->name ?? 'C'),
            'messages' => $messages,
            'sendUrl' => route('conversations.messages.store', $c->id),
            'showUrl' => route('conversations.show', $c->id),
            'readUrl' => route('conversations.mark-read', $c->id),
            'meId' => $user->id,
        ];
    }

    private function stats($conversations, $user): array
    {
        $unread = $conversations->sum(fn ($c) => $c->messages()->where('sender_id', '!=', $user->id)
            ->whereDoesntHave('reads', fn ($q) => $q->where('user_id', $user->id))->count());
        $escrow = (float) Booking::where('client_id', $user->id)->where('status', 'confirmed')->sum('price');

        return [
            'unread' => $unread,
            'total' => $conversations->count(),
            'priority' => $conversations->filter(fn ($c) => $c->booking?->status === 'requested')->count(),
            'response' => $this->avgResponseTime($conversations, $user),
            'compliance' => Booking::where('client_id', $user->id)->where('status', 'confirmed')->whereDoesntHave('agreements')->count(),
            'escrow' => $escrow,
            'escrow_convos' => Booking::where('client_id', $user->id)->where('status', 'confirmed')->count(),
        ];
    }

    /** Average reply latency: mean gap from an inbound message to the user's next reply. */
    private function avgResponseTime($conversations, $user): string
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
