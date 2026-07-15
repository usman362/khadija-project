<nav class="navbar" aria-label="Main navigation">
    {{--
        Two-row nav (Alibaba-style):
          Row 1 — logo on the left, auth/CTA buttons on the right.
          Row 2 — nav links including the "All Categories" mega trigger.
        Each row gets its own .container so width alignment matches the rest
        of the page. Mobile collapses to just the top row (links row hides).
    --}}
    <div class="navbar-row navbar-row-top">
        <div class="container">
            <a href="{{ route('landing') }}" class="navbar-brand">
                <img src="{{ asset('gigresource-logos/gigresource-logo-dark.png') }}" alt="{{ config('app.name') }}" class="navbar-logo">
            </a>

            <div class="navbar-actions">
                @auth
                    @php
                        $unreadCount = auth()->user()->unreadNotifications->count();
                    @endphp

                    {{-- Notifications bell. Count bubble only shows when > 0. --}}
                    <a href="{{ url('/dashboard') }}" class="nav-icon-btn" aria-label="Notifications" title="Notifications">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/></svg>
                        @if($unreadCount > 0)
                            <span class="nav-icon-badge">{{ $unreadCount > 9 ? '9+' : $unreadCount }}</span>
                        @endif
                    </a>

                    <a href="{{ url('/dashboard') }}" class="btn btn-primary btn-sm">Dashboard</a>

                    {{-- User avatar dropdown. Shows name + role, quick links, logout. --}}
                    <div class="user-dropdown" id="userDropdown">
                        <button type="button" class="user-dropdown-btn" onclick="document.getElementById('userDropdown').classList.toggle('open')" aria-label="Account menu">
                            <img src="{{ auth()->user()->avatar_url }}" alt="{{ auth()->user()->name }}" class="user-dropdown-avatar">
                            <svg class="user-dropdown-chev" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 12 15 18 9"></polyline></svg>
                        </button>
                        <div class="user-dropdown-menu">
                            <div class="user-dropdown-head">
                                <img src="{{ auth()->user()->avatar_url }}" alt="" class="user-dropdown-head-avatar">
                                <div class="user-dropdown-head-info">
                                    <div class="user-dropdown-head-name">{{ auth()->user()->name }}</div>
                                    <div class="user-dropdown-head-email">{{ auth()->user()->email }}</div>
                                </div>
                            </div>
                            <a href="{{ url('/dashboard') }}" class="user-dropdown-item">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg>
                                Dashboard
                            </a>
                            <a href="{{ url('/dashboard') }}" class="user-dropdown-item">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                                Profile
                            </a>
                            <div class="user-dropdown-divider"></div>
                            <form action="{{ route('logout') }}" method="POST" class="user-dropdown-logout-form">
                                @csrf
                                <button type="submit" class="user-dropdown-item user-dropdown-item-danger">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
                                    Log out
                                </button>
                            </form>
                        </div>
                    </div>
                @else
                    <div class="join-dropdown" id="joinDropdown">
                        <button type="button" class="join-dropdown-btn" onclick="document.getElementById('joinDropdown').classList.toggle('open')">
                            Join As Professional
                            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 12 15 18 9"></polyline></svg>
                        </button>
                        <div class="join-dropdown-menu">
                            <a href="{{ route('register', ['role' => 'professional']) }}" class="join-dropdown-item">Join As Professional</a>
                            <a href="{{ route('register', ['role' => 'client']) }}" class="join-dropdown-item">Join As Client</a>
                            <a href="{{ route('influencer.join') }}" class="join-dropdown-item">Join As Influencer</a>
                        </div>
                    </div>
                    @if (Route::has('login'))
                        <a href="{{ route('login') }}" class="btn btn-outline btn-sm">Log in</a>
                    @endif
                @endauth
            </div>

            <button class="mobile-menu-btn" id="mobileMenuBtn" aria-label="Open menu" aria-expanded="false">&#9776;</button>

            {{--
                Mobile navigation drawer.
                Slides in from the right when the hamburger button is tapped.
                Mirrors the full desktop nav so mobile users have the same
                access to All Categories, Browse Professionals, etc.
            --}}
            <div class="mobile-nav" id="mobileNav" aria-hidden="true">
                <div class="mobile-nav-head">
                    <a href="{{ route('landing') }}" class="mobile-nav-brand">{{ config('app.name', 'GigResource') }}</a>
                    <button type="button" class="mobile-nav-close" id="mobileNavClose" aria-label="Close menu">&times;</button>
                </div>

                <div class="mobile-nav-body">
                    {{-- Quick search --}}
                    <form action="{{ route('public.browse') }}" method="GET" class="mobile-nav-search">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                        <input type="text" name="q" placeholder="Find professionals…" data-voice-search>
                    </form>

                    {{-- Primary nav links --}}
                    <div class="mobile-nav-section">
                        <h4>Explore</h4>
                        <a href="{{ route('events-categories') }}" class="mobile-nav-link">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="18" x2="21" y2="18"/></svg>
                            All Categories
                        </a>
                        <a href="{{ route('public.browse') }}" class="mobile-nav-link">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                            Browse Professionals
                        </a>
                        <a href="{{ route('events-categories') }}" class="mobile-nav-link">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/></svg>
                            Events
                        </a>
                        <a href="{{ route('public.how-it-works') }}" class="mobile-nav-link">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                            How It Works
                        </a>
                        <a href="{{ route('about-us') }}" class="mobile-nav-link">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12"/><line x1="12" y1="8" x2="12.01" y2="8"/></svg>
                            About Us
                        </a>
                        <a href="{{ route('blog.index') }}" class="mobile-nav-link">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
                            Blog
                        </a>
                        <a href="{{ route('public.faq') }}" class="mobile-nav-link">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
                            FAQ
                        </a>
                        <a href="{{ route('landing') }}#pricing" class="mobile-nav-link">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
                            Pricing
                        </a>
                    </div>

                    {{-- Auth actions --}}
                    <div class="mobile-nav-section mobile-nav-auth">
                        @auth
                            <a href="{{ url('/dashboard') }}" class="mobile-nav-btn primary">Dashboard</a>
                            <form action="{{ route('logout') }}" method="POST" style="margin:0;">
                                @csrf
                                <button type="submit" class="mobile-nav-btn ghost">Log out</button>
                            </form>
                        @else
                            <a href="{{ route('login') }}" class="mobile-nav-btn ghost">Log in</a>
                            <a href="{{ route('register', ['role' => 'client']) }}" class="mobile-nav-btn primary">Start Planning</a>
                            <a href="{{ route('register', ['role' => 'professional']) }}" class="mobile-nav-btn coral">List Your Services</a>
                        @endauth
                    </div>
                </div>
            </div>
            <div class="mobile-nav-backdrop" id="mobileNavBackdrop"></div>
        </div>
    </div>

    <div class="navbar-row navbar-row-links">
        <div class="container">
            <ul class="navbar-links">
            {{--
                All Categories mega-menu (Alibaba-style).
                Hover on desktop opens the big panel; click toggles on touch.
                Hovering a rail item in the panel swaps the right-side
                "Categories for you" bubble grid. JS at the bottom of the
                navbar wires it up.
            --}}
            <li class="nav-mega" id="navMega">
                <button type="button" class="nav-mega-trigger" aria-haspopup="true" aria-expanded="false">
                    <svg class="nmt-burger" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round">
                        <line x1="3" y1="6"  x2="21" y2="6"/>
                        <line x1="3" y1="12" x2="21" y2="12"/>
                        <line x1="3" y1="18" x2="21" y2="18"/>
                    </svg>
                    <span>All Categories</span>
                    <svg class="nmt-chev" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 12 15 18 9"/></svg>
                </button>

                <div class="nav-mega-panel" role="menu">
                    {{-- LEFT: category rail (hover-driven swap) — real top-level categories. --}}
                    <div class="nmp-rail" id="nmpRail">
                        @foreach(($megaCategories ?? collect()) as $i => $mc)
                            <a class="nmp-rail-item {{ $i === 0 ? 'active' : '' }}" data-target="{{ $mc->slug }}" href="{{ route('public.category', $mc->slug) }}">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 2 3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"/><line x1="3" y1="6" x2="21" y2="6"/><path d="M16 10a4 4 0 0 1-8 0"/></svg>
                                <span>{{ $mc->name }}</span>
                                <svg class="rail-caret" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="9 18 15 12 9 6"/></svg>
                            </a>
                        @endforeach
                    </div>

                    {{-- RIGHT: showcase. One .nmp-panel per rail item. --}}
                    <div class="nmp-showcase" id="nmpShowcase">
                        @foreach(($megaCategories ?? collect()) as $i => $mc)
                            <div class="nmp-panel {{ $i === 0 ? 'active' : '' }}" data-panel="{{ $mc->slug }}">
                                <h4 class="nmp-title">Popular in <span>{{ $mc->name }}</span></h4>
                                <div class="nmp-grid">
                                    @foreach($mc->children->take(8) as $child)
                                        @php($cimg = $child->thumbnail ?: $child->cover_image)
                                        <a class="nmp-tile" href="{{ route('public.category', $child->slug) }}">
                                            <span class="nmp-bubble">@if($cimg)<img src="{{ asset('storage/'.$cimg) }}" alt="{{ $child->name }}">@endif</span>
                                            <span class="nmp-label">{{ $child->name }}</span>
                                        </a>
                                    @endforeach
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </li>

            <li><a href="{{ route('public.browse') }}" class="{{ request()->routeIs('public.browse') ? 'is-active' : '' }}">Browse Professionals</a></li>
            <li><a href="{{ route('about-us') }}" class="{{ request()->routeIs('about-us') ? 'is-active' : '' }}">About Us</a></li>
            <li><a href="{{ route('events-categories') }}" class="{{ request()->routeIs('events-categories') ? 'is-active' : '' }}">Events</a></li>
            <li><a href="{{ route('blog.index') }}" class="{{ request()->routeIs('blog.*') ? 'is-active' : '' }}">Blog</a></li>
            <li><a href="{{ route('public.how-it-works') }}" class="{{ request()->routeIs('public.how-it-works') ? 'is-active' : '' }}">How It Works</a></li>
            <li><a href="{{ route('landing') }}#pricing">Pricing</a></li>
            <li><a href="{{ route('public.faq') }}" class="{{ request()->routeIs('public.faq') ? 'is-active' : '' }}">FAQ</a></li>
        </ul>
        </div>
    </div>
