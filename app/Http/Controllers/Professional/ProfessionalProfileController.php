<?php

namespace App\Http\Controllers\Professional;

use App\Domain\ActivityLog\Services\ActivityLogger;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ProfessionalProfileController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();
        $profile = $user->getOrCreateProfile();
        $tab = $request->string('tab')->toString() ?: 'general';

        return view('professional.profile.index', compact('user', 'profile', 'tab'));
    }

    public function updateGeneral(Request $request): RedirectResponse
    {
        $user = $request->user();

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users')->ignore($user->id)],
            'phone' => ['nullable', 'string', 'max:30'],
            'bio' => ['nullable', 'string', 'max:2000'],
            'headline' => ['nullable', 'string', 'max:255'],
            'date_of_birth' => ['nullable', 'date', 'before:today'],
            'gender' => ['nullable', 'in:male,female,other,prefer_not_to_say'],
            'address' => ['nullable', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:100'],
            'state' => ['nullable', 'string', 'max:100'],
            'country' => ['nullable', 'string', 'max:100'],
            'zip_code' => ['nullable', 'string', 'max:20'],
            'website' => ['nullable', 'url', 'max:255'],
        ]);

        $user->update([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'phone' => $validated['phone'] ?? null,
        ]);

        $user->getOrCreateProfile()->update([
            'bio' => $validated['bio'] ?? null,
            'headline' => $validated['headline'] ?? null,
            'date_of_birth' => $validated['date_of_birth'] ?? null,
            'gender' => $validated['gender'] ?? null,
            'address' => $validated['address'] ?? null,
            'city' => $validated['city'] ?? null,
            'state' => $validated['state'] ?? null,
            'country' => $validated['country'] ?? null,
            'zip_code' => $validated['zip_code'] ?? null,
            'website' => $validated['website'] ?? null,
        ]);

        return back()->with('status', 'Profile updated successfully.');
    }

    public function updateProfessional(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'hourly_rate' => ['nullable', 'numeric', 'min:0', 'max:99999'],
            'availability' => ['nullable', 'in:available,busy,not_available'],
            'experience_years' => ['nullable', 'integer', 'min:0', 'max:60'],
            'skills' => ['nullable', 'string', 'max:1000'],
            'languages' => ['nullable', 'string', 'max:500'],
        ]);

        $profile = $request->user()->getOrCreateProfile();

        // Parse skills as comma-separated
        $skills = null;
        if (!empty($validated['skills'])) {
            $skills = array_map('trim', explode(',', $validated['skills']));
            $skills = array_filter($skills);
            $skills = array_values($skills);
        }

        // Parse languages as comma-separated
        $languages = null;
        if (!empty($validated['languages'])) {
            $languages = array_map('trim', explode(',', $validated['languages']));
            $languages = array_filter($languages);
            $languages = array_values($languages);
        }

        $profile->update([
            'hourly_rate' => $validated['hourly_rate'] ?? null,
            'availability' => $validated['availability'] ?? null,
            'experience_years' => $validated['experience_years'] ?? null,
            'skills' => $skills,
            'languages' => $languages,
        ]);

        return back()->with('status', 'Professional info updated successfully.');
    }

    public function updatePortfolio(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'portfolio' => ['nullable', 'array', 'max:10'],
            'portfolio.*.title' => ['required_with:portfolio', 'string', 'max:255'],
            'portfolio.*.url' => ['required_with:portfolio', 'url', 'max:255'],
            'portfolio.*.description' => ['nullable', 'string', 'max:500'],
            'certifications' => ['nullable', 'array', 'max:10'],
            'certifications.*.name' => ['required_with:certifications', 'string', 'max:255'],
            'certifications.*.issuer' => ['nullable', 'string', 'max:255'],
            'certifications.*.year' => ['nullable', 'integer', 'min:1950', 'max:2030'],
        ]);

        $profile = $request->user()->getOrCreateProfile();

        // Filter out empty portfolio entries
        $portfolio = collect($validated['portfolio'] ?? [])->filter(fn($item) => !empty($item['title']))->values()->toArray();
        $certifications = collect($validated['certifications'] ?? [])->filter(fn($item) => !empty($item['name']))->values()->toArray();

        $profile->update([
            'portfolio' => $portfolio ?: null,
            'certifications' => $certifications ?: null,
        ]);

        return back()->with('status', 'Portfolio & certifications updated.');
    }

    public function updateSocial(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'linkedin' => ['nullable', 'url', 'max:255'],
            'twitter' => ['nullable', 'url', 'max:255'],
            'facebook' => ['nullable', 'url', 'max:255'],
            'instagram' => ['nullable', 'url', 'max:255'],
        ]);

        $request->user()->getOrCreateProfile()->update([
            'social_links' => array_filter($validated),
        ]);

        return back()->with('status', 'Social links updated successfully.');
    }

    public function updatePassword(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $user = $request->user();

        $user->update([
            'password' => Hash::make($validated['password']),
        ]);

        ActivityLogger::log(ActivityLogger::ACTION_PASSWORD_CHANGED, $user);

        return back()->with('status', 'Password changed successfully.');
    }

    public function updateNotifications(Request $request): RedirectResponse
    {
        $request->user()->getOrCreateProfile()->update([
            'notify_email_bookings' => $request->boolean('notify_email_bookings'),
            'notify_email_messages' => $request->boolean('notify_email_messages'),
            'notify_email_events' => $request->boolean('notify_email_events'),
            'notify_email_marketing' => $request->boolean('notify_email_marketing'),
        ]);

        return back()->with('status', 'Notification preferences updated.');
    }

    public function updateAvatar(Request $request): RedirectResponse
    {
        $request->validate([
            'avatar' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ]);

        $user = $request->user();

        if ($user->avatar) {
            Storage::disk('public')->delete($user->avatar);
        }

        $path = $request->file('avatar')->store('avatars', 'public');
        $user->update(['avatar' => $path]);

        return back()->with('status', 'Profile photo updated.');
    }

    public function removeAvatar(Request $request): RedirectResponse
    {
        $user = $request->user();

        if ($user->avatar) {
            Storage::disk('public')->delete($user->avatar);
            $user->update(['avatar' => null]);
        }

        return back()->with('status', 'Profile photo removed.');
    }

    /**
     * Upload the profile cover banner (Freelancer.com-style wide banner above
     * the avatar). Larger max than avatar since cover images are wider.
     */
    public function updateCover(Request $request): RedirectResponse
    {
        $request->validate([
            'cover_image' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
        ]);

        $user = $request->user();

        if ($user->cover_image) {
            Storage::disk('public')->delete($user->cover_image);
        }

        $path = $request->file('cover_image')->store('covers', 'public');
        $user->update(['cover_image' => $path]);

        return back()->with('status', 'Cover photo updated.');
    }

    public function removeCover(Request $request): RedirectResponse
    {
        $user = $request->user();

        if ($user->cover_image) {
            Storage::disk('public')->delete($user->cover_image);
            $user->update(['cover_image' => null]);
        }

        return back()->with('status', 'Cover photo removed.');
    }

    /**
     * Submit a verification document for one of three badge types:
     * trade_license | liability_insurance | workers_comp.
     *
     * Uploading replaces any existing doc AND clears the verified_at stamp —
     * so a re-upload goes back into the admin review queue.
     */
    public function submitVerification(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'badge' => ['required', 'in:trade_license,liability_insurance,workers_comp'],
            'number' => ['nullable', 'string', 'max:100'],
            'document' => ['required', 'file', 'mimes:jpg,jpeg,png,webp,pdf', 'max:5120'], // 5MB
        ]);

        $profile = $request->user()->getOrCreateProfile();
        $badge = $validated['badge'];
        $docCol = "{$badge}_doc";
        $numberCol = "{$badge}_number";
        $verifiedCol = "{$badge}_verified_at";

        // Clean up old doc if replacing
        if ($profile->$docCol) {
            Storage::disk('public')->delete($profile->$docCol);
        }

        $path = $validated['document']->store("verification/{$badge}", 'public');

        $profile->update([
            $docCol => $path,
            $numberCol => $validated['number'] ?? null,
            $verifiedCol => null, // always re-enters review queue
        ]);

        return back()->with('status', ucfirst(str_replace('_', ' ', $badge)) . ' submitted for verification. Our team will review it shortly.');
    }

    /**
     * Pro withdraws a submission before verification — clears file + number
     * + any approval stamp.
     */
    public function removeVerification(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'badge' => ['required', 'in:trade_license,liability_insurance,workers_comp'],
        ]);

        $profile = $request->user()->getOrCreateProfile();
        $badge = $validated['badge'];
        $docCol = "{$badge}_doc";

        if ($profile->$docCol) {
            Storage::disk('public')->delete($profile->$docCol);
        }

        $profile->update([
            $docCol => null,
            "{$badge}_number" => null,
            "{$badge}_verified_at" => null,
        ]);

        return back()->with('status', 'Verification document removed.');
    }
}
