{{-- Standard breadcrumb (Peter): user type › Dashboard › current page.
     One label per level; the deeper the page, the longer the trail. Coloured by
     portal — Professional blue, Client orange, Influencer green. --}}
@php
    $bcUser = auth()->user();
    $bcRole = $bcUser?->activeRole();

    if ($bcRole === 'professional') {
        $bcType = 'Professional'; $bcDash = 'professional.dashboard'; $bcAccent = '#2563eb'; // 5.17 on white
    } elseif ($bcRole === 'client') {
        $bcType = 'Client'; $bcDash = 'client.dashboard'; $bcAccent = '#c2410c';   // 3.56 -> 5.18 on white
    } elseif ($bcUser?->hasRole('influencer')) {
        $bcType = 'Influencer'; $bcDash = 'influencer.dashboard'; $bcAccent = '#15803d'; // 3.28 -> 4.72 on white
    } else {
        $bcType = 'Account'; $bcDash = null; $bcAccent = '#64748b';
    }

    /*
     * The current page's own title (each view sets @section('page-title')).
     *
     * yieldContent returns RENDERED html, so a title containing "&" arrives
     * here already escaped as "&amp;". Printing that through {{ }} escapes it a
     * second time, which is why the Virtual & Hybrid Hub breadcrumb read
     * "Virtual &amp; Hybrid Hub" on screen while the page's own heading — the
     * same string, printed once — was fine.
     *
     * Decoded back to plain text here so the single escape at output is the
     * only one.
     */
    $bcPage  = trim(html_entity_decode(strip_tags($__env->yieldContent('page-title')), ENT_QUOTES | ENT_HTML5));
    $bcOnDash = $bcDash && \Illuminate\Support\Facades\Route::has($bcDash) && request()->routeIs($bcDash);
@endphp
<nav aria-label="Breadcrumb" style="display:flex; align-items:center; gap:8px; flex-wrap:wrap; font-size:12.5px; font-weight:600; color:var(--text-muted, #475569); margin:0 0 16px;">
    <span style="color:{{ $bcAccent }}; font-weight:800;">{{ $bcType }}</span>
    @if($bcDash && \Illuminate\Support\Facades\Route::has($bcDash))
        <span style="opacity:.45;">›</span>
        @if($bcOnDash)
            <span style="color:var(--text-primary, #1e293b);">Dashboard</span>
        @else
            <a href="{{ route($bcDash) }}" style="color:var(--text-muted, #475569); text-decoration:none;">Dashboard</a>
        @endif
    @endif
    @if($bcPage && ! $bcOnDash && strtolower($bcPage) !== 'dashboard')
        <span style="opacity:.45;">›</span>
        <span style="color:var(--text-primary, #1e293b);">{{ $bcPage }}</span>
    @endif
</nav>
