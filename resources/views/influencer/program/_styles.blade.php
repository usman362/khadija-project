<style>
    .pg-head h1 { font-family: var(--ff); font-size: 26px; font-weight: 800; color: var(--ink); }
    .pg-head p { font-size: 14px; color: var(--muted); margin-top: 4px; }

    .pg-tiles { display: grid; grid-template-columns: repeat(4, minmax(0,1fr)); gap: 14px; margin: 20px 0; }
    @media (max-width: 900px) { .pg-tiles { grid-template-columns: repeat(2, minmax(0,1fr)); } }
    .pg-tile { background: var(--card); border: 1px solid var(--line); border-radius: 14px; padding: 16px; box-shadow: var(--shadow); }
    .pg-tile .ic { width: 38px; height: 38px; border-radius: 11px; display: flex; align-items: center; justify-content: center; margin-bottom: 10px; }
    .pg-tile .ic svg { width: 18px; height: 18px; }
    .pg-tile .v { font-family: var(--ff); font-size: 22px; font-weight: 800; color: var(--ink); line-height: 1; }
    .pg-tile .l { font-size: 12px; color: var(--muted); margin-top: 4px; }

    .pg-grid { display: grid; gap: 18px; }
    .pg-grid.two { grid-template-columns: 1.5fr 1fr; }
    .pg-grid.three { grid-template-columns: repeat(3, minmax(0,1fr)); }
    @media (max-width: 1000px) { .pg-grid.two, .pg-grid.three { grid-template-columns: 1fr; } }

    .pg-panel { background: var(--card); border: 1px solid var(--line); border-radius: var(--radius); box-shadow: var(--shadow); padding: 20px; }
    .pg-panel h3 { font-family: var(--ff); font-size: 16px; font-weight: 700; color: var(--ink); margin-bottom: 4px; }
    .pg-panel .sub { font-size: 12.5px; color: var(--muted); margin-bottom: 14px; }

    /* referral link box */
    .pg-linkbox { display: flex; align-items: center; gap: 10px; background: var(--orange-soft); border: 1px dashed #f5b890; border-radius: 12px; padding: 12px 14px; }
    .pg-linkbox code { flex: 1; font-family: var(--ff-body); font-size: 13px; color: var(--orange-dark); overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    .pg-copy { background: var(--orange); color: #fff; border: none; padding: 9px 16px; border-radius: 9px; font-family: var(--ff); font-weight: 700; font-size: 12.5px; cursor: pointer; flex-shrink: 0; }
    .pg-copy:hover { background: var(--orange-dark); }

    /* table */
    .pg-table { width: 100%; border-collapse: collapse; }
    .pg-table th { text-align: left; font-size: 11px; font-weight: 700; color: var(--muted); text-transform: uppercase; letter-spacing: .04em; padding: 8px 10px; border-bottom: 1px solid var(--line); }
    .pg-table td { padding: 11px 10px; border-bottom: 1px solid var(--line); font-size: 13px; color: var(--text); }
    .pg-table tr:last-child td { border-bottom: none; }
    .pg-pill { display: inline-flex; align-items: center; gap: 5px; padding: 3px 10px; border-radius: 20px; font-size: 11px; font-weight: 700; }
    .pg-pill.paid { background: #dcfce7; color: #15803d; }
    .pg-pill.earned { background: var(--blue-soft); color: #1d4ed8; }
    .pg-pill.pending { background: #fef3c7; color: #b45309; }
    .pg-pill.cancelled { background: #f1f5f9; color: #64748b; }

    .pg-bar { height: 8px; border-radius: 6px; background: #eef2f7; overflow: hidden; }
    .pg-bar > span { display: block; height: 100%; border-radius: 6px; }

    /* leaderboard */
    .pg-rank-row { display: flex; align-items: center; gap: 14px; padding: 13px 14px; border-radius: 12px; border: 1px solid var(--line); margin-bottom: 10px; }
    .pg-rank-row.me { background: var(--orange-soft); border-color: #f5b890; }
    .pg-rank-no { width: 34px; height: 34px; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-family: var(--ff); font-weight: 800; font-size: 14px; flex-shrink: 0; background: #f1f5f9; color: var(--muted); }
    .pg-rank-no.gold { background: #fef3c7; color: #b45309; }
    .pg-rank-no.silver { background: #e2e8f0; color: #475569; }
    .pg-rank-no.bronze { background: #fde4cf; color: #c2410c; }
    .pg-rank-av { width: 38px; height: 38px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-family: var(--ff); font-weight: 700; color: #fff; font-size: 14px; flex-shrink: 0; }
    .pg-rank-row .nm { flex: 1; min-width: 0; }
    .pg-rank-row .nm b { display: block; font-family: var(--ff); font-size: 13.5px; color: var(--ink); }
    .pg-rank-row .nm span { font-size: 11.5px; color: var(--muted); }
    .pg-rank-row .val { text-align: right; }
    .pg-rank-row .val b { font-family: var(--ff); font-size: 15px; color: var(--ink); }
    .pg-rank-row .val span { display: block; font-size: 11px; color: var(--muted); }

    /* challenges */
    .pg-chal { border: 1px solid var(--line); border-radius: 14px; padding: 16px; }
    .pg-chal .top { display: flex; align-items: center; gap: 11px; margin-bottom: 12px; }
    .pg-chal .ic { width: 40px; height: 40px; border-radius: 12px; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
    .pg-chal .ic svg { width: 19px; height: 19px; }
    .pg-chal b { font-family: var(--ff); font-size: 14px; color: var(--ink); display: block; }
    .pg-chal small { font-size: 11.5px; color: var(--muted); }
    .pg-chal .prog-meta { display: flex; justify-content: space-between; font-size: 11.5px; color: var(--muted); margin: 12px 0 6px; }

    /* commission tiers */
    .pg-tier-row { display: flex; align-items: center; gap: 14px; padding: 14px; border: 1px solid var(--line); border-radius: 13px; margin-bottom: 10px; }
    .pg-tier-row.current { border-width: 2px; box-shadow: 0 4px 14px rgba(249,115,22,.12); }
    .pg-tier-dot { width: 14px; height: 14px; border-radius: 50%; flex-shrink: 0; }
    .pg-tier-row .ti { flex: 1; }
    .pg-tier-row .ti b { font-family: var(--ff); font-size: 14px; color: var(--ink); }
    .pg-tier-row .ti > span { font-size: 11.5px; color: var(--muted); display: block; }
    .pg-tier-rate { font-family: var(--ff); font-size: 20px; font-weight: 800; }
    .pg-badge-now { display: inline-block; font-size: 10px; font-weight: 800; padding: 3px 9px; border-radius: 20px; background: var(--orange); color: #fff !important; text-transform: uppercase; vertical-align: middle; }

    /* asset cards (marketing) */
    .pg-assets { display: grid; grid-template-columns: repeat(3, minmax(0,1fr)); gap: 16px; }
    @media (max-width: 900px) { .pg-assets { grid-template-columns: 1fr; } }
    .pg-asset { border: 1px solid var(--line); border-radius: 14px; overflow: hidden; background: var(--card); box-shadow: var(--shadow); }
    .pg-asset .prev { height: 110px; display: flex; align-items: center; justify-content: center; color: #fff; position: relative; }
    .pg-asset .prev svg { width: 34px; height: 34px; opacity: .9; }
    .pg-asset .prev .sz { position: absolute; bottom: 8px; right: 10px; font-size: 10.5px; background: rgba(0,0,0,.28); padding: 2px 8px; border-radius: 12px; }
    .pg-asset .meta { padding: 14px; }
    .pg-asset .meta b { font-family: var(--ff); font-size: 13.5px; color: var(--ink); display: block; }
    .pg-asset .meta p { font-size: 12px; color: var(--muted); margin: 5px 0 12px; line-height: 1.45; }
    .pg-asset .meta .act { display: flex; gap: 8px; }
    .pg-btn-sm { flex: 1; text-align: center; padding: 7px; border-radius: 8px; font-family: var(--ff); font-weight: 700; font-size: 12px; cursor: pointer; border: 1.5px solid var(--orange); color: var(--orange-dark); background: none; }
    .pg-btn-sm.solid { background: var(--orange); color: #fff; border-color: var(--orange); }
    .pg-btn-sm:hover { background: var(--orange); color: #fff; }

    .pg-swipe { border: 1px solid var(--line); border-radius: 12px; padding: 14px; margin-bottom: 12px; }
    .pg-swipe .cap { display: flex; align-items: center; justify-content: space-between; margin-bottom: 8px; }
    .pg-swipe .cap b { font-family: var(--ff); font-size: 13px; color: var(--ink); }
    .pg-swipe p { font-size: 12.5px; color: var(--text); line-height: 1.55; background: #f8fafc; border-radius: 9px; padding: 11px; }

    .pg-note { display: flex; gap: 10px; align-items: flex-start; background: var(--blue-soft); border: 1px solid #c7dbff; border-radius: 12px; padding: 13px 15px; font-size: 12.5px; color: #1e40af; line-height: 1.5; margin-top: 18px; }
    .pg-note svg { width: 17px; height: 17px; flex-shrink: 0; margin-top: 1px; }
</style>
