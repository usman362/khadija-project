<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Throwable;

/**
 * AI Portfolio Optimizer (professional). Audits a pro's profile/portfolio and
 * recommends high-impact improvements to lift search visibility, views and
 * win-rate. Representative data.
 */
class AiPortfolioOptimizerController extends Controller
{
    public function show(Request $request): View
    {
        $aiLayout = $request->user()?->activeRole() === 'supplier' ? 'layouts.professional' : 'layouts.client';

        $level = \App\Domain\AiFeatures\AiAccess::level($request->user(), 'portfolio-optimizer');
        if ($request->user()?->isAdmin() && in_array($request->query('preview'), ['manual', 'semi', 'maximum'], true)) {
            $level = $request->query('preview');
        }

        return view('ai-tools.portfolio-optimizer', [
            'aiLayout' => $aiLayout,
            'level'    => $level,
            'stats' => [
                ['Portfolio Success Score', '94%', 'good'], ['Search Visibility', '87%', 'good'],
                ['Profile Views', '88%', 'good'], ['Profile Completeness', '98%', 'good'],
            ],
            'audit' => [
                ['Professional headshot', true], ['Business description', true],
                ['Portfolio (12+ photos)', true], ['Highlight video', false],
                ['Verified badges', true], ['Service packages listed', true],
                ['5+ recent reviews', false], ['Response time set', true],
            ],
            'recommendations' => [
                ['Update your hero image', 'Your top photo is 2 years old — a fresh hero lifts clicks ~18%.', 'High', '+18% clicks'],
                ['Add more portfolio photos', 'Add 8 photos to reach the 20-photo sweet spot for conversions.', 'High', '+12% inquiries'],
                ['Add a highlight video', 'Profiles with a 30-60s reel get 2.3× more saves.', 'High', '+130% saves'],
                ['Rewrite business description', 'AI can rewrite it keyword-rich for better search ranking.', 'Medium', '+9% visibility'],
                ['Add 5 more client reviews', 'You’re 5 reviews from the Top-Rated badge threshold.', 'Medium', 'Top-Rated badge'],
            ],
            'gallery' => [
                ['photo-1519741497674-611481863552', 92], ['photo-1465495976277-4387d4b0b4c6', 88],
                ['photo-1511795409834-ef04bbd61622', 95], ['photo-1519225421980-715cb0215aed', 71],
                ['photo-1469371670807-013ccf25f16a', 84], ['photo-1511285560929-80b456fea0bc', 90],
            ],
            'benchmark' => [
                ['Your Portfolio', 94, true], ['Top 10% in your area', 91, false], ['Category average', 73, false],
            ],
            'metrics' => [
                ['Media Quality', '72%', 'Good — add hi-res'], ['Profile Views', '+24%', 'vs last month'],
                ['Inquiry Rate', '8.4%', 'Above average'], ['Win Rate', '31%', 'Top quartile'],
            ],
        ]);
    }

