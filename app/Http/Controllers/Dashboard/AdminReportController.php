<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Support\Reports\AdminReport;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Admin reporting — the marketplace, over a date range.
 *
 * Route: GET /app/admin/reports
 */
class AdminReportController extends Controller
{
    /** The ranges the page offers, and what each means in days. */
    private const RANGES = [
        '7'   => 'Last 7 days',
        '30'  => 'Last 30 days',
        '90'  => 'Last 90 days',
        '365' => 'Last 12 months',
        'all' => 'All time',
    ];

    public function index(Request $request): View
    {
        [$from, $to, $range] = $this->range($request);

        return view('dashboard.admin.reports', [
            'report' => (new AdminReport($from, $to))->all(),
            'ranges' => self::RANGES,
            'range'  => $range,
        ]);
    }

    /**
     * The same figures as a file.
     *
     * Streamed rather than built in memory — a report is the one thing on the
     * platform that gets asked for over "all time".
     */
    public function csv(Request $request): StreamedResponse
    {
        [$from, $to] = $this->range($request);
        $report = (new AdminReport($from, $to))->all();

        $filename = 'gigresource-report-' . $from->toDateString() . '-to-' . $to->toDateString() . '.csv';

        return response()->streamDownload(function () use ($report) {
            $out = fopen('php://output', 'w');

            fputcsv($out, ['GigResource report']);
            fputcsv($out, ['From', $report['from']->toDateString(), 'To', $report['to']->toDateString()]);
            fputcsv($out, []);

            foreach (['money' => 'Money', 'marketplace' => 'Marketplace', 'people' => 'People',
                      'needs_attention' => 'Needs attention'] as $key => $heading) {
                fputcsv($out, [$heading]);
                foreach ($report[$key] as $label => $value) {
                    fputcsv($out, [ucwords(str_replace('_', ' ', $label)), $value ?? '—']);
                }
                fputcsv($out, []);
            }

            fputcsv($out, ['By state']);
            fputcsv($out, ['State', 'Gigs posted', 'Professionals', 'Revenue']);
            foreach ($report['by_state'] as $row) {
                fputcsv($out, [$row['state'], $row['gigs'], $row['professionals'], $row['revenue']]);
            }

            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv']);
    }

    /** @return array{0: Carbon, 1: Carbon, 2: string} */
    private function range(Request $request): array
    {
        $range = (string) $request->query('range', '30');
        $range = array_key_exists($range, self::RANGES) ? $range : '30';

        // "All time" starts at the first record rather than at some arbitrary
        // early date, so the range shown on the page is the range that has
        // data in it.
        $from = $range === 'all'
            ? Carbon::parse(\App\Models\User::min('created_at') ?? now()->subYear())
            : now()->subDays((int) $range);

        return [$from->startOfDay(), now()->endOfDay(), $range];
    }
}
