<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Ali, 13 November: "deep QA karo, mujhe kuch bhi repeat na karna pare."
 *
 * These are the faults that pass actually found, written so they cannot come
 * back quietly. They read the views rather than the rendered pages, because
 * the faults are in the markup and a view with no seeded rows renders nothing
 * to assert on.
 *
 * What the pass found, in the order it found it:
 *
 *   1. Three pages drew a three-dot "more actions" control and made it an
 *      <a> pointing at the same place as the button beside it. It looked
 *      like a menu and behaved as a redirect, which is what Ali reported.
 *      Priority Actions, Gig Operations Hub (overview), Gig Hub (gigs).
 *   2. Proposals & Bids built its own menu with an absolutely positioned
 *      popup inside .pp-tablewrap, which scrolls on both axes. It opened and
 *      was cut off, so on the lower rows it looked dead.
 *   3. The Messages header carried a telephone, a video camera and a kebab
 *      as bare <span>s with nothing behind any of them.
 *   4. Five pages never set a banner title, so the blue bar greeted the
 *      professional by name on Calendar, Team, Messages, Reports and Reviews
 *      instead of saying which page they were on.
 *   5. Four tables outgrew the window and took the whole page into a sideways
 *      scroll: Leads at 1280, My Clients and Payments & Payouts at 1024,
 *      and the Payments table again at 390 where its colgroup ignored the
 *      instruction to stack.
 */
class TheProfessionalPagesPassADeepUiPassTest extends TestCase
{
    private function views(): array
    {
        return File::allFiles(resource_path('views/professional'));
    }

    public function test_no_three_dot_control_is_a_link_in_disguise(): void
    {
        $offenders = [];

        foreach ($this->views() as $file) {
            $body = $file->getContents();

            // An <a …>⋮</a> or <a …>⋯</a> whose whole content is the glyph.
            if (preg_match('/<a\b[^>]*>\s*(?:&#8942;|&#8943;|⋮|⋯|…)\s*<\/a>/u', $body)) {
                $offenders[] = $file->getRelativePathname();
            }
        }

        $this->assertSame([], $offenders,
            'A three-dot control has to open a menu. These make it a link: ' . implode(', ', $offenders));
    }

    public function test_a_row_menu_uses_the_shared_fixed_position_script(): void
    {
        $offenders = [];

        foreach ($this->views() as $file) {
            $body = $file->getContents();

            if (! Str::contains($body, 'data-row-menu')) {
                continue;
            }

            if (! Str::contains($body, '_row-menu-script')) {
                $offenders[] = $file->getRelativePathname() . ' (no script)';
            }

            if (! Str::contains($body, 'data-row-menu-pop')) {
                $offenders[] = $file->getRelativePathname() . ' (no popup)';
            }
        }

        $this->assertSame([], $offenders,
            'A row menu drawn by hand gets clipped by the card it scrolls in. ' . implode(', ', $offenders));
    }

    public function test_the_messages_header_has_no_controls_that_do_nothing(): void
    {
        $body = File::get(resource_path('views/professional/chat/index.blade.php'));

        preg_match('/<div class="pm-th-actions">(.*?)<\/div>\s*<\/div>/s', $body, $m);
        $this->assertNotEmpty($m, 'The thread header block moved; this test needs to follow it.');

        $this->assertStringNotContainsString('<span class="pm-icon-btn">', $m[1],
            'A <span> styled as a button does nothing when clicked. Wire it up or take it out.');
    }

    /** @dataProvider namedPages */
    public function test_every_professional_page_names_itself_in_the_banner(string $view): void
    {
        $body = File::get(resource_path("views/professional/{$view}/index.blade.php"));

        $this->assertStringContainsString("@section('page-title'", $body,
            "The blue banner falls back to the dashboard's greeting, so {$view} would say "
            . '"Welcome back" instead of its own name.');
    }

    public static function namedPages(): array
    {
        return array_map(fn ($v) => [$v], [
            'calendar', 'chat', 'clients', 'earnings', 'leads', 'packages',
            'priority', 'proposals', 'reports', 'reviews', 'services', 'team',
        ]);
    }

    /** @dataProvider wideTables */
    public function test_a_table_too_wide_for_the_window_scrolls_inside_its_card(string $view, string $table): void
    {
        $body = File::get(resource_path("views/professional/{$view}/index.blade.php"));

        $this->assertMatchesRegularExpression(
            '/\.' . preg_quote($table, '/') . '\s*\{[^}]*overflow-x\s*:\s*auto/',
            $body,
            "Without a scroller of its own this table takes the whole page sideways."
        );
        $this->assertStringContainsString("<div class=\"{$table}\">", $body);
    }

    public static function wideTables(): array
    {
        return [
            'Leads CRM'          => ['leads', 'lc-tablewrap'],
            'My Clients'         => ['clients', 'mc-tablewrap'],
            'Payments & Payouts' => ['earnings', 'pm-tablewrap'],
        ];
    }

    public function test_the_payments_table_stops_obeying_its_colgroup_once_the_rows_stack(): void
    {
        $body = File::get(resource_path('views/professional/earnings/index.blade.php'));

        $this->assertMatchesRegularExpression(
            '/@media \(max-width: 900px\)\s*\{.*?\.pm-table colgroup \{ display:none; \}/s',
            $body,
            'Nine fixed column widths held the table at 897px on a 390px phone.'
        );
    }
}
