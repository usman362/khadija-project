@extends('layouts.client')
@section('title', 'Post an Event — Choose How to Request')
@section('page-title', 'How do you want to request?')
@section('page-subtitle', 'Pick the path that fits your event. You can always start another request later.')
@include('client.post-event._styles')

{{-- Step 0 — Choose Route. Packages are ONE route of five, not the whole
     product (Fix Spec 07.14). Package Search is the synchronous "buy a bundle"
     wizard; SSR/MSR/ER are postings that end at Publish then take bids on
     Proposals; Direct Request is a targeted, non-bidding invite. --}}

@push('styles')
<style>
    /* Two full rows of three, with a rail beside them.
       It was four across and a fifth card alone on its own line, with the
       right half of the page empty — the shape Khadijah replaced on 27 Aug. */
    /* The rail ends level with the cards.
       `align-items:start` let it size to its own content, so it finished
       above or below the second row depending on how many postings there
       were. Stretching it to the row height keeps the two columns square,
       and the list scrolls inside if there are more postings than fit. */
    /* Two rows named explicitly — the cards, then the hint card. `grid-row:
       1 / -1` on the rail counts to the last EXPLICIT line, so without these
       the span collapsed to row 1 and the rail stopped at the cards. */
    .rc-layout { display:grid; grid-template-columns:minmax(0,1fr) 300px; grid-template-rows:auto auto;
        gap:20px; align-items:stretch; }
    @media (max-width:1180px) { .rc-layout { grid-template-columns:1fr; grid-template-rows:none; } }

    .rc-grid { display:grid; grid-template-columns:repeat(3,minmax(0,1fr)); gap:16px; }
    @media (max-width:1400px) { .rc-grid { grid-template-columns:repeat(2,minmax(0,1fr)); } }
    @media (max-width:640px)  { .rc-grid { grid-template-columns:1fr; } }

    /* What other clients are posting. Real requests — see the controller for
       what is deliberately left out of them. */
    /* The rail runs the full height of the left column — cards AND the hint
       card under them — and ends level with it.

       Two things had to be true at once. It must reach the bottom of the
       hint card, and it must not be what decides how tall anything is: with
       the rail in the normal flow, six postings made the row 605px and
       stretched the cards to match, leaving dead space inside them. So the
       slot spans both rows and the rail sits absolutely inside it — it fills
       whatever the left column needs and its list scrolls if there is more to
       show than fits. */
    .rc-rail-slot { grid-column:2; grid-row:1 / -1; position:relative; }
    .rc-rail { position:absolute; inset:0; background:var(--pe-card); border:1px solid var(--pe-line);
        border-radius:16px; padding:18px; display:flex; flex-direction:column; min-height:0; }
    @media (max-width:1180px) {
        .rc-rail-slot { grid-column:1; grid-row:auto; min-height:0; }
        .rc-rail { position:static; }
    }
    /* The postings scroll; the heading and the footnote stay put. */
    .rc-rail-list { flex:1; min-height:0; overflow-y:auto; }
    .rc-rail-h { display:flex; align-items:center; gap:8px; font-size:12px; font-weight:800; letter-spacing:.4px;
        text-transform:uppercase; color:var(--pe-purple); margin-bottom:4px; }
    .rc-rail-h svg { width:15px; height:15px; }
    .rc-rail-sub { font-size:12px; color:var(--pe-muted); margin:0 0 14px; line-height:1.5; }
    .rc-post { display:flex; gap:10px; align-items:flex-start; padding:11px 0; border-top:1px solid var(--pe-line); }
    .rc-post:first-of-type { border-top:0; padding-top:0; }
    .rc-post-ic { width:30px; height:30px; border-radius:9px; background:var(--pe-purple-l); color:var(--pe-purple);
        display:flex; align-items:center; justify-content:center; flex-shrink:0; }
    .rc-post-ic svg { width:15px; height:15px; }
    .rc-post-b { min-width:0; flex:1; }
    .rc-post-t { display:block; font-size:13px; font-weight:700; color:var(--pe-text,#1f2937); line-height:1.35; }
    .rc-post-s { display:block; font-size:11.5px; color:var(--pe-muted); margin-top:3px; line-height:1.4; }
    .rc-post-w { font-size:11px; color:var(--pe-muted); white-space:nowrap; flex-shrink:0; padding-top:2px; }
    .rc-rail-foot { flex-shrink:0; font-size:11px; color:var(--pe-muted); margin:12px 0 0; padding-top:11px; border-top:1px solid var(--pe-line); }
    .rc-rail-empty { font-size:12.5px; color:var(--pe-muted); line-height:1.55; margin:0; }

    /* The closing hint. An icon, a heading, the text, and a picture — the
       shape in Khadijah's design, rather than a loose paragraph in a box. */
    /* Column 1, row 2 — under the cards, not under the rail. Which is what
       keeps the rail level with the cards rather than with the whole column. */
    .rc-note { grid-column:1; margin:0; display:flex; align-items:center; gap:16px;
        background:#fffaf3; border:1px solid #f3c98b; border-radius:14px; padding:16px 20px; }
    .rc-note-ic { width:38px; height:38px; border-radius:11px; flex-shrink:0; display:flex;
        align-items:center; justify-content:center; background:#fdecd3; color:var(--pe-orange-d); }
    .rc-note-ic svg { width:20px; height:20px; }
    .rc-note-b { flex:1; min-width:0; }
    .rc-note-b h4 { margin:0 0 4px; font-size:13.5px; font-weight:800; color:var(--pe-text,#1f2937); }
    .rc-note-b p { margin:0; font-size:12.5px; color:var(--pe-muted); line-height:1.65; }
    .rc-note-b b { color:var(--pe-orange-d); font-weight:700; }
    .rc-note-art { width:120px; flex-shrink:0; }
    @media (max-width:820px) { .rc-note-art { display:none; } }
    .rc-card { display:flex; flex-direction:column; gap:10px; background:var(--pe-card); border:1px solid var(--pe-line);
        border-radius:16px; padding:22px; text-decoration:none; color:inherit; position:relative;
        transition:border-color .15s, transform .15s, box-shadow .15s; }
    .rc-card:hover { border-color:var(--pe-orange); transform:translateY(-3px); box-shadow:0 12px 30px rgba(15,27,53,.08); }
    .rc-card.soon { opacity:.72; cursor:not-allowed; }
    .rc-card.soon:hover { transform:none; border-color:var(--pe-line); box-shadow:none; }
    .rc-ic { width:46px; height:46px; border-radius:12px; display:flex; align-items:center; justify-content:center;
        background:var(--pe-purple-l); color:var(--pe-purple); flex-shrink:0; }
    .rc-ic svg { width:24px; height:24px; }
    .rc-card h3 { font-size:16px; font-weight:800; margin:0; display:flex; align-items:center; gap:8px; }
    .rc-card p { font-size:13px; color:var(--pe-muted); margin:0; line-height:1.5; }
    .rc-tag { white-space:nowrap; font-size:10px; font-weight:800; letter-spacing:.4px; text-transform:uppercase; padding:3px 8px;
        border-radius:999px; background:var(--pe-line-2); color:var(--pe-muted); }
    .rc-tag.hot { background:#fff7ed; color:var(--pe-orange-d); }
    .rc-tag.soon { background:#eef2ff; color:#4f46e5; }
    .rc-foot { margin-top:auto; font-size:12.5px; font-weight:700; color:var(--pe-orange); display:flex; align-items:center; gap:6px; }
</style>
@endpush

@section('content')
<div class="pe-wrap">
    <div class="pe-container pe-main" style="padding-top:26px; padding-bottom:40px;">
        {{-- Title and subtitle are in the banner, like every other client
             screen — they used to be repeated here too, at a different size.
             See the note in the BR wizard. --}}

        @php
            $routes = [
                [
                    'href'  => route('public.packages'),
                    'tag'   => ['Ready-made', 'hot'],
                    'title' => 'Shop Packages',
                    'desc'  => 'Browse fixed service bundles from professionals and book instantly — one contract, one payment.',
                    'cta'   => 'Browse packages',
                    'icon'  => '<rect x="3" y="3" width="7" height="7" rx="1.5"/><rect x="14" y="3" width="7" height="7" rx="1.5"/><rect x="3" y="14" width="7" height="7" rx="1.5"/><rect x="14" y="14" width="7" height="7" rx="1.5"/>',
                ],
                // SSR and MSR were two cards for the same thing. They are the
                // SCOPE of a bidding request, not separate types — you pick one
                // service or several inside the form, exactly as ER already
                // works. One card (Peter, 2026-07-30). A Blade comment cannot go
                // here: inside @php it is not stripped and lands in the PHP.

                [
                    'href'  => route('client.bsr.step', 'service'),
                    'tag'   => ['Get bids', 'hot'],
                    'title' => 'Bidding Request (BR)',
                    'desc'  => 'Post one service or several — professionals bid on what they provide. Free to post; you only pay when you finalise.',
                    'cta'   => 'Start a request',
                    'icon'  => '<path d="M9 11l3 3L22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/>',
                ],
                [
                    'href'  => route('client.direct-offers.create'),
                    'tag'   => ['Targeted', ''],
                    'title' => 'Direct Request (DR)',
                    // What makes a DR a DR is that it is targeted, not broadcast —
                    // NOT that it is one service. A6 caps it at one professional per
                    // SERVICE at a time; a professional who offers several can be
                    // asked for several. The old wording implied otherwise.
                    'desc'  => 'Go straight to a professional you already want — one service or several, whatever they offer. They accept, decline, or reply. No open bidding.',
                    'cta'   => 'Send a Direct Request',
                    'icon'  => '<line x1="22" y1="2" x2="11" y2="13"/><polygon points="22 2 15 22 11 13 2 9 22 2"/>',
                ],
                [
                    'href'  => route('client.esr.create'),
                    'tag'   => ['Urgent', 'hot'],
                    'title' => 'Emergency Request (ER)',
                    'desc'  => 'Time-sensitive need within 72 hours — verified professionals are notified with priority so you get fast responses.',
                    'cta'   => 'Post a Rush Request',
                    'icon'  => '<path d="M10.29 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/>',
                ],
                [
                    'href'  => route('client.virtual-hub.brief'),
                    'tag'   => ['Online', ''],
                    'title' => 'Virtual & Hybrid Hub',
                    'desc'  => 'Search and connect with verified virtual professionals.',
                    'cta'   => 'Start a request',
                    'icon'  => '<path d="M23 7l-7 5 7 5V7z"/><rect x="1" y="5" width="15" height="14" rx="2"/>',
                ],
                [
                    'href'  => route('client.toolkit.plan'),
                    'tag'   => ['', ''],
                    'title' => 'Plan with Toolkit',
                    'desc'  => 'Not ready to post yet? Work out the shape of your event first, then turn it into a request.',
                    'cta'   => 'Open Toolkit',
                    'icon'  => '<path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"/>',
                ],
            ];
        @endphp

        <div class="rc-layout">
        <div class="rc-grid">
            @foreach($routes as $r)
                @php $soon = $r['href'] === null; @endphp
                <a href="{{ $r['href'] ?? '#' }}" class="rc-card {{ $soon ? 'soon' : '' }}" @if($soon) onclick="return false;" aria-disabled="true" @endif>
                    <div class="rc-ic"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">{!! $r['icon'] !!}</svg></div>
                    <h3>{{ $r['title'] }}
                        @if($r['tag'][0])<span class="rc-tag {{ $r['tag'][1] }}">{{ $r['tag'][0] }}</span>@endif
                    </h3>
                    <p>{{ $r['desc'] }}</p>
                    <span class="rc-foot">
                        {{ $r['cta'] }}
                        @unless($soon)<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg>@endunless
                    </span>
                </a>
            @endforeach
        </div>

        {{-- This line still offered "MSR" as a fifth choice after the cards became
     four. MSR is the scope inside a bidding request now, not a card you
     can pick, so pointing at it left the reader looking for something
     that is not on the screen. --}}
        


        {{-- What other clients are posting. Real published requests in this
             client's own state, newest first — the event, the services asked
             for, and how long ago. No names: a professional sees who posted
             because they are deciding whether to bid, and another client has
             no such reason. --}}
        <div class="rc-rail-slot">
        <aside class="rc-rail">
            <div class="rc-rail-h">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"><path d="M13 2 3 14h9l-1 8 10-12h-9l1-8z"/></svg>
                New client postings
            </div>
            <p class="rc-rail-sub">What other clients in your state are asking for.</p>

            <div class="rc-rail-list">
            @forelse($postings as $p)
                <div class="rc-post">
                    <span class="rc-post-ic">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                    </span>
                    <span class="rc-post-b">
                        <span class="rc-post-t">{{ $p['title'] }}</span>
                        @if($p['services'])<span class="rc-post-s">{{ $p['services'] }}</span>@endif
                    </span>
                    <span class="rc-post-w">{{ $p['when'] }}</span>
                </div>
            @empty
                <p class="rc-rail-empty">
                    Nothing posted in your state yet. Yours would be the first —
                    professionals here are notified as soon as it goes up.
                </p>
            @endforelse
            </div>

            {{-- NOT "updated in real-time". This list is as of the moment the
                 page loaded, and saying otherwise would be a claim the page
                 does not keep. --}}
            @if($postings->isNotEmpty())
                <p class="rc-rail-foot">As of now — reload to see newer ones.</p>
            @endif
        </aside>
        </div>{{-- /.rc-rail-slot --}}

        <div class="rc-note">
            <span class="rc-note-ic">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 18h6"/><path d="M10 22h4"/><path d="M12 2a7 7 0 0 0-4 12.7V17h8v-2.3A7 7 0 0 0 12 2z"/></svg>
            </span>
            <span class="rc-note-b">
                <h4>Not sure which to pick?</h4>
                <p>
                    <b>Shop Packages</b> is the fastest if a pro already offers what you need as a bundle.
                    Choose <b>Bidding Request</b> when you want professionals to compete on price — tick one
                    service or several, and it handles both.
                </p>
            </span>
            {{-- A signpost, drawn inline. Decoration, so it is hidden from
                 screen readers and dropped entirely on narrow screens. --}}
            <svg class="rc-note-art" viewBox="0 0 120 84" fill="none" aria-hidden="true" focusable="false">
                <ellipse cx="60" cy="76" rx="40" ry="5" fill="#f3c98b" opacity=".35"/>
                <circle cx="26" cy="18" r="7" fill="#fff" opacity=".9"/>
                <circle cx="34" cy="18" r="9" fill="#fff" opacity=".9"/>
                <circle cx="96" cy="26" r="6" fill="#fff" opacity=".9"/>
                <circle cx="103" cy="26" r="8" fill="#fff" opacity=".9"/>
                <rect x="56" y="20" width="6" height="56" rx="3" fill="#c9a227" opacity=".55"/>
                <path d="M22 32h38a2 2 0 0 1 2 2v10a2 2 0 0 1-2 2H22l-8-7z" fill="#8b5cf6"/>
                <path d="M98 52H60a2 2 0 0 0-2 2v10a2 2 0 0 0 2 2h38l8-7z" fill="#f97316"/>
                <path d="M86 74c4-10 12-13 18-12-2 8-9 13-18 12z" fill="#34d399" opacity=".8"/>
                <path d="M96 62c0 6-3 10-10 12" stroke="#10b981" stroke-width="1.6" stroke-linecap="round"/>
            </svg>
        </div>

        </div>{{-- /.rc-layout --}}
    </div>
</div>
@endsection
