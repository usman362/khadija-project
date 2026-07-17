@extends('layouts.professional')

@php $p = $package ?? null; $editing = (bool) $p; @endphp

@section('title', $editing ? 'Edit Package' : 'Create a Package')
@section('page-title', $editing ? 'Edit Package' : 'Create a Package for Package Search')
@section('page-subtitle', 'Build a ready-made service bundle clients can discover and book')

{{-- Professional — Create a Package for Package Search. A pro bundles 2+ of
     their own services (solo — one professional) into a fixed offering that
     appears in the client Package Service Search. NOT an MSR. --}}

@php
    $pServices   = old('services', $p?->services ?? []);
    $pEventTypes = old('event_types', $p?->event_types ?? []);
    $pIncludes   = old('includes', $p?->includes ?? []);
    $gMin = old('guest_min'); $gMax = old('guest_max');
@endphp

@push('styles')
<style>
    .pc { --pc: #2563eb; --pc-dark: #1d4ed8; --pc-soft: #eff6ff; }
    .pc-grid { display: grid; grid-template-columns: minmax(0,1fr) 320px; gap: 22px; align-items: start; }

    .pc-stepper { display: flex; align-items: center; background: var(--bg-card); border: 1px solid var(--border-color); border-radius: 14px; padding: 16px 20px; margin-bottom: 20px; }
    .pc-step { display: flex; align-items: center; gap: 11px; flex: 1; cursor: pointer; }
    .pc-step .num { width: 30px; height: 30px; border-radius: 50%; background: var(--bg-card-hover, #e5e7eb); color: var(--text-muted); font-weight: 800; font-size: 14px; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
    .pc-step.on .num, .pc-step.done .num { background: var(--pc); color: #fff; }
    .pc-step .lbl b { display: block; font-size: 13.5px; font-weight: 800; color: var(--text-muted); line-height: 1.2; }
    .pc-step.on .lbl b, .pc-step.done .lbl b { color: var(--text-primary); }
    .pc-step .lbl span { font-size: 11.5px; color: var(--text-muted); }
    .pc-step .bar { flex: 1; height: 2px; background: var(--border-color); margin: 0 6px; }

    .pc-panel { display: none; }
    .pc-panel.on { display: block; }
    .pc-card { background: var(--bg-card); border: 1px solid var(--border-color); border-radius: 16px; padding: 22px; margin-bottom: 18px; }
    .pc-card h3 { font-size: 16px; font-weight: 800; color: var(--text-primary); margin: 0 0 18px; }
    .pc-field { margin-bottom: 18px; }
    .pc-field label { display: block; font-size: 13px; font-weight: 700; color: var(--text-primary); margin-bottom: 7px; }
    .pc-field label .req { color: #dc2626; }
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
    .pc-svccount.bad b { color: #dc2626; }

    .pc-rep-item { display: flex; gap: 8px; margin-bottom: 8px; }
    .pc-rep-item input { flex: 1; }
    .pc-rep-del { border: 1px solid var(--border-color); background: var(--bg-card); border-radius: 9px; padding: 0 12px; color: var(--text-muted); cursor: pointer; }
    .pc-addbtn { border: 1px dashed var(--border-color); background: var(--bg-card); border-radius: 9px; padding: 9px 14px; font-size: 13px; font-weight: 700; color: var(--pc); cursor: pointer; }

    .pc-actions { display: flex; align-items: center; justify-content: space-between; gap: 12px; margin-top: 6px; }
    .pc-btn { border-radius: 11px; padding: 12px 22px; font-size: 14px; font-weight: 800; cursor: pointer; border: none; text-decoration: none; display: inline-flex; align-items: center; gap: 8px; }
    .pc-btn-primary { background: var(--pc); color: #fff; }
    .pc-btn-primary:hover { background: var(--pc-dark); }
    .pc-btn-ghost { background: var(--bg-card); color: var(--text-secondary); border: 1px solid var(--border-color); }
    .pc-head { display: flex; align-items: center; justify-content: flex-end; gap: 10px; margin-bottom: 16px; }

    .pc-side { display: flex; flex-direction: column; gap: 16px; position: sticky; top: 84px; }
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
    .pc-list svg { width: 15px; height: 15px; color: #16a34a; flex-shrink: 0; margin-top: 1px; }
    .pc-tips li { position: relative; padding-left: 15px; margin-bottom: 7px; font-size: 12px; color: var(--text-secondary); }
    .pc-tips li::before { content: "•"; position: absolute; left: 2px; color: var(--pc); }
    .pc-help p { font-size: 12px; color: var(--text-muted); margin: 0 0 8px; }
    .pc-help a { font-size: 12.5px; font-weight: 800; color: var(--pc); text-decoration: none; }

    @media (max-width: 1080px) { .pc-grid { grid-template-columns: 1fr; } .pc-side { position: static; } .pc-step .lbl span { display: none; } }
    @media (max-width: 640px) { .pc-two, .pc-typegrid { grid-template-columns: 1fr; } }
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

    <div class="pc-head">
        <a href="{{ route('professional.packages.index') }}" class="pc-btn pc-btn-ghost">Cancel</a>
        <button type="submit" name="is_active" value="0" class="pc-btn pc-btn-ghost">Save as Draft</button>
    </div>

    <div class="pc-stepper" id="pcStepper">
        <div class="pc-step on" data-step="1"><span class="num">1</span><span class="lbl"><b>Package Details</b><span>Basic information</span></span></div>
        <span class="bar"></span>
        <div class="pc-step" data-step="2"><span class="num">2</span><span class="lbl"><b>Services Included</b><span>Add multiple services</span></span></div>
        <span class="bar"></span>
        <div class="pc-step" data-step="3"><span class="num">3</span><span class="lbl"><b>Pricing &amp; Options</b><span>Set pricing &amp; add-ons</span></span></div>
        <span class="bar"></span>
        <div class="pc-step" data-step="4"><span class="num">4</span><span class="lbl"><b>Availability &amp; Coverage</b><span>When &amp; where you serve</span></span></div>
    </div>

    <div class="pc-grid">
        <div>
            {{-- STEP 1 --}}
            <div class="pc-panel on" data-panel="1">
                <div class="pc-card">
                    <h3>Package Information</h3>
                    <div class="pc-field">
                        <label>Package Name <span class="req">*</span></label>
                        <input type="text" name="title" id="pcName" class="pc-input" maxlength="160" required
                               value="{{ old('title', $p?->title) }}" placeholder="e.g. Elegant Wedding Photo &amp; Video Package">
                        <div class="hint">Choose a clear, attractive name for your package.</div>
                    </div>

                    {{-- Packages are solo-only: one professional provides all included
                         services (Team/Co-Op "combined force" removed platform-wide). --}}
                    <input type="hidden" name="type" value="solo">

                    <div class="pc-field">
                        <label>Short Description <span class="req">*</span></label>
                        <textarea name="description" id="pcDesc" class="pc-textarea" maxlength="200" oninput="pcCount()"
                                  placeholder="Describe what makes this package great…">{{ old('description', $p?->description) }}</textarea>
                        <div class="pc-counter"><span id="pcDescCount">0</span>/200</div>
                    </div>

                    <div class="pc-field">
                        <label>Event Types This Package Is Perfect For</label>
                        <div class="pc-chips">
                            @foreach($eventTypes as $et)
                                <label class="pc-chipbox">
                                    <input type="checkbox" name="event_types[]" value="{{ $et }}" @checked(in_array($et, $pEventTypes))>
                                    <span>{{ $et }}</span>
                                </label>
                            @endforeach
                        </div>
                    </div>

                    <div class="pc-two">
                        <div class="pc-field">
                            <label>Minimum Guest Count</label>
                            <input type="number" name="guest_min" class="pc-input" min="0" value="{{ $gMin }}" placeholder="50">
                            <div class="hint">Leave blank if not applicable</div>
                        </div>
                        <div class="pc-field">
                            <label>Maximum Guest Count</label>
                            <input type="number" name="guest_max" class="pc-input" min="0" value="{{ $gMax }}" placeholder="150">
                            <div class="hint">Leave blank if unlimited</div>
                        </div>
                    </div>
                </div>
                <div class="pc-actions">
                    <span></span>
                    <button type="button" class="pc-btn pc-btn-primary" data-next="2">Save &amp; Continue to Services →</button>
                </div>
            </div>

            {{-- STEP 2 --}}
            <div class="pc-panel" data-panel="2">
                <div class="pc-card">
                    <h3>Services Included in This Package</h3>
                    <div class="hint" style="margin-bottom:14px;">Select 2 or more services included in this package. These power the client Service-Mix Matcher.</div>
                    <div id="pcSvcList">
                        @foreach($serviceList as $svc)
                            <label class="pc-svcrow {{ in_array($svc, $pServices) ? '' : 'off' }}" data-svcrow>
                                <span class="ico"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 6 9 17l-5-5"/></svg></span>
                                <span class="meta"><b>{{ $svc }}</b><span>Included in this package</span></span>
                                <input type="checkbox" name="services[]" value="{{ $svc }}" style="display:none;" @checked(in_array($svc, $pServices)) onchange="pcSvcSync()">
                                <span class="pc-toggle {{ in_array($svc, $pServices) ? 'on' : '' }}" onclick="pcSvcToggle(this)"></span>
                            </label>
                        @endforeach
                    </div>
                    <div class="pc-svccount" id="pcSvcCount">Total Services: <b>0</b> · Minimum 2 required to appear in Package Search</div>
                </div>
                <div class="pc-actions">
                    <button type="button" class="pc-btn pc-btn-ghost" data-prev="1">← Back</button>
                    <button type="button" class="pc-btn pc-btn-primary" data-next="3">Save &amp; Continue to Pricing →</button>
                </div>
            </div>

            {{-- STEP 3 --}}
            <div class="pc-panel" data-panel="3">
                <div class="pc-card">
                    <h3>Pricing &amp; Options</h3>
                    <div class="pc-two">
                        <div class="pc-field">
                            <label>Total Package Price <span class="req">*</span></label>
                            <input type="number" name="price" id="pcPrice" class="pc-input" min="0" required value="{{ old('price', $p?->price) }}" placeholder="3250" oninput="pcPreview()">
                        </div>
                        <div class="pc-field">
                            <label>Price Basis <span class="req">*</span></label>
                            <select name="price_unit" class="pc-select">
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
                    <button type="button" class="pc-btn pc-btn-ghost" data-prev="2">← Back</button>
                    <button type="button" class="pc-btn pc-btn-primary" data-next="4">Save &amp; Continue to Availability →</button>
                </div>
            </div>

            {{-- STEP 4 --}}
            <div class="pc-panel" data-panel="4">
                <div class="pc-card">
                    <h3>Availability &amp; Coverage</h3>
                    <div class="pc-two">
                        <div class="pc-field">
                            <label>Event Coverage</label>
                            <input type="text" name="coverage" class="pc-input" maxlength="80" value="{{ old('coverage', $p?->coverage) }}" placeholder="Up to 10 Hours">
                        </div>
                        <div class="pc-field">
                            <label>Availability</label>
                            <input type="text" name="availability" class="pc-input" maxlength="80" value="{{ old('availability', $p?->availability) }}" placeholder="Available Weekends">
                        </div>
                    </div>
                    <div class="pc-field">
                        <label>Serves Regions</label>
                        <input type="text" name="serves_regions" class="pc-input" maxlength="120" value="{{ old('serves_regions', $p?->serves_regions) }}" placeholder="NY, NJ, CT">
                        <div class="hint">Comma-separated states/regions you'll travel to.</div>
                    </div>
                    <div class="pc-field">
                        <label>Cover Photo</label>
                        <input type="file" name="cover_image" class="pc-input" accept="image/*">
                        <div class="hint">A high-quality hero image makes your package stand out. JPG/PNG/WebP, up to 6 MB.</div>
                    </div>
                </div>
                <div class="pc-actions">
                    <button type="button" class="pc-btn pc-btn-ghost" data-prev="3">← Back</button>
                    <button type="submit" class="pc-btn pc-btn-primary">{{ $editing ? 'Update Package' : 'Publish Package' }} ✓</button>
                </div>
            </div>
        </div>

        {{-- Right rail --}}
        <aside class="pc-side">
            <div class="pc-scard">
                <h4>👁 Package Preview</h4>
                <div class="pc-prev-media" id="pcPrevMedia">Cover photo preview</div>
                <div class="pc-prev-title" id="pcPrevTitle">{{ old('title', $p?->title) ?: 'Your package name' }}</div>
                <span class="pc-prev-type" id="pcPrevType">Service Package</span>
                <div class="pc-prev-svc"><span id="pcPrevSvcCount">0</span> Services Included</div>
                <div class="pc-prev-price">$<span id="pcPrevPrice">{{ old('price', $p?->price) ?: '0' }}</span><small>Total Package Price</small></div>
            </div>

            <div class="pc-scard">
                <h4>💡 Package Highlights</h4>
                <ul class="pc-list">
                    @foreach(['Appears in Package Search results', 'One contract. One payment.', 'Clients can customize add-ons', 'You control your availability', 'Build trust with verified badge'] as $h)
                        <li><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"/></svg>{{ $h }}</li>
                    @endforeach
                </ul>
            </div>

            <div class="pc-scard">
                <h4>✨ Tips for a Great Package</h4>
                <ul class="pc-tips" style="list-style:none;padding:0;margin:0;">
                    <li>Include at least 2 complementary services</li>
                    <li>Use high-quality photos and clear descriptions</li>
                    <li>Offer add-ons to increase package value</li>
                    <li>Set competitive, transparent pricing</li>
                </ul>
            </div>

            <div class="pc-scard pc-help">
                <h4>🎧 Need Help?</h4>
                <p>Our support team is here to help you create the perfect package.</p>
                <a href="{{ route('professional.threads.index') }}">Contact Support →</a>
            </div>
        </aside>
    </div>
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
        window.scrollTo({ top: 0, behavior: 'smooth' });
    }
    document.querySelectorAll('[data-next]').forEach(function (b) { b.addEventListener('click', function () { goStep(b.dataset.next); }); });
    document.querySelectorAll('[data-prev]').forEach(function (b) { b.addEventListener('click', function () { goStep(b.dataset.prev); }); });
    document.querySelectorAll('.pc-step').forEach(function (s) { s.addEventListener('click', function () { goStep(s.dataset.step); }); });


    window.pcCount = function () {
        var d = document.getElementById('pcDesc');
        document.getElementById('pcDescCount').textContent = d.value.length;
    };
    pcCount();

    window.pcPreview = function () {
        document.getElementById('pcPrevPrice').textContent = Number(document.getElementById('pcPrice').value || 0).toLocaleString();
    };
    document.getElementById('pcName').addEventListener('input', function () {
        document.getElementById('pcPrevTitle').textContent = this.value || 'Your package name';
    });
    document.querySelector('input[name=cover_image]').addEventListener('change', function (e) {
        var f = e.target.files[0]; if (!f) return;
        document.getElementById('pcPrevMedia').innerHTML = '<img src="' + URL.createObjectURL(f) + '" alt="">';
    });

    window.pcSvcToggle = function (el) {
        var cb = el.parentElement.querySelector('input[type=checkbox]');
        cb.checked = !cb.checked;
        el.classList.toggle('on', cb.checked);
        el.closest('[data-svcrow]').classList.toggle('off', !cb.checked);
        pcSvcSync();
    };
    window.pcSvcSync = function () {
        var n = document.querySelectorAll('#pcSvcList input[type=checkbox]:checked').length;
        var box = document.getElementById('pcSvcCount');
        box.innerHTML = 'Total Services: <b>' + n + '</b> · Minimum 2 required to appear in Package Search';
        box.classList.toggle('bad', n < 2);
        document.getElementById('pcPrevSvcCount').textContent = n;
    };
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
