@extends('layouts.influencer-portal')
@section('title', 'Payouts')

@push('styles')
<style>
    .po-head h1 { font-family: var(--ff); font-size: 24px; font-weight: 800; color: var(--ink); }
    .po-head p { color: var(--muted); font-size: 14px; margin-top: 4px; }

    .po-stats { display: grid; grid-template-columns: repeat(3, 1fr); gap: 14px; margin: 18px 0; }
    .po-stat { background: var(--card); border: 1px solid var(--line); border-radius: var(--radius); padding: 16px 18px; box-shadow: var(--shadow); }
    .po-stat .l { font-size: 12px; font-weight: 600; color: var(--muted); display: flex; align-items: center; gap: 7px; }
    .po-stat .l svg { width: 15px; height: 15px; }
    .po-stat .v { font-family: var(--ff); font-size: 24px; font-weight: 800; color: var(--ink); margin-top: 6px; }
    .po-stat .v.green { color: var(--green-dark); }

    .po-grid { display: grid; grid-template-columns: minmax(0,0.9fr) minmax(0,1.1fr); gap: 18px; align-items: start; }
    @media (max-width: 900px) { .po-grid { grid-template-columns: 1fr; } .po-stats { grid-template-columns: 1fr; } }

    .po-card { background: var(--card); border: 1px solid var(--line); border-radius: var(--radius); box-shadow: var(--shadow); }
    .po-card-head { padding: 16px 18px; border-bottom: 1px solid var(--line); }
    .po-card-head h3 { font-family: var(--ff); font-size: 15px; font-weight: 700; color: var(--ink); }
    .po-card-head p { font-size: 12.5px; color: var(--muted); margin-top: 3px; }
    .po-card-body { padding: 18px; }

    .po-field { margin-bottom: 14px; }
    .po-label { display: block; font-family: var(--ff); font-size: 13px; font-weight: 700; color: var(--ink); margin-bottom: 6px; }
    .po-input, .po-select, .po-area { width: 100%; padding: 11px 13px; border: 1.5px solid var(--line); border-radius: 10px; font-family: var(--ff-body); font-size: 14px; color: var(--ink); background: #fff; }
    .po-area { resize: vertical; min-height: 64px; }
    .po-input:focus, .po-select:focus, .po-area:focus { outline: none; border-color: var(--green); box-shadow: 0 0 0 3px color-mix(in srgb, var(--green) 15%, transparent); }
    .po-btn { width: 100%; padding: 12px; border: none; border-radius: 11px; background: var(--green); color: #fff; font-family: var(--ff); font-size: 14.5px; font-weight: 700; cursor: pointer; transition: background .15s; }
    .po-btn:hover { background: var(--green-dark); }
    .po-hint { font-size: 12.5px; color: var(--muted); margin-bottom: 14px; }
    .po-hint b { color: var(--green-dark); }

    .po-table { width: 100%; border-collapse: collapse; }
    .po-table th { text-align: left; font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: .3px; color: var(--muted); padding: 10px 18px; border-bottom: 1px solid var(--line); }
    .po-table td { padding: 13px 18px; border-bottom: 1px solid var(--line); font-size: 13.5px; color: var(--ink); }
    .po-table tr:last-child td { border-bottom: none; }
    .po-amt { font-family: var(--ff); font-weight: 800; }
    .po-pill { display: inline-block; font-size: 11px; font-weight: 700; padding: 3px 10px; border-radius: 20px; text-transform: capitalize; }
    .po-pill.paid { background: #dcfce7; color: #15803d; }
    .po-pill.pending { background: #fef9c3; color: #a16207; }
    .po-pill.approved { background: #e0f2fe; color: #0369a1; }
    .po-pill.rejected { background: #fee2e2; color: #b91c1c; }
    .po-empty { text-align: center; color: var(--muted); font-size: 13.5px; padding: 30px; }

    .po-alert { padding: 12px 15px; border-radius: 11px; font-size: 13.5px; margin-bottom: 16px; }
    .po-alert.ok { background: #ecfdf3; border: 1px solid #c9ecd4; color: #15803d; }
    .po-alert.err { background: #fef2f2; border: 1px solid #fecaca; color: #b91c1c; }
</style>
@endpush

@section('content')
<div class="ipx-breadcrumb"><a href="{{ route('influencer.dashboard') }}">Dashboard</a> <span class="sep">›</span> Payouts</div>
<div class="po-head">
    <h1>Payouts</h1>
    <p>Request a withdrawal and track the status of your past payouts.</p>
</div>

@if(session('status'))<div class="po-alert ok">{{ session('status') }}</div>@endif
@if(session('error'))<div class="po-alert err">{{ session('error') }}</div>@endif

<div class="po-stats">
    <div class="po-stat">
        <div class="l"><svg viewBox="0 0 24 24" fill="none" stroke="#16a34a" stroke-width="2"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg> Available Balance</div>
        <div class="v green">${{ number_format($influencer->available_balance, 2) }}</div>
    </div>
    <div class="po-stat">
        <div class="l"><svg viewBox="0 0 24 24" fill="none" stroke="#7a879c" stroke-width="2"><path d="M20 12V8H6a2 2 0 0 1-2-2c0-1.1.9-2 2-2h12v4"/><path d="M4 6v12a2 2 0 0 0 2 2h14v-4"/><path d="M18 12a2 2 0 0 0 0 4h4v-4z"/></svg> Total Paid Out</div>
        <div class="v">${{ number_format($influencer->paid_out, 2) }}</div>
    </div>
    <div class="po-stat">
        <div class="l"><svg viewBox="0 0 24 24" fill="none" stroke="#7a879c" stroke-width="2"><line x1="19" y1="5" x2="5" y2="19"/><circle cx="6.5" cy="6.5" r="2.5"/><circle cx="17.5" cy="17.5" r="2.5"/></svg> Total Earned</div>
        <div class="v">${{ number_format($influencer->total_earnings, 2) }}</div>
    </div>
</div>

<div class="po-grid">
    {{-- Request a payout --}}
    <div class="po-card">
        <div class="po-card-head"><h3>Request a Payout</h3><p>Withdraw your available earnings.</p></div>
        <div class="po-card-body">
            <div class="po-hint">Available: <b>${{ number_format($influencer->available_balance, 2) }}</b> &middot; Minimum payout: ${{ number_format($minPayout, 2) }}</div>
            <form method="POST" action="{{ route('influencer.dashboard.payouts.request') }}">
                @csrf
                <div class="po-field">
                    <label class="po-label">Amount</label>
                    <input type="number" step="0.01" name="amount" required class="po-input" min="{{ $minPayout }}" max="{{ $influencer->available_balance }}" placeholder="0.00">
                </div>
                <div class="po-field">
                    <label class="po-label">Payout Method</label>
                    <select name="payout_method" class="po-select">
                        <option value="paypal">PayPal</option>
                        <option value="bank">Bank Transfer</option>
                        <option value="other">Other</option>
                    </select>
                </div>
                <div class="po-field">
                    <label class="po-label">Account (email / details)</label>
                    <input type="text" name="payout_account" class="po-input" placeholder="you@example.com">
                </div>
                <div class="po-field">
                    <label class="po-label">Notes <span style="color:var(--muted);font-weight:400;">(optional)</span></label>
                    <textarea name="user_notes" class="po-area" rows="2" placeholder="Anything we should know…"></textarea>
                </div>
                <button class="po-btn" type="submit">Submit Request</button>
            </form>
        </div>
    </div>

    {{-- History --}}
    <div class="po-card">
        <div class="po-card-head"><h3>Payout History</h3><p>Your requests and their status.</p></div>
        <table class="po-table">
            <thead><tr><th>Amount</th><th>Method</th><th>Status</th><th>Requested</th></tr></thead>
            <tbody>
            @forelse($payouts as $p)
                <tr>
                    <td class="po-amt">${{ number_format($p->amount, 2) }}</td>
                    <td style="text-transform:capitalize;">{{ $p->payout_method ?? '—' }}</td>
                    <td><span class="po-pill {{ strtolower($p->status->value) }}">{{ $p->status->value }}</span></td>
                    <td style="color:var(--muted);">{{ $p->created_at->format('M d, Y') }}</td>
                </tr>
            @empty
                <tr><td colspan="4" class="po-empty">No payout requests yet — request your first withdrawal on the left.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>

@if($payouts->hasPages())
    <div style="margin-top:16px;">{{ $payouts->links() }}</div>
@endif
@endsection
