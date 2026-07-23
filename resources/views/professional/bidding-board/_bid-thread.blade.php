{{-- Bid negotiation thread + reply/counter form.
     Requires: $bid, $replyRoute (route NAME), and $meId (current user id).
     $expanded — the caller already revealed this thread (e.g. the proposals
     table opens a row), so don't make them click a second toggle to see it. --}}
@php
    $replyRoute = $replyRoute ?? 'professional.bidding-board.reply';
    $meId = $meId ?? auth()->id();
    $expanded = $expanded ?? false;
    // A won bid is where coordination STARTS — keep it talkable. Only a
    // declined or withdrawn bid is genuinely a closed conversation.
    $closed = in_array($bid->status, ['declined', 'withdrawn']);
    $awarded = $bid->status === 'won';
    $threadId = 'thread-' . $bid->id;
@endphp
<div style="margin-top:10px;">
    @unless($expanded)
        <button type="button" onclick="var t=document.getElementById('{{ $threadId }}');t.style.display=t.style.display==='none'?'block':'none';"
                style="background:none;border:none;padding:0;cursor:pointer;font-size:12px;font-weight:700;color:#2563eb;">
            💬 Reply / Counter @if($bid->replies->count())<span style="opacity:.7;">({{ $bid->replies->count() }})</span>@endif
        </button>
    @endunless

    <div id="{{ $threadId }}" style="display:{{ $expanded ? 'block' : 'none' }};margin-top:8px;border-top:1px solid rgba(128,128,128,.2);padding-top:8px;">
        @forelse($bid->replies as $r)
            @php $mine = $r->user_id === $meId; @endphp
            <div style="margin-bottom:8px;text-align:{{ $mine ? 'right' : 'left' }};">
                <div style="display:inline-block;max-width:85%;text-align:left;background:{{ $mine ? 'rgba(37,99,235,.10)' : 'rgba(128,128,128,.10)' }};border-radius:10px;padding:7px 11px;">
                    <div style="font-size:11px;font-weight:700;opacity:.75;">{{ $mine ? 'You' : ($r->user?->name ?? 'Them') }}
                        <span style="font-weight:500;opacity:.7;">· {{ $r->created_at->diffForHumans() }}</span></div>
                    @if($r->counter_amount)
                        <div style="font-weight:800;color:#16a34a;font-size:13px;">Counter-offer: ${{ number_format($r->counter_amount) }}</div>
                    @endif
                    @if($r->note)<div style="font-size:13px;">{{ $r->note }}</div>@endif
                </div>
            </div>
        @empty
            <div style="font-size:12px;opacity:.6;margin-bottom:8px;">No messages yet — start the conversation below.</div>
        @endforelse

        @unless($closed)
            @if($awarded)
                <div style="font-size:12px;color:#16a34a;font-weight:700;margin-bottom:6px;">✓ Awarded — use this thread to sort out the details.</div>
            @endif
            <form method="POST" action="{{ route($replyRoute, $bid->id) }}" style="display:flex;flex-direction:column;gap:6px;">
                @csrf
                <div style="display:flex;gap:6px;align-items:center;">
                    @unless($awarded)
                        <span style="font-size:12px;opacity:.7;">Counter $</span>
                        <input type="number" name="counter_amount" min="1" placeholder="optional"
                               style="width:110px;padding:6px 8px;border:1px solid rgba(128,128,128,.3);border-radius:7px;font-size:13px;background:transparent;color:inherit;">
                    @endunless
                    {{-- Only required once the counter field is gone, or a
                         counter-amount-only reply would stop validating. --}}
                    <input type="text" name="note" maxlength="1000" placeholder="{{ $awarded ? 'Message the professional…' : 'Add a message…' }}" @if($awarded) required @endif
                           style="flex:1;padding:6px 8px;border:1px solid rgba(128,128,128,.3);border-radius:7px;font-size:13px;background:transparent;color:inherit;">
                    <button type="submit" style="background:#2563eb;color:#fff;border:none;border-radius:7px;padding:6px 12px;font-size:12px;font-weight:700;cursor:pointer;">Send</button>
                </div>
            </form>
        @else
            <div style="font-size:12px;opacity:.6;">This bid is {{ $bid->status }} — the conversation is closed.</div>
        @endunless
    </div>
</div>
