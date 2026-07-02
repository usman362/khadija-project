<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Throwable;

/**
 * AI Message Assistant (both). Drafts clear, professional event messages —
 * follow-ups, confirmations, declines — in a chosen tone. Deterministic,
 * template-based composer; no external API.
 */
class AiMessageAssistantController extends Controller
{
    public function show(Request $request): View
    {
        $aiLayout = $request->user()?->activeRole() === 'supplier' ? 'layouts.professional' : 'layouts.client';

        return view('ai-tools.message-assistant', [
            'aiLayout' => $aiLayout,
            'stats' => [
                ['Message Purposes', '5', ''], ['Tone Options', '3', 'good'],
                ['Drafts per Run', '2–3', 'good'], ['Built-in', 'No API', 'good'],
            ],
        ]);
    }

    public function compute(Request $request): JsonResponse
    {
        try {
            $data = $request->validate([
                'purpose'        => ['required', 'string', 'max:200'],
                'recipient_name' => ['nullable', 'string', 'max:120'],
                'tone'           => ['nullable', 'in:friendly,professional,warm'],
                'key_points'     => ['nullable', 'string', 'max:800'],
            ]);

            $result = $this->composeMessages($data);

            return response()->json(['success' => true, 'result' => $result]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => collect($e->errors())->flatten()->first() ?? 'Please check the form and try again.',
            ], 422);
        } catch (Throwable $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }

    /** @param array<string,mixed> $d */
    private function composeMessages(array $d): array
    {
        $purposeRaw = strtolower(trim((string) $d['purpose']));
        $name       = trim((string) ($d['recipient_name'] ?? ''));
        $tone       = (string) ($d['tone'] ?? 'friendly');
        $pointsRaw  = trim((string) ($d['key_points'] ?? ''));

        $greetName = $name !== '' ? " {$name}" : ' there';
        $signName  = $name !== '' ? $name : 'them';

        // Parse key points into a readable list of sentences.
        $points = $this->parsePoints($pointsRaw);
        $pointsSentence = $this->pointsToSentence($points);

        // Map free-text purpose to a known category.
        $category = $this->classifyPurpose($purposeRaw);

        $greeting = match ($tone) {
            'professional' => "Hello{$greetName},",
            'warm'         => "Hi{$greetName},",
            default        => "Hi{$greetName},",
        };

        $signoff = match ($tone) {
            'professional' => "Kind regards,",
            'warm'         => "Warmly,",
            default        => "Thanks so much,",
        };

        $variations = $this->buildVariations($category, $tone, $greeting, $signoff, $pointsSentence, $points);

        $catLabel = match ($category) {
            'follow_up' => 'follow-up after a quote',
            'details'   => 'request for more details',
            'confirm'   => 'booking confirmation',
            'decline'   => 'polite decline',
            'thanks'    => 'thank-you after the event',
            default     => 'general message',
        };

        return [
            'summary'    => "Drafted " . count($variations) . " {$tone} message option(s) for a {$catLabel}" . ($name !== '' ? " to {$name}" : '') . ". Pick the one that fits best and adjust the details before sending.",
            'variations' => $variations,
        ];
    }

