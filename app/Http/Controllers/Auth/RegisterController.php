<?php

namespace App\Http\Controllers\Auth;

use App\Domain\Auth\Contracts\UserRegistrationServiceInterface;
use App\Domain\Auth\DataTransferObjects\RegisterUserData;
use App\Domain\Influencer\Contracts\InfluencerServiceInterface;
use App\Domain\Influencer\DataTransferObjects\InfluencerApplicationData;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Rules\Recaptcha;
use App\Support\AgeEligibility;
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
        // "professional" is the public-facing name for the internal "professional" role.
        $role = $request->query('role', 'client');
        if ($role === 'professional') {
            $role = 'professional';
        }

        return view('auth.register', compact('role'));
    }

    protected function validator(array $data)
    {
        $role = $data['role'] ?? 'client';
        $allStates = implode(',', array_keys(config('geo.us_states', [])));

        // Anyone may register, wherever they are (Peter, 2026-07-30). The form
        // no longer offers only the launch states, and it never names them —
        // whether we operate where they live is worked out after the account
        // exists and shown on the post-registration screen. Influencers are not
        // geo-gated at all and may leave it blank.
        $stateRule = $role === 'influencer'
            ? ['nullable', 'string', 'in:' . $allStates]
            : ['required', 'string', 'in:' . $allStates];

        // Rule R62 — Clients and Professionals must be 18+. Influencers are
        // out of scope here on purpose; R24 governs their age separately, and
        // R62 exists as its own rule so that scope stays clean.
        $dobRule = AgeEligibility::appliesTo($role)
            ? ['required', 'date', 'before_or_equal:' . AgeEligibility::latestEligibleBirthdate()->toDateString()]
            : ['nullable', 'date'];

        // Clients and professionals only — see the disclosure rules below.
        $disclosureRule = in_array($role, ['client', 'professional'], true)
            ? ['accepted']
            : ['nullable'];

        return Validator::make($data, [
            'date_of_birth' => $dobRule,
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'phone' => ['nullable', 'string', 'max:30'],
            // The dialling code beside it. Was a fixed +1 nobody could change.
            'phone_country_code' => ['nullable', 'string', 'in:' . implode(',', array_keys(config('geo.dial_codes', [])))],
            'agree' => ['accepted'],

            /*
             * Sir Peter's location/state disclosure, 26 Aug 2026. Three
             * separate boxes, all required, no bypass — his words. They are
             * separate because they are three different things to understand,
             * and one combined box would let someone agree to a limit on who
             * they may hire without ever reading it.
             *
             * Influencers are out of scope: the rule is about matching clients
             * to professionals, and an influencer is neither.
             */
            'disclosure_event_location' => $disclosureRule,
            'disclosure_state_limit'    => $disclosureRule,
            'disclosure_temporary'      => $disclosureRule,
            'role' => ['sometimes', 'string', 'in:client,professional,influencer'],
            'state' => $stateRule,
            'country' => ['nullable', 'string', 'in:' . implode(',', array_keys(config('geo.countries', [])))],
            'city' => ['nullable', 'string', 'max:120'],
            'expansion_opt_in' => ['nullable', 'boolean'],
            'g-recaptcha-response' => [new Recaptcha('register')],
        ], [
            'agree.accepted' => 'Please accept the Terms of Service and Privacy Policy to continue.',
            'disclosure_event_location.accepted' => 'Please confirm you understand which location we use.',
            'disclosure_state_limit.accepted'    => 'Please confirm you understand the same-state rule.',
            'disclosure_temporary.accepted'      => 'Please confirm you understand this limit is temporary.',
            'state.required' => 'Please select your state.',
            'date_of_birth.required'        => 'Please enter your date of birth.',
            'date_of_birth.before_or_equal' => 'You must be at least ' . AgeEligibility::MINIMUM_AGE
                . ' years old to open an account.',
            // Deliberately says nothing about which states we serve — that is
            // only revealed after registration.
            'state.in' => 'Please choose a state from the list.',
        ]);
    }

    protected function create(array $data)
    {
        $role  = $data['role'] ?? 'client';
        if ($role === 'professional') {   // public alias → internal supplier role
            $role = 'professional';
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
            // Keep the code with the number. Storing the digits alone loses which
            // country they belong to, and the form only ever showed +1 before —
            // so a +44 number was saved looking like a US one.
            $phone = trim((string) $data['phone']);
            $code  = $data['phone_country_code'] ?? '+1';

            if (! str_starts_with($phone, '+')) {
                $phone = $code . ' ' . ltrim($phone, '0');
            }

            $user->update(['phone' => $phone]);
        }

        // Location as given, plus the eligibility answer. Everyone is stored —
        // an out-of-area registration is a demand signal and a waitlist entry,
        // not a rejection, so the account stays active either way.
        $country = $data['country'] ?? 'US';
        $status  = \App\Support\ServiceArea::statusFor($country, $state);

        $user->getOrCreateProfile()->update([
            'city'                 => $data['city'] ?? null,
            'state'                => $state,
            'country'              => $country,
            'service_area_status'  => $status,
            'expansion_opt_in'     => (bool) ($data['expansion_opt_in'] ?? false),
            // R62 — kept on the account so the eligibility answer survives the
            // request that validated it.
            'date_of_birth'        => $data['date_of_birth'] ?? null,
        ]);

        if ($state) {
            app(\App\Domain\Geolocation\GeolocationService::class)->rememberState($state);
        }

        /*
         * The record of acceptance Sir Peter asked for: who, when (UTC), which
         * wording, and from where. Written after the account exists because it
         * points at the user; the validator above is what guarantees they
         * actually ticked all three.
         *
         * Only for the roles the rule governs — an influencer is neither a
         * client nor a professional and is never matched by state.
         */
        if (in_array($role, ['client', 'professional'], true)) {
            \App\Models\RegistrationDisclosure::record($user, request()->ip());
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

        // Everyone lands on the welcome screen, which is where — and only
        // where — we say whether we operate in their area.
        return redirect()->route('register.welcome');
    }
}
