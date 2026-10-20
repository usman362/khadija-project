<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Go-Live Switch (pre-launch payment lock)
    |--------------------------------------------------------------------------
    | Per Peter: real money must NOT be charged until the platform officially
    | goes live — but the payment flow still needs to be testable.
    |
    | While this is FALSE (the default), any attempt to create a real/live
    | charge is blocked. Testing still works fully in Stripe/PayPal TEST mode
    | (admin Payment Settings → mode = Test) using test cards — no real money
    | ever moves.
    |
    | At launch: set PAYMENTS_GO_LIVE=true in .env AND switch the admin
    | Payment Settings mode to "Live" with live API keys.
    */
    'go_live' => env('PAYMENTS_GO_LIVE', false),

    /*
    |--------------------------------------------------------------------------
    | Deposit
    |--------------------------------------------------------------------------
    | Sir Peter, 2 October: "The clients should not have to enter any deposits
    | that the professionals and influencers role bc it's upto them to decide
    | on the percentage if any."
    |
    | He is right, and the client no longer chooses one. The professional has
    | nowhere to set theirs either, because their side of the agreement is not
    | open yet, and the deposit is what the checkout charges: left empty it
    | would take nothing. So until a professional can name their own figure,
    | this is the one used, and the client is told where it came from rather
    | than being asked to pick it.
    |
    | The 15 to 50 band stays the rule for whatever is set.
    */
    'deposit_percent_default' => (int) env('DEPOSIT_PERCENT_DEFAULT', 25),
    'deposit_percent_min'     => 15,
    'deposit_percent_max'     => 50,

    /*
    |--------------------------------------------------------------------------
    | Client request fee (R10)
    |--------------------------------------------------------------------------
    | "$0 to submit / post / bid; a single $2.99 only on finalization (per
    | request instance, not per gig/pro); $0 if nothing finalizes."
    |
    | `collect_at` is the one open pricing decision: 'finalization' is what R10
    | and R35 say today. Moving it to 'post' is a rule change — R10's "$0 to
    | post" and R35's "no pay-posting-fee step in the wizard" both have to be
    | rewritten first, so this stays where the rules are until that happens.
    |
    | The professional's commission is untouched by this either way: that is
    | theirs, deducted at payout (Commission::rateFor), and a client payment is
    | never offset against it.
    */
    'client_request_fee' => 2.99,

    /*
     * There was a 'client_request_fee_collect_at' key here. Nothing read it,
     * so setting it to 'publish' changed nothing while the fee went on being
     * charged at finalisation. Removed rather than left to mislead the next
     * reader: where the fee is taken is decided by Event::saved() recording a
     * platform_fee entry, and held by ThePostingFeeIsOwedAtPublishTest.
     */

    /*
    |--------------------------------------------------------------------------
    | Client pricing, confirmed by Sir Peter 16-17 October 2026
    |--------------------------------------------------------------------------
    |
    | He settled these over two days of chat, so they are written down here
    | rather than left in a thread. Every one is a figure he confirmed:
    |
    |   Posting: $2.99 for the founder cohort, $4.99 once that ends.
    |   Agreement: $4.99, and CLIENT ONLY. He first asked for it from both
    |     sides, then withdrew it for providers: "let the commission rate
    |     override my 4.99". A professional already pays membership for
    |     exposure and commission on the booking, so a flat fee would be a
    |     third charge on the same work, heaviest on the smallest jobs.
    |   Extension: $1.99 for 7, 14 or 21 more days, instead of reposting.
    |   Expiry: 7 days from publication.
    |
    | Founder definition, his words via Khadijah: the first 1,000 verified
    | Clients, six months counted from their FIRST PUBLISHED POSTING rather
    | than from signup, so the window is not burned by somebody who registered
    | early and was not ready to post.
    */
    /*
     * Both $2.99 since 20 October. Peter: "$2.99 to post a new request", and
     * Khadijah the same day: "Client event posting and reposting should remain
     * separate at $2.99 each." That replaces the founder/regular split he
     * confirmed on 16 October, where the regular rate was $4.99.
     *
     * The two keys are kept rather than collapsed into one, because the founder
     * cohort is still a live idea in his Founding Starter Plan and the day it
     * carries a different posting price again, this is where it goes. While
     * they are equal, nothing claims a founder discount: FeeSchedule only says
     * "you are on the founder rate" when the rates actually differ.
     */
    'posting_fee_founder' => 2.99,
    'posting_fee_regular' => 2.99,
    'agreement_fee'       => 4.99,

    /*
    |--------------------------------------------------------------------------
    | Requests: extend and repost (Peter, 20 October)
    |--------------------------------------------------------------------------
    |
    | "$1.99 to extend a request for the specified number of additional days.
    | $2.99 to edit/repost a request."
    |
    | A flat $1.99 replaces Rule R33's sliding ladder ($1.99 / $2.99 / $4.99 /
    | $7.99 by length), which is the conflict flagged on 19 October and which he
    | has now settled. EDITING a live request stays free: his own event
    | lifecycle document says "Edit an active event: free", and only a repost is
    | a new posting.
    */
    'request_extension_fee'  => 1.99,
    'request_extension_days' => [7, 14, 21],
    'repost_fee'             => 2.99,

    /*
    |--------------------------------------------------------------------------
    | Packages, charged to the Professional or Influencer (Peter, 20 October)
    |--------------------------------------------------------------------------
    |
    | "$2.99 to post a new package of services. $4.99 or the applicable
    | percentage for the agreement fee. $1.99 to extend a request by 30, 60, or
    | 90 additional days. $2.99 to edit/repost a package of services."
    |
    | The extension lengths are deliberately NOT the request ones. He gave 30 /
    | 60 / 90 for packages and 7 / 14 / 21 for requests, which makes sense: a
    | package is a standing offer and a request is a dated job.
    |
    | "The applicable percentage" is read as the provider's own tier rate, so
    | the agreement fee on a package is the greater of $4.99 and their
    | commission. That matches the Founding Starter Plan's "$4.99 or 3% of the
    | booking, whichever is higher". If he meant something else, FeeSchedule is
    | the one place to change it.
    */
    'package_posting_fee'    => 2.99,
    'package_agreement_fee'  => 4.99,
    'package_extension_fee'  => 1.99,
    'package_extension_days' => [30, 60, 90],
    'package_repost_fee'     => 2.99,

    'founder_cohort_size'   => 1000,
    'founder_months'        => 6,

    /*
     * Extensions and expiry are NOT here. Rule R33 already owns them, with its
     * own ladder (3 days $1.99, 7 days $2.99, 14 days $4.99, 30 days $7.99),
     * a limit of three paid extensions and a free 24-hour grace reopen, and it
     * derives expiry from the client's own proposal_deadline rather than from
     * a fixed number of days after publication.
     *
     * Peter's October figures (7 days flat, extensions of 7/14/21 at a flat
     * $1.99) conflict with that ladder. Two price lists for one feature is the
     * fault this codebase has just been cleaned of twice, so his numbers wait
     * for him to reconcile them with R33 rather than becoming a second set.
     */

    /*
     * Stripe's published US rate, for the Admin calculator and for showing a
     * net figure. Only ever used to ESTIMATE: the real fee is read back from
     * the charge and recorded as a provider_fee ledger entry, because an
     * estimate and a refund disagree. Stripe keeps its fee on a refund.
     */
    'provider_rate_percent' => 2.9,
    'provider_fee_fixed'    => 0.30,
];
