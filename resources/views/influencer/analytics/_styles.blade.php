<style>
    .an-head { display: flex; align-items: flex-end; justify-content: space-between; flex-wrap: wrap; gap: 12px; margin-bottom: 4px; }
    .an-head h1 { font-family: var(--ff); font-size: 26px; font-weight: 800; color: var(--ink); }
    .an-head p { font-size: 14px; color: var(--muted); margin-top: 4px; }
    .an-actions { display: flex; gap: 10px; }
    .an-btn { display: inline-flex; align-items: center; gap: 7px; padding: 9px 15px; border: 1px solid var(--line); border-radius: 10px; background: var(--card); font-family: var(--ff); font-weight: 600; font-size: 13px; color: var(--ink); }
    .an-btn:hover { border-color: #cbd5e1; }
    .an-btn svg { width: 15px; height: 15px; }

    .an-tiles { display: grid; grid-template-columns: repeat(4,1fr); gap: 14px; margin: 20px 0; }
    @media (max-width: 1080px) { .an-tiles { grid-template-columns: repeat(2,1fr); } }
    .an-tiles.five { grid-template-columns: repeat(5,1fr); }
    @media (max-width: 1180px) { .an-tiles.five { grid-template-columns: repeat(3,1fr); } }
    .an-tile { background: var(--card); border: 1px solid var(--line); border-radius: 14px; padding: 16px; box-shadow: var(--shadow); }
    .an-tile .top { display: flex; align-items: center; gap: 10px; }
    .an-tile .ic { width: 40px; height: 40px; border-radius: 11px; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
    .an-tile .ic svg { width: 19px; height: 19px; }
    .an-tile .lbl { font-size: 12px; color: var(--muted); }
    .an-tile .v { font-family: var(--ff); font-size: 22px; font-weight: 800; color: var(--ink); margin-top: 12px; line-height: 1; }
    .an-tile .tr { font-size: 11.5px; font-weight: 700; color: #16a34a; margin-top: 7px; }

    .an-panel { background: var(--card); border: 1px solid var(--line); border-radius: var(--radius); box-shadow: var(--shadow); padding: 20px; }
    .an-panel-head { display: flex; align-items: center; justify-content: space-between; margin-bottom: 14px; }
    .an-panel-head h3 { font-family: var(--ff); font-size: 16px; font-weight: 700; color: var(--ink); }
    .an-panel-head a, .an-panel-head .tag { font-size: 12px; color: var(--muted); font-weight: 600; }

    .an-grid-2 { display: grid; grid-template-columns: 1.4fr 1fr; gap: 18px; }
    @media (max-width: 1080px) { .an-grid-2 { grid-template-columns: 1fr; } }
    .an-grid-3 { display: grid; grid-template-columns: repeat(3,1fr); gap: 18px; margin-top: 18px; }
    @media (max-width: 1080px) { .an-grid-3 { grid-template-columns: 1fr; } }

    .an-chart { width: 100%; height: 220px; }
    .an-axis { font-size: 10.5px; fill: var(--muted); }

    .an-donut-wrap { display: flex; align-items: center; gap: 22px; flex-wrap: wrap; }
    .an-donut { width: 150px; height: 150px; border-radius: 50%; flex-shrink: 0; display: flex; align-items: center; justify-content: center; position: relative; }
    .an-donut::after { content: ''; position: absolute; width: 96px; height: 96px; background: var(--card); border-radius: 50%; }
    .an-donut-c { position: relative; z-index: 1; text-align: center; }
    .an-donut-c b { font-family: var(--ff); font-size: 20px; font-weight: 800; color: var(--ink); display: block; }
    .an-donut-c span { font-size: 11px; color: var(--muted); }
    .an-legend { flex: 1; min-width: 160px; }
    .an-legend .row { display: flex; align-items: center; gap: 9px; padding: 6px 0; font-size: 13px; }
    .an-legend .dot { width: 9px; height: 9px; border-radius: 50%; flex-shrink: 0; }
    .an-legend .nm { flex: 1; color: var(--text); }
    .an-legend .pc { font-family: var(--ff); font-weight: 700; color: var(--ink); }

    .an-bar-row { display: flex; align-items: center; gap: 12px; margin-bottom: 13px; font-size: 13px; }
    .an-bar-row .nm { width: 110px; color: var(--text); display: flex; align-items: center; gap: 7px; }
    .an-bar { flex: 1; height: 8px; background: var(--bg); border-radius: 6px; overflow: hidden; }
    .an-bar span { display: block; height: 100%; background: var(--orange); border-radius: 6px; }
    .an-bar-row .pc { width: 40px; text-align: right; color: var(--muted); font-weight: 600; }

    .an-table { width: 100%; border-collapse: collapse; }
    .an-table th { text-align: left; font-size: 11px; font-weight: 700; color: var(--muted); text-transform: uppercase; letter-spacing: .04em; padding: 0 0 10px; }
    .an-table td { padding: 11px 0; border-top: 1px solid var(--line); font-size: 13.5px; color: var(--text); }
    .an-table td.name { font-family: var(--ff); font-weight: 600; color: var(--ink); }
    .an-table td.amt { font-family: var(--ff); font-weight: 700; color: #16a34a; text-align: right; }
    .an-table td.num { text-align: right; }
    .an-pill { font-size: 10.5px; font-weight: 700; padding: 2px 9px; border-radius: 20px; text-transform: capitalize; }
    .an-pill-active { background: #dcfce7; color: #16a34a; } .an-pill-paused { background: #fef3c7; color: #d97706; } .an-pill-ended { background: #eef1f6; color: #7a879c; }

    .an-summary { display: flex; align-items: center; gap: 16px; background: var(--orange-soft); border: 1px solid #ffe2cd; border-radius: var(--radius); padding: 18px 22px; margin-top: 20px; flex-wrap: wrap; }
    .an-summary .ic { width: 48px; height: 48px; border-radius: 14px; background: var(--orange); color: #fff; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
    .an-summary .m { flex: 1; min-width: 220px; }
    .an-summary .m b { font-family: var(--ff); font-size: 15px; color: var(--ink); }
    .an-summary .m p { font-size: 13px; color: var(--text); margin-top: 3px; }
    .an-summary a { background: var(--orange); color: #fff; padding: 10px 18px; border-radius: 10px; font-family: var(--ff); font-weight: 700; font-size: 13px; white-space: nowrap; }

    .an-bars { display: flex; align-items: flex-end; gap: 8px; height: 150px; padding-top: 10px; }
    .an-bars .col { flex: 1; display: flex; flex-direction: column; align-items: center; gap: 6px; }
    .an-bars .bar { width: 100%; max-width: 26px; display: flex; flex-direction: column; justify-content: flex-end; gap: 2px; height: 120px; }
    .an-bars .bar i { display: block; border-radius: 3px 3px 0 0; }
    .an-bars .x { font-size: 10px; color: var(--muted); }
</style>
