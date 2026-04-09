<?php

namespace App\Http\Controllers;

use App\Models\PolicySignature;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class PolicySignatureController extends Controller
{
    private const ALLOWED_TYPES = [
        'privacy_policy',
        'ai_usage_agreement',
        'terms_of_service',
    ];

    public function sign(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'policy_type'    => ['required', 'string', 'in:' . implode(',', self::ALLOWED_TYPES)],
            'policy_version' => ['required', 'string', 'max:20'],
            'signature_type' => ['required', 'in:typed,drawn'],
            'signature_data' => ['required', 'string', 'max:100000'],
        ]);

        $user = $request->user();

        // Check already signed this version
        $exists = PolicySignature::where('user_id', $user->id)
            ->where('policy_type', $validated['policy_type'])
            ->where('policy_version', $validated['policy_version'])
            ->exists();

        if ($exists) {
            return back()->with('sign_status', 'already_signed');
        }

        // Validate typed signature is not empty/whitespace
        if ($validated['signature_type'] === 'typed') {
            $name = trim($validated['signature_data']);
            if (empty($name)) {
                return back()->withErrors(['signature_data' => 'Please type your full name to sign.']);
            }
        }

        PolicySignature::create([
            'user_id'        => $user->id,
            'policy_type'    => $validated['policy_type'],
            'policy_version' => $validated['policy_version'],
            'signature_type' => $validated['signature_type'],
            'signature_data' => $validated['signature_data'],
            'ip_address'     => $request->ip(),
            'user_agent'     => $request->userAgent(),
            'signed_at'      => now(),
        ]);

        return back()->with('sign_status', 'signed');
    }
}
