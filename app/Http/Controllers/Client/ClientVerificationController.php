<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\UserProfile;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

/**
 * Account verification for clients, the way Freelancer and similar platforms
 * do it: upload a government ID, the GigResource team checks it, and the
 * account shows the Verified Client badge (Sir Peter's PM-14 spec).
 *
 * The ID goes on the PRIVATE disk. It is read only by the team reviewing it,
 * through an admin-only link, and is never shown to professionals.
 */
class ClientVerificationController extends Controller
{
    public const TYPES = [
        'drivers_license' => "Driver's license",
        'passport'        => 'Passport',
        'state_id'        => 'State ID card',
    ];

    public function show(Request $request): View
    {
        $profile = $request->user()->getOrCreateProfile();

        return view('client.verification.show', [
            'profile' => $profile,
            'status'  => self::statusOf($profile),
            'types'   => self::TYPES,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $user    = $request->user();
        $profile = $user->getOrCreateProfile();

        abort_if($profile->identity_verified_at, 403, 'Your account is already verified.');

        $data = $request->validate([
            'document_type' => ['required', 'in:' . implode(',', array_keys(self::TYPES))],
            'document'      => ['required', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:8192'],
            'certify'       => ['accepted'],
        ], [
            'document.required' => 'Add a photo or scan of your ID.',
            'document.mimes'    => 'Use a JPG, PNG or PDF.',
            'document.max'      => 'The file has to be 8 MB or smaller.',
            'certify.accepted'  => 'Confirm the ID is yours.',
        ]);

        // A new upload replaces one still waiting, so there is only ever one
        // ID on file for the team to look at.
        if ($profile->identity_doc) {
            Storage::disk('local')->delete($profile->identity_doc);
        }

        $path = $request->file('document')->store("identity/{$user->id}", 'local');

        $profile->update([
            'identity_number'        => self::TYPES[$data['document_type']],
            'identity_doc'           => $path,
            'identity_submitted_at'  => now(),
            'identity_verified_at'   => null,
            'identity_rejected_note' => null,
        ]);

        return redirect()->route('client.verification.show')
            ->with('status', 'Thanks. Your ID is with our team, and your badge appears as soon as it is approved.');
    }

    /** none | pending | verified | rejected */
    public static function statusOf(UserProfile $profile): string
    {
        return match (true) {
            (bool) $profile->identity_verified_at   => 'verified',
            (bool) $profile->identity_doc           => 'pending',
            (bool) $profile->identity_rejected_note => 'rejected',
            default                                 => 'none',
        };
    }
}
