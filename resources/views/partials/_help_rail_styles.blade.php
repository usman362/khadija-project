{{-- The help rail's card styles, shared with rails that draw their own cards. --}}
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
