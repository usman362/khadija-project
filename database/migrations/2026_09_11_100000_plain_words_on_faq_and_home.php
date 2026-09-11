<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Two seeded sentences, corrected in place.
 *
 * OA-133 (Sep 10 review): the FAQ still said "event organizers (clients)"
 * where the platform's words are Client and Professional.
 *
 * The home page said "Every professional is verified & reviewed". Not every
 * professional is verified; a Verified badge follows a license check only
 * (ISSUE-1, OA-136). Reviews do come from completed bookings, so that is what
 * it says now.
 *
 * Only the exact seeded text is touched. Anything an administrator has since
 * rewritten is left alone.
 */
return new class extends Migration
{
    private const FAQ_OLD = 'connects event organizers (clients) with event service professionals';
    private const FAQ_NEW = 'connects Clients with event Professionals';

    private const HOME_OLD = 'Every professional is verified & reviewed';
    private const HOME_NEW = 'Reviews come from real, completed bookings';

    public function up(): void
    {
        $this->swap(self::FAQ_OLD, self::FAQ_NEW, self::HOME_OLD, self::HOME_NEW);
    }

    public function down(): void
    {
        $this->swap(self::FAQ_NEW, self::FAQ_OLD, self::HOME_NEW, self::HOME_OLD);
    }

    private function swap(string $faqFrom, string $faqTo, string $homeFrom, string $homeTo): void
    {
        if (Schema::hasTable('faqs')) {
            DB::table('faqs')->where('answer', 'like', '%' . $faqFrom . '%')->get(['id', 'answer'])
                ->each(fn ($row) => DB::table('faqs')->where('id', $row->id)
                    ->update(['answer' => str_replace($faqFrom, $faqTo, $row->answer)]));
        }

        if (Schema::hasTable('page_sections')) {
            $row = DB::table('page_sections')->where('page', 'landing')->where('key', 'why_choose')->first();

            if ($row && $row->payload) {
                $payload = json_decode($row->payload, true);
                $changed = false;

                foreach ($payload['items'] ?? [] as $i => $item) {
                    if (($item['text'] ?? null) === $homeFrom) {
                        $payload['items'][$i]['text'] = $homeTo;
                        $changed = true;
                    }
                }

                if ($changed) {
                    DB::table('page_sections')->where('id', $row->id)
                        ->update(['payload' => json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)]);
                }
            }
        }
    }
};
