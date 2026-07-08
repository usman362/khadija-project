{{-- Standard breadcrumb (Peter): user type › Dashboard › current page.
     One label per level; the deeper the page, the longer the trail. Coloured by
     portal — Professional blue, Client orange, Influencer green. --}}
@php
    $bcUser = auth()->user();
    $bcRole = $bcUser?->activeRole();

    if ($bcRole === 'supplier') {
        $bcType = 'Professional'; $bcDash = 'professional.dashboard'; $bcAccent = '#2563eb';
    } elseif ($bcRole === 'client') {
        $bcType = 'Client'; $bcDash = 'client.dashboard'; $bcAccent = '#ea580c';
    } elseif ($bcUser?->hasRole('influencer')) {
        $bcType = 'Influencer'; $bcDash = 'influencer.dashboard'; $bcAccent = '#16a34a';
    } else {
        $bcType = 'Account'; $bcDash = null; $bcAccent = '#64748b';
    }

    // The current page's own title (each view sets @section('page-title')).
    $bcPage  = trim(strip_tags($__env->yieldContent('page-title')));
    $bcOnDash = $bcDash && \Illuminate\Support\Facades\Route::has($bcDash) && request()->routeIs($bcDash);
@endphp
<nav aria-label="Breadcrumb" style="display:flex; align-items:center; gap:8px; flex-wrap:wrap; font-size:12.5px; font-weight:600; color:var(--text-muted, #94a3b8); margin:0 0 16px;">
    <span style="color:{{ $bcAccent }}; font-weight:800;">{{ $bcType }}</span>
    @if($bcDash && \Illuminate\Support\Facades\Route::has($bcDash))
        <span style="opacity:.45;">›</span>
        @if($bcOnDash)
            <span style="color:var(--text-primary, #1e293b);">Dashboard</span>
        @else
            <a href="{{ route($bcDash) }}" style="color:var(--text-muted, #94a3b8); text-decoration:none;">Dashboard</a>
        @endif
    @endif
    @if($bcPage && ! $bcOnDash && strtolower($bcPage) !== 'dashboard')
        <span style="opacity:.45;">›</span>
        <span style="color:var(--text-primary, #1e293b);">{{ $bcPage }}</span>
    @endif
</nav>
