<?php

namespace App\Http\Controllers\Auth;

use App\Domain\Auth\Contracts\UserRegistrationServiceInterface;
use App\Domain\Auth\DataTransferObjects\RegisterUserData;
use App\Domain\Influencer\Contracts\InfluencerServiceInterface;
use App\Domain\Influencer\DataTransferObjects\InfluencerApplicationData;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Rules\Recaptcha;
use Illuminate\Foundation\Auth\RegistersUsers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class RegisterController extends Controller
{
    use RegistersUsers;

    protected $redirectTo = '/dashboard';

    public function __construct(
        private readonly UserRegistrationServiceInterface $userRegistrationService,
        private readonly InfluencerServiceInterface $influencerService,
    ) {
        $this->middleware('guest');
    }

    /**
     * Show the registration form.
     */
    public function showRegistrationForm(Request $request)
    {
        // "professional" is the public-facing name for the internal "supplier" role.
        $role = $request->query('role', 'client');
        if ($role === 'professional') {
            $role = 'supplier';
        }

        return view('auth.register', compact('role'));
    }

    protected function validator(array $data)
    {
        $role = $data['role'] ?? 'client';
        $allowedStates = implode(',', array_keys(config('geo.allowed_states', [])));

        // Geo-restriction (Feedback v1.1 §7.1): platform is limited to 7
        // launch states. Clients and professionals both pick their state from
        // the hardcoded dropdown; influencers may leave it blank (they aren't
        // geo-gated). Free Layer-1 check — no paid API.
        $stateRule = $role === 'influencer'
            ? ['nullable', 'string', 'in:' . $allowedStates]
            : ['required', 'string', 'in:' . $allowedStates];

        return Validator::make($data, [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'phone' => ['nullable', 'string', 'max:30'],
            'agree' => ['accepted'],
            'role' => ['sometimes', 'string', 'in:client,supplier,professional,influencer'],
            'state' => $stateRule,
            'g-recaptcha-response' => [new Recaptcha('register')],
        ], [
            'agree.accepted' => 'Please accept the Terms of Service and Privacy Policy to continue.',
            'state.required' => 'Please select your state.',
            'state.in' => 'GigResource is currently available in Maryland, Pennsylvania, West Virginia, Virginia, New Jersey, Delaware, and Washington D.C.',
        ]);
    }

    protected function create(array $data)
    {
        $role  = $data['role'] ?? 'client';
        if ($role === 'professional') {   // public alias → internal supplier role
            $role = 'supplier';
        }
        $state = $data['state'] ?? null;

        if ($role === 'influencer') {
            // Self-serve affiliate signup: create the login account + a pending
            // influencer application. The influencer role is granted only on
            // admin approval (mirrors JoinAsInfluencerController).
            $user = User::create([
                'name'     => (string) $data['name'],
                'email'    => (string) $data['email'],
                'password' => Hash::make((string) $data['password']),
            ]);

            $this->influencerService->apply(
                InfluencerApplicationData::fromArray([
                    'full_name'            => (string) $data['name'],
                    'email'                => (string) $data['email'],
                    'social_media_links'   => [],
                    'audience_description' => null,
                    'monthly_reach'        => 0,
                    'user_id'              => $user->id,
                ])
            );
        } else {
            $user = $this->userRegistrationService->register(
                new RegisterUserData(
                    name: (string) $data['name'],
                    email: (string) $data['email'],
                    password: (string) $data['password'],
                    role: (string) $role,
                )
            );
        }

        // Record the account type they signed up as — their permanent "home"
        // role for login landing, independent of any later client/pro switch.
        $user->update(['primary_role' => $role]);

        // Phone captured at signup (optional) — stored on the user.
        if (! empty($data['phone'])) {
            $user->update(['phone' => $data['phone']]);
        }

        // Persist launch-state captured at signup (§7.1) + remember it so later
        // visits skip IP guessing.
        if ($state) {
            $user->getOrCreateProfile()->update(['state' => $state]);
            app(\App\Domain\Geolocation\GeolocationService::class)->rememberState($state);
        }

        // Attribute signup to an influencer if a referral cookie is present.
        $cookieName = (string) config('influencer.cookie_name', 'khadija_ref');
        $code = request()->cookie($cookieName);
        if ($code) {
            app(InfluencerServiceInterface::class)->attributeSignup($user, (string) $code);
        }

        return $user;
    }

    /**
     * After the trait logs the new user in, send affiliate applicants to their
     * application-status page; everyone else falls through to $redirectTo.
     */
    protected function registered(Request $request, $user)
    {
        if ($user->influencer) {
            return redirect()
                ->route('influencer.status')
                ->with('status', 'Account created! Your affiliate application is now under review.');
        }

        return null;
    }
}
