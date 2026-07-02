<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Client "Post an Event" — the guided 11-step booking journey (Peter's spec):
 * Event Information → Build Your Event → Service Details → Review & Search →
 * Results → Compare Packages → Customize Package → Package Combinations →
 * Checkout & Payment → Order Confirmed → Final Payment.
 *
 * Steps 1-4 collect the client's real request (persisted in session as they go).
 * Steps 5-11 (package matching / combinations / checkout / tracking) are
 * representative pending the live package-matching + escrow pipeline — the
 * markup mirrors the approved final design exactly.
 */
class PostEventController extends Controller
{
    /** The wizard steps, in order. key => label. */
    public const STEPS = [
        'event-info'   => 'Event Information',
        'build'        => 'Build Your Event',
        'service-details' => 'Service Details',
        'review-search' => 'Review & Search',
        'results'      => 'Results',
        'compare'      => 'Compare Packages',
        'customize'    => 'Customize Package',
        'combinations' => 'Package Combinations',
        'checkout'     => 'Checkout & Payment',
        'confirmed'    => 'Order Confirmed',
        'final-payment' => 'Final Payment',
    ];

    /** Shared view data every step needs (progress bar + summary rail). */
    private function shell(string $currentKey): array
    {
        $keys = array_keys(self::STEPS);
        return [
            'steps'   => self::STEPS,
            'current' => array_search($currentKey, $keys) + 1,   // 1-indexed
            'summary' => $this->summary(),
        ];
    }

    /** Event-at-a-glance summary carried across steps (session + sensible demo defaults). */
    private function summary(): array
    {
        return array_merge([
            'event_type' => 'Wedding',
            'date'       => 'Sep 20, 2026',
            'time'       => '5:00 PM – 11:00 PM',
            'location'   => 'Baltimore, MD',
            'venue'      => 'The Grand Pavilion',
            'guests'     => 150,
            'budget'     => '$8,000 – $10,000',
            'style'      => 'Elegant / Classic',
            'services'   => ['DJ / Entertainment', 'Photography', 'Videography', 'Décor & Design', 'Cake / Desserts', 'Bartending', 'Catering'],
        ], (array) session('post_event', []));
    }

    // ── Step 1 ──────────────────────────────────────────────────────────────
    public function eventInfo(Request $request): View
    {
        return view('client.post-event.event-info', $this->shell('event-info') + [
            'categories' => Category::query()->where('is_active', true)->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function storeEventInfo(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'event_type'   => ['nullable', 'string', 'max:120'],
            'start_time'   => ['nullable', 'string', 'max:20'],
            'end_time'     => ['nullable', 'string', 'max:20'],
            'venue'        => ['nullable', 'string', 'max:200'],
            'guests'       => ['nullable', 'integer', 'min:1', 'max:100000'],
            'budget'       => ['nullable', 'string', 'max:60'],
            'notes'        => ['nullable', 'string', 'max:1000'],
        ]);
        session()->put('post_event', array_merge((array) session('post_event', []), array_filter($data)));

        return redirect()->route('client.post-event.build');
    }

    // ── Steps 2-11 ─────────────────────────────────────────────────────────
    public function build(): View
    {
        return view('client.post-event.build', $this->shell('build') + [
            'services'    => $this->serviceCatalog(),
            'aiSuggested' => ['Photo Booth', 'Rentals', 'Transportation'],
        ]);
    }

    public function serviceDetails(): View
    {
        return view('client.post-event.service-details', $this->shell('service-details') + [
            'lines' => $this->serviceLines(),
            'match' => 92,
        ]);
    }

    public function reviewSearch(): View
    {
        return view('client.post-event.review-search', $this->shell('review-search') + [
            'lines' => $this->serviceLines(),
            'prefs' => [
                'Match whole packages that include multiple services',
                'Include a combination of packages if needed',
                'Show only verified & certified professionals',
                'Include add-on and upgrade options',
                'Allow AI to suggest matching services',
            ],
            'aiAdditions' => ['Photo Booth', 'Transportation', 'Event Planner / Coordinator'],
            'match' => 92,
        ]);
    }

    public function results(): View
    {
        return view('client.post-event.results', $this->shell('results') + [
            'packages' => $this->packages(),
        ]);
    }

    public function compare(): View
    {
        return view('client.post-event.compare', $this->shell('compare') + [
            'packages' => array_slice($this->packages(), 0, 3),
        ]);
    }

    public function customize(): View
    {
        return view('client.post-event.customize', $this->shell('customize') + [
            'package' => $this->packages()[0],
            'lines'   => $this->serviceLines(),
            'addons'  => $this->addons(),
        ]);
    }

    public function combinations(): View
    {
        return view('client.post-event.combinations', $this->shell('combinations') + [
            'combos' => $this->combinations_(),
        ]);
    }

    public function checkout(): View
    {
        return view('client.post-event.checkout', $this->shell('checkout') + [
            'order' => $this->order(),
        ]);
    }

    public function confirmed(): View
    {
        return view('client.post-event.confirmed', $this->shell('confirmed') + [
            'order' => $this->order(),
        ]);
    }

    public function finalPayment(): View
    {
        return view('client.post-event.final-payment', $this->shell('final-payment') + [
            'order' => $this->order(),
        ]);
    }

    // ── Representative data ──────────────────────────────────────────────────

    private function serviceCatalog(): array
    {
        return [
            ['name' => 'Photography',       'icon' => 'camera',   'selected' => true],
            ['name' => 'Videography',       'icon' => 'video',    'selected' => true],
            ['name' => 'Catering',          'icon' => 'utensils', 'selected' => true],
            ['name' => 'Bartending',        'icon' => 'glass',    'selected' => true],
            ['name' => 'Flowers / Florist', 'icon' => 'flower',   'selected' => false],
            ['name' => 'Cake / Desserts',   'icon' => 'cake',     'selected' => true],
            ['name' => 'Photo Booth',       'icon' => 'booth',    'selected' => false],
            ['name' => 'Live Band / Musicians', 'icon' => 'music', 'selected' => false],
            ['name' => 'Rentals',           'icon' => 'chair',    'selected' => false],
            ['name' => 'Event Staffing',    'icon' => 'users',    'selected' => false],
            ['name' => 'Security',          'icon' => 'shield',   'selected' => false],
            ['name' => 'Event Planner / Coordinator', 'icon' => 'clipboard', 'selected' => false],
        ];
    }

    private function serviceLines(): array
    {
        return [
            ['service' => 'DJ / Entertainment', 'required' => true,  'coverage' => '6 Hours', 'package' => 'Reception',   'budget' => '$500 – $2,000'],
            ['service' => 'Photography',        'required' => false, 'coverage' => '8 Hours', 'package' => 'Album Print', 'budget' => '$1,500 – $5,000'],
            ['service' => 'Videography',        'required' => false, 'coverage' => 'Entire',  'package' => 'Highlight Film', 'budget' => '$1,000 – $4,000'],
            ['service' => 'Bartending',         'required' => false, 'coverage' => '2 Bartenders', 'package' => 'Service', 'budget' => '$700 – $2,000'],
            ['service' => 'Décor & Design',     'required' => false, 'coverage' => 'Elegant', 'package' => 'Full Venue',  'budget' => '$1,500 – $5,000'],
            ['service' => 'Cake / Desserts',    'required' => false, 'coverage' => '150 Guests', 'package' => 'Tiered Cake', 'budget' => '$500 – $1,200'],
        ];
    }

    private function packages(): array
    {
        return [
            ['name' => 'Elite Wedding Experience Package', 'vendor' => 'Elite Events Co.', 'rating' => 4.9, 'reviews' => 128, 'price' => 9450, 'match' => 96, 'tier' => 'Excellent Match', 'badge' => 'Best Match', 'services' => ['DJ / Entertainment', 'Photography', 'Videography', 'Décor & Design', 'Cake / Desserts'], 'img' => 'photo-1519741497674-611481863552'],
            ['name' => 'Luxury Celebration Package', 'vendor' => 'Premier Events', 'rating' => 4.8, 'reviews' => 76, 'price' => 8250, 'match' => 83, 'tier' => 'Good Match', 'badge' => 'Top Pick', 'services' => ['Photography', 'Videography', 'Catering', 'Bartending'], 'img' => 'photo-1464366400600-7168b8af9bc3'],
            ['name' => 'Signature Wedding Package', 'vendor' => 'Signature Events', 'rating' => 4.7, 'reviews' => 54, 'price' => 7900, 'match' => 87, 'tier' => 'Good Match', 'badge' => 'Good Value', 'services' => ['DJ / Entertainment', 'Photography', 'Bartending', 'Catering'], 'img' => 'photo-1511578314322-379afb476865'],
            ['name' => 'Classic Wedding Package', 'vendor' => 'Timeless Events', 'rating' => 4.6, 'reviews' => 41, 'price' => 6750, 'match' => 74, 'tier' => 'Fair Match', 'badge' => null, 'services' => ['Photography', 'Catering', 'Décor & Design'], 'img' => 'photo-1469371670807-013ccf25f16a'],
        ];
    }

    private function addons(): array
    {
        return [
            ['name' => 'Live Band', 'meta' => '4 Hours', 'price' => 1800, 'img' => 'photo-1501281668745-f7f57925c3b4'],
            ['name' => 'Extra Hour', 'meta' => 'Additional Hour', 'price' => 190, 'img' => 'photo-1519671482749-fd09be7ccebf'],
            ['name' => 'Transportation', 'meta' => 'Luxury Shuttle', 'price' => 600, 'img' => 'photo-1549194388-f61be84a6e9e'],
            ['name' => 'Day-of Coordinator', 'meta' => '8 Hours', 'price' => 400, 'img' => 'photo-1511795409834-ef04bbd61622'],
        ];
    }

    private function combinations_(): array
    {
        return [
            ['label' => 'Best Match', 'total' => 13400, 'match' => 100, 'note' => 'All 7 requested services covered', 'packages' => [
                ['name' => 'Elite Wedding Experience Package', 'vendor' => 'Elite Events Co.', 'price' => 11450, 'img' => 'photo-1519741497674-611481863552'],
                ['name' => 'Luxury Floral & Cake Collection', 'vendor' => 'Bloom & Co.', 'price' => 1950, 'img' => 'photo-1464366400600-7168b8af9bc3'],
            ]],
            ['label' => 'Best Value', 'total' => 9100, 'match' => 100, 'note' => 'Within your budget', 'packages' => [
                ['name' => 'Signature Wedding Package', 'vendor' => 'Signature Events', 'price' => 8250, 'img' => 'photo-1511578314322-379afb476865'],
                ['name' => 'Photo Booth & Lighting Package', 'vendor' => 'Glow Studio', 'price' => 850, 'img' => 'photo-1530103862676-de8c9debad1d'],
            ]],
            ['label' => 'Budget Friendly', 'total' => 8550, 'match' => 100, 'note' => 'Lowest total', 'packages' => [
                ['name' => 'Signature Wedding Package', 'vendor' => 'Signature Events', 'price' => 7900, 'img' => 'photo-1469371670807-013ccf25f16a'],
                ['name' => 'Premium Transportation Service', 'vendor' => 'LuxRide', 'price' => 650, 'img' => 'photo-1549194388-f61be84a6e9e'],
            ]],
        ];
    }

    private function order(): array
    {
        return [
            'number'    => 'GR-2026-0920-1558',
            'placed'    => 'May 15, 2026 at 10:42 AM',
            'combo'     => 'Elite Wedding Experience + Luxury Floral & Cake Collection',
            'vendors'   => ['Elite Events Co.', 'Bloom & Co.'],
            'match'     => 100,
            'img'       => 'photo-1519741497674-611481863552',
            'services'  => ['DJ / Entertainment', 'Photography', 'Videography', 'Bartending', 'Décor & Design', 'Cake / Desserts', 'Catering'],
            'lineItems' => [
                ['label' => 'DJ / Entertainment', 'amount' => 1750],
                ['label' => 'Photography', 'amount' => 2200],
                ['label' => 'Videography', 'amount' => 2000],
                ['label' => 'Bartending', 'amount' => 900],
                ['label' => 'Decor & Design', 'amount' => 1600],
                ['label' => 'Cake / Desserts', 'amount' => 650],
                ['label' => 'Catering', 'amount' => 2350],
            ],
            'addons'      => [['label' => 'Photo Booth', 'amount' => 450], ['label' => 'Uplighting', 'amount' => 350]],
            'packageTotal' => 13400,
            'addonsTotal'  => 800,
            'subtotal'     => 14250,
            'tax'          => 852,
            'total'        => 15052,
            'deposit'      => 4516,
            'remaining'    => 10536,
            'depositPaidDate' => 'May 15, 2026',
            'balanceDue'   => 'Aug 20, 2026',
        ];
    }
}
