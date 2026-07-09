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

        // Always land the user in their PRIMARY role — the account type they
        // registered as, unaffected by any client/professional switch. So a
        // professional who switched to client and logged out still logs back in
        // as a professional, and a client stays a client, no matter which login
        // page they use. The active-role session is reset to the primary here.
        $landRole = $this->primaryRoleFor($user);

        if ($landRole === RoleName::SUPPLIER->value && $user->hasRole(RoleName::SUPPLIER->value)) {
            session(['active_role' => RoleName::SUPPLIER->value]);
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
        if (in_array($primary, [RoleName::SUPPLIER->value, RoleName::CLIENT->value], true) && $user->hasRole($primary)) {
            return $primary;
        }

        return $user->hasRole(RoleName::SUPPLIER->value) ? RoleName::SUPPLIER->value : RoleName::CLIENT->value;
    }
}
