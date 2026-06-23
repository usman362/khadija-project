@extends('layouts.influencer-portal')
@section('title', 'Resource Library')
@push('styles') @include('influencer.resources._styles') @endpush

@php
    $typeMeta = [
        'guide'     => ['#2563eb', '<path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"/>'],
        'video'     => ['#ef4444', '<polygon points="5 3 19 12 5 21 5 3"/>'],
        'template'  => ['#7c3aed', '<rect x="3" y="3" width="18" height="18" rx="2"/><line x1="3" y1="9" x2="21" y2="9"/><line x1="9" y1="21" x2="9" y2="9"/>'],
        'checklist' => ['#16a34a', '<path d="M9 11l3 3L22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/>'],
        'tool'      => ['#f97316', '<path d="M14.7 6.3a4 4 0 0 0-5.4 5.4L3 18l3 3 6.3-6.3a4 4 0 0 0 5.4-5.4l-2.6 2.6-2-2 2.6-2.6z"/>'],
        'webinar'   => ['#0891b2', '<rect x="2" y="3" width="20" height="14" rx="2"/><line x1="8" y1="21" x2="16" y2="21"/><line x1="12" y1="17" x2="12" y2="21"/>'],
        'article'   => ['#d97706', '<path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/>'],
    ];
    $tm = fn($t) => $typeMeta[$t] ?? $typeMeta['guide'];
    $fmt = fn($n) => $n >= 1000 ? round($n/1000,1).'K' : number_format($n);
    $typeColors = ['guide'=>'#2563eb','video'=>'#ef4444','template'=>'#7c3aed','checklist'=>'#16a34a','tool'=>'#f97316','webinar'=>'#0891b2','article'=>'#d97706'];
    // types donut
    $acc=0; $stops=[]; foreach($byType as $t=>$c){ $pct=$total?round($c/$total*100):0; $stops[]=($typeColors[$t]??'#999').' '.$acc.'% '.($acc+$pct).'%'; $acc+=$pct; }
@endphp

@section('content')
<div class="rs-hero">
    <div>
        <h2>Resource Library 📚</h2>
        <p>Your one-stop hub for guides, templates, videos, and tips to grow your influence and earnings.</p>
        <a href="{{ route('influencer.resources.academy') }}" class="cta">Explore the Academy <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg></a>
    </div>
    <div class="rs-search"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg><input type="text" placeholder="Search resources..."></div>
</div>

<div class="rs-tabs">
    <span class="rs-tab active">All Resources</span>
    @foreach(['guide'=>'Guides','video'=>'Videos','template'=>'Templates','checklist'=>'Checklists','tool'=>'Tools','webinar'=>'Webinars','article'=>'Articles'] as $t=>$lbl)
        <span class="rs-tab">{{ $lbl }}</span>
    @endforeach
</div>

<div class="rs-section-head"><h3>Featured Resources</h3></div>
<div class="rs-featured">
    @foreach($featured as $r)
        @php [$c,$glyph] = $tm($r->type); @endphp        <div class="rs-card">
            <div class="rs-card-top" style="background:linear-gradient(135deg,{{ $c }},{{ $c }}cc);">
                @if($r->badge)<span class="rs-card-badge">{{ $r->badge }}</span>@endif
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">{!! $glyph !!}</svg>
            </div>
            <div class="rs-card-body">
                <b>{{ $r->title }}</b>
                <p>{{ $r->description }}</p>
                <span class="rs-card-cta">{{ $r->type === 'video' ? 'Watch' : ($r->type === 'webinar' ? 'Register' : ($r->type === 'template' ? 'Download' : 'Read')) }}</span>
            </div>
        </div>
    @endforeach
</div>

<div class="rs-grid">
    <div class="rs-panel">
        <div class="rs-section-head" style="margin-top:0;"><h3>Popular Resources</h3></div>
        @foreach($popular as $r)
            @php [$c,$glyph] = $tm($r->type); @endphp            <div class="rs-list-row">
                <span class="rs-list-ic" style="background:{{ $c }}1a; color:{{ $c }};"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">{!! $glyph !!}</svg></span>
                <div class="m"><b>{{ $r->title }}</b><span>{{ $r->type }}</span></div>
                <div class="meta"><svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg> {{ $fmt($r->downloads) }}</div>
            </div>
        @endforeach
    </div>
    <div class="rs-panel">
        <h3>Resource Types</h3>
        <div class="rs-donut" style="background: conic-gradient({{ implode(',', $stops) }});"><div class="rs-donut-c"><b>{{ $total }}</b><span>resources</span></div></div>
        <div class="rs-types-legend">
            @foreach($byType as $t => $c)
                <div class="row"><span class="dot" style="background:{{ $typeColors[$t] ?? '#999' }};"></span><span class="nm">{{ $t }}s</span><span class="pc">{{ $c }}</span></div>
            @endforeach
        </div>
    </div>
    <div class="rs-panel">
        <h3>Recently Added</h3>
        @foreach($recent as $r)
            @php [$c,$glyph] = $tm($r->type); @endphp            <div class="rs-list-row">
                <span class="rs-list-ic" style="background:{{ $c }}1a; color:{{ $c }};"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">{!! $glyph !!}</svg></span>
                <div class="m"><b style="font-size:12.5px;">{{ $r->title }}</b><span>{{ $r->published_at?->format('M j') }}</span></div>
            </div>
        @endforeach
    </div>
</div>
@endsection
