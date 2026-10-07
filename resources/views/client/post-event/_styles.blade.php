{{-- Shared design system for the Post-an-Event 11-step flow. Pushed once per page. --}}
@push('styles')
<style>
    .pe-wrap { --pe-orange:#f97316; --pe-orange-d:#ea580c; --pe-orange-t:#c2410c; --pe-purple-t:#5b21b6; --pe-info-t:#4338ca; --pe-purple:#7c3aed; --pe-purple-l:#ede9fe;
        --pe-green:#16a34a; --pe-green-l:#dcfce7; --pe-ink:#111827; --pe-ink-2:#374151; --pe-muted:#6b7280;
        --pe-line:#e5e7eb; --pe-line-2:#f1f5f9; --pe-bg:#f8fafc; --pe-card:#ffffff;
        /* No background of its own. It painted #f8fafc, which was invisible
           while the page shell was also white — since the shell moved off
           white it showed as a pale block behind the cards. The shell is the
           layout's job; a page does not paint its own. */
        min-height:100%; color:var(--pe-ink); }

    /*
     * The same names in the dark shell.
     *
     * These were declared once, in light, and the eleven-step flow is built
     * entirely out of them — so with the theme switched the whole of Post an
     * Event stayed a white page inside a dark app. Every surface here reads
     * from a variable, which is why this is one block rather than a sweep of
     * the eleven views.
     *
     * The colours that mean something keep meaning it: orange is the brand,
     * green is done, purple is the toolkit. They are lifted a step because
     * the mid-tone that reads well on white does not on near-black, and
     * their pale tints become dark tints of the same hue.
     */
    [data-theme="dark"] .pe-wrap {
        --pe-orange-t:#fb923c; --pe-purple-t:#d8b4fe; --pe-info-t:#a5b4fc;
        --pe-purple:#a855f7; --pe-purple-l:rgba(168,85,247,.16);
        --pe-green:#22c55e;  --pe-green-l:rgba(34,197,94,.16);
        --pe-ink:#ffffff; --pe-ink-2:#e2e8f0; --pe-muted:#94a3b8;
        --pe-line:rgba(255,255,255,.08); --pe-line-2:rgba(255,255,255,.05);
        --pe-bg:#0a0e1a; --pe-card:#111827;
    }
    .pe-wrap * { box-sizing:border-box; }
    /*
     * The Post an Event pages sat in a column of their own — capped at 1280
     * and padded by another 24px — inside the page column they were already
     * in. So their centre body started and ended somewhere no other client
     * page does. The page already has a column; this stays inside it.
     */
    .pe-container { width:100%; }

    /* ── wizard progress bar ── */
    .pe-wizard { display:flex; align-items:center; gap:0; overflow-x:auto; padding:18px 24px; background:var(--pe-card);
        border-bottom:1px solid var(--pe-line); scrollbar-width:none; }
    .pe-wizard::-webkit-scrollbar { display:none; }
    .pe-step { display:flex; align-items:center; gap:8px; flex:0 0 auto; }
    .pe-step-dot { width:26px; height:26px; border-radius:50%; display:flex; align-items:center; justify-content:center;
        font-size:12px; font-weight:800; background:var(--pe-line-2); color:var(--pe-muted); border:2px solid var(--pe-line-2); flex-shrink:0; }
    .pe-step-label { font-size:12.5px; font-weight:700; color:var(--pe-muted); white-space:nowrap; }
    .pe-step.active .pe-step-dot { background:var(--pe-orange); border-color:var(--pe-orange); color:#fff; }
    .pe-step.active .pe-step-label { color:var(--pe-orange-t); }
    .pe-step.done .pe-step-dot { background:var(--pe-green); border-color:var(--pe-green); color:#fff; }
    .pe-step.done .pe-step-label { color:var(--pe-ink-2); }
    .pe-step-line { flex:1 1 22px; min-width:22px; height:2px; background:var(--pe-line); margin:0 8px; }
    .pe-step-line.done { background:var(--pe-green); }

    /* ── layout ── */
    .pe-main { padding:26px 0 60px; }
    .pe-h1 { font-size:26px; font-weight:800; letter-spacing:-.6px; margin:0 0 4px; display:flex; align-items:center; gap:8px; }
    .pe-sub { color:var(--pe-muted); font-size:14px; margin:0 0 22px; }
    .pe-grid { display:grid; grid-template-columns:minmax(0,1fr) var(--cl-rail); gap:var(--cl-rail-gap); align-items:start; }
    @media (max-width:980px){ .pe-grid { grid-template-columns:minmax(0,1fr); } }

    /* ── cards ── */
    .pe-card { background:var(--pe-card); border:1px solid var(--pe-line); border-radius:16px; padding:22px; margin-bottom:18px; }
    .pe-card h2, .pe-card h3 { margin:0 0 4px; font-weight:800; }
    .pe-card h2 { font-size:18px; } .pe-card h3 { font-size:15px; }
    .pe-label { display:block; font-size:12.5px; font-weight:700; color:var(--pe-ink-2); margin-bottom:6px; }
    .pe-req { color:var(--pe-orange); }
    .pe-optional { font-weight:500; color:var(--text-muted); font-size:11.5px; }
    .pe-input, .pe-select, .pe-textarea { width:100%; border:1px solid var(--pe-line); border-radius:10px; padding:11px 12px;
        font-size:14px; font-family:inherit; color:var(--pe-ink); background:var(--pe-card); outline:none; }
    .pe-input:focus, .pe-select:focus, .pe-textarea:focus { border-color:var(--pe-orange); box-shadow:0 0 0 3px rgba(249,115,22,.12); }
    .pe-textarea { min-height:90px; resize:vertical; }
    .pe-field { margin-bottom:16px; }
    .pe-row { display:grid; grid-template-columns:1fr 1fr; gap:14px; }
    @media (max-width:640px){ .pe-row { grid-template-columns:1fr; } }

    /* ── buttons ── */
    .pe-btn { display:inline-flex; align-items:center; justify-content:center; gap:8px; border:none; cursor:pointer;
        border-radius:11px; padding:12px 22px; font-size:14px; font-weight:800; font-family:inherit; text-decoration:none;
        background:linear-gradient(135deg,var(--pe-orange),var(--pe-orange-d)); color:#fff; }
    .pe-btn:hover { filter:brightness(1.04); }
    .pe-btn-ghost { background:var(--pe-card); color:var(--pe-ink-2); border:1px solid var(--pe-line); }
    .pe-btn-purple { background:linear-gradient(135deg,var(--pe-purple),#6d28d9); color:#fff; }
    .pe-btn svg { width:16px; height:16px; }
    .pe-actions { display:flex; align-items:center; justify-content:space-between; gap:12px; flex-wrap:wrap; margin-top:8px; }

    /* ── AI tip / suggestion boxes ── */
    .pe-aitip { display:flex; gap:10px; background:var(--pe-purple-l);
        border:1px solid color-mix(in srgb, var(--pe-purple) 32%, var(--pe-card));
        border-radius:12px; padding:12px 14px; }
    .pe-aitip .ic { color:var(--pe-purple); font-size:16px; }
    .pe-aitip h4 { margin:0 0 2px; font-size:13px; font-weight:800; color:var(--pe-purple); }
    .pe-aitip p { margin:0; font-size:12px; color:var(--pe-purple-t); line-height:1.5; }

    /* ── badges & match ── */
    .pe-badge { display:inline-flex; align-items:center; gap:5px; font-size:11px; font-weight:800; padding:3px 10px; border-radius:999px; }
    .pe-badge.req { background:#fee2e2; color:#b91c1c; }
    .pe-badge.pro { background:var(--pe-purple-l); color:var(--pe-purple); }
    .pe-badge.both { background:#e0e7ff; color:#4338ca; }
    .pe-badge.orange { background:color-mix(in srgb, var(--pe-orange) 16%, var(--pe-card)); color:var(--pe-orange-t); }
    .pe-badge.green { background:var(--pe-green-l); color:var(--ok-text); }
    .pe-ring { --v:92; width:60px; height:60px; border-radius:50%; display:grid; place-items:center; flex-shrink:0;
        background:conic-gradient(var(--pe-green) calc(var(--v)*1%), var(--pe-line) 0); position:relative; }
    .pe-ring::before { content:''; position:absolute; inset:5px; border-radius:50%; background:var(--pe-card); }
    .pe-ring b { position:relative; font-size:14px; font-weight:800; color:var(--pe-ink); }
    .pe-ring.sm { width:48px; height:48px; } .pe-ring.sm b { font-size:12px; }

    /* ── right rail summary ── */
    .pe-rail { position:sticky; top:16px; display:flex; flex-direction:column; gap:16px; }
    .pe-rail-card { background:var(--pe-card); border:1px solid var(--pe-line); border-radius:16px; padding:16px; }
    .pe-rail-card h4 { margin:0 0 12px; font-size:14px; font-weight:800; }
    .pe-rail-row { display:flex; align-items:center; gap:10px; padding:7px 0; font-size:13px; color:var(--pe-ink-2); border-bottom:1px dashed var(--pe-line-2); }
    .pe-rail-row:last-child { border-bottom:none; }
    .pe-rail-row .k { color:var(--pe-muted); } .pe-rail-row .v { margin-left:auto; font-weight:700; color:var(--pe-ink); }
    .pe-rail-why { background:linear-gradient(160deg,var(--pe-purple-l),var(--pe-card)); border-color:var(--pe-purple-l); }
    .pe-check { display:flex; align-items:flex-start; gap:8px; font-size:12.5px; color:var(--pe-ink-2); padding:5px 0; }
    .pe-check svg { width:15px; height:15px; color:var(--pe-green); flex-shrink:0; margin-top:1px; }

    /* ── service selection grid (step 2) ── */
    .pe-svc-grid { display:grid; grid-template-columns:repeat(4,1fr); gap:12px; }
    @media (max-width:820px){ .pe-svc-grid { grid-template-columns:repeat(3,1fr); } }
    @media (max-width:520px){ .pe-svc-grid { grid-template-columns:repeat(2,1fr); } }
    .pe-svc { position:relative; border:1.5px solid var(--pe-line); border-radius:14px; padding:16px 10px; text-align:center;
        cursor:pointer; background:var(--pe-card); transition:.12s; }
    .pe-svc:hover { border-color:color-mix(in srgb, var(--pe-purple) 48%, var(--pe-card)); }
    .pe-svc.on { border-color:var(--pe-purple); background:var(--pe-purple-l); }
    .pe-svc .ic { width:34px; height:34px; margin:0 auto 8px; color:var(--pe-purple); }
    .pe-svc .nm { font-size:12.5px; font-weight:700; color:var(--pe-ink); }
    .pe-svc .chk { position:absolute; top:8px; right:8px; width:18px; height:18px; border-radius:50%; background:var(--pe-purple);
        color:#fff; display:none; align-items:center; justify-content:center; font-size:11px; }
    .pe-svc.on .chk { display:flex; }

    /* ── generic list rows / package tables ── */
    .pe-list-row { display:flex; align-items:center; gap:14px; padding:14px 0; border-bottom:1px solid var(--pe-line-2); }
    .pe-list-row:last-child { border-bottom:none; }
    .pe-thumb { width:90px; height:64px; border-radius:10px; object-fit:cover; flex-shrink:0; background:var(--pe-line-2); }
    .pe-muted { color:var(--pe-muted); font-size:12.5px; }
    .pe-price { font-size:18px; font-weight:800; color:var(--pe-ink); }
    .pe-price small { font-size:11px; font-weight:600; color:var(--pe-muted); display:block; }

    /* pkg / combo cards */
    .pe-pkg { border:1px solid var(--pe-line); border-radius:14px; overflow:hidden; background:var(--pe-card); margin-bottom:14px; }
    .pe-pkg-cover { height:120px; background-size:cover; background-position:center; position:relative; }
    .pe-pkg-badge { position:absolute; top:10px; left:10px; background:var(--pe-orange); color:#fff; font-size:10.5px; font-weight:800; padding:3px 10px; border-radius:6px; }
    .pe-pkg-body { padding:14px 16px; }
    .pe-tag { display:inline-block; font-size:10.5px; font-weight:600; color:var(--pe-ink-2); background:var(--pe-line-2); border-radius:6px; padding:2px 8px; margin:2px 3px 0 0; }
</style>
@endpush
