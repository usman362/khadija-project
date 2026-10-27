@extends('layouts.professional')

@section('title', 'Agreements')
@section('page-title', 'Agreements')

@push('styles')
<style>
    .pa-head { display: flex; align-items: baseline; justify-content: space-between; gap: 14px; flex-wrap: wrap; margin-bottom: 16px; }
    .pa-head p { margin: 4px 0 0; font-size: 13px; color: var(--text-muted); }
    .pa-waiting { font-size: 12.5px; font-weight: 700; color: var(--accent-blue); }

    .pa-list { display: flex; flex-direction: column; gap: 12px; }
    .pa-row {
        display: flex; align-items: center; gap: 16px;
        padding: 16px 20px; border-radius: var(--radius);
        background: var(--bg-card); border: 1px solid var(--border-color);
        text-decoration: none; color: inherit; transition: var(--transition);
    }
    .pa-row:hover { border-color: var(--border-glow); background: var(--bg-card-hover); }
    .pa-row-main { flex: 1; min-width: 0; }
    .pa-row-title { font-size: 15px; font-weight: 600; color: var(--text-primary); }
    .pa-row-meta { margin-top: 4px; display: flex; gap: 16px; flex-wrap: wrap; font-size: 12.5px; color: var(--text-muted); }
    .pa-row-side { text-align: right; flex-shrink: 0; }
    .pa-price { font-size: 15px; font-weight: 700; color: var(--text-primary); }

    .pa-state { display: inline-block; padding: 3px 10px; border-radius: 999px; font-size: 11px; font-weight: 800; letter-spacing: .2px; }
    .pa-your-move { display: inline-block; margin-top: 6px; padding: 3px 10px; border-radius: 999px; font-size: 11px; font-weight: 800;
        background: var(--accent-blue-soft, rgba(37,99,235,.12)); color: var(--accent-blue, #2563eb); }

    .pa-empty { padding: 48px 20px; text-align: center; background: var(--bg-card); border: 1px solid var(--border-color); border-radius: var(--radius); }
    .pa-empty b { display: block; font-size: 14px; color: var(--text-primary); }
    .pa-empty span { display: block; margin-top: 6px; font-size: 13px; color: var(--text-muted); }
</style>
@endpush

@section('content')
    <div class="pa-head">
        <div>
            <p>The agreements you are named in: what was agreed, where each one stands, and which are waiting on you.</p>
        </div>
        @if($waiting > 0)
            <span class="pa-waiting">{{ $waiting }} waiting on you</span>
        @endif
    </div>

    @if($agreements->isEmpty())
        <div class="pa-empty">
            <b>No agreements yet</b>
            <span>When a client takes one of your bids forward, the agreement appears here while it is being settled.</span>
        </div>
    @else
        <div class="pa-list">
            @foreach($agreements as $f)
                @php
                    $state = \App\Domain\Agreements\Workspace::STATES[$f->workspaceStatus()];
                    // The row's own badge and the count in the header read the
                    // same question, so they cannot disagree.
                    $yourMove = \App\Domain\Agreements\Workspace::waitingOn($f, auth()->user());
                @endphp
                <a href="{{ route('professional.agreements.show', $f) }}" class="pa-row">
                    <div class="pa-row-main">
                        <div class="pa-row-title">{{ $f->event?->title ?? 'Untitled event' }}</div>
                        <div class="pa-row-meta">
                            <span>{{ $f->client?->name ?? 'Client removed' }}</span>
                            @if($f->category?->name)<span>{{ $f->category->name }}</span>@endif
                            @if($f->event?->starts_at)<span>{{ $f->event->starts_at->format('M j, Y') }}</span>@endif
                        </div>
                    </div>
                    <div class="pa-row-side">
                        @if($f->agreed_price !== null)
                            <div class="pa-price">${{ number_format((float) $f->agreed_price, 0) }}</div>
                        @endif
                        <span class="pa-state" style="background:{{ $state[1] }}1f;color:{{ $state[1] }};">{{ $state[0] }}</span>
                        @if($yourMove)<br><span class="pa-your-move">Your move</span>@endif
                    </div>
                </a>
            @endforeach
        </div>

        @if($agreements->hasPages())
            <div style="margin-top:16px;">{{ $agreements->links() }}</div>
        @endif
    @endif
@endsection
