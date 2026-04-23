<nav class="navbar">
    {{--
        Two-row nav (Alibaba-style):
          Row 1 — logo on the left, auth/CTA buttons on the right.
          Row 2 — nav links including the "All Categories" mega trigger.
        Each row gets its own .container so width alignment matches the rest
        of the page. Mobile collapses to just the top row (links row hides).
    --}}
    <div class="navbar-row navbar-row-top">
        <div class="container">
            <a href="{{ route('landing') }}" class="navbar-brand"><img src="{{ asset('logos/logo-light.png') }}" alt="{{ config('app.name') }}" style="height: 36px;"></a>

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
                            <a href="{{ route('register', ['role' => 'supplier']) }}" class="join-dropdown-item">Join As Professional</a>
                            <a href="{{ route('register', ['role' => 'client']) }}" class="join-dropdown-item">Join As Client</a>
                            <a href="{{ route('influencer.join') }}" class="join-dropdown-item">Join As Influencer</a>
                        </div>
                    </div>
                    @if (Route::has('login'))
                        <a href="{{ route('login') }}" class="btn btn-outline btn-sm">Log in</a>
                    @endif
                @endauth
            </div>

            <button class="mobile-menu-btn" onclick="this.nextElementSibling.classList.toggle('show')" aria-label="Menu">&#9776;</button>
            <div class="mobile-nav" style="display:none;"></div>
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
                    {{-- LEFT: category rail (hover-driven swap). --}}
                    <div class="nmp-rail" id="nmpRail">
                        <a class="nmp-rail-item active" data-target="weddings" href="{{ route('events-categories') }}">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 21s-7-4.35-7-10a5 5 0 0 1 9-3 5 5 0 0 1 9 3c0 5.65-7 10-7 10z"/></svg>
                            <span>Weddings &amp; Ceremonies</span>
                            <svg class="rail-caret" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="9 18 15 12 9 6"/></svg>
                        </a>
                        <a class="nmp-rail-item" data-target="corporate" href="{{ route('events-categories') }}">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="7" width="20" height="14" rx="2"/><path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"/></svg>
                            <span>Corporate &amp; Conferences</span>
                            <svg class="rail-caret" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="9 18 15 12 9 6"/></svg>
                        </a>
                        <a class="nmp-rail-item" data-target="birthday" href="{{ route('events-categories') }}">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21V10a2 2 0 0 0-2-2H6a2 2 0 0 0-2 2v11"/><path d="M4 15h16"/><path d="M12 4v4"/></svg>
                            <span>Birthday Parties</span>
                            <svg class="rail-caret" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="9 18 15 12 9 6"/></svg>
                        </a>
                        <a class="nmp-rail-item" data-target="baby-shower" href="{{ route('events-categories') }}">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M9 10h.01M15 10h.01M9.5 15a3 3 0 0 0 5 0"/></svg>
                            <span>Baby Showers</span>
                            <svg class="rail-caret" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="9 18 15 12 9 6"/></svg>
                        </a>
                        <a class="nmp-rail-item" data-target="music" href="{{ route('events-categories') }}">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 18V5l12-2v13"/><circle cx="6" cy="18" r="3"/><circle cx="18" cy="16" r="3"/></svg>
                            <span>Music &amp; Entertainment</span>
                            <svg class="rail-caret" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="9 18 15 12 9 6"/></svg>
                        </a>
                        <a class="nmp-rail-item" data-target="visual" href="{{ route('events-categories') }}">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M23 19V6a2 2 0 0 0-2-2h-4l-2-2h-6l-2 2H3a2 2 0 0 0-2 2v13a2 2 0 0 0 2 2h18a2 2 0 0 0 2-2z"/><circle cx="12" cy="13" r="4"/></svg>
                            <span>Photo &amp; Video</span>
                            <svg class="rail-caret" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="9 18 15 12 9 6"/></svg>
                        </a>
                        <a class="nmp-rail-item" data-target="food" href="{{ route('events-categories') }}">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 8h1a4 4 0 0 1 0 8h-1"/><path d="M2 8h16v9a4 4 0 0 1-4 4H6a4 4 0 0 1-4-4V8z"/></svg>
                            <span>Food &amp; Catering</span>
                            <svg class="rail-caret" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="9 18 15 12 9 6"/></svg>
                        </a>
                        <a class="nmp-rail-item" data-target="decor" href="{{ route('events-categories') }}">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87L18.18 22 12 18.27 5.82 22 7 14.14 2 9.27l6.91-1.01L12 2z"/></svg>
                            <span>Decor &amp; Floral</span>
                            <svg class="rail-caret" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="9 18 15 12 9 6"/></svg>
                        </a>
                        <a class="nmp-rail-item" data-target="staff" href="{{ route('events-categories') }}">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/></svg>
                            <span>Planners &amp; Staff</span>
                            <svg class="rail-caret" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="9 18 15 12 9 6"/></svg>
                        </a>
                    </div>

                    {{-- RIGHT: showcase. One .nmp-panel per rail item. --}}
                    <div class="nmp-showcase" id="nmpShowcase">

                        <div class="nmp-panel active" data-panel="weddings">
                            <h4 class="nmp-title">Popular in <span>Weddings &amp; Ceremonies</span></h4>
                            <div class="nmp-grid">
                                <a class="nmp-tile" href="{{ route('events-categories') }}"><span class="nmp-bubble"><img src="https://images.unsplash.com/photo-1452587925148-ce544e77e70d?w=200&q=80&auto=format&fit=crop" alt=""></span><span class="nmp-label">Photography</span></a>
                                <a class="nmp-tile" href="{{ route('events-categories') }}"><span class="nmp-bubble"><img src="https://images.unsplash.com/photo-1571266028243-e1d11d2c01a8?w=200&q=80&auto=format&fit=crop" alt=""></span><span class="nmp-label">Wedding DJs</span></a>
                                <a class="nmp-tile" href="{{ route('events-categories') }}"><span class="nmp-bubble"><img src="https://images.unsplash.com/photo-1511795409834-ef04bbd61622?w=200&q=80&auto=format&fit=crop" alt=""></span><span class="nmp-label">Floral Design</span></a>
                                <a class="nmp-tile" href="{{ route('events-categories') }}"><span class="nmp-bubble"><img src="https://images.unsplash.com/photo-1555244162-803834f70033?w=200&q=80&auto=format&fit=crop" alt=""></span><span class="nmp-label">Catering</span></a>
                                <a class="nmp-tile" href="{{ route('events-categories') }}"><span class="nmp-bubble"><img src="https://images.unsplash.com/photo-1519741497674-611481863552?w=200&q=80&auto=format&fit=crop" alt=""></span><span class="nmp-label">Venues</span></a>
                                <a class="nmp-tile" href="{{ route('events-categories') }}"><span class="nmp-bubble"><img src="https://images.unsplash.com/photo-1505236858219-8359eb29e329?w=200&q=80&auto=format&fit=crop" alt=""></span><span class="nmp-label">Planners</span></a>
                                <a class="nmp-tile" href="{{ route('events-categories') }}"><span class="nmp-bubble"><img src="https://images.unsplash.com/photo-1501386761578-eac5c94b800a?w=200&q=80&auto=format&fit=crop" alt=""></span><span class="nmp-label">Live Bands</span></a>
                            </div>
                        </div>

                        <div class="nmp-panel" data-panel="corporate">
                            <h4 class="nmp-title">Popular in <span>Corporate &amp; Conferences</span></h4>
                            <div class="nmp-grid">
                                <a class="nmp-tile" href="{{ route('events-categories') }}"><span class="nmp-bubble"><img src="https://images.unsplash.com/photo-1540575467063-178a50c2df87?w=200&q=80&auto=format&fit=crop" alt=""></span><span class="nmp-label">Conference AV</span></a>
                                <a class="nmp-tile" href="{{ route('events-categories') }}"><span class="nmp-bubble"><img src="https://images.unsplash.com/photo-1511578314322-379afb476865?w=200&q=80&auto=format&fit=crop" alt=""></span><span class="nmp-label">Videography</span></a>
                                <a class="nmp-tile" href="{{ route('events-categories') }}"><span class="nmp-bubble"><img src="https://images.unsplash.com/photo-1505236858219-8359eb29e329?w=200&q=80&auto=format&fit=crop" alt=""></span><span class="nmp-label">Event Planners</span></a>
                                <a class="nmp-tile" href="{{ route('events-categories') }}"><span class="nmp-bubble"><img src="https://images.unsplash.com/photo-1600565193348-f74bd3c7ccdf?w=200&q=80&auto=format&fit=crop" alt=""></span><span class="nmp-label">Event Staff</span></a>
                                <a class="nmp-tile" href="{{ route('events-categories') }}"><span class="nmp-bubble"><img src="https://images.unsplash.com/photo-1567360425618-1594206637d2?w=200&q=80&auto=format&fit=crop" alt=""></span><span class="nmp-label">Awards</span></a>
                                <a class="nmp-tile" href="{{ route('events-categories') }}"><span class="nmp-bubble"><img src="https://images.unsplash.com/photo-1555244162-803834f70033?w=200&q=80&auto=format&fit=crop" alt=""></span><span class="nmp-label">Catering</span></a>
                                <a class="nmp-tile" href="{{ route('events-categories') }}"><span class="nmp-bubble"><img src="https://images.unsplash.com/photo-1452587925148-ce544e77e70d?w=200&q=80&auto=format&fit=crop" alt=""></span><span class="nmp-label">Headshots</span></a>
                                <a class="nmp-tile" href="{{ route('events-categories') }}"><span class="nmp-bubble"><img src="https://images.unsplash.com/photo-1519741497674-611481863552?w=200&q=80&auto=format&fit=crop" alt=""></span><span class="nmp-label">Venues</span></a>
                            </div>
                        </div>

                        <div class="nmp-panel" data-panel="birthday">
                            <h4 class="nmp-title">Popular in <span>Birthday Parties</span></h4>
                            <div class="nmp-grid">
                                <a class="nmp-tile" href="{{ route('events-categories') }}"><span class="nmp-bubble"><img src="https://images.unsplash.com/photo-1571266028243-e1d11d2c01a8?w=200&q=80&auto=format&fit=crop" alt=""></span><span class="nmp-label">Party DJs</span></a>
                                <a class="nmp-tile" href="{{ route('events-categories') }}"><span class="nmp-bubble"><img src="https://images.unsplash.com/photo-1555244162-803834f70033?w=200&q=80&auto=format&fit=crop" alt=""></span><span class="nmp-label">Cakes</span></a>
                                <a class="nmp-tile" href="{{ route('events-categories') }}"><span class="nmp-bubble"><img src="https://images.unsplash.com/photo-1452587925148-ce544e77e70d?w=200&q=80&auto=format&fit=crop" alt=""></span><span class="nmp-label">Photo Booths</span></a>
                                <a class="nmp-tile" href="{{ route('events-categories') }}"><span class="nmp-bubble"><img src="https://images.unsplash.com/photo-1511795409834-ef04bbd61622?w=200&q=80&auto=format&fit=crop" alt=""></span><span class="nmp-label">Balloon Decor</span></a>
                                <a class="nmp-tile" href="{{ route('events-categories') }}"><span class="nmp-bubble"><img src="https://images.unsplash.com/photo-1600565193348-f74bd3c7ccdf?w=200&q=80&auto=format&fit=crop" alt=""></span><span class="nmp-label">Entertainers</span></a>
                                <a class="nmp-tile" href="{{ route('events-categories') }}"><span class="nmp-bubble"><img src="https://images.unsplash.com/photo-1505236858219-8359eb29e329?w=200&q=80&auto=format&fit=crop" alt=""></span><span class="nmp-label">Planners</span></a>
                                <a class="nmp-tile" href="{{ route('events-categories') }}"><span class="nmp-bubble"><img src="https://images.unsplash.com/photo-1567360425618-1594206637d2?w=200&q=80&auto=format&fit=crop" alt=""></span><span class="nmp-label">Party Favors</span></a>
                                <a class="nmp-tile" href="{{ route('events-categories') }}"><span class="nmp-bubble"><img src="https://images.unsplash.com/photo-1464347744102-11db6282f854?w=200&q=80&auto=format&fit=crop" alt=""></span><span class="nmp-label">Venues</span></a>
                            </div>
                        </div>

                        <div class="nmp-panel" data-panel="baby-shower">
                            <h4 class="nmp-title">Popular in <span>Baby Showers</span></h4>
                            <div class="nmp-grid">
                                <a class="nmp-tile" href="{{ route('events-categories') }}"><span class="nmp-bubble"><img src="https://images.unsplash.com/photo-1511795409834-ef04bbd61622?w=200&q=80&auto=format&fit=crop" alt=""></span><span class="nmp-label">Themed Decor</span></a>
                                <a class="nmp-tile" href="{{ route('events-categories') }}"><span class="nmp-bubble"><img src="https://images.unsplash.com/photo-1555244162-803834f70033?w=200&q=80&auto=format&fit=crop" alt=""></span><span class="nmp-label">Custom Cakes</span></a>
                                <a class="nmp-tile" href="{{ route('events-categories') }}"><span class="nmp-bubble"><img src="https://images.unsplash.com/photo-1452587925148-ce544e77e70d?w=200&q=80&auto=format&fit=crop" alt=""></span><span class="nmp-label">Photographers</span></a>
                                <a class="nmp-tile" href="{{ route('events-categories') }}"><span class="nmp-bubble"><img src="https://images.unsplash.com/photo-1505236858219-8359eb29e329?w=200&q=80&auto=format&fit=crop" alt=""></span><span class="nmp-label">Shower Planners</span></a>
                                <a class="nmp-tile" href="{{ route('events-categories') }}"><span class="nmp-bubble"><img src="https://images.unsplash.com/photo-1567360425618-1594206637d2?w=200&q=80&auto=format&fit=crop" alt=""></span><span class="nmp-label">Party Favors</span></a>
                                <a class="nmp-tile" href="{{ route('events-categories') }}"><span class="nmp-bubble"><img src="https://images.unsplash.com/photo-1464347744102-11db6282f854?w=200&q=80&auto=format&fit=crop" alt=""></span><span class="nmp-label">Balloons</span></a>
                                <a class="nmp-tile" href="{{ route('events-categories') }}"><span class="nmp-bubble"><img src="https://images.unsplash.com/photo-1530103862676-de8c9debad1d?w=200&q=80&auto=format&fit=crop" alt=""></span><span class="nmp-label">Gift Boxes</span></a>
                            </div>
                        </div>

                        <div class="nmp-panel" data-panel="music">
                            <h4 class="nmp-title">Popular in <span>Music &amp; Entertainment</span></h4>
                            <div class="nmp-grid">
                                <a class="nmp-tile" href="{{ route('events-categories') }}"><span class="nmp-bubble"><img src="https://images.unsplash.com/photo-1571266028243-e1d11d2c01a8?w=200&q=80&auto=format&fit=crop" alt=""></span><span class="nmp-label">DJ Services</span></a>
                                <a class="nmp-tile" href="{{ route('events-categories') }}"><span class="nmp-bubble"><img src="https://images.unsplash.com/photo-1501386761578-eac5c94b800a?w=200&q=80&auto=format&fit=crop" alt=""></span><span class="nmp-label">Live Bands</span></a>
                                <a class="nmp-tile" href="{{ route('events-categories') }}"><span class="nmp-bubble"><img src="https://images.unsplash.com/photo-1429962714451-bb934ecdc4ec?w=200&q=80&auto=format&fit=crop" alt=""></span><span class="nmp-label">Solo Artists</span></a>
                                <a class="nmp-tile" href="{{ route('events-categories') }}"><span class="nmp-bubble"><img src="https://images.unsplash.com/photo-1600565193348-f74bd3c7ccdf?w=200&q=80&auto=format&fit=crop" alt=""></span><span class="nmp-label">Emcees</span></a>
                                <a class="nmp-tile" href="{{ route('events-categories') }}"><span class="nmp-bubble"><img src="https://images.unsplash.com/photo-1540575467063-178a50c2df87?w=200&q=80&auto=format&fit=crop" alt=""></span><span class="nmp-label">Sound &amp; AV</span></a>
                                <a class="nmp-tile" href="{{ route('events-categories') }}"><span class="nmp-bubble"><img src="https://images.unsplash.com/photo-1519741497674-611481863552?w=200&q=80&auto=format&fit=crop" alt=""></span><span class="nmp-label">String Quartets</span></a>
                                <a class="nmp-tile" href="{{ route('events-categories') }}"><span class="nmp-bubble"><img src="https://images.unsplash.com/photo-1464347744102-11db6282f854?w=200&q=80&auto=format&fit=crop" alt=""></span><span class="nmp-label">Karaoke</span></a>
                                <a class="nmp-tile" href="{{ route('events-categories') }}"><span class="nmp-bubble"><img src="https://images.unsplash.com/photo-1452587925148-ce544e77e70d?w=200&q=80&auto=format&fit=crop" alt=""></span><span class="nmp-label">Dancers</span></a>
                            </div>
                        </div>

                        <div class="nmp-panel" data-panel="visual">
                            <h4 class="nmp-title">Popular in <span>Photo &amp; Video</span></h4>
                            <div class="nmp-grid">
                                <a class="nmp-tile" href="{{ route('events-categories') }}"><span class="nmp-bubble"><img src="https://images.unsplash.com/photo-1452587925148-ce544e77e70d?w=200&q=80&auto=format&fit=crop" alt=""></span><span class="nmp-label">Wedding Photo</span></a>
                                <a class="nmp-tile" href="{{ route('events-categories') }}"><span class="nmp-bubble"><img src="https://images.unsplash.com/photo-1511578314322-379afb476865?w=200&q=80&auto=format&fit=crop" alt=""></span><span class="nmp-label">Corporate Video</span></a>
                                <a class="nmp-tile" href="{{ route('events-categories') }}"><span class="nmp-bubble"><img src="https://images.unsplash.com/photo-1464347744102-11db6282f854?w=200&q=80&auto=format&fit=crop" alt=""></span><span class="nmp-label">Event Photo</span></a>
                                <a class="nmp-tile" href="{{ route('events-categories') }}"><span class="nmp-bubble"><img src="https://images.unsplash.com/photo-1501386761578-eac5c94b800a?w=200&q=80&auto=format&fit=crop" alt=""></span><span class="nmp-label">Drone Shoots</span></a>
                                <a class="nmp-tile" href="{{ route('events-categories') }}"><span class="nmp-bubble"><img src="https://images.unsplash.com/photo-1600565193348-f74bd3c7ccdf?w=200&q=80&auto=format&fit=crop" alt=""></span><span class="nmp-label">Photo Booths</span></a>
                                <a class="nmp-tile" href="{{ route('events-categories') }}"><span class="nmp-bubble"><img src="https://images.unsplash.com/photo-1530103862676-de8c9debad1d?w=200&q=80&auto=format&fit=crop" alt=""></span><span class="nmp-label">Lifestyle</span></a>
                                <a class="nmp-tile" href="{{ route('events-categories') }}"><span class="nmp-bubble"><img src="https://images.unsplash.com/photo-1540575467063-178a50c2df87?w=200&q=80&auto=format&fit=crop" alt=""></span><span class="nmp-label">Livestreaming</span></a>
                            </div>
                        </div>

                        <div class="nmp-panel" data-panel="food">
                            <h4 class="nmp-title">Popular in <span>Food &amp; Catering</span></h4>
                            <div class="nmp-grid">
                                <a class="nmp-tile" href="{{ route('events-categories') }}"><span class="nmp-bubble"><img src="https://images.unsplash.com/photo-1555244162-803834f70033?w=200&q=80&auto=format&fit=crop" alt=""></span><span class="nmp-label">Full Catering</span></a>
                                <a class="nmp-tile" href="{{ route('events-categories') }}"><span class="nmp-bubble"><img src="https://images.unsplash.com/photo-1567360425618-1594206637d2?w=200&q=80&auto=format&fit=crop" alt=""></span><span class="nmp-label">Bartending</span></a>
                                <a class="nmp-tile" href="{{ route('events-categories') }}"><span class="nmp-bubble"><img src="https://images.unsplash.com/photo-1540575467063-178a50c2df87?w=200&q=80&auto=format&fit=crop" alt=""></span><span class="nmp-label">Food Trucks</span></a>
                                <a class="nmp-tile" href="{{ route('events-categories') }}"><span class="nmp-bubble"><img src="https://images.unsplash.com/photo-1505236858219-8359eb29e329?w=200&q=80&auto=format&fit=crop" alt=""></span><span class="nmp-label">Cakes</span></a>
                                <a class="nmp-tile" href="{{ route('events-categories') }}"><span class="nmp-bubble"><img src="https://images.unsplash.com/photo-1429962714451-bb934ecdc4ec?w=200&q=80&auto=format&fit=crop" alt=""></span><span class="nmp-label">Coffee Carts</span></a>
                                <a class="nmp-tile" href="{{ route('events-categories') }}"><span class="nmp-bubble"><img src="https://images.unsplash.com/photo-1511578314322-379afb476865?w=200&q=80&auto=format&fit=crop" alt=""></span><span class="nmp-label">Private Chefs</span></a>
                                <a class="nmp-tile" href="{{ route('events-categories') }}"><span class="nmp-bubble"><img src="https://images.unsplash.com/photo-1600565193348-f74bd3c7ccdf?w=200&q=80&auto=format&fit=crop" alt=""></span><span class="nmp-label">Servers</span></a>
                                <a class="nmp-tile" href="{{ route('events-categories') }}"><span class="nmp-bubble"><img src="https://images.unsplash.com/photo-1530103862676-de8c9debad1d?w=200&q=80&auto=format&fit=crop" alt=""></span><span class="nmp-label">Desserts</span></a>
                            </div>
                        </div>

                        <div class="nmp-panel" data-panel="decor">
                            <h4 class="nmp-title">Popular in <span>Decor &amp; Floral</span></h4>
                            <div class="nmp-grid">
                                <a class="nmp-tile" href="{{ route('events-categories') }}"><span class="nmp-bubble"><img src="https://images.unsplash.com/photo-1511795409834-ef04bbd61622?w=200&q=80&auto=format&fit=crop" alt=""></span><span class="nmp-label">Florists</span></a>
                                <a class="nmp-tile" href="{{ route('events-categories') }}"><span class="nmp-bubble"><img src="https://images.unsplash.com/photo-1464347744102-11db6282f854?w=200&q=80&auto=format&fit=crop" alt=""></span><span class="nmp-label">Balloons</span></a>
                                <a class="nmp-tile" href="{{ route('events-categories') }}"><span class="nmp-bubble"><img src="https://images.unsplash.com/photo-1519741497674-611481863552?w=200&q=80&auto=format&fit=crop" alt=""></span><span class="nmp-label">Backdrops</span></a>
                                <a class="nmp-tile" href="{{ route('events-categories') }}"><span class="nmp-bubble"><img src="https://images.unsplash.com/photo-1540575467063-178a50c2df87?w=200&q=80&auto=format&fit=crop" alt=""></span><span class="nmp-label">Event Lighting</span></a>
                                <a class="nmp-tile" href="{{ route('events-categories') }}"><span class="nmp-bubble"><img src="https://images.unsplash.com/photo-1530103862676-de8c9debad1d?w=200&q=80&auto=format&fit=crop" alt=""></span><span class="nmp-label">Rentals</span></a>
                                <a class="nmp-tile" href="{{ route('events-categories') }}"><span class="nmp-bubble"><img src="https://images.unsplash.com/photo-1567360425618-1594206637d2?w=200&q=80&auto=format&fit=crop" alt=""></span><span class="nmp-label">Signage</span></a>
                                <a class="nmp-tile" href="{{ route('events-categories') }}"><span class="nmp-bubble"><img src="https://images.unsplash.com/photo-1511578314322-379afb476865?w=200&q=80&auto=format&fit=crop" alt=""></span><span class="nmp-label">Draping</span></a>
                                <a class="nmp-tile" href="{{ route('events-categories') }}"><span class="nmp-bubble"><img src="https://images.unsplash.com/photo-1505236858219-8359eb29e329?w=200&q=80&auto=format&fit=crop" alt=""></span><span class="nmp-label">Linens</span></a>
                            </div>
                        </div>

                        <div class="nmp-panel" data-panel="staff">
                            <h4 class="nmp-title">Popular in <span>Planners &amp; Staff</span></h4>
                            <div class="nmp-grid">
                                <a class="nmp-tile" href="{{ route('events-categories') }}"><span class="nmp-bubble"><img src="https://images.unsplash.com/photo-1505236858219-8359eb29e329?w=200&q=80&auto=format&fit=crop" alt=""></span><span class="nmp-label">Event Planners</span></a>
                                <a class="nmp-tile" href="{{ route('events-categories') }}"><span class="nmp-bubble"><img src="https://images.unsplash.com/photo-1600565193348-f74bd3c7ccdf?w=200&q=80&auto=format&fit=crop" alt=""></span><span class="nmp-label">Servers</span></a>
                                <a class="nmp-tile" href="{{ route('events-categories') }}"><span class="nmp-bubble"><img src="https://images.unsplash.com/photo-1540575467063-178a50c2df87?w=200&q=80&auto=format&fit=crop" alt=""></span><span class="nmp-label">Security</span></a>
                                <a class="nmp-tile" href="{{ route('events-categories') }}"><span class="nmp-bubble"><img src="https://images.unsplash.com/photo-1511578314322-379afb476865?w=200&q=80&auto=format&fit=crop" alt=""></span><span class="nmp-label">Registration</span></a>
                                <a class="nmp-tile" href="{{ route('events-categories') }}"><span class="nmp-bubble"><img src="https://images.unsplash.com/photo-1519741497674-611481863552?w=200&q=80&auto=format&fit=crop" alt=""></span><span class="nmp-label">Day-Of Coord.</span></a>
                                <a class="nmp-tile" href="{{ route('events-categories') }}"><span class="nmp-bubble"><img src="https://images.unsplash.com/photo-1464347744102-11db6282f854?w=200&q=80&auto=format&fit=crop" alt=""></span><span class="nmp-label">Valet</span></a>
                                <a class="nmp-tile" href="{{ route('events-categories') }}"><span class="nmp-bubble"><img src="https://images.unsplash.com/photo-1567360425618-1594206637d2?w=200&q=80&auto=format&fit=crop" alt=""></span><span class="nmp-label">Concierge</span></a>
                                <a class="nmp-tile" href="{{ route('events-categories') }}"><span class="nmp-bubble"><img src="https://images.unsplash.com/photo-1530103862676-de8c9debad1d?w=200&q=80&auto=format&fit=crop" alt=""></span><span class="nmp-label">Greeters</span></a>
                            </div>
                        </div>

                    </div>
                </div>
            </li>

            <li><a href="{{ route('public.browse') }}" class="{{ request()->routeIs('public.browse') ? 'is-active' : '' }}">Browse Pros</a></li>
            <li><a href="{{ route('about-us') }}" class="{{ request()->routeIs('about-us') ? 'is-active' : '' }}">About Us</a></li>
            <li><a href="{{ route('events-categories') }}" class="{{ request()->routeIs('events-categories') ? 'is-active' : '' }}">Events</a></li>
            <li><a href="{{ route('blog.index') }}" class="{{ request()->routeIs('blog.*') ? 'is-active' : '' }}">Blog</a></li>
            <li><a href="{{ route('public.how-it-works') }}" class="{{ request()->routeIs('public.how-it-works') ? 'is-active' : '' }}">How It Works</a></li>
            <li><a href="{{ route('landing') }}#pricing">Pricing</a></li>
            <li><a href="{{ route('landing') }}#faq">FAQ</a></li>
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
