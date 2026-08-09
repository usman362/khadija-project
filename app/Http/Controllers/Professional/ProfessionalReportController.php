<?php

namespace App\Http\Controllers\Professional;

use App\Http\Controllers\Controller;
use App\Support\Reports\ProfessionalReport;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * A professional's own report — Route: GET /professional/reports
 */
class ProfessionalReportController extends Controller
{
    private const RANGES = [
        '30'  => 'Last 30 days',
        '90'  => 'Last 90 days',
        '365' => 'Last 12 months',
        'all' => 'All time',
    ];

    public function index(Request $request): View
    {
        [$from, $to, $range] = $this->range($request);

        return view('professional.reports.index', [
            'report' => (new ProfessionalReport($request->user(), $from, $to))->all(),
            'ranges' => self::RANGES,
            'range'  => $range,
        ]);
    }

    public function csv(Request $request): StreamedResponse
    {
        [$from, $to] = $this->range($request);
        $report = (new ProfessionalReport($request->user(), $from, $to))->all();

        $filename = 'my-report-' . $from->toDateString() . '-to-' . $to->toDateString() . '.csv';

        return response()->streamDownload(function () use ($report) {
            $out = fopen('php://output', 'w');

            fputcsv($out, ['Your GigResource report']);
            fputcsv($out, ['From', $report['from']->toDateString(), 'To', $report['to']->toDateString()]);
            fputcsv($out, []);

            foreach (['bidding' => 'Bidding', 'money' => 'Money', 'reputation' => 'Reputation'] as $key => $heading) {
                fputcsv($out, [$heading]);
                foreach ($report[$key] as $label => $value) {
                    fputcsv($out, [ucwords(str_replace('_', ' ', $label)), $value ?? '—']);
                }
                fputcsv($out, []);
            }

            fputcsv($out, ['Month', 'Earned', 'Bookings']);
            foreach ($report['over_time'] as $row) {
                fputcsv($out, [$row['month'], $row['earned'], $row['bookings']]);
            }

            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv']);
    }

    /** @return array{0: Carbon, 1: Carbon, 2: string} */
    private function range(Request $request): array
    {
        $range = (string) $request->query('range', '90');
        $range = array_key_exists($range, self::RANGES) ? $range : '90';

        // "All time" starts when this account did, not at an arbitrary date,
        // so the months shown are months they could have earned in.
        $from = $range === 'all'
            ? Carbon::parse($request->user()->created_at)
            : now()->subDays((int) $range);

        return [$from->startOfDay(), now()->endOfDay(), $range];
    }
}
