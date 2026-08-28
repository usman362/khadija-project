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

        /*
         * One table, three columns, every row the same width.
         *
         * It used to write rows of four different widths into one sheet — a
         * one-cell title, a four-cell date row, two-cell label/value pairs,
         * then a three-cell table of professionals. A spreadsheet lays them
         * all on the same grid, so nothing lined up under anything. Reported
         * in the 26 Aug walkthrough as "columns aren't aligned".
         *
         * Section / Item / Value holds all of it and stays square, and the
         * sections are still readable as sections because the first column
         * names them.
         */
        return response()->streamDownload(function () use ($report) {
            $out = fopen('php://output', 'w');

            /*
             * A UTF-8 byte-order mark.
             *
             * Excel assumes the local codepage without one, so "How
             * professionals see you — 3" came out as mojibake and an em-dash
             * broke the cell. Three bytes, and the file opens correctly by
             * double-click on both Windows and Mac.
             */
            fwrite($out, "\xEF\xBB\xBF");

            fputcsv($out, ['Section', 'Item', 'Value']);
            fputcsv($out, ['Report', 'From', $report['from']->toDateString()]);
            fputcsv($out, ['Report', 'To', $report['to']->toDateString()]);

            foreach ([
                'spend'    => 'Spend',
                'requests' => 'Requests',
                'standing' => 'How professionals see you',
            ] as $key => $heading) {
                foreach ($report[$key] as $label => $value) {
                    fputcsv($out, [$heading, ucwords(str_replace('_', ' ', $label)), $value ?? '']);
                }
            }

            foreach ($report['professionals'] as $row) {
                // Two figures per professional, one row each, so the Value
                // column holds one number and stays sortable.
                fputcsv($out, ['Professionals', $row['name'].' — bookings', $row['bookings']]);
                fputcsv($out, ['Professionals', $row['name'].' — spent', $row['spent']]);
            }

            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
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
