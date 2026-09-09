@extends('layouts.dashboard')

@section('title', 'Compliance')

@section('content')
<div class="cmp">
    <div class="cmp-head">
        <h1>Compliance</h1>
        <p>
            One row per requirement, not per law — a single act can ask for three
            different things, and three things is what somebody has to build.
        </p>
    </div>

    {{-- Several of these rows are satisfied by a job that runs on a timer, and
         a timer that is not running fails silently: the code is right and the
         emails simply never go. So the page says whether the scheduler is
         alive rather than assuming it. --}}
    @php $__beat = \App\Console\Commands\SchedulerHeartbeat::lastRun(); @endphp
    <div class="cmp-beat {{ $__beat && $__beat->gt(now()->subMinutes(10)) ? 'is-ok' : 'is-bad' }}">
        @if(! $__beat)
            <b>The scheduler has never run.</b>
            <span>Nothing time-based is happening — no renewal notices, no account purges.
                  The host needs a cron entry calling <code>schedule:run</code> every minute.</span>
        @elseif($__beat->gt(now()->subMinutes(10)))
            <b>Scheduler running.</b>
            <span>Last heartbeat {{ $__beat->humanAgo() }}.</span>
        @else
            <b>The scheduler has stopped.</b>
            <span>Last heartbeat {{ $__beat->humanAgo() }} ({{ $__beat->format('M j, Y H:i') }}).
                  Anything on a timer has not run since.</span>
        @endif
    </div>

    <div class="cmp-tally">
        @foreach($tally as $status => $count)
            <span class="cmp-pill is-{{ $status }}">{{ \Illuminate\Support\Str::headline($status) }} <b>{{ $count }}</b></span>
        @endforeach
    </div>

    {{-- The register checking itself. A list nobody checks becomes a list of
         things somebody believes are done. --}}
    @if($problems->isNotEmpty())
        <div class="cmp-problems">
            <b>{{ $problems->count() }} {{ \Illuminate\Support\Str::plural('gap', $problems->count()) }} in the register itself</b>
            <ul>
                @foreach($problems as $p)
                    <li><code>{{ $p['key'] }}</code> — {{ $p['problem'] }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    @foreach($byJurisdiction as $jurisdiction => $rows)
        <h2 class="cmp-place">{{ $jurisdiction }}</h2>

        @foreach($rows as $r)
            <div class="cmp-row is-{{ $r['status'] }}">
                <div class="cmp-row-head">
                    <b>{{ $r['law'] }}</b>
                    <span class="cmp-pill is-{{ $r['status'] }}">{{ \Illuminate\Support\Str::headline($r['status']) }}</span>
                </div>

                <p class="cmp-requires">{{ $r['requires'] }}</p>

                <dl class="cmp-facts">
                    <div><dt>Citation</dt><dd>{{ $r['citation'] ?? '— not on file —' }}</dd></div>
                    <div><dt>In force from</dt><dd>{{ $r['effective'] ?? '—' }}</dd></div>
                    <div><dt>Done</dt><dd>{{ $r['done_on'] ?? '—' }}</dd></div>
                </dl>

                @if($r['implemented'])
                    <p class="cmp-impl"><b>What satisfies it:</b> {{ $r['implemented'] }}</p>
                @endif

                @if($r['note'])
                    <p class="cmp-note">{{ $r['note'] }}</p>
                @endif
            </div>
        @endforeach
    @endforeach
</div>

<style>
    .cmp { max-width: 900px; }
    .cmp-head h1 { font-size: 22px; font-weight: 800; margin: 0 0 4px; }
    .cmp-head p { font-size: 13px; color: var(--text-muted); margin: 0 0 16px; max-width: 620px; line-height: 1.6; }
    .cmp-beat { border-radius: 12px; padding: 12px 15px; margin-bottom: 16px; font-size: 13px; line-height: 1.6; }
    .cmp-beat b { display: block; }
    .cmp-beat span { color: var(--text-secondary); }
    .cmp-beat code { font-size: 12px; }
    .cmp-beat.is-ok { background: rgba(16,185,129,.10); border: 1px solid rgba(16,185,129,.3); }
    .cmp-beat.is-bad { background: rgba(239,68,68,.09); border: 1px solid rgba(239,68,68,.32); }
    .cmp-tally { display: flex; gap: 7px; flex-wrap: wrap; margin-bottom: 18px; }
    .cmp-pill { font-size: 11.5px; font-weight: 800; border-radius: 999px; padding: 5px 11px;
        background: var(--bg-card-hover); color: var(--text-muted); }
    .cmp-pill b { margin-left: 4px; color: var(--text-primary); }
    .cmp-pill.is-done { background: rgba(16,185,129,.12); color: var(--ok-text); }
    .cmp-pill.is-todo { background: rgba(245,158,11,.14); color: #b45309; }
    .cmp-pill.is-blocked { background: rgba(239,68,68,.12); color: var(--bad-text); }
    .cmp-problems { border: 1px solid rgba(245,158,11,.4); background: rgba(245,158,11,.07);
        border-radius: 12px; padding: 14px 16px; margin-bottom: 18px; font-size: 13px; }
    .cmp-problems ul { margin: 8px 0 0; padding-left: 18px; line-height: 1.7; color: var(--text-secondary); }
    .cmp-problems code { font-size: 12px; }
    .cmp-place { font-size: 15px; font-weight: 800; margin: 20px 0 10px; }
    .cmp-row { background: var(--bg-card); border: 1px solid var(--border-color); border-radius: 13px;
        padding: 15px 17px; margin-bottom: 10px; }
    .cmp-row.is-done { border-left: 3px solid var(--ok-text); }
    .cmp-row.is-todo { border-left: 3px solid #f59e0b; }
    .cmp-row-head { display: flex; justify-content: space-between; align-items: baseline; gap: 12px; }
    .cmp-row-head b { font-size: 14px; color: var(--text-primary); }
    .cmp-requires { font-size: 13.5px; color: var(--text-secondary); line-height: 1.6; margin: 8px 0 12px; }
    .cmp-facts { display: grid; grid-template-columns: repeat(auto-fit, minmax(150px,1fr)); gap: 10px 18px; margin: 0 0 10px; }
    .cmp-facts dt { font-size: 11px; color: var(--text-muted); font-weight: 700; }
    .cmp-facts dd { margin: 2px 0 0; font-size: 13px; color: var(--text-primary); font-weight: 600; }
    .cmp-impl { font-size: 13px; color: var(--text-secondary); line-height: 1.6; margin: 0; }
    .cmp-impl b { color: var(--text-primary); }
    .cmp-note { font-size: 12.5px; color: var(--text-muted); line-height: 1.6; margin: 8px 0 0;
        padding-top: 8px; border-top: 1px solid var(--border-color); }
</style>
@endsection
