@extends($layout)

@section('title', 'Case ' . $case->reference)

@php
    use App\Domain\Disputes\DecisionGuide;
    use App\Domain\Disputes\DisputeStates;

    /*
     * Rule R34 Phase 2 — one case, as a party sees it.
     *
     * The wording on this page is governed by §2 and §12: the platform review
     * is a "platform review", never a neutral, impartial, unbiased or fair
     * one. GigResource is a party to the contract it is reviewing, and DC's
     * consumer law has a low enough injury bar that the adjective is the
     * exposure. DecisionGuide::BANNED_WORDING keeps the list; a test in
     * DisputeResolutionPhase1Test keeps it honest.
     */
    $badge = match ($case->state) {
        DisputeStates::DECIDED, DisputeStates::CLOSED    => 'dsp-done',
        DisputeStates::FORMAL_INVESTIGATION,
        DisputeStates::OUTSIDE_ESCALATION                => 'dsp-review',
        DisputeStates::WITHDRAWN, DisputeStates::EXPIRED => 'dsp-shut',
        default                                          => 'dsp-open',
    };

    $other  = $case->client_id === auth()->id() ? $case->professional : $case->client;
    $mine   = fn ($item) => $item->submitted_by === auth()->id();
    $isFiler = auth()->id() === $case->filed_by;
@endphp

@push('styles')
    @include('disputes._styles')
@endpush

@section('content')
<div class="dsp-head">
    <div>
        <h1 class="dsp-h1">
            <span class="dsp-ref">{{ $case->reference }}</span>
            <span class="dsp-badge {{ $badge }}" style="margin-left:8px;vertical-align:middle;">{{ $case->stateLabel() }}</span>
        </h1>
        <p class="dsp-sub">
            {{ $case->taxonomyLabel() }} · {{ $case->booking?->event?->title ?? 'Booking #' . $case->booking_id }}
            · with {{ $other?->name ?? 'the other party' }}
        </p>
    </div>
    <a href="{{ route('disputes.index') }}" class="cl-btn">All disputes</a>
</div>

@if(session('status'))
    <div class="dsp-flash">{{ session('status') }}</div>
@endif

