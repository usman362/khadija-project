<?php

namespace App\Domain\AddressVerification;

/**
 * Layer-1 free address filter (Developer Feedback v1.1 §7.4 + §7.5).
 *
 * Runs BEFORE any paid API call — pure string logic, no network, no cost.
 * Rejects blanks, PO Boxes, and obviously-fake input so paid attempts aren't
 * wasted. Also flags (does NOT block) likely home addresses for manual review.
 */
class AddressFilter
{
    /**
     * Inspect a raw address. Returns a structured verdict:
     *   ['ok' => bool, 'reason' => ?string, 'block' => bool, 'flag_home' => bool]
     *
     * - ok=false + block=true  → hard reject at the free layer (PO Box, blank).
     * - ok=false + block=false → soft reject, prompt to correct.
     * - ok=true  + flag_home   → passes filter but should go to manual review.
     *
     * @param array{line1?:string,line2?:string,city?:string,state?:string,zip?:string} $address
     */
    public function inspect(array $address): array
    {
        $line1 = trim((string) ($address['line1'] ?? ''));
        $line2 = trim((string) ($address['line2'] ?? ''));
        $city  = trim((string) ($address['city'] ?? ''));
        $state = trim((string) ($address['state'] ?? ''));
        $zip   = trim((string) ($address['zip'] ?? ''));

        // Required fields.
        if ($line1 === '' || $city === '' || $state === '' || $zip === '') {
            return $this->verdict(false, 'Please complete all required address fields.', block: true);
        }

        // §7.5 — PO Boxes are blocked at the free layer (no paid call used).
        if ($this->isPoBox($line1) || $this->isPoBox($line2)) {
            return $this->verdict(false, 'PO Box addresses are not accepted. Please enter a physical street address.', block: true);
        }

        // Zip sanity (5 or 5+4).
        if (! preg_match('/^\d{5}(-\d{4})?$/', $zip)) {
            return $this->verdict(false, 'Please enter a valid 5-digit zip code.', block: false);
        }

        // Obvious junk: too short, no digit (street number), or repeated chars.
        if ($this->looksFake($line1)) {
            return $this->verdict(false, 'This address looks incomplete. Please enter a valid street address.', block: false);
        }

        // §7.5 — home/residential signals: flag for manual review, don't block.
        return $this->verdict(true, null, block: false, flagHome: $this->looksResidential($line1, $line2));
    }

    /**
     * PO Box / PMB detection — covers common spellings & abbreviations.
     */
    public function isPoBox(string $line): bool
    {
        if ($line === '') {
            return false;
        }

        return (bool) preg_match(
            '/\b(p\.?\s*o\.?\s*box|post\s*office\s*box|p\.?o\.?b\b|pmb|box\s+\d+)\b/i',
            $line
        );
    }

    /**
     * Heuristic for obviously-fake free-text (pre-API guard, §7.4 Layer 1).
     */
    private function looksFake(string $line1): bool
    {
        $clean = preg_replace('/[^a-z0-9 ]/i', '', $line1);

        if (strlen(trim((string) $clean)) < 5) {
            return true;
        }

        // A street address should contain a number.
        if (! preg_match('/\d/', $clean)) {
            return true;
        }

        // Same char hammered (e.g. "aaaaaa", "111111").
        if (preg_match('/^(.)\1{4,}$/', preg_replace('/\s+/', '', strtolower($clean)))) {
            return true;
        }

        // Common placeholders.
        if (preg_match('/\b(test|fake|asdf|qwerty|none|n\/?a|unknown|xxxx)\b/i', $line1)) {
            return true;
        }

        return false;
    }

    /**
     * Residential signals — apartment/unit hints suggest a home, which §7.5
     * says to flag for manual review (business may operate from home).
     */
    private function looksResidential(string $line1, string $line2): bool
    {
        $hay = strtolower($line1 . ' ' . $line2);

        return (bool) preg_match('/\b(apt|apartment|unit|suite|ste|#|fl|floor|room|rm)\b/i', $hay);
    }

    private function verdict(bool $ok, ?string $reason, bool $block = false, bool $flagHome = false): array
    {
        return ['ok' => $ok, 'reason' => $reason, 'block' => $block, 'flag_home' => $flagHome];
    }
}
