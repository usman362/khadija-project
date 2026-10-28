<?php

namespace Tests\Feature;

use App\Domain\Agreements\Workspace;
use App\Models\{Category, Event, Finalization, User};
use App\Support\ServiceArea;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The professional can download the agreement they are being asked to sign.
 *
 * The client has had this since Sir Peter asked for it beside their
 * workspace. The professional, who signs the same terms, had no copy of them
 * to read away from the screen or keep on file.
 *
 * It is the same view on both sides on purpose. Two documents drawn
 * separately is how one side ends up holding a copy that says something the
 * other's does not.
 *
 * Not the signed contract. app.agreements.download refuses anything both
 * sides have not accepted, and that file is the record of a booking; this one
 * says "draft" on every page until both signatures are on it.
 */
class TheProfessionalCanKeepACopyOfTheAgreementTest extends TestCase
{
    use RefreshDatabase;

    private User $professional;
    private User $client;
    private Finalization $agreement;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\PermissionSeeder::class);
        $this->seed(\Database\Seeders\RolePermissionSeeder::class);

        $this->client       = $this->person('client');
        $this->professional = $this->person('professional');
        $this->agreement    = $this->agreementBetween($this->client, $this->professional, 'Rivera Wedding');
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

    private function agreementBetween(User $client, User $supplier, string $title): Finalization
    {
        $service = Category::firstOrCreate(
            ['slug' => 'wedding-dj'],
            ['name' => 'Wedding DJ', 'kind' => Category::SERVICE, 'is_active' => true],
        );

        $event = Event::create([
            'title'      => $title,
            'client_id'  => $client->id,
            'created_by' => $client->id,
            'status'     => 'published',
            'starts_at'  => now()->addDays(40),
        ]);

        return Finalization::create([
            'event_id'      => $event->id,
            'category_id'   => $service->id,
            'client_id'     => $client->id,
            'supplier_id'   => $supplier->id,
            'terms_version' => 1,
            'agreed_price'  => 4000,
            'scope'         => 'Six hours of music and the ceremony sound.',
        ]);
    }

    private function pdf(string $body): string
    {
        return $body;
    }

    // ── There is a copy to take ──────────────────────────────

    public function test_the_professional_can_download_their_agreement(): void
    {
        $response = $this->actingAs($this->professional)
            ->get(route('professional.agreements.draft', $this->agreement));

        $response->assertSuccessful();
        $this->assertSame('application/pdf', $response->headers->get('Content-Type'));
        $this->assertStringStartsWith('%PDF', $this->pdf($response->getContent()));
    }

    /** It arrives as a file, named so a folder of them can be told apart. */
    public function test_it_is_saved_rather_than_opened_and_says_whose_it_is(): void
    {
        $disposition = $this->actingAs($this->professional)
            ->get(route('professional.agreements.draft', $this->agreement))
            ->headers->get('Content-Disposition');

        $this->assertStringContainsString('attachment;', (string) $disposition);
        $this->assertStringContainsString(
            \Illuminate\Support\Str::slug($this->client->name),
            (string) $disposition,
            'A professional tells these apart by the client, not by their own name.',
        );
        $this->assertStringEndsWith('-draft.pdf"', (string) $disposition);
    }

    /** And the page they read it from offers it. */
    public function test_the_agreement_page_links_to_the_copy(): void
    {
        $this->actingAs($this->professional)
            ->get(route('professional.agreements.show', $this->agreement))
            ->assertSuccessful()
            ->assertSee(route('professional.agreements.draft', $this->agreement), false);
    }

    // ── And only their own ───────────────────────────────────

    public function test_another_professional_cannot_take_a_copy(): void
    {
        $this->actingAs($this->person('professional'))
            ->get(route('professional.agreements.draft', $this->agreement))
            ->assertForbidden();
    }

    /**
     * The client on this very agreement is refused at this address too.
     *
     * They are not being kept from the document: they have their own copy at
     * their own route. One record with two doors is how the two drift apart.
     */
    public function test_the_client_uses_their_own_address_for_it(): void
    {
        $this->actingAs($this->client)
            ->get(route('professional.agreements.draft', $this->agreement))
            ->assertForbidden();

        $this->actingAs($this->client)
            ->get(route('client.finalize.draft', $this->agreement))
            ->assertSuccessful();
    }

    public function test_a_stranger_is_sent_to_sign_in(): void
    {
        $this->get(route('professional.agreements.draft', $this->agreement))
            ->assertRedirect(route('login'));
    }

    // ── What the copy says ───────────────────────────────────

    /**
     * Unsigned terms are marked as a draft, in the document itself.
     *
     * A draft that reads like a contract is worse than no draft at all: it is
     * a document somebody will believe binds the other side.
     */
    public function test_an_unsigned_copy_calls_itself_a_draft(): void
    {
        $html = $this->renderedFor($this->agreement);

        $this->assertStringContainsString('Draft Event Agreement', $html);
        $this->assertStringContainsString('This is a draft, not a contract.', $html);
    }

    /** Once both have signed, it stops calling itself a draft. */
    public function test_a_signed_copy_is_the_agreement(): void
    {
        Workspace::approve($this->agreement, $this->professional);
        Workspace::approve($this->agreement->fresh(), $this->client);

        $f = $this->agreement->fresh();
        $this->assertTrue(Workspace::signingOpen($f), 'Signing has to be open for this case to exist.');

        $f->forceFill([
            'client_signature'       => $this->client->name,
            'client_signed_at'       => now(),
            'client_signed_version'  => Workspace::version($f),
            'supplier_signature'     => $this->professional->name,
            'supplier_signed_at'     => now(),
            'supplier_signed_version' => Workspace::version($f),
        ])->save();

        $html = $this->renderedFor($f->fresh());

        $this->assertStringContainsString('Event Agreement', $html);
        $this->assertStringNotContainsString('This is a draft, not a contract.', $html);
    }

    /**
     * The client it names is the client on the agreement.
     *
     * The client's own copy passes the signed-in user, which is the same
     * person there and would be the professional here.
     */
    public function test_the_copy_names_the_client_not_the_reader(): void
    {
        $html = $this->renderedFor($this->agreement);

        $this->assertStringContainsString($this->client->name, $html);
    }

    /** Both sides are handed the same document, not two drawn separately. */
    private function renderedFor(Finalization $f): string
    {
        $f->load(['event', 'supplier', 'bid.category', 'client']);

        return view('client.finalize.draft-pdf', [
            'fin'         => $f,
            'event'       => $f->event,
            'pro'         => $f->supplier,
            'client'      => $f->client,
            'service'     => $f->bid?->category?->name,
            'generatedAt' => now(),
        ])->render();
    }
}
