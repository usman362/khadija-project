@extends('layouts.public')

@section('title', config('app.name', 'Khadija') . ' - Host Unforgettable Events With Confidence')

@section('content')


<!-- ─── HERO ─────────────────────────────────── -->
<section class="hero">
    <div class="hero-bg">
        <img src="https://images.unsplash.com/photo-1533174072545-7a4b6ad7a6c3?w=1600&q=80" alt="Outdoor event festival with colorful lights and staging" loading="eager">
    </div>
    <div class="container">
        <h1>Find The Right<br><span class="gradient-text">Professional</span> For<br>Every Event</h1>
        <p class="hero-subtitle">
            GigResource connects event organizers with verified professionals. Book photographers, DJs, caterers,
            decorators, and more &mdash; all in one platform.
        </p>
        <div class="hero-buttons">
            @auth
                <a href="{{ url('/dashboard') }}" class="btn btn-primary btn-lg">Go to Dashboard</a>
            @else
                <a href="{{ route('register', ['role' => 'supplier']) }}" class="btn btn-blue btn-lg">Join as Professional</a>
                <a href="{{ route('register', ['role' => 'client']) }}" class="btn btn-red btn-lg">Hire Now</a>
            @endauth
        </div>

        <div class="trust-badges">
            <div class="trust-badge">
                <div class="badge-icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/><polyline points="9 12 11 14 15 10"/></svg>
                </div>
                <h4>Verified Experts</h4>
                <p>Vetted professionals only</p>
            </div>
            <div class="trust-badge">
                <div class="badge-icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="1" y="4" width="22" height="16" rx="2" ry="2"/><line x1="1" y1="10" x2="23" y2="10"/></svg>
                </div>
                <h4>Secure Payments</h4>
                <p>Safe & trusted transactions</p>
            </div>
            <div class="trust-badge">
                <div class="badge-icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                </div>
                <h4>Event Categories</h4>
                <p>Browse all types of events</p>
            </div>
            <div class="trust-badge">
                <div class="badge-icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
                </div>
                <h4>24/7 Support</h4>
                <p>We're here to help anytime</p>
            </div>
        </div>
    </div>
</section>

<!-- ─── ABOUT US ─────────────────────────────── -->
<section class="section" id="about">
    <div class="container">
        <div class="about-grid">
            <div class="about-content">
                <h3>About Us</h3>
                <h2>We Connect <span class="gradient-text">Talent</span> With Opportunity</h2>
                <p>
                    GigResource is a next-generation marketplace designed to bridge the gap between skilled event
                    professionals and clients who need them. Whether you're planning a wedding, corporate event,
                    or private celebration, we make it effortless to find, book, and collaborate with top-tier talent.
                </p>
                <p>
                    Our platform handles everything from discovery to secure payments, real-time messaging,
                    and professional service agreements &mdash; so you can focus on what matters: creating
                    unforgettable experiences.
                </p>
                <div class="about-stats">
                    <div class="about-stat">
                        <h4>500+</h4>
                        <p>Professionals</p>
                    </div>
                    <div class="about-stat">
                        <h4>1,200+</h4>
                        <p>Events Booked</p>
                    </div>
                    <div class="about-stat">
                        <h4>98%</h4>
                        <p>Satisfaction</p>
                    </div>
                </div>
            </div>
            <div class="about-image">
                <img src="https://images.unsplash.com/photo-1511578314322-379afb476865?w=800&q=80" alt="Event planning team" loading="lazy">
            </div>
        </div>
    </div>
</section>

<!-- ─── HOW IT WORKS ──────────────────────────── -->
<section class="section" id="how-it-works">
    <div class="container">
        <div class="section-header">
            <h2>Getting Started is Easy</h2>
            <p>A simple, transparent process for planners and professionals.</p>
        </div>

        <div class="steps-grid">
            <div class="step-card">
                <div class="step-number">1</div>
                <div class="step-icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="12" y1="18" x2="12" y2="12"/><line x1="9" y1="15" x2="15" y2="15"/></svg>
                </div>
                <h3>Post Your Event</h3>
                <p>Describe your event, set dates, and specify the professionals you need.</p>
            </div>
            <div class="step-card">
                <div class="step-number">2</div>
                <div class="step-icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                </div>
                <h3>Choose Professionals</h3>
                <p>Browse verified profiles, compare rates, and select the perfect match for your event.</p>
            </div>
            <div class="step-card">
                <div class="step-number">3</div>
                <div class="step-icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/><polyline points="9 12 11 14 15 10"/></svg>
                </div>
                <h3>Book Safely</h3>
                <p>Confirm your booking with secure payments and real-time chat with your team.</p>
            </div>
        </div>

        <div style="text-align: center; margin-top: 48px; display: flex; gap: 16px; justify-content: center; flex-wrap: wrap;">
            @auth
                <a href="{{ url('/dashboard') }}" class="btn btn-primary btn-lg">Go to Dashboard</a>
            @else
                <a href="{{ route('register') }}" class="btn btn-primary btn-lg">Join as Professional</a>
                <a href="{{ route('register') }}" class="btn btn-outline btn-lg">Hire a Professional</a>
            @endauth
        </div>
    </div>
