@extends('layouts.professional')

@section('title', 'Create a Gig')
@section('page-title', 'Create a New Gig')
@section('page-subtitle', 'Post a gig and let AI help you shape a listing that attracts the right clients.')

{{-- Rich single-page gig builder — PROFESSIONAL (dark) portal. Mirrors the
     client builder but themed with the professional CSS variables and the
     pink accent (--bb #2563eb). The top step bar is anchor-nav that scrolls to
     each section. Core fields (title, description, starts_at, ends_at,
     category_ids[], location, budget) submit to professional.gigs.store; the
     extra guests / time / venue / upload inputs are cosmetic UI-only.
     A sticky Live Preview updates as you type. --}}

@push('styles')
<style>
    .pg { --bb: #2563eb; --bb-strong: #1d4ed8; --bb-soft: rgba(37,99,235,.12); --ai: #16a34a; }

    /* ---- Page header ---- */
    .pg-head { display: flex; align-items: flex-start; justify-content: space-between; gap: 16px; flex-wrap: wrap; margin-bottom: 22px; }
    .pg-head h1 { font-size: 24px; font-weight: 800; color: var(--text-primary); letter-spacing: -.5px; }
    .pg-head p { font-size: 13.5px; color: var(--text-muted); margin-top: 5px; max-width: 560px; }

    .pg-btn { border: none; border-radius: 11px; padding: 11px 20px; font-size: 13.5px; font-weight: 800; cursor: pointer; display: inline-flex; align-items: center; gap: 8px; font-family: inherit; text-decoration: none; }
    .pg-btn svg { width: 16px; height: 16px; }
    .pg-btn.primary { background: linear-gradient(135deg, var(--bb), var(--bb-strong)); color: #fff; }
    .pg-btn.primary:hover { transform: translateY(-1px); box-shadow: 0 8px 22px rgba(37,99,235,.28); }
    .pg-btn.ghost { background: var(--bg-card); color: var(--text-secondary); border: 1.5px solid var(--border-color); }
    .pg-btn.ghost:hover { border-color: var(--bb); color: var(--bb); }
    .pg-btn.block { width: 100%; justify-content: center; }

    /* ---- Step bar (anchor nav) ---- */
    .pg-steps { display: flex; gap: 4px; overflow-x: auto; padding: 4px 2px 0; margin-bottom: 24px; border-bottom: 1px solid var(--border-color); }
    .pg-steps::-webkit-scrollbar { height: 5px; } .pg-steps::-webkit-scrollbar-thumb { background: var(--border-color); border-radius: 999px; }
    .pg-step { flex: 1 0 auto; display: inline-flex; align-items: center; gap: 8px; padding: 10px 14px 13px; font-size: 12.5px; font-weight: 700; color: var(--text-muted); border-bottom: 2.5px solid transparent; margin-bottom: -1px; white-space: nowrap; text-decoration: none; cursor: pointer; transition: all .15s; }
    .pg-step:hover { color: var(--text-secondary); }
    .pg-step .n { width: 22px; height: 22px; border-radius: 50%; background: var(--bg-card); border: 1.5px solid var(--border-color); display: inline-flex; align-items: center; justify-content: center; font-size: 11px; font-weight: 800; color: var(--text-muted); }
    .pg-step.active { color: var(--bb); border-bottom-color: var(--bb); }
    .pg-step.active .n { background: var(--bb); border-color: var(--bb); color: #fff; }

    /* ---- Layout ---- */
    .pg-grid { display: grid; grid-template-columns: 1fr 360px; gap: 22px; align-items: start; }
    @media (max-width: 980px) { .pg-grid { grid-template-columns: 1fr; } .pg-aside { position: static !important; } }
    .pg-main { display: flex; flex-direction: column; gap: 18px; min-width: 0; }
    .pg-aside { position: sticky; top: 18px; display: flex; flex-direction: column; gap: 16px; }

    /* ---- Cards ---- */
    .pg-card { background: var(--bg-card); border: 1px solid var(--border-color); border-radius: var(--radius, 16px); padding: 22px 24px; scroll-margin-top: 90px; }
    .pg-card-hd { display: flex; align-items: center; gap: 10px; margin-bottom: 4px; }
    .pg-card-hd h3 { font-size: 16px; font-weight: 800; color: var(--text-primary); }
    .pg-card-hd .ic { width: 34px; height: 34px; border-radius: 10px; background: var(--bb-soft); color: var(--bb); display: inline-flex; align-items: center; justify-content: center; font-size: 17px; flex-shrink: 0; }
    .pg-card-sub { font-size: 12.5px; color: var(--text-muted); margin: 0 0 18px; }

    .pg-label { display: block; font-size: 12.5px; font-weight: 700; color: var(--text-secondary); margin: 0 0 7px; }
    .pg-req { color: var(--bb); }
    .pg-input, .pg-textarea, .pg-select { width: 100%; border: 1.5px solid var(--border-color); border-radius: 11px; padding: 11px 13px; font-size: 14px; color: var(--text-primary); background: var(--bg-secondary, var(--bg-card)); font-family: inherit; transition: border-color .15s, box-shadow .15s; }
    .pg-input:focus, .pg-textarea:focus, .pg-select:focus { outline: none; border-color: var(--bb); box-shadow: 0 0 0 3px rgba(37,99,235,.18); }
    .pg-textarea { resize: vertical; min-height: 150px; line-height: 1.55; }
    .pg-field { margin-bottom: 16px; }
    .pg-field:last-child { margin-bottom: 0; }
    .pg-row { display: grid; grid-template-columns: 1fr 1fr; gap: 14px; }
    @media (max-width: 520px) { .pg-row { grid-template-columns: 1fr; } }
    .pg-prefix { display: flex; align-items: stretch; }
    .pg-prefix .sym { flex-shrink: 0; width: 44px; display: flex; align-items: center; justify-content: center; background: var(--bb-soft); color: var(--bb); font-weight: 800; font-size: 15px; border: 1.5px solid var(--border-color); border-right: none; border-radius: 11px 0 0 11px; }
    .pg-prefix .pg-input { border-radius: 0 11px 11px 0; }

    /* category chips */
    .pg-chips { display: flex; flex-wrap: wrap; gap: 9px; }
    .pg-chip { display: inline-flex; align-items: center; gap: 7px; border: 1.5px solid var(--border-color); border-radius: 10px; padding: 8px 13px; font-size: 13px; font-weight: 700; color: var(--text-secondary); background: var(--bg-secondary, var(--bg-card)); cursor: pointer; user-select: none; transition: all .12s; }
    .pg-chip:hover { border-color: var(--bb); }
    .pg-chip.sel { border-color: var(--bb); background: var(--bb-soft); color: var(--bb); }
    .pg-chip .tick { display: none; font-size: 12px; } .pg-chip.sel .tick { display: inline; }

    /* AI assistant sub-panel */
    .pg-ai { border: 1px dashed var(--bb); background: linear-gradient(135deg, rgba(37,99,235,.08), rgba(190,18,60,.04)); border-radius: 13px; padding: 15px 16px; margin-bottom: 16px; }
    .pg-ai-hd { display: flex; align-items: center; gap: 8px; font-size: 12.5px; font-weight: 800; color: var(--bb); text-transform: uppercase; letter-spacing: .4px; margin-bottom: 10px; }
    .pg-ai-list { list-style: none; margin: 0 0 13px; padding: 0; display: flex; flex-direction: column; gap: 7px; }
    .pg-ai-list li { font-size: 12.5px; color: var(--text-secondary); display: flex; gap: 8px; line-height: 1.45; }
    .pg-ai-list li::before { content: '\2726'; color: var(--bb); flex-shrink: 0; }

    /* upload tiles */
    .pg-uploads { display: grid; grid-template-columns: repeat(auto-fill, minmax(96px, 1fr)); gap: 11px; }
    .pg-tile { aspect-ratio: 1; border-radius: 12px; border: 1.5px solid var(--border-color); display: flex; align-items: center; justify-content: center; font-size: 24px; color: var(--text-muted); overflow: hidden; position: relative; }
    .pg-tile.ph { background: linear-gradient(135deg, rgba(37,99,235,.12), rgba(37,99,235,.04)); }
    .pg-tile.add { border-style: dashed; cursor: pointer; flex-direction: column; gap: 4px; font-size: 22px; }
    .pg-tile.add:hover { border-color: var(--bb); color: var(--bb); }
    .pg-tile.add span { font-size: 10.5px; font-weight: 700; }
    .pg-tile.add input { position: absolute; inset: 0; opacity: 0; cursor: pointer; }
    .pg-upload-row { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-top: 14px; }
    @media (max-width: 520px) { .pg-upload-row { grid-template-columns: 1fr; } }
    .pg-drop { border: 1.5px dashed var(--border-color); border-radius: 12px; padding: 14px; text-align: center; font-size: 12px; font-weight: 700; color: var(--text-muted); position: relative; }
    .pg-drop:hover { border-color: var(--bb); color: var(--bb); }
    .pg-drop input { position: absolute; inset: 0; opacity: 0; cursor: pointer; }
    .pg-drop b { display: block; color: var(--text-secondary); font-size: 12.5px; margin-bottom: 2px; }

    /* budget slider */
    .pg-range { width: 100%; margin-top: 6px; accent-color: var(--bb); }

    /* advanced options */
    .pg-adv { border: 1px solid var(--border-color); border-radius: 12px; overflow: hidden; }
    .pg-adv summary { list-style: none; cursor: pointer; padding: 14px 18px; font-size: 13.5px; font-weight: 800; color: var(--text-primary); display: flex; align-items: center; justify-content: space-between; }
    .pg-adv summary::-webkit-details-marker { display: none; }
    .pg-adv summary .chev { transition: transform .2s; }
    .pg-adv[open] summary .chev { transform: rotate(180deg); }
    .pg-adv-body { padding: 4px 18px 18px; border-top: 1px solid var(--border-color); }
    .pg-toggle { display: flex; align-items: center; justify-content: space-between; gap: 12px; padding: 11px 0; border-bottom: 1px solid var(--border-color); }
    .pg-toggle:last-child { border-bottom: none; }
    .pg-toggle b { display: block; font-size: 13px; font-weight: 800; color: var(--text-primary); }
    .pg-toggle span { font-size: 11.5px; color: var(--text-muted); }
    .pg-sw { width: 42px; height: 24px; border-radius: 999px; background: var(--border-color); position: relative; cursor: pointer; flex-shrink: 0; transition: background .15s; }
    .pg-sw::after { content: ''; position: absolute; top: 2px; left: 2px; width: 20px; height: 20px; border-radius: 50%; background: #fff; transition: left .15s; box-shadow: 0 1px 3px rgba(0,0,0,.25); }
    .pg-sw.on { background: var(--bb); } .pg-sw.on::after { left: 20px; }

    .pg-actions { display: flex; align-items: center; justify-content: flex-end; gap: 12px; flex-wrap: wrap; }

    .pg-err { color: #f87171; font-size: 12.5px; font-weight: 700; margin-top: 8px; }

    /* ---- Live preview ---- */
    .pg-pv-hd h4 { font-size: 15px; font-weight: 800; color: var(--text-primary); }
    .pg-pv-hd p { font-size: 12px; color: var(--text-muted); margin-top: 3px; }
    .pg-pvcard { border: 1px solid var(--border-color); border-radius: 14px; overflow: hidden; background: var(--bg-secondary, var(--bg-card)); margin-top: 14px; }
    .pg-pv-cover { height: 84px; background: linear-gradient(135deg, var(--bb), var(--bb-strong)); position: relative; display: flex; align-items: center; justify-content: center; font-size: 30px; }
    .pg-pv-ring { position: absolute; right: 12px; bottom: -20px; width: 52px; height: 52px; border-radius: 50%; background: var(--bg-card); border: 1px solid var(--border-color); display: flex; align-items: center; justify-content: center; }
    .pg-pv-ring svg { width: 46px; height: 46px; transform: rotate(-90deg); }
    .pg-pv-ring .pct { position: absolute; font-size: 11px; font-weight: 800; color: var(--bb); }
    .pg-pv-body { padding: 26px 16px 16px; }
    .pg-pv-title { font-size: 15px; font-weight: 800; color: var(--text-primary); line-height: 1.3; }
    .pg-pv-tags { display: flex; flex-wrap: wrap; gap: 6px; margin: 9px 0; }
    .pg-pv-tag { font-size: 10.5px; font-weight: 700; color: var(--bb); background: var(--bb-soft); padding: 3px 9px; border-radius: 999px; }
    .pg-pv-meta { font-size: 11.5px; color: var(--text-muted); display: flex; align-items: center; gap: 6px; flex-wrap: wrap; }
    .pg-pv-desc { font-size: 12px; color: var(--text-secondary); line-height: 1.5; margin: 10px 0; display: -webkit-box; -webkit-line-clamp: 3; -webkit-box-orient: vertical; overflow: hidden; }
    .pg-pv-budget { font-size: 14px; font-weight: 800; color: var(--ai); }
    .pg-pv-badges { display: flex; gap: 8px; margin-top: 12px; padding-top: 12px; border-top: 1px solid var(--border-color); flex-wrap: wrap; }
    .pg-pv-badge { font-size: 10.5px; font-weight: 700; color: var(--text-secondary); display: inline-flex; align-items: center; gap: 4px; }
    .pg-pv-badge svg { width: 13px; height: 13px; color: var(--ai); }
    .pg-pv-actions { margin-top: 16px; display: flex; flex-direction: column; gap: 9px; align-items: center; }
    .pg-editlink { font-size: 12.5px; font-weight: 700; color: var(--text-muted); background: none; border: none; cursor: pointer; text-decoration: underline; font-family: inherit; }
    .pg-editlink:hover { color: var(--bb); }

    /* ---- AI tools row ---- */
    .pg-tools { margin-top: 26px; }
    .pg-tools h3 { font-size: 17px; font-weight: 800; color: var(--text-primary); margin-bottom: 4px; }
    .pg-tools p { font-size: 13px; color: var(--text-muted); margin-bottom: 16px; }
    .pg-tools-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(220px, 1fr)); gap: 14px; }
    .pg-tool { background: var(--bg-card); border: 1px solid var(--border-color); border-radius: 14px; padding: 18px; text-decoration: none; display: flex; flex-direction: column; transition: all .15s; }
    .pg-tool:hover { border-color: var(--bb); transform: translateY(-2px); box-shadow: 0 10px 26px rgba(37,99,235,.14); }
    .pg-tool .tic { width: 40px; height: 40px; border-radius: 11px; background: var(--bb-soft); color: var(--bb); display: inline-flex; align-items: center; justify-content: center; font-size: 20px; margin-bottom: 12px; }
    .pg-tool b { font-size: 14px; font-weight: 800; color: var(--text-primary); }
    .pg-tool span { font-size: 12px; color: var(--text-muted); line-height: 1.5; margin: 5px 0 12px; flex: 1; }
    .pg-tool .use { font-size: 12.5px; font-weight: 800; color: var(--bb); display: inline-flex; align-items: center; gap: 5px; }
</style>
@endpush

@section('content')
<div class="pg" id="pgRoot">

    {{-- A) Page header --}}
    <div class="pg-head">
        <div>
            <h1>Create a New Gig</h1>
            <p>Post a gig and let AI help you shape a listing that attracts the right clients.</p>
        </div>
        <button type="button" class="pg-btn ghost" onclick="document.getElementById('pgForm').submit();">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/><polyline points="17 21 17 13 7 13 7 21"/><polyline points="7 3 7 8 15 8"/></svg>
            Save Draft
        </button>
    </div>

    {{-- B) Step bar (anchor nav) --}}
    @php
        $pgSteps = [
            ['basics', 'Basics'],
            ['describe', 'Describe Your Project'],
            ['budget', 'Budget'],
            ['timeline', 'Timeline'],
            ['vendors', 'Vendors'],
            ['requirements', 'Requirements'],
            ['review', 'Review'],
            ['publish', 'Publish'],
        ];
    @endphp
    <div class="pg-steps" id="pgSteps">
        @foreach($pgSteps as $i => [$anchor, $label])
            <a href="#pg-{{ $anchor }}" class="pg-step {{ $anchor === 'describe' ? 'active' : '' }}" data-anchor="pg-{{ $anchor }}">
                <span class="n">{{ $i + 1 }}</span>{{ $label }}
            </a>
        @endforeach
    </div>

    <form method="POST" action="{{ route('professional.gigs.store') }}" id="pgForm" enctype="multipart/form-data">
        @csrf

        <div class="pg-grid">
            {{-- ============ LEFT COLUMN ============ --}}
            <div class="pg-main">

                {{-- 1 · Basics --}}
                <section class="pg-card" id="pg-basics">
                    <div class="pg-card-hd"><span class="ic">📋</span><h3>Basics</h3></div>
                    <p class="pg-card-sub">The essentials about your gig.</p>

                    <div class="pg-field">
                        <label class="pg-label">Gig Title <span class="pg-req">*</span></label>
                        <input type="text" name="title" id="pgTitle" class="pg-input" required maxlength="255"
                               placeholder="e.g. Wedding Photography Package for June Celebrations" value="{{ old('title') }}">
                        @error('title') <div class="pg-err">{{ $message }}</div> @enderror
                    </div>

                    <div class="pg-field">
                        <label class="pg-label">Category</label>
                        <div class="pg-chips" id="pgCats">
                            @foreach($categories as $cat)
                                <label class="pg-chip">
                                    <input type="checkbox" name="category_ids[]" value="{{ $cat->id }}" hidden>
                                    @if(!empty($cat->icon))<span>{{ $cat->icon }}</span>@endif
                                    <span class="lbl">{{ $cat->name }}</span>
                                    <span class="tick">✓</span>
                                </label>
                            @endforeach
                        </div>
                    </div>

                    <div class="pg-row">
                        <div class="pg-field" style="margin-bottom:0;">
                            <label class="pg-label">Location</label>
                            <input type="text" name="location" id="pgLocation" class="pg-input" placeholder="e.g. Miami, FL" value="{{ old('location') }}">
                        </div>
                        <div class="pg-field" style="margin-bottom:0;">
                            <label class="pg-label">Venue</label>
                            <input type="text" name="venue" id="pgVenue" class="pg-input" placeholder="e.g. Beach Palace Hotel" value="{{ old('venue') }}">
                        </div>
                    </div>
                </section>

                {{-- 2 · Describe Your Project --}}
                <section class="pg-card" id="pg-describe">
                    <div class="pg-card-hd"><span class="ic">📝</span><h3>Describe Your Project</h3></div>
                    <p class="pg-card-sub">Tell clients what you offer. Not sure where to start? Let the AI assistant draft it.</p>

                    <div class="pg-ai">
                        <div class="pg-ai-hd"><span>✨</span> AI Suggestions</div>
                        <ul class="pg-ai-list">
                            <li>Lead with the service, the event types you cover and your location so clients know if you fit.</li>
                            <li>Highlight your experience, style and what is included in the package.</li>
                            <li>Invite clients to review your portfolio and share their date so you can confirm availability.</li>
                        </ul>
                        <button type="button" class="pg-btn primary" id="pgApply">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path d="M12 2l2.4 7.4H22l-6 4.6 2.3 7.4-6.3-4.6L5.7 21.4 8 14 2 9.4h7.6z"/></svg>
                            Apply Suggestions
                        </button>
                    </div>

                    <textarea name="description" id="pgDesc" class="pg-textarea"
                              placeholder="Describe the service you offer, your style, what is included and any key details clients should know…">{{ old('description') }}</textarea>
                </section>

                {{-- 3 · Inspiration Photos --}}
                <section class="pg-card" id="pg-inspiration">
                    <div class="pg-card-hd"><span class="ic">🖼️</span><h3>Portfolio Photos (Optional)</h3></div>
                    <p class="pg-card-sub">Add visual references so clients understand the look and quality of your work.</p>

                    <div class="pg-uploads">
                        <div class="pg-tile ph">🎨</div>
                        <div class="pg-tile ph">📷</div>
                        <div class="pg-tile ph">🌸</div>
                        <label class="pg-tile add">
                            <span style="font-size:24px;">+</span>
                            <span>Upload Photo</span>
                            <input type="file" name="inspiration_photos[]" multiple accept="image/*">
                        </label>
                    </div>

                    <div class="pg-upload-row">
                        <label class="pg-drop">
                            <b>Upload Video (Optional)</b>
                            Drop a clip or browse
                            <input type="file" name="video" accept="video/*">
                        </label>
                        <label class="pg-drop">
                            <b>Upload Documents (Optional)</b>
                            Brochures, packages, PDFs
                            <input type="file" name="documents[]" multiple>
                        </label>
                    </div>
                </section>

                {{-- 4 · Timeline --}}
                <section class="pg-card" id="pg-timeline">
                    <div class="pg-card-hd"><span class="ic">📅</span><h3>Timeline</h3></div>
                    <p class="pg-card-sub">When is this gig available or scheduled?</p>

                    <div class="pg-row">
                        <div class="pg-field" style="margin-bottom:0;">
                            <label class="pg-label">Event Date</label>
                            <input type="date" name="starts_at" id="pgStarts" class="pg-input" value="{{ old('starts_at') }}">
                        </div>
                        <div class="pg-field" style="margin-bottom:0;">
                            <label class="pg-label">Event Time</label>
                            <input type="time" name="event_time" id="pgTime" class="pg-input" value="{{ old('event_time') }}">
                        </div>
                    </div>
                    <div class="pg-row" style="margin-top:14px;">
                        <div class="pg-field" style="margin-bottom:0;">
                            <label class="pg-label">End Date (Optional)</label>
                            <input type="date" name="ends_at" id="pgEnds" class="pg-input" value="{{ old('ends_at') }}">
                        </div>
                        <div class="pg-field" style="margin-bottom:0;">
                            <label class="pg-label">Guests (approx.)</label>
                            <input type="number" name="guest_count" id="pgGuests" class="pg-input" min="1" placeholder="e.g. 150" value="{{ old('guest_count') }}">
                        </div>
                    </div>
                </section>

                {{-- 5 · Budget --}}
                <section class="pg-card" id="pg-budget">
                    <div class="pg-card-hd"><span class="ic">💰</span><h3>Budget</h3></div>
                    <p class="pg-card-sub">Set a starting price so clients see realistic expectations. This is an estimate — you can adjust later.</p>

                    <div class="pg-field">
                        <label class="pg-label">Budget</label>
                        <div class="pg-prefix">
                            <span class="sym">$</span>
                            <input type="number" name="budget" id="pgBudget" class="pg-input" min="0" step="1" placeholder="2500" value="{{ old('budget') }}">
                        </div>
                        <input type="range" id="pgBudgetRange" class="pg-range" min="0" max="20000" step="100" value="2500">
                    </div>
                </section>

                {{-- 6 · Requirements / Advanced Options --}}
                <section class="pg-card" id="pg-requirements">
                    <div class="pg-card-hd"><span class="ic">⚙️</span><h3>Requirements</h3></div>
                    <p class="pg-card-sub">Optional preferences for how this gig is listed.</p>

                    <details class="pg-adv">
                        <summary>
                            Advanced Options
                            <svg class="chev" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" width="16" height="16"><polyline points="6 9 12 15 18 9"/></svg>
                        </summary>
                        <div class="pg-adv-body">
                            <div class="pg-toggle">
                                <div><b>Featured listing</b><span>Give this gig extra visibility in browse results.</span></div>
                                <span class="pg-sw on"></span>
                            </div>
                            <div class="pg-toggle">
                                <div><b>Instant booking</b><span>Let clients book this package without a proposal step.</span></div>
                                <span class="pg-sw"></span>
                            </div>
                            <div class="pg-toggle">
                                <div><b>Allow questions from clients</b><span>Let clients ask clarifying questions before booking.</span></div>
                                <span class="pg-sw on"></span>
                            </div>
                        </div>
                    </details>
                </section>

                {{-- 7 · Bottom actions --}}
                <div class="pg-actions">
                    <button type="button" class="pg-btn ghost" onclick="document.getElementById('pgForm').submit();">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/><polyline points="17 21 17 13 7 13 7 21"/><polyline points="7 3 7 8 15 8"/></svg>
                        Save Draft
                    </button>
                    <button type="submit" class="pg-btn primary">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><line x1="22" y1="2" x2="11" y2="13"/><polygon points="22 2 15 22 11 13 2 9 22 2"/></svg>
                        Publish Gig
                    </button>
                </div>
            </div>

            {{-- ============ RIGHT COLUMN — Live Preview ============ --}}
            <aside class="pg-aside" id="pg-publish">
                <div class="pg-card">
                    <div class="pg-pv-hd">
                        <h4>Live Preview</h4>
                        <p>This is how your gig will appear to clients.</p>
                    </div>

                    <div class="pg-pvcard">
                        <div class="pg-pv-cover">
                            🎉
                            <div class="pg-pv-ring" title="Gig readiness — how complete your listing is">
                                <svg viewBox="0 0 44 44">
                                    <circle cx="22" cy="22" r="19" fill="none" stroke="var(--border-color)" stroke-width="4"/>
                                    <circle id="pgRingBar" cx="22" cy="22" r="19" fill="none" stroke="var(--bb)" stroke-width="4" stroke-linecap="round" stroke-dasharray="119.4" stroke-dashoffset="119.4"/>
                                </svg>
                                <span class="pct" id="pgPct">0%</span>
                            </div>
                        </div>
                        <div class="pg-pv-body">
                            <div class="pg-pv-title" id="pgPvTitle">Your Gig Title</div>
                            <div class="pg-pv-tags" id="pgPvTags"></div>
                            <div class="pg-pv-meta" id="pgPvMeta">
                                <span id="pgPvLoc">Location</span> · <span id="pgPvDate">Date</span>
                            </div>
                            <div class="pg-pv-desc" id="pgPvDesc">Your gig description will appear here as you type it.</div>
                            <div class="pg-pv-budget" id="pgPvBudget">Budget on request</div>
                            <div class="pg-pv-badges">
                                <span class="pg-pv-badge"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg> Verified Pro</span>
                                <span class="pg-pv-badge"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg> Escrow Protected</span>
                            </div>
                        </div>
                    </div>

                    <div class="pg-pv-actions">
                        <button type="submit" form="pgForm" class="pg-btn primary block">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><polyline points="20 6 9 17 4 12"/></svg>
                            Looks Good? Publish
                        </button>
                        <button type="button" class="pg-editlink" onclick="window.scrollTo({top:0,behavior:'smooth'});">Edit Gig</button>
                    </div>
                </div>
            </aside>
        </div>
    </form>

    {{-- D) AI tools row --}}
    <div class="pg-tools">
        <h3>AI Tools to Help You Build the Perfect Gig</h3>
        <p>Use these AI helpers for pricing, copy and packaging while you set up your gig.</p>
        <div class="pg-tools-grid">
            @php
                $pgTools = [
                    ['route' => 'ai-tools.pricing-assistant', 'icon' => '💵', 'name' => 'AI Pricing Assistant', 'desc' => 'Get suggested pricing ranges based on your service and market.'],
                    ['route' => 'ai-tools.proposal-writer',   'icon' => '✍️', 'name' => 'AI Proposal Writer',   'desc' => 'Draft clear, compelling copy for your gig description.'],
                    ['route' => 'ai-tools.bid-optimizer',     'icon' => '📈', 'name' => 'AI Bid Optimizer',     'desc' => 'Refine your positioning to stand out on the bidding board.'],
                    ['route' => 'ai-tools.package-builder',   'icon' => '📦', 'name' => 'AI Package Builder',   'desc' => 'Structure tiered packages clients can choose from.'],
                ];
            @endphp
            @foreach($pgTools as $tool)
                <a href="{{ route($tool['route']) }}" class="pg-tool">
                    <span class="tic">{{ $tool['icon'] }}</span>
                    <b>{{ $tool['name'] }}</b>
                    <span>{{ $tool['desc'] }}</span>
                    <span class="use">Use tool →</span>
                </a>
            @endforeach
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
(function () {
    var form = document.getElementById('pgForm');
    if (!form) return;

    var titleEl  = document.getElementById('pgTitle');
    var descEl   = document.getElementById('pgDesc');
    var locEl    = document.getElementById('pgLocation');
    var venueEl  = document.getElementById('pgVenue');
    var startsEl = document.getElementById('pgStarts');
    var guestsEl = document.getElementById('pgGuests');
    var budgetEl = document.getElementById('pgBudget');
    var rangeEl  = document.getElementById('pgBudgetRange');

    // ---- category chips (multi-select) ----
    var cats = [].slice.call(document.querySelectorAll('#pgCats .pg-chip'));
    cats.forEach(function (chip) {
        chip.addEventListener('click', function () {
            var cb = chip.querySelector('input');
            cb.checked = !cb.checked;
            chip.classList.toggle('sel', cb.checked);
            updatePreview();
        });
    });
    function selectedCatNames() {
        return cats.filter(function (c) { return c.querySelector('input').checked; })
                   .map(function (c) { return c.querySelector('.lbl').textContent.trim(); });
    }

    // ---- advanced toggles (UI only) ----
    document.querySelectorAll('.pg-sw').forEach(function (sw) {
        sw.addEventListener('click', function () { sw.classList.toggle('on'); });
    });

    // ---- budget <-> slider sync ----
    if (rangeEl && budgetEl) {
        rangeEl.addEventListener('input', function () { budgetEl.value = rangeEl.value; updatePreview(); });
        budgetEl.addEventListener('input', function () {
            var v = parseInt(budgetEl.value, 10);
            if (!isNaN(v)) rangeEl.value = Math.min(v, rangeEl.max);
            updatePreview();
        });
    }

    // ---- Apply AI Suggestions -> fill description ----
    document.getElementById('pgApply').addEventListener('click', function () {
        var catNames = selectedCatNames();
        var category = catNames.length ? catNames.join(' and ') : 'service';
        var eventType = (titleEl.value.trim() || 'gig');
        var location = (locEl.value.trim() || 'your area');
        var guests = guestsEl.value.trim();
        var guestLine = guests ? (' Comfortable working with events of around ' + guests + ' guests.') : '';

        var text = 'Experienced ' + category + ' available for ' + eventType +
            ' in ' + location + '.' + guestLine +
            ' We bring strong attention to detail and a professional approach to every booking.' +
            ' Review our portfolio and share your date so we can confirm availability and tailor a package to your needs.';

        descEl.value = text;
        descEl.focus();
        updatePreview();
    });

    // ---- Live preview ----
    var pvTitle  = document.getElementById('pgPvTitle');
    var pvTags   = document.getElementById('pgPvTags');
    var pvLoc    = document.getElementById('pgPvLoc');
    var pvDate   = document.getElementById('pgPvDate');
    var pvDesc   = document.getElementById('pgPvDesc');
    var pvBudget = document.getElementById('pgPvBudget');

    function fmtMoney(n) { return '$' + Number(n).toLocaleString(undefined, { maximumFractionDigits: 0 }); }

    function updatePreview() {
        pvTitle.textContent = titleEl.value.trim() || 'Your Gig Title';

        var names = selectedCatNames();
        pvTags.innerHTML = names.slice(0, 4).map(function (n) {
            return '<span class="pg-pv-tag">' + n.replace(/</g, '&lt;') + '</span>';
        }).join('');

        pvLoc.textContent  = locEl.value.trim() || 'Location';
        pvDate.textContent = startsEl.value || 'Date';

        var d = descEl.value.trim();
        pvDesc.textContent = d || 'Your gig description will appear here as you type it.';

        var b = parseInt(budgetEl.value, 10);
        if (!isNaN(b) && b > 0) {
            var low = Math.round(b * 0.85), high = Math.round(b * 1.15);
            pvBudget.textContent = fmtMoney(low) + ' – ' + fmtMoney(high) + ' (est.)';
        } else {
            pvBudget.textContent = 'Budget on request';
        }

        updateReadiness();
    }

    // ---- Gig readiness score (real, computed from filled fields) ----
    var pvPct     = document.getElementById('pgPct');
    var pvRingBar = document.getElementById('pgRingBar');
    var RING_C    = 119.4;
    function updateReadiness() {
        var score = 0;
        if (titleEl.value.trim())              score += 20;
        if (descEl.value.trim().length > 15)   score += 15;
        if (selectedCatNames().length)         score += 15;
        if (locEl.value.trim())                score += 15;
        if (startsEl.value)                    score += 15;
        var bv = parseInt(budgetEl.value, 10);
        if (!isNaN(bv) && bv > 0)              score += 15;
        if (guestsEl && guestsEl.value.trim()) score += 5;
        if (score > 100) score = 100;
        if (pvPct) pvPct.textContent = score + '%';
        if (pvRingBar) pvRingBar.setAttribute('stroke-dashoffset', (RING_C * (1 - score / 100)).toFixed(1));
    }

    [titleEl, descEl, locEl, startsEl, budgetEl, guestsEl].forEach(function (el) {
        if (el) { el.addEventListener('input', updatePreview); el.addEventListener('change', updatePreview); }
    });
    updatePreview();

    // ---- Step bar: scroll-spy + smooth anchor scroll ----
    var stepLinks = [].slice.call(document.querySelectorAll('.pg-step'));
    stepLinks.forEach(function (link) {
        link.addEventListener('click', function (e) {
            e.preventDefault();
            var target = document.getElementById(link.getAttribute('data-anchor'));
            if (target) target.scrollIntoView({ behavior: 'smooth', block: 'start' });
        });
    });
    var sections = stepLinks.map(function (l) { return document.getElementById(l.getAttribute('data-anchor')); });
    if ('IntersectionObserver' in window) {
        var obs = new IntersectionObserver(function (entries) {
            entries.forEach(function (en) {
                if (en.isIntersecting) {
                    var id = en.target.id;
                    stepLinks.forEach(function (l) { l.classList.toggle('active', l.getAttribute('data-anchor') === id); });
                }
            });
        }, { rootMargin: '-40% 0px -55% 0px', threshold: 0 });
        sections.forEach(function (s) { if (s) obs.observe(s); });
    }

    updatePreview();
})();
</script>
@endpush
