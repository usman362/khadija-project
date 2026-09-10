<?php

/*
 * The compliance register — which state, which law, what we did, and when.
 *
 * Sir Peter, 2026-09-09: "put a timestamp somewhere, so down the road we know
 * which state, what date (month/year) and laws (title/ID) we can keep track" —
 * and Khadijah's checklist of Terms-of-Service items lands here as she writes
 * it, so each one can be ticked off as it is built.
 *
 * Deliberately NOT config/compliance.php, which is already the insurance rules
 * — two unrelated registers under one name is how one of them gets clobbered.
 *
 * One entry per REQUIREMENT, not per law: a single act can ask for three
 * different things, and three things is what somebody has to build.
 *
 *   jurisdiction  the state or district whose law this is
 *   law           its title as published
 *   citation      the code section or bill number. Left null where nobody has
 *                 given it to us yet — a made-up citation is worse than a
 *                 blank one, because a blank one gets asked about
 *   effective     when the law starts to bite (YYYY-MM, the granularity asked for)
 *   requires      what it obliges us to do, in plain words
 *   status        done | building | todo | blocked | not_applicable
 *   implemented   what in the product satisfies it — named, so the claim can
 *                 be checked rather than believed
 *   done_on       YYYY-MM we finished it. Only ever set with status done
 *   note          anything the next person needs to know
 */
return [

    'requirements' => [

        [
            'key'          => 'md-self-service-cancellation',
            'jurisdiction' => 'Maryland',
            'law'          => 'Subscription cancellation requirements (2025)',
            'citation'     => null,
            'effective'    => '2025-01',
            'requires'     => 'A simple, timely, self-service way to cancel a subscription, not "call us to cancel".',
            'status'       => 'done',
            'implemented'  => 'A member cancels their own subscription from the membership page in one step; no contact with support is required.',
            'done_on'      => '2026-09',
            'note'         => 'Citation still needed from Khadijah. The confirmation should also state when access ends.',
        ],

        [
            'key'          => 'dc-renewal-notice',
            'jurisdiction' => 'Washington DC',
            'law'          => 'Automatic Renewal Protections Act of 2018',
            'citation'     => null,
            'effective'    => '2018-01',
            'requires'     => 'Notify the member before the first renewal and before every renewal after it.',
            'status'       => 'done',
            'implemented'  => 'subscriptions:renewal-notices runs daily at 06:00 and emails every member 30 days and 7 days before their membership renews. Each notice is recorded in subscription_renewal_notices before it is sent, so the same one cannot go twice and we can show what was sent, to whom and when.',
            'done_on'      => '2026-09',
            'note'         => 'Sent to every member rather than by state, where somebody lives is not reliably known. Lead times are in config/subscriptions.php, so a state that names a specific window is a settings change. DEPENDS ON the host running Laravel\'s scheduler; two other jobs already rely on it, so if those run, this runs.',
        ],

        [
            'key'          => 'dc-trial-notice-and-consent',
            'jurisdiction' => 'Washington DC',
            'law'          => 'Automatic Renewal Protections Act of 2018',
            'citation'     => null,
            'effective'    => '2018-01',
            'requires'     => 'For a free trial: notice 15–30 days before it ends, and separate affirmative consent before the first paid charge.',
            'status'       => 'not_applicable',
            'implemented'  => null,
            'done_on'      => null,
            'note'         => 'GigResource offers no free trials. There are no trial fields on plans or subscriptions, so nothing can be in breach today. If a trial is ever added, this must be built WITH it rather than after it.',
        ],

    ],
];
