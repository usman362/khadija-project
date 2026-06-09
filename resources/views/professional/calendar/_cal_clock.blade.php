{{-- Reusable 3D calendar + clock illustration (blue, professional theme).
     Param: $w = render width in px (height auto from viewBox). --}}
@php $w = $w ?? 120; @endphp
<svg viewBox="0 0 120 112" width="{{ $w }}" height="auto" fill="none" xmlns="http://www.w3.org/2000/svg" style="display:block;">
    <defs>
        <linearGradient id="ccBody{{ $w }}" x1="30" y1="20" x2="108" y2="96"><stop stop-color="#3b82f6"/><stop offset="1" stop-color="#1d4ed8"/></linearGradient>
        <linearGradient id="ccFace{{ $w }}" x1="34" y1="30" x2="100" y2="92"><stop stop-color="#eff6ff"/><stop offset="1" stop-color="#dbeafe"/></linearGradient>
    </defs>
    {{-- sparkles --}}
    <path d="M16 20l1.6 4.4 4.4 1.6-4.4 1.6L16 32l-1.6-4.4L10 26l4.4-1.6z" fill="#60a5fa"/>
    <path d="M104 14l1.2 3.4 3.4 1.2-3.4 1.2L104 24l-1.2-3.4L99.4 19.8l3.4-1.2z" fill="#93c5fd"/>
    <path d="M112 70l1 2.6 2.6 1-2.6 1L112 78l-1-2.6-2.6-1 2.6-1z" fill="#3b82f6"/>

    {{-- calendar body --}}
    <rect x="32" y="28" width="74" height="68" rx="10" fill="url(#ccBody{{ $w }})"/>
    <rect x="38" y="40" width="62" height="50" rx="6" fill="url(#ccFace{{ $w }})"/>
    {{-- header band --}}
    <rect x="38" y="40" width="62" height="13" rx="6" fill="#2563eb"/>
    <rect x="38" y="48" width="62" height="5" fill="#2563eb"/>
    {{-- binding rings --}}
    <rect x="46" y="22" width="5" height="16" rx="2.5" fill="#1e40af"/>
    <rect x="66" y="22" width="5" height="16" rx="2.5" fill="#1e40af"/>
    <rect x="86" y="22" width="5" height="16" rx="2.5" fill="#1e40af"/>
    {{-- grid dots --}}
    @php $gx = [46,57,68,79,90]; $gy = [62,72,82]; @endphp
    @foreach($gy as $yy)@foreach($gx as $xx)<rect x="{{ $xx }}" y="{{ $yy }}" width="7" height="6" rx="1.5" fill="#bfdbfe"/>@endforeach @endforeach
    {{-- a marked day --}}
    <rect x="57" y="72" width="7" height="6" rx="1.5" fill="#2563eb"/>

    {{-- clock overlapping bottom-left --}}
    <circle cx="34" cy="78" r="22" fill="#1d4ed8"/>
    <circle cx="34" cy="78" r="18" fill="#fff"/>
    <circle cx="34" cy="78" r="18" fill="none" stroke="#2563eb" stroke-width="2"/>
    <line x1="34" y1="78" x2="34" y2="67" stroke="#1e293b" stroke-width="2.4" stroke-linecap="round"/>
    <line x1="34" y1="78" x2="42" y2="82" stroke="#2563eb" stroke-width="2.4" stroke-linecap="round"/>
    <circle cx="34" cy="78" r="2.2" fill="#1e293b"/>
    @for($i = 0; $i < 12; $i++)
        @php $a = deg2rad($i * 30); $r1 = 15.5; $cx = 34 + sin($a) * $r1; $cy = 78 - cos($a) * $r1; @endphp
        <circle cx="{{ round($cx, 1) }}" cy="{{ round($cy, 1) }}" r="0.9" fill="#94a3b8"/>
    @endfor
</svg>
