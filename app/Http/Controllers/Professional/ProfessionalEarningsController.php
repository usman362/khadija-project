<?php

namespace App\Http\Controllers\Professional;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Support\Earnings;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\View\View;

class ProfessionalEarningsController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();

        return view('professional.earnings.index', [
            // Same source as the Transactions page — the two used to disagree
            // with each other on the same account at the same moment.
            'stats'    => Earnings::forProfessional($user),
            'earnings' => $this->loadEarnings($request),
            'search'   => (string) $request->query('search', ''),
        ]);
    }

    /**
     * The earning behind each booking. Shows the net figure, matching the
     * totals above — the gross is what the client paid, not what arrives.
     */
    private function loadEarnings(Request $request): LengthAwarePaginator
    {
        $user  = $request->user();
        $query = Booking::query()
            ->where('supplier_id', $user->id)
            ->with(['event:id,title', 'client:id,name']);

        if (($term = trim((string) $request->query('search', ''))) !== '') {
            $query->where(function ($q) use ($term) {
                $q->whereHas('event', fn ($e) => $e->where('title', 'like', "%{$term}%"))
                  ->orWhereHas('client', fn ($c) => $c->where('name', 'like', "%{$term}%"))
                  ->orWhere('status', 'like', "%{$term}%");
            });
        }

        return $query->latest('created_at')
            ->paginate(10)
            ->withQueryString()
            ->through(fn (Booking $b) => [
                'date'        => ($b->booked_at ?? $b->created_at)?->format('M d, Y') ?? '—',
                'description' => trim(($b->event?->title ?? 'Booking')
                    . ($b->client?->name ? ' · ' . $b->client->name : '')),
                'gross'       => (float) $b->price,
                'net'         => \App\Support\Commission::netOf((float) $b->price, $user),
                'status'      => ucfirst((string) $b->status),
            ]);
    }
}
