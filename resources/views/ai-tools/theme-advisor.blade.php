@extends($aiLayout ?? 'layouts.client')

@section('title', 'AI Theme & Style Advisor')
@section('page-title', 'AI Theme & Style Advisor')
@section('page-subtitle', 'Cohesive themes, palettes & mood boards for your event')

{{-- AI Theme & Style Advisor (client). AI-generated themes + colour palette +
     mood board + category filters. Representative data. --}}

@push('styles')
<style>
    .ta { --ta: #7c3aed; --ta-strong: #6d28d9; }
    .ta-sec { background: var(--bg-card); border: 1px solid var(--border-color); border-radius: 16px; padding: 18px; margin-bottom: 18px; }
    .ta-sec > h3 { font-size: 15px; font-weight: 800; color: var(--text-primary); margin-bottom: 14px; }

    .ta-themes { display: grid; grid-template-columns: repeat(3, 1fr); gap: 14px; }
    .ta-theme { border: 1px solid var(--border-color); border-radius: 14px; overflow: hidden; background: var(--bg-card); }
    .ta-theme.best { border-color: var(--ta); box-shadow: 0 0 0 1px var(--ta); }
    .ta-img { position: relative; height: 130px; }
    .ta-img img { width: 100%; height: 100%; object-fit: cover; }
    .ta-best { position: absolute; left: 8px; top: 8px; font-size: 10px; font-weight: 800; color: #fff; background: var(--ta); padding: 3px 9px; border-radius: 999px; }
    .ta-match { position: absolute; right: 8px; top: 8px; font-size: 11px; font-weight: 800; color: #fff; background: rgba(22,163,74,.92); padding: 3px 9px; border-radius: 999px; }
    .ta-body { padding: 13px; }
    .ta-body h4 { font-size: 14.5px; font-weight: 800; color: var(--text-primary); }
    .ta-body p { font-size: 12px; color: var(--text-muted); line-height: 1.5; margin: 6px 0 10px; }
    .ta-sw { display: flex; gap: 5px; margin-bottom: 12px; }
    .ta-sw i { width: 26px; height: 26px; border-radius: 7px; border: 1px solid rgba(0,0,0,.1); }
    .ta-acts { display: flex; gap: 8px; }
    .ta-btn { flex: 1; text-align: center; font-size: 12px; font-weight: 800; border-radius: 9px; padding: 9px; cursor: pointer; border: 1px solid var(--border-color); background: var(--bg-card); color: var(--text-secondary); }
    .ta-btn.primary { border: none; background: linear-gradient(135deg, var(--ta), var(--ta-strong)); color: #fff; }

    .ta-palette { display: grid; grid-template-columns: repeat(4, 1fr); gap: 12px; }
    .ta-pal { border: 1px solid var(--border-color); border-radius: 12px; overflow: hidden; }
    .ta-pal-sw { height: 56px; }
    .ta-pal-info { padding: 9px 11px; }
    .ta-pal-info .role { font-size: 10.5px; font-weight: 800; color: var(--text-muted); text-transform: uppercase; letter-spacing: .3px; }
    .ta-pal-info h5 { font-size: 13px; font-weight: 800; color: var(--text-primary); }
    .ta-pal-info code { font-size: 11px; color: var(--text-muted); }

    .ta-cats { display: flex; gap: 8px; flex-wrap: wrap; margin-bottom: 13px; }
    .ta-cat { font-size: 12px; font-weight: 700; border: 1px solid var(--border-color); border-radius: 999px; padding: 6px 13px; color: var(--text-secondary); cursor: pointer; background: var(--bg-card); }
    .ta-cat.on { background: var(--ta); border-color: var(--ta); color: #fff; }
    .ta-mood { display: grid; grid-template-columns: repeat(6, 1fr); gap: 8px; }
    .ta-mood img { width: 100%; height: 90px; object-fit: cover; border-radius: 10px; }

    @media (max-width: 1000px) { .ta-themes, .ta-palette { grid-template-columns: 1fr 1fr; } .ta-mood { grid-template-columns: repeat(3,1fr); } }
    @media (max-width: 620px) { .ta-themes, .ta-palette { grid-template-columns: 1fr; } }
</style>
@endpush

@section('content')
<div class="ta">
    {{-- Themes --}}
    <div class="ta-sec">
        <h3>✨ Your AI-Generated Themes</h3>
        <div class="ta-themes">
            @foreach($themes as [$name, $match, $desc, $img, $sw, $best])
                <div class="ta-theme {{ $best ? 'best' : '' }}">
                    <div class="ta-img">
                        @if($best)<span class="ta-best">★ Best Match</span>@endif
                        <span class="ta-match">{{ $match }}% Match</span>
                        <img src="https://images.unsplash.com/{{ $img }}?w=420&q=70&auto=format&fit=crop" alt="{{ $name }}" loading="lazy">
                    </div>
                    <div class="ta-body">
                        <h4>{{ $name }}</h4>
                        <p>{{ $desc }}</p>
                        <div class="ta-sw">@foreach($sw as $c)<i style="background: {{ $c }};"></i>@endforeach</div>
                        <div class="ta-acts">
                            <span class="ta-btn">View Details</span>
                            <span class="ta-btn primary">Select Theme</span>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    {{-- Palette --}}
    <div class="ta-sec">
        <h3>🎨 Recommended Color Palette</h3>
        <div class="ta-palette">
            @foreach($palette as [$role, $hex, $label])
                <div class="ta-pal">
                    <div class="ta-pal-sw" style="background: {{ $hex }};"></div>
                    <div class="ta-pal-info"><div class="role">{{ $role }}</div><h5>{{ $label }}</h5><code>{{ $hex }}</code></div>
                </div>
            @endforeach
        </div>
    </div>

    {{-- Mood board --}}
    <div class="ta-sec">
        <h3>🖼 Mood Board</h3>
        <div class="ta-cats">
            @foreach($categories as $i => $c)<span class="ta-cat {{ $i===0 ? 'on' : '' }}">{{ $c }}</span>@endforeach
        </div>
        <div class="ta-mood">
            @foreach($moodboard as $m)<img src="https://images.unsplash.com/{{ $m }}?w=240&q=65&auto=format&fit=crop" alt="" loading="lazy">@endforeach
        </div>
    </div>
</div>
@endsection
