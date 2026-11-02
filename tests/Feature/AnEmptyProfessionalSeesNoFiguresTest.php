<?php

namespace Tests\Feature;

use App\Models\User;
use App\Support\ServiceArea;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * A measurement, not a grep.
 *
 * Asked whether the professional pages are all driven by real records. Twice
 * before I answered that from a pattern search and was wrong, because
 * `$x = [` finds SVG coordinates as happily as it finds invented rows.
 *
 * So this asks the application instead: sign in as a professional who has
 * nothing at all, open every page, and see which ones still print money, a
 * percentage or somebody's name. Anything a brand new account is shown was
 * typed into the page.
 */
class AnEmptyProfessionalSeesNoFiguresTest extends TestCase
{
    use RefreshDatabase;

    /** Pages that take no parameters. */
    private const PAGES = [
        'professional.dashboard', 'professional.priority.index',
        'professional.bid-intelligence.index', 'professional.bidding-board.index',
        'professional.bidding-board.my-bids', 'professional.calendar.index',
        'professional.clients.index', 'professional.gig-hub.index',
        'professional.leads.index', 'professional.chat.index',
        'professional.packages.index', 'professional.packages.create',
        'professional.proposals.index', 'professional.reviews.index',
        'professional.reports.index', 'professional.earnings.index',
        'professional.transactions.index', 'professional.team.index',
        'professional.notifications.index', 'professional.profile.index',
        'professional.agreements.index',
    ];

    /**
     * Nothing is allowed to print a figure to an account with no records.
     *
     * Priority Actions used to be the exception: an explainer whose panels
     * carried fixed values under headings reading "Example:". Honest as far
     * as it went, but a date, a budget and a guest count sat there looking
     * like every other date, budget and guest count on this side. They name
     * the field now instead of showing a value for it, so there is no
     * exception left to make.
     */
    private const EXPLAINERS = [];

    public function test_no_page_shows_a_figure_to_an_account_with_no_records(): void
    {
        $this->seed(\Database\Seeders\PermissionSeeder::class);
        $this->seed(\Database\Seeders\RolePermissionSeeder::class);

        $pro = User::factory()->create(['primary_role' => 'professional', 'name' => 'Hollis Tremaine']);
        $pro->assignRole('professional');
        $pro->getOrCreateProfile()->update([
            'country' => 'US', 'state' => 'MD', 'city' => 'Baltimore',
            'service_area_status' => ServiceArea::SUPPORTED,
        ]);
        $pro = User::findOrFail($pro->id);

        $offenders = [];

        foreach (self::PAGES as $name) {
            if (in_array($name, self::EXPLAINERS, true)) {
                continue;
            }

            $body = $this->actingAs($pro)->get(route($name))->getContent();

            // Visible text only: a percentage inside a conic-gradient is not
            // a figure anybody reads.
            $text = preg_replace('/<(script|style)\b[^>]*>.*?<\/\1>/su', ' ', $body);
            $text = preg_replace('/<[^>]+>/u', ' ', $text);
            $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5);

            preg_match_all('/\$[0-9][0-9,]{2,}(?:\.[0-9]{2})?/u', $text, $money);
            preg_match_all('/\b[0-9]{1,3}%/u', $text, $pct);

            // A zero is not a claim, and neither is a rate the site charges.
            $money = array_diff($money[0], ['$0', '$0.00']);
            $pct   = array_diff($pct[0], ['0%', '100%', $this->commissionRate($pro)]);

            /*
             * Invoice #INV-2026-1048 slipped past the first version of this,
             * because a reference number carries neither a currency symbol
             * nor a percent sign. So anything shaped like a record's own
             * identifier counts too.
             */
            preg_match_all('/#[A-Z]{2,}-[0-9][0-9-]*/u', $text, $refs);

            /*
             * A clock time. The review page printed a literal "12:00 AM"
             * beside a real date, so the line was half somebody's event and
             * half nothing.
             *
             * Dates are deliberately NOT checked here. Today's date is in the
             * banner on every page and the calendar shows this week, so the
             * check would be all false alarms. An invented date carries an
             * invented job with it, and the money, name and reference checks
             * catch that.
             */
            preg_match_all('/\b[0-9]{1,2}:[0-9]{2}\s?(?:AM|PM)\b/u', $text, $times);

            $found = array_values(array_unique(
                array_merge($money, $pct, $refs[0], $times[0])
            ));

            if ($found) {
                $offenders[] = str_replace('professional.', '', $name) . ': ' . implode(' ', $found);
            }
        }

        $this->assertSame(
            [],
            $offenders,
            "A professional with no records at all was shown figures:\n  " . implode("\n  ", $offenders)
        );
    }

    /** The commission the site charges this account, which is a real rate. */
    private function commissionRate(User $pro): string
    {
        $rate = \App\Support\Commission::rateFor($pro);

        return rtrim(rtrim(number_format($rate, 2), '0'), '.') . '%';
    }
}
