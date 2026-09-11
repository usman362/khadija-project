<?php

namespace App\Http\Controllers\Client;

use App\Domain\Badges\ClientBadges;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Every badge a client can earn, and how far along they are with each.
 *
 * Sir Peter, 2026-09-11: nobody knows yet how many badges there will be, so a
 * user needs one place to see them all. Worked out from their record on every
 * load, like the profile, so it is always current as badges are earned or lost.
 */
class ClientBadgeController extends Controller
{
    public function index(Request $request): View
    {
        $badges = ClientBadges::progressFor($request->user());

        return view('client.badges.index', [
            'badges' => $badges,
            'earned' => $badges->where('earned', true)->count(),
        ]);
    }
}
