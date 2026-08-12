@extends($layout)

@section('title', 'Forms')

@push('styles')
    @include('disputes._styles')
    <style>
        .fm-grid { display:grid; grid-template-columns:repeat(auto-fill, minmax(240px,1fr)); gap:12px; }
        .fm-tile { display:block; border:1px solid var(--border-color); border-radius:13px; padding:15px 16px;
                   background:var(--bg-card); text-decoration:none; color:inherit; }
        .fm-tile:hover { border-color:var(--accent-orange,#f97316); }
        .fm-tile b { display:block; font-size:14px; font-weight:800; margin-bottom:4px; }
        .fm-tile span { font-size:12.5px; color:var(--text-muted); line-height:1.5; }
    </style>
@endpush

@section('content')
<div class="dsp-head">
    <div>
        <h1 class="dsp-h1">Forms</h1>
        <p class="dsp-sub">Everything you can send us, and everything you have already sent.</p>
    </div>
</div>

@if(session('status'))
    <div class="dsp-flash">{{ session('status') }}</div>
@endif

{{-- Waiting on you: a change order somebody sent that has not been answered.
     At the top, because it is the only thing here that is holding someone
     else up. --}}
@if($waiting->isNotEmpty())
    <div class="dsp-card" style="border-color:#fcd34d;background:#fffbeb;">
        <p class="dsp-sec" style="color:#92400e;">Waiting for your answer</p>
        @foreach($waiting as $item)
            <div class="dsp-row">
                <dt><a href="{{ route('forms.show', $item) }}" class="dsp-ref">{{ $item->reference }}</a> — {{ $item->title() }}</dt>
                <dd>from {{ $item->submitter?->name ?? 'the other party' }}</dd>
            </div>
        @endforeach
    </div>
@endif

<div class="dsp-card">
    <p class="dsp-sec">Send something</p>
    <div class="fm-grid">
        @foreach($forms as $key => $form)
            <a href="{{ route('forms.create', $key) }}" class="fm-tile">
                <b>{{ $form['title'] }}</b>
                <span>{{ $form['purpose'] }}</span>
            </a>
        @endforeach
    </div>
</div>

<div class="dsp-card">
    <p class="dsp-sec">What you have sent</p>
    @if($mine->isEmpty())
        <p class="dsp-hint" style="margin:0;">Nothing yet.</p>
    @else
        <table class="dsp-table">
            <thead><tr><th>Reference</th><th>Form</th><th>Status</th><th>Sent</th></tr></thead>
            <tbody>
                @foreach($mine as $item)
                    <tr>
                        <td><a href="{{ route('forms.show', $item) }}" class="dsp-ref">{{ $item->reference }}</a></td>
                        <td>{{ $item->title() }}</td>
                        <td>
                            <span class="dsp-badge {{ $item->status === 'withdrawn' ? 'dsp-shut' : ($item->isAccepted() ? 'dsp-done' : 'dsp-open') }}">
                                {{ $item->needsApproval() ? ucfirst($item->approval_status ?? 'pending') : ucfirst($item->status) }}
                            </span>
                        </td>
                        <td class="dsp-when">{{ $item->created_at?->format('M j, Y') }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif
</div>
@endsection
