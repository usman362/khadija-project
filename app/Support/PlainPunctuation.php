<?php

namespace App\Support;

/**
 * Text without the spaced dash.
 *
 * Ali, 2026-09-10: "yeh dash nahi ana chaiye… agay se jo bhi text generate
 * to yeh mistake na ho". The " — " was everywhere in the product's copy, and
 * the language model the AI tools call writes it too. One set of rules, used
 * for both: the copy was cleaned with it once, and every model answer passes
 * through it before anyone sees it.
 *
 * A dash is not replaced by one symbol everywhere; that produces sentences
 * nobody would write. It becomes what the sentence needs:
 *   - before and / but / including / which …        → a comma
 *   - before a new clause (you, we, it, an imperative) → a full stop + capital
 *   - before an explanation (a, the, one, what, no)    → a colon
 *   - before a value ($1.99, {name}, %d) or a Capital   → a colon
 *   - a pair around an aside                           → two commas
 *   - "— Choose one —" as a whole placeholder          → "Choose one"
 *   - " — GigResource" in a title                      → " | GigResource"
 *   - anything else                                    → a comma
 *
 * The en dash in a range ("12 PM – 4 PM") is a different character with a
 * different job, and is never touched.
 */
final class PlainPunctuation
{
    private const LINKERS = 'and|or|but|so|then|which|who|where|when|while|because|including|like|not|even|especially|only|just|plus|without|with|from|for|to|in|on|at|by|as|if|unless|until|than|rather|instead|usually|often|mostly|also|again|once|either|neither|nor|yet|still|though|although|always|never|whether|except|per|via';

    // Not "all": "— all in one dashboard" carries the sentence on rather
    // than starting one, and a full stop there leaves a fragment.
    private const CLAUSE = 'i|you|we|they|it|he|she|there|this|that|these|those|your|our|their|its|nothing|everything|everyone|someone|anyone|nobody|professionals|pros|clients|each|every|none|most|some|many|now|here|anything'
        . '|request|click|choose|pick|add|set|use|try|see|tap|open|save|send|post|book|contact|message|browse|start|finish|check|review|come|leave|go|keep|make|let|ask|tell|write|find|share|call|visit|read|sign|log|upgrade|compare|expect|give|pay|get|note|remember'
        . '|upload|submit|update|reply|respond|accept|decline|invite|download|print|refer|earn|join|claim|renew|verify|confirm|complete|enable|connect|schedule|follow|wait|edit|remove|delete|select|enter|fill|type|drag|drop|link|attach|approve|reject|cancel|resend|retry|reload|refresh|contact';

    private const APPOSITION = 'a|an|the|one|two|three|four|five|six|seven|eight|nine|ten|what|how|why|e\.g\.|i\.e\.|no';

    /** Plain text: a message, a label, a sentence. */
    public static function text(string $s): string
    {
        if (! str_contains($s, '—')) {
            return $s;
        }

        // A whole placeholder: "— Choose your event —".
        if (preg_match('/^(\s*)— (.+?) —(\s*)$/su', $s, $m) && ! str_contains($m[2], '—')) {
            return $m[1] . $m[2] . $m[3];
        }

        // A dash opening a line or the text ("— Sir Peter", a bullet): drop it.
        $s = preg_replace('/(^|\n)([ \t]*)—[ \t]+/u', '$1$2', $s);

        // Page titles.
        $s = preg_replace('/ — (GigResource\b)/u', ' | $1', $s);

        // A pair in one sentence is an aside.
        $s = preg_replace('/ — ([^—.!?\n<>{}]{1,90}?) — /u', ', $1, ', $s);

        $s = preg_replace_callback('/(.)[ \t]—([ \t]*\n[ \t]*|[ \t]+)(<[^>]+>)?(\S+)/u', function ($m) {
            [, $prev, $gap, $tag, $next] = [$m[0], $m[1], $m[2], $m[3] ?? '', $m[4]];
            $space = str_contains($gap, "\n") ? "\n" . ltrim(preg_replace('/^[ \t]*/', '', $gap), "\n") : ' ';
            $join  = fn (string $sep) => $prev . $sep . $space . $tag . $next;

            if (preg_match('/[.,;:!?(\[]/u', $prev)) {
                return $prev . $space . $tag . $next;
            }
            if ($tag === '' && preg_match('/^[\'"%{$]/u', $next)) {
                return $join(':');
            }
            if ($tag === '' && preg_match('/^\p{Lu}/u', $next)) {
                return $join(':');
            }

            $word = preg_replace('/[^\p{L}.]+$/u', '', strtolower(preg_replace('/^[^\p{L}\p{N}$]+/u', '', $next)));

            if ($word !== '' && preg_match('/^(' . self::LINKERS . ')$/', $word)) {
                return $join(',');
            }
            if ($word !== '' && preg_match('/^(' . self::APPOSITION . ')$/', $word)) {
                return $join(':');
            }
            if ($word !== '' && preg_match('/^(' . self::CLAUSE . ')$/', $word)) {
                $cap = preg_replace_callback('/^([^\p{L}]*)(\p{Ll})/u', fn ($x) => $x[1] . mb_strtoupper($x[2]), $next, 1);

                return $prev . '.' . $space . $tag . $cap;
            }

            return $join(',');
        }, $s);

        // A dash left at the very end of the text.
        return preg_replace('/[ \t]+—[ \t]*$/u', '', $s);
    }

    /**
     * What a language model wrote. Models also write the dash with no spaces
     * ("the ceremony—then dinner"), so those are opened up first and put
     * through the same rules. A range ("8am–6pm") uses the en dash and is left
     * alone.
     */
    public static function model(string $s): string
    {
        $s = preg_replace('/(\S)—(\S)/u', '$1 — $2', $s);

        return self::text($s);
    }

    /** HTML from a model: only the text between tags is changed. */
    public static function modelHtml(string $html): string
    {
        $parts = preg_split('/(<[^>]*>)/', $html, -1, PREG_SPLIT_DELIM_CAPTURE);
        foreach ($parts as $i => $part) {
            if ($i % 2 === 0 && $part !== '') {
                $parts[$i] = self::model($part);
            }
        }

        return implode('', $parts);
    }

    /** Every string inside a decoded model answer (JSON arrays), keys untouched. */
    public static function modelDeep(mixed $value): mixed
    {
        if (is_string($value)) {
            return self::model($value);
        }
        if (is_array($value)) {
            return array_map([self::class, 'modelDeep'], $value);
        }

        return $value;
    }

    /** The line a system prompt carries, so the model is asked before it is corrected. */
    public const PROMPT_RULE = 'Never use the em dash character (—) anywhere in your answer. Use a comma, a full stop, a colon or parentheses instead.';
}
