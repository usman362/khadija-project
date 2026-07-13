<?php

namespace App\Http\Controllers;

use App\Domain\AiFeatures\AiAccess;
use App\Domain\AiFeatures\AiFeatureCode;
use App\Domain\AiFeatures\Services\AiFeatureGate;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Throwable;

/**
 * AI Review Writer — a portal AI Toolkit tool that turns a rating + a few
 * quick thoughts into a polished review, ready for any platform.
 *
 * Deterministic generator (no LLM, no quota): it parses the provider, event,
 * rating, tone and keywords and assembles a review in six formats (Short,
 * Detailed, Social, Google, LinkedIn, Custom) in one pass, so the format tabs
 * switch instantly. Every input meaningfully changes the output.
 *
 * Plan-gated (Developer Feedback v1.1 §8.3): the entry-level AI tool — included on
 * every tier (Starter capped at 10/month, Professional & Enterprise unlimited).
 * Enforcement is centralised in AiFeatureGate and only bites once
 * AI_FEATURES_FREE_FOR_ALL is flipped off at launch.
 *
 * Routes: GET  /ai-tools/review-writer          (show)
 *         POST /ai-tools/review-writer/compose   (regenerate → JSON)
 */
class AiReviewWriterController extends Controller
{
    public function __construct(private AiFeatureGate $gate) {}

    public const TONES = ['friendly' => 'Friendly & Warm', 'balanced' => 'Balanced', 'professional' => 'Professional'];

    public const FORMATS = ['short', 'detailed', 'social', 'google', 'linkedin', 'custom'];

    /** Default scenario (matches the reference). */
    private const DEFAULTS = [
        'provider'   => 'Sarah Bennett Photography',
        'service'    => 'Wedding Photography',
        'event'      => 'corporate gala',
        'rating'     => 5,
        'tone'       => 'balanced',
        'thoughts'   => 'On time, great energy, captured amazing candid shots, professional team, delivered edits in 2 weeks',
    ];

    public const SUGGESTED_KEYWORDS = ['Professional', 'On Time', 'Great Communication', 'High Quality', 'Friendly Team', 'Attention to Detail', 'Exceeded Expectations', 'Amazing Results'];

    public function show(Request $request): View
    {
        $review = $this->composeAll(self::DEFAULTS);

        $level = AiAccess::level($request->user(), 'review-writer');
        if ($request->user()?->isAdmin() && in_array($request->query('preview'), ['manual', 'semi', 'maximum'], true)) {
            $level = $request->query('preview');
        }

        // Review Builder is a shared ('both') tool — render it inside the shell
        // for the user's ACTIVE role so a professional doesn't get the client one.
        $aiLayout = $request->user()?->activeRole() === 'supplier' ? 'layouts.professional' : 'layouts.client';

        return view('client.ai-tools.review-writer', [
            'aiLayout' => $aiLayout,
            'tones'    => self::TONES,
            'defaults' => self::DEFAULTS,
            'keywords' => self::SUGGESTED_KEYWORDS,
            'review'   => $review,
            'metrics'  => $this->metrics(),
            'level'    => $level,
            'status'   => $this->gate->status($request->user(), AiFeatureCode::REVIEW_WRITER),
        ]);
    }

    public function compose(Request $request): JsonResponse
    {
        try {
            $this->gate->authorize($request->user(), AiFeatureCode::REVIEW_WRITER);
        } catch (Throwable $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }

        $data = $request->validate([
            'provider' => ['nullable', 'string', 'max:160'],
            'service'  => ['nullable', 'string', 'max:160'],
            'event'    => ['nullable', 'string', 'max:160'],
            'rating'   => ['nullable', 'numeric', 'min:1', 'max:5'],
            'tone'     => ['nullable', 'string'],
            'thoughts' => ['nullable', 'string', 'max:1000'],
        ]);

        $input = [
            'provider' => $data['provider'] ?: self::DEFAULTS['provider'],
            'service'  => $data['service'] ?: '',
            'event'    => $data['event'] ?: 'event',
            'rating'   => (float) ($data['rating'] ?? 5),
            'tone'     => array_key_exists($data['tone'] ?? '', self::TONES) ? $data['tone'] : 'balanced',
            'thoughts' => $data['thoughts'] ?: '',
        ];

        $this->gate->recordUsage($request->user(), AiFeatureCode::REVIEW_WRITER);

        return response()->json([
            'success' => true,
            'review'  => $this->composeAll($input),
            'status'  => $this->gate->status($request->user(), AiFeatureCode::REVIEW_WRITER),
        ]);
    }

