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
 * Requests & Submissions — the landing screen.
 *
 * It states four numbers about what a person is part of and then offers tabs
 * that filter the same list, so the numbers and the tabs have to be one
 * reading. The one to be careful about is "Needs Your Action": telling someone
 * to act when there is nothing to act on is worse than saying nothing.
 */
class RequestsShelfTest extends TestCase
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

    /** A change order the client sent the professional, awaiting a decision. */
    private function changeOrder(array $over = []): FormSubmission
    {
        return FormSubmission::create(array_merge([
            'form_key'        => 'change_order',
            'submitted_by'    => $this->client->id,
            'submitted_role'  => 'client',
            'counterparty_id' => $this->pro->id,
            'approval_status' => 'pending',
            'status'          => 'submitted',
            'payload'         => ['what_changes' => 'date', 'detail' => 'Moved an hour later.'],
        ], $over));
    }

    private function counts(User $user, array $query = []): array
    {
        return $this->actingAs($user)->get(route('forms.index', $query))
            ->assertOk()->viewData('counts');
    }

    // ── "Needs Your Action" ──────────────────────────────────────

    public function test_a_pending_change_order_waits_on_the_other_party_not_the_sender(): void
    {
        $this->changeOrder();

        $this->assertSame(1, $this->counts($this->pro)['action'], 'the person who must decide is waiting');
        $this->assertSame(0, $this->counts($this->client)['action'], 'the sender is not waiting on themselves');
        $this->assertSame(1, $this->counts($this->client)['review'], 'for the sender it is simply in progress');
    }

    public function test_a_form_with_no_approval_step_waits_on_neither_party(): void
    {
        // It is with our team. Neither side can move it, so neither is told
        // they can.
        FormSubmission::create([
            'form_key' => 'support_request', 'submitted_by' => $this->pro->id,
            'submitted_role' => 'professional', 'status' => 'submitted',
            'payload' => ['topic' => 'payments', 'details' => 'Where is my payout?'],
        ]);

        $this->assertSame(0, $this->counts($this->pro)['action']);
        $this->assertSame(1, $this->counts($this->pro)['review']);
    }

    public function test_an_answered_change_order_stops_waiting_on_anyone(): void
    {
        $this->changeOrder(['approval_status' => 'accepted', 'approved_at' => now()]);

        $this->assertSame(0, $this->counts($this->pro)['action']);
        $this->assertSame(1, $this->counts($this->pro)['completed']);
    }

    public function test_a_declined_or_withdrawn_request_is_closed(): void
    {
        $this->changeOrder(['approval_status' => 'declined', 'approved_at' => now()]);
        FormSubmission::create([
            'form_key' => 'content_report', 'submitted_by' => $this->pro->id,
            'submitted_role' => 'professional', 'status' => 'withdrawn',
            'payload' => ['what' => 'message', 'details' => 'Reported in error.'],
        ]);

        $this->assertSame(2, $this->counts($this->pro)['closed']);
        $this->assertSame(0, $this->counts($this->pro)['action']);
        $this->assertSame(0, $this->counts($this->pro)['review']);
    }

    // ── Each tile counts the list its tab opens (R1/R6) ──────────

    public function test_every_tile_counts_the_list_its_tab_shows(): void
    {
        $this->changeOrder();
        $this->changeOrder(['approval_status' => 'accepted', 'approved_at' => now()]);
        $this->changeOrder(['approval_status' => 'declined', 'approved_at' => now()]);
        FormSubmission::create([
            'form_key' => 'support_request', 'submitted_by' => $this->pro->id,
            'submitted_role' => 'professional', 'status' => 'submitted',
            'payload' => ['topic' => 'account', 'details' => 'A question.'],
        ]);

        $counts = $this->counts($this->pro);
        $this->assertSame(4, $counts['all']);

        foreach (['action', 'review', 'completed', 'closed'] as $tab) {
            $rows = $this->actingAs($this->pro)->get(route('forms.index', ['tab' => $tab]))
                ->assertOk()->viewData('submissions');

            $this->assertCount($counts[$tab], $rows, "the {$tab} tab shows what its tile counted");
        }
    }

    public function test_a_request_you_are_not_part_of_is_not_on_your_shelf(): void
    {
        $this->changeOrder();

        $stranger = $this->account('client');

        $this->assertSame(0, $this->counts($stranger)['all']);
    }

    // ── The four areas ───────────────────────────────────────────

    public function test_every_form_belongs_to_exactly_one_area(): void
    {
        // A form in two areas is a form somebody files twice; a form in none
        // is one nobody can reach from this screen.
        $grouped = array_merge(...array_column(FormRegistry::GROUPS, 'keys'));

        $this->assertSame([], array_diff(array_keys(FormRegistry::all()), $grouped), 'a form is in no area');
        $this->assertSame(count($grouped), count(array_unique($grouped)), 'a form is in two areas');
    }

    public function test_an_area_card_counts_only_the_forms_that_audience_may_file(): void
    {
        /*
         * The card says "N request types" and opens that list. Counted across
         * every audience it would promise a client a professional's forms and
         * then 403 them.
         */
        $groups = $this->actingAs($this->client)->get(route('forms.index'))
            ->assertOk()->viewData('groups');

        foreach ($groups as $slug => $group) {
            foreach ($group['forms'] as $key => $form) {
                $this->assertContains($form['audience'], [FormRegistry::CLIENT, FormRegistry::ANYONE],
                    "{$key} is offered to a client and is not theirs to file");
            }
        }

        // And the count on the card is the size of that same list.
        $html = $this->actingAs($this->client)->get(route('forms.index'))->getContent();
        foreach ($groups as $group) {
            $n = count($group['forms']);
            $this->assertStringContainsString($n . ' request type', $html);
        }
    }

    public function test_the_page_lists_what_you_can_send_without_a_click(): void
    {
        // Putting the forms behind an area card would mean the page whose job
        // is "here is what you can send" needed a click before it said so.
        $this->actingAs($this->client)->get(route('forms.index'))
            ->assertOk()
            ->assertSee('Request a Correction')
            ->assertSee('Contact Support');
    }

    // ── Filters ──────────────────────────────────────────────────

    public function test_the_area_filter_narrows_the_list(): void
    {
        $this->changeOrder();                       // bookings
        FormSubmission::create([                    // safety
            'form_key' => 'support_request', 'submitted_by' => $this->pro->id,
            'submitted_role' => 'professional', 'status' => 'submitted',
            'payload' => ['topic' => 'account', 'details' => 'A question.'],
        ]);

        $rows = $this->actingAs($this->pro)->get(route('forms.index', ['group' => 'safety']))
            ->assertOk()->viewData('submissions');

        $this->assertCount(1, $rows);
        $this->assertSame('support_request', $rows->first()->form_key);
    }

    public function test_search_matches_the_form_name_and_the_reference(): void
    {
        $order = $this->changeOrder();
        FormSubmission::create([
            'form_key' => 'support_request', 'submitted_by' => $this->pro->id,
            'submitted_role' => 'professional', 'status' => 'submitted',
            'payload' => ['topic' => 'account', 'details' => 'A question.'],
        ]);

        $byName = $this->actingAs($this->pro)->get(route('forms.index', ['q' => 'change']))
            ->assertOk()->viewData('submissions');
        $this->assertCount(1, $byName);

        $byRef = $this->actingAs($this->pro)->get(route('forms.index', ['q' => $order->reference]))
            ->assertOk()->viewData('submissions');
        $this->assertCount(1, $byRef);
    }

    public function test_a_made_up_filter_falls_back_rather_than_emptying_the_page(): void
    {
        $this->changeOrder();

        foreach ([['tab' => 'nonsense'], ['range' => '999'], ['group' => 'gremlins']] as $query) {
            $this->assertCount(1, $this->actingAs($this->pro)
                ->get(route('forms.index', $query))->assertOk()->viewData('submissions'));
        }
    }

    // ── The name in the sidebar ──────────────────────────────────

    public function test_the_sidebar_calls_it_requests_and_submissions(): void
    {
        // "Forms" named the thing we built, not the thing the person came to do.
        foreach ([$this->client, $this->pro] as $user) {
            $this->actingAs($user)->get(route('forms.index'))
                ->assertOk()
                ->assertSee('Requests &amp; Submissions', false);
        }
    }

    // ── No deadlines stated ──────────────────────────────────────

    public function test_the_next_step_column_never_states_a_deadline(): void
    {
        // Nobody has agreed a response time for any of these, and a number
        // here would be one the platform then has to meet.
        $this->changeOrder();
        $this->actingAs($this->pro);

        $html = $this->get(route('forms.index'))->assertOk()->getContent();
        $html = preg_replace('/\{\{--.*?--\}\}/s', '', $html);

        foreach (['24-48', '24–48', 'within 48 hours', 'business days', 'within 2 days'] as $deadline) {
            $this->assertStringNotContainsStringIgnoringCase($deadline, $html);
        }
    }
}
