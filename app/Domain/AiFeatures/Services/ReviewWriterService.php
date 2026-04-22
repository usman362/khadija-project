<?php

namespace App\Domain\AiFeatures\Services;

use App\Domain\AiFeatures\AiFeatureCode;
use App\Domain\Settings\Services\SettingsService;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
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

        $apiKey = $this->settings->getOpenAIKey();
        if (!$apiKey) {
            throw new RuntimeException('AI service is not configured. Please contact support.');
        }

        $prompt = $this->buildPrompt($input);

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $apiKey,
                'Content-Type'  => 'application/json',
            ])->timeout(45)->post('https://api.openai.com/v1/chat/completions', [
                'model' => $this->settings->getOpenAIModel(),
                'response_format' => ['type' => 'json_object'],
                'messages' => [
                    ['role' => 'system', 'content' => $this->systemPrompt()],
                    ['role' => 'user',   'content' => $prompt],
                ],
                'temperature' => 0.7,
                'max_tokens'  => 800,
            ]);

            if (!$response->successful()) {
                Log::error('Review Writer OpenAI error', ['status' => $response->status(), 'body' => $response->body()]);
                throw new RuntimeException('AI service is temporarily unavailable. Please try again.');
            }

            $data    = $response->json();
            $content = $data['choices'][0]['message']['content'] ?? null;
            $tokens  = $data['usage']['total_tokens'] ?? 0;

            if (!$content) {
                throw new RuntimeException('Empty response from AI. Please try again.');
            }

            $parsed = json_decode($content, true);
            if (!is_array($parsed) || empty($parsed['review'])) {
                Log::warning('Review Writer response not parseable', ['content' => $content]);
                throw new RuntimeException('AI returned an unexpected format. Please try again.');
            }

            $this->gate->recordUsage($user, AiFeatureCode::REVIEW_WRITER, $tokens, [
                'professional' => $input['professional_name'] ?? null,
                'rating'       => (int) ($input['rating'] ?? 0),
                'tone'         => $input['tone'] ?? 'balanced',
            ]);

            return [
                'review'      => trim($parsed['review']),
                'short'       => trim($parsed['short'] ?? ''),
                'tokens_used' => $tokens,
            ];
        } catch (RuntimeException $e) {
            throw $e;
        } catch (\Throwable $e) {
            Log::error('Review Writer exception', ['error' => $e->getMessage()]);
            throw new RuntimeException('Unable to generate review. Please try again.');
        }
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
