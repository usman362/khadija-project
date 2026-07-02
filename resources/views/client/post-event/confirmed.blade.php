@extends('layouts.client')
@section('title', 'Post an Event — Order Confirmed')
@include('client.post-event._styles')

@section('content')
<div class="pe-wrap">
    @include('client.post-event._wizard')

    <div class="pe-container pe-main">
        {{-- Confirmation header --}}
        <div style="display:flex; align-items:flex-start; justify-content:space-between; gap:18px; flex-wrap:wrap; margin-bottom:22px;">
            <div style="display:flex; align-items:flex-start; gap:16px;">
                <span style="width:56px; height:56px; border-radius:50%; background:var(--pe-green-l); color:var(--pe-green); display:flex; align-items:center; justify-content:center; flex-shrink:0;">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" style="width:28px; height:28px;"><polyline points="20 6 9 17 4 12"/></svg>
                </span>
                <div>
                    <h1 class="pe-h1" style="margin-bottom:2px;">Your Order is Confirmed!</h1>
                    <p class="pe-sub" style="margin:0 0 8px;">Thank you! Your package combination has been booked and your date is secured.</p>
                    <div style="display:flex; align-items:center; gap:14px; flex-wrap:wrap; font-size:13px;">
                        <span style="font-weight:800; color:var(--pe-ink);">Order Number {{ $order['number'] }}</span>
                        <span class="pe-muted">Placed on {{ $order['placed'] }}</span>
                    </div>
                </div>
            </div>
            <div style="display:flex; align-items:center; gap:10px; flex-wrap:wrap;">
                <a href="#" class="pe-btn pe-btn-ghost">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
                    View / Download Receipt
                </a>
                <a href="#" class="pe-btn">View Order Details</a>
            </div>
        </div>

        <div class="pe-grid">
            {{-- Main --}}
            <div>
                {{-- Order summary card --}}
                <div class="pe-card" style="padding:0; overflow:hidden;">
                    <div style="height:170px; background-image:url('https://images.unsplash.com/{{ $order['img'] }}?w=600&q=80&auto=format&fit=crop'); background-size:cover; background-position:center;"></div>
                    <div style="padding:20px 22px;">
                        <div style="display:flex; align-items:center; gap:10px; flex-wrap:wrap; margin-bottom:4px;">
                            <h2 style="margin:0; font-size:18px;">{{ $order['combo'] }}</h2>
                            <span class="pe-badge green">{{ $order['match'] }}% Match</span>
                        </div>
                        <div class="pe-muted" style="margin-bottom:12px;">By {{ implode(' & ', $order['vendors']) }}</div>

                        <div style="display:flex; flex-wrap:wrap; gap:18px; font-size:13px; color:var(--pe-ink-2); font-weight:700; padding-bottom:16px; border-bottom:1px solid var(--pe-line-2);">
                            <span>{{ count($order['services']) }} Services</span>
                            <span>{{ $summary['guests'] }} Guests</span>
                            <span>{{ $summary['date'] }}</span>
                            <span>{{ $summary['location'] }}</span>
                        </div>

                        {{-- Payments row --}}
                        <div style="display:grid; grid-template-columns:repeat(3,1fr); gap:16px; margin-top:16px;">
                            <div>
                                <div class="pe-muted" style="font-weight:700; margin-bottom:2px;">Total Paid</div>
                                <div style="font-size:18px; font-weight:800; color:var(--pe-green);">${{ number_format($order['deposit']) }}</div>
                                <div class="pe-muted">Deposit 30%</div>
                            </div>
                            <div>
                                <div class="pe-muted" style="font-weight:700; margin-bottom:2px;">Remaining Balance</div>
                                <div style="font-size:18px; font-weight:800; color:var(--pe-ink);">${{ number_format($order['remaining']) }}</div>
                                <div class="pe-muted">Due before {{ $order['balanceDue'] }}</div>
                            </div>
                            <div>
                                <div class="pe-muted" style="font-weight:700; margin-bottom:2px;">Payment Method</div>
                                <div style="font-size:15px; font-weight:800; color:var(--pe-ink);">Visa •••• 4242</div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Order Progress timeline --}}
                <div class="pe-card">
                    <h2 style="margin-bottom:18px;">Order Progress</h2>
                    @php
                        $stages = [
                            ['label' => 'Order Confirmed', 'state' => 'done'],
                            ['label' => 'Planning & Preparation', 'state' => 'active'],
                            ['label' => 'Event Day', 'state' => ''],
                            ['label' => 'Review & Approval', 'state' => ''],
                            ['label' => 'Complete', 'state' => ''],
                        ];
                    @endphp
                    <div style="display:flex; align-items:flex-start;">
                        @foreach($stages as $stage)
                            @php
                                $isDone = $stage['state'] === 'done';
                                $isActive = $stage['state'] === 'active';
                                $dotBg = $isDone ? 'var(--pe-green)' : ($isActive ? 'var(--pe-orange)' : '#eef1f5');
                                $dotColor = ($isDone || $isActive) ? '#fff' : 'var(--pe-muted)';
                                $lineOn = $isDone;
                            @endphp
                            <div style="flex:1; text-align:center; position:relative;">
                                @unless($loop->first)
                                    <span style="position:absolute; top:14px; right:50%; width:100%; height:3px; background:{{ $stages[$loop->index - 1]['state'] === 'done' ? 'var(--pe-green)' : 'var(--pe-line)' }};"></span>
                                @endunless
                                <span style="position:relative; z-index:1; width:30px; height:30px; border-radius:50%; background:{{ $dotBg }}; color:{{ $dotColor }}; display:inline-flex; align-items:center; justify-content:center; font-size:13px; font-weight:800;">
                                    @if($isDone)
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" style="width:15px; height:15px;"><polyline points="20 6 9 17 4 12"/></svg>
                                    @else
                                        {{ $loop->iteration }}
                                    @endif
                                </span>
                                <div style="margin-top:8px; font-size:12px; font-weight:700; color:{{ $isActive ? 'var(--pe-orange)' : ($isDone ? 'var(--pe-ink-2)' : 'var(--pe-muted)') }};">{{ $stage['label'] }}</div>
                                @if($isActive)<div style="font-size:10.5px; font-weight:800; color:var(--pe-orange); margin-top:2px;">In Progress</div>@endif
                            </div>
                        @endforeach
                    </div>
                </div>

                {{-- Your Services --}}
                <div class="pe-card">
                    <h2 style="margin-bottom:14px;">Your Services ({{ count($order['services']) }})</h2>
                    <div class="pe-svc-grid" style="grid-template-columns:repeat(3,1fr);">
                        @foreach($order['services'] as $i => $svc)
                            @php $confirmed = $i < 4; @endphp
                            <div style="border:1px solid var(--pe-line); border-radius:12px; padding:12px 14px;">
                                <div style="font-size:13px; font-weight:800; color:var(--pe-ink); margin-bottom:6px;">{{ $svc }}</div>
                                @if($confirmed)
                                    <span class="pe-badge green">Confirmed</span>
                                @else
                                    <span class="pe-badge orange">In Progress</span>
                                @endif
                            </div>
                        @endforeach
                    </div>
                </div>

                {{-- What's Next --}}
                <div class="pe-card">
                    <h2 style="margin-bottom:14px;">What is Next?</h2>
                    @php
                        $next = [
                            ['Vendor is preparing your event', 'Your professionals have begun planning and will reach out with details soon.'],
                            ['Stay in touch', 'Use Messages to coordinate details, share inspiration, and ask questions.'],
                            ['Review your timeline', 'Track milestones as your event date approaches.'],
                            ['Final payment reminder', 'Your remaining balance is due before ' . $order['balanceDue'] . '.'],
                        ];
                    @endphp
                    <div style="display:grid; grid-template-columns:1fr 1fr; gap:14px;">
                        @foreach($next as $n)
                            <div style="display:flex; gap:12px; align-items:flex-start;">
                                <span style="width:34px; height:34px; border-radius:9px; background:#ffedd5; color:var(--pe-orange-d); display:flex; align-items:center; justify-content:center; flex-shrink:0;">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" style="width:17px; height:17px;"><circle cx="12" cy="12" r="9"/><polyline points="12 7 12 12 15 14"/></svg>
                                </span>
                                <div>
                                    <div style="font-size:13.5px; font-weight:800; color:var(--pe-ink); margin-bottom:2px;">{{ $n[0] }}</div>
                                    <div class="pe-muted">{{ $n[1] }}</div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>

                {{-- Bottom nav --}}
                <div class="pe-actions" style="margin-top:22px;">
                    <a href="{{ route('client.dashboard') }}" class="pe-btn pe-btn-ghost">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><line x1="19" y1="12" x2="5" y2="12"/><polyline points="12 19 5 12 12 5"/></svg>
                        Back to Dashboard
                    </a>
                    <div style="display:flex; align-items:center; gap:12px; flex-wrap:wrap;">
                        <a href="{{ route('client.post-event.final-payment') }}" class="pe-btn pe-btn-ghost">Proceed to Final Payment</a>
                        <a href="{{ route('client.chat.index') }}" class="pe-btn">Go to Messages
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg>
                        </a>
                    </div>
                </div>
            </div>

            {{-- Rail --}}
            <aside class="pe-rail">
                @include('client.post-event._rail')

                <div class="pe-rail-card">
                    <h4>Order Totals</h4>
                    <div class="pe-rail-row"><span class="k">Services</span><span class="v">{{ count($order['services']) }}</span></div>
                    <div class="pe-rail-row"><span class="k">Total Price</span><span class="v">${{ number_format($order['total']) }}</span></div>
                    <div class="pe-rail-row"><span class="k">Deposit Paid</span><span class="v" style="color:var(--pe-green);">${{ number_format($order['deposit']) }}</span></div>
                    <div class="pe-rail-row"><span class="k">Remaining</span><span class="v">${{ number_format($order['remaining']) }}</span></div>
                </div>

                <div class="pe-rail-card">
                    <h4>Messages & Updates</h4>
                    <div style="display:flex; gap:10px; align-items:flex-start;">
                        <span style="width:34px; height:34px; border-radius:50%; background:var(--pe-purple-l); color:var(--pe-purple); display:flex; align-items:center; justify-content:center; font-weight:800; flex-shrink:0; font-size:13px;">EE</span>
                        <div>
                            <div style="font-size:13px; font-weight:800; color:var(--pe-ink);">Elite Events Co.</div>
                            <p class="pe-muted" style="margin:2px 0 0; line-height:1.5;">We are thrilled to work with you! We will share a detailed planning timeline within a few days.</p>
                        </div>
                    </div>
                    <a href="{{ route('client.chat.index') }}" class="pe-btn pe-btn-ghost" style="width:100%; margin-top:12px;">Open Messages</a>
                </div>

                <div class="pe-rail-card">
                    <h4>Documents & Contracts</h4>
                    @php
                        $docs = [
                            ['Event Services Agreement', 'Signed', 'green'],
                            ['Invoice & Receipt', 'Paid', 'green'],
                            ['Payment Schedule', 'View', 'orange'],
                            ['Event Details & Timeline', 'View', 'orange'],
                        ];
                    @endphp
                    @foreach($docs as $doc)
                        <div class="pe-rail-row">
                            <span class="k" style="color:var(--pe-ink-2); font-weight:700;">{{ $doc[0] }}</span>
                            <span class="v"><span class="pe-badge {{ $doc[2] }}">{{ $doc[1] }}@if($doc[2] === 'green') ✓@endif</span></span>
                        </div>
                    @endforeach
                </div>

                <div class="pe-rail-card pe-rail-why">
                    <h4>Need Help?</h4>
                    <p class="pe-muted" style="margin:-6px 0 10px;">Our team is here to guide you through every step of your event.</p>
                    <a href="{{ route('client.chat.index') }}" class="pe-btn pe-btn-purple" style="width:100%;">Chat with a Specialist</a>
                    <p class="pe-muted" style="margin:10px 0 0; text-align:center;">Dedicated support</p>
                </div>
            </aside>
        </div>
    </div>
</div>
@endsection
