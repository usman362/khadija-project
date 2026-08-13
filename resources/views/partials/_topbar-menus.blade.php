{{-- Topbar bell + account dropdowns.

     $portal — 'client' or 'professional'; picks the route names.
     $trigger — 'avatar' (round letter, client) or 'chip' (avatar + name, pro).

     Both used to be plain links straight to the profile page, which read as
     menus and behaved as redirects. --}}
@php
    $portal      = $portal ?? 'client';
    $trigger     = $trigger ?? 'avatar';
    $me          = auth()->user();
    /*
     * Checklist row 145 — one avatar, one generator.
     *
     * This used to be a single letter drawn in CSS while the profile page
     * rendered avatar_url: two initials, a per-user hue, and any photo the
     * person had actually uploaded. Same account, two different avatars on
     * one page load. avatar_url is the real generator, so it wins.
     */
    $avatarUrl   = $me?->avatar_url;
    // Real rows only — no placeholder count. An empty bell means nothing new.
    $notes       = $me ? $me->unreadNotifications()->latest()->limit(6)->get() : collect();
    $noteCount   = $notes->count();
    $profileRoute = $portal . '.profile.index';
    $notifRoute   = $portal . '.notifications.index';
@endphp

@include('partials._topbar-menu-styles')
@include('partials._row-menu-script')

{{-- Notifications --}}
<div class="tbm" data-row-menu>
    <button class="{{ $portal === 'client' ? 'cl-nav-btn' : 'pro-icon-btn' }}" type="button"
            aria-haspopup="true" aria-expanded="false" title="Notifications">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/></svg>
        @if($noteCount > 0)
            <span class="{{ $portal === 'client' ? 'cl-nav-btn-count' : 'pro-icon-badge red' }}">{{ $noteCount > 9 ? '9+' : $noteCount }}</span>
        @endif
    </button>
    <div class="tbm-pop" data-row-menu-pop>
        <div class="tbm-head">Notifications</div>
        @forelse($notes as $n)
            @php
                $d   = $n->data ?? [];
                $url = $d['url'] ?? null;
                $msg = $d['message'] ?? 'You have a new notification.';
            @endphp
            <a class="tbm-item" href="{{ $url ?: route($notifRoute) }}">
                <span class="tbm-note unread">{{ \Illuminate\Support\Str::limit($msg, 110) }}</span>
                <span class="sub">{{ $n->created_at?->humanAgo() }}</span>
            </a>
        @empty
            <div class="tbm-empty">Nothing new right now.</div>
        @endforelse
        <div class="tbm-foot">
            <a class="tbm-item" href="{{ route($notifRoute) }}">Notification settings</a>
        </div>
    </div>
</div>

{{-- Account --}}
<div class="tbm" data-row-menu>
    @if($trigger === 'chip')
        <button class="pro-avatar-chip" type="button" aria-haspopup="true" aria-expanded="false" title="Account">
            <img class="pro-avatar-img" src="{{ $avatarUrl }}" alt="">
            <span class="pro-avatar-meta"><b>{{ $me?->name ?? 'Your account' }}</b><span>PRO</span></span>
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="6 9 12 15 18 9"/></svg>
        </button>
    @else
        <button class="cl-navbar-avatar" type="button" aria-haspopup="true" aria-expanded="false" title="{{ $me?->name }}" aria-label="Your account"><img src="{{ $avatarUrl }}" alt="" style="width:100%;height:100%;border-radius:inherit;object-fit:cover;display:block;"></button>
    @endif
    <div class="tbm-pop" data-row-menu-pop>
        <div class="tbm-user">
            <b>{{ $me?->name }}</b>
            <span>{{ $me?->email }}</span>
        </div>
        <div class="tbm-sep"></div>
        <a class="tbm-item" href="{{ route($profileRoute) }}">My profile</a>
        <a class="tbm-item" href="{{ route($notifRoute) }}">Notification settings</a>
        <div class="tbm-sep"></div>
        <form action="{{ route('logout') }}" method="POST">@csrf
            <button type="submit" class="tbm-item danger">Log out</button>
        </form>
    </div>
</div>
