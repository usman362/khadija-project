<?php

namespace App\Http\Controllers\Auth;

use App\Domain\Auth\Enums\RoleName;
use App\Http\Controllers\Controller;
use App\Rules\Recaptcha;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class LoginController extends Controller
{
    use AuthenticatesUsers;

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

    /**
     * Route affiliate-only accounts to the right place after login:
     * approved → portal, otherwise → application status page.
     */
    protected function authenticated(Request $request, $user): ?RedirectResponse
    {
        $isStandardUser = $user->hasAnyRole([
            RoleName::ADMIN->value,
            RoleName::CLIENT->value,
            RoleName::SUPPLIER->value,
        ]);

        if (! $isStandardUser && $user->influencer) {
            if ($user->influencer->isApproved() && $user->hasRole(RoleName::INFLUENCER->value)) {
                return redirect()->route('influencer.dashboard');
            }

            return redirect()->route('influencer.status');
        }

        // Honor the login page the user came through. A dual-role user who signs
        // in via /professional/login must land in the PROFESSIONAL portal (blue)
        // without having to switch out of client mode — and vice-versa. The
        // login form posts a hidden `login_role` for this.
        $intended = (string) $request->input('login_role', '');

        if ($intended === RoleName::SUPPLIER->value && $user->hasRole(RoleName::SUPPLIER->value)) {
            session(['active_role' => RoleName::SUPPLIER->value]);
            return redirect()->intended(route('professional.dashboard'));
        }

        if ($intended === RoleName::CLIENT->value && $user->hasRole(RoleName::CLIENT->value)) {
            session(['active_role' => RoleName::CLIENT->value]);
            return redirect()->intended(route('client.dashboard'));
        }

        // No explicit intent (generic /login): land each user in their own
        // portal, resetting any stale active-role. Client role wins for
        // dual-role users; supplier-only users go professional.
        if ($user->hasRole(RoleName::CLIENT->value)) {
            session(['active_role' => RoleName::CLIENT->value]);
            return redirect()->intended(route('client.dashboard'));
        }

        if ($user->hasRole(RoleName::SUPPLIER->value)) {
            session(['active_role' => RoleName::SUPPLIER->value]);
            return redirect()->intended(route('professional.dashboard'));
        }

        return null; // admin / others → default dashboard
    }
}
