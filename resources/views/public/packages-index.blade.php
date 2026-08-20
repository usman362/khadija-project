@extends('layouts.landing')

@section('title', 'Find the Perfect Package — GigResource')
@section('meta_description', 'Search ready-made service bundles from event professionals who handle multiple parts of your event — one contract, one payment, better value.')

@php
    use App\Support\ResponseStats;

    $f = $filters;

    /*
     * Every filter on the page, as a query array. Sorting, paging, the view
     * toggle and the removable chips all start from this one value, so
     * changing the sort can no longer drop the client's chosen occasion —
     * which is exactly what happened when each control built its own.
     */
    $carry = array_filter([
        'services'   => $f['selected'] ?: null,
        'q'          => $f['q'] ?: null,
        'event_type' => $f['event_type'] ?: null,
        'location'   => $f['location'] ?: null,
        'scope'      => $f['location'] !== '' && $f['scope'] === 'city' ? 'city' : null,
        'budget_min' => $f['budget_min'] > $budgetFloor ? $f['budget_min'] : null,
        'budget_max' => $f['budget_max'] < $budgetCeiling ? $f['budget_max'] : null,
        'date'       => $f['date'] ?: null,
        'guests'     => $f['guests'] ?: null,
        'saved'      => ! empty($f['saved']) ? 1 : null,
        'sort'       => $f['sort'] !== 'relevant' ? $f['sort'] : null,
        'view'       => $f['view'] !== 'list' ? $f['view'] : null,
        'per_page'   => $perPage !== $perPageOptions[0] ? $perPage : null,
    ], fn ($v) => $v !== null);

    $link = fn (array $overrides = []) => route('public.packages', array_filter(
        array_merge($carry, $overrides),
        fn ($v) => $v !== null && $v !== '' && $v !== []
    ));

    $withoutService = fn ($svc) => $link(['services' => array_values(array_diff($f['selected'], [$svc])) ?: null]);

    $activeCount = count($f['selected'])
        + ($f['event_type'] !== '' ? 1 : 0)
        + ($f['location'] !== '' ? 1 : 0)
        + ($f['budget_min'] > $budgetFloor || $f['budget_max'] < $budgetCeiling ? 1 : 0)
        + ($f['date'] !== '' ? 1 : 0)
        + ($f['guests'] > 0 ? 1 : 0)
        + (! empty($f['saved']) ? 1 : 0);
@endphp

