@extends('layouts.professional')

@php $package = $package ?? null; @endphp

@section('title', $package ? 'Edit Package' : 'Create a Package')
@section('page-title', $package ? 'Edit Package' : 'Create a Package')
@section('page-subtitle', 'Bundle your services into a fixed offering clients can browse and book.')

@push('styles')
<style>
    .pk-wrap { max-width: 820px; }
    .pk-card { background: var(--bg-card); border: 1px solid var(--border-color); border-radius: 16px; padding: 24px; margin-bottom: 18px; }
    .pk-card h3 { font-size: 15px; font-weight: 800; color: var(--text-white); margin: 0 0 4px; }
    .pk-card .hint { font-size: 12.5px; color: var(--text-muted); margin: 0 0 16px; }
    .pk-row { display: grid; grid-template-columns: 1fr 1fr; gap: 14px; }
    .pk-field { margin-bottom: 14px; }
    .pk-field label { display: block; font-size: 13px; font-weight: 700; color: var(--text-light); margin-bottom: 6px; }
    .pk-input, .pk-select, .pk-textarea { width: 100%; padding: 11px 13px; border-radius: 10px; border: 1px solid var(--border-color);
        background: var(--bg-section); color: var(--text-white); font-size: 14px; font-family: inherit; }
    .pk-textarea { min-height: 96px; resize: vertical; }
    .pk-inc-row { display: flex; gap: 8px; margin-bottom: 8px; }
    .pk-inc-row .pk-input { flex: 1; }
    .pk-inc-del, .pk-inc-add { border: 1px solid var(--border-color); background: var(--bg-section); color: var(--text-muted); border-radius: 10px; padding: 0 14px; cursor: pointer; font-weight: 800; }
    .pk-inc-add { color: var(--accent-blue, #2563eb); font-size: 13px; padding: 9px 14px; }
    .pk-cover { border: 2px dashed var(--border-color); border-radius: 12px; padding: 22px; text-align: center; color: var(--text-muted); cursor: pointer; font-weight: 700; }
    .pk-cover:hover { border-color: var(--accent-blue, #2563eb); color: var(--accent-blue, #2563eb); }
    .pk-cover input { display: none; }
    .pk-cover img { max-height: 140px; border-radius: 10px; margin-bottom: 8px; }
    .pk-actions { display: flex; gap: 10px; justify-content: flex-end; }
    .pk-btn { padding: 11px 22px; border-radius: 10px; font-weight: 800; cursor: pointer; border: none; text-decoration: none; font-size: 14px; }
    .pk-btn-primary { background: linear-gradient(135deg, #3b82f6, #2563eb); color: #fff; }
    .pk-btn-ghost { background: transparent; border: 1px solid var(--border-color); color: var(--text-light); }
    .pk-toggle { display: flex; align-items: center; gap: 10px; font-size: 13px; color: var(--text-light); }
    .pk-err { color: #ef4444; font-size: 12.5px; margin-top: 4px; }
</style>
@endpush

@section('content')
<div class="pk-wrap">
    @if($errors->any())
        <div class="pk-card" style="border-color:rgba(239,68,68,.4);background:rgba(239,68,68,.08);">
            <ul style="margin:0;padding-left:18px;color:#ef4444;font-size:13px;">
                @foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach
            </ul>
        </div>
    @endif

    <form method="POST"
          action="{{ $package ? route('professional.packages.update', $package) : route('professional.packages.store') }}"
          enctype="multipart/form-data">
        @csrf
        @if($package) @method('PATCH') @endif

        <div class="pk-card">
            <h3>Package details</h3>
            <p class="hint">Give your package a clear name and what it covers.</p>

            <div class="pk-field">
                <label>Package title *</label>
                <input type="text" name="title" class="pk-input" required maxlength="160"
                       value="{{ old('title', $package->title ?? '') }}" placeholder="e.g. Full-Day Wedding Photography Package">
            </div>

            <div class="pk-row">
                <div class="pk-field">
                    <label>Category</label>
                    <select name="category_id" class="pk-select">
                        <option value="">Select a category</option>
                        @foreach($categories as $c)
                            <option value="{{ $c['id'] }}" @selected((int) old('category_id', $package->category_id ?? 0) === $c['id'])>{{ $c['name'] }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="pk-field">
                    <label>Package type</label>
                    <select name="type" class="pk-select">
                        <option value="solo" @selected(old('type', $package->type ?? 'solo') === 'solo')>Solo — just my services</option>
                        <option value="co-op" @selected(old('type', $package->type ?? '') === 'co-op')>Co-op — bundled with another pro</option>
                    </select>
                </div>
            </div>

            <div class="pk-field">
                <label>Description</label>
                <textarea name="description" class="pk-textarea" maxlength="2000" placeholder="What makes this package great? Who is it for?">{{ old('description', $package->description ?? '') }}</textarea>
            </div>
        </div>

        <div class="pk-card">
            <h3>Pricing</h3>
            <p class="hint">Set a clear starting price. Clients see this on your package card.</p>
            <div class="pk-row">
                <div class="pk-field">
                    <label>Price (USD) *</label>
                    <input type="number" name="price" class="pk-input" min="0" max="1000000" required
                           value="{{ old('price', $package->price ?? '') }}" placeholder="2500">
                </div>
                <div class="pk-field">
                    <label>Price type</label>
                    <select name="price_unit" class="pk-select">
                        <option value="flat"   @selected(old('price_unit', $package->price_unit ?? 'flat') === 'flat')>Flat rate</option>
                        <option value="from"   @selected(old('price_unit', $package->price_unit ?? '') === 'from')>Starting from</option>
                        <option value="hourly" @selected(old('price_unit', $package->price_unit ?? '') === 'hourly')>Per hour</option>
                    </select>
                </div>
            </div>
            <div class="pk-field">
                <label>Duration / scope <span style="color:var(--text-muted);font-weight:500;">(optional)</span></label>
                <input type="text" name="duration" class="pk-input" maxlength="60"
                       value="{{ old('duration', $package->duration ?? '') }}" placeholder="e.g. 6 hours coverage">
            </div>
        </div>

        <div class="pk-card">
            <h3>What's included</h3>
            <p class="hint">List the key things a client gets. Add as many as you need.</p>
            <div id="pkIncludes">
                @php $inc = old('includes', $package->includes ?? ['']); @endphp
                @foreach((array) ($inc ?: ['']) as $line)
                    <div class="pk-inc-row">
                        <input type="text" name="includes[]" class="pk-input" maxlength="160" value="{{ $line }}" placeholder="e.g. Edited online gallery (300+ photos)">
                        <button type="button" class="pk-inc-del" onclick="this.closest('.pk-inc-row').remove()">✕</button>
                    </div>
                @endforeach
            </div>
            <button type="button" class="pk-inc-add" onclick="pkAddInclude()">＋ Add item</button>
        </div>

        <div class="pk-card">
            <h3>Cover image</h3>
            <p class="hint">One great photo — we auto-generate every size for cards and detail pages.</p>
            <label class="pk-cover" id="pkCover">
                @php $cover = $package?->heroUrls(1)[0] ?? null; @endphp
                @if($cover)<img src="{{ $cover }}" alt="cover" id="pkCoverImg">@endif
                <div id="pkCoverText">{{ $cover ? 'Change cover photo' : '＋ Upload cover photo' }}</div>
                <input type="file" name="cover_image" accept="image/jpeg,image/png,image/webp" onchange="pkPreview(this)">
            </label>
        </div>

        <div class="pk-card">
            <label class="pk-toggle">
                <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $package->is_active ?? true))>
                Publish this package (visible to clients)
            </label>
        </div>

        <div class="pk-actions">
            <a href="{{ route('professional.packages.index') }}" class="pk-btn pk-btn-ghost">Cancel</a>
            <button type="submit" class="pk-btn pk-btn-primary">{{ $package ? 'Save changes' : 'Create package' }}</button>
        </div>
    </form>
</div>

<script>
    function pkAddInclude() {
        var wrap = document.getElementById('pkIncludes');
        var row = document.createElement('div');
        row.className = 'pk-inc-row';
        row.innerHTML = '<input type="text" name="includes[]" class="pk-input" maxlength="160" placeholder="Add an item">' +
                        '<button type="button" class="pk-inc-del" onclick="this.closest(\'.pk-inc-row\').remove()">✕</button>';
        wrap.appendChild(row);
    }
    function pkPreview(input) {
        if (!input.files || !input.files[0]) return;
        var url = URL.createObjectURL(input.files[0]);
        var img = document.getElementById('pkCoverImg');
        if (!img) { img = document.createElement('img'); img.id = 'pkCoverImg'; document.getElementById('pkCover').prepend(img); }
        img.src = url;
        document.getElementById('pkCoverText').textContent = 'Change cover photo';
    }
</script>
@endsection
