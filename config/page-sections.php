<?php

/**
 * What an admin may edit on each public-page section, and how the editor
 * renders it.
 *
 * The Blade template still owns layout, icons and colour. This declares only
 * the words and pictures, which is what keeps the page un-breakable from the
 * admin side: there is no field here that can produce a broken layout.
 *
 * Field types: text · textarea · image · url · toggle
 * `repeater` describes a list in the section's `payload`, with `min`/`max`
 * bounds so a three-column row cannot become a four-column one.
 */
return [

    'landing' => [

        'branding' => [
            'name'   => 'Logo & branding',
            'note'   => 'The logo in the header and footer, and the short description under it.',
            'fields' => [
                'image' => ['type' => 'image', 'label' => 'Logo', 'hint' => 'PNG or SVG, transparent background. Leave empty to use the built-in logo.'],
                'body'  => ['type' => 'textarea', 'label' => 'Footer description', 'rows' => 3],
            ],
        ],

        'hero' => [
            'name'   => 'Hero (top of page)',
            'note'   => 'The first thing a visitor sees.',
            'fields' => [
                'heading'    => ['type' => 'text', 'label' => 'Headline', 'hint' => 'Use | to break a line. Wrap words in *stars* for orange and _underscores_ for blue.'],
                'subheading' => ['type' => 'textarea', 'label' => 'Sub-text', 'rows' => 3],
                'image'      => ['type' => 'image', 'label' => 'Main photo'],
            ],
            'repeaters' => [
                'roles' => [
                    'label' => 'The two role cards',
                    'min' => 2, 'max' => 2,
                    'fields' => [
                        'title' => ['type' => 'text', 'label' => 'Title'],
                        'text'  => ['type' => 'text', 'label' => 'Description'],
                        'cta'   => ['type' => 'text', 'label' => 'Button label'],
                        'image' => ['type' => 'image', 'label' => 'Card photo'],
                    ],
                ],
            ],
            'extra' => [
                'badge_title' => ['type' => 'text', 'label' => 'Photo badge — title'],
                'badge_text'  => ['type' => 'text', 'label' => 'Photo badge — sub-text'],
                'trust_text'  => ['type' => 'text', 'label' => 'Trust line under the cards'],
                'trust_sub'   => ['type' => 'text', 'label' => 'Trust line — second row'],
            ],
        ],

        'trust_bar' => [
            'name'      => 'Trust bar (five icons)',
            'note'      => 'The strip of five promises. Icons and colours are fixed by the design.',
            'repeaters' => [
                'items' => [
                    'label' => 'Items', 'min' => 5, 'max' => 5,
                    'fields' => [
                        'title' => ['type' => 'text', 'label' => 'Title'],
                        'text'  => ['type' => 'text', 'label' => 'Description'],
                    ],
                ],
            ],
        ],

        'categories' => [
            'name'   => 'Explore Popular Categories',
            'note'   => 'The tiles themselves come from your real categories — only the heading is editable here.',
            'fields' => [
                'heading' => ['type' => 'text', 'label' => 'Heading'],
                'body'    => ['type' => 'text', 'label' => '“View all” link label'],
            ],
        ],

        'how_it_works' => [
            'name'   => 'How GigResource Works',
            'fields' => [
                'heading'    => ['type' => 'text', 'label' => 'Heading'],
                'subheading' => ['type' => 'text', 'label' => 'Sub-text'],
            ],
            'repeaters' => [
                'steps' => [
                    'label' => 'Steps', 'min' => 5, 'max' => 5,
                    'fields' => [
                        'title' => ['type' => 'text', 'label' => 'Step title'],
                        'text'  => ['type' => 'text', 'label' => 'Step description'],
                    ],
                ],
            ],
        ],

        'assistance' => [
            'name'   => 'Choose Your Level of Assistance',
            'fields' => [
                'heading'    => ['type' => 'text', 'label' => 'Heading'],
                'subheading' => ['type' => 'textarea', 'label' => 'Sub-text', 'rows' => 2],
            ],
            'repeaters' => [
                'cards' => [
                    'label' => 'The three level cards', 'min' => 3, 'max' => 3,
                    'fields' => [
                        'title'   => ['type' => 'text', 'label' => 'Title'],
                        'text'    => ['type' => 'text', 'label' => 'Sub-title'],
                        'bullets' => ['type' => 'textarea', 'label' => 'Bullet points', 'rows' => 4, 'hint' => 'One per line.'],
                        'footer'  => ['type' => 'text', 'label' => 'Footer line'],
                        'image'   => ['type' => 'image', 'label' => 'Card photo'],
                    ],
                ],
            ],
        ],

        'why_choose' => [
            'name'   => 'Why Choose GigResource?',
            'fields' => [
                'heading' => ['type' => 'text', 'label' => 'Heading'],
            ],
            'repeaters' => [
                'items' => [
                    'label' => 'Reasons', 'min' => 4, 'max' => 4,
                    'fields' => [
                        'title' => ['type' => 'text', 'label' => 'Title'],
                        'text'  => ['type' => 'text', 'label' => 'Description'],
                    ],
                ],
            ],
        ],

        'video' => [
            'name'   => 'Promo video',
            'note'   => 'Paste a YouTube, Vimeo or direct .mp4 link. Leave it empty and the picture shows with no play button.',
            'fields' => [
                'body'    => ['type' => 'url', 'label' => 'Video link'],
                'image'   => ['type' => 'image', 'label' => 'Thumbnail'],
                'heading' => ['type' => 'text', 'label' => 'Title (for screen readers)'],
            ],
        ],

        'testimonials' => [
            'name'   => 'Loved by Our Community',
            'note'   => 'The quote is your newest 5-star review — only the heading is editable.',
            'fields' => [
                'heading' => ['type' => 'text', 'label' => 'Heading'],
            ],
        ],

        'value_band' => [
            'name'      => 'Coloured value band',
            'repeaters' => [
                'items' => [
                    'label' => 'Items', 'min' => 5, 'max' => 5,
                    'fields' => [
                        'title' => ['type' => 'text', 'label' => 'Title'],
                        'text'  => ['type' => 'text', 'label' => 'Sub-title'],
                        'note'  => ['type' => 'text', 'label' => 'Small line'],
                    ],
                ],
            ],
        ],

        'pricing' => [
            'name'   => 'Pricing',
            'note'   => 'The plans and their prices come from Membership Plans — only the heading is editable here.',
            'fields' => [
                'heading'    => ['type' => 'text', 'label' => 'Heading'],
                'subheading' => ['type' => 'text', 'label' => 'Sub-text'],
                'body'       => ['type' => 'text', 'label' => 'Footnote under the plans'],
            ],
        ],

        'cta_banner' => [
            'name'   => 'Closing banner',
            'fields' => [
                'heading'    => ['type' => 'text', 'label' => 'Heading', 'hint' => 'Use | to break a line.'],
                'subheading' => ['type' => 'textarea', 'label' => 'Sub-text', 'rows' => 2],
                'image'      => ['type' => 'image', 'label' => 'Background photo'],
            ],
            'repeaters' => [
                'buttons' => [
                    'label' => 'Buttons', 'min' => 2, 'max' => 2,
                    'fields' => [
                        'title' => ['type' => 'text', 'label' => 'Label'],
                    ],
                ],
            ],
        ],
    ],
];
