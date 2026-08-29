<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Email verification
    |--------------------------------------------------------------------------
    |
    | A new account is always SENT a verification link. `required` decides
    | whether it also has to be clicked before the account can be used.
    |
    | It ships off, because turning it on locks every existing account out
    | overnight — nobody signed up so far has ever been asked to verify, so
    | their email_verified_at is null. Backfill those first (or verify them on
    | next login), then turn this on.
    |
    */
    'verification' => [
        'required' => (bool) env('EMAIL_VERIFICATION_REQUIRED', false),
    ],

    /*
    |--------------------------------------------------------------------------
    | Lifecycle email
    |--------------------------------------------------------------------------
    |
    | The master switch. With MAIL_MAILER=log nothing leaves the server anyway,
    | which is what a staging box wants; this is the flag for turning the
    | automation off in a live environment without editing code.
    |
    */
    'lifecycle' => [
        'enabled' => (bool) env('LIFECYCLE_EMAIL_ENABLED', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | Where replies go
    |--------------------------------------------------------------------------
    |
    | Automated mail comes from MAIL_FROM_ADDRESS. A person replying to it
    | should reach somebody, so point this at a mailbox that is read.
    |
    */
    'reply_to' => env('MAIL_REPLY_TO', env('MAIL_FROM_ADDRESS')),

];
