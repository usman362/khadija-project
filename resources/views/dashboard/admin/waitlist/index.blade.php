@extends('layouts.dashboard')

@section('title', 'Expansion Waitlist')

@section('content')
<div class="d-flex justify-content-between align-items-center flex-wrap mb-4">
    <div>
        <h4 class="mb-1"><i data-lucide="map-pin" class="me-2" style="width:24px;height:24px;"></i> Expansion Waitlist</h4>
        <p class="text-secondary mb-0">
            People who signed up from somewhere we don't operate yet. Everyone here has a working account —
            they just can't book or bid until we open their state.
        </p>
    </div>
</div>

<div class="row g-3 mb-4">
    <div class="col-md-4">
        <div class="card h-100"><div class="card-body">
            <p class="text-secondary mb-1 small">Waiting</p>
            <h3 class="mb-0">{{ number_format($totalWaiting) }}</h3>
        </div></div>
    </div>
    <div class="col-md-4">
        <div class="card h-100"><div class="card-body">
            <p class="text-secondary mb-1 small">Asked to be notified</p>
            <h3 class="mb-0">{{ number_format($totalNotify) }}</h3>
        </div></div>
    </div>
    <div class="col-md-4">
        <div class="card h-100"><div class="card-body">
            <p class="text-secondary mb-1 small">Currently open</p>
            <h3 class="mb-0">{{ count($openStates) }} <span class="fs-6 text-secondary">states</span></h3>
            <p class="text-secondary mb-0 small mt-1">{{ implode(' · ', $openStates) }}</p>
        </div></div>
    </div>
</div>

<div class="row g-3">
    <div class="col-xl-5">
        <div class="card h-100">
            <div class="card-body">
                <h6 class="mb-1">Where they are</h6>
                <p class="text-secondary small mb-3">Largest first — this is the "where do we open next" list.</p>

                @if($byState->isEmpty())
                    <p class="text-secondary mb-0">Nobody is waiting. Every registration so far is inside the launch area.</p>
                @else
                    <div class="table-responsive">
                        <table class="table table-sm mb-0 align-middle">
                            <thead>
                                <tr>
                                    <th>Place</th>
                                    <th class="text-end">People</th>
                                    <th class="text-end">Notify me</th>
                                    <th class="text-end">Latest</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($byState as $row)
                                    <tr @class(['table-active' => $state === $row['state']])>
                                        <td>
                                            @if($row['state'])
                                                <a href="{{ route('app.admin.waitlist.index', ['state' => $row['state']]) }}">{{ $row['label'] }}</a>
                                            @else
                                                <span class="text-secondary">{{ $row['label'] }}</span>
                                            @endif
                                        </td>
                                        <td class="text-end fw-semibold">{{ $row['people'] }}</td>
                                        <td class="text-end">
                                            @if($row['notify'] > 0)
                                                <span class="badge bg-success-subtle text-success">{{ $row['notify'] }}</span>
                                            @else
                                                <span class="text-secondary">—</span>
                                            @endif
                                        </td>
                                        <td class="text-end text-secondary small">
                                            {{ $row['latest'] ? \Illuminate\Support\Carbon::parse($row['latest'])->diffForHumans(short: true) : '—' }}
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </div>
    </div>

    <div class="col-xl-7">
        <div class="card h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center flex-wrap mb-3">
                    <div>
                        <h6 class="mb-1">
                            @if($state)
                                Waiting in {{ config('geo.us_states', [])[$state] ?? $state }}
                            @else
                                Everyone waiting
                            @endif
                        </h6>
                        <p class="text-secondary small mb-0">{{ $people->total() }} {{ Str::plural('account', $people->total()) }}</p>
                    </div>
                    @if($state)
                        <a href="{{ route('app.admin.waitlist.index') }}" class="btn btn-sm btn-outline-secondary">Show all</a>
                    @endif
                </div>

                @if($people->isEmpty())
                    <p class="text-secondary mb-0">Nothing to show here.</p>
                @else
                    <div class="table-responsive">
                        <table class="table table-sm mb-0 align-middle">
                            <thead>
                                <tr>
                                    <th>Name</th>
                                    <th>Where</th>
                                    <th>Notify</th>
                                    <th>Signed up</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($people as $profile)
                                    <tr>
                                        <td>
                                            <div>{{ $profile->user?->name ?? 'Deleted user' }}</div>
                                            <div class="text-secondary small">{{ $profile->user?->email }}</div>
                                        </td>
                                        <td class="small">
                                            {{ \App\Support\ServiceArea::describe($profile->city, $profile->state, $profile->country) }}
                                        </td>
                                        <td>
                                            @if($profile->expansion_opt_in)
                                                <span class="badge bg-success-subtle text-success">Yes</span>
                                            @else
                                                <span class="text-secondary">—</span>
                                            @endif
                                        </td>
                                        <td class="text-secondary small">
                                            {{ optional($profile->user?->created_at)->format('j M Y') ?? '—' }}
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <div class="mt-3">{{ $people->links() }}</div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