    /**
     * Score a professional's profile from real inputs using a deterministic
     * weighted rubric — no external API. Output is an estimate to guide edits.
     */
    public function compute(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'num_photos'        => ['required', 'integer', 'min:0', 'max:1000'],
            'has_video'         => ['nullable', 'boolean'],
            'num_reviews'       => ['required', 'integer', 'min:0', 'max:100000'],
            'avg_rating'        => ['required', 'numeric', 'min:0', 'max:5'],
            'response_hours'    => ['required', 'numeric', 'min:0', 'max:9999'],
            'categories_listed' => ['required', 'integer', 'min:0', 'max:100'],
        ]);

        try {
            $photos     = (int) $validated['num_photos'];
            $hasVideo   = (bool) ($validated['has_video'] ?? false);
            $reviews    = (int) $validated['num_reviews'];
            $rating     = (float) $validated['avg_rating'];
            $respHours  = (float) $validated['response_hours'];
            $categories = (int) $validated['categories_listed'];

            // --- Weighted factors (total 100) ---
            // Photos: up to 25, full at >= 15.
            $photoMax = 25;
            $photoPts = (int) round(min(1.0, $photos / 15) * $photoMax);

            // Video: 15 all-or-nothing.
            $videoMax = 15;
            $videoPts = $hasVideo ? $videoMax : 0;

            // Reviews: up to 20, full at >= 20.
            $reviewMax = 20;
            $reviewPts = (int) round(min(1.0, $reviews / 20) * $reviewMax);

            // Rating: up to 20, full at 5.0 (scaled linearly from 0).
            $ratingMax = 20;
            $ratingPts = (int) round(min(1.0, $rating / 5) * $ratingMax);

            // Responsiveness: up to 10, full at <= 2h, zero at >= 48h.
            $respMax = 10;
            if ($respHours <= 2) {
                $respPts = $respMax;
            } elseif ($respHours >= 48) {
                $respPts = 0;
            } else {
                $respPts = (int) round((1 - (($respHours - 2) / 46)) * $respMax);
            }

            // Categories: up to 10, full at >= 3.
            $catMax = 10;
            $catPts = (int) round(min(1.0, $categories / 3) * $catMax);

            $score = $photoPts + $videoPts + $reviewPts + $ratingPts + $respPts + $catPts;
            $score = max(0, min(100, $score));

            $grade = $score >= 85 ? 'A' : ($score >= 70 ? 'B' : ($score >= 55 ? 'C' : 'D'));

            $factors = [
                [
                    'label'  => 'Portfolio photos',
                    'points' => $photoPts,
                    'max'    => $photoMax,
                    'status' => $photoPts >= $photoMax ? 'good' : 'warn',
                    'detail' => $photos . ' photo' . ($photos === 1 ? '' : 's') . ' (15+ recommended)',
                ],
                [
                    'label'  => 'Highlight video',
                    'points' => $videoPts,
                    'max'    => $videoMax,
                    'status' => $hasVideo ? 'good' : 'warn',
                    'detail' => $hasVideo ? 'Video added' : 'No video',
                ],
                [
                    'label'  => 'Client reviews',
                    'points' => $reviewPts,
                    'max'    => $reviewMax,
                    'status' => $reviewPts >= $reviewMax ? 'good' : 'warn',
                    'detail' => $reviews . ' review' . ($reviews === 1 ? '' : 's') . ' (20+ recommended)',
                ],
                [
                    'label'  => 'Average rating',
                    'points' => $ratingPts,
                    'max'    => $ratingMax,
                    'status' => $ratingPts >= $ratingMax ? 'good' : 'warn',
                    'detail' => number_format($rating, 1) . ' / 5.0',
                ],
                [
                    'label'  => 'Responsiveness',
                    'points' => $respPts,
                    'max'    => $respMax,
                    'status' => $respPts >= $respMax ? 'good' : 'warn',
                    'detail' => 'Replies in ~' . rtrim(rtrim(number_format($respHours, 1), '0'), '.') . 'h (2h or less is ideal)',
                ],
                [
                    'label'  => 'Categories listed',
                    'points' => $catPts,
                    'max'    => $catMax,
                    'status' => $catPts >= $catMax ? 'good' : 'warn',
                    'detail' => $categories . ' categor' . ($categories === 1 ? 'y' : 'ies') . ' (3+ recommended)',
                ],
            ];

            // Build prioritised actions only for factors below max, largest gap first.
            $actionMap = [
                'Portfolio photos'  => 'Add ' . max(0, 15 - $photos) . ' more photos to reach the 15+ range that tends to convert best.',
                'Highlight video'   => 'Add a short 30–60s highlight video — profiles with a reel are often browsed longer.',
                'Client reviews'    => 'Request ' . max(0, 20 - $reviews) . ' more reviews from past clients to strengthen social proof.',
                'Average rating'    => 'Follow up on lower-rated jobs and encourage happy clients to leave feedback to lift your average.',
                'Responsiveness'    => 'Aim to reply within 2 hours — faster responses are commonly associated with more booked inquiries.',
                'Categories listed' => 'List at least 3 relevant service categories so more searches surface your profile.',
            ];

            $gaps = [];
            foreach ($factors as $f) {
                if ($f['points'] < $f['max']) {
                    $gaps[] = [
                        'label' => $f['label'],
                        'gap'   => $f['max'] - $f['points'],
                        'text'  => $actionMap[$f['label']],
                    ];
                }
            }
            usort($gaps, fn ($a, $b) => $b['gap'] <=> $a['gap']);
            $actions = array_map(fn ($g) => $g['text'], $gaps);

            $result = [
                'score'   => $score,
                'grade'   => $grade,
                'factors' => $factors,
                'actions' => $actions,
                'summary' => 'Estimated profile score: ' . $score . '/100 (grade ' . $grade . '). '
                    . (count($actions) > 0
                        ? 'There are ' . count($actions) . ' area' . (count($actions) === 1 ? '' : 's') . ' with room to improve — see the suggestions below.'
                        : 'Every scored area is already at full points based on the values you entered.')
                    . ' This score is an estimate to help you prioritise, not a guarantee of results.',
            ];
        } catch (Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }

        return response()->json([
            'success' => true,
            'result'  => $result,
        ]);
    }
}
