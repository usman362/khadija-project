<?php

/*
 * What the site calls the people on the other side of a request.
 *
 * See App\Support\Vocabulary for the three rules and for why this is a switch
 * rather than 277 rewritten sentences. In short: the approved wording is
 * "professionals and influencers", and it is not true yet, because nothing on
 * the platform can send a request to an influencer.
 */
return [

    /*
     * Turn this on when an influencer can genuinely receive a request: an
     * influencer account can be created, it appears in the pickers, and it can
     * answer a bidding, emergency or direct request. Until then the site says
     * "professionals", which is what actually happens.
     */
    'influencers_included' => env('VOCAB_INFLUENCERS_INCLUDED', false),

];
