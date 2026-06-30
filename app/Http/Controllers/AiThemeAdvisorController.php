<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * AI Theme & Style Advisor (client). Generates cohesive event themes — colour
 * palettes, mood boards and matching vendor styles. Representative data.
 */
class AiThemeAdvisorController extends Controller
{
    public function show(Request $request): View
    {
        $aiLayout = $request->user()?->hasRole('supplier') ? 'layouts.professional' : 'layouts.client';

        return view('ai-tools.theme-advisor', [
            'aiLayout' => $aiLayout,
            'themes' => [
                ['Elegant Garden Romance', 98, 'Lush greenery, soft blush florals and timeless elegance for a classic garden affair.', 'photo-1519225421980-715cb0215aed', ['#5a7d57', '#a8c3a0', '#f4d9d0', '#e8b4b8', '#c9a227'], true],
                ['Modern Luxury', 95, 'Sleek black, warm gold accents and dramatic lighting for a sophisticated celebration.', 'photo-1511795409834-ef04bbd61622', ['#1a1a1a', '#2d2d2d', '#c9a227', '#e8d8a0', '#8a8a8a'], false],
                ['Rustic Chic', 92, 'Warm wood tones, natural textures and cozy details for a relaxed, charming vibe.', 'photo-1464366400600-7168b8af9bc3', ['#8b5e3c', '#c19a6b', '#d9c3a5', '#a0522d', '#6b4423'], false],
            ],
            'palette' => [
                ['Primary', '#5a7d57', 'Sage Green'],
                ['Secondary', '#f4d9d0', 'Blush Pink'],
                ['Accent', '#e8b4b8', 'Dusty Rose'],
                ['Metallic', '#c9a227', 'Champagne Gold'],
            ],
            'moodboard' => [
                'photo-1519741497674-611481863552', 'photo-1465495976277-4387d4b0b4c6',
                'photo-1511285560929-80b456fea0bc', 'photo-1469371670807-013ccf25f16a',
                'photo-1464366400600-7168b8af9bc3', 'photo-1511795409834-ef04bbd61622',
            ],
            'categories' => ['All', 'Ceremony', 'Reception', 'Tablescape', 'Floral', 'Lighting', 'Cake', 'Lounge'],
        ]);
    }
}
