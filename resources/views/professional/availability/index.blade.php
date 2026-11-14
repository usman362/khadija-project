@extends('layouts.professional')

@section('title', 'Availability & Resources')
@section('page-title', 'Availability & Resources')
@section('page-subtitle', 'The two things a client asks before they book you: which days you can work, and what you bring with you.')

@php
    /*
     * Khadijah's 28 October sidebar, the one item with no page behind it.
     *
     * Three months of days a professional can answer for, and under them the
     * two things they bring to a gig: the crew on their books and the services
     * they have priced. The days are the same store the calendar writes, so
     * marking one here shows there and the other way round.
     *
     * A day nobody has answered is drawn grey and says so. It is never drawn
     * as free; see App\Domain\Calendar\Availability for why that distinction
     * is the whole reason the store exists.
     */
    $weekdays = ['S', 'M', 'T', 'W', 'T', 'F', 'S'];
@endphp

@push('styles')
<style>
    .av-actions { display:flex; justify-content:flex-end; margin-bottom:16px; }

    .av-stats { display:grid; grid-template-columns:repeat(auto-fit, minmax(150px,1fr)); gap:13px; margin-bottom:22px; }
    .av-stat { background:var(--bg-card); border:1px solid var(--border-color); border-radius:12px; padding:14px 16px; }
    .av-stat .n { font-size:22px; font-weight:800; color:var(--text-primary); line-height:1.1; }
    .av-stat .l { font-size:11.5px; color:var(--text-muted); margin-top:3px; }
    .av-stat .d { width:8px; height:8px; border-radius:50%; display:inline-block; margin-right:6px; vertical-align:middle; }

    .av-card { background:var(--bg-card); border:1px solid var(--border-color); border-radius:12px; padding:18px; margin-bottom:20px; }
    .av-card > h2 { font-size:15px; font-weight:800; margin:0 0 4px; color:var(--text-primary); }
    .av-card > .sub { font-size:12.5px; color:var(--text-muted); margin:0 0 16px; }

    .av-months { display:grid; grid-template-columns:repeat(auto-fit, minmax(260px,1fr)); gap:18px; }
    .av-month h3 { font-size:13px; font-weight:800; margin:0 0 10px; color:var(--text-primary); }
    .av-grid { display:grid; grid-template-columns:repeat(7,1fr); gap:4px; }
    .av-wd { font-size:10px; font-weight:700; color:var(--text-muted); text-align:center; padding-bottom:4px; }
    .av-day { border:1px solid var(--border-color); border-radius:7px; background:var(--bg-primary);
        padding:0; aspect-ratio:1/1; display:flex; align-items:center; justify-content:center;
        font-size:12px; font-weight:700; color:var(--text-primary); cursor:pointer; font-family:inherit; width:100%; }
    .av-day:hover:not(:disabled) { border-color:var(--accent-blue); }
    .av-day:disabled { cursor:default; }
    .av-day.is-past { opacity:0.4; }
    .av-day.is-available { background:rgba(16,185,129,0.18); border-color:#10b981; color:var(--ok-text); }
    .av-day.is-unavailable { background:rgba(239,68,68,0.16); border-color:#ef4444; color:var(--bad-text); }
    .av-day.is-booked { background:#3b82f6; border-color:#2563eb; color:#fff; cursor:default; }
    .av-day.is-today { box-shadow:0 0 0 2px var(--accent-blue); }
    .av-blank { aspect-ratio:1/1; }

    .av-legend { display:flex; gap:16px; flex-wrap:wrap; margin-top:16px; font-size:11.5px; color:var(--text-muted); }
    .av-legend span.k { width:10px; height:10px; border-radius:3px; display:inline-block; margin-right:5px; vertical-align:middle; }

    .av-range { display:flex; gap:10px; flex-wrap:wrap; align-items:flex-end; margin-top:18px;
        padding-top:16px; border-top:1px solid var(--border-color); }
    .av-range label { display:block; font-size:11px; font-weight:700; color:var(--text-muted); margin-bottom:4px; }
    .av-range input, .av-range select { font:inherit; font-size:13px; padding:8px 10px; border-radius:9px;
        border:1px solid var(--border-color); background:var(--bg-primary); color:var(--text-primary); }
    .av-btn { font:inherit; font-size:13px; font-weight:700; padding:9px 16px; border-radius:9px; border:0;
        background:var(--accent-blue); color:#fff; cursor:pointer; }

    .av-table { width:100%; border-collapse:collapse; font-size:13px; }
    .av-table th { text-align:left; font-size:11px; text-transform:uppercase; letter-spacing:0.4px;
        color:var(--text-muted); padding:0 10px 8px; border-bottom:1px solid var(--border-color); }
    .av-table td { padding:11px 10px; border-bottom:1px solid var(--border-color); color:var(--text-primary); }
    .av-table tr:last-child td { border-bottom:0; }
    .av-tag { font-size:10.5px; font-weight:700; padding:3px 8px; border-radius:20px;
        background:var(--bg-primary); border:1px solid var(--border-color); color:var(--text-muted); }
    .av-tag.ok { background:rgba(16,185,129,0.12); border-color:#10b981; color:var(--ok-text); }
    .av-empty { color:var(--text-muted); font-size:13px; padding:18px 0; }
    .av-sub-head { font-size:11px; font-weight:800; text-transform:uppercase; letter-spacing:0.4px;
        color:var(--text-muted); margin:20px 0 10px; padding-top:16px; border-top:1px solid var(--border-color); }
    .av-chips { display:flex; flex-wrap:wrap; gap:6px; }
    .av-chip { font-size:11.5px; padding:4px 10px; border-radius:20px; background:var(--bg-primary);
        border:1px solid var(--border-color); color:var(--text-muted); }
    .av-chip.more { font-weight:700; color:var(--text-primary); }
    .av-note { font-size:12.5px; color:var(--text-muted); margin-top:12px; }
    .av-card a, .av-empty a { color:var(--info-text); font-weight:600; text-decoration:none; }
    .av-card a:hover, .av-empty a:hover { text-decoration:underline; }
</style>
@endpush

@section('content')
<div class="av-actions">
    <a href="{{ route('professional.calendar.index') }}" class="av-btn" style="text-decoration:none;">Open calendar</a>
</div>

<div class="av-stats">
    <div class="av-stat"><div class="n"><span class="d" style="background:#10b981"></span>{{ $counts['available'] }}</div><div class="l">Days you said available</div></div>
    <div class="av-stat"><div class="n"><span class="d" style="background:#ef4444"></span>{{ $counts['unavailable'] }}</div><div class="l">Days you said unavailable</div></div>
    <div class="av-stat"><div class="n"><span class="d" style="background:#3b82f6"></span>{{ $counts['booked'] }}</div><div class="l">Days already booked</div></div>
    <div class="av-stat"><div class="n">{{ $counts['unanswered'] }}</div><div class="l">Days with no answer yet</div></div>
    <div class="av-stat"><div class="n">{{ $counts['crew'] }}</div><div class="l">People on your crew</div></div>
    <div class="av-stat"><div class="n">{{ $counts['priced'] }} / {{ $counts['services'] }}</div><div class="l">Services with a price</div></div>
</div>

<div class="av-card">
    <h2>Your days</h2>
    <p class="sub">Click a day to say you are available. Click it again to say you are not, and once more to take the answer back. A day nobody has answered stays grey, and a client is never told it is free.</p>

    <div class="av-months">
        @foreach ($months as $month)
            <div class="av-month">
                <h3>{{ $month['label'] }}</h3>
                <div class="av-grid">
                    @foreach ($weekdays as $wd)
                        <div class="av-wd">{{ $wd }}</div>
                    @endforeach

                    @for ($i = 0; $i < $month['lead']; $i++)
                        <div class="av-blank"></div>
                    @endfor

                    @foreach ($month['days'] as $day)
                        @php
                            // Clicking cycles: nothing → available → unavailable → nothing.
                            // mark() clears a day already holding the state it is sent,
                            // so "unavailable" twice is how the answer comes off.
                            $next = $day['state'] === 'available' ? 'unavailable' : 'available';
                            $cls  = $day['booked'] ? 'is-booked' : ($day['state'] ? 'is-' . $day['state'] : '');
                            $tip  = $day['booked']
                                ? 'Booked: ' . ($day['gig'] ?? 'a gig')
                                : ($day['state'] ? ucfirst($day['state']) : 'No answer yet');
                        @endphp
                        <form method="POST" action="{{ route('professional.availability.day') }}" style="display:contents;">
                            @csrf
                            <input type="hidden" name="day" value="{{ $day['date'] }}">
                            <input type="hidden" name="state" value="{{ $next }}">
                            <button type="submit"
                                    class="av-day {{ $cls }} {{ $day['today'] ? 'is-today' : '' }} {{ $day['past'] ? 'is-past' : '' }}"
                                    title="{{ $day['date'] }}: {{ $tip }}"
                                    @disabled($day['past'] || $day['booked'])>{{ $day['num'] }}</button>
                        </form>
                    @endforeach
                </div>
            </div>
        @endforeach
    </div>

    <div class="av-legend">
        <span><span class="k" style="background:rgba(16,185,129,0.4); border:1px solid #10b981"></span>Available</span>
        <span><span class="k" style="background:rgba(239,68,68,0.3); border:1px solid #ef4444"></span>Unavailable</span>
        <span><span class="k" style="background:#3b82f6; border:1px solid #2563eb"></span>Booked</span>
        <span><span class="k" style="background:var(--bg-primary); border:1px solid var(--border-color)"></span>No answer yet</span>
    </div>

    <form method="POST" action="{{ route('professional.availability.range') }}" class="av-range">
        @csrf
        <div>
            <label for="avFrom">From</label>
            <input type="date" id="avFrom" name="from" value="{{ $today->format('Y-m-d') }}" required>
        </div>
        <div>
            <label for="avTo">To</label>
            <input type="date" id="avTo" name="to" value="{{ $today->copy()->addDays(6)->format('Y-m-d') }}" required>
        </div>
        <div>
            <label for="avState">Set these days</label>
            <select id="avState" name="state">
                <option value="available">Available</option>
                <option value="unavailable">Unavailable</option>
                <option value="not_set">Back to no answer</option>
            </select>
        </div>
        <button type="submit" class="av-btn">Apply to range</button>
    </form>
    @error('to') <div class="av-note" style="color:var(--bad-text);">{{ $message }}</div> @enderror
</div>

<div class="av-card">
    <h2>Your crew</h2>
    <p class="sub">The people you can put on a gig. Add and edit them on Team &amp; Workforce.</p>

    @if ($crew->isEmpty())
        <div class="av-empty">Nobody on the books yet. <a href="{{ route('professional.team.index') }}">Add your first crew member</a>.</div>
    @else
        <table class="av-table">
            <thead><tr><th>Name</th><th>Role</th><th>Rate</th><th>Status</th></tr></thead>
            <tbody>
            @foreach ($crew as $person)
                <tr>
                    <td>
                        <strong>{{ $person->name }}</strong>
                        @if ($person->is_supervisor) <span class="av-tag">Supervisor</span> @endif
                    </td>
                    <td>{{ $person->role ?: 'Not set' }}</td>
                    <td>{{ $person->hourly_rate !== null ? '$' . number_format((float) $person->hourly_rate, 2) . ' / hr' : 'Not set' }}</td>
                    <td><span class="av-tag {{ $person->status === 'active' ? 'ok' : '' }}">{{ ucfirst($person->status ?: 'unknown') }}</span></td>
                </tr>
            @endforeach
            </tbody>
        </table>
    @endif
</div>

<div class="av-card">
    <h2>What you offer</h2>
    <p class="sub">A service without a price cannot fill a package or answer a request on its own. Set them on My Services &amp; Pricing.</p>

    @php
        /*
         * One row per service put 241 rows on this page, all but one of them
         * reading "No price yet", and the card became the page. A service with
         * no price has nothing to say in four columns: it is a name and a
         * missing number. So the table carries the priced ones, which have
         * figures worth reading, and the rest are named as chips and counted.
         */
        $priced   = $services->filter(fn ($s) => $s->pivot->price !== null)->values();
        $unpriced = $services->filter(fn ($s) => $s->pivot->price === null)->values();
        $shown    = $unpriced->take(24);
        $hidden   = $unpriced->count() - $shown->count();
    @endphp

    @if ($services->isEmpty())
        <div class="av-empty">No services on your profile yet. <a href="{{ route('professional.services.index') }}">Pick the ones you offer</a>.</div>
    @else
        @if ($priced->isNotEmpty())
            <table class="av-table">
                <thead><tr><th>Service</th><th>Price</th><th>Covers</th><th>Extra hour</th></tr></thead>
                <tbody>
                @foreach ($priced as $service)
                    <tr>
                        <td><strong>{{ $service->name }}</strong></td>
                        <td>
                            ${{ number_format((float) $service->pivot->price, 2) }}
                            <span class="av-tag">{{ $service->pivot->price_unit ?: 'flat rate' }}</span>
                        </td>
                        <td>{{ $service->pivot->duration_hours ? $service->pivot->duration_hours . ' hours' : 'Not set' }}</td>
                        <td>{{ $service->pivot->extra_hour_price !== null ? '$' . number_format((float) $service->pivot->extra_hour_price, 2) : 'Not set' }}</td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        @else
            <div class="av-empty">Not one of your {{ $counts['services'] }} services has a price on it yet.</div>
        @endif

        @if ($unpriced->isNotEmpty())
            <div class="av-sub-head">Waiting on a price &middot; {{ $unpriced->count() }}</div>
            <div class="av-chips">
                @foreach ($shown as $service)
                    <span class="av-chip">{{ $service->name }}</span>
                @endforeach
                @if ($hidden > 0)
                    <span class="av-chip more">and {{ number_format($hidden) }} more</span>
                @endif
            </div>
            <div class="av-note">
                <a href="{{ route('professional.services.index') }}">Price them on My Services &amp; Pricing</a>.
                A service without a price cannot fill a package or answer a request.
            </div>
        @endif
    @endif
</div>
@endsection
