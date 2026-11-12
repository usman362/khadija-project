<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Ali, 13 November, with a photograph of the Leads CRM.
 *
 * Two faults on one screen.
 *
 * The blue banner at the top of every professional page took its subtitle from
 * the dashboard whenever a page did not set one. Twenty-one professional pages
 * name themselves and six give a subtitle, so fifteen of them carried a line
 * belonging to another page: "Leads CRM" sitting over "Your business at a
 * glance, Nov 13". The greeting is the dashboard's and now shows only when the
 * dashboard's title does.
 *
 * The leads table was eight columns of chips that each refused to wrap. Its row
 * wanted about 1431px, so on anything narrower than a very wide monitor it grew
 * straight through the card and took the whole page into a sideways scroll,
 * with the right-hand columns cut off the screen. It has a scroller of its own
 * now, and the chips wrap inside a cell, which brought the row down to 976.
 */
class TheProfessionalBannerSaysWhereYouAreTest extends TestCase
{
    use RefreshDatabase;

    private function professional(): User
    {
        $this->seed(\Database\Seeders\PermissionSeeder::class);
        $this->seed(\Database\Seeders\RolePermissionSeeder::class);

        $user = User::factory()->create(['primary_role' => 'professional']);
        $user->assignRole('professional');
        $user->givePermissionTo('dashboard.view');
        $user->getOrCreateProfile()->update(['country' => 'US', 'state' => 'MD', 'city' => 'Baltimore']);

        return $user->fresh();
    }

    public function test_a_named_page_does_not_borrow_the_dashboards_greeting(): void
    {
        $html = $this->actingAs($this->professional())
            ->get(route('professional.leads.index'))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('Leads CRM', $html);
        $this->assertStringNotContainsString('Your business at a glance', $html);
        $this->assertStringNotContainsString('Welcome back,', $html);
    }

    public function test_the_dashboard_still_greets_the_professional(): void
    {
        $pro  = $this->professional();
        $html = $this->actingAs($pro)
            ->get(route('professional.dashboard'))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('Your business at a glance', $html);
    }

    public function test_the_leads_table_scrolls_inside_its_card_rather_than_the_page(): void
    {
        $html = $this->actingAs($this->professional())
            ->get(route('professional.leads.index'))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('lc-tablewrap', $html);
        $this->assertMatchesRegularExpression(
            '/\.lc-tablewrap\s*\{[^}]*overflow-x\s*:\s*auto/',
            $html,
            'The leads table needs a scroller of its own, or the page scrolls sideways.'
        );
    }

    public function test_the_lead_avatar_keeps_its_white_centred_initials(): void
    {
        $html = $this->actingAs($this->professional())
            ->get(route('professional.leads.index'))
            ->assertOk()
            ->getContent();

        /* ".lc-who span" is one class and one element, ".lc-av" is one class,
           so the row rule won and the avatar lost display:flex and color:#fff.
           The initials went grey and sat in the disc's top-left corner, half
           of them outside it. The row rule has to exclude the avatar. */
        $this->assertDoesNotMatchRegularExpression(
            '/\.lc-who\s+span\s*\{/',
            $html,
            'A bare ".lc-who span" rule also catches the avatar and overrides it.'
        );
        $this->assertStringContainsString('.lc-who span:not(.lc-av)', $html);
    }
}
