<?php

use App\Models\Category;
use Illuminate\Database\Migrations\Migration;

/**
 * Two event types renamed — Sir Peter, 29 Aug.
 *
 *   "Silent Auction"                 -> "Live Auction"
 *   "Retirement & Going-Away Party"  -> "Going-Away Party"
 *
 * His reasons, in his words: an auction is more likely to be live than silent,
 * and the combined title duplicated the separate "Retirement Party" that sits
 * two cards along.
 *
 * A migration rather than a seeder edit, because these rows already exist on
 * the live site — the seeder only helps a fresh install, and it is updated
 * alongside this for that case.
 *
 * THE PICTURE. Sir Peter said the Silent Auction image has to change too, and
 * he is right: the photograph shows a silent-auction bid sheet, which under
 * "Live Auction" would be a picture of the wrong thing. It is cleared here, so
 * the card falls back to its lettered tile until he uploads the new one —
 * the same rule the rest of the site follows, that a placeholder nobody chose
 * is worse than an honest blank. The Going-Away Party picture is kept: he
 * said its image is already right, only the wording was wrong.
 */
return new class extends Migration
{
    /** old slug => [new name, new slug, clear the picture?] */
    private const RENAMES = [
        'silent-auction'                => ['Live Auction', 'live-auction', true],
        'retirement-going-away-party'   => ['Going-Away Party', 'going-away-party', false],
    ];

    public function up(): void
    {
        foreach (self::RENAMES as $oldSlug => [$name, $slug, $clearImage]) {
            $category = Category::withoutGlobalScopes()->where('slug', $oldSlug)->first();

            if (! $category) {
                continue;   // already renamed, or not present in this environment
            }

            $attributes = ['name' => $name, 'slug' => $slug];

            if ($clearImage) {
                $attributes['thumbnail'] = null;
                $attributes['cover_image'] = null;
            }

            $category->forceFill($attributes)->save();
        }
    }

    public function down(): void
    {
        foreach (self::RENAMES as $oldSlug => [$name, $slug]) {
            $category = Category::withoutGlobalScopes()->where('slug', $slug)->first();

            if ($category) {
                // The picture is not restored — it was deleted, not moved.
                $category->forceFill([
                    'name' => $oldSlug === 'silent-auction' ? 'Silent Auction' : 'Retirement & Going-Away Party',
                    'slug' => $oldSlug,
                ])->save();
            }
        }
    }
};
