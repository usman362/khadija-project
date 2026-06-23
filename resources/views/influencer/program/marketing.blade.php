@extends('layouts.influencer-portal')
@section('title', 'Marketing Center')
@push('styles') @include('influencer.program._styles') @endpush

@php
    $banners = [
        ['Leaderboard Banner', '728 × 90', '#2563eb', 'Great for blog headers and website tops.'],
        ['Square Post', '1080 × 1080', '#f97316', 'Perfect for Instagram & Facebook feeds.'],
        ['Story / Reel', '1080 × 1920', '#7c3aed', 'Vertical format for Stories and Reels.'],
    ];
    $swipes = [
        ['Instagram / Facebook', "Planning an event? I use GigResource to find trusted event professionals in one place. Check it out and book with confidence 👉 {$referralUrl}"],
        ['Short & Punchy', "Find your perfect event pro on GigResource — quick, simple, reliable. Start here: {$referralUrl}"],
        ['Email / Newsletter', "Hi! If you're organising an event, I'd recommend GigResource for discovering and booking event professionals. You can explore it using my link: {$referralUrl}"],
    ];
@endphp

@section('content')
<div class="pg-head"><h1>Marketing Center</h1><p>Ready-to-use assets and copy to help you promote your referral link.</p></div>

<div class="pg-panel" style="margin:20px 0 18px;">
    <h3>Your Referral Link</h3>
    <p class="sub">Add this link to any asset or post below.</p>
    <div class="pg-linkbox">
        <code id="mkLink">{{ $referralUrl }}</code>
        <button class="pg-copy" data-copy="#mkLink">Copy Link</button>
    </div>
</div>

<div class="pg-panel" style="margin-bottom:18px;">
    <h3>Promotional Banners</h3>
    <p class="sub">Branded graphics in the most-used social and web sizes.</p>
    <div class="pg-assets">
        @foreach($banners as [$name, $size, $color, $desc])
            <div class="pg-asset">
                <div class="prev" style="background:linear-gradient(135deg,{{ $color }},{{ $color }}cc);">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></svg>
                    <span class="sz">{{ $size }}</span>
                </div>
                <div class="meta">
                    <b>{{ $name }}</b>
                    <p>{{ $desc }}</p>
                    <div class="act"><button class="pg-btn-sm solid">Download</button><button class="pg-btn-sm">Preview</button></div>
                </div>
            </div>
        @endforeach
    </div>
</div>

<div class="pg-grid two">
    <div class="pg-panel">
        <h3>Swipe Copy</h3>
        <p class="sub">Pre-written posts with your link already added — copy and share.</p>
        @foreach($swipes as $i => [$label, $text])
            <div class="pg-swipe">
                <div class="cap"><b>{{ $label }}</b><button class="pg-copy" style="padding:6px 12px; font-size:11.5px;" data-copy="#swipe{{ $i }}">Copy</button></div>
                <p id="swipe{{ $i }}">{{ $text }}</p>
            </div>
        @endforeach
    </div>

    <div class="pg-panel">
        <h3>Brand Assets</h3>
        <p class="sub">Logos and brand guidelines for on-brand promotion.</p>
        @foreach([['GigResource Logo (PNG)', 'Full-colour logo on transparent background', '#0f172a'],['Logo Mark', 'Icon-only mark for avatars & favicons', '#f97316'],['Brand Guidelines', 'Colours, fonts and usage rules (PDF)', '#2563eb']] as [$t,$d,$c])
            <div style="display:flex; align-items:center; gap:12px; padding:12px 0; border-bottom:1px solid var(--line);">
                <span style="width:40px; height:40px; border-radius:10px; background:{{ $c }}1a; color:{{ $c }}; display:flex; align-items:center; justify-content:center;"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg></span>
                <div style="flex:1;"><b style="font-family:var(--ff); font-size:13px; color:var(--ink); display:block;">{{ $t }}</b><span style="font-size:11.5px; color:var(--muted);">{{ $d }}</span></div>
                <button class="pg-btn-sm" style="flex:0 0 auto; padding:7px 14px;">Get</button>
            </div>
        @endforeach

        <div class="pg-note">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12"/><line x1="12" y1="8" x2="12.01" y2="8"/></svg>
            <span>Keep your promotion honest — describe GigResource as a marketplace to discover and book event professionals. Avoid guarantees or specific outcome claims.</span>
        </div>
    </div>
</div>

@include('influencer.program._copy_js')
@endsection
