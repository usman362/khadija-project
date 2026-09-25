<?php

return [

    /*
    |--------------------------------------------------------------------------
    | The product's name
    |--------------------------------------------------------------------------
    |
    | Deliberately not env('APP_NAME'). Every brand mention on the site used
    | to read that, and APP_NAME is a server setting: it was set to "Gigs" on
    | production, so the professional profile offered "How booking works on
    | Gigs" and "Accepted on Gigs", and the emails went out under that name
    | too. The product is not called Gigs, and what it is called is not a
    | deployment decision — a typo in a server file should not be able to
    | rename it.
    |
    | APP_NAME is left to the framework for the things it is for.
    |
    */

    'name' => 'GigResource',

];
