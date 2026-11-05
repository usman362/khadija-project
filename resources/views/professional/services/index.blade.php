@extends('layouts.professional')

@section('title', 'My Services & Pricing')
@section('page-title', 'My Services & Pricing')

{{-- What this professional offers and what each of it costs.

     Khadijah's 28 October screen: five counts across the top, the list grouped
     under its parent categories rather than run flat, and an Add Service
     Offering button so the one page you would add a service from is the page
     you price it on.

     Choosing WHICH services is still the same pivot the profile writes, so the
     two screens cannot disagree about the list. --}}

@push('styles')
<style>
    .sp-head { display:flex; justify-content:space-between; align-items:flex-end; gap:16px; flex-wrap:wrap; margin-bottom:18px; }
    .sp-head h1 { font-size:24px; font-weight:800; color:var(--text-primary); margin:0 0 4px; }
    .sp-head p { font-size:13px; color:var(--text-muted); margin:0; line-height:1.6; max-width:70ch; }

    .sp-stats { display:grid; grid-template-columns:repeat(auto-fit, minmax(150px,1fr)); gap:13px; margin-bottom:20px; }
    .sp-stat { background:var(--bg-card); border:1px solid var(--border-color); border-radius:12px; padding:14px 16px; }
    .sp-stat .n { font-size:22px; font-weight:800; color:var(--text-primary); line-height:1.1; }
    .sp-stat .l { font-size:11.5px; color:var(--text-muted); margin-top:3px; }

    .sp-card { background:var(--bg-card); border:1px solid var(--border-color); border-radius:14px; padding:18px; margin-bottom:18px; }
    .sp-card > h2 { font-size:15px; font-weight:800; margin:0 0 4px; color:var(--text-primary); }
    .sp-card > .sub { font-size:12.5px; color:var(--text-muted); margin:0 0 16px; }

    .sp-tools { display:grid; grid-template-columns:minmax(0,2fr) 1fr 1fr; gap:10px; margin-bottom:16px; }
    .sp-in, .sp-sel { width:100%; font-family:inherit; font-size:13px; padding:9px 11px; border-radius:9px;
        border:1px solid var(--border-color); background:var(--bg-page, var(--bg-card)); color:var(--text-primary); }

    .sp-group { border:1px solid var(--border-color); border-radius:12px; margin-bottom:12px; overflow:hidden; }
    .sp-group > summary { list-style:none; cursor:pointer; display:flex; align-items:center; gap:10px;
        padding:12px 14px; background:var(--bg-page, var(--bg-card)); font-size:13.5px; font-weight:800; color:var(--text-primary); }
    .sp-group > summary::-webkit-details-marker { display:none; }
    .sp-group > summary .chev { margin-left:auto; color:var(--text-muted); transition:transform .15s; }
    .sp-group[open] > summary .chev { transform:rotate(180deg); }
    .sp-group > summary .pill { font-size:10.5px; font-weight:700; color:var(--text-muted);
        border:1px solid var(--border-color); border-radius:20px; padding:2px 8px; background:var(--bg-card); }
    .sp-groupbody { padding:4px 14px 10px; }

    .sp-row { display:grid; grid-template-columns:minmax(0,2fr) 110px 120px 100px 110px 92px; gap:11px; align-items:end;
        padding:14px 0; border-bottom:1px solid var(--border-color); }
    .sp-row:last-child { border-bottom:none; }
    .sp-row.is-resting { opacity:.58; }
    .sp-name { font-size:13.5px; font-weight:700; color:var(--text-primary); }
    .sp-tags { display:flex; gap:5px; flex-wrap:wrap; margin-top:5px; }
    .sp-tag { font-size:10px; font-weight:700; padding:2px 7px; border-radius:20px;
        border:1px solid var(--border-color); color:var(--text-muted); background:var(--bg-page, var(--bg-card)); }
    .sp-tag.ok { border-color:#10b981; color:#047857; background:rgba(16,185,129,0.12); }
    .sp-tag.pkg { border-color:#6366f1; color:#4338ca; background:rgba(99,102,241,0.12); }
    .sp-tag.rest { border-color:#f59e0b; color:#b45309; background:rgba(245,158,11,0.14); }
    .sp-lab { display:block; font-size:10.5px; font-weight:700; text-transform:uppercase; letter-spacing:.3px;
        color:var(--text-muted); margin-bottom:4px; }
    .sp-mini { font-family:inherit; font-size:12.5px; font-weight:700; padding:8px 10px; border-radius:9px; width:100%;
        border:1px solid var(--border-color); background:var(--bg-card); color:var(--text-secondary); cursor:pointer; }
    .sp-mini:hover { border-color:var(--accent-blue); color:var(--text-primary); }

    .sp-foot { display:flex; align-items:center; gap:14px; margin-top:16px; flex-wrap:wrap; }
    .sp-save { background:var(--accent-blue, #2563eb); color:#fff; border:none; border-radius:10px;
        padding:11px 20px; font-size:13.5px; font-weight:700; cursor:pointer; }
    .sp-note { font-size:12.5px; color:var(--text-muted); }
    .sp-empty { font-size:13px; color:var(--text-muted); line-height:1.6; }
    .sp-none { font-size:13px; color:var(--text-muted); padding:14px 0; }

    .sp-add { display:grid; grid-template-columns:minmax(0,1fr) auto; gap:10px; align-items:end; }
    .sp-tips { list-style:none; padding:0; margin:0; }
    .sp-tips li { position:relative; padding-left:16px; margin-bottom:8px; font-size:12.5px; color:var(--text-secondary); }
    .sp-tips li::before { content:"\2713"; position:absolute; left:0; color:#10b981; font-weight:800; }

    .sp-tabs { display: flex; gap: 7px; flex-wrap: wrap; margin-bottom: 18px; }
    .sp-tab { padding: 8px 15px; border-radius: 999px; font-size: 12.5px; font-weight: 700;
        text-decoration: none; color: var(--text-secondary, var(--text-muted));
        border: 1px solid var(--border-color); background: var(--bg-card); white-space: nowrap; }
    .sp-tab.on { background: var(--text-primary); color: var(--bg-card); border-color: var(--text-primary); }
    .sp-three { display: grid; grid-template-columns: repeat(3, minmax(0,1fr)); gap: 14px; }
    .sp-inline { display: grid; grid-template-columns: minmax(0,2fr) 120px 140px auto; gap: 11px; align-items: end; margin-top: 16px; }
    .sp-check { display: flex; gap: 9px; align-items: center; font-size: 13.5px; font-weight: 700; color: var(--text-primary); }
    textarea.sp-in { font-family: inherit; resize: vertical; }
    @media (max-width: 860px) { .sp-three, .sp-inline { grid-template-columns: 1fr; } }
    @media (max-width: 1000px) { .sp-row { grid-template-columns:1fr 1fr 1fr; } .sp-tools, .sp-add { grid-template-columns:1fr; } }
</style>
@endpush

@section('content')
<div class="sp-head">
    <div>
        <h1>My Services &amp; Pricing</h1>
        <p>
            What you charge for each service you offer. These prices are what Create a Package fills in
            for you, and what a quick answer to a request is worked out from. Leave any of them blank
            and that service simply has no price yet.
        </p>
    </div>
    <a href="{{ route('professional.profile.index') }}" class="sp-save" style="text-decoration:none;">Preview public profile</a>
</div>

<div class="sp-stats">
    <div class="sp-stat"><div class="n">{{ $counts['total'] }}</div><div class="l">Services you offer</div></div>
    <div class="sp-stat"><div class="n">{{ $counts['active'] }}</div><div class="l">Active, visible to clients</div></div>
    <div class="sp-stat"><div class="n">{{ $counts['packaged'] }}</div><div class="l">Used in your packages</div></div>
    <div class="sp-stat"><div class="n">{{ $counts['quickbid'] }}</div><div class="l">Priced, ready to quote</div></div>
    <div class="sp-stat"><div class="n">{{ $counts['inactive'] }}</div><div class="l">Resting, hidden from clients</div></div>
</div>

{{-- Khadijah's screen puts these five across the page. Services was the only
     one with anything behind it; the other four had nowhere to store an
     answer, so a weekend surcharge or a travel charge was typed into every
     quote by hand. --}}
@php
    $spTabs = [
        'services' => 'Services',
        'addons'   => 'Add-ons & extras',
        'rules'    => 'Pricing rules',
        'travel'   => 'Travel',
        'quickbid' => 'Quick Bid',
    ];
@endphp
<div class="sp-tabs">
    @foreach ($spTabs as $key => $label)
        <a class="sp-tab {{ $tab === $key ? 'on' : '' }}"
           href="{{ route('professional.services.index', $key === 'services' ? [] : ['tab' => $key]) }}">{{ $label }}</a>
    @endforeach
</div>

@if ($tab === 'services')
{{-- Add Service Offering. Khadijah asked for it here on 5 November; before
     this, the only way to add a service was to leave this page. --}}
<div class="sp-card">
    <h2>Add a service offering</h2>
    <p class="sub">Pick anything you do that is not on your list yet. You can price it straight after.</p>

    @if (empty($addable))
        <div class="sp-none">You already offer every service on the site.</div>
    @else
        <form method="POST" action="{{ route('professional.services.add') }}" class="sp-add">
            @csrf
            <div>
                <label class="sp-lab" for="spAdd">Service</label>
                <select class="sp-sel" id="spAdd" name="category_id" required>
                    <option value="">Choose a service</option>
                    @foreach ($addable as $option)
                        <option value="{{ $option['id'] }}">{{ $option['label'] }}</option>
                    @endforeach
                </select>
                @error('category_id') <div class="sp-note" style="color:#b91c1c;margin-top:6px;">{{ $message }}</div> @enderror
            </div>
            <button type="submit" class="sp-save">Add service offering</button>
        </form>
    @endif
</div>

<div class="sp-card">
    <h2>Your services</h2>
    <p class="sub">Grouped by category. Open a group to price what is in it.</p>

    @if ($services->isEmpty())
        <p class="sp-empty">
            Nothing on your list yet. Add your first service above, or pick several at once on your
            <a href="{{ route('professional.profile.index') }}">profile</a>.
        </p>
    @else
        <div class="sp-tools">
            <input type="search" id="spSearch" class="sp-in" placeholder="Search your services..." oninput="spFilter()">
            <select id="spStatus" class="sp-sel" onchange="spFilter()">
                <option value="">All statuses</option>
                <option value="active">Active only</option>
                <option value="resting">Resting only</option>
            </select>
            <select id="spPriced" class="sp-sel" onchange="spFilter()">
                <option value="">All services</option>
                <option value="priced">Priced only</option>
                <option value="unpriced">No price yet</option>
            </select>
        </div>

        <form method="POST" action="{{ route('professional.services.update') }}">
            @csrf
            @method('PATCH')

            @foreach ($groups as $groupName => $groupServices)
                <details class="sp-group" data-spgroup open>
                    <summary>
                        <span>{{ $groupName }}</span>
                        <span class="pill" data-spcount>{{ $groupServices->count() }}</span>
                        <svg class="chev" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="6 9 12 15 18 9"/></svg>
                    </summary>
                    <div class="sp-groupbody">
                        @foreach ($groupServices as $service)
                            @php
                                $resting = ! (bool) ($service->pivot->is_active ?? true);
                                $hasPrice = $service->pivot->price !== null;
                                $packaged = $inPackages->contains($service->name);
                                $num = fn ($v) => $v === null ? '' : rtrim(rtrim(number_format((float) $v, 2, '.', ''), '0'), '.');
                            @endphp
                            <div class="sp-row {{ $resting ? 'is-resting' : '' }}"
                                 data-sprow
                                 data-name="{{ Str::lower($service->name) }}"
                                 data-status="{{ $resting ? 'resting' : 'active' }}"
                                 data-priced="{{ $hasPrice ? 'priced' : 'unpriced' }}">
                                <div>
                                    <div class="sp-name">{{ $service->name }}</div>
                                    <div class="sp-tags">
                                        @if ($resting)
                                            <span class="sp-tag rest">Resting</span>
                                        @else
                                            <span class="sp-tag ok">Active</span>
                                        @endif
                                        @if ($hasPrice) <span class="sp-tag">Ready to quote</span> @endif
                                        @if ($packaged) <span class="sp-tag pkg">In a package</span> @endif
                                    </div>
                                </div>
                                <div>
                                    <label class="sp-lab" for="price-{{ $service->id }}">Price</label>
                                    <input class="sp-in" type="number" step="0.01" min="0" inputmode="decimal"
                                           id="price-{{ $service->id }}"
                                           name="rows[{{ $service->id }}][price]"
                                           value="{{ $num($service->pivot->price) }}" placeholder="e.g. 1200">
                                </div>
                                <div>
                                    <label class="sp-lab" for="unit-{{ $service->id }}">Read as</label>
                                    <select class="sp-sel" id="unit-{{ $service->id }}" name="rows[{{ $service->id }}][price_unit]">
                                        @foreach ($units as $key => $label)
                                            <option value="{{ $key }}" @selected(($service->pivot->price_unit ?? 'from') === $key)>{{ $label }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div>
                                    <label class="sp-lab" for="hours-{{ $service->id }}">Includes</label>
                                    <input class="sp-in" type="number" min="1" max="24"
                                           id="hours-{{ $service->id }}"
                                           name="rows[{{ $service->id }}][duration_hours]"
                                           value="{{ $service->pivot->duration_hours }}" placeholder="hours">
                                </div>
                                <div>
                                    <label class="sp-lab" for="extra-{{ $service->id }}">Extra hour</label>
                                    <input class="sp-in" type="number" step="0.01" min="0" inputmode="decimal"
                                           id="extra-{{ $service->id }}"
                                           name="rows[{{ $service->id }}][extra_hour_price]"
                                           value="{{ $num($service->pivot->extra_hour_price) }}" placeholder="optional">
                                </div>
                                <div>
                                    <span class="sp-lab">&nbsp;</span>
                                    {{-- Its own form, because a button inside the pricing form would
                                         submit that one. Resting keeps the price; see the migration. --}}
                                    <button type="submit" class="sp-mini"
                                            form="spToggle-{{ $service->id }}"
                                            title="{{ $resting ? 'Offer this again' : 'Stop offering this for now, keeping its price' }}">
                                        {{ $resting ? 'Resume' : 'Rest' }}
                                    </button>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </details>
            @endforeach

            <div class="sp-none" id="spNone" hidden>Nothing matches that. Clear the filters to see the rest.</div>

            <div class="sp-foot">
                <button type="submit" class="sp-save">Save pricing</button>
                <span class="sp-note">
                    {{ $priced }} of {{ $services->count() }} {{ $services->count() === 1 ? 'service has' : 'services have' }} a price.
                    A service with no price cannot answer a request on its own.
                </span>
            </div>
        </form>

        {{-- Outside the pricing form: nesting a form in a form is not allowed,
             and the browser drops the inner one silently. --}}
        @foreach ($services as $service)
            <form id="spToggle-{{ $service->id }}" method="POST" action="{{ route('professional.services.toggle') }}" hidden>
                @csrf
                <input type="hidden" name="category_id" value="{{ $service->id }}">
            </form>
        @endforeach
    @endif
</div>

@endif

@if ($tab === 'addons')
    {{-- An extra is not a service. Nobody hires somebody for an extra hour of
         lighting with no lighting under it, so these are listed apart and
         priced apart. --}}
    <div class="sp-card">
        <h2>Add-ons and extras</h2>
        <p class="sub">The optional things you sell on top of a service. They appear beside your price when you quote.</p>

        @if ($addons->isEmpty())
            <div class="sp-none">Nothing yet. Add your first one below.</div>
        @else
            <table class="sp-table">
                <thead><tr><th>Extra</th><th>Price</th><th>Charged</th><th></th></tr></thead>
                <tbody>
                @foreach ($addons as $addon)
                    <tr>
                        <td><strong>{{ $addon->name }}</strong></td>
                        <td>{{ $addon->price !== null ? '$' . number_format((float) $addon->price, 2) : 'No price yet' }}</td>
                        <td>{{ ['flat' => 'Flat', 'hourly' => 'Per hour', 'per_person' => 'Per person'][$addon->price_unit] ?? 'Flat' }}</td>
                        <td style="text-align:right;">
                            <form method="POST" action="{{ route('professional.services.addons.remove', $addon) }}">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="sp-mini">Remove</button>
                            </form>
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        @endif

        <form method="POST" action="{{ route('professional.services.addons.add') }}" class="sp-inline">
            @csrf
            <div>
                <label class="sp-lab" for="adName">Name</label>
                <input class="sp-in" type="text" id="adName" name="name" maxlength="80" required placeholder="e.g. Extra hour of lighting">
            </div>
            <div>
                <label class="sp-lab" for="adPrice">Price</label>
                <input class="sp-in" type="number" id="adPrice" name="price" step="0.01" min="0" placeholder="150">
            </div>
            <div>
                <label class="sp-lab" for="adUnit">Charged</label>
                <select class="sp-sel" id="adUnit" name="price_unit">
                    <option value="flat">Flat</option>
                    <option value="hourly">Per hour</option>
                    <option value="per_person">Per person</option>
                </select>
            </div>
            <button type="submit" class="sp-save">Add extra</button>
        </form>
        @error('name') <div class="sp-note" style="color:#b91c1c;">{{ $message }}</div> @enderror
    </div>
@endif

@if ($tab === 'rules')
    <div class="sp-card">
        <h2>Pricing rules</h2>
        <p class="sub">What you will not go below, and what you add on. Leave anything blank and it simply does not apply.</p>

        <form method="POST" action="{{ route('professional.services.rules') }}">
            @csrf
            <input type="hidden" name="tab" value="rules">
            <div class="sp-three">
                <div>
                    <label class="sp-lab" for="rMin">Minimum booking</label>
                    <input class="sp-in" type="number" id="rMin" name="minimum_booking" step="0.01" min="0"
                           value="{{ old('minimum_booking', $rules->minimum_booking) }}" placeholder="e.g. 500">
                    <div class="sp-note">Blank means you have not set one. It does not mean zero.</div>
                </div>
                <div>
                    <label class="sp-lab" for="rWeekend">Weekend surcharge</label>
                    <input class="sp-in" type="number" id="rWeekend" name="weekend_surcharge_pct" min="0" max="100"
                           value="{{ old('weekend_surcharge_pct', $rules->weekend_surcharge_pct) }}" placeholder="percent">
                </div>
                <div>
                    <label class="sp-lab" for="rRush">Short notice surcharge</label>
                    <input class="sp-in" type="number" id="rRush" name="rush_surcharge_pct" min="0" max="100"
                           value="{{ old('rush_surcharge_pct', $rules->rush_surcharge_pct) }}" placeholder="percent">
                </div>
            </div>
            <button type="submit" class="sp-save" style="margin-top:14px;">Save pricing rules</button>
        </form>
    </div>
@endif

@if ($tab === 'travel')
    <div class="sp-card">
        <h2>Travel</h2>
        <p class="sub">How far you will go, and what you charge beyond the distance you cover for nothing.</p>

        <form method="POST" action="{{ route('professional.services.rules') }}">
            @csrf
            <input type="hidden" name="tab" value="travel">
            <div class="sp-three">
                <div>
                    <label class="sp-lab" for="tFree">Free within</label>
                    <input class="sp-in" type="number" id="tFree" name="travel_free_miles" min="0" max="5000"
                           value="{{ old('travel_free_miles', $rules->travel_free_miles) }}" placeholder="miles">
                </div>
                <div>
                    <label class="sp-lab" for="tRate">Then per mile</label>
                    <input class="sp-in" type="number" id="tRate" name="travel_rate_per_mile" step="0.01" min="0"
                           value="{{ old('travel_rate_per_mile', $rules->travel_rate_per_mile) }}" placeholder="e.g. 1.50">
                </div>
                <div>
                    <label class="sp-lab" for="tMax">Will not travel past</label>
                    <input class="sp-in" type="number" id="tMax" name="travel_max_miles" min="0" max="5000"
                           value="{{ old('travel_max_miles', $rules->travel_max_miles) }}" placeholder="miles">
                </div>
            </div>
            <div class="sp-note" style="margin-top:10px;">
                This is what you charge to get there. Which states you can work in is a separate thing, set on your
                <a href="{{ route('professional.profile.index') }}">profile</a>.
            </div>
            <button type="submit" class="sp-save" style="margin-top:14px;">Save travel</button>
        </form>
    </div>
@endif

@if ($tab === 'quickbid')
    <div class="sp-card">
        <h2>Quick Bid</h2>
        <p class="sub">Answer a request with the prices you have already set, instead of working them out again.</p>

        <form method="POST" action="{{ route('professional.services.rules') }}">
            @csrf
            <input type="hidden" name="tab" value="quickbid">
            <input type="hidden" name="quick_bid_enabled" value="0">
            <label class="sp-check">
                <input type="checkbox" name="quick_bid_enabled" value="1" @checked($rules->quick_bid_enabled)>
                <span>Fill a bid from my own prices</span>
            </label>
            <div class="sp-note">
                It fills the figure in. It never sends anything: a bid in your name is still yours to send.
                {{ $counts['quickbid'] }} of your {{ $counts['total'] }} services have a price it can use.
            </div>

            <div style="margin-top:14px;">
                <label class="sp-lab" for="qTerms">Terms to include</label>
                <textarea class="sp-in" id="qTerms" name="quick_bid_terms" rows="4" maxlength="600"
                          placeholder="e.g. Deposit due on booking. Final numbers one week before.">{{ old('quick_bid_terms', $rules->quick_bid_terms) }}</textarea>
            </div>

            <button type="submit" class="sp-save" style="margin-top:14px;">Save Quick Bid</button>
        </form>
    </div>
@endif

<div class="sp-card">
    <h2>Getting the most out of this page</h2>
    <ul class="sp-tips">
        <li>A service with a price can be quoted without you typing a number every time.</li>
        <li>The extra hour rate is what an approved time extension is charged at.</li>
        <li>Resting a service hides it from clients and keeps everything you set on it.</li>
        <li>Services already in a package are marked, so you know what a change reaches.</li>
    </ul>
</div>

<script>
(function () {
    /* Search and the two dropdowns narrow the same list. A group whose rows
       are all filtered out hides itself rather than sitting there empty. */
    window.spFilter = function () {
        var q      = (document.getElementById('spSearch') || {}).value || '';
        var status = (document.getElementById('spStatus') || {}).value || '';
        var priced = (document.getElementById('spPriced') || {}).value || '';
        q = q.trim().toLowerCase();

        var shown = 0;

        document.querySelectorAll('[data-spgroup]').forEach(function (group) {
            var visible = 0;

            group.querySelectorAll('[data-sprow]').forEach(function (row) {
                var ok = (!q      || row.dataset.name.indexOf(q) !== -1)
                      && (!status || row.dataset.status === status)
                      && (!priced || row.dataset.priced === priced);
                row.hidden = !ok;
                if (ok) { visible++; }
            });

            group.hidden = visible === 0;
            var count = group.querySelector('[data-spcount]');
            if (count) { count.textContent = visible; }
            if (visible > 0 && (q || status || priced)) { group.open = true; }
            shown += visible;
        });

        var none = document.getElementById('spNone');
        if (none) { none.hidden = shown > 0; }
    };
})();
</script>
@endsection
