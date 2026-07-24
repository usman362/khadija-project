@extends('layouts.client')

@section('title', 'My Professionals')
@section('page-title', 'My Professionals')
@section('page-subtitle', 'The professionals you\'ve hired and saved — re-book them in one click.')

@push('styles')
<style>
    .mp-sec { margin-bottom: 26px; }
    .mp-sec-h { font-size: 13px; font-weight: 800; color: var(--text-primary); margin: 0 0 4px; display: flex; align-items: center; gap: 8px; }
    .mp-sec-sub { font-size: 12.5px; color: var(--text-muted); margin: 0 0 14px; }
    .mp-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 14px; }
    .mp-card { background: var(--bg-card); border: 1px solid var(--border-color); border-radius: 16px; padding: 16px; display: flex; flex-direction: column; gap: 10px; }
    .mp-top { display: flex; gap: 12px; align-items: center; }
    .mp-av { width: 46px; height: 46px; border-radius: 12px; background: linear-gradient(135deg,#f97316,#ea580c); color: #fff; font-weight: 800; font-size: 17px; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
    .mp-name { font-size: 14.5px; font-weight: 800; color: var(--text-primary); text-decoration: none; }
    .mp-name:hover { color: #f97316; }
    .mp-meta { font-size: 11.5px; color: var(--text-muted); margin-top: 2px; }
    .mp-stars { color: #f59e0b; font-size: 12px; }
    .mp-tags { display: flex; flex-wrap: wrap; gap: 6px; }
    .mp-tag { font-size: 10.5px; font-weight: 700; color: var(--text-secondary); background: var(--bg-card-hover, #f1f5f9); border-radius: 999px; padding: 3px 9px; }
    .mp-actions { display: flex; gap: 8px; margin-top: auto; }
    .mp-btn { flex: 1; text-align: center; font-size: 12.5px; font-weight: 700; padding: 8px 10px; border-radius: 9px; border: 1px solid var(--border-color); background: var(--bg-card); color: var(--text-primary); text-decoration: none; cursor: pointer; }
    .mp-btn.primary { background: #f97316; color: #fff; border-color: #f97316; }
    .mp-btn.primary:hover { filter: brightness(1.05); }
    .mp-empty { background: var(--bg-card); border: 1px dashed var(--border-color); border-radius: 14px; padding: 24px; text-align: center; color: var(--text-muted); font-size: 13px; }
    .mp-empty a { color: #f97316; font-weight: 700; text-decoration: none; }
</style>
@endpush

@section('content')
<div>
    @if(session('status'))
        <div style="background:rgba(16,163,74,.12);border:1px solid rgba(16,163,74,.35);color:#16a34a;padding:11px 16px;border-radius:10px;margin-bottom:16px;font-size:13.5px;">{{ session('status') }}</div>
    @endif

    {{-- Worked with — derived from real bookings --}}
    <div class="mp-sec">
        <h3 class="mp-sec-h">🤝 Worked With</h3>
        <p class="mp-sec-sub">Professionals you've hired before. Re-book without starting a new search.</p>

        @if($workedWith->isEmpty())
            <div class="mp-empty">You haven't hired anyone yet. <a href="{{ route('client.search.index') }}">Find professionals →</a></div>
        @else
            <div class="mp-grid">
                @foreach($workedWith as $row)
                    @php $pro = $row['pro']; $isSaved = $savedIds->contains($pro->id); @endphp
                    <div class="mp-card">
                        <div class="mp-top">
                            <div class="mp-av">{{ strtoupper(substr($pro->name, 0, 1)) }}</div>
                            <div>
                                <a href="{{ route('public.professional.show', $pro) }}" class="mp-name">{{ $pro->name }}</a>
                                <div class="mp-meta">
                                    @if($pro->reviews_avg)<span class="mp-stars">{{ str_repeat('★', (int) round($pro->reviews_avg)) }}</span> {{ number_format($pro->reviews_avg, 1) }} · @endif
                                    Hired {{ $row['times'] }}{{ $row['times'] === 1 ? ' time' : ' times' }}
                                </div>
                            </div>
                        </div>
                        <div class="mp-meta">Last worked together {{ $row['last']?->diffForHumans() }}{{ $row['completed'] ? ' · ' . $row['completed'] . ' completed' : '' }}</div>
                        <div class="mp-actions">
                            <a href="{{ route('client.direct-offers.create', ['pro' => $pro->id]) }}" class="mp-btn primary">Re-book</a>
                            <a href="{{ route('client.chat.index') }}" class="mp-btn">Message</a>
                            @if($isSaved)
                                <form method="POST" action="{{ route('client.saved-professionals.destroy', $pro) }}" style="flex:1;">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="mp-btn" title="Saved — click to unsave">★ Saved</button>
                                </form>
                            @else
                                <form method="POST" action="{{ route('client.saved-professionals.store') }}" style="flex:1;">
                                    @csrf
                                    <input type="hidden" name="professional_id" value="{{ $pro->id }}">
                                    <button type="submit" class="mp-btn" title="Save">☆ Save</button>
                                </form>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>

    {{-- Explicitly saved (that they haven't necessarily hired) --}}
    <div class="mp-sec">
        <h3 class="mp-sec-h">⭐ Saved</h3>
        <p class="mp-sec-sub">Professionals you've pinned to come back to.</p>

        @if($saved->isEmpty())
            <div class="mp-empty">No saved professionals yet. Save any pro from their profile or from Worked With above.</div>
        @else
            <div class="mp-grid">
                @foreach($saved as $pro)
                    <div class="mp-card">
                        <div class="mp-top">
                            <div class="mp-av">{{ strtoupper(substr($pro->name, 0, 1)) }}</div>
                            <div>
                                <a href="{{ route('public.professional.show', $pro) }}" class="mp-name">{{ $pro->name }}</a>
                                <div class="mp-meta">
                                    @if($pro->reviews_avg)<span class="mp-stars">{{ str_repeat('★', (int) round($pro->reviews_avg)) }}</span> {{ number_format($pro->reviews_avg, 1) }}@else Not yet reviewed @endif
                                </div>
                            </div>
                        </div>
                        @if($pro->pivot->note)<div class="mp-meta">“{{ $pro->pivot->note }}”</div>@endif
                        <div class="mp-actions">
                            <a href="{{ route('client.direct-offers.create', ['pro' => $pro->id]) }}" class="mp-btn primary">Invite</a>
                            <form method="POST" action="{{ route('client.saved-professionals.destroy', $pro) }}" style="flex:1;">
                                @csrf @method('DELETE')
                                <button type="submit" class="mp-btn">Remove</button>
                            </form>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</div>
@endsection
