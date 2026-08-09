@extends('layouts.dashboard')

@section('title', 'Case ' . $case->reference)

@php
    use App\Domain\Disputes\DisputeClassification;
    use App\Domain\Disputes\DisputeStates;

    /*
     * Rule R34 Phase 2 — one case, as staff work it.
     *
     * The Decision Matrix appears on this page as a column of suggestions
     * WITH their reasoning, beside the decision form — never inside it. §5
     * and R29 both make the same point: it is a consistency guide for a human
     * investigator, not a decision engine. A "suggest an outcome" button that
     * filled the form in would have made the guide the decider whatever the
     * label above it said.
     */
    $staffOnly = fn ($entry) => ! $entry->visible_to_parties;
@endphp

@section('content')
<div class="d-flex justify-content-between align-items-center flex-wrap mb-4">
    <div>
        <h4 class="mb-1">
            <span class="font-monospace">{{ $case->reference }}</span>
            <span class="badge bg-secondary ms-2">{{ $case->stateLabel() }}</span>
        </h4>
        <p class="text-secondary mb-0">
            {{ $case->taxonomyLabel() }} · {{ $case->severityLabel() }} ·
            {{ $case->booking?->event?->title ?? 'Booking #' . $case->booking_id }}
        </p>
    </div>
    <a href="{{ route('app.admin.disputes.index') }}" class="btn btn-sm btn-outline-secondary">Back to queue</a>
</div>

@if(session('status'))
    <div class="alert alert-success py-2">{{ session('status') }}</div>
@endif

@if($errors->any())
    <div class="alert alert-danger py-2">
        @foreach($errors->all() as $error)<div>{{ $error }}</div>@endforeach
    </div>
@endif

