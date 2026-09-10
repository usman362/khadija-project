<?php

/*
 * What the side of a request page explains.
 *
 * Sir Peter, 2026-09-09: "if all of these workflows ultimately lead to an
 * agreement, I would make the core agreement-driving information consistent
 * across all of them." And 2026-09-10, on the recreated mockups: "we lack data
 * for the users in these current we have."
 *
 * The four request flows -- bidding, emergency, direct, and planning with the
 * toolkit -- each had their own hand-written side rail, so the same fact was
 * written four times and the $2.99 was explained in four different sentences.
 * They are written once here instead. Change the fee wording in `pay` and it
 * changes on every one of them, which is the point.
 *
 * COPY RULES (Sir Peter's, enforced by RequestHelpRailTest): no guarantees, no
 * response-time promises, no invented metrics, nothing about a service we do
 * not run. Every line here has to be something the system actually does.
 */

$pay = [
    'title' => "What it'll cost",
    'icon'  => 'dollar',
    'items' => [
        ['b' => '$0 to post', 't' => 'Nothing is charged when you send this.'],
        ['b' => 'A single $2.99', 't' => 'Charged only when you finalize with a professional.'],
        ['b' => 'Nothing if it goes nowhere', 't' => 'No professional, no charge.'],
    ],
];

$editable = [
    'b' => 'Editable until you choose',
    't' => 'Change the details or back out any time before you pick someone.',
];

return [

    'br' => [
        [
            'title' => 'How bidding works',
            'icon'  => 'clock',
            'steps' => [
                ['b' => 'Publish your request',        't' => 'Tell professionals what you need, where, and when.'],
                ['b' => 'Eligible professionals bid',  't' => 'Everyone in your state who offers those services can propose.'],
                ['b' => 'Compare and finalize',        't' => 'Proposals land on your Proposals page. Pick one and confirm.'],
            ],
        ],
        [
            'title' => 'Single, multi, or direct?',
            'icon'  => 'check',
            'items' => [
                ['b' => 'SSR', 't' => 'One service, one agreement.'],
                ['b' => 'MSR', 't' => 'Several services. Each is bid on and agreed separately. You do not have to choose; picking more than one service makes it an MSR.'],
                ['b' => 'Direct Request', 't' => 'Goes to one professional you have already chosen, instead of out to the board.'],
            ],
        ],
        // No fee panel here: Sir Peter wants the $2.99 shown only on the
        // last step, as the checkbox the client ticks before publishing.
        [
            'title' => 'Tips for better proposals',
            'icon'  => 'bolt',
            'items' => [
                ['b' => 'Say what changes the price', 't' => 'Access, timings, equipment, dietary needs.'],
                ['b' => 'Give a budget range',        't' => 'Professionals can rule themselves in or out instead of guessing.'],
                ['b' => 'Set a realistic deadline',   't' => 'Proposals always close before the event starts.'],
            ],
        ],
        [
            'title' => 'Good to know',
            'icon'  => 'info',
            'items' => [
                ['b' => 'Sealed proposals', 't' => 'Amounts are visible only to you and the professional who sent them.'],
                $editable,
                ['b' => 'Your state only', 't' => 'Requests are matched to professionals in your own state.'],
            ],
        ],
    ],

    'er' => [
        [
            'title' => 'How rush requests work',
            'icon'  => 'clock',
            'steps' => [
                ['b' => 'Publish your request',       't' => "Tell us what you need and by when, it's free to post."],
                ['b' => 'Available pros are notified', 't' => 'It goes out to every professional in your state who offers the service.'],
                ['b' => 'Respond and finalize',       't' => 'Replies appear on your Proposals page. Pick one and confirm.'],
            ],
        ],
        [
            'title' => 'Single or multi?',
            'icon'  => 'check',
            'items' => [
                ['b' => 'Single service', 't' => 'One urgent gap, one agreement.'],
                ['b' => 'Multi-service',  't' => 'Several gaps; each is bid on and agreed separately.'],
                ['b' => 'Either way',     't' => 'This goes out to the board. It is not sent to one professional the way a Direct Request is.'],
            ],
        ],
        $pay,
        [
            'title' => 'Tips for faster replies',
            'icon'  => 'bolt',
            'items' => [
                ['b' => 'Be exact about "needed by"', 't' => 'A professional decides on the time before anything else.'],
                ['b' => 'Give a budget range',        't' => 'It lets someone commit without a back-and-forth.'],
                ['b' => 'Say where it is',            't' => 'Distance is the second thing they check.'],
            ],
        ],
        [
            'title' => 'Good to know',
            'icon'  => 'info',
            'items' => [
                ['b' => 'Bids show as they arrive', 't' => 'A rush request is never held to a closing time.'],
                $editable,
            ],
        ],
    ],

    'dr' => [
        [
            'title' => 'How Direct Requests work',
            'icon'  => 'send',
            'steps' => [
                ['b' => 'Pick your pro and services', 't' => "Choose who you're sending this to and exactly what you need."],
                ['b' => 'Send the request',           't' => 'Your answers become a brief and go to that professional.'],
                ['b' => 'They respond',               't' => 'They can accept, counter, or ask questions, replies land on your Proposals page.'],
            ],
        ],
        [
            'title' => 'Single, multi, or bidding?',
            'icon'  => 'check',
            'items' => [
                ['b' => 'SSR', 't' => 'One service, handled as a single agreement.'],
                ['b' => 'MSR', 't' => 'Several services; each is sent as its own agreement.'],
                ['b' => 'Bidding Request', 't' => 'Use one instead if you would rather several professionals proposed.'],
            ],
        ],
        $pay,
        [
            'title' => 'Tips for a good answer',
            'icon'  => 'bolt',
            'items' => [
                ['b' => 'Name the event',       't' => 'A request with no name is harder to place.'],
                ['b' => 'Say what you expect',  't' => 'Setup, timings, anything that changes the price.'],
                ['b' => 'Give a budget range',  't' => 'Only this professional sees it.'],
            ],
        ],
        [
            'title' => 'Good to know',
            'icon'  => 'info',
            'items' => [
                ['b' => 'One professional only', 't' => 'This is not posted to the board.'],
                ['b' => 'You can negotiate',     't' => 'Counter or ask questions before anything is confirmed.'],
                $editable,
            ],
        ],
    ],

    'toolkit' => [
        [
            'title' => 'How planning works',
            'icon'  => 'clock',
            'steps' => [
                ['b' => 'Work something out in a tool', 't' => 'A budget, a checklist, a timeline, whatever you saved.'],
                ['b' => 'Choose where it goes',         't' => 'Onto an open request, or into a professional’s agreement.'],
                ['b' => 'Choose how it is added',       't' => 'As a copy, or kept linked so it follows your changes.'],
            ],
        ],
        [
            'title' => 'Copy or linked?',
            'icon'  => 'check',
            'items' => [
                ['b' => 'Add a copy', 't' => 'A snapshot as it is now. Later edits in the tool do not change it.'],
                ['b' => 'Keep linked', 't' => 'Edit the plan later and we flag what you attached as needing review. Nothing is rewritten without you.'],
            ],
        ],
        [
            'title' => 'Good to know',
            'icon'  => 'info',
            'items' => [
                ['b' => 'Nothing is sent from here', 't' => 'Attaching a plan does not post a request or notify anyone on its own.'],
                ['b' => 'Agreed terms are left alone', 't' => 'Once an agreement is accepted, a linked figure inside it stops taking updates.'],
            ],
        ],
    ],
];
