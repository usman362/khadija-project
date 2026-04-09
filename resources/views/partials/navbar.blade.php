<nav class="navbar">
    <div class="container">
        <a href="{{ route('landing') }}" class="navbar-brand"><img src="{{ asset('logos/logo-light.png') }}" alt="{{ config('app.name') }}" style="height: 36px;"></a>

        <ul class="navbar-links">
            <li><a href="{{ route('about-us') }}">About Us</a></li>
            <li><a href="{{ route('events-categories') }}">Events</a></li>
            <li><a href="{{ route('blog.index') }}">Blog</a></li>
            <li><a href="{{ route('landing') }}#how-it-works">How It Works</a></li>
            <li><a href="{{ route('landing') }}#pricing">Pricing</a></li>
            <li><a href="{{ route('landing') }}#faq">FAQ</a></li>
        </ul>

        <div class="navbar-actions">
            @auth
                <a href="{{ url('/dashboard') }}" class="btn btn-primary btn-sm">Dashboard</a>
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
</nav>
