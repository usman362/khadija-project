@extends('layouts.client')

@section('title', 'Finalize With ' . ($pro->name ?? 'Professional'))
@section('page-title', 'Finalize With Professional')

{{-- Screen 4 of the client BR set — the seven-step agreement.

     Accepting a bid used to jump straight to a confirmed booking. Peter's rule
     is that either side may back out until a final agreement is made, so each
     step is stored with a timestamp and "booked" is only reached at step 7. --}}

@section('content')
<style>
    .fz-top { display: flex; justify-content: space-between; align-items: flex-start; gap: 16px; flex-wrap: wrap; margin-bottom: 16px; }
    .fz-h { font-size: 23px; font-weight: 800; color: var(--text-primary); }
    .fz-sub { font-size: 13.5px; color: var(--text-secondary); margin-top: 4px; max-width: 620px; line-height: 1.6; }

    .fz-pro { display: flex; align-items: center; gap: 14px; background: var(--bg-card); border: 1px solid var(--border-color); border-radius: 14px; padding: 15px 18px; margin-bottom: 16px; flex-wrap: wrap; }
    .fz-av { width: 46px; height: 46px; border-radius: 12px; object-fit: cover; }
    .fz-pro b { font-size: 15.5px; font-weight: 800; color: var(--text-primary); }
    .fz-pro-meta { font-size: 12.5px; color: var(--text-muted); margin-top: 2px; }
    .fz-price { margin-left: auto; text-align: right; }
    .fz-price b { font-size: 20px; font-weight: 800; color: var(--text-primary); }
    .fz-price span { display: block; font-size: 11.5px; color: var(--text-muted); }

    .fz-steps { display: flex; gap: 4px; overflow-x: auto; padding-bottom: 4px; margin-bottom: 18px; }
    .fz-step { flex: 1 1 0; min-width: 100px; text-align: center; text-decoration: none; }
    .fz-dot { width: 29px; height: 29px; margin: 0 auto 6px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 12px; font-weight: 800; background: var(--bg-subtle, rgba(0,0,0,.05)); color: var(--text-muted); border: 1px solid var(--border-color); }
    .fz-step.done .fz-dot { background: #15803d; border-color: #16a34a; color: #fff; }
    .fz-step.on .fz-dot { background: #f97316; border-color: #f97316; color: #fff; }
    .fz-step small { display: block; font-size: 11px; font-weight: 700; color: var(--text-muted); }
    .fz-step.on small { color: var(--brand-text); }

    .fz-grid { display: grid; grid-template-columns: minmax(0,1fr) 290px; gap: 18px; align-items: start; }
    @media (max-width: 1000px) { .fz-grid { grid-template-columns: minmax(0,1fr); } }

    .fz-card { background: var(--bg-card); border: 1px solid var(--border-color); border-radius: 16px; padding: 22px 24px; }
    .fz-card h3 { font-size: 17px; font-weight: 800; color: var(--text-primary); margin-bottom: 5px; }
    .fz-card .lede { font-size: 13px; color: var(--text-secondary); line-height: 1.65; margin-bottom: 18px; }
    .fz-f { margin-bottom: 16px; }
    .fz-f label { display: block; font-size: 12.5px; font-weight: 800; color: var(--text-primary); margin-bottom: 6px; }
    .fz-f input, .fz-f textarea, .fz-f select { width: 100%; background: transparent; border: 1px solid var(--border-color); border-radius: 10px; padding: 10px 12px; font-size: 13.5px; color: var(--text-primary); font-family: inherit; }
    .fz-f textarea { min-height: 120px; resize: vertical; line-height: 1.6; }
    .fz-help { font-size: 11.5px; color: var(--text-muted); margin-top: 5px; line-height: 1.5; }
    .fz-two { display: grid; grid-template-columns: 1fr 1fr; gap: 14px; }
    @media (max-width: 620px) { .fz-two { grid-template-columns: 1fr; } }

    .fz-row { display: flex; justify-content: space-between; gap: 16px; padding: 9px 0; border-bottom: 1px solid var(--border-color); font-size: 13px; }
    .fz-row:last-of-type { border-bottom: 0; }
    .fz-row span { color: var(--text-muted); font-weight: 600; }
    .fz-row b { color: var(--text-primary); text-align: right; }

    .fz-contract { background: var(--bg-subtle, rgba(0,0,0,.03)); border: 1px solid var(--border-color); border-radius: 12px; padding: 16px 18px; font-size: 12.5px; line-height: 1.75; white-space: pre-line; color: var(--text-secondary); max-height: 330px; overflow-y: auto; font-family: ui-monospace, SFMono-Regular, Menlo, monospace; }

    .fz-note { display: flex; gap: 9px; border-radius: 11px; padding: 12px 14px; font-size: 12.5px; line-height: 1.6; margin-bottom: 14px; }
    .fz-note.info { background: rgba(37,99,235,.07); border: 1px solid rgba(37,99,235,.22); color: var(--text-secondary); }
    .fz-note.warn { background: rgba(245,158,11,.09); border: 1px solid rgba(245,158,11,.3); color: var(--warn-text); }
    .fz-note.ok { background: rgba(22,163,74,.09); border: 1px solid rgba(22,163,74,.28); color: var(--ok-text); }
    .fz-note b { color: inherit; }

    .fz-nav { display: flex; justify-content: space-between; gap: 10px; margin-top: 20px; padding-top: 16px; border-top: 1px solid var(--border-color); flex-wrap: wrap; }
    .fz-btn { border: 1px solid var(--border-color); background: transparent; border-radius: 10px; padding: 10px 18px; font-size: 13px; font-weight: 700; color: var(--text-secondary); text-decoration: none; cursor: pointer; font-family: inherit; display: inline-flex; align-items: center; }
    .fz-btn.go { background: #f97316; border-color: #f97316; color: #fff; font-weight: 800; }
    .fz-btn.pay { background: #15803d; border-color: #16a34a; color: #fff; font-weight: 800; }
    .fz-btn.bad { border-color: rgba(220,38,38,.4); color: var(--bad-text); }

    .fz-rail { background: var(--bg-card); border: 1px solid var(--border-color); border-radius: 14px; padding: 15px 17px; margin-bottom: 13px; }
    .fz-rail h4 { font-size: 12.5px; font-weight: 800; color: var(--text-primary); margin-bottom: 10px; }
    .fz-ck { display: flex; align-items: flex-start; gap: 9px; padding: 6px 0; font-size: 12.5px; }
    .fz-ck i { font-style: normal; font-size: 13px; }
    .fz-ck b { display: block; color: var(--text-primary); font-weight: 700; }
    .fz-ck span { font-size: 11.5px; color: var(--text-muted); }
</style>

@if(session('status'))
    <div class="cl-card" style="background:#ecfdf5;border:1px solid #a7f3d0;color:#065f46;padding:12px 16px;margin-bottom:16px;font-size:13.5px;">✅ {{ session('status') }}</div>
@endif
@if($errors->any())
    <div class="cl-card" style="background:#fef2f2;border:1px solid #fecaca;color:#b91c1c;padding:12px 16px;margin-bottom:16px;font-size:13.5px;">
        @foreach($errors->all() as $e)<div>{{ $e }}</div>@endforeach
    </div>
@endif

@php
    $keys = array_keys($steps);
    $prev = $stepIndex > 0 ? $keys[$stepIndex - 1] : null;
    $done = collect($keys)->filter(fn ($k) => $fin->completed($k))->count();
@endphp

<div class="fz-top">
    <div>
        <div class="fz-h">Finalize With Professional</div>
        <p class="fz-sub">Agree the scope, price, schedule and terms, sign, and secure the deposit. Either side can still back out until both have signed and the deposit is secured.</p>
    </div>
    @if($fin->status !== 'booked')
        <form method="POST" action="{{ route('client.finalize.cancel', $fin) }}"
              onsubmit="return confirm('Cancel this finalization? The other proposals reopen.');">
            @csrf
            <button type="submit" class="fz-btn bad">Back out</button>
        </form>
    @endif
</div>

<div class="fz-pro">
    <img class="fz-av" src="{{ $pro->avatar_url }}" alt="{{ $pro->name }}">
    <div>
        <b>{{ $pro->name }}</b>
        <div class="fz-pro-meta">
            {{ $pro->profile?->headline ?? 'Event professional' }}
            @if($pro->profile?->city) · 📍 {{ $pro->profile->city }} @endif
        </div>
    </div>
    <div class="fz-price">
        <b>${{ number_format((float) ($fin->agreed_price ?? 0)) }}</b>
        <span>{{ $fin->price_agreed_at ? 'Agreed price' : 'Proposed price' }}</span>
    </div>
</div>

<div class="fz-steps">
    @foreach($steps as $key => [$label, $col])
        @php $i = $loop->index; @endphp
        <a class="fz-step {{ $i === $stepIndex ? 'on' : ($fin->completed($key) ? 'done' : '') }}"
           href="{{ $i <= $stepIndex || $fin->completed($key) ? route('client.finalize.step', [$fin, $key]) : 'javascript:void(0)' }}">
            <span class="fz-dot">{{ $fin->completed($key) ? '✓' : $i + 1 }}</span>
            <small>{{ $label }}</small>
        </a>
    @endforeach
</div>

<form method="POST" action="{{ route('client.finalize.save', [$fin, $step]) }}">
@csrf
<div class="fz-grid">
    <div class="fz-card">

    {{-- ── 1 · Review bid ──────────────────────────────────── --}}
    @if($step === 'bid')
        <h3>Review the proposal</h3>
        <p class="lede">What {{ $pro->name }} sent you. Everything below is what you're about to turn into an agreement.</p>

        <div class="fz-row"><span>Bid amount</span><b>${{ number_format((float) ($bid->amount ?? 0)) }}</b></div>
        <div class="fz-row"><span>Submitted</span><b>{{ $bid?->created_at?->format('M j, Y · g:i A') ?? '—' }}</b></div>
        @if($bid?->available_confirmed)
            <div class="fz-row"><span>Availability</span><b>Confirmed for the date</b></div>
        @endif
        @if($bid?->breakdown)
            <div style="margin-top:14px;">
                <label style="font-size:12.5px;font-weight:800;color:var(--text-primary);">Price breakdown</label>
                @foreach($bid->breakdown as $item)
                    <div class="fz-row"><span>{{ $item['label'] }}</span><b>${{ number_format($item['cost']) }}</b></div>
                @endforeach
            </div>
        @endif
        @foreach([['plan','Service plan'], ['terms','Their terms'], ['note','Message']] as [$k, $lbl])
            @if(! empty($bid?->{$k}))
                <div style="margin-top:14px;">
                    <label style="font-size:12.5px;font-weight:800;color:var(--text-primary);">{{ $lbl }}</label>
                    <p style="font-size:13px;color:var(--text-secondary);line-height:1.7;white-space:pre-line;margin-top:5px;">{{ $bid->{$k} }}</p>
                </div>
            @endif
        @endforeach

    {{-- ── 2 · Scope ───────────────────────────────────────── --}}
    @elseif($step === 'scope')
        <h3>Confirm the scope</h3>
        <p class="lede">Prefilled from their service plan. Edit it until it says exactly what's being delivered — this becomes part of the contract.</p>
        <div class="fz-f">
            <label>Final scope &amp; deliverables</label>
            <textarea name="scope" style="min-height:200px;">{{ old('scope', $fin->scope) }}</textarea>
            <p class="fz-help">Say what's included and what isn't. Unstated exclusions are where disputes start.</p>
        </div>

    {{-- ── 3 · Price ───────────────────────────────────────── --}}
    @elseif($step === 'price')
        <h3>Confirm price &amp; fees</h3>
        <p class="lede">The final figure you're both agreeing to. If you negotiated a different number, enter it here.</p>
        <div class="fz-f">
            <label>Agreed price</label>
            <input type="number" name="agreed_price" min="1" step="1" value="{{ old('agreed_price', (int) $fin->agreed_price) }}">
            <p class="fz-help">Their original bid was ${{ number_format((float) ($bid->amount ?? 0)) }}.</p>
        </div>
        <div class="fz-note info">
            💸 <span>
                <b>Your fee: ${{ number_format($clientFee, 2) }}</b>, charged once when this finalizes. Posting was free, and nothing is charged if you don't book.
                The professional's commission ({{ $proRate }}%) comes out of their payout, not your price.
            </span>
        </div>

    {{-- ── 4 · Schedule ────────────────────────────────────── --}}
    @elseif($step === 'schedule')
        <h3>Schedule &amp; service timeline</h3>
        <p class="lede">When they arrive, when the service runs, and anything time-critical around it.</p>
        <div class="fz-two">
            <div class="fz-f">
                <label>Service starts</label>
                <input type="datetime-local" name="service_start"
                       value="{{ old('service_start', $fin->service_start?->format('Y-m-d\TH:i') ?? $event->starts_at?->format('Y-m-d\TH:i')) }}">
            </div>
            <div class="fz-f">
                <label>Service ends</label>
                <input type="datetime-local" name="service_end" value="{{ old('service_end', $fin->service_end?->format('Y-m-d\TH:i')) }}">
            </div>
        </div>
        <div class="fz-f">
            <label>Timeline notes</label>
            <textarea name="schedule_notes" style="min-height:110px;" placeholder="Setup access from 2pm, guests arrive 6pm, service to 10pm, breakdown by 11pm…">{{ old('schedule_notes', $fin->schedule_notes) }}</textarea>
        </div>

    {{-- ── 5 · Deposit & terms ─────────────────────────────── --}}
    @elseif($step === 'terms')
        <h3>Deposit &amp; payment terms</h3>
        <p class="lede">What secures the booking now, and when the rest is due.</p>
        <div class="fz-two">
            <div class="fz-f">
                <label>Deposit</label>
                <select name="deposit_percent" id="fzPct">
                    @foreach([15,20,25,30,35,40,45,50] as $p)
                        <option value="{{ $p }}" @selected((int) old('deposit_percent', $fin->deposit_percent ?? 25) === $p)>{{ $p }}%</option>
                    @endforeach
                </select>
                {{-- The 15–50% band is a platform rule, so the control can't
                     offer anything outside it and the server checks again. --}}
                <p class="fz-help">Deposits run between 15% and 50%. On ${{ number_format((float) $fin->agreed_price) }} that's <b id="fzAmt">$0</b>.</p>
            </div>
            <div class="fz-f">
                <label>Balance due on</label>
                <input type="date" name="balance_due_on" value="{{ old('balance_due_on', $fin->balance_due_on?->format('Y-m-d')) }}">
            </div>
        </div>
        <div class="fz-f">
            <label>Payment terms</label>
            <textarea name="payment_terms" style="min-height:100px;" placeholder="e.g. Balance due 7 days before the event. Cancellation inside 14 days forfeits the deposit.">{{ old('payment_terms', $fin->payment_terms) }}</textarea>
        </div>

    {{-- ── 6 · Contract ────────────────────────────────────── --}}
    @elseif($step === 'contract')
        <h3>Contract</h3>
        <p class="lede">Assembled from what you both agreed in the previous steps. Read it before signing.</p>

        <div class="fz-contract">{{ $fin->contract_body ?: $preview ?? '' }}@if(! $fin->contract_body)
SERVICE AGREEMENT

Request: {{ $event->title }}
Client: {{ $fin->client->name ?? auth()->user()->name }}
Professional: {{ $pro->name }}

SCOPE
{{ $fin->scope }}

PRICE
Agreed price: ${{ number_format((float) $fin->agreed_price, 2) }}
Deposit: {{ $fin->deposit_percent }}% (${{ number_format((float) $fin->deposit_amount, 2) }})
Balance due: {{ $fin->balance_due_on?->format('F j, Y') ?? 'before the service date' }}

SCHEDULE
Service starts: {{ $fin->service_start?->format('F j, Y · g:i A') ?? '—' }}
Service ends: {{ $fin->service_end?->format('F j, Y · g:i A') ?? '—' }}
{{ $fin->schedule_notes }}

PAYMENT TERMS
{{ $fin->payment_terms }}@endif</div>

        @if($fin->client_signed_at)
            <div class="fz-note ok" style="margin-top:14px;">
                ✍️ <span><b>Signed.</b> You signed as {{ $fin->client_signature }} on {{ $fin->client_signed_at->format('M j, Y · g:i A') }}. {{ $pro->name }} counter-signed on {{ $fin->supplier_signed_at?->format('M j, Y · g:i A') }}.</span>
            </div>
        @else
            <div class="fz-f" style="margin-top:16px;">
                <label>Type your full name to sign</label>
                <input type="text" name="client_signature" value="{{ old('client_signature') }}" placeholder="{{ auth()->user()->name }}">
            </div>
            <label class="fz-note info" style="cursor:pointer;">
                <input type="checkbox" name="agree" value="1" style="margin-top:2px;">
                <span>I've read the agreement above and accept it on behalf of myself or my organisation. Signing does not charge anything — the deposit is the next step.</span>
            </label>
        @endif

    {{-- ── 7 · Payment ─────────────────────────────────────── --}}
    @elseif($step === 'payment')
        <h3>Secure the deposit</h3>
        <p class="lede">The last step. Once the deposit is secured the booking is confirmed and the date is held.</p>

        <div class="fz-row"><span>Agreed price</span><b>${{ number_format((float) $fin->agreed_price) }}</b></div>
        <div class="fz-row"><span>Deposit ({{ $fin->deposit_percent }}%)</span><b>${{ number_format((float) $fin->deposit_amount) }}</b></div>
        <div class="fz-row"><span>Balance</span><b>${{ number_format((float) $fin->agreed_price - (float) $fin->deposit_amount) }} {{ $fin->balance_due_on ? 'due ' . $fin->balance_due_on->format('M j, Y') : 'due before the service' }}</b></div>
        <div class="fz-row"><span>Service fee</span><b>${{ number_format($clientFee, 2) }}</b></div>

        @if($fin->isFunded())
            <div class="fz-note ok" style="margin-top:16px;">
                ✅ <span>
                    <b>Booked.</b> The deposit was secured on {{ $fin->funded_at->format('M j, Y · g:i A') }}.
                    @if($fin->payment_mode === 'test')
                        This ran in <b>test mode</b> — no real money moved.
                    @endif
                </span>
            </div>
            <a class="fz-btn go" style="margin-top:14px;" href="{{ route('client.bookings.index') }}">View booking</a>
        @else
            {{-- The mode is stated up front. A test-mode run must never be
                 mistaken for a real payment, so it says so before the click and
                 again on the record afterwards. --}}
            {{-- With Stripe configured the card is entered on Stripe's page,
                 not here, so the button leaves the site. Saying so beforehand
                 means the redirect is expected rather than alarming. --}}
            @if(\App\Domain\Payments\DepositCheckout::isConfigured())
                <div class="fz-note info" style="margin-top:16px;">
                    💳 <span>You'll enter your card on <b>Stripe's secure page</b>, then come
                    straight back here. Your card details never touch GigResource.</span>
                </div>
            @endif

            @if($payMode === 'test')
                <div class="fz-note warn" style="margin-top:16px;">
                    🧪 <span><b>Test mode.</b> Payments are running against test credentials, so no real money will move. The booking will be created and marked as a test-mode deposit. Switch Payment Settings to Live (after go-live) to take real deposits.</span>
                </div>
            @elseif(! $goLive)
                <div class="fz-note warn" style="margin-top:16px;">
                    🔒 <span><b>Live payments are locked.</b> The platform hasn't gone live yet, so a real charge will be refused. Set Payment Settings to Test mode to run this through.</span>
                </div>
            @endif

            <label class="fz-note info" style="cursor:pointer;">
                <input type="checkbox" name="confirm_payment" value="1" style="margin-top:2px;">
                {{-- Note the space before @if: Blade won't treat a directive as one when it
                         is glued to the preceding word, but it still compiles the closing
                         @endif — which silently unbalances the whole template. --}}
                    <span>Secure ${{ number_format((float) $fin->deposit_amount) }} to confirm this booking
                        @if($payMode === 'test')<b>(test mode — no real charge)</b>@endif.</span>
            </label>
        @endif
    @endif

        @unless($step === 'payment' && $fin->isFunded())
        <div class="fz-nav">
            <div>
                @if($prev)<a class="fz-btn" href="{{ route('client.finalize.step', [$fin, $prev]) }}">Back</a>@endif
            </div>
            <button type="submit" class="fz-btn {{ $step === 'payment' ? 'pay' : 'go' }}">
                @if($step === 'payment') {{ \App\Domain\Payments\DepositCheckout::isConfigured() ? 'Continue to payment' : 'Secure deposit & book' }}
                @elseif($step === 'contract') {{ $fin->client_signed_at ? 'Continue' : 'Sign agreement' }}
                @elseif($step === 'bid') Looks right — continue
                @else Confirm &amp; continue @endif
            </button>
        </div>
        @endunless
    </div>

    {{-- ── Right rail ──────────────────────────────────────── --}}
    <aside>
        <div class="fz-rail">
            <h4>Finalization checklist</h4>
            @foreach($steps as $key => [$label, $col])
                <div class="fz-ck">
                    <i>{{ $fin->completed($key) ? '✅' : ($key === $step ? '🔵' : '⚪️') }}</i>
                    <div><b>{{ $label }}</b><span>{{ $fin->completed($key) ? 'Done' : ($key === $step ? 'In progress' : 'Not started') }}</span></div>
                </div>
            @endforeach
            <div style="margin-top:10px;font-size:11.5px;color:var(--text-muted);">{{ $done }} of {{ count($steps) }} complete</div>
        </div>

        <div class="fz-rail">
            <h4>Fees</h4>
            <div class="fz-ck"><i>✅</i><div><b>Free to post</b><span>Nothing charged to publish a request.</span></div></div>
            <div class="fz-ck"><i>💳</i><div><b>${{ number_format($clientFee, 2) }} service fee</b><span>Once, only when this finalizes.</span></div></div>
            <div class="fz-ck"><i>🚫</i><div><b>$0 if nothing books</b><span>Back out before signing and funding and you pay nothing.</span></div></div>
        </div>

        <div class="fz-rail">
            <h4>Good to know</h4>
            <div class="fz-ck"><i>↩️</i><div><b>You can still back out</b><span>Until both sides have signed and the deposit is secured.</span></div></div>
            <div class="fz-ck"><i>🔒</i><div><b>Held by our processor</b><span>Funds sit with the licensed payment processor until contract terms are met.</span></div></div>
        </div>
    </aside>
</div>
</form>

@if($step === 'terms')
<script>
(function () {
    // Show the deposit in money, not just a percentage — the client is agreeing
    // to a figure, so show them the figure.
    var price = {{ (float) $fin->agreed_price }};
    var pct   = document.getElementById('fzPct');
    var out   = document.getElementById('fzAmt');
    function sync() { out.textContent = '$' + Math.round(price * pct.value / 100).toLocaleString(); }
    pct.addEventListener('change', sync);
    sync();
})();
</script>
@endif
@endsection
