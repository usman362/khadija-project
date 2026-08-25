<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\AgreementLog;
use App\Models\Booking;
use App\Models\Event;
use App\Models\Package;
use App\Models\User;
use App\Notifications\ProposalReceived;
use App\Support\Availability;
use App\Support\StateMatching;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/**
 * Buying a package.
 *
 * Until now a package had a price, a page and a "Request this Package" button
 * that dropped the client into the Direct Request form — a different product,
 * with the package's own price nowhere in it. The package was browsable but
 * not buyable.
 *
 * This is the purchase path. What it deliberately does NOT do:
 *
 *  - It does not invent tiers or priced add-ons. The Owner has not decided
 *    those, so the client is charged the package's own listed price and
 *    nothing else. When tiers land, they add lines here; they do not change
 *    the shape of the flow.
 *  - It does not take payment. There is no payment provider wired to this app,
 *    so the confirmation screen says what is owed and how it will be
 *    collected, and never implies money has moved.
 *  - It does not auto-confirm. "Instant book" means the client does not have
 *    to write a brief and wait for a quote — the price and scope are already
 *    fixed. It cannot mean the professional is committed before they have seen
 *    the date: that would be this codebase's oldest bug, a screen promising
 *    something the other side never agreed to. So the booking is created at
 *    `requested`, the professional is notified, and the existing
 *    requested→confirmed transition finishes the job.
 *
 * Routes:
 *   GET  /client/packages/{package}/book  → create()
 *   POST /client/packages/{package}/book  → store()
 *   GET  /client/packages/booked/{booking} → confirmation()
 */
class ClientPackageBookingController extends Controller
{
    /** A package is bookable only if it is live and in the client's own state (R38). */
    private function assertBookable(Package $package, ?User $client): void
    {
        $live = ($package->status ?? ($package->is_active ? 'active' : 'draft')) === 'active';
        abort_unless($live, 404);

        // The client cannot buy their own package, and cannot buy across states.
        abort_if($client && $client->id === $package->user_id, 403, 'This is your own package.');

        $pro = User::find($package->user_id);
        abort_unless($pro, 404);

        if (! StateMatching::allows($client, $pro)) {
            abort(403, 'This package is offered in '.($package->state ?: 'another state').'. GigResource matches within a state.');
        }
    }

    public function create(Request $request, Package $package): View
    {
        $client = $request->user();
        $this->assertBookable($package, $client);

        $package->load(['category:id,name,slug', 'user:id,name', 'user.profile:user_id,city,state,headline,company_name']);

        $pro = $package->user;

        // The professional's own calendar. Wording matters: a date with nothing
        // on it means nothing is booked ON GIGRESOURCE — it is not a claim that
        // the professional is free.
        $busy = $pro ? Availability::busyDates($pro) : [];

        $chosen = $request->query('date');
        $chosenBusy = $chosen && in_array($chosen, $busy, true);

        return view('client.packages.book', compact('package', 'pro', 'busy', 'chosen', 'chosenBusy'));
    }

    public function store(Request $request, Package $package): RedirectResponse
    {
        $client = $request->user();
        $this->assertBookable($package, $client);

        $validated = $request->validate([
            'event_title' => ['required', 'string', 'max:255'],
            'date'        => ['required', 'date', 'after_or_equal:today'],
            'location'    => ['nullable', 'string', 'max:255'],
            'guests'      => ['nullable', 'integer', 'min:1', 'max:100000'],
            'notes'       => ['nullable', 'string', 'max:1000'],
            'agree'       => ['accepted'],
        ], [
            'agree.accepted' => 'Please confirm you have read what is included before booking.',
            'date.after_or_equal' => 'Pick a date that has not already passed.',
        ]);

        $date = Carbon::parse($validated['date'])->startOfDay();

        // One package, one professional, one date — booking the same package
        // twice for the same day is a double-submit, not a second order.
        $duplicate = Booking::where('client_id', $client->id)
            ->where('supplier_id', $package->user_id)
            ->whereIn('status', ['requested', 'confirmed'])
            ->whereDate('booked_at', $date)
            ->exists();

        if ($duplicate) {
            return back()
                ->withInput()
                ->withErrors(['date' => 'You already have a request with this professional on that date.']);
        }

        $booking = DB::transaction(function () use ($client, $package, $validated, $date) {
            /*
             * A booking needs an event. For a package purchase the client never
             * wrote a brief, so the event is built from what they did give us
             * and is NOT published — nobody bids on a package that is already
             * priced and awarded to one professional.
             */
            $event = Event::create([
                'client_id'    => $client->id,
                'created_by'   => $client->id,
                'title'        => $validated['event_title'],
                'starts_at'    => $date,
                'location'     => $validated['location'] ?? null,
                'guest_count'  => $validated['guests'] ?? null,
                'status'       => 'confirmed',
                'is_published' => false,
                'source'       => 'package',
                'state'        => $package->state,
                'category_id'  => $package->category_id,
            ]);

            $booking = Booking::create([
                'event_id'    => $event->id,
                'category_id' => $package->category_id,
                'client_id'   => $client->id,
                'supplier_id' => $package->user_id,
                'created_by'  => $client->id,
                'status'      => 'requested',
                'price'       => $package->price,
                'currency'    => 'USD',
                'booked_at'   => $date,
                'source'      => 'package',
                'notes'       => trim(
                    'Package: '.$package->title
                    .(($validated['notes'] ?? null) ? "\n\n".$validated['notes'] : '')
                ),
            ]);

            AgreementLog::create([
                'subject_type' => 'booking',
                'subject_id'   => $booking->id,
                'from_status'  => null,
                'to_status'    => 'requested',
                'changed_by'   => $client->id,
            ]);

            return $booking;
        });

        if ($pro = User::find($package->user_id)) {
            $pro->notify(new ProposalReceived($booking));
        }

        return redirect()->route('client.packages.booked', $booking);
    }

    public function confirmation(Request $request, Booking $booking): View
    {
        abort_unless($booking->client_id === $request->user()->id, 403);

        $booking->load(['event:id,title,starts_at,location', 'supplier:id,name', 'supplier.profile:user_id,city,state,headline,company_name']);

        return view('client.packages.booked', compact('booking'));
    }
}
