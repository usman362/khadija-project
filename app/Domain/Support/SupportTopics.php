<?php

namespace App\Domain\Support;

/**
 * What the assistant offers to help with (Sir Peter's meeting notes, item 6).
 *
 * The assistant used to open with nine unrelated chips — "Post a wedding",
 * "Commission tiers", "Photo limit" — a wall of things to pick from with no
 * order to it, most of which had nothing to do with why anyone had opened it.
 *
 * It is now a tree that narrows: Main category, then a subcategory, then the
 * particular issue, and only then the answer. A client who knows their
 * problem is three taps from it, and a client who does not can read their way
 * down instead of guessing from a list.
 *
 * Two doors are open at every level, and that is the point of them:
 *
 *   Other / None of these   the tree must never be a trap. A problem that
 *                           does not match a heading is still a problem, and
 *                           this hands the client back the keyboard.
 *   Talk to Staff           never behind the whole tree. Sir Peter: "Users
 *                           should not be forced to work through every
 *                           automated category before they can request a
 *                           staff member."
 *
 * A leaf carries the question it asks on the client's behalf, so the
 * assistant receives a full sentence rather than a heading it has to guess at.
 */
class SupportTopics
{
    /** Where "Talk to Staff" goes until staff messaging itself is built. */
    public const STAFF_FORM = 'support_request';

