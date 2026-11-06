@extends('layouts.landing')

@php
    $seoTitle       = 'FAQs: Questions? We\'ve Got Answers | GigResource';
    $seoDescription = 'Find quick answers to the most common questions about using GigResource, getting started, requests, payments, account security, and support.';
@endphp

@push('styles')
<style>
    /* ══════ FAQ, against the 2026 design file ══════
       Its content column is 90.8% of the viewport (930px in a 1024px
       screenshot). Every number below is that file's own multiplied by
       1.368, the ratio between its column and a 1272px one, and the unit
       carries the rest: 1px at a 1272 column, growing with it. */
    .fq-page { --u: clamp(0.78px, 0.0714vw, 1.34px); }

    /* On this page the header, the content and the footer share the file's
       column. Pushed from here, so no other page is affected. */
    .lp-container { max-width: min(calc(90.8% + 48px), 1620px); }

    /* ── Hero, with the search inside it ──────────────────────── */
    .fq-hero { position: relative; overflow: hidden;
        background: linear-gradient(103deg, #ecf3fe 0%, #eef4fe 46%, #f4f8fe 100%); }
    /* Two cuts from the file, not one. The file's own search row sits
       under the left of this area, so the lower strip starts to the right
       of it and the upper one, which carries the FAQ card and the ticks,
       runs the full width above it. The stripes that were here came from
       filling that corner with pixels copied along each row; nothing is
       painted over now. */
    .fq-hero-art, .fq-hero-foot { position: absolute; right: 0; z-index: 1;
        background-position: right top; background-size: 100% 100%;
        background-repeat: no-repeat; }
    .fq-hero-art { top: 0; height: 69.7%; width: 54.1%;
        background-image: url('{{ asset('images/faq/hero.png') }}');
        -webkit-mask-image: linear-gradient(90deg, transparent 0, #000 9%);
        mask-image: linear-gradient(90deg, transparent 0, #000 9%); }
    .fq-hero-foot { top: 69.7%; bottom: 0; width: 45.3%;
        background-image: url('{{ asset('images/faq/hero-foot.png') }}');
        -webkit-mask-image: linear-gradient(90deg, transparent 0, #000 11%);
        mask-image: linear-gradient(90deg, transparent 0, #000 11%); }
    .fq-hero-in { position: relative; z-index: 2; padding: calc(18 * var(--u)) 0 calc(24 * var(--u));
        max-width: calc(700 * var(--u)); }
    .fq-eyebrow { display: block; font-size: calc(15 * var(--u)); font-weight: 800;
        letter-spacing: calc(1 * var(--u)); color: #fb4404; margin: 0 0 calc(5 * var(--u)); }
    .fq-hero h1 { font-size: calc(52 * var(--u)); font-weight: 800; letter-spacing: calc(-1.4 * var(--u));
        line-height: 1.1; margin: 0; color: #0b1340; }
    .fq-hero h1 .o { color: #f4400a; }
    .fq-hero h1 .b { color: #1565e8; }
    .fq-hero p { font-size: calc(17 * var(--u)); color: #3f4a63; line-height: 1.5;
        margin: calc(11 * var(--u)) 0 0; max-width: calc(470 * var(--u)); }

    .fq-searchrow { position: relative; z-index: 2; display: flex; margin: calc(16 * var(--u)) 0 0;
        max-width: calc(688 * var(--u)); border-radius: calc(10 * var(--u)); overflow: hidden;
        border: 1px solid #cfe0fb; background: #fff; }
    .fq-searchrow .ic { display: flex; align-items: center; padding: 0 calc(5 * var(--u)) 0 calc(15 * var(--u)); color: #0b1340; }
    .fq-searchrow .ic svg { width: calc(22 * var(--u)); height: calc(22 * var(--u)); }
    .fq-searchrow input { flex: 1 1 auto; min-width: 0; border: 0; outline: 0; background: transparent;
        font-family: inherit; font-size: calc(16 * var(--u)); color: #0b1340;
        padding: calc(12 * var(--u)) calc(8 * var(--u)); }
    .fq-searchrow input::placeholder { color: #7b879c; }
    .fq-searchrow button { flex: none; border: 0; cursor: pointer; background: #1565e8; color: #fff;
        font-family: inherit; font-size: calc(16 * var(--u)); font-weight: 700; padding: 0 calc(34 * var(--u)); }
    .fq-pop { position: relative; z-index: 2; display: flex; align-items: center; flex-wrap: wrap;
        gap: calc(8 * var(--u)); margin-top: calc(11 * var(--u)); }
    .fq-pop > b { font-size: calc(13.5 * var(--u)); font-weight: 600; color: #3f4a63; }
    .fq-pop button { cursor: pointer; background: #fff; border: 1px solid #dfe8f5;
        border-radius: calc(999 * var(--u)); padding: calc(5 * var(--u)) calc(15 * var(--u));
        font-family: inherit; font-size: calc(13.5 * var(--u)); color: #2f3b55; }
    .fq-pop button:hover { border-color: #1565e8; color: #1565e8; }

    .fq-body { background: #f7fbfe; padding-bottom: calc(14 * var(--u)); }

    /* ── Section headings ─────────────────────────────────────── */
    .fq-sec { padding-top: calc(22 * var(--u)); }
    .fq-sec h2 { font-size: calc(26 * var(--u)); font-weight: 800; color: #0b1340;
        margin: 0; line-height: 1.2; letter-spacing: calc(-.4 * var(--u)); }
    .fq-sec > p.lede { font-size: calc(15 * var(--u)); color: #5a6679; margin: calc(3 * var(--u)) 0 calc(13 * var(--u)); }
    .fq-sechead { display: flex; align-items: flex-start; justify-content: space-between; gap: calc(20 * var(--u)); }
    .fq-viewall { font-size: calc(14.5 * var(--u)); font-weight: 700; color: #1565e8; text-decoration: none;
        display: inline-flex; align-items: center; gap: calc(7 * var(--u)); white-space: nowrap; }
    .fq-viewall svg { width: calc(15 * var(--u)); height: calc(15 * var(--u)); }

    /* ── I am a… ──────────────────────────────────────────────── */
    .fq-roles { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: calc(13 * var(--u)); }
    .fq-role { display: grid; grid-template-columns: calc(72 * var(--u)) minmax(0, 1fr) calc(20 * var(--u));
        align-items: center; text-decoration: none; border-radius: calc(12 * var(--u));
        padding: calc(15 * var(--u)) calc(13 * var(--u)); background: var(--t); }
    .fq-role .circ { width: calc(52 * var(--u)); height: calc(52 * var(--u)); border-radius: 50%;
        background: var(--k); display: flex; align-items: center; justify-content: center; color: #fff; }
    .fq-role .circ svg { width: calc(27 * var(--u)); height: calc(27 * var(--u)); }
    .fq-role b { display: block; font-size: calc(18 * var(--u)); font-weight: 700; color: var(--k);
        line-height: 1.2; margin-bottom: calc(3 * var(--u)); }
    .fq-role span { display: block; font-size: calc(14 * var(--u)); color: #3f4a63; line-height: 1.4; }
    .fq-role .go { color: var(--k); display: flex; justify-content: flex-end; }
    .fq-role .go svg { width: calc(15 * var(--u)); height: calc(15 * var(--u)); }

    /* ── Browse help topics ───────────────────────────────────── */
    .fq-topics { display: grid; grid-template-columns: repeat(8, minmax(0, 1fr)); gap: calc(11 * var(--u)); }
    .fq-topic { text-decoration: none; background: #fff; border: 1px solid #e7eef8;
        border-radius: calc(12 * var(--u)); padding: calc(16 * var(--u)) calc(6 * var(--u)) calc(14 * var(--u));
        text-align: center; display: block; }
    .fq-topic:hover { border-color: #b9d1f7; }
    .fq-topic svg { width: calc(32 * var(--u)); height: calc(32 * var(--u)); color: var(--k); }
    .fq-topic b { display: block; margin-top: calc(9 * var(--u)); font-size: calc(14.5 * var(--u));
        font-weight: 700; color: #0b1340; line-height: 1.25; }

    /* ── Popular questions ────────────────────────────────────── */
    .fq-cards { display: grid; grid-template-columns: repeat(5, minmax(0, 1fr)); gap: calc(11 * var(--u)); }
    .fq-card { position: relative; text-decoration: none; background: #fff; border: 1px solid #dde9f8;
        border-radius: calc(12 * var(--u)); padding: calc(13 * var(--u)) calc(14 * var(--u)) calc(26 * var(--u));
        display: grid; grid-template-columns: calc(52 * var(--u)) minmax(0, 1fr); column-gap: calc(3 * var(--u)); }
    .fq-card:hover { border-color: #b9d1f7; }
    .fq-card .circ { width: calc(42 * var(--u)); height: calc(42 * var(--u)); border-radius: 50%;
        background: var(--k); color: #fff; display: flex; align-items: center; justify-content: center; }
    .fq-card .circ svg { width: calc(21 * var(--u)); height: calc(21 * var(--u)); }
    .fq-card b { font-size: calc(15.5 * var(--u)); font-weight: 700; color: #0b1340; line-height: 1.2; }
    .fq-card p { grid-column: 2; margin: calc(8 * var(--u)) 0 0; font-size: calc(13.5 * var(--u));
        color: #5a6679; line-height: 1.4; }
    .fq-card .go { position: absolute; right: calc(12 * var(--u)); bottom: calc(10 * var(--u)); color: #1565e8; }
    .fq-card .go svg { width: calc(15 * var(--u)); height: calc(15 * var(--u)); }

    /* ── All questions ────────────────────────────────────────── */
    .fq-sort { border: 1px solid #dfe8f5; background: #fff; border-radius: calc(9 * var(--u));
        padding: calc(8 * var(--u)) calc(13 * var(--u)); font-family: inherit;
        font-size: calc(14.5 * var(--u)); color: #0b1340; }
    .fq-qa { display: grid; grid-template-columns: calc(228 * var(--u)) minmax(0, 1fr);
        gap: calc(13 * var(--u)); background: #fff; border: 1px solid #dde9f8;
        border-radius: calc(13 * var(--u)); padding: calc(12 * var(--u)); }
    .fq-side { display: flex; flex-direction: column; gap: calc(2 * var(--u)); }
    .fq-side button { display: grid; grid-template-columns: calc(32 * var(--u)) minmax(0, 1fr);
        align-items: center; text-align: left; cursor: pointer; background: none; border: 0;
        border-left: calc(3 * var(--u)) solid transparent; border-radius: calc(8 * var(--u));
        padding: calc(10 * var(--u)) calc(9 * var(--u)); font-family: inherit;
        font-size: calc(15.5 * var(--u)); font-weight: 600; color: #2f3b55; }
    .fq-side button svg { width: calc(19 * var(--u)); height: calc(19 * var(--u)); color: var(--k); }
    .fq-side button.on { background: #e4effe; border-left-color: #1565e8; color: #1565e8; font-weight: 700; }

    .fq-list { display: flex; flex-direction: column; gap: calc(7 * var(--u)); }
    .fq-item { border: 1px solid #e7eef8; border-radius: calc(10 * var(--u)); overflow: hidden; background: #fff; }
    .fq-item > summary { list-style: none; cursor: pointer; display: grid;
        grid-template-columns: calc(24 * var(--u)) minmax(0, 1fr) calc(24 * var(--u));
        align-items: center; gap: calc(4 * var(--u));
        padding: calc(11 * var(--u)) calc(15 * var(--u)); font-size: calc(15.5 * var(--u));
        font-weight: 700; color: #0b1340; }
    .fq-item > summary::-webkit-details-marker { display: none; }
    .fq-item > summary svg { width: calc(14 * var(--u)); height: calc(14 * var(--u)); color: #1565e8; }
    .fq-item > summary .sign { justify-self: end; font-size: calc(20 * var(--u)); color: #1565e8;
        line-height: 1; display: flex; align-items: center; }
    .fq-item > summary .sign i { font-style: normal; }
    .fq-item > summary .sign svg { display: none; width: calc(14 * var(--u)); height: calc(14 * var(--u)); }
    .fq-item[open] { border-color: #cfe0fb; }
    .fq-item[open] > summary { background: #e4effe; }
    /* One state indicator, on the right: a plus closed, a chevron up open.
       The chevron on the left used to turn as well, which said the same
       thing twice and disagreed with the file. */
    .fq-item[open] > summary .sign i { display: none; }
    .fq-item[open] > summary .sign svg { display: block; }
    .fq-item .ans { padding: calc(9 * var(--u)) calc(15 * var(--u)) calc(14 * var(--u)) calc(43 * var(--u));
        font-size: calc(15 * var(--u)); color: #4a5670; line-height: 1.55; background: #f4f8ff; }
    .fq-none { padding: calc(24 * var(--u)) calc(15 * var(--u)); font-size: calc(15 * var(--u)); color: #5a6679; }
    .fq-more { align-self: flex-start; cursor: pointer; background: none; border: 0;
        padding: calc(6 * var(--u)) calc(4 * var(--u)); font-family: inherit;
        font-size: calc(14.5 * var(--u)); font-weight: 700; color: #1565e8; }

    /* ── Still need help ──────────────────────────────────────── */
    .fq-help { position: relative; margin-top: calc(20 * var(--u)); background: #fff;
        border: 1px solid #e7eef8; border-radius: calc(13 * var(--u));
        display: grid; grid-template-columns: calc(110 * var(--u)) minmax(0, 1fr) auto;
        align-items: center; gap: calc(12 * var(--u));
        padding: calc(22 * var(--u)) calc(250 * var(--u)) calc(22 * var(--u)) calc(18 * var(--u)); }
    .fq-help .head { display: flex; justify-content: center; color: #1565e8; }
    .fq-help .head svg { width: calc(60 * var(--u)); height: calc(60 * var(--u)); }
    .fq-help h3 { font-size: calc(23 * var(--u)); font-weight: 800; color: #0b1340; margin: 0 0 calc(4 * var(--u)); }
    .fq-help p { font-size: calc(14.5 * var(--u)); color: #5a6679; line-height: 1.45; margin: 0;
        max-width: calc(430 * var(--u)); }
    .fq-help .btns { display: flex; gap: calc(11 * var(--u)); flex-wrap: wrap; }
    .fq-hbtn { display: inline-flex; align-items: center; gap: calc(9 * var(--u)); text-decoration: none;
        font-size: calc(15.5 * var(--u)); font-weight: 700; padding: calc(12 * var(--u)) calc(20 * var(--u));
        border-radius: calc(9 * var(--u)); border: 1px solid transparent; white-space: nowrap; }
    .fq-hbtn svg { width: calc(18 * var(--u)); height: calc(18 * var(--u)); flex: none; }
    .fq-hbtn.solid { background: #1565e8; color: #fff; }
    .fq-hbtn.ghost { background: #fff; color: #1565e8; border-color: #bed5fa; }
    .fq-help .agent { position: absolute; right: calc(-6 * var(--u)); top: 50%;
        transform: translateY(-50%); width: calc(200 * var(--u)); height: calc(150 * var(--u));
        background-image: url('{{ asset('images/faq/agent.png') }}');
        background-size: contain; background-position: right center; background-repeat: no-repeat; }

    /* ── The band above the footer ────────────────────────────── */
    .fq-cta { position: relative; margin-top: calc(14 * var(--u)); overflow: hidden;
        background: linear-gradient(100deg, #dde9fd 0%, #e8f0fe 52%, #eaf2ff 100%); }
    .fq-cta-art { position: absolute; left: 0; top: 0; bottom: 0; width: 29.3%;
        background-image: url('{{ asset('images/faq/cta-left.png') }}');
        background-position: left center; background-size: 100% 100%;
        -webkit-mask-image: linear-gradient(270deg, transparent 0, #000 16%);
        mask-image: linear-gradient(270deg, transparent 0, #000 16%); }
    .fq-cta-script { position: absolute; right: 0; top: 0; bottom: 0; width: 20.7%;
        background-image: url('{{ asset('images/faq/cta-script.png') }}');
        background-position: right center; background-size: 100% 100%;
        -webkit-mask-image: linear-gradient(90deg, transparent 0, #000 12%);
        mask-image: linear-gradient(90deg, transparent 0, #000 12%); }
    .fq-cta-in { position: relative; z-index: 2; text-align: center;
        padding: calc(20 * var(--u)) 0; max-width: calc(620 * var(--u)); margin: 0 auto; }
    .fq-cta h2 { font-size: calc(26 * var(--u)); font-weight: 800; color: #0b1340; margin: 0 0 calc(6 * var(--u)); }
    .fq-cta p { font-size: calc(15 * var(--u)); color: #3f4a63; line-height: 1.45;
        margin: 0 auto calc(16 * var(--u)); max-width: calc(560 * var(--u)); }
    .fq-cta-btns { display: flex; justify-content: center; gap: calc(14 * var(--u)); flex-wrap: wrap; }
    .fq-cbtn { display: inline-flex; align-items: center; gap: calc(10 * var(--u)); text-decoration: none;
        color: #fff; font-size: calc(15.5 * var(--u)); font-weight: 700;
        padding: calc(13 * var(--u)) calc(22 * var(--u)); border-radius: calc(9 * var(--u)); white-space: nowrap; }
    .fq-cbtn svg { width: calc(18 * var(--u)); height: calc(18 * var(--u)); flex: none; }
    .fq-cbtn .go { width: calc(15 * var(--u)); height: calc(15 * var(--u)); }
    .fq-cbtn.orange { background: #f4400a; }
    .fq-cbtn.blue { background: #0d5bea; }

    @media (max-width: 1180px) {
        .fq-topics { grid-template-columns: repeat(4, minmax(0, 1fr)); }
        .fq-cards { grid-template-columns: repeat(3, minmax(0, 1fr)); }
        .fq-roles { grid-template-columns: repeat(2, minmax(0, 1fr)); }
        .fq-help { padding-right: calc(18 * var(--u)); }
        .fq-help .agent { display: none; }
    }
    @media (max-width: 760px) {
        .fq-hero-art, .fq-hero-foot, .fq-cta-art, .fq-cta-script { display: none; }
        .fq-hero-in { max-width: none; }
        .fq-searchrow { max-width: none; }
        .fq-topics, .fq-cards, .fq-roles { grid-template-columns: minmax(0, 1fr); }
        .fq-qa { grid-template-columns: minmax(0, 1fr); }
        .fq-side { flex-direction: row; overflow-x: auto; }
        .fq-help { grid-template-columns: minmax(0, 1fr); text-align: center; }
        .fq-help .btns { justify-content: center; }
    }
</style>
@endpush

@section('content')

@php
    $icon = [
        'rocket'  => '<svg viewBox="0 0 24 24" fill="currentColor"><path d="M14.6 2.4c2.7-.9 5.4-.6 6.5.5s1.4 3.8.5 6.5c-.8 2.4-2.3 4.7-4.3 6.5l-.4.3-.5 3.3a1 1 0 0 1-.5.75l-3 1.7a1 1 0 0 1-1.48-1l.3-2.9-3.3-3.3-2.9.3a1 1 0 0 1-1-1.48l1.7-3a1 1 0 0 1 .75-.5l3.3-.5.3-.4c1.8-2 4.1-3.5 6.5-4.3zm1.3 3.9a1.8 1.8 0 1 0 2.5 2.5 1.8 1.8 0 0 0-2.5-2.5zM6.2 16.2c.6.6.4 1.9-.5 2.8-.7.7-2.6 1.3-3.4 1.5-.2.05-.4-.15-.35-.35.2-.8.8-2.7 1.5-3.4.9-.9 2.2-1.1 2.75-.55z"/></svg>',
        'doc'     => '<svg viewBox="0 0 24 24" fill="currentColor"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8zm-1 7V3.5L18.5 9zM8.3 12.3h7.4a.95.95 0 0 1 0 1.9H8.3a.95.95 0 0 1 0-1.9zm0 3.6h5a.95.95 0 0 1 0 1.9h-5a.95.95 0 0 1 0-1.9z"/></svg>',
        'pencil'  => '<svg viewBox="0 0 24 24" fill="currentColor"><path d="M5 3h9.2l-2.1 2.1H5.3a.3.3 0 0 0-.3.3v13.3c0 .16.14.3.3.3h13.4c.16 0 .3-.14.3-.3v-6.9L21 9.7V19a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2zm13.9-.6 2.7 2.7a1 1 0 0 1 0 1.4l-8.5 8.5a1 1 0 0 1-.45.26l-3.4.85a.6.6 0 0 1-.73-.73l.85-3.4a1 1 0 0 1 .26-.45l8.5-8.5a1 1 0 0 1 1.4 0z"/></svg>',
        'card'    => '<svg viewBox="0 0 24 24" fill="currentColor"><path d="M3.5 5h17A1.5 1.5 0 0 1 22 6.5v11a1.5 1.5 0 0 1-1.5 1.5h-17A1.5 1.5 0 0 1 2 17.5v-11A1.5 1.5 0 0 1 3.5 5zm.4 6.4a1.7 1.7 0 1 0 0 3.4 1.7 1.7 0 0 0 0-3.4zm4.4.35a1.35 1.35 0 0 0 0 2.7h2.1a1.35 1.35 0 0 0 0-2.7z"/></svg>',
        'chat'    => '<svg viewBox="0 0 24 24" fill="currentColor"><path d="M9.6 2.5c4.2 0 7.6 2.7 7.6 6.1s-3.4 6.1-7.6 6.1c-.86 0-1.7-.11-2.47-.32l-3.5 1.5a.5.5 0 0 1-.68-.6l.8-2.7C2.6 11.5 2 10.2 2 8.6 2 5.2 5.4 2.5 9.6 2.5zm7.1 5.3c3 .4 5.3 2.5 5.3 5.1 0 1.3-.6 2.5-1.6 3.4l.7 2.3a.5.5 0 0 1-.68.6l-3-1.3c-.65.17-1.35.26-2.07.26-2.6 0-4.8-1.3-5.6-3.2.3.02.6.03.9.03 4.9 0 8.9-3.3 8.9-7.4 0-.26-.02-.52-.05-.77z"/></svg>',
        'person'  => '<svg viewBox="0 0 24 24" fill="currentColor"><path d="M12 2.6a4.9 4.9 0 1 1 0 9.8 4.9 4.9 0 0 1 0-9.8zm0 11.3c4.4 0 8 2.5 8 5.6 0 1.1-.9 1.9-2 1.9H6c-1.1 0-2-.8-2-1.9 0-3.1 3.6-5.6 8-5.6z"/></svg>',
        'lock'    => '<svg viewBox="0 0 24 24" fill="currentColor"><path d="M12 1.8A5.2 5.2 0 0 0 6.8 7v2.3H6A2.5 2.5 0 0 0 3.5 11.8v7.7A2.5 2.5 0 0 0 6 22h12a2.5 2.5 0 0 0 2.5-2.5v-7.7A2.5 2.5 0 0 0 18 9.3h-.8V7A5.2 5.2 0 0 0 12 1.8zm0 2.4A2.8 2.8 0 0 1 14.8 7v2.3H9.2V7A2.8 2.8 0 0 1 12 4.2z"/></svg>',
        'cal'     => '<svg viewBox="0 0 24 24" fill="currentColor"><path d="M8 2a1 1 0 0 1 1 1v1h6V3a1 1 0 1 1 2 0v1h1.5A2.5 2.5 0 0 1 21 6.5v12a2.5 2.5 0 0 1-2.5 2.5h-13A2.5 2.5 0 0 1 3 18.5v-12A2.5 2.5 0 0 1 5.5 4H7V3a1 1 0 0 1 1-1zM5 10v8.5c0 .3.2.5.5.5h13c.3 0 .5-.2.5-.5V10zm2.6 1.8h2v2h-2zm3.4 0h2v2h-2zm3.4 0h2v2h-2zm-6.8 3.4h2v2h-2zm3.4 0h2v2h-2zm3.4 0h2v2h-2z"/></svg>',
        'dollar'  => '<svg viewBox="0 0 24 24" fill="currentColor"><path d="M12.9 2.6v1.7c1.6.2 2.9.9 3.7 2a1.2 1.2 0 0 1-1.95 1.4c-.6-.8-1.6-1.2-2.95-1.2-1.6 0-2.6.6-2.6 1.55 0 .9.7 1.3 3 1.8 3.1.65 4.6 1.7 4.6 4 0 2.1-1.5 3.6-3.8 4v1.6a1.2 1.2 0 0 1-2.4 0v-1.6c-1.8-.25-3.2-1.05-4-2.3a1.2 1.2 0 0 1 2-1.3c.6.95 1.7 1.4 3.2 1.4 1.8 0 2.8-.65 2.8-1.65 0-.95-.7-1.35-3.1-1.9-3-.65-4.5-1.7-4.5-3.9 0-2 1.4-3.45 3.6-3.85V2.6a1.2 1.2 0 0 1 2.4 0z"/></svg>',
        'brief'   => '<svg viewBox="0 0 24 24" fill="currentColor"><path d="M9 2h6a2.5 2.5 0 0 1 2.5 2.5V6h2A2.5 2.5 0 0 1 22 8.5v10A2.5 2.5 0 0 1 19.5 21h-15A2.5 2.5 0 0 1 2 18.5v-10A2.5 2.5 0 0 1 4.5 6h2V4.5A2.5 2.5 0 0 1 9 2zm.4 2.4a.6.6 0 0 0-.6.6V6h6.4V5a.6.6 0 0 0-.6-.6zM10.8 12a1.2 1.2 0 0 0 0 2.4h2.4a1.2 1.2 0 0 0 0-2.4z"/></svg>',
        'camera'  => '<svg viewBox="0 0 24 24" fill="currentColor"><path d="M9.3 3h5.4a2 2 0 0 1 1.7.95l.75 1.25h2.35A2.5 2.5 0 0 1 22 7.7v10A2.5 2.5 0 0 1 19.5 20h-15A2.5 2.5 0 0 1 2 17.5v-10A2.5 2.5 0 0 1 4.5 5.2h2.35l.75-1.25A2 2 0 0 1 9.3 3zm2.7 4.6a4.9 4.9 0 1 0 0 9.8 4.9 4.9 0 0 0 0-9.8zm0 2.3a2.6 2.6 0 1 1 0 5.2 2.6 2.6 0 0 1 0-5.2z"/></svg>',
        'users'   => '<svg viewBox="0 0 24 24" fill="currentColor"><path d="M8.3 3.4a3.6 3.6 0 1 1 0 7.2 3.6 3.6 0 0 1 0-7.2zm8 1a3 3 0 1 1 0 6 3 3 0 0 1 0-6zM8.3 12.3c3.6 0 6.5 2 6.5 4.6 0 1-.8 1.8-1.8 1.8H3.6c-1 0-1.8-.8-1.8-1.8 0-2.6 2.9-4.6 6.5-4.6zm8 .4c3.1 0 5.5 1.7 5.5 3.9 0 .9-.7 1.6-1.6 1.6h-3.9c.2-.4.3-.85.3-1.3 0-1.7-.85-3.2-2.2-4.1a9 9 0 0 1 1.9-.1z"/></svg>',
        'mega'    => '<svg viewBox="0 0 24 24" fill="currentColor"><path d="M17.6 3.3a1.3 1.3 0 0 1 2.2.95v15.5a1.3 1.3 0 0 1-2.2.95l-3.9-3.7H12v3.4a1.6 1.6 0 0 1-1.6 1.6H8.2a1.6 1.6 0 0 1-1.6-1.6V17H5A3 3 0 0 1 2 14v-4.1a3 3 0 0 1 3-3h8.7zM8.9 17v2.6h1.1V17z"/></svg>',
    ];

    /* The eight topics her file draws, in her order, with her icons. The
       questions already written were moved into them on 6 November, so the
       labels below and the `category` column are the same eight words. A
       topic nobody has written a question for yet says so rather than being
       filled with invented policy. */
    $topics = [
        ['Getting Started',        'rocket', '#1565e8'],
        ['Requests & Proposals',   'doc',    '#fb4404'],
        ['Agreements & Contracts', 'pencil', '#9a1ae8'],
        ['Payments & Payouts',     'card',   '#0f9d6b'],
        ['Messaging',              'chat',   '#1565e8'],
        ['Account & Settings',     'person', '#fb4404'],
        ['Trust & Safety',         'lock',   '#fa035f'],
        ['Events & Bookings',      'cal',    '#9a1ae8'],
    ];

    $byCat = collect($topics)->mapWithKeys(fn ($t) => [$t[0] => $grouped[$t[0]] ?? collect()]);

    /* Her five, each pointing at something real: a question that exists, or
       the page that answers it. Nothing here invents an answer. */
    $find = fn (string $needle) => $faqs->first(fn ($f) => str_contains(strtolower($f->question), $needle));

    $popular = [
        ['How do I create an account?', 'Learn how to sign up as a Client, Professional, Influencer, or Affiliate.', 'users',  '#fb4404', $find('create an account')],
        ['How do I post a request?',    'Step-by-step guide to creating a Single or Multi-Service Request.',        'doc',    '#1565e8', $find('multiple services')],
        ['What does GigResource cost?', 'See our service fees, commissions, and payment options.',                  'dollar', '#1565e8', $find('fee to post')],
        ['How do I message a user?',    'Find out the messaging rules and what each role can chat with.',           'chat',   '#9a1ae8', null],
        ['How do bookings and payments work?', 'Understand the steps from agreement to payment and booking.',       'cal',    '#fa035f', $find('payment work when i book')],
    ];

    /* Her five pills, all of them. They were filtered to the words that
       match something somebody wrote, which quietly dropped the fifth:
       nobody has written a question containing "how it works" yet. A pill
       that finds nothing now says so, which is the honest half of this,
       and the missing fifth is reported rather than hidden. */
    $terms = collect(['pricing', 'payments', 'proposals', 'cancellation', 'how it works']);

    /* The roles somebody can sign up as. All four exist here: Affiliate has
       its own AFF- reference, its own sign-in and its own signup. The
       colours are the table Sir Peter set in stone (OA-164), which every
       avatar and badge reads, so these cannot drift from the rest. */
    $tint = fn (string $r) => \App\Support\RoleColours::ROLES[$r][0];
    $key  = fn (string $r) => \App\Support\RoleColours::ROLES[$r][1];

    $roles = [
        ['Client', 'Plan and hire event services.', 'users', 'client', route('register', ['role' => 'client'])],
        ['Professional', 'Provide event services, submit bids, and grow your business.', 'brief', 'professional', route('register', ['role' => 'professional'])],
        ['Influencer', 'Create and share event experiences.', 'camera', 'influencer', route('register', ['role' => 'influencer'])],
        ['Affiliate', 'Refer new users and earn commissions.', 'mega', 'affiliate', route('influencer.join')],
    ];

    $chev  = '<svg class="chev" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><path d="m9 5 7 7-7 7"/></svg>';
    $up    = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><path d="M5 15.5 12 8.5 19 15.5"/></svg>';
    $right = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.6" stroke-linecap="round" stroke-linejoin="round"><path d="m9 5 7 7-7 7"/></svg>';
    $arrow = '<svg class="go" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.6" stroke-linecap="round" stroke-linejoin="round"><path d="M4 12h15"/><path d="m13 6 6 6-6 6"/></svg>';
@endphp

<div class="fq-page">

{{-- ── Hero, search inside it ───────────────────────────────── --}}
<section class="fq-hero">
    <div class="fq-hero-art" role="presentation"></div>
    <div class="fq-hero-foot" role="presentation"></div>
    <div class="lp-container">
        <div class="fq-hero-in">
            <span class="fq-eyebrow">FAQ</span>
            <h1>Questions?<br><span class="o">We&rsquo;ve Got</span> <span class="b">Answers.</span></h1>
            <p>Find quick answers to the most common questions about using GigResource. If you need more help, our support team is always here for you.</p>

            <div class="fq-searchrow">
                <span class="ic" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.6" stroke-linecap="round"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.6-3.6"/></svg>
                </span>
                <label for="fqSearch" style="position:absolute;left:-9999px;">Search questions</label>
                <input id="fqSearch" type="search" placeholder="Search questions, keywords, or topics..." autocomplete="off">
                <button type="button" id="fqSearchGo">Search</button>
            </div>

            @if($terms->isNotEmpty())
                <div class="fq-pop">
                    <b>Popular searches:</b>
                    @foreach($terms as $term)
                        <button type="button" data-term="{{ $term }}">{{ $term }}</button>
                    @endforeach
                </div>
            @endif
        </div>
    </div>
</section>

<div class="fq-body">
<div class="lp-container">

    {{-- ── I am a… ──────────────────────────────────────────── --}}
    <section class="fq-sec">
        <h2>I am a&hellip;</h2>
        <p class="lede">Get answers tailored to your experience on GigResource.</p>
        <div class="fq-roles">
            @foreach($roles as [$name, $blurb, $ic, $role, $href])
                <a class="fq-role" style="--k: {{ $key($role) }}; --t: {{ $tint($role) }};" href="{{ $href }}">
                    <span class="circ" aria-hidden="true">{!! $icon[$ic] !!}</span>
                    <span>
                        <b>{{ $name }}</b>
                        <span>{{ $blurb }}</span>
                    </span>
                    <span class="go" aria-hidden="true">{!! $right !!}</span>
                </a>
            @endforeach
        </div>
    </section>

    {{-- ── Browse help topics ───────────────────────────────── --}}
    <section class="fq-sec">
        <h2>Browse Help Topics</h2>
        <p class="lede">Select a topic to find answers to common questions.</p>
        <div class="fq-topics">
            @foreach($topics as [$cat, $ic, $k])
                <a class="fq-topic" style="--k: {{ $k }};" href="#fqAll" data-jump="{{ $cat }}">
                    {!! $icon[$ic] !!}
                    <b>{{ $cat }}</b>
                </a>
            @endforeach
        </div>
    </section>

    {{-- ── Popular questions ────────────────────────────────── --}}
    <section class="fq-sec">
        <div class="fq-sechead">
            <div>
                <h2>Popular Questions</h2>
                <p class="lede">Quick answers to our most frequently asked questions.</p>
            </div>
            <a class="fq-viewall" href="#fqAll">View All Questions {!! $right !!}</a>
        </div>
        <div class="fq-cards">
            @foreach($popular as [$title, $blurb, $ic, $k, $faq])
                {{-- Where a question of this name exists it opens that one;
                     where it does not, the card goes to the page that does
                     answer it rather than to an answer nobody has written. --}}
                <a class="fq-card" style="--k: {{ $k }};"
                   @if($faq) href="#fqAll" data-open="{{ $faq->id }}"
                   @elseif($ic === 'dollar') href="{{ route('pricing-and-rates') }}"
                   @else href="#fqAll" data-jump="Messaging" @endif>
                    <span class="circ" aria-hidden="true">{!! $icon[$ic] !!}</span>
                    <b>{{ $title }}</b>
                    <p>{{ $blurb }}</p>
                    <span class="go" aria-hidden="true">{!! $right !!}</span>
                </a>
            @endforeach
        </div>
    </section>

    {{-- ── All questions ────────────────────────────────────── --}}
    <section class="fq-sec" id="fqAll">
        <div class="fq-sechead">
            <div>
                <h2>All Questions &amp; Answers</h2>
                <p class="lede">Browse questions by topic or use the search bar above.</p>
            </div>
            <label>
                <span style="position:absolute;left:-9999px;">Order questions</span>
                <select class="fq-sort" id="fqSort">
                    <option value="popular">Popular First</option>
                    <option value="az">A to Z</option>
                </select>
            </label>
        </div>

        <div class="fq-qa">
            <div class="fq-side" role="tablist">
                @foreach($topics as $i => [$cat, $ic, $k])
                    <button type="button" role="tab" style="--k: {{ $k }};"
                            class="{{ $i === 0 ? 'on' : '' }}"
                            aria-selected="{{ $i === 0 ? 'true' : 'false' }}"
                            data-cat="{{ $cat }}">
                        {!! $icon[$ic] !!}
                        <span>{{ $cat }}</span>
                    </button>
                @endforeach
            </div>

            <div class="fq-list" id="fqList">
                @foreach($topics as $i => [$cat, $ic, $k])
                    @foreach($byCat[$cat] as $j => $faq)
                        {{-- Her file shows eight under a topic. The ninth and
                             anything after it is behind "Show all", not hidden:
                             it is in the page, the search still reads it, and
                             one click brings it up. --}}
                        <details class="fq-item" data-cat="{{ $cat }}" data-id="{{ $faq->id }}"
                                 data-extra="{{ $j >= 8 ? 1 : 0 }}"
                                 data-q="{{ \Illuminate\Support\Str::lower($faq->question . ' ' . strip_tags($faq->answer)) }}"
                                 @if($i !== 0 || $j >= 8) hidden @endif
                                 @if($i === 0 && $j === 0) open @endif>
                            <summary>
                                {!! $chev !!}
                                <span>{{ $faq->question }}</span>
                                <span class="sign" aria-hidden="true"><i>+</i>{!! $up !!}</span>
                            </summary>
                            <div class="ans">{!! nl2br(e($faq->answer)) !!}</div>
                        </details>
                    @endforeach
                @endforeach
                {{-- Two of her eight topics have no question written for them
                     yet. Saying so is better than filling them in. --}}
                <p class="fq-none" id="fqNone" hidden>Nothing here yet. Try another topic, another word, or contact support below.</p>
                <button type="button" class="fq-more" id="fqMore" hidden></button>
            </div>
        </div>
    </section>

    {{-- ── Still need help ──────────────────────────────────── --}}
    <div class="fq-help">
        <span class="head" aria-hidden="true">
            <svg viewBox="0 0 24 24" fill="currentColor"><path d="M12 2.2A9.3 9.3 0 0 0 2.7 11.5v5.1A3.4 3.4 0 0 0 6.1 20h.8a1.6 1.6 0 0 0 1.6-1.6v-5.2a1.6 1.6 0 0 0-1.6-1.6H5.1v-.1a6.9 6.9 0 1 1 13.8 0v.1h-1.8a1.6 1.6 0 0 0-1.6 1.6v5.2a1.6 1.6 0 0 0 1.6 1.6h1.4a4.6 4.6 0 0 1-3.9 2.2h-1.2a1.15 1.15 0 0 0 0 2.3h1.2a6.9 6.9 0 0 0 6.7-5.2c.07-.3.1-.6.1-.9v-6.4A9.3 9.3 0 0 0 12 2.2z"/></svg>
        </span>
        <div>
            <h3>Still Need Help?</h3>
            <p>Our support team is here to help. Get in touch or visit our Help Center for guides, tips, and detailed resources.</p>
        </div>
        <div class="btns">
            {{-- The support form, not the about page: a button that says
                 Contact Support and opens something else is the fault this
                 project already fixed once. --}}
            <a href="{{ \App\Domain\Forms\FormRegistry::url('support_request') }}" class="fq-hbtn solid">
                <svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M12 2.6c5.2 0 9.4 3.5 9.4 7.9s-4.2 7.9-9.4 7.9c-1 0-2-.13-2.9-.37l-4.2 1.8a.6.6 0 0 1-.82-.72l1-3.3c-1.3-1.45-2.08-3.26-2.08-5.3 0-4.4 4.2-7.9 9.4-7.9z"/></svg>
                Contact Support
            </a>
            <a class="fq-hbtn ghost" href="{{ route('public.how-it-works') }}">
                <svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M8 2a1 1 0 0 1 1 1v1h6V3a1 1 0 1 1 2 0v1h1.5A2.5 2.5 0 0 1 21 6.5v12a2.5 2.5 0 0 1-2.5 2.5h-13A2.5 2.5 0 0 1 3 18.5v-12A2.5 2.5 0 0 1 5.5 4H7V3a1 1 0 0 1 1-1zM5 10v8.5c0 .3.2.5.5.5h13c.3 0 .5-.2.5-.5V10z"/></svg>
                Visit Help Center
            </a>
        </div>
        <span class="agent" role="presentation"></span>
    </div>
</div>
</div>

{{-- ── The band above the footer ────────────────────────────── --}}
<section class="fq-cta">
    <div class="fq-cta-art" role="presentation"></div>
    <div class="fq-cta-script" role="presentation"></div>
    <div class="lp-container">
        <div class="fq-cta-in">
            <h2>Ready to Get Started with GigResource?</h2>
            <p>Find event services or offer your services through the GigResource marketplace.</p>
            <div class="fq-cta-btns">
                <a class="fq-cbtn orange" href="{{ auth()->check() ? route('client.post-event.event-info') : route('register') }}">
                    <svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M8 2a1 1 0 0 1 1 1v1h6V3a1 1 0 1 1 2 0v1h1.5A2.5 2.5 0 0 1 21 6.5v12a2.5 2.5 0 0 1-2.5 2.5h-13A2.5 2.5 0 0 1 3 18.5v-12A2.5 2.5 0 0 1 5.5 4H7V3a1 1 0 0 1 1-1zM5 10v8.5c0 .3.2.5.5.5h13c.3 0 .5-.2.5-.5V10z"/></svg>
                    Post an Event {!! $arrow !!}
                </a>
                <a class="fq-cbtn blue" href="{{ route('register', ['role' => 'professional']) }}">
                    {!! $icon['brief'] !!}
                    Join as Professional {!! $arrow !!}
                </a>
            </div>
        </div>
    </div>
</section>

</div>

<script>
(function () {
    var list  = document.getElementById('fqList');
    if (!list) { return; }

    var items = Array.prototype.slice.call(list.querySelectorAll('.fq-item'));
    var tabs  = Array.prototype.slice.call(document.querySelectorAll('.fq-side button'));
    var none  = document.getElementById('fqNone');
    var input = document.getElementById('fqSearch');

    function current() {
        var on = document.querySelector('.fq-side button.on');
        return on ? on.getAttribute('data-cat') : null;
    }

    // One rule for what is on screen: a search beats the chosen topic,
    // because somebody who typed a word wants that word, not that topic.
    var more = document.getElementById('fqMore');
    var all  = false;   // has the reader asked for the rest of this topic?

    function apply() {
        var q   = (input.value || '').trim().toLowerCase();
        var cat = current();
        var seen = 0, held = 0;

        items.forEach(function (el) {
            var mine = q
                ? el.getAttribute('data-q').indexOf(q) !== -1
                : el.getAttribute('data-cat') === cat;

            // A search reads every question, including the held-back ones.
            var hold = !q && !all && el.getAttribute('data-extra') === '1';

            el.hidden = !mine || hold;
            if (mine && hold) { held++; }
            if (mine && !hold) { seen++; }
        });

        none.hidden = seen > 0 || held > 0;
        more.hidden = held === 0;
        more.textContent = held === 0 ? '' : 'Show all ' + (seen + held) + ' questions';
    }

    more.addEventListener('click', function () { all = true; apply(); });

    function choose(cat) {
        tabs.forEach(function (b) {
            var on = b.getAttribute('data-cat') === cat;
            b.classList.toggle('on', on);
            b.setAttribute('aria-selected', on ? 'true' : 'false');
        });
        input.value = '';
        all = false;
        apply();
    }

    tabs.forEach(function (btn) {
        btn.addEventListener('click', function () { choose(btn.getAttribute('data-cat')); });
    });

    input.addEventListener('input', apply);
    document.getElementById('fqSearchGo').addEventListener('click', apply);
    input.addEventListener('keydown', function (e) {
        if (e.key === 'Enter') { e.preventDefault(); apply(); }
    });

    document.querySelectorAll('.fq-pop button').forEach(function (pill) {
        pill.addEventListener('click', function () {
            input.value = pill.getAttribute('data-term');
            apply();
            document.getElementById('fqAll').scrollIntoView({ behavior: 'smooth' });
        });
    });

    document.querySelectorAll('[data-jump]').forEach(function (card) {
        card.addEventListener('click', function () { choose(card.getAttribute('data-jump')); });
    });

    document.querySelectorAll('[data-open]').forEach(function (card) {
        card.addEventListener('click', function () {
            var want = list.querySelector('.fq-item[data-id="' + card.getAttribute('data-open') + '"]');
            if (!want) { return; }
            choose(want.getAttribute('data-cat'));
            if (want.hidden) { all = true; apply(); }
            items.forEach(function (el) { el.open = el === want; });
        });
    });

    // The design file shows one answer open and the rest closed.
    list.addEventListener('toggle', function (e) {
        if (e.target.open) {
            items.forEach(function (el) { if (el !== e.target) { el.open = false; } });
        }
    }, true);

    // Once on load: the markup renders the first topic and holds back its
    // extras, but only this knows whether there are any to offer.
    apply();

    document.getElementById('fqSort').addEventListener('change', function () {
        var az = this.value === 'az';
        var sorted = items.slice().sort(function (a, b) {
            if (!az) { return items.indexOf(a) - items.indexOf(b); }
            return a.querySelector('summary span').textContent
                .localeCompare(b.querySelector('summary span').textContent);
        });
        sorted.forEach(function (el) { list.insertBefore(el, none); });
    });
})();
</script>
@endsection
