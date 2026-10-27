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
    .pa-btn.go { background: var(--accent-blue, #2563eb); border-color: var(--accent-blue, #2563eb); color: #fff; }
    .pa-btn.off { color: #b91c1c; }
    .pa-acts-note { font-size: 12.5px; color: var(--text-muted); }

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
            <div style="text-align:right;">
                <span class="pa-state" style="background:{{ $state[1] }}1f;color:{{ $state[1] }};">{{ $state[0] }}</span>
                <div class="pa-state-note">{{ $state[2] }}</div>
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
                        <span class="pa-acts-note">You approved version {{ $version }}. It waits on {{ $f->client?->name ?? 'the client' }}.</span>
                    @endif
                </div>

                <div class="pa-soon" style="margin-top:14px;">
                    <b>Signing lands later this week.</b>
                    Once you both approve the same version the pen opens here. To ask for a different
                    price or date, message {{ $f->client?->name ?? 'the client' }} for now.
                </div>
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
