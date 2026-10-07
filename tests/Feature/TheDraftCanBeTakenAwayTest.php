<?php

namespace Tests\Feature;

use App\Domain\Agreements\Workspace;
use App\Models\Bid;
use App\Models\Category;
use App\Models\Event;
use App\Models\Finalization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Sir Peter asked for a Download Agreement Draft beside the workspace.
 *
 * The signed contract already had a download — app.agreements.download, which
 * refuses anything both sides have not accepted, and should. That is the
 * record of a booking. This is the other thing: what the terms say today, so
 * a client can read them away from the screen or send them to whoever else
 * has to agree.
 *
 * Which makes the wording the dangerous part. A draft that reads like a
 * contract is worse than no draft, because somebody will act on it. So the
 * document says what it is, and an unsettled term is printed as unsettled
 * rather than left blank — a blank line in a document about money reads as
 * nothing owed.
 */
class TheDraftCanBeTakenAwayTest extends TestCase
{
    use RefreshDatabase;

    private User $client;

    private User $pro;

    private Event $event;

    private Finalization $fin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\PermissionSeeder::class);
        $this->seed(\Database\Seeders\RolePermissionSeeder::class);

        $this->client = User::factory()->create(['name' => 'Dana Whitfield']);
        $this->client->assignRole('client');
        $this->client->getOrCreateProfile()->update(['country' => 'US', 'state' => 'MD', 'city' => 'Baltimore']);
        $this->client = $this->client->fresh();

        $this->pro = User::factory()->create(['name' => 'Velvet Beats', 'primary_role' => 'professional']);
        $this->pro->assignRole('professional');

        $svc = Category::create([
            'name' => 'Wedding DJs', 'slug' => Str::slug('Wedding DJs') . '-draft',
            'kind' => Category::SERVICE, 'is_active' => true,
        ]);

        $this->event = Event::create([
            'title' => 'Wedding Reception', 'created_by' => $this->client->id, 'client_id' => $this->client->id,
            'status' => 'published', 'is_published' => true, 'source' => 'direct_offer',
            'starts_at' => now()->addDays(40)->setTime(17, 0),
        ]);
        $this->event->categories()->sync([$svc->id]);

        $bid = Bid::create([
            'event_id' => $this->event->id, 'category_id' => $svc->id, 'supplier_id' => $this->pro->id,
            'amount' => 1400, 'status' => 'submitted', 'available_confirmed' => true,
        ]);

        $this->fin = Finalization::create([
            'event_id' => $this->event->id, 'category_id' => $svc->id, 'bid_id' => $bid->id,
            'client_id' => $this->client->id, 'supplier_id' => $this->pro->id, 'status' => 'open',
        ]);
    }

    private function download(?User $as = null)
    {
        return $this->actingAs($as ?? $this->client)
            ->get(route('client.finalize.draft', $this->fin));
    }

    /** The button is on the workspace, and it leads somewhere. */
    public function test_the_workspace_offers_the_draft(): void
    {
        $html = $this->actingAs($this->client)
            ->get(route('client.finalize.overview', $this->fin))
            ->assertSuccessful()->getContent();

        $this->assertStringContainsString('Download agreement draft', $html);
        $this->assertStringContainsString(route('client.finalize.draft', $this->fin), $html);
    }

    /** It is a PDF, named after the request so a folder of them sorts. */
    public function test_it_downloads_a_pdf_named_after_the_request(): void
    {
        $res = $this->download()->assertSuccessful();

        $res->assertHeader('Content-Type', 'application/pdf');

        $this->assertStringContainsString(
            'filename="' . $this->event->reference() . '-velvet-beats-draft.pdf"',
            $res->headers->get('Content-Disposition')
        );

        $this->assertStringStartsWith('%PDF', $res->getContent());
    }

    /**
     * The whole risk of this feature: a draft that reads like a contract.
     */
    public function test_it_says_it_is_a_draft_and_binds_nobody(): void
    {
        $text = $this->pdfText($this->download()->getContent());

        $this->assertStringContainsString('draft, not a contract', $text);
        $this->assertStringContainsString('binds nobody', $text);

        // And no claim that anybody signed, because nobody has.
        $this->assertStringContainsString('Has not approved this version', $text);
    }

    /** An unsettled term is printed as unsettled, never as a blank. */
    public function test_nothing_settled_yet_is_said_so_rather_than_left_blank(): void
    {
        $text = $this->pdfText($this->download()->getContent());

        $this->assertStringContainsString('No price agreed yet', $text);
        $this->assertStringContainsString('Not settled yet', $text);
        $this->assertStringContainsString('has not been written down yet', $text);
    }

    /** With terms settled, it prints the terms. */
    public function test_a_settled_draft_carries_the_figures(): void
    {
        $this->fin->update([
            'agreed_price' => 1400, 'deposit_percent' => 25, 'deposit_amount' => 350,
            'scope' => 'Five hours of music, including setup.',
            'service_start' => now()->addDays(40)->setTime(17, 0),
            'balance_due_on' => now()->addDays(35),
        ]);

        $text = $this->pdfText($this->download()->getContent());

        $this->assertStringContainsString('1,400.00', $text);
        $this->assertStringContainsString('350.00', $text);
        $this->assertStringContainsString('25%', $text);
        $this->assertStringContainsString('Five hours of music', $text);
        $this->assertStringContainsString($this->event->reference(), $text);
    }

    /** Someone else's agreement is not downloadable. */
    public function test_it_belongs_to_the_client_it_is_about(): void
    {
        $stranger = User::factory()->create();
        $stranger->assignRole('client');

        $this->download($stranger->fresh())->assertForbidden();
    }

    /** Both signed: it stops calling itself a draft. */
    public function test_once_signed_it_is_not_called_a_draft(): void
    {
        $this->fin->update([
            'terms_version' => 1,
            'client_approved_version' => 1, 'supplier_approved_version' => 1,
            'client_signature' => 'Dana Whitfield', 'client_signed_at' => now(), 'client_signed_version' => 1,
            'supplier_signature' => 'Velvet Beats', 'supplier_signed_at' => now(), 'supplier_signed_version' => 1,
        ]);

        $this->assertTrue(Workspace::bothSigned($this->fin->fresh()));

        $text = $this->pdfText($this->download()->getContent());

        $this->assertStringContainsString('Signed by both sides', $text);
        $this->assertStringNotContainsString('binds nobody', $text);
    }

    /**
     * The words as they reach the page.
     *
     * DomPDF writes its text runs as UTF-16BE inside parentheses, so a plain
     * search of the file finds nothing however plainly the sentence is
     * printed — which is exactly the sort of test that passes against an
     * empty string. Decoding is the point.
     */
    private function pdfText(string $pdf): string
    {
        $streams = '';
        if (preg_match_all('/stream\r?\n(.*?)\r?\nendstream/s', $pdf, $m)) {
            foreach ($m[1] as $chunk) {
                $plain = @gzuncompress($chunk);
                $streams .= $plain === false ? $chunk : $plain;
            }
        }

        preg_match_all('/\(((?:\\\\.|[^()\\\\])*)\)/s', $streams, $runs);

        $text = '';
        foreach ($runs[1] as $run) {
            $run = str_replace(['\\(', '\\)', '\\\\'], ['(', ')', '\\'], $run);
            // Every run DomPDF writes here is UTF-16BE, byte-order mark or not.
            $text .= mb_convert_encoding($run, 'UTF-8', 'UTF-16BE');
        }

        return $text;
    }
}
