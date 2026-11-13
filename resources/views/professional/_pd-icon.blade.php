{{-- One line drawing, by name.

     The dashboard needs the same handful of marks in a dozen places: on the
     summary cards, beside each thing waiting, against each count that has
     changed. They were being pasted in full at every call site, which is how
     two of them drifted into being different shapes for the same idea.

     EVERY PATH HERE STAYS INSIDE ROUGHLY 3 TO 21 ON BOTH AXES, and is
     centred on 12,12. That is not tidiness. These sit in 30 pixel tiles at
     15 pixels, so a drawing that uses the full 0 to 24 box renders visibly
     bigger than its neighbours and one that leans to a side sits off-centre
     in its tile. Three did: `users` ran from 1 to 23 across with a half
     figure on the right only, `dollar` ran the full height while being half
     the width of everything else, and `trend` ran the full width. Beside a
     warning triangle drawn at 2 to 22 they read as a different icon set.

     A new icon is checked against that box before it goes in. --}}
@php
    $pdIcons = [
        'chat'     => '<path d="M21 11.5a8.38 8.38 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.38 8.38 0 0 1-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.38 8.38 0 0 1 3.8-.9h.5a8.48 8.48 0 0 1 8 8v.5z"/>',
        'calendar' => '<rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/>',
        'wallet'   => '<path d="M21 12V7H5a2 2 0 0 1 0-4h14v4"/><path d="M3 5v14a2 2 0 0 0 2 2h16v-5"/><path d="M18 12a2 2 0 0 0 0 4h4v-4z"/>',
        'clock'    => '<circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/>',
        'file'     => '<path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/>',
        'inbox'    => '<polyline points="22 12 16 12 14 15 10 15 8 12 2 12"/><path d="M5.45 5.11 2 12v6a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2v-6l-3.45-6.89A2 2 0 0 0 16.76 4H7.24a2 2 0 0 0-1.79 1.11z"/>',
        // Two whole figures, not one and a stub. The half-person form that
        // most icon sets use for "users" loses its second figure at 15px and
        // reads as one person with a smudge beside them.
        'users'    => '<circle cx="8.5" cy="8.5" r="3.2"/><circle cx="16" cy="8.5" r="3.2"/><path d="M3 19.5a5.5 5.5 0 0 1 11 0"/><path d="M10 19.5a5.5 5.5 0 0 1 11 0"/>',
        'tag'      => '<path d="M20.59 13.41 13.42 20.6a2 2 0 0 1-2.83 0L2 12V2h10l8.59 8.59a2 2 0 0 1 0 2.82z"/><line x1="7" y1="7" x2="7.01" y2="7"/>',
        'box'      => '<path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/><polyline points="3.27 6.96 12 12.01 20.73 6.96"/>',
        'alert'    => '<path d="M10.29 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/>',
        'dollar'   => '<line x1="12" y1="3" x2="12" y2="21"/><path d="M16.5 7H10a3 3 0 0 0 0 6h4a3 3 0 0 1 0 6H7"/>',
        'star'     => '<polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/>',
        'bolt'     => '<polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"/>',
        'trend'    => '<polyline points="21 7 13 15 9 11 3 17"/><polyline points="15.5 7 21 7 21 12.5"/>',
        'pin'      => '<path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/>',
    ];
    $pdPath = $pdIcons[$name ?? ''] ?? $pdIcons['bolt'];
@endphp
<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">{!! $pdPath !!}</svg>