</nav>

{{--
    Mega-menu wiring.
    - Hover on the trigger opens the panel (desktop).
    - Click toggles (touch / accessibility).
    - Hovering a rail item swaps the showcase panel.
    - Outside-click closes the whole menu.

    Kept inline in the partial so every page that includes the navbar
    gets it without touching each layout's @push('scripts').
--}}
<script>
/* ── Mobile navigation drawer ─────────────────────────────────────
   Hamburger opens a right-side slide-in drawer with the full nav.
   Body scroll locks while open. Click backdrop / Escape to close.
*/
(function () {
    var btn      = document.getElementById('mobileMenuBtn');
    var nav      = document.getElementById('mobileNav');
    var closeBtn = document.getElementById('mobileNavClose');
    var backdrop = document.getElementById('mobileNavBackdrop');
    if (!btn || !nav) return;

    function open() {
        nav.classList.add('is-open');
        nav.setAttribute('aria-hidden', 'false');
        btn.setAttribute('aria-expanded', 'true');
        if (backdrop) backdrop.classList.add('is-visible');
        document.body.style.overflow = 'hidden';
    }
    function close() {
        nav.classList.remove('is-open');
        nav.setAttribute('aria-hidden', 'true');
        btn.setAttribute('aria-expanded', 'false');
        if (backdrop) backdrop.classList.remove('is-visible');
        document.body.style.overflow = '';
    }

    btn.addEventListener('click', open);
    if (closeBtn) closeBtn.addEventListener('click', close);
    if (backdrop) backdrop.addEventListener('click', close);
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape' && nav.classList.contains('is-open')) close();
    });
    // Auto-close when crossing the breakpoint to desktop
    var mq = window.matchMedia('(min-width: 769px)');
    if (mq.addEventListener) mq.addEventListener('change', function (e) { if (e.matches) close(); });
})();

