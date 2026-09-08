<?php

use App\Rules\NotTheHintText;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * OA-131 — the form's own hints, saved as somebody's details.
 *
 * A profile carried "Your Company LLC" as its company name, "e.g. Technology,
 * Healthcare" as its industry and "https://iyourcompany.com" as its website.
 * The form is written correctly — those are placeholder attributes and never
 * submit — so they were typed in, typo and all, and stored as facts about a
 * business.
 *
 * The rule that refuses them on save is the fix. This clears what is already
 * there, because a value nobody entered on purpose is worse than an empty
 * field: it looks like an answer, and a client who saves without editing
 * confirms it.
 *
 * Narrow by design. Only fields that are the hint for that field, and the
 * comparison ignores case, spacing, punctuation and the scheme so a mistyped
 * hint is caught — that is what "iyourcompany" was. Anything a person could
 * plausibly have meant is left alone.
 */
return new class extends Migration
{
    /** column => the placeholder shown on that field. */
    private const HINTS = [
        'website' => 'https://yourwebsite.com',
        'company_name' => 'Your Company LLC',
        'company_website' => 'https://yourcompany.com',
        'industry' => 'e.g. Technology, Healthcare',
    ];

    public function up(): void
    {
        foreach (self::HINTS as $column => $hint) {
            if (! Schema::hasColumn('user_profiles', $column)) {
                continue;
            }

            DB::table('user_profiles')
                ->whereNotNull($column)
                ->orderBy('id')
                ->select(['id', $column])
                ->chunk(200, function ($rows) use ($column, $hint) {
                    foreach ($rows as $row) {
                        $value = (string) $row->{$column};

                        if (NotTheHintText::looksLike($value, $hint)
                            || preg_match('/^\s*e\.?g\.?\b/i', $value)) {
                            DB::table('user_profiles')
                                ->where('id', $row->id)
                                ->update([$column => null]);
                        }
                    }
                });
        }
    }

    /**
     * Not reversible, and there is nothing to restore — these were never
     * anybody's details.
     */
    public function down(): void
    {
        // Intentionally empty.
    }
};
