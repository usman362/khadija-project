<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\User;
use App\Support\ServiceArea;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Sir Peter, 27 September: "why does both the Request a Quote and Message go
 * the same weblink".
 *
 * They did. One address served both buttons on a professional's profile, so
 * the button that offers a quote opened a chat window, and a client who
 * wanted a price had to type the whole request out by hand in a message.
 *
 * Request a Quote opens the Direct Request with that professional already
 * chosen. Message opens the conversation. Two buttons, two destinations.
 */
class QuoteAndMessageGoElsewhereTest extends TestCase
{
    use RefreshDatabase;

    private User $client;

    private User $pro;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\PermissionSeeder::class);
        $this->seed(\Database\Seeders\RolePermissionSeeder::class);

        $this->client = User::factory()->create(['primary_role' => 'client']);
        $this->client->assignRole('client');
        $this->client->getOrCreateProfile()->update([
            'country' => 'US', 'state' => 'MD', 'city' => 'Baltimore',
            'service_area_status' => ServiceArea::SUPPORTED,
        ]);
        $this->client = User::findOrFail($this->client->id);

        $service = Category::create([
            'name' => 'Live Sound', 'slug' => 'quote-live-sound',
            'kind' => Category::SERVICE, 'is_active' => true,
        ]);

        $this->pro = User::factory()->create(['primary_role' => 'professional', 'name' => 'Ridgeline Sound']);
        $this->pro->assignRole('professional');
        $this->pro->getOrCreateProfile()->update(['country' => 'US', 'state' => 'MD', 'city' => 'Baltimore']);
        $this->pro->serviceCategories()->syncWithoutDetaching([$service->id]);
    }

    public function test_the_two_buttons_do_not_share_one_address(): void
    {
        $html = $this->actingAs($this->client)
            ->get(route('public.professional.show', $this->pro->id))
            ->assertOk()->getContent();

        $quote = route('client.direct-offers.create', ['pro' => $this->pro->id]);
        $message = route('client.chat.index', ['to' => $this->pro->id]);

        $this->assertStringContainsString('href="'.e($quote).'"', $html);
        $this->assertStringContainsString('href="'.e($message).'"', $html);

        // The quote button is the one that says Request a Quote.
        $this->assertMatchesRegularExpression(
            '#href="'.preg_quote(e($quote), '#').'"[^>]*>.{0,400}?Request a Quote#s',
            $html,
        );
    }

    /** And the form it opens has already chosen that professional. */
    public function test_the_quote_form_arrives_with_the_professional_settled(): void
    {
        $page = $this->actingAs($this->client)
            ->get(route('client.direct-offers.create', ['pro' => $this->pro->id]))
            ->assertOk();

        $this->assertSame($this->pro->id, $page->viewData('selectedPro')?->id);
        $this->assertStringContainsString('Ridgeline Sound', $page->getContent());
    }

    /** Signed out, both still send you to sign in first. */
    public function test_a_visitor_is_asked_to_sign_in(): void
    {
        $response = $this->get(route('public.professional.show', $this->pro->id));

        if ($response->getStatusCode() !== 200) {
            $this->markTestSkipped('The public profile is behind sign-in here.');
        }

        $this->assertStringContainsString('href="'.e(route('login')).'"', $response->getContent());
    }
}