@push('styles')
<style>
    .pk { --pk: var(--orange, #f97316); --pk-dark: #ea580c; --pk-soft: #fff4ec; }
    .pk-wrap { background: var(--bg-soft); }
    .pk-shell { max-width: 1480px; margin: 0 auto; padding: 20px 22px 60px; }

    /* Hero */
    .pk-hero { display: flex; align-items: center; justify-content: space-between; gap: 24px; flex-wrap: wrap; padding: 22px 0 20px; }
    .pk-hero h1 { font-size: clamp(1.7rem, 3.4vw, 2.5rem); margin: 0 0 6px; }
    .pk-hero h1 span { color: var(--pk); }
    .pk-hero p { color: var(--muted); font-size: 15px; max-width: 430px; margin: 0; }
    .pk-props { display: grid; grid-template-columns: repeat(4, minmax(120px, 1fr)); gap: 12px; }
    .pk-prop { display: flex; align-items: center; gap: 10px; background: #fff; border: 1px solid var(--line); border-radius: 12px; padding: 12px 14px; }
    .pk-prop svg { width: 22px; height: 22px; color: var(--pk); flex-shrink: 0; }
    .pk-prop b { display: block; font-size: 13px; font-weight: 800; color: var(--ink); line-height: 1.2; }
    .pk-prop span { font-size: 11.5px; color: var(--muted); }

    /* Toolbar */
    .pk-toolbar { display: flex; align-items: center; gap: 12px; flex-wrap: wrap; background: #fff; border: 1px solid var(--line); border-radius: 14px; padding: 12px 16px; margin-bottom: 18px; }
    .pk-sel-lbl { font-size: 13px; font-weight: 800; color: var(--ink); display: inline-flex; align-items: center; gap: 6px; }
    .pk-sel-lbl .i { width: 16px; height: 16px; border-radius: 50%; background: var(--line); color: var(--muted); font-size: 11px; display: inline-flex; align-items: center; justify-content: center; font-weight: 700; }
    .pk-chip { display: inline-flex; align-items: center; gap: 7px; background: var(--pk-soft); color: var(--pk-dark); border: 1px solid #fed7aa; border-radius: 999px; padding: 6px 12px; font-size: 12.5px; font-weight: 700; text-decoration: none; }
    .pk-chip a { color: var(--pk-dark); text-decoration: none; font-weight: 800; line-height: 1; }
    .pk-addsvc { display: inline-flex; align-items: center; gap: 6px; border: 1px dashed var(--line); background: #fff; border-radius: 999px; padding: 6px 13px; font-size: 12.5px; font-weight: 700; color: var(--ink-2); cursor: pointer; }
    .pk-favlink { display: inline-flex; align-items: center; gap: 6px; border: 1px solid #fecaca; background: #fff5f5; color: #b91c1c; border-radius: 999px; padding: 6px 13px; font-size: 12.5px; font-weight: 700; }
    .pk-favlink.on { background: #dc2626; border-color: #dc2626; color: #fff; }
    .pk-count { font-size: 13.5px; font-weight: 700; color: var(--ink); }
    .pk-count b { color: var(--pk); }
    .pk-tools-right { margin-left: auto; display: inline-flex; align-items: center; gap: 14px; flex-wrap: wrap; }
    .pk-sortsel { display: inline-flex; align-items: center; gap: 7px; font-size: 12.5px; font-weight: 700; color: var(--muted); }
    .pk-sortsel select { border: 1px solid var(--line); border-radius: 9px; padding: 7px 10px; font-size: 12.5px; font-weight: 700; color: var(--ink); background: #fff; font-family: inherit; cursor: pointer; }
    .pk-viewtog { display: inline-flex; gap: 4px; font-size: 12.5px; font-weight: 700; color: var(--muted); align-items: center; }
    .pk-viewtog a { display: inline-flex; align-items: center; gap: 5px; padding: 6px 9px; border-radius: 8px; text-decoration: none; color: var(--muted); border: 1px solid transparent; }
    .pk-viewtog a.on { color: var(--pk-dark); border-color: #fed7aa; background: var(--pk-soft); }

    /* Flash */
    .pk-flash { border-radius: 12px; padding: 11px 15px; font-size: 13px; font-weight: 700; margin-bottom: 14px; }
    .pk-flash.ok { background: #ecfdf5; border: 1px solid #a7f3d0; color: #065f46; }
    .pk-flash.no { background: #fef2f2; border: 1px solid #fecaca; color: #991b1b; }

    /* Layout */
    .pk-grid { display: grid; grid-template-columns: 262px minmax(0,1fr) 288px; gap: 20px; align-items: start; }

    /* Left rail */
    .pk-rail { background: #fff; border: 1px solid var(--line); border-radius: 16px; padding: 18px; }
    .pk-rail-head { display: flex; align-items: center; justify-content: space-between; gap: 10px; margin-bottom: 14px; }
    .pk-rail-head h3 { font-size: 15px; margin: 0; }
    .pk-clear { font-size: 12px; font-weight: 700; color: var(--pk-dark); text-decoration: none; white-space: nowrap; }
    .pk-rail-sec { font-size: 13px; font-weight: 800; color: var(--ink); margin: 16px 0 4px; }
    .pk-rail-hint { font-size: 11.5px; color: var(--muted); margin: 0 0 10px; line-height: 1.45; }
    .pk-input { width: 100%; border: 1px solid var(--line); border-radius: 9px; padding: 8px 11px; font-size: 12.5px; font-family: inherit; color: var(--ink); background: #fff; }
    .pk-input + .pk-input { margin-top: 8px; }
    select.pk-input { cursor: pointer; }
    .pk-svcsearch { margin-bottom: 10px; }
    .pk-check { display: flex; align-items: center; gap: 9px; padding: 6px 0; font-size: 13px; color: var(--ink-2); cursor: pointer; }
    .pk-check input { width: 15px; height: 15px; accent-color: var(--pk); }
    .pk-check .cnt { margin-left: auto; font-size: 11px; color: var(--muted); font-weight: 700; }
    .pk-check.hidden { display: none; }
    .pk-showmore { font-size: 12.5px; font-weight: 700; color: var(--pk-dark); background: none; border: none; cursor: pointer; padding: 6px 0; }
    .pk-apply { width: 100%; margin-top: 16px; border: none; background: var(--pk); color: #fff; border-radius: 11px; padding: 11px; font-size: 14px; font-weight: 800; cursor: pointer; font-family: inherit; }
    .pk-apply:hover { background: var(--pk-dark); }
    .pk-savebtn { display: flex; align-items: center; justify-content: center; gap: 6px; width: 100%; margin-top: 10px; background: none; border: none; font-size: 12.5px; font-weight: 800; color: var(--pk-dark); cursor: pointer; font-family: inherit; padding: 4px; }
    .pk-savebtn svg { width: 14px; height: 14px; }
    .pk-divider { height: 1px; background: var(--line); margin: 16px 0; }
    .pk-saved-list { margin-top: 10px; display: flex; flex-direction: column; gap: 6px; }
    .pk-saved-row { display: flex; align-items: center; gap: 8px; font-size: 11.5px; }
    .pk-saved-row a { color: var(--ink-2); font-weight: 600; line-height: 1.35; }
    .pk-saved-row a:hover { color: var(--pk-dark); }
    .pk-saved-row button { margin-left: auto; background: none; border: none; color: var(--faint); cursor: pointer; font-size: 13px; line-height: 1; padding: 2px 4px; }

    /* Budget dual slider — two ranges stacked, thumbs only. */
    .pk-range { position: relative; height: 30px; margin: 6px 0 2px; }
    .pk-range .track { position: absolute; top: 13px; left: 0; right: 0; height: 4px; border-radius: 3px; background: var(--line); }
    .pk-range .fill { position: absolute; top: 13px; height: 4px; border-radius: 3px; background: var(--pk); }
    .pk-range input[type=range] { position: absolute; top: 6px; left: 0; width: 100%; margin: 0; height: 18px; background: none; pointer-events: none; -webkit-appearance: none; appearance: none; }
    .pk-range input[type=range]::-webkit-slider-thumb { -webkit-appearance: none; pointer-events: auto; width: 17px; height: 17px; border-radius: 50%; background: #fff; border: 3px solid var(--pk); cursor: pointer; }
    .pk-range input[type=range]::-moz-range-thumb { pointer-events: auto; width: 13px; height: 13px; border-radius: 50%; background: #fff; border: 3px solid var(--pk); cursor: pointer; }
    .pk-range input[type=range]:focus-visible::-webkit-slider-thumb { outline: 3px solid var(--blue-onwhite-2); outline-offset: 2px; }
    .pk-range-vals { display: flex; justify-content: space-between; font-size: 11.5px; font-weight: 700; color: var(--ink-2); font-family: var(--ff); }

    /* Cards (list) */
    .pk-cards { display: flex; flex-direction: column; gap: 16px; }
    .pk-card { display: grid; grid-template-columns: 228px minmax(0,1fr) 178px; background: #fff; border: 1px solid var(--line); border-radius: 16px; overflow: hidden; transition: transform .18s, box-shadow .18s; }
    .pk-media { position: relative; min-height: 232px; background: linear-gradient(135deg,#e2e8f0,#eef2ff); }
    .pk-slide { position: absolute; inset: 0; width: 100%; height: 100%; object-fit: cover; opacity: 0; transition: opacity .4s ease; }
    .pk-slide.active { opacity: 1; }
    .pk-nav { position: absolute; top: 50%; transform: translateY(-50%); z-index: 3; width: 30px; height: 30px; border-radius: 50%; border: none; background: rgba(255,255,255,.94); color: #111827; font-size: 17px; line-height: 1; display: flex; align-items: center; justify-content: center; cursor: pointer; opacity: 0; transition: opacity .15s; box-shadow: 0 2px 8px rgba(0,0,0,.22); }
    .pk-media:hover .pk-nav, .pk-nav:focus-visible { opacity: 1; }
    .pk-nav.prev { left: 9px; } .pk-nav.next { right: 9px; }
    .pk-dots { position: absolute; bottom: 10px; right: 10px; z-index: 2; display: flex; gap: 5px; }
    .pk-dot { width: 6px; height: 6px; border-radius: 50%; background: rgba(255,255,255,.6); transition: all .15s; }
    .pk-dot.active { background: #fff; width: 15px; border-radius: 3px; }
    .pk-cards.grid .pk-card:hover { transform: translateY(-3px); box-shadow: 0 16px 36px rgba(0,0,0,.12); }
    .pk-photos { position: absolute; bottom: 10px; left: 10px; font-size: 11px; font-weight: 700; color: #fff; background: rgba(0,0,0,.62); padding: 4px 9px; border-radius: 7px; display: inline-flex; align-items: center; gap: 5px; }
    .pk-heart { position: absolute; top: 10px; right: 10px; width: 32px; height: 32px; border-radius: 50%; background: rgba(255,255,255,.94); border: none; display: flex; align-items: center; justify-content: center; cursor: pointer; box-shadow: 0 2px 8px rgba(0,0,0,.16); }
    .pk-heart svg { width: 17px; height: 17px; color: #556070; }
    .pk-heart.on svg { color: #dc2626; fill: #dc2626; }
    .pk-cmp { position: absolute; top: 10px; left: 10px; display: inline-flex; align-items: center; gap: 6px; background: rgba(255,255,255,.94); border-radius: 8px; padding: 5px 9px; font-size: 11.5px; font-weight: 800; color: var(--ink); cursor: pointer; box-shadow: 0 2px 8px rgba(0,0,0,.16); }
    .pk-cmp input { width: 14px; height: 14px; accent-color: var(--pk); margin: 0; }

    .pk-main { padding: 16px 18px; min-width: 0; display: flex; flex-direction: column; }
    .pk-title { font-size: 17px; margin: 0 0 4px; }
    /* Named on purpose: the layout styles `article a:not([class])` for blog
       prose, the card IS an <article>, and a classless anchor in here came out
       indigo and underlined. */
    .pk-title-link { color: var(--ink); text-decoration: none; }
    .pk-title-link:hover { color: var(--pk-dark); }
    .pk-pro { font-size: 13px; color: var(--ink-2); font-weight: 600; display: flex; align-items: center; gap: 6px; flex-wrap: wrap; }
    .pk-verif { color: var(--green-onwhite); }
    .pk-meta { display: flex; align-items: center; gap: 9px; flex-wrap: wrap; margin: 8px 0 0; font-size: 12px; color: var(--muted); font-weight: 600; }
    .pk-meta .star { color: #b45309; font-weight: 800; font-family: var(--ff); }
    .pk-meta .sep { color: #cbd5e1; }
    .pk-tags { display: flex; flex-wrap: wrap; gap: 6px; margin: 10px 0; }
    .pk-tag { display: inline-flex; align-items: center; gap: 5px; font-size: 11.5px; font-weight: 600; color: var(--ink-2); background: var(--bg-soft); border: 1px solid var(--line); border-radius: 7px; padding: 3px 9px; }
    .pk-facts { display: grid; grid-template-columns: repeat(auto-fit, minmax(104px, 1fr)); gap: 10px 12px; background: var(--pk-soft); border-radius: 10px; padding: 11px 13px; margin-bottom: 11px; }
    .pk-fact { display: flex; gap: 7px; min-width: 0; }
    .pk-fact > div { min-width: 0; }
    .pk-fact svg { width: 14px; height: 14px; color: var(--pk-dark); flex-shrink: 0; margin-top: 2px; }
    .pk-fact span { display: block; font-size: 9.5px; font-weight: 800; letter-spacing: .2px; text-transform: uppercase; color: var(--pk-dark); margin-bottom: 2px; white-space: nowrap; }
    .pk-fact b { font-size: 12px; font-weight: 700; color: var(--ink); display: block; line-height: 1.35; }
    .pk-cardfoot { display: flex; align-items: center; gap: 16px; flex-wrap: wrap; font-size: 12px; color: var(--muted); font-weight: 600; margin-top: auto; }
    .pk-cardfoot .free { color: var(--green-onwhite); font-weight: 700; }

    .pk-pricebox { border-left: 1px solid var(--line); padding: 18px 16px; display: flex; flex-direction: column; align-items: center; text-align: center; justify-content: center; }
    .pk-pricebox .lbl { font-size: 11px; color: var(--muted); font-weight: 700; }
    .pk-pricebox .amt { font-size: 25px; font-family: var(--ff); font-weight: 800; color: var(--ink); line-height: 1.1; margin: 2px 0; }
    .pk-pricebox .tp { font-size: 11px; color: var(--muted); margin-bottom: 12px; }
    .pk-btn { display: block; width: 100%; text-align: center; border-radius: 10px; padding: 10px; font-size: 13.5px; font-weight: 800; text-decoration: none; cursor: pointer; }
    .pk-btn-primary { background: var(--pk); color: #fff; border: none; }
    .pk-btn-primary:hover { background: var(--pk-dark); }
    .pk-save { font-size: 11.5px; font-family: var(--ff); font-weight: 800; color: var(--green-onwhite); margin-top: 12px; line-height: 1.4; }

    /* Grid view */
    .pk-cards.grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(272px,1fr)); gap: 18px; }
    .pk-cards.grid .pk-card { grid-template-columns: 1fr; }
    .pk-cards.grid .pk-media { min-height: 168px; }
    .pk-cards.grid .pk-pricebox { border-left: none; border-top: 1px solid var(--line); }
    .pk-cards.grid .pk-facts { grid-template-columns: 1fr; }

    /* Pager */
    .pk-pager { display: flex; align-items: center; justify-content: space-between; gap: 14px; flex-wrap: wrap; margin-top: 22px; }
    .pk-pager-note { font-size: 12.5px; color: var(--muted); font-weight: 600; }
    .pk-perpage { display: inline-flex; align-items: center; gap: 7px; font-size: 12.5px; font-weight: 700; color: var(--muted); }
    .pk-perpage select { border: 1px solid var(--line); border-radius: 9px; padding: 7px 10px; font-size: 12.5px; font-weight: 700; color: var(--ink); background: #fff; font-family: inherit; cursor: pointer; }

    /* Right rail */
    .pk-side { display: flex; flex-direction: column; gap: 18px; position: sticky; top: 84px; }
    .pk-scard { background: #fff; border: 1px solid var(--line); border-radius: 16px; padding: 16px; }
    .pk-scard h4 { font-size: 14px; margin: 0 0 12px; }
    .pk-avail-note { font-size: 11.5px; color: var(--muted); margin: -4px 0 10px; }
    .pk-avail { display: flex; align-items: center; justify-content: space-between; gap: 10px; font-size: 12.5px; color: var(--ink-2); padding: 5px 0; }
    .pk-avail + .pk-avail { border-top: 1px solid var(--line-soft); }
    .pk-avail b { color: var(--ink); font-family: var(--ff); }
    .pk-avail.more { display: none; }
    .pk-avail.more.shown { display: flex; }
    .pk-why { display: flex; gap: 10px; padding: 9px 0; }
    .pk-why svg { width: 18px; height: 18px; color: var(--pk); flex-shrink: 0; margin-top: 1px; }
    .pk-why b { display: block; font-size: 12.5px; font-weight: 800; color: var(--ink); }
    .pk-why span { font-size: 11.5px; color: var(--muted); }
    .pk-viewall { display: block; text-align: center; font-size: 12px; font-weight: 800; color: var(--pk-dark); margin-top: 12px; background: none; border: none; cursor: pointer; width: 100%; font-family: inherit; padding: 2px; }
    .pk-cmpbtn { display: block; width: 100%; text-align: center; border: 1px solid var(--pk); color: var(--pk-dark); background: #fff; border-radius: 10px; padding: 10px; font-size: 12.5px; font-weight: 800; text-decoration: none; font-family: inherit; cursor: pointer; }
    .pk-cmpbtn[aria-disabled="true"] { opacity: .5; cursor: not-allowed; }
    .pk-recent { display: flex; flex-direction: column; gap: 10px; }
    .pk-recentrow { display: flex; gap: 10px; align-items: flex-start; }
    .pk-recentrow img { width: 56px; height: 56px; object-fit: cover; border-radius: 9px; flex-shrink: 0; }
    .pk-recentrow .t { font-size: 12px; font-weight: 700; color: var(--ink); line-height: 1.3; }
    .pk-recentrow .p { font-size: 11px; color: var(--muted); margin-top: 2px; }
    .pk-recentrow .again { font-size: 11.5px; font-weight: 800; color: var(--pk-dark); margin-top: 3px; display: inline-block; }

    /* Empty */
    .pk-empty { background: #fff; border: 1px dashed var(--line); border-radius: 18px; padding: 54px 20px; text-align: center; }
    .pk-empty h3 { font-size: 18px; margin: 10px 0 6px; }
    .pk-empty p { color: var(--muted); margin: 0 0 16px; }
    .pk-empty a { display: inline-flex; background: var(--pk); color: #fff; border-radius: 11px; padding: 11px 22px; font-weight: 800; text-decoration: none; }

    /* Compare tray */
    /* Hidden means hidden: translate alone left a sliver of the bar showing
       above the fold on a page nobody had ticked anything on. */
    .pk-tray { position: fixed; left: 50%; transform: translateX(-50%) translateY(160%); bottom: 20px; z-index: 900; display: flex; align-items: center; gap: 14px; background: var(--ink); color: #fff; border-radius: 14px; padding: 12px 16px; box-shadow: 0 18px 44px rgba(15,27,53,.32); transition: transform .22s ease, opacity .22s ease; opacity: 0; visibility: hidden; }
    .pk-tray.up { transform: translateX(-50%) translateY(0); opacity: 1; visibility: visible; }
    .pk-tray b { font-size: 13px; font-weight: 800; }
    .pk-tray a { background: var(--pk); color: #fff; border-radius: 9px; padding: 8px 15px; font-size: 12.5px; font-weight: 800; }
    .pk-tray button { background: none; border: none; color: #cbd5e1; font-size: 12.5px; font-weight: 700; cursor: pointer; font-family: inherit; }

    @media (max-width: 1360px) { .pk-grid { grid-template-columns: 240px minmax(0,1fr); } .pk-side { display: none; } }
    @media (max-width: 820px) {
        .pk-grid { grid-template-columns: 1fr; }
        .pk-card { grid-template-columns: 1fr; }
        .pk-media { min-height: 190px; }
        .pk-pricebox { border-left: none; border-top: 1px solid var(--line); }
        .pk-props { grid-template-columns: repeat(2,1fr); }
        .pk-facts { grid-template-columns: 1fr; }
    }
</style>
@endpush

@section('content')
<div class="pk pk-wrap">
    <div class="pk-shell">
        {{-- Hero + value props --}}
        <div class="pk-hero">
            <div>
                <h1>Find the Perfect <span>Package</span></h1>
                <p>Search ready-made service bundles from professionals who can handle multiple parts of your event.</p>
            </div>
            <div class="pk-props">
                <div class="pk-prop"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="8" width="18" height="13" rx="2"/><path d="M12 8V5a2 2 0 0 1 2-2h1M12 8V5a2 2 0 0 0-2-2H9"/><line x1="12" y1="8" x2="12" y2="21"/></svg><div><b>One Contract</b><span>One point of contact</span></div></div>
                <div class="pk-prop"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M8 12l2.5 2.5L16 9"/></svg><div><b>Professionally</b><span>Coordinated</span></div></div>
                <div class="pk-prop"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg><div><b>Better Value</b><span>Bundle pricing</span></div></div>
                <div class="pk-prop"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="4" y1="21" x2="4" y2="14"/><line x1="4" y1="10" x2="4" y2="3"/><line x1="12" y1="21" x2="12" y2="12"/><line x1="12" y1="8" x2="12" y2="3"/><line x1="20" y1="21" x2="20" y2="16"/><line x1="20" y1="12" x2="20" y2="3"/><line x1="1" y1="14" x2="7" y2="14"/><line x1="9" y1="8" x2="15" y2="8"/><line x1="17" y1="16" x2="23" y2="16"/></svg><div><b>Customizable</b><span>To your needs</span></div></div>
            </div>
        </div>

        @if(session('status'))<div class="pk-flash ok">{{ session('status') }}</div>@endif
        @if(session('error'))<div class="pk-flash no">{{ session('error') }}</div>@endif

        {{-- Toolbar --}}
        <div class="pk-toolbar">
            <span class="pk-sel-lbl">Selected Services (AND Match)
                <span class="i" title="A package must include every service you tick.">i</span>
            </span>
            @forelse($f['selected'] as $svc)
                <span class="pk-chip">{{ $svc }} <a href="{{ $withoutService($svc) }}" aria-label="Remove {{ $svc }}">✕</a></span>
            @empty
                <span style="font-size:12.5px;color:var(--muted);">None — pick services in the matcher →</span>
            @endforelse
            <button type="button" class="pk-addsvc" onclick="document.getElementById('pkSvcSearch')?.focus()">+ Add Another Service</button>

            @auth
                @if(auth()->user()->hasRole('client') && (count($savedIds) || ! empty($f['saved'])))
                    {{-- Where the hearts lead. Without it the heart saved into a
                         drawer with no handle on the outside. --}}
                    <a class="pk-favlink {{ ! empty($f['saved']) ? 'on' : '' }}"
                       href="{{ ! empty($f['saved']) ? $link(['saved' => null]) : $link(['saved' => 1]) }}">
                        ♥ {{ ! empty($f['saved']) ? 'Showing favourites — show all' : 'My favourites (' . count($savedIds) . ')' }}
                    </a>
                @endif
            @endauth

            <div class="pk-tools-right">
                <span class="pk-count">Showing <b>{{ $total }}</b> Package{{ $total === 1 ? '' : 's' }}</span>
                <form class="pk-sortsel" method="GET" action="{{ route('public.packages') }}">
                    @foreach(\Illuminate\Support\Arr::except($carry, ['sort']) as $k => $v)
                        @if(is_array($v))
                            @foreach($v as $vv)<input type="hidden" name="{{ $k }}[]" value="{{ $vv }}">@endforeach
                        @else
                            <input type="hidden" name="{{ $k }}" value="{{ $v }}">
                        @endif
                    @endforeach
                    <label for="pk-sort">Sort by:</label>
                    <select id="pk-sort" name="sort" onchange="this.form.submit()">
                        <option value="relevant" @selected($f['sort']==='relevant')>Most Relevant</option>
                        <option value="savings" @selected($f['sort']==='savings')>Best Savings</option>
                        <option value="price_low" @selected($f['sort']==='price_low')>Price: Low to High</option>
                        <option value="price_high" @selected($f['sort']==='price_high')>Price: High to Low</option>
                        <option value="newest" @selected($f['sort']==='newest')>Newest</option>
                    </select>
                </form>
                <span class="pk-viewtog">View:
                    <a href="{{ $link(['view' => 'grid']) }}" class="{{ $f['view']==='grid' ? 'on' : '' }}">▦ Grid</a>
                    <a href="{{ $link(['view' => null]) }}" class="{{ $f['view']==='list' ? 'on' : '' }}">☰ List</a>
                </span>
            </div>
        </div>

        <div class="pk-grid">
            {{-- ── Left rail: the six filters on Peter's mockup ────────────── --}}
            <form class="pk-rail" method="GET" action="{{ route('public.packages') }}" id="pkFilters">
                @if($f['view'] !== 'list')<input type="hidden" name="view" value="{{ $f['view'] }}">@endif
                @if($f['sort'] !== 'relevant')<input type="hidden" name="sort" value="{{ $f['sort'] }}">@endif
                @if($perPage !== $perPageOptions[0])<input type="hidden" name="per_page" value="{{ $perPage }}">@endif
                @if($f['q'])<input type="hidden" name="q" value="{{ $f['q'] }}">@endif

                <div class="pk-rail-head">
                    <h3>Refine Package Search</h3>
                    @if($activeCount)<a class="pk-clear" href="{{ route('public.packages') }}">Clear All</a>@endif
                </div>

                {{-- 1 --}}
                <div class="pk-rail-sec">1. Services (AND Match)</div>
                <p class="pk-rail-hint">Packages must include all selected services</p>
                <input type="text" id="pkSvcSearch" class="pk-input pk-svcsearch" placeholder="Search services…"
                       oninput="pkFilterSvc(this.value)" aria-label="Search services">
                <div id="pkSvcList">
                    @foreach($services as $i => $svc)
                        <label class="pk-check {{ $i >= 6 && ! in_array($svc, $f['selected']) ? 'more hidden' : '' }}" data-svc="{{ Str::lower($svc) }}">
                            <input type="checkbox" name="services[]" value="{{ $svc }}" @checked(in_array($svc, $f['selected']))>
                            {{ $svc }}
                            <span class="cnt">{{ $serviceCounts[$svc] ?? 0 }}</span>
                        </label>
                    @endforeach
                </div>
                <button type="button" class="pk-showmore" id="pkShowMore" onclick="pkToggleMore()">Show More ▾</button>

                <div class="pk-divider"></div>

                {{-- 2 --}}
                <div class="pk-rail-sec">2. Event / Occasion</div>
                <select name="event_type" class="pk-input" aria-label="Occasion">
                    <option value="">All Occasions</option>
                    @foreach($occasions as $o)
                        <option value="{{ $o }}" @selected($f['event_type'] === $o)>{{ $o }}</option>
                    @endforeach
                </select>

                <div class="pk-divider"></div>

                {{-- 3. Not a mile radius, and deliberately so: R38 means a package
                       is bookable only inside its professional's own state, so
                       "within 50 miles" would describe a marketplace this one is
                       not. The scope control offers the two answers the data can
                       actually give. --}}
                <div class="pk-rail-sec">3. Location</div>
                <input type="text" name="location" class="pk-input" value="{{ $f['location'] }}"
                       placeholder="City, State or ZIP Code" aria-label="City, state or ZIP code">
                <select name="scope" class="pk-input" aria-label="How wide to search">
                    <option value="state" @selected($f['scope'] === 'state')>Anywhere in that state</option>
                    <option value="city" @selected($f['scope'] === 'city')>That city only</option>
                </select>
                <p class="pk-rail-hint" style="margin-top:8px;">Professionals take bookings inside their own state.</p>

                <div class="pk-divider"></div>

                {{-- 4 --}}
                <div class="pk-rail-sec">4. Budget Range</div>
                <div class="pk-range">
                    <div class="track"></div>
                    <div class="fill" id="pkFill"></div>
                    <input type="range" id="pkMin" name="budget_min" min="{{ $budgetFloor }}" max="{{ $budgetCeiling }}"
                           step="500" value="{{ $f['budget_min'] }}" aria-label="Minimum budget" oninput="pkBudget()">
                    <input type="range" id="pkMax" name="budget_max" min="{{ $budgetFloor }}" max="{{ $budgetCeiling }}"
                           step="500" value="{{ $f['budget_max'] }}" aria-label="Maximum budget" oninput="pkBudget()">
                </div>
                <div class="pk-range-vals">
                    <span id="pkMinLbl">${{ number_format($f['budget_min']) }}</span>
                    <span id="pkMaxLbl">${{ number_format($f['budget_max']) }}{{ $f['budget_max'] >= $budgetCeiling ? '+' : '' }}</span>
                </div>

                <div class="pk-divider"></div>

                {{-- 5. Reads the same calendar My Gigs reads, so the page can never
                       offer a day the professional's own diary has taken. --}}
                <div class="pk-rail-sec">5. Availability</div>
                <label class="pk-check" style="padding-bottom:2px;">
                    <input type="checkbox" id="pkDateOn" @checked($f['date'] !== '')
                           onchange="document.getElementById('pkDate').disabled = !this.checked; if(this.checked){document.getElementById('pkDate').focus();}else{document.getElementById('pkDate').value='';}">
                    Available on my date
                </label>
                <input type="date" id="pkDate" name="date" class="pk-input" value="{{ $f['date'] }}"
                       min="{{ now()->toDateString() }}" aria-label="Event date" @disabled($f['date'] === '')>
                <p class="pk-rail-hint" style="margin-top:8px;">Hides professionals already committed on GigResource that day.</p>

                <div class="pk-divider"></div>

                {{-- 6 --}}
                <div class="pk-rail-sec">6. Guest Count</div>
                <select name="guests" class="pk-input" aria-label="Guest count">
                    <option value="">Any guest count</option>
                    @foreach($guestBuckets as $value => $label)
                        <option value="{{ $value }}" @selected($f['guests'] === $value)>{{ $label }}</option>
                    @endforeach
                </select>

                <button type="submit" class="pk-apply">Apply Filters</button>

                @auth
                    <button type="submit" class="pk-savebtn" form="pkSaveSearch">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 21l-7-5-7 5V5a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2z"/></svg>
                        Save Search
                    </button>
                @endauth
            </form>

            {{-- The save posts the same values the filter form holds. Outside the
                 GET form because a form cannot nest; the button above targets it
                 by id and the script copies the current values across. --}}
            @auth
                <form id="pkSaveSearch" method="POST" action="{{ route('public.packages.save-search') }}" hidden>
                    @csrf
                </form>
            @endauth

            {{-- ── Centre: the cards ──────────────────────────────────────── --}}
            <div>
                @if($packages->count())
                    <div class="pk-cards {{ $f['view']==='grid' ? 'grid' : '' }}">
                        @foreach($packages as $pkg)
                            @php
                                $pro = $pkg->user;
                                $company = $pro?->profile?->company_name ?: $pro?->name;
                                $gallery = $pkg->heroUrls(4);
                                // A package with no uploads gets one stand-in picture, not four.
                                // Padding it out to four stock shots gave every card arrows and
                                // dots — a gallery the professional never uploaded.
                                if (empty($gallery)) { $gallery = [$pkg->fallbackHeroUrl(520)]; }
                                $rating   = $pro?->reviews_avg ? number_format($pro->reviews_avg, 1) : null;
                                $bookings = $card['bookings'][$pro?->id] ?? null;
                                $responds = ResponseStats::brief($card['responds'][$pro?->id] ?? null);
                                $svcTags  = $pkg->services ?: ($pkg->category ? [$pkg->category->name] : []);
                                $area     = trim(($pro?->profile?->city ? $pro->profile->city . ', ' : '')
                                            . ($pro?->profile?->state ?: $pkg->state ?: ''), ', ');
                                $isSaved  = in_array($pkg->id, $savedIds, true);
                            @endphp
                            <article class="pk-card">
                                <div class="pk-media">
                                    @foreach($gallery as $gi => $src)
                                        <img class="pk-slide {{ $gi === 0 ? 'active' : '' }}" src="{{ $src }}" alt="{{ $pkg->title }}" loading="lazy">
                                    @endforeach

                                    <label class="pk-cmp" title="Add to comparison">
                                        <input type="checkbox" class="pk-cmp-box" value="{{ $pkg->id }}"
                                               onchange="pkCompare(this)" aria-label="Compare {{ $pkg->title }}">
                                        Compare
                                    </label>

                                    {{-- The heart is wired now (saved_packages). It used to only
                                         toggle a CSS class, which promised a shortlist that did
                                         not exist. --}}
                                    @auth
                                        @if(auth()->user()->hasRole('client'))
                                            <form method="POST" action="{{ route('public.package.save', $pkg) }}">
                                                @csrf
                                                <button class="pk-heart {{ $isSaved ? 'on' : '' }}" type="submit"
                                                        aria-label="{{ $isSaved ? 'Remove from favourites' : 'Save to favourites' }}">
                                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 1 0-7.78 7.78L12 21.23l8.84-8.84a5.5 5.5 0 0 0 0-7.78z"/></svg>
                                                </button>
                                            </form>
                                        @endif
                                    @endauth

                                    @if($pkg->photosCount())<span class="pk-photos">📷 {{ $pkg->photosCount() }} Photos</span>@endif
                                    @if(count($gallery) > 1)
                                        <button class="pk-nav prev" type="button" onclick="pkSlide(this,-1)" aria-label="Previous photo">‹</button>
                                        <button class="pk-nav next" type="button" onclick="pkSlide(this,1)" aria-label="Next photo">›</button>
                                        <div class="pk-dots">@foreach($gallery as $gi => $s)<span class="pk-dot {{ $gi === 0 ? 'active' : '' }}"></span>@endforeach</div>
                                    @endif
                                </div>

                                <div class="pk-main">
                                    <h3 class="pk-title"><a class="pk-title-link" href="{{ route('public.package', $pkg->slug) }}">{{ $pkg->title }}</a></h3>
                                    <div class="pk-pro">by {{ $company ?? 'Verified Professional' }} <span class="pk-verif">✔</span></div>

                                    {{-- Three facts about the professional, each shown only when
                                         it exists. A new pro is not "0 bookings" — the card just
                                         says less about them. --}}
                                    <div class="pk-meta">
                                        @if($rating)
                                            <span><span class="star">★ {{ $rating }}</span> ({{ $pro->reviews_count }} review{{ $pro->reviews_count === 1 ? '' : 's' }})</span>
                                        @else
                                            <span>New on GigResource</span>
                                        @endif
                                        @if($bookings)<span class="sep">•</span><span>{{ $bookings }} booking{{ $bookings === 1 ? '' : 's' }}</span>@endif
                                        @if($responds)<span class="sep">•</span><span>{{ $responds }}</span>@endif
                                    </div>

                                    <div class="pk-tags">
                                        @foreach(array_slice($svcTags, 0, 4) as $t)<span class="pk-tag">{{ $t }}</span>@endforeach
                                        @if(count($svcTags) > 4)<span class="pk-tag">+{{ count($svcTags) - 4 }}</span>@endif
                                    </div>

                                    <div class="pk-facts">
                                        <div class="pk-fact">
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="9"/><polyline points="12 7 12 12 15 14"/></svg>
                                            <div><span>Coverage</span><b>{{ $pkg->coverage ?: $pkg->duration ?: 'Ask the pro' }}</b></div>
                                        </div>
                                        <div class="pk-fact">
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/></svg>
                                            <div><span>Guests</span><b>{{ $pkg->guests ?: 'Ask the pro' }}</b></div>
                                        </div>
                                        <div class="pk-fact">
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>
                                            <div><span>Service Area</span><b>{{ $area ?: 'Not set' }}</b></div>
                                        </div>
                                    </div>

                                    <div class="pk-cardfoot">
                                        @if($f['date'] !== '')
                                            <span class="free">✓ Available on {{ \Illuminate\Support\Carbon::parse($f['date'])->format('M j, Y') }}</span>
                                        @endif
                                        @if($pkg->availability)<span>🗓 {{ $pkg->availability }}</span>@endif
                                        {{-- "Serves MD" used to sit here. It says what the
                                             SERVICE AREA cell two lines above already says,
                                             and R38 means it can never say anything else —
                                             a package is offered in its professional's own
                                             state. One line, not two. --}}
                                    </div>
                                </div>

                                <div class="pk-pricebox">
                                    <span class="lbl">Starting at</span>
                                    <span class="amt">${{ number_format($pkg->price) }}</span>
                                    <span class="tp">Total Package</span>
                                    {{-- One button. "Customize Package" pointed at the very same
                                         URL, so it read as a second, different action and wasn't. --}}
                                    <a class="pk-btn pk-btn-primary" href="{{ route('public.package', $pkg->slug) }}">View Package</a>
                                    @if($pkg->savings_pct)<div class="pk-save">Save up to {{ $pkg->savings_pct }}%<br>vs. booking separately</div>@endif
                                </div>
                            </article>
                        @endforeach
                    </div>

                    <div class="pk-pager">
                        <span class="pk-pager-note">
                            Showing {{ $packages->firstItem() }}–{{ $packages->lastItem() }} of {{ $total }} package{{ $total === 1 ? '' : 's' }}
                        </span>
                        <div>{{ $packages->onEachSide(1)->links() }}</div>
                        <form class="pk-perpage" method="GET" action="{{ route('public.packages') }}">
                            @foreach(\Illuminate\Support\Arr::except($carry, ['per_page']) as $k => $v)
                                @if(is_array($v))
                                    @foreach($v as $vv)<input type="hidden" name="{{ $k }}[]" value="{{ $vv }}">@endforeach
                                @else
                                    <input type="hidden" name="{{ $k }}" value="{{ $v }}">
                                @endif
                            @endforeach
                            <label for="pk-per">Per page:</label>
                            <select id="pk-per" name="per_page" onchange="this.form.submit()">
                                @foreach($perPageOptions as $n)
                                    <option value="{{ $n }}" @selected($perPage === $n)>{{ $n }} per page</option>
                                @endforeach
                            </select>
                        </form>
                    </div>
                @else
                    <div class="pk-empty">
                        <div style="font-size:40px;">🎁</div>
                        <h3>No packages match these filters</h3>
                        <p>Try removing a service — a package has to include every one you tick — or widen the budget.</p>
                        <a href="{{ route('public.packages') }}">Clear filters</a>
                    </div>
                @endif
            </div>

            {{-- ── Right rail ─────────────────────────────────────────────── --}}
            <aside class="pk-side">
                <div class="pk-scard">
                    <h4>Where Packages Are Available</h4>
                    {{-- There was a decorative blue blob above this list posing as a
                         map. It plotted nothing — no coordinates, no map library — so
                         it read as coverage data while carrying none. The counts below
                         are the real answer to the same question, and they add up to
                         the package count printed at the top of the page. --}}
                    <p class="pk-avail-note">Cities these packages are offered from.</p>
                    @foreach($availability as $city => $count)
                        {{-- A package whose professional has not set a city. Named
                             rather than dropped, so the numbers still add up. --}}
                        <div class="pk-avail {{ $loop->index >= 5 ? 'more' : '' }}">
                            <span>{{ $city === 'Other' ? 'City not listed' : $city }}</span>
                            <b>{{ $count }}</b>
                        </div>
                    @endforeach
                    @if($availability->count() > 5)
                        <button type="button" class="pk-viewall" id="pkLocMore" onclick="pkMoreLocations()">
                            View all {{ $availability->count() }} locations
                        </button>
                    @endif
                </div>

                <div class="pk-scard">
                    <h4>Why Package Bundles?</h4>
                    <div class="pk-why"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg><div><b>Better Value</b><span>Save more with bundle pricing</span></div></div>
                    <div class="pk-why"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/></svg><div><b>Seamless Experience</b><span>Professionals coordinate for you</span></div></div>
                    <div class="pk-why"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg><div><b>One Contract</b><span>One payment, one point of contact</span></div></div>
                    <div class="pk-why"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="4" y1="21" x2="4" y2="14"/><line x1="4" y1="10" x2="4" y2="3"/><line x1="12" y1="21" x2="12" y2="12"/><line x1="12" y1="8" x2="12" y2="3"/><line x1="20" y1="21" x2="20" y2="16"/><line x1="20" y1="12" x2="20" y2="3"/><line x1="1" y1="14" x2="7" y2="14"/><line x1="9" y1="8" x2="15" y2="8"/><line x1="17" y1="16" x2="23" y2="16"/></svg><div><b>Customizable</b><span>Adjust services to fit your needs</span></div></div>
                </div>

                <div class="pk-scard">
                    <h4>Compare Packages</h4>
                    <p class="pk-avail-note">Compare up to {{ $compareMax }} packages side by side.</p>
                    <a class="pk-cmpbtn" id="pkCmpBtn" href="{{ route('public.packages.compare') }}" aria-disabled="true">
                        Compare Packages (<span id="pkCmpN">0</span>/{{ $compareMax }})
                    </a>
                </div>

                @if($recent->count())
                    <div class="pk-scard">
                        <h4>Recently Viewed</h4>
                        <div class="pk-recent">
                            @foreach($recent->take(3) as $r)
                                @php $rhero = $r->heroUrls(1)[0] ?? $r->fallbackHeroUrl(160); @endphp
                                <div class="pk-recentrow">
                                    <img src="{{ $rhero }}" alt="{{ $r->title }}" loading="lazy">
                                    <div>
                                        <div class="t">{{ \Illuminate\Support\Str::limit($r->title, 44) }}</div>
                                        <div class="p">{{ $r->user?->profile?->company_name ?: $r->user?->name }}</div>
                                        <a class="again" href="{{ route('public.package', $r->slug) }}">View Again →</a>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif

                @auth
                    @if($savedSearches->count())
                        <div class="pk-scard">
                            <h4>Your Saved Searches</h4>
                            <div class="pk-saved-list">
                                @foreach($savedSearches as $s)
                                    <div class="pk-saved-row">
                                        <a href="{{ route('public.packages', $s->queryParams()) }}">{{ $s->label }}</a>
                                        <form method="POST" action="{{ route('public.packages.delete-search', $s) }}">
                                            @csrf @method('DELETE')
                                            <button type="submit" aria-label="Remove saved search">✕</button>
                                        </form>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif
                @endauth
            </aside>
        </div>
    </div>
</div>

{{-- Compare tray — appears once something is ticked. --}}
<div class="pk-tray" id="pkTray">
    <b><span id="pkTrayN">0</span> selected</b>
    <a href="{{ route('public.packages.compare') }}" id="pkTrayGo">Compare →</a>
    <button type="button" onclick="pkClearCompare()">Clear</button>
</div>

<script>
    var PK_COMPARE_MAX = @json($compareMax);
    var PK_COMPARE_URL = @json(route('public.packages.compare'));

    function pkFilterSvc(v) {
        v = (v || '').toLowerCase();
        document.querySelectorAll('#pkSvcList .pk-check').forEach(function (el) {
            el.style.display = el.getAttribute('data-svc').indexOf(v) !== -1 ? 'flex' : 'none';
        });
    }
    function pkToggleMore() {
        var btn = document.getElementById('pkShowMore');
        var expand = btn.textContent.indexOf('More') !== -1;
        document.querySelectorAll('#pkSvcList .pk-check.more').forEach(function (el) {
            el.classList.toggle('hidden', !expand);
        });
        btn.textContent = expand ? 'Show Less ▴' : 'Show More ▾';
    }
    function pkMoreLocations() {
        document.querySelectorAll('.pk-avail.more').forEach(function (el) { el.classList.add('shown'); });
        var b = document.getElementById('pkLocMore');
        if (b) b.remove();
    }

    // Budget: two thumbs on one track. Whichever handle is dragged past the
    // other is pushed back, so min can never exceed max.
    function pkBudget() {
        var lo = document.getElementById('pkMin'), hi = document.getElementById('pkMax');
        if (!lo || !hi) return;
        var min = +lo.min, max = +lo.max;
        if (+lo.value > +hi.value) { if (document.activeElement === lo) lo.value = hi.value; else hi.value = lo.value; }
        var a = (+lo.value - min) / (max - min) * 100, b = (+hi.value - min) / (max - min) * 100;
        var fill = document.getElementById('pkFill');
        fill.style.left = a + '%'; fill.style.width = (b - a) + '%';
        document.getElementById('pkMinLbl').textContent = '$' + (+lo.value).toLocaleString();
        document.getElementById('pkMaxLbl').textContent = '$' + (+hi.value).toLocaleString() + (+hi.value >= max ? '+' : '');
    }
    pkBudget();

    // "Save Search" posts the rail's current values. Copied at click time so
    // what gets saved is what the client is looking at, not what the page
    // loaded with.
    var saveForm = document.getElementById('pkSaveSearch');
    if (saveForm) {
        saveForm.addEventListener('submit', function () {
            saveForm.querySelectorAll('[data-copied]').forEach(function (el) { el.remove(); });
            new FormData(document.getElementById('pkFilters')).forEach(function (value, key) {
                var input = document.createElement('input');
                input.type = 'hidden'; input.name = key; input.value = value;
                input.setAttribute('data-copied', '1');
                saveForm.appendChild(input);
            });
        });
    }

    // Compare tray. The ticks live in this tab only (sessionStorage) — a
    // comparison is a thing you are doing right now, not a saved list.
    function pkCompareIds() {
        try { return JSON.parse(sessionStorage.getItem('pkCompare') || '[]'); } catch (e) { return []; }
    }
    function pkCompareWrite(ids) {
        sessionStorage.setItem('pkCompare', JSON.stringify(ids));
        pkCompareRender();
    }
    function pkCompare(box) {
        var ids = pkCompareIds(), id = +box.value;
        if (box.checked) {
            if (ids.indexOf(id) === -1) {
                if (ids.length >= PK_COMPARE_MAX) {
                    box.checked = false;
                    alert('You can compare up to ' + PK_COMPARE_MAX + ' packages at a time.');
                    return;
                }
                ids.push(id);
            }
        } else {
            ids = ids.filter(function (i) { return i !== id; });
        }
        pkCompareWrite(ids);
    }
    function pkClearCompare() {
        pkCompareWrite([]);
        document.querySelectorAll('.pk-cmp-box').forEach(function (b) { b.checked = false; });
    }
    function pkCompareRender() {
        var ids = pkCompareIds();
        var href = ids.length ? PK_COMPARE_URL + '?ids=' + ids.join(',') : PK_COMPARE_URL;
        var btn = document.getElementById('pkCmpBtn');
        if (btn) {
            document.getElementById('pkCmpN').textContent = ids.length;
            btn.href = href;
            btn.setAttribute('aria-disabled', ids.length < 2 ? 'true' : 'false');
        }
        var tray = document.getElementById('pkTray');
        if (tray) {
            document.getElementById('pkTrayN').textContent = ids.length;
            document.getElementById('pkTrayGo').href = href;
            tray.classList.toggle('up', ids.length > 0);
        }
        document.querySelectorAll('.pk-cmp-box').forEach(function (b) {
            b.checked = ids.indexOf(+b.value) !== -1;
        });
    }
    pkCompareRender();

    // Package card image carousel — arrows, dots, hover auto-advance.
    function pkSlide(btn, dir) {
        var media = btn.closest('.pk-media');
        var slides = media.querySelectorAll('.pk-slide');
        var dots = media.querySelectorAll('.pk-dot');
        if (slides.length < 2) return;
        var cur = 0;
        slides.forEach(function (s, i) { if (s.classList.contains('active')) cur = i; });
        var next = (cur + dir + slides.length) % slides.length;
        slides[cur].classList.remove('active'); slides[next].classList.add('active');
        if (dots[cur]) dots[cur].classList.remove('active');
        if (dots[next]) dots[next].classList.add('active');
    }
    document.querySelectorAll('.pk-media').forEach(function (m) {
        if (m.querySelectorAll('.pk-slide').length < 2) return;
        var timer = null, nextBtn = m.querySelector('.pk-nav.next');
        m.addEventListener('mouseenter', function () {
            timer = setInterval(function () { if (nextBtn) pkSlide(nextBtn, 1); }, 1400);
        });
        m.addEventListener('mouseleave', function () {
            if (timer) { clearInterval(timer); timer = null; }
            m.querySelectorAll('.pk-slide').forEach(function (s, i) { s.classList.toggle('active', i === 0); });
            m.querySelectorAll('.pk-dot').forEach(function (d, i) { d.classList.toggle('active', i === 0); });
        });
    });
</script>
@endsection
