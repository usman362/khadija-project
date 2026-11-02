<?php

namespace App\Http\Controllers\Professional;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

/**
 * My Services & Pricing.
 *
 * A professional could always say which services they offer. What any of
 * them costs had nowhere to live, and three things were waiting on it:
 * Create a Package shows a price beside each service it asks them to pick,
 * Quick Bid answers a request with a number, and Sir Peter's rough budget
 * estimator was parked because "a price range per service is a field on
 * their profile and nobody has one".
 *
 * Only the services they already offer appear here. Choosing WHICH services
 * stays where it is, on the profile, so there are not two places to pick
 * them and no chance of the two disagreeing.
 */
class ProfessionalServicePricingController extends Controller
{
    /** The three a price can be read in, as the packages table uses them. */
    public const UNITS = [
        'from'   => 'Starting at',
        'flat'   => 'Flat rate',
        'hourly' => 'Per hour',
    ];

    public function index(Request $request): View
    {
        $services = $request->user()->serviceCategories()
            ->where('categories.kind', Category::SERVICE)
            ->orderBy('categories.name')
            ->get();

        return view('professional.services.index', [
            'services' => $services,
            'units'    => self::UNITS,
            'priced'   => $services->filter(fn ($s) => $s->pivot->price !== null)->count(),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'rows'                       => ['array'],
            'rows.*.price'               => ['nullable', 'numeric', 'min:0', 'max:1000000'],
            'rows.*.price_unit'          => ['nullable', 'string', 'in:' . implode(',', array_keys(self::UNITS))],
            'rows.*.duration_hours'      => ['nullable', 'integer', 'min:1', 'max:24'],
            'rows.*.extra_hour_price'    => ['nullable', 'numeric', 'min:0', 'max:100000'],
        ]);

        $user = $request->user();

        /*
         * Only services this professional actually offers. The ids arrive from
         * the form, so a hand-edited one must not be able to write a price
         * against a service they do not do, or against somebody else's row.
         */
        $theirs = $user->serviceCategories()
            ->where('categories.kind', Category::SERVICE)
            ->pluck('categories.id')
            ->all();

        $saved = 0;

        foreach ($data['rows'] ?? [] as $id => $row) {
            if (! in_array((int) $id, $theirs, true)) {
                continue;
            }

            // A row may arrive with any of its boxes missing, so every one
            // is read the same way: absent and empty both mean not given.
            $blank = fn (string $key) => ($row[$key] ?? '') === '' ? null : $row[$key];

            $price = $blank('price');

            $user->serviceCategories()->updateExistingPivot((int) $id, [
                'price'            => $price,
                // A unit on its own says nothing, so it only means something
                // once there is a price for it to describe.
                'price_unit'       => $price === null ? 'from' : ($row['price_unit'] ?? 'from'),
                'duration_hours'   => $blank('duration_hours'),
                'extra_hour_price' => $blank('extra_hour_price'),
            ]);

            if ($price !== null) {
                $saved++;
            }
        }

        return back()->with('status', $saved === 0
            ? 'Saved. None of your services has a price yet, so none of them can be quoted automatically.'
            : 'Saved. ' . $saved . ' of your services ' . ($saved === 1 ? 'has' : 'have') . ' a price.');
    }
}
