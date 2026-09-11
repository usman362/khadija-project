<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use Illuminate\View\View;

/**
 * Every badge on the platform on one page, for review.
 *
 * Khadijah decides the colours, shades and icons (Sir Peter, 2026-09-09), and
 * until now the only way to see a badge was to find an account that had
 * earned it. This shows each one both ways, earned and not yet earned, with
 * what earns it, read from the same config the live pages use.
 */
class AdminBadgeController extends Controller
{
    public function index(): View
    {
        return view('dashboard.admin.badges.index', [
            'client' => config('badges.client', []),
            'professional' => [
                [
                    'name'   => 'Verified',
                    'icon'   => '✓',
                    'colour' => config('badges.verified_colour', '#2563eb'),
                    'blurb'  => "Licence, insurance and workers' comp all on file and approved.",
                    'where'  => 'Browse and the professional\'s profile',
                ],
                [
                    'name'   => 'Top Rated',
                    'icon'   => '★',
                    'colour' => config('badges.top_rated_colour', '#f59e0b'),
                    'blurb'  => 'Rated highly by the clients who booked them.',
                    'where'  => 'Browse and the professional\'s profile',
                ],
            ],
            'influencer' => config('influencer.badges', []),
            'tiers'      => config('influencer.tiers', []),
        ]);
    }
}
