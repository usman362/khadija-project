@extends('layouts.client')

@section('title', $event->title)
@section('page-title', 'Event Details')

@section('content')
    @if ($errors->any())
        <div class="cl-card" style="background:#fef2f2;border:1px solid #fecaca;color:#b91c1c;padding:12px 16px;margin-bottom:18px;font-size:13.5px;">
            @foreach ($errors->all() as $error)<div>{{ $error }}</div>@endforeach
        </div>
    @endif

    {{-- ── Header ───────────────────────────────────────────── --}}
    @php
        $evProposals = $event->bookings->where('status', 'requested')->count();
        $evBids      = isset($bids) ? $bids->count() : 0;
        $evDaysToGo  = $event->starts_at ? (int) round(now()->startOfDay()->diffInDays($event->starts_at->startOfDay(), false)) : null;
    @endphp

    <style>
        .ev-hero { position: relative; overflow: hidden; border-radius: 18px; border: 1px solid var(--border-color); background: var(--bg-card); padding: 22px 24px; margin-bottom: 18px; }
        .ev-hero::before { content: ''; position: absolute; inset: 0 0 auto 0; height: 100%; background: radial-gradient(620px 220px at 4% 0%, rgba(249,115,22,.10), transparent 60%); pointer-events: none; }
        .ev-hero > * { position: relative; }
        .ev-back { display: inline-flex; align-items: center; gap: 6px; color: var(--text-muted); text-decoration: none; font-size: 12.5px; font-weight: 600; margin-bottom: 12px; }
        .ev-back:hover { color: var(--accent-orange, #f97316); }
        .ev-title { font-size: 26px; font-weight: 800; letter-spacing: -0.01em; color: var(--text-primary); margin: 0 0 9px; }
        .ev-chips { display: flex; gap: 7px; align-items: center; flex-wrap: wrap; }
        .ev-cat { font-size: 11.5px; font-weight: 700; color: var(--text-secondary); background: var(--bg-subtle, rgba(0,0,0,.04)); border: 1px solid var(--border-color); border-radius: 999px; padding: 3px 10px; }
        .ev-meta { display: flex; flex-wrap: wrap; gap: 18px; margin-top: 16px; padding-top: 14px; border-top: 1px solid var(--border-color); }
        .ev-meta div { display: flex; align-items: center; gap: 7px; font-size: 13px; color: var(--text-secondary); font-weight: 600; }
        .ev-meta svg { width: 15px; height: 15px; color: var(--accent-orange, #f97316); flex-shrink: 0; }
        .ev-stats { display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 12px; margin-bottom: 20px; }
        .ev-stat { border: 1px solid var(--border-color); border-radius: 14px; background: var(--bg-card); padding: 14px 16px; }
        .ev-stat b { display: block; font-size: 22px; font-weight: 800; color: var(--text-primary); line-height: 1.1; }
        .ev-stat span { display: block; font-size: 11.5px; font-weight: 700; text-transform: uppercase; letter-spacing: .4px; color: var(--text-muted); margin-top: 4px; }

        /* Request type + scope chips */
        .ev-type { font-size: 11px; font-weight: 800; border-radius: 6px; padding: 3px 9px; letter-spacing: .3px; }
        .ev-type-BR { background: rgba(37,99,235,.13); color: var(--info-text); }
        .ev-type-ER { background: rgba(220,38,38,.13); color: var(--bad-text); }
        .ev-type-DR { background: rgba(124,58,237,.13); color: var(--accent-text); }
        .ev-scope { font-size: 11px; font-weight: 700; border-radius: 6px; padding: 3px 9px; background: var(--bg-subtle, rgba(0,0,0,.04)); color: var(--text-secondary); border: 1px solid var(--border-color); }

        /* Tabs */
        .ev-tabs { display: flex; gap: 4px; flex-wrap: wrap; border-bottom: 1px solid var(--border-color); margin-bottom: 20px; }
        .ev-tab { display: inline-flex; align-items: center; gap: 6px; padding: 10px 15px; font-size: 13.5px; font-weight: 700; color: var(--text-muted); text-decoration: none; border-bottom: 2px solid transparent; margin-bottom: -1px; }
        .ev-tab:hover { color: var(--text-primary); }
        .ev-tab.on { color: var(--accent-orange, #f97316); border-bottom-color: var(--accent-orange, #f97316); }
        .ev-tab .n { font-size: 11px; font-weight: 800; background: var(--bg-subtle, rgba(0,0,0,.05)); border-radius: 999px; padding: 1px 7px; }
        .ev-tab.on .n { background: rgba(249,115,22,.15); color: var(--accent-orange, #f97316); }

        /* Requirements */
        .ev-req-row { display: flex; justify-content: space-between; gap: 16px; padding: 9px 0; border-bottom: 1px solid var(--border-color); font-size: 13.5px; }
        .ev-req-row:last-child { border-bottom: 0; }
        .ev-req-row span { color: var(--text-muted); font-weight: 600; }
        .ev-req-row b { color: var(--text-primary); font-weight: 700; text-align: right; }
        .ev-hint { display: block; margin-top: 9px; font-size: 12px; color: var(--warn-text); }

        /* Proposals */
        .ev-sealed { display: flex; gap: 8px; background: rgba(37,99,235,.07); border: 1px solid rgba(37,99,235,.2); border-radius: 12px; padding: 11px 14px; font-size: 12.5px; color: var(--text-secondary); line-height: 1.6; margin-bottom: 14px; }
        .ev-prop { display: flex; justify-content: space-between; gap: 16px; padding: 14px 0; border-bottom: 1px solid var(--border-color); }
        .ev-prop:last-child { border-bottom: 0; }
        .ev-prop-meta { display: flex; gap: 10px; flex-wrap: wrap; font-size: 12px; color: var(--text-muted); margin-top: 4px; }
        .ev-prop-note { font-size: 13px; color: var(--text-secondary); margin-top: 8px; line-height: 1.6; }

        /* Questions + activity + empty */
        .ev-q { border: 1px solid var(--border-color); border-radius: 12px; padding: 13px 15px; margin-bottom: 10px; }
        .ev-q-head { display: flex; justify-content: space-between; gap: 10px; font-size: 12.5px; margin-bottom: 6px; }
        .ev-q-head b { color: var(--text-primary); }
        .ev-q-head span { color: var(--text-muted); }
        .ev-q p { font-size: 13.5px; color: var(--text-secondary); line-height: 1.6; margin-bottom: 10px; }
        .ev-act-row { display: flex; gap: 12px; align-items: flex-start; padding: 11px 0; border-bottom: 1px solid var(--border-color); }
        .ev-act-row:last-child { border-bottom: 0; }
        .ev-act-ico { font-size: 15px; width: 26px; text-align: center; flex-shrink: 0; }
        .ev-empty { text-align: center; padding: 36px 20px; }
        .ev-empty b { display: block; font-size: 14.5px; color: var(--text-primary); margin-bottom: 5px; }
        .ev-empty p { font-size: 13px; color: var(--text-muted); margin-bottom: 12px; line-height: 1.6; }
    </style>

    <div class="ev-hero">
        <a href="{{ route('client.events.index') }}" class="ev-back">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="15 18 9 12 15 6"/></svg>
            Back to My Events
        </a>
        <div style="display: flex; justify-content: space-between; align-items: flex-start; flex-wrap: wrap; gap: 16px;">
            <div style="min-width:0;">
                <h2 class="ev-title">{{ $event->title }}</h2>
                <div class="ev-chips">
                    {{-- Publish state once; the workflow status only when it adds
                         something beyond "published" (confirmed / completed / …). --}}
                    {{-- Rule R33 §8 — the listing status in the client's own
                         words. "Open for Proposals", not "Open for Bidding";
                         the Bidding Board and R8's sealed bidding keep their
                         names, because those are the mechanism. --}}
                    @php $lifecycle = \App\Domain\Requests\RequestLifecycle::statusFor($event); @endphp
                    <span class="cl-badge {{ $lifecycle === 'expired' ? '' : 'cl-badge-published' }}"
                          @if($lifecycle === 'expired') style="background:#fef3c7;color:#b45309;" @endif>
                        {{ \App\Domain\Requests\RequestLifecycle::LABELS[$lifecycle] }}
                    </span>
                    @if(! in_array($event->status, ['published', 'pending'], true))
                        <span class="cl-badge cl-badge-{{ $event->status }}">{{ ucfirst(str_replace('_', ' ', $event->status)) }}</span>
                    @endif
                    {{-- Request TYPE and SCOPE, the same model the professional board
                         uses: BR is broadcast bidding, ER is that with urgency, DR
                         targets one professional. SSR/MSR is the service count. --}}
                    <span class="ev-type ev-type-{{ $type }}">{{ $type }}</span>
                    <span class="ev-scope">{{ $scope }} · {{ $scope === 'MSR' ? 'multi-service' : 'single service' }}</span>
                    @foreach($event->categories as $cat)
                        <span class="ev-cat">{{ $cat->name }}</span>
                    @endforeach
                </div>
            </div>
            <div style="display: flex; gap: 8px; flex-wrap: wrap;">
                <button type="button" class="cl-btn cl-btn-ghost cl-btn-sm" onclick="document.getElementById('editEventModal').classList.add('show')">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                    Edit
                </button>
                @if(!$event->is_published)
                    <form method="POST" action="{{ route('client.events.publish', $event) }}" style="display:inline;">
                        @csrf
                        <button type="submit" class="cl-btn cl-btn-primary cl-btn-sm" style="background:#f97316;border-color:#f97316;">
                            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 2 11 13"/><path d="M22 2 15 22l-4-9-9-4 20-7z"/></svg>
                            Publish Event
                        </button>
                    </form>
                @endif
            </div>
        </div>

        <div class="ev-meta">
            @if($event->starts_at)
                <div><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>{{ $event->starts_at->format('M d, Y · g:i A') }}</div>
            @endif
            @if($event->location)
                <div><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>{{ $event->location }}</div>
            @endif
            @if($event->budget)
                <div><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>${{ number_format($event->budget, 2) }}</div>
            @endif
            @if($event->guest_count)
                <div><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/></svg>{{ number_format($event->guest_count) }} guests</div>
            @endif
        </div>
    </div>

    {{-- ── Rule R33: an expired request, and what to do with it ──
         Only rendered when the request has actually expired. The options
         come from RequestLifecycle, never from this view: a page that
         offers a tier the backend refuses takes the client's money and
         then says no. --}}
    @if($lifecycle === 'expired')
        @php
            // No `use` here — Blade compiles this block inline, and a PHP
            // `use` statement inside an if-block is a parse error.
            $r33     = \App\Domain\Requests\RequestLifecycle::class;
            $inGrace = $r33::inGracePeriod($event);
            $options = $r33::extensionOptions($event);
            $used    = $r33::paidExtensionsUsed($event);
            $isEsr   = $r33::isEsr($event);
        @endphp

        <div class="cl-card" style="border:1px solid #fcd34d;background:#fffbeb;padding:18px 20px;margin-bottom:20px;">
            <div style="font-size:15px;font-weight:800;color:#92400e;margin-bottom:5px;">
                This request has expired
            </div>
            <p style="font-size:13px;color:#78350f;line-height:1.6;margin:0 0 14px;">
                The proposal deadline passed{{ $event->proposal_deadline ? ' on ' . $event->proposal_deadline->format('M j, g:i A') : '' }}.
                Nothing has been deleted — your proposals, messages and documents are all still here, and
                no new proposals can arrive until you reopen it.
            </p>

            @if($inGrace)
                {{-- §2 — the free reopen, once, inside 24 hours. --}}
                <form method="POST" action="{{ route('client.events.reopen', $event) }}"
                      style="display:flex;gap:8px;align-items:flex-end;flex-wrap:wrap;margin-bottom:14px;">
                    @csrf
                    <div>
                        <label style="display:block;font-size:12px;font-weight:700;color:#92400e;margin-bottom:4px;">
                            Reopen free — new deadline
                        </label>
                        <input type="datetime-local" name="proposal_deadline" required
                               max="{{ $event->starts_at?->format('Y-m-d\TH:i') }}"
                               style="padding:8px 10px;border:1px solid #fcd34d;border-radius:9px;font-size:13px;">
                    </div>
                    <button type="submit" class="cl-btn cl-btn-primary cl-btn-sm">Reopen at no cost</button>
                    <span style="font-size:11.5px;color:#92400e;align-self:center;">
                        Free for the first 24 hours after the deadline. Doesn't use one of your extensions.
                    </span>
                </form>
            @endif

            @if($isEsr)
                {{-- §5 — an emergency request's window is hours. Every paid
                     tier would land past the event, so none is offered. --}}
                <p style="font-size:12.5px;color:#78350f;line-height:1.6;margin:0 0 12px;">
                    Emergency requests can't be extended by days — the event is too close. You can close
                    this request, copy it as a new one, or turn it into a standard request if it is no
                    longer urgent.
                </p>
            @elseif($options !== [])
                <div style="font-size:12px;font-weight:700;color:#92400e;margin-bottom:8px;">
                    Extend it ({{ 3 - $used }} of 3 left)
                </div>
                <div style="display:flex;gap:8px;flex-wrap:wrap;margin-bottom:12px;">
                    @foreach($options as $option)
                        <form method="POST" action="{{ route('client.events.extend', $event) }}">
                            @csrf
                            <input type="hidden" name="days" value="{{ $option['days'] }}">
                            <input type="hidden" name="gateway" value="stripe">
                            <button type="submit" class="cl-btn cl-btn-ghost cl-btn-sm"
                                    style="border-color:#fcd34d;color:#92400e;">
                                +{{ $option['days'] }} days — ${{ number_format($option['price'], 2) }}
                                <span style="display:block;font-size:10.5px;font-weight:600;opacity:.75;">
                                    until {{ $option['new_deadline']->format('M j') }}
                                </span>
                            </button>
                        </form>
                    @endforeach
                </div>
            @elseif($used >= 3)
                <p style="font-size:12.5px;color:#78350f;line-height:1.6;margin:0 0 12px;">
                    You've used all three extensions on this request. You can close it, or copy it as a
                    fresh request — a copy starts over with a new set of extensions.
                </p>
            @else
                <p style="font-size:12.5px;color:#78350f;line-height:1.6;margin:0 0 12px;">
                    There isn't room to extend this one — a new deadline would fall after the event itself.
                    Move the event date first, or close and copy it.
                </p>
            @endif

            <div style="display:flex;gap:8px;flex-wrap:wrap;">
                <form method="POST" action="{{ route('client.events.duplicate', $event) }}">
                    @csrf
                    <button type="submit" class="cl-btn cl-btn-ghost cl-btn-sm">Copy as a new request</button>
                </form>
                <form method="POST" action="{{ route('client.events.close', $event) }}">
                    @csrf
                    <button type="submit" class="cl-btn cl-btn-ghost cl-btn-sm">Close this request</button>
                </form>
            </div>
        </div>
    @endif

    <div class="ev-stats">
        <div class="ev-stat"><b>{{ $evProposals }}</b><span>Proposals</span></div>
        <div class="ev-stat"><b>{{ $evBids }}</b><span>Sealed Bids</span></div>
        <div class="ev-stat"><b>{{ $event->categories->count() }}</b><span>Services</span></div>
        <div class="ev-stat">
            <b>{{ $evDaysToGo === null ? '—' : ($evDaysToGo > 0 ? $evDaysToGo : ($evDaysToGo === 0 ? 'Today' : 'Past')) }}</b>
            <span>{{ $evDaysToGo !== null && $evDaysToGo > 0 ? 'Days to go' : 'Event date' }}</span>
        </div>
    </div>


    {{-- Six tabs, server-rendered and switched by query string so each one is
         linkable and survives a reload. Peter's mockup has these as JS tabs;
         a client sharing a link to the Proposals tab is the common case. --}}
    @php
        $evTabs = [
            'overview'     => ['Overview', null],
            'requirements' => ['Requirements', $event->categories->count()],
            'proposals'    => ['Proposals', $bids->count()],
            // R60 — the guest list lives on the event it belongs to.
            'attendees'    => ['Attendees', $attendeeSummary['total']],
            'questions'    => ['Questions', $questions->count()],
            'files'        => ['Files', null],
            'activity'     => ['Activity', $activity->count()],
        ];
    @endphp
    <div class="ev-tabs">
        @foreach($evTabs as $key => [$label, $count])
            <a class="ev-tab {{ $tab === $key ? 'on' : '' }}"
               href="{{ route('client.events.show', ['event' => $event, 'tab' => $key]) }}">
                {{ $label }}@if($count !== null)<span class="n">{{ $count }}</span>@endif
            </a>
        @endforeach
    </div>

    @if($tab === 'overview')
    <div class="cl-grid cl-grid-3">
        {{-- ── Main column ───────────────────────────────────── --}}
        <div style="grid-column: span 2; display: flex; flex-direction: column; gap: 20px;">
            {{-- Description --}}
            <div class="cl-card">
                <h3 style="font-size: 16px; font-weight: 600; margin-bottom: 12px;">Description</h3>
                <p style="font-size: 14px; color: var(--text-secondary); line-height: 1.7;">
                    {{ $event->description ?: 'No description provided yet. Click Edit to add details about your event.' }}
                </p>
            </div>

            {{-- Tool results saved onto this event ("Add to my event") --}}
            @php
                $aiArtifacts = $event->aiArtifacts;
            @endphp
            @if($aiArtifacts->isNotEmpty() || ($availableArtifacts ?? collect())->isNotEmpty())
            <div class="cl-card" style="margin-top:20px;">
                {{-- R29 is platform-wide: no feature may claim or imply AI. The tools are
                     rules-based calculators and templates, so the heading says that. --}}
                <h3 style="font-size:16px;font-weight:600;margin-bottom:14px;">Toolkit Results ({{ $aiArtifacts->count() }})</h3>
                @if($aiArtifacts->isEmpty())
                    <p style="font-size:13px;color:var(--text-muted);margin:0 0 12px;">
                        Nothing on this request yet.
                    </p>
                @endif
                <div style="display:flex;flex-direction:column;gap:10px;">
                    @foreach($aiArtifacts as $art)
                        <div style="display:flex;align-items:center;gap:12px;border:1px solid var(--border-color);border-radius:12px;padding:11px 13px;">
                            <span style="font-size:20px;">{{ $art->icon() }}</span>
                            <div style="flex:1;min-width:0;">
                                <div style="font-size:13.5px;font-weight:700;color:var(--text-primary);">{{ $art->title }}</div>
                                <div style="font-size:11.5px;color:var(--text-muted);">{{ $art->tool_name }} · {{ $art->created_at->diffForHumans() }}{{ $art->mode === 'auto' ? ' · auto-attached' : '' }}</div>
                            </div>
                            <form method="POST" action="{{ route('client.ai-artifacts.destroy', $art) }}" onsubmit="return confirm('Remove this result from your event?');">
                                @csrf @method('DELETE')
                                <button type="submit" style="border:none;background:none;color:var(--bad-text);font-size:12px;font-weight:700;cursor:pointer;">Remove</button>
                            </form>
                        </div>
                    @endforeach
                </div>

                {{-- Checklist row 194 — "Add Tool Data".

                     The bridge only ever ran one way: a tool pushed its result
                     onto an event. A client on an open request had no way to
                     reach for something they had already worked out, and had
                     to go back and run the tool again.

                     What comes across is a COPY and a normal field — editable
                     and removable like any other detail. The row is explicit
                     that it is never locked or authoritative, which matters
                     given R29: these are calculators, and a figure a
                     calculator produced is still the client's to change. --}}
                @if(($availableArtifacts ?? collect())->isNotEmpty())
                    <details style="margin-top:14px;">
                        <summary style="cursor:pointer;font-size:13px;font-weight:700;color:var(--accent-orange,#f97316);">
                            + Add tool data from your other events
                        </summary>
                        <div style="display:flex;flex-direction:column;gap:8px;margin-top:11px;">
                            @foreach($availableArtifacts as $art)
                                <div style="display:flex;align-items:center;gap:12px;border:1px dashed var(--border-color);border-radius:11px;padding:10px 12px;">
                                    <span style="font-size:18px;">{{ $art->icon() }}</span>
                                    <div style="flex:1;min-width:0;">
                                        <div style="font-size:13px;font-weight:700;">{{ $art->title }}</div>
                                        <div style="font-size:11.5px;color:var(--text-muted);">
                                            {{ $art->tool_name }} · saved {{ $art->created_at->diffForHumans() }}
                                        </div>
                                    </div>
                                    <form method="POST" action="{{ route('client.ai-artifacts.copy', [$event, $art]) }}">
                                        @csrf
                                        <button type="submit" class="cl-btn cl-btn-ghost cl-btn-sm">Add</button>
                                    </form>
                                </div>
                            @endforeach
                        </div>
                        <p style="font-size:11.5px;color:var(--text-muted);margin:10px 0 0;">
                            Adding a result copies it here — your original stays on the other event, and this
                            copy is yours to edit or remove.
                        </p>
                    </details>
                @endif
            </div>
            @endif

            {{-- Proposals & Bookings --}}
            @php
                $proposals = $event->bookings->where('status', 'requested');
                $active    = $event->bookings->whereIn('status', ['confirmed', 'completed']);
            @endphp
            <div class="cl-card">
                <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:16px;">
                    <h3 style="font-size: 16px; font-weight: 600;">Professionals &amp; Proposals ({{ $event->bookings->count() }})</h3>
                    <a href="{{ route('client.search.index') }}" class="cl-btn cl-btn-ghost cl-btn-sm">
                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                        Find Professionals
                    </a>
                </div>
                @if($event->bookings->count())
                    <table class="cl-table">
                        <thead><tr><th>Professional</th><th>Status</th><th>Requested</th><th></th></tr></thead>
                        <tbody>
                            @foreach($event->bookings->sortByDesc('created_at') as $booking)
                            <tr>
                                <td style="color: var(--text-primary); font-weight: 500;">{{ $booking->supplier?->name ?? '—' }}</td>
                                <td><span class="cl-badge cl-badge-{{ $booking->status }}">{{ ucfirst($booking->status) }}</span></td>
                                <td style="color:var(--text-muted);">{{ $booking->created_at->format('M d, Y') }}</td>
                                <td style="text-align:right;">
                                    <a href="{{ route('client.bookings.index') }}" style="color:var(--accent-orange,#f97316);font-size:12.5px;font-weight:600;text-decoration:none;">View →</a>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                @else
                    <div style="text-align:center;padding:28px 16px;">
                        <div style="width:48px;height:48px;border-radius:12px;background:#fff4ec;color:#f97316;display:flex;align-items:center;justify-content:center;margin:0 auto 12px;">
                            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/></svg>
                        </div>
                        <p style="color: var(--text-muted); font-size: 14px; margin-bottom:14px;">No professionals yet. {{ $event->is_published ? 'Find and invite pros to start receiving proposals.' : 'Publish your event so professionals can find it — or search and invite them directly.' }}</p>
                        <a href="{{ route('client.search.index') }}" class="cl-btn cl-btn-primary cl-btn-sm" style="background:#f97316;border-color:#f97316;">Find Professionals</a>
                    </div>
                @endif
            </div>

            {{-- Sealed bids received — the client (event owner) sees every amount;
                 other professionals can't. Sorted lowest-first. --}}
            <div class="cl-card" style="margin-top:20px;">
                <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:6px;">
                    <h3 style="font-size:16px;font-weight:600;">🔒 Sealed Bids Received ({{ $bids->count() }})</h3>
                </div>
                <p style="font-size:12.5px;color:var(--text-muted);margin-bottom:16px;">
                    Bid amounts are hidden from other professionals — only you can see them here.
                </p>
                @if($bids->count())
                    <table class="cl-table">
                        <thead><tr><th>Professional</th><th>Bid Amount</th><th>Submitted</th><th></th></tr></thead>
                        <tbody>
                            @foreach($bids as $bid)
                            <tr>
                                <td style="color:var(--text-primary);font-weight:500;">
                                    {{ $bid->supplier?->name ?? '—' }}
                                    @if($loop->first)<span class="cl-badge" style="background:#ecfdf5;color:#065f46;margin-left:6px;">Lowest</span>@endif
                                </td>
                                <td style="color:var(--text-primary);font-weight:700;">${{ number_format($bid->amount) }}</td>
                                <td style="color:var(--text-muted);">{{ $bid->created_at->format('M d, Y') }}</td>
                                <td style="text-align:right;">
                                    <a href="{{ route('client.chat.index') }}" style="color:var(--accent-orange,#f97316);font-size:12.5px;font-weight:600;text-decoration:none;">Message →</a>
                                </td>
                            </tr>
                            @if($bid->note)
                            <tr><td colspan="4" style="color:var(--text-muted);font-size:12.5px;padding-top:0;">↳ {{ $bid->note }}</td></tr>
                            @endif
                            @endforeach
                        </tbody>
                    </table>
                @else
                    <div style="text-align:center;padding:24px 16px;color:var(--text-muted);font-size:13.5px;">
                        No sealed bids yet. {{ $event->is_published ? 'Professionals can bid on this event from their bidding board.' : 'Publish your event so professionals can place sealed bids.' }}
                    </div>
                @endif
            </div>
        </div>

        {{-- ── Right rail ────────────────────────────────────── --}}
        <div style="display: flex; flex-direction: column; gap: 20px;">
            <div class="cl-card">
                <h3 style="font-size: 16px; font-weight: 600; margin-bottom: 16px;">Event Details</h3>
                <div style="display: flex; flex-direction: column; gap: 15px;">
                    <div>
                        <div style="font-size: 12px; color: var(--text-muted); margin-bottom: 3px;">Start Date</div>
                        <div style="font-size: 14px; font-weight: 500;">{{ $event->starts_at?->format('M d, Y · h:i A') ?? '—' }}</div>
                    </div>
                    <div>
                        <div style="font-size: 12px; color: var(--text-muted); margin-bottom: 3px;">End Date</div>
                        <div style="font-size: 14px; font-weight: 500;">{{ $event->ends_at?->format('M d, Y · h:i A') ?? '—' }}</div>
                    </div>
                    <div>
                        <div style="font-size: 12px; color: var(--text-muted); margin-bottom: 3px;">Location</div>
                        <div style="font-size: 14px; font-weight: 500;">{{ $event->location ?: '—' }}</div>
                    </div>
                    <div>
                        <div style="font-size: 12px; color: var(--text-muted); margin-bottom: 3px;">Budget</div>
                        <div style="font-size: 14px; font-weight: 600; color:var(--ok-text);">{{ $event->budget ? '$'.number_format($event->budget, 2) : 'Not set' }}</div>
                    </div>
                    @if($event->categories->count())
                    <div>
                        <div style="font-size: 12px; color: var(--text-muted); margin-bottom: 5px;">Categories</div>
                        <div style="display: flex; flex-wrap: wrap; gap: 5px;">
                            @foreach($event->categories as $cat)
                                <span class="cl-badge" style="font-size: 12px;">{{ $cat->name }}</span>
                            @endforeach
                        </div>
                    </div>
                    @endif
                    <div>
                        <div style="font-size: 12px; color: var(--text-muted); margin-bottom: 3px;">Created</div>
                        <div style="font-size: 14px; font-weight: 500;">{{ $event->created_at->format('M d, Y') }}</div>
                    </div>
                    @if($event->supplier)
                    <div>
                        <div style="font-size: 12px; color: var(--text-muted); margin-bottom: 3px;">Assigned Professional</div>
                        <div style="font-size: 14px; font-weight: 500;">{{ $event->supplier->name }}</div>
                    </div>
                    @endif
                </div>
            </div>

            {{-- Quick actions --}}
            <div class="cl-card">
                <h3 style="font-size: 16px; font-weight: 600; margin-bottom: 14px;">Quick Actions</h3>
                <div style="display:flex;flex-direction:column;gap:9px;">
                    <a href="{{ route('client.search.index') }}" class="cl-btn cl-btn-ghost cl-btn-sm" style="justify-content:flex-start;">
                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                        Find Professionals
                    </a>
                    <button type="button" class="cl-btn cl-btn-ghost cl-btn-sm" style="justify-content:flex-start;" onclick="document.getElementById('editEventModal').classList.add('show')">
                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                        Edit Event
                    </button>
                    <a href="{{ route('ai-tools.budget-allocator') }}" class="cl-btn cl-btn-ghost cl-btn-sm" style="justify-content:flex-start;">
                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
                        Plan Budget
                    </a>
                </div>
            </div>
        </div>
    </div>
    @endif

    {{-- ── Requirements ─────────────────────────────────────── --}}
    @if($tab === 'requirements')
        <div class="cl-card">
            <h3 style="font-size:16px;font-weight:600;margin-bottom:14px;">What you asked for</h3>
            <div class="ev-req">
                <div class="ev-req-row"><span>Request type</span><b>{{ $type }} — {{ $type === 'BR' ? 'open to bidding' : ($type === 'ER' ? 'emergency, open to bidding' : 'direct to one professional') }}</b></div>
                <div class="ev-req-row"><span>Scope</span><b>{{ $scope }} — {{ $scope === 'MSR' ? 'multi-service' : 'single service' }}</b></div>
                <div class="ev-req-row"><span>Services requested</span><b>{{ $event->categories->pluck('name')->implode(', ') ?: '—' }}</b></div>
                <div class="ev-req-row"><span>Event date</span><b>{{ $event->starts_at?->format('M j, Y · g:i A') ?? 'Flexible' }}</b></div>
                <div class="ev-req-row"><span>Location</span><b>{{ $event->location ?: '—' }}</b></div>
                <div class="ev-req-row"><span>Guest count</span><b>{{ $event->guest_count ? number_format($event->guest_count) : '—' }}</b></div>
                <div class="ev-req-row"><span>Budget</span><b>{{ $event->budget ? '$' . number_format($event->budget) : 'Not stated' }}</b></div>
            </div>
            @if($event->description)
                <h4 style="font-size:13.5px;font-weight:700;margin:18px 0 8px;">Description</h4>
                <p style="font-size:13.5px;color:var(--text-secondary);line-height:1.7;white-space:pre-line;">{{ $event->description }}</p>
            @endif
            <div style="margin-top:18px;">
                <button type="button" class="cl-btn cl-btn-ghost cl-btn-sm" onclick="document.getElementById('editEventModal').classList.add('show')">Edit request</button>
                {{-- Peter's rule: the client may edit freely until a professional
                     is selected; after that the terms are being agreed. --}}
                @if($award)
                    <span class="ev-hint">A professional has been selected — major changes may require them to revise.</span>
                @endif
            </div>
        </div>
    @endif

    {{-- ── Proposals ────────────────────────────────────────── --}}
    @if($tab === 'proposals')
        <div class="cl-card">
            <div style="display:flex;justify-content:space-between;align-items:center;gap:12px;flex-wrap:wrap;margin-bottom:14px;">
                <h3 style="font-size:16px;font-weight:600;">Proposals received ({{ $bids->count() }})</h3>
                @if($bids->count() > 1)
                    <a class="cl-btn cl-btn-primary cl-btn-sm" style="background:#f97316;border-color:#f97316;"
                       href="{{ route('client.proposals.compare', $event) }}">Compare proposals</a>
                @endif
            </div>

            <div class="ev-sealed">🔒 <b>Sealed proposals.</b> Each amount is visible only to you and the professional who sent it. Competitors cannot see each other's bids.</div>

            @forelse($bids as $bid)
                @php $sup = $bid->supplier; $prof = $sup?->profile; @endphp
                <div class="ev-prop">
                    <div style="min-width:0;">
                        <div style="font-size:14.5px;font-weight:800;color:var(--text-primary);">{{ $sup?->name ?? 'Professional' }}</div>
                        <div class="ev-prop-meta">
                            @if($prof?->headline)<span>{{ $prof->headline }}</span>@endif
                            @if($prof?->city)<span>📍 {{ $prof->city }}</span>@endif
                            @if($bid->category)<span>{{ $bid->category->name }}</span>@endif
                            <span>Submitted {{ $bid->created_at->diffForHumans() }}</span>
                        </div>
                        @if($bid->note)<p class="ev-prop-note">{{ $bid->note }}</p>@endif
                    </div>
                    <div style="text-align:right;white-space:nowrap;">
                        <div style="font-size:19px;font-weight:800;color:var(--text-primary);">${{ number_format($bid->amount) }}</div>
                        @if($event->budget)
                            <div style="font-size:11.5px;font-weight:700;color:{{ $bid->amount <= $event->budget ? '#16a34a' : '#d97706' }};">
                                {{ $bid->amount <= $event->budget ? 'Within budget' : 'Above budget' }}
                            </div>
                        @endif
                        <div style="margin-top:8px;display:flex;gap:6px;justify-content:flex-end;">
                            <a class="cl-btn cl-btn-ghost cl-btn-sm" href="{{ route('public.professional.show', $sup) }}">View profile</a>
                        </div>
                    </div>
                </div>
            @empty
                <div class="ev-empty">
                    <b>No proposals yet</b>
                    <p>{{ $type === 'DR' ? 'The professional you sent this to has not responded yet.' : 'Professionals are being notified. Proposals appear here as they come in.' }}</p>
                </div>
            @endforelse
        </div>
    @endif

    {{-- ── Attendees (Rule R60) ─────────────────────────────── --}}
    @if($tab === 'attendees')
        @include('client.events._attendees')
    @endif

    {{-- ── Questions ────────────────────────────────────────── --}}
    @if($tab === 'questions')
        <div class="cl-card">
            <h3 style="font-size:16px;font-weight:600;margin-bottom:6px;">Clarifying questions ({{ $questions->count() }})</h3>
            <p style="font-size:12.5px;color:var(--text-muted);margin-bottom:14px;">
                Messages on a proposal thread that aren't a counter-offer. Answering one is visible only to that professional.
            </p>
            @forelse($questions as $q)
                <div class="ev-q">
                    <div class="ev-q-head">
                        <b>{{ $q['reply']->user?->name ?? 'Professional' }}</b>
                        <span>{{ $q['reply']->created_at->diffForHumans() }}</span>
                    </div>
                    <p>{{ $q['reply']->note }}</p>
                    <a class="cl-btn cl-btn-ghost cl-btn-sm" href="{{ route('client.proposals.compare', $event) }}">Reply on the proposal</a>
                </div>
            @empty
                <div class="ev-empty"><b>No questions yet</b><p>If a professional needs something clarified before bidding, it will show up here.</p></div>
            @endforelse
        </div>
    @endif

    {{-- ── Files ────────────────────────────────────────────── --}}
    @if($tab === 'files')
        <div class="cl-card">
            <h3 style="font-size:16px;font-weight:600;margin-bottom:6px;">Files</h3>
            {{-- There is no attachment model on requests yet, so this states that
                 plainly instead of rendering an upload box that drops the file. --}}
            <div class="ev-empty">
                <b>File attachments aren't available yet</b>
                <p>Briefs, floor plans and reference documents will attach to a request here once uploads are enabled. For now, share them in the message thread with a professional.</p>
                <a class="cl-btn cl-btn-ghost cl-btn-sm" href="{{ route('client.chat.index') }}">Open messages</a>
            </div>
        </div>
    @endif

    {{-- ── Activity ─────────────────────────────────────────── --}}
    @if($tab === 'activity')
        <div class="cl-card">
            <h3 style="font-size:16px;font-weight:600;margin-bottom:14px;">Activity</h3>
            <div class="ev-act">
                @foreach($activity as $a)
                    <div class="ev-act-row">
                        <span class="ev-act-ico">{{ $a['icon'] }}</span>
                        <div>
                            <div style="font-size:13.5px;color:var(--text-primary);font-weight:600;">{{ $a['text'] }}</div>
                            <div style="font-size:11.5px;color:var(--text-muted);">{{ $a['at']->format('M j, Y · g:i A') }} · {{ $a['at']->diffForHumans() }}</div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif


    {{-- ════════════ EDIT EVENT MODAL ════════════ --}}
    <div class="cl-modal-overlay" id="editEventModal">
        <div class="cl-modal" style="max-width: 720px;">
            <form method="POST" action="{{ route('client.events.update', $event) }}">
                @csrf
                @method('PATCH')
                <div class="cl-modal-header">
                    <div>
                        <div class="cl-modal-title">Edit Event</div>
                        <p style="font-size: 13px; color: var(--text-muted); margin-top: 4px;">Update your event details below.</p>
                    </div>
                    <button type="button" class="cl-modal-close" onclick="document.getElementById('editEventModal').classList.remove('show')">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                    </button>
                </div>
                <div class="cl-modal-body">
                    <div class="cl-form-group">
                        <label class="cl-form-label">Event Title *</label>
                        <input type="text" name="title" class="cl-form-input" value="{{ old('title', $event->title) }}" required>
                    </div>
                    <div class="cl-form-group">
                        <label class="cl-form-label">Description</label>
                        <textarea name="description" class="cl-form-textarea" rows="4" placeholder="Describe your event, expectations, and requirements...">{{ old('description', $event->description) }}</textarea>
                    </div>
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
                        <div class="cl-form-group">
                            <label class="cl-form-label">Start Date &amp; Time</label>
                            <input type="datetime-local" name="starts_at" class="cl-form-input" value="{{ old('starts_at', $event->starts_at?->format('Y-m-d\TH:i')) }}">
                        </div>
                        <div class="cl-form-group">
                            <label class="cl-form-label">End Date &amp; Time</label>
                            <input type="datetime-local" name="ends_at" class="cl-form-input" value="{{ old('ends_at', $event->ends_at?->format('Y-m-d\TH:i')) }}">
                        </div>
                    </div>
                    <div class="cl-form-group">
                        <label class="cl-form-label">Categories <span style="font-weight:400; color: var(--text-muted);">(select one or more)</span></label>
                        <div style="display:flex;flex-wrap:wrap;gap:8px;">
                            @foreach ($categories as $cat)
                                <label style="display:inline-flex;align-items:center;gap:7px;border:1px solid var(--border,#e2e8f0);border-radius:9px;padding:7px 12px;font-size:13px;cursor:pointer;">
                                    <input type="checkbox" name="category_ids[]" value="{{ $cat->id }}" {{ in_array($cat->id, old('category_ids', $selectedCategoryIds)) ? 'checked' : '' }}>
                                    {{ $cat->name }}
                                </label>
                            @endforeach
                        </div>
                    </div>
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
                        <div class="cl-form-group">
                            <label class="cl-form-label">Location</label>
                            <input type="text" name="location" class="cl-form-input" value="{{ old('location', $event->location) }}" placeholder="City, Venue, or Address">
                        </div>
                        <div class="cl-form-group">
                            <label class="cl-form-label">Budget <span style="opacity:.6;font-weight:400">(USD, optional)</span></label>
                            <input type="number" name="budget" class="cl-form-input" value="{{ old('budget', $event->budget) }}" placeholder="e.g. 2500" min="0" step="0.01">
                        </div>
                    </div>
                </div>
                <div class="cl-modal-footer">
                    <button type="button" class="cl-btn cl-btn-ghost" onclick="document.getElementById('editEventModal').classList.remove('show')">Cancel</button>
                    <button type="submit" class="cl-btn cl-btn-primary" style="background:#f97316;border-color:#f97316;">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/><polyline points="17 21 17 13 7 13 7 21"/><polyline points="7 3 7 8 15 8"/></svg>
                        Save Changes
                    </button>
                </div>
            </form>
        </div>
    </div>

    @if ($errors->any())
        <script>document.addEventListener('DOMContentLoaded', function(){ document.getElementById('editEventModal').classList.add('show'); });</script>
    @endif
@endsection
