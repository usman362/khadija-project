<?php

namespace App\Http\Controllers\Auth;

use App\Domain\Auth\Enums\RoleName;
use App\Http\Controllers\Controller;
use App\Rules\Recaptcha;
use App\Support\LoginLockout;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class LoginController extends Controller
{
    // The throttle hooks below replace the trait's, so the one piece of it
    // still wanted — the ordinary "those credentials don't match" response —
    // is aliased through. `parent::` cannot reach a trait method: the trait is
    // flattened into THIS class, and Controller::__call turns the miss into a
    // BadMethodCallException at runtime rather than a compile error.
    use AuthenticatesUsers {
        sendFailedLoginResponse as protected baseSendFailedLoginResponse;
    }

    /**
     * Where to redirect users after login.
     *
     * @var string
     */
    protected $redirectTo = '/dashboard';

    /**
     * Create a new controller instance.
     */
    public function __construct()
    {
        $this->middleware('guest')->except('logout');
        $this->middleware('auth')->only('logout');
    }

    /**
     * Validate the user login request — includes reCAPTCHA when enabled.
     */
    protected function validateLogin(Request $request): void
    {
        $rules = [
            $this->username() => 'required|string',
            'password' => 'required|string',
        ];

        // Add reCAPTCHA rule if enabled
        $rules['g-recaptcha-response'] = [new Recaptcha('login')];

        $request->validate($rules);
    }

    /*
    |--------------------------------------------------------------------------
    | Rule R56 — 3 attempts per rolling 24 hours, then locked until reset
    |--------------------------------------------------------------------------
    |
    | Laravel's own throttle is 5 tries with a one-minute cooldown that lifts
    | itself. R56 wants both numbers changed AND the cooldown removed, so the
    | count is retuned here and the lock itself lives on the user record — see
    | App\Support\LoginLockout for why it cannot be a rate limiter alone.
    |
    */

    /** R56: three, not Laravel's five. */
    public function maxAttempts(): int
    {
        return LoginLockout::MAX_ATTEMPTS;
    }

    /** R56: a rolling 24 hours, not Laravel's one minute. */
    public function decayMinutes(): int
    {
        return LoginLockout::WINDOW_HOURS * 60;
    }

    /**
     * A locked account is refused before the password is even checked — the
     * lock is the answer, whether or not they have since remembered it.
     */
    protected function hasTooManyLoginAttempts(Request $request): bool
    {
        $email = $request->input($this->username());

        return LoginLockout::isLocked($email) || LoginLockout::remaining($email) === 0;
    }

    /** Count the failure, and lock on the third. */
    protected function incrementLoginAttempts(Request $request): void
    {
        LoginLockout::recordFailure($request->input($this->username()));
    }

    /** Signing in clears the counter. It never clears a lock. */
    protected function clearLoginAttempts(Request $request): void
    {
        LoginLockout::clearCounter($request->input($this->username()));
    }

    /**
     * The failure that locks the account says so, rather than repeating
     * "wrong password" and leaving them to discover it on the next try.
     */
    protected function sendFailedLoginResponse(Request $request)
    {
        if (LoginLockout::isLocked($request->input($this->username()))) {
            return $this->sendLockoutResponse($request);
        }

        return $this->baseSendFailedLoginResponse($request);
    }

    /**
     * What a locked-out person is told.
     *
     * R26 forbids "Blocked" / "Rejected" / "Unsupported", and there is a
     * practical reason beyond tone: the only way out is a password reset, so
     * the message has to say that or the account is a dead end.
     */
    protected function sendLockoutResponse(Request $request)
    {
        throw ValidationException::withMessages([
            $this->username() => [
                'For your security this account is paused after 3 incorrect passwords. '
                . 'Reset your password to sign in again.',
            ],
        ])->status(423);
    }

    /**
     * Route affiliate-only accounts to the right place after login:
     * approved → portal, otherwise → application status page.
     */
    protected function authenticated(Request $request, $user): ?RedirectResponse
    {
        $isStandardUser = $user->hasAnyRole([
            RoleName::ADMIN->value,
            RoleName::CLIENT->value,
            RoleName::PROFESSIONAL->value,
        ]);

        if (! $isStandardUser && $user->influencer) {
            if ($user->influencer->isApproved() && $user->hasRole(RoleName::INFLUENCER->value)) {
                return redirect()->route('influencer.dashboard');
            }

            return redirect()->route('influencer.status');
        }

        // Always land the user in their PRIMARY role — the account type they
        // registered as, unaffected by any client/professional switch. So a
        // professional who switched to client and logged out still logs back in
        // as a professional, and a client stays a client, no matter which login
        // page they use. The active-role session is reset to the primary here.
        $landRole = $this->primaryRoleFor($user);

        if ($landRole === RoleName::PROFESSIONAL->value && $user->hasRole(RoleName::PROFESSIONAL->value)) {
            session(['active_role' => RoleName::PROFESSIONAL->value]);
            return redirect()->intended(route('professional.dashboard'));
        }

        if ($landRole === RoleName::CLIENT->value && $user->hasRole(RoleName::CLIENT->value)) {
            session(['active_role' => RoleName::CLIENT->value]);
            return redirect()->intended(route('client.dashboard'));
        }

        return null; // admin / others → default dashboard
    }

    /**
     * The user's home role: the stored primary_role when present, otherwise a
     * sensible fallback from their assigned roles (supplier-first for dual).
     */
    private function primaryRoleFor($user): string
    {
        $primary = $user->primary_role;
        if (in_array($primary, [RoleName::PROFESSIONAL->value, RoleName::CLIENT->value], true) && $user->hasRole($primary)) {
            return $primary;
        }

        // No stored primary_role: prefer CLIENT for a dual-role account so a client
        // never lands in the professional portal. A pure professional (no client
        // role) still resolves to supplier.
        return $user->hasRole(RoleName::CLIENT->value) ? RoleName::CLIENT->value : RoleName::PROFESSIONAL->value;
    }
}
