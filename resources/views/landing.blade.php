@extends('layouts.landing')

@section('content')

@push('styles')
<style>
    /* ════════ Landing page (light) — page-scoped ════════ */
    .lp-section { padding: 78px 0; }
    .lp-section-soft { background: var(--bg-soft); }
    .lp-head { text-align: center; max-width: 680px; margin: 0 auto 48px; }
    .lp-eyebrow { display: inline-flex; align-items: center; gap: 7px; font-size: 12.5px; font-weight: 800; letter-spacing: .4px; color: var(--blue); text-transform: uppercase; background: var(--bg-soft-2); padding: 6px 14px; border-radius: 999px; margin-bottom: 16px; }
    .lp-h2 { font-size: 36px; font-weight: 800; letter-spacing: -0.8px; }
    .lp-h2 .ic3d { display: inline-block; vertical-align: middle; margin-left: 8px; }
    .lp-lead { font-size: 16px; color: var(--muted); margin: 14px 0 0; line-height: 1.6; }

    /* ── HERO ───────────────────────────────────────── */
    .lp-hero { position: relative; padding: 56px 0 70px; overflow: hidden; }
    .lp-hero::before { content: ''; position: absolute; top: -160px; right: -120px; width: 520px; height: 520px; background: radial-gradient(circle, rgba(37,99,235,0.10), transparent 70%); z-index: 0; }
    .lp-hero::after { content: ''; position: absolute; bottom: -180px; left: -140px; width: 460px; height: 460px; background: radial-gradient(circle, rgba(249,115,22,0.08), transparent 70%); z-index: 0; }
    .lp-hero-grid { position: relative; z-index: 1; display: grid; grid-template-columns: minmax(0, 1fr) minmax(0, 1.04fr); gap: 50px; align-items: center; }
    .lp-h1 { font-size: 54px; font-weight: 800; letter-spacing: -1.6px; line-height: 1.06; }
    .lp-h1 .o { color: var(--orange); }
    .lp-h1 .b { color: var(--blue); }
    .lp-sub { font-size: 17px; color: var(--muted); line-height: 1.6; margin: 22px 0 28px; max-width: 470px; }
    .lp-roles { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; max-width: 510px; }
    .lp-role { border-radius: var(--radius); padding: 20px 20px 20px; position: relative; overflow: hidden; min-height: 168px; display: flex; flex-direction: column; color: #fff; }
    .lp-role-blue { background: linear-gradient(150deg, #3b82f6, #1d4ed8); }
    .lp-role-orange { background: linear-gradient(150deg, #fb923c, #ea580c); }
    .lp-role-img { position: absolute; right: -8px; top: -8px; width: 96px; height: 96px; border-radius: 50%; object-fit: cover; border: 3px solid rgba(255,255,255,0.35); }
    .lp-role h3 { color: #fff; font-size: 17px; font-weight: 800; margin-bottom: 6px; padding-right: 84px; }
    .lp-role p { font-size: 12.5px; color: rgba(255,255,255,0.9); line-height: 1.45; margin: 0; padding-right: 84px; }
    .lp-role .lp-role-btn { margin-top: auto; display: inline-flex; align-items: center; gap: 6px; background: #fff; color: var(--ink); font-weight: 800; font-size: 13px; padding: 9px 15px; border-radius: 9px; align-self: flex-start; }
    .lp-role-orange .lp-role-btn { color: var(--orange-dark); }
    .lp-role-blue .lp-role-btn { color: var(--blue-dark); }
    .lp-trustbadge { display: flex; align-items: center; gap: 14px; margin-top: 30px; }
    .lp-avatars { display: flex; }
    .lp-avatars img, .lp-avatars span { width: 34px; height: 34px; border-radius: 50%; border: 2.5px solid #fff; margin-left: -10px; object-fit: cover; }
    .lp-avatars img:first-child { margin-left: 0; }
    .lp-avatars span { background: var(--blue); color: #fff; font-size: 12px; font-weight: 800; display: flex; align-items: center; justify-content: center; }
    .lp-trustbadge p { font-size: 12.5px; color: var(--muted); margin: 0; line-height: 1.4; }
    .lp-trustbadge p b { color: var(--ink); }

    /* hero visual */
    .lp-hero-right { position: relative; min-height: 540px; }
    .lp-hero-photo { width: 100%; height: 540px; object-fit: cover; border-radius: 26px; box-shadow: var(--shadow-lg); }
    .lp-fcard { position: absolute; background: #fff; border-radius: 16px; box-shadow: var(--shadow-lg); border: 1px solid var(--line); }
    .lp-fc-match { top: 30px; left: -26px; width: 234px; padding: 13px; }
    .lp-fc-match .row { display: flex; gap: 10px; align-items: center; }
    .lp-fc-match img { width: 44px; height: 44px; border-radius: 11px; object-fit: cover; }
    .lp-fc-tag { font-size: 10px; font-weight: 800; color: var(--orange-dark); background: rgba(249,115,22,0.12); padding: 3px 8px; border-radius: 6px; display: inline-block; margin-bottom: 7px; }
    .lp-fc-match b { font-size: 13.5px; color: var(--ink); display: block; }
    .lp-fc-match small { font-size: 11px; color: var(--muted); }
    .lp-fc-match .stars { font-size: 11px; color: #f59e0b; font-weight: 700; margin-top: 4px; display: flex; align-items: center; gap: 4px; }
    .lp-fc-match .rated { margin-left: auto; font-size: 9.5px; font-weight: 800; color: var(--blue); background: rgba(37,99,235,0.1); padding: 3px 7px; border-radius: 6px; }
    .lp-fc-budget { left: -34px; bottom: 64px; width: 250px; padding: 15px; background: linear-gradient(160deg, #1e293b, #0f1b35); border: none; color: #fff; }
    .lp-fc-budget .top { display: flex; justify-content: space-between; align-items: flex-start; }
    .lp-fc-budget small { font-size: 11px; color: #9aa6bf; }
    .lp-fc-budget b { font-size: 22px; font-weight: 800; display: block; margin-top: 2px; }
    .lp-fc-budget .ok { font-size: 10px; font-weight: 800; color: #34d399; }
    .lp-fc-budget svg { width: 100%; height: 46px; margin-top: 8px; display: block; }
    .lp-fc-snap { top: 88px; right: -30px; width: 250px; padding: 15px; }
    .lp-fc-snap .sh { display: flex; gap: 10px; align-items: center; margin-bottom: 12px; }
    .lp-fc-snap .sh img { width: 40px; height: 40px; border-radius: 9px; object-fit: cover; }
    .lp-fc-snap .sh b { font-size: 13px; color: var(--ink); display: block; }
    .lp-fc-snap .sh small { font-size: 10.5px; color: var(--muted); }
    .lp-fc-row { display: flex; align-items: center; gap: 8px; font-size: 11.5px; padding: 6px 0; border-top: 1px solid var(--line-soft); }
    .lp-fc-row .ck { width: 16px; height: 16px; border-radius: 50%; background: #10b981; color: #fff; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
    .lp-fc-row .ck svg { width: 9px; height: 9px; }
    .lp-fc-row span { color: var(--ink-2); font-weight: 600; }
    .lp-fc-row em { margin-left: auto; font-style: normal; color: var(--muted); font-weight: 700; }
    .lp-fc-snap .vd { width: 100%; margin-top: 11px; text-align: center; background: rgba(37,99,235,0.08); color: var(--blue); font-weight: 800; font-size: 12px; padding: 9px; border-radius: 9px; display: flex; align-items: center; justify-content: center; gap: 6px; }
    .lp-fc-confirm { bottom: 6px; right: 24px; padding: 11px 15px; display: flex; align-items: center; gap: 10px; width: 252px; }
    .lp-fc-confirm .ic { width: 30px; height: 30px; border-radius: 50%; background: rgba(16,185,129,0.12); color: #10b981; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
    .lp-fc-confirm b { font-size: 12.5px; color: var(--ink); display: block; }
    .lp-fc-confirm small { font-size: 10.5px; color: var(--muted); }

    /* ── TRUST FEATURES BAR ─────────────────────────── */
    .lp-trust { margin: -40px auto 0; position: relative; z-index: 5; background: #fff; border: 1px solid var(--line); border-radius: 18px; box-shadow: var(--shadow); display: grid; grid-template-columns: repeat(5, minmax(0, 1fr)); }
    .lp-trust-item { padding: 22px 20px; display: flex; gap: 13px; align-items: flex-start; border-right: 1px solid var(--line-soft); }
    .lp-trust-item:last-child { border-right: none; }
    .lp-trust-ic { width: 40px; height: 40px; border-radius: 11px; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
    .lp-trust-ic svg { width: 20px; height: 20px; color: #fff; }
    .lp-trust-item b { font-size: 13.5px; color: var(--ink); display: block; margin-bottom: 3px; }
    .lp-trust-item p { font-size: 11.5px; color: var(--muted); margin: 0; line-height: 1.4; }

    /* ── CATEGORIES CAROUSEL ────────────────────────── */
    .lp-cats-head { display: flex; align-items: flex-end; justify-content: space-between; margin-bottom: 28px; }
    .lp-cats-head .lp-h2 { font-size: 30px; }
    .lp-viewall { font-size: 13.5px; font-weight: 800; color: var(--blue); display: inline-flex; align-items: center; gap: 6px; }
    .lp-cat-wrap { position: relative; }
    .lp-cat-track { display: grid; grid-auto-flow: column; grid-auto-columns: minmax(180px, 1fr); gap: 18px; overflow-x: auto; scroll-behavior: smooth; padding-bottom: 6px; scrollbar-width: none; }
    .lp-cat-track::-webkit-scrollbar { display: none; }
    .lp-cat { position: relative; border-radius: 16px; overflow: hidden; height: 150px; display: block; box-shadow: var(--shadow-sm); }
    .lp-cat img { width: 100%; height: 100%; object-fit: cover; transition: transform .5s; }
    .lp-cat:hover img { transform: scale(1.07); }
    .lp-cat::after { content: ''; position: absolute; inset: 0; background: linear-gradient(to top, rgba(15,27,53,0.78), transparent 65%); }
    .lp-cat b { position: absolute; bottom: 14px; left: 15px; right: 15px; z-index: 2; color: #fff; font-size: 15px; font-weight: 800; }
    .lp-cat-arrow { position: absolute; top: 50%; transform: translateY(-50%); width: 42px; height: 42px; border-radius: 50%; background: #fff; border: 1px solid var(--line); box-shadow: var(--shadow); display: flex; align-items: center; justify-content: center; cursor: pointer; z-index: 6; color: var(--ink); }
    .lp-cat-arrow:hover { color: var(--blue); border-color: var(--blue); }
    .lp-cat-arrow svg { width: 18px; height: 18px; }
    .lp-cat-arrow.prev { left: -20px; }
    .lp-cat-arrow.next { right: -20px; }

    /* ── HOW IT WORKS ───────────────────────────────── */
    .lp-works-grid { display: grid; grid-template-columns: repeat(5, minmax(0, 1fr)); gap: 8px; position: relative; }
    .lp-step { text-align: center; padding: 0 6px; position: relative; }
    .lp-step-ic { width: 84px; height: 84px; border-radius: 26px; margin: 0 auto 18px; display: flex; align-items: center; justify-content: center; position: relative; box-shadow: var(--shadow); }
    .lp-step-ic svg { width: 34px; height: 34px; color: #fff; }
    .lp-step-num { position: absolute; top: -8px; right: -8px; width: 26px; height: 26px; border-radius: 50%; color: #fff; font-size: 12px; font-weight: 800; display: flex; align-items: center; justify-content: center; border: 3px solid #fff; }
    .lp-step h4 { font-size: 15px; font-weight: 800; margin-bottom: 7px; }
    .lp-step p { font-size: 12.5px; color: var(--muted); line-height: 1.5; margin: 0 auto; max-width: 190px; text-wrap: balance; }
    .lp-step-line { position: absolute; top: 42px; left: 62%; width: 76%; height: 2px; z-index: 0; }
    .lp-step-line svg { width: 100%; height: 14px; }

    /* ── LEVEL OF ASSISTANCE ────────────────────────── */
    .lp-assist-grid { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 22px; }
    .lp-acard { background: #fff; border: 1px solid var(--line); border-radius: var(--radius-lg); overflow: hidden; box-shadow: var(--shadow-sm); transition: transform .2s, box-shadow .2s; display: flex; flex-direction: column; }
    .lp-acard:hover { transform: translateY(-5px); box-shadow: var(--shadow-lg); }
    .lp-acard-top { padding: 26px 26px 0; display: flex; gap: 16px; }
    .lp-acard-body { padding: 18px 26px 26px; display: flex; flex-direction: column; flex: 1; }
    .lp-acard-ic { width: 46px; height: 46px; border-radius: 13px; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
    .lp-acard-ic svg { width: 23px; height: 23px; color: #fff; }
    .lp-acard h3 { font-size: 18px; font-weight: 800; margin-bottom: 3px; }
    .lp-acard .tagline { font-size: 13px; color: var(--muted); }
    .lp-acard-img { width: 130px; height: 92px; border-radius: 12px; object-fit: cover; margin-left: auto; flex-shrink: 0; }
    .lp-acard ul { list-style: none; margin: 4px 0 18px; padding: 0; display: flex; flex-direction: column; gap: 11px; }
    .lp-acard li { display: flex; gap: 10px; align-items: flex-start; font-size: 13.5px; color: var(--ink-2); font-weight: 500; }
    .lp-acard li svg { width: 17px; height: 17px; flex-shrink: 0; margin-top: 1px; }
    .lp-acard-foot { margin-top: auto; font-size: 13px; font-weight: 800; display: inline-flex; align-items: center; gap: 6px; }

    /* ── WHY CHOOSE / VIDEO / TESTIMONIAL ───────────── */
    .lp-why-grid { display: grid; grid-template-columns: minmax(0, 0.9fr) minmax(0, 1.25fr) minmax(0, 0.9fr); gap: 30px; align-items: center; }
    .lp-why-list h3 { font-size: 22px; font-weight: 800; margin-bottom: 22px; }
    .lp-why-item { display: flex; gap: 13px; margin-bottom: 20px; }
    .lp-why-ic { width: 40px; height: 40px; border-radius: 11px; background: var(--bg-soft-2); display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
    .lp-why-ic svg { width: 19px; height: 19px; color: var(--blue); }
    .lp-why-item b { font-size: 14.5px; color: var(--ink); display: block; margin-bottom: 3px; }
    .lp-why-item p { font-size: 12.5px; color: var(--muted); margin: 0; line-height: 1.5; }
    .lp-video { position: relative; border-radius: 20px; overflow: hidden; box-shadow: var(--shadow-lg); }
    .lp-video img { width: 100%; height: 360px; object-fit: cover; }
    .lp-video .play { position: absolute; inset: 0; display: flex; align-items: center; justify-content: center; }
    .lp-video .play span { width: 66px; height: 66px; border-radius: 50%; background: rgba(255,255,255,0.92); display: flex; align-items: center; justify-content: center; box-shadow: var(--shadow); }
    .lp-video .play svg { width: 24px; height: 24px; color: var(--blue); margin-left: 3px; }
    .lp-video-badge { position: absolute; left: 18px; bottom: 18px; right: 18px; background: rgba(255,255,255,0.96); border-radius: 13px; padding: 11px 14px; display: flex; align-items: center; gap: 11px; }
    .lp-video-badge .vic { width: 36px; height: 36px; border-radius: 10px; background: linear-gradient(135deg,#fb923c,#ea580c); display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
    .lp-video-badge .vic svg { width: 18px; height: 18px; color: #fff; }
    .lp-video-badge b { font-size: 14px; color: var(--ink); display: block; }
    .lp-video-badge small { font-size: 11px; color: var(--muted); }
    .lp-testi h3 { font-size: 19px; font-weight: 800; margin-bottom: 16px; }
    .lp-testi-card { background: #fff; border: 1px solid var(--line); border-radius: 18px; padding: 24px; box-shadow: var(--shadow); }
    .lp-testi-q { font-size: 34px; line-height: 1; color: var(--blue); font-family: Georgia, serif; }
    .lp-testi-card p { font-size: 14px; color: var(--ink-2); line-height: 1.6; margin: 8px 0 18px; font-weight: 500; }
    .lp-testi-by { display: flex; align-items: center; gap: 11px; }
    .lp-testi-by img { width: 42px; height: 42px; border-radius: 50%; object-fit: cover; }
    .lp-testi-by b { font-size: 13.5px; color: var(--ink); display: block; }
    .lp-testi-by small { font-size: 11.5px; color: var(--muted); }
    .lp-testi-by .st { margin-left: auto; color: #f59e0b; font-size: 13px; }
    .lp-testi-dots { display: flex; gap: 6px; justify-content: center; margin-top: 18px; }
    .lp-testi-dots i { width: 7px; height: 7px; border-radius: 50%; background: var(--line); }
    .lp-testi-dots i.on { width: 20px; border-radius: 99px; background: var(--blue); }

    /* ── VALUE BAND (5 value tiles, no unverified numbers) ─────── */
    .lp-metrics-wrap { padding: 36px 0; }
    .lp-valueband { background: linear-gradient(100deg, #1d4ed8 0%, #2563eb 45%, #ea580c 100%); border-radius: 22px; display: grid; grid-template-columns: repeat(5, minmax(0, 1fr)); padding: 34px 0; box-shadow: var(--shadow-lg); }
    .lp-vb-tile { text-align: center; padding: 4px 18px; border-right: 1px solid rgba(255,255,255,0.2); display: flex; flex-direction: column; align-items: center; gap: 5px; }
    .lp-vb-tile:last-child { border-right: none; }
    .lp-vb-ic { width: 44px; height: 44px; border-radius: 13px; background: rgba(255,255,255,0.16); display: flex; align-items: center; justify-content: center; margin-bottom: 5px; box-shadow: inset 0 1.5px 0 rgba(255,255,255,0.25); }
    .lp-vb-ic svg { width: 22px; height: 22px; color: #fff; }
    .lp-vb-tile b { font-size: 21px; font-weight: 800; color: #fff; letter-spacing: 0.2px; line-height: 1; }
    .lp-vb-label { font-size: 13px; color: #fff; font-weight: 700; }
    .lp-vb-sub { font-size: 11.5px; color: rgba(255,255,255,0.82); line-height: 1.35; max-width: 150px; margin: 0 auto; text-align: center; text-wrap: balance; }

    /* ── PRICING ────────────────────────────────────── */
    .lp-pricing-grid { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 22px; align-items: stretch; max-width: 1020px; margin: 0 auto; }
    .lp-plan { background: #fff; border: 1px solid var(--line); border-radius: var(--radius-lg); padding: 30px 26px; display: flex; flex-direction: column; position: relative; box-shadow: var(--shadow-sm); }
    .lp-plan.pop { border: 2px solid var(--blue); box-shadow: var(--shadow-lg); transform: translateY(-8px); }
    .lp-plan-badge { position: absolute; top: 0; left: 50%; transform: translate(-50%, -50%); background: var(--blue); color: #fff; font-size: 11px; font-weight: 800; letter-spacing: .5px; padding: 6px 16px; border-radius: 999px; }
    .lp-plan h3 { font-size: 18px; font-weight: 800; }
    .lp-plan .pdesc { font-size: 12.5px; color: var(--muted); margin: 5px 0 16px; line-height: 1.45; min-height: 34px; }
    .lp-plan-price { display: flex; align-items: flex-end; gap: 3px; margin-bottom: 20px; }
    .lp-plan-price b { font-size: 44px; font-weight: 800; color: var(--ink); letter-spacing: -1.5px; line-height: 1; }
    .lp-plan-price span { font-size: 14px; color: var(--muted); font-weight: 600; margin-bottom: 6px; }
    .lp-plan ul { list-style: none; margin: 0 0 24px; padding: 0; display: flex; flex-direction: column; gap: 12px; }
    .lp-plan li { display: flex; gap: 10px; align-items: flex-start; font-size: 13px; color: var(--ink-2); font-weight: 500; }
    .lp-plan li svg { width: 16px; height: 16px; flex-shrink: 0; margin-top: 1px; }
    /* Cap the visible features so all three pricing cards read as a similar
       height; the rest expand on click. */
    .lp-plan li.lp-feat-extra { display: none; }
    .lp-plan ul.lp-expanded li.lp-feat-extra { display: flex; }
    .lp-more-btn { background: none; border: none; padding: 0; cursor: pointer; font-family: inherit; font-size: 13px; font-weight: 700; color: #2563eb; display: inline-flex; align-items: center; gap: 5px; }
    .lp-more-btn:hover { text-decoration: underline; }
    .lp-more-btn svg { width: 13px; height: 13px; }
    .lp-plan .lp-btn { width: 100%; margin-top: auto; }

    /* ── CTA BANNER ─────────────────────────────────── */
    .lp-cta-wrap { padding: 30px 0 80px; }
    .lp-cta { position: relative; border-radius: 24px; overflow: hidden; padding: 52px 56px; display: flex; align-items: center; justify-content: space-between; gap: 30px; flex-wrap: wrap; }
    .lp-cta::before { content: ''; position: absolute; inset: 0; background: linear-gradient(100deg, rgba(15,27,53,0.92), rgba(29,78,216,0.86) 55%, rgba(234,88,12,0.82)); z-index: 1; }
    .lp-cta img.bg { position: absolute; inset: 0; width: 100%; height: 100%; object-fit: cover; z-index: 0; }
    .lp-cta-txt { position: relative; z-index: 2; }
    .lp-cta-txt h2 { color: #fff; font-size: 32px; font-weight: 800; letter-spacing: -0.8px; max-width: 440px; }
    .lp-cta-txt p { color: rgba(255,255,255,0.9); font-size: 14.5px; margin: 12px 0 0; max-width: 440px; line-height: 1.5; }
    .lp-cta-btns { position: relative; z-index: 2; display: flex; gap: 14px; flex-wrap: wrap; }
    .lp-cta-btns .lp-btn { padding: 13px 22px; font-size: 14.5px; }
    .lp-cta-btns .lp-btn-white { background: #fff; color: var(--ink); }

    /* 3D icon shell (section header accents) */
    .ic3d { width: 30px; height: 30px; border-radius: 9px; display: inline-flex; align-items: center; justify-content: center; box-shadow: 0 5px 12px rgba(37,99,235,0.35), inset 0 1.5px 0 rgba(255,255,255,0.5); }
    .ic3d svg { width: 17px; height: 17px; color: #fff; }

    @media (max-width: 980px) {
        .lp-hero-grid { grid-template-columns: 1fr; gap: 36px; }
        .lp-hero-right { display: none; }
        .lp-trust { grid-template-columns: 1fr 1fr; }
        .lp-trust-item:nth-child(2) { border-right: none; }
        .lp-works-grid { grid-template-columns: 1fr 1fr; gap: 28px; }
        .lp-step-line { display: none; }
        .lp-assist-grid { grid-template-columns: 1fr; }
        .lp-why-grid { grid-template-columns: 1fr; }
        .lp-valueband { grid-template-columns: 1fr 1fr 1fr; gap: 22px 0; }
        .lp-vb-tile:nth-child(3) { border-right: none; }
        .lp-pricing-grid { grid-template-columns: 1fr; max-width: 420px; }
        .lp-plan.pop { transform: none; }
        .lp-h1 { font-size: 40px; }
        .lp-h2 { font-size: 28px; }
    }
    @media (max-width: 560px) {
        .lp-trust { grid-template-columns: 1fr; }
        .lp-trust-item { border-right: none; }
        .lp-works-grid { grid-template-columns: 1fr; }
        .lp-roles { grid-template-columns: 1fr; }
        .lp-cta { padding: 36px 28px; }
    }
</style>
@endpush

{{-- ════════════ HERO ════════════ --}}
<section class="lp-hero">
    <div class="lp-container lp-hero-grid">
        <div class="lp-hero-left">
            <h1 class="lp-h1">Where Events<br>Come to Life.<br><span class="o">Your Vision.</span> <span class="b">Our Network.</span></h1>
            <p class="lp-sub">The all-in-one marketplace to connect with verified event professionals or find top talent to plan, manage, and deliver unforgettable events.</p>

            <div class="lp-roles">
                <div class="lp-role lp-role-blue">
                    <img class="lp-role-img" src="https://images.unsplash.com/photo-1511578314322-379afb476865?w=200&q=80&auto=format&fit=crop" alt="">
                    <h3>I'm a Professional</h3>
                    <p>Grow your business and get discovered</p>
                    <a href="{{ route('register', ['role' => 'supplier']) }}" class="lp-role-btn">Join as a Pro
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" width="13" height="13"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg></a>
                </div>
                <div class="lp-role lp-role-orange">
                    <img class="lp-role-img" src="https://images.unsplash.com/photo-1469371670807-013ccf25f16a?w=200&q=80&auto=format&fit=crop" alt="">
                    <h3>I'm a Client</h3>
                    <p>Find the perfect team for your event</p>
                    <a href="{{ route('register', ['role' => 'client']) }}" class="lp-role-btn">Find Talent
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" width="13" height="13"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg></a>
                </div>
            </div>

            <div class="lp-trustbadge">
                <div class="lp-avatars">
                    <img src="https://images.unsplash.com/photo-1606800052052-a08af7148866?w=80&q=80&auto=format&fit=crop" alt="">
                    <img src="https://images.unsplash.com/photo-1511578314322-379afb476865?w=80&q=80&auto=format&fit=crop" alt="">
                    <img src="https://images.unsplash.com/photo-1464366400600-7168b8af9bc3?w=80&q=80&auto=format&fit=crop" alt="">
                    <img src="https://images.unsplash.com/photo-1607990281513-2c110a25bd8c?w=80&q=80&auto=format&fit=crop" alt="">
                    <span>+</span>
                </div>
                <p><b>Trusted by event professionals &amp; clients</b><br>around the world</p>
            </div>
        </div>

        <div class="lp-hero-right">
            <img class="lp-hero-photo" src="https://images.unsplash.com/photo-1464366400600-7168b8af9bc3?w=900&q=80&auto=format&fit=crop" alt="Elegant event venue">

            {{-- Booking confirmed pill --}}
            <div class="lp-fcard lp-fc-confirm">
                <span class="ic"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg></span>
                <div><b>Booking Confirmed! 🎉</b><small>Your event is in great hands.</small></div>
            </div>
        </div>
    </div>
</section>

{{-- ════════════ TRUST FEATURES BAR ════════════ --}}
<div class="lp-container">
    <div class="lp-trust">
        @php
            $trust = [
                ['#2563eb', '<path d="M9 12l2 2 4-4"/><path d="M21 12c0 1.66-.45 3.2-1.24 4.5C18.5 18.7 15.6 21 12 21s-6.5-2.3-7.76-4.5A8.94 8.94 0 0 1 3 12V5l9-3 9 3z"/>', 'Verified Professionals', 'Profile &amp; business verification'],
                ['#0ea5e9', '<rect x="1" y="4" width="22" height="16" rx="2"/><line x1="1" y1="10" x2="23" y2="10"/>', 'Secure Payments', 'Escrow protected transactions'],
                ['#8b5cf6', '<path d="M3 18v-6a9 9 0 0 1 18 0v6"/><path d="M21 19a2 2 0 0 1-2 2h-1a2 2 0 0 1-2-2v-3a2 2 0 0 1 2-2h3zM3 19a2 2 0 0 0 2 2h1a2 2 0 0 0 2-2v-3a2 2 0 0 0-2-2H3z"/>', 'Guided Support', 'Smart help at every step'],
                ['#10b981', '<rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/>', 'Privacy Controls', 'You choose what you share'],
                ['#f97316', '<path d="M6 9H4.5a2.5 2.5 0 0 1 0-5H6"/><path d="M18 9h1.5a2.5 2.5 0 0 0 0-5H18"/><path d="M4 22h16"/><path d="M10 14.66V17c0 .55-.47.98-.97 1.21C7.85 18.75 7 20.24 7 22"/><path d="M14 14.66V17c0 .55.47.98.97 1.21C16.15 18.75 17 20.24 17 22"/><path d="M18 2H6v7a6 6 0 0 0 12 0V2Z"/>', 'Built for Events', 'Smart tools to plan with confidence'],
            ];
        @endphp
        @foreach($trust as [$col, $path, $title, $desc])
            <div class="lp-trust-item">
                <span class="lp-trust-ic" style="background:linear-gradient(135deg,{{ $col }},{{ $col }}cc);box-shadow:0 5px 12px {{ $col }}40,inset 0 1.5px 0 rgba(255,255,255,0.4);">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">{!! $path !!}</svg>
                </span>
                <div><b>{{ $title }}</b><p>{!! $desc !!}</p></div>
            </div>
        @endforeach
    </div>
</div>

{{-- ════════════ EXPLORE POPULAR CATEGORIES ════════════ --}}
<section class="lp-section">
    <div class="lp-container">
        <div class="lp-cats-head">
            <h2 class="lp-h2">Explore Popular Categories</h2>
            <a href="{{ route('events-categories') }}" class="lp-viewall">View all categories
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" width="14" height="14"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg></a>
        </div>
        <div class="lp-cat-wrap">
            <button type="button" class="lp-cat-arrow prev" id="lpCatPrev" aria-label="Previous"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="15 18 9 12 15 6"/></svg></button>
            <div class="lp-cat-track" id="lpCatTrack">
                @foreach($showcaseCategories as $cat)
                    <a href="{{ $cat['link'] }}" class="lp-cat">
                        <img src="{{ $cat['image'] }}" alt="{{ $cat['name'] }}" loading="lazy">
                        <b>{{ $cat['name'] }}</b>
                    </a>
                @endforeach
            </div>
            <button type="button" class="lp-cat-arrow next" id="lpCatNext" aria-label="Next"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="9 18 15 12 9 6"/></svg></button>
        </div>
    </div>
</section>

{{-- ════════════ HOW IT WORKS ════════════ --}}
<section class="lp-section lp-section-soft">
    <div class="lp-container">
        <div class="lp-head">
            <h2 class="lp-h2">How GigResource Works
                <span class="ic3d" style="background:linear-gradient(135deg,#3b82f6,#1d4ed8);"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg></span>
            </h2>
        </div>
        <div class="lp-works-grid">
            @php
                $steps = [
                    ['#3b82f6', '#2563eb', '1', '<path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="12" y1="18" x2="12" y2="12"/><line x1="9" y1="15" x2="15" y2="15"/>', 'Tell Us What You Need', 'Share your event details, budget, and preferences.'],
                    ['#6366f1', '#4f46e5', '2', '<path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/>', 'Get Matched', 'We connect you with the best professionals.'],
                    ['#8b5cf6', '#7c3aed', '3', '<path d="M9 11l3 3L22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/>', 'Compare &amp; Choose', 'Review profiles, portfolios, reviews, and quotes.'],
                    ['#ec4899', '#db2777', '4', '<rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/><path d="M9 16l2 2 4-4"/>', 'Book with Confidence', 'Secure payments, contracts, and clear communication.'],
                    ['#fb923c', '#ea580c', '5', '<path d="M8 21h8m-4-4v4M5 3h14l-1 9a6 6 0 0 1-12 0L5 3z"/><path d="M5 7H3a2 2 0 0 0 0 4h2M19 7h2a2 2 0 0 1 0 4h-2"/>', 'Deliver &amp; Celebrate', 'Enjoy a seamless event experience.'],
                ];
            @endphp
            @foreach($steps as $i => [$c1, $c2, $num, $path, $title, $desc])
                <div class="lp-step">
                    <div class="lp-step-ic" style="background:linear-gradient(140deg,{{ $c1 }},{{ $c2 }});box-shadow:0 12px 24px {{ $c2 }}40,inset 0 2px 0 rgba(255,255,255,0.4);">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">{!! $path !!}</svg>
                        <span class="lp-step-num" style="background:{{ $c2 }};">{{ $num }}</span>
                    </div>
                    @if($i < 4)
                        <div class="lp-step-line"><svg viewBox="0 0 100 14" preserveAspectRatio="none"><line x1="0" y1="7" x2="100" y2="7" stroke="#cbd5e1" stroke-width="2" stroke-dasharray="5 5"/></svg></div>
                    @endif
                    <h4>{!! $title !!}</h4>
                    <p>{!! $desc !!}</p>
                </div>
            @endforeach
        </div>
    </div>
</section>

{{-- ════════════ LEVEL OF ASSISTANCE ════════════ --}}
<section class="lp-section">
    <div class="lp-container">
        <div class="lp-head">
            <h2 class="lp-h2">Choose Your Level of Assistance</h2>
            <p class="lp-lead">You're in control. Each level unlocks more capability — from fully manual to fully automated.</p>
        </div>
        <div class="lp-assist-grid">
            <div class="lp-acard">
                <div class="lp-acard-top">
                    <span class="lp-acard-ic" style="background:linear-gradient(135deg,#3b82f6,#1d4ed8);box-shadow:0 8px 16px rgba(37,99,235,0.32),inset 0 1.5px 0 rgba(255,255,255,0.4);"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="3" width="20" height="14" rx="2"/><line x1="8" y1="21" x2="16" y2="21"/><line x1="12" y1="17" x2="12" y2="21"/></svg></span>
                    <div><h3>Manual</h3><span class="tagline">You handle everything.</span></div>
                    <img class="lp-acard-img" src="https://images.unsplash.com/photo-1486312338219-ce68d2c6f44d?w=300&q=80&auto=format&fit=crop" alt="">
                </div>
                <div class="lp-acard-body">
                    <ul>
                        <li><svg viewBox="0 0 24 24" fill="none" stroke="#2563eb" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>Search &amp; compare professionals</li>
                        <li><svg viewBox="0 0 24 24" fill="none" stroke="#2563eb" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>Message &amp; negotiate</li>
                        <li><svg viewBox="0 0 24 24" fill="none" stroke="#2563eb" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>Manage bookings &amp; payments</li>
                    </ul>
                    <a href="{{ route('register', ['role' => 'client']) }}" class="lp-acard-foot" style="color:var(--blue);">Best for experienced planners
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" width="13" height="13"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg></a>
                </div>
            </div>
            <div class="lp-acard">
                <div class="lp-acard-top">
                    <span class="lp-acard-ic" style="background:linear-gradient(135deg,#60a5fa,#2563eb);box-shadow:0 8px 16px rgba(37,99,235,0.32),inset 0 1.5px 0 rgba(255,255,255,0.4);"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg></span>
                    <div><h3>Semi-Assisted</h3><span class="tagline">AI guides the way.</span></div>
                    <img class="lp-acard-img" src="https://images.unsplash.com/photo-1556761175-b413da4baf72?w=300&q=80&auto=format&fit=crop" alt="">
                </div>
                <div class="lp-acard-body">
                    <ul>
                        <li><svg viewBox="0 0 24 24" fill="none" stroke="#2563eb" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>Smart suggestions</li>
                        <li><svg viewBox="0 0 24 24" fill="none" stroke="#2563eb" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>AI recommendations</li>
                        <li><svg viewBox="0 0 24 24" fill="none" stroke="#2563eb" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>Templates &amp; best practices</li>
                    </ul>
                    <a href="{{ route('register', ['role' => 'client']) }}" class="lp-acard-foot" style="color:var(--blue);">Best for growing planners
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" width="13" height="13"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg></a>
                </div>
            </div>
            <div class="lp-acard">
                <div class="lp-acard-top">
                    <span class="lp-acard-ic" style="background:linear-gradient(135deg,#fb923c,#ea580c);box-shadow:0 8px 16px rgba(234,88,12,0.32),inset 0 1.5px 0 rgba(255,255,255,0.4);"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M2 20h20l-2-9-4 3-4-7-4 7-4-3-2 9z"/></svg></span>
                    <div><h3>Maximum Assistance</h3><span class="tagline">AI handles the details.</span></div>
                    <img class="lp-acard-img" src="https://images.unsplash.com/photo-1519741497674-611481863552?w=300&q=80&auto=format&fit=crop" alt="">
                </div>
                <div class="lp-acard-body">
                    <ul>
                        <li><svg viewBox="0 0 24 24" fill="none" stroke="#ea580c" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>AI matches &amp; outreach</li>
                        <li><svg viewBox="0 0 24 24" fill="none" stroke="#ea580c" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>AI negotiation assistant</li>
                        <li><svg viewBox="0 0 24 24" fill="none" stroke="#ea580c" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>Smart booking workflows</li>
                    </ul>
                    <a href="{{ route('register', ['role' => 'client']) }}" class="lp-acard-foot" style="color:var(--orange-dark);">Best for busy professionals &amp; clients
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" width="13" height="13"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg></a>
                </div>
            </div>
        </div>
    </div>
</section>

{{-- ════════════ WHY CHOOSE / VIDEO / TESTIMONIAL ════════════ --}}
<section class="lp-section lp-section-soft">
    <div class="lp-container">
        <div class="lp-why-grid">
            <div class="lp-why-list">
                <h3>Why Choose GigResource?</h3>
                @php
                    $why = [
                        ['<path d="M9 12l2 2 4-4"/><circle cx="12" cy="12" r="10"/>', 'Quality You Can Trust', 'Every professional is verified &amp; reviewed'],
                        ['<rect x="2" y="7" width="20" height="14" rx="2"/><path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"/>', 'All-in-One Convenience', 'Everything you need in one place'],
                        ['<line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/>', 'Time &amp; Cost Saving', 'Smart tools to save you time &amp; money'],
                        ['<path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>', 'Flexible for Every Event', 'Any type, any size, anywhere'],
                    ];
                @endphp
                @foreach($why as [$path, $t, $d])
                    <div class="lp-why-item">
                        <span class="lp-why-ic"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">{!! $path !!}</svg></span>
                        <div><b>{!! $t !!}</b><p>{!! $d !!}</p></div>
                    </div>
                @endforeach
            </div>

            <div class="lp-video">
                <img src="https://images.unsplash.com/photo-1492684223066-81342ee5ff30?w=800&q=80&auto=format&fit=crop" alt="Event highlights">
                <div class="play"><span><svg viewBox="0 0 24 24" fill="currentColor"><polygon points="5 3 19 12 5 21 5 3"/></svg></span></div>
                <div class="lp-video-badge">
                    <span class="vic"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/></svg></span>
                    <div><b>Powering Unforgettable Celebrations</b><small>Weddings, conferences, concerts &amp; more</small></div>
                </div>
            </div>

            <div class="lp-testi">
                <h3>Loved by Our Community</h3>
                <div class="lp-testi-card">
                    <div class="lp-testi-q">&ldquo;</div>
                    @if($featuredReview)
                        <p>{{ \Illuminate\Support\Str::limit($featuredReview->comment, 170) }}</p>
                        <div class="lp-testi-by">
                            <img src="https://images.unsplash.com/photo-1464366400600-7168b8af9bc3?w=120&q=80&auto=format&fit=crop" alt="">
                            <div><b>{{ optional($featuredReview->reviewer)->name ?? 'Verified Client' }}</b><small>GigResource Member</small></div>
                            <span class="st">★★★★★</span>
                        </div>
                    @else
                        <p>GigResource helped me find amazing vendors and grew my business 3X faster. The tools and support make my job so much easier!</p>
                        <div class="lp-testi-by">
                            <img src="https://images.unsplash.com/photo-1606800052052-a08af7148866?w=120&q=80&auto=format&fit=crop" alt="">
                            <div><b>Sarah J.</b><small>Wedding Planner</small></div>
                            <span class="st">★★★★★</span>
                        </div>
                    @endif
                </div>
                <div class="lp-testi-dots"><i class="on"></i><i></i><i></i></div>
            </div>
        </div>
    </div>
</section>

{{-- ════════════ VALUE BAND (value-driven — no unverified metrics; client-approved design) ════════════ --}}
<div class="lp-metrics-wrap">
    <div class="lp-container">
        <div class="lp-valueband">
            @php
                $vbTiles = [
                    ['<path d="M9 12l2 2 4-4"/><path d="M21 12c0 1.66-.45 3.2-1.24 4.5C18.5 18.7 15.6 21 12 21s-6.5-2.3-7.76-4.5A8.94 8.94 0 0 1 3 12V5l9-3 9 3z"/>', 'VERIFIED', 'Professionals', 'Trust badges &amp; profile verification'],
                    ['<rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/>', 'ALL EVENTS', 'Event Solutions', 'Solutions for events of any size'],
                    ['<line x1="4" y1="21" x2="4" y2="14"/><line x1="4" y1="10" x2="4" y2="3"/><line x1="12" y1="21" x2="12" y2="12"/><line x1="12" y1="8" x2="12" y2="3"/><line x1="20" y1="21" x2="20" y2="16"/><line x1="20" y1="12" x2="20" y2="3"/><line x1="1" y1="14" x2="7" y2="14"/><line x1="9" y1="8" x2="15" y2="8"/><line x1="17" y1="16" x2="23" y2="16"/>', '3 LEVELS', 'Assistance', 'Manual · Semi-Assisted · Maximum'],
                    ['<rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/>', 'SECURE', 'Payments &amp; Contracts', 'Escrow protection &amp; e-signatures'],
                    ['<path d="M3 18v-6a9 9 0 0 1 18 0v6"/><path d="M21 19a2 2 0 0 1-2 2h-1a2 2 0 0 1-2-2v-3a2 2 0 0 1 2-2h3zM3 19a2 2 0 0 0 2 2h1a2 2 0 0 0 2-2v-3a2 2 0 0 0-2-2H3z"/>', 'SUPPORT', 'Help Center', 'Guides &amp; help when you need it'],
                ];
            @endphp
            @foreach($vbTiles as [$icon, $word, $label, $sub])
                <div class="lp-vb-tile">
                    <span class="lp-vb-ic"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">{!! $icon !!}</svg></span>
                    <b>{{ $word }}</b>
                    <span class="lp-vb-label">{!! $label !!}</span>
                    <span class="lp-vb-sub">{!! $sub !!}</span>
                </div>
            @endforeach
        </div>
    </div>
</div>

{{-- ════════════ PRICING ════════════ --}}
<section class="lp-section" id="pricing">
    <div class="lp-container">
        <div class="lp-head">
            <h2 class="lp-h2">Simple, Transparent Pricing</h2>
            <p class="lp-lead">Choose the plan that's right for you.</p>
        </div>
        <div class="lp-pricing-grid">
            @foreach($plans as $index => $plan)
                @php
                    $pop = (bool) $plan->is_featured;
                    $accent = $pop ? '#2563eb' : ($loop->last ? '#ea580c' : '#2563eb');
                    $isFree = $plan->price <= 0;
                    $priceDisplay = $isFree ? 'Free' : '$' . rtrim(rtrim(number_format($plan->price, 2), '0'), '.');
                    $priceSuffix = $isFree ? '' : trim($plan->billingLabel());
                    // Badge + colour come from the plan module's own fields (admin-managed).
                    $badgeMap = ['primary' => '#2563eb', 'success' => '#10b981', 'warning' => '#f59e0b', 'danger' => '#ef4444', 'info' => '#0ea5e9', 'secondary' => '#64748b', 'orange' => '#f97316'];
                    $badgeColor = $badgeMap[$plan->badge_color] ?? '#2563eb';
                    if ($pop)            { $ctaLabel = 'Start Free Trial'; $ctaClass = 'lp-btn-blue';    $ctaRole = 'supplier'; }
                    elseif ($loop->last) { $ctaLabel = 'Contact Sales';    $ctaClass = 'lp-btn-outline'; $ctaRole = null; }
                    else                 { $ctaLabel = 'Get Started';      $ctaClass = 'lp-btn-outline'; $ctaRole = 'supplier'; }
                    $ctaHref = $ctaRole ? route('register', ['role' => $ctaRole]) : route('about-us');
                @endphp
                <div class="lp-plan {{ $pop ? 'pop' : '' }}">
                    @if($plan->badge_text)<span class="lp-plan-badge" style="background:{{ $badgeColor }};">{{ strtoupper($plan->badge_text) }}</span>@endif
                    <h3>{{ $plan->name }}</h3>
                    <p class="pdesc">{{ \Illuminate\Support\Str::limit($plan->description, 72) }}</p>
                    <div class="lp-plan-price">
                        <b>{{ $priceDisplay }}</b>@if($priceSuffix)<span>{{ $priceSuffix }}</span>@endif
                    </div>
                    @php
                        $lpIncluded = $plan->features->where('is_included', true)->values();
                        $lpLimit = 8;
                    @endphp
                    <ul>
                        @forelse($lpIncluded as $fi => $f)
                            <li class="{{ $fi >= $lpLimit ? 'lp-feat-extra' : '' }}"><svg viewBox="0 0 24 24" fill="none" stroke="{{ $accent }}" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>{{ $f->feature }}</li>
                        @empty
                            <li><svg viewBox="0 0 24 24" fill="none" stroke="{{ $accent }}" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>{{ $plan->contractTermLabel() }}</li>
                        @endforelse
                        @if($lpIncluded->count() > $lpLimit)
                            <li><button type="button" class="lp-more-btn" data-more="{{ $lpIncluded->count() - $lpLimit }}" onclick="lpToggleFeatures(this)">+ {{ $lpIncluded->count() - $lpLimit }} more to know<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="6 9 12 15 18 9"/></svg></button></li>
                        @endif
                    </ul>
                    <a href="{{ $ctaHref }}" class="lp-btn {{ $ctaClass }}">{{ $ctaLabel }}</a>
                </div>
            @endforeach
        </div>
        <p style="text-align:center;color:var(--muted);font-size:13px;margin-top:26px;">All plans include secure payments and escrow protection.</p>
    </div>
</section>

{{-- ════════════ CTA BANNER ════════════ --}}
<div class="lp-cta-wrap">
    <div class="lp-container">
        <div class="lp-cta">
            <img class="bg" src="https://images.unsplash.com/photo-1464366400600-7168b8af9bc3?w=1200&q=80&auto=format&fit=crop" alt="">
            <div class="lp-cta-txt">
                <h2>Ready to Create Unforgettable Events?</h2>
                <p>Join thousands of professionals and clients who trust GigResource to bring their events to life.</p>
            </div>
            <div class="lp-cta-btns">
                <a href="{{ route('register', ['role' => 'supplier']) }}" class="lp-btn lp-btn-white">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="16" height="16"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><line x1="19" y1="8" x2="19" y2="14"/><line x1="22" y1="11" x2="16" y2="11"/></svg>
                    Join as a Professional</a>
                <a href="{{ route('register', ['role' => 'client']) }}" class="lp-btn lp-btn-orange">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="16" height="16"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                    Hire Top Talent</a>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
    // Expand / collapse the extra pricing-card features (Peter: keep card
    // heights consistent).
    function lpToggleFeatures(btn) {
        var ul = btn.closest('ul');
        if (!ul) return;
        var expanded = ul.classList.toggle('lp-expanded');
        var more = btn.getAttribute('data-more');
        btn.firstChild.nodeValue = expanded ? 'Show less' : ('+ ' + more + ' more to know');
        var svg = btn.querySelector('svg');
        if (svg) svg.style.transform = expanded ? 'rotate(180deg)' : '';
    }
</script>
<script>
    (function () {
        var track = document.getElementById('lpCatTrack');
        var prev = document.getElementById('lpCatPrev');
        var next = document.getElementById('lpCatNext');
        if (track && prev && next) {
            var step = function () { return Math.max(track.clientWidth * 0.8, 220); };
            prev.addEventListener('click', function () { track.scrollBy({ left: -step(), behavior: 'smooth' }); });
            next.addEventListener('click', function () { track.scrollBy({ left: step(), behavior: 'smooth' }); });
        }
    })();
</script>
@endpush

@endsection
