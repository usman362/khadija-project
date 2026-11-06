<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Event;
use App\Models\ProfessionalClientNote;
use App\Models\User;
use App\Support\ServiceArea;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Tags and the quick note on My Clients.
 *
 * Khadijah's screen has both. Nothing on the platform records that a client
 * is "high value" or "prefers evening setup", so the only honest version is
 * one the professional writes themselves, and it stays private to them.
 */
class AProfessionalKeepsTheirOwnNoteOnAClientTest extends TestCase
{
    use RefreshDatabase;

    private User $pro;
    private User $client;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\PermissionSeeder::class);
        $this->seed(\Database\Seeders\RolePermissionSeeder::class);

        $this->pro    = $this->person('professional', 'Hollis Tremaine');
        $this->client = $this->person('client', 'Imogen Hartwell');
    }

    private function person(string $role, string $name): User
    {
        $u = User::factory()->create(['primary_role' => $role, 'name' => $name]);
        $u->assignRole($role);
        $u->getOrCreateProfile()->update([
            'country' => 'US', 'state' => 'MD', 'city' => 'Baltimore',
            'service_area_status' => ServiceArea::SUPPORTED,
        ]);

        return User::findOrFail($u->id);
    }

    private function booking(): Booking
    {
        $event = Event::create([
            'title' => 'Hartwell Anniversary', 'client_id' => $this->client->id,
            'created_by' => $this->client->id, 'supplier_id' => $this->pro->id,
            'status' => 'published', 'is_published' => true,
            'starts_at' => now()->subDays(5), 'location' => 'Baltimore, MD',
        ]);

        return Booking::create([
            'event_id' => $event->id, 'client_id' => $this->client->id,
            'created_by' => $this->client->id, 'supplier_id' => $this->pro->id,
            'status' => 'completed', 'price' => 1800,
        ]);
    }

    public function test_tags_and_a_note_are_saved_and_shown_back(): void
    {
        $this->booking();

        $this->actingAs($this->pro)->post(route('professional.clients.note', $this->client), [
            'tags' => 'Wedding, High value, Wedding',
            'note' => 'Prefers an evening setup.',
        ])->assertRedirect();

        $note = ProfessionalClientNote::firstOrFail();

        // Duplicates are dropped, so the same tag twice is one tag.
        $this->assertSame(['Wedding', 'High value'], $note->tags);
        $this->assertSame('Prefers an evening setup.', $note->note);

        $this->actingAs($this->pro)->get(route('professional.clients.index'))
            ->assertOk()
            ->assertSee('High value')
            ->assertSee('Prefers an evening setup.');
    }

    /** It is one professional's note about one client, not a public label. */
    public function test_another_professional_cannot_see_it(): void
    {
        $this->booking();

        $this->actingAs($this->pro)->post(route('professional.clients.note', $this->client), [
            'tags' => 'Pays late',
        ]);

        $other = $this->person('professional', 'Marguerite Okafor');

        $this->actingAs($other)->get(route('professional.clients.index'))
            ->assertOk()
            ->assertDontSee('Pays late');
    }

    /** A note can only be put against somebody who actually hired them. */
    public function test_a_note_cannot_be_left_on_a_stranger(): void
    {
        $stranger = $this->person('client', 'Somebody Else');

        $this->actingAs($this->pro)->post(route('professional.clients.note', $stranger), [
            'note' => 'Should not be stored',
        ])->assertNotFound();

        $this->assertDatabaseCount('professional_client_notes', 0);
    }

    /** Lifetime value is money that was paid, not a forecast. */
    public function test_lifetime_value_is_what_they_actually_paid(): void
    {
        $this->booking();

        $this->actingAs($this->pro)->get(route('professional.clients.index'))
            ->assertOk()
            ->assertSee('Lifetime value')
            ->assertSee('$1,800.00');
    }

    /** No contact at all is said plainly, not shown as a number. */
    public function test_a_client_with_no_contact_is_not_given_a_date(): void
    {
        Booking::create([
            'event_id' => Event::create([
                'title' => 'Undated job', 'client_id' => $this->client->id,
                'created_by' => $this->client->id, 'supplier_id' => $this->pro->id,
                'status' => 'published', 'is_published' => true,
            ])->id,
            'client_id' => $this->client->id, 'created_by' => $this->client->id,
            'supplier_id' => $this->pro->id, 'status' => 'requested', 'price' => 100,
        ]);

        $this->actingAs($this->pro)->get(route('professional.clients.index'))
            ->assertOk()
            // The column was renamed with the table rebuild; what it must
            // still do is say Never rather than show a date.
            ->assertSee('Since contact')
            ->assertSee('Never');
    }
}
