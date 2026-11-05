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
        $user = $request->user();

        $services = $user->serviceCategories()
            ->where('categories.kind', Category::SERVICE)
            ->with('parent:id,name')
            ->orderBy('categories.name')
            ->get();

        /*
         * Which of them are already carrying a package.
         *
         * Packages store the service NAME, not its id, so this matches on the
         * name. That is the shape the whole package side reads, and changing
         * it is a migration rather than a page.
         */
        $inPackages = \App\Models\Package::where('user_id', $user->id)
            ->pluck('services')
            ->flatMap(fn ($list) => is_array($list) ? $list : [])
            ->unique()
            ->values();

        $active   = $services->filter(fn ($s) => (bool) ($s->pivot->is_active ?? true));
        $priced   = $services->filter(fn ($s) => $s->pivot->price !== null);

        // Khadijah's screen groups the list under its parent categories rather
        // than running it flat, so a professional with thirty services is not
        // reading one long wall.
        $groups = $services
            ->groupBy(fn ($s) => $s->parent?->name ?: 'Other services')
            ->sortKeys();

        return view('professional.services.index', [
            'services'   => $services,
            'groups'     => $groups,
            'units'      => self::UNITS,
            'priced'     => $priced->count(),
            'inPackages' => $inPackages,
            'addable'    => $this->addable($services),
            'counts'     => [
                'total'    => $services->count(),
                'active'   => $active->count(),
                'inactive' => $services->count() - $active->count(),
                'packaged' => $services->filter(fn ($s) => $inPackages->contains($s->name))->count(),
                'quickbid' => $priced->count(),
            ],
        ]);
    }

    /**
     * Add a service to what this professional offers.
     *
     * Khadijah asked for this button on this page. Choosing services was only
     * ever possible on the profile, which meant leaving the pricing screen to
     * add the thing you came here to price.
     */
    public function add(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'category_id' => ['required', 'integer', 'exists:categories,id'],
        ]);

        $service = Category::where('id', $data['category_id'])
            ->where('kind', Category::SERVICE)
            ->first();

        // Only a level 3 service can be offered. An event type or a category
        // arriving here would put an occasion in somebody's services list.
        if (! $service) {
            return back()->withErrors(['category_id' => 'That is not a service you can offer.']);
        }

        $user = $request->user();

        if ($user->serviceCategories()->where('categories.id', $service->id)->exists()) {
            return back()->with('status', $service->name . ' is already on your list.');
        }

        $user->serviceCategories()->attach($service->id);

        return back()->with('status', $service->name . ' added. Give it a price so it can answer a request.');
    }

    /** Rest a service, or put it back, without losing what it costs. */
    public function toggle(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'category_id' => ['required', 'integer'],
        ]);

        $service = $request->user()->serviceCategories()
            ->where('categories.id', (int) $data['category_id'])
            ->first();

        if (! $service) {
            return back();
        }

        $nowActive = ! (bool) ($service->pivot->is_active ?? true);

        $request->user()->serviceCategories()
            ->updateExistingPivot($service->id, ['is_active' => $nowActive]);

        return back()->with('status', $nowActive
            ? $service->name . ' is active again and clients can see it.'
            : $service->name . ' is resting. Its price is kept, and clients will not see it.');
    }

    /**
     * The services they could add: every level 3 service they do not already
     * offer, named with its category so two services called "Lighting" under
     * different categories can be told apart.
     *
     * @param  \Illuminate\Support\Collection  $mine
     * @return array<int, array{id:int, label:string}>
     */
    private function addable($mine): array
    {
        $have = $mine->pluck('id')->all();

        return Category::where('kind', Category::SERVICE)
            ->whereNotIn('id', $have)
            ->with('parent:id,name')
            ->orderBy('name')
            ->get()
            ->map(fn ($s) => [
                'id'    => $s->id,
                // No spaced dash in anything a user reads; this list is a
                // dropdown they scroll through.
                'label' => $s->parent ? $s->parent->name . ' / ' . $s->name : $s->name,
            ])
            ->all();
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
