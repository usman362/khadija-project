<?php

namespace App\Http\Controllers\Client;

use App\Domain\HiredTalent;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
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
    /*
     * Khadijah, 21 October: "You can remove the My professionals webpage, as
     * per our discussion in the meeting its the same thing."
     *
     * Its "Worked With" tab was the same thing: people you have booked, which
     * is this page. Its "Saved" tab was not. Saving somebody is the heart on
     * Browse and the Favorites icon in the public header, and those are people
     * you have NOT hired, so nothing on this page would have shown them. The
     * page could not simply be deleted without taking a live feature with it,
     * so Saved moves here as a fourth group and the page goes.
     */
    private const GROUPS = ['all', 'professionals', 'influencers', 'saved'];
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

        /*
         * Saved people are not hired people, so they are not in rows() and
         * cannot be filtered out of it. Loaded only for their own tab.
         */
        $saved = $group === 'saved'
            ? $request->user()->savedProfessionals()
                ->excludingSelf($request->user())
                ->with(['profile', 'serviceCategories:id,name'])
                ->withAvg(['reviewsReceived as reviews_avg' => fn ($r) => $r->where('is_hidden', false)], 'rating')
                ->withCount(['reviewsReceived as reviews_count' => fn ($r) => $r->where('is_hidden', false)])
                ->when($q !== '', fn ($c) => $c->where('name', 'like', "%{$q}%"))
                ->get()
            : collect();

        return view('client.hired-talent.index', [
            'saved'   => $saved,
            'savedCount' => $request->user()->savedProfessionals()->excludingSelf($request->user())->count(),
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
            'shown'   => $group === 'saved' ? $saved->count() : $shown->count(),
            'group'   => $group,
            'state'   => $state,
            'q'       => $q,
        ]);
    }

    /**
     * Save somebody for later. The heart on Browse posts here.
     *
     * Moved from ClientSavedProfessionalController with the page it belonged
     * to. The route NAME is unchanged, which is why the heart on Browse and
     * the Favorites icon in the header needed no edit.
     */
    public function save(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'professional_id' => ['required', 'exists:users,id'],
            'note'            => ['nullable', 'string', 'max:200'],
        ]);

        $pro = User::findOrFail($data['professional_id']);

        // Never yourself: a saved self-reference would offer you a quote from
        // your own account.
        abort_if($pro->id === $request->user()->id, 422);

        $request->user()->savedProfessionals()->syncWithoutDetaching([
            $pro->id => ['note' => $data['note'] ?? null],
        ]);

        return back()->with('status', $pro->name . ' saved. They are under Saved in Hired Talent.');
    }

    public function unsave(Request $request, User $professional): RedirectResponse
    {
        $request->user()->savedProfessionals()->detach($professional->id);

        return back()->with('status', 'Removed from your saved list.');
    }
}
