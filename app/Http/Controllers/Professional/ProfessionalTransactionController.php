<?php

namespace App\Http\Controllers\Professional;

use App\Http\Controllers\Controller;
use App\Models\Booking;
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

        // Real earnings summary from this professional's bookings.
        $base  = Booking::where('supplier_id', $request->user()->id);
        $earned = (float) (clone $base)->where('status', 'completed')->sum('price');

        // Payout ledger — withdrawn = paid out, held = still-requested.
        $payoutBase = \App\Models\Payout::where('user_id', $request->user()->id);
        $withdrawn  = (float) (clone $payoutBase)->where('status', 'paid')->sum('amount');
        $requested  = (float) (clone $payoutBase)->where('status', 'requested')->sum('amount');

        $stats = [
            'total'     => (clone $base)->count(),
            'earned'    => $earned,
            'pending'   => (float) (clone $base)->whereIn('status', ['pending', 'confirmed'])->sum('price'),
            'withdrawn' => $withdrawn,
            // What the pro can still withdraw: earned minus paid-out minus in-flight requests.
            'available' => max(0.0, $earned - $withdrawn - $requested),
        ];

        $payouts = (clone $payoutBase)->latest()->take(20)->get();

        return view('professional.transactions.index', [
            'stats'          => $stats,
            'transactions'   => $transactions,
            'activity'       => $activity,
            'filters'        => $filters,
            'contentFilters' => self::CONTENT_FILTERS,
            'payouts'        => $payouts,
        ]);
    }

    /**
     * Request a payout (withdrawal) against the available balance.
     * Recorded as 'requested'; an admin/gateway marks it 'paid' later.
     */
    public function requestPayout(Request $request): \Illuminate\Http\RedirectResponse
    {
        $data = $request->validate([
            'amount' => ['required', 'integer', 'min:1'],
            'method' => ['nullable', 'string', 'max:40'],
        ]);

        $base       = Booking::where('supplier_id', $request->user()->id);
        $earned     = (float) (clone $base)->where('status', 'completed')->sum('price');
        $payoutBase = \App\Models\Payout::where('user_id', $request->user()->id);
        $withdrawn  = (float) (clone $payoutBase)->where('status', 'paid')->sum('amount');
        $requested  = (float) (clone $payoutBase)->where('status', 'requested')->sum('amount');
        $available  = max(0.0, $earned - $withdrawn - $requested);

        if ($data['amount'] > $available) {
            return back()->withErrors([
                'amount' => 'That exceeds your available balance of $' . number_format($available, 2) . '.',
            ]);
        }

        \App\Models\Payout::create([
            'user_id'      => $request->user()->id,
            'amount'       => $data['amount'],
            'currency'     => 'USD',
            'method'       => $data['method'] ?? null,
            'status'       => 'requested',
            'requested_at' => now(),
        ]);

        return back()->with('status', 'Payout of $' . number_format($data['amount']) . ' requested — you\'ll be notified once it\'s processed.');
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
     * Real transactions = this professional's bookings. Each booking becomes a
     * transaction row (its price is the professional's earning). Supports the
     * search / status / date-range filters and paginates 10 per page. A
     * dedicated Transaction/payout table can slot in later without touching the
     * view contract.
     */
    private function loadTransactions(Request $request, array $filters): LengthAwarePaginator
    {
        $query = Booking::query()
            ->where('supplier_id', $request->user()->id)
            ->with(['event:id,title', 'client:id,name']);

        if (($filters['status'] ?? '') !== '') {
            $query->where('status', $filters['status']);
        }
        if (($filters['date_from'] ?? '') !== '') {
            $query->whereDate('created_at', '>=', $filters['date_from']);
        }
        if (($filters['date_to'] ?? '') !== '') {
            $query->whereDate('created_at', '<=', $filters['date_to']);
        }
        if (($filters['search'] ?? '') !== '') {
            $term = $filters['search'];
            $query->where(function ($q) use ($term) {
                $q->whereHas('event', fn ($e) => $e->where('title', 'like', "%{$term}%"))
                  ->orWhereHas('client', fn ($c) => $c->where('name', 'like', "%{$term}%"))
                  ->orWhere('status', 'like', "%{$term}%");
            });
        }

        return $query->latest('created_at')
            ->paginate(10, ['*'], 'page', (int) $request->query('page', 1))
            ->through(fn (Booking $b) => [
                'id'          => $b->id,
                'date'        => ($b->booked_at ?? $b->created_at)?->format('M d, Y') ?? '—',
                'type'        => 'Booking',
                'description' => trim(($b->event?->title ?? 'Booking')
                    . ($b->client?->name ? ' · ' . $b->client->name : '')),
                'amount'      => (float) $b->price,
                'status'      => ucfirst((string) $b->status),
            ]);
    }

    /**
     * Activity feed — a higher-level log built from the professional's recent
     * bookings ("Booking confirmed", etc.). Paginated via a separate page param
     * so it doesn't collide with the transactions table.
     */
    private function loadActivity(Request $request, array $filters): LengthAwarePaginator
    {
        $page    = (int) $request->query('activity_page', 1);
        $perPage = 10;

        $items = Booking::query()
            ->where('supplier_id', $request->user()->id)
            ->with(['event:id,title', 'client:id,name'])
            ->latest('created_at')
            ->get()
            ->map(fn (Booking $b) => [
                'title' => 'Booking ' . strtolower((string) $b->status)
                    . ($b->event?->title ? ' — ' . $b->event->title : ''),
                'meta'  => trim(($b->client?->name ? $b->client->name . ' · ' : '')
                    . (($b->booked_at ?? $b->created_at)?->diffForHumans() ?? '')),
            ])
            ->values();

        return new LengthAwarePaginator(
            items: $items->forPage($page, $perPage)->values(),
            total: $items->count(),
            perPage: $perPage,
            currentPage: $page,
            options: [
                'path'     => $request->url(),
                'pageName' => 'activity_page',
                'query'    => $request->query(),
            ],
        );
    }
}
