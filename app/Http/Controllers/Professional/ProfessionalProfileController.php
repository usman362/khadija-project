<?php

namespace App\Http\Controllers\Professional;

use App\Domain\ActivityLog\Services\ActivityLogger;
use App\Domain\Uploads\UploadPipeline;
use App\Http\Controllers\Controller;
use App\Support\ProfessionalStateAccount;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
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

        // The service picker only renders on the Professional tab, so only pay
        // for the category list there.
        $categories = $tab === 'professional'
            ? \App\Models\Category::active()->bookableServices()
                ->orderBy('name')->get(['id', 'name'])
            : collect();

        $selectedServices = $tab === 'professional'
            ? $user->serviceCategories()->pluck('categories.id')->all()
            : [];

        return view('professional.profile.index', compact(
            'user', 'profile', 'tab', 'categories', 'selectedServices'
        ));
    }

    /**
     * The categories this pro offers. This is what puts them on a category
     * landing page and into a filtered /browse — free-text skills never could,
     * since "Fine-Art Wedding Photographer" does not contain the category name
     * "Photography Services".
     */
    public function updateServices(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'services'   => ['nullable', 'array'],
            'services.*' => ['integer', 'exists:categories,id'],
        ]);

        $picked = collect($data['services'] ?? [])->unique();

        // The picker dedupes by NAME — the legacy tree repeats "Event
        // Photography" under a dozen event types — so it can only ever submit
        // one id per name. Saving those ids verbatim would quietly drop the pro
        // from every other branch carrying the same service. Expand back out:
        // offering a service means offering it wherever a client browses to it.
        $names = \App\Models\Category::whereIn('id', $picked)->pluck('name');
        $ids   = \App\Models\Category::whereIn('name', $names)->pluck('id')->all();

        $request->user()->serviceCategories()->sync($ids);

        $shown = $names->count();

        return back()->with('status', $shown === 0
            ? 'Services cleared — your profile will not appear on any category page until you pick at least one.'
            : $shown . ' ' . \Illuminate\Support\Str::plural('service', $shown) . ' saved. Clients browsing those categories can now find you.');
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
            // Rule R47 — a professional's registered state is fixed once set.
            // Working in a second state means a second account, not editing
            // this field: flipping it would carry this account's reviews,
            // badges and booking history into a state it was never licensed
            // in, and would silently move every package and gig it owns under
            // R38. An account that has no state yet may still set one, and it
            // has to come from the seven (R9), not from free text.
            'state' => ProfessionalStateAccount::ownerMaySetState($user)
                ? ['nullable', 'string', Rule::in(array_keys(config('geo.allowed_states', [])))]
                : ['prohibited'],
            'country' => ['nullable', 'string', 'max:100'],
            'zip_code' => ['nullable', 'string', 'max:20'],
            'website' => ['nullable', 'url', 'max:255'],
            'service_origin_line' => ['nullable', 'string', 'max:255'],
            'service_origin_city' => ['nullable', 'string', 'max:100'],
            'service_origin_zip' => ['nullable', 'string', 'max:20'],
            'travel_radius_miles' => ['nullable', 'integer', 'min:1', 'max:200'],
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
            // Absent from the payload on a locked account, so the stored
            // value stands rather than being nulled by the ?? below.
            'state' => $validated['state'] ?? $user->profile?->state,
            'country' => $validated['country'] ?? null,
            'zip_code' => $validated['zip_code'] ?? null,
            'website' => $validated['website'] ?? null,
            'service_origin_line' => $validated['service_origin_line'] ?? null,
            'service_origin_city' => $validated['service_origin_city'] ?? null,
            'service_origin_state' => $user->profile?->state ?? $validated['state'] ?? null,
            'service_origin_zip' => $validated['service_origin_zip'] ?? null,
            'travel_radius_miles' => $validated['travel_radius_miles'] ?? null,
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

        // Merge with any uploaded portfolio IMAGES already stored (those live in
        // the same portfolio array with type=image and must not be wiped by the
        // link-based repeater).
        $existingImages = collect(is_array($profile->portfolio) ? $profile->portfolio : [])
            ->filter(fn ($i) => ($i['type'] ?? null) === 'image')->values()->toArray();

        $profile->update([
            'portfolio' => array_merge($existingImages, $portfolio) ?: null,
            'certifications' => $certifications ?: null,
        ]);

        return back()->with('status', 'Portfolio & certifications updated.');
    }

    /**
     * Upload one portfolio photo → auto-generate every size (Peter's image
     * pipeline). The first image becomes the featured cover used on search cards.
     */
    public function uploadPortfolioImage(Request $request, \App\Services\ImagePipelineService $pipeline): RedirectResponse
    {
        $request->validate([
            'portfolio_image' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:6144'],
            'focal_x' => ['nullable', 'numeric', 'between:0,1'],
            'focal_y' => ['nullable', 'numeric', 'between:0,1'],
        ]);

        $user = $request->user();
        $profile = $user->getOrCreateProfile();
        $items = collect(is_array($profile->portfolio) ? $profile->portfolio : []);

        if ($items->filter(fn ($i) => ($i['type'] ?? null) === 'image')->count() >= 12) {
            return back()->withErrors(['portfolio_image' => 'You can upload up to 12 portfolio images.']);
        }

        // Fifty images a day — Khadijah's sheet, 29 Aug. Counted after the
        // 12-per-portfolio check above, which is a different rule about how
        // much work one profile shows; this one is about volume per day.
        \App\Support\UserLimit::hit('pro-images', $user, null, 'portfolio_image');

        $sizes = $pipeline->process(
            $request->file('portfolio_image'),
            'portfolio/' . $user->id,
            (float) $request->input('focal_x', 0.5),
            (float) $request->input('focal_y', 0.5),
        );
        if (empty($sizes)) {
            return back()->withErrors(['portfolio_image' => 'That image could not be processed. Try a JPG or PNG.']);
        }

        $isFirstImage = $items->filter(fn ($i) => ($i['type'] ?? null) === 'image')->isEmpty();
        $items->push(array_merge($sizes, [
            'type' => 'image',
            'featured' => $isFirstImage,
            'uploaded_at' => now()->toIso8601String(),
        ]));

        $profile->update(['portfolio' => $items->values()->toArray()]);
        Log::info('Image upload (rights confirmed)', ['type' => 'portfolio', 'user_id' => $user->id, 'at' => now()->toIso8601String()]);

        return back()->with('status', 'Portfolio image added.');
    }

    public function deletePortfolioImage(Request $request, \App\Services\ImagePipelineService $pipeline): RedirectResponse
    {
        $idx = (int) $request->input('index', -1);
        $profile = $request->user()->getOrCreateProfile();
        $items = collect(is_array($profile->portfolio) ? $profile->portfolio : [])->values();

        if ($item = $items->get($idx)) {
            if (($item['type'] ?? null) === 'image') {
                $pipeline->delete($item);
            }
            $items->forget($idx);
            $items = $items->values();
            // If the featured cover was removed, promote the first remaining image.
            if (! $items->firstWhere('featured', true)) {
                $arr = $items->toArray();
                foreach ($arr as $k => $i) {
                    if (($i['type'] ?? null) === 'image') { $arr[$k]['featured'] = true; break; }
                }
                $items = collect($arr);
            }
            $profile->update(['portfolio' => $items->isEmpty() ? null : $items->values()->toArray()]);
        }

        return back()->with('status', 'Portfolio image removed.');
    }

    public function setFeaturedPortfolio(Request $request): RedirectResponse
    {
        $idx = (int) $request->input('index', -1);
        $profile = $request->user()->getOrCreateProfile();
        $items = collect(is_array($profile->portfolio) ? $profile->portfolio : [])
            ->values()
            ->map(function ($i, $k) use ($idx) {
                $i['featured'] = ($k === $idx);
                return $i;
            });
        $profile->update(['portfolio' => $items->toArray()]);

        return back()->with('status', 'Featured cover updated.');
    }

    /** Re-crop a portfolio image around a focal point the pro picked. */
    public function adjustPortfolioCrop(Request $request, \App\Services\ImagePipelineService $pipeline): RedirectResponse
    {
        $data = $request->validate([
            'index'   => ['required', 'integer', 'min:0'],
            'focal_x' => ['required', 'numeric', 'between:0,1'],
            'focal_y' => ['required', 'numeric', 'between:0,1'],
        ]);

        $user = $request->user();
        $profile = $user->getOrCreateProfile();
        $items = collect(is_array($profile->portfolio) ? $profile->portfolio : [])->values();
        $item = $items->get((int) $data['index']);

        if ($item && ($item['type'] ?? null) === 'image') {
            $updated = $pipeline->reprocess($item, 'portfolio/' . $user->id, (float) $data['focal_x'], (float) $data['focal_y']);
            $arr = $items->toArray();
            $arr[(int) $data['index']] = $updated;
            $profile->update(['portfolio' => $arr]);
        }

        return back()->with('status', 'Cover crop updated.');
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

    public function notifications(Request $request): View
    {
        $user = $request->user();
        $profile = $user->getOrCreateProfile();

        return view('professional.notifications.index', compact('user', 'profile'));
    }

    /**
     * Run risk-based address verification on the professional's saved business
     * address (Developer Feedback v1.1 §7.3). Free Layer-1 filtering runs now;
     * the paid provider call is launch-gated by AddressVerificationGuard.
     */
    public function verifyAddress(Request $request, \App\Domain\AddressVerification\AddressVerificationService $service): RedirectResponse
    {
        $profile = $request->user()->getOrCreateProfile();

        if (trim((string) $profile->address) === '') {
            return back(303)->with('error', 'Please add and save your business address first, then verify it.');
        }

        $result = $service->verifyBusiness($request->user(), [
            'line1' => (string) $profile->address,
            'city'  => (string) $profile->city,
            'state' => (string) $profile->state,
            'zip'   => (string) $profile->zip_code,
        ]);

        $message = 'Address status: ' . $result['label']
            . ($result['reason'] ? ' — ' . $result['reason'] : '');

        return back(303)->with('status', $message);
    }

    public function updateNotifications(Request $request): RedirectResponse
    {
        $request->user()->getOrCreateProfile()->update([
            'notify_email_bookings' => $request->boolean('notify_email_bookings'),
            'notify_email_messages' => $request->boolean('notify_email_messages'),
            'notify_email_events' => $request->boolean('notify_email_events'),
            'notify_email_marketing' => $request->boolean('notify_email_marketing'),
            'notify_push' => $request->boolean('notify_push'),
            'notify_sms' => $request->boolean('notify_sms'),
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

        // DMCA audit trail (Feedback v1.1 §1.3): every image upload is
        // logged with user ID + timestamp; the uploader confirmed rights
        // at the point of upload.
        Log::info('Image upload (rights confirmed)', ['type' => 'avatar', 'path' => $path, 'user_id' => $user->id, 'at' => now()->toIso8601String()]);

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

        Log::info('Image upload (rights confirmed)', ['type' => 'cover', 'path' => $path, 'user_id' => $user->id, 'at' => now()->toIso8601String()]);

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
        $isInsurance = $request->input('badge') === 'liability_insurance';

        $validated = $request->validate([
            'badge' => ['required', 'in:trade_license,liability_insurance,workers_comp'],
            'number' => ['nullable', 'string', 'max:100'],
            'document' => ['required', 'file', 'mimes:jpg,jpeg,png,webp,pdf', 'max:5120'], // 5MB

            // A certificate of insurance is only worth anything with these on
            // it. Required for insurance, ignored for the other two badges.
            'insurer' => [Rule::requiredIf($isInsurance), 'nullable', 'string', 'max:160'],
            'coverage' => [Rule::requiredIf($isInsurance), 'nullable', 'integer', 'min:1'],
            'effective_from' => [Rule::requiredIf($isInsurance), 'nullable', 'date'],
            // Cover that has already run out is not cover.
            'expires_on' => [Rule::requiredIf($isInsurance), 'nullable', 'date', 'after:today', 'after:effective_from'],
        ], [
            'expires_on.after' => 'The policy must not have expired already.',
        ]);

        $profile = $request->user()->getOrCreateProfile();
        $badge = $validated['badge'];
        $docCol = "{$badge}_doc";
        $numberCol = "{$badge}_number";
        $verifiedCol = "{$badge}_verified_at";

        // Rule R54 — through the one pipeline, not straight to disk.
        //
        // This wrote to the PUBLIC disk, which put a professional's trade
        // licence, insurance certificate and workers' comp document on URLs
        // that needed no sign-in: the path was the only thing between the
        // file and anyone who guessed it. The pipeline quarantines it, checks
        // that its contents match its name, records that no malware scanner
        // is configured, and holds it for the admin review these documents
        // already went to. `uploaded_files.id` replaces the raw path.
        $record = app(UploadPipeline::class)
            ->accept($validated['document'], 'verification', $request->user());

        if ($record->status === \App\Models\UploadedFile::REJECTED) {
            return back()->withErrors(['document' => $record->decision_reason]);
        }

        // Clean up the previous submission once the new one is safely stored.
        if ($profile->$docCol) {
            $this->deleteVerificationDocument($profile->$docCol);
        }

        $attributes = [
            $docCol => (string) $record->id,
            $numberCol => $validated['number'] ?? null,
            $verifiedCol => null, // always re-enters review queue
        ];

        // R47 — a trade licence is proof of ONE state's licensing, and this
        // account works in one state. Stamped from the account rather than
        // asked for, because there is only one answer it can have: a licence
        // for anywhere else belongs on that state's own account.
        if ($badge === 'trade_license') {
            $attributes['trade_license_state'] = \App\Support\StateMatching::stateOf($request->user());
        }

        if ($isInsurance) {
            $attributes += [
                'liability_insurance_insurer' => $validated['insurer'],
                'liability_insurance_coverage' => $validated['coverage'],
                'liability_insurance_effective_from' => $validated['effective_from'],
                'liability_insurance_expires_on' => $validated['expires_on'],
            ];
        }

        $profile->update($attributes);

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
            $this->deleteVerificationDocument($profile->$docCol);
        }

        $profile->update([
            $docCol => null,
            "{$badge}_number" => null,
            "{$badge}_verified_at" => null,
        ]);

        return back()->with('status', 'Verification document removed.');
    }

    /**
     * Delete a verification document, whichever era it came from.
     *
     * Since R54 the column holds an uploaded_files id; before it, a raw path
     * on the public disk. Both exist in the same table until the older rows
     * age out, so both are handled rather than leaving the old files behind
     * on a disk anyone can read.
     */
    private function deleteVerificationDocument(string $reference): void
    {
        if (ctype_digit($reference)) {
            \App\Models\UploadedFile::find($reference)?->delete();

            return;
        }

        Storage::disk('public')->delete($reference);
    }
}
