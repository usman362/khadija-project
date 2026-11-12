@extends('layouts.professional')

@php $p = $package ?? null; $editing = (bool) $p; @endphp

@section('title', $editing ? 'Edit Package' : 'Create a Package')
@section('page-title', $editing ? 'Edit Package' : 'Create a Package')
@section('page-subtitle', 'Bundle your services into a package to attract more clients. Use your existing services, set pricing, and customize the details.')

{{-- Professional — Create a Package for Package Search. A pro bundles 2+ of
     their own services (solo — one professional) into a fixed offering that
     appears in the client Package Service Search. NOT an MSR. --}}

@php
    use App\Support\PackageProgress;

    $pServices   = old('services', $p?->services ?? []);
    $pEventTypes = old('event_types', $p?->event_types ?? []);
    $pIncludes   = old('includes', $p?->includes ?? []);
    /* Always three rows. A professional offering two levels leaves the last
       one empty and the controller drops it; an empty repeater with an "add"
       button hides the feature behind a click. */
    $pTiers = old('tiers', $p?->tierList() ?? []);
    $pTiers = array_pad(array_values((array) $pTiers), 3, ['name' => '', 'price' => '', 'blurb' => '']);
    $gMin = old('guest_min'); $gMax = old('guest_max');

    /*
     * The six steps of the flow, in Khadijah's 28 October order: services are
     * chosen first, because the package is the bundle and everything after it
     * describes that bundle. Four of them are things PackageProgress measures,
     * so the readiness ring here and the progress bar on My Packages are one
     * calculation and cannot disagree.
     *
     * Options & Add-ons is optional by definition and Review & Publish is the
     * click at the end, so neither is a ring step.
     *
     * No "Est. time" labels: the mockup carries them, but nobody has measured
     * how long any of these take and a made-up "15–20 min" is a promise about
     * the professional's evening.
     */
    $ringSteps = $p ? PackageProgress::steps($p) : PackageProgress::steps(new \App\Models\Package());
    $ringPct   = $p ? PackageProgress::percent($p) : 0;
    $published = ($p?->status ?? null) === 'active';

    $stepBlurb = [
        1 => 'Choose 2 or more of your existing services.',
        2 => 'Tell clients what your package is about.',
        3 => 'Set the package price against what the parts cost.',
        4 => 'Anything optional that comes with it.',
        5 => 'Set when you are available and where you serve.',
        6 => 'Review every detail, preview it, and publish.',
    ];
@endphp

