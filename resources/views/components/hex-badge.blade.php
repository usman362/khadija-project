{{--
    A badge, as a hexagon.

    Sir Peter, 2026-09-09: "we are now and only using the hexagon style badges
    across the users, but whatever colors, shades, icons within them is
    Khadijah's decision."

    So the SHAPE is fixed here — one component, so a second badge style cannot
    appear somewhere later — and everything inside it is passed in. The colours
    and icons for the client badges live in config/badges.php, which is where
    Khadijah's choices go without a code change.

    @props
      icon    what sits in the middle (an emoji, or a character)
      label   the badge's name, shown under it
      colour  the crest colour; the shade below it is derived, so one value
              per badge is all anyone has to choose
      earned  false draws it unfilled — the same badge, not yet won
      title   the tooltip, usually what earns it
--}}
@props([
    'icon'   => '★',
    'label'  => null,
    'colour' => '#f97316',
    'earned' => true,
    'title'  => null,
    'size'   => 58,
])

@once
@push('styles')
<style>
    .hexb { display: inline-flex; flex-direction: column; align-items: center; gap: 6px; width: max-content; }
    .hexb-crest {
        display: flex; align-items: center; justify-content: center;
        /* The same polygon the client tier crest already uses, so the two do
           not disagree about what a hexagon is on this site. */
        clip-path: polygon(50% 0%, 100% 14%, 100% 62%, 50% 100%, 0 62%, 0 14%);
        line-height: 1;
    }
    .hexb-crest span { font-style: normal; }
    .hexb-label { font-size: 11px; font-weight: 700; color: var(--text-primary, #111827);
        text-align: center; max-width: 84px; line-height: 1.25; }

    /* Not yet earned: the same crest, drained. Greyed rather than hidden, so a
       client can see what there is to win. */
    .hexb.is-locked .hexb-crest { filter: grayscale(1); opacity: .38; }
    .hexb.is-locked .hexb-label { color: var(--text-muted, #6b7280); font-weight: 600; }
</style>
@endpush
@endonce

<span class="hexb {{ $earned ? '' : 'is-locked' }}" @if($title) title="{{ $title }}" @endif>
    <span class="hexb-crest"
          style="width: {{ $size }}px; height: {{ round($size * 1.14) }}px;
                 font-size: {{ round($size * 0.42) }}px;
                 background: linear-gradient(160deg, {{ $colour }} 0%, color-mix(in srgb, {{ $colour }} 62%, #0f172a) 100%);
                 box-shadow: inset 0 0 0 2px color-mix(in srgb, #fff 42%, {{ $colour }});">
        <span>{{ $icon }}</span>
    </span>
    @if($label)<span class="hexb-label">{{ $label }}</span>@endif
</span>
