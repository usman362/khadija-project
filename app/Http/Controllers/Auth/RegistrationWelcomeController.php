<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Support\ServiceArea;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * The one screen that tells a new user whether we operate where they are.
 *
 * Registration itself never mentions the launch area — not in the dropdown,
 * not in the hint, not in a validation message. The answer is given here,
 * once, after the account already exists (Peter, 2026-07-30).
 */
class RegistrationWelcomeController extends Controller
{
    public function show(Request $request): View
    {
        $user    = $request->user();
        $profile = $user->profile;

        return view('auth.welcome', [
            'user'      => $user,
            'supported' => ($profile?->service_area_status ?? ServiceArea::COMING_SOON) === ServiceArea::SUPPORTED,
            'where'     => ServiceArea::describe($profile?->city, $profile?->state, $profile?->country),
            'optedIn'   => (bool) $profile?->expansion_opt_in,
        ]);
    }

    /**
     * The "tell me when you launch here" checkbox. Its own endpoint so the
     * choice can be changed later from account settings with the same call.
     */
    public function optIn(Request $request): RedirectResponse
    {
        $data = $request->validate(['expansion_opt_in' => ['nullable', 'boolean']]);

        $request->user()->getOrCreateProfile()->update([
            'expansion_opt_in' => (bool) ($data['expansion_opt_in'] ?? false),
        ]);

        return back()->with('status', ($data['expansion_opt_in'] ?? false)
            ? "We'll email you as soon as GigResource opens in your area."
            : 'Preference saved.');
    }
}
