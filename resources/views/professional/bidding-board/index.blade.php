@extends('layouts.professional')

@section('title', 'Main Bidding Board')
@section('page-title', 'Main Bidding Board')
@section('page-subtitle', 'Find the perfect gigs and place your best bids')

{{-- Professional — Main Bidding Board. Every open client gig in one place,
     filterable by request type (SSR / MSR / ESR), with AI match-scores, live
     time-left and market insights. Gigs are representative pending the live
     gig/bid pipeline. --}}

@push('styles')
<style>
    .bb { --bb: #e11d48; --bb-strong: #be123c; }
    .bb-grid { display: grid; grid-template-columns: minmax(0,1fr) 300px; gap: 20px; align-items: start; }

    /* filter tabs */
    .bb-tabs { display: flex; gap: 8px; flex-wrap: wrap; margin-bottom: 16px; }
    .bb-tab { display: inline-flex; align-items: center; gap: 7px; border: 1px solid var(--border-color); background: var(--bg-card); border-radius: 999px; padding: 8px 15px; font-size: 13px; font-weight: 700; color: var(--text-secondary); cursor: pointer; }
    .bb-tab.on { background: var(--bb); border-color: var(--bb); color: #fff; }
    .bb-tab .n { font-size: 11px; font-weight: 800; opacity: .8; }

    /* gig card */
    .bb-card { display: grid; grid-template-columns: 150px minmax(0,1fr); gap: 0; background: var(--bg-card); border: 1px solid var(--border-color); border-radius: 16px; overflow: hidden; margin-bottom: 14px; }
    .bb-media { position: relative; background: var(--bg-card-hover, var(--border-color)); }
    .bb-media img { width: 100%; height: 100%; min-height: 150px; object-fit: cover; display: block; }
    .bb-type { position: absolute; left: 8px; top: 8px; font-size: 10px; font-weight: 800; letter-spacing: .3px; padding: 3px 9px; border-radius: 6px; color: #fff; }
    .bb-type.ESR { background: #e11d48; } .bb-type.SSR { background: #2563eb; } .bb-type.MSR { background: #7c3aed; }
    .bb-body { padding: 14px 16px; display: flex; flex-direction: column; }
    .bb-top { display: flex; align-items: flex-start; gap: 10px; }
    .bb-title { font-size: 15.5px; font-weight: 800; color: var(--text-primary); }
    .bb-urgent { font-size: 10px; font-weight: 800; color: #c2410c; background: rgba(234,88,12,.14); padding: 2px 8px; border-radius: 999px; }
    .bb-match { margin-left: auto; width: 52px; height: 52px; border-radius: 50%; flex-shrink: 0; display: flex; flex-direction: column; align-items: center; justify-content: center; color: #fff; font-weight: 800; }
    .bb-match b { font-size: 14px; line-height: 1; } .bb-match span { font-size: 7.5px; letter-spacing: .3px; opacity: .9; }
    .bb-desc { font-size: 12.5px; color: var(--text-muted); line-height: 1.5; margin: 7px 0; }
    .bb-meta { display: flex; flex-wrap: wrap; gap: 12px; font-size: 11.5px; color: var(--text-secondary); margin-bottom: 8px; }
    .bb-meta span { display: inline-flex; align-items: center; gap: 4px; }
    .bb-tags { display: flex; flex-wrap: wrap; gap: 5px; margin-bottom: 10px; }
    .bb-tagx { font-size: 10.5px; font-weight: 600; color: var(--text-muted); background: var(--bg-card-hover, rgba(125,125,125,.08)); border: 1px solid var(--border-color); border-radius: 6px; padding: 2px 8px; }
    .bb-foot { display: flex; align-items: center; gap: 14px; margin-top: auto; padding-top: 10px; border-top: 1px solid var(--border-color); flex-wrap: wrap; }
    .bb-budget b { font-size: 15px; font-weight: 800; color: var(--text-primary); }
    .bb-budget span, .bb-time span { display: block; font-size: 10.5px; color: var(--text-muted); }
    .bb-time b { font-size: 13.5px; font-weight: 800; color: #c2410c; font-variant-numeric: tabular-nums; }
    .bb-actions { margin-left: auto; display: flex; align-items: center; gap: 8px; }
    .bb-ghost { border: 1px solid var(--border-color); background: var(--bg-card); border-radius: 9px; width: 34px; height: 34px; display: inline-flex; align-items: center; justify-content: center; cursor: pointer; color: var(--text-muted); }
    .bb-ghost svg { width: 15px; height: 15px; }
    .bb-bid { border: none; border-radius: 10px; padding: 10px 20px; font-size: 13px; font-weight: 800; color: #fff; background: linear-gradient(135deg, var(--bb), var(--bb-strong)); cursor: pointer; }

    /* sidebar */
    .bb-rail { position: sticky; top: 84px; display: flex; flex-direction: column; gap: 16px; }
    .bb-rail-card { background: var(--bg-card); border: 1px solid var(--border-color); border-radius: 16px; padding: 15px; }
    .bb-rail-card h4 { font-size: 13.5px; font-weight: 800; color: var(--text-primary); margin-bottom: 12px; }
    .bb-ins { display: flex; align-items: center; gap: 10px; padding: 8px 0; border-bottom: 1px dashed var(--border-color); }
    .bb-ins:last-child { border-bottom: none; }
    .bb-ins .e { font-size: 17px; }
    .bb-ins-main span { font-size: 11px; color: var(--text-muted); }
    .bb-ins-main h6 { font-size: 12.5px; font-weight: 800; color: var(--text-primary); }
    .bb-frow { margin-bottom: 11px; }
    .bb-frow label { display: block; font-size: 11.5px; font-weight: 700; color: var(--text-secondary); margin-bottom: 5px; }
    .bb-frow select, .bb-frow input { width: 100%; border: 1px solid var(--border-color); border-radius: 9px; padding: 8px 10px; font-size: 12.5px; color: var(--text-primary); background: var(--bg-card); font-family: inherit; }
    .bb-apply { width: 100%; border: none; border-radius: 10px; padding: 10px; font-size: 13px; font-weight: 800; color: #fff; background: linear-gradient(135deg, var(--bb), var(--bb-strong)); cursor: pointer; margin-top: 4px; }
    .bb-sealed { background: linear-gradient(135deg, rgba(225,29,72,.1), rgba(124,58,237,.08)); border: 1px solid var(--border-color); }
    .bb-sealed h4 { display: flex; align-items: center; gap: 7px; }
    .bb-sealed p { font-size: 11.5px; color: var(--text-muted); line-height: 1.5; margin-bottom: 10px; }
    .bb-sealed a { font-size: 12px; font-weight: 800; color: var(--bb); text-decoration: none; }

    @media (max-width: 1080px) { .bb-grid { grid-template-columns: minmax(0,1fr); } .bb-rail { position: static; } }
    @media (max-width: 620px) { .bb-card { grid-template-columns: minmax(0,1fr); } }
</style>
@endpush

@section('content')
<div class="bb">
    {{-- Filter tabs --}}
    <div class="bb-tabs">
        <span class="bb-tab on">All Gigs <span class="n">{{ $counts['all'] }}</span></span>
        <span class="bb-tab">🔥 ESR <span class="n">{{ $counts['ESR'] }}</span></span>
        <span class="bb-tab">SSR <span class="n">{{ $counts['SSR'] }}</span></span>
        <span class="bb-tab">MSR <span class="n">{{ $counts['MSR'] }}</span></span>
        <span class="bb-tab">Invite Only</span>
        <span class="bb-tab">★ Bookmarked</span>
    </div>

    <div class="bb-grid">
        {{-- Gigs --}}
        <div class="bb-list">
            @foreach($gigs as $g)
                @php $mc = $g['match'] >= 90 ? '#16a34a' : ($g['match'] >= 80 ? '#65a30d' : '#d97706'); @endphp
                <article class="bb-card">
                    <div class="bb-media">
                        <span class="bb-type {{ $g['type'] }}">{{ $g['type'] }}</span>
                        <img src="https://images.unsplash.com/{{ $g['img'] }}?w=320&q=70&auto=format&fit=crop" alt="" loading="lazy">
                    </div>
                    <div class="bb-body">
                        <div class="bb-top">
                            <div>
                                <div class="bb-title">{{ $g['title'] }} @if($g['urgent'])<span class="bb-urgent">URGENT</span>@endif</div>
                            </div>
                            <span class="bb-match" style="background: {{ $mc }};"><b>{{ $g['match'] }}%</b><span>MATCH</span></span>
                        </div>
                        <p class="bb-desc">{{ $g['desc'] }}</p>
                        <div class="bb-meta">
                            <span>📍 {{ $g['loc'] }}</span>
                            <span>📅 {{ $g['date'] }}</span>
                            @if($g['guests'])<span>👥 {{ $g['guests'] }}</span>@endif
                        </div>
                        <div class="bb-tags">
                            @foreach($g['tags'] as $t)<span class="bb-tagx">{{ $t }}</span>@endforeach
                        </div>
                        <div class="bb-foot">
                            <div class="bb-budget"><b>{{ $g['budget'] }}</b><span>Budget</span></div>
                            <div class="bb-time"><b>{{ $g['time'] }}</b><span>Time left</span></div>
                            <div class="bb-actions">
                                <button class="bb-ghost" title="Save"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 21l-7-5-7 5V5a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2z"/></svg></button>
                                <button class="bb-ghost" title="Share"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="18" cy="5" r="3"/><circle cx="6" cy="12" r="3"/><circle cx="18" cy="19" r="3"/><line x1="8.6" y1="13.5" x2="15.4" y2="17.5"/><line x1="15.4" y1="6.5" x2="8.6" y2="10.5"/></svg></button>
                                <button class="bb-bid">Place Bid</button>
                            </div>
                        </div>
                    </div>
                </article>
            @endforeach
            <div style="text-align:center; padding:8px;"><button class="bb-tab" style="margin:0 auto;">Load More Gigs ↓</button></div>
        </div>

        {{-- Sidebar --}}
        <aside class="bb-rail">
            <div class="bb-rail-card">
                <h4>📊 Market Insights</h4>
                @foreach($insights as [$label, $val, $emoji])
                    <div class="bb-ins">
                        <span class="e">{{ $emoji }}</span>
                        <div class="bb-ins-main"><span>{{ $label }}</span><h6>{{ $val }}</h6></div>
                    </div>
                @endforeach
            </div>

            <div class="bb-rail-card">
                <h4>Filters</h4>
                <div class="bb-frow"><label>Request Type</label><select><option>All Types</option><option>ESR — Emergency</option><option>SSR — Single</option><option>MSR — Multiple</option></select></div>
                <div class="bb-frow"><label>Category</label><select><option>All Categories</option><option>Photography</option><option>DJ &amp; Music</option><option>Catering</option><option>Décor</option></select></div>
                <div class="bb-frow"><label>Location</label><input placeholder="City or state"></div>
                <div class="bb-frow"><label>Budget Range</label><input type="number" placeholder="Min $"></div>
                <button class="bb-apply">Apply Filters</button>
            </div>

            <div class="bb-rail-card bb-sealed">
                <h4>🔒 Try Sealed Bidding</h4>
                <p>Hide competitor bids and get more quality proposals — your bid stays private until the client reviews.</p>
            </div>
        </aside>
    </div>
</div>
@endsection
