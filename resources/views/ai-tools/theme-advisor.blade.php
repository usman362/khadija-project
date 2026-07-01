@extends($aiLayout ?? 'layouts.client')

@section('title', 'AI Theme & Style Advisor')
@section('page-title', 'AI Theme & Style Advisor')
@section('page-subtitle', 'Cohesive themes, palettes & mood boards for your event')

{{-- AI Theme & Style Advisor (client). AI-generated themes + colour palette +
     mood board + category filters. Representative data. --}}

@push('styles')
<style>
    .ta { --ta: #7c3aed; --ta-strong: #6d28d9; }
    .ta-sec { background: var(--bg-card); border: 1px solid var(--border-color); border-radius: 16px; padding: 18px; margin-bottom: 18px; }
    .ta-sec > h3 { font-size: 15px; font-weight: 800; color: var(--text-primary); margin-bottom: 14px; }

    .ta-themes { display: grid; grid-template-columns: repeat(3, 1fr); gap: 14px; }
    .ta-theme { border: 1px solid var(--border-color); border-radius: 14px; overflow: hidden; background: var(--bg-card); }
    .ta-theme.best { border-color: var(--ta); box-shadow: 0 0 0 1px var(--ta); }
    .ta-img { position: relative; height: 130px; }
    .ta-img img { width: 100%; height: 100%; object-fit: cover; }
    .ta-best { position: absolute; left: 8px; top: 8px; font-size: 10px; font-weight: 800; color: #fff; background: var(--ta); padding: 3px 9px; border-radius: 999px; }
    .ta-match { position: absolute; right: 8px; top: 8px; font-size: 11px; font-weight: 800; color: #fff; background: rgba(22,163,74,.92); padding: 3px 9px; border-radius: 999px; }
    .ta-body { padding: 13px; }
    .ta-body h4 { font-size: 14.5px; font-weight: 800; color: var(--text-primary); }
    .ta-body p { font-size: 12px; color: var(--text-muted); line-height: 1.5; margin: 6px 0 10px; }
    .ta-sw { display: flex; gap: 5px; margin-bottom: 12px; }
    .ta-sw i { width: 26px; height: 26px; border-radius: 7px; border: 1px solid rgba(0,0,0,.1); }
    .ta-acts { display: flex; gap: 8px; }
    .ta-btn { flex: 1; text-align: center; font-size: 12px; font-weight: 800; border-radius: 9px; padding: 9px; cursor: pointer; border: 1px solid var(--border-color); background: var(--bg-card); color: var(--text-secondary); }
    .ta-btn.primary { border: none; background: linear-gradient(135deg, var(--ta), var(--ta-strong)); color: #fff; }

    .ta-palette { display: grid; grid-template-columns: repeat(4, 1fr); gap: 12px; }
    .ta-pal { border: 1px solid var(--border-color); border-radius: 12px; overflow: hidden; }
    .ta-pal-sw { height: 56px; }
    .ta-pal-info { padding: 9px 11px; }
    .ta-pal-info .role { font-size: 10.5px; font-weight: 800; color: var(--text-muted); text-transform: uppercase; letter-spacing: .3px; }
    .ta-pal-info h5 { font-size: 13px; font-weight: 800; color: var(--text-primary); }
    .ta-pal-info code { font-size: 11px; color: var(--text-muted); }

    .ta-cats { display: flex; gap: 8px; flex-wrap: wrap; margin-bottom: 13px; }
    .ta-cat { font-size: 12px; font-weight: 700; border: 1px solid var(--border-color); border-radius: 999px; padding: 6px 13px; color: var(--text-secondary); cursor: pointer; background: var(--bg-card); }
    .ta-cat.on { background: var(--ta); border-color: var(--ta); color: #fff; }
    .ta-mood { display: grid; grid-template-columns: repeat(6, 1fr); gap: 8px; }
    .ta-mood img { width: 100%; height: 90px; object-fit: cover; border-radius: 10px; }

    @media (max-width: 1000px) { .ta-themes, .ta-palette { grid-template-columns: 1fr 1fr; } .ta-mood { grid-template-columns: repeat(3,1fr); } }
    @media (max-width: 620px) { .ta-themes, .ta-palette { grid-template-columns: 1fr; } }

    /* Advisor form */
    .ta-form-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 14px; }
    .ta-field label { display: block; font-size: 12px; font-weight: 700; color: var(--text-secondary); margin-bottom: 6px; }
    .ta-field input, .ta-field select { width: 100%; padding: 10px 12px; background: var(--bg-body, var(--bg-card)); border: 1px solid var(--border-color); border-radius: 10px; color: var(--text-primary); font-size: 13.5px; font-family: inherit; }
    .ta-field input:focus, .ta-field select:focus { outline: none; border-color: var(--ta); }
    .ta-gen-btn { margin-top: 16px; display: inline-flex; align-items: center; gap: 8px; padding: 11px 22px; background: linear-gradient(135deg, var(--ta), var(--ta-strong)); color: #fff; border: none; border-radius: 10px; font-size: 13.5px; font-weight: 800; cursor: pointer; font-family: inherit; }
    .ta-gen-btn:disabled { opacity: .6; cursor: not-allowed; }
    .ta-err { display: none; margin-top: 12px; padding: 10px 14px; background: rgba(220,38,38,.1); border: 1px solid rgba(220,38,38,.3); color: #dc2626; border-radius: 10px; font-size: 12.5px; }
    .ta-err.on { display: block; }
    .ta-out { display: none; }
    .ta-out.on { display: block; }
    .ta-out-sum { padding: 13px 16px; background: rgba(124,58,237,.06); border-left: 3px solid var(--ta); border-radius: 8px; font-size: 13px; color: var(--text-secondary); line-height: 1.55; margin-bottom: 16px; }
    .ta-chips { display: flex; gap: 8px; flex-wrap: wrap; }
    .ta-chip { font-size: 12px; font-weight: 700; background: rgba(124,58,237,.1); color: var(--ta-strong); border-radius: 999px; padding: 6px 13px; text-transform: capitalize; }
    .ta-list { margin: 0; padding-left: 18px; }
    .ta-list li { font-size: 13px; color: var(--text-secondary); line-height: 1.6; margin-bottom: 5px; }
    @media (max-width: 700px) { .ta-form-grid { grid-template-columns: 1fr 1fr; } }
    @media (max-width: 460px) { .ta-form-grid { grid-template-columns: 1fr; } }
</style>
@endpush

@section('content')
<div class="ta">
    {{-- Advisor form --}}
    <div class="ta-sec">
        <h3>🎯 Build Your Palette</h3>
        <form id="taForm">
            <div class="ta-form-grid">
                <div class="ta-field">
                    <label>Event Type</label>
                    <select name="event_type" required>
                        <option value="Wedding">Wedding</option>
                        <option value="Birthday Party">Birthday Party</option>
                        <option value="Corporate Event">Corporate Event</option>
                        <option value="Product Launch">Product Launch</option>
                        <option value="Anniversary">Anniversary</option>
                        <option value="Private Party">Private Party</option>
                    </select>
                </div>
                <div class="ta-field">
                    <label>Season</label>
                    <select name="season" required>
                        <option value="spring">Spring</option>
                        <option value="summer">Summer</option>
                        <option value="fall">Fall</option>
                        <option value="winter">Winter</option>
                    </select>
                </div>
                <div class="ta-field">
                    <label>Primary Color</label>
                    <input type="text" name="primary_color" required placeholder="#c81d5a or “emerald”">
                </div>
                <div class="ta-field">
                    <label>Formality</label>
                    <select name="formality" required>
                        <option value="casual">Casual</option>
                        <option value="semi-formal">Semi-formal</option>
                        <option value="formal">Formal</option>
                    </select>
                </div>
            </div>
            <button type="submit" class="ta-gen-btn" id="taSubmit">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg>
                Generate Palette
            </button>
            <div class="ta-err" id="taErr"></div>
        </form>
    </div>

    {{-- Generated palette + guidance --}}
    <div class="ta-out" id="taOut">
        <div class="ta-sec">
            <h3>🎨 Your Suggested Palette</h3>
            <div class="ta-out-sum" id="taSummary"></div>
            <div class="ta-palette" id="taPalette"></div>
        </div>
        <div class="ta-sec">
            <h3>💭 Mood Keywords</h3>
            <div class="ta-chips" id="taMood"></div>
        </div>
        <div class="ta-sec">
            <h3>🪄 Décor Suggestions</h3>
            <ul class="ta-list" id="taDecor"></ul>
        </div>
        <div class="ta-sec">
            <h3>✨ Styling Tips</h3>
            <ul class="ta-list" id="taTips"></ul>
        </div>
    </div>

    {{-- Themes --}}
    <div class="ta-sec">
        <h3>✨ Your AI-Generated Themes</h3>
        <div class="ta-themes">
            @foreach($themes as [$name, $match, $desc, $img, $sw, $best])
                <div class="ta-theme {{ $best ? 'best' : '' }}">
                    <div class="ta-img">
                        @if($best)<span class="ta-best">★ Best Match</span>@endif
                        <span class="ta-match">{{ $match }}% Match</span>
                        <img src="https://images.unsplash.com/{{ $img }}?w=420&q=70&auto=format&fit=crop" alt="{{ $name }}" loading="lazy">
                    </div>
                    <div class="ta-body">
                        <h4>{{ $name }}</h4>
                        <p>{{ $desc }}</p>
                        <div class="ta-sw">@foreach($sw as $c)<i style="background: {{ $c }};"></i>@endforeach</div>
                        <div class="ta-acts">
                            <span class="ta-btn">View Details</span>
                            <span class="ta-btn primary">Select Theme</span>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    {{-- Palette --}}
    <div class="ta-sec">
        <h3>🎨 Recommended Color Palette</h3>
        <div class="ta-palette">
            @foreach($palette as [$role, $hex, $label])
                <div class="ta-pal">
                    <div class="ta-pal-sw" style="background: {{ $hex }};"></div>
                    <div class="ta-pal-info"><div class="role">{{ $role }}</div><h5>{{ $label }}</h5><code>{{ $hex }}</code></div>
                </div>
            @endforeach
        </div>
    </div>

    {{-- Mood board --}}
    <div class="ta-sec">
        <h3>🖼 Mood Board</h3>
        <div class="ta-cats">
            @foreach($categories as $i => $c)<span class="ta-cat {{ $i===0 ? 'on' : '' }}">{{ $c }}</span>@endforeach
        </div>
        <div class="ta-mood">
            @foreach($moodboard as $m)<img src="https://images.unsplash.com/{{ $m }}?w=240&q=65&auto=format&fit=crop" alt="" loading="lazy">@endforeach
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
(function () {
    const form = document.getElementById('taForm');
    if (!form) return;

    const submit = document.getElementById('taSubmit');
    const out    = document.getElementById('taOut');
    const errEl  = document.getElementById('taErr');
    const csrf   = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

    form.addEventListener('submit', async function (e) {
        e.preventDefault();
        errEl.classList.remove('on');
        out.classList.remove('on');
        submit.disabled = true;
        const prev = submit.innerHTML;
        submit.innerHTML = 'Generating…';

        const payload = Object.fromEntries(new FormData(form).entries());

        try {
            const r = await fetch('{{ route("ai-tools.theme-advisor.compute") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrf,
                    'Accept': 'application/json',
                },
                credentials: 'same-origin',
                body: JSON.stringify(payload),
            });
            const data = await r.json();
            submit.disabled = false;
            submit.innerHTML = prev;

            if (!data.success) {
                errEl.textContent = data.message || 'Could not generate palette.';
                errEl.classList.add('on');
                return;
            }
            render(data.result);
            out.classList.add('on');
            out.scrollIntoView({ behavior: 'smooth', block: 'start' });
        } catch (err) {
            submit.disabled = false;
            submit.innerHTML = prev;
            errEl.textContent = 'Network error. Please try again.';
            errEl.classList.add('on');
        }
    });

    function render(res) {
        document.getElementById('taSummary').textContent = res.summary || '';

        document.getElementById('taPalette').innerHTML = (res.palette || []).map(function (p) {
            return '<div class="ta-pal">' +
                '<div class="ta-pal-sw" style="background:' + esc(p.hex) + ';"></div>' +
                '<div class="ta-pal-info"><h5>' + esc(p.name) + '</h5><code>' + esc(p.hex) + '</code></div>' +
                '</div>';
        }).join('');

        document.getElementById('taMood').innerHTML = (res.mood_keywords || []).map(function (m) {
            return '<span class="ta-chip">' + esc(m) + '</span>';
        }).join('');

        document.getElementById('taDecor').innerHTML = (res.decor_suggestions || []).map(function (d) {
            return '<li>' + esc(d) + '</li>';
        }).join('');

        document.getElementById('taTips').innerHTML = (res.tips || []).map(function (t) {
            return '<li>' + esc(t) + '</li>';
        }).join('');
    }

    function esc(s) {
        return String(s === null || s === undefined ? '' : s).replace(/[&<>"']/g, function (c) {
            return { '&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;' }[c];
        });
    }
})();
</script>
@endpush
