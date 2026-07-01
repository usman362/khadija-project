<?php

namespace App\Domain\AiFeatures\Services;

use App\Domain\AiFeatures\AiFeatureCode;
use App\Domain\Settings\Services\SettingsService;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use RuntimeException;

class ReviewWriterService
{
    public function __construct(
        private SettingsService $settings,
        private AiFeatureGate $gate,
    ) {}

    /**
     * Generate a polished review text based on user's quick thoughts.
     * Returns: ['review' => string, 'short' => string, 'tokens_used' => int]
     */
    public function compose(User $user, array $input): array
    {
        $this->gate->authorize($user, AiFeatureCode::REVIEW_WRITER);

        // Try the AI provider when configured; fall back to the deterministic
        // template composer so the tool always returns a polished review.
        $result = null;
        $tokens = 0;

        $apiKey = $this->settings->getOpenAIKey();
        if ($apiKey) {
            try {
                [$result, $tokens] = $this->composeViaAi($apiKey, $input);
            } catch (\Throwable $e) {
                Log::warning('Review Writer AI path failed, using local composer', ['error' => $e->getMessage()]);
                $result = null;
            }
        }

        if ($result === null) {
            $result = $this->composeLocally($input);
        }

        $this->gate->recordUsage($user, AiFeatureCode::REVIEW_WRITER, $tokens, [
            'professional' => $input['professional_name'] ?? null,
            'rating'       => (int) ($input['rating'] ?? 0),
            'tone'         => $input['tone'] ?? 'balanced',
        ]);

        return [
            'review'      => $result['review'],
            'short'       => $result['short'] ?? '',
            'tokens_used' => $tokens,
        ];
    }

