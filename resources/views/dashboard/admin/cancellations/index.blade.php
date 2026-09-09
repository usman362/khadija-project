@extends('layouts.dashboard')

@section('title', 'Cancellations')

@section('content')
<div class="ac">
    <div class="ac-head">
        <div>
            <h1>Cancellations</h1>
            <p>Requests waiting on a decision. Approving an event cancellation takes the event down; nothing changes before that.</p>
        </div>
    </div>

    {{-- Counted per status, so the tab says how much is actually waiting
         rather than just naming the status. --}}
    <div class="ac-tabs">
        @foreach(\App\Models\CancellationRequest::STATUS_LABELS as $key => $label)
            <a class="ac-tab {{ $status === $key ? 'is-on' : '' }}"
               href="{{ route('app.admin.cancellations.index', ['status' => $key]) }}">
                {{ $label }} <b>{{ $counts[$key] ?? 0 }}</b>
            </a>
        @endforeach
    </div>

    @if(session('status'))<div class="ac-flash">{{ session('status') }}</div>@endif

    @forelse($requests as $item)
        <div class="ac-card">
            <div class="ac-card-head">
                <div>
                    <b>{{ $item->reference }}</b>
                    <span class="ac-kind">{{ $item->kindLabel() }}</span>
                </div>
                <span class="ac-when">{{ $item->created_at->format('M j, Y') }}</span>
            </div>

            <dl class="ac-facts">
                <div><dt>{{ $item->isEventCancellation() ? 'Event' : 'Booking' }}</dt>
                    <dd>{{ $item->event?->title ?? ('Booking #' . $item->booking_id) }}</dd></div>
                <div><dt>Raised by</dt><dd>{{ $item->raiser?->name ?? 'Unknown' }} ({{ $item->raised_role }})</dd></div>
                @if($item->event?->starts_at)
                    <div><dt>Event date</dt><dd>{{ $item->event->starts_at->format('M j, Y') }}</dd></div>
                @endif
                @if($item->hasQuote())
                    <div><dt>Refund quoted</dt><dd>${{ number_format((float) $item->quoted_refund, 2) }}</dd></div>
                @endif
            </dl>

            <p class="ac-reason">{{ $item->reason }}</p>
            @if($item->detail)<p class="ac-detail">{{ $item->detail }}</p>@endif

            @if($item->isPending())
                <div class="ac-actions">
                    <form method="POST" action="{{ route('app.admin.cancellations.approve', $item) }}">
                        @csrf
                        <input type="text" name="resolution_note" placeholder="Note for the client (optional)" maxlength="2000">
                        <button type="submit" class="ac-btn ac-btn-ok">Approve</button>
                    </form>
                    <form method="POST" action="{{ route('app.admin.cancellations.decline', $item) }}">
                        @csrf
                        <input type="text" name="resolution_note" placeholder="Why it is declined — the client sees this" maxlength="2000" required>
                        <button type="submit" class="ac-btn ac-btn-no">Decline</button>
                    </form>
                </div>
            @else
                <div class="ac-outcome">
                    <b>{{ $item->statusLabel() }}</b>
                    @if($item->actioned_at)<span>{{ $item->actioned_at->format('M j, Y') }}</span>@endif
                    @if($item->resolution_note)<p>{{ $item->resolution_note }}</p>@endif
                </div>
            @endif
        </div>
    @empty
        <div class="ac-empty">Nothing {{ \Illuminate\Support\Str::lower(\App\Models\CancellationRequest::STATUS_LABELS[$status]) }}.</div>
    @endforelse

    <div class="ac-pager">{{ $requests->links() }}</div>
</div>

<style>
    .ac { max-width: 980px; }
    .ac-head h1 { font-size: 22px; font-weight: 800; margin: 0 0 4px; }
    .ac-head p { font-size: 13px; color: var(--text-muted); margin: 0 0 18px; }
    .ac-tabs { display: flex; gap: 6px; flex-wrap: wrap; margin-bottom: 16px; }
    .ac-tab { border: 1px solid var(--border-color); border-radius: 999px; padding: 7px 14px; font-size: 12.5px; font-weight: 700; color: var(--text-secondary); text-decoration: none; }
    .ac-tab b { color: var(--text-muted); font-weight: 800; margin-left: 4px; }
    .ac-tab.is-on { background: rgba(249,115,22,.10); border-color: rgba(249,115,22,.35); color: var(--brand-text); }
    .ac-flash { background: rgba(16,185,129,.10); border: 1px solid rgba(16,185,129,.3); color: var(--ok-text); border-radius: 10px; padding: 10px 14px; font-size: 13px; margin-bottom: 14px; }
    .ac-card { background: var(--bg-card); border: 1px solid var(--border-color); border-radius: 14px; padding: 16px 18px; margin-bottom: 12px; }
    .ac-card-head { display: flex; justify-content: space-between; align-items: baseline; gap: 12px; }
    .ac-card-head b { font-family: ui-monospace, monospace; font-size: 13px; }
    .ac-kind { font-size: 12.5px; color: var(--text-muted); margin-left: 10px; }
    .ac-when { font-size: 12px; color: var(--text-muted); }
    .ac-facts { display: grid; grid-template-columns: repeat(auto-fit, minmax(180px,1fr)); gap: 10px 18px; margin: 12px 0; }
    .ac-facts dt { font-size: 11px; color: var(--text-muted); font-weight: 700; }
    .ac-facts dd { margin: 2px 0 0; font-size: 13.5px; color: var(--text-primary); font-weight: 600; }
    .ac-reason { font-size: 13.5px; color: var(--text-primary); line-height: 1.6; margin: 0; }
    .ac-detail { font-size: 13px; color: var(--text-muted); line-height: 1.6; margin: 6px 0 0; }
    .ac-actions { display: flex; gap: 10px; flex-wrap: wrap; margin-top: 14px; padding-top: 14px; border-top: 1px solid var(--border-color); }
    .ac-actions form { display: flex; gap: 8px; flex: 1 1 320px; }
    .ac-actions input { flex: 1; min-width: 0; border: 1px solid var(--border-color); border-radius: 9px; padding: 8px 11px; font: inherit; font-size: 13px; background: var(--bg-page); color: var(--text-primary); }
    .ac-btn { border: 0; border-radius: 9px; padding: 8px 16px; font-size: 13px; font-weight: 800; cursor: pointer; color: #fff; }
    .ac-btn-ok { background: #059669; }
    .ac-btn-no { background: #dc2626; }
    .ac-outcome { margin-top: 12px; padding-top: 12px; border-top: 1px solid var(--border-color); font-size: 13px; }
    .ac-outcome span { color: var(--text-muted); margin-left: 8px; font-size: 12px; }
    .ac-outcome p { margin: 6px 0 0; color: var(--text-secondary); line-height: 1.6; }
    .ac-empty { background: var(--bg-card); border: 1px dashed var(--border-color); border-radius: 14px; padding: 28px; text-align: center; font-size: 13.5px; color: var(--text-muted); }
    .ac-pager { margin-top: 14px; }
</style>
@endsection
