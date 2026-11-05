@php
    $money = fn ($n) => '$' . number_format((float) $n, 0);
    // Row 133 — the badge comes from GigStats' own definitions now. The map
    // that used to sit here badged a `requested` booking "In Progress" while
    // the In Progress count above it meant an event running right now: the
    // same two words for two different things, on one screen.
    $gigDeposits = \App\Support\GigStatus::depositsFor($gigs);
    // Row 130 — the sample cards read from the page's one shared today rather
    // than carrying dates typed in by hand, which drifted out of date the
    // moment they were written.
    $sampleDate = $today->copy()->addDays(21)->format('M j');
    $thumbs = ['linear-gradient(135deg,#f59e0b,#b45309)','linear-gradient(135deg,#10b981,#047857)','linear-gradient(135deg,#8b5cf6,#6d28d9)','linear-gradient(135deg,#2563eb,#1d4ed8)','linear-gradient(135deg,#ec4899,#be185d)'];
@endphp

@push('styles')
<style>
    .pg { --pg-blue: #2563eb; }
    .pg-card { background: var(--bg-card); border: 1px solid var(--border-color); border-radius: 16px; padding: 18px 20px; }

    .pg-top { display: grid; grid-template-columns: minmax(0,0.85fr) minmax(0,1.15fr); gap: 18px; align-items: start; margin-bottom: 20px; }

    /* Left intro */
    .pg-art { width: 110px; height: 100px; display: flex; align-items: center; justify-content: center; margin-bottom: 8px; }
    .pg-art svg { width: 100%; height: auto; }
    .pg-intro h1 { font-size: 30px; font-weight: 800; color: var(--text-primary); margin: 0; }
    .pg-intro .tag { font-size: 14px; color: var(--text-secondary); margin: 4px 0 10px; }
    .pg-intro p { font-size: 12.5px; color: var(--text-muted); line-height: 1.6; margin: 0 0 14px; }
    .pg-feat { display: flex; gap: 11px; padding: 11px 13px; border-radius: 11px; margin-bottom: 8px; }
    .pg-feat.x1 { background: rgba(139,92,246,0.06); } .pg-feat.x2 { background: rgba(16,185,129,0.06); }
    .pg-feat.x3 { background: rgba(249,115,22,0.06); } .pg-feat.x4 { background: rgba(37,99,235,0.06); }
    .pg-feat-ico { width: 30px; height: 30px; border-radius: 8px; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
    .pg-feat-ico svg { width: 15px; height: 15px; }
    .pg-feat b { font-size: 12.5px; color: var(--text-primary); }
    .pg-feat p { font-size: 11px; color: var(--text-muted); margin: 2px 0 0; line-height: 1.4; }

    /* Right gig card */
    .pg-hub-h { display: flex; align-items: center; gap: 12px; margin-bottom: 14px; }
    .pg-hub-ico { width: 48px; height: 48px; border-radius: 50%; background: #eef2ff; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
    .pg-hub-ico svg { width: 30px; height: 28px; }
    .pg-hub-h b { font-size: 17px; font-weight: 800; color: var(--text-primary); }
    .pg-hub-h p { font-size: 12px; color: var(--text-muted); margin: 1px 0 0; }
    .pg-scores { display: grid; grid-template-columns: repeat(3, 1fr); gap: 10px; margin-bottom: 14px; }
    .pg-score { border: 1px solid var(--border-color); border-radius: 11px; padding: 12px 14px; display: flex; align-items: center; justify-content: space-between; }
    .pg-score-k { font-size: 11px; color: var(--text-muted); font-weight: 600; }
    .pg-score-v { font-size: 24px; font-weight: 800; color: var(--text-primary); }
    .pg-score svg { width: 18px; height: 18px; color: var(--text-muted); }
    .pg-gig { display: flex; align-items: center; gap: 12px; padding: 12px 0; border-top: 1px solid var(--border-color); }
    .pg-gig-thumb { width: 60px; height: 46px; border-radius: 9px; flex-shrink: 0; display: flex; align-items: center; justify-content: center; color: rgba(255,255,255,0.85); }
    .pg-gig-thumb svg { width: 18px; height: 18px; }
    .pg-gig-mid { flex: 1; min-width: 0; }
    .pg-gig-name { font-size: 13px; font-weight: 800; color: var(--text-primary); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .pg-gig-meta { font-size: 11px; color: var(--text-muted); }
    .pg-gig-svc { font-size: 11px; color: var(--text-muted); }
    .pg-gig-price b { font-size: 12.5px; font-weight: 800; color: var(--text-primary); white-space: nowrap; }
    .pg-gig-status { font-size: 11px; font-weight: 700; }
    .pg-gig-stats { display: flex; align-items: center; gap: 14px; flex-shrink: 0; }
    .pg-gig-stat { display: inline-flex; align-items: center; gap: 5px; font-size: 12px; font-weight: 700; color: var(--text-secondary); }
    .pg-gig-stat svg { width: 14px; height: 14px; color: var(--info-text); }
    .pg-viewall { display: block; text-align: center; padding: 13px; margin-top: 6px; font-size: 13px; font-weight: 800; color: var(--pg-blue); text-decoration: none; }
    .pg-empty { padding: 26px 10px; text-align: center; color: var(--text-muted); font-size: 13px; }

    /* Breakdown */
    .pg-sec-h { display: flex; align-items: center; gap: 9px; margin-bottom: 4px; }
    .pg-sec-h svg { width: 20px; height: 20px; color: var(--info-text); }
    .pg-sec-h b { font-size: 18px; font-weight: 800; color: var(--text-primary); }
    .pg-sec-sub { font-size: 12.5px; color: var(--text-muted); margin: 0 0 14px; }

    /* Click-a-gig cards */
        color: var(--text-muted); margin: 5px 0 1px; }

    .pg-banner { display: flex; align-items: center; gap: 14px; background: rgba(37,99,235,0.06); border: 1px solid rgba(37,99,235,0.18); border-radius: 14px; padding: 16px 20px; margin-top: 18px; }
    .pg-banner svg.star { width: 26px; height: 26px; color: var(--info-text); flex-shrink: 0; }
    .pg-banner-txt { flex: 1; }
    .pg-banner-txt b { font-size: 13.5px; color: var(--text-primary); }
    .pg-banner-txt p { font-size: 12px; color: var(--text-muted); margin: 1px 0 0; }
    .pg-banner a { display: inline-flex; align-items: center; gap: 8px; background: #2563eb; color: #fff; font-size: 13px; font-weight: 800; padding: 11px 18px; border-radius: 10px; text-decoration: none; white-space: nowrap; }
    .pg-banner a svg { width: 15px; height: 15px; }

    @media (max-width: 1200px) { .pg-top { grid-template-columns: 1fr; } }
    .pg-work { display: grid; grid-template-columns: minmax(0,1fr) 300px; gap: 18px; align-items: start; }
    .pg-rail { display: flex; flex-direction: column; gap: 16px; }
    .pg-rail-h { font-size: 12.5px; font-weight: 800; color: var(--text-primary); margin: 0 0 10px; }
    .pg-rail-row { padding: 8px 0; border-top: 1px solid var(--border-color); }
    .pg-rail-row:first-of-type { border-top: 0; }
    .pg-rail-row b { display: block; font-size: 12.5px; font-weight: 700; color: var(--text-primary); }
    .pg-rail-row span { display: block; font-size: 11.5px; color: var(--text-secondary, var(--text-muted)); }
    .pg-rail-row i { font-style: normal; font-size: 10.5px; color: var(--text-muted); }
    .pg-rail-none { font-size: 12.5px; color: var(--text-muted); }
    @media (max-width: 1100px) { .pg-work { grid-template-columns: 1fr; } }
    /* Below the gigs it is one column of explanation, not a side panel. */
    .pg-top-after { grid-template-columns: 1fr; margin-top: 20px; }
    @media (max-width: 760px) { .pg-scores { grid-template-columns: 1fr; } }
</style>
@endpush
<div class="pg">

    {{-- ════════ Top: intro + gig snapshot ════════ --}}
    <div class="pg-work">
        <div class="pg-card">
            <div class="pg-hub-h">
                <span class="pg-hub-ico"><svg viewBox="0 0 32 28" fill="none" xmlns="http://www.w3.org/2000/svg"><rect x="3" y="9" width="26" height="17" rx="3.5" fill="#2563eb"/><rect x="3" y="9" width="26" height="17" rx="3.5" fill="url(#hbg)" opacity="0.22"/><rect x="11" y="3.5" width="10" height="6.5" rx="2" fill="#1d4ed8"/><rect x="13" y="5.5" width="6" height="3.2" rx="1.4" fill="#3b82f6"/><rect x="3" y="15" width="26" height="2" fill="rgba(255,255,255,0.4)"/><rect x="13.5" y="16" width="5" height="3.6" rx="1.5" fill="#fff"/><defs><linearGradient id="hbg" x1="3" y1="9" x2="29" y2="26"><stop stop-color="#fff"/><stop offset="1" stop-color="#1e40af"/></linearGradient></defs></svg></span>
                <div><b>Gig Operations Hub</b><p>Manage all your gigs in one place.</p></div>
            </div>
            <div class="pg-scores">
                <div class="pg-score"><div><div class="pg-score-k">Active Gigs</div><div class="pg-score-v">{{ $stats['active'] }}</div></div><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="3" y1="10" x2="21" y2="10"/></svg></div>
                <div class="pg-score"><div><div class="pg-score-k">Work in Progress</div><div class="pg-score-v">{{ $stats['in_progress'] }}</div></div><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 2v6h-6M3 12a9 9 0 0 1 15-6.7L21 8M3 22v-6h6M21 12a9 9 0 0 1-15 6.7L3 16"/></svg></div>
                <div class="pg-score"><div><div class="pg-score-k">Completed</div><div class="pg-score-v">{{ $stats['completed'] }}</div></div><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="9 12 11 14 15 10"/></svg></div>
            </div>
            @forelse($gigs as $i => $g)
                @php
                    $ev = $g->event;
                    [$stLabel, $stColor] = \App\Support\GigStatus::for($g, $gigDeposits);
                    $cr = $crew->get($g->event_id);
                    $crewTxt = $cr ? ((int)$cr->filled).'/'.((int)$cr->total) : '—';
                    $mc = optional($msg->get($g->id))->messages_count ?? 0;
                @endphp
                <div class="pg-gig">
                    <span class="pg-gig-thumb" style="background:{{ $thumbs[$i % count($thumbs)] }};"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="3" y1="10" x2="21" y2="10"/></svg></span>
                    <div class="pg-gig-mid">
                        <div class="pg-gig-name">{{ \Illuminate\Support\Str::limit($ev?->title ?? 'Gig', 26) }}</div>
                        <div class="pg-gig-meta">{{ $ev?->starts_at?->format('M d, Y') ?? '—' }} · {{ $ev?->location ? \Illuminate\Support\Str::limit($ev->location,16) : 'TBD' }}</div>
                        <div class="pg-gig-svc">{{ $ev?->categories->first()?->name ?? 'Service' }}</div>
                    </div>
                    <div class="pg-gig-price" style="text-align:right;min-width:96px;">
                        <b>{{ $g->price ? $money($g->price) : ($ev?->budget ? $money($ev->budget) : '—') }}</b>
                        <div class="pg-gig-status" style="color:{{ $stColor }};">{{ $stLabel }}</div>
                    </div>
                    <div class="pg-gig-stats">
                        <span class="pg-gig-stat"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/></svg>{{ $crewTxt }}</span>
                        <span class="pg-gig-stat"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>{{ $mc }}</span>
                    </div>
                </div>
            @empty
                <div class="pg-empty">No gigs yet. Win a gig from Live Opportunities to see it here.</div>
            @endforelse
            <a href="{{ route('professional.gig-hub.index', ['tab' => 'gigs']) }}" class="pg-viewall">View All Gigs →</a>
        </div>

        <aside class="pg-rail">
            {{-- Khadijah's 28 October screen carries these two beside the
                 gigs. Both read records that already exist: there is no
                 activity log, and adding one would mean a second account of
                 what the bookings and agreements already know. --}}
            <div class="pg-card">
                <h4 class="pg-rail-h">Upcoming deadlines</h4>
                @forelse ($deadlines as $d)
                    <div class="pg-rail-row">
                        <b>{{ $d['title'] }}</b>
                        <span>{{ $d['subject'] ?: $d['kind'] }}</span>
                        <i>{{ $d['when'] ?? $d['due']->timezone(config('app.display_timezone'))->format('j M Y') }}</i>
                    </div>
                @empty
                    <div class="pg-rail-none">Nothing with a date on it.</div>
                @endforelse
            </div>

            <div class="pg-card">
                <h4 class="pg-rail-h">Recent activity</h4>
                @forelse ($activity as $a)
                    <div class="pg-rail-row">
                        <b>{{ $a['what'] }}</b>
                        @if ($a['subject']) <span>{{ $a['subject'] }}</span> @endif
                        <i>{{ $a['at']->humanAgo() }}</i>
                    </div>
                @empty
                    <div class="pg-rail-none">Nothing has happened yet.</div>
                @endforelse
            </div>
        </aside>
    </div>


    {{-- The explanation sat beside the gigs at half width, so somebody
         opening this page read about the page before they could read their
         own jobs. Khadijah's screen puts the work first, so it moved under
         them. --}}
    <div class="pg-top pg-top-after">
        <div class="pg-intro">
            <div class="pg-art">
                <svg viewBox="0 0 120 110" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <ellipse cx="62" cy="60" rx="54" ry="44" fill="#eef2ff"/>
                    <rect x="26" y="44" width="72" height="50" rx="9" fill="#2563eb"/>
                    <rect x="26" y="44" width="72" height="50" rx="9" fill="url(#bg)" opacity="0.25"/>
                    <rect x="48" y="34" width="28" height="16" rx="5" fill="#1d4ed8"/>
                    <rect x="52" y="38" width="20" height="9" rx="3" fill="#3b82f6"/>
                    <rect x="26" y="62" width="72" height="5" fill="rgba(255,255,255,0.35)"/>
                    <rect x="56" y="66" width="12" height="9" rx="2.5" fill="#fff"/>
                    <path d="M150 30" /><path d="M104 40l1.4 4 4 1.4-4 1.4-1.4 4-1.4-4-4-1.4 4-1.4z" fill="#f59e0b"/>
                    <path d="M20 36l1.2 3.4 3.4 1.2-3.4 1.2-1.2 3.4-1.2-3.4-3.4-1.2 3.4-1.2z" fill="#60a5fa"/>
                    <defs><linearGradient id="bg" x1="26" y1="44" x2="98" y2="94"><stop stop-color="#fff"/><stop offset="1" stop-color="#1e40af"/></linearGradient></defs>
                </svg>
            </div>
            <h1>Gig Operations Hub</h1>
            <div class="tag">Manage all your gigs in one place.</div>
            <p>Your centralized control center to track jobs, monitor earnings, manage staffing, and organize communications, all in one powerful dashboard.</p>
            <div class="pg-feat x1"><span class="pg-feat-ico" style="background:rgba(139,92,246,0.14);color:var(--accent-text);"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19.5 12.5l-7 7a2 2 0 0 1-2.8 0l-7-7a2 2 0 0 1 0-2.8l7-7a2 2 0 0 1 2.8 0l7 7a2 2 0 0 1 0 2.8z"/></svg></span><div><b>Stops Confusion</b><p>Gathers scattered details into one view.</p></div></div>
            <div class="pg-feat x2"><span class="pg-feat-ico" style="background:rgba(16,185,129,0.14);color:var(--ok-text);"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M12 6v12M9 9.5a2.5 2.5 0 0 1 5 0M9 14.5a2.5 2.5 0 0 0 5 0"/></svg></span><div><b>Tracks Money</b><p>Shows exactly how much each job pays.</p></div></div>
            <div class="pg-feat x3"><span class="pg-feat-ico" style="background:rgba(249,115,22,0.14);color:var(--brand-text);"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/></svg></span><div><b>Manages Teams</b><p>Helps verify that enough workers are assigned to every event.</p></div></div>
            <div class="pg-feat x4"><span class="pg-feat-ico" style="background:rgba(37,99,235,0.14);color:var(--info-text);"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg></span><div><b>One place</b><p>Your gigs, their money and their messages on one screen.</p></div></div>
        </div>
    </div>

    {{-- Two sections stood here and both are gone.

         The first was a "Detailed Section Breakdown": three columns
         explaining this page, on this page, the last of them a drawing of an
         interface shown inside the interface. The same block was on My
         Calendar, the Lead Pipeline and Bid Intelligence, and went from all
         four for the same reason.

         The second was "What happens if you click a gig", and it was the
         largest invented thing on the professional side. A map with a route
         drawn on it and "1 hr 23 min (84.6 mi)" — no maps provider exists, so
         that was the same distance for every gig on the platform. A team chat
         with three messages nobody sent. An inventory of chicken, salmon,
         chafing dishes and serving tables, for a caterer who may be a DJ. And
         an event of 120 guests on a $4,000 contract with 3 of 5 crew
         assigned, none of it belonging to the person reading it.

         A professional opening their own Gig Operations Hub saw somebody
         else's job, in detail, and had no way to know it was not theirs. --}}

    {{-- ════════ Bottom banner ════════ --}}
    <div class="pg-banner">
        <svg class="star" viewBox="0 0 24 24" fill="currentColor"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>
        {{-- "profitable" and "stress-free" are promises about somebody's
             business, which Sir Peter's rules on claims forbid. --}}
        <div class="pg-banner-txt"><b>Your gigs, in one place.</b><p>What is booked, what is owed, and what still needs doing.</p></div>
        <a href="{{ route('professional.dashboard') }}"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/></svg>Go to Dashboard</a>
    </div>
</div>
