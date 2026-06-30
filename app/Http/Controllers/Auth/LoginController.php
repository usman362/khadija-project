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

        // Land each user in THEIR OWN portal and reset the active-role session,
        // so a stale "professional mode" from a previous session can't carry
        // over and bounce a client into the professional portal. A user who
        // holds the client role logs in as a client (they can still switch to
        // professional via the toggle); supplier-only users go professional.
        if ($user->hasRole(RoleName::CLIENT->value)) {
            session(['active_role' => RoleName::CLIENT->value]);
            return redirect()->route('client.dashboard');
        }

        if ($user->hasRole(RoleName::SUPPLIER->value)) {
            session(['active_role' => RoleName::SUPPLIER->value]);
            return redirect()->route('professional.dashboard');
        }

        return null; // admin / others → default dashboard
    }
}
