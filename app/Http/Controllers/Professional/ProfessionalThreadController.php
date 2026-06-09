<?php

namespace App\Http\Controllers\Professional;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Conversation;
use App\Models\Review;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

/**
 * Professional Threads — a per-conversation deep view: the thread plus the
 * linked booking, shared files, conversation info, quick actions, and an
 * "AI Extracted Commitments" panel that mines the chat for contract-ready
 * terms (coverage time, arrival, delivery, …).
 *
 * REAL data: conversations the pro participates in, their messages and
 * attachments, the linked booking/event, and commitments extracted from the
 * message text by deterministic pattern matching (no LLM). The contract-
 * consent toggle + rating fall-backs are illustrative where the model has no
 * direct field.
 *
 * Routes: GET /professional/threads            (index)
 *         GET /professional/threads/{conversation}  (show)
 */
class ProfessionalThreadController extends Controller
{
    public function index(Request $request): View
    {
        return view('professional.threads.index', $this->viewData($request, null));
    }

    public function show(Request $request, Conversation $conversation): View
    {
        $this->authorize('view', $conversation);

        return view('professional.threads.index', $this->viewData($request, $conversation->id));
    }

    private function viewData(Request $request, ?int $activeId): array
    {
        $user = $request->user();

        $conversations = Conversation::query()
            ->whereHas('participants', fn ($q) => $q->where('users.id', $user->id))
            ->with(['participants:id,name,email', 'booking.event:id,title,starts_at', 'event:id,title,starts_at',
                'messages' => fn ($q) => $q->latest()->limit(1)])
            ->get()
            ->sortByDesc(fn ($c) => optional($c->messages->first())->created_at ?? $c->updated_at)
            ->values();

        $list = $conversations->map(function ($c) use ($user) {
            $other = $c->participants->firstWhere('id', '!=', $user->id) ?? $c->participants->first();
            $last  = $c->messages->first();
            $event = $c->event ?? $c->booking?->event;
            return [
                'id' => $c->id,
                'name' => $other?->name ?? 'Conversation',
                'project' => $event?->title ?? Str::headline($c->type ?? 'Direct'),
                'preview' => $last ? Str::limit($last->body, 34) : 'No messages yet',
                'time' => optional($last?->created_at)->diffForHumans(null, true) ?? '',
                'unread' => $c->messages()->where('sender_id', '!=', $user->id)
                    ->whereDoesntHave('reads', fn ($q) => $q->where('user_id', $user->id))->count(),
                'type' => $c->type ?? 'direct',
                'initials' => $this->initials($other?->name ?? 'C'),
            ];
        })->all();

        $active = $activeId ? $conversations->firstWhere('id', $activeId) : $conversations->first();

        $thread = null;
        if ($active) {
            $active->load(['messages.sender:id,name', 'messages.attachments', 'participants:id,name,email',
                'booking.event', 'event']);
            $thread = $this->thread($active, $user);
        }

        return [
            'conversations' => $list,
            'thread' => $thread,
            'archivedCount' => 15,
        ];
    }

    private function thread(Conversation $c, $user): array
    {
        $other = $c->participants->firstWhere('id', '!=', $user->id) ?? $c->participants->first();
        $event = $c->event ?? $c->booking?->event;
        $booking = $c->booking;

        $messages = $c->messages->sortBy('created_at')->map(fn ($m) => [
            'id'   => $m->id,
            'mine' => $m->sender_id === $user->id,
            'sender' => $m->sender?->name ?? 'User',
            'body' => $m->body,
            'time' => optional($m->created_at)->format('g:i A'),
            'day'  => optional($m->created_at)->format('M d, Y'),
        ])->values()->all();

        // Shared files = every attachment across the conversation.
        $files = $c->messages->flatMap->attachments->map(fn ($a) => [
            'name' => $a->file_name,
            'ext'  => strtoupper(pathinfo($a->file_name, PATHINFO_EXTENSION)),
            'size' => $this->size($a->file_size),
            'date' => optional($a->created_at)->format('M d'),
        ])->values()->all();

        // Reviewer reputation (real where present, else representative).
        $rating = round((float) Review::where('reviewee_id', $other?->id)->where('is_hidden', false)->avg('rating'), 1);
        $reviews = Review::where('reviewee_id', $other?->id)->where('is_hidden', false)->count();
        $gigs = $other ? Booking::where('supplier_id', $other->id)->where('status', 'completed')->count() : 0;

        return [
            'id' => $c->id,
            'name' => $other?->name ?? 'Conversation',
            'role' => 'Photographer',
            'initials' => $this->initials($other?->name ?? 'C'),
            'verified' => true,
            'rating' => $rating > 0 ? $rating : 4.9,
            'reviews' => $reviews > 0 ? $reviews : 124,
            'gigs' => $gigs > 0 ? $gigs : 32,
            'messages' => $messages,
            'sendUrl' => route('conversations.messages.store', $c->id),
            'showUrl' => route('conversations.show', $c->id),
            'readUrl' => route('conversations.mark-read', $c->id),
            'meId'    => $user->id,
            'booking' => $booking ? [
                'event' => $event?->title ?? 'Event',
                'date'  => optional($event?->starts_at)->format('M d, Y'),
                'location' => $event?->location ?? 'TBD',
                'id'    => 'BK-' . str_pad((string) (78290 + $booking->id), 5, '0', STR_PAD_LEFT),
                'status' => Str::headline($booking->status),
                'total' => '$' . number_format((float) $booking->price, 0),
            ] : null,
            'files' => $files,
            'commitments' => $this->commitments($c->messages),
        ];
    }

    /**
     * Mine the chat for contract-ready commitments (deterministic patterns).
     *
     * @return array<int, array<string, string>>
     */
    private function commitments($messages): array
    {
        $out = [];
        foreach ($messages->sortBy('created_at') as $m) {
            $text = $m->body;
            $when = optional($m->created_at)->format('M d, g:i A');

            if (! isset($out['coverage']) && preg_match('/from\s+(\d{1,2}:\d{2}\s*[AP]M)\s+to\s+(\d{1,2}:\d{2}\s*[AP]M)/i', $text, $mm)) {
                $out['coverage'] = ['title' => 'Coverage Time', 'detail' => "Main event coverage from {$mm[1]} to {$mm[2]}.", 'when' => $when];
            }
            if (! isset($out['arrival']) && preg_match('/arriv\w*\s+at\s+(\d{1,2}:\d{2}\s*[AP]M)/i', $text, $mm)) {
                $out['arrival'] = ['title' => 'Pre-Event Photoshoot', 'detail' => "Arrival at {$mm[1]} for pre-event photoshoot.", 'when' => $when];
            } elseif (! isset($out['arrival']) && preg_match('/photoshoot\s+at\s+(\d{1,2}:\d{2}\s*[AP]M)/i', $text, $mm)) {
                $out['arrival'] = ['title' => 'Pre-Event Photoshoot', 'detail' => "Pre-event photoshoot at {$mm[1]}.", 'when' => $when];
            }
            if (! isset($out['delivery']) && preg_match('/(\d+\+?)\s+edited\s+photos?\s+within\s+(\d+)\s+days?/i', $text, $mm)) {
                $out['delivery'] = ['title' => 'Photo Delivery', 'detail' => "{$mm[1]} edited photos will be delivered within {$mm[2]} days.", 'when' => $when];
            }
        }

        return array_values($out);
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
