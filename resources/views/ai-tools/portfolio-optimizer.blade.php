@extends($aiLayout ?? 'layouts.professional')

@section('title', 'Portfolio Optimizer')
@section('page-title', 'Portfolio Optimizer')
@section('page-subtitle', 'Lift your visibility, views and win-rate')

{{-- Portfolio Optimizer (professional). Profile audit + high-impact
     recommendations + gallery scoring + benchmark. Representative data. --}}

@push('styles')
<style>
    .po { --po: var(--brand, #6366f1); --po-strong: var(--brand-strong, #4f46e5); }
    .po-stats { display: grid; grid-template-columns: repeat(4, 1fr); gap: 12px; margin-bottom: 18px; }
    .po-stat { background: var(--bg-card); border: 1px solid var(--border-color); border-radius: 14px; padding: 14px 16px; }
    .po-stat b { display: block; font-size: 24px; font-weight: 800; color: #16a34a; line-height: 1; } .po-stat .l { font-size: 11.5px; color: var(--text-muted); margin-top: 6px; }

    .po-grid { display: grid; grid-template-columns: 270px minmax(0,1fr); gap: 18px; align-items: start; }
    .po-card { background: var(--bg-card); border: 1px solid var(--border-color); border-radius: 16px; overflow: hidden; margin-bottom: 18px; }
    .po-card-hd { padding: 14px 16px; border-bottom: 1px solid var(--border-color); font-size: 14px; font-weight: 800; color: var(--text-primary); }

    .po-audit { padding: 6px 16px 14px; }
    .po-au { display: flex; align-items: center; gap: 9px; padding: 8px 0; font-size: 12.5px; color: var(--text-secondary); border-bottom: 1px dashed var(--border-color); }
    .po-au:last-child { border-bottom: none; }
    .po-au .ck { width: 18px; height: 18px; border-radius: 50%; flex-shrink: 0; display: flex; align-items: center; justify-content: center; font-size: 10px; color: #fff; }
    .po-au.yes .ck { background: #16a34a; } .po-au.no .ck { background: var(--border-color); color: var(--text-muted); }
    .po-au.no { color: var(--text-muted); }

    .po-rec { display: flex; align-items: flex-start; gap: 12px; padding: 13px 16px; border-bottom: 1px solid var(--border-color); }
    .po-rec:last-child { border-bottom: none; }
    .po-rec-main { flex: 1; min-width: 0; } .po-rec-main h5 { font-size: 13.5px; font-weight: 800; color: var(--text-primary); } .po-rec-main p { font-size: 12px; color: var(--text-muted); margin-top: 3px; line-height: 1.45; }
    .po-imp { font-size: 10px; font-weight: 800; color: #16a34a; background: rgba(22,163,74,.1); padding: 3px 9px; border-radius: 999px; white-space: nowrap; }
    .po-pri { font-size: 9.5px; font-weight: 800; padding: 2px 7px; border-radius: 999px; }
    .po-pri.High { background: rgba(220,38,38,.12); color: #dc2626; } .po-pri.Medium { background: rgba(217,119,6,.14); color: #d97706; }
    .po-fix { border: none; border-radius: 8px; padding: 7px 13px; font-size: 11.5px; font-weight: 800; color: #fff; background: linear-gradient(135deg, var(--po), var(--po-strong)); cursor: pointer; white-space: nowrap; }

    .po-gal { display: grid; grid-template-columns: repeat(3, 1fr); gap: 10px; padding: 14px 16px; }
    .po-gi { position: relative; border-radius: 10px; overflow: hidden; }
    .po-gi img { width: 100%; height: 86px; object-fit: cover; display: block; }
    .po-gscore { position: absolute; right: 6px; top: 6px; font-size: 10px; font-weight: 800; color: #fff; padding: 2px 7px; border-radius: 999px; }

    .po-bench { padding: 14px 16px; }
    .po-bn { margin-bottom: 12px; } .po-bn:last-child { margin-bottom: 0; }
    .po-bn-top { display: flex; justify-content: space-between; font-size: 12px; margin-bottom: 5px; } .po-bn-top span { color: var(--text-secondary); font-weight: 700; } .po-bn-top b { color: var(--text-primary); font-weight: 800; }
    .po-bbar { height: 8px; border-radius: 999px; background: var(--border-color); overflow: hidden; } .po-bbar > i { display: block; height: 100%; border-radius: 999px; background: var(--border-color); } .po-bn.me .po-bbar > i { background: linear-gradient(90deg, var(--po), var(--po-strong)); }

    .po-metrics { display: grid; grid-template-columns: repeat(4, 1fr); gap: 12px; }
    .po-m { background: var(--bg-card); border: 1px solid var(--border-color); border-radius: 14px; padding: 13px 15px; } .po-m b { font-size: 19px; font-weight: 800; color: var(--text-primary); } .po-m .l { font-size: 11.5px; color: var(--text-muted); margin-top: 3px; } .po-m .s { font-size: 10.5px; color: #16a34a; font-weight: 700; margin-top: 2px; }

    @media (max-width: 1000px) { .po-grid { grid-template-columns: minmax(0,1fr); } .po-stats, .po-metrics { grid-template-columns: 1fr 1fr; } }

    /* Interactive optimizer */
    .po-tool { background: var(--bg-card); border: 1px solid var(--border-color); border-radius: 16px; padding: 18px; margin-bottom: 18px; }
    .po-tool h3 { font-size: 14.5px; font-weight: 800; color: var(--text-primary); margin-bottom: 4px; }
    .po-tool .sub { font-size: 12px; color: var(--text-muted); margin-bottom: 16px; }
    .po-form { display: grid; grid-template-columns: repeat(3, 1fr); gap: 14px; }
    .po-form .full { grid-column: 1 / -1; }
    .po-lbl { display: block; font-size: 12px; font-weight: 700; color: var(--text-secondary); margin-bottom: 6px; }
    .po-in { width: 100%; padding: 10px 12px; background: var(--bg-primary, var(--bg-card)); border: 1px solid var(--border-color); border-radius: 9px; color: var(--text-primary); font-size: 13.5px; font-family: inherit; }
    .po-in:focus { outline: none; border-color: var(--po); box-shadow: 0 0 0 3px rgba(99,102,241,.15); }
    .po-check { display: flex; align-items: center; gap: 8px; font-size: 13px; color: var(--text-secondary); font-weight: 700; padding-top: 24px; }
    .po-go { border: none; border-radius: 10px; padding: 11px 20px; font-size: 13px; font-weight: 800; color: #fff; background: linear-gradient(135deg, var(--po), var(--po-strong)); cursor: pointer; }
    .po-err { display: none; margin-top: 14px; padding: 10px 14px; background: rgba(220,38,38,.1); border: 1px solid rgba(220,38,38,.3); color: #f87171; border-radius: 9px; font-size: 12.5px; }
    .po-err.open { display: block; }
    .po-loading { display: none; text-align: center; padding: 26px; color: var(--text-muted); font-size: 12.5px; }
    .po-loading.open { display: block; }
    .po-spin { width: 40px; height: 40px; border: 3px solid rgba(99,102,241,.2); border-top-color: var(--po); border-radius: 50%; margin: 0 auto 12px; animation: poSpin .8s linear infinite; }
    @keyframes poSpin { to { transform: rotate(360deg); } }
    .po-out { display: none; margin-top: 16px; }
    .po-out.open { display: block; animation: poFade .3s ease; }
    @keyframes poFade { from { opacity: 0; transform: translateY(8px); } to { opacity: 1; transform: translateY(0); } }
    .po-score-row { display: flex; align-items: center; gap: 16px; margin-bottom: 14px; }
    .po-score-num { font-size: 40px; font-weight: 800; color: var(--text-primary); line-height: 1; }
    .po-score-grade { font-size: 22px; font-weight: 800; color: #fff; background: linear-gradient(135deg, var(--po), var(--po-strong)); border-radius: 12px; width: 52px; height: 52px; display: flex; align-items: center; justify-content: center; }
    .po-out-sum { font-size: 12.5px; color: var(--text-secondary); line-height: 1.55; flex: 1; }
    .po-factors { display: grid; grid-template-columns: repeat(2, 1fr); gap: 10px; margin-bottom: 14px; }
    .po-fac { background: var(--bg-primary, var(--bg-card)); border: 1px solid var(--border-color); border-radius: 10px; padding: 11px 13px; }
    .po-fac-top { display: flex; justify-content: space-between; font-size: 12.5px; font-weight: 800; color: var(--text-primary); margin-bottom: 6px; }
    .po-fac-top .pts.good { color: #16a34a; } .po-fac-top .pts.warn { color: #d97706; }
    .po-fac-bar { height: 6px; border-radius: 999px; background: var(--border-color); overflow: hidden; margin-bottom: 5px; } .po-fac-bar > i { display: block; height: 100%; border-radius: 999px; }
    .po-fac-bar > i.good { background: #16a34a; } .po-fac-bar > i.warn { background: #d97706; }
    .po-fac-det { font-size: 11px; color: var(--text-muted); }
    .po-out-acts { list-style: none; margin: 0; padding: 0; }
    .po-out-acts li { font-size: 12.5px; color: var(--text-secondary); line-height: 1.5; padding: 8px 0 8px 24px; position: relative; border-bottom: 1px dashed var(--border-color); }
    .po-out-acts li:last-child { border-bottom: none; } .po-out-acts li::before { content: '🚀'; position: absolute; left: 0; top: 7px; }
    @media (max-width: 700px) { .po-form, .po-factors { grid-template-columns: 1fr; } .po-check { padding-top: 0; } }
</style>
@endpush

@section('content')
@php
    $level = $level ?? 'maximum';
    $isManual = $level === 'manual'; $isSemi = $level === 'semi'; $isMax = $level === 'maximum';
    $lvlMeta = [
        'manual'  => ['Do It Myself', '#64748b', 'Score your own profile — enter your details and see your rating, no AI suggestions.'],
        'semi'    => ['Help Me Plan', '#2563eb', 'AI scores your profile and suggests prioritised improvements you can act on.'],
        'maximum' => ['Coordinate It For Me', '#16a34a', 'AI audits your whole profile, benchmarks it and hands you a full improvement plan.'],
    ];
    [$lvlLabel, $lvlColor, $lvlDesc] = $lvlMeta[$level] ?? $lvlMeta['maximum'];
@endphp
<div class="po" data-level="{{ $level }}">

    {{-- Membership-level banner --}}
    <div style="display:flex;align-items:center;gap:12px;flex-wrap:wrap;background:var(--bg-card);border:1px solid var(--border-color);border-left:4px solid {{ $lvlColor }};border-radius:12px;padding:12px 16px;margin-bottom:16px;">
        <span style="font-size:10.5px;font-weight:800;letter-spacing:.4px;text-transform:uppercase;color:#fff;background:{{ $lvlColor }};padding:4px 11px;border-radius:999px;">{{ $lvlLabel }}</span>
        <span style="font-size:12.5px;color:var(--text-secondary);">{{ $lvlDesc }}</span>
        @unless($isMax)<a href="{{ Route::has('membership.plans') ? route('membership.plans') : url('/#pricing') }}" style="margin-left:auto;font-size:12px;font-weight:700;color:var(--po,#2563eb);text-decoration:none;">Upgrade for more →</a>@endunless
    </div>

    {{-- Interactive portfolio optimizer --}}
    <div class="po-tool">
        <h3>🔎 Score Your Profile</h3>
        <div class="sub">{{ $isManual ? 'Enter your profile details to score your profile yourself — no AI suggestions.' : ($isSemi ? 'Enter your profile details for an estimated score plus prioritised AI suggestions.' : 'Enter your current profile details for an estimated score and prioritised improvement suggestions.') }}</div>
        <form id="poForm" class="po-form">
            <div>
                <label class="po-lbl">Portfolio Photos</label>
                <input type="number" name="num_photos" class="po-in" min="0" max="1000" step="1" required placeholder="e.g. 12">
            </div>
            <div>
                <label class="po-lbl">Client Reviews</label>
                <input type="number" name="num_reviews" class="po-in" min="0" step="1" required placeholder="e.g. 8">
            </div>
            <div>
                <label class="po-lbl">Average Rating (0–5)</label>
                <input type="number" name="avg_rating" class="po-in" min="0" max="5" step="0.1" required placeholder="e.g. 4.8">
            </div>
            <div>
                <label class="po-lbl">Avg Response Time (hours)</label>
                <input type="number" name="response_hours" class="po-in" min="0" step="0.5" required placeholder="e.g. 3">
            </div>
            <div>
                <label class="po-lbl">Categories Listed</label>
                <input type="number" name="categories_listed" class="po-in" min="0" step="1" required placeholder="e.g. 2">
            </div>
            <div>
                <label class="po-check"><input type="checkbox" name="has_video" value="1"> Has a highlight video</label>
            </div>
            <div class="full">
                <button type="submit" class="po-go" id="poSubmit">{{ $isManual ? '🧮 Score My Profile' : ($isSemi ? '✨ Score + suggest' : '🔎 Analyze My Profile') }}</button>
            </div>
        </form>

        <div class="po-err" id="poError"></div>

        <div class="po-loading" id="poLoading">
            <div class="po-spin"></div>
            Scoring your profile...
        </div>

        <div class="po-out" id="poOut">
            <div class="po-score-row">
                <div class="po-score-num" id="poScore"></div>
                <div class="po-score-grade" id="poGrade"></div>
                <div class="po-out-sum" id="poSum"></div>
            </div>
            <div class="po-factors" id="poFactors"></div>
            <ul class="po-out-acts" id="poActs"></ul>
        </div>
    </div>

    @if($isMax)
    {{-- Coordinate It For Me — full auto audit dashboard --}}
    <div class="po-stats">
        @foreach($stats as [$lbl, $val, $tone])
            <div class="po-stat"><b>{{ $val }}</b><div class="l">{{ $lbl }}</div></div>
        @endforeach
    </div>

    <div class="po-grid">
        {{-- Audit --}}
        <div class="po-card">
            <div class="po-card-hd">✅ Portfolio Audit</div>
            <div class="po-audit">
                @foreach($audit as [$item, $done])
                    <div class="po-au {{ $done ? 'yes' : 'no' }}"><span class="ck">{{ $done ? '✓' : '○' }}</span> {{ $item }}</div>
                @endforeach
            </div>
        </div>

        {{-- Recommendations + gallery + benchmark --}}
        <div>
            <div class="po-card">
                <div class="po-card-hd">🚀 Top Recommendations</div>
                @foreach($recommendations as [$title, $desc, $pri, $impact])
                    <div class="po-rec">
                        <div class="po-rec-main">
                            <h5>{{ $title }} <span class="po-pri {{ $pri }}">{{ $pri }}</span></h5>
                            <p>{{ $desc }}</p>
                        </div>
                        <span class="po-imp">{{ $impact }}</span>
                        <button class="po-fix">Fix</button>
                    </div>
                @endforeach
            </div>

            <div class="po-card">
                <div class="po-card-hd">🖼 Gallery Optimizer</div>
                <div class="po-gal">
                    @foreach($gallery as [$img, $score])
                        @php $sc = $score >= 85 ? '#16a34a' : ($score >= 75 ? '#d97706' : '#dc2626'); @endphp
                        <div class="po-gi">
                            <span class="po-gscore" style="background: {{ $sc }};">{{ $score }}</span>
                            <img src="https://images.unsplash.com/{{ $img }}?w=240&q=65&auto=format&fit=crop" alt="" loading="lazy">
                        </div>
                    @endforeach
                </div>
            </div>

            <div class="po-card">
                <div class="po-card-hd">📊 Competitor Benchmark</div>
                <div class="po-bench">
                    @foreach($benchmark as [$label, $val, $me])
                        <div class="po-bn {{ $me ? 'me' : '' }}">
                            <div class="po-bn-top"><span>{{ $label }}</span><b>{{ $val }}</b></div>
                            <div class="po-bbar"><i style="width: {{ $val }}%"></i></div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>

    <div class="po-metrics">
        @foreach($metrics as [$lbl, $val, $sub])
            <div class="po-m"><b>{{ $val }}</b><div class="l">{{ $lbl }}</div><div class="s">{{ $sub }}</div></div>
        @endforeach
    </div>
    @endif
</div>

@push('scripts')
<script>
(function () {
    const form = document.getElementById('poForm');
    if (!form) return;
    const LEVEL = document.querySelector('.po')?.dataset.level || 'maximum';
    const submit = document.getElementById('poSubmit');
    const loading = document.getElementById('poLoading');
    const out = document.getElementById('poOut');
    const errEl = document.getElementById('poError');
    const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

    form.addEventListener('submit', async function (e) {
        e.preventDefault();
        errEl.classList.remove('open');
        out.classList.remove('open');
        loading.classList.add('open');
        submit.disabled = true;

        const payload = Object.fromEntries(new FormData(form).entries());
        payload.has_video = form.querySelector('[name="has_video"]').checked ? 1 : 0;

        // Do It Myself — score locally with a mirrored rubric, no AI actions.
        if (LEVEL === 'manual') {
            loading.classList.remove('open');
            submit.disabled = false;
            const local = computeLocal(payload);
            if (!local) {
                errEl.textContent = 'Please fill in all fields with valid numbers.';
                errEl.classList.add('open');
                return;
            }
            render(local);
            const acts = document.getElementById('poActs');
            acts.innerHTML = ''; acts.style.display = 'none';   // no AI suggestions at this level
            out.classList.add('open');
            out.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
            return;
        }

        try {
            const r = await fetch('{{ route("ai-tools.portfolio-optimizer.compute") }}', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' },
                credentials: 'same-origin',
                body: JSON.stringify(payload),
            });
            const data = await r.json();
            loading.classList.remove('open');
            submit.disabled = false;

            if (!data.success) {
                errEl.textContent = data.message || 'Could not analyze the profile.';
                errEl.classList.add('open');
                return;
            }
            render(data.result);
            out.classList.add('open');
            out.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
        } catch (err) {
            loading.classList.remove('open');
            submit.disabled = false;
            errEl.textContent = 'Network error. Please try again.';
            errEl.classList.add('open');
        }
    });

    // Client-side scoring mirroring the server rubric (Do It Myself — no AI actions).
    function computeLocal(p) {
        const photos = parseInt(p.num_photos, 10), reviews = parseInt(p.num_reviews, 10);
        const rating = parseFloat(p.avg_rating), resp = parseFloat(p.response_hours), cats = parseInt(p.categories_listed, 10);
        const hasVideo = String(p.has_video) === '1';
        if ([photos, reviews, cats].some(n => isNaN(n)) || isNaN(rating) || isNaN(resp)) return null;
        const r = Math.round;
        const photoPts = r(Math.min(1, photos / 15) * 25);
        const videoPts = hasVideo ? 15 : 0;
        const reviewPts = r(Math.min(1, reviews / 20) * 20);
        const ratingPts = r(Math.min(1, rating / 5) * 20);
        const respPts = resp <= 2 ? 10 : (resp >= 48 ? 0 : r((1 - ((resp - 2) / 46)) * 10));
        const catPts = r(Math.min(1, cats / 3) * 10);
        const score = Math.max(0, Math.min(100, photoPts + videoPts + reviewPts + ratingPts + respPts + catPts));
        const grade = score >= 85 ? 'A' : (score >= 70 ? 'B' : (score >= 55 ? 'C' : 'D'));
        const f = (label, points, max, detail) => ({ label, points, max, status: points >= max ? 'good' : 'warn', detail });
        return {
            score, grade, actions: [],
            factors: [
                f('Portfolio photos', photoPts, 25, photos + ' photo' + (photos === 1 ? '' : 's') + ' (15+ recommended)'),
                f('Highlight video', videoPts, 15, hasVideo ? 'Video added' : 'No video'),
                f('Client reviews', reviewPts, 20, reviews + ' review' + (reviews === 1 ? '' : 's') + ' (20+ recommended)'),
                f('Average rating', ratingPts, 20, rating.toFixed(1) + ' / 5.0'),
                f('Responsiveness', respPts, 10, 'Replies in ~' + resp + 'h (2h or less is ideal)'),
                f('Categories listed', catPts, 10, cats + ' categor' + (cats === 1 ? 'y' : 'ies') + ' (3+ recommended)'),
            ],
            summary: 'Your profile scores ' + score + '/100 (grade ' + grade + ') on your own worksheet. Areas below full points are where you have the most room to improve.',
        };
    }

    function render(res) {
        const acts0 = document.getElementById('poActs'); if (acts0) acts0.style.display = '';
        document.getElementById('poScore').textContent = res.score + '/100';
        document.getElementById('poGrade').textContent = res.grade;
        document.getElementById('poSum').textContent = res.summary || '';

        const fac = document.getElementById('poFactors');
        fac.innerHTML = '';
        (res.factors || []).forEach(f => {
            const pct = f.max > 0 ? Math.round((f.points / f.max) * 100) : 0;
            const div = document.createElement('div');
            div.className = 'po-fac';
            div.innerHTML = `
                <div class="po-fac-top"><span>${esc(f.label)}</span><span class="pts ${f.status}">${f.points}/${f.max}</span></div>
                <div class="po-fac-bar"><i class="${f.status}" style="width:${pct}%"></i></div>
                <div class="po-fac-det">${esc(f.detail || '')}</div>`;
            fac.appendChild(div);
        });

        const acts = document.getElementById('poActs');
        acts.innerHTML = '';
        if ((res.actions || []).length) {
            res.actions.forEach(a => {
                const li = document.createElement('li');
                li.textContent = a;
                acts.appendChild(li);
            });
        } else {
            const li = document.createElement('li');
            li.textContent = 'No further actions — every scored area is already at full points.';
            acts.appendChild(li);
        }
    }
    function esc(s) { return String(s || '').replace(/[&<>"']/g, c => ({ '&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;' }[c])); }
})();
</script>
@endpush
@endsection
