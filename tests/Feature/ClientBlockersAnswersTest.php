<?php

namespace Tests\Feature;

use App\Domain\Forms\FormRegistry;
use App\Domain\Requests\{Award, RegulatedAcceptance};
use App\Models\{Bid, Category, Event, User};
use App\Support\ServiceArea;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

/**
 * The PM's answers to the client-side blockers (6 September): badge colours,
 * the BBB link, the FAQ set, the last forms, and bid acceptance in regulated
 * categories.
 */
class ClientBlockersAnswersTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\PermissionSeeder::class);
        $this->seed(\Database\Seeders\RolePermissionSeeder::class);
    }

    private function user(string $role): User
    {
        $u = User::factory()->create(['primary_role' => $role]);
        $u->assignRole($role);
        $u->getOrCreateProfile()->update([
            'country' => 'US', 'state' => 'MD', 'city' => 'Baltimore',
            'service_area_status' => ServiceArea::SUPPORTED,
        ]);

        return $u->fresh();
    }

    /* ── Bids in regulated categories (OA-104) ─────────────── */

    private function bid(string $l2, User $pro): Bid
    {
        $parent = Category::create(['name' => $l2, 'slug' => \Illuminate\Support\Str::slug($l2) . '-t', 'kind' => Category::SERVICE_CATEGORY, 'is_active' => true]);
        $svc = Category::create(['name' => $l2 . ' Service', 'slug' => \Illuminate\Support\Str::slug($l2) . '-svc-t', 'kind' => Category::SERVICE, 'parent_id' => $parent->id, 'is_active' => true]);
        $client = $this->user('client');
        $event = Event::create(['title' => 'Gala', 'client_id' => $client->id, 'created_by' => $client->id, 'status' => 'published', 'starts_at' => now()->addMonth()]);

        return Bid::create(['event_id' => $event->id, 'category_id' => $svc->id, 'supplier_id' => $pro->id, 'amount' => 900, 'status' => 'submitted']);
    }

    public function test_an_unverified_catering_bid_cannot_be_accepted(): void
    {
        $bid = $this->bid('Catering & Food Services', $this->user('professional'));

        $this->assertSame('Catering & Food Services', RegulatedAcceptance::categoryFor($bid));
        $this->expectException(ValidationException::class);
        Award::openFinalization($bid);
    }

    public function test_a_verified_professional_can_be_accepted(): void
    {
        $pro = $this->user('professional');
        $pro->profile->forceFill([
            'trade_license_verified_at' => now(), 'liability_insurance_verified_at' => now(), 'workers_comp_verified_at' => now(),
        ])->save();

        $this->assertNotNull(Award::openFinalization($this->bid('Security & Crowd Management', $pro->fresh())));
    }

    public function test_other_categories_are_not_limited(): void
    {
        $this->assertNotNull(Award::openFinalization($this->bid('Photography & Videography', $this->user('professional'))));
    }

    /* ── Forms (D-28) ──────────────────────────────────────── */

    /**
     * Khadijah, 13 Sep: the seven forms already live are the final list; the
     * three added on Sep 6 (Contract Dispute, Feature Request, Partnership
     * Inquiry) are withdrawn.
     */
    public function test_the_withdrawn_forms_are_not_offered(): void
    {
        $groups = FormRegistry::groupsForAudience(FormRegistry::CLIENT);
        $all = collect($groups)->flatMap(fn ($g) => array_keys($g['forms']))->all();

        foreach (['feature_request', 'partnership_inquiry', 'contract_dispute'] as $key) {
            $this->assertNotContains($key, $all);
            $this->assertArrayNotHasKey($key, FormRegistry::all());
        }
    }

    /* ── FAQ, badges, BBB ──────────────────────────────────── */

    public function test_the_faq_set_is_loaded(): void
    {
        $this->assertTrue(DB::table('faqs')->where('question', 'What happens if something goes wrong at my event?')->exists());
        $this->assertSame(12, DB::table('faqs')->whereIn('category', ['Bookings & Payments', 'Trust & Safety', 'Account & Membership', 'For Professionals'])->count());
        $this->assertStringNotContainsString(' — ', DB::table('faqs')->pluck('answer')->implode(' '));
    }

    public function test_client_badges_are_flat(): void
    {
        $client = $this->user('client');
        $html = $this->actingAs($client)->get(route('client.badges.index'))->assertOk()->getContent();

        $this->assertStringContainsString('background: #2F6FED;', $html);
        $this->assertStringNotContainsString('linear-gradient(160deg, #2F6FED', $html);
    }

    public function test_a_profile_links_to_bbb(): void
    {
        $pro = $this->user('professional');
        $pro->profile->update(['company_name' => 'Rossi Studio']);

        $this->actingAs($this->user('client'))->get(route('public.professional.show', $pro))
            ->assertOk()
            ->assertSee('https://www.bbb.org/search?find_country=USA&amp;find_text=Rossi+Studio', false)
            ->assertSee('Research this business on BBB.org');
    }
}
