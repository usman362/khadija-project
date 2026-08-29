<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Throwable;

/**
 * Style & Inspiration (client). Generates cohesive event themes — colour
 * palettes, mood boards and matching vendor styles. Representative data.
 */
class AiThemeAdvisorController extends Controller
{
    public function show(Request $request): View
    {
        $aiLayout = $request->user()?->activeRole() === 'professional' ? 'layouts.professional' : 'layouts.client';

        $level = \App\Domain\AiFeatures\AiAccess::level($request->user(), 'theme-advisor');
        if ($request->user()?->isAdmin() && in_array($request->query('preview'), ['manual', 'semi', 'maximum'], true)) {
            $level = $request->query('preview');
        }

        // The palette, theme concepts and mood board are produced by compute()
        // from what the client chooses. They used to be handed to the view as
        // literals — three finished concepts at 98/95/92% Match and a fixed
        // Sage Green palette — so the page answered before it was asked, and
        // gave the same answer to a red casual wedding as to any other.
        return view('ai-tools.theme-advisor', [
            'aiLayout' => $aiLayout,
            'level'    => $level,
        ]);
    }

    /**
     * Derive a real colour palette + mood + décor ideas from user input.
     * Pure deterministic colour math — no external API.
     */
    public function compute(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'event_type'    => ['required', 'string', 'max:120'],
                'season'        => ['required', 'string', 'in:spring,summer,fall,winter'],
                'primary_color' => ['required', 'string', 'max:40'],
                'formality'     => ['required', 'string', 'in:casual,semi-formal,formal'],
            ]);

            [$r, $g, $b] = $this->resolveColor($validated['primary_color']);
            $primaryHex  = $this->toHex($r, $g, $b);

            // Complementary via channel inversion.
            $compHex  = $this->toHex(255 - $r, 255 - $g, 255 - $b);
            // Lighter tint (toward white) and darker shade (toward black).
            $tintHex  = $this->toHex(...$this->scale($r, $g, $b, 0.45, 255));
            $shadeHex = $this->toHex(...$this->scale($r, $g, $b, 0.35, 0));
            // Season-driven neutral.
            $neutral = [
                'spring' => [244, 240, 232],
                'summer' => [250, 246, 238],
                'fall'   => [237, 228, 214],
                'winter' => [231, 234, 240],
            ][$validated['season']];
            $neutralHex = $this->toHex($neutral[0], $neutral[1], $neutral[2]);

            $palette = [
                ['name' => 'Primary', 'hex' => $primaryHex],
                ['name' => 'Light Tint', 'hex' => $tintHex],
                ['name' => 'Deep Shade', 'hex' => $shadeHex],
                ['name' => 'Complementary Accent', 'hex' => $compHex],
                ['name' => 'Neutral Base', 'hex' => $neutralHex],
            ];

            $seasonMood = [
                'spring' => ['fresh', 'airy', 'blooming'],
                'summer' => ['bright', 'vibrant', 'sunlit'],
                'fall'   => ['warm', 'rich', 'cozy'],
                'winter' => ['crisp', 'elegant', 'luminous'],
            ][$validated['season']];
            $formalityMood = [
                'casual'      => ['relaxed', 'playful'],
                'semi-formal' => ['refined', 'balanced'],
                'formal'      => ['sophisticated', 'timeless'],
            ][$validated['formality']];
            $moodKeywords = array_values(array_merge($seasonMood, $formalityMood));

            $decor = $this->decorSuggestions($validated['event_type'], $validated['season']);

            $tips = [
                "Use {$primaryHex} as your anchor colour and let {$neutralHex} carry the larger surfaces to keep the look balanced.",
                "Reserve the complementary accent ({$compHex}) for small highlights like signage, florals or linens — a little goes a long way.",
                "Match lighting warmth to a {$validated['formality']} " . ($validated['season'] === 'winter' || $validated['season'] === 'fall' ? 'warm' : 'soft') . " feel for a cohesive mood.",
            ];

            $summary = "A {$validated['formality']} {$validated['season']} palette for your {$validated['event_type']}, "
                . "built around your primary colour with tint, shade, a complementary accent and a seasonal neutral. "
                . "These are suggestions you can adapt.";

            return response()->json([
                'success' => true,
                'result'  => [
                    'summary'           => $summary,
                    'palette'           => $palette,
                    'mood_keywords'     => $moodKeywords,
                    'decor_suggestions' => $decor,
                    'tips'              => $tips,
                ],
            ]);
        } catch (Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Resolve a hex string or common colour name to an [r,g,b] triple.
     *
     * @return array{0:int,1:int,2:int}
     */
    private function resolveColor(string $input): array
    {
        $input = trim(strtolower($input));

        $names = [
            'red'       => '#dc2626',
            'blue'      => '#2563eb',
            'navy'      => '#1e3a5f',
            'blush'     => '#f4c2c2',
            'emerald'   => '#10b981',
            'gold'      => '#c9a227',
            'burgundy'  => '#800020',
            'sage'      => '#9caf88',
            'lavender'  => '#b57edc',
            'coral'     => '#ff6f61',
            'teal'      => '#14b8a6',
            'black'     => '#1a1a1a',
            'white'     => '#f5f5f5',
        ];

        if (isset($names[$input])) {
            $input = $names[$input];
        }

        $hex = ltrim($input, '#');
        if (strlen($hex) === 3) {
            $hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
        }

        if (! preg_match('/^[0-9a-f]{6}$/', $hex)) {
            // Fallback to a safe brand-neutral tone.
            $hex = '7c3aed';
        }

        return [
            (int) hexdec(substr($hex, 0, 2)),
            (int) hexdec(substr($hex, 2, 2)),
            (int) hexdec(substr($hex, 4, 2)),
        ];
    }

    /**
     * Scale an RGB triple toward a target channel value (255 = white, 0 = black).
     *
     * @return array{0:int,1:int,2:int}
     */
    private function scale(int $r, int $g, int $b, float $amount, int $target): array
    {
        return [
            (int) round($r + ($target - $r) * $amount),
            (int) round($g + ($target - $g) * $amount),
            (int) round($b + ($target - $b) * $amount),
        ];
    }

    private function toHex(int $r, int $g, int $b): string
    {
        $clamp = static fn (int $v): int => max(0, min(255, $v));

        return sprintf('#%02x%02x%02x', $clamp($r), $clamp($g), $clamp($b));
    }

    /**
     * @return array<int, string>
     */
    private function decorSuggestions(string $eventType, string $season): array
    {
        $type = strtolower($eventType);

        $seasonAccents = [
            'spring' => 'fresh tulips, cherry blossom branches and pastel linens',
            'summer' => 'citrus centerpieces, airy fabrics and string lighting',
            'fall'   => 'foliage garlands, candle clusters and textured runners',
            'winter' => 'evergreen accents, metallic details and warm ambient lighting',
        ][$season];

        if (str_contains($type, 'wedding') || str_contains($type, 'anniversary')) {
            return [
                "Layer a statement ceremony backdrop with {$seasonAccents}.",
                'Coordinate table linens and florals to your primary and accent colours.',
                'Add a signature lounge corner for guests to relax between moments.',
                'Use place cards and signage in your palette for a cohesive feel.',
            ];
        }

        if (str_contains($type, 'corporate') || str_contains($type, 'conference') || str_contains($type, 'launch')) {
            return [
                "Brand the stage and entrance with your colours plus {$seasonAccents}.",
                'Keep tablescapes minimal with accent-colour details and clean signage.',
                'Use lighting to highlight key areas like registration and the stage.',
                'Add a photo or branding moment for shareable snapshots.',
            ];
        }

        // birthday / party / general
        return [
            "Build a focal backdrop or balloon feature using {$seasonAccents}.",
            'Dress tables with accent-colour runners, napkins and simple centerpieces.',
            'Create a dessert or drinks station styled in your palette.',
            'Add playful signage and favours that echo the colour theme.',
        ];
    }
}
