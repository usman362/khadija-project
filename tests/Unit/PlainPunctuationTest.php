<?php

namespace Tests\Unit;

use App\Support\PlainPunctuation as P;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class PlainPunctuationTest extends TestCase
{
    /** @return array<string, array{string, string}> */
    public static function copy(): array
    {
        return [
            'new clause'          => ['Post one service or several — professionals bid on what they provide.', 'Post one service or several. Professionals bid on what they provide.'],
            'imperative'          => ['Clients no longer see you as insured — upload the renewed certificate below.', 'Clients no longer see you as insured. Upload the renewed certificate below.'],
            'linker'              => ['Try widening your search — or post your event.', 'Try widening your search, or post your event.'],
            'explanation'         => ['Your account is locked — no further actions possible.', 'Your account is locked: no further actions possible.'],
            'heading'             => ['Post an Event — Review & Search', 'Post an Event: Review & Search'],
            'value'               => ['+3 days — $1.99', '+3 days: $1.99'],
            'title'               => ['Compare Packages — GigResource', 'Compare Packages | GigResource'],
            'pair'                => ['The work — whatever it is — stays yours.', 'The work, whatever it is, stays yours.'],
            'placeholder'         => ['— Choose your event —', 'Choose your event'],
            'after punctuation'   => ['Done. — see you soon', 'Done. see you soon'],
            'range untouched'     => ['12:00 PM – 4:31 PM', '12:00 PM – 4:31 PM'],
            'all carries on'      => ['Organize communications — all in one dashboard.', 'Organize communications, all in one dashboard.'],
            'nothing to do'       => ['Plain sentence.', 'Plain sentence.'],
        ];
    }

    #[DataProvider('copy')]
    public function test_copy(string $in, string $out): void
    {
        $this->assertSame($out, P::text($in));
    }

    public function test_a_models_unspaced_dash_is_opened_up_and_fixed(): void
    {
        $this->assertSame('We handle the ceremony, then dinner.', P::model('We handle the ceremony—then dinner.'));
        // A range written with an en dash is not a dash to remove.
        $this->assertSame('Open 8am–6pm.', P::model('Open 8am–6pm.'));
    }

    public function test_model_html_changes_text_and_not_markup(): void
    {
        $html = '<p data-note="a—b">Terms apply — see <b>section 4</b>.</p>';

        $this->assertSame('<p data-note="a—b">Terms apply. See <b>section 4</b>.</p>', P::modelHtml($html));
    }

    public function test_model_deep_reaches_every_string_and_keeps_keys(): void
    {
        $in  = ['title—x' => 'Great — would recommend.', 'list' => ['Food — the big one', 3]];
        $out = P::modelDeep($in);

        $this->assertSame(['title—x', 'list'], array_keys($out));
        $this->assertSame('Great, would recommend.', $out['title—x']);
        $this->assertSame('Food: the big one', $out['list'][0]);
        $this->assertSame(3, $out['list'][1]);
    }

    public function test_no_output_ever_keeps_a_spaced_dash(): void
    {
        foreach (self::copy() as [$in]) {
            $this->assertStringNotContainsString(' — ', P::text($in), $in);
        }
    }
}
