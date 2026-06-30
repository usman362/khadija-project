@extends('layouts.client')

@section('title', 'Create a Gig')
@section('page-title', 'Create a Gig Request')
@section('page-subtitle', 'One focused question at a time — move back & forth freely')

{{-- Flash-card "Create a Gig" wizard (Peter's client-process pattern: one
     focused card per topic, navigate back/forth, all answers recombine into a
     single gig). Cards: AI assist → Basics → Describe → Budget → Urgency
     (Emergency = ESR) → Vendor Prefs → Find method → Review. Core fields submit
     to the existing client.events.store; richer fields are captured client-side
     and folded into the description so nothing is lost pending a schema. --}}

@push('styles')
<style>
    .gw { --gw: #f97316; --gw-strong: #ea580c; --ai: #16a34a; max-width: 740px; margin: 0 auto; }

    .gw-count { font-size: 12.5px; font-weight: 800; color: var(--text-muted); white-space: nowrap; }

    /* Named step indicator (Peter's "gig creation steps" style) — numbered
       circle + title + description, horizontal, active highlighted. */
    .gw-stepper { display: flex; gap: 2px; overflow-x: auto; padding: 2px 0 16px; margin-bottom: 20px; }
    .gw-stepper::-webkit-scrollbar { height: 5px; } .gw-stepper::-webkit-scrollbar-thumb { background: var(--border-color); border-radius: 999px; }
    .gw-st { display: flex; flex-direction: column; align-items: center; text-align: center; flex: 1 0 90px; min-width: 90px; position: relative; }
    .gw-st::before { content: ''; position: absolute; top: 14px; left: -50%; width: 100%; height: 2px; background: var(--border-color); z-index: 0; }
    .gw-st:first-child::before { display: none; }
    .gw-st.done::before { background: #16a34a; }
    .gw-st.active::before { background: var(--gw); }
    .gw-st-num { width: 30px; height: 30px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 12.5px; font-weight: 800; background: var(--bg-card); border: 2px solid var(--border-color); color: var(--text-muted); position: relative; z-index: 1; transition: all .2s; }
    .gw-st.done .gw-st-num { background: #16a34a; border-color: #16a34a; color: #fff; }
    .gw-st.active .gw-st-num { background: var(--gw); border-color: var(--gw); color: #fff; box-shadow: 0 0 0 4px rgba(249,115,22,.18); }
    .gw-st-title { font-size: 11px; font-weight: 800; color: var(--text-secondary); margin-top: 7px; line-height: 1.2; }
    .gw-st.active .gw-st-title, .gw-st.done .gw-st-title { color: var(--text-primary); }
    .gw-st-desc { font-size: 9.5px; color: var(--text-muted); margin-top: 2px; line-height: 1.3; max-width: 96px; }

    .gw-stage { position: relative; min-height: 380px; }
    .gw-card { position: absolute; inset: 0; opacity: 0; transform: translateX(40px); pointer-events: none; transition: opacity .35s ease, transform .35s cubic-bezier(.16,1,.3,1); }
    .gw-card.active { opacity: 1; transform: none; pointer-events: auto; position: relative; }
    .gw-card.leaving { transform: translateX(-40px); opacity: 0; }

    .gw-eyebrow { display: inline-flex; align-items: center; gap: 7px; font-size: 12px; font-weight: 800; letter-spacing: .4px; text-transform: uppercase; color: var(--gw); background: rgba(249,115,22,.1); padding: 6px 13px; border-radius: 999px; margin-bottom: 15px; }
    .gw-q { font-size: 25px; font-weight: 800; color: var(--text-primary); letter-spacing: -.5px; line-height: 1.2; }
    .gw-help { font-size: 14px; color: var(--text-muted); margin: 9px 0 20px; }

    .gw-flabel { display: block; font-size: 12.5px; font-weight: 700; color: var(--text-secondary); margin: 0 0 7px; }
    .gw-input { width: 100%; border: 2px solid var(--border-color); border-radius: 12px; padding: 12px 14px; font-size: 15px; color: var(--text-primary); background: var(--bg-card); font-family: inherit; transition: border-color .15s; }
    .gw-input:focus { outline: none; border-color: var(--gw); }
    textarea.gw-input { resize: vertical; min-height: 120px; }
    .gw-row { display: flex; gap: 13px; flex-wrap: wrap; margin-bottom: 13px; }
    .gw-row > div { flex: 1; min-width: 150px; }
    .gw-field { margin-bottom: 13px; }

    .gw-chips { display: flex; flex-wrap: wrap; gap: 9px; }
    .gw-chip { display: inline-flex; align-items: center; gap: 7px; border: 2px solid var(--border-color); border-radius: 11px; padding: 9px 14px; font-size: 13.5px; font-weight: 700; color: var(--text-secondary); background: var(--bg-card); cursor: pointer; user-select: none; }
    .gw-chip:hover { border-color: var(--gw); }
    .gw-chip.sel { border-color: var(--gw); background: rgba(249,115,22,.1); color: var(--gw-strong); }
    .gw-chip .tick { display: none; } .gw-chip.sel .tick { display: inline; }

    /* radio-card options (AI level, budget mode, urgency, find method) */
    .gw-opts { display: flex; flex-direction: column; gap: 10px; }
    .gw-opt { display: flex; align-items: flex-start; gap: 11px; border: 2px solid var(--border-color); border-radius: 13px; padding: 13px 15px; cursor: pointer; transition: all .15s; }
    .gw-opt:hover { border-color: var(--gw); }
    .gw-opt.sel { border-color: var(--gw); background: rgba(249,115,22,.07); }
    .gw-opt .ic { font-size: 18px; line-height: 1.2; }
    .gw-opt b { display: block; font-size: 14.5px; font-weight: 800; color: var(--text-primary); }
    .gw-opt span { font-size: 12.5px; color: var(--text-muted); }
    .gw-opt .pill { margin-left: auto; font-size: 10px; font-weight: 800; color: var(--gw-strong); background: rgba(249,115,22,.12); padding: 3px 9px; border-radius: 999px; align-self: center; }

    .gw-note { font-size: 12px; font-weight: 700; border-radius: 10px; padding: 9px 13px; margin-top: 12px; display: flex; align-items: center; gap: 8px; }
    .gw-note.warn { color: var(--gw-strong); background: rgba(249,115,22,.1); }
    .gw-note.ai { color: var(--ai); background: rgba(22,163,74,.09); }

    .gw-uploads { display: flex; gap: 10px; flex-wrap: wrap; }
    .gw-upload { width: 78px; height: 64px; border: 2px dashed var(--border-color); border-radius: 11px; display: flex; align-items: center; justify-content: center; color: var(--text-muted); font-size: 22px; cursor: pointer; }
    .gw-upload:hover { border-color: var(--gw); color: var(--gw); }

    .gw-ai-help { display: flex; align-items: center; gap: 11px; margin-top: 14px; border: 1px dashed var(--gw); background: rgba(249,115,22,.06); border-radius: 12px; padding: 11px 14px; flex-wrap: wrap; }
    .gw[data-ai="manual"] .gw-ai-help { display: none; }
    .gw-ai-help p { font-size: 12.5px; color: var(--text-secondary); flex: 1; min-width: 150px; }
    .gw-ai-help p b { color: var(--gw-strong); }
    .gw-ai-use { font-size: 12.5px; font-weight: 800; color: #fff; background: linear-gradient(135deg, var(--gw), var(--gw-strong)); border-radius: 9px; padding: 8px 14px; text-decoration: none; white-space: nowrap; }
    .gw-suggest { display: flex; flex-wrap: wrap; gap: 8px; margin-top: 14px; }
    .gw-suggest .lbl { width: 100%; font-size: 11.5px; font-weight: 800; color: var(--gw-strong); }
    .gw-sugg { font-size: 12px; font-weight: 700; color: var(--text-secondary); border: 1px solid var(--border-color); border-radius: 999px; padding: 6px 12px; cursor: pointer; background: var(--bg-card); }
    .gw-sugg:hover { border-color: var(--gw); color: var(--gw-strong); }

    .gw-review { border: 1px solid var(--border-color); border-radius: 14px; overflow: hidden; }
    .gw-rev-row { display: flex; justify-content: space-between; gap: 14px; padding: 12px 16px; border-bottom: 1px solid var(--border-color); font-size: 13.5px; }
    .gw-rev-row:last-child { border-bottom: none; }
    .gw-rev-row span { color: var(--text-muted); font-weight: 600; }
    .gw-rev-row b { color: var(--text-primary); font-weight: 700; text-align: right; }

    .gw-nav { display: flex; align-items: center; justify-content: space-between; gap: 12px; margin-top: 26px; }
    .gw-btn { border: none; border-radius: 12px; padding: 13px 26px; font-size: 14.5px; font-weight: 800; cursor: pointer; display: inline-flex; align-items: center; gap: 9px; font-family: inherit; text-decoration: none; }
    .gw-btn.next { background: linear-gradient(135deg, var(--gw), var(--gw-strong)); color: #fff; }
    .gw-btn.back { background: transparent; color: var(--text-muted); }
    .gw-btn svg { width: 17px; height: 17px; }
    .gw-err { color: #dc2626; font-size: 12.5px; font-weight: 700; margin-top: 10px; display: none; }
    .gw-hide { display: none; }

    /* toggle rows (bidding rules) */
    .gw-toggle { display: flex; align-items: center; justify-content: space-between; gap: 12px; padding: 12px 14px; border: 1px solid var(--border-color); border-radius: 12px; margin-bottom: 10px; }
    .gw-toggle div b { display: block; font-size: 13.5px; font-weight: 800; color: var(--text-primary); }
    .gw-toggle div span { font-size: 12px; color: var(--text-muted); }
    .gw-sw { width: 42px; height: 24px; border-radius: 999px; background: var(--border-color); position: relative; cursor: pointer; flex-shrink: 0; transition: background .15s; }
    .gw-sw::after { content: ''; position: absolute; top: 2px; left: 2px; width: 20px; height: 20px; border-radius: 50%; background: #fff; transition: left .15s; box-shadow: 0 1px 3px rgba(0,0,0,.25); }
    .gw-sw.on { background: var(--gw); } .gw-sw.on::after { left: 20px; }

    /* live professional preview (how it appears on the bidding board) */
    .gw-preview-wrap { border: 1px solid var(--border-color); border-radius: 14px; overflow: hidden; margin-top: 16px; }
    .gw-preview-hd { font-size: 11.5px; font-weight: 800; color: var(--gw-strong); background: rgba(249,115,22,.08); padding: 9px 14px; }
    .gw-pvcard { display: flex; gap: 12px; padding: 14px; }
    .gw-pvcard .img { width: 70px; height: 70px; border-radius: 10px; background: var(--bg-card); border: 1px solid var(--border-color); flex-shrink: 0; display: flex; align-items: center; justify-content: center; font-size: 24px; }
    .gw-pvcard h5 { font-size: 14px; font-weight: 800; color: var(--text-primary); }
    .gw-pvcard .b { font-size: 13px; font-weight: 800; color: #16a34a; margin-top: 4px; }
    .gw-pvcard .m { font-size: 11.5px; color: var(--text-muted); margin-top: 2px; }
    .gw-stats { display: grid; grid-template-columns: repeat(4, 1fr); gap: 10px; padding: 12px 14px; border-top: 1px solid var(--border-color); }
    .gw-stat { text-align: center; } .gw-stat b { display: block; font-size: 15px; font-weight: 800; color: var(--text-primary); } .gw-stat span { font-size: 10px; color: var(--text-muted); }
</style>
@endpush

@section('content')
<div class="gw" id="gwRoot" data-ai="semi">
    @php
        $gwSteps = [
            ['AI Assist', 'How much help'],
            ['Basics', 'Basic details'],
            ['Describe', 'About the project'],
            ['Budget', 'Your budget range'],
            ['Urgency', 'How urgent'],
            ['Preferences', 'Preferred vendors'],
            ['Find Vendors', 'How we find them'],
            ['Bidding Rules', 'Control bidding'],
            ['Review', 'Preview & publish'],
        ];
    @endphp
    <div class="gw-stepper" id="gwStepper">
        @foreach($gwSteps as $i => [$t, $d])
            <div class="gw-st {{ $i === 0 ? 'active' : '' }}" data-st="{{ $i }}">
                <span class="gw-st-num">{{ $i + 1 }}</span>
                <span class="gw-st-title">{{ $t }}</span>
                <span class="gw-st-desc">{{ $d }}</span>
            </div>
        @endforeach
    </div>

    <form method="POST" action="{{ route('client.events.store') }}" id="gwForm">
        @csrf
        <input type="hidden" name="location" id="gwLocation">
        <input type="hidden" name="budget" id="gwBudgetHidden">

        <div class="gw-stage">

            {{-- 1 · AI assist --}}
            <div class="gw-card active" data-step="0">
                <span class="gw-eyebrow">🤖 AI Assist</span>
                <h2 class="gw-q">How much help do you want?</h2>
                <p class="gw-help">Fill it in yourself, or let AI do the heavy lifting — your choice.</p>
                <div class="gw-opts">
                    <label class="gw-opt" data-ai="manual"><span class="ic">✍️</span><span><b>I’ll do it myself</b><span>Fill the form manually — no AI.</span></span></label>
                    <label class="gw-opt sel" data-ai="semi"><span class="ic">✨</span><span><b>Semi-AI — assist me</b><span>Smart suggestions + AI tools as you go.</span></span></label>
                    <label class="gw-opt" data-ai="max"><span class="ic">⚡</span><span><b>Maximum AI — draft it for me</b><span>AI pre-fills everything; you review &amp; tweak.</span></span></label>
                </div>
            </div>

            {{-- 2 · Basic Information --}}
            <div class="gw-card" data-step="1" data-required="basic">
                <span class="gw-eyebrow">① Basics</span>
                <h2 class="gw-q">Tell us the basics</h2>
                <p class="gw-help">The essentials about your event.</p>
                <div class="gw-field"><label class="gw-flabel">Event Title</label><input type="text" name="title" class="gw-input" placeholder="e.g. Johnson Wedding Celebration" maxlength="255"></div>
                <div class="gw-field">
                    <label class="gw-flabel">What do you need?</label>
                    <div class="gw-chips">
                        @foreach($categories as $cat)
                            <label class="gw-chip"><input type="checkbox" name="category_ids[]" value="{{ $cat->id }}" hidden><span>{{ $cat->name }}</span><span class="tick">✓</span></label>
                        @endforeach
                    </div>
                </div>
                <div class="gw-row">
                    <div><label class="gw-flabel">Event Type</label><input class="gw-input" name="event_type" placeholder="Wedding, Corporate…"></div>
                    <div><label class="gw-flabel">Guests (approx.)</label><input type="number" class="gw-input" name="guests" placeholder="150"></div>
                </div>
                <div class="gw-row">
                    <div><label class="gw-flabel">Date</label><input type="date" name="starts_at" class="gw-input"></div>
                    <div><label class="gw-flabel">Time</label><input type="time" name="time" class="gw-input"></div>
                </div>
                <div class="gw-row">
                    <div><label class="gw-flabel">City</label><input class="gw-input" id="gwCity" placeholder="Miami, FL"></div>
                    <div><label class="gw-flabel">Venue</label><input class="gw-input" id="gwVenue" placeholder="Beach Palace Hotel"></div>
                </div>
                <div class="gw-err">Please add an event title and pick at least one service.</div>
            </div>

            {{-- 3 · Describe --}}
            <div class="gw-card" data-step="2">
                <span class="gw-eyebrow">② Describe</span>
                <h2 class="gw-q">Describe the project</h2>
                <p class="gw-help">Tell pros more about what you’re looking for.</p>
                <textarea name="description" id="gwDesc" class="gw-input" placeholder="We need a professional photographer to capture our wedding ceremony, reception, family photos and candid moments…"></textarea>
                <div class="gw-suggest gw-ai-help" style="border:none;background:none;padding:0;">
                    <span class="lbl">✨ AI suggestions — tap to add:</span>
                    <span class="gw-sugg">Add venue details</span>
                    <span class="gw-sugg">Mention guest count</span>
                    <span class="gw-sugg">Specify preferred style</span>
                    <span class="gw-sugg">List must-have moments</span>
                </div>
                <label class="gw-flabel" style="margin-top:16px;">Upload inspiration photos / documents (optional)</label>
                <div class="gw-uploads">
                    <span class="gw-upload">+</span><span class="gw-upload">+</span><span class="gw-upload">+</span>
                </div>
            </div>

            {{-- 4 · Budget --}}
            <div class="gw-card" data-step="3">
                <span class="gw-eyebrow">③ Budget</span>
                <h2 class="gw-q">What’s your budget?</h2>
                <p class="gw-help">Tell us a range, or let us recommend one.</p>
                <div class="gw-opts">
                    <label class="gw-opt sel" data-bmode="known"><span class="ic">💰</span><span><b>I know my budget</b><span>Enter a min and max range.</span></span></label>
                    <label class="gw-opt" data-bmode="recommend"><span class="ic">🤖</span><span><b>I need recommendations</b><span>We’ll suggest a realistic budget from your details.</span></span></label>
                </div>
                <div class="gw-row gw-bknown" style="margin-top:14px;">
                    <div><label class="gw-flabel">Minimum Budget</label><input type="number" id="gwBmin" class="gw-input" placeholder="$2,000"></div>
                    <div><label class="gw-flabel">Maximum Budget</label><input type="number" id="gwBmax" class="gw-input" placeholder="$3,000"></div>
                </div>
                <div class="gw-note ai">📊 Based on similar events, most clients spend between $2,000 – $3,200.</div>
            </div>

            {{-- 5 · Timeline / Urgency --}}
            <div class="gw-card" data-step="4">
                <span class="gw-eyebrow">④ Urgency</span>
                <h2 class="gw-q">How urgent is this project?</h2>
                <p class="gw-help">This sets how your request is prioritised.</p>
                <div class="gw-opts">
                    <label class="gw-opt" data-urg="ESR"><span class="ic">🔥</span><span><b>Emergency</b><span>Need someone within 24 hours.</span></span><span class="pill">ESR</span></label>
                    <label class="gw-opt" data-urg="urgent"><span class="ic">⏰</span><span><b>Urgent</b><span>Within 3 days.</span></span></label>
                    <label class="gw-opt sel" data-urg="standard"><span class="ic">📅</span><span><b>Standard</b><span>Within 1–2 weeks.</span></span></label>
                    <label class="gw-opt" data-urg="flexible"><span class="ic">🌿</span><span><b>Flexible</b><span>No rush, flexible timing.</span></span></label>
                </div>
                <div class="gw-note warn gw-hide" id="gwEsrNote">🔥 This will be listed as an <b>Emergency Request (ESR)</b>.</div>
                <input type="hidden" name="urgency" id="gwUrg" value="standard">
            </div>

            {{-- 6 · Vendor Preferences --}}
            <div class="gw-card" data-step="5">
                <span class="gw-eyebrow">⑤ Preferences</span>
                <h2 class="gw-q">Vendor preferences</h2>
                <p class="gw-help">Choose the type of vendors you prefer.</p>
                <div class="gw-chips">
                    @foreach(['Verified Vendors Only','Top Rated Vendors','Local Vendors','Minority-Owned','Woman-Owned','Veteran-Owned','Eco-Friendly'] as $pref)
                        <label class="gw-chip {{ in_array($pref, ['Verified Vendors Only','Top Rated Vendors','Local Vendors']) ? 'sel' : '' }}">
                            <input type="checkbox" name="vendor_prefs[]" value="{{ $pref }}" hidden {{ in_array($pref, ['Verified Vendors Only','Top Rated Vendors','Local Vendors']) ? 'checked' : '' }}>
                            <span>{{ $pref }}</span><span class="tick">✓</span>
                        </label>
                    @endforeach
                </div>
                <div class="gw-note ai">🛡 Ensures quality and trust in every proposal.</div>
            </div>

            {{-- 7 · How to find vendors --}}
            <div class="gw-card" data-step="6">
                <span class="gw-eyebrow">⑥ Distribution</span>
                <h2 class="gw-q">How should we find vendors?</h2>
                <p class="gw-help">Choose how vendors can receive this project.</p>
                <div class="gw-opts">
                    <label class="gw-opt" data-find="open"><span class="ic">🌐</span><span><b>Open Bidding</b><span>Anyone qualified can bid.</span></span></label>
                    <label class="gw-opt" data-find="direct"><span class="ic">✉️</span><span><b>Direct Offers</b><span>We recommend vendors and invite them.</span></span></label>
                    <label class="gw-opt" data-find="invite"><span class="ic">👤</span><span><b>Invite Only</b><span>You select who receives the project.</span></span></label>
                    <label class="gw-opt sel" data-find="ai"><span class="ic">✨</span><span><b>AI Match</b><span>AI sends it to the best-fit vendors.</span></span><span class="pill">Recommended</span></label>
                    <label class="gw-opt" data-find="hybrid"><span class="ic">🔀</span><span><b>Hybrid</b><span>AI recommendations while keeping it open.</span></span></label>
                </div>
                <input type="hidden" name="find_method" id="gwFind" value="ai">
            </div>

            {{-- 8 · Bidding Rules --}}
            <div class="gw-card" data-step="7">
                <span class="gw-eyebrow">⑦ Bidding Rules</span>
                <h2 class="gw-q">Control how bidding works</h2>
                <p class="gw-help">Decide what professionals can see while they bid.</p>
                <div class="gw-toggle">
                    <div><b>🔒 Sealed bidding</b><span>Hide competitor bids — get more honest, quality proposals.</span></div>
                    <span class="gw-sw" data-toggle="gwSealed"></span>
                </div>
                <div class="gw-toggle">
                    <div><b>Hide my budget from pros</b><span>Pros bid blind to your budget range.</span></div>
                    <span class="gw-sw" data-toggle="gwHideBudget"></span>
                </div>
                <div class="gw-toggle">
                    <div><b>Allow questions from pros</b><span>Let professionals ask clarifying questions.</span></div>
                    <span class="gw-sw on" data-toggle="gwAllowQ"></span>
                </div>
                <input type="hidden" name="sealed" id="gwSealed" value="0">
                <input type="hidden" name="hide_budget" id="gwHideBudget" value="0">
                <input type="hidden" name="allow_questions" id="gwAllowQ" value="1">
            </div>

            {{-- 9 · Review + Professional Preview --}}
            <div class="gw-card" data-step="8">
                <span class="gw-eyebrow">✓ Review</span>
                <h2 class="gw-q">Review &amp; publish</h2>
                <p class="gw-help">Check everything’s right — you can go back to any card to edit.</p>
                <div class="gw-review" id="gwReview"></div>

                <div class="gw-preview-wrap">
                    <div class="gw-preview-hd">👀 How professionals will see it on the Bidding Board</div>
                    <div class="gw-pvcard">
                        <div class="img">🎉</div>
                        <div>
                            <h5 id="gwPvTitle">Your gig</h5>
                            <div class="b" id="gwPvBudget">Budget</div>
                            <div class="m" id="gwPvMeta">Location · Date</div>
                        </div>
                    </div>
                    <div class="gw-stats">
                        <div class="gw-stat"><b>~150</b><span>Est. Views</span></div>
                        <div class="gw-stat"><b>8–14</b><span>Est. Bids</span></div>
                        <div class="gw-stat"><b>Medium</b><span>Competition</span></div>
                        <div class="gw-stat"><b>2h 15m</b><span>Avg. Response</span></div>
                    </div>
                </div>
            </div>
        </div>

        <div class="gw-nav">
            <button type="button" class="gw-btn back" id="gwBack" style="visibility:hidden;">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><line x1="19" y1="12" x2="5" y2="12"/><polyline points="12 19 5 12 12 5"/></svg> Back
            </button>
            <button type="button" class="gw-btn next" id="gwNext">
                Next <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg>
            </button>
            <button type="submit" class="gw-btn next" id="gwSubmit" style="display:none;">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg> Publish Gig
            </button>
        </div>
    </form>
</div>
@endsection

@push('scripts')
<script>
(function () {
    var form = document.getElementById('gwForm');
    var root = document.getElementById('gwRoot');
    var cards = [].slice.call(form.querySelectorAll('.gw-card'));
    var total = cards.length, cur = 0;
    var steps = [].slice.call(document.querySelectorAll('.gw-st'));
    var back = document.getElementById('gwBack'), next = document.getElementById('gwNext'), submit = document.getElementById('gwSubmit');

    // generic multi-select chips
    form.querySelectorAll('.gw-chip').forEach(function (chip) {
        chip.addEventListener('click', function () { var cb = chip.querySelector('input'); cb.checked = !cb.checked; chip.classList.toggle('sel', cb.checked); });
    });

    // radio-card groups: AI level, budget mode, urgency, find method
    function radioGroup(selector, onPick) {
        var opts = form.querySelectorAll(selector);
        opts.forEach(function (opt) {
            opt.addEventListener('click', function () {
                opts.forEach(function (o) { o.classList.toggle('sel', o === opt); });
                onPick(opt);
            });
        });
    }
    radioGroup('.gw-opt[data-ai]', function (o) { root.setAttribute('data-ai', o.getAttribute('data-ai')); });
    radioGroup('.gw-opt[data-bmode]', function (o) {
        form.querySelector('.gw-bknown').style.display = o.getAttribute('data-bmode') === 'known' ? '' : 'none';
    });
    radioGroup('.gw-opt[data-urg]', function (o) {
        var v = o.getAttribute('data-urg'); document.getElementById('gwUrg').value = v;
        document.getElementById('gwEsrNote').classList.toggle('gw-hide', v !== 'ESR');
    });
    radioGroup('.gw-opt[data-find]', function (o) { document.getElementById('gwFind').value = o.getAttribute('data-find'); });

    // bidding-rule toggle switches
    form.querySelectorAll('.gw-sw').forEach(function (sw) {
        sw.addEventListener('click', function () {
            sw.classList.toggle('on');
            var input = document.getElementById(sw.getAttribute('data-toggle'));
            if (input) input.value = sw.classList.contains('on') ? '1' : '0';
        });
    });

    // AI suggestion chips → append to description
    var desc = document.getElementById('gwDesc');
    form.querySelectorAll('.gw-sugg').forEach(function (s) {
        s.addEventListener('click', function () { if (desc) { desc.value = (desc.value ? desc.value.replace(/\s*$/, '') + '\n' : '') + '• ' + s.textContent.trim() + ': '; desc.focus(); } });
    });

    function show(idx) {
        cards.forEach(function (c, i) { c.classList.toggle('active', i === idx); c.classList.toggle('leaving', i < idx); });
        cur = idx;
        steps.forEach(function (s, i) { s.classList.toggle('done', i < idx); s.classList.toggle('active', i === idx); });
        if (steps[idx]) steps[idx].scrollIntoView({ inline: 'center', block: 'nearest' });
        back.style.visibility = idx === 0 ? 'hidden' : 'visible';
        var last = idx === total - 1;
        next.style.display = last ? 'none' : 'inline-flex';
        submit.style.display = last ? 'inline-flex' : 'none';
        if (last) buildReview();
    }

    function validate(idx) {
        var card = cards[idx], req = card.getAttribute('data-required'), err = card.querySelector('.gw-err'), ok = true;
        if (req === 'basic') ok = form.title.value.trim().length > 0 && form.querySelectorAll('input[name="category_ids[]"]:checked').length > 0;
        if (err) err.style.display = ok ? 'none' : 'block';
        return ok;
    }

    function syncHidden() {
        var city = document.getElementById('gwCity').value.trim(), venue = document.getElementById('gwVenue').value.trim();
        document.getElementById('gwLocation').value = [venue, city].filter(Boolean).join(', ');
        var bmin = document.getElementById('gwBmin').value, bmax = document.getElementById('gwBmax').value;
        document.getElementById('gwBudgetHidden').value = bmax || bmin || '';
    }

    function buildReview() {
        syncHidden();
        var svcs = [].slice.call(form.querySelectorAll('input[name="category_ids[]"]:checked')).map(function (c) { return c.parentElement.querySelector('span').textContent; });
        var prefs = [].slice.call(form.querySelectorAll('input[name="vendor_prefs[]"]:checked')).map(function (c) { return c.value; });
        var rows = [
            ['Event', form.title.value || '—'],
            ['Services', svcs.length ? svcs.join(', ') : '—'],
            ['When', (form.starts_at.value || 'Flexible') + (form.time.value ? ' · ' + form.time.value : '')],
            ['Location', document.getElementById('gwLocation').value || '—'],
            ['Budget', (document.getElementById('gwBmin').value || document.getElementById('gwBmax').value) ? ('$' + (document.getElementById('gwBmin').value||'?') + ' – $' + (document.getElementById('gwBmax').value||'?')) : 'Recommend for me'],
            ['Urgency', document.getElementById('gwUrg').value],
            ['Vendor prefs', prefs.length ? prefs.join(', ') : 'Any'],
            ['Distribution', document.getElementById('gwFind').value],
        ];
        document.getElementById('gwReview').innerHTML = rows.map(function (r) {
            return '<div class="gw-rev-row"><span>' + r[0] + '</span><b>' + (r[1] + '').replace(/</g, '&lt;') + '</b></div>';
        }).join('');

        // Live "how professionals see it" preview card
        var bmin = document.getElementById('gwBmin').value, bmax = document.getElementById('gwBmax').value;
        document.getElementById('gwPvTitle').textContent = form.title.value || 'Your gig';
        document.getElementById('gwPvBudget').textContent = (bmin || bmax) ? ('$' + (bmin || '?') + ' – $' + (bmax || '?')) : 'Budget on request';
        document.getElementById('gwPvMeta').textContent = [document.getElementById('gwLocation').value, form.starts_at.value].filter(Boolean).join(' · ') || 'Location · Date';
    }

    next.addEventListener('click', function () { if (validate(cur) && cur < total - 1) show(cur + 1); });
    back.addEventListener('click', function () { if (cur > 0) show(cur - 1); });
    form.addEventListener('submit', syncHidden);
    show(0);
})();
</script>
@endpush
