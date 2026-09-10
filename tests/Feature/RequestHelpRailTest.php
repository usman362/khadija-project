<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Every request flow explains itself, and explains itself the same way.
 *
 * Sir Peter, 2026-09-10, sending recreated versions of four of our screens:
 * "we lack data for the users in these current we have." The forms were not
 * missing records. They were missing what sits beside them in every one of his
 * mockups -- how it works, what it costs, what to write.
 *
 * And 2026-09-09, the rule underneath it: "if all of these workflows ultimately
 * lead to an agreement, I would make the core agreement-driving information
 * consistent across all of them." That is the part these tests hold. Four
 * hand-written rails is how the $2.99 came to be explained in four different
 * sentences; the direct request page had no fee panel at all.
 */
class RequestHelpRailTest extends TestCase
{
    use RefreshDatabase;

    private function client(): User
    {
        $this->seed(\Database\Seeders\PermissionSeeder::class);
        $this->seed(\Database\Seeders\RolePermissionSeeder::class);

        $user = User::factory()->create();
        $user->assignRole('client');
        $user->getOrCreateProfile()->update(['country' => 'US', 'state' => 'MD', 'city' => 'Baltimore']);

        return $user->fresh();
    }

    /** @return array<string, string> flow => route */
    private function flows(): array
    {
        return [
            'br'      => route('client.bsr.step', 'service'),
            'er'      => route('client.esr.create'),
            'dr'      => route('client.direct-offers.create'),
            'toolkit' => route('client.toolkit.plan'),
        ];
    }

    private function railOf(string $url): string
    {
        $html = $this->actingAs($this->client())->get($url)->assertOk()->getContent();

        // Only the rail. The rest of the page mentions the fee too, and a
        // whole-document search would pass on the form's own wording.
        $at = strpos($html, 'class="hr-rail"');
        $this->assertNotFalse($at, "No help rail on {$url}.");

        return substr($html, $at, strpos($html, '</aside>', $at) - $at);
    }

    public function test_every_request_flow_has_a_help_rail(): void
    {
        foreach ($this->flows() as $flow => $url) {
            $rail = $this->railOf($url);

            foreach (config('request-help.' . $flow) as $panel) {
                $this->assertStringContainsString(
                    e($panel['title']),
                    $rail,
                    "The {$flow} rail is missing its \"{$panel['title']}\" panel.",
                );
            }
        }
    }

    /**
     * The fee is the agreement-driving fact. It is one entry in the config and
     * it has to arrive identical on every screen that sends a request.
     */
    public function test_the_fee_is_explained_in_the_same_words_everywhere(): void
    {
        // Not the bidding request: Sir Peter wants its $2.99 shown only as
        // the checkbox on the last step. See the test below.
        $sending = ['er', 'dr'];

        foreach ($sending as $flow) {
            $rail = $this->railOf($this->flows()[$flow]);

            $this->assertStringContainsString('$0 to post', $rail, "The {$flow} rail does not say posting is free.");
            $this->assertStringContainsString(
                'Charged only when you finalize with a professional.',
                $rail,
                "The {$flow} rail explains the fee in its own words.",
            );
        }
    }

    /** Sir Peter, 11 Sep: on the bidding request the fee is on the last step only. */
    public function test_the_bidding_rail_does_not_repeat_the_fee(): void
    {
        $rail = $this->railOf($this->flows()['br']);

        $this->assertStringNotContainsString('2.99', $rail);
        $this->assertStringNotContainsString("What it'll cost", $rail);
    }

    /**
     * Sir Peter's compliance wording, which applies to explanatory copy as much
     * as to marketing: nothing promised that the system does not do.
     */
    public function test_the_copy_promises_nothing_we_do_not_do(): void
    {
        $banned = ['guarantee', '24/7', 'instantly', 'always available', 'best price', 'money back', 'refund'];

        foreach (config('request-help') as $flow => $panels) {
            foreach ($panels as $panel) {
                $lines = array_merge($panel['steps'] ?? [], $panel['items'] ?? []);

                foreach ($lines as $line) {
                    $text = strtolower($line['b'] . ' ' . $line['t']);

                    foreach ($banned as $claim) {
                        $this->assertStringNotContainsString(
                            $claim,
                            $text,
                            "The {$flow} rail claims \"{$claim}\" in \"{$line['b']}\".",
                        );
                    }
                }
            }
        }
    }

    /** Written once. A fifth hand-written rail is how the four drifted apart. */
    public function test_no_page_writes_its_own_rail(): void
    {
        $strays = [];

        foreach (['esr/create', 'direct-offers/create', 'bsr/wizard', 'toolkit/plan'] as $view) {
            $html = file_get_contents(resource_path("views/client/{$view}.blade.php"));

            if (preg_match('/class="(esr-rcard|do-rcard|bw-rail-card)"/', $html)) {
                $strays[] = $view;
            }
        }

        $this->assertSame([], $strays, 'These pages still hand-write their own help panels: ' . implode(', ', $strays));
    }
}