@push('styles')
<style>
    .pc { --pc: #2563eb; --pc-dark: #1d4ed8; --pc-soft: #eff6ff; }
    .pc-grid { display: grid; grid-template-columns: minmax(0,1fr) 320px; gap: 22px; align-items: start; }

    /* Her stepper: the number sits above its label, and the line runs
       between the circles rather than between the whole blocks. */
    .pc-tiers { margin-top: 18px; padding-top: 16px; border-top: 1px solid var(--border-color); }
    .pc-tiers-h b { display: block; font-size: 13px; font-weight: 800; color: var(--text-primary); }
    .pc-tiers-h span { display: block; font-size: 11.5px; color: var(--text-muted); margin: 2px 0 12px; }
    .pc-tier-row { display: grid; grid-template-columns: minmax(0,1fr) minmax(0,110px) minmax(0,1.4fr);
        gap: 10px; margin-bottom: 9px; }
    @media (max-width: 760px) { .pc-tier-row { grid-template-columns: minmax(0, 1fr); } }

    .pc-stepper { display: flex; align-items: flex-start; background: var(--bg-card);
        border: 1px solid var(--border-color); border-radius: 14px; padding: 18px 20px 16px; margin-bottom: 20px; }
    .pc-own { margin-top: 10px; padding: 10px 12px; border: 1px solid var(--border-color); border-radius: 10px; background: var(--bg-card-hover, transparent); }
    .pc-own b { display: block; font-size: 11px; font-weight: 800; text-transform: uppercase; letter-spacing: .3px; color: var(--text-muted); margin-bottom: 6px; }
    .pc-own-row, .pc-own-sum { display: flex; justify-content: space-between; gap: 10px; font-size: 12.5px; color: var(--text-secondary); padding: 3px 0; }
    .pc-own-row i, .pc-own-sum i { font-style: normal; font-weight: 700; color: var(--text-primary); }
    .pc-own-sum { border-top: 1px solid var(--border-color); margin-top: 5px; padding-top: 6px; font-weight: 700; color: var(--text-primary); }
    .pc-step { display: flex; flex-direction: column; align-items: center; gap: 9px;
        flex: 1 1 auto; min-width: 0; cursor: pointer; text-align: center; padding: 0 2px; }
    .pc-step .num { width: 34px; height: 34px; border-radius: 50%; background: var(--bg-card);
        border: 2px solid var(--border-color); color: var(--text-muted); font-weight: 800; font-size: 14px;
        display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
    .pc-step.on .num, .pc-step.done .num { background: var(--pc); border-color: var(--pc); color: #fff; }
    .pc-step .lbl b { display: block; font-size: 12.5px; font-weight: 800; color: var(--text-muted); line-height: 1.25; white-space: nowrap; }
    .pc-step.on .lbl b, .pc-step.done .lbl b { color: var(--text-primary); }
    .pc-step .lbl span { display: block; font-size: 10.5px; color: var(--text-muted); line-height: 1.4; margin-top: 2px; }
    /* The connector lines up with the middle of the circles above it. */
    .pc-stepper .bar { flex: 0 1 60px; min-width: 12px; height: 2px; background: var(--border-color); margin: 17px -2px 0; }
    .pc-stepper .bar.done { background: var(--pc); }

    .pc-panel { display: none; }
    .pc-panel.on { display: block; }
    .pc-card { background: var(--bg-card); border: 1px solid var(--border-color); border-radius: 16px; padding: 22px; margin-bottom: 18px; }
    .pc-card h3 { font-size: 16px; font-weight: 800; color: var(--text-primary); margin: 0 0 18px; }
    .pc-field { margin-bottom: 18px; }
    .pc-field label { display: block; font-size: 13px; font-weight: 700; color: var(--text-primary); margin-bottom: 7px; }
    .pc-field label .req { color: var(--bad-text); }
    .pc-field .hint { font-size: 11.5px; color: var(--text-muted); margin-top: 5px; }
    .pc-input, .pc-textarea, .pc-select { width: 100%; border: 1px solid var(--border-color); border-radius: 10px; padding: 11px 13px; font-size: 14px; font-family: inherit; color: var(--text-primary); background: var(--bg-body, var(--bg-card)); }
    .pc-textarea { min-height: 84px; resize: vertical; }
    .pc-two { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }
    .pc-counter { text-align: right; font-size: 11px; color: var(--text-muted); margin-top: 4px; }

    .pc-typegrid { display: grid; grid-template-columns: 1fr 1fr; gap: 14px; }
    .pc-typeopt { border: 2px solid var(--border-color); border-radius: 12px; padding: 15px; cursor: pointer; display: flex; gap: 12px; align-items: flex-start; }
    .pc-typeopt.on { border-color: var(--pc); background: var(--pc-soft); }
    .pc-typeopt svg { width: 26px; height: 26px; color: var(--pc); flex-shrink: 0; }
    .pc-typeopt b { display: block; font-size: 14px; font-weight: 800; color: var(--text-primary); }
    .pc-typeopt span { font-size: 12px; color: var(--text-muted); }
    .pc-typeopt input { display: none; }

    .pc-chips { display: flex; flex-wrap: wrap; gap: 9px; }
    .pc-chipbox { position: relative; }
    .pc-chipbox input { position: absolute; opacity: 0; }
    .pc-chipbox span { display: inline-flex; align-items: center; gap: 6px; border: 1.5px solid var(--border-color); border-radius: 999px; padding: 8px 15px; font-size: 13px; font-weight: 700; color: var(--text-secondary); cursor: pointer; }
    .pc-chipbox input:checked + span { border-color: var(--pc); background: var(--pc-soft); color: var(--pc-dark); }

    .pc-svcrow { display: flex; align-items: center; gap: 12px; padding: 12px 0; border-top: 1px solid var(--border-color); }
    .pc-svcrow:first-child { border-top: none; }
    .pc-svcrow .ico { width: 38px; height: 38px; border-radius: 9px; background: var(--pc-soft); color: var(--pc); display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
    .pc-svcrow .ico svg { width: 18px; height: 18px; }
    .pc-svcrow .meta { flex: 1; min-width: 0; }
    .pc-svcrow .meta b { display: block; font-size: 13.5px; font-weight: 700; color: var(--text-primary); }
    .pc-svcrow .meta span { font-size: 11.5px; color: var(--text-muted); }
    .pc-svcrow.off { opacity: .5; }
    .pc-toggle { position: relative; width: 40px; height: 22px; border-radius: 999px; background: var(--border-color); cursor: pointer; flex-shrink: 0; transition: background .15s; }
    .pc-toggle::after { content: ""; position: absolute; top: 2px; left: 2px; width: 18px; height: 18px; border-radius: 50%; background: #fff; transition: transform .15s; }
    .pc-toggle.on { background: var(--pc); }
    .pc-toggle.on::after { transform: translateX(18px); }
    .pc-svccount { font-size: 12.5px; color: var(--text-muted); margin-top: 14px; }
    .pc-svccount b { color: var(--text-primary); }
    .pc-svccount.bad b { color: var(--bad-text); }

    /* Step 1 — the professional's own services as cards, per Khadijah's screen. */
    .pc-svctools { display: grid; grid-template-columns: minmax(0,2fr) 1fr 1fr; gap: 10px; margin-bottom: 16px; }
    .pc-svcgrid { display: grid; grid-template-columns: repeat(auto-fill, minmax(196px,1fr)); gap: 14px; }
    /* display on the element itself beats the hidden attribute, which is how
       a filtered-out service stayed on the grid. Third time on this project. */
    .pc-svccard:not([hidden]) { display: block; }
    .pc-svccard { position: relative; border: 2px solid var(--border-color); border-radius: 13px;
        cursor: pointer; overflow: hidden; background: var(--bg-card); }
    .pc-svccard.on { border-color: var(--pc); }
    .pc-svccard input { position: absolute; opacity: 0; pointer-events: none; }
    .pc-svccard .media { position: relative; display: flex; align-items: center; justify-content: center;
        height: 108px; background: linear-gradient(135deg, color-mix(in srgb, var(--t) 85%, #000),
        color-mix(in srgb, var(--t) 45%, #000)); overflow: hidden; }
    .pc-svccard .media img { width: 100%; height: 100%; object-fit: cover; }
    .pc-svccard .media .initial { font-size: 30px; font-weight: 800; color: rgba(255,255,255,.85); }
    .pc-svccard .tick { position: absolute; top: 9px; left: 9px; width: 22px; height: 22px; border-radius: 50%;
        border: 2px solid rgba(255,255,255,.85); background: rgba(0,0,0,.2); color: #fff;
        display: flex; align-items: center; justify-content: center; }
    .pc-svccard .tick svg { width: 12px; height: 12px; opacity: 0; }
    .pc-svccard.on .tick { background: var(--pc); border-color: var(--pc); }
    .pc-svccard.on .tick svg { opacity: 1; }
    .pc-svccard .live { position: absolute; top: 9px; right: 9px; font-size: 10.5px; font-weight: 700;
        padding: 3px 9px; border-radius: 20px; background: rgba(209,250,229,.95); color: #047857; }
    .pc-svccard .live.off { background: rgba(241,245,249,.95); color: #475569; }
    .pc-svccard .body { display: block; padding: 11px 13px 13px; }
    .pc-svccard b { display: block; font-size: 13.5px; font-weight: 800; color: var(--text-primary); line-height: 1.25; }
    .pc-svccard .cat { display: block; font-size: 11.5px; color: var(--text-muted); margin-top: 3px; }
    .pc-svccard .price { display: block; font-size: 17px; font-weight: 800; color: var(--text-primary); margin-top: 9px; }
    .pc-svccard .price i { display: block; font-style: normal; font-size: 11px; font-weight: 600; color: var(--text-muted); }
    .pc-svcnone { font-size: 13px; color: var(--text-muted); padding: 16px 0; }

    .pc-picked-head { display: flex; align-items: center; justify-content: space-between; gap: 10px; }
    .pc-clearall { background: none; border: 0; padding: 0; cursor: pointer; font-family: inherit;
        font-size: 12px; font-weight: 700; color: var(--pc); }
    .pc-picked { list-style: none; padding: 0; margin: 0; display: flex; flex-direction: column; gap: 9px; }
    .pc-picked li { display: flex; align-items: center; gap: 9px; padding: 9px 10px;
        border: 1px solid var(--border-color); border-radius: 11px; background: var(--bg-card); }
    .pc-picked li.drag { opacity: .45; }
    .pc-picked .grip { color: var(--text-muted); cursor: grab; flex: none; line-height: 1;
        font-size: 13px; letter-spacing: -2px; }
    .pc-picked .thumb { width: 44px; height: 34px; border-radius: 7px; flex: none; overflow: hidden;
        display: flex; align-items: center; justify-content: center; color: rgba(255,255,255,.9);
        font-size: 13px; font-weight: 800; }
    .pc-picked .thumb img { width: 100%; height: 100%; object-fit: cover; }
    .pc-picked .m { min-width: 0; flex: 1 1 auto; }
    .pc-picked .m b { display: block; font-size: 12.5px; font-weight: 800; color: var(--text-primary);
        line-height: 1.3; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    .pc-picked .m .c { display: block; font-size: 11px; color: var(--text-muted); }
    .pc-picked .m .p { display: block; font-size: 11.5px; font-weight: 700; color: var(--text-primary); margin-top: 1px; }
    .pc-picked .m .p.none { font-weight: 600; color: var(--text-muted); }
    .pc-picked .x { flex: none; width: 22px; height: 22px; border-radius: 50%; border: 0; cursor: pointer;
        background: none; color: var(--text-muted); font-size: 13px; line-height: 1; font-family: inherit; }
    .pc-picked .x:hover { background: var(--bg-card-hover); color: var(--bad-text); }
    .pc-picked-empty { font-size: 12.5px; color: var(--text-muted); }
    .pc-picked-tip:not([hidden]) { display: flex; gap: 8px; margin-top: 12px; padding: 10px 11px;
        border-radius: 10px; background: color-mix(in srgb, var(--pc) 9%, transparent); }
    .pc-picked-tip svg { width: 14px; height: 14px; flex: none; color: var(--pc); margin-top: 1px; }
    .pc-picked-tip span { font-size: 11px; color: var(--text-secondary, var(--text-muted)); line-height: 1.5; }
    .pc-picked-tip b { display: block; font-weight: 800; color: var(--text-primary); }
    /* display on the element itself would beat the hidden attribute, which is
       how this row showed a $0 total with nothing selected. */
    .pc-picked-sum:not([hidden]) { display: flex; align-items: center; justify-content: space-between; gap: 10px;
        margin-top: 14px; padding-top: 13px; border-top: 2px solid var(--border-color); }
    .pc-picked-sum span { font-size: 12.5px; font-weight: 800; color: var(--text-primary); }
    .pc-picked-sum small { display: block; font-size: 10.5px; font-weight: 600; color: var(--text-muted);
        line-height: 1.45; margin-top: 2px; }
    .pc-picked-sum b { font-size: 21px; font-weight: 900; color: var(--text-primary); white-space: nowrap; }

    .pc-rep-item { display: flex; gap: 8px; margin-bottom: 8px; }
    .pc-rep-item input { flex: 1; }
    .pc-rep-del { border: 1px solid var(--border-color); background: var(--bg-card); border-radius: 9px; padding: 0 12px; color: var(--text-muted); cursor: pointer; }
    .pc-addbtn { border: 1px dashed var(--border-color); background: var(--bg-card); border-radius: 9px; padding: 9px 14px; font-size: 13px; font-weight: 700; color: var(--pc); cursor: pointer; }

    .pc-actions { display: flex; align-items: center; justify-content: space-between; gap: 12px; margin-top: 6px;
        background: var(--bg-card); border: 1px solid var(--border-color); border-radius: 14px; padding: 14px 16px; }
    .pc-actions-r { display: inline-flex; align-items: center; gap: 10px; }
    .pc-btn { border-radius: 11px; padding: 12px 22px; font-size: 14px; font-weight: 800; cursor: pointer; border: none; text-decoration: none; display: inline-flex; align-items: center; gap: 8px; }
    .pc-btn-primary { background: var(--pc); color: #fff; }
    .pc-btn-primary:hover { background: var(--pc-dark); }
    .pc-btn-ghost { background: var(--bg-card); color: var(--text-secondary); border: 1px solid var(--border-color); }
    .pc-head { display: flex; align-items: center; justify-content: flex-end; gap: 10px; margin-bottom: 16px; }

    .pc-side { display: flex; flex-direction: column; gap: 16px; position: sticky; top: 84px; }
    /* Her step 1 rail holds the picked services and nothing else. */
    .pc-afterone[hidden] { display: none; }
    .pc-scard { background: var(--bg-card); border: 1px solid var(--border-color); border-radius: 16px; padding: 16px; }
    .pc-scard h4 { font-size: 13.5px; font-weight: 800; color: var(--text-primary); margin: 0 0 12px; display: flex; align-items: center; gap: 7px; }
    .pc-prev-media { aspect-ratio: 16/10; border-radius: 12px; background: linear-gradient(135deg,#e2e8f0,#eff6ff); overflow: hidden; margin-bottom: 12px; display: flex; align-items: center; justify-content: center; color: var(--text-muted); font-size: 12px; }
    .pc-prev-media img { width: 100%; height: 100%; object-fit: cover; }
    .pc-prev-title { font-size: 15px; font-weight: 800; color: var(--text-primary); line-height: 1.25; }
    .pc-prev-type { display: inline-block; font-size: 11px; font-weight: 800; color: var(--pc-dark); background: var(--pc-soft); padding: 3px 9px; border-radius: 6px; margin: 8px 0; }
    .pc-prev-svc { font-size: 12px; color: var(--text-muted); }
    .pc-prev-price { font-size: 20px; font-weight: 900; color: var(--text-primary); margin-top: 10px; }
    .pc-prev-price small { display: block; font-size: 11px; color: var(--text-muted); font-weight: 600; }
    .pc-list { list-style: none; padding: 0; margin: 0; }
    .pc-list li { display: flex; gap: 8px; font-size: 12.5px; color: var(--text-secondary); padding: 5px 0; }
    .pc-list svg { width: 15px; height: 15px; color: var(--ok-text); flex-shrink: 0; margin-top: 1px; }
    .pc-tips li { position: relative; padding-left: 15px; margin-bottom: 7px; font-size: 12px; color: var(--text-secondary); }
    .pc-tips li::before { content: "•"; position: absolute; left: 2px; color: var(--pc); }
    .pc-help p { font-size: 12px; color: var(--text-muted); margin: 0 0 8px; }
    .pc-help a { font-size: 12.5px; font-weight: 800; color: var(--pc); text-decoration: none; }

    @media (max-width: 1080px) { .pc-grid { grid-template-columns: 1fr; } .pc-side { position: static; } .pc-step .lbl span { display: none; } }
    @media (max-width: 640px) { .pc-two, .pc-typegrid, .pc-svctools { grid-template-columns: 1fr; } }

    /* Multi-image gallery uploader */
    .pc-imgs { display: grid; grid-template-columns: repeat(auto-fill, minmax(128px, 1fr)); gap: 12px; margin: 12px 0; }
    .pc-img { position: relative; border: 1px solid var(--border-color); border-radius: 12px; overflow: hidden; background: var(--bg-subtle, rgba(0,0,0,.02)); }
    .pc-img.is-cover { border-color: var(--accent-orange, #f97316); box-shadow: 0 0 0 2px rgba(249,115,22,.25); }
    .pc-img.removing { opacity: .4; }
    .pc-img img { display: block; width: 100%; aspect-ratio: 16/10; object-fit: cover; }
    .pc-img-bar { display: flex; align-items: center; justify-content: space-between; gap: 6px; padding: 6px 8px; font-size: 11px; font-weight: 700; }
    .pc-img-bar label { display: inline-flex; align-items: center; gap: 4px; cursor: pointer; color: var(--text-secondary); }
    .pc-img-bar input { accent-color: var(--accent-orange, #f97316); }
    .pc-cover-tag { position: absolute; top: 7px; left: 7px; font-size: 9.5px; font-weight: 800; letter-spacing: .3px; color: #fff; background: var(--accent-orange, #f97316); border-radius: 6px; padding: 3px 7px; display: none; }
    .pc-img.is-cover .pc-cover-tag { display: inline-block; }
    .pc-drop { border: 1.5px dashed var(--border-color); border-radius: 12px; padding: 20px; text-align: center; font-size: 12.5px; color: var(--text-muted); cursor: pointer; }
    .pc-drop:hover { border-color: var(--accent-orange, #f97316); color: var(--accent-orange, #f97316); }

    /* Step headings, section blocks */
    .pc-card h3 { display: flex; align-items: center; gap: 10px; flex-wrap: wrap; }
    .pc-card h3 small { font-size: 12px; font-weight: 600; color: var(--text-muted); }
    .pc-stepdot { width: 26px; height: 26px; border-radius: 50%; background: var(--pc); color: #fff; font-size: 13px; font-weight: 800; display: inline-flex; align-items: center; justify-content: center; flex-shrink: 0; }
    .pc-block { font-size: 13.5px; font-weight: 800; color: var(--text-primary); margin: 22px 0 14px; padding-bottom: 8px; border-bottom: 1px solid var(--border-color); }
    .pc-block:first-of-type { margin-top: 4px; }
    .pc-block small { font-size: 11.5px; font-weight: 600; color: var(--text-muted); }

    /* Package Summary — derived, never typed twice */
    .pc-summary { display: grid; grid-template-columns: repeat(4, minmax(0,1fr)); gap: 12px; }
    .pc-summary > div { border: 1px solid var(--border-color); border-radius: 10px; padding: 10px 12px; min-width: 0; }
    .pc-summary span { display: block; font-size: 10.5px; font-weight: 800; letter-spacing: .3px; text-transform: uppercase; color: var(--text-muted); margin-bottom: 4px; }
    .pc-summary b { display: block; font-size: 13px; font-weight: 700; color: var(--text-primary); line-height: 1.35; overflow-wrap: anywhere; }

    /* Review step */
    .pc-review { display: grid; grid-template-columns: repeat(2, minmax(0,1fr)); gap: 0 22px; }
    .pc-rev { display: flex; justify-content: space-between; gap: 14px; padding: 10px 0; border-bottom: 1px solid var(--border-color); font-size: 13px; }
    .pc-rev span { color: var(--text-muted); flex-shrink: 0; }
    .pc-rev b { font-weight: 700; color: var(--text-primary); text-align: right; overflow-wrap: anywhere; }
    .pc-gate, .pc-ready { border-radius: 12px; padding: 14px 16px; margin-top: 18px; font-size: 13px; line-height: 1.55; }
    .pc-gate { border: 1px solid rgba(217,119,6,.35); background: rgba(217,119,6,.08); color: var(--warn-text); }
    .pc-ready { border: 1px solid rgba(16,163,74,.35); background: rgba(16,163,74,.09); color: var(--ok-text); }
    .pc-gate b, .pc-ready b { display: block; font-weight: 800; margin-bottom: 5px; }
    .pc-gate ul { margin: 6px 0 8px 18px; padding: 0; }
    .pc-gate span, .pc-ready span { color: var(--text-muted); font-size: 12px; }

    /* Readiness ring */
    .pc-ring { position: relative; width: 132px; margin: 2px auto 10px; }
    .pc-ring svg { width: 132px; height: 132px; display: block; }
    #pcRingArc { transition: stroke-dashoffset .35s ease; }
    .pc-ring-mid { position: absolute; inset: 0; display: flex; flex-direction: column; align-items: center; justify-content: center; }
    .pc-ring-mid b { font-size: 24px; font-weight: 800; color: var(--text-primary); line-height: 1; }
    .pc-ring-mid span { font-size: 11px; color: var(--text-muted); margin-top: 2px; }
    .pc-ring-note { font-size: 11.5px; color: var(--text-muted); text-align: center; margin-bottom: 12px; line-height: 1.45; }
    .pc-ready-list { list-style: none; margin: 0; padding: 0; }
    .pc-ready-list li { display: flex; align-items: center; gap: 9px; font-size: 12.5px; color: var(--text-muted); padding: 6px 0; }
    .pc-ready-list li .dot { width: 15px; height: 15px; border-radius: 50%; border: 2px solid var(--border-color); flex-shrink: 0; }
    .pc-ready-list li.on { color: var(--text-primary); font-weight: 700; }
    .pc-ready-list li.on .dot { background: var(--ok-text, #16a34a); border-color: var(--ok-text, #16a34a); }

    /* Footer trio */
    .pc-trio { display: grid; grid-template-columns: repeat(3, minmax(0,1fr)); gap: 14px; margin-top: 20px; }
    .pc-tcard { display: flex; gap: 12px; background: var(--bg-card); border: 1px solid var(--border-color); border-radius: 14px; padding: 15px; }
    .pc-tcard .ic { width: 34px; height: 34px; border-radius: 10px; background: var(--pc-soft); display: flex; align-items: center; justify-content: center; font-size: 15px; flex-shrink: 0; }
    .pc-tcard b { display: block; font-size: 13px; font-weight: 800; color: var(--text-primary); margin-bottom: 3px; }
    .pc-tcard span { display: block; font-size: 11.5px; color: var(--text-muted); line-height: 1.45; }
    .pc-tcard a, .pc-tcard button { display: inline-block; margin-top: 8px; font-size: 12px; font-weight: 800; color: var(--pc); text-decoration: none; background: none; border: none; padding: 0; cursor: pointer; font-family: inherit; }

    @media (max-width: 900px) {
        .pc-summary { grid-template-columns: repeat(2, minmax(0,1fr)); }
        .pc-review { grid-template-columns: 1fr; }
        .pc-trio { grid-template-columns: 1fr; }
    }
</style>
@endpush

@section('content')
<div class="pc">
<form method="POST" action="{{ $editing ? route('professional.packages.update', $p) : route('professional.packages.store') }}" enctype="multipart/form-data" id="pcForm">
    @csrf
    @if($editing) @method('PATCH') @endif

    @if($errors->any())
        <div class="pc-card" style="border-color:#fecaca;background:#fef2f2;color:#b91c1c;">
            <b>Please fix:</b>
            <ul style="margin:8px 0 0 18px;font-size:13px;">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
        </div>
    @endif

    {{-- ONE draft feature. The mockup shows "Save as Draft" in four places
         with an "Auto-saved 2 min ago" line beside it; there is no autosave, so
         claiming one would be a lie about whether the professional's work is
         safe. This is the single button, and the card at the foot of the page
         submits this same one rather than being a second, different thing. --}}
    <div class="pc-head">
        @if($editing)
            <a href="{{ route('public.package', $p->slug) }}" class="pc-btn pc-btn-ghost" target="_blank" rel="noopener">Preview as Client</a>
        @endif
        {{-- There is no separate help guide; the FAQ is the help page this
             platform actually has, so the button goes there. --}}
        <a href="{{ route('public.faq') }}" class="pc-btn pc-btn-ghost">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                 style="width:16px;height:16px;"><circle cx="12" cy="12" r="10"/><path d="M9.1 9a3 3 0 0 1 5.8 1c0 2-3 3-3 3"/><path d="M12 17h.01"/></svg>
            View Help Guide
        </a>
    </div>

    <div class="pc-stepper" id="pcStepper">
        <div class="pc-step on" data-step="1"><span class="num">1</span><span class="lbl"><b>Select Services</b><span>Choose from your services</span></span></div>
        <span class="bar"></span>
        <div class="pc-step" data-step="2"><span class="num">2</span><span class="lbl"><b>Package Details</b><span>Name, description, photos</span></span></div>
        <span class="bar"></span>
        <div class="pc-step" data-step="3"><span class="num">3</span><span class="lbl"><b>Pricing &amp; Discount</b><span>Set package price</span></span></div>
        <span class="bar"></span>
        <div class="pc-step" data-step="4"><span class="num">4</span><span class="lbl"><b>Options &amp; Add-ons</b><span>Enhance your package</span></span></div>
        <span class="bar"></span>
        <div class="pc-step" data-step="5"><span class="num">5</span><span class="lbl"><b>Availability</b><span>Set date and service rules</span></span></div>
        <span class="bar"></span>
        <div class="pc-step" data-step="6"><span class="num">6</span><span class="lbl"><b>Review &amp; Publish</b><span>Confirm and publish</span></span></div>
    </div>

    <div class="pc-grid">
        <div>
            {{-- STEP 1 — Select Services.

                 Khadijah's 28 October screen opens on this, and it opens on
                 the professional's OWN services rather than the site-wide
                 catalogue: a package is a bundle of things this person
                 already does, at prices they already set.

                 The checkbox still submits the service NAME, which is what
                 every package in the database holds and what the public page,
                 the search filter and the matcher all read. Moving that to an
                 id is a migration, not a form change. --}}
            <div class="pc-panel on" data-panel="1">
                <div class="pc-card">
                    <h3><span class="pc-stepdot">1</span> Select Services <small>{{ $stepBlurb[1] }}</small></h3>

                    @if(($myServices ?? collect())->isNotEmpty())
                        <div class="pc-svctools">
                            <input type="search" id="pcSvcSearch" class="pc-input" placeholder="Search your services..." oninput="pcSvcFilter()">
                            <select id="pcSvcCat" class="pc-select" onchange="pcSvcFilter()">
                                <option value="">All categories</option>
                                @foreach($myServices->pluck('parent.name')->filter()->unique()->sort() as $catName)
                                    <option value="{{ $catName }}">{{ $catName }}</option>
                                @endforeach
                            </select>
                            {{-- "Active" is the switch on My Services & Pricing:
                                 a service turned off is one this professional
                                 is not taking work for, so it should not be
                                 bundled into something clients can book. --}}
                            <select id="pcSvcPriced" class="pc-select" onchange="pcSvcFilter()">
                                <option value="active">Active Services Only</option>
                                <option value="">All Services</option>
                                <option value="priced">Priced Services Only</option>
                            </select>
                        </div>

                        <div class="pc-svcgrid" id="pcSvcList">
                            @foreach($myServices as $svc)
                                @php
                                    $svcPrice  = $svc->pivot->price;
                                    $svcOn     = in_array($svc->name, $pServices, true);
                                    $svcLive   = (bool) ($svc->pivot->is_active ?? true);
                                    $svcImage  = $svc->thumbnail ?: $svc->cover_image;
                                    // No photograph is stored for most services, so the
                                    // tile is a tile. A stock picture of somebody else's
                                    // wedding would say this is what you get.
                                    $svcTint   = ['#2563eb', '#7c3aed', '#db2777', '#d97706', '#059669'][
                                        crc32($svc->name) % 5];
                                @endphp
                                <label class="pc-svccard {{ $svcOn ? 'on' : '' }}"
                                       data-svccard
                                       data-name="{{ Str::lower($svc->name) }}"
                                       data-cat="{{ $svc->parent?->name }}"
                                       data-live="{{ $svcLive ? '1' : '0' }}"
                                       data-priced="{{ $svcPrice !== null ? '1' : '0' }}">
                                    <input type="checkbox" name="services[]" value="{{ $svc->name }}"
                                           data-price="{{ $svcPrice !== null ? (float) $svcPrice : '' }}"
                                           data-cat="{{ $svc->parent?->name }}"
                                           data-unit="{{ $svc->pivot->price_unit ?: 'flat rate' }}"
                                           data-tint="{{ $svcTint }}"
                                           @checked($svcOn) onchange="pcSvcSync()">

                                    <span class="media" style="--t: {{ $svcTint }};">
                                        @if($svcImage)
                                            <img src="{{ $svcImage }}" alt="" loading="lazy">
                                        @else
                                            <span class="initial">{{ Str::upper(Str::substr($svc->name, 0, 1)) }}</span>
                                        @endif
                                        <span class="tick" aria-hidden="true">
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><path d="M20 6 9 17l-5-5"/></svg>
                                        </span>
                                        <span class="live {{ $svcLive ? '' : 'off' }}">{{ $svcLive ? 'Active' : 'Inactive' }}</span>
                                    </span>

                                    <span class="body">
                                        <b>{{ $svc->name }}</b>
                                        <span class="cat">{{ $svc->parent?->name ?? 'Service' }}</span>
                                        <span class="price">
                                            @if($svcPrice !== null)
                                                ${{ number_format((float) $svcPrice, 0) }}
                                                <i>{{ $svc->pivot->price_unit ?: 'flat rate' }}</i>
                                            @else
                                                <i>No price set</i>
                                            @endif
                                        </span>
                                    </span>
                                </label>
                            @endforeach
                        </div>
                        <div class="pc-svcnone" id="pcSvcNone" hidden>Nothing matches that. Clear the search to see the rest.</div>
                    @else
                        {{-- Nothing on their profile yet, so the site-wide list
                             is all there is to pick from. The toggles are the
                             older control, kept so this path still works. --}}
                        <div class="hint" style="margin-bottom:14px;">
                            You have not listed your own services yet, so these are the site's.
                            <a href="{{ route('professional.services.index') }}">Add yours and price them</a> to pick from your own list.
                        </div>
                        <div id="pcSvcList">
                            @foreach($serviceList as $svc)
                                <label class="pc-svcrow {{ in_array($svc, $pServices) ? '' : 'off' }}" data-svcrow>
                                    <span class="ico"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 6 9 17l-5-5"/></svg></span>
                                    <span class="meta"><b>{{ $svc }}</b><span>Included in this package</span></span>
                                    <input type="checkbox" name="services[]" value="{{ $svc }}" style="display:none;" @checked(in_array($svc, $pServices)) onchange="pcSvcSync()">
                                    <button type="button" class="pc-toggle {{ in_array($svc, $pServices) ? 'on' : '' }}" onclick="pcSvcToggle(this)" aria-pressed="{{ in_array($svc, $pServices) ? 'true' : 'false' }}"></button>
                                </label>
                            @endforeach
                        </div>
                    @endif

                    <div class="pc-svccount" id="pcSvcCount">Total Services: <b>0</b> &middot; Minimum 2 required to appear in Package Search</div>
                </div>

                <div class="pc-actions">
                    <a href="{{ route('professional.packages.index') }}" class="pc-btn pc-btn-ghost">Cancel</a>
                    <span class="pc-actions-r">
                        <button type="submit" name="is_active" value="0" class="pc-btn pc-btn-ghost">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                                 stroke-linecap="round" stroke-linejoin="round" style="width:15px;height:15px;">
                                <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6"/>
                            </svg>
                            Save as Draft
                        </button>
                        <button type="button" class="pc-btn pc-btn-primary" data-next="2">Next Step &rarr;</button>
                    </span>
                </div>
            </div>

            {{-- STEP 2 — Package Details --}}
            <div class="pc-panel" data-panel="2">
                <div class="pc-card">
                    <h3><span class="pc-stepdot">2</span> Package Details <small>{{ $stepBlurb[2] }}</small></h3>

                    <div class="pc-block">Package Identity</div>
                    <div class="pc-two">
                        <div class="pc-field">
                            <label for="pcName">Package Name <span class="req">*</span></label>
                            <input type="text" name="title" id="pcName" class="pc-input" maxlength="60" required
                                   value="{{ old('title', $p?->title) }}" placeholder="e.g. Elegant Wedding Photo &amp; Video Package">
                            <div class="pc-counter"><span id="pcNameCount">0</span>/60</div>
                            <div class="hint">Choose a clear, attractive name.</div>
                        </div>
                        <div class="pc-field">
                            <label for="pcCat">Package Category</label>
                            {{-- Khadijah, 4 November: pick at the service level
                                 and below, so a client filtering on a specialty
                                 finds the packages that match it.

                                 The list used to be every root and everything
                                 under it. Under v2 the event types are roots as
                                 well, so this offered 106 occasions as though a
                                 package could be about "Halloween Party", and
                                 nothing filtering on a service would ever find
                                 one filed that way. The headings below are the
                                 service categories and are not selectable. --}}
                            @php
                                $pcPicked  = (int) old('category_id', $p?->category_id);
                                $pcOffered = collect($categories)
                                    ->flatMap(fn ($g) => collect($g['options'])->pluck('id'))
                                    ->contains($pcPicked);
                            @endphp
                            <select name="category_id" id="pcCat" class="pc-select">
                                <option value="">Choose a category</option>

                                {{-- A package saved against a top level category
                                     keeps it until somebody changes it. Dropping
                                     the option would blank the field the next
                                     time this package was edited and saved. --}}
                                @if($pcPicked && ! $pcOffered && $p?->category)
                                    <option value="{{ $pcPicked }}" selected>{{ $p->category->name }} (current)</option>
                                @endif

                                @foreach($categories as $group)
                                    <optgroup label="{{ $group['label'] }}">
                                        @foreach($group['options'] as $option)
                                            <option value="{{ $option['id'] }}" @selected($pcPicked === (int) $option['id'])>{{ $option['depth'] ? str_repeat("\u{00A0}", 4) . $option['name'] : $option['name'] }}</option>
                                        @endforeach
                                    </optgroup>
                                @endforeach
                            </select>
                            <div class="hint">Helps clients find your package. Pick the closest match you offer.</div>
                        </div>
                    </div>

                    <div class="pc-two">
                        <div class="pc-field">
                            <label for="pcPurpose">Primary Package Purpose</label>
                            <input type="text" name="purpose" id="pcPurpose" class="pc-input" maxlength="160"
                                   value="{{ old('purpose', $p?->purpose) }}" placeholder="e.g. Complete Wedding Photography &amp; Videography">
                            <div class="hint">What this package is designed to deliver, in one line.</div>
                        </div>
                        <div class="pc-field">
                            <label>Suitable Event Types</label>
                            <div class="pc-chips">
                                @foreach($eventTypes as $et)
                                    <label class="pc-chipbox">
                                        <input type="checkbox" name="event_types[]" value="{{ $et }}" @checked(in_array($et, $pEventTypes))>
                                        <span>{{ $et }}</span>
                                    </label>
                                @endforeach
                            </div>
                        </div>
                    </div>

                    {{-- Packages are solo-only: one professional provides all included
                         services (Team/Co-Op "combined force" removed platform-wide). --}}
                    <input type="hidden" name="type" value="solo">

                    <div class="pc-block">Package Summary <small>(shown to clients)</small></div>
                    {{-- Read-only on purpose. Every cell is something the
                         professional types somewhere else in this form, so an
                         editable copy here would be a second place for the same
                         fact to live and a second place for it to be wrong. --}}
                    <div class="pc-summary">
                        <div><span>Best For</span><b id="pcSumGuests">—</b></div>
                        <div><span>Typical Coverage</span><b id="pcSumCover">Set in step 4</b></div>
                        <div><span>Services Included</span><b id="pcSumSvcs">Set in step 2</b></div>
                        <div><span>Starting Price</span><b id="pcSumPrice">Set in step 3</b></div>
                    </div>

                    <div class="pc-field" style="margin-top:18px;">
                        <label for="pcDesc">Short Description <span class="req">*</span></label>
                        <textarea name="description" id="pcDesc" class="pc-textarea" maxlength="500" oninput="pcCount()"
                                  placeholder="Describe what makes this package great…">{{ old('description', $p?->description) }}</textarea>
                        <div class="pc-counter"><span id="pcDescCount">0</span>/500</div>
                    </div>

                    <div class="pc-block">Package Media</div>
                    <div class="hint" style="margin-bottom:12px;">
                        Add up to 10 photos and pick one as the <b>cover</b>. It becomes the hero image and
                        drives the card's hover carousel. JPG/PNG/WebP, up to 6&nbsp;MB each.
                    </div>

                    @if($editing && !empty($p->images))
                        <div class="pc-imgs" id="pcExisting">
                            @foreach($p->images as $i => $img)
                                {{-- Inline rather than @php($u = …): Blade pairs a
                                     bare @php with the NEXT @endphp anywhere in
                                     the file, and moving this block above the
                                     one further down the form made it swallow
                                     140 lines as raw PHP. --}}
                                <div class="pc-img {{ ($img['featured'] ?? false) ? 'is-cover' : '' }}" data-img>
                                    <span class="pc-cover-tag">★ Cover</span>
                                    <img src="{{ \Illuminate\Support\Facades\Storage::url($img['hero'] ?? $img['square'] ?? '') }}" alt="">
                                    <div class="pc-img-bar">
                                        <label><input type="radio" name="cover" value="e{{ $i }}" {{ ($img['featured'] ?? false) ? 'checked' : '' }} onchange="pcMarkCover(this)"> Cover</label>
                                        <label><input type="checkbox" name="remove_images[]" value="e{{ $i }}" onchange="this.closest('[data-img]').classList.toggle('removing', this.checked)"> Remove</label>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif

                    <label for="pcGallery" class="pc-drop" id="pcDrop">＋ Click to add photos (up to 10)</label>
                    <input type="file" name="gallery_images[]" id="pcGallery" accept="image/*" multiple hidden>
                    <div class="pc-imgs" id="pcNewPreviews"></div>

                    {{-- The mockup also has an "Intro / Highlight Video" slot.
                         Not built: nothing on this platform stores or serves
                         video, and Peter's own note on the mockup asks for the
                         upload, storage, size limits and retention policy to be
                         confirmed first. A slot that accepts a 128 MB file and
                         drops it would be worse than no slot. --}}

                    <div class="pc-two" style="margin-top:18px;">
                        <div class="pc-field">
                            <label for="pcGmin">Minimum Guest Count</label>
                            <input type="number" name="guest_min" id="pcGmin" class="pc-input" min="0" value="{{ $gMin ?? '' }}" placeholder="50" oninput="pcSummary()">
                            <div class="hint">Leave blank if not applicable</div>
                        </div>
                        <div class="pc-field">
                            <label for="pcGmax">Maximum Guest Count</label>
                            <input type="number" name="guest_max" id="pcGmax" class="pc-input" min="0" value="{{ $gMax ?? '' }}" placeholder="150" oninput="pcSummary()">
                            <div class="hint">Leave blank if unlimited</div>
                        </div>
                    </div>
                </div>
                <div class="pc-actions">
                    <span></span>
                    <button type="button" class="pc-btn pc-btn-ghost" data-prev="1">&larr; Back</button>
                    <button type="button" class="pc-btn pc-btn-primary" data-next="3">Save &amp; Continue to Pricing &rarr;</button>
                </div>
            </div>
            {{-- STEP 3 — Pricing & Discount --}}
            <div class="pc-panel" data-panel="3">
                <div class="pc-card">
                    <h3><span class="pc-stepdot">3</span> Pricing &amp; Discount <small>{{ $stepBlurb[3] }}</small></h3>
                    <div class="pc-two">
                        <div class="pc-field">
                            <label>Total Package Price <span class="req">*</span></label>
                            <input type="number" name="price" id="pcPrice" class="pc-input" min="0" value="{{ old('price', $p?->price) }}" placeholder="3250" oninput="pcPreview()">
                            <div class="hint">Required to publish. A draft can be saved without it.</div>

                            {{-- What the parts cost, so the bundle price is set
                                 against something. These come from My Services &
                                 Pricing; before that page existed there was
                                 nowhere for them to come from. --}}
                            @if(($myPrices ?? collect())->isNotEmpty())
                                <div class="pc-own">
                                    <b>Your own prices</b>
                                    @foreach($myPrices as $__s)
                                        <span class="pc-own-row">{{ $__s->name }}<i>${{ number_format((float) $__s->pivot->price, 2) }}</i></span>
                                    @endforeach
                                    <span class="pc-own-sum">Booked separately<i>${{ number_format($myPriceSum, 2) }}</i></span>
                                </div>
                            @else
                                <div class="pc-own">
                                    <b>Your own prices</b>
                                    <span class="pc-own-row" style="color:var(--text-muted);">
                                        None of your services has a price yet.
                                        <a href="{{ route('professional.services.index') }}">Set them</a>
                                    </span>
                                </div>
                            @endif
                        </div>
                        <div class="pc-field">
                            <label>Price Basis <span class="req">*</span></label>
                            <select name="price_unit" class="pc-select" aria-label="price_unit ?? 'from')===$val)>">
                                @foreach(['from' => 'Starting at', 'flat' => 'Flat rate', 'hourly' => 'Per hour'] as $val => $lbl)
                                    <option value="{{ $val }}" @selected(old('price_unit', $p?->price_unit ?? 'from')===$val)>{{ $lbl }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="pc-two">
                        <div class="pc-field">
                            <label>Savings vs. Booking Separately (%)</label>
                            <input type="number" name="savings_pct" class="pc-input" min="0" max="90" value="{{ old('savings_pct', $p?->savings_pct) }}" placeholder="15">
                            <div class="hint">Shown as "Save up to X%" on the card. Optional.</div>
                        </div>
                        <div class="pc-field">
                            <label>Duration</label>
                            <input type="text" name="duration" class="pc-input" maxlength="60" value="{{ old('duration', $p?->duration) }}" placeholder="Full day">
                        </div>
                    </div>

                    {{-- Levels. Optional, and the whole block says so, because a
                         professional with one price should not feel they have
                         left something blank. --}}
                    <div class="pc-tiers">
                        <div class="pc-tiers-h">
                            <b>Offer more than one level</b>
                            <span>Optional. Give a client a choice of two or three, like Silver and Gold. Leave it empty to sell one price.</span>
                        </div>
                        @foreach($pTiers as $i => $t)
                            <div class="pc-tier-row">
                                <input type="text" name="tiers[{{ $i }}][name]" class="pc-input" maxlength="40"
                                       value="{{ $t['name'] ?? '' }}" placeholder="{{ ['Silver','Gold','Platinum'][$i] ?? 'Level name' }}"
                                       aria-label="Level {{ $i + 1 }} name">
                                <input type="number" name="tiers[{{ $i }}][price]" class="pc-input" min="1" max="10000000"
                                       value="{{ $t['price'] ?? '' }}" placeholder="Price" aria-label="Level {{ $i + 1 }} price">
                                <input type="text" name="tiers[{{ $i }}][blurb]" class="pc-input" maxlength="120"
                                       value="{{ $t['blurb'] ?? '' }}" placeholder="What makes this one different"
                                       aria-label="Level {{ $i + 1 }} description">
                            </div>
                        @endforeach
                        <div class="hint">
                            A level needs a name and a price to count. Once you add levels, the price above
                            becomes the cheapest of them and the card reads "Starting at", so clients are
                            never shown a figure they cannot buy at.
                        </div>
                    </div>
                </div>
                <div class="pc-actions">
                    <button type="button" class="pc-btn pc-btn-ghost" data-prev="2">&larr; Back</button>
                    <button type="button" class="pc-btn pc-btn-primary" data-next="4">Save &amp; Continue to Options &amp; Add-ons &rarr;</button>
                </div>
            </div>

            {{-- STEP 4 — Options & Add-ons. Split off pricing on 5 November:
                 the price and the extras were one step, and the extras are
                 optional while the price is not. --}}
            <div class="pc-panel" data-panel="4">
                <div class="pc-card">
                    <h3><span class="pc-stepdot">4</span> Options &amp; Add-ons <small>{{ $stepBlurb[4] }}</small></h3>
                    <div class="pc-field">
                        <label>What's Included</label>
                        <div id="pcIncludes">
                            @forelse($pIncludes as $inc)
                                <div class="pc-rep-item"><input type="text" name="includes[]" class="pc-input" value="{{ $inc }}" placeholder="e.g. Edited online gallery"><button type="button" class="pc-rep-del" onclick="pcDel(this)">✕</button></div>
                            @empty
                                <div class="pc-rep-item"><input type="text" name="includes[]" class="pc-input" placeholder="e.g. Edited online gallery"><button type="button" class="pc-rep-del" onclick="pcDel(this)">✕</button></div>
                            @endforelse
                        </div>
                        <button type="button" class="pc-addbtn" onclick="pcAdd('pcIncludes','includes[]','e.g. add-on or deliverable')">+ Add Item</button>
                    </div>
                </div>
                <div class="pc-actions">
                    <button type="button" class="pc-btn pc-btn-ghost" data-prev="3">&larr; Back</button>
                    <button type="button" class="pc-btn pc-btn-primary" data-next="5">Save &amp; Continue to Availability &rarr;</button>
                </div>
            </div>
            {{-- STEP 5 — Availability --}}
            <div class="pc-panel" data-panel="5">
                <div class="pc-card">
                    <h3><span class="pc-stepdot">5</span> Availability <small>{{ $stepBlurb[5] }}</small></h3>
                    <div class="pc-two">
                        <div class="pc-field">
                            <label>Event Coverage</label>
                            <input type="text" name="coverage" id="pcCoverage" class="pc-input" maxlength="80" value="{{ old('coverage', $p?->coverage) }}" placeholder="Up to 10 Hours" oninput="pcSummary()">
                        </div>
                        <div class="pc-field">
                            <label>Availability</label>
                            <input type="text" name="availability" class="pc-input" maxlength="80" value="{{ old('availability', $p?->availability) }}" placeholder="Available Weekends">
                        </div>
                    </div>
                    {{-- Not an input any more. A package is offered in the
                         professional's own state only (Option B), so this is read
                         from the profile rather than typed — a free-text box let a
                         professional claim states they cannot legally serve. --}}
                    <div class="pc-field">
                        <label>Service area</label>
                        @php $proState = auth()->user()->profile?->state; @endphp
                        <div class="pc-input" style="background:var(--bg-card-hover,#f8fafc);display:flex;align-items:center;">
                            {{ $proState ? (config('geo.allowed_states')[$proState] ?? $proState) : 'Not set' }}
                        </div>
                        <div class="hint">
                            @if($proState)
                                Packages are offered in your own state. Update it in
                                <a href="{{ route('professional.profile.index') }}">Profile &amp; Settings</a> and your packages follow.
                            @else
                                Set your state in <a href="{{ route('professional.profile.index') }}">Profile &amp; Settings</a> so clients can find this package.
                            @endif
                        </div>
                    </div>
                </div>
                <div class="pc-actions">
                    <button type="button" class="pc-btn pc-btn-ghost" data-prev="4">&larr; Back</button>
                    <button type="button" class="pc-btn pc-btn-primary" data-next="6">Save &amp; Continue to Review &rarr;</button>
                </div>
            </div>
            {{-- STEP 6 — Review & Publish.
                 Everything on it is read from the form as it stands, so it
                 cannot show a tidy summary of a package the form does not
                 actually hold. Publishing is the only button that publishes. --}}
            <div class="pc-panel" data-panel="6">
                <div class="pc-card">
                    <h3><span class="pc-stepdot">6</span> Review &amp; Publish <small>{{ $stepBlurb[6] }}</small></h3>

                    <div class="pc-review" id="pcReview">
                        <div class="pc-rev"><span>Package name</span><b data-rev="title">—</b></div>
                        <div class="pc-rev"><span>Purpose</span><b data-rev="purpose">—</b></div>
                        <div class="pc-rev"><span>Category</span><b data-rev="category">—</b></div>
                        <div class="pc-rev"><span>Event types</span><b data-rev="events">—</b></div>
                        <div class="pc-rev"><span>Services included</span><b data-rev="services">—</b></div>
                        <div class="pc-rev"><span>Price</span><b data-rev="price">—</b></div>
                        <div class="pc-rev"><span>Guests</span><b data-rev="guests">—</b></div>
                        <div class="pc-rev"><span>Coverage</span><b data-rev="coverage">—</b></div>
                        <div class="pc-rev"><span>Availability</span><b data-rev="availability">—</b></div>
                        <div class="pc-rev"><span>Photos</span><b data-rev="photos">—</b></div>
                    </div>

                    {{-- What is still missing, named. "Not ready yet" without
                         saying why is the defect this whole flow is meant to
                         avoid. --}}
                    <div class="pc-gate" id="pcGate" hidden>
                        <b>Not ready to publish yet</b>
                        <ul id="pcGateList"></ul>
                        <span>You can still save this as a draft and come back.</span>
                    </div>

                    <div class="pc-ready" id="pcReady" hidden>
                        <b>✓ Ready to publish</b>
                        <span>Once published, this package appears in Package Search for clients in your state.</span>
                    </div>
                </div>
                <div class="pc-actions">
                    <button type="button" class="pc-btn pc-btn-ghost" data-prev="5">&larr; Back</button>
                    <button type="submit" name="is_active" value="1" class="pc-btn pc-btn-primary">{{ $editing ? 'Save &amp; Publish' : 'Publish Package' }} ✓</button>
                </div>
            </div>
        </div>

        {{-- Right rail --}}
        <aside class="pc-side">
            {{-- PACKAGE READINESS.
                 Peter's note on the mockup asks for this number's formula to be
                 defined, and points out that the "32%" shown there does not
                 follow from one of eight items being ticked. So it is one row
                 per step of the form, ticked when that step has what it asks
                 for, and the percentage is simply how many of them are done —
                 the same reading PackageProgress gives the progress bar on My
                 Packages, so the two screens can never quote different numbers
                 for one package.

                 The mockup's eight rows include Travel & Coverage and Booking
                 Terms, which are not sections of this form and have no fields
                 behind them. Counting them would mean a package could never
                 reach 100%. --}}
            {{-- What they have picked, and what those parts cost booked one at a
                 time. The discount on step 3 is measured against this number,
                 so it is shown here before it is used there. --}}
            <div class="pc-scard" id="pcPicked">
                <h4 class="pc-picked-head">
                    <span>Selected Services (<span id="pcPickedN">0</span>)</span>
                    <button type="button" id="pcPickedClear" class="pc-clearall" hidden>Clear All</button>
                </h4>
                <ul class="pc-picked" id="pcPickedList"></ul>
                <div class="pc-picked-empty" id="pcPickedEmpty">Nothing selected yet. A package needs at least two.</div>

                {{-- The order is the order they are listed in, and dragging
                     changes it. Said here because nothing else on the card
                     shows that the handles do anything. --}}
                <div class="pc-picked-tip" id="pcPickedTip" hidden>
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round">
                        <circle cx="12" cy="12" r="10"/><path d="M12 16v-4"/><path d="M12 8h.01"/>
                    </svg>
                    <span><b>You can change the order of services by dragging.</b>
                    These services will be bundled together in this package.</span>
                </div>

                <div class="pc-picked-sum" id="pcPickedSum" hidden>
                    <span>Estimated Individual Total<small>Total if each service is booked separately at base price.</small></span>
                    <b id="pcPickedTotal">$0</b>
                </div>
            </div>

            <div class="pc-scard pc-afterone">
                <h4>Package Readiness</h4>
                <div class="pc-ring">
                    <svg viewBox="0 0 120 120" aria-hidden="true">
                        <circle cx="60" cy="60" r="52" fill="none" stroke="var(--border-color)" stroke-width="12"/>
                        <circle id="pcRingArc" cx="60" cy="60" r="52" fill="none" stroke="var(--pc)" stroke-width="12"
                                stroke-linecap="round" stroke-dasharray="326.7" stroke-dashoffset="326.7"
                                transform="rotate(-90 60 60)"/>
                    </svg>
                    <div class="pc-ring-mid"><b id="pcRingPct">{{ $ringPct }}%</b><span>Complete</span></div>
                </div>
                <div class="pc-ring-note" id="pcRingNote">Fill in steps 1 to 4, then publish in step 5.</div>
                <ul class="pc-ready-list" id="pcReadyList">
                    @foreach($ringSteps as $step)
                        <li data-ring-step="{{ $step['n'] }}" class="{{ $step['done'] ? 'on' : '' }}">
                            <span class="dot"></span>{{ $step['label'] }}
                        </li>
                    @endforeach
                    <li data-ring-publish class="{{ $published ? 'on' : '' }}">
                        <span class="dot"></span>Review &amp; Publish
                    </li>
                </ul>
            </div>

            <div class="pc-scard pc-afterone">
                <h4>Package Preview</h4>
                <div class="pc-prev-media" id="pcPrevMedia">Cover photo preview</div>
                <div class="pc-prev-title" id="pcPrevTitle">{{ old('title', $p?->title) ?: 'Your package name' }}</div>
                <span class="pc-prev-type" id="pcPrevType">Service Package</span>
                <div class="pc-prev-svc"><span id="pcPrevSvcCount">0</span> Services Included</div>
                <div class="pc-prev-price">$<span id="pcPrevPrice">{{ old('price', $p?->price) ?: '0' }}</span><small>Total Package Price</small></div>
                @if($editing)
                    {{-- Real: the owner may open their own package before it is
                         live, and the page it opens says so. --}}
                    <a class="pc-btn pc-btn-ghost" style="display:block;text-align:center;margin-top:12px;"
                       href="{{ route('public.package', $p->slug) }}" target="_blank" rel="noopener">Preview as Client</a>
                @else
                    <div class="hint" style="margin-top:10px;">Save this as a draft to preview it as a client.</div>
                @endif
            </div>

            <div class="pc-scard pc-afterone">
                <h4>Package Highlights</h4>
                <ul class="pc-list">
                    {{-- "Build trust with verified badge" only when there is a
                         badge to show. Every verification mark on the site was
                         switched off on 23 September, and the professional's
                         own Verification tab was promising the same thing
                         until 29 October. Selling a package on the strength of
                         a badge nobody can see is the same promise again. --}}
                    @foreach(array_filter([
                        'Appears in Package Search results',
                        'One contract. One payment.',
                        'Clients can customize add-ons',
                        'You control your availability',
                        \App\Support\VerifiedBadge::shown() ? 'Build trust with verified badge' : null,
                    ]) as $h)
                        <li><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"/></svg>{{ $h }}</li>
                    @endforeach
                </ul>
            </div>

            <div class="pc-scard pc-afterone">
                <h4>Tips for a Great Package</h4>
                <ul class="pc-tips" style="list-style:none;padding:0;margin:0;">
                    <li>Include at least 2 complementary services</li>
                    <li>Use high-quality photos and clear descriptions</li>
                    <li>Offer add-ons to increase package value</li>
                    <li>Set competitive, transparent pricing</li>
                </ul>
            </div>

            <div class="pc-scard pc-help pc-afterone">
                <h4>Need Help?</h4>
                <p>Our support team is here to help you create the perfect package.</p>
                {{-- Row 122 — pointed at client↔professional messaging, which
                     reaches another user rather than the platform. --}}
                <a href="{{ \App\Domain\Forms\FormRegistry::url('support_request') }}">Contact Support →</a>
            </div>
        </aside>
    </div>

    {{-- The three cards an older mockup ended on are gone. Her 28 October
         screen ends at the action bar, and all three were a second doorway to
         something already on this page: help is the button in the header,
         saving a draft is the button in the bar, and previewing is in the
         header once there is a draft to preview. One of them clicked a
         button that no longer exists. --}}
</form>
</div>

<script>
(function () {
    function goStep(n) {
        document.querySelectorAll('.pc-panel').forEach(function (p) { p.classList.toggle('on', p.dataset.panel == n); });
        document.querySelectorAll('.pc-step').forEach(function (s) {
            s.classList.toggle('on', s.dataset.step == n);
            s.classList.toggle('done', parseInt(s.dataset.step) < n);
        });

        /* The connector between two circles is filled once the step on its
           left has been passed, so the line reads as progress. */
        var bars = document.querySelectorAll('#pcStepper .bar');
        Array.prototype.forEach.call(bars, function (b, i) { b.classList.toggle('done', i + 1 < n); });

        if (window.pcStepRail) { window.pcStepRail(parseInt(n)); }

        window.scrollTo({ top: 0, behavior: 'smooth' });
    }
    document.querySelectorAll('[data-next]').forEach(function (b) { b.addEventListener('click', function () { goStep(b.dataset.next); }); });
    document.querySelectorAll('[data-prev]').forEach(function (b) { b.addEventListener('click', function () { goStep(b.dataset.prev); }); });
    document.querySelectorAll('.pc-step').forEach(function (s) { s.addEventListener('click', function () { goStep(s.dataset.step); }); });


    window.pcCount = function () {
        document.getElementById('pcDescCount').textContent = document.getElementById('pcDesc').value.length;
        document.getElementById('pcNameCount').textContent = document.getElementById('pcName').value.length;
        pcSummary();
    };

    /*
     * The Package Summary strip, the readiness ring and the review step all
     * read the form as it stands right now. One function, so the three of them
     * cannot describe three different packages — which is what happens when a
     * summary is typed separately from the fields it summarizes.
     *
     * The four rules below are PackageProgress::steps() in JavaScript. They
     * have to stay in step with it: this is what the professional sees while
     * typing, and that is what My Packages shows afterwards.
     */
    function pcState() {
        var val = function (id) { var el = document.getElementById(id); return el ? el.value.trim() : ''; };
        var svcs = Array.prototype.map.call(
            document.querySelectorAll('#pcSvcList input[type=checkbox]:checked'), function (c) { return c.value; });
        var events = Array.prototype.map.call(
            document.querySelectorAll('.pc-chipbox input:checked'), function (c) { return c.value; });
        var existing = document.querySelectorAll('#pcExisting .pc-img:not(.removing)').length;
        var added = document.querySelectorAll('#pcNewPreviews .pc-img').length;
        var cat = document.getElementById('pcCat');

        return {
            title: val('pcName'),
            purpose: val('pcPurpose'),
            description: val('pcDesc'),
            category: cat && cat.value ? cat.options[cat.selectedIndex].text : '',
            events: events,
            services: svcs,
            price: parseInt(val('pcPrice') || '0', 10),
            gmin: val('pcGmin'),
            gmax: val('pcGmax'),
            coverage: val('pcCoverage'),
            availability: (document.querySelector('[name=availability]') || {}).value || '',
            photos: existing + added
        };
    }

    function pcGuestLabel(s) {
        if (s.gmin && s.gmax) return s.gmin + '–' + s.gmax + ' guests';
        if (s.gmax) return 'Up to ' + s.gmax + ' guests';
        if (s.gmin) return s.gmin + '+ guests';
        return '';
    }

    // Mirrors PackageProgress::steps(). Same order, same conditions.
    function pcSteps(s) {
        return [
            { n: 1, label: 'Select Services',    done: s.services.length >= 2,       missing: 'at least two services' },
            { n: 2, label: 'Package Details',    done: !!s.title && !!s.description, missing: 'a description' },
            { n: 3, label: 'Pricing & Discount', done: s.price > 0,                  missing: 'a price' },
            { n: 5, label: 'Availability',       done: !!s.coverage || !!s.availability, missing: 'coverage or availability' }
        ];
    }

    window.pcSummary = function () {
        var s = pcState();
        var set = function (id, text) { var el = document.getElementById(id); if (el) el.textContent = text; };

        set('pcSumGuests', pcGuestLabel(s) || '—');
        set('pcSumCover', s.coverage || 'Set in step 4');
        set('pcSumSvcs', s.services.length ? s.services.slice(0, 2).join(', ') + (s.services.length > 2 ? ' +' + (s.services.length - 2) : '') : 'Set in step 2');
        set('pcSumPrice', s.price > 0 ? '$' + s.price.toLocaleString() : 'Set in step 3');

        // Readiness ring
        var steps = pcSteps(s);
        var done = steps.filter(function (x) { return x.done; }).length;
        var pct = Math.round(done / steps.length * 100);
        var arc = document.getElementById('pcRingArc');
        if (arc) {
            var c = 2 * Math.PI * 52;
            arc.setAttribute('stroke-dasharray', c);
            arc.setAttribute('stroke-dashoffset', c * (1 - pct / 100));
        }
        set('pcRingPct', pct + '%');
        steps.forEach(function (x) {
            var li = document.querySelector('[data-ring-step="' + x.n + '"]');
            if (li) li.classList.toggle('on', x.done);
        });
        var note = document.getElementById('pcRingNote');
        if (note) note.textContent = pct === 100
            ? 'Everything is filled in. Go to step 5 to publish.'
            : 'Fill in steps 1 to 4, then publish in step 5.';

        // Review step
        var rev = {
            title: s.title, purpose: s.purpose, category: s.category,
            events: s.events.join(', '), services: s.services.join(', '),
            price: s.price > 0 ? '$' + s.price.toLocaleString() : '',
            guests: pcGuestLabel(s), coverage: s.coverage, availability: s.availability,
            photos: s.photos ? s.photos + (s.photos === 1 ? ' photo' : ' photos') : ''
        };
        Object.keys(rev).forEach(function (k) {
            var el = document.querySelector('[data-rev="' + k + '"]');
            if (el) el.textContent = rev[k] || '—';
        });

        // What is still stopping it, named.
        var missing = steps.filter(function (x) { return !x.done; });
        var gate = document.getElementById('pcGate'), ready = document.getElementById('pcReady');
        if (gate && ready) {
            gate.hidden = missing.length === 0;
            ready.hidden = missing.length > 0;
            var list = document.getElementById('pcGateList');
            list.innerHTML = '';
            missing.forEach(function (x) {
                var li = document.createElement('li');
                li.textContent = 'Step ' + x.n + ', ' + x.label + ', needs ' + x.missing;
                list.appendChild(li);
            });
        }
    };

    document.querySelectorAll('.pc-chipbox input, #pcCat, #pcPurpose, [name=availability]').forEach(function (el) {
        el.addEventListener('change', pcSummary);
        el.addEventListener('input', pcSummary);
    });

    pcCount();

    window.pcPreview = function () {
        document.getElementById('pcPrevPrice').textContent = Number(document.getElementById('pcPrice').value || 0).toLocaleString();
        pcSummary();
    };
    document.getElementById('pcName').addEventListener('input', function () {
        document.getElementById('pcPrevTitle').textContent = this.value || 'Your package name';
        pcCount();
    });
    // ── Multi-image gallery uploader ──
    window.pcMarkCover = function (radio) {
        document.querySelectorAll('#pcExisting .pc-img, #pcNewPreviews .pc-img').forEach(function (c) {
            var r = c.querySelector('input[name=cover]');
            c.classList.toggle('is-cover', !!r && r.checked);
        });
        pcSyncPreview();
    };
    function pcSyncPreview() {
        var cover = document.querySelector('input[name=cover]:checked');
        var img = cover ? cover.closest('.pc-img').querySelector('img') : document.querySelector('.pc-img img');
        var media = document.getElementById('pcPrevMedia');
        if (img && media) media.innerHTML = '<img src="' + img.src + '" alt="">';
    }
    var pcGallery = document.getElementById('pcGallery');
    if (pcGallery) {
        pcGallery.addEventListener('change', function (e) {
            var box = document.getElementById('pcNewPreviews');
            box.innerHTML = '';
            var files = Array.prototype.slice.call(e.target.files, 0, 10);
            var noExistingCover = !document.querySelector('#pcExisting input[name=cover]:checked');
            files.forEach(function (f, i) {
                var url = URL.createObjectURL(f);
                var checked = (i === 0 && noExistingCover) ? 'checked' : '';
                var div = document.createElement('div');
                div.className = 'pc-img' + (checked ? ' is-cover' : '');
                div.setAttribute('data-img', '');
                div.innerHTML = '<span class="pc-cover-tag">★ Cover</span><img src="' + url + '" alt="">' +
                    '<div class="pc-img-bar"><label><input type="radio" name="cover" value="n' + i + '" ' + checked + ' onchange="pcMarkCover(this)"> Cover</label><span style="color:var(--text-muted);font-weight:600;">New</span></div>';
                box.appendChild(div);
            });
            pcSyncPreview();
            pcSummary();
        });
    }

    window.pcSvcToggle = function (el) {
        var cb = el.parentElement.querySelector('input[type=checkbox]');
        cb.checked = !cb.checked;
        el.classList.toggle('on', cb.checked);
        el.closest('[data-svcrow]').classList.toggle('off', !cb.checked);
        pcSvcSync();
    };
    window.pcSvcSync = function () {
        var picked = Array.prototype.slice.call(
            document.querySelectorAll('#pcSvcList input[type=checkbox]:checked'));
        var n = picked.length;

        var box = document.getElementById('pcSvcCount');
        box.innerHTML = 'Total Services: <b>' + n + '</b> \u00b7 Minimum 2 required to appear in Package Search';
        box.classList.toggle('bad', n < 2);
        document.getElementById('pcPrevSvcCount').textContent = n;

        // The card border follows the checkbox rather than the click, so a
        // card selected from old() on a failed submit is drawn selected too.
        document.querySelectorAll('[data-svccard]').forEach(function (card) {
            card.classList.toggle('on', card.querySelector('input[type=checkbox]').checked);
        });

        /*
         * Selected Services, and what those parts cost booked one at a time.
         *
         * Only services carrying a price are added up, and the ones without
         * say so rather than counting as zero: a total that quietly treats an
         * unpriced service as free is a number the professional would set
         * their discount against.
         */
        var list  = document.getElementById('pcPickedList');
        var empty = document.getElementById('pcPickedEmpty');
        var sumEl = document.getElementById('pcPickedSum');
        if (!list) { pcSummary(); return; }

        var total = 0, unpriced = 0;
        list.innerHTML = '';

        picked.forEach(function (c) {
            var raw = c.dataset.price;
            var has = raw !== undefined && raw !== '';
            if (has) { total += parseFloat(raw) || 0; } else { unpriced++; }

            var li = document.createElement('li');
            li.draggable = true;

            var grip = document.createElement('span');
            grip.className = 'grip';
            grip.textContent = '\u2059\u2059';
            grip.setAttribute('aria-hidden', 'true');

            var thumb = document.createElement('span');
            thumb.className = 'thumb';
            thumb.style.background = c.dataset.tint || '#64748b';
            thumb.textContent = (c.value || '?').charAt(0).toUpperCase();

            var m = document.createElement('span');
            m.className = 'm';

            var name = document.createElement('b');
            name.textContent = c.value;
            name.title = c.value;

            var cat = document.createElement('span');
            cat.className = 'c';
            cat.textContent = c.dataset.cat || 'Service';

            var price = document.createElement('span');
            price.className = has ? 'p' : 'p none';
            price.textContent = has
                ? ('$' + (parseFloat(raw) || 0).toLocaleString() + ' ' + (c.dataset.unit || ''))
                : 'No price set';

            m.appendChild(name); m.appendChild(cat); m.appendChild(price);

            /* Removing from here unticks the card, so the two can never show
               different things about the same service. */
            var x = document.createElement('button');
            x.type = 'button';
            x.className = 'x';
            x.setAttribute('aria-label', 'Remove ' + c.value);
            x.textContent = '\u2715';
            x.addEventListener('click', function () { c.checked = false; pcSvcSync(); });

            li.appendChild(grip); li.appendChild(thumb); li.appendChild(m); li.appendChild(x);
            list.appendChild(li);
        });

        pcPickedDrag(list);

        var nEl = document.getElementById('pcPickedN');
        if (nEl) { nEl.textContent = n; }
        if (empty) { empty.hidden = n > 0; }

        var tip = document.getElementById('pcPickedTip');
        if (tip) { tip.hidden = n < 2; }

        var clear = document.getElementById('pcPickedClear');
        if (clear) { clear.hidden = n === 0; }
        if (sumEl) {
            sumEl.hidden = total <= 0;
            var totalEl = document.getElementById('pcPickedTotal');
            if (totalEl) {
                totalEl.textContent = '$' + total.toLocaleString();
                totalEl.title = unpriced > 0
                    ? unpriced + ' of the services you picked have no price, so they are not in this total.'
                    : '';
            }
        }

        pcSummary();
    };

    /* Dragging reorders the list, and the order is the order the services
       are stored in, so the package reads the way it was arranged. */
    function pcPickedDrag(list) {
        var from = null;

        list.querySelectorAll('li').forEach(function (li) {
            li.addEventListener('dragstart', function () { from = li; li.classList.add('drag'); });
            li.addEventListener('dragend', function () { li.classList.remove('drag'); pcPickedOrder(); });
            li.addEventListener('dragover', function (e) {
                e.preventDefault();
                if (!from || from === li) { return; }
                var box = li.getBoundingClientRect();
                var after = (e.clientY - box.top) > box.height / 2;
                list.insertBefore(from, after ? li.nextSibling : li);
            });
        });
    }

    /* The checkboxes are what the form submits, so the list's order is
       written back onto them rather than kept only on screen. */
    function pcPickedOrder() {
        var list = document.getElementById('pcPickedList');
        var host = document.getElementById('pcSvcList');
        if (!list || !host) { return; }

        Array.prototype.slice.call(list.children).reverse().forEach(function (li) {
            var name = li.querySelector('.m b');
            if (!name) { return; }

            var cb = Array.prototype.slice.call(
                host.querySelectorAll('input[type=checkbox]')
            ).filter(function (c) { return c.value === name.textContent; })[0];

            var card = cb && cb.closest('[data-svccard]');
            if (card && card.parentElement) { card.parentElement.prepend(card); }
        });
    }

    var pcClear = document.getElementById('pcPickedClear');
    if (pcClear) {
        pcClear.addEventListener('click', function () {
            document.querySelectorAll('#pcSvcList input[type=checkbox]:checked')
                .forEach(function (c) { c.checked = false; });
            pcSvcSync();
        });
    }

    /* Search and the two dropdowns, all three narrowing the same grid. */
    window.pcSvcFilter = function () {
        var q    = (document.getElementById('pcSvcSearch') || {}).value || '';
        var cat  = (document.getElementById('pcSvcCat') || {}).value || '';
        var only = (document.getElementById('pcSvcPriced') || {}).value || '';
        q = q.trim().toLowerCase();

        var shown = 0;
        document.querySelectorAll('[data-svccard]').forEach(function (card) {
            /* A service already in the package stays on screen whatever the
               filter says: hiding something that is ticked makes it look as
               though it was dropped. */
            var picked = card.querySelector('input[type=checkbox]').checked;

            var ok = picked || (
                   (!q   || card.dataset.name.indexOf(q) !== -1)
                && (!cat || card.dataset.cat === cat)
                && (only !== 'priced' || card.dataset.priced === '1')
                && (only !== 'active' || card.dataset.live === '1')
            );
            card.hidden = !ok;
            if (ok) { shown++; }
        });

        var none = document.getElementById('pcSvcNone');
        if (none) { none.hidden = shown > 0; }
    };

    /* Her step 1 rail carries the picked services and nothing else. The
       readiness ring and the preview appear once there is something for them
       to describe. */
    window.pcStepRail = function (step) {
        document.querySelectorAll('.pc-afterone').forEach(function (c) { c.hidden = step === 1; });
    };

    pcSvcFilter();
    pcStepRail(1);

    pcSvcSync();

    window.pcAdd = function (id, name, ph) {
        var row = document.createElement('div');
        row.className = 'pc-rep-item';
        row.innerHTML = '<input type="text" name="' + name + '" class="pc-input" placeholder="' + ph + '"><button type="button" class="pc-rep-del" onclick="pcDel(this)">✕</button>';
        document.getElementById(id).appendChild(row);
    };
    window.pcDel = function (btn) {
        var wrap = btn.parentElement.parentElement;
        if (wrap.querySelectorAll('.pc-rep-item').length > 1) btn.parentElement.remove();
        else btn.previousElementSibling.value = '';
    };
})();
</script>
@endsection