<div class="dsp-two">
    <div>
        {{-- ── The decision, when there is one ───────────────── --}}
        @if($decision)
            <div class="dsp-card">
                <p class="dsp-sec">Decision</p>
                <p style="font-size:16px;font-weight:800;margin:0 0 4px;">{{ $decision->resolutionTypeLabel() }}</p>
                @if($decision->financialOutcomeLabel())
                    <p style="font-size:13px;color:var(--text-muted);margin:0 0 12px;">{{ $decision->financialOutcomeLabel() }}</p>
                @endif

                <p style="font-size:13.5px;line-height:1.65;white-space:pre-line;margin:0;">{{ $decision->reasoning }}</p>

                @if($decision->amount_to_client || $decision->amount_to_professional)
                    <dl style="margin:14px 0 0;">
                        @if($decision->amount_to_client)
                            <div class="dsp-row"><dt>Returning to the client</dt><dd>${{ number_format($decision->amount_to_client, 2) }}</dd></div>
                        @endif
                        @if($decision->amount_to_professional)
                            <div class="dsp-row"><dt>Releasing to the professional</dt><dd>${{ number_format($decision->amount_to_professional, 2) }}</dd></div>
                        @endif
                    </dl>
                @endif

                @if($decision->isRevision())
                    {{-- §5 — a revision never replaces the original silently.
                         Being told the decision changed is the whole point. --}}
                    <p class="dsp-hint" style="margin-top:12px;">
                        This decision was revised on {{ $decision->created_at?->format('M j, Y') }}.
                        Reason: {{ $decision->revision_reason ?: 'not recorded' }}.
                    </p>
                @endif

                <p class="dsp-hint" style="margin-top:12px;">
                    Issued by GigResource platform review, comparing what was delivered against
                    the agreed contract terms.
                </p>
            </div>
        @endif

        {{-- ── What was said ─────────────────────────────────── --}}
        <div class="dsp-card">
            <p class="dsp-sec">The case</p>
            <p style="font-size:13.5px;line-height:1.65;white-space:pre-line;margin:0;">{{ $case->summary }}</p>
            <p class="dsp-hint" style="margin-top:10px;">
                Filed by {{ $case->filed_by === auth()->id() ? 'you' : ($other?->name ?? 'the other party') }}
                on {{ $case->created_at?->format('M j, Y') }}.
            </p>
        </div>

        {{-- ── Evidence ──────────────────────────────────────── --}}
        <div class="dsp-card">
            <p class="dsp-sec">Evidence</p>

            @forelse($evidence as $item)
                <div class="dsp-ev {{ $item->isWithdrawn() ? 'dsp-strike' : '' }}">
                    <div style="display:flex;justify-content:space-between;gap:10px;">
                        <strong style="font-size:12.5px;">{{ $item->kindLabel() }}</strong>
                        <span class="dsp-when">
                            {{ $mine($item) ? 'You' : ($item->submitter?->name ?? 'Unknown') }}
                            · {{ $item->created_at?->format('M j, Y') }}
                        </span>
                    </div>
                    <p style="margin:5px 0 0;white-space:pre-line;">{{ $item->description }}</p>

                    @if($item->isWithdrawn())
                        <p class="dsp-hint">Withdrawn — {{ $item->withdrawn_reason }}</p>
                    @elseif($item->isSuperseded())
                        <p class="dsp-hint">Replaced by a later submission. Kept for the record.</p>
                    @endif
                </div>
            @empty
                <p class="dsp-hint" style="margin:0;">Nothing submitted yet.</p>
            @endforelse

            @if($case->isOpen())
                <form method="POST" action="{{ route('disputes.evidence', $case) }}" style="margin-top:16px;">
                    @csrf
                    <div class="dsp-field">
                        <label class="dsp-label" for="kind">What kind of evidence</label>
                        <select name="kind" id="kind" class="dsp-select" required>
                            {{-- The weight guide is an aid for the reviewer
                                 (§4), not a label for the submitter. Printing
                                 "Low unless corroborated" beside someone's
                                 account of their own wedding is not what it
                                 is for. --}}
                            @foreach(DecisionGuide::EVIDENCE_WEIGHT as $key => $entry)
                                <option value="{{ $key }}">{{ $entry['label'] }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="dsp-field">
                        <label class="dsp-label" for="description">What it shows</label>
                        <textarea name="description" id="description" class="dsp-area" required
                                  style="min-height:80px;"></textarea>
                    </div>

                    @php $replaceable = $evidence->filter(fn ($e) => $mine($e) && ! $e->isWithdrawn()); @endphp
                    @if($replaceable->isNotEmpty())
                        <div class="dsp-field">
                            <label class="dsp-label" for="supersedes">Does this replace something you sent earlier?</label>
                            <select name="supersedes" id="supersedes" class="dsp-select">
                                <option value="">No, it is new</option>
                                @foreach($replaceable as $item)
                                    <option value="{{ $item->id }}">{{ Str::limit($item->description, 60) }}</option>
                                @endforeach
                            </select>
                            <p class="dsp-hint">
                                The original stays on the case. Nothing submitted here is ever
                                edited or deleted — that is what makes it evidence.
                            </p>
                        </div>
                    @endif

                    <label class="dsp-cert">
                        <input type="checkbox" name="certify_unaltered" value="1" required>
                        <span>I certify that this is the original and that I have not edited or altered it.</span>
                    </label>

                    <div style="margin-top:12px;">
                        <button type="submit" class="cl-btn cl-btn-primary">Add evidence</button>
                    </div>
                </form>
            @endif
        </div>

        {{-- ── Respond ───────────────────────────────────────── --}}
        @if($case->isOpen() && ! $isFiler)
            <div class="dsp-card">
                <p class="dsp-sec">Your response</p>
                <form method="POST" action="{{ route('disputes.respond', $case) }}">
                    @csrf
                    <div class="dsp-field">
                        <label class="dsp-label" for="position">Your account of what happened</label>
                        <textarea name="position" id="position" class="dsp-area" required></textarea>
                    </div>
                    <label class="dsp-cert">
                        <input type="checkbox" name="certify_truthful" value="1" required>
                        <span>
                            I certify that the information I have provided is true and accurate to
                            the best of my knowledge.
                        </span>
                    </label>
                    <div style="margin-top:12px;">
                        <button type="submit" class="cl-btn cl-btn-primary">Send response</button>
                    </div>
                </form>
            </div>
        @endif

        {{-- ── History ───────────────────────────────────────── --}}
        <div class="dsp-card">
            <p class="dsp-sec">History</p>
            <div class="dsp-time">
                @forelse($timeline as $entry)
                    <div class="dsp-ev">
                        <div class="dsp-when">{{ $entry->created_at?->format('M j, Y · g:ia') }}</div>
                        <div>
                            {{ ucfirst(str_replace('_', ' ', $entry->action)) }}
                            @if($entry->old_value && $entry->new_value)
                                — {{ $entry->old_value }} → {{ $entry->new_value }}
                            @endif
                        </div>
                        @if($entry->reason)
                            <p class="dsp-hint" style="margin:3px 0 0;">{{ $entry->reason }}</p>
                        @endif
                    </div>
                @empty
                    <p class="dsp-hint" style="margin:0;">Nothing recorded yet.</p>
                @endforelse
            </div>
        </div>
    </div>

    {{-- ── Sidebar ───────────────────────────────────────────── --}}
    <div>
        <div class="dsp-card">
            <p class="dsp-sec">This case</p>
            <dl style="margin:0;">
                <div class="dsp-row"><dt>Case number</dt><dd class="dsp-ref">{{ $case->reference }}</dd></div>
                <div class="dsp-row"><dt>Status</dt><dd>{{ $case->stateLabel() }}</dd></div>
                <div class="dsp-row"><dt>Subject</dt><dd>{{ $case->taxonomyLabel() }}</dd></div>
                <div class="dsp-row"><dt>Booking</dt><dd>{{ $case->booking?->event?->title ?? '#' . $case->booking_id }}</dd></div>
                <div class="dsp-row">
                    <dt>Balance for this booking</dt>
                    <dd>{{ $case->balance_paused ? 'Paused' : 'Not paused' }}</dd>
                </div>
            </dl>
            @if($case->balance_paused)
                <p class="dsp-hint" style="margin-top:10px;">
                    Only this booking is paused. Other professionals on the same event, and money
                    already paid out, are not affected.
                </p>
            @endif
        </div>

        {{-- Move to review — §2 Step 2, at either party's request. --}}
        @if(in_array($case->state, [DisputeStates::DIRECT_RESOLUTION, DisputeStates::AWAITING_RESPONSE], true))
            <div class="dsp-card">
                <p class="dsp-sec">Not getting anywhere?</p>
                <p style="font-size:13px;line-height:1.6;color:var(--text-muted);margin:0 0 12px;">
                    Either of you can ask for a platform review. Our team looks at the contract,
                    the messages and everything submitted, and issues a written decision.
                </p>
                <form method="POST" action="{{ route('disputes.review', $case) }}">
                    @csrf
                    <input type="hidden" name="reason" value="Requested a platform review.">
                    <button type="submit" class="cl-btn">Ask for a platform review</button>
                </form>
            </div>
        @endif

        {{-- §2 Step 4 — the single post-decision step. --}}
        @if($case->state === DisputeStates::DECIDED)
            <div class="dsp-card">
                <p class="dsp-sec">If you disagree with the decision</p>
                <p style="font-size:13px;line-height:1.6;color:var(--text-muted);margin:0 0 12px;">
                    GigResource has finished its review and will not review this decision again.
                    Where the Terms of Service provide for it, you can ask for the matter to be
                    taken outside GigResource.
                </p>
                <form method="POST" action="{{ route('disputes.escalate', $case) }}">
                    @csrf
                    <div class="dsp-field">
                        <label class="dsp-label" for="grounds">Why you are escalating</label>
                        <textarea name="grounds" id="grounds" class="dsp-area" required style="min-height:80px;"></textarea>
                    </div>
                    <label class="dsp-cert">
                        <input type="checkbox" name="acknowledge_no_internal_appeal" value="1" required>
                        <span>I understand GigResource will not review this decision again internally.</span>
                    </label>
                    <div style="margin-top:12px;">
                        <button type="submit" class="cl-btn">Request outside escalation</button>
                    </div>
                </form>
            </div>
        @endif

        {{-- Only the party who filed may withdraw. The other side agreeing to
             drop it is a settlement, and a settlement has two signatures. --}}
        @if($case->isOpen() && $isFiler)
            <div class="dsp-card">
                <p class="dsp-sec">Withdraw this case</p>
                <form method="POST" action="{{ route('disputes.withdraw', $case) }}">
                    @csrf
                    <div class="dsp-field">
                        <label class="dsp-label" for="reason">Why you are withdrawing</label>
                        <textarea name="reason" id="reason" class="dsp-area" required style="min-height:70px;"></textarea>
                    </div>
                    <label class="dsp-cert">
                        <input type="checkbox" name="acknowledge_final" value="1" required>
                        <span>
                            I understand this closes the case and the balance will be released
                            according to the original agreement.
                        </span>
                    </label>
                    <div style="margin-top:12px;">
                        <button type="submit" class="cl-btn">Withdraw</button>
                    </div>
                </form>
            </div>
        @endif
    </div>
</div>
@endsection
