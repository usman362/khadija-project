<?php

namespace Tests\Feature;

use App\Domain\Agreements\Workspace;
use App\Models\{Category, Event, Finalization, User};
use App\Support\ServiceArea;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The pen is not held out before there is something settled to sign.
 *
 * Ali, 28 October, from the client's Contract step: the page said "Not ready
 * to sign. Neither of you has accepted these terms yet", and pressing the
 * button under it answered "Type your full name to sign. You need to accept
 * the agreement to sign it" about a field and a checkbox that page does not
 * draw in that state.
 *
 * Two faults, one cause. The screen has always known the rule and said it
 * plainly, but drew its submit button whatever the state; and the server never
 * checked at all, so the only thing standing between an unapproved agreement
 * and a signature was a form that happened not to render.
 *
 * Sir Peter, 27 September: "E-signature should unlock only after both parties
 * approve the same agreement version." The professional's own signing route
 * has had this guard since it was built.
 */
class NobodySignsBeforeBothAcceptTest extends TestCase
{
    use RefreshDatabase;

    private User $client;
    private User $professional;
    private Finalization $agreement;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\PermissionSeeder::class);
        $this->seed(\Database\Seeders\RolePermissionSeeder::class);

        $this->client       = $this->person('client');
        $this->professional = $this->person('professional');

        $service = Category::firstOrCreate(
            ['slug' => 'wedding-dj'],
            ['name' => 'Wedding DJ', 'kind' => Category::SERVICE, 'is_active' => true],
        );

        $event = Event::create([
            'title'      => 'Award Ceremony',
            'client_id'  => $this->client->id,
            'created_by' => $this->client->id,
            'status'     => 'published',
            'starts_at'  => now()->addDays(40),
        ]);

        $this->agreement = Finalization::create([
            'event_id'           => $event->id,
            'category_id'        => $service->id,
            'client_id'          => $this->client->id,
            'supplier_id'        => $this->professional->id,
            'terms_version'      => 1,
            'agreed_price'       => 1000,
            'deposit_percent'    => 25,
            'deposit_amount'     => 250,
            'scope'              => 'Test scope.',
            'service_start'      => now()->addDays(40)->setTime(21, 0),
            'service_end'        => now()->addDays(40)->setTime(23, 0),
            'bid_reviewed_at'    => now(),
            'scope_agreed_at'    => now(),
            'price_agreed_at'    => now(),
            'schedule_agreed_at' => now(),
            'terms_agreed_at'    => now(),
        ]);
    }

    private function person(string $role): User
    {
        $u = User::factory()->create(['primary_role' => $role]);
        $u->assignRole($role);
        $u->getOrCreateProfile()->update([
            'country' => 'US', 'state' => 'MD', 'city' => 'Baltimore',
            'service_area_status' => ServiceArea::SUPPORTED,
        ]);

        return User::findOrFail($u->id);
    }

    private function trySigning(): \Illuminate\Testing\TestResponse
    {
        return $this->actingAs($this->client)->post(
            route('client.finalize.save', [$this->agreement, 'contract']),
            ['client_signature' => $this->client->name, 'agree' => '1'],
        );
    }

    private function contractStep(): string
    {
        return $this->actingAs($this->client)
            ->get(route('client.finalize.step', [$this->agreement->fresh(), 'contract']))
            ->assertSuccessful()
            ->getContent();
    }

    // ── The server ───────────────────────────────────────────

    /**
     * The one that mattered: a complete, well-formed signature is refused.
     *
     * Everything the old validation asked for is supplied here. Before the
     * guard, this signed an agreement neither side had accepted.
     */
    public function test_a_signature_is_refused_while_neither_side_has_accepted(): void
    {
        $this->trySigning()->assertSessionHasErrors('client_signature');

        $this->assertNull($this->agreement->fresh()->client_signed_at, 'It signed anyway.');
    }

    public function test_it_is_still_refused_when_only_the_client_accepted(): void
    {
        Workspace::approve($this->agreement, $this->client);

        $this->trySigning()->assertSessionHasErrors('client_signature');

        $this->assertNull($this->agreement->fresh()->client_signed_at);
    }

    public function test_it_is_still_refused_when_only_the_professional_accepted(): void
    {
        Workspace::approve($this->agreement, $this->professional);

        $this->trySigning()->assertSessionHasErrors('client_signature');

        $this->assertNull($this->agreement->fresh()->client_signed_at);
    }

    public function test_once_both_accept_the_signature_goes_through(): void
    {
        Workspace::approve($this->agreement, $this->professional);
        Workspace::approve($this->agreement->fresh(), $this->client);

        $this->trySigning()->assertSessionHasNoErrors();

        $this->assertNotNull($this->agreement->fresh()->client_signed_at);
    }

    /** The reason given names the actual problem. */
    public function test_the_refusal_says_what_is_actually_wrong(): void
    {
        $this->trySigning()->assertSessionHasErrors([
            'client_signature' => 'You both have to accept the same version of these terms before either of you can sign.',
        ]);
    }

    /** A declined agreement says that instead, which is a different problem. */
    public function test_a_declined_agreement_says_so(): void
    {
        $this->agreement->forceFill(['declined_at' => now(), 'declined_by' => $this->professional->id])->save();

        $this->trySigning()->assertSessionHasErrors([
            'client_signature' => 'This agreement was ended, so it cannot be signed.',
        ]);
    }

    // ── The screen ───────────────────────────────────────────

    public function test_the_button_does_not_invite_a_signature_it_will_refuse(): void
    {
        $html = $this->contractStep();

        $this->assertStringContainsString('Not ready to sign', $html);
        $this->assertStringContainsString('Both of you must accept first', $html);
        $this->assertStringNotContainsString('>Sign agreement', $html);
    }

    public function test_the_button_comes_back_once_both_accept(): void
    {
        Workspace::approve($this->agreement, $this->professional);
        Workspace::approve($this->agreement->fresh(), $this->client);

        $html = $this->contractStep();

        $this->assertStringNotContainsString('Both of you must accept first', $html);
        $this->assertStringContainsString('Sign agreement', $html);
    }

    /**
     * An edit after one side accepted puts the button away again.
     *
     * Approval is approval of a version, so changing a term takes it back.
     */
    public function test_an_edit_closes_the_pen_again(): void
    {
        Workspace::approve($this->agreement, $this->professional);
        Workspace::approve($this->agreement->fresh(), $this->client);

        \App\Domain\Agreements\EditAgreement::submit(
            $this->agreement->fresh(),
            $this->client,
            ['agreed_price' => 1200] + \App\Domain\Agreements\EditAgreement::current($this->agreement->fresh()),
            'New budget.',
        );

        $this->assertStringContainsString('Both of you must accept first', $this->contractStep());

        $this->trySigning()->assertSessionHasErrors('client_signature');
    }
}
