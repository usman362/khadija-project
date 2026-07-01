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

        // Try the AI provider only when a key is configured; on ANY failure we
        // fall back to the deterministic local engine so the tool always returns
        // a real, usable allocation (no "AI unavailable" dead-ends in demos).
        $result = null;
        $tokens = 0;

        $apiKey = $this->settings->getOpenAIKey();
        if ($apiKey) {
            try {
                [$result, $tokens] = $this->allocateViaAi($apiKey, $input);
            } catch (\Throwable $e) {
                Log::warning('Budget Allocator AI path failed, using local engine', ['error' => $e->getMessage()]);
                $result = null;
            }
        }

        if ($result === null) {
            $result = $this->allocateLocally($input);
        }

        $this->gate->recordUsage($user, AiFeatureCode::BUDGET_ALLOCATOR, $tokens, [
            'event_type'  => $input['event_type'] ?? null,
            'total'       => $input['total_budget'],
            'guest_count' => $input['guest_count'] ?? null,
            'engine'      => $tokens > 0 ? 'ai' : 'local',
        ]);

        return [
            'allocations' => $result['allocations'] ?? [],
            'tips'        => $result['tips'] ?? [],
            'summary'     => $result['summary'] ?? null,
            'total'       => (float) $input['total_budget'],
            'currency'    => $input['currency'] ?? 'USD',
            'tokens_used' => $tokens,
        ];
    }

    /** Call the AI provider. Returns [parsed, tokens] or throws. */
    private function allocateViaAi(string $apiKey, array $input): array
    {
        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $apiKey,
            'Content-Type'  => 'application/json',
        ])->timeout(45)->post('https://api.openai.com/v1/chat/completions', [
            'model'           => $this->settings->getOpenAIModel(),
            'response_format' => ['type' => 'json_object'],
            'messages'        => [
                ['role' => 'system', 'content' => $this->systemPrompt()],
                ['role' => 'user',   'content' => $this->buildPrompt($input)],
            ],
            'temperature' => 0.4,
            'max_tokens'  => 1500,
        ]);

        if (!$response->successful()) {
            throw new RuntimeException('AI provider returned ' . $response->status());
        }

        $data   = $response->json();
        $parsed = $this->parseResponse($data['choices'][0]['message']['content'] ?? null);

        return [$parsed, (int) ($data['usage']['total_tokens'] ?? 0)];
    }

    /**
     * Deterministic, rules-based budget allocation. No external calls — produces
     * a realistic category split that always sums exactly to the total budget.
     */
    public function allocateLocally(array $input): array
    {
        $total    = (float) $input['total_budget'];
        $currency = $input['currency'] ?? 'USD';
        $guests   = (int) ($input['guest_count'] ?? 0);
        $type     = strtolower(trim((string) ($input['event_type'] ?? 'general event')));

        // Category weight profiles per event type (percent, must total 100).
        $profiles = [
            'wedding' => ['Venue & Rentals' => 24, 'Catering & Bar' => 22, 'Photography & Video' => 13, 'Décor & Florals' => 12, 'Entertainment & Music' => 8, 'Attire & Beauty' => 7, 'Planning & Coordination' => 5, 'Stationery & Favors' => 3, 'Transportation' => 2, 'Contingency' => 4],
            'corporate' => ['Venue & Rentals' => 22, 'Catering & Bar' => 20, 'AV & Production' => 18, 'Speakers & Program' => 10, 'Marketing & Branding' => 8, 'Staffing' => 8, 'Décor' => 5, 'Transportation' => 4, 'Contingency' => 5],
            'conference' => ['Venue & Rentals' => 24, 'Catering & Bar' => 18, 'AV & Production' => 18, 'Speakers & Program' => 12, 'Marketing & Branding' => 8, 'Staffing' => 8, 'Signage & Print' => 4, 'Transportation' => 3, 'Contingency' => 5],
            'birthday' => ['Venue & Rentals' => 20, 'Catering & Bar' => 24, 'Entertainment & Music' => 16, 'Décor & Balloons' => 14, 'Cake & Desserts' => 8, 'Photography' => 7, 'Favors' => 5, 'Contingency' => 6],
            'gala' => ['Venue & Rentals' => 22, 'Catering & Bar' => 24, 'Entertainment & Program' => 14, 'Décor & Florals' => 14, 'AV & Lighting' => 10, 'Staffing' => 7, 'Photography' => 4, 'Contingency' => 5],
            'baby shower' => ['Venue & Rentals' => 18, 'Catering & Bar' => 26, 'Décor & Balloons' => 18, 'Cake & Desserts' => 12, 'Games & Activities' => 8, 'Favors' => 8, 'Photography' => 5, 'Contingency' => 5],
        ];

        // Pick the best-matching profile by keyword, else a balanced default.
        $weights = $profiles['wedding'];
        $matched = false;
        foreach ($profiles as $key => $w) {
            if (str_contains($type, $key)) { $weights = $w; $matched = true; break; }
        }
        if (!$matched) {
            $weights = ['Venue & Rentals' => 25, 'Catering & Bar' => 25, 'Entertainment & Music' => 12, 'Décor & Florals' => 12, 'Photography & Video' => 10, 'Staffing' => 6, 'Transportation' => 5, 'Contingency' => 5];
        }

        // Nudge weights toward user priorities (e.g. "Photography, Catering").
        if (!empty($input['priorities'])) {
            foreach (array_map('trim', explode(',', (string) $input['priorities'])) as $pri) {
                if ($pri === '') continue;
                foreach ($weights as $cat => $val) {
                    if (stripos($cat, $pri) !== false || stripos($pri, explode(' ', $cat)[0]) !== false) {
                        $weights[$cat] = $val + 5;
                    }
                }
            }
        }

        // Large guest counts push more toward catering (per-head driven).
        if ($guests >= 200 && isset($weights['Catering & Bar'])) {
            $weights['Catering & Bar'] += 4;
        }

        // Re-normalise to 100, compute amounts, fix rounding to hit the exact total.
        $sum = array_sum($weights);
        $allocations = [];
        $running = 0.0;
        foreach ($weights as $cat => $w) {
            $pct    = round($w / $sum * 100, 1);
            $amount = round($total * $w / $sum);
            $running += $amount;
            $allocations[] = ['category' => $cat, 'percent' => $pct, 'amount' => (float) $amount, 'notes' => $this->categoryNote($cat, (float) $amount, $guests, $currency)];
        }
        // Absorb any rounding remainder into Contingency (or the last row).
        $diff = $total - $running;
        if (abs($diff) >= 1) {
            for ($i = count($allocations) - 1; $i >= 0; $i--) {
                if (str_contains($allocations[$i]['category'], 'Contingency') || $i === count($allocations) - 1) {
                    $allocations[$i]['amount'] += $diff;
                    break;
                }
            }
        }

        return [
            'summary' => 'Recommended allocation for a ' . ($input['event_type'] ?? 'general') . ' of '
                . ($guests ? number_format($guests) . ' guests' : 'your size')
                . ', based on typical ' . $currency . ' ' . number_format($total) . ' event splits'
                . (!empty($input['priorities']) ? ', weighted toward your priorities.' : '.'),
            'allocations' => $allocations,
            'tips'        => $this->localTips($total, $guests, $currency, $allocations),
        ];
    }

    private function categoryNote(string $cat, float $amount, int $guests, string $currency): string
    {
        if ($guests > 0 && str_contains($cat, 'Catering')) {
            return 'About ' . $currency . ' ' . number_format($amount / max(1, $guests), 0) . ' per guest — food, service & beverages.';
        }
        return match (true) {
            str_contains($cat, 'Venue')        => 'Rental, tables, chairs, linens and core setup.',
            str_contains($cat, 'Photo')         => 'Coverage, editing and a delivered gallery.',
            str_contains($cat, 'Décor')         => 'Centerpieces, florals, lighting and styling.',
            str_contains($cat, 'Entertainment') => 'DJ, band or performers and sound.',
            str_contains($cat, 'AV')            => 'Sound, screens, staging and technicians.',
            str_contains($cat, 'Contingency')   => 'Buffer for last-minute changes and overages.',
            default                             => 'Allocated based on typical event ratios.',
        };
    }

    private function localTips(float $total, int $guests, string $currency, array $allocations): array
    {
        $tips = [];
        if ($guests > 0) {
            $tips[] = 'Your per-guest budget is roughly ' . $currency . ' ' . number_format($total / max(1, $guests), 0) . '. Confirm caterer minimums early.';
        }
        $tips[] = 'Keep the Contingency line untouched until the final two weeks — it covers the surprises every event has.';
        $tips[] = 'Book the top two categories (' . $allocations[0]['category'] . ', ' . ($allocations[1]['category'] ?? $allocations[0]['category']) . ') first; they drive availability and price.';
        if ($total < 5000) {
            $tips[] = 'On a lean budget, prioritise one standout element and keep the rest simple rather than spreading thin.';
        } else {
            $tips[] = 'Ask vendors for package deals across categories — bundling often saves 8–12%.';
        }
        return $tips;
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