/* Scrolled-state toggle: stronger shadow + deeper bg once the user
   leaves the hero. Uses rAF throttling so we never churn the layout. */
(function () {
    var bar = document.querySelector('.navbar');
    if (!bar) return;
    var ticking = false;
    function update() {
        bar.classList.toggle('is-scrolled', window.scrollY > 12);
        ticking = false;
    }
    window.addEventListener('scroll', function () {
        if (!ticking) {
            window.requestAnimationFrame(update);
            ticking = true;
        }
    }, { passive: true });
    update();
})();

/* Close the user + join dropdowns when the user clicks anywhere outside
   them. Each button has an inline onclick that toggles .open, so we just
   need to cover the dismiss case here. */
(function () {
    document.addEventListener('click', function (e) {
        ['userDropdown', 'joinDropdown'].forEach(function (id) {
            var el = document.getElementById(id);
            if (el && el.classList.contains('open') && !el.contains(e.target)) {
                el.classList.remove('open');
            }
        });
    });
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') {
            ['userDropdown', 'joinDropdown'].forEach(function (id) {
                var el = document.getElementById(id);
                if (el) el.classList.remove('open');
            });
        }
    });
})();

(function () {
    var mega     = document.getElementById('navMega');
    if (!mega) return;

    var trigger  = mega.querySelector('.nav-mega-trigger');
    var rail     = document.getElementById('nmpRail');
    var show     = document.getElementById('nmpShowcase');
    if (!trigger || !rail || !show) return;

    // Debounced close: when the cursor briefly exits the trigger+panel
    // hit area (e.g. crossing a visual seam between the two), we DON'T
    // want to close instantly. A small grace window lets the user reach
    // the panel from the trigger and back without the menu vanishing.
    var closeTimer = null;
    function cancelClose() {
        if (closeTimer) { clearTimeout(closeTimer); closeTimer = null; }
    }
    function open() {
        cancelClose();
        mega.classList.add('open');
        trigger.setAttribute('aria-expanded', 'true');
    }
    function close() {
        mega.classList.remove('open');
        trigger.setAttribute('aria-expanded', 'false');
    }
    function scheduleClose() {
        cancelClose();
        closeTimer = setTimeout(close, 180);
    }

    // Desktop: hover opens. Leave starts a grace timer; re-entering the
    // trigger, panel, or any descendant cancels it. Click toggles
    // (works for touch too).
    mega.addEventListener('mouseenter', open);
    mega.addEventListener('mouseleave', scheduleClose);

    // The panel lives inside .nav-mega in the DOM, but in some browsers
    // with absolute positioning + visual seams the mouse can briefly
    // transit outside both rects. Listen on the panel directly too.
    var panel = mega.querySelector('.nav-mega-panel');
    if (panel) {
        panel.addEventListener('mouseenter', cancelClose);
        panel.addEventListener('mouseleave', scheduleClose);
    }

    trigger.addEventListener('click', function (e) {
        e.preventDefault();
        mega.classList.contains('open') ? close() : open();
    });

    // Click elsewhere closes.
    document.addEventListener('click', function (e) {
        if (!mega.contains(e.target)) close();
    });

    // Esc closes and returns focus to the trigger.
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape' && mega.classList.contains('open')) {
            close();
            trigger.focus();
        }
    });

    // Rail hover → swap the showcase panel.
    function activate(target) {
        rail.querySelectorAll('.nmp-rail-item').forEach(function (i) {
            i.classList.toggle('active', i.getAttribute('data-target') === target);
        });
        show.querySelectorAll('.nmp-panel').forEach(function (p) {
            p.classList.toggle('active', p.getAttribute('data-panel') === target);
        });
    }
    rail.querySelectorAll('.nmp-rail-item').forEach(function (item) {
        var t = item.getAttribute('data-target');
        item.addEventListener('mouseenter', function () { activate(t); });
        item.addEventListener('focus',      function () { activate(t); });
    });
})();
</script>
