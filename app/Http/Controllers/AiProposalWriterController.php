<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

/**
 * AI Proposal Writer — a client-portal AI Toolkit tool that turns a short
 * event description into a polished, ready-to-send proposal, with adjustable
 * tone, focus and length.
 *
 * Like the AI Pricing Assistant, this is a DETERMINISTIC generator: it parses
 * the event description (type, date, theme, service hints) and assembles a
 * tailored proposal from a transparent template model — no LLM call, no AI
 * quota — so it is fast, reliable and not plan-gated. Every input
 * meaningfully changes the output (tone → voice, focus → selling angle,
 * length → depth), so the tool is genuinely dynamic.
 *
 * Routes: GET  /ai-tools/proposal-writer           (show)
 *         POST /ai-tools/proposal-writer/generate  (regenerate → JSON)
 */
class AiProposalWriterController extends Controller
{
    public const TONES = [
        'professional_friendly' => 'Professional & Friendly',
        'warm_personal'         => 'Warm & Personal',
        'confident_bold'        => 'Confident & Bold',
        'creative_playful'      => 'Creative & Playful',
    ];

    public const FOCUSES = [
        'experience' => 'Highlight Experience',
        'value'      => 'Emphasize Value',
        'creativity' => 'Showcase Creativity',
        'trust'      => 'Build Trust',
    ];

    public const LENGTHS = ['short' => 'Short', 'medium' => 'Medium', 'long' => 'Long'];

    /** Quick-fill example descriptions, keyed by chip label. */
    public const EXAMPLES = [
        'Wedding'         => "We're hosting a Enchanted Garden wedding on June 14, 2025. Looking for live music and magical vibes.",
        'Birthday Party'  => "Planning a fun 30th birthday party on August 9, 2025. We want a lively DJ and a vibrant, festive atmosphere.",
        'Corporate Event' => "We're organizing a corporate gala on October 3, 2025. We need a polished, professional act for an elegant evening.",
        'Private Party'   => "Hosting an intimate private party on July 20, 2025. Looking for a warm, classy live performance for close friends.",
        'Other'           => "We have a special celebration coming up on September 12, 2025. We'd love a memorable, creative performance.",
    ];

    public function show(Request $request): View
    {
        $defaultDescription = self::EXAMPLES['Wedding'];
        $proposal = $this->compose($defaultDescription, 'professional_friendly', 'experience', 'medium');

        return view('client.ai-tools.proposal-writer', [
            'tones'       => self::TONES,
            'focuses'     => self::FOCUSES,
            'lengths'     => self::LENGTHS,
            'examples'    => self::EXAMPLES,
            'description' => $defaultDescription,
            'proposal'    => $proposal,
            // Professional-facing tool → professional shell for suppliers.
            'aiLayout'    => $request->user()?->activeRole() === 'supplier' ? 'layouts.professional' : 'layouts.client',
        ]);
    }

    public function generate(Request $request): JsonResponse
    {
        $data = $request->validate([
            'description' => ['required', 'string', 'min:10', 'max:1500'],
            'tone'        => ['nullable', 'string'],
            'focus'       => ['nullable', 'string'],
            'length'      => ['nullable', 'string'],
        ]);

        $tone   = array_key_exists($data['tone'] ?? '', self::TONES) ? $data['tone'] : 'professional_friendly';
        $focus  = array_key_exists($data['focus'] ?? '', self::FOCUSES) ? $data['focus'] : 'experience';
        $length = array_key_exists($data['length'] ?? '', self::LENGTHS) ? $data['length'] : 'medium';

        return response()->json([
            'success'  => true,
            'proposal' => $this->compose($data['description'], $tone, $focus, $length),
        ]);
    }