</section>

<!-- ─── CTA BANNER ────────────────────────────── -->
<section class="section section-alt">
    <div class="container">
        <div class="cta-banner">
            <div class="cta-content">
                <h2>Become a {{ config('app.name', 'Khadija') }} Professional</h2>
                <p>Partner with a leading platform, help others create amazing events, and earn competitive commissions for every successful referral.</p>
                <ul class="cta-features">
                    <li>
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
                        Earning Potential — Set your own rates
                    </li>
                    <li>
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
                        Simple Tracking & Payments
                    </li>
                    <li>
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
                        Grow your client base organically
                    </li>
                </ul>
                <a href="{{ Route::has('register') ? route('register') : '#' }}" class="btn btn-primary btn-lg">Start Today</a>
            </div>
            <div class="cta-image">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
            </div>
        </div>
    </div>
</section>

<!-- ─── PRICING ───────────────────────────────── -->
<section class="section section-alt" id="pricing">
    <div class="container">
        <div class="section-header">
            <h2>Flexible Pricing for Every Need</h2>
            <p>Choose the perfect plan to launch your events to the next level.</p>
        </div>

        <div class="pricing-tabs">
            <div class="pricing-tab active">For Professionals</div>
            <div class="pricing-tab">For Clients</div>
        </div>

        <div class="pricing-toggle">
            <span class="toggle-label active" id="monthlyLabel">Monthly</span>
            <div class="toggle-switch" id="billingToggle" onclick="this.classList.toggle('yearly')"></div>
            <span class="toggle-label" id="yearlyLabel">Yearly</span>
            <span class="pricing-save">Save 15%</span>
        </div>

        <div class="pricing-grid">
            @php
                $planIcons = [
                    0 => ['bg' => 'rgba(107,114,128,0.15)', 'color' => '#9ca3af'],
                    1 => ['bg' => 'rgba(59,130,246,0.15)', 'color' => '#3b82f6'],
                    2 => ['bg' => 'rgba(139,92,246,0.15)', 'color' => '#8b5cf6'],
                    3 => ['bg' => 'rgba(245,158,11,0.15)', 'color' => '#f59e0b'],
                ];
            @endphp

            @foreach($plans as $index => $plan)
                @php
                    $icon = $planIcons[$index % 4];
                @endphp
                <div class="pricing-card {{ $plan->is_featured ? 'featured' : '' }}">
                    @if($plan->badge_text)
                        <div class="pricing-badge badge-{{ $plan->badge_color ?? 'primary' }}">{{ $plan->badge_text }}</div>
                    @endif

                    <div class="pricing-card-icon" style="background: {{ $icon['bg'] }};">
                        @if($index === 0)
                            <svg viewBox="0 0 24 24" fill="none" stroke="{{ $icon['color'] }}" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                        @elseif($index === 1)
                            <svg viewBox="0 0 24 24" fill="none" stroke="{{ $icon['color'] }}" stroke-width="2"><path d="M12 2L2 7l10 5 10-5-10-5z"/><path d="M2 17l10 5 10-5"/><path d="M2 12l10 5 10-5"/></svg>
                        @elseif($index === 2)
                            <svg viewBox="0 0 24 24" fill="none" stroke="{{ $icon['color'] }}" stroke-width="2"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>
                        @else
                            <svg viewBox="0 0 24 24" fill="none" stroke="{{ $icon['color'] }}" stroke-width="2"><path d="M12 2L2 7l10 5 10-5-10-5z"/><path d="M2 17l10 5 10-5"/><path d="M2 12l10 5 10-5"/><circle cx="12" cy="12" r="3"/></svg>
                        @endif
                    </div>

                    <div class="pricing-plan-name">{{ $plan->name }}</div>
                    <div class="pricing-plan-desc">{{ $plan->description ?? 'Perfect for your needs' }}</div>

                    <div class="pricing-amount">
                        <span class="pricing-currency">$</span>
                        <span class="pricing-value">{{ intval($plan->price) }}</span>
                        @if(!$plan->isFree())
                            <span class="pricing-cycle">{{ $plan->billingLabel() }}</span>
                        @endif
                    </div>

                    <ul class="pricing-features">
                        @if($plan->max_events)
                            <li>
                                <svg class="check" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
                                Up to {{ $plan->max_events }} events
                            </li>
                        @else
                            <li>
                                <svg class="check" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
                                Unlimited events
                            </li>
                        @endif
                        @if($plan->max_bookings)
                            <li>
                                <svg class="check" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
                                Up to {{ $plan->max_bookings }} bookings
                            </li>
                        @else
                            <li>
                                <svg class="check" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
                                Unlimited bookings
                            </li>
                        @endif
                        @foreach($plan->features as $feature)
                            <li class="{{ !$feature->is_included ? 'excluded' : '' }}">
                                @if($feature->is_included)
                                    <svg class="check" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
                                @else
                                    <svg class="cross" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                                @endif
                                {{ $feature->feature }}
                            </li>
                        @endforeach
                    </ul>

                    @auth
                        <a href="{{ route('app.membership-plans.index') }}" class="pricing-btn {{ $plan->is_featured ? 'pricing-btn-primary' : 'pricing-btn-default' }}">
                            {{ $plan->isFree() ? 'Get Started' : 'Choose Plan' }}
                        </a>
                    @else
                        <a href="{{ route('register') }}" class="pricing-btn {{ $plan->is_featured ? 'pricing-btn-primary' : 'pricing-btn-default' }}">
                            {{ $plan->isFree() ? 'Get Started' : 'Choose Plan' }}
                        </a>
                    @endauth
                </div>
            @endforeach
        </div>
    </div>
