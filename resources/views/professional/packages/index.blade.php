@extends('layouts.professional')

@section('title', 'My Packages')
@section('page-title', 'My Packages')
@section('page-subtitle', 'Fixed service bundles clients can browse and book directly.')

@push('styles')
<style>
    .pkl-head { display: flex; justify-content: space-between; align-items: center; margin-bottom: 18px; flex-wrap: wrap; gap: 12px; }
    .pkl-new { display: inline-flex; align-items: center; gap: 8px; background: linear-gradient(135deg,#3b82f6,#2563eb); color: #fff; padding: 11px 20px; border-radius: 10px; font-weight: 800; text-decoration: none; font-size: 14px; }
    .pkl-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 18px; }
    .pkl-card { background: var(--bg-card); border: 1px solid var(--border-color); border-radius: 16px; overflow: hidden; display: flex; flex-direction: column; }
    .pkl-media { height: 160px; background: linear-gradient(135deg,#1e3a5f,#2d1b69); position: relative; }
    .pkl-media img { width: 100%; height: 100%; object-fit: cover; }
    .pkl-badge { position: absolute; top: 10px; left: 10px; font-size: 11px; font-weight: 800; padding: 4px 10px; border-radius: 999px; }
    .pkl-badge.on { background: #16a34a; color: #fff; }
    .pkl-badge.off { background: rgba(15,27,53,.7); color: #fff; }
    .pkl-st-active   { background: #16a34a; color: #fff; }
    .pkl-st-draft    { background: rgba(15,27,53,.7); color: #fff; }
    .pkl-st-paused   { background: #d97706; color: #fff; }
    .pkl-st-archived { background: #64748b; color: #fff; }
    .pkl-body { padding: 16px; display: flex; flex-direction: column; gap: 6px; flex: 1; }
    .pkl-cat { font-size: 11.5px; color: var(--text-muted); text-transform: uppercase; letter-spacing: .3px; font-weight: 700; }
    .pkl-title { font-size: 15px; font-weight: 800; color: var(--text-primary); line-height: 1.25; }
    .pkl-price { font-size: 18px; font-weight: 900; color: var(--accent-blue, #3b82f6); margin-top: 2px; }
    .pkl-price small { font-size: 12px; font-weight: 600; color: var(--text-muted); }
    .pkl-actions { display: flex; gap: 8px; margin-top: 10px; }
    .pkl-btn { flex: 1; text-align: center; padding: 8px; border-radius: 9px; font-size: 13px; font-weight: 700; text-decoration: none; border: 1px solid var(--border-color); color: var(--text-secondary); background: transparent; cursor: pointer; }
    .pkl-btn.del { color: #ef4444; border-color: rgba(239,68,68,.3); }
    .pkl-empty { background: var(--bg-card); border: 1px dashed var(--border-color); border-radius: 16px; padding: 56px 24px; text-align: center; color: var(--text-muted); }
    .pkl-empty h3 { color: var(--text-primary); margin: 0 0 8px; }
</style>
@endpush

@section('content')
    <div class="pkl-head">
        <div style="font-size:14px;color:var(--text-muted);">{{ $packages->total() }} package{{ $packages->total() === 1 ? '' : 's' }}</div>
        <a href="{{ route('professional.packages.create') }}" class="pkl-new">＋ Create a Package</a>
    </div>

    @if(session('status'))
        <div style="background:rgba(16,163,74,.12);border:1px solid rgba(16,163,74,.35);color:#16a34a;padding:11px 16px;border-radius:10px;margin-bottom:16px;font-size:13.5px;">{{ session('status') }}</div>
    @endif

    @if($packages->count())
        <div class="pkl-grid">
            @foreach($packages as $pkg)
                @php $hero = $pkg->heroUrls(1)[0] ?? null; @endphp
                <div class="pkl-card">
                    <div class="pkl-media">
                        @if($hero)<img src="{{ $hero }}" alt="{{ $pkg->title }}" loading="lazy">@endif
                        @php $st = $pkg->status ?? ($pkg->is_active ? 'active' : 'draft'); @endphp
                        <span class="pkl-badge pkl-st-{{ $st }}">{{ ucfirst($st) }}</span>
                    </div>
                    <div class="pkl-body">
                        <div class="pkl-cat">{{ $pkg->category?->name ?? ucfirst($pkg->type) }}</div>
                        <div class="pkl-title">{{ $pkg->title }}</div>
                        <div class="pkl-price">{{ $pkg->priceLabel() }} @if($pkg->duration)<small>· {{ $pkg->duration }}</small>@endif</div>
                        <div class="pkl-actions">
                            <a href="{{ route('professional.packages.edit', $pkg) }}" class="pkl-btn">Edit</a>
                            @php $st = $pkg->status ?? ($pkg->is_active ? 'active' : 'draft'); @endphp
                            {{-- Lifecycle: draft/paused → Publish (active); active → Pause. --}}
                            @if($st === 'active')
                                <form action="{{ route('professional.packages.status', $pkg) }}" method="POST" style="flex:1;">
                                    @csrf @method('PATCH')
                                    <input type="hidden" name="status" value="paused">
                                    <button type="submit" class="pkl-btn" style="width:100%;">Pause</button>
                                </form>
                            @elseif($st !== 'archived')
                                <form action="{{ route('professional.packages.status', $pkg) }}" method="POST" style="flex:1;">
                                    @csrf @method('PATCH')
                                    <input type="hidden" name="status" value="active">
                                    <button type="submit" class="pkl-btn" style="width:100%;">Publish</button>
                                </form>
                            @endif
                        </div>
                        <div class="pkl-actions" style="margin-top:6px;">
                            @if($st !== 'archived')
                                <form action="{{ route('professional.packages.status', $pkg) }}" method="POST" style="flex:1;" onsubmit="return confirm('Archive this package? It will stop appearing in search.')">
                                    @csrf @method('PATCH')
                                    <input type="hidden" name="status" value="archived">
                                    <button type="submit" class="pkl-btn" style="width:100%;">Archive</button>
                                </form>
                            @endif
                            <form action="{{ route('professional.packages.destroy', $pkg) }}" method="POST" style="flex:1;" onsubmit="return confirm('Delete this package?')">
                                @csrf @method('DELETE')
                                <button type="submit" class="pkl-btn del" style="width:100%;">Delete</button>
                            </form>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
        <div class="mt-4">{{ $packages->links() }}</div>
    @else
        <div class="pkl-empty">
            <h3>No packages yet</h3>
            <p>Create a package to let clients book your services directly — no back-and-forth.</p>
            <a href="{{ route('professional.packages.create') }}" class="pkl-new" style="margin-top:14px;">＋ Create your first package</a>
        </div>
    @endif
@endsection
