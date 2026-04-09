<?php

namespace Tests\Feature;

use App\Domain\Auth\Enums\RoleName;
use App\Domain\Influencer\Contracts\InfluencerServiceInterface;
use App\Domain\Influencer\DataTransferObjects\InfluencerApplicationData;
use App\Models\Booking;
use App\Models\Event;
use App\Models\Influencer;
use App\Models\User;
use Database\Seeders\RolesTableSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InfluencerModuleTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesTableSeeder::class);
    }

    public function test_join_page_loads(): void
    {
        $this->get('/join-as-influencer')->assertOk();
    }

    public function test_guest_can_submit_application(): void
    {
        $response = $this->post('/join-as-influencer', [
            'full_name' => 'Jane Doe',
            'email' => 'jane@example.com',
            'social_media_links' => 'https://instagram.com/jane, https://youtube.com/@jane',
            'audience_description' => 'Event planners and creators',
            'monthly_reach' => 50000,
        ]);
        $response->assertRedirect(route('influencer.join'));

        $this->assertDatabaseHas('influencers', [
            'email' => 'jane@example.com',
            'status' => 'pending',
        ]);
    }

    public function test_admin_can_approve_influencer(): void
    {
        $admin = $this->makeUserWithRole(RoleName::ADMIN->value);
        $influencer = Influencer::create([
            'full_name' => 'Test',
            'email' => 'test@influencer.com',
            'referral_code' => Influencer::generateReferralCode(),
            'commission_tier' => 'starter',
            'status' => 'pending',
        ]);

        $this->actingAs($admin)
            ->post(route('app.influencers.approve', $influencer))
            ->assertRedirect();

        $this->assertDatabaseHas('influencers', [
            'id' => $influencer->id,
            'status' => 'approved',
        ]);
    }

    public function test_signup_attribution_credits_bonus(): void
    {
        $influencer = Influencer::create([
            'full_name' => 'I',
            'email' => 'i@e.com',
            'referral_code' => 'REFCODE1',
            'commission_tier' => 'starter',
            'status' => 'approved',
        ]);

        $newUser = $this->makeUserWithRole(RoleName::CLIENT->value);

        app(InfluencerServiceInterface::class)->attributeSignup($newUser, 'REFCODE1');

        $influencer->refresh();
        $this->assertEquals(1, $influencer->total_referrals);
        $this->assertEquals((float) config('influencer.signup_bonus'), (float) $influencer->total_earnings);
        $this->assertDatabaseHas('users', [
            'id' => $newUser->id,
            'referred_by_influencer_id' => $influencer->id,
        ]);
    }

    public function test_booking_completion_credits_commission(): void
    {
        $influencer = Influencer::create([
            'full_name' => 'I',
            'email' => 'i@e.com',
            'referral_code' => 'REFCODE2',
            'commission_tier' => 'starter',
            'status' => 'approved',
        ]);

        $client = $this->makeUserWithRole(RoleName::CLIENT->value);
        $supplier = $this->makeUserWithRole(RoleName::SUPPLIER->value);
        $client->update(['referred_by_influencer_id' => $influencer->id]);

        $event = Event::create([
            'title' => 'Test Event',
            'description' => 'd',
            'status' => 'pending',
            'client_id' => $client->id,
            'supplier_id' => $supplier->id,
            'created_by' => $client->id,
        ]);

        $booking = Booking::create([
            'event_id' => $event->id,
            'client_id' => $client->id,
            'supplier_id' => $supplier->id,
            'created_by' => $client->id,
            'status' => 'confirmed',
            'price' => 200.00,
        ]);

        $booking->update(['status' => 'completed']);

        $influencer->refresh();
        $this->assertGreaterThan(0, (float) $influencer->total_earnings);
        $this->assertDatabaseHas('influencer_referrals', [
            'booking_id' => $booking->id,
            'type' => 'booking_commission',
        ]);
    }

    public function test_payout_request_requires_minimum(): void
    {
        $user = $this->makeUserWithRole(RoleName::CLIENT->value);
        $influencer = Influencer::create([
            'user_id' => $user->id,
            'full_name' => 'Test',
            'email' => 'test@e.com',
            'referral_code' => 'CODEX',
            'commission_tier' => 'starter',
            'status' => 'approved',
            'available_balance' => 10.00,
            'total_earnings' => 10.00,
        ]);

        $this->expectException(\RuntimeException::class);
        app(InfluencerServiceInterface::class)->requestPayout($influencer, 10.00, 'paypal', 'x@y.com', null);
    }

    protected function makeUserWithRole(string $role): User
    {
        $user = User::create([
            'name' => 'User ' . uniqid(),
            'email' => uniqid() . '@example.com',
            'password' => bcrypt('password'),
        ]);
        $user->assignRole($role);
        return $user;
    }
}
