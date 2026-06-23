@props(['color' => '#f97316', 'icon' => 'star', 'size' => 64])
@php
    $glyphs = [
        'leaf'   => '<path d="M11 20A7 7 0 0 1 9.8 6.1C15.5 5 17 4.48 19 2c1 2 2 4.18 2 8 0 5.5-4.78 10-10 10z" fill="#fff"/><path d="M2 21c0-3 1.85-5.36 5.08-6" stroke="#fff" stroke-width="2" fill="none" stroke-linecap="round"/>',
        'star'   => '<polygon points="16 7 17.8 12.4 23.5 12.5 19 16 20.6 21.5 16 18.2 11.4 21.5 13 16 8.5 12.5 14.2 12.4" fill="#fff" transform="translate(-4 -4)"/>',
        'gem'    => '<path d="M6 9h12l-6 9z" fill="#fff"/><path d="M6 9l2-3h8l2 3" fill="none" stroke="#fff" stroke-width="1.6" stroke-linejoin="round"/>',
        'crown'  => '<path d="M5 17h14l1-9-4 3-4-6-4 6-4-3z" fill="#fff"/>',
        'trophy' => '<path d="M8 21h8M12 17v4M7 5h10v4a5 5 0 0 1-10 0z" fill="none" stroke="#fff" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>',
    ];
    $g = $glyphs[$icon] ?? $glyphs['star'];
    $s = (int) $size;
@endphp
<svg width="{{ $s }}" height="{{ $s }}" viewBox="0 0 100 100" fill="none" xmlns="http://www.w3.org/2000/svg" {{ $attributes }}>
    <defs>
        <linearGradient id="hx{{ $icon }}{{ $s }}" x1="20" y1="8" x2="80" y2="92">
            <stop stop-color="{{ $color }}" stop-opacity="0.92"/>
            <stop offset="1" stop-color="{{ $color }}"/>
        </linearGradient>
    </defs>
    {{-- depth --}}
    <polygon points="50,12 84.6,32 84.6,72 50,92 15.4,72 15.4,32" fill="{{ $color }}" opacity="0.35" transform="translate(0 4)"/>
    {{-- main hexagon --}}
    <polygon points="50,8 84.6,28 84.6,68 50,88 15.4,68 15.4,28" fill="url(#hx{{ $icon }}{{ $s }})" stroke="{{ $color }}" stroke-width="2" stroke-linejoin="round"/>
    {{-- inset highlight --}}
    <polygon points="50,16 77.6,32 77.6,50 50,40 22.4,50 22.4,32" fill="#fff" opacity="0.18"/>
    {{-- glyph --}}
    <g transform="translate(34 30) scale(1.35)">{!! $g !!}</g>
</svg>
