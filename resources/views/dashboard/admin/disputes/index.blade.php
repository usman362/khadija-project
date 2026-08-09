@extends('layouts.dashboard')

@section('title', 'Disputes')

@php
    use App\Domain\Disputes\DisputeStates;

    /*
     * Rule R34 Phase 2 — the staff queue.
     *
     * Ordered by PRIORITY, then age. Not by severity: §3 separates the two
     * fields precisely so a high-value quality dispute can outrank a payment
     * dispute, and sorting by severity would bury the cases that separation
     * exists to surface.
     */
    $tone = fn ($p) => match ($p) {
        'critical' => 'danger',
        'high'     => 'warning',
        'low'      => 'secondary',
        default    => 'info',
    };
@endphp

@section('content')
<div class="d-flex justify-content-between align-items-center flex-wrap mb-4">
    <div>
        <h4 class="mb-1"><i data-lucide="gavel" class="me-2" style="width:24px;height:24px;"></i> Disputes</h4>
        <p class="text-secondary mb-0">
            Worked in priority order, oldest first. Every case covers one service line.
        </p>
    </div>
    <form method="GET" class="d-flex gap-2">
        <select name="state" class="form-select form-select-sm" onchange="this.form.submit()">
            <option value="open" @selected($state === 'open')>Open cases</option>
            <option value="all"  @selected($state === 'all')>All cases</option>
            @foreach($states as $key => $label)
                <option value="{{ $key }}" @selected($state === $key)>{{ $label }}</option>
            @endforeach
        </select>
        <select name="priority" class="form-select form-select-sm" onchange="this.form.submit()">
            <option value="all" @selected($priority === 'all')>Any priority</option>
            @foreach($priorities as $key => $label)
                <option value="{{ $key }}" @selected($priority === $key)>{{ $label }}</option>
            @endforeach
        </select>
    </form>
</div>

@if(session('status'))
    <div class="alert alert-success py-2">{{ session('status') }}</div>
@endif

<div class="row g-3 mb-4">
    @foreach([
        ['Open cases', $counts['open'], null],
        ['Unassigned', $counts['unassigned'], 'Nobody owns these yet'],
        ['Critical priority', $counts['critical'], null],
    ] as [$label, $value, $note])
        <div class="col-md-4 col-sm-6">
            <div class="card h-100"><div class="card-body">
                <p class="text-secondary mb-1 small">{{ $label }}</p>
                <h3 class="mb-0">{{ number_format($value) }}</h3>
                @if($note)<p class="text-secondary mb-0" style="font-size:11.5px;">{{ $note }}</p>@endif
            </div></div>
        </div>
    @endforeach
</div>

<div class="card">
    <div class="card-body">
        @if($cases->isEmpty())
            <p class="text-secondary mb-0 py-4 text-center">No cases match this filter.</p>
        @else
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Case</th>
                            <th>Priority</th>
                            <th>Severity</th>
                            <th>Subject</th>
                            <th>Client</th>
                            <th>Professional</th>
                            <th>Owner</th>
                            <th>Status</th>
                            <th>Age</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($cases as $case)
                            <tr>
                                <td>
                                    <a href="{{ route('app.admin.disputes.show', $case) }}" class="fw-bold font-monospace small">
                                        {{ $case->reference }}
                                    </a>
                                </td>
                                <td>
                                    <span class="badge bg-{{ $tone($case->priority) }}">
                                        {{ $priorities[$case->priority] ?? $case->priority }}
                                    </span>
                                </td>
                                <td class="small">{{ $case->severityLabel() }}</td>
                                <td class="small">{{ $case->taxonomyLabel() }}</td>
                                <td class="small">{{ $case->client?->name ?? '—' }}</td>
                                <td class="small">{{ $case->professional?->name ?? '—' }}</td>
                                <td class="small">
                                    @if($case->assignee)
                                        {{ $case->assignee->name }}
                                    @else
                                        <span class="text-secondary">Unassigned</span>
                                    @endif
                                </td>
                                <td class="small">{{ $case->stateLabel() }}</td>
                                {{-- Age, not "days remaining". §12 holds every
                                     window; how long a case has been open is a
                                     fact, a countdown would be a policy. --}}
                                <td class="small text-secondary">{{ $case->created_at?->diffForHumans(short: true) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</div>
@endsection
