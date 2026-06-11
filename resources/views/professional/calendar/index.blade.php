@extends('layouts.professional')

@section('title', 'My Calendar')

{{-- My Calendar — explainer + a live snapshot of the pro's schedule.
     REAL data: agenda unifies assigned Shifts + booking Events; the month
     grid marks days that have items; the availability strip derives
     busy/free per day. Phone-sync + travel-time cards are explainer UI. --}}

@php
    $dayNames = ['Sun','Mon','Tue','Wed','Thu','Fri','Sat'];
    // Agenda row badge: all-day shows the date, timed shows the start time.
    $badgeFor = fn ($it) => $it['all_day'] ? $it['start']->format('M d') : $it['start']->format('g:i A');
@endphp

@push('styles')
<style>
    .mc { --mc-blue: #2563eb; }
    .mc-card { background: var(--bg-card); border: 1px solid var(--border-color); border-radius: 16px; padding: 20px 22px; }

    /* ── Hero ── */
    .mc-hero { display: grid; grid-template-columns: minmax(0,1.15fr) minmax(0,0.85fr); gap: 22px; align-items: stretch; background: linear-gradient(135deg, rgba(37,99,235,0.05), rgba(139,92,246,0.04)); border: 1px solid var(--border-color); border-radius: 18px; padding: 24px 26px; margin-bottom: 20px; }
    .mc-hero-l { display: flex; flex-direction: column; }
    .mc-hero-top { display: grid; grid-template-columns: 200px minmax(0,1fr); gap: 18px; align-items: center; margin-bottom: 18px; }
    .mc-art { display: flex; align-items: center; justify-content: center; }
    .mc-art svg { width: 100%; max-width: 190px; height: auto; }
    .mc-hero-l h1 { font-size: 34px; font-weight: 800; color: var(--text-primary); margin: 0; }
    .mc-hero-l .sub { font-size: 15px; color: var(--text-secondary); margin: 6px 0 0; line-height: 1.45; }
    .mc-feats { display: grid; grid-template-columns: repeat(2, minmax(0,1fr)); gap: 12px; }
    .mc-feat { display: flex; gap: 11px; padding: 12px 14px; border: 1px solid var(--border-color); border-radius: 12px; background: var(--bg-card); }
    .mc-feat-ico { width: 32px; height: 32px; border-radius: 9px; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
    .mc-feat-ico svg { width: 17px; height: 17px; }
    .mc-feat b { font-size: 12.5px; font-weight: 800; color: var(--text-primary); display: block; line-height: 1.3; }
    .mc-feat p { font-size: 10.5px; color: var(--text-muted); margin: 3px 0 0; line-height: 1.45; }

    /* Today card */
    .mc-today { background: var(--bg-card); border: 1px solid var(--border-color); border-radius: 14px; padding: 16px 18px; display: flex; flex-direction: column; }
    .mc-today-h { display: flex; align-items: center; gap: 12px; padding-bottom: 14px; }
    .mc-today-ico { width: 44px; height: 44px; flex-shrink: 0; }
    .mc-today-ico svg { width: 100%; height: 100%; }
    .mc-today-h .lbl { font-size: 13px; color: var(--text-muted); font-weight: 600; }
    .mc-today-h .dt { font-size: 16px; font-weight: 800; color: var(--text-primary); }
    .mc-ag-list { border: 1px solid var(--border-color); border-radius: 12px; padding: 6px 12px; }
    .mc-ag { display: flex; align-items: center; gap: 13px; padding: 13px 4px; border-top: 1px solid var(--border-color); }
    .mc-ag:first-child { border-top: none; }
    .mc-ag-ic { width: 38px; height: 38px; border-radius: 10px; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
    .mc-ag-ic svg { width: 19px; height: 19px; }
    .mc-ag-mid { flex: 1; min-width: 0; }
    .mc-ag-title { font-size: 14px; font-weight: 800; color: var(--text-primary); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .mc-ag-sub { font-size: 12px; margin-top: 2px; }
    .mc-ag-badge { font-size: 12px; font-weight: 800; padding: 6px 13px; border-radius: 9px; white-space: nowrap; flex-shrink: 0; }
    .mc-viewcal { display: flex; align-items: center; justify-content: center; gap: 8px; padding: 14px; margin-top: 14px; border: 1px solid var(--border-color); border-radius: 11px; font-size: 14px; font-weight: 800; color: var(--mc-blue); text-decoration: none; }
    .mc-viewcal svg { width: 16px; height: 16px; }
    .mc-empty { padding: 26px 10px; text-align: center; color: var(--text-muted); font-size: 13px; }

    /* ── Section heading ── */
    .mc-sec-h { display: flex; align-items: center; gap: 10px; margin-bottom: 4px; }
    .mc-sec-h .ic { width: 30px; height: 30px; border-radius: 9px; background: rgba(37,99,235,0.1); display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
    .mc-sec-h .ic svg { width: 17px; height: 17px; color: #2563eb; }
    .mc-sec-h b { font-size: 19px; font-weight: 800; color: var(--text-primary); }
    .mc-sec-sub { font-size: 12.5px; color: var(--text-muted); margin: 0 0 16px; padding-left: 40px; }

    /* ── Detailed Section Breakdown (3 cols) ── */
    .mc-bd { display: grid; grid-template-columns: repeat(3, minmax(0,1fr)); gap: 16px; }
    .mc-bd-col { border: 1px solid var(--border-color); border-radius: 14px; padding: 18px; }
    .mc-bd-h { font-size: 14.5px; font-weight: 800; text-align: center; margin-bottom: 14px; padding-bottom: 10px; border-bottom: 2px solid; }
    .mc-bd-art { display: flex; justify-content: center; padding: 6px 0 14px; }
    .mc-bd-art svg { width: 130px; height: auto; }
    .mc-bd-note { font-size: 12.5px; color: var(--text-secondary); line-height: 1.55; margin: 0 0 8px; }
    .mc-bd-note b { color: #2563eb; }
    .mc-bd-row { display: flex; gap: 12px; padding: 11px 0; align-items: flex-start; }
    .mc-bd-row .ic { width: 34px; height: 34px; border-radius: 10px; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
    .mc-bd-row .ic svg { width: 17px; height: 17px; }
    .mc-bd-row b { font-size: 13px; color: var(--text-primary); display: block; }
    .mc-bd-row .tm { font-size: 12px; font-weight: 700; margin: 1px 0; }
    .mc-bd-row p { font-size: 11px; color: var(--text-muted); margin: 1px 0 0; line-height: 1.4; }
    .mc-navmock { background: var(--bg-card-hover); border: 1px solid var(--border-color); border-radius: 12px; padding: 14px; display: flex; gap: 14px; align-items: center; margin-bottom: 14px; }
    .mc-navmock .cal { width: 60px; height: 60px; border-radius: 12px; background: rgba(16,185,129,0.12); display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
    .mc-navmock .cal svg { width: 30px; height: 30px; color: #10b981; }
    .mc-navmock .lines { flex: 1; }
    .mc-navmock .lrow { display: flex; align-items: center; gap: 8px; margin-bottom: 9px; }
    .mc-navmock .lrow:last-child { margin-bottom: 0; }
    .mc-navmock .ln { flex: 1; height: 7px; border-radius: 4px; background: var(--border-color); }
    .mc-navmock .chk { width: 18px; height: 18px; border-radius: 50%; background: #10b981; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
    .mc-navmock .chk svg { width: 11px; height: 11px; color: #fff; }
    .mc-bd-cta { display: flex; align-items: center; justify-content: center; gap: 8px; padding: 13px; background: rgba(37,99,235,0.06); border-radius: 11px; font-size: 13.5px; font-weight: 800; color: var(--mc-blue); text-decoration: none; }
    .mc-bd-cta svg { width: 15px; height: 15px; }

    /* ── What happens (4 cols) ── */
    .mc-cc { display: grid; grid-template-columns: repeat(4, minmax(0,1fr)); gap: 16px; }
    .mc-cc-card { border: 1px solid var(--border-color); border-radius: 14px; padding: 16px; background: var(--bg-card); }
    .mc-cc-h { display: flex; align-items: flex-start; gap: 9px; margin-bottom: 4px; }
    .mc-cc-h .ic { width: 30px; height: 30px; border-radius: 8px; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
    .mc-cc-h .ic svg { width: 16px; height: 16px; }
    .mc-cc-h b { font-size: 13px; font-weight: 800; color: var(--text-primary); line-height: 1.3; }
    .mc-cc-card > p { font-size: 11px; color: var(--text-muted); line-height: 1.45; margin: 0 0 12px; }

    /* mini month grid */
    .mc-mini { background: var(--bg-card-hover); border: 1px solid var(--border-color); border-radius: 10px; padding: 11px; }
    .mc-mini-h { display: flex; align-items: center; justify-content: space-between; margin-bottom: 8px; }
    .mc-mini-h b { font-size: 12px; font-weight: 800; color: var(--text-primary); }
    .mc-mini-h svg { width: 13px; height: 13px; color: var(--text-muted); }
    .mc-mini-grid { display: grid; grid-template-columns: repeat(7, minmax(0, 1fr)); gap: 2px; }
    .mc-mini-dn { font-size: 8.5px; font-weight: 700; color: var(--text-muted); text-align: center; padding: 2px 0; }
    .mc-mini-day { position: relative; aspect-ratio: 1; display: flex; align-items: center; justify-content: center; font-size: 9.5px; color: var(--text-secondary); border-radius: 6px; }
    .mc-mini-day.out { color: var(--text-muted); opacity: 0.4; }
    .mc-mini-day.today { background: #2563eb; color: #fff; font-weight: 800; }
    .mc-mini-bars { position: absolute; bottom: 2px; left: 0; right: 0; display: flex; justify-content: center; gap: 2px; }
    .mc-mini-bars i { width: 5px; height: 3px; border-radius: 2px; }

    /* availability toggles */
    .mc-av-row { display: flex; align-items: center; justify-content: space-between; gap: 10px; padding: 10px 0; border-top: 1px solid var(--border-color); }
    .mc-av-row:first-child { border-top: none; }
    .mc-av-d { font-size: 12px; font-weight: 700; color: var(--text-primary); }
    .mc-av-s { font-size: 12px; font-weight: 700; }
    .mc-tog { width: 34px; height: 19px; border-radius: 999px; position: relative; flex-shrink: 0; }
    .mc-tog::after { content: ''; position: absolute; top: 2px; width: 15px; height: 15px; border-radius: 50%; background: #fff; transition: .2s; }
    .mc-tog.on::after { left: 17px; } .mc-tog.off::after { left: 2px; }

    /* phone sync */
    .mc-sync { display: flex; align-items: center; justify-content: center; gap: 14px; padding: 6px 0 12px; }
    .mc-sync-app { text-align: center; }
    .mc-sync-app .box { width: 46px; height: 46px; border-radius: 12px; border: 1px solid var(--border-color); display: flex; align-items: center; justify-content: center; margin: 0 auto 4px; background: var(--bg-card-hover); }
    .mc-sync-app .box svg { width: 24px; height: 24px; }
    .mc-sync-app span { font-size: 9.5px; color: var(--text-muted); }
    .mc-sync-arr svg { width: 22px; height: 22px; color: #10b981; }
    .mc-sync-done { display: flex; align-items: center; justify-content: center; gap: 7px; background: rgba(16,185,129,0.1); border-radius: 9px; padding: 9px; font-size: 11px; font-weight: 700; color: #10b981; }
    .mc-sync-done svg { width: 14px; height: 14px; }

    /* travel time */
    .mc-tt-row { display: flex; align-items: center; gap: 9px; padding: 8px 0; }
    .mc-tt-row .k { font-size: 11px; color: var(--text-muted); width: 40px; flex-shrink: 0; }
    .mc-tt-row .v { font-size: 11.5px; font-weight: 700; color: var(--text-primary); flex: 1; min-width: 0; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .mc-tt-row .pin { width: 15px; height: 15px; flex-shrink: 0; }
    .mc-tt-big { display: flex; align-items: center; gap: 9px; padding-top: 10px; border-top: 1px solid var(--border-color); margin-top: 4px; }
    .mc-tt-big .k { font-size: 10.5px; color: var(--text-muted); }
    .mc-tt-big .v { font-size: 20px; font-weight: 800; }
    .mc-tt-big svg { width: 20px; height: 20px; }

    /* ── Bottom banner ── */
    .mc-banner { display: flex; flex-wrap: wrap; align-items: center; gap: 16px; background: linear-gradient(135deg, rgba(37,99,235,0.07), rgba(139,92,246,0.05)); border: 1px solid rgba(37,99,235,0.18); border-radius: 16px; padding: 18px 22px; margin-top: 20px; }
    .mc-banner .clock { width: 48px; height: 48px; flex-shrink: 0; }
    .mc-banner .clock svg { width: 100%; height: 100%; }
    .mc-banner-txt { flex: 1; }
    .mc-banner-txt b { font-size: 17px; color: var(--text-primary); }
    .mc-banner-txt p { font-size: 12.5px; color: var(--text-muted); margin: 3px 0 0; line-height: 1.45; }
    .mc-banner a { display: inline-flex; align-items: center; gap: 9px; background: #2563eb; color: #fff; font-size: 15px; font-weight: 800; padding: 14px 24px; border-radius: 12px; text-decoration: none; white-space: nowrap; }
    .mc-banner a svg { width: 17px; height: 17px; }

    @media (max-width: 1200px) { .mc-hero, .mc-hero-top { grid-template-columns: 1fr; } .mc-bd { grid-template-columns: 1fr; } .mc-cc { grid-template-columns: repeat(2, minmax(0,1fr)); } }
    @media (max-width: 760px) { .mc-feats, .mc-cc { grid-template-columns: 1fr; } }
</style>
@endpush

@section('content')
<div class="mc">

    {{-- ════════ Hero ════════ --}}
    <div class="mc-hero">
        <div class="mc-hero-l">
            <div class="mc-hero-top">
                <div class="mc-art">@include('professional.calendar._cal_clock', ['w' => 190])</div>
                <div>
                    <h1>My Calendar</h1>
                    <div class="sub">View your schedule, availability and important dates.</div>
                </div>
            </div>
            <div class="mc-feats">
                <div class="mc-feat">
                    <span class="mc-feat-ico" style="background:rgba(37,99,235,0.12);color:#2563eb;"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/><polyline points="9 12 11 14 15 10"/></svg></span>
                    <div><b>Prevents Double-Booking</b><p>Stops you from scheduling two clients at the same time.</p></div>
                </div>
                <div class="mc-feat">
                    <span class="mc-feat-ico" style="background:rgba(16,185,129,0.12);color:#10b981;"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="13" r="8"/><path d="M12 9v4l2 2"/><path d="M5 3 2 6M22 6l-3-3M9 1h6"/></svg></span>
                    <div><b>Improves Punctuality</b><p>Keeps event start times front and center so you're never late.</p></div>
                </div>
                <div class="mc-feat">
                    <span class="mc-feat-ico" style="background:rgba(139,92,246,0.12);color:#8b5cf6;"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M8 14s1.5 2 4 2 4-2 4-2"/><line x1="9" y1="9" x2="9.01" y2="9"/><line x1="15" y1="9" x2="15.01" y2="9"/></svg></span>
                    <div><b>Reduces Daily Stress</b><p>No more guessing what you have to do today.</p></div>
                </div>
                <div class="mc-feat">
                    <span class="mc-feat-ico" style="background:rgba(249,115,22,0.12);color:#f97316;"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg></span>
                    <div><b>Protects Free Time</b><p>See when you're busy and when you're free to relax.</p></div>
                </div>
            </div>
        </div>

        {{-- Today card --}}
        <div class="mc-today">
            <div class="mc-today-h">
                <span class="mc-today-ico">@include('professional.calendar._cal_clock', ['w' => 44])</span>
                <div><span class="lbl">Today</span> &nbsp;•&nbsp; <span class="dt">{{ $now->format('M d, Y') }}</span></div>
            </div>
            <div class="mc-ag-list">
                @forelse($agenda as $it)
                    <div class="mc-ag">
                        <span class="mc-ag-ic" style="background:{{ $it['color'] }}1f;color:{{ $it['color'] }};"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg></span>
                        <div class="mc-ag-mid">
                            <div class="mc-ag-title">{{ $it['title'] }}</div>
                            <div class="mc-ag-sub" style="color:{{ $it['color'] }};">{{ $it['all_day'] ? $it['start']->format('M d') . ' · All Day' : $it['start']->format('g:i A') }}</div>
                        </div>
                        <span class="mc-ag-badge" style="background:{{ $it['color'] }}1f;color:{{ $it['color'] }};">{{ $badgeFor($it) }}</span>
                    </div>
                @empty
                    <div class="mc-empty">No upcoming events. Win a gig or get assigned a shift to see it here.</div>
                @endforelse
            </div>
            <a href="{{ route('professional.gigs.index') }}" class="mc-viewcal">View Calendar <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg></a>
        </div>
    </div>

    {{-- ════════ Detailed Section Breakdown ════════ --}}
    <div class="mc-card" style="margin-bottom:20px;">
        <div class="mc-sec-h"><span class="ic"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"/></svg></span><b>Detailed Section Breakdown</b></div>
        <p class="mc-sec-sub">Understand how each part of your calendar keeps your day on track.</p>
        <div class="mc-bd">
            {{-- 1. Header --}}
            <div class="mc-bd-col">
                <div class="mc-bd-h" style="color:#2563eb;border-color:#2563eb;">1. The Clock &amp; Calendar Header</div>
                <div class="mc-bd-art">@include('professional.calendar._cal_clock', ['w' => 130])</div>
                <p class="mc-bd-note"><b>The Visual:</b> The calendar and clock symbolize organized time.</p>
                <p class="mc-bd-note"><b style="color:#8b5cf6;">The Mission:</b> This is your home base for tracking your daily routine, open hours, and deadlines.</p>
            </div>
            {{-- 2. Daily Agenda --}}
            <div class="mc-bd-col">
                <div class="mc-bd-h" style="color:#8b5cf6;border-color:#8b5cf6;">2. The Daily Agenda</div>
                <div class="mc-bd-row"><span class="ic" style="background:rgba(37,99,235,0.12);color:#2563eb;"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg></span><div><b>Date Indicator</b><div class="tm" style="color:#2563eb;">Today • {{ $now->format('M d, Y') }}</div><p>Clear header showing the day you are viewing.</p></div></div>
                @foreach($agenda as $idx => $it)
                    <div class="mc-bd-row"><span class="ic" style="background:{{ $it['color'] }}1f;color:{{ $it['color'] }};"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg></span><div><b>{{ \Illuminate\Support\Str::limit($it['title'], 24) }}</b><div class="tm" style="color:{{ $it['color'] }};">{{ $it['all_day'] ? $it['start']->format('M d') . ' • All Day' : $it['start']->format('g:i A') }}</div><p>{{ $it['all_day'] ? 'All-day event or date reminder.' : ($idx === 0 ? 'Your first scheduled event.' : 'Your next scheduled event.') }}</p></div></div>
                @endforeach
                @if($agenda->isEmpty())
                    <div class="mc-empty">Your agenda is clear — no scheduled items yet.</div>
                @endif
            </div>
            {{-- 3. Navigation Gate --}}
            <div class="mc-bd-col">
                <div class="mc-bd-h" style="color:#10b981;border-color:#10b981;">3. The Navigation Gate</div>
                <div class="mc-navmock">
                    <span class="cal"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg></span>
                    <div class="lines">
                        <div class="lrow"><span class="ln"></span><span class="chk"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"/></svg></span></div>
                        <div class="lrow"><span class="ln" style="width:80%;"></span><span class="chk"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"/></svg></span></div>
                        <div class="lrow"><span class="ln" style="width:65%;"></span><span class="chk"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"/></svg></span></div>
                    </div>
                </div>
                <p class="mc-bd-note">The <b>"View Calendar"</b> button takes you to the full monthly calendar and advanced tools.</p>
                <a href="{{ route('professional.gigs.index') }}" class="mc-bd-cta">View Calendar <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg></a>
            </div>
        </div>
    </div>

    {{-- ════════ What happens if you click ════════ --}}
    <div class="mc-sec-h"><span class="ic"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4.5 16.5c-1.5 1.26-2 5-2 5s3.74-.5 5-2c.71-.84.7-2.13-.09-2.91a2.18 2.18 0 0 0-2.91 0z"/><path d="M12 15l-3-3a22 22 0 0 1 2-3.95A12.88 12.88 0 0 1 22 2c0 2.72-.78 7.5-6 11a22.35 22.35 0 0 1-4 2z"/><path d="M9 12H4s.55-3.03 2-4c1.62-1.08 5 0 5 0M12 15v5s3.03-.55 4-2c1.08-1.62 0-5 0-5"/></svg></span><b>What Happens if You Click?</b></div>
    <p class="mc-sec-sub">Unlock powerful scheduling tools to manage your time like a pro.</p>
    <div class="mc-cc">
        {{-- Monthly Grid View --}}
        <div class="mc-cc-card">
            <div class="mc-cc-h"><span class="ic" style="background:rgba(37,99,235,0.12);color:#2563eb;"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg></span><b>Monthly Grid View</b></div>
            <p>See your entire month. Drag and drop events to change dates.</p>
            <div class="mc-mini">
                <div class="mc-mini-h"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="15 18 9 12 15 6"/></svg><b>{{ $monthLabel }}</b><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="9 18 15 12 9 6"/></svg></div>
                <div class="mc-mini-grid">
                    @foreach($dayNames as $dn)<div class="mc-mini-dn">{{ $dn }}</div>@endforeach
                    @foreach($grid as $cell)
                        <div class="mc-mini-day {{ $cell['isToday'] ? 'today' : '' }} {{ $cell['inMonth'] ? '' : 'out' }}">
                            {{ $cell['day'] }}
                            @if(!empty($cell['markers']) && !$cell['isToday'])
                                <span class="mc-mini-bars">@foreach($cell['markers'] as $mk)<i style="background:{{ $mk }};"></i>@endforeach</span>
                            @endif
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
        {{-- Availability Toggles --}}
        <div class="mc-cc-card">
            <div class="mc-cc-h"><span class="ic" style="background:rgba(139,92,246,0.12);color:#8b5cf6;"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="1" y="5" width="22" height="14" rx="7"/><circle cx="16" cy="12" r="3"/></svg></span><b>Availability Toggles</b></div>
            <p>Block off days so new clients can't book you.</p>
            <div style="border:1px solid var(--border-color);border-radius:10px;padding:4px 12px;">
                @foreach($availability as $av)
                    @php
                        if ($av['busy']) { $sLabel='Fully Booked'; $sColor='#ef4444'; $tColor='#ef4444'; }
                        else { $sLabel='Available'; $sColor='#10b981'; $tColor='#10b981'; }
                    @endphp
                    <div class="mc-av-row">
                        <span class="mc-av-d">{{ $av['date']->format('M d') }}</span>
                        <span class="mc-av-s" style="color:{{ $sColor }};">{{ $sLabel }}</span>
                        <span class="mc-tog on" style="background:{{ $tColor }};"></span>
                    </div>
                @endforeach
            </div>
        </div>
        {{-- Phone Synchronization --}}
        <div class="mc-cc-card">
            <div class="mc-cc-h"><span class="ic" style="background:rgba(37,99,235,0.12);color:#2563eb;"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="5" y="2" width="14" height="20" rx="2"/><line x1="12" y1="18" x2="12" y2="18"/></svg></span><b>Phone Synchronization</b></div>
            <p>Sync with your phone so your schedule is always updated.</p>
            <div class="mc-sync">
                <div class="mc-sync-app"><span class="box"><svg viewBox="0 0 24 24" fill="currentColor" style="color:var(--text-primary);"><path d="M16.365 1.43c0 1.14-.493 2.27-1.177 3.08-.744.9-1.99 1.57-2.987 1.57-.12 0-.23-.02-.3-.03-.01-.06-.04-.22-.04-.39 0-1.15.572-2.27 1.206-2.98.804-.94 2.142-1.64 3.248-1.68.03.13.05.28.05.43zm4.565 15.71c-.03.07-.463 1.58-1.518 3.12-.945 1.34-1.94 2.71-3.43 2.71-1.517 0-1.9-.88-3.63-.88-1.698 0-2.302.91-3.67.91-1.377 0-2.332-1.26-3.428-2.8-1.287-1.82-2.323-4.63-2.323-7.28 0-4.28 2.797-6.55 5.552-6.55 1.448 0 2.675.95 3.6.95.865 0 2.222-1.01 3.902-1.01.613 0 2.886.06 4.374 2.19-.13.09-2.383 1.37-2.383 4.19 0 3.26 2.854 4.42 2.955 4.46z"/></svg></span><span>Apple<br>Calendar</span></div>
                <div class="mc-sync-arr"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="23 4 23 10 17 10"/><polyline points="1 20 1 14 7 14"/><path d="M3.51 9a9 9 0 0 1 14.85-3.36L23 10M1 14l4.64 4.36A9 9 0 0 0 20.49 15"/></svg></div>
                <div class="mc-sync-app"><span class="box"><svg viewBox="0 0 24 24" fill="none"><rect x="4" y="5" width="16" height="16" rx="2" fill="#fff" stroke="#e2e8f0"/><rect x="4" y="5" width="16" height="4" rx="2" fill="#4285f4"/><text x="12" y="18" font-size="8" font-weight="800" fill="#34a853" text-anchor="middle">31</text></svg></span><span>Google<br>Calendar</span></div>
            </div>
            <div class="mc-sync-done"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"/></svg>Synced Successfully</div>
        </div>
        {{-- Travel Time Calculator --}}
        <div class="mc-cc-card">
            <div class="mc-cc-h"><span class="ic" style="background:rgba(249,115,22,0.12);color:#f97316;"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M5 17H3v-5l2-5h11l3 5h2v5h-2"/><circle cx="7.5" cy="17.5" r="2"/><circle cx="17.5" cy="17.5" r="2"/></svg></span><b>Travel Time Calculator</b></div>
            <p>See travel time and when you should leave.</p>
            @php $dest = $agenda->first()['title'] ?? 'your event'; $leaveBy = isset($agenda[0]) && !$agenda[0]['all_day'] ? $agenda[0]['start']->copy()->subMinutes(35)->format('g:i A') : '5:25 PM'; @endphp
            <div class="mc-tt-row"><span class="k">From</span><span class="v">Your Location</span><span class="pin"><svg viewBox="0 0 24 24" fill="#2563eb" stroke="none"><path d="M12 2a7 7 0 0 0-7 7c0 5 7 13 7 13s7-8 7-13a7 7 0 0 0-7-7z"/><circle cx="12" cy="9" r="2.5" fill="#fff"/></svg></span></div>
            <div class="mc-tt-row" style="border-top:1px solid var(--border-color);"><span class="k">To</span><span class="v">{{ \Illuminate\Support\Str::limit($dest, 18) }}</span><span class="pin"><svg viewBox="0 0 24 24" fill="#ef4444" stroke="none"><path d="M12 2a7 7 0 0 0-7 7c0 5 7 13 7 13s7-8 7-13a7 7 0 0 0-7-7z"/><circle cx="12" cy="9" r="2.5" fill="#fff"/></svg></span></div>
            <div class="mc-tt-big">
                <div style="flex:1;"><div class="k">Travel Time</div><div class="v" style="color:#f97316;">35 mins</div></div>
                <svg viewBox="0 0 24 24" fill="none" stroke="#f97316" stroke-width="2"><path d="M5 17H3v-5l2-5h11l3 5h2v5h-2"/><circle cx="7.5" cy="17.5" r="2"/><circle cx="17.5" cy="17.5" r="2"/></svg>
            </div>
            <div class="mc-tt-big" style="border-top:none;padding-top:4px;">
                <div style="flex:1;"><div class="k">Leave By</div><div class="v" style="color:#2563eb;">{{ $leaveBy }}</div></div>
            </div>
        </div>
    </div>

    {{-- ════════ Bottom banner ════════ --}}
    <div class="mc-banner">
        <span class="clock">@include('professional.calendar._cal_clock', ['w' => 48])</span>
        <div class="mc-banner-txt"><b>Stay Organized. Be On Time. Win More.</b><p>My Calendar keeps your day on track so you can focus on what matters most—your clients.</p></div>
        <a href="{{ route('professional.calendar.index') }}"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>Go to Full Calendar <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg></a>
    </div>
</div>
@endsection
