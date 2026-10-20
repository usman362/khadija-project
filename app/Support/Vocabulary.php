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
     * WHY THERE IS A SWITCH, and not just the new words.
     *
     * The approved wording is "professionals and influencers". It is not the
     * truth yet. Measured on 21 October: there are no influencer accounts on
     * the platform, no influencer packages, the Direct Request picker lists
     * professionals only, and the influencer role holds 5 of the 18
     * permissions the professional role holds. Nothing can send a request to
     * an influencer.
     *
     * So writing "your request will be visible to professionals and
     * influencers" across the site today would be the same thing as the
     * verified badge and the landing page's trust bar: a page claiming
     * something the platform does not do. Sir Peter found both of those
     * himself, and the second one twice.
     *
     * The answer is therefore the switch rather than the sentence. Every
     * screen reads these methods, so the wording turns on in ONE edit the day
     * an influencer can actually receive a request, instead of 277 of them.
     *
     * Flip `config('vocabulary.influencers_included')` on that day.
     */
    public static function includesInfluencers(): bool
    {
        return (bool) config('vocabulary.influencers_included', false);
    }

    /** Everyone who might answer a request. */
    public static function providers(): string
    {
        return static::includesInfluencers() ? 'professionals and influencers' : 'professionals';
    }

    /** One of them, unknown which. */
    public static function provider(): string
    {
        return static::includesInfluencers() ? 'professional or influencer' : 'professional';
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
     * The term for rules, policies and agreements.
     *
     * One defined term rather than a list, because a policy that names two
     * kinds of account has to be amended when a third is added, and because
     * "Service Provider" is what the agreements already have to call the party
     * that performs the work.
     */
    public static function legalTerm(bool $plural = false): string
    {
        return $plural ? 'Service Providers' : 'Service Provider';
    }
}
