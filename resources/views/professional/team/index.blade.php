@extends('layouts.professional')

@section('title', 'Team & Staffing')
@section('page-title', 'Team & Staffing')
@section('page-subtitle', 'The people you work with and the shifts they are on.')

{{-- Team & Staffing — full backend (StaffMember + Shift). Real crew,
     on-shift / open shifts, fill-shift, add staff/shift, and a real
     labor-cost estimate. --}}

@push('styles')
<style>
    .pt { --pt-blue: #2563eb; }
    .pt-card { background: var(--bg-card); border: 1px solid var(--border-color); border-radius: 16px; padding: 18px 20px; }

    /* Header */
    .pt-head { display: flex; align-items: center; gap: 20px; margin-bottom: 18px; }
    .pt-head-art { width: 92px; height: 92px; border-radius: 22px; background: linear-gradient(135deg,#dbeafe,#bfdbfe); display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
    .pt-head-art svg { width: 50px; height: 50px; color: var(--info-text); }
    .pt-head h1 { font-size: 30px; font-weight: 800; color: var(--text-primary); margin: 0; }
    .pt-head p { font-size: 14px; color: var(--text-muted); margin: 4px 0 0; }
    .pt-head-spacer { flex: 1; }
    .pt-chips { display: grid; grid-template-columns: repeat(4, auto); gap: 10px; }
    .pt-chip { background: var(--bg-card); border: 1px solid var(--border-color); border-radius: 12px; padding: 11px 12px; text-align: center; width: 100px; }
    .pt-chip svg { width: 22px; height: 22px; margin-bottom: 5px; }
    .pt-chip span { font-size: 10.5px; font-weight: 700; color: var(--text-secondary); line-height: 1.25; display: block; }

    /* Stat cards */
    .pt-stats { display: grid; grid-template-columns: repeat(3, 1fr); gap: 14px; margin-bottom: 16px; }
    .pt-stat { border-radius: 16px; padding: 18px 20px; display: flex; align-items: center; gap: 16px; border: 1px solid var(--border-color); }
    .pt-stat.s-blue { background: rgba(37,99,235,0.04); }
    .pt-stat.s-green { background: rgba(16,185,129,0.05); }
    .pt-stat.s-red { background: rgba(239,68,68,0.05); }
    .pt-stat-ico { width: 60px; height: 60px; border-radius: 50%; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
    .pt-stat-ico svg { width: 28px; height: 28px; }
    .s-blue .pt-stat-ico { background: rgba(37,99,235,0.12); color: var(--info-text); }
    .s-green .pt-stat-ico { background: rgba(16,185,129,0.12); color: var(--ok-text); }
    .s-red .pt-stat-ico { background: rgba(239,68,68,0.12); color: var(--bad-text); }
    .pt-stat-label { font-size: 13px; font-weight: 800; color: var(--text-primary); display: flex; align-items: center; gap: 7px; }
    .pt-stat-label .dot { width: 8px; height: 8px; border-radius: 50%; }
    .pt-stat-val { font-size: 34px; font-weight: 800; color: var(--text-primary); line-height: 1.1; }
    .pt-stat-sub { font-size: 11.5px; color: var(--text-muted); }

    /* Two columns: on-shift + open shifts */
    .pt-row { display: grid; grid-template-columns: 1fr 1fr; gap: 14px; margin-bottom: 16px; align-items: start; }
    .pt-sec-h { display: flex; align-items: center; gap: 9px; margin-bottom: 14px; }
    .pt-sec-h .dot { width: 11px; height: 11px; border-radius: 50%; }
    .pt-sec-h b { font-size: 16px; font-weight: 800; color: var(--text-primary); }
    .pt-sec-h .tag { margin-left: auto; font-size: 10px; font-weight: 800; padding: 3px 9px; border-radius: 6px; }
    .tag-live { background: rgba(16,185,129,0.12); color: var(--ok-text); }
    .tag-urgent { background: rgba(239,68,68,0.12); color: var(--bad-text); }

    .pt-avatars { display: flex; flex-wrap: wrap; gap: 12px; justify-content: space-between; padding: 6px 4px 14px; }
    .pt-av { text-align: center; }
    .pt-av-img { width: 50px; height: 50px; border-radius: 50%; position: relative; display: flex; align-items: center; justify-content: center; color: #fff; font-weight: 800; font-size: 16px; margin: 0 auto 5px; }
    .pt-av-img .on { position: absolute; bottom: 1px; right: 1px; width: 12px; height: 12px; border-radius: 50%; background: #10b981; border: 2px solid var(--bg-card); }
    .pt-av-name { font-size: 10.5px; font-weight: 700; color: var(--text-secondary); }
    .pt-onshift-foot { display: flex; align-items: center; gap: 10px; background: rgba(16,185,129,0.06); border: 1px solid rgba(16,185,129,0.18); border-radius: 11px; padding: 11px 13px; }
    .pt-onshift-foot .ic { width: 30px; height: 30px; border-radius: 8px; background: rgba(16,185,129,0.14); color: var(--ok-text); display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
    .pt-onshift-foot .ic svg { width: 15px; height: 15px; }
    .pt-onshift-foot .t b { font-size: 12px; color: var(--text-primary); display: block; }
    .pt-onshift-foot .t span { font-size: 10.5px; color: var(--text-muted); }
    .pt-onshift-foot a { margin-left: auto; font-size: 11.5px; font-weight: 800; color: var(--ok-text); text-decoration: none; white-space: nowrap; }

    .pt-shift { display: flex; align-items: center; gap: 11px; padding: 10px 0; border-bottom: 1px solid var(--border-color); }
    .pt-shift:last-child { border-bottom: none; }
    .pt-shift-pin { width: 26px; height: 26px; border-radius: 50%; background: rgba(239,68,68,0.1); color: var(--bad-text); display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
    .pt-shift-pin svg { width: 14px; height: 14px; }
    .pt-shift-body { flex: 1; min-width: 0; }
    .pt-shift-role { font-size: 12.5px; font-weight: 800; color: var(--text-primary); }
    .pt-shift-time { font-size: 11px; color: var(--text-muted); }
    .pt-fill { font-size: 11px; font-weight: 800; color: var(--bad-text); background: rgba(239,68,68,0.09); border: none; border-radius: 8px; padding: 7px 14px; cursor: pointer; white-space: nowrap; }
    .pt-fill:hover { background: rgba(239,68,68,0.16); }
    .pt-empty { padding: 22px 10px; text-align: center; color: var(--text-muted); font-size: 12.5px; }

    /* Manage staffing button + panel */
    .pt-manage-btn { display: flex; align-items: center; justify-content: center; gap: 9px; width: 320px; max-width: 100%; margin: 4px auto 18px; padding: 13px; border-radius: 11px; background: #2563eb; color: #fff; border: none; font-size: 14px; font-weight: 800; cursor: pointer; }
    .pt-manage-btn svg { width: 17px; height: 17px; }
    .pt-manage-panel { display: none; grid-template-columns: 1fr 1fr; gap: 14px; margin-bottom: 18px; }
    .pt-manage-panel.open { display: grid; }
    .pt-form-h { font-size: 13px; font-weight: 800; color: var(--text-primary); margin-bottom: 10px; }
    .pt-field { margin-bottom: 9px; }
    .pt-field label { display: block; font-size: 11px; font-weight: 700; color: var(--text-secondary); margin-bottom: 4px; }
    .pt-input { width: 100%; height: 36px; border: 1px solid var(--border-color); border-radius: 8px; padding: 0 10px; font-size: 12.5px; font-family: inherit; color: var(--text-primary); background: var(--bg-card-hover); outline: none; }
    .pt-input:focus { border-color: #2563eb; }
    .pt-grid2 { display: grid; grid-template-columns: 1fr 1fr; gap: 9px; }
    .pt-form-btn { width: 100%; padding: 9px; border-radius: 9px; background: #2563eb; color: #fff; border: none; font-size: 12.5px; font-weight: 800; cursor: pointer; margin-top: 5px; }

    /* Feature cards */
    .pt-feats { display: grid; grid-template-columns: repeat(4, 1fr); gap: 14px; }
    .pt-feat { border-radius: 14px; padding: 16px; border: 1px solid var(--border-color); }
    .pt-feat.f1 { background: rgba(37,99,235,0.04); }
    .pt-feat.f2 { background: rgba(16,185,129,0.04); }
    .pt-feat.f3 { background: rgba(139,92,246,0.04); }
    .pt-feat.f4 { background: rgba(249,115,22,0.05); }
    .pt-feat-h { display: flex; align-items: center; gap: 9px; margin-bottom: 6px; }
    .pt-feat-ico { width: 34px; height: 34px; border-radius: 9px; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
    .pt-feat-ico svg { width: 17px; height: 17px; }
    .f1 .pt-feat-ico { background: rgba(37,99,235,0.14); color: var(--info-text); }
    .f2 .pt-feat-ico { background: rgba(16,185,129,0.14); color: var(--ok-text); }
    .f3 .pt-feat-ico { background: rgba(139,92,246,0.14); color: var(--accent-text); }
    .f4 .pt-feat-ico { background: rgba(249,115,22,0.14); color: var(--brand-text); }
    .pt-feat-nm { font-size: 13px; font-weight: 800; color: var(--text-primary); }
    .pt-feat p { font-size: 11px; color: var(--text-muted); line-height: 1.4; margin: 0 0 10px; }
    .pt-labor-total-k { font-size: 10px; color: var(--text-muted); text-align: center; }
    .pt-labor-total { font-size: 26px; font-weight: 800; color: var(--ok-text); text-align: center; }
    .pt-labor-meta { font-size: 11px; color: var(--text-muted); text-align: center; margin-top: 4px; }
    .pt-feat-btn { display: flex; align-items: center; justify-content: center; gap: 7px; width: 100%; padding: 9px; border-radius: 9px; border: none; font-size: 12px; font-weight: 800; cursor: pointer; color: #fff; text-decoration: none; }

    @media (max-width: 1200px) { .pt-head { flex-wrap: wrap; } .pt-chips { grid-template-columns: repeat(4, 1fr); width: 100%; } .pt-chip { width: auto; } .pt-feats { grid-template-columns: 1fr 1fr; } }
    @media (max-width: 760px) { .pt-stats, .pt-row, .pt-feats, .pt-manage-panel.open { grid-template-columns: 1fr; } .pt-chips { grid-template-columns: 1fr 1fr; } }
</style>
@endpush

@section('content')
<div class="pt">

    @if(session('status'))
        <div style="background:rgba(16,185,129,0.1);border:1px solid rgba(16,185,129,0.3);color:var(--ok-text);border-radius:10px;padding:11px 16px;margin-bottom:16px;font-size:13px;font-weight:600;">{{ session('status') }}</div>
    @endif

    {{-- Header --}}
    <div class="pt-head">
        <div class="pt-head-art"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg></div>
        <div>
            <h1>Team &amp; Staffing</h1>
            <p>Manage staff, on-shift confirmations and staffing needs.</p>
        </div>
        <span class="pt-head-spacer"></span>
        <div class="pt-chips">
            <div class="pt-chip"><svg viewBox="0 0 24 24" fill="none" stroke="#2563eb" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/></svg><span>Track Team Headcount</span></div>
            <div class="pt-chip"><svg viewBox="0 0 24 24" fill="none" stroke="#10b981" stroke-width="2"><path d="M9 11l3 3L22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/></svg><span>Monitor On-Shift</span></div>
            <div class="pt-chip"><svg viewBox="0 0 24 24" fill="none" stroke="#8b5cf6" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg><span>Spot Open Shifts</span></div>
            <div class="pt-chip"><svg viewBox="0 0 24 24" fill="none" stroke="#2563eb" stroke-width="2"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg><span>Keep Their Details To Hand</span></div>
        </div>
    </div>

    {{-- Stats --}}
    <div class="pt-stats">
        <div class="pt-stat s-blue">
            <span class="pt-stat-ico"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/></svg></span>
            <div><div class="pt-stat-label">Total Staff</div><div class="pt-stat-val">{{ $stats['total_staff'] }}</div><div class="pt-stat-sub">All available workers</div></div>
        </div>
        <div class="pt-stat s-green">
            <span class="pt-stat-ico"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="5" r="2"/><path d="M5 22l4-9 4 3 3-2"/><path d="M9 13l-2-3 5-1 3 3"/></svg></span>
            <div><div class="pt-stat-label"><span class="dot" style="background:#10b981;"></span>On Shift</div><div class="pt-stat-val">{{ $stats['on_shift'] }}</div><div class="pt-stat-sub">Actively working now</div></div>
        </div>
        <div class="pt-stat s-red">
            <span class="pt-stat-ico"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="3" y1="10" x2="21" y2="10"/><line x1="12" y1="14" x2="12" y2="17"/><line x1="12" y1="19" x2="12.01" y2="19"/></svg></span>
            <div><div class="pt-stat-label"><span class="dot" style="background:#ef4444;"></span>Open Shifts</div><div class="pt-stat-val">{{ $stats['open_shifts'] }}</div><div class="pt-stat-sub">Need to be filled</div></div>
        </div>
    </div>

    {{-- On-shift + open shifts --}}
    <div class="pt-row">
        <div class="pt-card">
            <div class="pt-sec-h"><span class="dot" style="background:#10b981;"></span><b>Current On-Shift ({{ $onShift->count() }})</b><span class="tag tag-live">Live</span></div>
            @if($onShift->count())
                {{-- Deep enough for the white initials that sit on them to stay legible. --}}
                @php $avColors = ['#be185d','#1d4ed8','#047857','#6d28d9','#b45309','#c2410c','#475569','#0e7490']; @endphp
                <div class="pt-avatars">
                    @foreach($onShift as $i => $sh)
                        <div class="pt-av">
                            <div class="pt-av-img" style="background:{{ $avColors[$i % count($avColors)] }};">{{ $sh->staff?->initials() ?? '–' }}<span class="on"></span></div>
                            <div class="pt-av-name">{{ \Illuminate\Support\Str::of($sh->staff?->name ?? '')->explode(' ')->first() }}</div>
                        </div>
                    @endforeach
                </div>
                <div class="pt-onshift-foot">
                    <span class="ic"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg></span>
                    {{-- This said "Tap to message team" and linked to Messages,
                         which is the client conversation inbox. A staff member
                         has no account to be messaged at: Sir Peter decided on
                         29 October against individual staff logins. Their phone
                         and email sit on their own record instead. --}}
                    <div class="t"><b>{{ $onShift->count() }} {{ $onShift->count() === 1 ? 'worker' : 'workers' }} on shift</b><span>Their contact details are on the crew list below</span></div>
                </div>
            @else
                <div class="pt-empty">
                    No one is on shift right now.
                    @if(($upcomingShifts ?? collect())->isNotEmpty())
                        Next: {{ $upcomingShifts->first()->staff?->name }} on {{ $upcomingShifts->first()->starts_at?->format('M d') }} at {{ $upcomingShifts->first()->starts_at?->format('g:i A') }}.
                    @else
                        Fill an open shift to put your crew to work.
                    @endif
                </div>
            @endif
        </div>

        <div class="pt-card">
            <div class="pt-sec-h"><span class="dot" style="background:#ef4444;"></span><b>Open Shifts ({{ $openShifts->count() }})</b>@if($openShifts->count())<span class="tag tag-urgent">Urgent</span>@endif</div>
            @forelse($openShifts as $sh)
                <div class="pt-shift">
                    <span class="pt-shift-pin"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg></span>
                    <div class="pt-shift-body">
                        <div class="pt-shift-role">{{ $sh->role }}{{ $sh->slots > 1 ? ' ('.$sh->slots.')' : '' }}</div>
                        <div class="pt-shift-time">{{ $sh->starts_at?->format('M d') }} · {{ $sh->starts_at?->format('g:i A') }} – {{ $sh->ends_at?->format('g:i A') }}</div>
                    </div>
                    <form method="POST" action="{{ route('professional.team.shifts.fill', $sh) }}">@csrf<button class="pt-fill" type="submit">Fill Shift</button></form>
                </div>
            @empty
                {{-- "Your crew is fully booked" was printed even with nobody on
                     the crew, which reads as an answer about people who do not
                     exist. --}}
                <div class="pt-empty">@if($staff->isEmpty())No open shifts, and nobody on your crew yet. Add somebody below, then create a shift.@else No open shifts. Everyone you have is accounted for. Create a shift below to staff up.@endif</div>
            @endforelse
        </div>
    </div>

    {{-- The crew itself.

         The page could add people and never show them. On-shift avatars
         answered "who is working now", which is a different question from
         "who is on my team", and that second one is the list a professional
         picks a point of contact from. --}}
    <div class="pt-card" style="margin-top:20px;">
        <div class="pt-sec-h"><span class="dot" style="background:#2563eb;"></span><b>Your Crew ({{ $staff->count() }})</b></div>
        @forelse($staff as $i => $member)
            @php $avColors = ['#be185d','#1d4ed8','#047857','#6d28d9','#b45309','#c2410c','#475569','#0e7490']; @endphp
            <div style="display:flex;align-items:center;gap:13px;padding:12px 0;border-top:{{ $i ? '1px solid var(--border-color)' : '0' }};">
                @if($member->photoUrl())
                    <img src="{{ $member->photoUrl() }}" alt=""
                         style="width:40px;height:40px;border-radius:50%;object-fit:cover;flex-shrink:0;">
                @else
                    <div style="width:40px;height:40px;border-radius:50%;flex-shrink:0;display:flex;align-items:center;
                                justify-content:center;font-size:13px;font-weight:700;color:#fff;
                                background:{{ $avColors[$i % count($avColors)] }};">{{ $member->initials() }}</div>
                @endif
                <div style="flex:1;min-width:0;">
                    <div style="font-size:14px;font-weight:700;color:var(--text-primary);">
                        {{ $member->name }}
                        @if($member->is_supervisor)
                            <span style="margin-left:6px;padding:2px 8px;border-radius:999px;font-size:10.5px;
                                         font-weight:800;letter-spacing:.3px;text-transform:uppercase;
                                         background:rgba(37,99,235,0.14);color:var(--info-text);">Supervisor</span>
                        @endif
                    </div>
                    {{-- Joined in PHP: Blade ignores a directive that follows
                         a word character, so "@endif@if" prints rather than
                         compiles. --}}
                    <div style="font-size:12.5px;color:var(--text-muted);margin-top:2px;">
                        {{ implode(' · ', array_filter([$member->public_id, $member->role, $member->phone])) }}
                    </div>
                </div>
            </div>
        @empty
            <div class="pt-empty">Nobody on your team yet. Add your crew below, and you can send one of them to a job in your place.</div>
        @endforelse
    </div>

    {{-- Manage staffing --}}
    <button class="pt-manage-btn" type="button" onclick="document.getElementById('pt-manage').classList.toggle('open')">Add Crew and Shifts <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg></button>

    <div class="pt-manage-panel" id="pt-manage">
        <div class="pt-card">
            <div class="pt-form-h">Add Team Member</div>
            {{-- enctype, because the photo is a file. Without it the form
                 posts the filename and the upload silently never arrives. --}}
            <form method="POST" action="{{ route('professional.team.staff.store') }}" enctype="multipart/form-data">@csrf
                <div class="pt-field"><label>Name</label><input class="pt-input" name="name" required placeholder="e.g. Emma Martinez"></div>
                <div class="pt-grid2">
                    <div class="pt-field"><label>Role</label><input class="pt-input" name="role" placeholder="Server"></div>
                    <div class="pt-field"><label>Hourly Rate ($)</label><input class="pt-input" name="hourly_rate" type="number" step="0.01" placeholder="25"></div>
                </div>
                <div class="pt-grid2">
                    <div class="pt-field"><label>Phone</label><input class="pt-input" name="phone" placeholder="Optional"></div>
                    <div class="pt-field"><label>Email</label><input class="pt-input" name="email" type="email" placeholder="Optional"></div>
                </div>
                {{-- The one thing here a client ever sees, so it says so. --}}
                <div class="pt-field">
                    <label for="pt-photo">Photo</label>
                    <input class="pt-input" id="pt-photo" name="photo" type="file" accept="image/*">
                    <span style="display:block;font-size:11.5px;color:var(--text-muted);margin-top:5px;line-height:1.5;">
                        Shown to a client when this person is the point of contact for their event.
                    </span>
                </div>
                {{-- A rank the person holds, not a job for one event: the
                     supervisor is often not the one the client rings. --}}
                <div class="pt-field">
                    <label style="display:flex;gap:9px;align-items:flex-start;cursor:pointer;font-weight:400;">
                        <input type="checkbox" name="is_supervisor" value="1" style="margin-top:3px;flex-shrink:0;">
                        <span>Supervisor
                            <span style="display:block;font-size:11.5px;color:var(--text-muted);margin-top:3px;line-height:1.5;">
                                Can be put in charge on site. Separate from who the client contacts.
                            </span>
                        </span>
                    </label>
                </div>
                <button class="pt-form-btn" type="submit">+ Add Member</button>
            </form>
        </div>
        <div class="pt-card">
            <div class="pt-form-h">Create Shift</div>
            <form method="POST" action="{{ route('professional.team.shifts.store') }}">@csrf
                <div class="pt-grid2">
                    <div class="pt-field"><label>Role</label><input class="pt-input" name="role" required placeholder="Server (Evening)"></div>
                    <div class="pt-field"><label>Slots</label><input class="pt-input" name="slots" type="number" min="1" value="1"></div>
                </div>
                <div class="pt-field"><label>Location</label><input class="pt-input" name="location" placeholder="Optional"></div>
                <div class="pt-grid2">
                    <div class="pt-field"><label>Starts</label><input class="pt-input" name="starts_at" type="datetime-local" required></div>
                    <div class="pt-field"><label>Ends</label><input class="pt-input" name="ends_at" type="datetime-local" required></div>
                </div>
                <button class="pt-form-btn" type="submit">+ Create Shift</button>
            </form>
        </div>
    </div>

    {{-- Feature cards --}}
    {{-- Four cards stood here and two of them described features that do
         not exist.

         "Interactive Shift Scheduler: drag and drop staff into open shifts"
         had no button and there is no drag and drop anywhere on this page.

         "Team Chat: message on-shift workers instantly" opened the client
         conversation inbox. A staff member cannot be messaged at all: Sir
         Peter decided on 29 October against individual staff logins, so they
         are named records with a phone number, not accounts.

         "Send Shift Requests: notify available staff to claim open shifts"
         notified nobody. Its button opened the panel above and scrolled to
         it, and a staff member has no account to claim a shift with.

         The labour estimate is real and stays. --}}
    <div class="pt-feats">
        <div class="pt-feat f3">
            <div class="pt-feat-h"><span class="pt-feat-ico"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg></span><b>Labour This Week</b></div>
            {{-- Filled shifts only, which is what the controller counts: an
                 open shift has nobody on it and so no rate to cost. Saying
                 "this week's shifts" would read as all of them. --}}
            <p>Shifts you have filled this week, at your crew's hourly rates.</p>
            <div class="pt-labor-total-k">Estimated Total</div>
            <div class="pt-labor-total">${{ number_format($labor['cost'], 2) }}</div>
            <div class="pt-labor-meta">{{ $labor['hours'] }} hrs · {{ $labor['workers'] }} {{ $labor['workers'] === 1 ? 'worker' : 'workers' }}</div>
            @if(($labor['unrated'] ?? 0) > 0)
                <div class="pt-labor-meta" style="color:var(--brand-text);margin-top:4px;">
                    {{ $labor['unrated'] }} of them {{ $labor['unrated'] === 1 ? 'has' : 'have' }} no hourly rate on file, so {{ $labor['unrated'] === 1 ? 'their' : 'those' }} hours are not in this total.
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
