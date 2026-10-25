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
        $email = $request->input($this->username());

        /*
         * Two different situations reached this one message, and only one of
         * them was true.
         *
         * `hasTooManyLoginAttempts` is also satisfied by the rate limiter
         * alone, and the limiter counts failures against addresses that have
         * no account, deliberately, so that guessing addresses is throttled.
         * So anybody who mistyped their email three times was told their
         * account was paused and to reset their password: there was no account
         * to pause and no password to reset, and the one instruction given was
         * a door that opens onto nothing.
         *
         * Found on 25 October after a day spent unlocking an admin account
         * that did not exist on that server. The screen said it was paused, so
         * we believed it.
         *
         * Deliberately accepted: the two messages differ, so three failures
         * reveal whether an address has an account. That signal costs an
         * attacker three attempts to obtain and tells them only what the
         * lockout behaviour already shows. Telling a real person to reset a
         * password they do not have costs more.
         */
        if (LoginLockout::isLocked($email)) {
            throw ValidationException::withMessages([
                $this->username() => [
                    'For your security this account is paused after ' . LoginLockout::MAX_ATTEMPTS
                    . ' incorrect passwords. Reset your password to sign in again.',
                ],
            ])->status(423);
        }

        throw ValidationException::withMessages([
            $this->username() => [
                'Too many sign-in attempts from this address. Wait a little and try again, '
                . 'and check the email address itself as well as the password.',
            ],
        ])->status(429);
    }

    /**
     * Route affiliate-only accounts to the right place after login:
     * approved → portal, otherwise → application status page.
     */
    /**
     * An administrator signs in from the admin portal, and nowhere else.
     *
     * Reported 25 October: "yeh simple login wale page se bhi admin me login
     * ho raha hai, admin sirf admin page se hi hona chahiye."
     *
     * Both forms post to this one route, so until now nothing could tell them
     * apart. The admin form carries a hidden field saying which door was used;
     * the ordinary form does not and cannot be made to, because a field a
     * browser sends is a field anybody can send.
     *
     * The check runs AFTER the password has been accepted, deliberately. Doing
     * it before would answer "is this address an administrator?" to anybody who
     * typed it, which is a question the sign-in page should not answer. After a
     * correct password the person already holds the account, so being told
     * where to sign in costs nothing.
     *
     * The session is thrown away rather than kept: a half-signed-in
     * administrator on the client side is the thing being prevented.
     */
    private function wrongDoor(Request $request, $user): ?RedirectResponse
    {
        $throughAdminPortal = $request->input('portal') === 'admin';
        $isAdmin            = $user->hasRole(RoleName::ADMIN->value);

        if ($isAdmin && ! $throughAdminPortal) {
            $this->bounce($request);

            return redirect()->route('admin.login')
                ->with('status', 'Administrators sign in from the admin portal.');
        }

        // The admin page says "Not an admin? Go to user login", so it holds
        // itself to that: it is not a second front door for everybody else.
        if (! $isAdmin && $throughAdminPortal) {
            $this->bounce($request);

            return redirect()->route('login')
                ->with('status', 'This is the admin portal. Sign in here instead.');
        }

        return null;
    }

    /** Undo the sign-in that has just happened. */
    private function bounce(Request $request): void
    {
        $this->guard()->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
    }

    protected function authenticated(Request $request, $user): ?RedirectResponse
    {
        if ($elsewhere = $this->wrongDoor($request, $user)) {
            return $elsewhere;
        }

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
