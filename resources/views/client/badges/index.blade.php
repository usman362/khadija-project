@extends('layouts.client')

@section('title', 'Your Badges')
@section('page-title', 'Your Badges')
@section('page-subtitle', 'Every badge you can earn, and how close you are.')

@push('styles')
<style>
    .bdg-sum { background: var(--bg-card); border: 1px solid var(--border-color); border-radius: 16px; padding: 16px 20px; margin-bottom: 18px;
               display: flex; align-items: center; justify-content: space-between; gap: 12px; flex-wrap: wrap; }
    .bdg-sum b { font-size: 16px; color: var(--text-primary); }
    .bdg-sum span { font-size: 13px; color: var(--text-muted); }
    .bdg-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(250px, 1fr)); gap: 14px; }
    .bdg-card { background: var(--bg-card); border: 1px solid var(--border-color); border-radius: 16px; padding: 18px; display: flex; gap: 14px; align-items: flex-start; }
    .bdg-card.is-earned { border-color: #bfdbfe; box-shadow: 0 0 0 1px #bfdbfe inset; }
    .bdg-card h4 { font-size: 14.5px; font-weight: 800; color: var(--text-primary); margin: 0 0 4px; }
    .bdg-card p { font-size: 12.5px; color: var(--text-muted); line-height: 1.5; margin: 0 0 8px; }
    .bdg-state { font-size: 11.5px; font-weight: 800; padding: 2px 8px; border-radius: 999px; display: inline-block; }
    .bdg-state.on { background: #dcfce7; color: #166534; }
    .bdg-state.off { background: #f3f4f6; color: #4b5563; }
    .bdg-bar { height: 6px; border-radius: 999px; background: #f1f5f9; overflow: hidden; margin: 8px 0 4px; }
    .bdg-bar i { display: block; height: 100%; background: #2563eb; }
    .bdg-card small { font-size: 11.5px; color: var(--text-muted); }
    .bdg-card a { font-size: 12.5px; font-weight: 700; color: #ea580c; }
    .bdg-note { font-size: 12.5px; color: var(--text-muted); margin-top: 16px; line-height: 1.6; }
</style>
@endpush

@section('content')
<div class="bdg-sum">
    <div>
        <b>{{ $earned }} of {{ $badges->count() }} badges earned</b><br>
        <span>Badges are worked out from what you do on GigResource, so they update on their own as you earn or lose them.</span>
    </div>
    <a href="{{ route('client.profile.index') }}" style="font-size:13px;font-weight:700;color:#ea580c;">Back to your profile</a>
</div>

<div class="bdg-grid">
    @foreach($badges as $b)
        <div class="bdg-card {{ $b['earned'] ? 'is-earned' : '' }}">
            <x-hex-badge :icon="$b['icon']" :colour="$b['colour'] ?? '#2563eb'" :earned="$b['earned']" :size="52" />
            <div style="flex:1;min-width:0;">
                <h4>{{ $b['name'] }}</h4>
                <p>{{ $b['blurb'] }}</p>
                @if($b['earned'])
                    <span class="bdg-state on">Earned</span>
                @else
                    <span class="bdg-state off">Not yet</span>
                    @if($b['need'] > 1)
                        <div class="bdg-bar"><i style="width: {{ round(($b['progress'] / max(1, $b['need'])) * 100) }}%;"></i></div>
                        <small>{{ $b['progress'] }} of {{ $b['need'] }}</small>
                    @endif
                    @if($b['key'] === 'verified-client')
                        <div style="margin-top:6px;"><a href="{{ route('client.verification.show') }}">Verify your identity</a></div>
                    @endif
                @endif
            </div>
        </div>
    @endforeach
</div>

<p class="bdg-note">Professionals see your badges on your profile. Nothing here can be bought or given by hand.</p>
@endsection