<div class="row g-3">
    <div class="col-lg-8">
        {{-- ── The case ──────────────────────────────────────── --}}
        <div class="card mb-3"><div class="card-body">
            <h6 class="text-secondary text-uppercase small mb-2">What was filed</h6>
            <p style="white-space:pre-line;" class="mb-2">{{ $case->summary }}</p>
            <p class="text-secondary small mb-0">
                Filed by {{ $case->filedBy?->name ?? 'unknown' }} on {{ $case->created_at?->format('M j, Y') }}.
                Client: {{ $case->client?->name }} · Professional: {{ $case->professional?->name }}.
            </p>
        </div></div>

        {{-- ── Evidence ──────────────────────────────────────── --}}
        <div class="card mb-3"><div class="card-body">
            <h6 class="text-secondary text-uppercase small mb-2">Evidence</h6>

            @forelse($evidence as $item)
                <div class="border-top py-2 {{ $item->isWithdrawn() ? 'opacity-50' : '' }}">
                    <div class="d-flex justify-content-between gap-2">
                        <strong class="small">
                            {{ $item->kindLabel() }}
                            {{-- §4's hierarchy: platform records are primary
                                 because they are timestamped and verifiable.
                                 An investigator needs to see that at a glance. --}}
                            @if($item->platform_generated)
                                <span class="badge bg-primary-subtle text-primary-emphasis ms-1">Platform record</span>
                            @endif
                        </strong>
                        <span class="text-secondary" style="font-size:11.5px;">
                            {{ $item->submitter?->name ?? 'Unknown' }} · {{ $item->created_at?->format('M j, Y') }}
                        </span>
                    </div>
                    <p class="mb-1 small" style="white-space:pre-line;">{{ $item->description }}</p>
                    <p class="text-secondary mb-0" style="font-size:11.5px;">
                        Weight: {{ $item->weightLabel() }}
                        @if($item->isWithdrawn()) · Withdrawn: {{ $item->withdrawn_reason }} @endif
                        @if($item->isSuperseded()) · Replaced by a later submission @endif
                    </p>
                </div>
            @empty
                <p class="text-secondary small mb-0">Nothing submitted yet.</p>
            @endforelse
        </div></div>

        {{-- ── Decision ──────────────────────────────────────── --}}
        @if($canDecide && ! DisputeStates::isTerminal($case->state))
            <div class="card mb-3"><div class="card-body">
                <h6 class="text-secondary text-uppercase small mb-1">
                    {{ $decision ? 'Revise the decision' : 'Record a decision' }}
                </h6>
                <p class="text-secondary small mb-3">
                    Platform conformance review — was the work as agreed in the contract, not was
                    the client satisfied.
                </p>

                <form method="POST" action="{{ route('app.admin.disputes.decide', $case) }}">
                    @csrf
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-bold" for="resolution_type">Resolution type</label>
                            <select name="resolution_type" id="resolution_type" class="form-select form-select-sm" required>
                                @foreach($resolutionTypes as $key => $label)
                                    <option value="{{ $key }}" @selected(old('resolution_type') === $key)>{{ $label }}</option>
                                @endforeach
                            </select>
                            <div class="form-text" style="font-size:11.5px;">The reporting label for this case.</div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold" for="financial_outcome">Financial outcome</label>
                            <select name="financial_outcome" id="financial_outcome" class="form-select form-select-sm">
                                <option value="">None — housekeeping closure</option>
                                @foreach($outcomes as $key => $label)
                                    <option value="{{ $key }}" @selected(old('financial_outcome') === $key)>{{ $label }}</option>
                                @endforeach
                            </select>
                            <div class="form-text" style="font-size:11.5px;">
                                What happens to the held balance. A separate axis from the label.
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold" for="amount_to_client">Amount to the client</label>
                            <input type="number" step="0.01" min="0" name="amount_to_client" id="amount_to_client"
                                   class="form-control form-control-sm" value="{{ old('amount_to_client') }}">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold" for="amount_to_professional">Amount to the professional</label>
                            <input type="number" step="0.01" min="0" name="amount_to_professional" id="amount_to_professional"
                                   class="form-control form-control-sm" value="{{ old('amount_to_professional') }}">
                        </div>

                        {{-- §7 — a fraud finding names the party it is
                             against. The client who filed can be the one who
                             altered the invoice, so this is recorded rather
                             than inferred from who holds which role. --}}
                        <div class="col-md-6">
                            <label class="form-label small fw-bold" for="finding_against">Fraud finding is against</label>
                            <select name="finding_against" id="finding_against" class="form-select form-select-sm">
                                <option value="">Not a fraud finding</option>
                                <option value="{{ $case->client_id }}">{{ $case->client?->name }} (client)</option>
                                <option value="{{ $case->professional_id }}">{{ $case->professional?->name }} (professional)</option>
                            </select>
                            <div class="form-text" style="font-size:11.5px;">
                                Required when the resolution type is Fraud Confirmed.
                            </div>
                        </div>

                        @if($decision)
                            <div class="col-md-6">
                                <label class="form-label small fw-bold" for="revision_reason">Why it is being revised</label>
                                <input type="text" name="revision_reason" id="revision_reason"
                                       class="form-control form-control-sm" value="{{ old('revision_reason') }}">
                                <div class="form-text" style="font-size:11.5px;">
                                    The original stays on the case, and both parties are told.
                                </div>
                            </div>
                        @endif

                        <div class="col-12">
                            <label class="form-label small fw-bold" for="reasoning">Reasoning</label>
                            <textarea name="reasoning" id="reasoning" class="form-control form-control-sm"
                                      rows="4" required>{{ old('reasoning') }}</textarea>
                            <div class="form-text" style="font-size:11.5px;">
                                Goes to both parties word for word. A decision with no stated reasoning
                                answers nothing later.
                            </div>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-sm btn-primary mt-3">
                        {{ $decision ? 'Record the revision' : 'Record the decision' }}
                    </button>
                </form>
            </div></div>
        @elseif($decision)
            <div class="card mb-3"><div class="card-body">
                <h6 class="text-secondary text-uppercase small mb-2">Decision</h6>
                <p class="mb-1"><strong>{{ $decision->resolutionTypeLabel() }}</strong>
                    @if($decision->financialOutcomeLabel()) — {{ $decision->financialOutcomeLabel() }} @endif
                </p>
                <p class="small mb-2" style="white-space:pre-line;">{{ $decision->reasoning }}</p>
                <p class="text-secondary small mb-0">
                    {{ $decision->decider?->name }} ({{ $decision->decided_role }}) ·
                    {{ $decision->created_at?->format('M j, Y') }}
                </p>
            </div></div>
        @endif

        {{-- ── The guide, beside the form and never inside it ── --}}
        <div class="card mb-3"><div class="card-body">
            <h6 class="text-secondary text-uppercase small mb-1">Consistency guide</h6>
            <p class="text-secondary small mb-3">
                What comparable findings have usually led to, and why. A reference for the person
                deciding — it fills nothing in and decides nothing.
            </p>
            <div class="table-responsive">
                <table class="table table-sm mb-0">
                    <thead><tr><th>Finding</th><th>Usually leads to</th><th>Why</th></tr></thead>
                    <tbody>
                        @foreach($guide as $row)
                            <tr>
                                <td class="small">{{ $row['finding'] }}</td>
                                <td class="small">{{ $outcomes[$row['suggests']] ?? $row['suggests'] }}</td>
                                <td class="small text-secondary">{{ $row['because'] }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div></div>

        {{-- ── Audit trail, in full ──────────────────────────── --}}
        <div class="card mb-3"><div class="card-body">
            <h6 class="text-secondary text-uppercase small mb-2">Audit trail</h6>
            @forelse($timeline as $entry)
                <div class="border-top py-2 small">
                    <div class="d-flex justify-content-between gap-2">
                        <span>
                            <strong>{{ ucfirst(str_replace('_', ' ', $entry->action)) }}</strong>
                            @if($entry->old_value || $entry->new_value)
                                — {{ $entry->old_value ?: '(none)' }} → {{ $entry->new_value ?: '(none)' }}
                            @endif
                            @if($staffOnly($entry))
                                <span class="badge bg-secondary-subtle text-secondary-emphasis ms-1">Internal</span>
                            @endif
                        </span>
                        <span class="text-secondary" style="font-size:11.5px;">
                            {{ $entry->actor?->name ?? 'System' }}
                            @if($entry->actor_role) ({{ $entry->actor_role }}) @endif
                            · {{ $entry->created_at?->format('M j, Y g:ia') }}
                        </span>
                    </div>
                    @if($entry->reason)
                        <p class="text-secondary mb-0" style="font-size:11.5px;">{{ $entry->reason }}</p>
                    @endif
                </div>
            @empty
                <p class="text-secondary small mb-0">Nothing recorded yet.</p>
            @endforelse
        </div></div>
    </div>

    {{-- ── Sidebar ───────────────────────────────────────────── --}}
    <div class="col-lg-4">
        {{-- Classification — §3's three independent fields --}}
        <div class="card mb-3"><div class="card-body">
            <h6 class="text-secondary text-uppercase small mb-2">Classification</h6>
            <form method="POST" action="{{ route('app.admin.disputes.classify', $case) }}">
                @csrf
                <div class="mb-2">
                    <label class="form-label small fw-bold" for="severity">Severity</label>
                    <select name="severity" id="severity" class="form-select form-select-sm">
                        @foreach($severities as $level => $label)
                            <option value="{{ $level }}" @selected($case->severity === $level)>{{ $level }} — {{ $label }}</option>
                        @endforeach
                    </select>
                    <div class="form-text" style="font-size:11.5px;">
                        Levels 4 and 5 move the case straight to review.
                    </div>
                </div>
                <div class="mb-2">
                    <label class="form-label small fw-bold" for="priority">Priority</label>
                    <select name="priority" id="priority" class="form-select form-select-sm">
                        @foreach($priorities as $key => $label)
                            <option value="{{ $key }}" @selected($case->priority === $key)>{{ $label }}</option>
                        @endforeach
                    </select>
                    <div class="form-text" style="font-size:11.5px;">
                        Set independently. A high-value quality dispute can outrank a payment one.
                    </div>
                </div>
                <div class="mb-2">
                    <label class="form-label small fw-bold" for="tax">Subject</label>
                    <select name="taxonomy" id="tax" class="form-select form-select-sm">
                        @foreach($taxonomy as $key => $label)
                            <option value="{{ $key }}" @selected($case->taxonomy === $key)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <button type="submit" class="btn btn-sm btn-outline-primary">Save classification</button>
            </form>
        </div></div>

        {{-- Assignment, with §7's disclosure built into it --}}
        <div class="card mb-3"><div class="card-body">
            <h6 class="text-secondary text-uppercase small mb-2">Case owner</h6>
            <p class="small mb-2">
                {{ $case->assignee?->name ?? 'Unassigned' }}
                @if($case->assigned_role)
                    <span class="text-secondary">— {{ $staffRoles[$case->assigned_role] ?? $case->assigned_role }}</span>
                @endif
            </p>

            <form method="POST" action="{{ route('app.admin.disputes.assign', $case) }}">
                @csrf
                <div class="mb-2">
                    <label class="form-label small fw-bold" for="staff_id">Assign to</label>
                    <select name="staff_id" id="staff_id" class="form-select form-select-sm" required>
                        <option value="">Choose a staff member…</option>
                        @foreach($assignable as $person)
                            <option value="{{ $person->id }}" @selected($case->assigned_to === $person->id)>
                                {{ $person->name }} — {{ $person->email }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="mb-2">
                    <label class="form-label small fw-bold" for="assign_role">In the role of</label>
                    <select name="role" id="assign_role" class="form-select form-select-sm">
                        @foreach($staffRoles as $key => $label)
                            <option value="{{ $key }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="mb-2">
                    <label class="form-label small fw-bold">Conflict of interest</label>
                    <div class="form-check">
                        <input class="form-check-input" type="radio" name="has_connection" id="conn_no" value="no" required>
                        <label class="form-check-label small" for="conn_no">No personal connection to either party</label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="radio" name="has_connection" id="conn_yes" value="yes">
                        <label class="form-check-label small" for="conn_yes">There is a connection</label>
                    </div>
                    <input type="text" name="conflict_detail" class="form-control form-control-sm mt-2"
                           placeholder="Describe it">
                    <div class="form-text" style="font-size:11.5px;">
                        A disclosed connection means this case needs a different owner.
                    </div>
                </div>
                <button type="submit" class="btn btn-sm btn-outline-primary">Assign</button>
            </form>
        </div></div>

        {{-- §7 — history per account. Only confirmed findings count. --}}
        <div class="card mb-3"><div class="card-body">
            <h6 class="text-secondary text-uppercase small mb-2">Account history</h6>
            @foreach(['client' => $case->client, 'professional' => $case->professional] as $side => $person)
                <div class="border-top py-2">
                    <div class="d-flex justify-content-between small">
                        <strong>{{ $person?->name ?? ucfirst($side) }}</strong>
                        <span class="text-secondary">{{ ucfirst($side) }}</span>
                    </div>
                    <div class="small text-secondary">
                        {{ $history[$side]['findings'] }} confirmed
                        {{ Str::plural('finding', $history[$side]['findings']) }}
                        across {{ $history[$side]['cases'] }} {{ Str::plural('case', $history[$side]['cases']) }}
                    </div>
                    <div class="small">Ladder suggests: <strong>{{ $history[$side]['step'] }}</strong></div>
                </div>
            @endforeach
            <p class="text-secondary mt-2 mb-0" style="font-size:11.5px;">
                Only confirmed outcomes count here. Filing a case, or having one filed against you,
                counts for nothing until it is decided. The ladder is a recommendation — nothing is
                applied automatically.
            </p>
        </div></div>

        {{-- §6 — independent-but-connected cases, staff-visible --}}
        @if($related->isNotEmpty())
            <div class="card mb-3"><div class="card-body">
                <h6 class="text-secondary text-uppercase small mb-2">Related cases</h6>
                @foreach($related as $sibling)
                    <div class="border-top py-2 small">
                        <a href="{{ route('app.admin.disputes.show', $sibling) }}" class="font-monospace">{{ $sibling->reference }}</a>
                        — {{ $sibling->professional?->name }} · {{ $sibling->stateLabel() }}
                    </div>
                @endforeach
                <p class="text-secondary mt-2 mb-0" style="font-size:11.5px;">
                    Same event, different service line. Each resolves entirely on its own merits.
                </p>
            </div></div>
        @endif

        {{-- Internal note — never reaches either party --}}
        <div class="card mb-3"><div class="card-body">
            <h6 class="text-secondary text-uppercase small mb-2">Internal note</h6>
            <form method="POST" action="{{ route('app.admin.disputes.note', $case) }}">
                @csrf
                <textarea name="note" class="form-control form-control-sm mb-2" rows="3" required></textarea>
                <button type="submit" class="btn btn-sm btn-outline-secondary">Add note</button>
            </form>
            <p class="text-secondary mt-2 mb-0" style="font-size:11.5px;">Staff only. The parties never see this.</p>
        </div></div>

        {{-- Closure --}}
        @unless(DisputeStates::isTerminal($case->state))
            <div class="card"><div class="card-body">
                <h6 class="text-secondary text-uppercase small mb-2">Close the case</h6>
                <form method="POST" action="{{ route('app.admin.disputes.close', $case) }}">
                    @csrf
                    <textarea name="closure_note" class="form-control form-control-sm mb-2" rows="2"
                              placeholder="Closing note" required></textarea>
                    <button type="submit" class="btn btn-sm btn-outline-danger">Close</button>
                </form>
                <p class="text-secondary mt-2 mb-0" style="font-size:11.5px;">
                    A closed case is never reopened — a new case is opened instead. Closing also
                    ends the hold on this booking's balance.
                </p>
            </div></div>
        @endunless
    </div>
</div>
@endsection
