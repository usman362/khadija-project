@extends('layouts.professional')

@section('title', 'Reviews & Reputation')

{{-- ════════════════════════════════════════════════════════════════
     Professional "Reviews, Ratings & Reputation" — the flow for a pro to
     give feedback to a client after a completed event. REAL: pending
     completed booking to rate + posting a Review (reviewer = pro). The
     3-area scores average into Review.rating. Echo Effect / Re-Shape /
     Vanish / Peer Mediate are illustrative (not modelled yet).
═══════════════════════════════════════════════════════════════════ --}}

@push('styles')
<style>
    .pr { --pr-blue: #2563eb; }
    .pr-card { background: var(--bg-card); border: 1px solid var(--border-color); border-radius: 14px; padding: 16px 18px; }

    /* Header */
    .pr-header { text-align: center; margin-bottom: 22px; position: relative; }
    .pr-header h1 { font-size: 30px; font-weight: 800; color: var(--text-primary); margin: 0; letter-spacing: 0.5px; }
    .pr-header h1 .blue { color: #2563eb; display: block; }
    .pr-header p { font-size: 14px; color: var(--text-muted); margin: 8px 0 0; }
    .pr-corner { position: absolute; top: 0; display: flex; align-items: flex-start; gap: 9px; background: rgba(37,99,235,0.05); border: 1px solid rgba(37,99,235,0.16); border-radius: 12px; padding: 11px 13px; max-width: 230px; }
    .pr-corner.l { left: 0; } .pr-corner.r { right: 0; }
    .pr-corner .ic { width: 30px; height: 30px; border-radius: 8px; background: rgba(37,99,235,0.12); color: #2563eb; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
    .pr-corner .ic svg { width: 16px; height: 16px; }
    .pr-corner p { font-size: 11px; color: var(--text-secondary); margin: 0; line-height: 1.4; text-align: left; }

    /* Main grid: 3 steps + sidebar */
    .pr-grid { display: grid; grid-template-columns: repeat(3, minmax(0,1fr)) 250px; gap: 14px; align-items: start; margin-bottom: 20px; }
    .pr-step-badge { display: inline-flex; align-items: center; gap: 0; margin-bottom: 12px; }
    .pr-step-badge .n { background: #2563eb; color: #fff; font-size: 11px; font-weight: 800; padding: 5px 10px; border-radius: 7px 0 0 7px; }
    .pr-step-badge .t { background: var(--bg-card); border: 1px solid var(--border-color); border-left: none; color: var(--text-secondary); font-size: 11px; font-weight: 800; padding: 5px 10px; border-radius: 0 7px 7px 0; letter-spacing: 0.5px; }
    .pr-step-title { font-size: 16px; font-weight: 800; color: #2563eb; text-align: center; margin: 0 0 3px; }
    .pr-step-sub { font-size: 11.5px; color: var(--text-muted); text-align: center; margin: 0 0 14px; }

    /* Step 1 — event completed */
    .pr-evt { background: linear-gradient(135deg,#1e3a8a,#1e40af); border-radius: 11px 11px 0 0; padding: 12px 14px; display: flex; align-items: center; justify-content: space-between; color: #fff; }
    .pr-evt .l { display: flex; align-items: center; gap: 8px; font-size: 12px; font-weight: 800; }
    .pr-evt .l svg { width: 16px; height: 16px; }
    .pr-evt .r { text-align: right; font-size: 11px; opacity: 0.9; }
    .pr-evt-body { border: 1px solid var(--border-color); border-top: none; border-radius: 0 0 11px 11px; padding: 14px; }
    .pr-evt-msg { display: flex; gap: 9px; margin-bottom: 12px; }
    .pr-evt-msg svg { width: 18px; height: 18px; color: #2563eb; flex-shrink: 0; }
    .pr-evt-msg div { font-size: 12px; color: var(--text-secondary); line-height: 1.4; }
    .pr-evt-msg b { color: var(--text-primary); display: block; }
    .pr-evt-card { display: flex; gap: 10px; align-items: center; border: 1px solid var(--border-color); border-radius: 10px; padding: 10px; margin-bottom: 12px; }
    .pr-evt-av { width: 44px; height: 44px; border-radius: 10px; flex-shrink: 0; background: linear-gradient(135deg,#2563eb,#1d4ed8); color: #fff; font-weight: 800; display: flex; align-items: center; justify-content: center; font-size: 16px; }
    .pr-evt-nm { font-size: 13px; font-weight: 800; color: var(--text-primary); }
    .pr-evt-mt { font-size: 11px; color: var(--text-muted); }
    .pr-note-box { background: rgba(37,99,235,0.05); border-radius: 9px; padding: 10px 12px; font-size: 11.5px; color: var(--text-secondary); text-align: center; line-height: 1.45; margin-bottom: 12px; }
    .pr-btn { display: block; width: 100%; text-align: center; padding: 11px; border-radius: 10px; background: #2563eb; color: #fff; border: none; font-size: 12.5px; font-weight: 800; cursor: pointer; text-decoration: none; }
    .pr-btn:hover { background: #1d4ed8; }
    .pr-why { display: flex; gap: 9px; margin-top: 14px; padding: 11px 12px; background: rgba(37,99,235,0.04); border-radius: 10px; }
    .pr-why svg { width: 16px; height: 16px; color: #2563eb; flex-shrink: 0; }
    .pr-why p { font-size: 11px; color: var(--text-secondary); margin: 0; line-height: 1.45; }
    .pr-why b { color: var(--text-primary); }

    /* Step 2 — metrics / star ratings */
    .pr-metric { border: 1px solid var(--border-color); border-radius: 11px; padding: 12px; margin-bottom: 10px; }
    .pr-metric-h { display: flex; align-items: center; gap: 9px; margin-bottom: 8px; }
    .pr-metric-ico { width: 34px; height: 34px; border-radius: 9px; display: flex; align-items: center; justify-content: center; flex-shrink: 0; color: #fff; }
    .pr-metric-ico svg { width: 17px; height: 17px; }
    .pr-metric-nm { font-size: 12.5px; font-weight: 800; color: var(--text-primary); }
    .pr-metric-q { font-size: 10.5px; color: var(--text-muted); line-height: 1.35; }
    .pr-metric-row { display: flex; align-items: center; gap: 10px; }
    .pr-stars { display: inline-flex; flex-direction: row-reverse; }
    .pr-stars input { display: none; }
    .pr-stars label { color: var(--border-color); cursor: pointer; font-size: 22px; line-height: 1; padding: 0 1px; transition: color 0.12s; }
    .pr-stars label:hover, .pr-stars label:hover ~ label, .pr-stars input:checked ~ label { color: #2563eb; }
    .pr-score-tag { font-size: 12px; font-weight: 800; color: #059669; background: rgba(16,185,129,0.12); border-radius: 20px; padding: 3px 10px; margin-left: auto; }
    .pr-score-tag .x { font-size: 9px; color: var(--text-muted); font-weight: 600; }
    .pr-note-label { font-size: 11.5px; font-weight: 700; color: var(--text-primary); margin: 12px 0 6px; }
    .pr-note-label span { font-weight: 500; color: var(--text-muted); }
    .pr-textarea { width: 100%; min-height: 64px; border: 1px solid var(--border-color); border-radius: 9px; padding: 9px 11px; font-size: 12px; font-family: inherit; color: var(--text-primary); background: var(--bg-card-hover); resize: vertical; outline: none; }
    .pr-textarea:focus { border-color: #2563eb; }
    .pr-char { text-align: right; font-size: 10px; color: var(--text-muted); margin-top: 3px; }

    /* Step 3 — submission */
    .pr-overall { border: 1px solid var(--border-color); border-radius: 11px; padding: 16px; text-align: center; margin-bottom: 14px; }
    .pr-overall-h { display: flex; align-items: center; justify-content: center; gap: 7px; font-size: 12.5px; font-weight: 800; color: var(--text-primary); margin-bottom: 10px; }
    .pr-overall-h svg { width: 18px; height: 18px; color: #2563eb; }
    .pr-overall-stars { font-size: 26px; color: var(--border-color); letter-spacing: 2px; }
    .pr-overall-stars .on { color: #2563eb; }
    .pr-overall-score { display: inline-block; font-size: 13px; font-weight: 800; color: #059669; background: rgba(16,185,129,0.12); border-radius: 20px; padding: 3px 11px; margin: 8px 0; }
    .pr-overall-msg { font-size: 11.5px; color: var(--text-muted); }
    .pr-submit { display: flex; align-items: center; justify-content: center; gap: 8px; width: 100%; padding: 12px; border-radius: 10px; background: #2563eb; color: #fff; border: none; font-size: 13px; font-weight: 800; cursor: pointer; }
    .pr-submit:hover { background: #1d4ed8; }
    .pr-submit svg { width: 15px; height: 15px; }
    .pr-next { margin-top: 14px; }
    .pr-next-h { font-size: 12px; font-weight: 800; color: var(--text-primary); margin-bottom: 9px; }
    .pr-next-row { display: flex; gap: 9px; margin-bottom: 10px; }
    .pr-next-row svg { width: 16px; height: 16px; color: #2563eb; flex-shrink: 0; }
    .pr-next-row p { font-size: 11px; color: var(--text-secondary); margin: 0; line-height: 1.4; }
    .pr-next-row b { color: var(--text-primary); }

    /* Sidebar */
    .pr-side { display: flex; flex-direction: column; gap: 14px; }
    .pr-side-card { background: var(--bg-card); border: 1px solid var(--border-color); border-radius: 12px; padding: 14px; }
    .pr-side-h { font-size: 11.5px; font-weight: 800; color: #2563eb; letter-spacing: 0.4px; margin-bottom: 11px; }
    .pr-side-row { display: flex; gap: 9px; padding: 6px 0; font-size: 11px; color: var(--text-secondary); line-height: 1.4; }
    .pr-side-row svg { width: 15px; height: 15px; color: #2563eb; flex-shrink: 0; margin-top: 1px; }
    .pr-echo-flow { display: flex; align-items: center; justify-content: center; gap: 8px; margin: 10px 0; }
    .pr-echo-flow svg { width: 18px; height: 18px; color: #2563eb; }
    .pr-echo-txt { font-size: 10.5px; color: var(--text-secondary); text-align: center; line-height: 1.45; }
    .pr-safe { display: flex; gap: 9px; align-items: flex-start; }
    .pr-safe .ic { width: 30px; height: 30px; border-radius: 8px; background: rgba(37,99,235,0.12); color: #2563eb; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
    .pr-safe .ic svg { width: 15px; height: 15px; }
    .pr-safe b { font-size: 11.5px; color: var(--text-primary); }
    .pr-safe p { font-size: 10.5px; color: var(--text-muted); margin: 2px 0 0; line-height: 1.4; }

    /* Bottom options */
    .pr-opts-title { text-align: center; font-size: 15px; font-weight: 800; color: var(--text-primary); margin: 4px 0 14px; }
    .pr-opts { display: grid; grid-template-columns: repeat(3, 1fr) 1fr; gap: 14px; }
    .pr-opt-h { display: flex; align-items: center; gap: 9px; margin-bottom: 8px; }
    .pr-opt-ico { width: 32px; height: 32px; border-radius: 9px; display: flex; align-items: center; justify-content: center; flex-shrink: 0; color: #fff; }
    .pr-opt-ico svg { width: 16px; height: 16px; }
    .pr-opt-nm { font-size: 13px; font-weight: 800; color: var(--text-primary); }
    .pr-opt-nm span { font-size: 9.5px; font-weight: 600; color: var(--text-muted); }
    .pr-opt-desc { font-size: 11px; color: var(--text-muted); line-height: 1.4; margin-bottom: 10px; }
    .pr-opt-chk { display: flex; gap: 7px; font-size: 11px; color: var(--text-secondary); padding: 3px 0; }
    .pr-opt-chk svg { width: 13px; height: 13px; color: #10b981; flex-shrink: 0; }
    .pr-opt-btn { display: block; width: 100%; text-align: center; padding: 9px; border-radius: 9px; color: #fff; border: none; font-size: 12px; font-weight: 800; cursor: pointer; text-decoration: none; margin-top: 11px; }

    @media (max-width: 1300px) { .pr-grid { grid-template-columns: 1fr 1fr; } .pr-opts { grid-template-columns: 1fr 1fr; } .pr-corner { display: none; } }
    @media (max-width: 760px) { .pr-grid, .pr-opts { grid-template-columns: 1fr; } }
</style>
@endpush

@section('content')
<div class="pr">

    {{-- ════════ Header ════════ --}}
    <div class="pr-header">
        <div class="pr-corner l"><span class="ic"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="8" r="6"/><path d="M15.477 12.89 17 22l-5-3-5 3 1.523-9.11"/></svg></span><p>Your feedback shapes better events for everyone.</p></div>
        <h1>REVIEWS, RATINGS &amp; REPUTATION<span class="blue">HOW IT WORKS FOR PROFESSIONALS</span></h1>
        <p>Give fair, helpful feedback to clients and help build a trusted event community.</p>
        <div class="pr-corner r"><span class="ic"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/></svg></span><p>Honest feedback today creates better experiences tomorrow.</p></div>
    </div>

    @if(session('status'))
        <div style="background:rgba(16,185,129,0.1);border:1px solid rgba(16,185,129,0.3);color:#059669;border-radius:10px;padding:11px 16px;margin-bottom:16px;font-size:13px;font-weight:600;">{{ session('status') }}</div>
    @endif

    @php
        $client = $pendingReview?->client;
        $cname  = $client?->name ?? 'the client';
        $cfirst = \Illuminate\Support\Str::of($cname)->explode(' ')->first();
    @endphp

    <form method="POST" action="{{ route('professional.reviews.store') }}" id="pr-form">
        @csrf
        @if($pendingReview)<input type="hidden" name="booking_id" value="{{ $pendingReview->id }}">@endif

        {{-- ════════ 3 steps + sidebar ════════ --}}
        <div class="pr-grid">

            {{-- STEP 1 --}}
            <div class="pr-card">
                <div class="pr-step-badge"><span class="n">STEP 1</span><span class="t">INITIALIZE</span></div>
                <div class="pr-step-title">The Midnight Trigger</div>
                <div class="pr-step-sub">Start the feedback process right after your event ends.</div>

                <div class="pr-evt">
                    <span class="l"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="12" cy="12" r="10"/><polyline points="9 12 11 14 15 10"/></svg>EVENT COMPLETED</span>
                    <span class="r">12:00 AM<br>{{ $pendingReview?->event?->ends_at?->format('M d, Y') ?? $pendingReview?->event?->starts_at?->format('M d, Y') ?? now()->format('M d, Y') }}</span>
                </div>
                <div class="pr-evt-body">
                    @if($pendingReview)
                        <div class="pr-evt-msg"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/></svg><div><b>Your event has ended!</b>Share your experience with the client while it's fresh.</div></div>
                        <div class="pr-evt-card">
                            <span class="pr-evt-av">{{ strtoupper(substr($cname, 0, 1)) }}</span>
                            <div>
                                <div class="pr-evt-nm">{{ \Illuminate\Support\Str::limit($pendingReview->event?->title ?? 'Event', 22) }}</div>
                                <div class="pr-evt-mt">Hosted by {{ $cname }}</div>
                                <div class="pr-evt-mt">{{ $pendingReview->event?->starts_at?->format('M d, Y · g:i A') }}{{ $pendingReview->event?->ends_at ? ' – ' . $pendingReview->event->ends_at->format('g:i A') : '' }}</div>
                            </div>
                        </div>
                        <div class="pr-note-box">Your feedback helps other professionals decide who is great to work with.</div>
                        <a href="#pr-form" class="pr-btn">Rate Your Experience with {{ $cfirst }}</a>
                    @else
                        <div class="pr-evt-msg"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg><div><b>You're all caught up!</b>No completed events are awaiting your feedback right now.</div></div>
                        <div class="pr-note-box">When an event you delivered completes, it'll appear here for you to rate the client.</div>
                    @endif
                </div>
                <div class="pr-why"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 18h6"/><path d="M10 22h4"/><path d="M12 2a7 7 0 0 0-4 12.7c.6.5 1 1.3 1 2.3h6c0-1 .4-1.8 1-2.3A7 7 0 0 0 12 2z"/></svg><p><b>Why?</b> Memories fade fast. Feedback right after the event is honest and accurate.</p></div>
            </div>

            {{-- STEP 2 --}}
            <div class="pr-card">
                <div class="pr-step-badge"><span class="n">STEP 2</span><span class="t">METRICS</span></div>
                <div class="pr-step-title">The Impact Weights</div>
                <div class="pr-step-sub">Rate the client on 3 key areas that matter most.</div>

                @php
                    $metrics = [
                        ['punctuality', 'Punctuality (On-Time Load In)', 'Did the client clear the venue on time for setup?', '#10b981', 'M12 6 12 12 16 14|circle'],
                        ['communication', 'Communication Clarity', 'Was the plan clear and easy to follow?', '#2563eb', 'chat'],
                        ['safety', 'Safety & Hospitality', 'Did the client provide a safe environment and take care of vendors?', '#8b5cf6', 'shield'],
                    ];
                    $defaults = ['punctuality' => 4, 'communication' => 5, 'safety' => 4];
                @endphp
                @foreach($metrics as $m)
                    <div class="pr-metric">
                        <div class="pr-metric-h">
                            <span class="pr-metric-ico" style="background:{{ $m[3] }};">
                                @if($m[4] === 'chat')<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
                                @elseif($m[4] === 'shield')<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
                                @else<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>@endif
                            </span>
                            <div style="min-width:0;"><div class="pr-metric-nm">{{ $m[1] }}</div><div class="pr-metric-q">{{ $m[2] }}</div></div>
                        </div>
                        <div class="pr-metric-row">
                            <span class="pr-stars" data-group="{{ $m[0] }}">
                                @for($v = 5; $v >= 1; $v--)
                                    <input type="radio" name="{{ $m[0] }}" id="{{ $m[0] }}-{{ $v }}" value="{{ $v }}" {{ $defaults[$m[0]] == $v ? 'checked' : '' }}>
                                    <label for="{{ $m[0] }}-{{ $v }}">★</label>
                                @endfor
                            </span>
                            <span class="pr-score-tag" data-score="{{ $m[0] }}">{{ number_format($defaults[$m[0]], 1) }}<span class="x">/5</span></span>
                        </div>
                    </div>
                @endforeach

                <div class="pr-note-label">Add a Private Note <span>(Optional)</span></div>
                <textarea name="note" class="pr-textarea" maxlength="300" placeholder="Share any helpful details about working with this client..." oninput="document.getElementById('pr-char').textContent = this.value.length"></textarea>
                <div class="pr-char"><span id="pr-char">0</span>/300</div>

                <div class="pr-why"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 18h6"/><path d="M10 22h4"/><path d="M12 2a7 7 0 0 0-4 12.7c.6.5 1 1.3 1 2.3h6c0-1 .4-1.8 1-2.3A7 7 0 0 0 12 2z"/></svg><p><b>Why?</b> These ratings help create a fair, data-based reputation for every client.</p></div>
            </div>

            {{-- STEP 3 --}}
            <div class="pr-card">
                <div class="pr-step-badge"><span class="n">STEP 3</span><span class="t">SUBMISSION</span></div>
                <div class="pr-step-title">Nurture &amp; Echo Submission</div>
                <div class="pr-step-sub">Lock in your feedback and help the community grow.</div>

                <div class="pr-overall">
                    <div class="pr-overall-h"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>YOUR OVERALL SCORE</div>
                    <div class="pr-overall-stars" id="pr-overall-stars">★★★★★</div>
                    <div class="pr-overall-score"><span id="pr-overall-num">4.3</span> /5</div>
                    <div class="pr-overall-msg" id="pr-overall-msg">Great experience! Thanks for booking.</div>
                </div>
                <button type="submit" class="pr-submit" {{ $pendingReview ? '' : 'disabled style=opacity:0.5;cursor:not-allowed;' }}>
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>Secure &amp; Post Feedback
                </button>

                <div class="pr-next">
                    <div class="pr-next-h">What Happens Next?</div>
                    <div class="pr-next-row"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg><p>Your feedback is locked into the marketplace so other pros can make smarter decisions.</p></div>
                    <div class="pr-next-row"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 12 20 22 4 22 4 12"/><rect x="2" y="7" width="20" height="5"/><line x1="12" y1="22" x2="12" y2="7"/><path d="M12 7H7.5a2.5 2.5 0 0 1 0-5C11 2 12 7 12 7z"/><path d="M12 7h4.5a2.5 2.5 0 0 0 0-5C13 2 12 7 12 7z"/></svg><p><b>The Echo Effect is activated!</b> The client receives a <b>reward token</b> via text to book you again.</p></div>
                </div>
                <div class="pr-why"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 18h6"/><path d="M10 22h4"/><path d="M12 2a7 7 0 0 0-4 12.7c.6.5 1 1.3 1 2.3h6c0-1 .4-1.8 1-2.3A7 7 0 0 0 12 2z"/></svg><p><b>Why?</b> Your feedback protects the community and boosts your reputation.</p></div>
            </div>

            {{-- SIDEBAR --}}
            <div class="pr-side">
                <div class="pr-side-card">
                    <div class="pr-side-h">WHY YOUR FEEDBACK MATTERS</div>
                    <div class="pr-side-row"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/></svg>Helps other professionals choose great clients.</div>
                    <div class="pr-side-row"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>Builds a safer, more respectful event community.</div>
                    <div class="pr-side-row"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="23 6 13.5 15.5 8.5 10.5 1 18"/><polyline points="17 6 23 6 23 12"/></svg>Improves the quality of events for everyone.</div>
                    <div class="pr-side-row"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>Boosts your reputation as a trusted professional.</div>
                </div>
                <div class="pr-side-card">
                    <div class="pr-side-h" style="text-align:center;">THE ECHO EFFECT</div>
                    <div class="pr-echo-flow"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 11l18-5v12L3 14v-3z"/><path d="M11.6 16.8a3 3 0 1 1-5.8-1.6"/></svg><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="19 12 12 19 5 12"/></svg></div>
                    <p class="pr-echo-txt">Happy clients get reward tokens when you submit feedback. They use these tokens to book you again → <b>More gigs for you!</b></p>
                </div>
                <div class="pr-side-card pr-safe">
                    <span class="ic"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg></span>
                    <div><b>Safe · Fair · Anonymous</b><p>All feedback is encrypted, secure, and used to build trust.</p></div>
                </div>
            </div>
        </div>
    </form>

    {{-- ════════ Bottom options ════════ --}}
    <div class="pr-opts-title">NEED TO MAKE A CHANGE? YOU'VE GOT OPTIONS.</div>
    <div class="pr-opts">
        <div class="pr-card">
            <div class="pr-opt-h"><span class="pr-opt-ico" style="background:#10b981;"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"/></svg></span><div class="pr-opt-nm">RE-SHAPE <span>(Adjust a Rating)</span></div></div>
            <p class="pr-opt-desc">Adjust a score if you and the client cleared up a misunderstanding.</p>
            <div class="pr-opt-chk"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"/></svg>Only you can change your rating</div>
            <div class="pr-opt-chk"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"/></svg>Text review stays locked</div>
            <div class="pr-opt-chk"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"/></svg>Keeps your feedback honest</div>
            <a href="#" class="pr-opt-btn" style="background:#10b981;">Re-Shape Review</a>
        </div>
        <div class="pr-card">
            <div class="pr-opt-h"><span class="pr-opt-ico" style="background:#8b5cf6;"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 10h.01M15 10h.01M12 2a8 8 0 0 0-8 8v12l3-3 2 2 3-3 3 3 2-2 3 3V10a8 8 0 0 0-8-8z"/></svg></span><div class="pr-opt-nm">VANISH <span>(Temporary Hold)</span></div></div>
            <p class="pr-opt-desc">Hide a review for up to 48 hours if needed.</p>
            <div class="pr-opt-chk"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"/></svg>Moves review to a 48-hour holding tank</div>
            <div class="pr-opt-chk"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"/></svg>Client can restore it anytime</div>
            <div class="pr-opt-chk"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"/></svg>Auto-deletes after time expires</div>
            <a href="#" class="pr-opt-btn" style="background:#8b5cf6;">Vanish Review</a>
        </div>
        <div class="pr-card">
            <div class="pr-opt-h"><span class="pr-opt-ico" style="background:#14b8a6;"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 3v18M3 7h18M6 7l-3 6h6zM18 7l-3 6h6z"/></svg></span><div class="pr-opt-nm">PEER MEDIATE <span>(Get Help)</span></div></div>
            <p class="pr-opt-desc">Need help with an unfair review situation?</p>
            <div class="pr-opt-chk"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"/></svg>Escalate to our Peer Mediation Panel</div>
            <div class="pr-opt-chk"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"/></svg>3 verified pros review anonymously</div>
            <div class="pr-opt-chk"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"/></svg>Fair decision based on facts</div>
            <a href="#" class="pr-opt-btn" style="background:#14b8a6;">Request Mediation</a>
        </div>
        <div class="pr-card">
            <div class="pr-opt-h"><span class="pr-opt-ico" style="background:#2563eb;"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 18h6"/><path d="M10 22h4"/><path d="M12 2a7 7 0 0 0-4 12.7c.6.5 1 1.3 1 2.3h6c0-1 .4-1.8 1-2.3A7 7 0 0 0 12 2z"/></svg></span><div class="pr-opt-nm">TIPS FOR GIVING GREAT FEEDBACK</div></div>
            <div class="pr-opt-chk"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"/></svg>Be specific and professional</div>
            <div class="pr-opt-chk"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"/></svg>Focus on facts, not emotions</div>
            <div class="pr-opt-chk"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"/></svg>Help improve future events</div>
            <div class="pr-opt-chk"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"/></svg>Keep the community positive</div>
        </div>
    </div>
</div>

<script>
(function () {
    const groups = ['punctuality', 'communication', 'safety'];
    const msgs = [
        [0,   'Rate the client to see your score.'],
        [2,   'There were some challenges to note.'],
        [3.5, 'A solid working experience.'],
        [4.2, 'Great experience! Thanks for booking.'],
        [5,   'Outstanding — a pleasure to work with!'],
    ];
    function vals() {
        return groups.map(g => {
            const el = document.querySelector('input[name="' + g + '"]:checked');
            return el ? parseInt(el.value, 10) : 0;
        });
    }
    function update() {
        const v = vals();
        groups.forEach((g, i) => {
            const tag = document.querySelector('[data-score="' + g + '"]');
            if (tag) tag.firstChild.textContent = v[i] ? v[i].toFixed(1) : '0.0';
        });
        const rated = v.filter(x => x > 0);
        const avg = rated.length ? rated.reduce((a, b) => a + b, 0) / rated.length : 0;
        const num = document.getElementById('pr-overall-num');
        const stars = document.getElementById('pr-overall-stars');
        const msg = document.getElementById('pr-overall-msg');
        if (num) num.textContent = avg ? avg.toFixed(1) : '0.0';
        if (stars) {
            const on = Math.round(avg);
            stars.innerHTML = '<span class="on">' + '★'.repeat(on) + '</span>' + '★'.repeat(5 - on);
        }
        if (msg) { let m = msgs[0][1]; for (const [t, txt] of msgs) if (avg >= t) m = txt; msg.textContent = m; }
    }
    document.querySelectorAll('.pr-stars input').forEach(i => i.addEventListener('change', update));
    update();
})();
</script>
@endsection
