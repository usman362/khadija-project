<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

/**
 * The admin portal answers at /admin, not /app.
 *
 * Ali, 27 October: "admin portal me prefix check kro sahi se admin/ hona
 * chiaye." /app/users and /app/admin/categories were the admin dashboard
 * wearing the name of the source directory. That is not only untidy: a deny
 * rule written in .htaccess for the app/ SOURCE directory matched those URLs
 * on 20 October and answered 403 to 135 admin pages on the live site.
 *
 * Two things this checks that a glance at routes/web.php cannot. First, that
 * nothing admin-gated was left behind, which is how one stray activity-logs
 * route was found. Second, and more important, that nothing was dragged along
 * that is NOT admin: /app/agreements, /app/bookings, /app/chat, /app/events,
 * /app/membership-plans and /app/payments are permission-gated, and clients
 * and professionals hold those permissions. Filing them under /admin would
 * have replaced one wrong name with another.
 */
class TheAdminPortalLivesUnderAdminTest extends TestCase
{
    use RefreshDatabase;

    /** Every route whose middleware demands the admin role. */
    private function adminRoutes(): array
    {
        return array_values(array_filter(
            Route::getRoutes()->getRoutes(),
            fn ($r) => in_array('role:admin', $r->gatherMiddleware(), true),
        ));
    }

    public function test_every_admin_route_answers_under_admin(): void
    {
        $strays = [];

        foreach ($this->adminRoutes() as $route) {
            $uri = $route->uri();

            if ($uri !== 'admin' && ! str_starts_with($uri, 'admin/')) {
                $strays[] = $uri . ' (' . ($route->getName() ?? 'unnamed') . ')';
            }
        }

        $this->assertSame([], $strays, "Admin routes still outside /admin:\n" . implode("\n", $strays));
    }

    public function test_no_admin_page_is_filed_under_the_source_directory_name(): void
    {
        foreach (Route::getRoutes()->getRoutes() as $route) {
            $this->assertStringStartsNotWith(
                'app/admin',
                $route->uri(),
                'An admin page is still under /app.',
            );
        }
    }

    /**
     * What is still under /app is not admin, and must not be moved.
     *
     * These are permission-gated, and PermissionSeeder gives those permissions
     * to client and professional as well as admin. The assertion is written
     * against the middleware rather than a list of paths so that a route added
     * under /app with role:admin fails here rather than passing quietly.
     */
    public function test_what_is_left_under_app_is_reachable_by_more_than_admins(): void
    {
        $left = array_filter(
            Route::getRoutes()->getRoutes(),
            fn ($r) => str_starts_with($r->uri(), 'app/') && $r->getName() !== 'app.moved-to-admin',
        );

        $this->assertNotEmpty($left, 'Nothing is left under /app, so this test is no longer measuring anything.');

        foreach ($left as $route) {
            $this->assertNotContains(
                'role:admin',
                $route->gatherMiddleware(),
                "{$route->uri()} is admin-only and should have moved to /admin.",
            );
        }
    }

    /** The names did not move, which is why nothing in the views needed editing. */
    public function test_the_route_names_did_not_change(): void
    {
        foreach ([
            'app.admin.categories.index' => 'admin/categories',
            'app.admin.disputes.index'   => 'admin/disputes',
            'app.users.index'            => 'admin/users',
            'app.roles.index'            => 'admin/roles',
            'app.permissions.index'      => 'admin/permissions',
            'app.influencers.index'      => 'admin/influencers',
            'app.ai-tools-admin.index'   => 'admin/ai-tools',
            'app.admin.activity-logs.index' => 'admin/activity-logs',
        ] as $name => $uri) {
            $route = Route::getRoutes()->getByName($name);

            $this->assertNotNull($route, "The route named {$name} is gone.");
            $this->assertSame($uri, $route->uri(), "{$name} is not at {$uri}.");
        }
    }

    // ── Nobody's bookmark breaks ─────────────────────────────

    public static function oldAddresses(): array
    {
        return [
            'users'        => ['/app/users', '/admin/users'],
            'roles'        => ['/app/roles', '/admin/roles'],
            'permissions'  => ['/app/permissions', '/admin/permissions'],
            'influencers'  => ['/app/influencers', '/admin/influencers'],
            'ai tools'     => ['/app/ai-tools', '/admin/ai-tools'],
            'a sub page'   => ['/app/admin/categories', '/admin/categories'],
            'deeper still' => ['/app/admin/blog/posts', '/admin/blog/posts'],
            'the root'     => ['/app/admin', '/admin'],
        ];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('oldAddresses')]
    public function test_an_old_address_lands_on_the_new_one(string $old, string $new): void
    {
        $this->get($old)->assertRedirect($new);
    }

    /**
     * And it is a temporary redirect.
     *
     * A 301 is cached by the browser for as long as it likes, so it would
     * outlive a change of mind about the rename.
     */
    public function test_the_redirect_is_not_permanent(): void
    {
        $this->get('/app/users')->assertStatus(302);
    }

    /**
     * A page that stayed under /app is not swept into the redirect.
     *
     * The catch-all is constrained to the prefixes that moved. Without that
     * constraint it would sit in front of every remaining /app route.
     */
    public function test_a_page_that_stayed_is_not_redirected(): void
    {
        foreach (['/app/agreements', '/app/bookings', '/app/chat', '/app/events', '/app/membership-plans'] as $stayed) {
            $response = $this->get($stayed);

            // Logged out, so each bounces to login. What matters is that none
            // of them bounces to /admin.
            $this->assertNotSame(
                url('/admin' . substr($stayed, 4)),
                $response->headers->get('Location'),
                "{$stayed} was redirected into /admin.",
            );
        }
    }

    /** The admin sign-in door is still its own route and was not shadowed. */
    public function test_the_admin_login_page_still_answers(): void
    {
        $this->get('/admin/login')->assertSuccessful();
    }
}
