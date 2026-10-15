<?php

namespace Tests\Feature;

use App\Models\UserProfile;
use App\Support\VerifiedBadge;
use Tests\TestCase;

/**
 * The site must not claim a verification it did not perform.
 *
 * Sir Peter's handoff of 12 October 2026, the final V1 decision: keep the
 * generic "Verified Professional" and "Verified Influencer" badges disabled,
 * and "do not claim identity, background, licensing, insurance,
 * qualifications, or service-quality verification unless the specific check
 * has actually been implemented, passed, and documented".
 *
 * Three different faults were found on 12 October, and this holds all three
 * shut, because each of them survived OA-165 by not going through
 * VerifiedBadge at all:
 *
 *   1. A tick printed as unconditional markup on every public package card.
 *   2. The words "Verified Professional" used as the fallback for a missing
 *      company name, which never asked any verification rule.
 *   3. Copy asserting that background and licence checks happen before a
 *      professional goes live, which they do not.
 *
 * And the structural one underneath them: two definitions of verified, a
 * strict one requiring the document and the approval, and a loose one that
 * took the approval alone.
 */
class VerificationClaimsStayHonestTest extends TestCase
{
    /** Developer comments keep whatever wording they like; nobody reads them. */
    private const COMMENTS = '/(\{\{--.*?--\}\}|<!--.*?-->|(?<![\w"\'=\/])\/\*.*?\*\/|(?m:^[ \t]*\/\/[^\n]*$)|(?<=[;{}),])[ \t]*\/\/[^\n]*)/s';

    /** @return array<string, string> path => body with comments removed */
    private function views(): array
    {
        $out = [];

        $it = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator(resource_path('views')));

        foreach ($it as $file) {
            if (! $file->isFile() || ! str_ends_with($file->getFilename(), '.blade.php')) {
                continue;
            }

            $path = ltrim(str_replace(base_path(), '', $file->getPathname()), '/');
            $parts = preg_split(self::COMMENTS, file_get_contents($file->getPathname()), -1, PREG_SPLIT_DELIM_CAPTURE);

            $body = '';
            foreach ($parts as $i => $part) {
                if ($i % 2 === 0) {
                    $body .= $part;
                }
            }

            $out[$path] = $body;
        }

