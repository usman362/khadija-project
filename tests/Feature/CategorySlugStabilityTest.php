<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * A published category keeps its address.
 *
 * The admin screen built every slug as Str::slug($name) . '-' . Str::random(4),
 * on create AND on update. So a category was born at a gibberish URL, and every
 * later save -- even one that only touched a description -- moved it to a new
 * one. On production that left most event types at addresses like
 * /category/bridal-shower-9mKC while the clean URL people had shared and
 * search engines had indexed returned 404.
 *
 * These guard the two halves: a new category gets a readable slug, and an edit
 * never changes an existing one.
 */
class CategorySlugStabilityTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        $this->seed(\Database\Seeders\PermissionSeeder::class);
        $this->seed(\Database\Seeders\RolePermissionSeeder::class);

        $u = User::factory()->create();
        $u->assignRole('admin');
        $u->givePermissionTo('events.update');

        return $u->fresh();
    }

    public function test_a_new_category_gets_a_readable_slug(): void
    {
        $slug = Category::makeSlug('Bridal Shower');

        $this->assertSame('bridal-shower', $slug);
        $this->assertDoesNotMatchRegularExpression('/-[A-Za-z0-9]{4}$/', $slug,
            'A slug must not carry a random suffix.');
    }

    /** A real clash counts, so the URL still reads as the thing it names. */
    public function test_a_name_clash_counts_rather_than_randomises(): void
    {
        Category::create(['name' => 'Bridal Shower', 'slug' => 'bridal-shower', 'is_active' => true]);

        $this->assertSame('bridal-shower-2', Category::makeSlug('Bridal Shower'));
    }

    /** The heart of it: editing a category must not move its public URL. */
    public function test_editing_a_category_does_not_change_its_url(): void
    {
        $cat = Category::create([
            'name' => 'Bridal Shower', 'slug' => 'bridal-shower', 'is_active' => true,
        ]);

        $this->actingAs($this->admin())
            ->patch(route('app.admin.categories.update', $cat), [
                'name'              => 'Bridal Shower',
                'short_description' => 'A description edit, nothing more.',
                'is_active'         => 1,
            ])
            ->assertRedirect();

        $cat->refresh();

        // Prove the edit actually landed -- otherwise an unchanged slug would
        // just mean the request was rejected, and the test would pass for the
        // wrong reason (it did, until this assertion was added).
        $this->assertSame('A description edit, nothing more.', $cat->short_description,
            'The edit never reached the controller, so this proves nothing.');

        $this->assertSame('bridal-shower', $cat->slug,
            'A description edit changed the category URL.');
    }

    /** The repair only touches the exact damage, and only when the clean slug is free. */
    public function test_the_repair_restores_a_randomised_slug(): void
    {
        $cat = Category::create(['name' => 'Bridal Shower', 'slug' => 'bridal-shower-9mKC', 'is_active' => true]);
        $keep = Category::create(['name' => 'Wedding', 'slug' => 'wedding-classic', 'is_active' => true]);

        $this->artisan('categories:repair-slugs')->assertSuccessful();

        $this->assertSame('bridal-shower', $cat->fresh()->slug);
        $this->assertSame('wedding-classic', $keep->fresh()->slug,
            'A deliberately chosen slug must be left alone.');
    }

    public function test_the_repair_leaves_a_taken_slug_alone(): void
    {
        Category::create(['name' => 'Bridal Shower', 'slug' => 'bridal-shower', 'is_active' => true]);
        $dup = Category::create(['name' => 'Bridal Shower', 'slug' => 'bridal-shower-9mKC', 'is_active' => true]);

        $this->artisan('categories:repair-slugs')->assertSuccessful();

        $this->assertSame('bridal-shower-9mKC', $dup->fresh()->slug,
            'Two categories share a name — renaming one is a decision, not a repair.');
    }
}
