@extends($aiLayout ?? 'layouts.client')

@section('title', 'AI Translator')
@section('page-title', 'AI Translator')
@section('page-subtitle', 'Talk to anyone in their language — tone kept intact')

@push('styles')
<style>
    .tr { --tr: #7c3aed; }
    .tr-stats { display: grid; grid-template-columns: repeat(4, 1fr); gap: 12px; margin-bottom: 18px; }
    .tr-stat { background: var(--bg-card); border: 1px solid var(--border-color); border-radius: 14px; padding: 14px 16px; }
    .tr-stat b { display: block; font-size: 22px; font-weight: 800; color: var(--text-primary); line-height: 1; } .tr-stat.good b { color: #16a34a; } .tr-stat .l { font-size: 11.5px; color: var(--text-muted); margin-top: 6px; }
    .tr-grid { display: grid; grid-template-columns: minmax(0,1fr) minmax(0,1fr); gap: 18px; align-items: start; }
    .tr-card { background: var(--bg-card); border: 1px solid var(--border-color); border-radius: 16px; padding: 16px; }
    .tr-card h3 { font-size: 14.5px; font-weight: 800; color: var(--text-primary); margin-bottom: 4px; } .tr-card .det { font-size: 11.5px; color: var(--text-muted); margin-bottom: 12px; }
    .tr-text { width: 100%; border: 1.5px solid var(--border-color); border-radius: 11px; padding: 13px; font-size: 13.5px; color: var(--text-primary); background: var(--bg-card); font-family: inherit; line-height: 1.6; resize: vertical; min-height: 130px; }
    .tr-langs { display: flex; gap: 7px; flex-wrap: wrap; margin: 14px 0; } .tr-lang { font-size: 12px; font-weight: 700; border: 1px solid var(--border-color); border-radius: 999px; padding: 7px 13px; cursor: pointer; color: var(--text-secondary); } .tr-lang.on { background: var(--tr); border-color: var(--tr); color: #fff; }
    .tr-btn { width: 100%; border: none; border-radius: 11px; padding: 12px; font-size: 13.5px; font-weight: 800; color: #fff; background: linear-gradient(135deg, var(--tr), #6d28d9); cursor: pointer; }
    .tr-out { background: rgba(124,58,237,.06); border: 1px solid rgba(124,58,237,.3); border-radius: 12px; padding: 14px; font-size: 13.5px; color: var(--text-secondary); line-height: 1.6; }
    .tr-tag { font-size: 10.5px; font-weight: 800; color: var(--tr); margin-bottom: 8px; }
    .tr-acts { display: flex; gap: 8px; margin-top: 12px; } .tr-acts button { flex: 1; border-radius: 10px; padding: 10px; font-size: 12.5px; font-weight: 800; cursor: pointer; } .tr-copy { border: 1px solid var(--border-color); background: var(--bg-card); color: var(--text-secondary); } .tr-reply { border: none; background: linear-gradient(135deg, var(--tr), #6d28d9); color: #fff; }
    .tr-note { font-size: 11px; color: var(--text-muted); margin-top: 10px; line-height: 1.5; }
    @media (max-width: 1000px) { .tr-grid { grid-template-columns: minmax(0,1fr); } .tr-stats { grid-template-columns: 1fr 1fr; } }
</style>
@endpush

@section('content')
<div class="tr">
    <div class="tr-stats">@foreach($stats as [$lbl, $val, $tone])<div class="tr-stat {{ $tone }}"><b>{{ $val }}</b><div class="l">{{ $lbl }}</div></div>@endforeach</div>
    <div class="tr-grid">
        <div class="tr-card">
            <h3>🌐 Original</h3>
            <div class="det">Detected: {{ $detected }}</div>
            <textarea class="tr-text">{{ $original }}</textarea>
            <div class="tr-langs">@foreach($languages as $i => $l)<span class="tr-lang {{ $i===0 ? 'on' : '' }}">{{ $l }}</span>@endforeach</div>
            <button class="tr-btn">🔄 Translate</button>
        </div>
        <div class="tr-card">
            <h3>✨ Translation</h3>
            <div class="det">English · tone-matched</div>
            <div class="tr-out">{{ $translation }}</div>
            <div class="tr-acts"><button class="tr-copy">📋 Copy</button><button class="tr-reply">↩ Reply in their language</button></div>
            <p class="tr-note">Replies can be auto-translated back to the client’s language — conversations just work across languages.</p>
        </div>
    </div>
</div>
@endsection
