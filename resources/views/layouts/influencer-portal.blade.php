@php
    use Illuminate\Support\Facades\Route as RouteFacade;
    /** Resolve a route name to a URL, or '#' if it doesn't exist yet (pages built in phases). */
    $ip_url = fn (?string $name, array $p = []) => $name && RouteFacade::has($name) ? route($name, $p) : '#';

    // Sidebar nav. Each item: [label, icon-key, route-name|null, [children]].
    $ip_nav = [
        ['Dashboard', 'home', 'influencer.dashboard', []],
        ['Invite & Earn More', 'gift', null, [
            ['Invite Tools', 'influencer.invite.tools'],
            ['Earn', 'influencer.invite.earn'],
            ['Promote', 'influencer.invite.promote'],
            ['Onboarding', 'influencer.invite.onboarding'],
            ['Become an Influencer', 'influencer.invite.become'],
            ['Success Stories', 'influencer.invite.stories'],
            ['FAQs', 'influencer.invite.faqs'],
        ]],
        ['Referral Center', 'link', 'influencer.referral-center', []],
        ['Marketing Center', 'mega', 'influencer.marketing', []],
        ['Leaderboards & Challenges', 'trophy', 'influencer.leaderboards', []],
        ['Commissions', 'percent', 'influencer.commissions', []],
        ['Payouts', 'wallet', 'influencer.dashboard.payouts', []],
        ['Analytics', 'chart', null, [
            ['Performance', 'influencer.analytics.performance'],
            ['Campaign Performance', 'influencer.analytics.campaigns'],
            ['Audience Insights', 'influencer.analytics.audience'],
            ['Content Metrics', 'influencer.analytics.content'],
            ['Reports', 'influencer.analytics.reports'],
            ['Export Data', 'influencer.analytics.export'],
        ]],
        ['Resources', 'book', null, [
            ['Getting Started', 'influencer.resources.getting-started'],
            ['Resource Library', 'influencer.resources.library'],
            ['Academy', 'influencer.resources.academy'],
            ['Tutorials', 'influencer.resources.tutorials'],
            ['Featured Articles', 'influencer.resources.articles'],
        ]],
        ['Badges & Tiers', 'badge', null, [
            ['Current Tier', 'influencer.badges.current'],
            ['Progress', 'influencer.badges.progress'],
            ['All Badges', 'influencer.badges.all'],
            ['Main Tiers', 'influencer.badges.tiers'],
            ['Tier Benefits', 'influencer.badges.benefits'],
        ]],
    ];

    $ip_icons = [
        'home'   => '<path d="M3 9.5 12 3l9 6.5V20a1 1 0 0 1-1 1h-5v-7H9v7H4a1 1 0 0 1-1-1z"/>',
        'gift'   => '<rect x="3" y="8" width="18" height="4" rx="1"/><path d="M12 8v13M5 12v8a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1v-8"/><path d="M12 8S10.5 3 8 3a2.5 2.5 0 0 0 0 5zM12 8s1.5-5 4-5a2.5 2.5 0 0 1 0 5z"/>',
        'link'   => '<path d="M10 13a5 5 0 0 0 7.5.5l3-3a5 5 0 0 0-7-7l-1.7 1.7"/><path d="M14 11a5 5 0 0 0-7.5-.5l-3 3a5 5 0 0 0 7 7l1.7-1.7"/>',
        'mega'   => '<path d="M3 11l16-5v12L3 13v-2z"/><path d="M11 18.5a3 3 0 0 1-5.5-1.5"/>',
        'trophy' => '<path d="M8 21h8M12 17v4M7 4h10v5a5 5 0 0 1-10 0z"/><path d="M5 9a2 2 0 0 1-2-2V5h4M19 9a2 2 0 0 0 2-2V5h-4"/>',
        'percent'=> '<line x1="19" y1="5" x2="5" y2="19"/><circle cx="6.5" cy="6.5" r="2.5"/><circle cx="17.5" cy="17.5" r="2.5"/>',
        'wallet' => '<path d="M21 12V7H5a2 2 0 0 1 0-4h14v4"/><path d="M3 5v14a2 2 0 0 0 2 2h16v-5"/><path d="M18 12a2 2 0 0 0 0 4h4v-4z"/>',
        'chart'  => '<line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/>',
        'book'   => '<path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"/>',
        'badge'  => '<polygon points="12 2 15 9 22 9.3 16.5 14 18.5 21 12 17 5.5 21 7.5 14 2 9.3 9 9"/>',
    ];

    $ip_user = auth()->user();
    $ip_inf  = $influencer ?? ($ip_user?->influencer ?? null);
    $ip_tier = $ip_inf?->commission_tier?->label() ?? 'Influencer';
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Influencer') — GigResource</title>
    <link rel="icon" type="image/png" href="{{ asset('gigresource-logos/gigresource-icon.png') }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        *,*::before,*::after { box-sizing: border-box; margin: 0; padding: 0; }
        :root {
            /* Influencer brand accent = GREEN (per Peter). Var names kept as
               --orange* to avoid churn across the many views; values are green. */
            --orange: #16a34a; --orange-dark: #15803d; --orange-soft: #ecfdf3;
            --green: #16a34a; --green-dark: #15803d; --green-soft: #ecfdf3;
            --blue: #2563eb; --blue-soft: #eaf1ff;
            --ink: #0f1b35; --text: #3b4760; --muted: #7a879c; --line: #eef1f6;
            --bg: #f6f8fc; --card: #ffffff;
            --ff: 'Plus Jakarta Sans', system-ui, sans-serif; --ff-body: 'Inter', system-ui, sans-serif;
            --sb: 256px; --top: 68px; --radius: 16px; --shadow: 0 1px 3px rgba(15,27,53,.04), 0 8px 24px rgba(15,27,53,.04);
        }
        body { font-family: var(--ff-body); color: var(--text); background: var(--bg); }
        a { text-decoration: none; color: inherit; }
        ::-webkit-scrollbar { width: 8px; height: 8px; } ::-webkit-scrollbar-thumb { background: #d7deea; border-radius: 8px; }

        /* ── Sidebar ── */
        .ipx-sb {
            position: fixed; top: 0; left: 0; bottom: 0; width: var(--sb); background: var(--card);
            border-right: 1px solid var(--line); display: flex; flex-direction: column; z-index: 50;
            transition: transform .25s ease;
        }
        .ipx-brand { display: flex; align-items: center; padding: 18px 20px; flex-shrink: 0; }
        .ipx-brand img { height: 34px; }
        .ipx-nav { flex: 1; overflow-y: auto; padding: 6px 12px 12px; }
        .ipx-link, .ipx-parent {
            display: flex; align-items: center; gap: 12px; width: 100%;
            padding: 10px 12px; border-radius: 11px; font-family: var(--ff); font-size: 14px; font-weight: 600;
            color: var(--text); cursor: pointer; border: none; background: none; text-align: left; transition: background .12s, color .12s;
        }
        .ipx-link:hover, .ipx-parent:hover { background: #f5f7fb; color: var(--ink); }
        .ipx-link.active { background: var(--orange-soft); color: var(--orange-dark); }
        .ipx-link svg, .ipx-parent svg.ic { width: 19px; height: 19px; flex-shrink: 0; }
        .ipx-parent .chev { margin-left: auto; width: 15px; height: 15px; transition: transform .2s; color: var(--muted); }
        .ipx-group.open .ipx-parent { color: var(--orange-dark); }
        .ipx-group.open .ipx-parent .chev { transform: rotate(180deg); }
        .ipx-sub { display: none; padding: 2px 0 4px 30px; }
        .ipx-group.open .ipx-sub { display: block; }
        .ipx-sub a {
            display: flex; align-items: center; gap: 9px; padding: 8px 10px; border-radius: 9px;
            font-size: 13px; font-weight: 500; color: var(--muted);
        }
        .ipx-sub a::before { content: ''; width: 5px; height: 5px; border-radius: 50%; background: #d7deea; flex-shrink: 0; }
        .ipx-sub a:hover { background: #f5f7fb; color: var(--ink); }
        .ipx-sub a.active { color: var(--orange-dark); font-weight: 600; }
        .ipx-sub a.active::before { background: var(--orange); }

        .ipx-help {
            margin: 10px 14px 16px; background: var(--orange-soft); border: 1px solid #c9ecd4;
            border-radius: 14px; padding: 16px; flex-shrink: 0;
        }
        .ipx-help h4 { font-family: var(--ff); font-size: 14px; font-weight: 700; color: var(--ink); margin-bottom: 10px; }
        .ipx-help a { display: flex; align-items: center; gap: 8px; font-size: 13px; color: var(--text); padding: 4px 0; }
        .ipx-help a:hover { color: var(--orange-dark); }
        .ipx-help svg { width: 15px; height: 15px; color: var(--muted); }
        .ipx-help-cta {
            display: flex; align-items: center; justify-content: center; gap: 7px; margin-top: 10px;
            padding: 9px; border: 1.5px solid var(--orange); border-radius: 10px; color: var(--orange-dark);
            font-family: var(--ff); font-weight: 700; font-size: 12.5px;
        }
        .ipx-help-cta:hover { background: var(--orange); color: #fff; }

        /* ── Top bar ── */
        .ipx-top {
            position: fixed; top: 0; left: var(--sb); right: 0; height: var(--top); background: var(--card);
            border-bottom: 1px solid var(--line); display: flex; align-items: center; gap: 16px;
            padding: 0 24px; z-index: 40;
        }
        .ipx-burger { display: none; background: none; border: none; cursor: pointer; color: var(--ink); }
        .ipx-search { flex: 1; max-width: 460px; position: relative; }
        .ipx-search svg { position: absolute; left: 13px; top: 50%; transform: translateY(-50%); width: 17px; height: 17px; color: var(--muted); }
        .ipx-search input {
            width: 100%; padding: 9px 14px 9px 38px; border: 1px solid var(--line); border-radius: 10px;
            background: var(--bg); font-size: 13.5px; color: var(--ink); font-family: var(--ff-body);
        }
        .ipx-search input:focus { outline: none; border-color: var(--orange); background: #fff; }
        .ipx-top-right { margin-left: auto; display: flex; align-items: center; gap: 14px; }
        .ipx-create {
            display: inline-flex; align-items: center; gap: 7px; padding: 9px 16px; background: var(--orange);
            color: #fff; border-radius: 10px; font-family: var(--ff); font-weight: 700; font-size: 13.5px;
        }
        .ipx-create:hover { background: var(--orange-dark); }
        .ipx-iconbtn { position: relative; width: 38px; height: 38px; border-radius: 10px; border: 1px solid var(--line); background: var(--card); display: flex; align-items: center; justify-content: center; color: var(--muted); cursor: pointer; }
        .ipx-iconbtn:hover { color: var(--ink); border-color: #d7deea; }
        .ipx-iconbtn svg { width: 18px; height: 18px; }
        .ipx-dot { position: absolute; top: 7px; right: 8px; width: 7px; height: 7px; border-radius: 50%; background: var(--orange); border: 2px solid #fff; }
        .ipx-profile { display: flex; align-items: center; gap: 10px; padding-left: 6px; }
        .ipx-avatar { width: 38px; height: 38px; border-radius: 50%; object-fit: cover; background: var(--blue-soft); }
        .ipx-profile-meta { line-height: 1.2; }
        .ipx-profile-meta b { display: block; font-family: var(--ff); font-size: 13.5px; font-weight: 700; color: var(--ink); }
        .ipx-profile-meta span { font-size: 11.5px; color: var(--muted); }

        /* ── Content ── */
        .ipx-main { margin-left: var(--sb); padding-top: var(--top); min-height: 100vh; }
        .ipx-content { padding: 26px 28px 40px; }
        .ipx-breadcrumb { font-size: 13px; color: var(--muted); margin-bottom: 6px; }
        .ipx-breadcrumb a:hover { color: var(--orange-dark); }
        .ipx-breadcrumb .sep { margin: 0 7px; }

        /* shared card helpers used by pages */
        .ipx-card { background: var(--card); border: 1px solid var(--line); border-radius: var(--radius); box-shadow: var(--shadow); }

        /* overlay for mobile */
        .ipx-overlay { display: none; position: fixed; inset: 0; background: rgba(15,27,53,.4); z-index: 45; }

        @media (max-width: 1000px) {
            .ipx-sb { transform: translateX(-100%); }
            body.ipx-open .ipx-sb { transform: translateX(0); }
            body.ipx-open .ipx-overlay { display: block; }
            .ipx-top { left: 0; }
            .ipx-main { margin-left: 0; }
            .ipx-burger { display: inline-flex; }
            .ipx-create span { display: none; }
        }
        @media (max-width: 640px) { .ipx-search { display: none; } .ipx-profile-meta { display: none; } .ipx-content { padding: 18px 16px 32px; } }
    </style>
    @stack('styles')
</head>
<body>
<div class="ipx-overlay" onclick="document.body.classList.remove('ipx-open')"></div>

{{-- ── Sidebar ── --}}
<aside class="ipx-sb">
    <div class="ipx-brand">
        <a href="{{ route('influencer.dashboard') }}"><img src="{{ asset('gigresource-logos/gigresource-logo-light.png') }}" alt="GigResource"></a>
    </div>
    <nav class="ipx-nav">
        @foreach($ip_nav as [$label, $icon, $route, $children])
            @if(empty($children))
                <a href="{{ $ip_url($route) }}" class="ipx-link {{ $route && request()->routeIs($route) ? 'active' : '' }}">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">{!! $ip_icons[$icon] !!}</svg>
                    {{ $label }}
                </a>
            @else
                @php($childRoutes = collect($children)->pluck(1)->filter()->all())
                @php($groupOpen = collect($childRoutes)->contains(fn($r) => RouteFacade::has($r) && request()->routeIs($r)))
                <div class="ipx-group {{ $groupOpen ? 'open' : '' }}">
                    <button type="button" class="ipx-parent" onclick="this.closest('.ipx-group').classList.toggle('open')">
                        <svg class="ic" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">{!! $ip_icons[$icon] !!}</svg>
                        {{ $label }}
                        <svg class="chev" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 12 15 18 9"/></svg>
                    </button>
                    <div class="ipx-sub">
                        @foreach($children as [$clabel, $croute])
                            <a href="{{ $ip_url($croute) }}" class="{{ $croute && RouteFacade::has($croute) && request()->routeIs($croute) ? 'active' : '' }}">{{ $clabel }}</a>
                        @endforeach
                    </div>
                </div>
            @endif
        @endforeach
    </nav>

    <div class="ipx-help">
        <h4>Need Help?</h4>
        <a href="{{ route('public.faq') }}"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M9.1 9a3 3 0 0 1 5.8 1c0 2-3 3-3 3"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg> Help Center</a>
        <a href="#"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg> Contact Support</a>
        <a href="#"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M8 12h.01M12 12h.01M16 12h.01M21 12a9 9 0 1 1-4.5-7.8L21 3v9z"/></svg> Live Chat</a>
        <a href="#" class="ipx-help-cta">Go to Support Center <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg></a>
    </div>
</aside>

{{-- ── Top bar ── --}}
<header class="ipx-top">
    <button type="button" class="ipx-burger" onclick="document.body.classList.toggle('ipx-open')">
        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="18" x2="21" y2="18"/></svg>
    </button>
    <div class="ipx-search">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
        <input type="text" placeholder="Search anything...">
    </div>
    <div class="ipx-top-right">
        <a href="{{ $ip_url('influencer.invite.promote') }}" class="ipx-create"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.6"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg><span>Create</span></a>
        <button class="ipx-iconbtn"><span class="ipx-dot"></span><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.7 21a2 2 0 0 1-3.4 0"/></svg></button>
        <button class="ipx-iconbtn"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="4" width="20" height="16" rx="2"/><path d="M22 7l-10 7L2 7"/></svg></button>
        <div class="ipx-profile">
            <img src="{{ $ip_user?->avatar_url ?? asset('gigresource-logos/gigresource-icon.png') }}" alt="" class="ipx-avatar">
            <div class="ipx-profile-meta">
                <b>{{ $ip_user?->name ?? 'Influencer' }}</b>
                <span>{{ $ip_tier }}</span>
            </div>
        </div>
    </div>
</header>

{{-- ── Main ── --}}
<main class="ipx-main">
    <div class="ipx-content">
        @include('partials._breadcrumb')

        @yield('content')
    </div>
</main>

@stack('scripts')
</body>
</html>
