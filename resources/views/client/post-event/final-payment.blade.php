@extends('layouts.client')
@section('title', 'Post an Event — Final Payment')
@include('client.post-event._styles')

@section('content')
<div class="pe-wrap">
    @include('client.post-event._wizard')

    <div class="pe-container pe-main">
        <h1 class="pe-h1">🔒 Final Payment</h1>
        <p class="pe-sub">Your event is complete! Review your final invoice and make the remaining payment to release funds to your professional.</p>

        {{-- Completion banner --}}
        <div style="display:flex; gap:12px; align-items:flex-start; background:var(--pe-green-l); border:1px solid #bbf7d0; border-radius:12px; padding:14px 16px; margin-bottom:22px;">
            <svg viewBox="0 0 24 24" fill="none" stroke="#15803d" stroke-width="2.4" style="width:20px; height:20px; flex-shrink:0; margin-top:1px;"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
            <div style="font-size:13px; color:#15803d; line-height:1.5;">
                <strong>Great news!</strong> Your vendor has marked the event as complete. Please review the final invoice and approve the release of funds.
            </div>
        </div>

        <div class="pe-grid">
            {{-- Main --}}
            <div>
                {{-- Payment Overview --}}
                <div class="pe-card">
                    <h2 style="margin-bottom:14px;">Payment Overview</h2>
                    <div class="pe-rail-row"><span class="k">Total Package Amount</span><span class="v">${{ number_format($order['total']) }}</span></div>
                    <div class="pe-rail-row"><span class="k">Deposit Paid ({{ $order['depositPaidDate'] }})</span><span class="v" style="color:var(--pe-green);">−${{ number_format($order['deposit']) }}</span></div>
                    <div class="pe-rail-row" style="border-top:1px solid var(--pe-line); margin-top:4px;">
                        <span class="k" style="font-weight:800; color:var(--pe-ink);">Remaining Balance</span>
                        <span class="v" style="font-size:18px;">${{ number_format($order['remaining']) }}</span>
                    </div>

                    <div style="display:flex; gap:10px; align-items:flex-start; background:var(--pe-green-l); border:1px solid #bbf7d0; border-radius:10px; padding:12px 14px; margin-top:16px;">
                        <svg viewBox="0 0 24 24" fill="none" stroke="#15803d" stroke-width="2.2" style="width:18px; height:18px; flex-shrink:0; margin-top:1px;"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                        <div style="font-size:12.5px; color:#15803d; line-height:1.5;">Funds are held securely securely until the event is completed to your satisfaction.</div>
                    </div>

                    {{-- Payment mini timeline --}}
                    <div style="margin-top:18px;">
                        <div style="font-size:12.5px; font-weight:800; color:var(--pe-ink); margin-bottom:12px;">Payment Status: <span style="color:var(--pe-green);">Protected</span></div>
                        @php
                            $secure payment = [
                                ['Deposit Paid', 'done'],
                                ['Event Completed', 'done'],
                                ['Final Payment', 'pending'],
                            ];
                        @endphp
                        <div style="display:flex; align-items:flex-start;">
                            @foreach($secure payment as $e)
                                @php
                                    $done = $e[1] === 'done';
                                    $dotBg = $done ? 'var(--pe-green)' : '#eef1f5';
                                    $dotColor = $done ? '#fff' : 'var(--pe-muted)';
                                @endphp
                                <div style="flex:1; text-align:center; position:relative;">
                                    @unless($loop->first)
                                        <span style="position:absolute; top:12px; right:50%; width:100%; height:3px; background:{{ $secure payment[$loop->index - 1][1] === 'done' ? 'var(--pe-green)' : 'var(--pe-line)' }};"></span>
                                    @endunless
                                    <span style="position:relative; z-index:1; width:26px; height:26px; border-radius:50%; background:{{ $dotBg }}; color:{{ $dotColor }}; display:inline-flex; align-items:center; justify-content:center; font-size:12px; font-weight:800;">
                                        @if($done)
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" style="width:13px; height:13px;"><polyline points="20 6 9 17 4 12"/></svg>
                                        @else
                                            {{ $loop->iteration }}
                                        @endif
                                    </span>
                                    <div style="margin-top:6px; font-size:11.5px; font-weight:700; color:{{ $done ? 'var(--pe-ink-2)' : 'var(--pe-muted)' }};">{{ $e[0] }}</div>
                                    @unless($done)<div style="font-size:10.5px; font-weight:800; color:var(--pe-orange); margin-top:1px;">Pending</div>@endunless
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>

                {{-- Choose Payment Method --}}
                <div class="pe-card">
                    <h2 style="margin-bottom:14px;">Choose Payment Method</h2>

                    <label style="display:flex; gap:12px; align-items:flex-start; border:1.5px solid var(--pe-orange); border-radius:12px; padding:14px; margin-bottom:12px; background:#fff7ed; cursor:pointer;">
                        <input type="radio" name="pay_method" value="card" checked style="margin-top:3px; accent-color:var(--pe-orange);">
                        <div style="flex:1;">
                            <div style="font-size:14px; font-weight:800; color:var(--pe-ink);">Credit / Debit Card</div>
                            <div class="pe-muted" style="margin-bottom:12px;">Visa, Mastercard, American Express</div>
                            <div class="pe-field" style="margin-bottom:12px;">
                                <label class="pe-label">Card Number</label>
                                <input type="text" class="pe-input" placeholder="1234 5678 9012 3456">
                            </div>
                            <div class="pe-field" style="margin-bottom:12px;">
                                <label class="pe-label">Name on Card</label>
                                <input type="text" class="pe-input" placeholder="Full name">
                            </div>
                            <div class="pe-row">
                                <div class="pe-field" style="margin-bottom:0;">
                                    <label class="pe-label">Expiry</label>
                                    <input type="text" class="pe-input" placeholder="MM / YY">
                                </div>
                                <div class="pe-field" style="margin-bottom:0;">
                                    <label class="pe-label">CVV</label>
                                    <input type="text" class="pe-input" placeholder="123">
                                </div>
                            </div>
                        </div>
                    </label>

                    <label style="display:flex; gap:12px; align-items:center; border:1.5px solid var(--pe-line); border-radius:12px; padding:14px; margin-bottom:12px; cursor:pointer;">
                        <input type="radio" name="pay_method" value="ach" style="accent-color:var(--pe-orange);">
                        <div>
                            <div style="font-size:14px; font-weight:800; color:var(--pe-ink);">Bank Transfer (ACH)</div>
                            <div class="pe-muted">Direct transfer from your bank account</div>
                        </div>
                    </label>

                    <label style="display:flex; gap:12px; align-items:center; border:1.5px solid var(--pe-line); border-radius:12px; padding:14px; margin-bottom:16px; cursor:pointer;">
                        <input type="radio" name="pay_method" value="paypal" style="accent-color:var(--pe-orange);">
                        <div>
                            <div style="font-size:14px; font-weight:800; color:var(--pe-ink);">PayPal</div>
                            <div class="pe-muted">Pay with your PayPal account</div>
                        </div>
                    </label>

                    <button type="button" class="pe-btn" style="width:100%;" onclick="peReleaseFunds()">🔒 Pay Remaining Balance of ${{ number_format($order['remaining']) }}</button>

                    <div style="display:flex; gap:10px; align-items:flex-start; background:var(--pe-green-l); border:1px solid #bbf7d0; border-radius:10px; padding:12px 14px; margin-top:14px;">
                        <svg viewBox="0 0 24 24" fill="none" stroke="#15803d" stroke-width="2.2" style="width:18px; height:18px; flex-shrink:0; margin-top:1px;"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                        <div style="font-size:12.5px; color:#15803d; line-height:1.5;"><strong>Secure Payment</strong> — Your payment will be released to the professional once you approve the event completion.</div>
                    </div>
                </div>
            </div>

            {{-- Rail --}}
            <aside class="pe-rail">
                {{-- Invoice Details --}}
                <div class="pe-rail-card">
                    <div style="display:flex; align-items:baseline; justify-content:space-between; margin-bottom:12px;">
                        <h4 style="margin:0;">Invoice Details</h4>
                        <span class="pe-muted" style="font-weight:700;">Invoice #{{ $order['number'] }}</span>
                    </div>

                    <div style="font-size:12px; font-weight:800; color:var(--pe-muted); text-transform:uppercase; letter-spacing:.4px; margin-bottom:4px;">Services</div>
                    @foreach($order['lineItems'] as $item)
                        <div class="pe-rail-row"><span class="k">{{ $item['label'] }}</span><span class="v">${{ number_format($item['amount']) }}</span></div>
                    @endforeach

                    <div style="font-size:12px; font-weight:800; color:var(--pe-muted); text-transform:uppercase; letter-spacing:.4px; margin:12px 0 4px;">Add-Ons ({{ count($order['addons']) }})</div>
                    @foreach($order['addons'] as $addon)
                        <div class="pe-rail-row"><span class="k">{{ $addon['label'] }}</span><span class="v">${{ number_format($addon['amount']) }}</span></div>
                    @endforeach

                    <div class="pe-rail-row" style="border-top:1px solid var(--pe-line); margin-top:8px;"><span class="k">Subtotal</span><span class="v">${{ number_format($order['subtotal']) }}</span></div>
                    <div class="pe-rail-row"><span class="k">Estimated Tax (6%)</span><span class="v">${{ number_format($order['tax']) }}</span></div>
                    <div class="pe-rail-row"><span class="k" style="font-weight:800; color:var(--pe-ink);">Total</span><span class="v" style="font-size:16px;">${{ number_format($order['total']) }}</span></div>
                    <div class="pe-rail-row"><span class="k">Deposit Paid</span><span class="v" style="color:var(--pe-green);">−${{ number_format($order['deposit']) }}</span></div>
                    <div class="pe-rail-row"><span class="k" style="font-weight:800; color:var(--pe-ink);">Remaining Balance</span><span class="v" style="font-size:16px;">${{ number_format($order['remaining']) }}</span></div>
                </div>

                {{-- Vendor card --}}
                <div class="pe-rail-card">
                    <h4>Your Professional</h4>
                    <div style="display:flex; gap:12px; align-items:center;">
                        <span style="width:42px; height:42px; border-radius:50%; background:var(--pe-purple-l); color:var(--pe-purple); display:flex; align-items:center; justify-content:center; font-weight:800; flex-shrink:0;">EE</span>
                        <div>
                            <div style="font-size:14px; font-weight:800; color:var(--pe-ink); display:flex; align-items:center; gap:6px;">
                                Elite Events Co.
                                <svg viewBox="0 0 24 24" fill="none" stroke="var(--pe-green)" stroke-width="3" style="width:14px; height:14px;"><polyline points="20 6 9 17 4 12"/></svg>
                            </div>
                            <div style="font-size:12.5px; font-weight:700; color:var(--pe-ink-2);"><span style="color:#f59e0b;">★</span> 4.9 <span class="pe-muted" style="font-weight:600;">(128 reviews)</span></div>
                        </div>
                    </div>
                </div>

                {{-- What Happens Next --}}
                <div class="pe-rail-card">
                    <h4>What Happens Next?</h4>
                    @php
                        $whats = [
                            ['Event Completed', 'done'],
                            ['Final Payment', 'pending'],
                            ['Funds Released', 'upcoming', 'Released to your professional within a short window'],
                            ['Leave a Review', 'upcoming', 'Share your experience with the community'],
                        ];
                    @endphp
                    @foreach($whats as $w)
                        <div style="display:flex; gap:10px; align-items:flex-start; padding:7px 0;">
                            <span style="width:22px; height:22px; border-radius:50%; flex-shrink:0; display:flex; align-items:center; justify-content:center; font-size:11px; font-weight:800;
                                background:{{ $w[1] === 'done' ? 'var(--pe-green)' : ($w[1] === 'pending' ? 'var(--pe-orange)' : '#eef1f5') }};
                                color:{{ $w[1] === 'upcoming' ? 'var(--pe-muted)' : '#fff' }};">
                                @if($w[1] === 'done')
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" style="width:12px; height:12px;"><polyline points="20 6 9 17 4 12"/></svg>
                                @else
                                    {{ $loop->iteration }}
                                @endif
                            </span>
                            <div>
                                <div style="font-size:13px; font-weight:800; color:var(--pe-ink);">{{ $w[0] }}@if($w[1] === 'pending') <span class="pe-muted" style="font-weight:700;">(pending)</span>@endif</div>
                                @isset($w[2])<div class="pe-muted">{{ $w[2] }}</div>@endisset
                            </div>
                        </div>
                    @endforeach
                </div>

                {{-- Need Help --}}
                <div class="pe-rail-card pe-rail-why">
                    <h4>Need Help?</h4>
                    <p class="pe-muted" style="margin:-6px 0 10px;">Questions about your invoice or the release of funds? We are here to help.</p>
                    <a href="{{ route('client.chat.index') }}" class="pe-btn pe-btn-purple" style="width:100%;">Chat with Support</a>
                    <p class="pe-muted" style="margin:10px 0 0; text-align:center;">Dedicated support</p>
                </div>
            </aside>
        </div>

        {{-- Bottom nav --}}
        <div class="pe-actions" style="margin-top:22px;">
            <a href="{{ route('client.post-event.confirmed') }}" class="pe-btn pe-btn-ghost">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><line x1="19" y1="12" x2="5" y2="12"/><polyline points="12 19 5 12 12 5"/></svg>
                Back to Order Details
            </a>
            <a href="#" class="pe-btn pe-btn-ghost">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
                Download Invoice
            </a>
        </div>
    </div>
</div>

<script>
    function peReleaseFunds() {
        window.location.href = "{{ route('client.post-event.confirmed') }}";
    }
</script>
@endsection
