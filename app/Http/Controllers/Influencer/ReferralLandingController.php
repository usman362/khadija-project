<?php

namespace App\Http\Controllers\Influencer;

use App\Http\Controllers\Controller;
use App\Models\Influencer;
use Illuminate\Http\RedirectResponse;
use Symfony\Component\HttpFoundation\Cookie;

class ReferralLandingController extends Controller
{
    public function __invoke(string $code): RedirectResponse
    {
        // Validate referral code exists and belongs to an approved influencer
        $exists = Influencer::where('referral_code', $code)
            ->where('status', 'approved')
            ->exists();

        $target = $exists ? route('register') : route('landing');

        $response = redirect($target);

        if ($exists) {
            $cookieName = (string) config('influencer.cookie_name', 'khadija_ref');
            $days = (int) config('influencer.cookie_days', 30);
            $response->headers->setCookie(
                Cookie::create($cookieName, $code, time() + ($days * 86400), '/', null, false, true)
            );
        }

        return $response;
    }
}
