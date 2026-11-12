<?php

namespace App\Domain\Taxonomy;

use App\Models\Category;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Sir Peter's Master Service Classification, 8 November 2026.
 *
 * His list is a faithful export of this tree: all 241 service titles match
 * ours exactly and every one sits under the category it already sat under.
 * What it adds is the answer to a question nobody had asked the data before,
 * which is who may offer a service, and fifteen services for a side of the
 * site that had none.
 *
 *   213 professional only, which is every row's default
 *    28 either
 *    15 influencer only, under a new category
 *
 * This lives in a class rather than only in its migration because two things
 * have to apply it and the order they run in is not fixed. On an existing
 * database the migration finds the 241 and marks them. On a fresh one the
 * migration runs FIRST and finds nothing, and `taxonomy:import-v2` creates
 * the tree afterwards — so the import calls this too, and a database built
 * either way ends up the same.
 *
 * Idempotent by construction: it marks by name and creates only what is
 * missing.
 */
final class ServiceEligibility
{
    public const CATEGORY = 'Influencer Marketing & Promotion';

    /** The 28 his classification marks as either. */
    public const BOTH = [
        'Brand Ambassadors',
        'Branded Swag & Merchandise',
        'Caricature Artists',
        'Comedians',
        'Corporate Event Management',
        'Costumed Characters',
        'Cultural & Folk Musicians',
        'Dancers & Dance Troupes',
        'Drone Photography',
        'Engagement & Pre-Event Shoots',
        'Event Photography',
        'Fashion & Costume Design',
        'Food Truck Booking',
        'Karaoke Hosting',
        'Keynote & Guest Speakers',
        'Live Streaming Services',
        'Livestream Production',
        'MC & Emcee Services',
        'Magicians',
        'Master of Ceremonies (MC)',
        'Party & Club DJs',
        'Product Demo Staffing',
        'Roaming Photographers with Instant Prints',
        'Same-Day Edit Videography',
        'Solo Musicians & Acoustic Acts',
        'Trivia Hosts',
        'Videography & Cinematic Films',
        'Wedding Photography',
    ];

    /**
     * The 15 he proposes.
     *
     * He called them "proposals for approval", so they are created INACTIVE:
     * present in the tree and countable, and nobody can pick them until
     * somebody turns them on.
     */
    public const PROPOSED = [
        'Social Media Promotion',
        'Sponsored Social Media Posts',
        'Brand Ambassador Campaigns',
        'Event Promotion & Advertising',
        'Product Reviews & Demonstrations',
        'Influencer Marketing Campaigns',
        'Social Media Content Creation',
        'Livestream Hosting & Promotion',
        'Promotional Event Hosting',
        'Brand Photography & Promotional Videos',
        'Social Media Takeovers',
        'Promotional Event Appearances',
        'User-Generated Content (UGC)',
        'Product Launch Promotion',
        'Influencer Meet & Greets',
    ];

    public static function apply(string $version = 'v2'): void
    {
        DB::table('categories')
            ->where('kind', Category::SERVICE)
            ->where('taxonomy_version', $version)
            ->whereIn('name', self::BOTH)
            ->update(['eligibility' => Category::ELIGIBLE_BOTH]);

        $categoryId = self::category($version);

        foreach (self::PROPOSED as $i => $name) {
            $exists = DB::table('categories')
                ->where('kind', Category::SERVICE)
                ->where('taxonomy_version', $version)
                ->where('name', $name)
                ->exists();

            if ($exists) {
                continue;
            }

            DB::table('categories')->insert([
                'name' => $name,
                'slug' => Str::slug(self::CATEGORY . ' ' . $name),
                'parent_id' => $categoryId,
                'kind' => Category::SERVICE,
                'eligibility' => Category::ELIGIBLE_INFLUENCER,
                'taxonomy_version' => $version,
                'is_active' => false,
                'sort_order' => $i + 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    /** The 28th service category, made once. */
    private static function category(string $version): int
    {
        $existing = DB::table('categories')
            ->where('kind', Category::SERVICE_CATEGORY)
            ->where('taxonomy_version', $version)
            ->where('name', self::CATEGORY)
            ->first();

        if ($existing) {
            DB::table('categories')->where('id', $existing->id)
                ->update(['eligibility' => Category::ELIGIBLE_INFLUENCER]);

            return $existing->id;
        }

        // Inactive, like everything in it.
        //
        // The category holds fifteen proposals and nothing else, and a client
        // picking services for a wedding was being offered "Influencer
        // Marketing & Promotion" with nothing behind it. It switches on when
        // the proposals are approved and influencers can actually be hired,
        // which are the same moment.

        $sort = (int) DB::table('categories')
            ->where('kind', Category::SERVICE_CATEGORY)
            ->where('taxonomy_version', $version)
            ->max('sort_order');

        return DB::table('categories')->insertGetId([
            'name' => self::CATEGORY,
            'slug' => Str::slug(self::CATEGORY),
            'parent_id' => null,
            'kind' => Category::SERVICE_CATEGORY,
            'eligibility' => Category::ELIGIBLE_INFLUENCER,
            'taxonomy_version' => $version,
            'is_active' => false,
            'sort_order' => $sort + 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /** Undo, for the migration's down(). */
    public static function remove(): void
    {
        $category = DB::table('categories')
            ->where('kind', Category::SERVICE_CATEGORY)
            ->where('name', self::CATEGORY)
            ->first();

        if ($category) {
            DB::table('categories')->where('parent_id', $category->id)->delete();
            DB::table('categories')->where('id', $category->id)->delete();
        }

        DB::table('categories')
            ->whereIn('name', self::BOTH)
            ->update(['eligibility' => Category::ELIGIBLE_PROFESSIONAL]);
    }
}
