<?php

namespace App\Domain\Taxonomy;

/**
 * A line icon per service category.
 *
 * None of the 27 service categories has artwork, and none is coming soon — so
 * the choice on the event-type page was a photo that does not exist, a broken
 * <img>, or a symbol. An icon is a symbol: it illustrates without claiming to
 * be a picture of anything, which a stock photograph of somebody else's wedding
 * would not manage.
 *
 * Keyed by slug so a renamed category keeps its icon, with a generic mark for
 * anything unmapped — a missing icon should look plain, never broken.
 */
class ServiceIcon
{
    /** Stroke paths, drawn on a 24x24 grid. */
    private const PATHS = [
        'audio-visual-lighting-staging'   => '<path d="M12 2v10"/><circle cx="12" cy="16" r="4"/><path d="M5 20h14"/>',
        'bakery-desserts-cake-design'     => '<path d="M4 21h16v-7H4z"/><path d="M4 14c2-3 4-3 4 0m4 0c2-3 4-3 4 0"/><path d="M12 3v4"/>',
        'bar-beverage-mixology-services'  => '<path d="M4 4h16l-8 9z"/><path d="M12 13v7"/><path d="M8 20h8"/>',
        'catering-food-services'          => '<path d="M4 3v8a3 3 0 0 0 6 0V3"/><path d="M7 11v10"/><path d="M17 3c-2 3-2 6 0 8v10"/>',
        'cleaning-sanitation-waste-services' => '<path d="M3 6h18"/><path d="M8 6V4h8v2"/><path d="M5 6l1 15h12l1-15"/>',
        'decor-floral-balloon-design'     => '<circle cx="12" cy="9" r="6"/><path d="M12 15v6"/><path d="M9 21h6"/>',
        'djs-live-bands-musicians'        => '<path d="M9 18V5l12-2v13"/><circle cx="6" cy="18" r="3"/><circle cx="18" cy="16" r="3"/>',
        'entertainment-performers-activities' => '<circle cx="12" cy="12" r="9"/><path d="M8 14s1.5 2 4 2 4-2 4-2"/><path d="M9 9h.01M15 9h.01"/>',
        'event-planning-day-of-coordination' => '<rect x="3" y="4" width="18" height="17" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/><path d="M9 15l2 2 4-4"/>',
        'event-rentals-furnishings'       => '<path d="M4 18v-7a3 3 0 0 1 3-3h10a3 3 0 0 1 3 3v7"/><path d="M2 18h20"/><path d="M6 21v-3M18 21v-3"/>',
        'event-staffing-guest-services'   => '<path d="M17 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/>',
        'event-technology-livestreaming'  => '<rect x="2" y="5" width="15" height="14" rx="2"/><path d="M22 8l-5 4 5 4z"/>',
        'favors-gifting-awards'           => '<rect x="3" y="8" width="18" height="13" rx="2"/><path d="M12 8v13"/><path d="M3 12h18"/><path d="M12 8S9 3 7 5s5 3 5 3 3-5 5-3-5 3-5 3z"/>',
        'fundraising-auction-services'    => '<path d="M14 4l6 6"/><path d="M4 20l7-7"/><path d="M9 9l6 6"/><path d="M3 21h8"/>',
        'funeral-memorial-services'       => '<path d="M12 21s-7-4.5-7-10a7 7 0 0 1 14 0c0 5.5-7 10-7 10z"/><path d="M12 8v6M9 11h6"/>',
        'hair-makeup-wardrobe-styling'    => '<circle cx="12" cy="8" r="5"/><path d="M5 21c1-4 4-6 7-6s6 2 7 6"/>',
        'invitations-printing-signage'    => '<rect x="3" y="5" width="18" height="14" rx="2"/><path d="M3 7l9 6 9-6"/>',
        'officiants-hosts-ceremonial-leaders' => '<path d="M12 3v18"/><path d="M5 8h14"/><path d="M7 21h10"/>',
        'party-rentals-inflatables'       => '<path d="M12 3a5 5 0 0 1 5 5c0 4-5 8-5 8S7 12 7 8a5 5 0 0 1 5-5z"/><path d="M12 16v5"/>',
        'pet-services-for-events'         => '<circle cx="7" cy="9" r="2"/><circle cx="17" cy="9" r="2"/><circle cx="10" cy="5" r="2"/><circle cx="14" cy="5" r="2"/><path d="M12 12c-3 0-5 2-5 5a3 3 0 0 0 3 3h4a3 3 0 0 0 3-3c0-3-2-5-5-5z"/>',
        'photo-booths-interactive-experiences' => '<rect x="3" y="4" width="18" height="16" rx="2"/><circle cx="12" cy="12" r="3"/><path d="M3 9h18"/>',
        'photography-videography'         => '<path d="M3 8h4l2-3h6l2 3h4v12H3z"/><circle cx="12" cy="13" r="4"/>',
        'property-staging-real-estate-event-services' => '<path d="M3 11l9-7 9 7"/><path d="M5 10v10h14V10"/><path d="M10 20v-6h4v6"/>',
        'security-crowd-management'       => '<path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>',
        'trade-show-exhibit-services'     => '<path d="M3 4h18v10H3z"/><path d="M8 20l4-6 4 6"/><path d="M12 14v6"/>',
        'transportation-valet-services'   => '<path d="M5 17h14"/><path d="M6 17V9l2-4h8l2 4v8"/><circle cx="8" cy="17" r="2"/><circle cx="16" cy="17" r="2"/>',
        'venues-event-spaces'             => '<path d="M3 21V8l9-5 9 5v13"/><path d="M9 21v-7h6v7"/>',
    ];

    private const FALLBACK = '<circle cx="12" cy="12" r="9"/><path d="M12 8v8M8 12h8"/>';

    public static function pathFor(?string $slug): string
    {
        return self::PATHS[$slug] ?? self::FALLBACK;
    }

    /** @return list<string> */
    public static function mappedSlugs(): array
    {
        return array_keys(self::PATHS);
    }
}
