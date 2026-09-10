<?php

use App\Models\PageSection;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * The one piece of page copy that lives in the database carried a " — ".
 *
 * Ali, 2026-09-10: remove the spaced dash from all copy. The code and the views
 * were done in one pass, but the home page's "levels of assistance" line is a
 * page section — seeded into the database, editable from the admin — so the
 * site kept showing the dash however clean the code was.
 *
 * Only a row still holding the seeded wording is changed. If someone has edited
 * it from the admin since, that is their text and it is left alone.
 */
return new class extends Migration
{
    private const OLD = "You're in control. Each level unlocks more capability — from fully manual to fully automated.";
    private const NEW = "You're in control. Each level unlocks more capability, from fully manual to fully automated.";

    public function up(): void
    {
        DB::table('page_sections')->where('subheading', self::OLD)->update(['subheading' => self::NEW]);

        // Page sections are cached; without this the site keeps the old line.
        PageSection::forgetCache();
    }

    public function down(): void
    {
        DB::table('page_sections')->where('subheading', self::NEW)->update(['subheading' => self::OLD]);
        PageSection::forgetCache();
    }
};