    /**
     * Main => [label, blurb, icon, children].
     *
     * The headings follow the platform's own shape — a client plans an event
     * and books the professionals for it — rather than the shape of the
     * database, so "Bidding Request" and "Find Professionals" sit where a
     * client would look for them.
     */
    public const TREE = [
        'events' => [
            'label' => 'Events',
            'blurb' => 'Requests, proposals, dates and services',
            'icon'  => 'calendar',
            'children' => [
                'bidding' => [
                    'label' => 'Bidding Request',
                    'blurb' => 'Post, proposals, negotiations, acceptance',
                    'icon'  => 'megaphone',
                    'children' => [
                        ['label' => 'Posting a bidding request', 'ask' => 'How do I post a bidding request, and what happens after I publish it?'],
                        ['label' => 'No proposals yet', 'ask' => 'I posted a bidding request and no professionals have sent a proposal. What can I do?'],
                        ['label' => 'Comparing proposals', 'ask' => 'How do I compare the proposals on my request?'],
                        ['label' => 'Accepting or declining', 'ask' => 'How do I accept a proposal, and what happens to the ones I decline?'],
                        ['label' => 'Changing a posted request', 'ask' => 'Can I change a bidding request after it has been posted?'],
                    ],
                ],
                'details' => [
                    'label' => 'Event Details',
                    'blurb' => 'Date, time, location, services',
                    'icon'  => 'clipboard',
                    'children' => [
                        ['label' => 'Changing the date or time', 'ask' => 'How do I change the date or time of my event?'],
                        ['label' => 'Changing the location', 'ask' => 'How do I change the location of my event, or ask for help finding a venue?'],
                        ['label' => 'Adding or removing a service', 'ask' => 'How do I add or remove a service on my event?'],
                        ['label' => 'Guest count and budget', 'ask' => 'How do the guest count and the budget on my event affect the proposals I get?'],
                    ],
                ],
                'find' => [
                    'label' => 'Find Professionals',
                    'blurb' => 'Search, filters, location matching',
                    'icon'  => 'users',
                    'children' => [
                        ['label' => 'No professionals in my area', 'ask' => 'No professionals are showing for my area. What should I do?'],
                        ['label' => 'Filtering the search', 'ask' => 'How do I filter professionals by service, budget or location?'],
                        ['label' => 'Saving one for later', 'ask' => 'How do I save a professional so I can come back to them?'],
                        ['label' => 'Asking one professional directly', 'ask' => 'How do I send a request to one professional instead of posting it for bids?'],
                    ],
                ],
                'agreements' => [
                    'label' => 'Contracts & Agreements',
                    'blurb' => 'Terms, deliverables, changes',
                    'icon'  => 'document',
                    'children' => [
                        ['label' => 'What the agreement covers', 'ask' => 'What does the agreement between me and a professional cover?'],
                        ['label' => 'Changing a signed agreement', 'ask' => 'Can an agreement be changed after both sides have signed it?'],
                        ['label' => 'Cancelling an agreement', 'ask' => 'How do I cancel an agreement, and what happens to the money?'],
                    ],
                ],
                'timeline' => [
                    'label' => 'Timeline & Availability',
                    'blurb' => 'Event timeline, backup dates',
                    'icon'  => 'clock',
                    'children' => [
                        ['label' => 'Adding backup dates', 'ask' => 'How do backup dates work on a request?'],
                        ['label' => 'A professional is not free', 'ask' => 'The professional I want is not available on my date. What are my options?'],
                        ['label' => 'I need someone urgently', 'ask' => 'I need a professional at short notice. How does an emergency request work?'],
                    ],
                ],
            ],
        ],

        'payments' => [
            'label' => 'Payments',
            'blurb' => 'Deposits, balances, refunds, receipts',
            'icon'  => 'card',
            'children' => [
                'paying' => [
                    'label' => 'Paying a Professional',
                    'blurb' => 'Deposits and the balance',
                    'icon'  => 'card',
                    'children' => [
                        ['label' => 'How a deposit works', 'ask' => 'How does the deposit work when I book a professional?'],
                        ['label' => 'Paying the balance', 'ask' => 'When do I pay the balance, and how do I pay it?'],
                        ['label' => 'A payment did not go through', 'ask' => 'My payment did not go through. What should I do?'],
                    ],
                ],
                'refunds' => [
                    'label' => 'Refunds & Cancellations',
                    'blurb' => 'Calling off a booking',
                    'icon'  => 'rotate',
                    'children' => [
                        ['label' => 'Cancelling a booking', 'ask' => 'If I cancel a booking, what happens to what I have already paid?'],
                        ['label' => 'A refund has not arrived', 'ask' => 'My refund has not arrived yet. How long does it take and what can I check?'],
                    ],
                ],
                'records' => [
                    'label' => 'Receipts & Spending',
                    'blurb' => 'Records of what you paid',
                    'icon'  => 'document',
                    'children' => [
                        ['label' => 'Finding a receipt', 'ask' => 'Where do I find the receipt for a payment I made?'],
                        ['label' => 'What I have spent', 'ask' => 'Where can I see everything I have spent so far?'],
                    ],
                ],
            ],
        ],

        'account' => [
            'label' => 'Account',
            'blurb' => 'Profile, verification, access',
            'icon'  => 'user',
            'children' => [
                'profile' => [
                    'label' => 'Profile & Settings',
                    'blurb' => 'Your details and preferences',
                    'icon'  => 'user',
                    'children' => [
                        ['label' => 'Updating my details', 'ask' => 'How do I update the details on my account?'],
                        ['label' => 'Notification settings', 'ask' => 'How do I change which notifications I receive?'],
                        ['label' => 'My GigResource ID', 'ask' => 'What is my GigResource ID for, and where do I find it?'],
                    ],
                ],
                'verification' => [
                    'label' => 'Verification',
                    'blurb' => 'Identity and documents',
                    'icon'  => 'shield',
                    'children' => [
                        ['label' => 'Identity verification', 'ask' => 'How does identity verification work on GigResource?'],
                        ['label' => 'Documents a professional holds', 'ask' => 'What documents does a professional have to hold, and where can I see them?'],
                    ],
                ],
                'access' => [
                    'label' => 'Access & Security',
                    'blurb' => 'Signing in, closing an account',
                    'icon'  => 'lock',
                    'children' => [
                        ['label' => 'Password and signing in', 'ask' => 'I am having trouble signing in. How do I reset my password?'],
                        ['label' => 'Closing my account', 'ask' => 'How do I close my account, and what happens to my bookings?'],
                    ],
                ],
            ],
        ],

        'messages' => [
            'label' => 'Messages',
            'blurb' => 'Talking to professionals',
            'icon'  => 'chat',
            'children' => [
                'talking' => [
                    'label' => 'Talking to a Professional',
                    'blurb' => 'Starting and keeping a conversation',
                    'icon'  => 'chat',
                    'children' => [
                        ['label' => 'Starting a conversation', 'ask' => 'How do I start a conversation with a professional?'],
                        ['label' => 'Marking a message urgent', 'ask' => 'What do the message priority levels mean and how do I set one?'],
                        ['label' => 'Sending a file or photo', 'ask' => 'How do I send a file or a photo in a conversation?'],
                    ],
                ],
                'quiet' => [
                    'label' => 'Notifications & Quiet',
                    'blurb' => 'Sound and Do Not Disturb',
                    'icon'  => 'bell',
                    'children' => [
                        ['label' => 'Turning off the sound', 'ask' => 'How do I turn off the message sound or use Do Not Disturb?'],
                        ['label' => 'I am not seeing new messages', 'ask' => 'I am not seeing new messages. What should I check?'],
                    ],
                ],
            ],
        ],
    ];

    /** The node a path points at, or null. */
    public static function at(array $path): ?array
    {
        $node = ['children' => self::TREE];

        foreach ($path as $key) {
            $children = $node['children'] ?? [];

            if (! is_array($children) || ! array_key_exists($key, $children)) {
                return null;
            }

            $node = $children[$key];
        }

        return $node;
    }

    /** Every leaf question in the tree, for checking none of them is empty. */
    public static function questions(): array
    {
        $asks = [];

        $walk = function (array $nodes) use (&$walk, &$asks): void {
            foreach ($nodes as $node) {
                if (isset($node['ask'])) {
                    $asks[] = $node['ask'];
                    continue;
                }

                $walk($node['children'] ?? []);
            }
        };

        $walk(self::TREE);

        return $asks;
    }
}
