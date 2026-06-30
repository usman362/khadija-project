@extends($aiLayout ?? 'layouts.client')

@section('title', 'AI Contract Assistant')
@section('page-title', 'AI Contract Assistant')
@section('page-subtitle', 'A plain-English breakdown before you sign')

@push('styles')
<style>
    .ca { --ca: #7c3aed; }
    .ca-stats { display: grid; grid-template-columns: repeat(4, 1fr); gap: 12px; margin-bottom: 18px; }
    .ca-stat { background: var(--bg-card); border: 1px solid var(--border-color); border-radius: 14px; padding: 14px 16px; }
    .ca-stat b { display: block; font-size: 22px; font-weight: 800; color: var(--text-primary); line-height: 1; } .ca-stat.good b { color: #16a34a; } .ca-stat.warn b { color: #d97706; } .ca-stat .l { font-size: 11.5px; color: var(--text-muted); margin-top: 6px; }
    .ca-grid { display: grid; grid-template-columns: minmax(0,1fr) minmax(0,1fr); gap: 18px; align-items: start; }
    .ca-card { background: var(--bg-card); border: 1px solid var(--border-color); border-radius: 16px; padding: 16px; }
    .ca-card h3 { font-size: 14.5px; font-weight: 800; color: var(--text-primary); margin-bottom: 12px; }
    .ca-drop { border: 2px dashed var(--border-color); border-radius: 12px; padding: 22px; text-align: center; color: var(--text-muted); font-size: 12.5px; margin-bottom: 12px; }
    .ca-doc { display: flex; align-items: center; gap: 10px; border: 1px solid var(--border-color); border-radius: 10px; padding: 11px 13px; font-size: 13px; font-weight: 700; color: var(--text-primary); }
    .ca-toggle { display: flex; gap: 8px; margin: 14px 0; } .ca-tg { flex: 1; text-align: center; font-size: 12px; font-weight: 700; border: 1px solid var(--border-color); border-radius: 9px; padding: 9px; cursor: pointer; color: var(--text-secondary); } .ca-tg.on { background: var(--ca); border-color: var(--ca); color: #fff; }
    .ca-btn { width: 100%; border: none; border-radius: 11px; padding: 12px; font-size: 13.5px; font-weight: 800; color: #fff; background: linear-gradient(135deg, var(--ca), #6d28d9); cursor: pointer; }
    .ca-pt { display: flex; gap: 10px; padding: 11px 0; border-bottom: 1px dashed var(--border-color); } .ca-pt:last-of-type { border-bottom: none; }
    .ca-dot { width: 9px; height: 9px; border-radius: 50%; margin-top: 5px; flex-shrink: 0; } .ca-dot.good { background: #16a34a; } .ca-dot.warn { background: #d97706; }
    .ca-pt h6 { font-size: 13px; font-weight: 800; color: var(--text-primary); } .ca-pt p { font-size: 12px; color: var(--text-muted); margin-top: 2px; line-height: 1.5; }
    .ca-disc { font-size: 11px; color: var(--text-muted); line-height: 1.5; margin-top: 12px; padding: 10px; border: 1px dashed var(--border-color); border-radius: 9px; }
    @media (max-width: 1000px) { .ca-grid { grid-template-columns: minmax(0,1fr); } .ca-stats { grid-template-columns: 1fr 1fr; } }
</style>
@endpush

@section('content')
<div class="ca">
    <div class="ca-stats">@foreach($stats as [$lbl, $val, $tone])<div class="ca-stat {{ $tone }}"><b>{{ $val }}</b><div class="l">{{ $lbl }}</div></div>@endforeach</div>
    <div class="ca-grid">
        <div class="ca-card">
            <h3>📄 Your Contract</h3>
            <div class="ca-drop">⬆ Drag in an agreement, or pick an existing GigResource one</div>
            <div class="ca-doc">📑 {{ $document }}</div>
            <div class="ca-toggle"><span class="ca-tg on">I'm reviewing as a Client</span><span class="ca-tg">as a Professional</span></div>
            <button class="ca-btn">🔍 Explain This Contract</button>
        </div>
        <div class="ca-card">
            <h3>📝 Plain-English Summary</h3>
            @foreach($summary as [$title, $tone, $text])
                <div class="ca-pt"><span class="ca-dot {{ $tone }}"></span><div><h6>{{ $title }}</h6><p>{{ $text }}</p></div></div>
            @endforeach
            <div class="ca-disc">⚠️ This is a plain-language summary to help you understand the contract — it isn’t legal advice. For important agreements, consider a professional review.</div>
        </div>
    </div>
</div>
@endsection
