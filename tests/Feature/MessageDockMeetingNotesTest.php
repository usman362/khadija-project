<?php

namespace Tests\Feature;

use App\Models\User;
use App\Support\ServiceArea;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Sir Peter's meeting notes, 25 September, items 1 to 3.
 *
 * The right-side popup stays; what changed is the clutter around it. The
 * strip along the top shows a picture and a first name and nothing else, and
 * condenses to pictures alone when several conversations are open. The
 * controls became four icons in one group on the right, each naming itself
 * on hover in three words at most instead of wearing its label all day.
 *
 * The strip is drawn in the browser, so what a test can hold is the template
 * it is drawn from, and that the four controls are there with their words.
 */
class MessageDockMeetingNotesTest extends TestCase
{
    use RefreshDatabase;

    private function dock(): string
    {
        $this->seed(\Database\Seeders\PermissionSeeder::class);
        $this->seed(\Database\Seeders\RolePermissionSeeder::class);

        $client = User::factory()->create(['primary_role' => 'client']);
        $client->assignRole('client');
        $client->getOrCreateProfile()->update([
            'country' => 'US', 'state' => 'MD', 'city' => 'Baltimore',
            'service_area_status' => ServiceArea::SUPPORTED,
        ]);

        return $this->actingAs(User::findOrFail($client->id))
            ->get('/client/dashboard')->assertOk()->getContent();
    }

    public function test_the_strip_carries_a_picture_and_a_first_name(): void
    {
        $dock = $this->dock();

        // The first name, and the whole name only as the hover title.
        $this->assertStringContainsString("(p.name || '').split(' ')[0]", $dock);

        // The four things that came off it, read out of the template the
        // strip is drawn from rather than the page as a whole: the chat
        // window below still shows a priority on each message, which is
        // where Sir Peter asked for it.
        $start = strpos($dock, 'function tabHtml(');
        $this->assertNotFalse($start, 'The strip is drawn somewhere else now.');
        $template = substr($dock, $start, strpos($dock, 'function renderTabs(') - $start);

        foreach ([
            'lmd-pri' => 'the priority badge',
            'lmd-st'  => 'the read and unread dot',
            'lmd-pv'  => 'the message preview',
            'lmd-ok'  => 'the replied tick',
        ] as $marker => $what) {
            $this->assertStringNotContainsString($marker, $template, "The strip still draws {$what}.");
        }

        // Condensing to pictures alone is what the crowded view does.
        $this->assertStringContainsString('is-mini', $dock);
        $this->assertStringContainsString('.lmd-tab.is-mini .lmd-tab-t { display: none; }', $dock);
    }

    public function test_the_controls_are_four_icons_that_name_themselves_on_hover(): void
    {
        $dock = $this->dock();

        foreach (['Sound', 'Do Not Disturb', 'Message Settings', 'New Message'] as $tip) {
            $this->assertStringContainsString('data-tip="'.$tip.'"', $dock, "No tooltip for {$tip}.");
            $this->assertLessThanOrEqual(3, count(explode(' ', $tip)), "The tooltip \"{$tip}\" is longer than three words.");
        }

        // The label that used to sit there all day, and its explanation.
        $this->assertStringNotContainsString('<span>Message Settings</span>', $dock);
        $this->assertStringNotContainsString('Still receive messages (no sound)', $dock);
    }

    /** "Do not create a new standalone messaging webpage as the solution." */
    public function test_the_popup_is_still_the_popup(): void
    {
        $dock = $this->dock();

        $this->assertStringContainsString('class="lmd-bar"', $dock);
        $this->assertStringContainsString('class="lmd-chat"', $dock);
    }
}
