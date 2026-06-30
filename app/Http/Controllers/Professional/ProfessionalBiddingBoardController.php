<?php

namespace App\Http\Controllers\Professional;

use App\Http\Controllers\Controller;
use App\Models\Event;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Professional — Main Bidding Board.
 *
 * Every OPEN client gig in one place to bid on. Gigs are REAL published Events
 * (not completed/cancelled). Request type (SSR/MSR/ESR) is derived from how many
 * services the gig spans; match-score, time-left and images are AI/representative
 * fields until the live bid pipeline + scoring model land.
 */
class ProfessionalBiddingBoardController extends Controller
{
    public function index(Request $request): View
    {
        // Real open gigs: published & still biddable.
        $events = Event::query()
            ->where('is_published', true)
            ->whereNotIn('status', ['completed', 'cancelled'])
            ->with('categories:id,name')
            ->orderByRaw('starts_at IS NULL, starts_at ASC')
            ->limit(15)
            ->get();

        $gigs = $events->map(fn ($e) => $this->mapEvent($e))->all();

        $counts = [
            'all' => count($gigs),
            'ESR' => count(array_filter($gigs, fn ($g) => $g['type'] === 'ESR')),
            'SSR' => count(array_filter($gigs, fn ($g) => $g['type'] === 'SSR')),
            'MSR' => count(array_filter($gigs, fn ($g) => $g['type'] === 'MSR')),
        ];

        // Insights computed from the real open gigs.
        $topCat = $events->flatMap(fn ($e) => $e->categories->pluck('name'))
            ->countBy()->sortDesc()->keys()->first() ?: 'Photography';
        $avgBudget = $events->filter(fn ($e) => $e->budget)->avg('budget');
        $closingSoon = $events->filter(fn ($e) => $e->starts_at && $e->starts_at->isBetween(now(), now()->addDays(7)))->count();

        return view('professional.bidding-board.index', [
            'gigs'     => $gigs,
            'counts'   => $counts,
            'insights' => [
                ['Highest Demand', $topCat, '🔥'],
                ['Open Gigs', (string) count($gigs), '📈'],
                ['Avg. Budget', $avgBudget ? '$' . number_format($avgBudget) : '—', '💰'],
                ['Closing Soon', (string) $closingSoon, '⏰'],
            ],
        ]);
    }

    /** Map a real Event to the bidding-board gig card shape. */
    private function mapEvent(Event $e): array
    {
        $cats  = $e->categories->pluck('name')->all();
        $type  = count($cats) >= 4 ? 'ESR' : (count($cats) >= 2 ? 'MSR' : 'SSR');
        $days  = $e->starts_at ? (int) round(now()->diffInDays($e->starts_at, false)) : null;
        $stock = ['photo-1519741497674-611481863552', 'photo-1511795409834-ef04bbd61622', 'photo-1530103862676-de8c9debad1d', 'photo-1492684223066-81342ee5ff30'];

        return [
            'type'   => $type,
            'urgent' => $days !== null && $days >= 0 && $days <= 3,
            'title'  => $e->title,
            'desc'   => Str::limit($e->description ?: 'Open gig — full details available on request.', 140),
            'loc'    => $e->location ?: 'Location flexible',
            'date'   => $e->starts_at ? $e->starts_at->format('M j, Y') : 'Flexible',
            'guests' => null,
            'tags'   => $cats ?: ['General'],
            'budget' => $e->budget ? '$' . number_format($e->budget * 0.85) . ' – $' . number_format($e->budget) : 'Open budget',
            'time'   => ($days !== null && $days >= 0) ? ($days . ($days === 1 ? ' day left' : ' days left')) : 'Open',
            'match'  => 78 + ($e->id % 22), // representative AI match until scoring model lands
            'rating' => 5,
            'img'    => $stock[$e->id % count($stock)],
        ];
    }
}