    /**
     * Assemble a tailored proposal from the parsed event details + options.
     */
    private function compose(string $description, string $tone, string $focus, string $length): string
    {
        $p = $this->parse($description);

        // ── Greeting (tone) ───────────────────────────────────────
        $greeting = match ($tone) {
            'warm_personal'    => 'Hi there!',
            'creative_playful' => 'Hey there! ✨',
            default            => 'Hello!',
        };

        // ── Opening: thank-you referencing the event ──────────────
        $eventPhrase = trim(($p['theme'] ? $p['theme'] . ' ' : '') . $p['eventType']);
        $dateClause  = $p['date'] ? ' on ' . $p['date'] : '';
        $opening = match ($tone) {
            'confident_bold'   => "Thank you for considering me for your {$eventPhrase}{$dateClause} — I'd be thrilled to make it exceptional.",
            'creative_playful' => "Thank you so much for thinking of me for your {$eventPhrase}{$dateClause}!",
            'warm_personal'    => "Thank you for considering me for your {$eventPhrase}{$dateClause}. It would be an honour to be part of your day.",
            default            => "Thank you for considering me for your {$eventPhrase}{$dateClause}.",
        };

        // ── Focus / value sentence ────────────────────────────────
        $service = $p['service'];
        $mood    = $p['mood'];
        $focusLine = match ($focus) {
            'value'      => "I offer outstanding value with flexible packages tailored to your budget, so every detail counts without compromise.",
            'creativity' => "I love crafting one-of-a-kind, {$mood} experiences that surprise and delight your guests with a fresh, original touch.",
            'trust'      => "You can count on me to be reliable, communicative and fully prepared, so you can relax and simply enjoy the moment.",
            default      => "I specialize in creating {$mood} atmospheres that perfectly match your vision.",
        };

        // ── Service / impact sentence ─────────────────────────────
        $impact = "My {$service} will bring elegance and charm to your special day, ensuring an unforgettable experience for you and your guests.";

        // ── Optional extra paragraph (length: long) ───────────────
        $extra = "I'd be glad to walk you through my packages, share examples of past events, and answer any questions you may have. Let's create something truly memorable together.";

        // ── Sign-off (tone) ───────────────────────────────────────
        $signoff = match ($tone) {
            'confident_bold'   => "Let's make it unforgettable.",
            'creative_playful' => "Can't wait to bring your vision to life! 🎉",
            'warm_personal'    => "Warm regards, and I hope to hear from you soon.",
            default            => "Looking forward to the opportunity to work with you.",
        };

        // ── Assemble by length ────────────────────────────────────
        //   short  → greeting + opening/focus
        //   medium → + impact sentence (matches the reference output exactly)
        //   long   → + extra paragraph + sign-off
        $lines = [$greeting, '', $opening . ' ' . $focusLine];
        if ($length !== 'short') {
            $lines[2] .= ' ' . $impact;
        }
        if ($length === 'long') {
            $lines[] = '';
            $lines[] = $extra;
            $lines[] = '';
            $lines[] = $signoff;
        }

        return implode("\n", $lines);
    }

    /**
     * Extract event type, date, theme, mood and service hints from free text.
     */
    private function parse(string $description): array
    {
        $text  = ' ' . Str::lower($description) . ' ';

        // Event type.
        $types = [
            'wedding' => 'wedding', 'birthday' => 'birthday party', 'corporate' => 'corporate event',
            'gala' => 'gala', 'anniversary' => 'anniversary celebration', 'engagement' => 'engagement party',
            'graduation' => 'graduation party', 'baby shower' => 'baby shower', 'retirement' => 'retirement party',
            'holiday' => 'holiday party', 'private party' => 'private party', 'reception' => 'reception',
            'party' => 'party',
        ];
        $eventType = 'event';
        foreach ($types as $needle => $label) {
            if (str_contains($text, $needle)) {
                $eventType = $label;
                break;
            }
        }

        // Date (e.g. "June 14, 2025" / "June 14 2025").
        $date = null;
        if (preg_match('/((?:Jan|Feb|Mar|Apr|May|Jun|Jul|Aug|Sep|Oct|Nov|Dec)[a-z]*\.?\s+\d{1,2}(?:st|nd|rd|th)?,?\s+\d{4})/i', $description, $m)) {
            $date = trim($m[1]);
        }

        // Theme phrase: 1–2 capitalised words immediately before an event noun.
        $theme = null;
        if (preg_match('/\b([A-Z][a-z]+(?:\s[A-Z][a-z]+)?)\s+(?:wedding|party|gala|event|celebration|reception)\b/', $description, $tm)) {
            $candidate = trim($tm[1]);
            if (! in_array(Str::lower($candidate), ['the', 'a', 'an', 'we', 'our', 'my'], true)) {
                $theme = $candidate;
            }
        }

        // Mood adjectives from descriptive keywords, else by event type.
        $adjMap = ['magical', 'romantic', 'elegant', 'rustic', 'modern', 'vintage', 'enchanted',
            'intimate', 'glamorous', 'festive', 'classic', 'bohemian', 'tropical', 'lively', 'vibrant', 'polished'];
        $found = array_values(array_filter($adjMap, fn ($a) => str_contains($text, $a)));
        if (! empty($found)) {
            $mood = $found[0] === 'magical' ? 'magical, romantic' : implode(', ', array_slice($found, 0, 2));
        } else {
            $mood = match (true) {
                str_contains($eventType, 'wedding')   => 'magical, romantic',
                str_contains($eventType, 'corporate') => 'polished, professional',
                str_contains($eventType, 'birthday')  => 'fun, vibrant',
                default                                => 'memorable, engaging',
            };
        }

        // Service hint.
        $service = match (true) {
            str_contains($text, 'dj')                                   => 'DJ set',
            str_contains($text, 'band')                                 => 'live band',
            str_contains($text, 'music') || str_contains($text, 'perform') => 'live performance',
            str_contains($text, 'photo')                                => 'photography',
            str_contains($text, 'cater') || str_contains($text, 'food') => 'catering',
            default                                                     => 'service',
        };

        return compact('eventType', 'date', 'theme', 'mood', 'service');
    }
}
