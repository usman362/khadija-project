<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Foundation\Auth\SendsPasswordResetEmails;

class ForgotPasswordController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Password Reset Controller
    |--------------------------------------------------------------------------
    |
    | This controller is responsible for handling password reset emails and
    | includes a trait which assists in sending these notifications from
    | your application to your users. Feel free to explore this trait.
    |
    */

    use SendsPasswordResetEmails;

    /**
     * Three a day — Khadijah's sheet, 29 Aug.
     *
     * Keyed on the EMAIL, not the account. Nobody is signed in here, and the
     * address is the only stable thing about the request: keying on the IP
     * would let one person work through a list of addresses, and would lock a
     * shared office out over one person's forgetfulness.
     */
    public function sendResetLinkEmail(\Illuminate\Http\Request $request)
    {
        $this->validateEmail($request);

        \App\Support\UserLimit::hit(
            'password-reset',
            null,
            'e:'.sha1(strtolower(trim((string) $request->input('email')))),
            'email',
        );

        return parent::sendResetLinkEmail($request);
    }
}
