@extends('layouts.professional')

@section('title', 'Agreement')
@section('page-title', 'Agreement')

@push('styles')
<style>
    .pa-card { background: var(--bg-card); border: 1px solid var(--border-color); border-radius: var(--radius); padding: 22px; margin-bottom: 16px; }
    .pa-card-h { font-size: 12px; font-weight: 800; letter-spacing: .4px; text-transform: uppercase; color: var(--text-muted); margin-bottom: 14px; }

    .pa-top { display: flex; align-items: flex-start; justify-content: space-between; gap: 16px; flex-wrap: wrap; }
    .pa-event { font-size: 18px; font-weight: 700; color: var(--text-primary); }
    .pa-sub { margin-top: 4px; font-size: 13px; color: var(--text-muted); }
    .pa-state { display: inline-block; padding: 4px 12px; border-radius: 999px; font-size: 11.5px; font-weight: 800; }
    .pa-state-note { margin-top: 6px; font-size: 12px; color: var(--text-muted); max-width: 300px; }

    .pa-terms { display: grid; grid-template-columns: repeat(auto-fit, minmax(190px, 1fr)); gap: 16px 22px; }
    .pa-term dt { font-size: 11.5px; font-weight: 700; letter-spacing: .2px; text-transform: uppercase; color: var(--text-muted); }
    .pa-term dd { margin: 4px 0 0; font-size: 14px; font-weight: 600; color: var(--text-primary); }
    .pa-term dd.quiet { font-weight: 500; color: var(--text-muted); }

    .pa-sig { display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 16px; }
    .pa-sig-box { padding: 14px 16px; border-radius: var(--radius-sm); border: 1px solid var(--border-color); }
    .pa-sig-who { font-size: 12px; font-weight: 700; color: var(--text-secondary); }
    .pa-sig-state { margin-top: 6px; font-size: 13px; font-weight: 600; color: var(--text-primary); }
    .pa-sig-when { margin-top: 3px; font-size: 11.5px; color: var(--text-muted); }

    .pa-body { font-size: 13.5px; line-height: 1.7; color: var(--text-secondary); white-space: pre-wrap; }

    .pa-soon { margin-top: 4px; padding: 14px 16px; border-radius: var(--radius-sm);
        background: var(--bg-card-hover, rgba(255,255,255,.03)); border: 1px dashed var(--border-color);
        font-size: 13px; color: var(--text-muted); }
    .pa-soon b { color: var(--text-primary); }

    /* What moved, old value beside new. */
    .pa-moves { list-style: none; margin: 0; padding: 0; display: flex; flex-direction: column; gap: 10px; }
    .pa-moves li { font-size: 13px; line-height: 1.6; }
    .pa-moves b { display: block; font-size: 12px; font-weight: 700; letter-spacing: .2px;
        text-transform: uppercase; color: var(--text-muted); }
    .pa-was { color: var(--text-muted); text-decoration: line-through; }
    .pa-now { color: var(--text-primary); font-weight: 600; }

    .pa-rev { padding: 12px 0; border-top: 1px solid var(--border-color); }
    .pa-rev:first-of-type { border-top: 0; padding-top: 0; }
    .pa-rev-h { display: flex; justify-content: space-between; gap: 12px; flex-wrap: wrap;
        font-size: 13px; color: var(--text-secondary); margin-bottom: 8px; }
    .pa-rev-h b { color: var(--text-primary); }
    .pa-rev-h span { font-size: 12px; color: var(--text-muted); }
    .pa-rev-why { margin: 8px 0 0; font-size: 12.5px; color: var(--text-muted); font-style: italic; }

    .pa-acts { display: flex; gap: 10px; flex-wrap: wrap; align-items: center; margin-top: 4px; }
    .pa-btn { appearance: none; border: 1px solid var(--border-color); background: var(--bg-card);
        color: var(--text-primary); font: inherit; font-size: 13px; font-weight: 700;
        padding: 10px 18px; border-radius: var(--radius-sm); cursor: pointer; }
    .pa-btn:hover { border-color: var(--border-glow); }
    /* The same class on a link: it has to carry the button's own look back. */
    a.pa-btn { display: inline-flex; align-items: center; gap: 7px; text-decoration: none;
        padding: 8px 14px; font-size: 12.5px; }
    .pa-btn.go { background: var(--accent-blue, #2563eb); border-color: var(--accent-blue, #2563eb); color: #fff; }
    .pa-btn.off { color: #b91c1c; }
    .pa-acts-note { font-size: 12.5px; color: var(--text-muted); }

    .pa-sign-f { display: block; margin-top: 10px; }
    .pa-sign-f span { display: block; font-size: 12px; font-weight: 700; color: var(--text-secondary); margin-bottom: 5px; }
    .pa-sign-f input { width: 100%; max-width: 360px; }
    .pa-sign-ok { display: flex; gap: 9px; align-items: flex-start; margin-top: 12px; cursor: pointer;
        font-size: 12.5px; line-height: 1.6; color: var(--text-secondary); }
    .pa-sign-ok input { margin-top: 3px; flex-shrink: 0; }
    .pa-err { margin: 6px 0 0; font-size: 12px; color: #dc2626; }

    .pa-edit { margin-top: 18px; border-top: 1px dashed var(--border-color); padding-top: 14px; }
    .pa-edit > summary { cursor: pointer; list-style: none; font-size: 13px; font-weight: 700; color: var(--text-secondary); }
    .pa-edit > summary::-webkit-details-marker { display: none; }
    .pa-edit > summary::before { content: '+ '; font-weight: 800; }
    .pa-edit[open] > summary::before { content: '\2212 '; }
    .pa-edit-note { margin: 12px 0 14px; font-size: 12.5px; line-height: 1.6; color: var(--text-muted); }
    .pa-edit-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(190px, 1fr)); gap: 12px 16px; }
    .pa-edit label, .pa-edit-wide { display: block; margin-bottom: 12px; }
    .pa-edit label span { display: block; font-size: 11.5px; font-weight: 700; letter-spacing: .2px;
        text-transform: uppercase; color: var(--text-muted); margin-bottom: 5px; }
    .pa-edit input, .pa-edit textarea { width: 100%; padding: 9px 11px; font: inherit; font-size: 13px;
        color: var(--text-primary); background: var(--bg-input, var(--bg-card-hover, rgba(255,255,255,.04)));
        border: 1px solid var(--border-color); border-radius: var(--radius-sm); }
    .pa-edit textarea { resize: vertical; }

</style>
@endpush

@section('content')
    {{-- No flash message here: layouts/professional already draws
         session('status'), and saying it twice is how one approval looked
         like two. --}}
    <div class="pa-card">
        <div class="pa-top">
            <div>
                <div class="pa-event">{{ $f->event?->title ?? 'Untitled event' }}</div>
                <div class="pa-sub">
                    With {{ $f->client?->name ?? 'a client' }}@if($f->category?->name) for {{ $f->category->name }}@endif.
                    Version {{ $version }} of these terms.
                </div>
            </div>
            <div style="text-align:right;display:flex;flex-direction:column;align-items:flex-end;gap:8px;">
                <span class="pa-state" style="background:{{ $state[1] }}1f;color:{{ $state[1] }};">{{ $state[0] }}</span>
                <div class="pa-state-note">{{ $state[2] }}</div>
                {{-- The terms as a document, to read away from the screen or
                     keep on file. It says "draft" on every page until both
                     signatures are on it. --}}
                <a class="pa-btn" href="{{ route('professional.agreements.draft', $f) }}" style="white-space:nowrap;">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:13px;height:13px;"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
                    Download a copy
                </a>
            </div>
        </div>
    </div>

    @if($since !== [])
        {{-- The narrow question, answered first and on its own. Somebody
             looking at an agreement that came back to them wants to know what
             is different from the version they said yes to, not everything
             that has ever changed. --}}
        <div class="pa-card" style="border-color: var(--accent-blue, #2563eb);">
            <div class="pa-card-h">Changed since you approved</div>
            <ul class="pa-moves">
                @foreach($since as $field => $move)
                    <li>
                        <b>{{ $move['label'] ?? $field }}</b>
                        <span class="pa-was">{{ \App\Domain\Agreements\EditAgreement::readable($field, $move['from'] ?? null) }}</span>
                        &rarr;
                        <span class="pa-now">{{ \App\Domain\Agreements\EditAgreement::readable($field, $move['to'] ?? null) }}</span>
                    </li>
                @endforeach
            </ul>
            <p class="pa-rev-why" style="font-style:normal;">
                Your approval was given to the earlier terms, so it no longer counts. Read these and approve again if they suit you.
            </p>
        </div>
    @endif

    @if($f->change_request)
        <div class="pa-card">
            <div class="pa-card-h">A change was asked for</div>
            <div class="pa-body">{{ $f->change_request }}</div>
            @if($f->change_requested_at)
                <div class="pa-sig-when" style="margin-top:8px;">
                    {{ $f->change_requested_by === $f->supplier_id ? 'You asked' : ($f->client?->name ?? 'The client') . ' asked' }}
                    on {{ $f->change_requested_at->format('M j, Y · g:i A') }}
                </div>
            @endif
        </div>
    @endif

    <div class="pa-card">
        <div class="pa-card-h">What was agreed</div>
        <dl class="pa-terms">
            <div class="pa-term">
                <dt>Price</dt>
                <dd class="{{ $f->agreed_price === null ? 'quiet' : '' }}">
                    {{ $f->agreed_price !== null ? '$' . number_format((float) $f->agreed_price, 2) : 'Not settled yet' }}
                </dd>
            </div>
            <div class="pa-term">
                <dt>Deposit</dt>
                <dd class="{{ $f->deposit_amount === null ? 'quiet' : '' }}">
                    {{ $f->deposit_amount !== null ? '$' . number_format((float) $f->deposit_amount, 2) : 'Not settled yet' }}
                    @if($f->deposit_percent) ({{ $f->deposit_percent }}%) @endif
                </dd>
            </div>
            <div class="pa-term">
                <dt>Balance due</dt>
                <dd class="{{ $f->balance_due_on === null ? 'quiet' : '' }}">
                    {{ $f->balance_due_on?->format('M j, Y') ?? 'Not settled yet' }}
                </dd>
            </div>
            <div class="pa-term">
                <dt>Service window</dt>
                <dd class="{{ $f->service_start === null ? 'quiet' : '' }}">
                    @if($f->service_start)
                        {{ $f->service_start->format('M j, Y · g:i A') }}
                        @if($f->service_end) to {{ $f->service_end->format('g:i A') }} @endif
                    @else
                        Not settled yet
                    @endif
                </dd>
            </div>
        </dl>

        @if($f->scope)
            <div style="margin-top:20px;">
                <div class="pa-card-h" style="margin-bottom:8px;">Scope</div>
                <div class="pa-body">{{ $f->scope }}</div>
            </div>
        @endif

        @if($f->payment_terms)
            <div style="margin-top:20px;">
                <div class="pa-card-h" style="margin-bottom:8px;">Payment terms</div>
                <div class="pa-body">{{ $f->payment_terms }}</div>
            </div>
        @endif

        {{-- Said on both sides, whichever way it is answered. An agreement
             silent about who turns up is where the argument starts. --}}
        <div style="margin-top:20px;">
            <div class="pa-card-h" style="margin-bottom:8px;">Who attends</div>
            <div class="pa-body">
                @if($f->staff_may_attend)
                    A member of your staff may attend instead of you, and {{ $f->client?->name ?? 'the client' }}
                    has agreed to that.
                @else
                    You are expected to attend. If you send somebody from your team instead,
                    {{ $f->client?->name ?? 'the client' }} is told.
                @endif
                You remain responsible for fulfilling this agreement either way.
            </div>
        </div>
    </div>

    <div class="pa-card">
        <div class="pa-card-h">Where the signatures stand</div>
        <div class="pa-sig">
            @php
                $ws = \App\Domain\Agreements\Workspace::class;
            @endphp
            <div class="pa-sig-box">
                <div class="pa-sig-who">{{ $f->client?->name ?? 'The client' }}</div>
                <div class="pa-sig-state">
                    @if($ws::clientSigned($f)) Signed
                    @elseif($ws::clientApproved($f)) Approved these terms
                    @else Has not approved this version
                    @endif
                </div>
                @if($f->client_signed_at && $ws::clientSigned($f))
                    <div class="pa-sig-when">{{ $f->client_signed_at->format('M j, Y · g:i A') }}</div>
                @endif
            </div>
            <div class="pa-sig-box" style="{{ $waiting ? 'border-color: var(--accent-blue, #2563eb);' : '' }}">
                <div class="pa-sig-who">You</div>
                <div class="pa-sig-state">
                    @if($ws::supplierSigned($f)) Signed
                    @elseif($ws::supplierApproved($f)) Approved these terms
                    @else You have not approved this version
                    @endif
                </div>
                @if($f->supplier_signed_at && $ws::supplierSigned($f))
                    <div class="pa-sig-when">{{ $f->supplier_signed_at->format('M j, Y · g:i A') }}</div>
                @endif
            </div>
        </div>

        {{-- The two actions, and only where they mean something.

             Approving is approval OF A VERSION, so the button says which.
             Declining ends the agreement, so it asks twice and says so: a
             professional who wants a date moved should not reach for it, and
             asking for a change is a separate action that lands Wednesday. --}}
        @php
            $ws         = \App\Domain\Agreements\Workspace::class;
            $over       = $f->declined_at || $f->funded_at;
            $canApprove = ! $over && ! $ws::supplierApproved($f);
        @endphp

        @if($over)
            <div class="pa-soon">
                @if($f->declined_at)
                    <b>This agreement was ended.</b>
                    {{ $f->declined_by === $f->supplier_id ? 'You ended it' : ($f->client?->name ?? 'The client') . ' ended it' }}
                    on {{ $f->declined_at->format('M j, Y') }}.
                @else
                    <b>This agreement is funded.</b> The terms are closed. Anything further goes through support.
                @endif
            </div>
        @else
            <div style="margin-top:18px;">
                <div class="pa-acts">
                    @if($canApprove)
                        <form method="POST" action="{{ route('professional.agreements.approve', $f) }}">
                            @csrf
                            <button type="submit" class="pa-btn go">Approve version {{ $version }}</button>
                        </form>
                    @endif

                    <form method="POST" action="{{ route('professional.agreements.decline', $f) }}"
                          onsubmit="return confirm('Ending this agreement cannot be undone, and the client will be told. If you only want something changed, close this and message {{ $f->client?->name ?? 'the client' }} instead. End it?');">
                        @csrf
                        <button type="submit" class="pa-btn off">End this agreement</button>
                    </form>

                    @if(! $canApprove)
                        {{-- "It waits on the client" was said here whenever the
                             professional had approved, which stopped being true
                             the moment the client approved as well: it then
                             waits on the signatures, and the pen is right
                             below. --}}
                        <span class="pa-acts-note">
                            You approved version {{ $version }}.
                            @if(! $ws::clientApproved($f))
                                It waits on {{ $f->client?->name ?? 'the client' }} to approve it too.
                            @endif
                        </span>
                    @endif
                </div>

                {{-- The pen, and only once there is something settled to sign.

                     "E-signature should unlock only after both parties approve
                     the same agreement version" (Sir Peter, 27 September).
                     Before that, holding out the pen says the terms are
                     settled when they are not. --}}
                @if($ws::supplierSigned($f))
                    <div class="pa-soon" style="margin-top:14px;">
                        <b>You signed on {{ $f->supplier_signed_at->format('M j, Y · g:i A') }}</b>
                        as {{ $f->supplier_signature }}.
                        @if($ws::clientSigned($f))
                            {{ $f->client?->name ?? 'The client' }} has signed too. The deposit is theirs to pay next.
                        @else
                            It waits on {{ $f->client?->name ?? 'the client' }} to sign.
                        @endif
                    </div>
                @elseif($ws::signingOpen($f))
                    <form method="POST" action="{{ route('professional.agreements.sign', $f) }}" style="margin-top:16px;">
                        @csrf
                        <div class="pa-card-h">Sign this agreement</div>
                        <label class="pa-sign-f">
                            <span>Type your full name to sign</span>
                            <input type="text" name="supplier_signature" value="{{ old('supplier_signature') }}"
                                   placeholder="{{ auth()->user()->name }}" maxlength="120">
                        </label>
                        @error('supplier_signature')<p class="pa-err">{{ $message }}</p>@enderror

                        <label class="pa-sign-ok">
                            <input type="checkbox" name="agree" value="1">
                            <span>I have read the terms above and accept them. Signing charges nothing: the client pays the deposit next.</span>
                        </label>
                        @error('agree')<p class="pa-err">{{ $message }}</p>@enderror

                        <button type="submit" class="pa-btn go" style="margin-top:12px;">Sign as version {{ $version }}</button>
                    </form>
                @else
                    <div class="pa-soon" style="margin-top:14px;">
                        <b>Not ready to sign.</b>
                        @if(! $ws::supplierApproved($f) && ! $ws::clientApproved($f))
                            Neither of you has approved these terms yet.
                        @elseif(! $ws::supplierApproved($f))
                            {{ $f->client?->name ?? 'The client' }} approved these terms. Approve them yourself to open signing.
                        @else
                            You approved these terms. It waits on {{ $f->client?->name ?? 'the client' }}.
                        @endif
                        Signing opens once you have both approved the same version.
                    </div>
                @endif

                {{-- Counter-proposing. The same edit the client makes, so a
                     professional answering a change request can say what they
                     want rather than asking the client to guess. --}}
                <details class="pa-edit">
                    <summary>Ask for different terms</summary>
                    <form method="POST" action="{{ route('professional.agreements.request-changes', $f) }}">
                        @csrf
                        <p class="pa-edit-note">
                            Sending these raises the version, so every approval and signature on the
                            current terms stops counting, including your own. {{ $f->client?->name ?? 'The client' }}
                            has to approve the new terms before anyone signs.
                        </p>

                        @php
                            $terms = \App\Domain\Agreements\EditAgreement::current($f);
                        @endphp

                        <div class="pa-edit-grid">
                            <label><span>Agreed price</span>
                                <input type="number" step="0.01" min="0" name="terms[agreed_price]"
                                       value="{{ old('terms.agreed_price', $terms['agreed_price']) }}"></label>
                            <label><span>Deposit percentage</span>
                                <input type="number" min="0" max="100" name="terms[deposit_percent]"
                                       value="{{ old('terms.deposit_percent', $terms['deposit_percent']) }}"></label>
                            <label><span>Service start</span>
                                <input type="datetime-local" name="terms[service_start]"
                                       value="{{ old('terms.service_start', $f->service_start?->format('Y-m-d\TH:i')) }}"></label>
                            <label><span>Service end</span>
                                <input type="datetime-local" name="terms[service_end]"
                                       value="{{ old('terms.service_end', $f->service_end?->format('Y-m-d\TH:i')) }}"></label>
                            <label><span>Balance due on</span>
                                <input type="date" name="terms[balance_due_on]"
                                       value="{{ old('terms.balance_due_on', $f->balance_due_on?->format('Y-m-d')) }}"></label>
                        </div>

                        <label class="pa-edit-wide"><span>Scope and deliverables</span>
                            <textarea name="terms[scope]" rows="3" maxlength="5000">{{ old('terms.scope', $terms['scope']) }}</textarea></label>
                        <label class="pa-edit-wide"><span>Schedule notes</span>
                            <textarea name="terms[schedule_notes]" rows="2" maxlength="2000">{{ old('terms.schedule_notes', $terms['schedule_notes']) }}</textarea></label>
                        <label class="pa-edit-wide"><span>Payment terms</span>
                            <textarea name="terms[payment_terms]" rows="2" maxlength="2000">{{ old('terms.payment_terms', $terms['payment_terms']) }}</textarea></label>

                        {{-- Sir Peter, 28 October: say up front that a staff
                             member may come, so a later swap is expected
                             rather than a surprise. The hidden 0 goes with
                             the box: an unticked checkbox sends nothing, and
                             "nothing" would read as "unchanged" rather than
                             as the no it means. --}}
                        <div class="pa-edit-wide">
                            <input type="hidden" name="terms[staff_may_attend]" value="0">
                            <label class="pa-sign-ok" style="margin-top:0;">
                                <input type="checkbox" name="terms[staff_may_attend]" value="1"
                                       {{ old('terms.staff_may_attend', $terms['staff_may_attend']) ? 'checked' : '' }}>
                                <span style="font-weight:600;">
                                    A member of my staff may attend instead of me.
                                    <span style="display:block;font-weight:400;color:var(--text-muted);margin-top:3px;">
                                        You stay responsible for the work either way. Agreeing this now means
                                        {{ $f->client?->name ?? 'the client' }} is not surprised if you send somebody
                                        on the day.
                                    </span>
                                </span>
                            </label>
                        </div>
                        <label class="pa-edit-wide"><span>What you are asking for, and why</span>
                            <textarea name="summary" rows="2" maxlength="2000" required
                                      placeholder="{{ $f->client?->name ?? 'The client' }} reads this.">{{ old('summary') }}</textarea></label>
                        @error('summary')<p class="pa-err">{{ $message }}</p>@enderror

                        <button type="submit" class="pa-btn" style="margin-top:12px;">Send these terms back</button>
                    </form>
                </details>
            </div>
        @endif
    </div>

    @if($history->isNotEmpty())
        <div class="pa-card">
            <div class="pa-card-h">Version history</div>
            @foreach($history as $rev)
                <div class="pa-rev">
                    <div class="pa-rev-h">
                        <b>Version {{ $rev->version }}</b>
                        <span>
                            {{ $rev->created_by === $f->supplier_id ? 'You' : ($rev->author?->name ?? 'Someone') }}
                            &middot; {{ $rev->created_at?->format('M j, Y · g:i A') }}
                        </span>
                    </div>
                    @if($rev->changed)
                        <ul class="pa-moves">
                            @foreach($rev->changed as $field => $move)
                                <li>
                                    <b>{{ $move['label'] ?? $field }}</b>
                                    <span class="pa-was">{{ \App\Domain\Agreements\EditAgreement::readable($field, $move['from'] ?? null) }}</span>
                                    &rarr;
                                    <span class="pa-now">{{ \App\Domain\Agreements\EditAgreement::readable($field, $move['to'] ?? null) }}</span>
                                </li>
                            @endforeach
                        </ul>
                    @endif
                    @if($rev->summary)
                        <p class="pa-rev-why">{{ $rev->summary }}</p>
                    @endif
                    @if($rev->supplier_signed_at || $rev->client_signed_at)
                        <p class="pa-rev-why" style="font-style:normal;">
                            Signed on this version:
                            {{ collect([
                                $rev->supplier_signed_at ? 'you' : null,
                                $rev->client_signed_at ? ($f->client?->name ?? 'the client') : null,
                            ])->filter()->implode(' and ') }}.
                        </p>
                    @endif
                </div>
            @endforeach
        </div>
    @endif

    @if($f->contract_body)
        <div class="pa-card">
            <div class="pa-card-h">The agreement</div>
            <div class="pa-body">{{ $f->contract_body }}</div>
        </div>
    @endif
@endsection
