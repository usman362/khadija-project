@extends('layouts.professional')

@section('title', 'My Clients')
@section('page-title', 'My Clients')

{{-- Khadijah's 28 October My Clients.

     Everything that can be read off bookings is: who came back, what they
     have paid, when they were last worked for and whether they book at the
     same time every year. The rest is what the professional writes about
     them, and the client never sees it.

     Her screen has a "Next Likely Event" column offering Annual, Possible
     and Not Likely. Only the first is a fact, so only the first is here. The
     other two are guesses about somebody's intention. --}}

@php
    $money = fn ($v) => '$' . number_format((float) $v, 2);
    $link  = fn (array $over = []) => route('professional.clients.index', array_merge(array_filter([
        'show' => $show !== 'all' ? $show : null,
        'q'    => $q ?: null,
    ]), $over));

    $relationship = [
        'repeat'   => ['Came back', '#10b981'],
        'one_time' => ['Once so far', '#2563eb'],
        'past'     => ['Gone quiet', '#f59e0b'],
    ];
@endphp

@push('styles')
<style>
    .mc-head { display:flex; justify-content:space-between; align-items:flex-end; gap:16px; flex-wrap:wrap; margin-bottom:18px; }
    .mc-head h1 { font-size:24px; font-weight:800; color:var(--text-primary); margin:0 0 4px; }
    .mc-head p { font-size:13px; color:var(--text-muted); margin:0; max-width:70ch; }
    .mc-btn { font-size:13px; font-weight:700; padding:10px 17px; border-radius:10px; text-decoration:none;
        background:var(--accent-blue, #2563eb); color:#fff; border:0; cursor:pointer; font-family:inherit; }

    .mc-card { background:var(--bg-card); border:1px solid var(--border-color); border-radius:14px; padding:17px 18px; margin-bottom:17px; }

    .mc-sums { display:grid; grid-template-columns:repeat(4, minmax(0,1fr)); gap:13px; margin-bottom:17px; }
    .mc-sum { background:var(--bg-card); border:1px solid var(--border-color); border-radius:13px; padding:14px 16px; }
    .mc-sum .k { font-size:10.5px; font-weight:800; text-transform:uppercase; letter-spacing:.4px; color:var(--text-muted); }
    .mc-sum .v { font-size:25px; font-weight:800; color:var(--text-primary); line-height:1.1; margin:5px 0 3px; }
    .mc-sum .n { font-size:11px; color:var(--text-muted); }

    .mc-tools { display:grid; grid-template-columns:minmax(0,1fr) auto auto; gap:10px; align-items:center; }
    .mc-in { width:100%; font:inherit; font-size:13px; padding:9px 11px; border-radius:9px;
        border:1px solid var(--border-color); background:var(--bg-page, var(--bg-card)); color:var(--text-primary); }
    .mc-clear { font-size:12px; font-weight:700; color:var(--accent-blue, #2563eb); text-decoration:none; white-space:nowrap; }

    .mc-tabs { display:flex; gap:7px; flex-wrap:wrap; margin-bottom:15px; }
    .mc-tab { padding:8px 14px; border-radius:999px; font-size:12.5px; font-weight:700; text-decoration:none;
        color:var(--text-secondary, var(--text-muted)); border:1px solid var(--border-color);
        background:var(--bg-card); white-space:nowrap; }
    .mc-tab.on { background:var(--text-primary); color:var(--bg-card); border-color:var(--text-primary); }
    .mc-tab .n { opacity:.65; margin-left:4px; }

    .mc-table { width:100%; border-collapse:collapse; font-size:12.5px; }
    .mc-table th { text-align:left; font-size:10px; font-weight:800; text-transform:uppercase; letter-spacing:.4px;
        color:var(--text-muted); padding:0 9px 8px; border-bottom:1px solid var(--border-color); }
    .mc-table td { padding:11px 9px; border-bottom:1px solid var(--border-color); vertical-align:top; color:var(--text-primary); }
    .mc-table tr.detail td { background:var(--bg-page, var(--bg-card)); }
    .mc-who { display:flex; gap:10px; align-items:flex-start; }
    .mc-av { width:31px; height:31px; border-radius:50%; flex:none; object-fit:cover; }
    .mc-who b { display:block; font-size:13px; font-weight:700; }
    .mc-who span { display:block; font-size:11px; color:var(--text-muted); }
    .mc-tag { display:inline-block; font-size:10.5px; font-weight:700; padding:2px 8px; border-radius:20px;
        border:1px solid var(--border-color); color:var(--text-muted); margin:0 3px 3px 0; }
    .mc-rel { display:inline-block; font-size:10.5px; font-weight:800; padding:3px 9px; border-radius:20px; white-space:nowrap; }
    .mc-quiet { font-weight:700; }
    .mc-muted { color:var(--text-muted); }
    .mc-exp { font:inherit; font-size:11.5px; font-weight:700; color:var(--accent-blue, #2563eb);
        background:none; border:0; cursor:pointer; padding:0; }

    .mc-noteform { display:grid; grid-template-columns:minmax(0,1fr) minmax(0,1fr) minmax(0,1fr) auto; gap:11px; align-items:end; }
    .mc-noteform label { display:block; font-size:10.5px; font-weight:800; text-transform:uppercase;
        letter-spacing:.4px; color:var(--text-muted); margin-bottom:4px; }
    .mc-noteform input { width:100%; font:inherit; font-size:12.5px; padding:8px 10px; border-radius:9px;
        border:1px solid var(--border-color); background:var(--bg-card); color:var(--text-primary); }

    .mc-rules { display:grid; grid-template-columns:repeat(3, minmax(0,1fr)); gap:17px; }
    .mc-rules h4 { font-size:12.5px; font-weight:800; color:var(--text-primary); margin:0 0 9px; }
    .mc-rules div.row { display:flex; gap:9px; font-size:11.5px; color:var(--text-secondary, var(--text-muted));
        padding:4px 0; align-items:baseline; }
    .mc-rules i { width:8px; height:8px; border-radius:50%; flex:none; }
    .mc-rules b { color:var(--text-primary); }

    .mc-none { text-align:center; padding:30px 18px; }
    .mc-none h2 { font-size:16px; font-weight:800; color:var(--text-primary); margin:0 0 6px; }
    .mc-none p { font-size:13px; color:var(--text-muted); margin:0 0 14px; }

    @media (max-width: 1100px) { .mc-sums { grid-template-columns:repeat(2, minmax(0,1fr)); }
        .mc-rules { grid-template-columns:1fr; } .mc-noteform { grid-template-columns:1fr; } }
    @media (max-width: 900px) { .mc-table thead { display:none; }
        .mc-table td { display:block; border:0; padding:3px 0; }
        .mc-table tr { display:block; border-bottom:1px solid var(--border-color); padding:11px 0; } }
</style>
@endpush

@section('content')
<div class="mc-head">
    <div>
        <h1>My Clients</h1>
        <p>
            Everyone who has booked you, and what you have done for them. A client who has hired you
            before is the shortest route to the next job, so this is sorted by who you worked for most
            recently and marks the ones who came back.
        </p>
    </div>
    <a class="mc-btn" href="{{ route('professional.clients.export', request()->query()) }}">Download these clients</a>
</div>

<div class="mc-sums">
    <div class="mc-sum"><div class="k">Clients</div><div class="v">{{ $summary['clients'] }}</div><div class="n">Have booked you at least once</div></div>
    <div class="mc-sum"><div class="k">Came back</div><div class="v">{{ $summary['repeat'] }}</div><div class="n">Booked you more than once</div></div>
    <div class="mc-sum"><div class="k">Still ahead</div><div class="v">{{ $summary['upcoming'] }}</div><div class="n">Bookings not yet finished</div></div>
    <div class="mc-sum"><div class="k">Earned from them</div><div class="v">{{ $money($summary['earned']) }}</div><div class="n">Completed work, after commission</div></div>
</div>

<div class="mc-tabs">
    @foreach($tabs as $key => [$label, $count])
        <a class="mc-tab {{ $show === $key ? 'on' : '' }}" href="{{ $link(['show' => $key === 'all' ? null : $key]) }}">
            {{ $label }}<span class="n">{{ $count }}</span>
        </a>
    @endforeach
</div>

<div class="mc-card">
    <form method="GET" action="{{ route('professional.clients.index') }}" class="mc-tools">
        @if($show !== 'all')<input type="hidden" name="show" value="{{ $show }}">@endif
        <input type="search" class="mc-in" name="q" value="{{ $q }}" placeholder="Search by name, place or tag">
        <button type="submit" class="mc-btn">Search</button>
        @if($q !== '')
            <a class="mc-clear" href="{{ $link(['q' => null]) }}">Clear</a>
        @endif
    </form>
</div>

<div class="mc-card">
@if($rows->isEmpty())
    <div class="mc-none">
        <h2>
            @if($summary['clients'] === 0)
                Nobody has hired you yet
            @else
                Nobody in this group
            @endif
        </h2>
        <p>
            @if($summary['clients'] === 0)
                Once a client books you, they appear here with everything you have done for them.
            @else
                Try another tab, or clear the search.
            @endif
        </p>
        <a class="mc-btn" href="{{ route('professional.bidding-board.index') }}">Open the bidding board</a>
    </div>
@else
    <table class="mc-table">
        <thead>
            <tr>
                <th>Client</th>
                <th>Relationship</th>
                <th>Bookings</th>
                <th>Lifetime value</th>
                <th>Last worked</th>
                <th>Since contact</th>
                <th>Books yearly</th>
                <th>Tags</th>
                <th>What they need</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
        @foreach($rows as $r)
            @php [$relLabel, $relColour] = $relationship[$r['relationship']]; @endphp
            <tr>
                <td>
                    <div class="mc-who">
                        <img class="mc-av" src="{{ $r['client']['avatar'] }}" alt="">
                        <span>
                            <b>{{ $r['client']['name'] }}</b>
                            <span>{{ $r['client']['where'] ?: 'Place not stated' }}</span>
                        </span>
                    </div>
                </td>
                <td>
                    <span class="mc-rel" style="background: {{ $relColour }}1f; color: {{ $relColour }};">{{ $relLabel }}</span>
                </td>
                <td>{{ $r['bookings'] }}</td>
                <td>
                    {{ $money($r['gross']) }}
                    <span class="mc-muted" style="display:block;font-size:10.5px;">{{ $money($r['earned']) }} yours</span>
                </td>
                <td>
                    @if($r['last'])
                        {{ $r['last']['at']?->format('j M Y') ?: 'Undated' }}
                        @if($r['last_type'])<span class="mc-muted" style="display:block;font-size:10.5px;">{{ $r['last_type'] }}</span>@endif
                    @else
                        <span class="mc-muted">Nothing finished</span>
                    @endif
                </td>
                <td>
                    {{-- Never is not a long time ago. It means there has been
                         no contact at all, which is a different thing and the
                         one worth acting on. --}}
                    @if($r['days_quiet'] === null)
                        <span class="mc-quiet" style="color:#b91c1c;">Never</span>
                    @elseif($r['days_quiet'] >= $quietDays)
                        <span class="mc-quiet" style="color:#b91c1c;">{{ $r['days_quiet'] }} days</span>
                    @else
                        <span class="mc-muted">{{ $r['days_quiet'] }} days</span>
                    @endif
                </td>
                <td>
                    @if($r['annual'])
                        <span class="mc-rel" style="background:#2563eb1f;color:#2563eb;">Same month, two years</span>
                    @else
                        <span class="mc-muted">No pattern yet</span>
                    @endif
                </td>
                <td>
                    @forelse($r['tags'] as $tag)
                        <span class="mc-tag">{{ $tag }}</span>
                    @empty
                        <span class="mc-muted">None</span>
                    @endforelse
                </td>
                <td class="mc-muted">{{ $r['requirements'] ?: 'Not noted' }}</td>
                <td>
                    <button type="button" class="mc-exp" data-client="{{ $r['client']['id'] }}">Open</button>
                </td>
            </tr>
            <tr class="detail" id="client-{{ $r['client']['id'] }}" hidden>
                <td colspan="10">
                    {{-- Tags, the note and what they need every time, in the
                         professional's own words. Nothing on the platform
                         records any of it, and the client never sees it. --}}
                    <form method="POST" action="{{ route('professional.clients.note', $r['client']['id']) }}" class="mc-noteform">
                        @csrf
                        <div>
                            <label>Tags</label>
                            <input type="text" name="tags" maxlength="160" value="{{ implode(', ', $r['tags']) }}"
                                   placeholder="Comma separated, up to six">
                        </div>
                        <div>
                            <label>What they need every time</label>
                            <input type="text" name="requirements" maxlength="300" value="{{ $r['requirements'] }}"
                                   placeholder="e.g. insurance certificate, invoice on 30 days">
                        </div>
                        <div>
                            <label>Private note</label>
                            <input type="text" name="note" maxlength="400" value="{{ $r['note'] }}"
                                   placeholder="Only you can see this">
                        </div>
                        <button type="submit" class="mc-btn">Save</button>
                    </form>

                    <div style="display:flex;gap:9px;flex-wrap:wrap;margin-top:12px;">
                        <a class="mc-tab" href="{{ $r['conversation']
                            ? route('professional.chat.show', $r['conversation'])
                            : route('professional.chat.index') }}">
                            {{ $r['conversation'] ? 'Open messages' : 'Start a message' }}
                        </a>
                        @if($r['next'])
                            <a class="mc-tab" href="{{ route('professional.gigs.show', $r['next']['id']) }}">Open the gig</a>
                        @endif
                        @if($r['services'])
                            <span class="mc-tab" style="cursor:default;">{{ implode(', ', $r['services']) }}</span>
                        @endif
                    </div>
                </td>
            </tr>
        @endforeach
        </tbody>
    </table>
@endif
</div>

{{-- The rules behind the columns, printed where they are used. Each one is a
     line of code somebody can check, not a judgement. --}}
<div class="mc-card mc-rules">
    <div>
        <h4>What the relationship means</h4>
        <div class="row"><i style="background:#10b981;"></i><span><b>Came back</b>: two or more completed bookings, whenever.</span></div>
        <div class="row"><i style="background:#2563eb;"></i><span><b>Once so far</b>: one booking, and something within the last two years.</span></div>
        <div class="row"><i style="background:#f59e0b;"></i><span><b>Gone quiet</b>: nothing at all in the last two years.</span></div>
    </div>
    <div>
        <h4>Since contact</h4>
        <div class="row"><i style="background:#10b981;"></i><span>Under {{ $quietDays }} days: nothing to do.</span></div>
        <div class="row"><i style="background:#ef4444;"></i><span>{{ $quietDays }} days or more: worth a chase.</span></div>
        <div class="row"><i style="background:#ef4444;"></i><span><b>Never</b>: no job and no message between you. Not the same as a long gap.</span></div>
    </div>
    <div>
        <h4>Books yearly</h4>
        <div class="row"><i style="background:#2563eb;"></i><span>Two bookings in the same calendar month in different years.</span></div>
        <div class="row"><i style="background:var(--border-color);"></i><span>Anything less says no pattern yet, rather than guessing at one.</span></div>
    </div>
</div>

<script>
(function () {
    Array.prototype.slice.call(document.querySelectorAll('[data-client]')).forEach(function (btn) {
        btn.addEventListener('click', function () {
            var row = document.getElementById('client-' + btn.dataset.client);
            if (!row) { return; }

            var opening = row.hidden;

            document.querySelectorAll('tr.detail').forEach(function (r) { r.hidden = true; });
            document.querySelectorAll('[data-client]').forEach(function (b) { b.textContent = 'Open'; });

            row.hidden = !opening;
            btn.textContent = opening ? 'Close' : 'Open';
        });
    });
})();
</script>
@endsection