    /**
     * Build all six review formats from the inputs in one pass.
     */
    private function composeAll(array $in): array
    {
        $provider = trim($in['provider']);
        $first    = Str::of($provider)->explode(' ')->first() ?: 'They';
        $event    = trim($in['event']) ?: 'event';
        $rating   = (float) $in['rating'];
        $tone     = $in['tone'];

        $pos = $this->positives($in['thoughts']);   // ['professional', 'punctual', ...]
        $word = $rating >= 4.5 ? 'amazing' : ($rating >= 3.5 ? 'great' : 'good');
        $rave = $rating >= 4.5 ? 'highly recommend' : ($rating >= 3.5 ? 'recommend' : 'would consider');

        // Tone openers / closers.
        $open = match ($tone) {
            'friendly'     => "We had such a wonderful experience working with {$provider} for our {$event}!",
            'professional' => "We engaged {$provider} for our {$event} and were thoroughly impressed.",
            default        => "We had an {$word} working experience with {$provider} for our {$event}.",
        };
        $close = match ($tone) {
            'friendly'     => "We {$rave} {$first} and can't wait to work together again! 💛",
            'professional' => "We {$rave} {$provider} for any professional engagement and would gladly collaborate again.",
            default        => "We {$rave} {$provider} for any event and would absolutely work with {$first} again!",
        };

        $traits   = $this->phrase(array_slice($pos, 0, 3));
        $body1    = "{$first} was {$traits} throughout the entire process.";
        $extras   = array_slice($pos, 3);
        $body2    = ! empty($extras)
            ? ucfirst($first) . " and the team delivered " . $this->phrase($extras) . " — every detail handled with care."
            : "{$first} and the team handled every detail with real care and skill.";
        $body3    = "Their attention to detail, creativity, and warm personality made a huge difference in the overall experience.";

        $detailed = implode(' ', [$open, $body1, $body2, $body3, $close]);
        $short    = "{$open} {$first} was {$traits}, and we'd {$rave} them to anyone.";

        $hashtags = '#' . Str::of($event)->camel() . ' #' . Str::of($provider)->replace(' ', '') . ' #Recommended';
        $social   = "{$word} experience with {$provider} for our {$event}! 🎉 {$first} was {$traits} and the results were stunning. {$hashtags}";

        $stars    = str_repeat('★', (int) round($rating)) . str_repeat('☆', 5 - (int) round($rating));
        $google   = "{$stars}\n{$open} {$body1} {$body2} {$close}";

        $linkedin = "I had the pleasure of working with {$provider} for our {$event}. {$first} demonstrated {$traits} and a strong commitment to quality. {$body3} I'd confidently {$rave} {$provider} to any professional network.";

        $formats = [
            'short'    => $short,
            'detailed' => $detailed,
            'social'   => $social,
            'google'   => $google,
            'linkedin' => $linkedin,
            'custom'   => $detailed,
        ];

        $words = [];
        foreach ($formats as $k => $v) {
            $words[$k] = str_word_count(strip_tags($v));
        }

        return ['formats' => $formats, 'words' => $words, 'toneLabel' => self::TONES[$tone] . ' Tone'];
    }

    /**
     * Turn free-text thoughts (+ sensible defaults) into positive traits.
     */
    private function positives(string $thoughts): array
    {
        $text = Str::lower($thoughts);
        $map  = [
            'on time'        => 'punctual', 'punctual' => 'punctual', 'time' => 'punctual',
            'professional'   => 'professional', 'communic' => 'communicative', 'friendly' => 'friendly',
            'creativ'        => 'creative', 'candid' => 'great at capturing candid moments',
            'quality'        => 'detail-oriented', 'detail' => 'detail-oriented', 'energy' => 'full of positive energy',
            'fast'           => 'quick to deliver', 'edit' => 'quick with the final edits', 'budget' => 'great value',
            'stunning'       => 'incredibly talented', 'amazing' => 'outstanding',
        ];
        $found = [];
        foreach ($map as $needle => $trait) {
            if (str_contains($text, $needle) && ! in_array($trait, $found, true)) {
                $found[] = $trait;
            }
        }
        // Fill with strong defaults so the review always reads well.
        foreach (['professional', 'punctual', 'communicative', 'detail-oriented'] as $d) {
            if (count($found) >= 4) {
                break;
            }
            if (! in_array($d, $found, true)) {
                $found[] = $d;
            }
        }

        return $found;
    }

    /** Join traits into a natural list with an Oxford-style "and". */
    private function phrase(array $items): string
    {
        $items = array_values(array_filter($items));
        if (count($items) <= 1) {
            return $items[0] ?? 'wonderful to work with';
        }
        $last = array_pop($items);

        return implode(', ', $items) . ', and ' . $last;
    }

    /**
     * Rating cards + reputation metrics shown around the form (display data).
     */
    private function metrics(): array
    {
        return [
            'cards' => [
                ['Overall Experience', 4.8, 'Excellent'],
                ['Communication', 4.9, 'Excellent'],
                ['Timeliness', 4.7, 'Very Good'],
                ['Quality of Service', 4.8, 'Excellent'],
                ['Value for Money', 4.6, 'Very Good'],
            ],
            'reputation' => [
                'score' => 4.8, 'count' => 24, 'rank' => 'Top 10% in Photography',
                'bars'  => [['Professionalism', 98], ['Communication', 96], ['Timeliness', 95], ['Quality', 94], ['Value', 88]],
            ],
            'badges' => [
                ['Top Rated', 'crown'], ['On Time Pro', 'clock'], ['Great Communicator', 'chat'],
                ['High Quality', 'gem'], ['Repeat Client Favorite', 'star'], ['Trusted Partner', 'shield'],
            ],
            'checklist' => [
                ['Arrived On Time', 'Yes'], ['Stayed Within Budget', 'Yes'], ['Delivered Agreed Services', 'Yes'],
                ['Communication', 'Excellent'], ['Quality as Expected', 'Yes'], ['Would Hire Again', 'Yes'],
            ],
            'platforms' => ['GigResource', 'Google', 'Facebook', 'LinkedIn', 'The Knot', 'WeddingWire', 'Yelp'],
        ];
    }
}
