<?php

namespace Tests\Feature;

use App\Domain\Forms\FormRegistry;
use App\Models\Booking;
use App\Models\Event;
use App\Models\FormSubmission;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The forms audit's missing forms — checklist rows 183, 184, 185, 205, 233,
 * 237, 239, 243, 245, 246.
 *
 * Every one of those rows says the same thing: a workflow document names a
 * form, the rules exist, and nobody ever wrote down the fields. FormRegistry
 * is that missing spec, so a good half of these tests are about the spec
 * itself — and about the three forms that must NOT be built yet.
 */
class FormsAuditTest extends TestCase
{
    use RefreshDatabase;

    private User $client;
    private User $pro;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(\Database\Seeders\PermissionSeeder::class);
        $this->seed(\Database\Seeders\RolePermissionSeeder::class);

        $this->client = $this->account('client');
        $this->pro    = $this->account('professional');
    }

    private function account(string $role): User
    {
        $user = User::factory()->create(['primary_role' => $role]);
        $user->assignRole($role);
        $user->getOrCreateProfile()->update(['country' => 'US', 'state' => 'MD', 'city' => 'Baltimore']);

        return $user->fresh();
    }

    private function booking(): Booking
    {
        $event = Event::create([
            'title' => 'Spring gala', 'client_id' => $this->client->id, 'created_by' => $this->client->id,
            'status' => 'published', 'starts_at' => now()->addDays(30),
        ]);

        return Booking::create([
            'event_id' => $event->id, 'client_id' => $this->client->id, 'supplier_id' => $this->pro->id,
            'created_by' => $this->client->id, 'status' => 'confirmed', 'price' => 1500,
        ]);
    }

    /* ── The spec the rows asked for ────────────────────────── */

    /** Every form the audit named, minus the three that are blocked. */
    public function test_every_audited_form_has_a_field_level_spec(): void
    {
        $expected = [
            'change_order', 'content_report', 'correction_request', 'payout_details',
            'elite_verification', 'influencer_application', 'package_purchase',
            'crew_record', 'shift_request', 'shift_confirmation', 'crew_assignment', 'menu_inventory',
            'campaign_plan', 'testimonial',
        ];

        foreach ($expected as $key) {
            $this->assertArrayHasKey($key, FormRegistry::all(), $key);
        }
    }

    public function test_every_form_names_its_checklist_row_and_its_fields(): void
    {
        foreach (FormRegistry::all() as $key => $form) {
            $this->assertArrayHasKey('row', $form, $key);
            $this->assertArrayHasKey('audience', $form, $key);
            $this->assertNotEmpty($form['purpose'], $key);
            $this->assertNotEmpty($form['fields'], "{$key} has no fields — the whole point of the row");

            foreach ($form['fields'] as $field) {
                if (($field['type'] ?? null) === 'certification') {
                    $this->assertNotEmpty($field['text'], "{$key} has a signature with no wording");
                    continue;
                }

                $this->assertNotEmpty($field['name'] ?? null, $key);
                $this->assertNotEmpty($field['label'] ?? null, "{$key}.{$field['name']} has no label");
            }
        }
    }

    /** Row 243 asked for five crew forms, batched into one pass. */
    public function test_all_five_crew_forms_exist(): void
    {
        $crew = array_filter(FormRegistry::all(), fn ($f) => $f['row'] === 243);

        $this->assertCount(5, $crew);
    }

    /**
     * The three the audit lists that must NOT be built.
     *
     * Each is blocked on a decision nobody has made — lawful consent wording,
     * which trades need insurance, whether tax details live here at all.
     * Building them would mean inventing the answer.
     */
    public function test_the_blocked_forms_are_flagged_and_not_built(): void
    {
        foreach (['data_privacy_consent', 'insurance_coi', 'w9_tax'] as $key) {
            $this->assertArrayHasKey($key, FormRegistry::BLOCKED);
            $this->assertNotEmpty(FormRegistry::BLOCKED[$key]['reason']);
            $this->assertArrayNotHasKey($key, FormRegistry::all(), "{$key} was built despite being blocked");
        }
    }

    /** A blocked form has no page to reach, either. */
    public function test_a_blocked_form_has_no_screen(): void
    {
        $this->actingAs($this->client)->get(route('forms.create', 'w9_tax'))->assertNotFound();
    }

    /* ── Certifications ─────────────────────────────────────── */

    /** §1 of every policy that has one: never pre-ticked. */
    public function test_no_certification_is_pre_ticked(): void
    {
        $page = $this->actingAs($this->client)->get(route('forms.create', 'correction_request'));

        $page->assertOk();
        $page->assertDontSee('name="certify" value="1" checked', false);
        $page->assertSee('holds the final payment', false);
    }

    public function test_a_form_cannot_be_sent_without_its_certification(): void
    {
        $booking = $this->booking();

        $this->actingAs($this->client)->post(route('forms.store', 'correction_request'), [
            'booking_id'  => $booking->id,
            'deliverable' => 'The edited photographs',
            'expected'    => 'Two hundred edited images within three weeks.',
            'requested'   => 'The remaining images, edited to the same standard.',
        ])->assertSessionHasErrors('certify');

        $this->assertDatabaseCount('form_submissions', 0);
    }

    /** The wording shown is stored with the record, not looked up later. */
    public function test_the_certification_wording_is_stored_with_the_submission(): void
    {
        $booking = $this->booking();

        $this->actingAs($this->client)->post(route('forms.store', 'correction_request'), [
            'booking_id'  => $booking->id,
            'deliverable' => 'The edited photographs',
            'expected'    => 'Two hundred edited images within three weeks.',
            'requested'   => 'The remaining images, edited to the same standard.',
            'certify'     => '1',
        ])->assertRedirect();

        $this->assertStringContainsString(
            'holds the final payment',
            FormSubmission::firstOrFail()->certification_text,
        );
    }

    /* ── Row 183: the Change Order's dual approval ──────────── */

    public function test_a_change_order_is_a_proposal_until_the_other_side_accepts(): void
    {
        $booking = $this->booking();

        $this->actingAs($this->client)->post(route('forms.store', 'change_order'), [
            'booking_id'  => $booking->id,
            'change_type' => 'date',
            'current'     => 'The gala was booked for the eighteenth.',
            'proposed'    => 'We need to move it to the twenty-fifth.',
            'reason'      => 'The venue double-booked our original date.',
            'certify'     => '1',
        ])->assertRedirect();

        $order = FormSubmission::firstOrFail();

        $this->assertTrue($order->needsApproval());
        $this->assertSame('pending', $order->approval_status);
        $this->assertSame($this->pro->id, $order->counterparty_id);
        $this->assertFalse($order->isAccepted());
    }

    public function test_only_the_other_party_answers_a_change_order(): void
    {
        $order = $this->changeOrder();

        // Not the person who raised it.
        $this->actingAs($this->client)
            ->post(route('forms.respond', $order), ['decision' => 'accepted'])
            ->assertForbidden();

        // Not a passer-by.
        $this->actingAs($this->account('client'))
            ->post(route('forms.respond', $order), ['decision' => 'accepted'])
            ->assertForbidden();

        $this->actingAs($this->pro)
            ->post(route('forms.respond', $order), ['decision' => 'accepted'])
            ->assertRedirect();

        $this->assertTrue($order->fresh()->isAccepted());
    }

    /** And only once — a decision is not re-openable by re-posting. */
    public function test_a_change_order_is_answered_once(): void
    {
        $order = $this->changeOrder();

        $this->actingAs($this->pro)->post(route('forms.respond', $order), ['decision' => 'declined']);
        $this->actingAs($this->pro)->post(route('forms.respond', $order), ['decision' => 'accepted'])
            ->assertForbidden();

        $this->assertSame('declined', $order->fresh()->approval_status);
    }

    /* ── Scope and access ───────────────────────────────────── */

    /** A booking form offers your own bookings, never everyone's. */
    public function test_a_form_about_a_booking_refuses_someone_elses(): void
    {
        $booking  = $this->booking();
        $outsider = $this->account('client');

        $this->actingAs($outsider)->post(route('forms.store', 'correction_request'), [
            'booking_id'  => $booking->id,
            'deliverable' => 'Something on a booking that is not mine',
            'expected'    => 'This booking has nothing to do with my account.',
            'requested'   => 'Nothing, I should not be able to send this.',
            'certify'     => '1',
        ])->assertForbidden();
    }

    /** A form is offered to the people who can actually file it. */
    public function test_a_client_is_not_offered_the_professionals_forms(): void
    {
        $page = $this->actingAs($this->client)->get(route('forms.index'));

        $page->assertOk();
        $page->assertSee('Request a Correction', false);
        $page->assertDontSee('Confirm Your Payout Details', false);

        $this->actingAs($this->client)->get(route('forms.create', 'payout_details'))->assertForbidden();
    }

    public function test_a_professional_sees_their_own_and_the_shared_ones(): void
    {
        $page = $this->actingAs($this->pro)->get(route('forms.index'));

        $page->assertSee('Confirm Your Payout Details', false);
        $page->assertSee('Report Content', false);          // audience: anyone
        $page->assertDontSee('Request a Correction', false); // client only
    }

    /**
     * Row 205 is money-consequential, and the safest version of it collects
     * no bank details at all — those belong to the licensed processor. A
     * marketplace holding account numbers has taken on a liability it gains
     * nothing from.
     */
    public function test_the_payout_form_never_asks_for_bank_numbers(): void
    {
        $page = $this->actingAs($this->pro)->get(route('forms.create', 'payout_details'));

        $page->assertOk();
        $page->assertSee('never ask for bank or card numbers', false);

        $names = array_column(FormRegistry::get('payout_details')['fields'], 'name');

        foreach (['account_number', 'routing_number', 'iban', 'sort_code', 'card_number'] as $forbidden) {
            $this->assertNotContains($forbidden, $names);
        }
    }

    /* ── Sending and reading ────────────────────────────────── */

    public function test_a_form_with_no_subject_can_still_be_sent(): void
    {
        $this->actingAs($this->client)->post(route('forms.store', 'testimonial'), [
            'headline' => 'Found our photographer in a day',
            'story'    => 'We posted on a Tuesday and had three proposals by Wednesday morning.',
        ])->assertRedirect();

        $this->assertDatabaseCount('form_submissions', 1);
    }

    /** Nothing goes public unless the person ticked the box saying it may. */
    public function test_a_testimonial_is_private_unless_permission_is_given(): void
    {
        $this->actingAs($this->client)->post(route('forms.store', 'testimonial'), [
            'headline' => 'Found our photographer in a day',
            'story'    => 'We posted on a Tuesday and had three proposals by Wednesday morning.',
        ]);

        $this->assertNull(FormSubmission::firstOrFail()->payload['may_publish'] ?? null);
    }

    public function test_a_submission_is_readable_by_its_two_sides_and_nobody_else(): void
    {
        $order = $this->changeOrder();

        $this->actingAs($this->client)->get(route('forms.show', $order))->assertOk();
        $this->actingAs($this->pro)->get(route('forms.show', $order))->assertOk();
        $this->actingAs($this->account('client'))->get(route('forms.show', $order))->assertForbidden();
    }

    /** Answers are shown under the labels they were asked with. */
    public function test_the_submission_reads_back_in_the_words_it_was_asked_in(): void
    {
        $order = $this->changeOrder();

        $this->actingAs($this->pro)
            ->get(route('forms.show', $order))
            ->assertSee('What you are proposing instead', false)
            ->assertSee('We need to move it to the twenty-fifth.', false);
    }

    private function changeOrder(): FormSubmission
    {
        $booking = $this->booking();

        $this->actingAs($this->client)->post(route('forms.store', 'change_order'), [
            'booking_id'  => $booking->id,
            'change_type' => 'date',
            'current'     => 'The gala was booked for the eighteenth.',
            'proposed'    => 'We need to move it to the twenty-fifth.',
            'reason'      => 'The venue double-booked our original date.',
            'certify'     => '1',
        ]);

        return FormSubmission::firstOrFail();
    }
}
