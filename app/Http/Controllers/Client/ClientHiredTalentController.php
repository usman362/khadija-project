<?php

namespace App\Http\Controllers\Client;

use App\Domain\HiredTalent;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Hired Talent, the people this client has actually engaged.
 *
 * Sir Peter asked for Calendar & Availability to become subpages instead of
 * tabs, and for Hired Professionals and Hired Influencers to be "one subpage,
 * because once someone is hired, the Client's purpose is essentially the same
 * regardless of role".
 *
 * His sidebar image shows them as two entries and his mockups as two pages,
 * which disagrees with his own sentence. One page with a filter satisfies both
 * readings: a single place to go, and each group still reachable on its own.
 *
 * The influencer filter will be empty until influencers can be hired at all.
 * It says so rather than hiding, because an empty answer to "who have I hired"
 * is a real answer.
 */
class ClientHiredTalentController extends Controller
{
    private const GROUPS = ['all', 'professionals', 'influencers'];
    private const STATES = ['all', 'active', 'past'];

    public function index(Request $request): View
    {
        $rows = HiredTalent::rows($request->user());

        $group = in_array($request->query('who'), self::GROUPS, true)
            ? $request->query('who')
            : 'all';

        $state = in_array($request->query('show'), self::STATES, true)
            ? $request->query('show')
            : 'all';

        $q = trim((string) $request->query('q', ''));

        $shown = $rows
            ->when($group !== 'all', fn ($c) => $c->filter(
                fn ($r) => $r['supplier']['noun'] === rtrim($group, 's'),
            ))
            ->when($state === 'active', fn ($c) => $c->reject(
                fn ($r) => in_array($r['raw_state'], ['completed', 'cancelled'], true),
            ))
            ->when($state === 'past', fn ($c) => $c->filter(
                fn ($r) => in_array($r['raw_state'], ['completed', 'cancelled'], true),
            ))
            ->when($q !== '', fn ($c) => $c->filter(fn ($r) => str_contains(
                mb_strtolower(($r['supplier']['name'] ?? '') . ' ' . ($r['event']['title'] ?? '') . ' ' . ($r['service'] ?? '')),
                mb_strtolower($q),
            )))
            ->values();

        return view('client.hired-talent.index', [
            'groups'  => $shown
                ->groupBy(fn ($r) => $r['event']['id'])
                ->map(fn ($g) => ['event' => $g->first()['event'], 'rows' => $g->values()])
                ->sortByDesc(fn ($g) => $g['event']['at']?->timestamp ?? 0)
                ->values(),
            'counts'  => HiredTalent::summary($rows),
            // How many of each kind there are in total, so the filter can say
            // "Influencers (0)" instead of looking broken when it is empty.
            'kinds'   => [
                'professionals' => $rows->where('supplier.noun', 'professional')->count(),
                'influencers'   => $rows->where('supplier.noun', 'influencer')->count(),
            ],
            'shown'   => $shown->count(),
            'group'   => $group,
            'state'   => $state,
            'q'       => $q,
        ]);
    }
}
