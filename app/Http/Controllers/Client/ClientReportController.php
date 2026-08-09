<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Support\Reports\ClientReport;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * A client's own report — Route: GET /client/reports
 */
class ClientReportController extends Controller
{
    private const RANGES = [
        '90'  => 'Last 90 days',
        '365' => 'Last 12 months',
        'all' => 'All time',
    ];

    public function index(Request $request): View
    {
        [$from, $to, $range] = $this->range($request);

        return view('client.reports.index', [
            'report' => (new ClientReport($request->user(), $from, $to))->all(),
            'ranges' => self::RANGES,
            'range'  => $range,
        ]);
    }

    public function csv(Request $request): StreamedResponse
    {
        [$from, $to] = $this->range($request);
        $report = (new ClientReport($request->user(), $from, $to))->all();

        $filename = 'my-events-report-' . $from->toDateString() . '-to-' . $to->toDateString() . '.csv';

        return response()->streamDownload(function () use ($report) {
            $out = fopen('php://output', 'w');

            fputcsv($out, ['Your GigResource report']);
            fputcsv($out, ['From', $report['from']->toDateString(), 'To', $report['to']->toDateString()]);
            fputcsv($out, []);

            foreach (['spend' => 'Spend', 'requests' => 'Requests', 'standing' => 'How professionals see you'] as $key => $heading) {
                fputcsv($out, [$heading]);
                foreach ($report[$key] as $label => $value) {
                    fputcsv($out, [ucwords(str_replace('_', ' ', $label)), $value ?? '—']);
                }
                fputcsv($out, []);
            }

            fputcsv($out, ['Professional', 'Bookings', 'Spent']);
            foreach ($report['professionals'] as $row) {
                fputcsv($out, [$row['name'], $row['bookings'], $row['spent']]);
            }

            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv']);
    }

    /** @return array{0: Carbon, 1: Carbon, 2: string} */
    private function range(Request $request): array
    {
        $range = (string) $request->query('range', '365');
        $range = array_key_exists($range, self::RANGES) ? $range : '365';

        $from = $range === 'all'
            ? Carbon::parse($request->user()->created_at)
            : now()->subDays((int) $range);

        return [$from->startOfDay(), now()->endOfDay(), $range];
    }
}
