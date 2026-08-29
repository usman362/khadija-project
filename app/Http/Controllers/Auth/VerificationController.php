<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Foundation\Auth\VerifiesEmails;

class VerificationController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Email Verification Controller
    |--------------------------------------------------------------------------
    |
    | This controller is responsible for handling email verification for any
    | user that recently registered with the application. Emails may also
    | be re-sent if the user didn't receive the original email message.
    |
    */

    use VerifiesEmails;

    /**
     * Where to redirect users after verification.
     *
     * @var string
     */
    protected $redirectTo = '/home';

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('signed')->only('verify');

        /*
         * The throttle stays on `verify` — that one is about somebody
         * hammering signed links, which is a security concern and not the
         * same thing as asking for the email again.
         *
         * `resend` used to sit under the same rule: six a MINUTE, which is
         * not really a limit on resending at all — you could ask for three
         * hundred and sixty in an hour. Khadijah's sheet says three a day,
         * and that lives in resend() below where the message can explain
         * itself.
         */
        $this->middleware('throttle:6,1')->only('verify');
    }

    /**
     * Three a day — Khadijah's sheet, 29 Aug.
     *
     * The message points at the inbox and the spam folder, because somebody
     * asking a fourth time has usually already been sent three.
     */
    public function resend(\Illuminate\Http\Request $request)
    {
        if ($request->user()->hasVerifiedEmail()) {
            return redirect($this->redirectPath());
        }

        \App\Support\UserLimit::hit('email-resend', $request->user(), null, 'email');

        $request->user()->sendEmailVerificationNotification();

        return back()->with('resent', true);
    }
}
