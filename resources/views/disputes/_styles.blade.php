{{--
    Rule R34 Phase 2 — shared styling for the party-facing dispute screens.

    One partial rather than three copies: the client and the professional see
    the same module in different chrome, and a badge that means "Under Review"
    on one page must not be a different colour on the other.
--}}
<style>
    .dsp-head   { display:flex; justify-content:space-between; align-items:flex-end; gap:16px; flex-wrap:wrap; margin-bottom:18px; }
    .dsp-h1     { font-size:22px; font-weight:800; margin:0; }
    .dsp-sub    { font-size:13px; color:var(--text-muted); margin:4px 0 0; line-height:1.5; }
    .dsp-card   { background:var(--bg-card); border:1px solid var(--border-color); border-radius:13px; padding:16px 18px; margin-bottom:14px; }
    .dsp-sec    { font-size:11px; font-weight:800; text-transform:uppercase; letter-spacing:.05em; color:var(--text-muted); margin:0 0 10px; }
    .dsp-two    { display:grid; grid-template-columns:minmax(0,2fr) minmax(0,1fr); gap:16px; align-items:start; }
    @media (max-width:900px) { .dsp-two { grid-template-columns:1fr; } }

    .dsp-ref    { font-family:ui-monospace,SFMono-Regular,Menlo,monospace; font-size:13px; font-weight:700; }
    .dsp-row    { display:flex; justify-content:space-between; gap:12px; padding:9px 0; border-top:1px solid var(--border-color); font-size:13.5px; }
    .dsp-row:first-of-type { border-top:0; }
    .dsp-row dt { color:var(--text-muted); }
    .dsp-row dd { margin:0; text-align:right; font-weight:600; }

    /* Case states. Deliberately muted — a dispute screen that shouts red at
       both parties makes a scope disagreement feel like an accusation. */
    .dsp-badge  { display:inline-block; padding:3px 9px; border-radius:999px; font-size:11px; font-weight:700; border:1px solid transparent; }
    .dsp-open   { background:rgba(245,158,11,.12);  color:#b45309; border-color:rgba(245,158,11,.3); }
    .dsp-review { background:rgba(59,130,246,.12);  color:#1d4ed8; border-color:rgba(59,130,246,.3); }
    .dsp-done   { background:rgba(16,185,129,.12);  color:#047857; border-color:rgba(16,185,129,.3); }
    .dsp-shut   { background:var(--bg-hover,rgba(120,120,120,.1)); color:var(--text-muted); border-color:var(--border-color); }

    .dsp-table  { width:100%; border-collapse:collapse; font-size:13.5px; }
    .dsp-table th { text-align:left; font-size:11px; text-transform:uppercase; letter-spacing:.04em; color:var(--text-muted); padding:0 10px 8px 0; font-weight:700; }
    .dsp-table td { padding:11px 10px 11px 0; border-top:1px solid var(--border-color); vertical-align:top; }

    .dsp-field  { margin-bottom:13px; }
    .dsp-label  { display:block; font-size:12.5px; font-weight:700; margin-bottom:5px; }
    .dsp-hint   { font-size:11.5px; color:var(--text-muted); margin-top:4px; line-height:1.5; }
    .dsp-input, .dsp-select, .dsp-area {
        width:100%; padding:9px 11px; border:1px solid var(--border-color); border-radius:9px;
        background:var(--bg-card); color:var(--text-primary); font-size:13.5px; font-family:inherit;
    }
    .dsp-area   { min-height:110px; resize:vertical; line-height:1.55; }

    /* §1 — a certification is an electronic signature under ESIGN/UETA, so it
       gets the weight of one on the page rather than sitting inline as a
       throwaway checkbox. Never pre-ticked. */
    .dsp-cert   { display:flex; gap:10px; align-items:flex-start; padding:12px 14px; border:1px solid var(--border-color);
                  border-radius:10px; background:var(--bg-hover,rgba(120,120,120,.04)); font-size:12.5px; line-height:1.55; }
    .dsp-cert input { margin-top:2px; flex-shrink:0; }

    .dsp-time   { border-left:2px solid var(--border-color); padding-left:14px; margin-left:4px; }
    .dsp-ev     { padding:11px 0; border-top:1px solid var(--border-color); font-size:13px; line-height:1.55; }
    .dsp-ev:first-child { border-top:0; }
    .dsp-when   { font-size:11px; color:var(--text-muted); }
    .dsp-empty  { padding:34px 20px; text-align:center; color:var(--text-muted); font-size:13.5px; line-height:1.6; }
    .dsp-err    { color:#b91c1c; font-size:12px; margin-top:4px; }
    .dsp-flash  { padding:11px 14px; border-radius:10px; background:rgba(16,185,129,.1); border:1px solid rgba(16,185,129,.3);
                  color:#047857; font-size:13px; margin-bottom:14px; }
    .dsp-strike { text-decoration:line-through; opacity:.55; }
</style>
