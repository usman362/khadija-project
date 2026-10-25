<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Password;
use Tests\TestCase;

/**
 * Every password box can be looked at.
 *
 * Asked on 25 October with the sign-in screen attached: put the eye icon in for
 * show password. The button was already on that screen, and on register, and
 * it was invisible on both.
 *
 * Measuring the live page said why rather than leaving it to guesswork. The
 * <svg> inside the button carried a viewBox and no width or height, and no
 * stylesheet rule sized it, so it rendered at 0 by 0 and the button came out
 * 8 pixels wide: present in the markup, absent on the screen. The lock icon on
 * the left of the same field sets 18px explicitly. That is why the component
 * carries its own size on the element.
 *
 * A grep for a CSS rule said the influencer join form was broken too. Opening
 * it showed width="18" on the attribute, so it was fine all along. A grep is a
 * hypothesis.
 *
 * It was missing outright from the password reset screen, which is the one
 * screen somebody locked out of their account has to get right first time, and
 * where they are typing a password they have just invented and must retype.
 */
class YouCanSeeWhatYouAreTypingTest extends TestCase
{
    use RefreshDatabase;

    /** Every password box on a page, and whether each has an eye for it. */
    private function audit(string $html): array
    {
        preg_match_all('/<input[^>]*type="password"[^>]*>/i', $html, $fields);

        $missing = [];

        foreach ($fields[0] as $field) {
            preg_match('/id="([^"]+)"/', $field, $id);

            if (! $id) {
                $missing[] = 'a field with no id';

                continue;
            }

            if (! str_contains($html, 'data-eye="' . $id[1] . '"')) {
                $missing[] = $id[1];
            }
        }

        return ['fields' => count($fields[0]), 'missing' => $missing];
    }

    public function test_the_sign_in_screen_can_show_the_password(): void
    {
        $audit = $this->audit($this->get(route('login'))->assertSuccessful()->getContent());

        $this->assertSame(1, $audit['fields']);
        $this->assertSame([], $audit['missing']);
    }

    public function test_the_sign_up_screen_can_show_both_passwords(): void
    {
        $audit = $this->audit($this->get(route('register'))->assertSuccessful()->getContent());

        $this->assertSame(2, $audit['fields'], 'Password and confirmation.');
        $this->assertSame([], $audit['missing']);
    }

    /** The screen a locked-out person has to get right first time. */
    public function test_the_password_reset_screen_can_show_the_password(): void
    {
        $user  = User::factory()->create();
        $token = Password::broker()->createToken($user);

        $audit = $this->audit(
            $this->get(route('password.reset', ['token' => $token, 'email' => $user->email]))
                ->assertSuccessful()->getContent(),
        );

        $this->assertSame(2, $audit['fields']);
        $this->assertSame([], $audit['missing']);
    }

    /**
     * The button is sized on the element, not by a page's stylesheet.
     *
     * This is the whole bug, so it is asserted on the markup the component
     * produces rather than on a class name existing somewhere.
     */
    public function test_the_icon_carries_its_own_size(): void
    {
        $html = $this->get(route('login'))->assertSuccessful()->getContent();

        preg_match('/<button[^>]*data-eye[^>]*>(.*?)<\/button>/s', $html, $m);

        $this->assertNotEmpty($m, 'No eye button on the sign-in screen at all.');

        preg_match_all('/<svg[^>]*>/', $m[1], $svgs);

        $this->assertNotEmpty($svgs[0], 'The button has no icon in it.');

        foreach ($svgs[0] as $svg) {
            $this->assertMatchesRegularExpression('/width="\d+"/', $svg, 'An icon with no width renders at zero.');
            $this->assertMatchesRegularExpression('/height="\d+"/', $svg, 'An icon with no height renders at zero.');
        }
    }

    /** And the toggle is bound, once, wherever an eye is rendered. */
    public function test_the_toggle_is_wired_on_every_screen_that_has_one(): void
    {
        foreach ([route('login'), route('register')] as $url) {
            $html = $this->get($url)->assertSuccessful()->getContent();

            $this->assertStringContainsString('data-eye', $html);
            $this->assertStringContainsString(
                "closest('[data-eye]')",
                $html,
                "Nothing listens for the eye on {$url}.",
            );
        }
    }

    /** It starts hidden. An eye that opens shown is not a password field. */
    public function test_the_field_starts_hidden(): void
    {
        $html = $this->get(route('login'))->assertSuccessful()->getContent();

        $this->assertMatchesRegularExpression('/<input[^>]*id="rgLoginPw"[^>]*type="password"|<input[^>]*type="password"[^>]*id="rgLoginPw"/', $html);
        $this->assertStringContainsString('aria-pressed="false"', $html);
    }
}
