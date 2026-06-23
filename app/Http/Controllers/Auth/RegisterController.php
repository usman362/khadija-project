<?php

namespace App\Http\Controllers\Auth;

use App\Domain\Auth\Contracts\UserRegistrationServiceInterface;
use App\Domain\Auth\DataTransferObjects\RegisterUserData;
use App\Domain\Influencer\Contracts\InfluencerServiceInterface;
use App\Http\Controllers\Controller;
use App\Rules\Recaptcha;
use Illuminate\Foundation\Auth\RegistersUsers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class RegisterController extends Controller
{
    use RegistersUsers;

    protected $redirectTo = '/dashboard';

    public function __construct(private readonly UserRegistrationServiceInterface $userRegistrationService)
    {
        $this->middleware('guest');
    }

    /**
     * Show the registration form.
     */
    public function showRegistrationForm(Request $request)
    {
        $role = $request->query('role', 'client');

        return view('auth.register', compact('role'));
    }

    protected function validator(array $data)
    {
        $role = $data['role'] ?? 'client';

        // Geo-restriction (Feedback v1.1 §7.1): platform is limited to 7
        // launch states. Professionals pick from a hardcoded dropdown;
        // clients are validated by zip code → state prefix mapping. Both
        // are free Layer-1 checks (no paid API).
        $geoRules = $role === 'supplier'
            ? ['state' => ['required', 'string', 'in:' . implode(',', array_keys(config('geo.allowed_states', [])))]]
            : ['zip_code' => ['required', 'regex:/^\d{5}(-\d{4})?$/', function ($attribute, $value, $fail) {
                $prefix = substr((string) $value, 0, 3);
                if (! array_key_exists($prefix, config('geo.zip_prefixes', []))) {
                    $fail('GigResource is currently available in Maryland, Virginia, Washington D.C., Delaware, Pennsylvania, New Jersey, and New York. This zip code is outside our service area.');
                }
            }]];

        return Validator::make($data, [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'phone' => ['nullable', 'string', 'max:30'],
            'agree' => ['accepted'],
            'role' => ['sometimes', 'string', 'in:client,supplier'],
            'g-recaptcha-response' => [new Recaptcha('register')],
            ...$geoRules,
        ], [
            'agree.accepted' => 'Please accept the Terms of Service and Privacy Policy to continue.',
        ]);
    }

    protected function create(array $data)
    {
        $role = $data['role'] ?? 'client';

        $user = $this->userRegistrationService->register(
            new RegisterUserData(
                name: (string) $data['name'],
                email: (string) $data['email'],
                password: (string) $data['password'],
                role: (string) $role,
            )
        );

        // Phone captured at signup (optional) — stored on the user.
        if (! empty($data['phone'])) {
            $user->update(['phone' => $data['phone']]);
        }

        // Persist launch-state info captured at signup (§7.1). Professionals
        // give their state; clients give a zip we map to its state.
        $state = $data['state'] ?? null;
        $zip   = $data['zip_code'] ?? null;
        if ($zip && ! $state) {
            $state = config('geo.zip_prefixes', [])[substr((string) $zip, 0, 3)] ?? null;
        }
        if ($state || $zip) {
            $user->getOrCreateProfile()->update(array_filter([
                'state'    => $state,
                'zip_code' => $zip,
            ]));

            // §7.2 Step 1 — remember the launch state in the session so later
            // visits skip IP guessing.
            app(\App\Domain\Geolocation\GeolocationService::class)->rememberState($state);
        }

        // Attribute signup to an influencer if referral cookie is present
        $cookieName = (string) config('influencer.cookie_name', 'khadija_ref');
        $code = request()->cookie($cookieName);
        if ($code) {
            app(InfluencerServiceInterface::class)->attributeSignup($user, (string) $code);
        }

        return $user;
    }
}
