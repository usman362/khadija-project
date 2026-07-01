@extends($aiLayout ?? 'layouts.professional')

@section('title', 'AI Bid Optimizer')
@section('page-title', 'AI Bid Optimizer')
@section('page-subtitle', 'The best bid for you — balanced against your margin')

@push('styles')
<style>
    .bo { --bo: #2563eb; }
    .bo-stats { display: grid; grid-template-columns: repeat(4, 1fr); gap: 12px; margin-bottom: 18px; }
    .bo-stat { background: var(--bg-card); border: 1px solid var(--border-color); border-radius: 14px; padding: 14px 16px; }
    .bo-stat b { display: block; font-size: 23px; font-weight: 800; color: var(--text-primary); line-height: 1; } .bo-stat.good b { color: #16a34a; } .bo-stat .l { font-size: 11.5px; color: var(--text-muted); margin-top: 6px; }
    .bo-grid { display: grid; grid-template-columns: minmax(0,1fr) 320px; gap: 18px; align-items: start; }
    .bo-card { background: var(--bg-card); border: 1px solid var(--border-color); border-radius: 16px; padding: 16px; }
    .bo-card h3 { font-size: 14.5px; font-weight: 800; color: var(--text-primary); margin-bottom: 14px; }
    .bo-kv { display: flex; justify-content: space-between; font-size: 13px; padding: 9px 0; border-bottom: 1px dashed var(--border-color); } .bo-kv:last-of-type { border-bottom: none; } .bo-kv span { color: var(--text-muted); } .bo-kv b { color: var(--text-primary); font-weight: 800; }
    .bo-toggle { display: flex; gap: 8px; margin: 14px 0; }
    .bo-tg { flex: 1; text-align: center; font-size: 12px; font-weight: 700; border: 1px solid var(--border-color); border-radius: 9px; padding: 9px; cursor: pointer; color: var(--text-secondary); background: var(--bg-card); }
    .bo-tg.on { background: var(--bo); border-color: var(--bo); color: #fff; }
    .bo-btn { width: 100%; border: none; border-radius: 11px; padding: 12px; font-size: 14px; font-weight: 800; color: #fff; background: linear-gradient(135deg, var(--bo), #1d4ed8); cursor: pointer; }
    .bo-ring { text-align: center; padding: 6px 0 12px; }
    .bo-ring .num { font-size: 34px; font-weight: 800; color: var(--text-primary); } .bo-ring .sub { font-size: 12px; color: #16a34a; font-weight: 800; }
    .bo-scale { display: flex; height: 8px; border-radius: 999px; overflow: hidden; margin: 14px 0 6px; } .bo-scale i { height: 100%; } .bo-scale .l { background: #d97706; } .bo-scale .r { background: #dc2626; } .bo-scale .m { background: #16a34a; }
    .bo-row { display: flex; justify-content: space-between; font-size: 11px; color: var(--text-muted); }
    .bo-strat { font-size: 12px; color: var(--text-secondary); line-height: 1.5; background: rgba(37,99,235,.07); border: 1px dashed var(--bo); border-radius: 10px; padding: 11px; margin-top: 14px; }
    @media (max-width: 1000px) { .bo-grid { grid-template-columns: minmax(0,1fr); } .bo-stats { grid-template-columns: 1fr 1fr; } }

    /* Interactive tool */
    .bo-tool { background: var(--bg-card); border: 1px solid var(--border-color); border-radius: 16px; padding: 18px; margin-bottom: 18px; }
    .bo-tool h3 { font-size: 14.5px; font-weight: 800; color: var(--text-primary); margin-bottom: 4px; }
    .bo-tool .sub { font-size: 12px; color: var(--text-muted); margin-bottom: 16px; }
    .bo-form { display: grid; grid-template-columns: repeat(2, 1fr); gap: 14px; }
    .bo-form .full { grid-column: 1 / -1; }
    .bo-lbl { display: block; font-size: 12px; font-weight: 700; color: var(--text-secondary); margin-bottom: 6px; }
    .bo-in { width: 100%; padding: 10px 12px; background: var(--bg-primary, var(--bg-card)); border: 1px solid var(--border-color); border-radius: 9px; color: var(--text-primary); font-size: 13.5px; font-family: inherit; }
    .bo-in:focus { outline: none; border-color: var(--bo); box-shadow: 0 0 0 3px rgba(37,99,235,.15); }
    .bo-err { display: none; margin: 14px 0 0; padding: 10px 14px; background: rgba(220,38,38,.1); border: 1px solid rgba(220,38,38,.3); color: #f87171; border-radius: 9px; font-size: 12.5px; }
    .bo-err.open { display: block; }
    .bo-loading { display: none; text-align: center; padding: 26px; color: var(--text-muted); font-size: 12.5px; }
    .bo-loading.open { display: block; }
    .bo-spin { width: 40px; height: 40px; border: 3px solid rgba(37,99,235,.2); border-top-color: var(--bo); border-radius: 50%; margin: 0 auto 12px; animation: boSpin .8s linear infinite; }
    @keyframes boSpin { to { transform: rotate(360deg); } }
    .bo-out { display: none; margin-top: 16px; }
    .bo-out.open { display: block; animation: boFade .3s ease; }
    @keyframes boFade { from { opacity: 0; transform: translateY(8px); } to { opacity: 1; transform: translateY(0); } }
    .bo-out .big { font-size: 32px; font-weight: 800; color: var(--text-primary); }
    .bo-out .meta { font-size: 12.5px; color: #16a34a; font-weight: 800; margin-top: 2px; }
    .bo-sum { font-size: 12.5px; color: var(--text-secondary); line-height: 1.55; background: rgba(37,99,235,.07); border: 1px dashed var(--bo); border-radius: 10px; padding: 11px; margin: 12px 0; }
    .bo-tips { list-style: none; margin: 0; padding: 0; }
    .bo-tips li { font-size: 12.5px; color: var(--text-secondary); line-height: 1.5; padding: 7px 0 7px 22px; position: relative; border-bottom: 1px dashed var(--border-color); }
    .bo-tips li:last-child { border-bottom: none; } .bo-tips li::before { content: '💡'; position: absolute; left: 0; top: 6px; }
    @media (max-width: 640px) { .bo-form { grid-template-columns: 1fr; } }
</style>
@endpush

@section('content')
<div class="bo">
    {{-- Interactive bid optimizer --}}
    <div class="bo-tool">
        <h3>⚡ Optimize a Bid</h3>
        <div class="sub">Enter the job details and get an estimated bid, range and win probability. Results are estimates to guide your decision.</div>
        <form id="boForm" class="bo-form">
            <div>
                <label class="bo-lbl">Client Budget ($)</label>
                <input type="number" name="gig_budget" class="bo-in" min="1" step="0.01" required placeholder="e.g. 2000">
            </div>
            <div>
                <label class="bo-lbl">Your Base Price ($)</label>
                <input type="number" name="your_base_price" class="bo-in" min="1" step="0.01" required placeholder="e.g. 1200">
            </div>
            <div>
                <label class="bo-lbl">Number of Competing Bids</label>
                <input type="number" name="num_competitors" class="bo-in" min="0" max="50" step="1" required placeholder="e.g. 7">
            </div>
            <div>
                <label class="bo-lbl">Turnaround</label>
                <select name="turnaround" class="bo-in">
                    <option value="standard">Standard</option>
                    <option value="rush">Rush</option>
                </select>
            </div>
            <div class="full">
                <button type="submit" class="bo-btn" id="boSubmit">⚡ Optimize My Bid</button>
            </div>
        </form>

        <div class="bo-err" id="boError"></div>

        <div class="bo-loading" id="boLoading">
            <div class="bo-spin"></div>
            Calculating your estimated bid...
        </div>

        <div class="bo-out" id="boOut">
            <div class="big" id="boBid"></div>
            <div class="meta" id="boMeta"></div>
            <div class="bo-sum" id="boSum"></div>
            <ul class="bo-tips" id="boTips"></ul>
        </div>
    </div>

    <div class="bo-stats">
        @foreach($stats as [$lbl, $val, $tone])<div class="bo-stat {{ $tone }}"><b>{{ $val }}</b><div class="l">{{ $lbl }}</div></div>@endforeach
    </div>
    <div class="bo-grid">
        <div class="bo-card">
            <h3>📄 The Gig You're Bidding On</h3>
            <div class="bo-kv"><span>Gig</span><b>{{ $gig['title'] }}</b></div>
            <div class="bo-kv"><span>Client Budget</span><b>{{ $gig['client_budget'] }}</b></div>
            <div class="bo-kv"><span>Event Date</span><b>{{ $gig['date'] }}</b></div>
            <div class="bo-kv"><span>Your Target Margin</span><b>{{ $gig['target_margin'] }}</b></div>
            <div class="bo-kv"><span>Urgency</span><b>{{ $gig['urgency'] }}</b></div>
            <div class="bo-toggle">
                <span class="bo-tg">Win the bid</span><span class="bo-tg">Protect margin</span><span class="bo-tg on">Balanced</span>
            </div>
            <button class="bo-btn">⚡ Optimize My Bid</button>
        </div>
        <div class="bo-card">
            <h3>🎯 Recommended Bid</h3>
            <div class="bo-ring"><div class="num">${{ number_format($bid['recommended']) }}</div><div class="sub">Great spot · {{ $bid['win'] }}% win · {{ $bid['margin'] }}% margin</div></div>
            <div class="bo-scale"><i class="l" style="width:33%"></i><i class="m" style="width:34%"></i><i class="r" style="width:33%"></i></div>
            <div class="bo-row"><span>{{ $bid['low']['label'] }} ${{ number_format($bid['low']['amount']) }}</span><span>{{ $bid['high']['label'] }} ${{ number_format($bid['high']['amount']) }}</span></div>
            <div class="bo-strat">💡 {{ $strategy }}</div>
        </div>
    </div>
</div>

@push('scripts')
<script>
(function () {
    const form = document.getElementById('boForm');
    if (!form) return;
    const submit = document.getElementById('boSubmit');
    const loading = document.getElementById('boLoading');
    const out = document.getElementById('boOut');
    const errEl = document.getElementById('boError');
    const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

    form.addEventListener('submit', async function (e) {
        e.preventDefault();
        errEl.classList.remove('open');
        out.classList.remove('open');
        loading.classList.add('open');
        submit.disabled = true;

        const payload = Object.fromEntries(new FormData(form).entries());

        try {
            const r = await fetch('{{ route("ai-tools.bid-optimizer.compute") }}', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' },
                credentials: 'same-origin',
                body: JSON.stringify(payload),
            });
            const data = await r.json();
            loading.classList.remove('open');
            submit.disabled = false;

            if (!data.success) {
                errEl.textContent = data.message || 'Could not optimize the bid.';
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

    function render(res) {
        document.getElementById('boBid').textContent = '$' + fmt(res.suggested_bid);
        document.getElementById('boMeta').textContent =
            'Range $' + fmt(res.bid_range.low) + '–$' + fmt(res.bid_range.high) +
            ' · ' + res.win_probability_pct + '% est. win · ~' + res.margin_pct + '% margin';
        document.getElementById('boSum').textContent = res.summary || '';
        const tips = document.getElementById('boTips');
        tips.innerHTML = '';
        (res.positioning || []).forEach(t => {
            const li = document.createElement('li');
            li.textContent = t;
            tips.appendChild(li);
        });
    }
    function fmt(n) { return Number(n).toLocaleString(undefined, { maximumFractionDigits: 0 }); }
})();
</script>
@endpush
@endsection
