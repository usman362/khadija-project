@extends('layouts.professional')

@section('title', 'Earnings')
@section('page-title', 'Earnings')

@push('styles')
<style>
    .cl-table {
        width: 100%;
        border-collapse: collapse;
    }
    .cl-table thead {
        border-bottom: 1px solid var(--border-color);
    }
    .cl-table th {
        padding: 12px 16px;
        text-align: left;
        font-size: 12px;
        font-weight: 600;
        color: var(--text-muted);
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }
    .cl-table td {
        padding: 16px;
        border-bottom: 1px solid var(--border-color);
        color: var(--text-secondary);
        font-size: 13px;
    }
    .cl-table tbody tr:hover {
        background: rgba(255,255,255,0.02);
    }
</style>
@endpush

@push('styles')
<style>
    .ern-break { margin-top: 6px; text-align: left; font-weight: 400; }
    .ern-break > summary { list-style: none; cursor: pointer; font-size: 10.5px; font-weight: 700;
        color: var(--accent-blue, #2563eb); text-align: right; }
    .ern-break > summary::-webkit-details-marker { display: none; }
    .ern-break > div { margin-top: 7px; background: var(--bg-page, var(--bg-card));
        border: 1px solid var(--border-color); border-radius: 9px; padding: 9px 11px; }
    .ern-break span { display: flex; justify-content: space-between; gap: 12px; font-size: 11.5px;
        color: var(--text-secondary, var(--text-muted)); padding: 3px 0; }
    .ern-break i { font-style: normal; font-weight: 700; color: var(--text-primary); }
    .ern-break .sum { border-top: 1px solid var(--border-color); margin-top: 4px; padding-top: 6px;
        font-weight: 800; color: var(--text-primary); }
    .ern-break .note { display: block; color: var(--text-muted); font-size: 10.5px; margin-top: 5px; }
</style>
@endpush

@section('content')
    {{-- Header --}}
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px;">
        <div>
            <h2 style="font-size: 24px; font-weight: 700; margin-bottom: 4px;">Earnings</h2>
        </div>
        <button class="cl-btn cl-btn-primary" disabled style="opacity: 0.5; cursor: not-allowed;">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
            Withdraw Funds
        </button>
    </div>

    {{-- Stat Cards --}}
    <div class="cl-grid cl-grid-4" style="margin-bottom: 24px;">
        <div class="cl-card">
            <div class="cl-stat-card">
                <div class="cl-stat-icon blue">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
                </div>
                <div>
                    <div class="cl-stat-label">Total Earnings</div>
                    <div class="cl-stat-value">${{ number_format($stats['earned'] ?? 0, 2) }}</div>
                </div>
            </div>
        </div>

        <div class="cl-card">
            <div class="cl-stat-card">
                <div class="cl-stat-icon green">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
                </div>
                <div>
                    <div class="cl-stat-label">Withdrawn</div>
                    <div class="cl-stat-value">${{ number_format($stats['withdrawn'] ?? 0, 2) }}</div>
                </div>
            </div>
        </div>

        <div class="cl-card">
            <div class="cl-stat-card">
                <div class="cl-stat-icon yellow">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                </div>
                <div>
                    <div class="cl-stat-label">Pending</div>
                    <div class="cl-stat-value">${{ number_format($stats['pending'] ?? 0, 2) }}</div>
                </div>
            </div>
        </div>

        <div class="cl-card">
            <div class="cl-stat-card">
                <div class="cl-stat-icon pink">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
                </div>
                <div>
                    <div class="cl-stat-label">Available Balance</div>
                    <div class="cl-stat-value">${{ number_format($stats['available'] ?? 0, 2) }}</div>
                </div>
            </div>
        </div>
    </div>

    {{-- What the tier costs, in this account's own money.

         The four totals above are what arrives after commission and said
         nothing about the rate that produced them. Every figure here is this
         professional's own gross through App\Support\Commission, so the band
         and the rows below cannot report different arithmetic. --}}
    <div class="cl-card" style="margin-bottom: 20px;">
        <div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(190px,1fr)); gap:18px; align-items:start;">
            <div>
                <div class="cl-stat-label">Your current tier</div>
                <div style="font-size:19px; font-weight:800; color:var(--text-primary); margin-top:3px;">
                    {{ $tier['label'] }} ({{ rtrim(rtrim(number_format($tier['rate'], 2), '0'), '.') }}%)
                </div>
                <div style="font-size:11.5px; color:var(--text-muted); margin-top:3px;">Taken at payout, on every completed booking.</div>
            </div>
            <div>
                <div class="cl-stat-label">Billed through this account</div>
                <div style="font-size:19px; font-weight:800; color:var(--text-primary); margin-top:3px;">${{ number_format($tier['gross'], 2) }}</div>
                <div style="font-size:11.5px; color:var(--text-muted); margin-top:3px;">Everything clients have been charged.</div>
            </div>
            <div>
                <div class="cl-stat-label">Platform fees</div>
                <div style="font-size:19px; font-weight:800; color:var(--bad-text, #b91c1c); margin-top:3px;">&minus;${{ number_format($tier['fees'], 2) }}</div>
                <div style="font-size:11.5px; color:var(--text-muted); margin-top:3px;">At your rate, on that amount.</div>
            </div>
            <div>
                <div class="cl-stat-label">Yours after fees</div>
                <div style="font-size:19px; font-weight:800; color:var(--ok-text, #047857); margin-top:3px;">${{ number_format($tier['net'], 2) }}</div>
                @if ($tier['saving'])
                    <div style="font-size:11.5px; color:var(--text-muted); margin-top:3px;">
                        On {{ $tier['saving']['label'] }} the same work would have kept ${{ number_format($tier['saving']['amount'], 2) }} more.
                    </div>
                @elseif ($tier['lowest'])
                    <div style="font-size:11.5px; color:var(--text-muted); margin-top:3px;">This is the lowest rate we charge.</div>
                @else
                    <div style="font-size:11.5px; color:var(--text-muted); margin-top:3px;">Fees start once a booking is completed and paid.</div>
                @endif
            </div>
        </div>

        {{-- The other tiers' rates only once there is money for them to be
             measured against. On an account with nothing billed, "Pro 3%"
             sits beside four zeros and reads like a figure of this
             professional's own. --}}
        @if ($tier['gross'] > 0)
        <div style="display:flex; gap:10px; flex-wrap:wrap; margin-top:16px; padding-top:14px; border-top:1px solid var(--border-color);">
            @foreach ($tier['ladder'] as $step)
                <span style="font-size:11.5px; font-weight:700; padding:5px 11px; border-radius:20px;
                             border:1px solid {{ $step['current'] ? 'var(--accent-blue, #2563eb)' : 'var(--border-color)' }};
                             color:{{ $step['current'] ? 'var(--accent-blue, #2563eb)' : 'var(--text-muted)' }};">
                    {{ $step['label'] }} {{ rtrim(rtrim(number_format($step['rate'], 2), '0'), '.') }}%{{ $step['current'] ? ' (you)' : '' }}
                </span>
            @endforeach
        </div>
        @endif
    </div>

    {{-- Search + Filter --}}
    <div class="cl-card" style="margin-bottom: 20px;">
        <form method="GET" action="{{ route('professional.earnings.index') }}" style="display: flex; gap: 12px; align-items: flex-end; flex-wrap: wrap;">
            <div style="flex: 1; min-width: 200px;">
                <div class="cl-search-box">
                    <svg class="search-icon" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                    <input type="text" name="search" placeholder="Search earnings..." value="{{ request('search') }}">
                </div>
            </div>
            <button type="submit" class="cl-btn cl-btn-primary cl-btn-sm">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                Filter
            </button>
        </form>
    </div>

    {{-- Each booking, with what actually arrives after commission. The gross is
         shown beside it so the two figures never look like a discrepancy. --}}
    <div class="cl-card">
        @if ($earnings->total() > 0)
            <div style="overflow-x: auto;">
                <table class="cl-table" style="width: 100%;">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Gig</th>
                            <th>Status</th>
                            <th style="text-align: right;">Client paid</th>
                            <th style="text-align: right;">You receive</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($earnings as $row)
                            <tr>
                                <td>{{ $row['date'] }}</td>
                                <td>{{ $row['description'] }}</td>
                                <td><span class="cl-badge">{{ $row['status'] }}</span></td>
                                <td style="text-align: right;">${{ number_format($row['gross'], 2) }}</td>
                                <td style="text-align: right; font-weight: 600;">
                                    ${{ number_format($row['net'], 2) }}
                                    {{-- The arithmetic, on the row it belongs to.
                                         Two figures side by side with nothing
                                         between them read as a discrepancy. --}}
                                    <details class="ern-break">
                                        <summary>Breakdown</summary>
                                        <div>
                                            <span>Client paid<i>${{ number_format($row['gross'], 2) }}</i></span>
                                            <span>Platform fee at {{ rtrim(rtrim(number_format($row['rate'], 2), '0'), '.') }}%<i>&minus;${{ number_format($row['fee'], 2) }}</i></span>
                                            <span class="sum">Yours<i>${{ number_format($row['net'], 2) }}</i></span>
                                            <span class="note">{{ $row['paid'] ? 'Counted in your earnings.' : 'Not counted until the booking is completed.' }}</span>
                                        </div>
                                    </details>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            @if ($earnings->hasPages())
                <div style="margin-top: 16px;">{{ $earnings->links() }}</div>
            @endif
        @else
            <div class="cl-empty">
                <div class="cl-empty-icon">
                    <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
                </div>
                <div class="cl-empty-title">
                    {{ $search !== '' ? 'Nothing matches that search' : 'No earnings recorded yet' }}
                </div>
                <div class="cl-empty-text">
                    {{ $search !== ''
                        ? 'Try a different gig or client name.'
                        : 'Complete gigs to start earning. Your earnings will appear here.' }}
                </div>
            </div>
        @endif
    </div>
@endsection
