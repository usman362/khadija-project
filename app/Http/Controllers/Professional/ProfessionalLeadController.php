<?php

namespace App\Http\Controllers\Professional;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Event;
use App\Models\LeadNote;
use App\Support\LeadPipeline;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Leads CRM, as Khadijah's 28 October screen lays it out: the five stages with
 * a count and a weighted value each, a searchable table of the leads
 * themselves, and the pipeline total, conversion rate and quick actions under
 * it.
 *
 * There is no leads table and there does not need to be. Every stage is read
 * off requests, bids and bookings that already exist; see App\Support\LeadPipeline
 * for which record puts a lead in which stage. The only thing stored is what
 * belongs to the professional alone: their private note on a lead, when they
 * last spoke to it and when they mean to chase it.
 *
 * Route: GET /professional/leads
 */
class ProfessionalLeadController extends Controller
{
    private const PER_PAGE = 6;

    public function index(Request $request): View
    {
        $me = $request->user();

        // One "now" for the whole page, so every age on it is counted from
        // the same instant rather than each row taking its own.
        $today = now();

        $all = LeadPipeline::forProfessional($me, $today);

        $filters = [
            'q'       => trim((string) $request->query('q', '')),
            'stage'   => (string) $request->query('stage', ''),
            'source'  => (string) $request->query('source', ''),
            'type'    => (string) $request->query('type', ''),
            'needs'   => $request->boolean('needs'),
        ];

        $shown = $this->filter($all, $filters);

        $page  = max(1, (int) $request->query('page', 1));
        $pages = max(1, (int) ceil($shown->count() / self::PER_PAGE));
        $page  = min($page, $pages);

        return view('professional.leads.index', [
            'leads'      => $shown->forPage($page, self::PER_PAGE)->values(),
            'shownTotal' => $shown->count(),
            'total'      => $all->count(),
            'page'       => $page,
            'pages'      => $pages,
            'filters'    => $filters,
            'today'      => $today,
            'summary'    => LeadPipeline::summary($all),
            /*
             * The same counts in the shape the funnel has always been
             * checked in. The rules behind them are the ones that matter and
             * they are pinned by tests: the stages partition the leads, a
             * request already pursued is not also a new lead, and the list
             * never shows more than the counters claim.
             */
            'stats'      => [
                'new'         => $all->where('stage', 'new')->count(),
                'proposal'    => $all->where('stage', 'proposal')->count(),
                'negotiation' => $all->where('stage', 'negotiation')->count(),
                'booked'      => $all->where('stage', 'booked')->count(),
                'lost'        => $all->where('stage', 'lost')->count(),
                'total'       => $all->count(),
            ],
            'conversion' => LeadPipeline::conversion($all),
            'pipeline'   => round($all->sum('weighted'), 2),
            'sources'    => $all->pluck('source')->unique()->sort()->values()->all(),
            'types'      => $all->pluck('type')->filter()->unique()->sort()->values()->all(),
            // Leads they said they would chase and have not.
            'overdue'    => $all->where('overdue', true)->count(),
            'lost'       => $all->where('stage', 'lost')->count(),
        ]);
    }

    /** Save the private note, the last contact, or the follow-up date. */
    public function saveNote(Request $request, Event $event): RedirectResponse
    {
        $me = $request->user();

        if (! $this->isTheirLead($me, $event)) {
            abort(404);
        }

        $data = $request->validate([
            'note'              => ['nullable', 'string', 'max:600'],
            'next_follow_up_on' => ['nullable', 'date'],
            'contacted'         => ['nullable', 'boolean'],
        ]);

        $note = LeadNote::firstOrNew(['supplier_id' => $me->id, 'event_id' => $event->id]);

        $note->supplier_id = $me->id;
        $note->event_id    = $event->id;
        $note->note        = $data['note'] ?: null;

        if ($request->has('next_follow_up_on')) {
            $note->next_follow_up_on = $data['next_follow_up_on'] ?: null;
        }

        // Ticking the box means "I have just spoken to them", so the time is
        // now rather than a date they have to type.
        if ($request->boolean('contacted')) {
            $note->last_contacted_at = now();
        }

        $note->save();

        return back()->with('status', 'Saved your note on this lead.');
    }

    /** The leads as they are filtered on screen, as a file. */
    public function export(Request $request): StreamedResponse
    {
        $me = $request->user();

        $rows = $this->filter(LeadPipeline::forProfessional($me), [
            'q'      => trim((string) $request->query('q', '')),
            'stage'  => (string) $request->query('stage', ''),
            'source' => (string) $request->query('source', ''),
            'type'   => (string) $request->query('type', ''),
            'needs'  => $request->boolean('needs'),
        ]);

        $name = 'leads-' . now()->format('Y-m-d') . '.csv';

        return response()->streamDownload(function () use ($rows) {
            $out = fopen('php://output', 'wb');

            fputcsv($out, ['Event', 'Client', 'Stage', 'Source', 'Location',
                           'Event date', 'Budget low', 'Budget high',
                           'Last contacted', 'Next follow up', 'Note']);

            foreach ($rows as $lead) {
                fputcsv($out, [
                    $lead['title'],
                    $lead['client'],
                    $lead['stage_label'],
                    $lead['source'],
                    $lead['where'],
                    $lead['when']?->format('Y-m-d'),
                    $lead['low'],
                    $lead['high'],
                    $lead['last_contacted']?->format('Y-m-d'),
                    $lead['next_follow_up']?->format('Y-m-d'),
                    $lead['note'],
                ]);
            }

            fclose($out);
        }, $name, ['Content-Type' => 'text/csv']);
    }

    /**
     * A lead is theirs when it is an open request anyone could take, or one
     * they have already acted on. Otherwise a note could be attached to
     * somebody else's booked job.
     */
    private function isTheirLead($me, Event $event): bool
    {
        $open = $event->is_published
            && $event->supplier_id === null
            && in_array($event->status, ['pending', 'published'], true);

        return $open
            || Booking::where('supplier_id', $me->id)->where('event_id', $event->id)->exists()
            || \App\Models\Bid::where('supplier_id', $me->id)->where('event_id', $event->id)->exists();
    }

    /** @param  \Illuminate\Support\Collection<int, array>  $leads */
    private function filter($leads, array $f)
    {
        return $leads
            ->when($f['q'] !== '', fn ($rows) => $rows->filter(function (array $lead) use ($f) {
                $hay = mb_strtolower(implode(' ', array_filter([
                    $lead['title'], $lead['client'], $lead['where'], $lead['type'],
                ])));

                return str_contains($hay, mb_strtolower($f['q']));
            }))
            ->when($f['stage'] !== '', fn ($rows) => $rows->where('stage', $f['stage']))
            ->when($f['source'] !== '', fn ($rows) => $rows->where('source', $f['source']))
            ->when($f['type'] !== '', fn ($rows) => $rows->where('type', $f['type']))
            // "Needs attention" is a promise they made and have not kept, not
            // a score: the follow-up date they set has passed.
            ->when($f['needs'], fn ($rows) => $rows->where('overdue', true))
            ->values();
    }
}
