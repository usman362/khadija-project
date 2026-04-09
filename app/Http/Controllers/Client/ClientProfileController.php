<?php

namespace App\Http\Controllers\Client;

use App\Domain\ActivityLog\Services\ActivityLogger;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ClientProfileController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();
        $profile = $user->getOrCreateProfile();
        $tab = $request->string('tab')->toString() ?: 'general';

        return view('client.profile.index', compact('user', 'profile', 'tab'));
    }

    public function updateGeneral(Request $request): RedirectResponse
    {
        $user = $request->user();

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users')->ignore($user->id)],
            'phone' => ['nullable', 'string', 'max:30'],
            'bio' => ['nullable', 'string', 'max:2000'],
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

    public function updateCompany(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'company_name' => ['nullable', 'string', 'max:255'],
            'company_website' => ['nullable', 'url', 'max:255'],
            'industry' => ['nullable', 'string', 'max:100'],
        ]);

        $request->user()->getOrCreateProfile()->update($validated);

        return back()->with('status', 'Company info updated successfully.');
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

        // Delete old avatar
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
}