        return $out;
    }

    // ── The strict rule itself ───────────────────────────────

    /**
     * An approval with no document is not a verification.
     *
     * This is ISSUE-1. VerifiedBadge was hardened for it; UserProfile was not,
     * and a seeder or a stray UPDATE can write the timestamp alone.
     */
    public function test_a_stamp_without_a_document_is_not_verified(): void
    {
        $profile = new UserProfile([
            'trade_license_verified_at' => now(),
            'trade_license_doc'         => null,
        ]);

        $this->assertSame('none', $profile->badgeStatus('trade_license'));
        $this->assertFalse(VerifiedBadge::holds($profile, 'trade_license'));
        $this->assertSame([], $profile->verifiedBadges());
    }

    /** A document with no approval is still only pending. */
    public function test_a_document_without_an_approval_is_pending(): void
    {
        $profile = new UserProfile([
            'trade_license_doc'         => 'licences/abc.pdf',
            'trade_license_verified_at' => null,
        ]);

        $this->assertSame('pending', $profile->badgeStatus('trade_license'));
        $this->assertFalse(VerifiedBadge::holds($profile, 'trade_license'));
    }

    /** Both halves, and only then. */
    public function test_a_document_and_an_approval_together_are_verified(): void
    {
        $profile = new UserProfile([
            'trade_license_doc'         => 'licences/abc.pdf',
            'trade_license_verified_at' => now(),
        ]);

        $this->assertSame('verified', $profile->badgeStatus('trade_license'));
        $this->assertTrue(VerifiedBadge::holds($profile, 'trade_license'));
    }

    /**
     * The two definitions must agree.
     *
     * The loose one fed User::isVerified(), the package pages, AI matchmaking
     * and the rule deciding who may accept regulated work. If they ever drift
     * apart again, this fails.
     */
    public function test_the_strict_and_loose_rules_give_the_same_answer(): void
    {
        foreach ([[null, null], ['doc.pdf', null], [null, now()], ['doc.pdf', now()]] as [$doc, $at]) {
            $profile = new UserProfile([
                'trade_license_doc'         => $doc,
                'trade_license_verified_at' => $at,
            ]);

            $this->assertSame(
                VerifiedBadge::holds($profile, 'trade_license'),
                $profile->badgeStatus('trade_license') === 'verified',
                'badgeStatus and VerifiedBadge::holds disagree for doc=' . var_export($doc, true)
                    . ' approved=' . var_export((bool) $at, true),
            );
        }
    }

    // ── What the screens may say ─────────────────────────────

    /**
     * The badge is off for V1, so the words must not appear as a label either.
     *
     * The public packages page used them as the fallback for a missing company
     * name, so a professional with no company was called a Verified
     * Professional, tick and all, with no verification anywhere in it.
     */
    public function test_no_view_prints_the_generic_verified_label(): void
    {
        $found = [];

        foreach ($this->views() as $path => $body) {
            // A file that checks the switch may use the words, because there
            // they describe a mark that only appears when marks are on and the
            // evidence is real: the tooltip on the browse avatar, the "Verified
            // professionals only" filter on event creation. The fault was the
            // words standing on their own with no rule behind them.
            if (str_contains($body, 'VerifiedBadge::shown()')) {
                continue;
            }

            if (preg_match('/Verified\s+(Professional|Influencer)s?/i', $body, $m)) {
                $found[] = $path . ': ' . $m[0];
            }
        }

        $this->assertSame([], $found, "The generic verified label is back:\n" . implode("\n", $found));
    }

    /**
     * No page may say the platform screens people before they go live.
     *
     * The browse page said "Background & license checks before pros go live"
     * while the signup disclaimer said background checks are not guaranteed.
     * Both cannot be true, and it was the disclaimer that was right.
     */
    public function test_no_view_claims_background_or_licence_screening(): void
    {
        $found = [];

        foreach ($this->views() as $path => $body) {
            // Tags out first. The disclaimer bolds the important half, so
            // "background checks are <strong>not guaranteed</strong>" has
            // markup sitting inside the sentence this is trying to read.
            $body = preg_replace('/\s+/', ' ', strip_tags($body));

            // The onboarding disclaimer is allowed to mention them, because it
            // says they are NOT guaranteed. That is the honest direction.
            if (preg_match('/background checks? (are|is) not guaranteed/i', $body)) {
                continue;
            }

            if (preg_match('/.{0,30}(background (check|screening)|licen[sc]e check).{0,30}/i', $body, $m)) {
                $found[] = $path . ': ' . trim(preg_replace('/\s+/', ' ', $m[0]));
            }
        }

        $this->assertSame([], $found, "A vetting claim is back in copy:\n" . implode("\n", $found));
    }

    /**
     * Every mark on a screen must pass the switch.
     *
     * Three pages asked whether somebody was verified without asking whether
     * marks are shown at all, which is how OA-165 missed the whole packages
     * section. A view that asks one question must ask the other.
     */
    public function test_every_view_that_asks_about_verification_also_asks_whether_marks_are_shown(): void
    {
        $found = [];

        foreach ($this->views() as $path => $body) {
            $asks = preg_match('/->isVerified\(\)|->badgeStatus\(|->verifiedBadges\(\)|->hasAnyVerifiedBadge\(\)/', $body);

            if (! $asks) {
                continue;
            }

            // Two places must see the raw state, and neither is the platform
            // making a claim to a third party.
            //
            // The admin verification queue: its whole job is approving
            // documents, and hiding a pending upload from the reviewer would
            // stop anybody ever being verified.
            //
            // A professional's own verification tab: showing somebody the
            // state of their own uploads is not a claim about them to anyone
            // else. The switch governs what one user is told about another.
            $ownOrReviewer = str_starts_with($path, 'resources/views/dashboard/admin/')
                || $path === 'resources/views/professional/profile/index.blade.php';

            if ($ownOrReviewer) {
                continue;
            }

            if (! str_contains($body, 'VerifiedBadge::shown()')) {
                $found[] = $path;
            }
        }

        $this->assertSame([], $found, "These views show a verification without checking the switch:\n" . implode("\n", $found));
    }

    /** And the switch is off, which is the V1 decision. */
    public function test_verification_marks_are_off_for_v1(): void
    {
        $this->assertFalse(
            VerifiedBadge::shown(),
            'Sir Peter, 12 October: generic verified badges stay disabled for V1.',
        );
    }
}
