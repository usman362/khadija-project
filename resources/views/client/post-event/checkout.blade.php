@extends('layouts.client')
@section('title', 'Post an Event — Checkout & Payment')
@include('client.post-event._styles')

@section('content')
<div class="pe-wrap">
    @include('client.post-event._wizard')

    <div class="pe-container pe-main">
        <h1 class="pe-h1">🔒 Checkout &amp; Secure Your Package</h1>
        <p class="pe-sub">Review your selections, sign the contract, and pay your deposit to secure your date.</p>
        <div class="pe-check" style="color:var(--pe-green); font-weight:700; margin:-12px 0 22px;">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"/></svg>
            Your payment is secure and protected.
        </div>

        <div class="pe-grid">
            {{-- Main --}}
            <div>
                {{-- Selected package combination --}}
                <div class="pe-card" style="padding:0; overflow:hidden;">
                    @php $img = $order['img']; @endphp
                    <div style="height:180px; background:#eee url('https://images.unsplash.com/{{ $img }}?w=600&q=80&auto=format&fit=crop') center/cover no-repeat;"></div>
                    <div style="padding:22px;">
                        <h2 style="margin-bottom:8px;">Your Selected Package Combination</h2>
                        <div style="display:flex; align-items:center; gap:10px; flex-wrap:wrap; margin-bottom:6px;">
                            <div style="font-weight:800; font-size:16px;">{{ $order['combo'] }}</div>
                            <span class="pe-badge green">{{ $order['match'] }}% Match</span>
                        </div>
                        <div class="pe-muted" style="margin-bottom:12px;">By {{ implode(' & ', $order['vendors']) }}</div>

                        <div style="display:flex; flex-wrap:wrap; gap:8px 18px; font-size:12.5px; color:var(--pe-ink-2); margin-bottom:18px;">
                            <span>{{ count($order['services']) }} Services</span>
                            <span>· {{ $summary['guests'] }} Guests</span>
                            <span>· {{ $summary['date'] }}</span>
                            <span>· {{ $summary['location'] }}</span>
                        </div>

                        <h3 style="font-size:14px; font-weight:800; margin-bottom:8px;">Services Included ({{ count($order['services']) }})</h3>
                        <div style="display:grid; grid-template-columns:1fr 1fr; gap:2px 18px; margin-bottom:18px;">
                            @foreach($order['services'] as $svc)
                                <div class="pe-check">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"/></svg>
                                    {{ $svc }}
                                </div>
                            @endforeach
                        </div>

                        <div style="border-top:1px solid var(--pe-line-2); padding-top:14px;">
                            <div class="pe-rail-row"><span class="k">Package Total</span><span class="v">${{ number_format($order['packageTotal']) }}</span></div>
                            <div class="pe-rail-row"><span class="k">Add-Ons Total</span><span class="v">${{ number_format($order['addonsTotal']) }}</span></div>
                            <div class="pe-rail-row"><span class="k">Estimated Tax (6%)</span><span class="v">${{ number_format($order['tax']) }}</span></div>
                            <div class="pe-rail-row"><span class="k" style="font-weight:800; color:var(--pe-ink);">Estimated Total</span><span class="v" style="font-size:16px;">${{ number_format($order['total']) }}</span></div>
                        </div>
                    </div>
                </div>

                {{-- Contract + Secure your date --}}
                <div class="pe-row" style="grid-template-columns:1fr 1fr; gap:18px; align-items:start;">
                    {{-- Review & Sign --}}
                    <div class="pe-card" style="margin-bottom:0;">
                        <h3 style="margin-bottom:12px;">Review &amp; Sign Your Contract</h3>
                        <div style="background:var(--pe-bg); border:1px solid var(--pe-line); border-radius:12px; padding:14px; margin-bottom:14px;">
                            <div style="font-weight:800; font-size:13px; margin-bottom:8px;">Event Services Agreement</div>
                            <div class="pe-rail-row"><span class="k">Event Date</span><span class="v">{{ $summary['date'] }}</span></div>
                            <div class="pe-rail-row"><span class="k">Location</span><span class="v">{{ $summary['location'] }}</span></div>
                            <div class="pe-rail-row"><span class="k">Services</span><span class="v">{{ count($order['services']) }}</span></div>
                            <div class="pe-rail-row"><span class="k">Total Amount</span><span class="v">${{ number_format($order['total']) }}</span></div>
                            <div class="pe-rail-row"><span class="k">Deposit Due</span><span class="v">${{ number_format($order['deposit']) }}</span></div>
                        </div>
                        <a href="#" class="pe-muted" style="display:inline-block; font-weight:700; text-decoration:none; color:var(--pe-orange); margin-bottom:14px;">View Full Contract</a>
                        <label style="display:flex; align-items:flex-start; gap:10px; cursor:pointer;">
                            <input type="checkbox" style="width:17px; height:17px; margin-top:1px; accent-color:var(--pe-orange); flex-shrink:0;">
                            <span style="font-size:12.5px; color:var(--pe-ink-2);">I have read and agree to the Terms &amp; Conditions and accept the contract.</span>
                        </label>
                    </div>

                    {{-- Secure Your Date --}}
                    <div class="pe-card" style="margin-bottom:0;">
                        <h3 style="margin-bottom:12px;">Secure Your Date</h3>
                        @foreach([
                            ['⚡','Instant Confirmation','Your date is locked the moment your deposit clears.'],
                            ['🛡️','Protected Payments','Funds are held in secure escrow until services are delivered.'],
                            ['🔄','Free Date Changes (1x)','Reschedule your event once at no extra cost.'],
                        ] as $point)
                            <div style="display:flex; gap:10px; padding:9px 0; border-bottom:1px dashed var(--pe-line-2);">
                                <span style="font-size:16px; flex-shrink:0;">{{ $point[0] }}</span>
                                <div>
                                    <div style="font-weight:800; font-size:13px;">{{ $point[1] }}</div>
                                    <div class="pe-muted">{{ $point[2] }}</div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>

                {{-- Buyer Protection strip --}}
                <div class="pe-card" style="margin-top:18px; display:flex; align-items:center; gap:10px; flex-wrap:wrap;">
                    <span style="font-size:16px;">🛡️</span>
                    <div style="font-weight:800; font-size:13px;">Buyer Protection</div>
                    <div class="pe-muted" style="font-size:12.5px;">Secure Escrow Payments · Verified Professionals · Dedicated Customer Support</div>
                </div>

                {{-- Actions --}}
                <div class="pe-actions" style="margin-top:22px;">
                    <a href="{{ route('client.post-event.combinations') }}" class="pe-btn pe-btn-ghost">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><line x1="19" y1="12" x2="5" y2="12"/><polyline points="12 19 5 12 12 5"/></svg>
                        Back: Package Combinations
                    </a>
                    <button type="button" class="pe-btn pe-btn-ghost">Save for Later</button>
                </div>
            </div>

            {{-- Rail --}}
            <aside class="pe-rail">
                <div class="pe-rail-card">
                    <h4>Payment Summary</h4>
                    <div class="pe-rail-row"><span class="k">Package Combination Total</span><span class="v">${{ number_format($order['packageTotal']) }}</span></div>
                    <div class="pe-rail-row"><span class="k">Add-Ons Total</span><span class="v">${{ number_format($order['addonsTotal']) }}</span></div>
                    <div class="pe-rail-row"><span class="k">Estimated Tax (6%)</span><span class="v">${{ number_format($order['tax']) }}</span></div>
                    <div class="pe-rail-row"><span class="k" style="font-weight:800; color:var(--pe-ink);">Estimated Total</span><span class="v" style="font-size:16px;">${{ number_format($order['total']) }}</span></div>
                    <div class="pe-check" style="color:var(--pe-green); font-weight:700; margin-top:10px;">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"/></svg>
                        Within your budget of {{ $summary['budget'] }}
                    </div>
                </div>

                <div class="pe-rail-card">
                    <h4>Deposit &amp; Payment</h4>
                    <div class="pe-rail-row"><span class="k">Deposit Due Today (30%)</span><span class="v" style="font-size:16px;">${{ number_format($order['deposit']) }}</span></div>
                    <p class="pe-muted" style="margin:10px 0 14px;">Your date is not reserved until the deposit is paid.</p>

                    @foreach(['Credit/Debit Card','Bank Transfer (ACH)','PayPal'] as $i => $method)
                        <label style="display:flex; align-items:center; gap:10px; padding:10px 12px; border:1px solid var(--pe-line); border-radius:10px; margin-bottom:8px; cursor:pointer;">
                            <input type="radio" name="pay_method" @checked($i === 0) style="width:16px; height:16px; accent-color:var(--pe-orange); flex-shrink:0;">
                            <span style="font-size:13px; font-weight:700; color:var(--pe-ink-2);">{{ $method }}</span>
                        </label>
                    @endforeach

                    <a href="{{ route('client.post-event.confirmed') }}" class="pe-btn" style="width:100%; margin-top:8px;">🔒 Pay Deposit Securely</a>
                </div>
            </aside>
        </div>
    </div>
</div>
@endsection
