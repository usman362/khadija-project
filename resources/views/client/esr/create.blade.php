@extends('layouts.client')
@section('title', 'Post a Rush Request (ER)')
@section('page-title', 'Emergency Request (ER)')
@section('page-subtitle', 'For urgent needs within 72 hours.')

@push('styles')
<style>
    .esr { max-width: 100%; margin: 0 auto; }
    /* Fill large screens: form + contextual rail side-by-side, stacks on narrow. */
    .esr-layout { display: grid; grid-template-columns: minmax(0,1fr) 340px; gap: 20px; align-items: start; }
    .esr-rail { display: flex; flex-direction: column; gap: 14px; position: sticky; top: 88px; }
    .esr-rcard { background: var(--bg-card,#fff); border: 1px solid var(--border-color,#e5e7eb); border-radius: 16px; padding: 18px; }
    .esr-rcard h4 { font-size: 13px; font-weight: 800; color: var(--text-primary,#111827); margin-bottom: 13px; display:flex; align-items:center; gap:8px; }
    .esr-rcard h4 svg { width:16px; height:16px; color:var(--brand-text); }
    .esr-step { display: flex; gap: 11px; margin-bottom: 12px; }
    .esr-step:last-child { margin-bottom: 0; }
    .esr-step-n { flex-shrink:0; width:24px; height:24px; border-radius:50%; background:rgba(234,88,12,0.12); color:var(--brand-text); font-size:12px; font-weight:800; display:flex; align-items:center; justify-content:center; }
    .esr-step-b { font-size:12.5px; color:var(--text-secondary,#4b5563); line-height:1.45; }
    .esr-step-b b { color:var(--text-primary,#111827); display:block; font-size:12.5px; margin-bottom:1px; }
    .esr-rlist { list-style:none; padding:0; margin:0; display:flex; flex-direction:column; gap:10px; }
    .esr-rlist li { display:flex; gap:8px; font-size:12.5px; color:var(--text-secondary,#4b5563); line-height:1.4; }
    .esr-rlist svg { width:14px; height:14px; color:var(--ok-text); flex-shrink:0; margin-top:2px; }
    .esr-alert { display:flex; gap:12px; align-items:flex-start; background:rgba(234,88,12,0.10); border:1px solid rgba(234,88,12,0.28); border-radius:14px; padding:14px 16px; margin-bottom:18px; }
    .esr-alert svg { width:22px; height:22px; color:var(--brand-text); flex-shrink:0; margin-top:1px; }
    .esr-alert b { color:var(--brand-text); }
    .esr-alert p { margin:2px 0 0; font-size:13px; color:var(--text-secondary); }
    .esr-card { background:var(--bg-card,#fff); border:1px solid var(--border-color,#e5e7eb); border-radius:16px; padding:22px; margin-bottom:16px; }
    .esr-sec-h { font-size:12px; font-weight:800; letter-spacing:.4px; text-transform:uppercase; color:var(--brand-text); margin-bottom:14px; display:flex; align-items:center; gap:8px; }
    .esr-field { margin-bottom:14px; }
    .esr-field label { display:block; font-size:12.5px; font-weight:700; color:var(--text-primary,#111827); margin-bottom:6px; }
    .esr-req { color:var(--brand-text); }
    .esr-input, .esr-select, .esr-textarea { width:100%; border:1px solid var(--border-color,#e5e7eb); border-radius:10px; padding:11px 12px; font-size:14px; font-family:inherit; color:var(--text-primary,#111827); background:var(--bg-card,#fff); outline:none; }
    .esr-input:focus, .esr-select:focus, .esr-textarea:focus { border-color:#f97316; box-shadow:0 0 0 3px rgba(249,115,22,.12); }
    .esr-hint { font-size:11.5px; color:var(--text-secondary,#6b7280); margin:5px 0 0; line-height:1.45; }
    .esr-err { font-size:11.5px; color:#b91c1c; font-weight:600; margin:5px 0 0; line-height:1.45; }
    .esr-textarea { min-height:80px; resize:vertical; }
    .esr-grid2 { display:grid; grid-template-columns:1fr 1fr; gap:12px; }
    .esr-grid3 { display:grid; grid-template-columns:1fr 1fr 1fr; gap:12px; }
    .esr-reasons { display:grid; grid-template-columns:1fr 1fr; gap:10px; }
    .esr-reason { display:flex; gap:10px; align-items:flex-start; border:1.5px solid var(--border-color,#e5e7eb); border-radius:12px; padding:12px; cursor:pointer; font-size:13px; }
    .esr-reason:has(input:checked) { border-color:#ea580c; background:rgba(234,88,12,0.10); }
    .esr-reason input { margin-top:2px; accent-color:#ea580c; }
    .esr-services { display:grid; grid-template-columns:repeat(auto-fill,minmax(170px,1fr)); gap:8px; max-height:230px; overflow-y:auto; padding:2px; }
    .esr-svc { display:flex; gap:8px; align-items:center; border:1.5px solid var(--border-color,#e5e7eb); border-radius:10px; padding:9px 11px; cursor:pointer; font-size:13px; }
    .esr-svc:has(input:checked) { border-color:#f97316; background:rgba(249,115,22,0.10); }
    .esr-svc input { accent-color:#f97316; }
    /* Single vs multi scope chooser — an ER can be either, same as SSR/MSR. */
    .esr-scope { display:grid; grid-template-columns:1fr 1fr; gap:12px; margin-bottom:16px; }
    .esr-scope-o { border:2px solid var(--border-color,#e5e7eb); border-radius:14px; padding:15px; cursor:pointer; background:var(--bg-card,#fff); transition:border-color .15s, background .15s; }
    .esr-scope-o:hover { border-color:#f97316; }
    .esr-scope-o.sel { border-color:#ea580c; background:rgba(234,88,12,0.10); }
    .esr-scope-code { display:inline-flex; align-items:center; gap:6px; font-size:11.5px; font-weight:800; letter-spacing:.3px; color:var(--brand-text); background:rgba(234,88,12,.12); padding:3px 10px; border-radius:999px; }
    .esr-scope-o h5 { font-size:14px; font-weight:800; color:var(--text-primary,#111827); margin:9px 0 5px; }
    .esr-scope-o p { font-size:12px; color:var(--text-muted,#6b7280); line-height:1.45; margin:0; }
    /* Copy that belongs to only one scope. */
    .esr[data-scope="single"] [data-scope-only="multi"],
    .esr[data-scope="multi"]  [data-scope-only="single"] { display:none; }
    @media (max-width:760px){ .esr-scope { grid-template-columns:1fr; } }
    .esr-foot { display:flex; justify-content:space-between; align-items:center; gap:12px; flex-wrap:wrap; }
    .esr-fees { font-size:12.5px; color:var(--text-muted,#6b7280); }
    .esr-fees b { color:var(--text-primary,#111827); }
    .esr-btn { display:inline-flex; align-items:center; gap:8px; background:linear-gradient(135deg,#f97316,#ea580c); color:#fff; border:none; border-radius:12px; padding:13px 26px; font-size:14.5px; font-weight:800; cursor:pointer; }
    .esr-btn:hover { filter:brightness(1.05); }
    @media (max-width:1024px){ .esr-layout { grid-template-columns: 1fr; } .esr-rail { position: static; flex-direction: row; flex-wrap: wrap; } .esr-rcard { flex:1; min-width: 240px; } }
    @media (max-width:640px){ .esr-grid3,.esr-grid2,.esr-reasons{ grid-template-columns:1fr; } .esr-rail{ flex-direction:column; } }
</style>
@endpush

@section('content')
<div class="esr" data-scope="{{ $scope }}">
    {{-- Validation errors are rendered once, by layouts.client, for every
         page. This screen used to print its own copy as well, so a failed
         submit showed the same sentence twice. --}}

    <div class="esr-alert">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M10.29 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
        <div>
            <b>This is an Emergency Request.</b>
            <p>Use this only for genuine time-sensitive needs within the next 72 hours. Verified professionals are notified with priority, and you'll see responses on your Proposals page.</p>
        </div>
    </div>

    <div class="esr-layout">
    <form method="POST" action="{{ route('client.esr.store') }}">
        @csrf

        {{-- 0. Single or multi. A rush request can be either — one urgent gap to
             fill, or several at once. Unlike a Direct Request it still publishes to
             the board and every professional bids; it is not aimed at one pro. --}}
        <div class="esr-scope">
            <div class="esr-scope-o {{ $scope === 'single' ? 'sel' : '' }}" data-scope-pick="single">
                <span class="esr-scope-code">SINGLE SERVICE</span>
                <h5>One urgent service</h5>
                <p>One gap to fill right now: a replacement DJ, a caterer, a van. Handled as a single agreement.</p>
            </div>
            <div class="esr-scope-o {{ $scope === 'multi' ? 'sel' : '' }}" data-scope-pick="multi">
                <span class="esr-scope-code">MULTI-SERVICE</span>
                <h5>Several urgent services</h5>
                <p>More than one thing fell through. Each service is bid on and agreed separately.</p>
            </div>
        </div>
        <input type="hidden" name="scope" id="esrScope" value="{{ $scope }}">

        {{-- 1. Emergency & timing --}}
        <div class="esr-card">
            <x-form-section :n="1" title="Emergency & Timing" required />
            <div class="esr-field">
                <label for="esrOrgType">This request is for <span class="esr-req">*</span></label>
                <select name="organization_type" id="esrOrgType" class="esr-input" required>
                    @foreach($orgTypes as $key => $label)
                        <option value="{{ $key }}" @selected(old('organization_type', 'individual') === $key)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="esr-field">
                <label>Why is this urgent? <span class="esr-req">*</span></label>
                <div class="esr-reasons">
                    @foreach($reasons as $key => $label)
                        <label class="esr-reason"><input type="radio" name="reason" value="{{ $key }}" @checked(old('reason')===$key) required><span>{{ $label }}</span></label>
                    @endforeach
                </div>
            </div>
            {{-- The name of the event. It used to be built from the services
                 picked -- "Urgent: Buffet Catering" -- so the professional saw
                 a label nobody wrote and the client could not correct it. The
                 bidding and direct forms both ask; this one now does too. --}}
            <div class="esr-field">
                <label>Event Name <span class="esr-req">*</span></label>
                <input name="event_name" class="esr-input" value="{{ old('event_name') }}"
                       maxlength="200" placeholder="e.g. Corporate Lunch: Friday" required>
                @error('event_name')<p class="esr-err">{{ $message }}</p>@enderror
            </div>

            <div class="esr-grid3">
                @php $esrBuffer = (int) config('bsr.esr.closes_hours_before_start'); @endphp
                <div class="esr-field">
                    <label for="esr-needed-by">Needed by <span class="esr-req">*</span></label>
                    <input type="datetime-local" id="esr-needed-by" name="needed_by" class="esr-input" required
                           min="{{ now()->addHours($esrBuffer)->format('Y-m-d\TH:i') }}"
                           value="{{ old('needed_by') }}"
                           aria-describedby="esr-needed-by-hint">
                    {{-- Rule R7. Said before they submit, not only after it is refused. --}}
                    <p class="esr-hint" id="esr-needed-by-hint">At least {{ $esrBuffer }} hours from now, so professionals have time to reach you.</p>
                    @error('needed_by')<p class="esr-err">{{ $message }}</p>@enderror
                </div>
                <div class="esr-field"><label>Location</label><input name="location" class="esr-input" value="{{ old('location') }}" placeholder="Venue or city"></div>
                {{-- The state selector is gone — see the BR wizard for why.
                     Every request matches on the client's own home state, so
                     choosing one here changed nothing. --}}
                @php $__homeState = config('geo.allowed_states')[auth()->user()?->profile?->state] ?? null; @endphp
                @if($__homeState)
                    <div class="esr-field"><label>Who this reaches</label>
                        <p style="font-size:12px;color:var(--text-muted);margin:6px 0 0;line-height:1.5;">
                            Professionals in <b>{{ $__homeState }}</b>. GigResource works within one state for now.
                        </p>
                    </div>
                @endif
                <div class="esr-field"><label>Guest count</label><input type="number" name="guest_count" class="esr-input" value="{{ old('guest_count') }}" placeholder="e.g. 150"></div>
            </div>
        </div>

        {{-- 2. Services --}}
        <div class="esr-card">
            <x-form-section :n="2" required><span data-scope-only="single">The Service You Need</span><span data-scope-only="multi">Services You Need</span></x-form-section>
            <p style="font-size:12.5px;color:var(--text-muted,#6b7280);margin:-6px 0 12px;">
                <span data-scope-only="single">Pick the one service you need covered, choosing another replaces it.</span>
                <span data-scope-only="multi">Pick every service you need covered. Each one is bid on separately.</span>
            </p>
            <x-service-picker :categories="$categories" name="services" :selected="old('services', [])" :single="$scope === 'single'" />
        </div>

        {{-- 3. Budget & details --}}
        <div class="esr-card">
            <x-form-section :n="3" title="Budget & Details" />
            <div class="esr-grid2">
                <div class="esr-field"><label>Budget (visible to responders only)</label><input type="number" name="budget_min" class="esr-input" value="{{ old('budget_min') }}" placeholder="e.g. 2000"></div>
            </div>
            @include('client.partials._service_budget_split', [
                'pickerName' => 'services',
                'split'      => old('service_budgets', []),
                'suggestUrl' => route('client.bsr.suggest-split'),
            ])

            {{-- Required now. It is what a professional reads before deciding
                 whether to answer, and it goes into the agreement -- the same
                 reason the bidding form has always required it. --}}
            <div class="esr-field">
                <label>What should professionals know? <span class="esr-req">*</span></label>
                <textarea name="description" class="esr-textarea" maxlength="4000" required
                          placeholder="Scope, access, equipment, timing. Anything that changes the price.">{{ old('description') }}</textarea>
                <p class="esr-hint">At least a sentence or two.</p>
                @error('description')<p class="esr-err">{{ $message }}</p>@enderror
            </div>

            {{-- Appears the moment a catering or bar service is ticked above. --}}
            @include('partials._food_delivery', [
                'mode'  => old('delivery_mode'),
                'live'  => true,
                'shown' => \App\Domain\Requests\FoodDelivery::appliesTo(
                    array_map('intval', (array) old('services', []))
                ),
            ])
        </div>

        {{-- Publish --}}
        <div class="esr-card esr-foot">
            {{-- Was this page's own wording, and the only page that carried it.
                 Shared now, with the agreement Sir Peter asked for. --}}
            @include('client.partials._request_fee_terms', ['action' => 'posting this rush request'])
            <button type="submit" class="esr-btn">
                <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg>
                Publish Rush Request
            </button>
        </div>
    </form>

    {{-- Contextual rail — fills large screens, stacks under the form on tablet/mobile --}}
    {{-- Was four hand-written cards saying what the bidding and direct pages
         also said, in different words. Shared now -- see config/request-help. --}}
    <aside class="hr-rail">
        @include('partials._help_rail', ['flow' => 'er'])
    </aside>
    </div>
</div>
@endsection

@push('scripts')
<script>
(function () {
    var root   = document.querySelector('.esr');
    var field  = document.getElementById('esrScope');
    var picker = root && root.querySelector('[data-svc-picker]');
    if (!root || !field) return;

    root.querySelectorAll('[data-scope-pick]').forEach(function (card) {
        card.addEventListener('click', function () {
            var val = card.getAttribute('data-scope-pick');
            if (val === field.value) return;

            field.value = val;
            root.setAttribute('data-scope', val);
            root.querySelectorAll('[data-scope-pick]').forEach(function (c) {
                c.classList.toggle('sel', c === card);
            });
            // Let the picker collapse a multi-selection down to one itself, so
            // its tags and counters stay in step.
            if (picker) {
                picker.dispatchEvent(new CustomEvent('svc:single', { detail: { on: val === 'single' } }));
            }
        });
    });
})();
</script>
@endpush
