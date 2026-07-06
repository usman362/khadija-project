<style>
    .bt-head h1 { font-family: var(--ff); font-size: 26px; font-weight: 800; color: var(--ink); }
    .bt-head p { font-size: 14px; color: var(--muted); margin-top: 4px; }
    .bt-info { display: flex; align-items: center; gap: 11px; background: var(--blue-soft); border: 1px solid #d8e4ff; border-radius: 12px; padding: 13px 16px; font-size: 13.5px; color: #1e40af; margin: 18px 0 22px; }
    .bt-info svg { width: 19px; height: 19px; flex-shrink: 0; }

    .bt-layout { display: grid; grid-template-columns: minmax(0,1fr) 300px; gap: 22px; align-items: start; }
    @media (max-width: 1180px) { .bt-layout { grid-template-columns: 1fr; } }

    .bt-tiers { display: grid; grid-template-columns: repeat(4, 1fr); gap: 14px; }
    @media (max-width: 1080px) { .bt-tiers { grid-template-columns: repeat(2,1fr); } }
    @media (max-width: 560px) { .bt-tiers { grid-template-columns: 1fr; } }
    .bt-tier { background: var(--card); border: 1px solid var(--line); border-radius: 16px; padding: 20px 18px; box-shadow: var(--shadow); position: relative; display: flex; flex-direction: column; }
    .bt-tier.current { border-color: var(--orange); box-shadow: 0 0 0 2px var(--orange-soft); }
    .bt-tier-flag { position: absolute; top: -10px; left: 50%; transform: translateX(-50%); background: var(--orange); color: #fff; font-size: 10.5px; font-weight: 800; padding: 4px 12px; border-radius: 20px; letter-spacing: .03em; text-transform: uppercase; white-space: nowrap; }
    .bt-tier-badge { display: flex; justify-content: center; margin: 6px 0 12px; }
    .bt-tier h3 { font-family: var(--ff); font-size: 17px; font-weight: 800; color: var(--ink); text-align: center; }
    .bt-tier-pill { display: block; width: fit-content; margin: 7px auto 0; background: #eef1f6; color: var(--muted); font-size: 10.5px; font-weight: 700; padding: 3px 11px; border-radius: 20px; letter-spacing: .04em; text-transform: uppercase; }
    .bt-tier-tag { font-size: 12.5px; color: var(--muted); text-align: center; margin: 12px 0; line-height: 1.5; min-height: 38px; }
    .bt-req-lbl { font-family: var(--ff); font-size: 11px; font-weight: 700; color: var(--muted); text-transform: uppercase; letter-spacing: .05em; margin-bottom: 4px; }
    .bt-req { font-family: var(--ff); font-size: 14px; font-weight: 700; color: var(--ink); margin-bottom: 14px; }
    .bt-ben-lbl { font-family: var(--ff); font-size: 11px; font-weight: 700; color: var(--muted); text-transform: uppercase; letter-spacing: .05em; margin-bottom: 8px; }
    .bt-ben { display: flex; align-items: flex-start; gap: 8px; font-size: 12.5px; color: var(--text); margin-bottom: 8px; }
    .bt-ben svg { width: 15px; height: 15px; color: #16a34a; flex-shrink: 0; margin-top: 1px; }

    .bt-panel { background: var(--card); border: 1px solid var(--line); border-radius: var(--radius); box-shadow: var(--shadow); padding: 22px; margin-top: 22px; }
    .bt-panel h3 { font-family: var(--ff); font-size: 17px; font-weight: 700; color: var(--ink); }
    .bt-panel .sub { font-size: 13px; color: var(--muted); margin-top: 3px; }

    .bt-levelup { display: grid; grid-template-columns: repeat(5,1fr); gap: 14px; margin-top: 18px; text-align: center; }
    @media (max-width: 900px) { .bt-levelup { grid-template-columns: repeat(2,1fr); } }
    .bt-lu-ic { width: 50px; height: 50px; border-radius: 14px; margin: 0 auto 10px; display: flex; align-items: center; justify-content: center; }
    .bt-lu-ic svg { width: 22px; height: 22px; }
    .bt-levelup b { display: block; font-family: var(--ff); font-size: 13px; font-weight: 700; color: var(--ink); }
    .bt-levelup span { display: block; font-size: 11.5px; color: var(--muted); margin-top: 4px; line-height: 1.4; }

    .bt-rail-card { background: var(--card); border: 1px solid var(--line); border-radius: var(--radius); box-shadow: var(--shadow); padding: 18px; margin-bottom: 18px; }
    .bt-rail-card h4 { font-family: var(--ff); font-size: 15px; font-weight: 700; color: var(--ink); margin-bottom: 6px; }
    .bt-rail-card p { font-size: 12.5px; color: var(--muted); line-height: 1.55; }
    .bt-rail-list { margin-top: 6px; }
    .bt-rail-list .it { display: flex; align-items: flex-start; gap: 9px; font-size: 12.5px; color: var(--text); padding: 6px 0; }
    .bt-rail-list .it svg { width: 15px; height: 15px; color: var(--orange); flex-shrink: 0; margin-top: 1px; }
    .bt-rail-cta { display: inline-flex; align-items: center; gap: 7px; margin-top: 12px; width: 100%; justify-content: center; padding: 10px; border: 1.5px solid var(--orange); border-radius: 10px; color: var(--orange-dark); font-family: var(--ff); font-weight: 700; font-size: 13px; }
    .bt-rail-cta:hover { background: var(--orange); color: #fff; }
    .bt-rail-cta svg { width: 15px; height: 15px; flex-shrink: 0; }
    .bt-rail-soft { background: var(--orange-soft); border-color: #c9ecd4; }

    /* progress + current-tier hero */
    .bt-hero { background: linear-gradient(135deg, var(--orange-soft), #fff); border: 1px solid #c9ecd4; border-radius: var(--radius); padding: 24px; display: flex; gap: 22px; align-items: center; flex-wrap: wrap; }
    .bt-hero-main { flex: 1; min-width: 240px; }
    .bt-hero-flag { display: inline-block; background: var(--orange); color: #fff; font-size: 10px; font-weight: 800; padding: 3px 10px; border-radius: 20px; text-transform: uppercase; letter-spacing: .05em; margin-bottom: 8px; }
    .bt-hero-main h2 { font-family: var(--ff); font-size: 26px; font-weight: 800; color: var(--ink); }
    .bt-hero-main p { font-size: 13.5px; color: var(--text); margin-top: 6px; max-width: 46ch; line-height: 1.55; }

    .bt-stats { display: grid; grid-template-columns: repeat(4,1fr); gap: 14px; margin-top: 18px; }
    @media (max-width: 760px) { .bt-stats { grid-template-columns: repeat(2,1fr); } }
    .bt-stat { background: var(--card); border: 1px solid var(--line); border-radius: 14px; padding: 16px; box-shadow: var(--shadow); }
    .bt-stat .l { font-size: 12px; color: var(--muted); display: flex; align-items: center; gap: 6px; }
    .bt-stat .l svg { width: 14px; height: 14px; }
    .bt-stat .v { font-family: var(--ff); font-size: 20px; font-weight: 800; color: var(--ink); margin-top: 7px; }
    .bt-stat .v.orange { color: var(--orange-dark); }

    .bt-progress { height: 12px; background: var(--bg); border-radius: 8px; overflow: hidden; margin-top: 10px; }
    .bt-progress span { display: block; height: 100%; background: linear-gradient(90deg, var(--orange), var(--orange-dark)); border-radius: 8px; }

    /* badges grid */
    .bt-badges { display: grid; grid-template-columns: repeat(4,1fr); gap: 14px; }
    @media (max-width: 900px) { .bt-badges { grid-template-columns: repeat(2,1fr); } }
    @media (max-width: 520px) { .bt-badges { grid-template-columns: 1fr; } }
    .bt-badge { background: var(--card); border: 1px solid var(--line); border-radius: 16px; padding: 20px; text-align: center; box-shadow: var(--shadow); }
    .bt-badge.locked { opacity: .6; filter: grayscale(.5); }
    .bt-badge-ic { width: 60px; height: 60px; border-radius: 16px; margin: 0 auto 12px; display: flex; align-items: center; justify-content: center; }
    .bt-badge-ic svg { width: 28px; height: 28px; color: #fff; }
    .bt-badge b { display: block; font-family: var(--ff); font-size: 14px; font-weight: 700; color: var(--ink); }
    .bt-badge span { display: block; font-size: 12px; color: var(--muted); margin-top: 5px; line-height: 1.45; }
    .bt-badge-state { display: inline-block; margin-top: 10px; font-size: 11px; font-weight: 700; padding: 3px 10px; border-radius: 20px; }
    .bt-state-earned { background: #dcfce7; color: #16a34a; } .bt-state-locked { background: #eef1f6; color: #7a879c; }
</style>
