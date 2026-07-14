@extends('layouts.client')

@section('title', 'Browse Packages')
@section('page-title', 'Browse Packages')
@section('page-subtitle', 'Browse professional service packages and book the right team')

{{-- Client — Find Gigs. The client-side mirror of the professional bidding board:
     browse professional GIG LISTINGS (service packages), filter by type / category /
     location / budget, and book or message. Each card is backed by a real supplier
     (name + live review average); gig titles, prices, locations and images are
     representative until pros publish structured gig listings. --}}

@push('styles')
<style>
    .fg { --fg: var(--accent-orange, #f97316); --fg-strong: #ea580c; }
    .fg-grid { display: grid; grid-template-columns: minmax(0,1fr) 300px; gap: 20px; align-items: start; }

    /* top bar: tabs + sort */
    .fg-bar { display: flex; align-items: center; gap: 12px; flex-wrap: wrap; margin-bottom: 16px; }
    .fg-tabs { display: flex; gap: 8px; flex-wrap: wrap; }
    .fg-tab { display: inline-flex; align-items: center; gap: 7px; border: 1px solid var(--border-color); background: var(--bg-card); border-radius: 999px; padding: 7px 15px; font-size: 13px; font-weight: 700; color: var(--text-secondary); cursor: pointer; text-decoration: none; }
    .fg-tab.on { background: var(--fg); border-color: var(--fg); color: #fff; }
    .fg-tab .n { font-size: 11px; font-weight: 800; opacity: .85; }
    .fg-tab .sub { font-size: 9.5px; font-weight: 700; letter-spacing: .2px; opacity: .7; margin-left: 2px; }
    .fg-sort { margin-left: auto; display: inline-flex; align-items: center; gap: 8px; font-size: 12px; font-weight: 700; color: var(--text-secondary); }
    .fg-sort select { border: 1px solid var(--border-color); border-radius: 9px; padding: 7px 10px; font-size: 12.5px; font-weight: 700; color: var(--text-primary); background: var(--bg-card); font-family: inherit; cursor: pointer; }

    /* gig card — horizontal row: media | main | stats | actions */
    .fg-card { display: grid; grid-template-columns: 128px minmax(0,1fr) 176px 128px; gap: 0; background: var(--bg-card); border: 1px solid var(--border-color); border-radius: 16px; overflow: hidden; margin-bottom: 14px; }
    .fg-media { position: relative; background: var(--bg-card-hover, var(--border-color)); }
    .fg-media img { width: 100%; height: 100%; object-fit: cover; display: block; }
    .fg-type { position: absolute; left: 8px; top: 8px; font-size: 10px; font-weight: 800; letter-spacing: .3px; padding: 3px 9px; border-radius: 6px; color: #fff !important; }
    .fg-type.ESR { background: #e11d48; } .fg-type.SSR { background: #2563eb; } .fg-type.MSR { background: #7c3aed; }

    .fg-main { padding: 14px 16px; display: flex; flex-direction: column; min-width: 0; }
    .fg-top { display: flex; align-items: baseline; flex-wrap: wrap; gap: 8px; }
    .fg-title { font-size: 15.5px; font-weight: 800; color: var(--text-primary); }
    .fg-revn { font-size: 12px; font-weight: 700; color: var(--text-muted); }
    .fg-revn::before { content: "•"; margin: 0 7px; color: var(--border-color); }
    .fg-featured { font-size: 10px; font-weight: 800; color: #fff; background: var(--fg); padding: 2px 8px; border-radius: 999px; }
    .fg-pro { font-size: 12px; font-weight: 700; color: var(--fg-strong); margin: 3px 0 0; display: inline-flex; align-items: center; gap: 5px; }
    .fg-pro svg { width: 13px; height: 13px; }
    .fg-desc { font-size: 12.5px; color: var(--text-muted); line-height: 1.45; margin: 6px 0; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; }
    .fg-meta { display: flex; flex-wrap: wrap; gap: 12px; font-size: 11.5px; color: var(--text-secondary); margin-bottom: 8px; }
    .fg-meta span { display: inline-flex; align-items: center; gap: 4px; }
    .fg-tags { display: flex; flex-wrap: wrap; gap: 5px; margin-top: auto; }
    .fg-tagx { font-size: 10.5px; font-weight: 600; color: var(--text-muted); background: var(--bg-card-hover, rgba(125,125,125,.08)); border: 1px solid var(--border-color); border-radius: 6px; padding: 2px 8px; }

    /* stats column */
    .fg-stats { padding: 13px 14px; border-left: 1px solid var(--border-color); display: flex; flex-direction: column; gap: 11px; justify-content: center; }
    .fg-stat span { display: block; font-size: 9.5px; font-weight: 700; letter-spacing: .3px; text-transform: uppercase; color: var(--text-muted); margin-bottom: 2px; }
    .fg-stat b { font-size: 13.5px; font-weight: 800; color: var(--text-primary); white-space: nowrap; }
    .fg-stat .from { color: var(--fg-strong); }
    .fg-ring { display: flex; align-items: center; gap: 9px; }
    .fg-score { width: 44px; height: 44px; border-radius: 50%; display: flex; flex-direction: column; align-items: center; justify-content: center; color: #fff !important; font-weight: 800; flex-shrink: 0; background: #f59e0b; }
    .fg-score b { font-size: 12.5px; line-height: 1; color: #fff !important; } .fg-score em { font-size: 6.5px; font-style: normal; letter-spacing: .3px; opacity: .95; color: #fff !important; }
    .fg-ring-txt { display: flex; flex-direction: column; gap: 2px; }
    .fg-score-lbl { font-size: 9.5px; font-weight: 800; letter-spacing: .3px; text-transform: uppercase; color: #d97706; }
    .fg-stars { font-size: 11px; letter-spacing: .5px; color: #f59e0b; line-height: 1; }
    .fg-stars i { color: var(--border-color); font-style: normal; }

    /* actions column */
    .fg-actions { padding: 14px 12px; border-left: 1px solid var(--border-color); display: flex; flex-direction: column; gap: 8px; justify-content: center; }
    .fg-book { border: none; border-radius: 10px; padding: 10px 14px; font-size: 13px; font-weight: 800; color: #fff; background: linear-gradient(135deg, var(--fg), var(--fg-strong)); cursor: pointer; text-align: center; text-decoration: none; }
    .fg-ob { display: inline-flex; align-items: center; justify-content: center; gap: 6px; border: 1px solid var(--border-color); background: var(--bg-card); border-radius: 10px; padding: 8px 12px; font-size: 12.5px; font-weight: 800; color: var(--text-secondary); cursor: pointer; text-decoration: none; }
    .fg-ob svg { width: 14px; height: 14px; }

    /* sidebar */
    .fg-rail { position: sticky; top: 84px; display: flex; flex-direction: column; gap: 16px; }
    .fg-rail-card { background: var(--bg-card); border: 1px solid var(--border-color); border-radius: 16px; padding: 15px; }
    .fg-rail-head { display: flex; align-items: center; gap: 8px; margin-bottom: 12px; }
    .fg-rail-head h4 { font-size: 13.5px; font-weight: 800; color: var(--text-primary); }
    .fg-live { display: inline-flex; align-items: center; gap: 4px; font-size: 10px; font-weight: 800; color: #16a34a; background: rgba(22,163,74,.12); padding: 2px 8px; border-radius: 999px; }
    .fg-live b { font-size: 8px; line-height: 1; }
    .fg-clear { margin-left: auto; font-size: 11.5px; font-weight: 700; color: var(--fg-strong); background: none; border: none; cursor: pointer; text-decoration: none; }
    .fg-ins { display: flex; align-items: center; gap: 10px; padding: 8px 0; border-bottom: 1px dashed var(--border-color); }
    .fg-ins:last-child { border-bottom: none; }
    .fg-ins .e { font-size: 17px; }
    .fg-ins-main span { font-size: 11px; color: var(--text-muted); }
    .fg-ins-main h6 { font-size: 12.5px; font-weight: 800; color: var(--text-primary); }
    .fg-frow { margin-bottom: 11px; }
    .fg-frow label { display: block; font-size: 11.5px; font-weight: 700; color: var(--text-secondary); margin-bottom: 5px; }
    .fg-frow select, .fg-frow input { width: 100%; border: 1px solid var(--border-color); border-radius: 9px; padding: 8px 10px; font-size: 12.5px; color: var(--text-primary); background: var(--bg-card); font-family: inherit; }
    .fg-apply { width: 100%; border: none; border-radius: 10px; padding: 10px; font-size: 13px; font-weight: 800; color: #fff; background: linear-gradient(135deg, var(--fg), var(--fg-strong)); cursor: pointer; margin-top: 4px; }
    .fg-cta { background: linear-gradient(135deg, rgba(249,115,22,.1), rgba(236,72,153,.06)); border: 1px solid var(--border-color); }
    .fg-cta h4 { display: flex; align-items: center; gap: 7px; }
    .fg-cta p { font-size: 11.5px; color: var(--text-muted); line-height: 1.5; margin: 8px 0 10px; }
    .fg-cta a { display: inline-block; font-size: 12.5px; font-weight: 800; color: #fff; background: linear-gradient(135deg, var(--fg), var(--fg-strong)); border-radius: 10px; padding: 9px 14px; text-decoration: none; }
    .fg-empty { background: var(--bg-card); border: 1px dashed var(--border-color); border-radius: 16px; padding: 40px; text-align: center; color: var(--text-muted); font-size: 13px; }

    @media (max-width: 1080px) { .fg-grid { grid-template-columns: minmax(0,1fr); } .fg-rail { position: static; } }
    @media (max-width: 720px) {
        .fg-card { grid-template-columns: minmax(0,1fr); }
        .fg-media img { min-height: 120px; }
        .fg-stats { border-left: none; border-top: 1px solid var(--border-color); flex-direction: row; justify-content: space-around; flex-wrap: wrap; }
        .fg-actions { border-left: none; border-top: 1px solid var(--border-color); }
    }
</style>
@endpush

@section('content')
<div class="fg">
    @php $f = $filters; @endphp

    {{-- Top bar: type tabs (links) + sort --}}
    <div class="fg-bar">
        <div class="fg-tabs">
            <a href="{{ route('client.find-gigs.index', array_merge(request()->except(['type','page']), [])) }}" class="fg-tab {{ $f['type'] === '' ? 'on' : '' }}">All Packages <span class="n">{{ $counts['all'] }}</span></a>
            <a href="{{ route('client.find-gigs.index', array_merge(request()->except('page'), ['type' => 'ESR'])) }}" class="fg-tab {{ $f['type'] === 'ESR' ? 'on' : '' }}">🔥 ESR <span class="sub">(Emergency Service Request)</span> <span class="n">{{ $counts['ESR'] }}</span></a>
            <a href="{{ route('client.find-gigs.index', array_merge(request()->except('page'), ['type' => 'SSR'])) }}" class="fg-tab {{ $f['type'] === 'SSR' ? 'on' : '' }}">SSR <span class="sub">(Single Service Request)</span> <span class="n">{{ $counts['SSR'] }}</span></a>
            <a href="{{ route('client.find-gigs.index', array_merge(request()->except('page'), ['type' => 'MSR'])) }}" class="fg-tab {{ $f['type'] === 'MSR' ? 'on' : '' }}">MSR <span class="sub">(Multi-Service Request)</span> <span class="n">{{ $counts['MSR'] }}</span></a>
        </div>
        <form class="fg-sort" method="GET" action="{{ route('client.find-gigs.index') }}">
            @foreach(request()->except(['sort','page']) as $k => $v)<input type="hidden" name="{{ $k }}" value="{{ $v }}">@endforeach
            <label for="fg-sort-sel">Sort by:</label>
            <select id="fg-sort-sel" name="sort" onchange="this.form.submit()">
                <option value="recommended" @selected($f['sort']==='recommended')>Recommended</option>
                <option value="rating" @selected($f['sort']==='rating')>Top Rated</option>
                <option value="price_low" @selected($f['sort']==='price_low')>Price: Low to High</option>
                <option value="price_high" @selected($f['sort']==='price_high')>Price: High to Low</option>
            </select>
        </form>
    </div>

    <div class="fg-grid">
        {{-- Gigs --}}
        <div class="fg-list">
            @forelse($gigs as $g)
                @php $rf = (int) round($g['rating']); @endphp
                <article class="fg-card">
                    <div class="fg-media">
                        <span class="fg-type {{ $g['type'] }}">{{ $g['type'] }}</span>
                        <img src="{{ $g['img_url'] ?? ('https://images.unsplash.com/' . $g['img'] . '?w=320&q=70&auto=format&fit=crop') }}" alt="{{ $g['title'] }}" loading="lazy">
                    </div>

                    <div class="fg-main">
                        <div class="fg-top">
                            <span class="fg-title">{{ $g['title'] }}</span>
                            <span class="fg-revn">{{ $g['reviews'] }} reviews</span>
                            @if($g['featured'])<span class="fg-featured">Featured</span>@endif
                        </div>
                        <div class="fg-pro">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                            {{ $g['pro'] }}
                        </div>
                        <p class="fg-desc">{{ $g['desc'] }}</p>
                        <div class="fg-meta">
                            <span>📍 {{ $g['loc'] }}</span>
                            <span>🏷️ {{ $g['cat'] }}</span>
                        </div>
                        <div class="fg-tags">
                            <span class="fg-tagx">{{ $g['cat'] }}</span>
                            <span class="fg-tagx">{{ $g['type'] === 'MSR' ? 'Multi-Service' : ($g['type'] === 'ESR' ? 'Emergency' : 'Single Service') }}</span>
                        </div>
                    </div>

                    <div class="fg-stats">
                        <div class="fg-stat"><span>Starting At</span><b class="from">{{ $g['from'] }}</b></div>
                        <div class="fg-stat"><span>Price Range</span><b>{{ $g['price'] }}</b></div>
                        <div class="fg-ring">
                            <span class="fg-score"><b>{{ $g['rating'] ? number_format($g['rating'], 1) : '—' }}</b><em>RATING</em></span>
                            <div class="fg-ring-txt">
                                <span class="fg-score-lbl">{{ !$g['rating'] ? 'New' : ($g['rating'] >= 4.8 ? 'Top Rated' : 'Great') }}</span>
                                <span class="fg-stars">@for($i = 1; $i <= 5; $i++){!! $i <= $rf ? '★' : '<i>★</i>' !!}@endfor</span>
                            </div>
                        </div>
                    </div>

                    <div class="fg-actions">
                        <a class="fg-book" href="{{ $g['detail_url'] ?? route('client.search.index', ['q' => $g['cat']]) }}">View Package</a>
                        <a class="fg-ob" href="{{ route('client.chat.index') }}"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>Message</a>
                        <button class="fg-ob" type="button"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20.8 4.6a5.5 5.5 0 0 0-7.8 0L12 5.7l-1-1.1a5.5 5.5 0 0 0-7.8 7.8L12 21l8.8-8.6a5.5 5.5 0 0 0 0-7.8z"/></svg>Save</button>
                    </div>
                </article>
            @empty
                <div class="fg-empty">No packages match your filters. <a href="{{ route('client.find-gigs.index') }}" style="color:var(--fg-strong);font-weight:700;">Clear filters</a> to see all packages.</div>
            @endforelse
        </div>

        {{-- Sidebar --}}
        <aside class="fg-rail">
            <div class="fg-rail-card">
                <div class="fg-rail-head">
                    <h4>📊 Popular Now</h4>
                    <span class="fg-live"><b>●</b> Live</span>
                </div>
                @foreach($insights as [$label, $val, $emoji])
                    <div class="fg-ins">
                        <span class="e">{{ $emoji }}</span>
                        <div class="fg-ins-main"><span>{{ $label }}</span><h6>{{ $val }}</h6></div>
                    </div>
                @endforeach
            </div>

            <form class="fg-rail-card" method="GET" action="{{ route('client.find-gigs.index') }}">
                <div class="fg-rail-head">
                    <h4>Filters</h4>
                    <a class="fg-clear" href="{{ route('client.find-gigs.index') }}">Clear All</a>
                </div>
                <div class="fg-frow">
                    <label>Service Type</label>
                    <select name="type">
                        <option value="">All Types</option>
                        <option value="SSR" @selected($f['type']==='SSR')>SSR — Single Service Request</option>
                        <option value="MSR" @selected($f['type']==='MSR')>MSR — Multi-Service Request</option>
                        <option value="ESR" @selected($f['type']==='ESR')>ESR — Emergency Service Request</option>
                    </select>
                </div>
                <div class="fg-frow">
                    <label>Category</label>
                    <select name="category">
                        <option value="">All Categories</option>
                        @foreach($categories as $c)
                            <option value="{{ $c }}" @selected($f['catFilter']===$c)>{{ $c }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="fg-frow"><label>Location</label><input name="loc" value="{{ $f['loc'] }}" placeholder="City or state"></div>
                <div class="fg-frow"><label>Min Budget</label><input type="number" name="budget_min" value="{{ $f['budgetMin'] ?: '' }}" placeholder="Min $"></div>
                <button class="fg-apply" type="submit">Apply Filters</button>
            </form>

            <div class="fg-rail-card fg-cta">
                <h4>📣 Can't find the right package?</h4>
                <p>Post your event and let verified professionals send you tailored proposals.</p>
                <a href="{{ route('client.post-event.event-info') }}">Post an Event</a>
            </div>
        </aside>
    </div>
</div>
@endsection
