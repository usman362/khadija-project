<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Support\LoginLockout;
use Illuminate\Foundation\Auth\ResetsPasswords;

class ResetPasswordController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Password Reset Controller
    |--------------------------------------------------------------------------
    |
    | This controller is responsible for handling password reset requests
    | and uses a simple trait to include this behavior. You're free to
    | explore this trait and override any methods you wish to tweak.
    |
    */

    // Aliased rather than reached with `parent::` — the trait is flattened
    // into this class, so parent:: misses it and Controller::__call turns
    // that into a runtime BadMethodCallException.
    use ResetsPasswords {
        resetPassword as protected baseResetPassword;
    }

    /**
     * Where to redirect users after resetting their password.
     *
     * @var string
     */
    protected $redirectTo = '/home';

    /**
     * Rule R56 — completing a reset is the only thing that lifts a login lock.
     *
     * This sits here rather than on a listener for the Illuminate\Auth\Events\
     * PasswordReset event, because that event also fires from flows that do not
     * prove control of the mailbox. The lock should only lift for someone who
     * followed the emailed link.
     */
    protected function resetPassword($user, $password): void
    {
        $this->baseResetPassword($user, $password);

        LoginLockout::release($user);
    }
}