    /** Call the AI provider. Returns [['review','short'], tokens] or throws. */
    private function composeViaAi(string $apiKey, array $input): array
    {
        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $apiKey,
            'Content-Type'  => 'application/json',
        ])->timeout(45)->post('https://api.openai.com/v1/chat/completions', [
            'model' => $this->settings->getOpenAIModel(),
            'response_format' => ['type' => 'json_object'],
            'messages' => [
                ['role' => 'system', 'content' => $this->systemPrompt()],
                ['role' => 'user',   'content' => $this->buildPrompt($input)],
            ],
            'temperature' => 0.7,
            'max_tokens'  => 800,
        ]);

        if (!$response->successful()) {
            throw new RuntimeException('AI provider returned ' . $response->status());
        }

        $data   = $response->json();
        $parsed = json_decode($data['choices'][0]['message']['content'] ?? '', true);
        if (!is_array($parsed) || empty($parsed['review'])) {
            throw new RuntimeException('AI returned an unexpected format.');
        }

        return [
            ['review' => trim($parsed['review']), 'short' => trim($parsed['short'] ?? '')],
            (int) ($data['usage']['total_tokens'] ?? 0),
        ];
    }

    /**
     * Deterministic template composer — no external calls. Builds a natural
     * review from the rating, tone and the customer's own thoughts.
     */
    private function composeLocally(array $input): array
    {
        $rating   = (int) ($input['rating'] ?? 5);
        $name     = trim((string) ($input['professional_name'] ?? '')) ?: 'the team';
        $service  = trim((string) ($input['service_type'] ?? ''));
        $event    = trim((string) ($input['event_type'] ?? ''));
        $tone     = strtolower((string) ($input['tone'] ?? 'balanced'));
        $thoughts = trim((string) ($input['thoughts'] ?? ''));

        $eventPhrase   = $event ? " for our {$event}" : '';
        $servicePhrase = $service ? " {$service}" : ' service';

        $open = match (true) {
            $rating >= 5 => "We couldn't be happier with {$name}{$eventPhrase}.",
            $rating === 4 => "We had a genuinely good experience with {$name}{$eventPhrase}.",
            $rating === 3 => "Our experience with {$name}{$eventPhrase} was a mixed one.",
            $rating === 2 => "Unfortunately our experience with {$name}{$eventPhrase} fell short of what we hoped.",
            default       => "Regrettably, {$name} did not meet our expectations{$eventPhrase}.",
        };

        if ($thoughts !== '') {
            $t = rtrim($thoughts, '.');
            $middle = match (true) {
                $rating >= 4  => " What stood out most: {$t}. From start to finish the{$servicePhrase} was handled professionally and with real care.",
                $rating === 3 => " A few things went well — {$t} — though there was room to improve on communication and consistency.",
                default       => " In particular, {$t}. We had hoped for a smoother experience given the booking.",
            };
        } else {
            $middle = match (true) {
                $rating >= 4  => " The{$servicePhrase} was professional, well-organised and delivered exactly what we needed.",
                $rating === 3 => " The{$servicePhrase} was acceptable overall, with a few areas that could be tightened up.",
                default       => " The{$servicePhrase} did not go as smoothly as we expected.",
            };
        }

        $close = match (true) {
            $rating >= 5  => " We'd recommend them without hesitation and would absolutely book again.",
            $rating === 4 => " We'd happily recommend them and would consider booking again.",
            $rating === 3 => " With a few adjustments this could be a strong option for others.",
            $rating === 2 => " We'd be cautious about recommending them without reservations.",
            default       => " We're sharing this so future clients can set their expectations accordingly.",
        };

        if ($tone === 'friendly' && $rating >= 4) {
            $close .= ' Thanks again' . ($name !== 'the team' ? ", {$name}" : '') . '! 🎉';
        }

        $short = match (true) {
            $rating >= 5  => ($service ?: 'Excellent service') . " — highly recommend {$name}!",
            $rating === 4 => "Great experience with {$name} — would recommend.",
            $rating === 3 => "Decent experience with {$name}, a few things to improve.",
            default       => "Mixed experience with {$name} — see the full review.",
        };

        return [
            'review'      => trim($open . $middle . $close),
            'short'       => Str::limit($short, 145),
            'tokens_used' => 0,
        ];
    }

    private function systemPrompt(): string
    {
        return <<<'PROMPT'
You are an expert at writing authentic, helpful customer reviews for event service professionals. Given a star rating and the customer's quick thoughts, produce a polished, natural-sounding review.

Return STRICT JSON in this exact shape (no markdown, no commentary outside JSON):

{
  "review": "A polished 3-5 sentence review (120-200 words) that sounds genuinely human, specific, and helpful to future clients",
  "short": "A 1-2 sentence TL;DR version (under 150 characters) suitable for social media or a card preview"
}

Rules:
- Match the rating: 5★ = enthusiastic praise, 4★ = positive with minor notes, 3★ = balanced, 2★ = disappointed but fair, 1★ = critical but professional
- Match the tone requested: 'friendly', 'professional', or 'balanced'
- Weave the user's specific thoughts/keywords naturally into the review
- Never fabricate specifics not mentioned by the user
- Use first person ("I", "we")
- Be honest, specific, and helpful — avoid generic platitudes
- Never mention "AI" or "artificial intelligence" in the review
PROMPT;
    }

    private function buildPrompt(array $input): string
    {
        $rating = (int) ($input['rating'] ?? 5);
        $stars  = str_repeat('★', $rating) . str_repeat('☆', 5 - $rating);

        $parts = [
            "Write a review with the following details:",
            "",
            "Rating: {$stars} ({$rating}/5)",
            "Professional / Service Provider: " . ($input['professional_name'] ?? 'the service provider'),
        ];

        if (!empty($input['service_type'])) {
            $parts[] = "Service Type: " . $input['service_type'];
        }
        if (!empty($input['event_type'])) {
            $parts[] = "Event: " . $input['event_type'];
        }
        if (!empty($input['tone'])) {
            $parts[] = "Preferred Tone: " . $input['tone'];
        }

        $parts[] = "";
        $parts[] = "Customer's quick thoughts / highlights:";
        $parts[] = $input['thoughts'] ?? '(not provided)';

        return implode("\n", $parts);
    }
}
