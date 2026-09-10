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
                'participants:id,name,email,public_id,avatar,last_active_at',
                'booking:id,event_id,status,price',
                // Owner and publication too: the details panel's Award needs to
                // know the event is this client's, and "Posted 1 day ago" is
                // read from when it was published. With only id, title and date
                // loaded, every chat showed "Not posted yet" and no Award.
                'booking.event:id,title,starts_at,client_id,is_published,published_at,created_at,status',
                'event:id,title,starts_at,client_id,is_published,published_at,created_at,status',
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
            $activeConv->load(['messages.sender:id,name', 'messages.attachments', 'participants:id,name,email,public_id,avatar,last_active_at']);
            $thread = $this->thread($activeConv, $user);
        }

        return [
            'currentUser' => $user,
            'conversations' => $list,
            'thread' => $thread,
            'info' => $activeConv ? $this->info($activeConv, $user) : null,
            'stats' => $this->stats($conversations, $user),
            // Counted without what this person has archived: an archived
            // conversation is out of the inbox, and so out of its numbers.
            'tabCounts' => [
                'inbox' => collect($list)->where('archived', false)->count(),
                // Muted conversations keep their unread marks but not the count.
                'unread' => collect($list)->where('archived', false)->where('muted', false)->where('unread', '>', 0)->count(),
                'sent' => collect($list)->where('archived', false)->where('lastFromMe', true)->count(),
                'drafts' => 0,
                'archived' => collect($list)->where('archived', true)->count(),
            ],
            // Drives the event filter above the list. Only events that actually
            // have a conversation appear, so the dropdown can never offer a
            // filter that returns nothing.
            'eventFilters' => collect($list)->pluck('event')->filter()
                ->unique('id')->sortBy('title')->values()->all(),
            'recipients' => User::where('id', '!=', $user->id)->select('id', 'name')->orderBy('name')->get(),
        ];
    }

    /**
     * The rail beside the thread: who the client is talking to, and the booking
     * this conversation is about. The professional side shows the same panel
     * pointed the other way.
     */
    private function info(Conversation $c, $user): array
    {
        $pro     = $c->participants->firstWhere('id', '!=', $user->id) ?? $c->participants->first();
        $booking = $c->booking;
        $event   = $c->event ?? $booking?->event;

        /*
         * The job this chat is about, when the chat does not say.
         *
         * Sending a proposal does not open a conversation, so a client and a
         * professional who bid almost always talk in a plain direct chat with
         * no event on it. Reading the job only from the conversation meant the
         * job card, and the Award button with it, never appeared at all (Ali,
         * 2026-09-11: "chat me kahin show nhi horaha hai award karne ka").
         *
         * So it falls back to their proposals on this client's own events: the
         * newest one still open, or failing that the newest of any status, so
         * an award already made still reads as Awarded. Never another client's
         * event. The card says it came from their proposal.
         */
        $jobSource = $event ? 'chat' : null;
        $moreOpen  = 0;
        if (! $event && $pro) {
            $bids = \App\Models\Bid::where('supplier_id', $pro->id)
                ->whereHas('event', fn ($q) => $q->where('client_id', $user->id))
                ->with('event')
                ->latest('id')
                ->get();

            $open  = $bids->where('status', 'submitted');
            $pick  = $open->first() ?? $bids->first();
            $event = $pick?->event;
            if ($event) {
                $jobSource = 'proposal';
                $moreOpen  = $open->pluck('event_id')->unique()->reject(fn ($id) => $id === $event->id)->count();
            }
        }

        $withThisPro = $pro
            ? Booking::where('client_id', $user->id)->where('supplier_id', $pro->id)
            : null;

        return [
            'name'         => $pro?->name ?? 'Professional',
            // Idea 1: the permanent reference. Two professionals with the same
            // name are told apart here, in the conversation, rather than on a
            // profile page the client would have to go and find — and it is
            // what support asks for first when this thread becomes a dispute.
            'public_id'    => $pro?->public_id,
            'initials'     => $this->initials($pro?->name ?? 'P'),
            'email'        => $pro?->email,
            'phone'        => $pro?->phone ?? optional($pro?->profile)->phone,
            'location'     => optional($pro?->profile)->city ?? optional($pro?->profile)->address,
            'member_since' => optional($pro?->created_at)->format('M d, Y'),
            'bookings'     => $withThisPro ? (clone $withThisPro)->count() : 0,
            'spent'        => $withThisPro
                ? (float) (clone $withThisPro)->whereIn('status', ['confirmed', 'completed'])->sum('price')
                : 0.0,
            'profileUrl'   => $pro ? route('public.professional.show', $pro->id) : null,
            // Their photo, or the initials image every avatar falls back to.
            'avatar'       => $pro?->avatar_url,
            // Sir Peter, 2026-09-10: the job the conversation is about, with an
            // Award button, as in the Freelancer chat he sent.
            'job'          => $event ? [
                'title'  => $event->title,
                'posted' => $event->postedAt()?->humanAgo(),
                'url'    => route('client.events.show', $event),
                // 'chat' when the conversation names the event, 'proposal'
                // when it was found from their proposals.
                'source' => $jobSource,
                // Their other open proposals on this client's events.
                'more'   => $moreOpen,
            ] : null,
            'award'        => $this->award($event, $pro, $user),
            'archived'     => (bool) optional($c->participants->firstWhere('id', $user->id))->pivot?->archived_at,
            'archiveUrl'   => route('conversations.archive', $c),
            'muted'        => (bool) optional($c->participants->firstWhere('id', $user->id))->pivot?->muted_at,
            'muteUrl'      => route('conversations.mute', $c),
            'blockUrl'     => route('conversations.block', $c),
            'blockedByMe'  => $pro ? \App\Domain\Messaging\Blocking::blocked($user->id, $pro->id) : false,
            'blockedMe'    => $pro ? \App\Domain\Messaging\Blocking::blocked($pro->id, $user->id) : false,
            // When they were last here, to five minutes. Not "online".
            'lastActive'   => $pro?->last_active_at ? \Illuminate\Support\Carbon::parse($pro->last_active_at)->humanAgo() : null,
            'booking'      => $booking ? [
                'title'  => $event?->title ?? 'Booking',
                'ref'    => 'BK-' . str_pad((string) $booking->id, 4, '0', STR_PAD_LEFT),
                'price'  => (float) $booking->price,
                'status' => $booking->status,
                'date'   => optional($booking->created_at)->format('M d, Y'),
                'url'    => route('client.bookings.index'),
            ] : null,
        ];
    }

    /**
     * Where awarding this professional stands, for the event this chat is about.
     *
     * Award goes through finalization, the same way the Compare page does:
     * scope, price, schedule, contract and the $2.99 fee, before anything is
     * booked. The Proposals list's direct accept skips all of that, which is
     * why it is not used here. Starting again is safe; finalize.start picks up
     * the one already open.
     *
     * No proposal from them, no button: an Award with nothing to award is a
     * button that cannot do what it says.
     */
    private function award($event, $pro, $user): ?array
    {
        if (! $event || ! $pro || (int) $event->client_id !== (int) $user->id) {
            return null;
        }

        $bid = \App\Models\Bid::where('event_id', $event->id)->where('supplier_id', $pro->id)->latest('id')->first();

        $booked = \App\Models\Booking::where('event_id', $event->id)->where('supplier_id', $pro->id)
            ->whereNotIn('status', \App\Domain\Finance\ClientTotals::VOID_STATUSES)->exists();

        $started = $bid && \App\Models\Finalization::where('event_id', $event->id)
            ->where('supplier_id', $pro->id)->where('category_id', $bid->category_id)->exists();

        $state = match (true) {
            $booked || $bid?->status === 'won' => 'awarded',
            $bid?->status === 'declined'        => 'declined',
            $bid?->status === 'withdrawn'       => 'withdrawn',
            $bid !== null && $started           => 'in_progress',
            $bid !== null                       => 'open',
            default                             => 'none',
        };

        return [
            'state'  => $state,
            'url'    => in_array($state, ['open', 'in_progress'], true) ? route('client.finalize.start', $bid) : null,
            'amount' => $bid?->amount,
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
            // Idea 1 (Sir Peter): the other party's permanent reference, in the
            // one place a client is most likely to need it — two professionals
            // with the same name are told apart here, not on a profile page
            // they would have to go and find.
            'public_id' => $other?->public_id,
            'role' => Str::headline($c->type ?? 'direct'),
            'subject' => $event?->title ?? ($c->type === 'booking' ? 'Booking discussion' : 'Direct message'),
            'preview' => $last ? Str::limit($last->body, 38) : 'No messages yet',
            'time' => optional($last?->created_at)->diffForHumans(null, true) ?? '',
            'unread' => $unread,
            'type' => $c->type ?? 'direct',
            'tags' => $tags,
            'initials' => $this->initials($other?->name ?? 'C'),
            'lastFromMe' => $last && $last->sender_id === $user->id,
            'archived' => (bool) optional($c->participants->firstWhere('id', $user->id))->pivot?->archived_at,
            'muted' => (bool) optional($c->participants->firstWhere('id', $user->id))->pivot?->muted_at,
            'event' => $event ? ['id' => $event->id, 'title' => $event->title] : null,
            'sortAt' => optional($last?->created_at)->timestamp ?? optional($c->updated_at)->timestamp ?? 0,
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
            /*
             * A url, because the tile in the thread was a plain <div>: the
             * reader could see a file had been sent and could not open it.
             * Reported in the 26 Aug walkthrough as "upload works, viewing
             * doesn't" — and it was never viewable, only listed.
             */
            'attachments' => $m->attachments->map(fn ($a) => [
                'name'     => $a->file_name,
                'size'     => $this->size($a->file_size),
                'url'      => route('attachments.download', $a),
                'is_image' => $a->isImage(),
                'kind'     => $a->kind,
                'mime'     => $a->mime_type,
            ])->all(),
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