    /**
     * @param array<int,string> $points
     * @return array<int,array{label:string,subject:string,body:string}>
     */
    private function buildVariations(string $category, string $tone, string $greeting, string $signoff, string $pointsSentence, array $points): array
    {
        $pointsList = $points ? "\n\n" . implode("\n", array_map(fn ($p) => "• " . $p, $points)) : '';

        [$subjectA, $subjectB, $subjectC] = $this->subjects($category);

        $bodies = match ($category) {
            'follow_up' => [
                "{$greeting}\n\nI wanted to follow up on the quote I sent over. I'd love to work together on your event, and I'm happy to walk through any of the details or adjust the package to suit what you have in mind.{$this->weave($pointsSentence)}\n\nWhenever you're ready, just let me know and we can take the next step.\n\n{$signoff}",
                "{$greeting}\n\nJust checking in on the proposal I shared — I didn't want it to get lost in your inbox. If any part of it would be helpful to revisit, I'm glad to explain or tailor it further.{$pointsList}\n\nHappy to answer any questions whenever it suits you.\n\n{$signoff}",
                "{$greeting}\n\nI hope your planning is going well. I'm following up on the quote to see if you had any thoughts, and to let you know I'm holding your date in mind.{$this->weave($pointsSentence)}\n\nNo rush at all — reply whenever works for you.\n\n{$signoff}",
            ],
            'details' => [
                "{$greeting}\n\nThank you for getting in touch about your event. To put together the right plan, could you share a few more details?{$pointsList}\n\nOnce I have those, I can send something tailored to exactly what you need.\n\n{$signoff}",
                "{$greeting}\n\nI'd love to help with your event. Before I prepare anything, it would help to know a little more.{$this->weave($pointsSentence)} Even rough figures are fine to start with.\n\nLooking forward to hearing back.\n\n{$signoff}",
            ],
            'confirm' => [
                "{$greeting}\n\nGreat news — your booking is confirmed! I'm looking forward to being part of your event.{$this->weave($pointsSentence)}\n\nI'll be in touch closer to the date to finalise the last details. In the meantime, feel free to reach out with anything.\n\n{$signoff}",
                "{$greeting}\n\nThis is to confirm your booking is all set on my side.{$pointsList}\n\nEverything is noted, and I'll follow up nearer the time. Thank you for choosing to work with me.\n\n{$signoff}",
            ],
            'decline' => [
                "{$greeting}\n\nThank you so much for thinking of me for your event — it genuinely means a lot. Unfortunately, I'm not able to take it on this time.{$this->weave($pointsSentence)}\n\nI'd be glad to keep in touch for future dates, and I wish you a wonderful event.\n\n{$signoff}",
                "{$greeting}\n\nI really appreciate you reaching out. After looking at the details, I'm sorry to say I won't be able to help with this one.{$pointsList}\n\nPlease do keep me in mind for another occasion — I'd love the chance to work together down the line.\n\n{$signoff}",
            ],
            'thanks' => [
                "{$greeting}\n\nThank you so much for having me be part of your event — it was a real pleasure.{$this->weave($pointsSentence)}\n\nIf there's ever anything I can help with again, you know where to find me. Wishing you all the best.\n\n{$signoff}",
                "{$greeting}\n\nI just wanted to say how much I enjoyed working with you on your event.{$pointsList}\n\nIt would mean a lot if you felt like sharing a quick review, but either way, thank you for the opportunity.\n\n{$signoff}",
            ],
            default => [
                "{$greeting}\n\nThank you for your message.{$this->weave($pointsSentence)}\n\nDo let me know if there's anything else I can help with — I'm glad to assist.\n\n{$signoff}",
                "{$greeting}\n\nThanks for reaching out.{$pointsList}\n\nI'm happy to help however I can, so just let me know what would be most useful.\n\n{$signoff}",
            ],
        };

        $labels = ['Option A', 'Option B', 'Option C'];
        $subjects = [$subjectA, $subjectB, $subjectC];

        $out = [];
        foreach ($bodies as $i => $body) {
            $out[] = [
                'label'   => $labels[$i] ?? ('Option ' . ($i + 1)),
                'subject' => $subjects[$i] ?? $subjectA,
                'body'    => $body,
            ];
        }

        return $out;
    }

    /** @return array{0:string,1:string,2:string} */
    private function subjects(string $category): array
    {
        return match ($category) {
            'follow_up' => ['Following up on your quote', 'Just checking in', 'Still happy to help with your event'],
            'details'   => ['A few quick questions about your event', 'Getting the details right', 'Quick questions before I quote'],
            'confirm'   => ['Your booking is confirmed', 'All set — booking confirmed', 'Confirmed and looking forward to it'],
            'decline'   => ['Thank you for thinking of me', 'About your event', 'Following up on your enquiry'],
            'thanks'    => ['Thank you!', 'It was a pleasure', 'Thank you for having me'],
            default     => ['Following up', 'A quick note', 'Getting back to you'],
        };
    }

    private function classifyPurpose(string $p): string
    {
        return match (true) {
            str_contains($p, 'follow') || str_contains($p, 'quote') || str_contains($p, 'proposal') || str_contains($p, 'check in') || str_contains($p, 'check-in') => 'follow_up',
            str_contains($p, 'detail') || str_contains($p, 'more info') || str_contains($p, 'question') || str_contains($p, 'ask')                                     => 'details',
            str_contains($p, 'confirm') || str_contains($p, 'booking') || str_contains($p, 'book')                                                                     => 'confirm',
            str_contains($p, 'decline') || str_contains($p, 'unavailable') || str_contains($p, 'not available') || str_contains($p, 'reject') || str_contains($p, 'no ')=> 'decline',
            str_contains($p, 'thank') || str_contains($p, 'after event') || str_contains($p, 'after the event')                                                        => 'thanks',
            default                                                                                                                                                     => 'general',
        };
    }

    /** @return array<int,string> */
    private function parsePoints(string $raw): array
    {
        if ($raw === '') {
            return [];
        }
        $parts = preg_split('/[\r\n]+|(?<=[.;])\s+|,\s+/', $raw) ?: [];
        $parts = array_map('trim', $parts);
        $parts = array_filter($parts, fn ($p) => $p !== '');

        return array_values(array_map(function ($p) {
            $p = trim($p);
            return mb_strtoupper(mb_substr($p, 0, 1)) . mb_substr($p, 1);
        }, $parts));
    }

    /** @param array<int,string> $points */
    private function pointsToSentence(array $points): string
    {
        if (!$points) {
            return '';
        }
        $lower = array_map(fn ($p) => rtrim(mb_strtolower(mb_substr($p, 0, 1)) . mb_substr($p, 1), '.'), $points);
        if (count($lower) === 1) {
            return $lower[0];
        }
        $last = array_pop($lower);
        return implode(', ', $lower) . ' and ' . $last;
    }

    private function weave(string $pointsSentence): string
    {
        return $pointsSentence !== '' ? " A couple of things worth noting: {$pointsSentence}." : '';
    }
}
