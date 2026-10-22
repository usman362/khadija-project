<?php

namespace App\Support;

/**
 * What GigResource calls the people on the other side of a request.
 *
 * Sir Peter, 21 October: "I need to help figure out how we will word the
 * influencers in the rules, tasks, the bidding since we have almost everywhere
 * listed it only as professionals." Then: "I will leave that
 * professionals/influencers wording with you to do."
 *
 * Measured before changing anything: 289 user-visible occurrences of
 * "professional" across 45 client views. Written out by hand, a change of mind
 * is 289 edits, which is how the fee figures went stale in three places inside
 * one month. So the phrase is defined once, here.
 *
 * THE THREE RULES, which is the part worth keeping:
 *
 * 1. Where somebody is CHOOSING between them, the two words stay apart. Find
 *    Professionals and Find Influencers are two different searches, and
 *    merging them would hide one behind the other.
 *
 * 2. Where the word names ONE person already booked or already bidding, use
 *    SupplierProfile::noun(), which reads their account and says which they
 *    are. That is the same source the profile link and the message button
 *    read, so a row cannot call somebody an influencer and then offer to open
 *    their professional profile.
 *
 * 3. Where the word means "whoever can answer this request", which is most of
 *    the 289, use the phrases below.
 *
 * What stays "professional" on purpose: anything true of professionals only.
 * Trade licences, insurance and certificates of insurance are professional
 * obligations; an influencer holds none of them, so wording those inclusively
 * would state a rule that does not exist.
 */
final class Vocabulary
{
    /**
     * SETTLED BY KHADIJAH, 21 October 11:37 PM, after reading the three rules:
     *
     *   "Yes, let's use 'Service Provider' as the defined neutral term. Keep
     *    Professional and Influencer separate whenever the user is choosing
     *    between those two roles, and use Service Provider when referring
     *    generally to whoever was booked or submitted a bid. Please apply this
     *    consistently in one pass."
     *
     * Her term replaces the one proposed here first. "professionals and
     * influencers" had a problem her answer does not: it was not true. Nothing
     * on the platform can send a request to an influencer yet, so promising
     * them on 289 screens would have been the verified badge over again.
     *
     * "Service Provider" is true today and stays true tomorrow. A professional
     * is one; an influencer will be one; neither is promised where it cannot
     * be delivered. So there is no longer anything to switch on later, and the
     * flag that stood here has gone with it.
     */

    /** Everyone who might answer a request. */
    public static function providers(): string
    {
        return 'service providers';
    }

    /** One of them, unknown which. */
    public static function provider(): string
    {
        return 'service provider';
    }

    /**
     * Sentence-initial, and headings.
     *
     * A separate method rather than ucfirst() at the call site: PHP method
     * names are case-insensitive, so this cannot simply be Providers().
     */
    public static function providersCapitalised(): string
    {
        return ucfirst(static::providers());
    }

    /**
     * The same term capitalised, for rules, policies and agreements.
     *
     * Khadijah's wording is a DEFINED term, so in a document that defines it,
     * it is capitalised: "the Service Provider" means the party performing the
     * work. In ordinary screen copy it is lower case, like any other noun.
     */
    public static function legalTerm(bool $plural = false): string
    {
        return $plural ? 'Service Providers' : 'Service Provider';
    }
}
