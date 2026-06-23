<style>
    .rs-head h1 { font-family: var(--ff); font-size: 26px; font-weight: 800; color: var(--ink); }
    .rs-head p { font-size: 14px; color: var(--muted); margin-top: 4px; }

    .rs-hero { display: flex; align-items: center; justify-content: space-between; gap: 20px; background: linear-gradient(135deg, var(--orange-soft), #fff); border: 1px solid #ffe2cd; border-radius: var(--radius); padding: 26px; margin-bottom: 20px; flex-wrap: wrap; }
    .rs-hero h2 { font-family: var(--ff); font-size: 24px; font-weight: 800; color: var(--ink); }
    .rs-hero p { font-size: 13.5px; color: var(--text); margin: 7px 0 14px; max-width: 48ch; line-height: 1.55; }
    .rs-hero a.cta { display: inline-flex; align-items: center; gap: 7px; background: var(--orange); color: #fff; padding: 11px 20px; border-radius: 11px; font-family: var(--ff); font-weight: 700; font-size: 13.5px; }
    .rs-search { display: flex; align-items: center; gap: 9px; background: #fff; border: 1px solid var(--line); border-radius: 11px; padding: 11px 14px; min-width: 240px; }
    .rs-search svg { width: 17px; height: 17px; color: var(--muted); }
    .rs-search input { border: none; outline: none; flex: 1; font-size: 13.5px; font-family: var(--ff-body); color: var(--ink); background: none; }

    .rs-tabs { display: flex; gap: 8px; flex-wrap: wrap; margin-bottom: 20px; }
    .rs-tab { display: inline-flex; align-items: center; gap: 7px; padding: 8px 14px; border: 1px solid var(--line); border-radius: 10px; background: var(--card); font-family: var(--ff); font-weight: 600; font-size: 13px; color: var(--text); cursor: pointer; }
    .rs-tab.active { background: var(--orange); border-color: var(--orange); color: #fff; }
    .rs-tab:hover:not(.active) { border-color: #cbd5e1; }

    .rs-section-head { display: flex; align-items: center; justify-content: space-between; margin: 6px 0 14px; }
    .rs-section-head h3 { font-family: var(--ff); font-size: 17px; font-weight: 700; color: var(--ink); }
    .rs-section-head a { font-size: 12.5px; color: var(--orange-dark); font-weight: 600; }

    .rs-featured { display: grid; grid-template-columns: repeat(5,1fr); gap: 14px; }
    @media (max-width: 1200px) { .rs-featured { grid-template-columns: repeat(3,1fr); } }
    @media (max-width: 760px) { .rs-featured { grid-template-columns: repeat(2,1fr); } }
    @media (max-width: 480px) { .rs-featured { grid-template-columns: 1fr; } }
    .rs-card { background: var(--card); border: 1px solid var(--line); border-radius: 14px; overflow: hidden; box-shadow: var(--shadow); display: flex; flex-direction: column; }
    .rs-card-top { height: 88px; display: flex; align-items: center; justify-content: center; position: relative; }
    .rs-card-top svg { width: 40px; height: 40px; color: #fff; }
    .rs-card-badge { position: absolute; top: 10px; left: 10px; font-size: 9.5px; font-weight: 800; padding: 3px 9px; border-radius: 20px; text-transform: uppercase; letter-spacing: .03em; color: #fff; background: rgba(0,0,0,.25); }
    .rs-card-body { padding: 14px; flex: 1; display: flex; flex-direction: column; }
    .rs-card-body b { font-family: var(--ff); font-size: 13.5px; font-weight: 700; color: var(--ink); line-height: 1.35; }
    .rs-card-body p { font-size: 12px; color: var(--muted); margin: 6px 0 12px; line-height: 1.45; flex: 1; }
    .rs-card-cta { display: inline-block; text-align: center; padding: 8px; border: 1.5px solid var(--orange); border-radius: 9px; color: var(--orange-dark); font-family: var(--ff); font-weight: 700; font-size: 12px; }
    .rs-card-cta:hover { background: var(--orange); color: #fff; }

    .rs-grid { display: grid; grid-template-columns: 1.3fr 1fr 1fr; gap: 18px; margin-top: 22px; }
    @media (max-width: 1080px) { .rs-grid { grid-template-columns: 1fr; } }
    .rs-panel { background: var(--card); border: 1px solid var(--line); border-radius: var(--radius); box-shadow: var(--shadow); padding: 20px; }
    .rs-panel h3 { font-family: var(--ff); font-size: 16px; font-weight: 700; color: var(--ink); margin-bottom: 14px; }

    .rs-list-row { display: flex; align-items: center; gap: 12px; padding: 11px 0; border-bottom: 1px solid var(--line); }
    .rs-list-row:last-child { border-bottom: none; }
    .rs-list-ic { width: 34px; height: 34px; border-radius: 9px; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
    .rs-list-ic svg { width: 16px; height: 16px; }
    .rs-list-row .m { flex: 1; min-width: 0; }
    .rs-list-row .m b { display: block; font-family: var(--ff); font-size: 13px; font-weight: 600; color: var(--ink); }
    .rs-list-row .m span { font-size: 11.5px; color: var(--muted); text-transform: capitalize; }
    .rs-list-row .meta { font-size: 11.5px; color: var(--muted); display: flex; align-items: center; gap: 4px; }

    .rs-donut { width: 130px; height: 130px; border-radius: 50%; margin: 0 auto; display: flex; align-items: center; justify-content: center; position: relative; }
    .rs-donut::after { content: ''; position: absolute; width: 84px; height: 84px; background: var(--card); border-radius: 50%; }
    .rs-donut-c { position: relative; z-index: 1; text-align: center; }
    .rs-donut-c b { font-family: var(--ff); font-size: 19px; font-weight: 800; color: var(--ink); display: block; }
    .rs-donut-c span { font-size: 10.5px; color: var(--muted); }
    .rs-types-legend { margin-top: 14px; }
    .rs-types-legend .row { display: flex; align-items: center; gap: 8px; padding: 4px 0; font-size: 12.5px; }
    .rs-types-legend .dot { width: 8px; height: 8px; border-radius: 50%; }
    .rs-types-legend .nm { flex: 1; color: var(--text); text-transform: capitalize; }
    .rs-types-legend .pc { color: var(--muted); font-weight: 600; }

    /* academy */
    .rs-stats { display: grid; grid-template-columns: repeat(4,1fr); gap: 14px; margin-bottom: 22px; }
    @media (max-width: 760px) { .rs-stats { grid-template-columns: repeat(2,1fr); } }
    .rs-stat { background: var(--card); border: 1px solid var(--line); border-radius: 14px; padding: 16px; box-shadow: var(--shadow); display: flex; align-items: center; gap: 12px; }
    .rs-stat .ic { width: 42px; height: 42px; border-radius: 12px; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
    .rs-stat .ic svg { width: 20px; height: 20px; }
    .rs-stat .v { font-family: var(--ff); font-size: 20px; font-weight: 800; color: var(--ink); line-height: 1; }
    .rs-stat .l { font-size: 12px; color: var(--muted); margin-top: 3px; }

    .rs-cats { display: grid; grid-template-columns: repeat(6,1fr); gap: 12px; }
    @media (max-width: 1080px) { .rs-cats { grid-template-columns: repeat(3,1fr); } }
    @media (max-width: 560px) { .rs-cats { grid-template-columns: repeat(2,1fr); } }
    .rs-cat { background: var(--card); border: 1px solid var(--line); border-radius: 13px; padding: 16px; text-align: center; }
    .rs-cat .ic { width: 44px; height: 44px; border-radius: 12px; margin: 0 auto 10px; display: flex; align-items: center; justify-content: center; }
    .rs-cat .ic svg { width: 20px; height: 20px; }
    .rs-cat b { display: block; font-family: var(--ff); font-size: 13px; font-weight: 700; color: var(--ink); }
    .rs-cat span { font-size: 11.5px; color: var(--muted); }

    .rs-courses { display: grid; grid-template-columns: repeat(4,1fr); gap: 14px; }
    @media (max-width: 1080px) { .rs-courses { grid-template-columns: repeat(2,1fr); } }
    @media (max-width: 560px) { .rs-courses { grid-template-columns: 1fr; } }
    .rs-course { background: var(--card); border: 1px solid var(--line); border-radius: 14px; overflow: hidden; box-shadow: var(--shadow); }
    .rs-course-top { height: 90px; display: flex; align-items: center; justify-content: center; position: relative; }
    .rs-course-top svg { width: 38px; height: 38px; color: #fff; }
    .rs-course-lvl { position: absolute; bottom: 10px; left: 10px; font-size: 9.5px; font-weight: 800; padding: 3px 9px; border-radius: 20px; text-transform: uppercase; color: #fff; background: rgba(0,0,0,.3); }
    .rs-course-body { padding: 14px; }
    .rs-course-body b { font-family: var(--ff); font-size: 13.5px; font-weight: 700; color: var(--ink); display: block; line-height: 1.35; }
    .rs-course-meta { display: flex; gap: 14px; margin-top: 10px; font-size: 11.5px; color: var(--muted); }
    .rs-course-cta { display: block; text-align: center; margin-top: 12px; padding: 8px; background: var(--orange-soft); color: var(--orange-dark); border-radius: 9px; font-family: var(--ff); font-weight: 700; font-size: 12px; }
    .rs-course-cta:hover { background: var(--orange); color: #fff; }

    /* article cards */
    .rs-articles { display: grid; grid-template-columns: repeat(3,1fr); gap: 16px; }
    @media (max-width: 900px) { .rs-articles { grid-template-columns: 1fr; } }
    .rs-article { background: var(--card); border: 1px solid var(--line); border-radius: 14px; overflow: hidden; box-shadow: var(--shadow); }
    .rs-article-top { height: 100px; display: flex; align-items: center; justify-content: center; }
    .rs-article-top svg { width: 40px; height: 40px; color: #fff; }
    .rs-article-body { padding: 16px; }
    .rs-article-cat { font-size: 11px; font-weight: 700; color: var(--orange-dark); text-transform: uppercase; letter-spacing: .03em; }
    .rs-article-body b { display: block; font-family: var(--ff); font-size: 15px; font-weight: 700; color: var(--ink); margin: 6px 0; line-height: 1.35; }
    .rs-article-body p { font-size: 12.5px; color: var(--muted); line-height: 1.5; }
    .rs-article-meta { display: flex; align-items: center; gap: 6px; margin-top: 12px; font-size: 11.5px; color: var(--muted); }
</style>
