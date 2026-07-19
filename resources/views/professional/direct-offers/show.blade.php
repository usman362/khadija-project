@extends('layouts.professional')

@section('title', 'Direct Offer Received')

@push('styles')
<style>
    .do { --do: #2563eb; }
    .do-back { display:inline-flex; align-items:center; gap:7px; font-size:13px; color:var(--text-muted); text-decoration:none; }
    .do-back svg { width:15px; height:15px; }
    .do-pill { font-size:11.5px; font-weight:800; color:#d97706; background:rgba(217,119,6,0.12); padding:4px 12px; border-radius:999px; }
    .do-card { background:var(--bg-card); border:1px solid var(--border-color); border-radius:16px; padding:22px; }
    .do-h-top { display:flex; align-items:center; justify-content:space-between; gap:14px; flex-wrap:wrap; margin-bottom:14px; }
    .do-title { font-size:22px; font-weight:800; color:var(--text-primary); display:flex; align-items:center; gap:10px; flex-wrap:wrap; }
    .do-badge { font-size:11px; font-weight:800; color:#7c3aed; background:rgba(124,58,237,0.12); padding:3px 9px; border-radius:6px; }
    .do-sub { font-size:12.5px; color:var(--text-muted); margin-top:5px; }
    .do-btn { display:inline-flex; align-items:center; justify-content:center; gap:8px; padding:11px 18px; border-radius:10px; font-size:13.5px; font-weight:800; cursor:pointer; font-family:inherit; text-decoration:none; border:1px solid transparent; }
    .do-btn.primary { background:var(--do); color:#fff; border:none; }
    .do-btn.ghost { background:var(--bg-card); color:#dc2626; border:1px solid #fca5a5; }
    .do-btn svg { width:15px; height:15px; }

    .do-summary { display:grid; grid-template-columns:1.4fr 1fr 1fr; gap:22px; padding-top:18px; border-top:1px solid var(--border-color); margin-top:6px; }
    .do-req { display:flex; align-items:center; gap:13px; }
    .do-req-av { width:54px; height:54px; border-radius:50%; background:#2563eb; color:#fff; display:flex; align-items:center; justify-content:center; font-size:18px; font-weight:800; flex-shrink:0; }
    .do-req b { font-size:15px; font-weight:800; color:var(--text-primary); display:flex; align-items:center; gap:6px; }
    .do-req .verif { color:#2563eb; }
    .do-req span { font-size:12px; color:var(--text-muted); display:block; margin-top:2px; }
    .do-k { font-size:11.5px; color:var(--text-muted); }
    .do-v { font-size:20px; font-weight:800; color:var(--text-primary); margin-top:3px; }
    .do-v.warn { color:#dc2626; }
    .do-v small { font-size:12px; color:var(--text-muted); font-weight:600; }

    .do-grid { display:grid; grid-template-columns:minmax(0,1fr) minmax(0,340px); gap:18px; align-items:start; margin-top:18px; }
    .do-sticky { position:sticky; top:16px; display:flex; flex-direction:column; gap:16px; }
    .do-sec-h { display:flex; align-items:center; gap:9px; font-size:16px; font-weight:800; color:var(--text-primary); margin-bottom:16px; }
    .do-sec-h svg { width:18px; height:18px; color:var(--do); }
    .do-row { display:grid; grid-template-columns:170px 1fr; gap:12px; padding:9px 0; border-bottom:1px solid var(--border-color); font-size:13px; }
    .do-row:last-child { border-bottom:none; }
    .do-row .rk { color:var(--text-muted); }
    .do-row .rv { color:var(--text-primary); font-weight:600; }
    .do-chip { font-size:11.5px; font-weight:700; padding:2px 9px; border-radius:6px; background:rgba(37,99,235,0.1); color:#2563eb; display:inline-block; }

    .do-services { display:grid; grid-template-columns:1fr 1fr; gap:9px 18px; }
    .do-srv { display:flex; align-items:center; gap:9px; font-size:13px; color:var(--text-primary); }
    .do-srv svg { width:16px; height:16px; color:#16a34a; flex-shrink:0; }
    .do-note-box { background:var(--bg-card-hover); border:1px solid var(--border-color); border-radius:10px; padding:13px; font-size:13px; color:var(--text-secondary); line-height:1.6; }
    .do-twocol { display:grid; grid-template-columns:1fr 1fr; gap:14px; }
    .do-mini-h { font-size:13px; font-weight:800; color:var(--text-primary); margin-bottom:7px; }
    .do-venue-warn { background:rgba(217,119,6,0.08); border:1px solid rgba(217,119,6,0.25); border-radius:10px; padding:12px 14px; }
    .do-venue-warn div { display:flex; align-items:center; gap:8px; font-size:12.5px; color:#b45309; padding:3px 0; }
    .do-venue-warn svg { width:15px; height:15px; flex-shrink:0; }
    .do-files { display:grid; grid-template-columns:repeat(4,1fr); gap:12px; }
    .do-file { border:1px solid var(--border-color); border-radius:11px; padding:12px; text-align:center; }
    .do-file .ic { width:40px; height:40px; border-radius:9px; background:rgba(220,38,38,0.1); color:#dc2626; display:flex; align-items:center; justify-content:center; margin:0 auto 8px; }
    .do-file .ic svg { width:18px; height:18px; }
    .do-file b { font-size:11.5px; color:var(--text-primary); word-break:break-word; display:block; }

    /* response center */
    .do-action { padding-bottom:16px; margin-bottom:16px; border-bottom:1px solid var(--border-color); }
    .do-action:last-child { border-bottom:none; margin-bottom:0; padding-bottom:0; }
    .do-action-h { display:flex; align-items:center; gap:9px; font-size:14px; font-weight:800; color:var(--text-primary); margin-bottom:4px; }
    .do-num { width:22px; height:22px; border-radius:7px; background:var(--do); color:#fff; font-size:12px; font-weight:800; display:flex; align-items:center; justify-content:center; flex-shrink:0; }
    .do-num.red { background:#dc2626; }
    .do-action p { font-size:12px; color:var(--text-muted); margin:0 0 11px 31px; line-height:1.45; }
    .do-action-btn { display:block; width:100%; box-sizing:border-box; text-align:center; padding:11px; border-radius:9px; font-size:13px; font-weight:800; cursor:pointer; font-family:inherit; text-decoration:none; border:1px solid var(--do); color:var(--do); background:var(--bg-card); }
    .do-action-btn.solid { background:var(--do); color:#fff; border:none; }
    .do-action-btn.danger { border-color:#fca5a5; color:#dc2626; }
    .do-action small { display:block; font-size:11px; color:var(--text-muted); margin:8px 0 0 31px; }

    .do-ta { width:100%; box-sizing:border-box; min-height:80px; border:1px solid var(--border-color); border-radius:9px; background:var(--bg-card); color:var(--text-primary); font-size:12.5px; padding:10px; font-family:inherit; resize:vertical; }
    .do-fld { margin-bottom:11px; }
    .do-fld label { font-size:11.5px; font-weight:700; color:var(--text-muted); display:block; margin-bottom:5px; }
    .do-fld select, .do-fld input { width:100%; box-sizing:border-box; padding:9px 11px; border:1px solid var(--border-color); border-radius:8px; background:var(--bg-card); color:var(--text-primary); font-size:12.5px; font-family:inherit; }
    .do-sub-row { display:flex; align-items:center; gap:9px; padding:7px 0; font-size:12.5px; }
    .do-sub-av { width:30px; height:30px; border-radius:50%; background:#2563eb; color:#fff; display:flex; align-items:center; justify-content:center; font-size:11px; font-weight:800; flex-shrink:0; }
    .do-sub-row b { font-size:12.5px; color:var(--text-primary); display:block; }
    .do-sub-row .tag { margin-left:auto; font-size:10.5px; color:var(--text-muted); }

    @media (max-width: 1080px) { .do-grid { grid-template-columns:1fr; } .do-sticky { position:static; } .do-summary { grid-template-columns:1fr; gap:14px; } }
    @media (max-width: 640px) { .do-services, .do-twocol, .do-files { grid-template-columns:1fr; } .do-row { grid-template-columns:1fr; gap:2px; } }
</style>
@endpush

@section('content')
<div class="do">

    @if(session('status'))
        <div style="background:#dcfce7;border:1px solid #bbf7d0;color:#15803d;border-radius:10px;padding:11px 15px;margin-bottom:16px;font-size:13.5px;font-weight:600;">✅ {{ session('status') }}</div>
    @endif

    {{-- header card --}}
    <div class="do-card" style="margin-bottom:18px;">
        <div style="display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap;margin-bottom:12px;">
            <a href="{{ route('professional.chat.index') }}" class="do-back"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="15 18 9 12 15 6"/></svg>Back to Direct Offers</a>
            <span class="do-pill">{{ $offer['status'] }}</span>
        </div>
        <div class="do-h-top">
            <div>
                <div class="do-title">{{ $offer['title'] }} <span class="do-badge">{{ $offer['request_type'] }}</span> <span style="font-size:12.5px;font-weight:600;color:var(--text-muted);">{{ $offer['request_label'] }}</span></div>
                <div class="do-sub">Direct Request ID: {{ $offer['id'] }} · Received on {{ $offer['received_at'] }}</div>
            </div>
            <div style="display:flex;gap:10px;">
                @if(($offer['is_open'] ?? false) && ($offer['event_id'] ?? null))
                    <form method="POST" action="{{ route('professional.direct-offers.decline', $offer['event_id']) }}">
                        @csrf
                        <button type="submit" class="do-btn ghost">Decline Request</button>
                    </form>
                    <form method="POST" action="{{ route('professional.direct-offers.accept', $offer['event_id']) }}">
                        @csrf
                        <button type="submit" class="do-btn primary">Accept Offer</button>
                    </form>
                @else
                    <span class="do-btn ghost" style="cursor:default;">{{ $offer['status'] }}</span>
                @endif
            </div>
        </div>
        <div class="do-summary">
            <div>
                <div class="do-k">Requested by</div>
                <div class="do-req" style="margin-top:8px;">
                    <span class="do-req-av">{{ strtoupper(substr($offer['client']['name'],0,1)) }}</span>
                    <div>
                        <b>{{ $offer['client']['name'] }} @if($offer['client']['verified'])<svg class="verif" width="15" height="15" viewBox="0 0 24 24" fill="currentColor"><path d="M9 12l2 2 4-4M12 2a10 10 0 100 20 10 10 0 000-20z" fill="none" stroke="currentColor" stroke-width="2"/></svg>@endif</b>
                        <span>{{ $offer['client']['tier'] }} · {{ $offer['client']['completed'] }} Previous Events Completed</span>
                    </div>
                </div>
            </div>
            <div>
                <div class="do-k">Offer Amount <span style="opacity:.7;">(Target)</span></div>
                <div class="do-v" style="color:#16a34a;">${{ number_format($offer['offer_min']) }} – ${{ number_format($offer['offer_max']) }} <small>USD</small></div>
                <div class="do-k" style="margin-top:3px;">{{ $offer['budget_note'] }}</div>
            </div>
            <div>
                <div class="do-k">Response Deadline</div>
                <div class="do-v">{{ $offer['response_deadline'] }}</div>
                <div class="do-k" style="color:#dc2626;margin-top:3px;font-weight:700;">{{ $offer['days_remaining'] }} Days Remaining</div>
            </div>
        </div>
    </div>

    {{-- 2-column: content + sticky actions --}}
    <div class="do-grid">
        {{-- main content --}}
        <div style="display:flex;flex-direction:column;gap:18px;">
            <div class="do-card">
                <div class="do-sec-h"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12"/><line x1="12" y1="8" x2="12.01" y2="8"/></svg>Request Overview</div>
                <div class="do-row"><span class="rk">Request Type</span><span class="rv"><span class="do-chip">{{ $offer['request_type'] }}</span> {{ $offer['request_label'] }}</span></div>
                @foreach($offer['overview'] as $k => $v)
                    <div class="do-row"><span class="rk">{{ $k }}</span><span class="rv">{{ $v }}</span></div>
                @endforeach
            </div>

            <div class="do-card">
                <div class="do-sec-h"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>Event Details</div>
                @foreach($offer['event'] as $k => $v)
                    <div class="do-row"><span class="rk">{{ $k }}</span><span class="rv">{{ $v }}</span></div>
                @endforeach
            </div>

            <div class="do-card">
                <div class="do-sec-h"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 11l3 3L22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/></svg>Services Requested</div>
                <div class="do-services">
                    @foreach($offer['services'] as $srv)
                        <div class="do-srv"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>{{ $srv }}</div>
                    @endforeach
                </div>
                <div style="margin-top:16px;">
                    <div class="do-mini-h">Service Details / Notes</div>
                    <div class="do-note-box">{{ $offer['service_notes'] }}</div>
                </div>
                <div class="do-twocol" style="margin-top:14px;">
                    <div><div class="do-mini-h">Equipment Needed</div><div class="do-note-box">{{ $offer['equipment'] }}</div></div>
                    <div><div class="do-mini-h">Quantity / Scale</div><div class="do-note-box">{{ $offer['quantity'] }}</div></div>
                </div>
                <div style="margin-top:14px;">
                    <div class="do-mini-h">Venue Notes</div>
                    <div class="do-venue-warn">
                        @foreach($offer['venue_notes'] as $note)
                            <div><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M10.29 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/></svg>{{ $note }}</div>
                        @endforeach
                    </div>
                </div>
            </div>

            <div class="do-card">
                <div class="do-sec-h"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21.44 11.05l-9.19 9.19a6 6 0 0 1-8.49-8.49l9.19-9.19a4 4 0 0 1 5.66 5.66l-9.2 9.19a2 2 0 0 1-2.83-2.83l8.49-8.48"/></svg>Attachments &amp; Files ({{ count($offer['attachments']) }})</div>
                <div class="do-files">
                    @foreach($offer['attachments'] as $file)
                        <div class="do-file"><span class="ic"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg></span><b>{{ $file }}</b></div>
                    @endforeach
                </div>
            </div>

            <div class="do-card">
                <div class="do-sec-h"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>Additional Notes from Client</div>
                <div class="do-note-box">{{ $offer['client_note'] }}</div>
            </div>
        </div>

        {{-- sticky action sidebar --}}
        <div class="do-sticky">
            <div class="do-card">
                <div class="do-sec-h" style="margin-bottom:6px;">Your Response Center</div>
                <p style="font-size:12px;color:var(--text-muted);margin:0 0 16px;">Review details and choose how you'd like to respond.</p>

                @if(($offer['is_open'] ?? false) && ($offer['event_id'] ?? null))
                    <div class="do-action">
                        <div class="do-action-h"><span class="do-num">1</span>Accept the Offer</div>
                        <p>Take the job at the client's terms — a confirmed booking is created.</p>
                        <form method="POST" action="{{ route('professional.direct-offers.accept', $offer['event_id']) }}">@csrf<button type="submit" class="do-action-btn solid">Accept Offer</button></form>
                    </div>
                    <div class="do-action">
                        <div class="do-action-h"><span class="do-num">2</span>Message the Client</div>
                        <p>Ask questions or discuss the details before you respond.</p>
                        <a href="{{ route('professional.chat.index') }}" class="do-action-btn">Open Messages</a>
                    </div>
                    <div class="do-action">
                        <div class="do-action-h"><span class="do-num red">3</span>Decline Request</div>
                        <p>Not the right fit? Politely pass on this request.</p>
                        <form method="POST" action="{{ route('professional.direct-offers.decline', $offer['event_id']) }}">@csrf<button type="submit" class="do-action-btn danger">Decline This Request</button></form>
                    </div>
                @else
                    <div class="do-action">
                        <p style="margin:0;">This offer is <b>{{ $offer['status'] }}</b>. You can still message the client below.</p>
                        <a href="{{ route('professional.chat.index') }}" class="do-action-btn" style="margin-top:10px;">Open Messages</a>
                    </div>
                @endif
            </div>

            <div class="do-card">
                <div class="do-sec-h" style="font-size:14px;"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>Internal Notes <span style="font-size:11px;color:var(--text-muted);font-weight:600;">(Private)</span></div>
                <textarea class="do-ta" placeholder="Add internal notes about this request, client preferences, pricing ideas, etc..."></textarea>
            </div>

            <div class="do-card">
                <div class="do-sec-h" style="font-size:14px;"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/></svg>Estimate &amp; Planning</div>
                <div class="do-fld"><label>Target Profit Margin</label><select><option>{{ $offer['planning']['target_margin'] }}</option></select></div>
                <div class="do-twocol">
                    <div class="do-fld"><label>Staff Availability</label><select><option>{{ $offer['planning']['staff'] }}</option></select></div>
                    <div class="do-fld"><label>Potential Conflicts</label><select><option>{{ $offer['planning']['conflicts'] }}</option></select></div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