</section>

<!-- ─── TESTIMONIALS ──────────────────────────── -->
<section class="section section-alt">
    <div class="container">
        <div class="section-header">
            <h2>Trusted by Planners & Professionals</h2>
            <p>Here's what our community says about {{ config('app.name', 'Khadija') }}.</p>
        </div>

        <div class="testimonials-grid">
            <div class="testimonial-card">
                <div class="testimonial-stars">
                    @for($i = 0; $i < 5; $i++)
                        <svg viewBox="0 0 24 24"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>
                    @endfor
                </div>
                <blockquote>"{{ config('app.name') }} revolutionized how I manage events. It's intuitive, fast, and I found the perfect photographer for a last-minute wedding."</blockquote>
                <div class="testimonial-author">
                    <div class="testimonial-avatar">S</div>
                    <div>
                        <div class="testimonial-author-name">Sarah K.</div>
                        <div class="testimonial-author-role">Wedding Planner</div>
                    </div>
                </div>
            </div>
            <div class="testimonial-card">
                <div class="testimonial-stars">
                    @for($i = 0; $i < 5; $i++)
                        <svg viewBox="0 0 24 24"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>
                    @endfor
                </div>
                <blockquote>"The quality of professionals here is unmatched. The hiring process is as fair as it can get and it was flawless from start to finish."</blockquote>
                <div class="testimonial-author">
                    <div class="testimonial-avatar">M</div>
                    <div>
                        <div class="testimonial-author-name">Mike R.</div>
                        <div class="testimonial-author-role">Corporate Event Manager</div>
                    </div>
                </div>
            </div>
            <div class="testimonial-card">
                <div class="testimonial-stars">
                    @for($i = 0; $i < 5; $i++)
                        <svg viewBox="0 0 24 24"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>
                    @endfor
                </div>
                <blockquote>"As a DJ, I've doubled my bookings since joining. The platform makes it easy to showcase my work and connect with clients directly."</blockquote>
                <div class="testimonial-author">
                    <div class="testimonial-avatar">A</div>
                    <div>
                        <div class="testimonial-author-name">Ahmed J.</div>
                        <div class="testimonial-author-role">Professional DJ & Musician</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- ─── FAQ ───────────────────────────────────── -->
<section class="section" id="faq">
    <div class="container">
        <div class="section-header">
            <h2>Frequently Asked <span class="gradient-text">Questions</span></h2>
            <p>Everything you need to know about using GigResource.</p>
        </div>
        <div class="faq-grid">
            @forelse($faqs as $faq)
                <div class="faq-item {{ $loop->first ? 'active' : '' }}">
                    <button class="faq-question" onclick="toggleFaq(this)">
                        <span>{{ $faq->question }}</span>
                        <svg class="faq-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                    </button>
                    <div class="faq-answer">
                        <div class="faq-answer-inner">{!! $faq->answer !!}</div>
                    </div>
                </div>
            @empty
                {{-- Fallback if no FAQs in database yet --}}
                <div class="faq-item active">
                    <button class="faq-question" onclick="toggleFaq(this)">
                        <span>How does GigResource work?</span>
                        <svg class="faq-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                    </button>
                    <div class="faq-answer">
                        <div class="faq-answer-inner">
                            GigResource connects event organizers (clients) with verified service professionals (suppliers). Simply create an account, browse available professionals by category, send booking requests, discuss details through our built-in chat, and confirm your booking.
                        </div>
                    </div>
                </div>
            @endforelse
        </div>
    </div>
</section>

<!-- ─── NEWSLETTER ─────────────────────────────── -->
<section class="section section-alt newsletter">
    <div class="container">
        <h2>Get Eventful Updates!</h2>
        <p>Subscribe to our newsletter for the latest industry news, planning tips, and exclusive offers.</p>
        <div class="newsletter-form">
            <input type="email" placeholder="Enter your email address">
            <button class="btn btn-primary">Subscribe</button>
        </div>
    </div>
</section>

@endsection

@push('scripts')
<script>
    document.querySelectorAll('.pricing-tab').forEach(tab => {
        tab.addEventListener('click', function () {
            document.querySelectorAll('.pricing-tab').forEach(t => t.classList.remove('active'));
            this.classList.add('active');
        });
    });
    function toggleFaq(btn) {
        const item = btn.parentElement;
        const isActive = item.classList.contains('active');
        document.querySelectorAll('.faq-item').forEach(i => i.classList.remove('active'));
        if (!isActive) item.classList.add('active');
    }
</script>
@endpush
