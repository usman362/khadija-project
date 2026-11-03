<?php

namespace Tests\Feature;

use App\Models\{Category, Event, User};
use App\Support\ServiceArea;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Opening a bid starts from what they already said they charge.
 *
 * The bid box started empty on every request, so a professional retyped the
 * same figure all day, and the one place that knew it, My Services &
 * Pricing, was not asked.
 *
 * It is their own number, offered and editable, and said as theirs. A
 * suggestion from us about what somebody should charge is a figure we have
 * no business putting in front of them about their own money, which is the
 * same line the budget guide draws for clients.
 */
class ABidStartsFromTheirOwnPriceTest extends TestCase
{
    use RefreshDatabase;

    private User $pro;
    private Category $dj;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\PermissionSeeder::class);
        $this->seed(\Database\Seeders\RolePermissionSeeder::class);

        $this->pro = User::factory()->create(['primary_role' => 'professional', 'name' => 'Hollis Tremaine']);
        $this->pro->assignRole('professional');
        $this->pro->getOrCreateProfile()->update([
            'country' => 'US', 'state' => 'MD', 'city' => 'Baltimore',
            'service_area_status' => ServiceArea::SUPPORTED,
        ]);
        $this->pro = User::findOrFail($this->pro->id);

        $this->dj = Category::firstOrCreate(
            ['slug' => 'wedding-dj'],
            ['name' => 'Wedding DJ', 'kind' => Category::SERVICE, 'is_active' => true],
        );

        $client = User::factory()->create(['primary_role' => 'client', 'name' => 'Imogen Hartwell']);
        $client->assignRole('client');
        $client->getOrCreateProfile()->update([
            'country' => 'US', 'state' => 'MD', 'city' => 'Baltimore',
            'service_area_status' => ServiceArea::SUPPORTED,
        ]);

        Event::create([
            'title' => 'Hartwell Anniversary', 'client_id' => $client->id,
            'created_by' => $client->id, 'status' => 'published', 'is_published' => true,
            'starts_at' => now()->addDays(30), 'location' => 'Baltimore, MD', 'state' => 'MD',
        ]);
    }

    private function board(): string
    {
        return $this->actingAs($this->pro)
            ->get(route('professional.bidding-board.index'))
            ->assertSuccessful()
            ->getContent();
    }

    public function test_their_price_reaches_the_board(): void
    {
        $this->pro->serviceCategories()->attach($this->dj->id, ['price' => 1200]);

        $this->assertStringContainsString('"' . $this->dj->id . '":1200', $this->board());
    }

    public function test_a_service_with_no_price_sends_nothing(): void
    {
        $this->pro->serviceCategories()->attach($this->dj->id);

        $this->assertStringNotContainsString('"' . $this->dj->id . '":', $this->board());
    }

    /**
     * It is offered as theirs. The board must not read as GigResource
     * telling a professional what to charge.
     */
    public function test_the_note_says_whose_figure_it_is(): void
    {
        $this->pro->serviceCategories()->attach($this->dj->id, ['price' => 1200]);

        $html = $this->board();

        $this->assertStringContainsString('Your listed price for this service', $html);
        $this->assertStringContainsString('Change it to suit the job', $html);
    }

    /** And it only ever fills an empty box. */
    public function test_it_never_overwrites_an_amount_already_there(): void
    {
        $this->pro->serviceCategories()->attach($this->dj->id, ['price' => 1200]);

        $this->assertStringContainsString('if (!amount.value) {', $this->board());
    }
}
