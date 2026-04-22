<?php

namespace App\Http\Controllers\Professional;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Professional → Transactions page.
 *
 * Data source note:
 *   We don't yet store "transactions" for professionals in a dedicated table —
 *   this controller is the UI scaffold from client feedback (pagination,
 *   activity feed, export CSV/PDF, content filters). The methods here return
 *   empty paginators, but the view structure + controller contract are in
 *   place so wiring real queries later is a drop-in.
 */
class ProfessionalTransactionController extends Controller
{
    /**
     * Filter categories the "Filter by content" dropdown offers.
     * These mirror the client's annotation: "by events, services,
     * professionals names, and anything".
     */
    private const CONTENT_FILTERS = [
        'all'           => 'All content',
        'events'        => 'Events',
        'services'      => 'Services',
        'professionals' => 'Professionals',
        'clients'       => 'Clients',
        'bookings'      => 'Bookings',
        'payouts'       => 'Payouts',
    ];

    public function index(Request $request): View
    {
        $filters = [
            'search'       => (string) $request->query('search', ''),
            'date_from'    => (string) $request->query('date_from', ''),
            'date_to'      => (string) $request->query('date_to', ''),
            'status'       => (string) $request->query('status', ''),
            'content_type' => (string) $request->query('content_type', 'all'),
        ];

        $transactions = $this->loadTransactions($request, $filters);
        $activity     = $this->loadActivity($request, $filters);

        $stats = [
            'total'     => 0,
            'earned'    => 0,
            'withdrawn' => 0,
            'pending'   => 0,
        ];

        return view('professional.transactions.index', [
            'stats'          => $stats,
            'transactions'   => $transactions,
            'activity'       => $activity,
            'filters'        => $filters,
            'contentFilters' => self::CONTENT_FILTERS,
        ]);
    }

    /**
     * Export the currently-filtered transactions as CSV.
     *
     * Streams the response so large exports don't buffer the whole file in
     * memory. Headers include UTF-8 BOM so Excel opens it correctly.
     */
    public function exportCsv(Request $request): StreamedResponse
    {
        $filename = 'transactions-' . now()->format('Y-m-d-His') . '.csv';

        return response()->streamDownload(function () use ($request) {
            $handle = fopen('php://output', 'w');
            // UTF-8 BOM for Excel
            fwrite($handle, "\xEF\xBB\xBF");
            fputcsv($handle, ['ID', 'Date', 'Type', 'Description', 'Amount', 'Status']);

            foreach ($this->loadTransactions($request, $request->query())->items() as $txn) {
                fputcsv($handle, [
                    $txn['id'] ?? '',
                    $txn['date'] ?? '',
                    $txn['type'] ?? '',
                    $txn['description'] ?? '',
                    $txn['amount'] ?? '',
                    $txn['status'] ?? '',
                ]);
            }

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    /**
     * Export the currently-filtered transactions as a simple PDF-ish HTML
     * document. Browsers print this to PDF via `Ctrl+P → Save as PDF`.
     *
     * Returns a printable HTML page with auto-print JS; no external PDF
     * library required. If the project later adds dompdf/Browsershot, swap
     * this method for a true PDF stream.
     */
    public function exportPdf(Request $request): Response
    {
        $transactions = $this->loadTransactions($request, $request->query())->items();

        $html = view('professional.transactions.export-pdf', [
            'transactions' => $transactions,
            'generatedAt'  => now(),
        ])->render();

        return response($html, 200, [
            'Content-Type' => 'text/html; charset=UTF-8',
        ]);
    }

    /**
     * Placeholder data loader. Returns an empty paginator with metadata wired
     * up so the pagination strip renders. Replace the empty collection with
     * a real query once a Transaction model exists.
     */
    private function loadTransactions(Request $request, array $filters): LengthAwarePaginator
    {
        return new LengthAwarePaginator(
            items: new Collection([]),
            total: 0,
            perPage: 10,
            currentPage: (int) $request->query('page', 1),
            options: [
                'path'     => $request->url(),
                'pageName' => 'page',
                'query'    => $request->query(),
            ],
        );
    }

    /**
     * Placeholder activity feed (separate from transactions). Activity is a
     * higher-level log: "Booking confirmed", "Proposal sent", etc. Paginated
     * via a separate page param so it doesn't collide with transactions.
     */
    private function loadActivity(Request $request, array $filters): LengthAwarePaginator
    {
        return new LengthAwarePaginator(
            items: new Collection([]),
            total: 0,
            perPage: 10,
            currentPage: (int) $request->query('activity_page', 1),
            options: [
                'path'     => $request->url(),
                'pageName' => 'activity_page',
                'query'    => $request->query(),
            ],
        );
    }
}
