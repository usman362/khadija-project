<?php

namespace App\Domain\AiFeatures\Services;

use App\Domain\AiFeatures\AiFeatureCode;
use App\Domain\Auth\Enums\RoleName;
use App\Domain\Settings\Services\SettingsService;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class VendorMatchmakingService
{
    /** Max candidates to send to the LLM in a single request. */
    private const MAX_CANDIDATES = 25;

    public function __construct(
        private SettingsService $settings,
        private AiFeatureGate $gate,
    ) {}

    /**
     * Rank suppliers against the user's requirement and return the best matches.
     * Returns: ['matches' => [...], 'summary' => string, 'total_candidates' => int]
     */
    public function match(User $user, array $input): array
    {
        $this->gate->authorize($user, AiFeatureCode::VENDOR_MATCHMAKING);

        // ── 1. Pre-filter supplier candidates from DB (always real) ──
        $candidates = $this->fetchCandidates($input);

        if ($candidates->isEmpty()) {
            throw new RuntimeException('No matching professionals found. Try adjusting your budget or requirements.');
        }

        // ── 2. Rank: AI when configured, else deterministic local scoring so
        //        the tool always returns real, ranked matches (no dead-ends). ──
        $apiKey   = $this->settings->getOpenAIKey();
        $aiResult = null;
        if ($apiKey) {
            try {
                $aiResult = $this->rankWithAI($apiKey, $input, $candidates);
            } catch (\Throwable $e) {
                Log::warning('Matchmaking AI path failed, using local scoring', ['error' => $e->getMessage()]);
                $aiResult = null;
            }
        }
        if ($aiResult === null) {
            $aiResult = $this->rankLocally($input, $candidates);
        }

        // ── 3. Enrich AI ranking with full supplier data ──
        $matches = [];
        foreach ($aiResult['matches'] ?? [] as $m) {
            $supplier = $candidates->firstWhere('id', (int) ($m['supplier_id'] ?? 0));
            if (!$supplier) continue;

            $matches[] = [
                'supplier_id'     => $supplier->id,
                'name'            => $supplier->name,
                'avatar_url'      => $supplier->avatar_url,
                'headline'        => $supplier->profile?->headline,
                'hourly_rate'     => $supplier->profile?->hourly_rate,
                'experience'      => $supplier->profile?->experience_years,
                'skills'          => $supplier->profile?->skills ?? [],
                'availability'    => $supplier->profile?->availability,
                'verified_badges' => $supplier->profile?->verifiedBadges() ?? [], // ['trade_license', ...]
                'rank'            => (int) ($m['rank'] ?? 999),
                'match_score'     => (int) ($m['match_score'] ?? 0),
                'reasoning'       => (string) ($m['reasoning'] ?? ''),
            ];
        }

        // Sort by rank to be safe
        usort($matches, fn($a, $b) => $a['rank'] <=> $b['rank']);

        // Record usage
        $this->gate->recordUsage($user, AiFeatureCode::VENDOR_MATCHMAKING, $aiResult['tokens'] ?? 0, [
            'event_type'      => $input['event_type'] ?? null,
            'total_candidates'=> $candidates->count(),
            'matches_returned'=> count($matches),
        ]);

        return [
            'matches'          => $matches,
            'summary'          => $aiResult['summary'] ?? null,
            'total_candidates' => $candidates->count(),
        ];
    }

    /**
     * Pre-filter suppliers based on availability and optional budget.
     */
    private function fetchCandidates(array $input): \Illuminate\Support\Collection
    {
        $budgetMax = !empty($input['budget']) ? (float) $input['budget'] : null;

        $query = User::query()
            ->whereHas('roles', fn($q) => $q->where('name', RoleName::SUPPLIER->value))
            ->whereHas('profile')
            ->with('profile')
            ->whereNull('deletion_scheduled_at');

        // Budget filter: rough heuristic — suppliers whose hourly_rate is at most the budget
        // (we don't know hours, so use budget as upper bound for hourly rate * 1.0)
        if ($budgetMax) {
            $query->whereHas('profile', function ($q) use ($budgetMax) {
                $q->where(function ($inner) use ($budgetMax) {
                    $inner->whereNull('hourly_rate')
                          ->orWhere('hourly_rate', '<=', $budgetMax);
                });
            });
        }

        // Prefer available suppliers first
        $query->with(['profile' => fn($q) => $q->orderByRaw("CASE availability WHEN 'available' THEN 1 WHEN 'busy' THEN 2 ELSE 3 END")]);

        return $query->take(self::MAX_CANDIDATES)->get();
    }

    /**
     * Deterministic local scoring — no external calls. Ranks the REAL DB
     * candidates by services match, budget fit, experience, availability,
     * verified status and rating. Returns the same shape as rankWithAI().
     */
    private function rankLocally(array $input, \Illuminate\Support\Collection $candidates): array
    {
        $budget = !empty($input['budget']) ? (float) $input['budget'] : null;
        $needle = strtolower(trim(
            ($input['event_type'] ?? '') . ' ' . ($input['requirements'] ?? '') . ' ' . ($input['notes'] ?? '')
        ));

        $scored = $candidates->map(function (User $u) use ($budget, $needle) {
            $p = $u->profile;
            $score = 50;
            $reasons = [];

            // Keyword overlap between the requirement and the pro's profile.
            $hay = strtolower(
                ($p?->headline ?? '') . ' '
                . (is_array($p?->skills) ? implode(' ', $p->skills) : '') . ' '
                . ($p?->bio ?? '')
            );
            $overlap = 0;
            foreach (array_filter(explode(' ', $needle)) as $w) {
                if (strlen($w) >= 4 && str_contains($hay, $w)) {
                    $overlap++;
                }
            }
            if ($overlap) {
                $score += min(20, $overlap * 5);
                $reasons[] = 'services match your request';
            }

            // Budget fit.
            if ($budget && $p?->hourly_rate) {
                if ($p->hourly_rate <= $budget) {
                    $score += 12;
                    $reasons[] = 'within budget';
                } else {
                    $score -= 10;
                }
            }

            // Experience.
            $yrs = (int) ($p?->experience_years ?? 0);
            if ($yrs >= 8) {
                $score += 12;
                $reasons[] = $yrs . ' yrs experience';
            } elseif ($yrs >= 3) {
                $score += 6;
            }

            // Availability.
            if (($p?->availability ?? '') === 'available') {
                $score += 8;
                $reasons[] = 'available now';
            }

            // Verified badges.
            $badges = $p?->verifiedBadges() ?? [];
            if (count($badges)) {
                $score += min(10, count($badges) * 4);
                $reasons[] = 'verified pro';
            }

            // Rating.
            $rating = round((float) $u->reviewsReceived()->where('is_hidden', false)->avg('rating'), 1);
            if ($rating >= 4.5) {
                $score += 10;
                $reasons[] = $rating . '★ rated';
            } elseif ($rating >= 4.0) {
                $score += 5;
            }

            return ['id' => $u->id, 'score' => max(35, min(99, $score)), 'reasons' => $reasons];
        })->sortByDesc('score')->values();

        $matches = [];
        foreach ($scored as $i => $s) {
            $matches[] = [
                'supplier_id' => $s['id'],
                'rank'        => $i + 1,
                'match_score' => $s['score'],
                'reasoning'   => $s['reasons']
                    ? ucfirst(implode(', ', $s['reasons'])) . '.'
                    : 'Solid all-round fit for your event.',
            ];
        }

        return [
            'matches' => $matches,
            'summary' => 'Ranked ' . $candidates->count() . ' professionals for your '
                . ($input['event_type'] ?? 'event')
                . ' by services match, budget fit, experience, availability and verified status.',
            'tokens'  => 0,
        ];
    }

    /**
     * Send candidates to OpenAI and get a ranked JSON response.
     */
    private function rankWithAI(string $apiKey, array $input, \Illuminate\Support\Collection $candidates): array
    {
        $candidatesSummary = $candidates->map(fn($u) => $this->summarizeSupplier($u))->implode("\n\n");

        $requirement = $this->buildRequirementText($input);

        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $apiKey,
            'Content-Type'  => 'application/json',
        ])->timeout(60)->post('https://api.openai.com/v1/chat/completions', [
            'model' => $this->settings->getOpenAIModel(),
            'response_format' => ['type' => 'json_object'],
            'messages' => [
                ['role' => 'system', 'content' => $this->systemPrompt()],
                ['role' => 'user',   'content' => "CLIENT REQUIREMENT:\n{$requirement}\n\n\nAVAILABLE PROFESSIONALS:\n{$candidatesSummary}"],
            ],
            'temperature' => 0.3,
            'max_tokens'  => 2000,
        ]);

        if (!$response->successful()) {
            Log::error('Matchmaking OpenAI error', ['status' => $response->status(), 'body' => $response->body()]);
            throw new RuntimeException('AI service is temporarily unavailable. Please try again.');
        }

        $data    = $response->json();
        $content = $data['choices'][0]['message']['content'] ?? null;
        $tokens  = $data['usage']['total_tokens'] ?? 0;

        if (!$content) {
            throw new RuntimeException('Empty response from AI. Please try again.');
        }

        $parsed = json_decode($content, true);
        if (!is_array($parsed) || !isset($parsed['matches'])) {
            Log::warning('Matchmaking response not parseable', ['content' => $content]);
            throw new RuntimeException('AI returned an unexpected format. Please try again.');
        }

        return [
            'matches' => $parsed['matches'],
            'summary' => $parsed['summary'] ?? null,
            'tokens'  => $tokens,
        ];
    }

    private function summarizeSupplier(User $u): string
    {
        $p = $u->profile;
        $skills = is_array($p?->skills) ? implode(', ', $p->skills) : 'not specified';

        return sprintf(
            "ID:%d | %s\nHeadline: %s\nHourly Rate: %s\nExperience: %s years\nAvailability: %s\nSkills: %s\nBio: %s",
            $u->id,
            $u->name,
            $p?->headline ?: 'n/a',
            $p?->hourly_rate ? '$' . number_format((float) $p->hourly_rate, 2) : 'not specified',
            $p?->experience_years ?? 'n/a',
            $p?->availability ?: 'n/a',
            $skills,
            \Illuminate\Support\Str::limit($p?->bio ?: 'n/a', 200),
        );
    }

    private function buildRequirementText(array $input): string
    {
        $parts = [
            "Event Type: " . ($input['event_type'] ?? 'general'),
        ];
        if (!empty($input['budget']))        $parts[] = "Budget: $" . number_format((float) $input['budget'], 2);
        if (!empty($input['guest_count']))   $parts[] = "Guest Count: " . $input['guest_count'];
        if (!empty($input['location']))      $parts[] = "Location: " . $input['location'];
        if (!empty($input['date']))          $parts[] = "Event Date: " . $input['date'];
        if (!empty($input['requirements']))  $parts[] = "Requirements/Notes:\n" . $input['requirements'];

        return implode("\n", $parts);
    }

    private function systemPrompt(): string
    {
        return <<<'PROMPT'
You are an expert event matchmaker. Given a client's event requirement and a list of professional candidates with their details, select the top 5 best-matching professionals.

Return STRICT JSON in this exact shape (no markdown, no commentary outside JSON):

{
  "summary": "1-2 sentence overview of why these matches were selected",
  "matches": [
    {
      "supplier_id": 123,
      "rank": 1,
      "match_score": 92,
      "reasoning": "Specific 1-2 sentence justification tying this supplier's skills/experience/rate to the client's requirement"
    }
  ]
}

Rules:
- Return up to 5 matches, ranked 1-5 (rank 1 = best)
- match_score is 0-100 (integer)
- Only use supplier_ids that appear in the provided list
- The reasoning must be CONCRETE — cite specific skills, experience, or rate that match the requirement
- If fewer than 5 are good fits, return fewer matches (quality over quantity)
- Never invent supplier details — only use what's provided
PROMPT;
    }
}
