@extends($layout)

@section('title', 'Requests & Submissions')
@section('page-title', 'Requests & Submissions')
@section('page-subtitle', 'Send us anything you need, and track everything you have already sent.')

@php
    $f = $filters;
    $carry = array_filter([
        'tab'   => $f['tab'] !== 'all' ? $f['tab'] : null,
        'range' => $f['range'] !== 'all' ? $f['range'] : null,
        'group' => $f['group'] ?: null,
        'q'     => $f['q'] ?: null,
    ]);
    $link = fn (array $over = []) => route('forms.index', array_filter(
        array_merge($carry, $over), fn ($v) => $v !== null && $v !== ''
    ));

    $groupIcon = ['bookings' => '📅', 'payments' => '💳', 'account' => '🛡', 'safety' => '🚩'];
    $badge = ['action' => 'warn', 'review' => 'info', 'completed' => 'ok', 'closed' => 'muted'];
    $stateLabel = ['action' => 'Needs Your Action', 'review' => 'Under Review', 'completed' => 'Completed', 'closed' => 'Closed'];
@endphp

@push('styles')
    @include('disputes._styles')
<style>
    .rq-head { display: flex; justify-content: space-between; align-items: flex-start; gap: 16px; flex-wrap: wrap; margin-bottom: 18px; }
    .rq-head h1 { font-size: 23px; font-weight: 800; color: var(--text-primary); margin: 0 0 4px; }
    .rq-head p { font-size: 13px; color: var(--text-muted); margin: 0; }
    .rq-search { position: relative; flex: 0 0 320px; max-width: 100%; }
    .rq-search input { width: 100%; background: var(--bg-card); border: 1px solid var(--border-color); border-radius: 10px; padding: 10px 12px 10px 34px; font-size: 13px; color: var(--text-primary); font-family: inherit; }
    .rq-search svg { position: absolute; left: 11px; top: 50%; transform: translateY(-50%); width: 15px; height: 15px; color: var(--text-muted); }

    /* Tiles */
    .rq-stats { display: grid; grid-template-columns: repeat(4, minmax(0,1fr)); gap: 12px; margin-bottom: 18px; }
    .rq-stat { display: flex; gap: 12px; align-items: flex-start; background: var(--bg-card); border: 1px solid var(--border-color); border-radius: 13px; padding: 14px 15px; text-decoration: none; }
    .rq-stat:hover { border-color: var(--accent-blue); }
    .rq-stat.on { border-color: var(--accent-blue); box-shadow: 0 0 0 1px var(--accent-blue) inset; }
    .rq-stat .ic { width: 34px; height: 34px; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 15px; flex-shrink: 0; background: var(--accent-blue-soft); }
    .rq-stat .ic.warn { background: rgba(245,158,11,.15); }
    .rq-stat .ic.ok { background: rgba(16,185,129,.13); }
    .rq-stat > span:last-child { display: block; min-width: 0; }
    .rq-stat .lbl { display: block; font-size: 12.5px; font-weight: 700; color: var(--text-primary); }
    .rq-stat .n { display: block; font-size: 26px; font-weight: 800; color: var(--text-primary); line-height: 1.15; margin: 1px 0 2px; }
    .rq-stat .note { display: block; font-size: 11px; color: var(--text-muted); line-height: 1.35; }

    /* Group cards */
    .rq-groups { display: grid; grid-template-columns: repeat(4, minmax(0,1fr)); gap: 12px; }
    .rq-group { display: block; background: var(--bg-card); border: 1px solid var(--border-color); border-radius: 13px; padding: 14px 15px; text-decoration: none; }
    .rq-group:hover { border-color: var(--accent-blue); }
    .rq-group .ic { font-size: 18px; }
    .rq-group b { display: block; font-size: 13px; font-weight: 800; color: var(--text-primary); margin: 8px 0 3px; }
    .rq-group span { display: block; font-size: 11.5px; color: var(--text-muted); line-height: 1.45; }
    .rq-group .n { display: inline-block; margin-top: 9px; font-size: 12px; font-weight: 800; color: var(--accent-blue); }
    .rq-group.on { border-color: var(--accent-blue); box-shadow: 0 0 0 1px var(--accent-blue) inset; }

    /* The forms inside the open group */
    .rq-groupsec { margin-top: 18px; }
    .rq-groupsec-h { font-size: 12.5px; font-weight: 800; color: var(--text-primary); padding-bottom: 8px; border-bottom: 1px solid var(--border-color); }
    .rq-forms { display: grid; grid-template-columns: repeat(auto-fill, minmax(238px,1fr)); gap: 11px; margin-top: 14px; }
    .rq-form { display: block; border: 1px solid var(--border-color); border-radius: 12px; padding: 13px 15px; background: var(--bg-card); text-decoration: none; }
    .rq-form:hover { border-color: var(--accent-blue); }
    .rq-form b { display: block; font-size: 13px; font-weight: 800; color: var(--text-primary); margin-bottom: 3px; }
    .rq-form span { font-size: 11.5px; color: var(--text-muted); line-height: 1.45; }

    /* Tabs + table */
    .rq-tabsbar { display: flex; align-items: center; gap: 10px; flex-wrap: wrap; border-bottom: 1px solid var(--border-color); margin-bottom: 12px; }
    .rq-tab { padding: 9px 4px; margin-right: 14px; font-size: 13px; font-weight: 700; color: var(--text-muted); text-decoration: none; border-bottom: 2px solid transparent; white-space: nowrap; }
    .rq-tab.on { color: var(--accent-blue); border-bottom-color: var(--accent-blue); }
    .rq-tabs-right { margin-left: auto; display: inline-flex; gap: 8px; padding-bottom: 7px; }
    .rq-sel { background: var(--bg-card); border: 1px solid var(--border-color); border-radius: 9px; padding: 7px 10px; font-size: 12.5px; font-weight: 700; color: var(--text-primary); font-family: inherit; cursor: pointer; }

    .rq-scroll { overflow-x: auto; }
    .rq-table { width: 100%; border-collapse: collapse; font-size: 13px; min-width: 720px; }
    .rq-table th { text-align: left; font-size: 10.5px; text-transform: uppercase; letter-spacing: .04em; color: var(--text-muted); padding: 0 12px 9px 0; font-weight: 800; }
    .rq-table td { padding: 12px 12px 12px 0; border-top: 1px solid var(--border-color); vertical-align: top; }
    .rq-table .ref { font-family: ui-monospace, SFMono-Regular, Menlo, monospace; font-size: 12px; font-weight: 700; color: var(--accent-blue); text-decoration: none; }
    .rq-table .ttl { display: block; font-weight: 700; color: var(--text-primary); margin-bottom: 2px; }
    .rq-muted { color: var(--text-muted); }
    .rq-view { display: inline-block; border: 1px solid var(--border-color); border-radius: 9px; padding: 6px 14px; font-size: 12.5px; font-weight: 700; color: var(--text-primary); text-decoration: none; white-space: nowrap; }
    .rq-view:hover { background: var(--bg-card-hover); }
    .rq-view.primary { background: var(--accent-blue); border-color: var(--accent-blue); color: #fff; }

    .rq-none { padding: 44px 20px; text-align: center; }
    .rq-none h3 { font-size: 16px; font-weight: 800; color: var(--text-primary); margin: 10px 0 6px; }
    .rq-none p { font-size: 13px; color: var(--text-muted); margin: 0 0 16px; line-height: 1.55; }
    .rq-ghost { display: inline-flex; border: 1px solid var(--accent-blue); color: var(--accent-blue); border-radius: 10px; padding: 9px 18px; font-size: 12.5px; font-weight: 800; text-decoration: none; }

    .rq-note { display: flex; gap: 10px; align-items: flex-start; background: var(--bg-hover, rgba(59,130,246,.05)); border: 1px solid var(--border-color); border-radius: 11px; padding: 12px 15px; font-size: 12.5px; color: var(--text-muted); line-height: 1.5; margin-top: 14px; }

    @media (max-width: 1000px) { .rq-stats, .rq-groups { grid-template-columns: repeat(2, minmax(0,1fr)); } }
    @media (max-width: 600px) { .rq-stats, .rq-groups { grid-template-columns: 1fr; } }
</style>
@endpush

@section('content')
<div class="rq-head">
    <div>
        {{-- Title and subtitle are in the banner at the top of the page, in both portals. --}}
    </div>
    <form class="rq-search" method="GET" action="{{ route('forms.index') }}">
        @foreach(\Illuminate\Support\Arr::except($carry, ['q']) as $k => $v)
            <input type="hidden" name="{{ $k }}" value="{{ $v }}">
        @endforeach
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>
        <input type="search" name="q" value="{{ $f['q'] }}" placeholder="What do you need help with?" aria-label="Search your requests">
    </form>
</div>

@if(session('status'))<div class="dsp-flash">{{ session('status') }}</div>@endif

{{-- The tiles are the tabs. Two controls filtering one list, one above the
     other, is one control drawn twice. --}}
<div class="rq-stats">
    <a class="rq-stat {{ $f['tab'] === 'action' ? 'on' : '' }}" href="{{ $link(['tab' => 'action', 'page' => null]) }}">
        <span class="ic warn">⏱</span>
        <span>
            <span class="lbl">Needs Your Action</span>
            <span class="n">{{ $counts['action'] }}</span>
            <span class="note">{{ $counts['action'] ? 'Waiting on your decision' : 'Nothing waiting on you' }}</span>
        </span>
    </a>
    <a class="rq-stat {{ $f['tab'] === 'review' ? 'on' : '' }}" href="{{ $link(['tab' => 'review', 'page' => null]) }}">
        <span class="ic">🔍</span>
        <span>
            <span class="lbl">Under Review</span>
            <span class="n">{{ $counts['review'] }}</span>
            <span class="note">{{ $counts['review'] ? 'In progress' : 'Nothing in progress' }}</span>
        </span>
    </a>
    <a class="rq-stat {{ $f['tab'] === 'completed' ? 'on' : '' }}" href="{{ $link(['tab' => 'completed', 'page' => null]) }}">
        <span class="ic ok">✅</span>
        <span>
            <span class="lbl">Completed</span>
            <span class="n">{{ $counts['completed'] }}</span>
            <span class="note">In the last 30 days</span>
        </span>
    </a>
    <a class="rq-stat {{ $f['tab'] === 'all' ? 'on' : '' }}" href="{{ $link(['tab' => null, 'page' => null]) }}">
        <span class="ic">🗂</span>
        <span>
            <span class="lbl">All Requests</span>
            <span class="n">{{ $counts['all'] }}</span>
            <span class="note">Everything you are part of</span>
        </span>
    </a>
</div>

<div class="dsp-card">
    <div style="font-size:14.5px;font-weight:800;color:var(--text-primary);">Start a new request</div>
    <p class="dsp-sub" style="margin:3px 0 14px;">Pick the area it belongs to. Fifteen forms in one wall is a list to read, not a place to start.</p>

    <div class="rq-groups">
        @foreach($groups as $slug => $group)
            {{-- The count is taken from the same list the card opens, so a card
                 cannot promise four request types and then show one. --}}
            <a class="rq-group {{ $f['group'] === $slug ? 'on' : '' }}"
               href="{{ $f['group'] === $slug ? $link(['group' => null]) : $link(['group' => $slug]) }}">
                <span class="ic">{{ $groupIcon[$slug] ?? '📄' }}</span>
                <b>{{ $group['label'] }}</b>
                <span>{{ $group['blurb'] }}</span>
                <span class="n">{{ count($group['forms']) }} request type{{ count($group['forms']) === 1 ? '' : 's' }} →</span>
            </a>
        @endforeach
    </div>

    {{-- With no area chosen this lists every form this person may file, under
         its heading. Choosing an area narrows it. Putting the forms behind a
         click would mean the page whose job is "here is what you can send"
         needed a click before it said so. --}}
    @php $shownGroups = $f['group'] !== '' ? array_intersect_key($groups, [$f['group'] => true]) : $groups; @endphp
    @foreach($shownGroups as $slug => $group)
        <div class="rq-groupsec">
            <div class="rq-groupsec-h">{{ $groupIcon[$slug] ?? '📄' }} {{ $group['label'] }}</div>
            <div class="rq-forms">
                @foreach($group['forms'] as $key => $form)
                    <a class="rq-form" href="{{ route('forms.create', $key) }}">
                        <b>{{ $form['title'] }}</b>
                        <span>{{ $form['purpose'] }}</span>
                    </a>
                @endforeach
            </div>
        </div>
    @endforeach
</div>

<div class="dsp-card">
    <div class="rq-tabsbar">
        <a class="rq-tab {{ $f['tab'] === 'all' ? 'on' : '' }}" href="{{ $link(['tab' => null, 'page' => null]) }}">All ({{ $counts['all'] }})</a>
        @foreach($tabs as $key => $label)
            <a class="rq-tab {{ $f['tab'] === $key ? 'on' : '' }}" href="{{ $link(['tab' => $key, 'page' => null]) }}">{{ $label }} ({{ $counts[$key] }})</a>
        @endforeach

        <div class="rq-tabs-right">
            <form method="GET" action="{{ route('forms.index') }}">
                @foreach(\Illuminate\Support\Arr::except($carry, ['range']) as $k => $v)
                    <input type="hidden" name="{{ $k }}" value="{{ $v }}">
                @endforeach
                <select name="range" class="rq-sel" aria-label="Time range" onchange="this.form.submit()">
                    @foreach($ranges as $key => [$label, $days])
                        <option value="{{ $key }}" @selected($f['range'] === $key)>{{ $label }}</option>
                    @endforeach
                </select>
            </form>
            {{-- The mockup's "Filters" button is this. Besides state and date,
                 the only thing a request list can be filtered by is which area
                 it belongs to — and a button opening an empty panel is a
                 control that does not control anything. --}}
            <form method="GET" action="{{ route('forms.index') }}">
                @foreach(\Illuminate\Support\Arr::except($carry, ['group']) as $k => $v)
                    <input type="hidden" name="{{ $k }}" value="{{ $v }}">
                @endforeach
                <select name="group" class="rq-sel" aria-label="Filter by area" onchange="this.form.submit()">
                    <option value="">All areas</option>
                    @foreach($groups as $slug => $group)
                        <option value="{{ $slug }}" @selected($f['group'] === $slug)>{{ $group['label'] }}</option>
                    @endforeach
                </select>
            </form>
        </div>
    </div>

    @if($submissions->isEmpty())
        <div class="rq-none">
            <div style="font-size:38px;opacity:.5;">🗃️</div>
            @if($f['tab'] !== 'all' || $f['q'] || $f['group'] || $f['range'] !== 'all')
                <h3>Nothing in this view</h3>
                <p>No request matches these filters.</p>
                <a class="rq-ghost" href="{{ route('forms.index') }}">Show everything</a>
            @else
                <h3>You have not sent anything yet</h3>
                <p>Pick an area above and we will take it from there.</p>
            @endif
        </div>
    @else
        <div class="rq-scroll">
            <table class="rq-table">
                <thead>
                    <tr>
                        <th>Request</th>
                        <th>Related to</th>
                        <th>Submitted</th>
                        <th>Status</th>
                        <th>Next step</th>
                        <th>Last activity</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($submissions as $item)
                        @php
                            $state = $stateOf($item);
                            $other = $item->submitted_by === auth()->id() ? $item->counterparty : $item->submitter;
                        @endphp
                        <tr>
                            <td>
                                <span class="ttl">{{ $item->title() }}</span>
                                <a class="ref" href="{{ route('forms.show', $item) }}">{{ $item->reference }}</a>
                            </td>
                            <td class="rq-muted">
                                {{-- The subject if the form had one, otherwise the other
                                     party. Never both, and never a dash where a name
                                     exists. --}}
                                @if($item->subject)
                                    {{ $item->subject->title ?? ($item->subject->event?->title ?? 'Booking #' . $item->subject_id) }}
                                @elseif($other)
                                    {{ $other->name }}
                                @else
                                    GigResource team
                                @endif
                            </td>
                            <td class="rq-muted">{{ $item->created_at?->format('M j, Y') }}</td>
                            <td>
                                <span class="dsp-badge dsp-{{ $state === 'completed' ? 'done' : ($state === 'review' ? 'review' : ($state === 'closed' ? 'shut' : 'open')) }}">
                                    {{ $stateLabel[$state] }}
                                </span>
                            </td>
                            <td class="rq-muted">{{ $nextStep($item) }}</td>
                            <td class="rq-muted">{{ $item->updated_at?->humanAgo() }}</td>
                            <td>
                                <a class="rq-view {{ $state === 'action' ? 'primary' : '' }}" href="{{ route('forms.show', $item) }}">
                                    {{ $state === 'action' ? 'Respond' : 'View' }}
                                </a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div style="display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap;margin-top:14px;">
            <span class="dsp-when">Showing {{ $submissions->firstItem() }} to {{ $submissions->lastItem() }} of {{ $submissions->total() }} request{{ $submissions->total() === 1 ? '' : 's' }}</span>
            <div>{{ $submissions->onEachSide(1)->links() }}</div>
        </div>
    @endif

    <div class="rq-note">
        <span>ℹ️</span>
        <span>Everything you send here is kept on file with its reference, so you and our team are always reading the same record.</span>
    </div>
</div>
@endsection
