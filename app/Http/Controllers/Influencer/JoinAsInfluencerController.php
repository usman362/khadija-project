<?php

namespace App\Http\Controllers\Influencer;

use App\Domain\Influencer\Contracts\InfluencerServiceInterface;
use App\Domain\Influencer\DataTransferObjects\InfluencerApplicationData;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class JoinAsInfluencerController extends Controller
{
    public function __construct(private readonly InfluencerServiceInterface $service)
    {
    }

    public function show(): View|RedirectResponse
    {
        // Already applied? Send them to their status page instead of the signup form.
        if (Auth::check() && Auth::user()->influencer) {
            return redirect()->route('influencer.status');
        }

        $tiers = config('influencer.tiers', []);
        return view('influencer.join', compact('tiers'));
    }

    /**
     * Self-serve affiliate signup: creates a login account + a pending
     * influencer application. Access to the portal is granted only after an
     * admin approves (which assigns the influencer role).
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'full_name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'agree' => ['accepted'],
            'social_media_links' => ['nullable', 'string', 'max:1000'],
            'audience_description' => ['nullable', 'string', 'max:2000'],
            'monthly_reach' => ['nullable', 'integer', 'min:0'],
        ], [
            'email.unique' => 'An account with this email already exists. Please log in instead.',
            'agree.accepted' => 'Please accept the Terms of Service to continue.',
        ]);

        // Create the login account (no influencer role yet — granted on approval).
        $user = User::create([
            'name' => $validated['full_name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
        ]);

        // Social links can come as comma/newline separated string — convert to array
        $links = [];
        if (! empty($validated['social_media_links'])) {
            $parts = preg_split('/[\s,]+/', $validated['social_media_links']) ?: [];
            $links = array_values(array_filter(array_map('trim', $parts)));
        }

        $dto = InfluencerApplicationData::fromArray([
            'full_name' => $validated['full_name'],
            'email' => $validated['email'],
            'social_media_links' => $links,
            'audience_description' => $validated['audience_description'] ?? null,
            'monthly_reach' => $validated['monthly_reach'] ?? 0,
            'user_id' => $user->id,
        ]);

        $this->service->apply($dto);

        // Log them in so they immediately see their application status.
        Auth::login($user);

        return redirect()
            ->route('influencer.status')
            ->with('status', 'Account created! Your affiliate application is now under review.');
    }

    /**
     * Application status landing for a logged-in affiliate.
     * Approved affiliates are sent straight to the portal.
     */
    public function status(): View|RedirectResponse
    {
        $user = Auth::user();
        $influencer = $user?->influencer;

        if (! $influencer) {
            return redirect()->route('influencer.join');
        }

        if ($influencer->isApproved() && $user->hasRole(\App\Domain\Auth\Enums\RoleName::INFLUENCER->value)) {
            return redirect()->route('influencer.dashboard');
        }

        return view('influencer.status', compact('influencer'));
    }
}
