<style>
    .iv-head h1 { font-family: var(--ff); font-size: 26px; font-weight: 800; color: var(--ink); }
    .iv-head p { font-size: 14px; color: var(--muted); margin-top: 4px; }
    .iv-layout { display: grid; grid-template-columns: minmax(0,1fr) 300px; gap: 22px; align-items: start; margin-top: 20px; }
    @media (max-width: 1180px) { .iv-layout { grid-template-columns: 1fr; } }

    .iv-panel { background: var(--card); border: 1px solid var(--line); border-radius: var(--radius); box-shadow: var(--shadow); padding: 22px; margin-bottom: 20px; }
    .iv-panel h3 { font-family: var(--ff); font-size: 16px; font-weight: 700; color: var(--ink); }
    .iv-panel .sub { font-size: 13px; color: var(--muted); margin: 3px 0 16px; }

    /* link generator */
    .iv-linkbox { display: flex; gap: 10px; flex-wrap: wrap; }
    .iv-linkbox .url { flex: 1; min-width: 240px; display: flex; align-items: center; gap: 9px; padding: 12px 14px; border: 1.5px solid var(--line); border-radius: 11px; background: var(--bg); font-size: 13.5px; color: var(--ink); overflow: hidden; }
    .iv-linkbox .url svg { width: 16px; height: 16px; color: var(--muted); flex-shrink: 0; }
    .iv-linkbox .url span { white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .iv-copy { display: inline-flex; align-items: center; gap: 7px; padding: 12px 18px; background: var(--orange); color: #fff; border: none; border-radius: 11px; font-family: var(--ff); font-weight: 700; font-size: 13.5px; cursor: pointer; }
    .iv-copy:hover { background: var(--orange-dark); }
    .iv-copy.copied { background: #16a34a; }

    /* share buttons */
    .iv-share { display: grid; grid-template-columns: repeat(7, 1fr); gap: 12px; }
    @media (max-width: 760px) { .iv-share { grid-template-columns: repeat(4,1fr); } }
    @media (max-width: 460px) { .iv-share { grid-template-columns: repeat(3,1fr); } }
    .iv-share a { display: flex; flex-direction: column; align-items: center; gap: 8px; text-align: center; }
    .iv-share .ic { width: 52px; height: 52px; border-radius: 15px; display: flex; align-items: center; justify-content: center; color: #fff; transition: transform .12s; }
    .iv-share a:hover .ic { transform: translateY(-3px); }
    .iv-share .ic svg { width: 22px; height: 22px; }
    .iv-share .nm { font-size: 11.5px; color: var(--muted); font-weight: 600; }

    /* material cards */
    .iv-mats { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }
    @media (max-width: 640px) { .iv-mats { grid-template-columns: 1fr; } }
    .iv-mat { display: flex; align-items: center; gap: 12px; padding: 14px; border: 1px solid var(--line); border-radius: 12px; cursor: pointer; transition: border-color .12s, background .12s; }
    .iv-mat:hover { border-color: var(--orange); background: var(--orange-soft); }
    .iv-mat .ic { width: 40px; height: 40px; border-radius: 11px; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
    .iv-mat .ic svg { width: 19px; height: 19px; }
    .iv-mat b { display: block; font-family: var(--ff); font-size: 13.5px; font-weight: 700; color: var(--ink); }
    .iv-mat span { font-size: 12px; color: var(--muted); }
    .iv-mat .arrow { margin-left: auto; color: var(--muted); }

    /* metric tiles */
    .iv-tiles { display: grid; grid-template-columns: repeat(4,1fr); gap: 14px; margin: 20px 0; }
    @media (max-width: 900px) { .iv-tiles { grid-template-columns: repeat(2,1fr); } }
    .iv-tile { background: var(--card); border: 1px solid var(--line); border-radius: 14px; padding: 16px; box-shadow: var(--shadow); display: flex; align-items: center; gap: 12px; }
    .iv-tile .ic { width: 42px; height: 42px; border-radius: 12px; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
    .iv-tile .ic svg { width: 20px; height: 20px; }
    .iv-tile .v { font-family: var(--ff); font-size: 20px; font-weight: 800; color: var(--ink); line-height: 1; }
    .iv-tile .l { font-size: 12px; color: var(--muted); margin-top: 4px; }

    /* rail */
    .iv-rail-card { background: var(--card); border: 1px solid var(--line); border-radius: var(--radius); box-shadow: var(--shadow); padding: 18px; margin-bottom: 18px; }
    .iv-rail-card h4 { font-family: var(--ff); font-size: 15px; font-weight: 700; color: var(--ink); margin-bottom: 10px; }
    .iv-rail-stat { display: flex; align-items: center; gap: 11px; padding: 9px 0; border-bottom: 1px solid var(--line); }
    .iv-rail-stat:last-child { border-bottom: none; }
    .iv-rail-stat .ic { width: 34px; height: 34px; border-radius: 10px; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
    .iv-rail-stat .ic svg { width: 16px; height: 16px; }
    .iv-rail-stat .m { flex: 1; }
    .iv-rail-stat .m .l { font-size: 12px; color: var(--muted); }
    .iv-rail-stat .m .v { font-family: var(--ff); font-size: 16px; font-weight: 800; color: var(--ink); }
    .iv-rail-list .it { display: flex; align-items: flex-start; gap: 9px; font-size: 12.5px; color: var(--text); padding: 6px 0; }
    .iv-rail-list .it svg { width: 15px; height: 15px; flex-shrink: 0; margin-top: 1px; }
    .iv-rail-cta { display: inline-flex; align-items: center; justify-content: center; gap: 7px; width: 100%; margin-top: 12px; padding: 10px; border: 1.5px solid var(--orange); border-radius: 10px; color: var(--orange-dark); font-family: var(--ff); font-weight: 700; font-size: 13px; }
    .iv-rail-cta:hover { background: var(--orange); color: #fff; }
    .iv-rail-soft { background: var(--orange-soft); border-color: #ffe2cd; }

    /* how-you-earn list */
    .iv-earn-row { display: flex; align-items: center; gap: 12px; padding: 12px 0; border-bottom: 1px solid var(--line); }
    .iv-earn-row:last-child { border-bottom: none; }
    .iv-earn-row .ic { width: 36px; height: 36px; border-radius: 10px; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
    .iv-earn-row .ic svg { width: 17px; height: 17px; }
    .iv-earn-row .m { flex: 1; }
    .iv-earn-row .m b { display: block; font-family: var(--ff); font-size: 13.5px; font-weight: 600; color: var(--ink); }
    .iv-earn-row .m span { font-size: 12px; color: var(--muted); }
    .iv-earn-row .amt { font-family: var(--ff); font-weight: 700; color: #16a34a; font-size: 13.5px; }

    /* steps (onboarding) */
    .iv-steps { counter-reset: s; }
    .iv-step { display: flex; gap: 16px; padding: 16px 0; border-bottom: 1px solid var(--line); }
    .iv-step:last-child { border-bottom: none; }
    .iv-step .num { counter-increment: s; width: 36px; height: 36px; border-radius: 50%; background: var(--orange-soft); color: var(--orange-dark); font-family: var(--ff); font-weight: 800; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
    .iv-step .num::before { content: counter(s); }
    .iv-step b { font-family: var(--ff); font-size: 15px; font-weight: 700; color: var(--ink); }
    .iv-step p { font-size: 13px; color: var(--muted); margin-top: 3px; line-height: 1.55; }

    /* faqs */
    .iv-faq { border: 1px solid var(--line); border-radius: 12px; margin-bottom: 10px; overflow: hidden; }
    .iv-faq summary { list-style: none; cursor: pointer; padding: 15px 18px; font-family: var(--ff); font-weight: 600; font-size: 14px; color: var(--ink); display: flex; align-items: center; justify-content: space-between; }
    .iv-faq summary::-webkit-details-marker { display: none; }
    .iv-faq summary .chev { transition: transform .2s; color: var(--muted); }
    .iv-faq[open] summary .chev { transform: rotate(180deg); }
    .iv-faq .ans { padding: 0 18px 15px; font-size: 13.5px; color: var(--text); line-height: 1.6; }

    /* stories */
    .iv-stories { display: grid; grid-template-columns: repeat(3,1fr); gap: 16px; }
    @media (max-width: 900px) { .iv-stories { grid-template-columns: 1fr; } }
    .iv-story { background: var(--card); border: 1px solid var(--line); border-radius: 16px; padding: 20px; box-shadow: var(--shadow); }
    .iv-story .stars { color: #fbbf24; font-size: 14px; letter-spacing: 1px; }
    .iv-story p { font-size: 13.5px; color: var(--text); line-height: 1.6; margin: 10px 0 16px; }
    .iv-story .who { display: flex; align-items: center; gap: 11px; }
    .iv-story .av { width: 40px; height: 40px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-family: var(--ff); font-weight: 700; color: #fff; font-size: 14px; }
    .iv-story .who b { display: block; font-family: var(--ff); font-size: 13.5px; color: var(--ink); }
    .iv-story .who span { font-size: 12px; color: var(--muted); }

    .iv-hero { background: linear-gradient(135deg, var(--orange), var(--orange-dark)); color: #fff; border-radius: var(--radius); padding: 30px; text-align: center; margin-bottom: 22px; }
    .iv-hero h2 { font-family: var(--ff); font-size: 26px; font-weight: 800; }
    .iv-hero p { font-size: 14px; opacity: .92; margin: 8px auto 18px; max-width: 52ch; line-height: 1.6; }
    .iv-hero a { display: inline-block; background: #fff; color: var(--orange-dark); padding: 11px 24px; border-radius: 11px; font-family: var(--ff); font-weight: 700; font-size: 14px; }
</style>
