<?php

namespace Tests\Feature;

use App\Domain\AiFeatures\ToolkitTiers;
use Tests\TestCase;

/**
 * OA-142 — /tools and the Toolkit Tiers table said different things.
 *
 * Contract Assistant was tagged "Semi" on /tools and marked Maximum-only in
 * the tiers table. Message Builder was tagged "Maximum" on /tools and sits in
 * the Semi bundle.
 *
 * They were not disagreeing about a fact. They were answering two questions
 * with the same word — "the highest level YOU can use" and "the tier that
 * unlocks this tool" — and both render as Semi or Maximum. A badge whose
 * meaning depends which page you are standing on is worse than no badge.
 *
 * The tier assignment now comes from one record: Rule R31 in
 * config/toolkit-tiers.php, the Owner's 2026-08-05 correction. These are the
 * two tools the ticket named.
 */
class ToolkitTierLabelsAgreeTest extends TestCase
{
    public function test_contract_assistant_is_maximum_only(): void
    {
        $this->assertSame(ToolkitTiers::MAXIMUM, ToolkitTiers::tierForName('Contract Assistant'));
    }

    public function test_message_builder_is_in_the_semi_bundle(): void
    {
        $this->assertSame(ToolkitTiers::SEMI, ToolkitTiers::tierForName('Message Builder'));
    }

    /** The five R31 records as Semi for a client, and nothing else. */
    public function test_the_semi_bundle_is_the_five_from_r31(): void
    {
        foreach (['Budget Planner', 'Smart Checklist', 'Timeline Builder', 'Best Match', 'Message Builder'] as $tool) {
            $this->assertSame(ToolkitTiers::SEMI, ToolkitTiers::tierForName($tool), $tool);
        }

        foreach (['Guided Event Planner', 'Venue Compatibility Check', 'Style & Inspiration', 'Review Builder'] as $tool) {
            $this->assertSame(ToolkitTiers::MAXIMUM, ToolkitTiers::tierForName($tool), $tool);
        }
    }

    /**
     * R31 calls it "Guest Capacity Calculator"; the card says "Guest
     * Capacity". A straight string comparison would move it to Maximum-only —
     * a pricing change caused by a missing word.
     */
    public function test_a_shorter_card_name_still_matches_its_bundle_entry(): void
    {
        $this->assertSame(
            ToolkitTiers::tierForName('Guest Capacity Calculator'),
            ToolkitTiers::tierForName('Guest Capacity'),
        );
    }

    /**
     * Nothing recorded means nothing claimed. The ticket is explicit: do not
     * guess assignments while the map is outstanding.
     */
    public function test_an_audience_with_no_bundle_is_not_guessed(): void
    {
        config(['toolkit-tiers.semi_tools.influencer' => []]);

        $this->assertNull(ToolkitTiers::tierForName('Anything', 'influencer'));
        $this->assertNull(ToolkitTiers::label(null));
    }
}
