@extends('layouts.professional')

@section('title', 'My Bids')
@section('page-title', 'My Bids')
@section('page-subtitle', 'Every bid you\'ve placed — sealed by default, reveal any time')

{{-- Professional — My Bids. The pro's own sealed bids across all gigs, with a
     per-bid seal/reveal opt-in. Amounts are private (only the pro + the client
     see them) unless the pro chooses to make a bid public. --}}

@push('styles')
<style>
    .mb-wrap { max-width: 880px; margin: 0 auto; }
    .mb-top { display: flex; align-items: center; justify-content: space-between; gap: 12px; margin-bottom: 18px; flex-wrap: wrap; }
    .mb-back { display: inline-flex; align-items: center; gap: 7px; border: 1px solid var(--border-color); background: var(--bg-card); border-radius: 999px; padding: 8px 16px; font-size: 13px; font-weight: 700; color: var(--text-secondary); text-decoration: none; }
    .mb-note { display: flex; gap: 9px; align-items: flex-start; background: #f5f3ff; border: 1px solid #ddd6fe; border-radius: 12px; padding: 12px 15px; font-size: 12.5px; color: #5b21b6; line-height: 1.5; margin-bottom: 18px; }
    .mb-flash { display: flex; align-items: center; gap: 8px; background: #ecfdf5; border: 1px solid #a7f3d0; color: #065f46; font-size: 13px; font-weight: 600; padding: 11px 16px; border-radius: 12px; margin-bottom: 16px; }

    .mb-card { display: grid; grid-template-columns: minmax(0,1fr) auto auto; gap: 16px; align-items: center; background: var(--bg-card); border: 1px solid var(--border-color); border-radius: 14px; padding: 15px 18px; margin-bottom: 12px; }
    .mb-title { font-size: 15px; font-weight: 800; color: var(--text-primary); }
    .mb-meta { font-size: 12px; color: var(--text-muted); margin-top: 3px; display: flex; flex-wrap: wrap; gap: 10px; }
    .mb-amt { text-align: right; }
    .mb-amt b { font-size: 18px; font-weight: 800; color: var(--text-primary); }
    .mb-amt span { display: block; font-size: 10px; font-weight: 700; letter-spacing: .3px; text-transform: uppercase; color: var(--text-muted); }
    .mb-badge { display: inline-flex; align-items: center; gap: 5px; font-size: 11px; font-weight: 800; padding: 4px 10px; border-radius: 7px; }
    .mb-badge.sealed { background: #f5f3ff; color: #6d28d9; border: 1px solid #ddd6fe; }
    .mb-badge.public { background: #eff6ff; color: #1d4ed8; border: 1px solid #bfdbfe; }
    .mb-badge.mb-award-won { background: #dcfce7; color: #15803d; border: 1px solid #bbf7d0; }
    .mb-badge.mb-award-declined { background: #f3f4f6; color: #6b7280; border: 1px solid #e5e7eb; }
    .mb-badge.mb-award-short { background: #fef9c3; color: #a16207; border: 1px solid #fde68a; }
    .mb-badge.mb-award-review { background: #fff7ed; color: #c2410c; border: 1px solid #fed7aa; }
    .mb-toggle { border: 1px solid var(--border-color); background: var(--bg-card); border-radius: 10px; padding: 8px 14px; font-size: 12.5px; font-weight: 700; color: var(--text-secondary); cursor: pointer; white-space: nowrap; }
    .mb-toggle:hover { border-color: var(--text-secondary); }
    .mb-status { display: flex; flex-direction: column; align-items: flex-end; gap: 7px; }

    .mb-empty { text-align: center; padding: 60px 20px; background: var(--bg-card); border: 1px dashed var(--border-color); border-radius: 16px; }
    .mb-empty h3 { font-size: 17px; font-weight: 800; color: var(--text-primary); margin: 12px 0 6px; }
    .mb-empty p { font-size: 13.5px; color: var(--text-muted); margin: 0 0 18px; }
    .mb-empty a { display: inline-flex; align-items: center; gap: 7px; background: #2563eb; color: #fff; border-radius: 10px; padding: 10px 20px; font-size: 13.5px; font-weight: 700; text-decoration: none; }
    @media (max-width: 620px) { .mb-card { grid-template-columns: 1fr; text-align: left; } .mb-amt { text-align: left; } .mb-status { align-items: flex-start; } }
</style>
@endpush

@section('content')
<div class="mb-wrap">
    @if(session('status'))
        <div class="mb-flash">✅ {{ session('status') }}</div>
    @endif

    <div class="mb-top">
        <a class="mb-back" href="{{ route('professional.bidding-board.index') }}">← Back to Bidding Board</a>
        <span style="font-size:12.5px;font-weight:700;color:var(--text-muted);">{{ $bids->total() }} bid{{ $bids->total() === 1 ? '' : 's' }} placed</span>
    </div>

    <div class="mb-note">
        <span>🔒</span>
        <span><b>Your bids are sealed.</b> Other professionals can't see your amounts — only you and the client can. Choose <b>Make public</b> on any bid if you'd rather show your amount openly.</span>
    </div>

    @forelse($bids as $bid)
        <div class="mb-card">
            <div>
                <div class="mb-title">{{ $bid->event?->title ?? 'Gig #' . $bid->event_id }}
                    @if($bid->category)<span style="font-size:11px;font-weight:700;background:rgba(37,99,235,.1);color:#2563eb;border-radius:6px;padding:2px 8px;margin-left:6px;">{{ $bid->category->name }}</span>@endif
                </div>
                <div class="mb-meta">
                    <span>📅 {{ $bid->event?->starts_at?->format('M j, Y') ?? 'Flexible' }}</span>
                    <span>Submitted {{ $bid->created_at->diffForHumans() }}</span>
                </div>
                @include('professional.bidding-board._bid-thread', ['bid' => $bid])
            </div>
            <div class="mb-amt">
                <b>${{ number_format($bid->amount) }}</b>
                <span>Your bid</span>
            </div>
            <div class="mb-status">
                @php
                    $award = match ($bid->status) {
                        'won'       => ['🏆 You won', 'won'],
                        'declined'  => ['Not selected', 'declined'],
                        'withdrawn' => ['Withdrawn', 'declined'],
                        'shortlisted' => ['⭐ Shortlisted', 'short'],
                        default     => ['⏳ Under review', 'review'],
                    };
                @endphp
                <span class="mb-badge mb-award-{{ $award[1] }}">{{ $award[0] }}</span>
                <span class="mb-badge {{ $bid->is_public ? 'public' : 'sealed' }}">
                    {{ $bid->is_public ? '📣 Public' : '🔒 Sealed' }}
                </span>
                @if(! in_array($bid->status, ['won', 'declined', 'withdrawn']))
                    <form method="POST" action="{{ route('professional.bidding-board.toggle', $bid) }}">
                        @csrf
                        <button type="submit" class="mb-toggle">{{ $bid->is_public ? 'Seal again' : 'Make public' }}</button>
                    </form>
                @endif
            </div>
        </div>
    @empty
        <div class="mb-empty">
            <div style="font-size:38px;">🔒</div>
            <h3>No bids yet</h3>
            <p>Head to the bidding board and place your first sealed bid on an open gig.</p>
            <a href="{{ route('professional.bidding-board.index') }}">Browse open gigs →</a>
        </div>
    @endforelse

    @if($bids->hasPages())
        <div style="margin-top:18px;">{{ $bids->links() }}</div>
    @endif
</div>
@endsection
