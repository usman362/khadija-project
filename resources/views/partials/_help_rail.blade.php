{{--
    The side of a request page: how it works, what it costs, what to say.

    Sir Peter, 2026-09-10, on the recreated mockups: "we lack data for the users
    in these current we have." The screens were not missing records — they were
    missing the explanation beside the form, which every one of his mockups has.

    And 2026-09-09, the rule underneath it: "if all of these workflows
    ultimately lead to an agreement, I would make the core agreement-driving
    information consistent across all of them." Four flows had four hand-written
    rails, so the $2.99 was explained four different ways. The words now come
    from config/request-help.php, once, for all four.

    $flow  'br' | 'er' | 'dr' | 'toolkit'
--}}
@php
    $panels = config('request-help.' . $flow, []);
@endphp

@once
@push('styles')
<style>
    .hr-rail { display: flex; flex-direction: column; gap: 14px; position: sticky; top: 88px; }
    .hr-card { background: var(--bg-card, #fff); border: 1px solid var(--border-color, #e5e7eb); border-radius: 16px; padding: 18px; }
    .hr-card h4 { font-size: 13px; font-weight: 800; color: var(--text-primary, #111827);
        margin: 0 0 13px; display: flex; align-items: center; gap: 8px; }
    .hr-card h4 svg { width: 16px; height: 16px; flex-shrink: 0; color: var(--brand, #f97316); }

    .hr-step { display: flex; gap: 10px; align-items: flex-start; margin-bottom: 12px; }
    .hr-step:last-child { margin-bottom: 0; }
    .hr-n { flex-shrink: 0; width: 20px; height: 20px; border-radius: 50%;
        background: var(--brand, #f97316); color: #fff; font-size: 11px; font-weight: 800;
        display: flex; align-items: center; justify-content: center; }

    .hr-item { display: flex; gap: 9px; align-items: flex-start; margin-bottom: 11px; }
    .hr-item:last-child { margin-bottom: 0; }
    .hr-item > svg { width: 13px; height: 13px; flex-shrink: 0; margin-top: 3px; color: var(--brand, #f97316); }

    .hr-card b { display: block; font-size: 12.5px; font-weight: 700; color: var(--text-primary, #111827); }
    .hr-card span.t { display: block; font-size: 12px; color: var(--text-muted, #6b7280); line-height: 1.5; margin-top: 2px; }

    /* Below the breakpoint the rail stops being a rail and becomes a row of
       cards under the form -- the explanation is still worth reading on a
       phone, it just cannot sit beside anything. */
    @media (max-width: 1024px) {
        .hr-rail { position: static; flex-direction: row; flex-wrap: wrap; }
        .hr-card { flex: 1; min-width: 240px; }
    }
    @media (max-width: 640px) { .hr-rail { flex-direction: column; } }
</style>
@endpush
@endonce

@php
    // One place for the icons, so a panel names a shape rather than carrying
    // a path around in the config file.
    $shapes = [
        'clock'  => '<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/>',
        'check'  => '<path d="M9 11l3 3L22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/>',
        'dollar' => '<path d="M12 2v20"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/>',
        'bolt'   => '<path d="M13 2 3 14h9l-1 8 10-12h-9l1-8z"/>',
        'info'   => '<circle cx="12" cy="12" r="10"/><path d="M12 16v-4"/><path d="M12 8h.01"/>',
        'send'   => '<line x1="22" y1="2" x2="11" y2="13"/><polygon points="22 2 15 22 11 13 2 9 22 2"/>',
    ];
@endphp

{{-- The page owns the <aside class="hr-rail"> so it can put its own card
     first -- the wizard's progress bar, for instance. --}}
@foreach($panels as $panel)
        <div class="hr-card">
            <h4>
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                    {!! $shapes[$panel['icon']] ?? $shapes['info'] !!}
                </svg>{{ $panel['title'] }}
            </h4>

            @if(! empty($panel['steps']))
                @foreach($panel['steps'] as $i => $step)
                    <div class="hr-step">
                        <span class="hr-n">{{ $i + 1 }}</span>
                        <span><b>{{ $step['b'] }}</b><span class="t">{{ $step['t'] }}</span></span>
                    </div>
                @endforeach
            @else
                @foreach($panel['items'] ?? [] as $item)
                    <div class="hr-item">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"/></svg>
                        <span><b>{{ $item['b'] }}</b><span class="t">{{ $item['t'] }}</span></span>
                    </div>
                @endforeach
            @endif
    </div>
@endforeach
