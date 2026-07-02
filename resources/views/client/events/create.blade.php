@extends('layouts.client')

@section('title', 'Create a Gig')
@section('page-title', 'Create a New Gig')
@section('page-subtitle', 'Fill in the details below and let our AI find the best vendors for you.')

{{-- Rich single-page gig builder (client / orange theme). Everything is visible
     on one page — the top step bar is anchor-nav that scrolls to each section.
     Core fields (title, description, starts_at, ends_at, category_ids[], location,
     budget) submit to client.events.store; the extra guests / time / venue /
     upload inputs are cosmetic UI-only. A sticky Live Preview updates as you type. --}}

@push('styles')
<style>
    .gb { --gb: #f97316; --gb-strong: #ea580c; --gb-soft: rgba(249,115,22,.10); --ai: #16a34a; }

    /* ---- Page header ---- */
    .gb-head { display: flex; align-items: flex-start; justify-content: space-between; gap: 16px; flex-wrap: wrap; margin-bottom: 22px; }
    .gb-head h1 { font-size: 24px; font-weight: 800; color: var(--text-primary); letter-spacing: -.5px; }
    .gb-head p { font-size: 13.5px; color: var(--text-muted); margin-top: 5px; max-width: 560px; }

    .gb-btn { border: none; border-radius: 11px; padding: 11px 20px; font-size: 13.5px; font-weight: 800; cursor: pointer; display: inline-flex; align-items: center; gap: 8px; font-family: inherit; text-decoration: none; }
    .gb-btn svg { width: 16px; height: 16px; }
    .gb-btn.primary { background: linear-gradient(135deg, var(--gb), var(--gb-strong)); color: #fff; }
    .gb-btn.primary:hover { transform: translateY(-1px); box-shadow: 0 8px 22px rgba(249,115,22,.28); }
    .gb-btn.ghost { background: var(--bg-card); color: var(--text-secondary); border: 1.5px solid var(--border-color); }
    .gb-btn.ghost:hover { border-color: var(--gb); color: var(--gb-strong); }
    .gb-btn.block { width: 100%; justify-content: center; }

    /* ---- Step bar (anchor nav) ---- */
    .gb-steps { display: flex; gap: 4px; overflow-x: auto; padding: 4px 2px 0; margin-bottom: 24px; border-bottom: 1px solid var(--border-color); }
    .gb-steps::-webkit-scrollbar { height: 5px; } .gb-steps::-webkit-scrollbar-thumb { background: var(--border-color); border-radius: 999px; }
    .gb-step { flex: 1 0 auto; display: inline-flex; align-items: center; gap: 8px; padding: 10px 14px 13px; font-size: 12.5px; font-weight: 700; color: var(--text-muted); border-bottom: 2.5px solid transparent; margin-bottom: -1px; white-space: nowrap; text-decoration: none; cursor: pointer; transition: all .15s; }
    .gb-step:hover { color: var(--text-secondary); }
    .gb-step .n { width: 22px; height: 22px; border-radius: 50%; background: var(--bg-secondary); border: 1.5px solid var(--border-color); display: inline-flex; align-items: center; justify-content: center; font-size: 11px; font-weight: 800; color: var(--text-muted); }
    .gb-step.active { color: var(--gb-strong); border-bottom-color: var(--gb); }
    .gb-step.active .n { background: var(--gb); border-color: var(--gb); color: #fff; }

    /* ---- Layout ---- */
    .gb-grid { display: grid; grid-template-columns: 1fr 360px; gap: 22px; align-items: start; }
    @media (max-width: 980px) { .gb-grid { grid-template-columns: 1fr; } .gb-aside { position: static !important; } }
    .gb-main { display: flex; flex-direction: column; gap: 18px; min-width: 0; }
    .gb-aside { position: sticky; top: 18px; display: flex; flex-direction: column; gap: 16px; }

    /* ---- Cards ---- */
    .gb-card { background: var(--bg-secondary); border: 1px solid var(--border-color); border-radius: var(--radius, 16px); padding: 22px 24px; scroll-margin-top: 90px; }
    .gb-card-hd { display: flex; align-items: center; gap: 10px; margin-bottom: 4px; }
    .gb-card-hd h3 { font-size: 16px; font-weight: 800; color: var(--text-primary); }
    .gb-card-hd .ic { width: 34px; height: 34px; border-radius: 10px; background: var(--gb-soft); color: var(--gb-strong); display: inline-flex; align-items: center; justify-content: center; font-size: 17px; flex-shrink: 0; }
    .gb-card-sub { font-size: 12.5px; color: var(--text-muted); margin: 0 0 18px; }

    .gb-label { display: block; font-size: 12.5px; font-weight: 700; color: var(--text-secondary); margin: 0 0 7px; }
    .gb-req { color: var(--gb-strong); }
    .gb-input, .gb-textarea, .gb-select { width: 100%; border: 1.5px solid var(--border-color); border-radius: 11px; padding: 11px 13px; font-size: 14px; color: var(--text-primary); background: var(--bg-card); font-family: inherit; transition: border-color .15s, box-shadow .15s; }
    .gb-input:focus, .gb-textarea:focus, .gb-select:focus { outline: none; border-color: var(--gb); box-shadow: 0 0 0 3px rgba(249,115,22,.14); }
    .gb-textarea { resize: vertical; min-height: 150px; line-height: 1.55; }
    .gb-field { margin-bottom: 16px; }
    .gb-field:last-child { margin-bottom: 0; }
    .gb-row { display: grid; grid-template-columns: 1fr 1fr; gap: 14px; }
    @media (max-width: 520px) { .gb-row { grid-template-columns: 1fr; } }
    .gb-prefix { display: flex; align-items: stretch; }
    .gb-prefix .sym { flex-shrink: 0; width: 44px; display: flex; align-items: center; justify-content: center; background: var(--gb-soft); color: var(--gb-strong); font-weight: 800; font-size: 15px; border: 1.5px solid var(--border-color); border-right: none; border-radius: 11px 0 0 11px; }
    .gb-prefix .gb-input { border-radius: 0 11px 11px 0; }

    /* category chips */
    .gb-chips { display: flex; flex-wrap: wrap; gap: 9px; }
    .gb-chip { display: inline-flex; align-items: center; gap: 7px; border: 1.5px solid var(--border-color); border-radius: 10px; padding: 8px 13px; font-size: 13px; font-weight: 700; color: var(--text-secondary); background: var(--bg-card); cursor: pointer; user-select: none; transition: all .12s; }
    .gb-chip:hover { border-color: var(--gb); }
    .gb-chip.sel { border-color: var(--gb); background: var(--gb-soft); color: var(--gb-strong); }
    .gb-chip .tick { display: none; font-size: 12px; } .gb-chip.sel .tick { display: inline; }

    /* AI assistant sub-panel */
    .gb-ai { border: 1px dashed var(--gb); background: linear-gradient(135deg, rgba(249,115,22,.06), rgba(234,88,12,.03)); border-radius: 13px; padding: 15px 16px; margin-bottom: 16px; }
    .gb-ai-hd { display: flex; align-items: center; gap: 8px; font-size: 12.5px; font-weight: 800; color: var(--gb-strong); text-transform: uppercase; letter-spacing: .4px; margin-bottom: 10px; }
    .gb-ai-list { list-style: none; margin: 0 0 13px; padding: 0; display: flex; flex-direction: column; gap: 7px; }
    .gb-ai-list li { font-size: 12.5px; color: var(--text-secondary); display: flex; gap: 8px; line-height: 1.45; }
    .gb-ai-list li::before { content: '✦'; color: var(--gb); flex-shrink: 0; }

    /* upload tiles */
    .gb-uploads { display: grid; grid-template-columns: repeat(auto-fill, minmax(96px, 1fr)); gap: 11px; }
    .gb-tile { aspect-ratio: 1; border-radius: 12px; border: 1.5px solid var(--border-color); display: flex; align-items: center; justify-content: center; font-size: 24px; color: var(--text-muted); overflow: hidden; position: relative; }
    .gb-tile.ph { background: linear-gradient(135deg, rgba(249,115,22,.10), rgba(249,115,22,.03)); }
    .gb-tile.add { border-style: dashed; cursor: pointer; flex-direction: column; gap: 4px; font-size: 22px; }
    .gb-tile.add:hover { border-color: var(--gb); color: var(--gb); }
    .gb-tile.add span { font-size: 10.5px; font-weight: 700; }
    .gb-tile.add input { position: absolute; inset: 0; opacity: 0; cursor: pointer; }
    .gb-upload-row { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-top: 14px; }
    @media (max-width: 520px) { .gb-upload-row { grid-template-columns: 1fr; } }
    .gb-drop { border: 1.5px dashed var(--border-color); border-radius: 12px; padding: 14px; text-align: center; font-size: 12px; font-weight: 700; color: var(--text-muted); position: relative; }
    .gb-drop:hover { border-color: var(--gb); color: var(--gb); }
    .gb-drop input { position: absolute; inset: 0; opacity: 0; cursor: pointer; }
    .gb-drop b { display: block; color: var(--text-secondary); font-size: 12.5px; margin-bottom: 2px; }

    /* budget slider */
    .gb-range { width: 100%; margin-top: 6px; accent-color: var(--gb); }

    /* advanced options */
    .gb-adv { border: 1px solid var(--border-color); border-radius: 12px; overflow: hidden; }
    .gb-adv summary { list-style: none; cursor: pointer; padding: 14px 18px; font-size: 13.5px; font-weight: 800; color: var(--text-primary); display: flex; align-items: center; justify-content: space-between; }
    .gb-adv summary::-webkit-details-marker { display: none; }
    .gb-adv summary .chev { transition: transform .2s; }
    .gb-adv[open] summary .chev { transform: rotate(180deg); }
    .gb-adv-body { padding: 4px 18px 18px; border-top: 1px solid var(--border-color); }
    .gb-toggle { display: flex; align-items: center; justify-content: space-between; gap: 12px; padding: 11px 0; border-bottom: 1px solid var(--border-color); }
    .gb-toggle:last-child { border-bottom: none; }
    .gb-toggle b { display: block; font-size: 13px; font-weight: 800; color: var(--text-primary); }
    .gb-toggle span { font-size: 11.5px; color: var(--text-muted); }
    .gb-sw { width: 42px; height: 24px; border-radius: 999px; background: var(--border-color); position: relative; cursor: pointer; flex-shrink: 0; transition: background .15s; }
    .gb-sw::after { content: ''; position: absolute; top: 2px; left: 2px; width: 20px; height: 20px; border-radius: 50%; background: #fff; transition: left .15s; box-shadow: 0 1px 3px rgba(0,0,0,.25); }
    .gb-sw.on { background: var(--gb); } .gb-sw.on::after { left: 20px; }

    .gb-actions { display: flex; align-items: center; justify-content: flex-end; gap: 12px; flex-wrap: wrap; }

    .gb-err { color: #dc2626; font-size: 12.5px; font-weight: 700; margin-top: 8px; }

    /* ---- Live preview ---- */
    .gb-pv-hd h4 { font-size: 15px; font-weight: 800; color: var(--text-primary); }
    .gb-pv-hd p { font-size: 12px; color: var(--text-muted); margin-top: 3px; }
    .gb-pvcard { border: 1px solid var(--border-color); border-radius: 14px; overflow: hidden; background: var(--bg-card); margin-top: 14px; }
    .gb-pv-cover { height: 84px; background: linear-gradient(135deg, var(--gb), var(--gb-strong)); position: relative; display: flex; align-items: center; justify-content: center; font-size: 30px; }
    .gb-pv-ring { position: absolute; right: 12px; bottom: -20px; width: 52px; height: 52px; border-radius: 50%; background: var(--bg-card); border: 1px solid var(--border-color); display: flex; align-items: center; justify-content: center; }
    .gb-pv-ring svg { width: 46px; height: 46px; transform: rotate(-90deg); }
    .gb-pv-ring .pct { position: absolute; font-size: 11px; font-weight: 800; color: var(--gb-strong); }
    .gb-pv-body { padding: 26px 16px 16px; }
    .gb-pv-title { font-size: 15px; font-weight: 800; color: var(--text-primary); line-height: 1.3; }
    .gb-pv-tags { display: flex; flex-wrap: wrap; gap: 6px; margin: 9px 0; }
    .gb-pv-tag { font-size: 10.5px; font-weight: 700; color: var(--gb-strong); background: var(--gb-soft); padding: 3px 9px; border-radius: 999px; }
    .gb-pv-meta { font-size: 11.5px; color: var(--text-muted); display: flex; align-items: center; gap: 6px; flex-wrap: wrap; }
    .gb-pv-desc { font-size: 12px; color: var(--text-secondary); line-height: 1.5; margin: 10px 0; display: -webkit-box; -webkit-line-clamp: 3; -webkit-box-orient: vertical; overflow: hidden; }
    .gb-pv-budget { font-size: 14px; font-weight: 800; color: var(--ai); }
    .gb-pv-badges { display: flex; gap: 8px; margin-top: 12px; padding-top: 12px; border-top: 1px solid var(--border-color); flex-wrap: wrap; }
    .gb-pv-badge { font-size: 10.5px; font-weight: 700; color: var(--text-secondary); display: inline-flex; align-items: center; gap: 4px; }
    .gb-pv-badge svg { width: 13px; height: 13px; color: var(--ai); }
    .gb-pv-actions { margin-top: 16px; display: flex; flex-direction: column; gap: 9px; align-items: center; }
    .gb-editlink { font-size: 12.5px; font-weight: 700; color: var(--text-muted); background: none; border: none; cursor: pointer; text-decoration: underline; font-family: inherit; }
    .gb-editlink:hover { color: var(--gb-strong); }

    /* ---- AI tools row ---- */
    .gb-tools { margin-top: 26px; }
    .gb-tools h3 { font-size: 17px; font-weight: 800; color: var(--text-primary); margin-bottom: 4px; }
    .gb-tools p { font-size: 13px; color: var(--text-muted); margin-bottom: 16px; }
    .gb-tools-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(220px, 1fr)); gap: 14px; }
    .gb-tool { background: var(--bg-secondary); border: 1px solid var(--border-color); border-radius: 14px; padding: 18px; text-decoration: none; display: flex; flex-direction: column; transition: all .15s; }
    .gb-tool:hover { border-color: var(--gb); transform: translateY(-2px); box-shadow: 0 10px 26px rgba(249,115,22,.10); }
    .gb-tool .tic { width: 40px; height: 40px; border-radius: 11px; background: var(--gb-soft); color: var(--gb-strong); display: inline-flex; align-items: center; justify-content: center; font-size: 20px; margin-bottom: 12px; }
    .gb-tool b { font-size: 14px; font-weight: 800; color: var(--text-primary); }
    .gb-tool span { font-size: 12px; color: var(--text-muted); line-height: 1.5; margin: 5px 0 12px; flex: 1; }
    .gb-tool .use { font-size: 12.5px; font-weight: 800; color: var(--gb-strong); display: inline-flex; align-items: center; gap: 5px; }
</style>
@endpush

@section('content')
<div class="gb" id="gbRoot">

    {{-- A) Page header --}}
    <div class="gb-head">
        <div>
            <h1>Create a New Gig</h1>
            <p>Fill in the details below and let our AI find the best vendors for you.</p>
        </div>
        <button type="button" class="gb-btn ghost" onclick="document.getElementById('gbForm').submit();">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/><polyline points="17 21 17 13 7 13 7 21"/><polyline points="7 3 7 8 15 8"/></svg>
            Save Draft
        </button>
    </div>

    {{-- B) Step bar (anchor nav) --}}
    @php
        $gbSteps = [
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
    <div class="gb-steps" id="gbSteps">
        @foreach($gbSteps as $i => [$anchor, $label])
            <a href="#gb-{{ $anchor }}" class="gb-step {{ $anchor === 'describe' ? 'active' : '' }}" data-anchor="gb-{{ $anchor }}">
                <span class="n">{{ $i + 1 }}</span>{{ $label }}
            </a>
        @endforeach
    </div>

    <form method="POST" action="{{ route('client.events.store') }}" id="gbForm" enctype="multipart/form-data">
        @csrf

        <div class="gb-grid">
            {{-- ============ LEFT COLUMN ============ --}}
            <div class="gb-main">

                {{-- 1 · Basics --}}
                <section class="gb-card" id="gb-basics">
                    <div class="gb-card-hd"><span class="ic">📋</span><h3>Basics</h3></div>
                    <p class="gb-card-sub">The essentials about your event.</p>

                    <div class="gb-field">
                        <label class="gb-label">Gig Title <span class="gb-req">*</span></label>
                        <input type="text" name="title" id="gbTitle" class="gb-input" required maxlength="255"
                               placeholder="e.g. Wedding Photographer for June Celebration" value="{{ old('title') }}">
                        @error('title') <div class="gb-err">{{ $message }}</div> @enderror
                    </div>

                    <div class="gb-field">
                        <label class="gb-label">Event Type</label>
                        <div class="gb-chips" id="gbCats">
                            @foreach($categories as $cat)
                                <label class="gb-chip">
                                    <input type="checkbox" name="category_ids[]" value="{{ $cat->id }}" hidden>
                                    @if(!empty($cat->icon))<span>{{ $cat->icon }}</span>@endif
                                    <span class="lbl">{{ $cat->name }}</span>
                                    <span class="tick">✓</span>
                                </label>
                            @endforeach
                        </div>
                    </div>

                    <div class="gb-row">
                        <div class="gb-field" style="margin-bottom:0;">
                            <label class="gb-label">Location</label>
                            <input type="text" name="location" id="gbLocation" class="gb-input" placeholder="e.g. Miami, FL" value="{{ old('location') }}">
                        </div>
                        <div class="gb-field" style="margin-bottom:0;">
                            <label class="gb-label">Venue</label>
                            <input type="text" name="venue" id="gbVenue" class="gb-input" placeholder="e.g. Beach Palace Hotel" value="{{ old('venue') }}">
                        </div>
                    </div>
                </section>

                {{-- 2 · Describe Your Project --}}
                <section class="gb-card" id="gb-describe">
                    <div class="gb-card-hd"><span class="ic">📝</span><h3>Describe Your Project</h3></div>
                    <p class="gb-card-sub">Tell professionals what you're looking for. Not sure where to start? Let the AI assistant draft it.</p>

                    <div class="gb-ai">
                        <div class="gb-ai-hd"><span>✨</span> AI Suggestions</div>
                        <ul class="gb-ai-list">
                            <li>Mention the service, event type and location so vendors know if they're a fit.</li>
                            <li>Note your guest count and any key moments you want covered.</li>
                            <li>Ask vendors to share a portfolio and their availability for your date.</li>
                        </ul>
                        <button type="button" class="gb-btn primary" id="gbApply">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path d="M12 2l2.4 7.4H22l-6 4.6 2.3 7.4-6.3-4.6L5.7 21.4 8 14 2 9.4h7.6z"/></svg>
                            Apply Suggestions
                        </button>
                    </div>

                    <textarea name="description" id="gbDesc" class="gb-textarea"
                              placeholder="Describe your event, the service you need, your style preferences and any must-have details…">{{ old('description') }}</textarea>
                </section>

                {{-- 3 · Inspiration Photos --}}
                <section class="gb-card" id="gb-inspiration">
                    <div class="gb-card-hd"><span class="ic">🖼️</span><h3>Inspiration Photos (Optional)</h3></div>
                    <p class="gb-card-sub">Add visual references so vendors understand the look and feel you're after.</p>

                    <div class="gb-uploads">
                        <div class="gb-tile ph">🎨</div>
                        <div class="gb-tile ph">📷</div>
                        <div class="gb-tile ph">🌸</div>
                        <label class="gb-tile add">
                            <span style="font-size:24px;">+</span>
                            <span>Upload Photo</span>
                            <input type="file" name="inspiration_photos[]" multiple accept="image/*">
                        </label>
                    </div>

                    <div class="gb-upload-row">
                        <label class="gb-drop">
                            <b>Upload Video (Optional)</b>
                            Drop a clip or browse
                            <input type="file" name="video" accept="video/*">
                        </label>
                        <label class="gb-drop">
                            <b>Upload Documents (Optional)</b>
                            Briefs, mood boards, PDFs
                            <input type="file" name="documents[]" multiple>
                        </label>
                    </div>
                </section>

                {{-- 4 · Timeline --}}
                <section class="gb-card" id="gb-timeline">
                    <div class="gb-card-hd"><span class="ic">📅</span><h3>Timeline</h3></div>
                    <p class="gb-card-sub">When is your event happening?</p>

                    <div class="gb-row">
                        <div class="gb-field" style="margin-bottom:0;">
                            <label class="gb-label">Event Date</label>
                            <input type="date" name="starts_at" id="gbStarts" class="gb-input" value="{{ old('starts_at') }}">
                        </div>
                        <div class="gb-field" style="margin-bottom:0;">
                            <label class="gb-label">Event Time</label>
                            <input type="time" name="event_time" id="gbTime" class="gb-input" value="{{ old('event_time') }}">
                        </div>
                    </div>
                    <div class="gb-row" style="margin-top:14px;">
                        <div class="gb-field" style="margin-bottom:0;">
                            <label class="gb-label">End Date (Optional)</label>
                            <input type="date" name="ends_at" id="gbEnds" class="gb-input" value="{{ old('ends_at') }}">
                        </div>
                        <div class="gb-field" style="margin-bottom:0;">
                            <label class="gb-label">Guests (approx.)</label>
                            <input type="number" name="guest_count" id="gbGuests" class="gb-input" min="1" placeholder="e.g. 150" value="{{ old('guest_count') }}">
                        </div>
                    </div>
                </section>

                {{-- 5 · Budget --}}
                <section class="gb-card" id="gb-budget">
                    <div class="gb-card-hd"><span class="ic">💰</span><h3>Budget</h3></div>
                    <p class="gb-card-sub">Give vendors a target so you get realistic proposals. This is an estimate — you can adjust later.</p>

                    <div class="gb-field">
                        <label class="gb-label">Budget</label>
                        <div class="gb-prefix">
                            <span class="sym">$</span>
                            <input type="number" name="budget" id="gbBudget" class="gb-input" min="0" step="1" placeholder="2500" value="{{ old('budget') }}">
                        </div>
                        <input type="range" id="gbBudgetRange" class="gb-range" min="0" max="20000" step="100" value="2500">
                    </div>
                </section>

                {{-- 6 · Requirements / Advanced Options --}}
                <section class="gb-card" id="gb-requirements">
                    <div class="gb-card-hd"><span class="ic">⚙️</span><h3>Requirements</h3></div>
                    <p class="gb-card-sub">Optional preferences for how vendors bid on your gig.</p>

                    <details class="gb-adv">
                        <summary>
                            Advanced Options
                            <svg class="chev" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" width="16" height="16"><polyline points="6 9 12 15 18 9"/></svg>
                        </summary>
                        <div class="gb-adv-body">
                            <div class="gb-toggle">
                                <div><b>Verified vendors only</b><span>Only vendors who've completed verification can bid.</span></div>
                                <span class="gb-sw on"></span>
                            </div>
                            <div class="gb-toggle">
                                <div><b>Sealed bidding</b><span>Hide competing bids so proposals stay independent.</span></div>
                                <span class="gb-sw"></span>
                            </div>
                            <div class="gb-toggle">
                                <div><b>Allow questions from vendors</b><span>Let vendors ask clarifying questions before bidding.</span></div>
                                <span class="gb-sw on"></span>
                            </div>
                        </div>
                    </details>
                </section>

                {{-- 7 · Bottom actions --}}
                <div class="gb-actions">
                    <button type="button" class="gb-btn ghost" onclick="document.getElementById('gbForm').submit();">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/><polyline points="17 21 17 13 7 13 7 21"/><polyline points="7 3 7 8 15 8"/></svg>
                        Save Draft
                    </button>
                    <button type="submit" class="gb-btn primary">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><line x1="22" y1="2" x2="11" y2="13"/><polygon points="22 2 15 22 11 13 2 9 22 2"/></svg>
                        Publish Gig
                    </button>
                </div>
            </div>

            {{-- ============ RIGHT COLUMN — Live Preview ============ --}}
            <aside class="gb-aside" id="gb-publish">
                <div class="gb-card">
                    <div class="gb-pv-hd">
                        <h4>Live Preview</h4>
                        <p>This is how your gig will appear to professionals.</p>
                    </div>

                    <div class="gb-pvcard">
                        <div class="gb-pv-cover">
                            🎉
                            <div class="gb-pv-ring" title="Gig readiness — how complete your listing is">
                                <svg viewBox="0 0 44 44">
                                    <circle cx="22" cy="22" r="19" fill="none" stroke="var(--border-color)" stroke-width="4"/>
                                    <circle id="pvRingBar" cx="22" cy="22" r="19" fill="none" stroke="var(--gb)" stroke-width="4" stroke-linecap="round" stroke-dasharray="119.4" stroke-dashoffset="119.4"/>
                                </svg>
                                <span class="pct" id="pvPct">0%</span>
                            </div>
                        </div>
                        <div class="gb-pv-body">
                            <div class="gb-pv-title" id="pvTitle">Your Event Title</div>
                            <div class="gb-pv-tags" id="pvTags"></div>
                            <div class="gb-pv-meta" id="pvMeta">
                                <span id="pvLoc">Location</span> · <span id="pvDate">Date</span>
                            </div>
                            <div class="gb-pv-desc" id="pvDesc">Your project description will appear here as you type it.</div>
                            <div class="gb-pv-budget" id="pvBudget">Budget on request</div>
                            <div class="gb-pv-badges">
                                <span class="gb-pv-badge"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg> Verified Client</span>
                                <span class="gb-pv-badge"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg> Escrow Protected</span>
                            </div>
                        </div>
                    </div>

                    <div class="gb-pv-actions">
                        <button type="submit" form="gbForm" class="gb-btn primary block">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><polyline points="20 6 9 17 4 12"/></svg>
                            Looks Good? Publish
                        </button>
                        <button type="button" class="gb-editlink" onclick="window.scrollTo({top:0,behavior:'smooth'});">Edit Gig</button>
                    </div>
                </div>
            </aside>
        </div>
    </form>

    {{-- D) AI tools row --}}
    <div class="gb-tools">
        <h3>AI Tools to Help You Create the Perfect Gig</h3>
        <p>Use these AI helpers for suggestions and estimates while you plan.</p>
        <div class="gb-tools-grid">
            @php
                $gbTools = [
                    ['route' => 'ai-tools.budget-allocator',   'icon' => '💵', 'name' => 'AI Budget Allocator',    'desc' => 'Break a total budget into smart category estimates.'],
                    ['route' => 'ai-tools.vendor-matchmaking', 'icon' => '🤝', 'name' => 'AI Vendor Matchmaking',  'desc' => 'Get suggested vendors that fit your event.'],
                    ['route' => 'ai-tools.timeline-builder',   'icon' => '🗓️', 'name' => 'AI Timeline Builder',    'desc' => 'Draft a day-of schedule for your event.'],
                    ['route' => 'ai-tools.checklist-generator','icon' => '✅', 'name' => 'AI Checklist Generator',  'desc' => 'Generate a planning checklist to stay on track.'],
                    ['route' => 'ai-tools.venue-analyzer',     'icon' => '🏛️', 'name' => 'AI Venue Analyzer',      'desc' => 'Review venue notes for fit and considerations.'],
                    ['route' => 'ai-tools.theme-advisor',      'icon' => '🎨', 'name' => 'AI Theme & Style Advisor','desc' => 'Explore theme and styling suggestions.'],
                ];
            @endphp
            @foreach($gbTools as $tool)
                <a href="{{ route($tool['route']) }}" class="gb-tool">
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
    var form = document.getElementById('gbForm');
    if (!form) return;

    var titleEl  = document.getElementById('gbTitle');
    var descEl   = document.getElementById('gbDesc');
    var locEl    = document.getElementById('gbLocation');
    var venueEl  = document.getElementById('gbVenue');
    var startsEl = document.getElementById('gbStarts');
    var guestsEl = document.getElementById('gbGuests');
    var budgetEl = document.getElementById('gbBudget');
    var rangeEl  = document.getElementById('gbBudgetRange');

    // ---- category chips (multi-select) ----
    var cats = [].slice.call(document.querySelectorAll('#gbCats .gb-chip'));
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
    document.querySelectorAll('.gb-sw').forEach(function (sw) {
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
    document.getElementById('gbApply').addEventListener('click', function () {
        var catNames = selectedCatNames();
        var category = catNames.length ? catNames.join(' and ') : 'vendor';
        var eventType = (titleEl.value.trim() || 'event');
        var location = (locEl.value.trim() || 'our area');
        var guests = guestsEl.value.trim();
        var guestLine = guests ? (' We are expecting around ' + guests + ' guests.') : '';

        var text = 'We need a professional ' + category + ' to cover our ' + eventType +
            ' in ' + location + '.' + guestLine +
            ' Looking for someone with relevant experience and strong attention to detail.' +
            ' Please share your portfolio and availability so we can find the best fit.';

        descEl.value = text;
        descEl.focus();
        updatePreview();
    });

    // ---- Live preview ----
    var pvTitle  = document.getElementById('pvTitle');
    var pvTags   = document.getElementById('pvTags');
    var pvLoc    = document.getElementById('pvLoc');
    var pvDate   = document.getElementById('pvDate');
    var pvDesc   = document.getElementById('pvDesc');
    var pvBudget = document.getElementById('pvBudget');

    function fmtMoney(n) { return '$' + Number(n).toLocaleString(undefined, { maximumFractionDigits: 0 }); }

    function updatePreview() {
        pvTitle.textContent = titleEl.value.trim() || 'Your Event Title';

        var names = selectedCatNames();
        pvTags.innerHTML = names.slice(0, 4).map(function (n) {
            return '<span class="gb-pv-tag">' + n.replace(/</g, '&lt;') + '</span>';
        }).join('');

        pvLoc.textContent  = locEl.value.trim() || 'Location';
        pvDate.textContent = startsEl.value || 'Date';

        var d = descEl.value.trim();
        pvDesc.textContent = d || 'Your project description will appear here as you type it.';

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
    var pvPct     = document.getElementById('pvPct');
    var pvRingBar = document.getElementById('pvRingBar');
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
    var stepLinks = [].slice.call(document.querySelectorAll('.gb-step'));
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
