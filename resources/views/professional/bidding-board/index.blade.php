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

    /* top bar: tabs + sort */
    .bb-bar { display: flex; align-items: center; gap: 12px; flex-wrap: wrap; margin-bottom: 16px; }
    .bb-tabs { display: flex; gap: 8px; flex-wrap: wrap; }
    .bb-tab { display: inline-flex; align-items: center; gap: 7px; border: 1px solid var(--border-color); background: var(--bg-card); border-radius: 999px; padding: 7px 15px; font-size: 13px; font-weight: 700; color: var(--text-secondary); cursor: pointer; }
    .bb-tab.on { background: var(--bb); border-color: var(--bb); color: #fff; }
    .bb-tab .n { font-size: 11px; font-weight: 800; opacity: .8; }
    .bb-tab .sub { font-size: 9.5px; font-weight: 700; letter-spacing: .2px; opacity: .65; margin-left: 2px; }
    .bb-sort { margin-left: auto; display: inline-flex; align-items: center; gap: 8px; font-size: 12px; font-weight: 700; color: var(--text-secondary); }
    .bb-sort select { border: 1px solid var(--border-color); border-radius: 9px; padding: 7px 10px; font-size: 12.5px; font-weight: 700; color: var(--text-primary); background: var(--bg-card); font-family: inherit; cursor: pointer; }

    /* gig card — horizontal row: media | main | stats | actions */
    .bb-card { display: grid; grid-template-columns: 128px minmax(0,1fr) 172px 120px; gap: 0; background: var(--bg-card); border: 1px solid var(--border-color); border-radius: 16px; overflow: hidden; margin-bottom: 14px; }
    .bb-media { position: relative; background: var(--bg-card-hover, var(--border-color)); }
    .bb-media img { width: 100%; height: 100%; object-fit: cover; display: block; }
    .bb-type { position: absolute; left: 8px; top: 8px; font-size: 10px; font-weight: 800; letter-spacing: .3px; padding: 3px 9px; border-radius: 6px; color: #fff !important; }
    .bb-type.ESR { background: #e11d48; } .bb-type.SSR { background: #2563eb; } .bb-type.MSR { background: #7c3aed; }

    .bb-main { padding: 14px 16px; display: flex; flex-direction: column; min-width: 0; }
    .bb-top { display: flex; align-items: baseline; flex-wrap: wrap; gap: 8px; }
    .bb-title { font-size: 15.5px; font-weight: 800; color: var(--text-primary); }
    .bb-bidsn { font-size: 12px; font-weight: 700; color: var(--text-muted); }
    .bb-bidsn::before { content: "•"; margin: 0 7px; color: var(--border-color); }
    .bb-urgent { font-size: 10px; font-weight: 800; color: #fff; background: var(--bb); padding: 2px 8px; border-radius: 999px; }
    .bb-desc { font-size: 12.5px; color: var(--text-muted); line-height: 1.45; margin: 6px 0; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; }
    .bb-meta { display: flex; flex-wrap: wrap; gap: 12px; font-size: 11.5px; color: var(--text-secondary); margin-bottom: 8px; }
    .bb-meta span { display: inline-flex; align-items: center; gap: 4px; }
    .bb-tags { display: flex; flex-wrap: wrap; gap: 5px; margin-top: auto; }
    .bb-tagx { font-size: 10.5px; font-weight: 600; color: var(--text-muted); background: var(--bg-card-hover, rgba(125,125,125,.08)); border: 1px solid var(--border-color); border-radius: 6px; padding: 2px 8px; }

    /* stats column */
    .bb-stats { padding: 13px 14px; border-left: 1px solid var(--border-color); display: flex; flex-direction: column; gap: 11px; justify-content: center; }
    .bb-stat-row { display: flex; gap: 16px; }
    .bb-stat span { display: block; font-size: 9.5px; font-weight: 700; letter-spacing: .3px; text-transform: uppercase; color: var(--text-muted); margin-bottom: 2px; }
    .bb-stat b { font-size: 13.5px; font-weight: 800; color: var(--text-primary); white-space: nowrap; }
    .bb-stat.t b { color: #c2410c; font-variant-numeric: tabular-nums; }
    .bb-ring { display: flex; align-items: center; gap: 9px; }
    .bb-match { width: 44px; height: 44px; border-radius: 50%; display: flex; flex-direction: column; align-items: center; justify-content: center; color: #fff !important; font-weight: 800; flex-shrink: 0; }
    .bb-match b, .bb-match em { color: #fff !important; }
    .bb-match b { font-size: 12.5px; line-height: 1; } .bb-match em { font-size: 6.5px; font-style: normal; letter-spacing: .3px; opacity: .9; }
    .bb-ring-txt { display: flex; flex-direction: column; gap: 2px; }
    .bb-score-lbl { font-size: 9.5px; font-weight: 800; letter-spacing: .3px; text-transform: uppercase; }
    .bb-stars { font-size: 11px; letter-spacing: .5px; color: #f59e0b; line-height: 1; }
    .bb-stars i { color: var(--border-color); font-style: normal; }

    /* actions column */
    .bb-actions { padding: 14px 12px; border-left: 1px solid var(--border-color); display: flex; flex-direction: column; gap: 8px; justify-content: center; }
    .bb-bid { border: none; border-radius: 10px; padding: 10px 14px; font-size: 13px; font-weight: 800; color: #fff; background: linear-gradient(135deg, var(--bb), var(--bb-strong)); cursor: pointer; }
    .bb-ob { display: inline-flex; align-items: center; justify-content: center; gap: 6px; border: 1px solid var(--border-color); background: var(--bg-card); border-radius: 10px; padding: 8px 12px; font-size: 12.5px; font-weight: 800; color: var(--text-secondary); cursor: pointer; }
    .bb-ob svg { width: 14px; height: 14px; }

    /* sidebar */
    .bb-rail { position: sticky; top: 84px; display: flex; flex-direction: column; gap: 16px; }
    .bb-rail-card { background: var(--bg-card); border: 1px solid var(--border-color); border-radius: 16px; padding: 15px; }
    .bb-rail-head { display: flex; align-items: center; gap: 8px; margin-bottom: 12px; }
    .bb-rail-head h4 { font-size: 13.5px; font-weight: 800; color: var(--text-primary); }
    .bb-live { display: inline-flex; align-items: center; gap: 4px; font-size: 10px; font-weight: 800; color: #16a34a; background: rgba(22,163,74,.12); padding: 2px 8px; border-radius: 999px; }
    .bb-live b { font-size: 8px; line-height: 1; }
    .bb-clear { margin-left: auto; font-size: 11.5px; font-weight: 700; color: var(--bb); background: none; border: none; cursor: pointer; text-decoration: none; }
    .bb-ins { display: flex; align-items: center; gap: 10px; padding: 8px 0; border-bottom: 1px dashed var(--border-color); }
    .bb-ins:last-child { border-bottom: none; }
    .bb-ins .e { font-size: 17px; }
    .bb-ins-main span { font-size: 11px; color: var(--text-muted); }
    .bb-ins-main h6 { font-size: 12.5px; font-weight: 800; color: var(--text-primary); }
    .bb-viewins { width: 100%; margin-top: 12px; border: 1px solid var(--border-color); border-radius: 10px; padding: 9px; font-size: 12.5px; font-weight: 800; color: var(--text-secondary); background: var(--bg-card); cursor: pointer; }
    .bb-frow { margin-bottom: 11px; }
    .bb-frow label { display: block; font-size: 11.5px; font-weight: 700; color: var(--text-secondary); margin-bottom: 5px; }
    .bb-frow select, .bb-frow input { width: 100%; border: 1px solid var(--border-color); border-radius: 9px; padding: 8px 10px; font-size: 12.5px; color: var(--text-primary); background: var(--bg-card); font-family: inherit; }
    .bb-apply { width: 100%; border: none; border-radius: 10px; padding: 10px; font-size: 13px; font-weight: 800; color: #fff; background: linear-gradient(135deg, var(--bb), var(--bb-strong)); cursor: pointer; margin-top: 4px; }
    .bb-chk { display: flex; align-items: center; gap: 8px; font-size: 12px; font-weight: 600; color: var(--text-secondary); margin-bottom: 9px; cursor: pointer; }
    .bb-chk input { width: 15px; height: 15px; accent-color: var(--bb); margin: 0; flex-shrink: 0; }
    .bb-save { width: 100%; border: 1px solid var(--border-color); border-radius: 10px; padding: 9px; font-size: 12.5px; font-weight: 800; color: var(--bb); background: var(--bg-card); cursor: pointer; margin-top: 8px; }
    .bb-sealed { background: linear-gradient(135deg, rgba(225,29,72,.1), rgba(124,58,237,.08)); border: 1px solid var(--border-color); }
    .bb-sealed h4 { display: flex; align-items: center; gap: 7px; }
    .bb-sealed p { font-size: 11.5px; color: var(--text-muted); line-height: 1.5; margin-bottom: 10px; }
    .bb-sealed a { font-size: 12px; font-weight: 800; color: var(--bb); text-decoration: none; }

    @media (max-width: 1080px) { .bb-grid { grid-template-columns: minmax(0,1fr); } .bb-rail { position: static; } }
    @media (max-width: 720px) {
        .bb-card { grid-template-columns: minmax(0,1fr); }
        .bb-media img { min-height: 120px; }
        .bb-stats { border-left: none; border-top: 1px solid var(--border-color); flex-direction: row; justify-content: space-around; flex-wrap: wrap; }
        .bb-actions { border-left: none; border-top: 1px solid var(--border-color); }
    }
</style>
@endpush

@section('content')
<div class="bb">
    {{-- Top bar: filter tabs + sort --}}
    <div class="bb-bar">
        <div class="bb-tabs">
            <span class="bb-tab on">All Gigs <span class="n">{{ $counts['all'] }}</span></span>
            <span class="bb-tab">🔥 ESR <span class="sub">(Emergency)</span> <span class="n">{{ $counts['ESR'] }}</span></span>
            <span class="bb-tab">SSR <span class="sub">(Single Service)</span> <span class="n">{{ $counts['SSR'] }}</span></span>
            <span class="bb-tab">MSR <span class="sub">(Multi-Service)</span> <span class="n">{{ $counts['MSR'] }}</span></span>
            <span class="bb-tab">Invite Only</span>
            <span class="bb-tab">★ Bookmarked</span>
        </div>
        <div class="bb-sort">
            <label for="bb-sort-sel">Sort by:</label>
            <select id="bb-sort-sel">
                <option>Recommended</option>
                <option>Newest</option>
                <option>Budget: High to Low</option>
                <option>Closing Soon</option>
            </select>
        </div>
    </div>

    <div class="bb-grid">
        {{-- Gigs --}}
        <div class="bb-list">
            @foreach($gigs as $g)
                @php
                    $mc = $g['match'] >= 90 ? '#16a34a' : ($g['match'] >= 80 ? '#65a30d' : '#d97706');
                    $ml = $g['match'] >= 90 ? 'Excellent' : ($g['match'] >= 80 ? 'Great' : 'Good');
                    $rf = (int) round($g['rating']);
                @endphp
                <article class="bb-card">
                    <div class="bb-media">
                        <span class="bb-type {{ $g['type'] }}">{{ $g['type'] }}</span>
                        <img src="https://images.unsplash.com/{{ $g['img'] }}?w=320&q=70&auto=format&fit=crop" alt="" loading="lazy">
                    </div>

                    <div class="bb-main">
                        <div class="bb-top">
                            <span class="bb-title">{{ $g['title'] }}</span>
                            <span class="bb-bidsn">{{ $g['bids'] }} Bids</span>
                            @if($g['urgent'])<span class="bb-urgent">Urgent</span>@endif
                        </div>
                        <p class="bb-desc">{{ $g['desc'] }}</p>
                        <div class="bb-meta">
                            <span>📍 {{ $g['loc'] }}</span>
                            <span>📅 {{ $g['date'] }}</span>
                            @if($g['guests'])<span>👥 {{ $g['guests'] }} Guests</span>@endif
                            <span>🏠 Indoor</span>
                        </div>
                        <div class="bb-tags">
                            @foreach($g['tags'] as $t)<span class="bb-tagx">{{ $t }}</span>@endforeach
                        </div>
                    </div>

                    <div class="bb-stats">
                        <div class="bb-stat"><span>Budget</span><b>{{ $g['budget'] }}</b></div>
                        <div class="bb-stat t">
                            <span>Time Left</span>
                            @if($g['urgent'])<b data-countdown="6300">--:--:--</b>@else<b>{{ $g['time'] }}</b>@endif
                        </div>
                        <div class="bb-ring">
                            <span class="bb-match" style="background: {{ $mc }};"><b>{{ $g['match'] }}%</b><em>MATCH</em></span>
                            <div class="bb-ring-txt">
                                <span class="bb-score-lbl" style="color: {{ $mc }};">{{ $ml }}</span>
                                <span class="bb-stars">@for($i = 1; $i <= 5; $i++){!! $i <= $rf ? '★' : '<i>★</i>' !!}@endfor</span>
                            </div>
                        </div>
                    </div>

                    <div class="bb-actions">
                        <button class="bb-bid">Place Bid</button>
                        <button class="bb-ob"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20.8 4.6a5.5 5.5 0 0 0-7.8 0L12 5.7l-1-1.1a5.5 5.5 0 0 0-7.8 7.8L12 21l8.8-8.6a5.5 5.5 0 0 0 0-7.8z"/></svg>Save</button>
                        <button class="bb-ob"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="18" cy="5" r="3"/><circle cx="6" cy="12" r="3"/><circle cx="18" cy="19" r="3"/><line x1="8.6" y1="13.5" x2="15.4" y2="17.5"/><line x1="15.4" y1="6.5" x2="8.6" y2="10.5"/></svg>Share</button>
                    </div>
                </article>
            @endforeach
            <div style="text-align:center; padding:8px;"><button class="bb-tab" style="margin:0 auto;">Load More Gigs ↓</button></div>
        </div>

        {{-- Sidebar --}}
        <aside class="bb-rail">
            <div class="bb-rail-card">
                <div class="bb-rail-head">
                    <h4>📊 Market Insights</h4>
                    <span class="bb-live"><b>●</b> Live</span>
                </div>
                @foreach($insights as [$label, $val, $emoji])
                    <div class="bb-ins">
                        <span class="e">{{ $emoji }}</span>
                        <div class="bb-ins-main"><span>{{ $label }}</span><h6>{{ $val }}</h6></div>
                    </div>
                @endforeach
                <button class="bb-viewins">View Full Market Insights</button>
            </div>

            <div class="bb-rail-card">
                <div class="bb-rail-head">
                    <h4>Filters</h4>
                    <button class="bb-clear" type="button">Clear All</button>
                </div>
                <div class="bb-frow"><label>Request Type</label><select><option>All Types</option><option>ESR — Emergency</option><option>SSR — Single</option><option>MSR — Multiple</option></select></div>
                <div class="bb-frow"><label>Category</label><select><option>All Categories</option><option>Photography</option><option>DJ &amp; Music</option><option>Catering</option><option>Décor</option></select></div>
                <div class="bb-frow"><label>Location</label><input placeholder="City or state"></div>
                <div class="bb-frow"><label>Budget Range</label><input type="number" placeholder="Min $"></div>
                <label class="bb-chk"><input type="checkbox" checked> Open Now</label>
                <label class="bb-chk"><input type="checkbox"> Ending Soon</label>
                <label class="bb-chk"><input type="checkbox"> High Match (80%+)</label>
                <button class="bb-apply">Apply Filters</button>
                <button class="bb-save">♡ Save Search</button>
            </div>

            <div class="bb-rail-card bb-sealed">
                <h4>🔒 Try Sealed Bidding</h4>
                <p>Hide competitor bids and get more quality proposals — your bid stays private until the client reviews.</p>
            </div>
        </aside>
    </div>
</div>

@push('scripts')
<script>
    (function () {
        var els = document.querySelectorAll('[data-countdown]');
        if (!els.length) return;
        function pad(n) { return (n < 10 ? '0' : '') + n; }
        function fmt(s) {
            var h = Math.floor(s / 3600);
            var m = Math.floor((s % 3600) / 60);
            var sec = s % 60;
            return pad(h) + ':' + pad(m) + ':' + pad(sec);
        }
        var timers = [];
        els.forEach(function (el) {
            timers.push(parseInt(el.getAttribute('data-countdown'), 10) || 0);
        });
        function tick() {
            els.forEach(function (el, i) {
                if (timers[i] > 0) { timers[i]--; }
                el.textContent = fmt(timers[i]);
            });
        }
        tick();
        setInterval(tick, 1000);
    })();
</script>
@endpush
@endsection
