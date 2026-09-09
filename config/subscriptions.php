<?php

/*
 * Membership renewals.
 *
 * How far ahead members are told their membership is about to renew.
 * Washington DC's Automatic Renewal Protections Act requires notice before the
 * first renewal and before every renewal after it; it does not name a number
 * of days, so two are sent — one with time to act, one close enough to be
 * remembered.
 *
 * In config because a state that names a specific window can be answered by
 * adding a number here rather than by changing code.
 */
return [
    'renewal_notice_days' => array_map('intval', array_filter(explode(',', (string) env('RENEWAL_NOTICE_DAYS', '30,7')))),
];
