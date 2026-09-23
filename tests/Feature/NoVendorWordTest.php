<?php

namespace Tests\Feature;

use App\Models\User;
use App\Support\ServiceArea;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Ali, 24 September: "yeh vendor etc ni ayega, professional hai vendor nhi."
 *
 * The platform calls them professionals. "Vendor" had been left in the copy
 * in a few dozen places — a tag on a profile, a table heading, the wording of
 * the agreement, the AI tools' descriptions — so the same person was a
 * professional on one screen and a vendor on the next.
 *
 * The word is allowed to stay in class names, array keys and code comments,
 * which nobody reads on the page. What this holds is the rendered text.
 */
class NoVendorWordTest extends TestCase
{
    use RefreshDatabase;

    private function client(): User
    {
        $this->seed(\Database\Seeders\PermissionSeeder::class);
        $this->seed(\Database\Seeders\RolePermissionSeeder::class);

        $u = User::factory()->create(['primary_role' => 'client']);
        $u->assignRole('client');
        $u->getOrCreateProfile()->update([
            'country' => 'US', 'state' => 'MD', 'city' => 'Baltimore',
            'service_area_status' => ServiceArea::SUPPORTED,
        ]);

        return User::findOrFail($u->id);
    }

    /** The visible text of a page, with markup and script taken out. */
    private function visibleText(string $html): string
    {
        $html = preg_replace('#<(script|style)\b[^>]*>.*?</\1>#is', ' ', $html);

        return html_entity_decode(strip_tags((string) $html), ENT_QUOTES | ENT_HTML5);
    }

    public function test_no_client_page_calls_a_professional_a_vendor(): void
    {
        $client = $this->client();

        $paths = [
            '/client/dashboard', '/client/events', '/client/events/create',
            '/client/bookings', '/client/proposals', '/client/messages',
            '/client/direct-offers/create', '/client/esr/create',
            '/client/payments', '/client/spending', '/ai-tools',
        ];

        $found = [];

        foreach ($paths as $path) {
            $response = $this->actingAs($client)->get($path);

            if ($response->getStatusCode() !== 200) {
                continue;
            }

            if (preg_match('/\bvendors?\b/i', $this->visibleText($response->getContent()), $m)) {
                $found[] = "{$path} says \"{$m[0]}\"";
            }
        }

        $this->assertSame([], $found, "pages still saying vendor:\n".implode("\n", $found));
    }

    public function test_a_new_professional_is_not_called_a_new_vendor(): void
    {
        $client = $this->client();

        $pro = User::factory()->create(['primary_role' => 'professional', 'name' => 'SnapSpin 360']);
        $pro->assignRole('professional');
        $pro->getOrCreateProfile()->update(['country' => 'US', 'state' => 'MD', 'city' => 'Baltimore']);

        $html = $this->actingAs($client)
            ->get(route('public.professional.show', $pro->id))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('New Professional', $html);
        $this->assertDoesNotMatchRegularExpression('/\bvendors?\b/i', $this->visibleText($html));
    }
}
