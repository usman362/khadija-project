@extends('layouts.professional')

@section('title', 'Dashboard')

{{-- ════════════════════════════════════════════════════════════════
     Professional Dashboard · reference-exact redesign (blue theme).
     UI scaffold matching the client's professional dashboard mockup:
     8-stat strip + Emergency Gigs · Priority Actions · Gig Operations
     Hub · Bid Intelligence · Finance Hub · Today's Schedule.

     NOTE: charts (donut/sparklines) are pure CSS/SVG; the map + weather
     are static placeholders (need Maps/Weather APIs). Live data wiring
     (real gigs, bids, finance, schedule) is a follow-up.
═══════════════════════════════════════════════════════════════════ --}}

@push('styles')
<style>
    /* Used for text as well as fills, so it follows the theme. */
    .pd { --pd-blue: var(--info-text); }

    /* ── Stats strip (8 cards) ── */
    /* Asks for the service origin. Sits above everything because it is about
       whether this professional can be matched at all. */
    .so-prompt { display: flex; align-items: flex-start; gap: 14px; padding: 15px 18px; margin-bottom: 18px;
                 border: 1.5px solid var(--border-color); border-left: 4px solid #f97316; border-radius: 12px;
                 background: var(--bg-card); flex-wrap: wrap; }
    .so-prompt-ico { flex-shrink: 0; width: 34px; height: 34px; display: grid; place-items: center; border-radius: 9px;
                     background: rgba(249,115,22,.12); color: #ea580c; }
    .so-prompt-ico svg { width: 18px; height: 18px; }
    .so-prompt-body { flex: 1; min-width: 240px; }
    .so-prompt-body b { display: block; font-size: 14px; color: var(--text-primary); margin-bottom: 3px; }
    .so-prompt-body p { margin: 0; font-size: 13px; color: var(--text-muted); line-height: 1.5; max-width: 640px; }
    .so-prompt-btn { flex-shrink: 0; align-self: center; padding: 9px 16px; border-radius: 9px; background: #ea580c;
                     color: #fff; font-size: 13px; font-weight: 700; text-decoration: none; white-space: nowrap; }

    .pd-stats { display: grid; grid-template-columns: repeat(4, minmax(0,1fr)); gap: 12px; margin-bottom: 16px; }
    .pd-stat { background: var(--bg-card); border: 1px solid var(--border-color); border-radius: 12px; padding: 13px 13px 11px; min-width: 0; }
    .pd-stat-top { display: flex; align-items: flex-start; gap: 8px; margin-bottom: 9px; min-height: 38px; }
    .pd-stat-ico { width: 30px; height: 30px; border-radius: 8px; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
    .pd-stat-ico svg { width: 15px; height: 15px; }
    .c-green { background: rgba(16,185,129,0.12); color: var(--ok-text); }
    .c-blue { background: rgba(37,99,235,0.12); color: var(--info-text); }
    .c-teal { background: rgba(20,184,166,0.12); color: #14b8a6; }
    .c-purple { background: rgba(139,92,246,0.12); color: var(--accent-text); }
    .c-indigo { background: rgba(99,102,241,0.12); color: var(--accent-text); }
    .c-cyan { background: rgba(6,182,212,0.12); color: #06b6d4; }
    .c-orange { background: rgba(249,115,22,0.12); color: var(--brand-text); }
    .pd-stat-label { font-size: 10px; color: var(--text-muted); font-weight: 600; line-height: 1.2; min-width: 0; }
    .pd-stat-ico { margin-top: 1px; }
    .pd-stat-info { margin-left: auto; width: 13px; height: 13px; color: var(--text-muted); flex-shrink: 0; }
    .pd-stat-val { font-size: 19px; font-weight: 800; color: var(--text-primary); letter-spacing: -0.02em; }
    .pd-stat-foot { display: flex; align-items: center; justify-content: space-between; gap: 6px; margin-top: 6px; }
    .pd-stat-delta { font-size: 9.5px; font-weight: 700; color: var(--ok-text); min-width: 0; }
    .pd-stat-delta.muted { color: var(--text-muted); }
    .pd-stat-spark { flex-shrink: 0; }

    /* ── two 3-column rows ── */
    .pd-row { display: grid; gap: 14px; margin-bottom: 14px; align-items: start; }
    .pd-row-1 { grid-template-columns: minmax(0,1.15fr) minmax(0,1.15fr) minmax(0,1fr); }
    .pd-row-2 { grid-template-columns: minmax(0,1fr) minmax(0,1.2fr); }
    .pd-card { background: var(--bg-card); border: 1px solid var(--border-color); border-radius: 14px; padding: 16px 17px; min-width: 0; }
    .pd-card-head { display: flex; align-items: center; gap: 9px; margin-bottom: 14px; }
    .pd-card-ico { width: 30px; height: 30px; border-radius: 8px; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
    .pd-card-ico svg { width: 16px; height: 16px; }
    .pd-card-title { font-size: 14px; font-weight: 800; color: var(--text-primary); }
    .pd-card-link { margin-left: auto; font-size: 11.5px; font-weight: 700; color: var(--pd-blue); text-decoration: none; display: inline-flex; align-items: center; gap: 4px; white-space: nowrap; }
    .pd-card-link svg { width: 12px; height: 12px; }
    .pd-pill-count { background: rgba(239,68,68,0.14); color: var(--bad-text); font-size: 10px; font-weight: 800; min-width: 17px; height: 17px; padding: 0 5px; border-radius: 9px; display: inline-flex; align-items: center; justify-content: center; }

    /* ── Emergency Gigs card ── */
    .pd-emergency { background: linear-gradient(180deg, rgba(239,68,68,0.06), rgba(249,115,22,0.02)); border-color: rgba(239,68,68,0.22); }
    .pd-emerg-head { display: flex; align-items: center; gap: 10px; margin-bottom: 14px; }
    .pd-emerg-ico { width: 34px; height: 34px; border-radius: 9px; background: #ef4444; color: #fff; display: flex; align-items: center; justify-content: center; }
    .pd-emerg-ico svg { width: 18px; height: 18px; }
    .pd-emerg-title { font-size: 13.5px; font-weight: 800; color: var(--text-primary); letter-spacing: 0.3px; }
    .pd-emerg-urgent { margin-left: auto; background: rgba(239,68,68,0.14); color: var(--bad-text); font-size: 8.5px; font-weight: 800; padding: 3px 7px; border-radius: 5px; letter-spacing: 0.5px; }
    .pd-emerg-job { font-size: 15px; font-weight: 800; color: var(--bad-text); margin-bottom: 4px; }
    .pd-emerg-sub { font-size: 12px; color: var(--text-secondary); margin-bottom: 2px; }
    .pd-emerg-meta { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 8px; margin: 13px 0; }
    .pd-emerg-meta .k { font-size: 9.5px; color: var(--text-muted); text-transform: uppercase; font-weight: 700; letter-spacing: 0.4px; }
    .pd-emerg-meta .v { font-size: 12px; font-weight: 700; color: var(--text-primary); margin-top: 2px; }
    .pd-emerg-meta .v.pay { color: var(--ok-text); }
    .pd-emerg-meta .v svg { width: 11px; height: 11px; vertical-align: -1px; }
    .pd-emerg-tag { font-size: 9.5px; font-weight: 800; padding: 2px 7px; border-radius: 6px; background: rgba(16,185,129,0.14); color: var(--ok-text); display: inline-block; }
    .pd-accept { display: block; width: 100%; text-align: center; padding: 11px; border-radius: 10px; background: #ef4444; color: #fff; border: none; font-size: 13px; font-weight: 800; cursor: pointer; margin: 4px 0 12px; }
    .pd-accept:hover { background: #dc2626; }
    .pd-emerg-note { font-size: 11px; color: var(--text-muted); line-height: 1.45; text-align: center; }
    .pd-emerg-countdown { text-align: center; font-size: 17px; font-weight: 800; color: var(--bad-text); margin-top: 12px; }

    /* ── Priority Actions list ── */
    .pd-pa { display: flex; flex-direction: column; }
    .pd-pa-row { display: flex; align-items: center; gap: 11px; padding: 10px 0; border-bottom: 1px solid var(--border-color); }
    .pd-pa-row:last-of-type { border-bottom: none; }
    .pd-pa-ico { width: 30px; height: 30px; border-radius: 8px; flex-shrink: 0; display: flex; align-items: center; justify-content: center; }
    .pd-pa-ico svg { width: 15px; height: 15px; }
    .pd-pa-body { flex: 1; min-width: 0; }
    .pd-pa-title { font-size: 12.5px; font-weight: 700; color: var(--text-primary); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .pd-pa-sub { font-size: 11px; color: var(--text-muted); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .pd-pa-pri { font-size: 9.5px; font-weight: 800; padding: 2px 8px; border-radius: 6px; flex-shrink: 0; }
    .pri-high { background: rgba(239,68,68,0.12); color: var(--bad-text); }
    .pri-medium { background: rgba(245,158,11,0.14); color: var(--warn-text); }
    .pri-low { background: rgba(16,185,129,0.12); color: var(--ok-text); }
    .pd-pa-foot { text-align: center; margin-top: 12px; }
    .pd-pa-foot a { font-size: 12px; font-weight: 700; color: var(--pd-blue); text-decoration: none; }

    /* ── Gig Operations Hub ── */
    .pd-tabs { display: flex; gap: 18px; border-bottom: 1px solid var(--border-color); margin-bottom: 4px; }
    .pd-tab { font-size: 12px; font-weight: 600; color: var(--text-muted); padding-bottom: 10px; border-bottom: 2px solid transparent; cursor: pointer; white-space: nowrap; }
    .pd-tab.active { color: var(--pd-blue); border-bottom-color: var(--pd-blue); }
    .pd-gtable-wrap { width: 100%; overflow-x: auto; }
    .pd-gtable { width: 100%; border-collapse: collapse; font-size: 11px; }
    .pd-gtable th { text-align: left; padding: 8px 6px; font-size: 9px; font-weight: 800; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.3px; border-bottom: 1px solid var(--border-color); white-space: nowrap; }
    .pd-gtable td { padding: 8px 6px; border-bottom: 1px solid var(--border-color); vertical-align: middle; }
    .pd-g-event { display: flex; align-items: center; gap: 9px; }
    .pd-g-event > div { min-width: 0; }
    .pd-g-thumb { width: 30px; height: 28px; border-radius: 7px; flex-shrink: 0; background: linear-gradient(135deg,#3b82f6,#1d4ed8); display: flex; align-items: center; justify-content: center; color: #fff; }
    .pd-g-thumb svg { width: 13px; height: 13px; }
    .pd-g-name { font-size: 11.5px; font-weight: 700; color: var(--text-primary); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; max-width: 160px; }
    .pd-g-client { font-size: 10px; color: var(--text-muted); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; max-width: 160px; }
    .pd-g-date, .pd-g-budget { font-size: 11.5px; color: var(--text-secondary); white-space: nowrap; }
    .pd-g-budget { font-weight: 600; color: var(--text-primary); }
    .pd-g-status { font-size: 9.5px; font-weight: 800; padding: 3px 8px; border-radius: 6px; white-space: nowrap; }
    .st-confirmed { background: rgba(16,185,129,0.12); color: var(--ok-text); }
    .st-waiting   { background: rgba(245,158,11,0.14); color: var(--brand-text); }
    .st-off       { background: rgba(239,68,68,0.12); color: var(--bad-text); }
    .st-quiet     { background: rgba(148,163,184,0.18); color: var(--text-muted); }
    .st-inprogress { background: rgba(245,158,11,0.14); color: var(--warn-text); }
    .st-today { background: rgba(37,99,235,0.12); color: var(--info-text); }
    .pd-g-staff { font-size: 11px; color: var(--text-secondary); display: inline-flex; align-items: center; gap: 7px; white-space: nowrap; }
    .pd-g-staff .chat { display: inline-flex; align-items: center; gap: 3px; color: var(--text-muted); }
    .pd-g-staff .chat svg { width: 11px; height: 11px; }
    .pd-ring { width: 28px; height: 28px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 8.5px; font-weight: 800; color: var(--text-primary); }
    .pd-ghub-foot { text-align: center; margin-top: 12px; }
    .pd-ghub-foot a { font-size: 12px; font-weight: 700; color: var(--pd-blue); text-decoration: none; display: inline-flex; align-items: center; gap: 5px; }
    .pd-ghub-foot svg { width: 13px; height: 13px; }

    /* ── Bid Intelligence donut ── */
    .pd-bid-body { display: flex; gap: 16px; align-items: center; }
    /* The ring is drawn from the professional's own bids, as an inline
       conic-gradient built below. It used to be written here as six fixed
       segments — 32% blue, 24% purple, 16% cyan, 12% amber, 12% green, 4%
       red — on every account on every day, so a page showing two bids, both
       open, drew a six-way split beside a legend that said 100% open. */
    .pd-donut { width: 124px; height: 124px; border-radius: 50%; flex-shrink: 0; position: relative;
        background: var(--border-color); }
    .pd-donut::after { content: ''; position: absolute; inset: 21px; background: var(--bg-card); border-radius: 50%; }
    .pd-donut-c { position: absolute; inset: 0; display: flex; flex-direction: column; align-items: center; justify-content: center; z-index: 1; }
    .pd-donut-c b { font-size: 22px; font-weight: 800; color: var(--text-primary); line-height: 1; }
    .pd-donut-c span { font-size: 9.5px; color: var(--text-muted); margin-top: 2px; }
    .pd-bid-legend { flex: 1; min-width: 0; display: flex; flex-direction: column; gap: 7px; }
    .pd-bid-leg { display: flex; align-items: center; gap: 8px; font-size: 11.5px; }
    .pd-bid-dot { width: 9px; height: 9px; border-radius: 50%; flex-shrink: 0; }
    .pd-bid-leg .nm { color: var(--text-secondary); flex: 1; }
    .pd-bid-leg .ct { font-weight: 700; color: var(--text-primary); }
    .pd-bid-leg .pc { color: var(--text-muted); width: 38px; text-align: right; }
    .pd-bid-rec { display: flex; gap: 10px; margin-top: 14px; padding: 11px 12px; border-radius: 10px; background: rgba(37,99,235,0.06); border: 1px solid rgba(37,99,235,0.16); }
    .pd-bid-rec .ic { width: 26px; height: 26px; border-radius: 7px; background: rgba(37,99,235,0.14); color: var(--info-text); display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
    .pd-bid-rec .ic svg { width: 14px; height: 14px; }
    .pd-bid-rec-txt { font-size: 11px; color: var(--text-secondary); line-height: 1.45; }
    .pd-bid-rec-txt b { color: var(--text-primary); display: block; margin-bottom: 1px; }
    .pd-bid-rec-spark { flex-shrink: 0; align-self: center; }

    /* ── Four summary cards ── */
    .pd-sum { display: grid; grid-template-columns: repeat(4, minmax(0,1fr)); gap: 13px; margin-bottom: 16px; }
    .pd-sumcard { display: block; text-decoration: none; background: var(--bg-card);
        border: 1px solid var(--border-color); border-radius: 14px; padding: 15px 16px; }
    .pd-sumcard:hover { border-color: var(--accent-blue, #2563eb); }
    .pd-sumlabel { display: block; font-size: 11.5px; font-weight: 700; color: var(--text-muted); }
    .pd-sumval { display: block; font-size: 28px; font-weight: 800; color: var(--text-primary); line-height: 1.1; margin: 5px 0 4px; }
    .pd-sumparts { display: block; font-size: 11.5px; color: var(--text-secondary, var(--text-muted)); }
    .pd-sumnote { display: block; font-size: 11.5px; font-weight: 700; color: var(--bad-text, #b91c1c); margin-top: 3px; }

    /* ── Needs your attention ── */
    .pd-row-attn { display: grid; grid-template-columns: minmax(0,1.5fr) minmax(0,1.1fr) minmax(0,1fr); gap: 16px;
        align-items: start; margin-bottom: 16px; }
    .pd-stack { display: flex; flex-direction: column; gap: 16px; }
    .pd-attn-row { display: grid; grid-template-columns: minmax(0,1fr) auto auto; gap: 11px; align-items: center;
        border-left: 3px solid var(--border-color); padding: 10px 0 10px 11px;
        border-bottom: 1px solid var(--border-color); }
    .pd-attn-row:last-child { border-bottom: 0; }
    .pd-attn-row b { display: block; font-size: 13px; font-weight: 700; color: var(--text-primary); line-height: 1.3; }
    .pd-attn-row span { display: block; font-size: 11.5px; color: var(--text-muted); }
    .pd-attn-pri { font-size: 11px; font-weight: 800; white-space: nowrap; }
    .pd-attn-go { font-size: 11.5px; font-weight: 700; padding: 7px 12px; border-radius: 8px;
        background: var(--accent-blue, #2563eb); color: #fff; text-decoration: none; white-space: nowrap; }
    .pd-quiet { font-size: 12.5px; color: var(--text-muted); margin: 4px 0 0; }
    .pd-next b { display: block; font-size: 14px; font-weight: 800; color: var(--text-primary); }
    .pd-next span { display: block; font-size: 12.5px; color: var(--text-secondary, var(--text-muted)); margin-top: 3px; }
    .pd-next i { display: block; font-style: normal; font-size: 11.5px; color: var(--text-muted); margin-top: 3px; }
    .pd-next a { display: inline-block; margin-top: 11px; font-size: 12.5px; font-weight: 700; padding: 9px 16px;
        border-radius: 9px; background: var(--accent-blue, #2563eb); color: #fff; text-decoration: none; }
    .pd-new { display: grid; grid-template-columns: repeat(2, minmax(0,1fr)); gap: 11px; }
    .pd-new b { display: block; font-size: 19px; font-weight: 800; color: var(--text-primary); line-height: 1.1; }
    .pd-new span { font-size: 11px; color: var(--text-muted); }
    @media (max-width: 1100px) { .pd-sum { grid-template-columns: repeat(2, minmax(0,1fr)); }
        .pd-row-attn { grid-template-columns: 1fr; } }
    @media (max-width: 620px) { .pd-attn-row { grid-template-columns: 1fr; } }

    .pd-goalset { display: inline-block; margin-top: 11px; font-size: 12.5px; font-weight: 700;
        padding: 9px 15px; border-radius: 9px; background: var(--accent-blue, #2563eb);
        color: #fff; text-decoration: none; }

    /* ── Setup bar, goal and seasons ── */
    .pd-setup { background: var(--bg-card); border: 1px solid var(--border-color); border-radius: 14px;
        padding: 15px 17px; margin-bottom: 18px; }
    .pd-setup-h { display: flex; justify-content: space-between; align-items: baseline; gap: 12px; margin-bottom: 9px; }
    .pd-setup-h b { font-size: 13.5px; font-weight: 800; color: var(--text-primary); }
    .pd-setup-h span { font-size: 11.5px; color: var(--text-muted); }
    .pd-setup-bar { height: 7px; border-radius: 4px; background: var(--border-color); overflow: hidden; }
    .pd-setup-bar i { display: block; height: 100%; background: var(--accent-blue, #2563eb); }
    .pd-setup-steps { display: flex; flex-wrap: wrap; gap: 7px; margin-top: 11px; }
    .pd-step { font-size: 11.5px; font-weight: 700; padding: 4px 10px; border-radius: 20px; text-decoration: none;
        border: 1px solid var(--border-color); color: var(--accent-blue, #2563eb); }
    .pd-step.done { color: var(--ok-text, #047857); border-color: currentColor; opacity: .75; }
    .pd-goal { display: flex; align-items: baseline; gap: 8px; margin: 4px 0 9px; }
    .pd-goal b { font-size: 23px; font-weight: 800; color: var(--text-primary); }
    .pd-goal span { font-size: 12.5px; color: var(--text-muted); }
    .pd-goal-note { font-size: 11.5px; color: var(--text-muted); margin: 10px 0 0; }
    .pd-months { display: grid; grid-template-columns: repeat(12, 1fr); gap: 5px; align-items: end; height: 96px; }
    .pd-month { display: flex; flex-direction: column; justify-content: flex-end; align-items: center; height: 100%; gap: 4px; }
    .pd-month i { display: block; width: 100%; border-radius: 4px 4px 0 0; background: var(--accent-blue, #2563eb); }
    .pd-month span { font-size: 9px; color: var(--text-muted); }

    .pd-gap { margin-top: 13px; border: 1px solid var(--border-color); border-left: 3px solid var(--accent-blue, #2563eb);
        border-radius: 10px; padding: 11px 13px; }
    .pd-gap b { display: block; font-size: 12.5px; font-weight: 800; color: var(--text-primary); line-height: 1.35; }
    .pd-gap span { display: block; font-size: 11.5px; color: var(--text-muted); margin: 4px 0 8px; }
    .pd-gap a { font-size: 12px; font-weight: 700; color: var(--accent-blue, #2563eb); text-decoration: none; }

    /* ── Finance Hub ── */
    .pd-fin-grid { display: grid; grid-template-columns: repeat(2, minmax(0,1fr)); gap: 9px; }
    .pd-fin-box { background: var(--bg-card-hover); border: 1px solid var(--border-color); border-radius: 10px; padding: 11px 10px; }
    .pd-fin-box .k { font-size: 10px; color: var(--text-muted); margin-bottom: 4px; }
    .pd-fin-box .v { font-size: 15px; font-weight: 800; color: var(--text-primary); }
    .pd-fin-box .v.green { color: var(--ok-text); }
    .pd-fin-box .v.amber { color: var(--warn-text); }
    .pd-fin-actions { display: grid; grid-template-columns: repeat(4, 1fr); gap: 8px; margin-top: 13px; }
    .pd-fin-btn { display: flex; flex-direction: column; align-items: center; gap: 5px; padding: 10px 4px; border-radius: 9px; border: 1px solid var(--border-color); background: var(--bg-card); color: var(--text-secondary); font-size: 10px; font-weight: 600; cursor: pointer; text-decoration: none; }
    .pd-fin-btn svg { width: 16px; height: 16px; color: var(--pd-blue); }

    /* ── Today's Schedule ── */
    .pd-sch-list { display: flex; flex-direction: column; gap: 0; }
    .pd-sch-row { display: flex; align-items: center; gap: 11px; padding: 9px 0; }
    .pd-sch-ico { width: 28px; height: 28px; border-radius: 7px; background: rgba(37,99,235,0.10); color: var(--info-text); display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
    .pd-sch-ico svg { width: 14px; height: 14px; }
    .pd-sch-time { font-size: 11.5px; font-weight: 700; color: var(--text-primary); width: 56px; flex-shrink: 0; }
    .pd-sch-body { flex: 1; min-width: 0; }
    .pd-sch-name { font-size: 12px; font-weight: 600; color: var(--text-primary); }
    .pd-sch-dur { font-size: 11px; color: var(--text-muted); flex-shrink: 0; }
    .pd-sch-open { display: block; text-align: center; margin: 10px 0 14px; padding: 9px; border-radius: 9px; border: 1px solid var(--border-color); background: var(--bg-card-hover); color: var(--pd-blue); font-size: 12px; font-weight: 700; text-decoration: none; }
    .pd-map { height: 130px; border-radius: 11px; position: relative; overflow: hidden; background: linear-gradient(135deg,#dbeafe,#eff6ff); border: 1px solid var(--border-color); }
    .pd-map-grid { position: absolute; inset: 0; background-image: linear-gradient(rgba(37,99,235,0.08) 1px, transparent 1px), linear-gradient(90deg, rgba(37,99,235,0.08) 1px, transparent 1px); background-size: 24px 24px; }
    .pd-map-route { position: absolute; top: 30%; left: 18%; width: 60%; height: 40%; border-left: 3px solid #2563eb; border-bottom: 3px solid #2563eb; border-bottom-left-radius: 12px; }
    .pd-map-pin { position: absolute; width: 14px; height: 14px; }
    .pd-map-pin svg { width: 14px; height: 14px; color: var(--bad-text); }
    .pd-map-label { position: absolute; right: 10px; bottom: 8px; font-size: 10px; font-weight: 700; color: #1e40af; background: rgba(255,255,255,0.8); padding: 2px 7px; border-radius: 5px; }
    .pd-meta-row { display: flex; gap: 9px; margin-top: 11px; }
    .pd-meta-box { flex: 1; background: var(--bg-card-hover); border: 1px solid var(--border-color); border-radius: 9px; padding: 9px 10px; display: flex; align-items: center; gap: 8px; }
    .pd-meta-box svg { width: 17px; height: 17px; flex-shrink: 0; }
    .pd-meta-box .t { font-size: 11.5px; font-weight: 700; color: var(--text-primary); }
    .pd-meta-box .s { font-size: 10px; color: var(--text-muted); }
    .pd-traffic { display: flex; align-items: center; gap: 8px; margin-top: 9px; padding: 9px 10px; background: rgba(16,185,129,0.08); border: 1px solid rgba(16,185,129,0.2); border-radius: 9px; font-size: 11.5px; }
    .pd-traffic svg { width: 15px; height: 15px; color: var(--ok-text); flex-shrink: 0; }
    .pd-traffic b { color: var(--text-primary); }

    /* ── Colour, to Khadijah's reference ──────────────────────────────
       The page was built structurally correct and visually flat: every
       card was a white box with a grey border, and the reference reads
       its state off colour. Each tone below belongs to a state the data
       already knows, so nothing here decorates anything that is not
       measured. ── */

    /* Cards sit on a faint blue field rather than on white-on-white. */
    .pd .pd-card, .pd .pd-setup, .pd .pd-sumcard { border-color: var(--pd-line, rgba(37,99,235,0.14)); }
    .pd { --pd-line: rgba(37,99,235,0.14); }

    /* Summary cards: the icon tile the reference leads each card with. */
    .pd-sumcard { position: relative; }
    .pd-sum-head { display: flex; align-items: center; gap: 9px; }
    .pd-sum-ico { width: 30px; height: 30px; border-radius: 9px; flex-shrink: 0;
        display: flex; align-items: center; justify-content: center; }
    .pd-sum-ico svg { width: 15px; height: 15px; }
    .pd-sumcard .pd-sumlabel { flex: 1; min-width: 0; }
    .pd-sum-go { width: 13px; height: 13px; color: var(--text-muted); flex-shrink: 0; }
    .pd-sumval { display: flex; align-items: baseline; gap: 6px; }

    /* Setup bar, finished. Hiding it meant the one state the reference
       actually shows, "All set", was the one state we never drew. */
    .pd-setup.ok { border-color: rgba(16,185,129,0.3); background: linear-gradient(90deg, rgba(16,185,129,0.05), transparent 60%); }
    .pd-setup.ok .pd-setup-bar i { background: var(--ok-text, #047857); }
    .pd-setup-done { display: flex; flex-wrap: wrap; align-items: center; gap: 8px 16px; margin-top: 11px; }
    .pd-setup-done .tick { display: inline-flex; align-items: center; gap: 6px; font-size: 11.5px; font-weight: 700; color: var(--text-secondary, #475569); }
    .pd-setup-done .tick svg { width: 14px; height: 14px; color: var(--ok-text, #047857); flex-shrink: 0; }
    .pd-setup-all { font-size: 11.5px; font-weight: 800; color: var(--ok-text, #047857); }
    .pd-setup-cta { margin-left: auto; font-size: 11.5px; font-weight: 700; padding: 7px 13px; border-radius: 8px;
        border: 1px solid var(--pd-line, rgba(37,99,235,0.2)); color: var(--accent-blue, #2563eb); text-decoration: none; white-space: nowrap; }

    /* Needs your attention: an icon per row, and the priority as a chip. */
    .pd-attn-row { grid-template-columns: 30px minmax(0,1fr) auto auto; }
    .pd-attn-ico { width: 30px; height: 30px; border-radius: 9px; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
    .pd-attn-ico svg { width: 15px; height: 15px; }
    .pd-attn-pri { padding: 3px 9px; border-radius: 7px; font-size: 10px; }

    /* Do this first: the reference marks it, and says where it came from. */
    .pd-next-top { display: flex; align-items: center; gap: 8px; margin-bottom: 8px; }
    .pd-next-chip { font-size: 10px; font-weight: 800; padding: 3px 9px; border-radius: 7px; white-space: nowrap; }
    .pd-next-src { font-size: 10.5px; color: var(--text-muted); margin-left: auto; white-space: nowrap; }
    .pd-next.hot { border-left: 3px solid var(--bad-text, #b91c1c); padding-left: 12px;
        background: linear-gradient(90deg, rgba(239,68,68,0.05), transparent 65%); border-radius: 0 10px 10px 0; padding-top: 10px; padding-bottom: 12px; }

    /* Since you were last here: four counts, each with its own mark. */
    .pd-new > div { display: flex; align-items: center; gap: 9px; }
    .pd-new-ico { width: 28px; height: 28px; border-radius: 8px; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
    .pd-new-ico svg { width: 14px; height: 14px; }
    .pd-new-txt { min-width: 0; }

    /* The goal bar is green when it is being met, amber behind pace. */
    .pd-goalbar { height: 8px; border-radius: 5px; background: var(--border-color); overflow: hidden; }
    .pd-goalbar i { display: block; height: 100%; border-radius: 5px; background: var(--ok-text, #047857); }
    .pd-goalbar.behind i { background: var(--warn-text, #b45309); }
    .pd-goal-pace { display: flex; justify-content: space-between; gap: 10px; margin-top: 8px; font-size: 11px; }
    .pd-goal-pace .off { font-weight: 800; color: var(--warn-text, #b45309); }
    .pd-goal-pace .off.ahead { color: var(--ok-text, #047857); }
    .pd-goal-pace .el { color: var(--text-muted); }

    /* The season reads as a season: its own months stand out from the rest. */
    .pd-month i { background: rgba(37,99,235,0.28); }
    .pd-month.peak i { background: var(--accent-blue, #2563eb); }

    /* Gig Operations Hub: the thumb carries the row's state. */
    .pd-g-thumb.t-on   { background: linear-gradient(135deg,#10b981,#047857); }
    .pd-g-thumb.t-wait { background: linear-gradient(135deg,#f59e0b,#b45309); }
    .pd-g-thumb.t-off  { background: linear-gradient(135deg,#f87171,#b91c1c); }
    .pd-g-thumb.t-idle { background: linear-gradient(135deg,#94a3b8,#475569); }

    /* Today's Schedule as the reference draws it: a line down the times. */
    .pd-sch-row { position: relative; padding-left: 2px; }
    .pd-sch-dot { width: 11px; height: 11px; border-radius: 50%; flex-shrink: 0; position: relative; z-index: 1;
        background: var(--accent-blue, #2563eb); box-shadow: 0 0 0 3px rgba(37,99,235,0.16); }
    .pd-sch-dot.done { background: var(--ok-text, #047857); box-shadow: 0 0 0 3px rgba(16,185,129,0.16); }
    .pd-sch-row:not(:last-of-type)::before { content: ''; position: absolute; left: 7px; top: 50%; bottom: -4px;
        width: 2px; background: var(--border-color); }
    .pd-sch-dur { font-weight: 700; color: var(--text-secondary, #475569); }

    /* Finance Hub: one headline figure, then what it is made of. */
    .pd-fin-lead { border: 1px solid var(--pd-line, rgba(37,99,235,0.14)); border-radius: 12px; padding: 13px 14px;
        background: linear-gradient(135deg, rgba(16,185,129,0.07), rgba(37,99,235,0.04)); margin-bottom: 11px; }
    .pd-fin-lead .k { font-size: 11px; font-weight: 700; color: var(--text-muted); }
    .pd-fin-lead .v { font-size: 27px; font-weight: 800; color: var(--text-primary); letter-spacing: -0.02em; margin: 3px 0 2px; }
    .pd-fin-lead .s { font-size: 11px; color: var(--text-muted); }
    .pd-fin-split { display: grid; grid-template-columns: repeat(3, minmax(0,1fr)); gap: 9px; }
    .pd-fin-cell { display: flex; align-items: center; gap: 9px; min-width: 0; }
    .pd-fin-cell .ic { width: 28px; height: 28px; border-radius: 8px; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
    .pd-fin-cell .ic svg { width: 14px; height: 14px; }
    .pd-fin-cell .tx { min-width: 0; }
    .pd-fin-cell .tx b { display: block; font-size: 14px; font-weight: 800; color: var(--text-primary); }
    .pd-fin-cell .tx span { display: block; font-size: 10px; color: var(--text-muted); }
    @media (max-width: 560px) { .pd-fin-split { grid-template-columns: 1fr; } }

    @media (min-width: 1680px) { .pd-stats { grid-template-columns: repeat(8, minmax(0,1fr)); } }
    @media (max-width: 1200px) { .pd-row-1, .pd-row-2 { grid-template-columns: 1fr; } }
    @media (max-width: 760px)  { .pd-stats { grid-template-columns: repeat(2, minmax(0,1fr)); } .pd-fin-grid { grid-template-columns: 1fr 1fr; } }
</style>
@endpush

@section('content')
@include('partials._service_origin_prompt')
<div class="pd">

    {{-- What is still missing before the marketplace can work for them.

         Khadijah's screen has a Visibility Score beside this. There is no
         score here: nothing measures how visible a professional is, so a
         number out of 100 would be a figure about their own business that
         nobody worked out. This lists the things that are actually missing
         instead, and every line opens the page that fixes it. --}}
    @php $pdSetupOk = $setup['done'] >= $setup['total']; @endphp
    <div class="pd-setup {{ $pdSetupOk ? 'ok' : '' }}">
        <div class="pd-setup-h">
            <b>{{ $pdSetupOk ? 'Business setup' : 'Finish setting up' }}</b>
            <span>{{ $setup['done'] }} of {{ $setup['total'] }} {{ $pdSetupOk ? 'completed' : 'done' }}</span>
        </div>
        <div class="pd-setup-bar"><i style="width: {{ round($setup['done'] / $setup['total'] * 100) }}%;"></i></div>
        @if ($pdSetupOk)
            {{-- The finished state. This block used to be hidden the moment
                 the last step was done, so the one state the reference screen
                 actually shows was the one we never drew, and a professional
                 who had completed everything saw the row vanish rather than
                 be told they were ready. --}}
            <div class="pd-setup-done">
                @foreach ($setup['steps'] as $step)
                    <span class="tick">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="12" cy="12" r="10"/><polyline points="8 12 11 15 16 9"/></svg>
                        {{ $step['label'] }}
                    </span>
                @endforeach
                <span class="pd-setup-all">All set, your profile is complete.</span>
                <a class="pd-setup-cta" href="{{ route('professional.profile.index') }}">View profile and settings</a>
            </div>
        @else
            <div class="pd-setup-steps">
                @foreach ($setup['steps'] as $step)
                    @if ($step['done'])
                        <span class="pd-step done">{{ $step['label'] }}</span>
                    @else
                        <a class="pd-step" href="{{ $step['to'] }}">{{ $step['label'] }}</a>
                    @endif
                @endforeach
            </div>
        @endif
    </div>

    {{-- Four cards, each carrying the parts it is made of.

         Eight flat figures stood here. Khadijah's screen has four, and each
         says what it is made of underneath: a "6" on its own tells somebody
         six things are waiting without saying what any of them are, which is
         the same fault the Priority Actions page had. --}}
    @php
        /*
         * Each card carries a tone, and until now the view ignored it: the
         * array below has said 'c-orange' since the day it was written and
         * nothing ever drew it, so four identically grey cards stood where
         * the reference has four coloured ones.
         */
        $pdCards = [
            ['key' => 'response', 'label' => 'Needs your response', 'tone' => 'c-orange', 'icon' => 'chat'],
            ['key' => 'upcoming', 'label' => 'Upcoming gigs',       'tone' => 'c-blue',   'icon' => 'calendar'],
            ['key' => 'payments', 'label' => 'Payments waiting',    'tone' => 'c-green',  'icon' => 'wallet'],
            ['key' => 'today',    'label' => "Today's commitments", 'tone' => 'c-purple', 'icon' => 'clock'],
        ];
    @endphp
    <div class="pd-sum">
        @foreach($pdCards as $card)
            @php $c = $summary[$card['key']]; @endphp
            <a class="pd-sumcard" href="{{ $c['to'] }}">
                <span class="pd-sum-head">
                    <span class="pd-sum-ico {{ $card['tone'] }}">@include('professional._pd-icon', ['name' => $card['icon']])</span>
                    <span class="pd-sumlabel">{{ $card['label'] }}</span>
                    <svg class="pd-sum-go" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="9 18 15 12 9 6"/></svg>
                </span>
                <span class="pd-sumval">{{ $c['count'] }}</span>
                <span class="pd-sumparts">{{ $c['parts'] ? implode(' · ', $c['parts']) : 'Nothing right now' }}</span>
                @if($c['note'])
                    <span class="pd-sumnote">{{ $c['note'] }}</span>
                @endif
            </a>
        @endforeach
    </div>

    {{-- Needs your attention, the one to do first, and what has changed. --}}
    <div class="pd-row pd-row-attn">
        <div class="pd-card">
            <div class="pd-card-head">
                <span class="pd-card-ico c-orange"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/></svg></span>
                <span class="pd-card-title">Needs your attention</span>
                <span class="pd-pill-count">{{ $waiting->count() }}</span>
                <a href="{{ route('professional.priority.index') }}" class="pd-card-link">View all</a>
            </div>
            @php
                /*
                 * What each kind of waiting thing looks like, and what colour
                 * its urgency is. The row used to print the priority as bare
                 * coloured text with no mark beside it, so five rows read as
                 * five identical lines and the reference's at-a-glance scan
                 * was not possible.
                 */
                $pdKindIcon = [
                    'Agreement'  => ['file',   'c-blue'],
                    'Request'    => ['inbox',  'c-purple'],
                    'Staffing'   => ['users',  'c-orange'],
                    'Bid'        => ['tag',    'c-cyan'],
                    'Package'    => ['box',    'c-indigo'],
                    'Scheduling' => ['alert',  'c-orange'],
                    'Payment'    => ['dollar', 'c-green'],
                ];
                /*
                 * Through the theme's own variables, not raw hex.
                 *
                 * A first pass wrote #b91c1c straight into the chip, which is
                 * a red chosen to be read on white. On the dark theme it sat
                 * at 2.7 to 1 against the card behind it, below the 4.5 a
                 * small label needs, so the urgency tag was the hardest thing
                 * on the page to read on the half of it that is dark. The
                 * variables already resolve to a different value per theme;
                 * the hex is only the fallback if one is ever missing.
                 */
                $pdTones = [
                    'urgent' => ['High',     'var(--bad-text, #b91c1c)',       'rgba(239,68,68,0.14)'],
                    'soon'   => ['Due soon', 'var(--warn-text, #b45309)',      'rgba(245,158,11,0.16)'],
                    'new'    => ['New',      'var(--info-text, #1d4ed8)',      'rgba(37,99,235,0.14)'],
                ];
            @endphp
            <div class="pd-attn">
                @forelse($waiting->take(5) as $item)
                    @php
                        $pdTone = $pdTones[$item['tone']] ?? ['Open', 'var(--text-secondary, #475569)', 'rgba(100,116,139,0.18)'];
                        [$pdIcoName, $pdIcoTone] = $pdKindIcon[$item['kind']] ?? ['bolt', 'c-blue'];
                    @endphp
                    <div class="pd-attn-row" style="border-left-color: {{ $pdTone[1] }};">
                        <span class="pd-attn-ico {{ $pdIcoTone }}">@include('professional._pd-icon', ['name' => $pdIcoName])</span>
                        <div>
                            <b>{{ $item['title'] }}</b>
                            @if($item['subject'])<span>{{ $item['subject'] }}</span>@endif
                        </div>
                        <span class="pd-attn-pri" style="color: {{ $pdTone[1] }}; background: {{ $pdTone[2] }};">{{ $pdTone[0] }}</span>
                        <a class="pd-attn-go" href="{{ $item['url'] }}">{{ $item['cta'] }}</a>
                    </div>
                @empty
                    <p class="pd-quiet">Nothing needs you right now.</p>
                @endforelse
            </div>
        </div>

        <div class="pd-stack">
            <div class="pd-card">
                <div class="pd-card-head">
                    <span class="pd-card-ico c-purple"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"/></svg></span>
                    <span class="pd-card-title">Do this first</span>
                </div>
                @if($nextBest)
                    @php
                        $pdNextTone = $pdTones[$nextBest['tone']] ?? ['Open', 'var(--text-secondary, #475569)', 'rgba(100,116,139,0.18)'];
                    @endphp
                    <div class="pd-next {{ $nextBest['tone'] === 'urgent' ? 'hot' : '' }}">
                        {{-- The reference marks this card with the priority
                             and names where the item came from, so somebody
                             can tell why this one is first. --}}
                        <div class="pd-next-top">
                            <span class="pd-next-chip" style="color: {{ $pdNextTone[1] }}; background: {{ $pdNextTone[2] }};">{{ $pdNextTone[0] }} priority</span>
                            <span class="pd-next-src">Source: {{ $nextBest['kind'] }}</span>
                        </div>
                        <b>{{ $nextBest['title'] }}</b>
                        @if($nextBest['subject'])<span>{{ $nextBest['subject'] }}</span>@endif
                        @if($nextBest['when'])<i>{{ $nextBest['when'] }}</i>@endif
                        <a href="{{ $nextBest['url'] }}">{{ $nextBest['cta'] }}</a>
                    </div>
                @else
                    <p class="pd-quiet">Nothing is waiting, so there is nothing to put first.</p>
                @endif
            </div>

            {{-- Measured from the last time this page was opened, not from the
                 last request made, or the answer would always be nothing. --}}
            <div class="pd-card">
                <div class="pd-card-head">
                    <span class="pd-card-ico c-blue"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg></span>
                    <span class="pd-card-title">Since you were last here</span>
                </div>
                @if($sinceLast)
                    @php
                        $pdNewIcon = [
                            'New requests'    => ['inbox', 'c-purple'],
                            'Bids you placed' => ['tag',   'c-cyan'],
                            'Messages'        => ['chat',  'c-blue'],
                            'Reviews'         => ['star',  'c-orange'],
                        ];
                    @endphp
                    <div class="pd-new">
                        @foreach($sinceLast as $row)
                            @php [$pdN, $pdT] = $pdNewIcon[$row['label']] ?? ['bolt', 'c-blue']; @endphp
                            <div>
                                <span class="pd-new-ico {{ $pdT }}">@include('professional._pd-icon', ['name' => $pdN])</span>
                                <span class="pd-new-txt"><b>{{ $row['count'] }}</b><span>{{ $row['label'] }}</span></span>
                            </div>
                        @endforeach
                    </div>
                @else
                    <p class="pd-quiet">Nothing has changed since you last opened this page.</p>
                @endif
            </div>
        </div>

        {{-- The third column of Khadijah's second row.

             Both cards used to sit at the bottom of the page and disappear
             when they had nothing to say, so the column was not there at
             all. They stay now and name what is missing instead: a goal
             nobody has set is an invitation, not an empty space. --}}
        <div class="pd-stack">
        {{-- This month against what they said they were aiming for.

             No target is ever invented. A dashboard that makes one up and
             then reports somebody behind it is making up the thing it
             measures them against. Where none has been set the card says so
             and offers to take one, rather than disappearing and leaving a
             hole in the row. --}}
        @if ($goal)
            <div class="pd-card">
                <div class="pd-card-h">
                    <span class="pd-card-title">This month against your goal</span>
                    <a class="pd-card-link" href="{{ route('professional.services.index', ['tab' => 'rules']) }}">Change it</a>
                </div>
                <div class="pd-goal">
                    <b>${{ number_format($goal['earned'], 2) }}</b>
                    <span>of ${{ number_format($goal['target'], 2) }}</span>
                    <span style="margin-left:auto;font-weight:800;color:var(--text-primary);">{{ $goal['percent'] }}%</span>
                </div>
                {{-- Green when the month is keeping up, amber when it is not.
                     A single blue bar said the same thing at 10% and at 90%. --}}
                <div class="pd-goalbar {{ $goal['off'] < 0 ? 'behind' : '' }}"><i style="width: {{ $goal['percent'] }}%;"></i></div>
                <div class="pd-goal-pace">
                    <span class="off {{ $goal['off'] >= 0 ? 'ahead' : '' }}">
                        ${{ number_format(abs($goal['off']), 2) }} {{ $goal['off'] >= 0 ? 'ahead of pace' : 'behind pace' }}
                    </span>
                    <span class="el">{{ $goal['elapsed'] }}% of the month gone ({{ $goal['day'] }} of {{ $goal['days'] }} days)</span>
                </div>
                <p class="pd-goal-note">
                    Counting work completed this month, after fees.
                </p>
            </div>
        @else
            <div class="pd-card">
                <div class="pd-card-h">
                    <span class="pd-card-title">Monthly goal</span>
                </div>
                <p class="pd-goal-note" style="margin-top:0;">
                    You have not set one. Name a figure and this card shows how the month is going against it.
                </p>
                <a class="pd-goalset" href="{{ route('professional.services.index', ['tab' => 'rules']) }}">Set a monthly goal</a>
            </div>
        @endif

        {{-- The months their own work actually falls in. Shown only once
             there is enough of their own history for it to mean anything. --}}
        @if ($busiest)
            <div class="pd-card">
                <div class="pd-card-h">
                    <span class="pd-card-title">When your work falls</span>
                </div>
                @php
                    $pdMax = collect($busiest)->max('count') ?: 1;
                    // A month in the top half of their own year is part of
                    // the season; the rest are the quiet months behind it.
                    $pdPeak = $pdMax / 2;
                @endphp
                <div class="pd-months">
                    @foreach ($busiest as $m)
                        <div class="pd-month {{ $m['count'] >= $pdPeak && $m['count'] > 0 ? 'peak' : '' }}" title="{{ $m['count'] }} {{ \Illuminate\Support\Str::plural('booking', $m['count']) }}">
                            <i style="height: {{ max(3, round($m['count'] / $pdMax * 100)) }}%;"></i>
                            <span>{{ $m['label'] }}</span>
                        </div>
                    @endforeach
                </div>
                <p class="pd-goal-note">Your own bookings, by the month the job happens in.</p>
            </div>
        @else
            <div class="pd-card">
                <div class="pd-card-h">
                    <span class="pd-card-title">When your work falls</span>
                </div>
                <p class="pd-goal-note" style="margin-top:0;">
                    Not enough of your own bookings yet. A handful of jobs does not make a season, so this
                    waits rather than drawing one.
                </p>
            </div>
        @endif
        </div>
    </div>


    {{-- ════════ Row 1 ════════ --}}
    <div class="pd-row pd-row-1">

        {{-- Opportunity Feed — Rule R61, Option B --}}
        @include('professional._opportunity-feed')

        {{-- The Priority Actions card that stood here is gone. "Needs your
             attention" above shows the same list, naming each job rather than
             counting it, so the page was telling somebody the same thing
             twice and disagreeing with itself on the detail. --}}


        {{-- Gig Operations Hub --}}
        <div class="pd-card">
            <div class="pd-card-head">
                <span class="pd-card-ico c-blue"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="7" width="20" height="14" rx="2"/><path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"/></svg></span>
                <span class="pd-card-title">Gig Operations Hub</span>
                <a href="{{ route('professional.gig-hub.index', ['tab' => 'gigs']) }}" class="pd-card-link">View All Gigs <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg></a>
            </div>
            <div class="pd-gtable-wrap">
            <table class="pd-gtable">
                {{-- Five invented jobs stood here, with a client's name on each
                     one: Corporate Gala Dinner for ABC Corporation, a wedding
                     for Emily & Michael, and so on, together with staffing
                     counts and progress rings for work nobody had done. The
                     controller was loading this professional's five most recent
                     bookings the whole time and the table never used them.

                     Staff and Progress are gone rather than guessed: a booking
                     carries no progress figure, and the crew assigned to one is
                     the point of contact, which the gig page shows properly. --}}
                <thead><tr><th>Event / Client</th><th>Date</th><th>Amount</th><th>Status</th></tr></thead>
                <tbody>
                    @forelse($recentBookings as $__b)
                        @php
                            /*
                             * One class for every status printed green, so a
                             * cancelled booking wore the colour that means it
                             * went well. Worked out here, before the row is
                             * drawn, because the thumb carries it too: every
                             * thumb was the same blue whatever had happened.
                             */
                            [$__tone, $__thumb] = match ($__b->status) {
                                'confirmed', 'completed' => ['st-confirmed', 't-on'],
                                'cancelled', 'declined'  => ['st-off', 't-off'],
                                'requested', 'pending'   => ['st-waiting', 't-wait'],
                                default                  => ['st-quiet', 't-idle'],
                            };
                        @endphp
                        <tr>
                            <td>
                                <div class="pd-g-event">
                                    <span class="pd-g-thumb {{ $__thumb }}"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="3" y1="10" x2="21" y2="10"/></svg></span>
                                    <div>
                                        <div class="pd-g-name">{{ $__b->event?->title ?? 'Untitled event' }}</div>
                                        <div class="pd-g-client">{{ $__b->client?->name ?? 'Client removed' }}</div>
                                    </div>
                                </div>
                            </td>
                            <td class="pd-g-date">{{ $__b->event?->starts_at?->format('M d, Y') ?? 'No date set' }}</td>
                            <td class="pd-g-budget">${{ number_format((float) $__b->price, 2) }}</td>
                            <td><span class="pd-g-status {{ $__tone }}">{{ ucfirst($__b->status) }}</span></td>
                        </tr>
                    @empty
                        <tr><td colspan="4" style="padding:18px;text-align:center;color:var(--text-muted);font-size:12px;">Nothing booked yet. Gigs you win appear here.</td></tr>
                    @endforelse
                </tbody>
            </table>
            </div>
            <div class="pd-ghub-foot"><a href="{{ route('professional.gig-hub.index') }}">Manage All Gigs <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg></a></div>
        </div>
        {{-- Today's Schedule --}}
        <div class="pd-card">
            <div class="pd-card-head">
                <span class="pd-card-ico c-blue"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg></span>
                <span class="pd-card-title">Today's Schedule</span>
                <a href="{{ route('professional.calendar.index') }}" class="pd-card-link">View Calendar <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg></a>
            </div>
            <div class="pd-sch-list">
                @forelse($todaySchedule as $__t)
                    @php
                        /*
                         * How long the job runs, rather than the finish time.
                         * The row printed the start and the end a few pixels
                         * apart in the same format, which reads as two times
                         * and answers neither "when" nor "how long" clearly.
                         */
                        $__from = $__t->event?->starts_at;
                        $__to   = $__t->event?->ends_at;
                        $__mins = $__from && $__to ? max(0, $__from->diffInMinutes($__to)) : null;
                        $__len  = $__mins === null ? null
                            : ($__mins >= 60
                                ? round($__mins / 60, ($__mins % 60 === 0 ? 0 : 1)) . ' hr'
                                : $__mins . ' min');
                    @endphp
                    <div class="pd-sch-row">
                        <span class="pd-sch-dot {{ $__from && $__from->isPast() ? 'done' : '' }}"></span>
                        <span class="pd-sch-time">{{ $__from?->format('g:i A') ?? '' }}</span>
                        <span class="pd-sch-body"><span class="pd-sch-name">{{ $__t->event?->title ?? 'Untitled event' }}</span></span>
                        @if($__len)<span class="pd-sch-dur">{{ $__len }}</span>@endif
                    </div>
                @empty
                    <p style="font-size:12px;color:var(--text-muted);margin:0;padding:4px 0;">Nothing booked for today.</p>
                @endforelse
            </div>
            <a href="{{ route('professional.calendar.index') }}" class="pd-sch-open">Open Full Calendar →</a>
            {{-- A drawn map with two pins, "84°F Partly Cloudy", "20% Rain"
                 and "Traffic is light · 25 min to venue" stood here. None of
                 it came from anywhere: there is no map or weather provider
                 connected, which is one of the open items with Sir Peter.
                 Put it back when there is something real behind it. --}}
            </div>
        </div>
    </div>

    {{-- ════════ Row 2 ════════ --}}
    <div class="pd-row pd-row-2">

        {{-- Bid Intelligence --}}
        <div class="pd-card">
            <div class="pd-card-head">
                <span class="pd-card-ico c-purple"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 20V10"/><path d="M12 20V4"/><path d="M6 20v-6"/></svg></span>
                <span class="pd-card-title">Bid Intelligence</span>
                <a href="{{ route('professional.bid-intelligence.index') }}" class="pd-card-link">View Full Pipeline <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg></a>
            </div>
            @php
                /*
                 * The ring, from the same counts the legend prints. Each
                 * status takes the share of the circle its own count is
                 * worth, so the two halves of this card can never disagree.
                 */
                $pdAt = 0.0;
                $pdStops = [];
                foreach ($bidBreakdown as $__seg) {
                    $pdTo = $bidTotal > 0 ? $pdAt + ($__seg['count'] / $bidTotal * 100) : 0;
                    $pdStops[] = $__seg['color'].' '.round($pdAt, 2).'% '.round($pdTo, 2).'%';
                    $pdAt = $pdTo;
                }
                $pdRing = $pdStops ? 'conic-gradient('.implode(', ', $pdStops).')' : null;
            @endphp
            <div class="pd-bid-body">
                <div class="pd-donut" @if($pdRing) style="background: {{ $pdRing }};" @endif><div class="pd-donut-c"><b>{{ $bidTotal }}</b><span>{{ $bidTotal === 1 ? 'Bid' : 'Bids' }}</span></div></div>
                <div class="pd-bid-legend">
                    @forelse($bidBreakdown as $__row)
                        <div class="pd-bid-leg">
                            <span class="pd-bid-dot" style="background:{{ $__row['color'] }};"></span>
                            <span class="nm">{{ $__row['label'] }}</span>
                            <span class="ct">{{ $__row['count'] }}</span>
                            <span class="pc">({{ $bidTotal > 0 ? (int) round($__row['count'] / $bidTotal * 100) : 0 }}%)</span>
                        </div>
                    @empty
                        <p style="font-size:12px;color:var(--text-muted);margin:0;">You have not bid on anything yet.</p>
                    @endforelse
                </div>
            </div>
            {{-- Khadijah's screen has a recommendation here, "Add Lighting
                 Services to match 5 new open bids".

                 This is that, counted rather than suggested: a service named
                 by open requests in this professional's own state that they
                 do not offer, with how many ask for it. Both halves can be
                 checked against the bidding board.

                 What used to sit here was "wedding packages are winning 21%
                 more than DJ-only packages". Nothing measured that, and a
                 figure about what wins work is one somebody prices against. --}}
            @if($gap)
                <div class="pd-gap">
                    <b>{{ $gap['count'] }} open {{ \Illuminate\Support\Str::plural('request', $gap['count']) }} ask for {{ $gap['name'] }}</b>
                    <span>You do not offer it yet. Adding it puts you in front of those requests.</span>
                    <a href="{{ route('professional.services.index') }}">Add it to your services</a>
                </div>
            @endif
        </div>


        {{-- Finance Hub --}}
        <div class="pd-card">
            <div class="pd-card-head">
                <span class="pd-card-ico c-green"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="17 1 21 5 17 9"/><path d="M3 11V9a4 4 0 0 1 4-4h14"/><polyline points="7 23 3 19 7 15"/><path d="M21 13v2a4 4 0 0 1-4 4H3"/></svg></span>
                <span class="pd-card-title">Finance Hub</span>
            </div>
            {{-- The headline, then what it is made of.

                 Four boxes of equal weight stood here, so the figure that
                 answers "what am I owed in total" looked exactly like the
                 three it is made from. The reference leads with the total and
                 breaks it down underneath, which is the same four numbers
                 read in the order somebody actually asks them.

                 The total is addition, not a prediction: the balance they can
                 withdraw now plus the work already agreed. "Secure Payment
                 Held", "Taxes Owed" and "Est. Profit (MTD)" were $7,850,
                 $1,250 and $6,450 typed into the page, and are not here,
                 because there is no escrow balance, no tax figure and no cost
                 side to profit anywhere in the system. --}}
            <div class="pd-fin-lead">
                <div class="k">Yours once everything settles</div>
                <div class="v">${{ number_format($cash['forecast'], 2) }}</div>
                <div class="s">Available now plus work already agreed, after fees.</div>
            </div>
            <div class="pd-fin-split">
                <div class="pd-fin-cell">
                    <span class="ic c-green">@include('professional._pd-icon', ['name' => 'wallet'])</span>
                    <span class="tx"><b>${{ number_format($stats['available_balance'] ?? 0, 2) }}</b><span>Available balance</span></span>
                </div>
                <div class="pd-fin-cell">
                    <span class="ic c-orange">@include('professional._pd-icon', ['name' => 'clock'])</span>
                    <span class="tx"><b>${{ number_format($stats['pending_payout'] ?? 0, 2) }}</b><span>Pending payout</span></span>
                </div>
                <div class="pd-fin-cell">
                    <span class="ic c-blue">@include('professional._pd-icon', ['name' => 'trend'])</span>
                    <span class="tx"><b>${{ number_format($stats['this_month_earnings'] ?? 0, 2) }}</b><span>Earned this month</span></span>
                </div>
            </div>
            <div class="pd-fin-actions">
                <a href="{{ route('professional.transactions.index') }}" class="pd-fin-btn"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>Invoices</a>
            </div>
        </div>

    </div>
</div>
@endsection
