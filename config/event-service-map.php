<?php

/**
 * Event-type → relevant-services cascade. When a client picks an event type,
 * the service picker narrows to the services that actually fit that occasion
 * (Peter's ask: "pick DJ/music event → only music-related services show").
 *
 * Matching is keyword-based against the service NAME (lowercased substring),
 * grouped into reusable themes. An event type not listed in 'map' shows every
 * service (no narrowing).
 */
return [
    // Theme → substrings that appear in matching service names.
    'themes' => [
        'catering'   => ['cater', 'food', 'beverage', 'bar', 'buffet', 'dessert', 'bbq', 'mixolog', 'culinary', 'bartender', 'dining', 'drink'],
        'photo'      => ['photo', 'video', 'film'],
        'music'      => ['dj', 'sound', 'music', 'band', 'entertain', 'performer', 'live band', 'karaoke'],
        'decor'      => ['decor', 'floral', 'flower', 'balloon', 'centerpiece', 'theme', 'styling', 'lighting design', 'drapery'],
        'planning'   => ['planning', 'coordinat', 'liaison', 'budget', 'reception support'],
        'venue'      => ['venue', 'hall', 'expo space', 'hosting', 'reception'],
        'av'         => ['av ', 'audio', 'visual', 'lighting', 'stage', 'screen', 'tech', 'stream', 'broadcast', 'projection'],
        'staffing'   => ['staff', 'security', 'crowd', 'usher', 'host', 'guidance', 'management', 'support', 'attendant'],
        'cake'       => ['cake', 'sweet', 'bakery', 'dessert'],
        'booth'      => ['booth', 'exhibit', 'trade', 'expo', 'convention', 'signage'],
        'activities' => ['carnival', 'ride', 'casino', 'game', 'cosplay', 'booth', 'activit', 'inflatable', 'photo booth'],
        'beauty'     => ['bridal', 'groom', 'attire', 'beauty', 'makeup', 'hair', 'glam'],
        'awards'     => ['award', 'trophy', 'branding', 'gift', 'recognition', 'apparel', 'print'],
    ],

    // Event type → themes to keep visible. Unlisted types show all services.
    'map' => [
        'Wedding'                        => ['photo', 'catering', 'decor', 'planning', 'venue', 'music', 'cake', 'beauty', 'staffing'],
        'Engagement Party'               => ['catering', 'decor', 'photo', 'music', 'cake'],
        'Bridal Shower'                  => ['catering', 'decor', 'cake', 'photo'],
        'Anniversary'                    => ['catering', 'decor', 'photo', 'music', 'cake', 'planning'],
        'Birthday Party'                 => ['catering', 'decor', 'music', 'cake', 'activities', 'photo'],
        'Baby Shower'                    => ['catering', 'decor', 'cake', 'photo', 'planning'],
        'Gender Reveal'                  => ['catering', 'decor', 'cake', 'photo', 'activities'],
        'Sweet Sixteen'                  => ['catering', 'decor', 'music', 'cake', 'activities', 'photo'],
        'Quinceañera'                    => ['catering', 'decor', 'music', 'cake', 'photo', 'beauty'],
        'Bar / Bat Mitzvah'              => ['catering', 'decor', 'music', 'cake', 'activities', 'photo'],
        'Graduation'                     => ['catering', 'photo', 'decor', 'venue', 'planning'],
        'Prom'                           => ['music', 'av', 'decor', 'photo', 'catering'],
        'Corporate Event'                => ['av', 'catering', 'planning', 'venue', 'staffing', 'awards', 'booth'],
        'Conference'                     => ['av', 'booth', 'catering', 'planning', 'venue', 'staffing'],
        'Seminar'                        => ['av', 'catering', 'venue', 'staffing'],
        'Workshop'                       => ['av', 'catering', 'venue', 'staffing'],
        'Product Launch'                 => ['av', 'catering', 'booth', 'planning', 'staffing', 'decor'],
        'Team Building'                  => ['activities', 'catering', 'venue', 'staffing'],
        'Trade Show'                     => ['booth', 'av', 'staffing', 'catering'],
        'Networking Event'               => ['catering', 'av', 'venue', 'staffing'],
        'Award Ceremony'                 => ['av', 'catering', 'awards', 'staffing', 'planning', 'venue'],
        'Gala'                           => ['catering', 'decor', 'music', 'av', 'planning', 'venue', 'staffing', 'awards'],
        'Fundraiser'                     => ['catering', 'decor', 'music', 'planning', 'venue', 'staffing'],
        'Charity Event'                  => ['catering', 'decor', 'music', 'planning', 'venue', 'staffing'],
        'Concert'                        => ['music', 'av', 'staffing', 'venue'],
        'Live Performance'               => ['music', 'av', 'staffing', 'venue'],
        'Comedy Show'                    => ['av', 'music', 'staffing', 'venue'],
        'Festival'                       => ['music', 'catering', 'av', 'staffing', 'activities', 'venue'],
        'Fashion Show'                   => ['av', 'music', 'staffing', 'decor', 'beauty', 'photo'],
        'Art Exhibition'                 => ['venue', 'catering', 'av', 'staffing'],
        'Holiday Party'                  => ['catering', 'decor', 'music', 'cake', 'activities'],
        'Christmas Party'                => ['catering', 'decor', 'music', 'cake'],
        'New Year Party'                 => ['catering', 'music', 'decor', 'av'],
        'Halloween Party'                => ['catering', 'decor', 'music', 'activities'],
        'Cocktail Party'                 => ['catering', 'music', 'decor', 'staffing'],
        'Dinner Party'                   => ['catering', 'decor', 'music', 'cake'],
        'Housewarming'                   => ['catering', 'decor', 'music'],
        'Retirement Party'               => ['catering', 'decor', 'music', 'cake', 'photo'],
        'Reunion'                        => ['catering', 'music', 'decor', 'photo', 'venue'],
        'Family Reunion'                 => ['catering', 'music', 'decor', 'activities', 'photo'],
        'Bachelor / Bachelorette Party'  => ['music', 'catering', 'activities', 'venue'],
        'Grand Opening'                  => ['catering', 'decor', 'av', 'staffing', 'music'],
        'Pop-up Event'                   => ['booth', 'catering', 'staffing', 'decor', 'av'],
        'Community Event'                => ['catering', 'music', 'staffing', 'activities', 'av'],
        'School Event'                   => ['catering', 'av', 'activities', 'staffing'],
        'Sports Event'                   => ['catering', 'av', 'staffing', 'activities'],
        'Religious Ceremony'             => ['venue', 'catering', 'music', 'decor', 'planning'],
        'Memorial / Celebration of Life' => ['venue', 'catering', 'decor', 'planning', 'music'],
    ],
];
