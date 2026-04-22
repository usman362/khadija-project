<?php

namespace App\Domain\AiFeatures\Services;

use App\Domain\AiFeatures\AiFeatureCode;
use App\Domain\Settings\Services\SettingsService;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class BudgetAllocatorService
{
    public function __construct(
        private SettingsService $settings,
        private AiFeatureGate $gate,
    ) {}

    /**
     * Generate a budget allocation plan via OpenAI.
     * Returns: ['allocations' => [...], 'tips' => [...], 'total' => number, 'currency' => string]
     */
    public function allocate(User $user, array $input): array
    {
        $this->gate->authorize($user, AiFeatureCode::BUDGET_ALLOCATOR);

        $apiKey = $this->settings->getOpenAIKey();
        if (!$apiKey) {
            throw new RuntimeException('AI service is not configured. Please contact support.');
        }

        $prompt = $this->buildPrompt($input);
        $model  = $this->settings->getOpenAIModel();

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $apiKey,
                'Content-Type'  => 'application/json',
            ])->timeout(45)->post('https://api.openai.com/v1/chat/completions', [
                'model'       => $model,
                'response_format' => ['type' => 'json_object'],
                'messages'    => [
                    ['role' => 'system', 'content' => $this->systemPrompt()],
                    ['role' => 'user',   'content' => $prompt],
                ],
                'temperature' => 0.4,
                'max_tokens'  => 1500,
            ]);

            if (!$response->successful()) {
                Log::error('Budget Allocator OpenAI error', ['status' => $response->status(), 'body' => $response->body()]);
                throw new RuntimeException('AI service is temporarily unavailable. Please try again in a moment.');
            }

            $data    = $response->json();
            $content = $data['choices'][0]['message']['content'] ?? null;
            $tokens  = $data['usage']['total_tokens'] ?? 0;

            $parsed = $this->parseResponse($content);

            // Record usage AFTER successful generation
            $this->gate->recordUsage($user, AiFeatureCode::BUDGET_ALLOCATOR, $tokens, [
                'event_type'  => $input['event_type'] ?? null,
                'total'       => $input['total_budget'],
                'guest_count' => $input['guest_count'] ?? null,
            ]);

            return [
                'allocations' => $parsed['allocations'] ?? [],
                'tips'        => $parsed['tips'] ?? [],
                'summary'     => $parsed['summary'] ?? null,
                'total'       => (float) $input['total_budget'],
                'currency'    => $input['currency'] ?? 'USD',
                'tokens_used' => $tokens,
            ];
        } catch (RuntimeException $e) {
            throw $e;
        } catch (\Throwable $e) {
            Log::error('Budget Allocator exception', ['error' => $e->getMessage()]);
            throw new RuntimeException('Unable to generate budget. Please try again.');
        }
    }

    private function systemPrompt(): string
    {
        return <<<'PROMPT'
You are an expert event budget planner. Given an event's details and total budget, produce a realistic category-by-category allocation.

Return STRICT JSON in this exact shape (no markdown, no commentary outside JSON):

{
  "summary": "1-2 sentence overview of the allocation strategy",
  "allocations": [
    { "category": "Venue", "percent": 30, "amount": 1500, "notes": "Brief justification" },
    { "category": "Catering", "percent": 25, "amount": 1250, "notes": "..." }
  ],
  "tips": [
    "Short practical tip 1",
    "Short practical tip 2",
    "Short practical tip 3"
  ]
}

Rules:
- Percentages MUST sum to exactly 100
- Amounts MUST sum to exactly the total budget
- Use 5 to 9 categories relevant to the event type
- Common categories: Venue, Catering, Decor, Photography/Videography, Entertainment/Music, Florals, Attire, Transportation, Staff, Marketing, Contingency
- Be realistic: adjust allocations based on event type and guest count
- Provide actionable tips specific to their budget size
- Round amounts to whole numbers (no decimals)
PROMPT;
    }

    private function buildPrompt(array $input): string
    {
        $currency = $input['currency'] ?? 'USD';
        $parts    = [
            "Please allocate a budget for the following event:",
            "- Event Type: " . ($input['event_type'] ?? 'general event'),
            "- Total Budget: {$currency} " . number_format((float) $input['total_budget'], 2),
            "- Guest Count: " . ($input['guest_count'] ?? 'not specified'),
        ];

        if (!empty($input['location'])) {
            $parts[] = "- Location: " . $input['location'];
        }
        if (!empty($input['date'])) {
            $parts[] = "- Event Date: " . $input['date'];
        }
        if (!empty($input['priorities'])) {
            $parts[] = "- Priorities / Must-haves: " . $input['priorities'];
        }
        if (!empty($input['notes'])) {
            $parts[] = "- Additional Notes: " . $input['notes'];
        }

        return implode("\n", $parts);
    }

    private function parseResponse(?string $content): array
    {
        if (!$content) {
            throw new RuntimeException('Empty response from AI. Please try again.');
        }

        $decoded = json_decode($content, true);

        if (!is_array($decoded) || !isset($decoded['allocations'])) {
            Log::warning('Budget Allocator response not parseable', ['content' => $content]);
            throw new RuntimeException('The AI returned an unexpected format. Please try again.');
        }

        return $decoded;
    }
}
