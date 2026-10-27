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
</style>
@endpush

@section('content')
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

        {{-- Honest about what this screen cannot do yet, rather than showing
             buttons that are not wired. Approving and declining land Tuesday,
             asking for a change Wednesday, signing Thursday. --}}
        <div class="pa-soon">
            <b>Reading only for now.</b>
            Approving, declining and signing from your side are being built this week.
            Until then, talk to {{ $f->client?->name ?? 'the client' }} in Messages about anything you want changed.
        </div>
    </div>

    @if($f->contract_body)
        <div class="pa-card">
            <div class="pa-card-h">The agreement</div>
            <div class="pa-body">{{ $f->contract_body }}</div>
        </div>
    @endif
@endsection
