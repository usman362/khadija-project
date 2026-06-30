@extends($aiLayout ?? 'layouts.client')

@section('title', 'AI Message Assistant')
@section('page-title', 'AI Message Assistant')
@section('page-subtitle', 'Clear, professional messages in the right tone')

@push('styles')
<style>
    .ma { --ma: #0d9488; }
    .ma-stats { display: grid; grid-template-columns: repeat(4, 1fr); gap: 12px; margin-bottom: 18px; }
    .ma-stat { background: var(--bg-card); border: 1px solid var(--border-color); border-radius: 14px; padding: 14px 16px; }
    .ma-stat b { display: block; font-size: 21px; font-weight: 800; color: var(--text-primary); line-height: 1; } .ma-stat.good b { color: #0d9488; } .ma-stat .l { font-size: 11.5px; color: var(--text-muted); margin-top: 6px; }
    .ma-grid { display: grid; grid-template-columns: minmax(0,1fr) minmax(0,1fr); gap: 18px; align-items: start; }
    .ma-card { background: var(--bg-card); border: 1px solid var(--border-color); border-radius: 16px; padding: 16px; }
    .ma-card h3 { font-size: 14.5px; font-weight: 800; color: var(--text-primary); margin-bottom: 12px; }
    .ma-lbl { display: block; font-size: 12px; font-weight: 700; color: var(--text-secondary); margin: 0 0 6px; }
    .ma-in { width: 100%; border: 1.5px solid var(--border-color); border-radius: 10px; padding: 10px 12px; font-size: 13px; color: var(--text-primary); background: var(--bg-card); font-family: inherit; margin-bottom: 12px; }
    textarea.ma-in { resize: vertical; min-height: 80px; }
    .ma-tones { display: flex; gap: 7px; flex-wrap: wrap; margin-bottom: 14px; } .ma-tone { font-size: 12px; font-weight: 700; border: 1px solid var(--border-color); border-radius: 999px; padding: 7px 13px; cursor: pointer; color: var(--text-secondary); } .ma-tone.on { background: var(--ma); border-color: var(--ma); color: #fff; }
    .ma-btn { width: 100%; border: none; border-radius: 11px; padding: 12px; font-size: 13.5px; font-weight: 800; color: #fff; background: linear-gradient(135deg, var(--ma), #0f766e); cursor: pointer; }
    .ma-out { background: rgba(13,148,136,.06); border: 1px solid rgba(13,148,136,.3); border-radius: 12px; padding: 14px; font-size: 13px; color: var(--text-secondary); line-height: 1.6; white-space: pre-line; }
    .ma-ready { font-size: 10.5px; font-weight: 800; color: #0d9488; margin-bottom: 8px; }
    .ma-acts { display: flex; gap: 8px; margin-top: 12px; } .ma-acts button { flex: 1; border-radius: 10px; padding: 10px; font-size: 12.5px; font-weight: 800; cursor: pointer; } .ma-copy { border: 1px solid var(--border-color); background: var(--bg-card); color: var(--text-secondary); } .ma-send { border: none; background: linear-gradient(135deg, var(--ma), #0f766e); color: #fff; }
    @media (max-width: 1000px) { .ma-grid { grid-template-columns: minmax(0,1fr); } .ma-stats { grid-template-columns: 1fr 1fr; } }
</style>
@endpush

@section('content')
<div class="ma">
    <div class="ma-stats">@foreach($stats as [$lbl, $val, $tone])<div class="ma-stat {{ $tone }}"><b>{{ $val }}</b><div class="l">{{ $lbl }}</div></div>@endforeach</div>
    <div class="ma-grid">
        <div class="ma-card">
            <h3>💬 What do you want to say?</h3>
            <label class="ma-lbl">Your intent</label>
            <input class="ma-in" value="{{ $intent }}">
            <label class="ma-lbl">Key points</label>
            <textarea class="ma-in">{{ $points }}</textarea>
            <label class="ma-lbl">Tone</label>
            <div class="ma-tones">@foreach($tones as $i => $t)<span class="ma-tone {{ $i===0 ? 'on' : '' }}">{{ $t }}</span>@endforeach</div>
            <button class="ma-btn">✍️ Draft My Message</button>
        </div>
        <div class="ma-card">
            <h3>📨 Suggested Message</h3>
            <div class="ma-ready">FRIENDLY & WARM · READY TO SEND</div>
            <div class="ma-out">{{ $suggested }}</div>
            <div class="ma-acts"><button class="ma-copy">📋 Copy</button><button class="ma-send">Send Message</button></div>
        </div>
    </div>
</div>
@endsection
