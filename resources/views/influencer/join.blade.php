@extends('layouts.public')

@section('title', 'Join As Influencer - ' . config('app.name', 'Khadija'))

@push('styles')
<style>
    .inf-hero {
        padding: 160px 0 100px;
        background: linear-gradient(135deg, #1e1b4b 0%, #312e81 50%, #4c1d95 100%);
        text-align: center;
        position: relative;
        overflow: hidden;
    }
    .inf-hero-bg {
        position: absolute;
        inset: 0;
        z-index: 0;
    }
    .inf-hero-bg img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        opacity: 0.25;
    }
    .inf-hero-bg::after {
        content: '';
        position: absolute; inset: 0;
        background: linear-gradient(180deg, rgba(30,27,75,0.65) 0%, rgba(30,27,75,0.9) 100%);
    }
    .inf-hero::before {
        content: '';
        position: absolute; inset: 0;
        background: radial-gradient(circle at 20% 30%, rgba(59,130,246,0.25), transparent 60%),
                    radial-gradient(circle at 80% 70%, rgba(139,92,246,0.25), transparent 60%);
        z-index: 1;
    }
    .inf-hero .container { position: relative; z-index: 2; }

    .inf-hero-avatars {
        display: flex;
        justify-content: center;
        gap: -10px;
        margin: 32px auto 0;
    }
    .inf-hero-avatar {
        width: 52px;
        height: 52px;
        border-radius: 50%;
        border: 3px solid rgba(255,255,255,0.2);
        overflow: hidden;
        margin-left: -14px;
        background: var(--bg-card);
    }
    .inf-hero-avatar:first-child { margin-left: 0; }
    .inf-hero-avatar img { width: 100%; height: 100%; object-fit: cover; }
    .inf-hero-stat {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        margin-top: 20px;
        padding: 10px 18px;
        background: rgba(255,255,255,0.08);
        border: 1px solid rgba(255,255,255,0.15);
        border-radius: 999px;
        backdrop-filter: blur(8px);
        font-size: 0.85rem;
        color: rgba(255,255,255,0.92);
    }
    .inf-hero-stat strong { color: #fff; font-weight: 700; }

    /* ─── SHOWCASE BANNER ─────────── */
    .inf-showcase {
        padding: 60px 0;
        background: var(--bg-dark);
    }
    .inf-showcase-grid {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 16px;
    }
    .inf-showcase-tile {
        position: relative;
        border-radius: 14px;
        overflow: hidden;
        aspect-ratio: 4 / 3;
    }
    .inf-showcase-tile img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        transition: transform 0.5s;
    }
    .inf-showcase-tile:hover img { transform: scale(1.05); }
    .inf-showcase-tile::after {
        content: '';
        position: absolute; inset: 0;
        background: linear-gradient(180deg, rgba(0,0,0,0), rgba(11,15,26,0.7));
    }
    .inf-showcase-label {
        position: absolute;
        left: 16px; bottom: 14px;
        color: #fff;
        font-weight: 600;
        z-index: 2;
        font-size: 0.95rem;
    }
    @media (max-width: 800px) { .inf-showcase-grid { grid-template-columns: 1fr 1fr; } }
    @media (max-width: 500px) { .inf-showcase-grid { grid-template-columns: 1fr; } }
    .inf-hero-badge {
        display: inline-block;
        padding: 6px 16px;
        background: rgba(255,255,255,0.1);
        border: 1px solid rgba(255,255,255,0.2);
        border-radius: 999px;
        font-size: 0.82rem;
        font-weight: 600;
        margin-bottom: 20px;
        backdrop-filter: blur(10px);
    }
    .inf-hero h1 {
        font-size: clamp(2.2rem, 5vw, 3.8rem);
        font-weight: 800;
        line-height: 1.15;
        margin-bottom: 20px;
        color: #fff;
    }
    .inf-hero p {
        font-size: 1.15rem;
        color: rgba(255,255,255,0.85);
        max-width: 680px;
        margin: 0 auto 32px;
    }
    .inf-section { padding: 80px 0; }
    .inf-section-header { text-align: center; margin-bottom: 56px; }
    .inf-section-header h2 {
        font-size: clamp(1.8rem, 3.5vw, 2.5rem);
        font-weight: 800;
        margin-bottom: 12px;
    }
    .inf-section-header p { color: var(--text-muted); font-size: 1.05rem; }
    .inf-bg { background: var(--bg-section); }

    .inf-grid-4 { display: grid; grid-template-columns: repeat(4, 1fr); gap: 24px; }
    .inf-grid-3 { display: grid; grid-template-columns: repeat(3, 1fr); gap: 24px; }
    @media (max-width: 900px) {
        .inf-grid-4 { grid-template-columns: repeat(2, 1fr); }
        .inf-grid-3 { grid-template-columns: 1fr; }
    }
    @media (max-width: 560px) {
        .inf-grid-4 { grid-template-columns: 1fr; }
    }

    .inf-card {
        background: var(--bg-card);
        border: 1px solid var(--border-color);
        border-radius: 14px;
        padding: 28px 24px;
        text-align: center;
        transition: all 0.25s;
    }
    .inf-card:hover {
        transform: translateY(-4px);
        background: var(--bg-card-hover);
        border-color: var(--primary);
    }
    .inf-card-icon {
        width: 56px; height: 56px;
        margin: 0 auto 16px;
        background: linear-gradient(135deg, rgba(59,130,246,0.2), rgba(139,92,246,0.2));
        border-radius: 14px;
        display: flex; align-items: center; justify-content: center;
        font-size: 1.6rem;
    }
    .inf-card h3 { font-size: 1.1rem; font-weight: 700; margin-bottom: 8px; }
    .inf-card p { color: var(--text-muted); font-size: 0.9rem; }

    .inf-tier {
        background: var(--bg-card);
        border: 1px solid var(--border-color);
        border-radius: 14px;
        padding: 32px 24px;
        text-align: center;
        transition: all 0.25s;
    }
    .inf-tier:hover { border-color: var(--primary); transform: translateY(-4px); }
    .inf-tier h3 { font-size: 1.2rem; font-weight: 700; margin-bottom: 8px; }
    .inf-tier-rate {
        font-size: 2.8rem;
        font-weight: 800;
        background: linear-gradient(135deg, var(--primary), var(--accent));
        -webkit-background-clip: text;
        background-clip: text;
        -webkit-text-fill-color: transparent;
        margin: 12px 0;
    }
    .inf-tier p { color: var(--text-muted); font-size: 0.88rem; }

    .inf-step-num {
        width: 48px; height: 48px;
        border-radius: 50%;
        background: linear-gradient(135deg, var(--primary), var(--accent));
        color: white;
        display: flex; align-items: center; justify-content: center;
        font-weight: 800;
        font-size: 1.15rem;
        margin: 0 auto 14px;
    }

    .inf-form-wrap { max-width: 620px; margin: 0 auto; }
    .inf-form-card {
        background: var(--bg-card);
        border: 1px solid var(--border-color);
        border-radius: 16px;
        padding: 36px;
    }
    .inf-form-group { margin-bottom: 18px; }
    .inf-form-group label {
        display: block;
        font-size: 0.85rem;
        font-weight: 600;
        margin-bottom: 8px;
        color: var(--text-light);
    }
    .inf-form-control {
        width: 100%;
        padding: 12px 14px;
        background: var(--bg-dark);
        border: 1px solid var(--border-color);
        border-radius: 10px;
        color: var(--text-white);
        font-size: 0.95rem;
        font-family: inherit;
        transition: all 0.2s;
    }
    .inf-form-control:focus {
        outline: none;
        border-color: var(--primary);
        box-shadow: 0 0 0 3px rgba(59,130,246,0.15);
    }
    textarea.inf-form-control { resize: vertical; min-height: 100px; }

    .inf-btn-submit {
        width: 100%;
        padding: 14px;
        background: linear-gradient(135deg, var(--primary), var(--accent));
        color: white;
        border-radius: 10px;
        font-weight: 700;
        font-size: 0.95rem;
        transition: all 0.2s;
        border: none;
        cursor: pointer;
    }
    .inf-btn-submit:hover { transform: translateY(-1px); box-shadow: 0 10px 30px rgba(59,130,246,0.35); }

    .inf-btn-cta {
        display: inline-block;
        padding: 14px 32px;
        background: linear-gradient(135deg, var(--primary), var(--accent));
        color: #fff;
        border-radius: 10px;
        font-weight: 700;
        font-size: 0.95rem;
        transition: all 0.2s;
        box-shadow: 0 10px 30px rgba(59,130,246,0.3);
    }
    .inf-btn-cta:hover { transform: translateY(-2px); box-shadow: 0 14px 40px rgba(59,130,246,0.4); }

    .inf-alert {
        padding: 14px 18px;
        border-radius: 10px;
        margin-bottom: 20px;
        font-size: 0.9rem;
    }
    .inf-alert-success { background: rgba(34,197,94,0.12); border: 1px solid rgba(34,197,94,0.35); color: #86efac; }
    .inf-alert-danger { background: rgba(239,68,68,0.12); border: 1px solid rgba(239,68,68,0.35); color: #fca5a5; }
    .inf-alert ul { margin: 0; padding-left: 18px; }
</style>
@endpush

@section('content')
<section class="inf-hero">
    <div class="inf-hero-bg">
        <img src="https://images.unsplash.com/photo-1492684223066-81342ee5ff30?w=1600&q=80&auto=format&fit=crop" alt="Vibrant celebration" loading="eager">
    </div>
    <div class="container">
        <span class="inf-hero-badge">✨ Partner Program</span>
        <h1>Turn your network into income</h1>
        <p>Become a {{ config('app.name', 'Khadija') }} Influencer and earn up to 30% commission by helping others create amazing events.</p>
        <a href="#apply" class="inf-btn-cta">Join the Program →</a>
        <div class="inf-hero-avatars">
            <div class="inf-hero-avatar"><img src="https://images.unsplash.com/photo-1580489944761-15a19d654956?w=150&q=80&auto=format&fit=crop&crop=faces" alt=""></div>
            <div class="inf-hero-avatar"><img src="https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?w=150&q=80&auto=format&fit=crop&crop=faces" alt=""></div>
            <div class="inf-hero-avatar"><img src="https://images.unsplash.com/photo-1531384441138-2736e62e0919?w=150&q=80&auto=format&fit=crop&crop=faces" alt=""></div>
            <div class="inf-hero-avatar"><img src="https://images.unsplash.com/photo-1573496359142-b8d87734a5a2?w=150&q=80&auto=format&fit=crop&crop=faces" alt=""></div>
            <div class="inf-hero-avatar"><img src="https://images.unsplash.com/photo-1519085360753-af0119f7cbe7?w=150&q=80&auto=format&fit=crop&crop=faces" alt=""></div>
        </div>
        <div><span class="inf-hero-stat"><strong>500+</strong> active influencers already earning</span></div>
    </div>
</section>

<!-- ─── SHOWCASE STRIP ──────────── -->
<section class="inf-showcase">
    <div class="container">
        <div class="inf-showcase-grid">
            <div class="inf-showcase-tile">
                <img src="https://images.unsplash.com/photo-1524863479829-916d8e77f114?w=700&q=80&auto=format&fit=crop" alt="Content creator filming" loading="lazy">
                <span class="inf-showcase-label">Content creators</span>
            </div>
            <div class="inf-showcase-tile">
                <img src="https://images.unsplash.com/photo-1556761175-b413da4baf72?w=700&q=80&auto=format&fit=crop" alt="Event influencer posing" loading="lazy">
                <span class="inf-showcase-label">Event promoters</span>
            </div>
            <div class="inf-showcase-tile">
                <img src="https://images.unsplash.com/photo-1611162617213-7d7a39e9b1d7?w=700&q=80&auto=format&fit=crop" alt="Social media lifestyle" loading="lazy">
                <span class="inf-showcase-label">Lifestyle creators</span>
            </div>
        </div>
    </div>
</section>

<section class="inf-section">
    <div class="container">
        <div class="inf-section-header">
            <h2>Why join us?</h2>
            <p>Everything you need to earn recurring income.</p>
        </div>
        <div class="inf-grid-4">
            <div class="inf-card"><div class="inf-card-icon">💰</div><h3>High Commissions</h3><p>Earn up to 30% on every referral</p></div>
            <div class="inf-card"><div class="inf-card-icon">🌐</div><h3>Growing Network</h3><p>Join 500+ active influencers</p></div>
            <div class="inf-card"><div class="inf-card-icon">🔄</div><h3>Recurring Income</h3><p>Earn on every booking, not just signups</p></div>
            <div class="inf-card"><div class="inf-card-icon">⭐</div><h3>Premium Support</h3><p>Dedicated team to help you grow</p></div>
        </div>
    </div>
</section>

<section class="inf-section inf-bg">
    <div class="container">
        <div class="inf-section-header">
            <h2>Commission Tiers</h2>
            <p>The more you refer, the more you earn.</p>
        </div>
        <div class="inf-grid-4">
            @foreach($tiers as $key => $tier)
            <div class="inf-tier">
                <h3>{{ $tier['label'] }}</h3>
                <div class="inf-tier-rate">{{ $tier['rate'] }}%</div>
                <p>{{ $tier['min_referrals'] }}+ referrals</p>
            </div>
            @endforeach
        </div>
    </div>
</section>

<section class="inf-section">
    <div class="container">
        <div class="inf-section-header">
            <h2>How it works</h2>
            <p>Start earning in three simple steps.</p>
        </div>
        <div class="inf-grid-3">
            <div class="inf-card"><div class="inf-step-num">1</div><h3>Share Your Link</h3><p>Get your unique referral link after approval</p></div>
            <div class="inf-card"><div class="inf-step-num">2</div><h3>People Book Services</h3><p>Your audience signs up and books events</p></div>
            <div class="inf-card"><div class="inf-step-num">3</div><h3>Earn Commission</h3><p>Get paid for every successful booking</p></div>
        </div>
    </div>
</section>

<section id="apply" class="inf-section inf-bg">
    <div class="container">
        <div class="inf-section-header">
            <h2>Apply to join</h2>
            <p>Fill in your details and our team will review your application.</p>
        </div>

        <div class="inf-form-wrap">
            @if(session('status'))
                <div class="inf-alert inf-alert-success">{{ session('status') }}</div>
            @endif

            @if($errors->any())
                <div class="inf-alert inf-alert-danger">
                    <ul>
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form action="{{ route('influencer.join.submit') }}" method="POST" class="inf-form-card">
                @csrf
                <div class="inf-form-group">
                    <label>Full Name</label>
                    <input type="text" name="full_name" value="{{ old('full_name', auth()->check() ? auth()->user()->name : '') }}" required class="inf-form-control">
                </div>
                <div class="inf-form-group">
                    <label>Email Address</label>
                    <input type="email" name="email" value="{{ old('email', auth()->check() ? auth()->user()->email : '') }}" required class="inf-form-control">
                </div>
                <div class="inf-form-group">
                    <label>Social Media Links</label>
                    <input type="text" name="social_media_links" value="{{ old('social_media_links') }}" placeholder="Instagram, YouTube, TikTok URLs (comma separated)" class="inf-form-control">
                </div>
                <div class="inf-form-group">
                    <label>Tell us about your audience</label>
                    <textarea name="audience_description" rows="4" class="inf-form-control" placeholder="Who are your followers? What content do you create?">{{ old('audience_description') }}</textarea>
                </div>
                <div class="inf-form-group">
                    <label>Monthly Reach</label>
                    <input type="number" name="monthly_reach" value="{{ old('monthly_reach') }}" min="0" placeholder="e.g. 50000" class="inf-form-control">
                </div>
                <button type="submit" class="inf-btn-submit">Submit Application</button>
            </form>
        </div>
    </div>
</section>
@endsection
