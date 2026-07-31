{{-- Messages icon for the topbar, with a real unread count.

     Shared so the two portals cannot drift: it was in the professional topbar
     only, and sat before the theme toggle instead of after it.

     $portal — 'client' or 'professional', to pick the right route. --}}
@php
    $__portal  = $portal ?? 'client';
    $__unread  = auth()->check()
        ? \App\Models\Message::where('recipient_id', auth()->id())
            ->whereDoesntHave('reads', fn ($q) => $q->where('user_id', auth()->id()))
            ->count()
        : 0;
    $__route = $__portal === 'professional' ? 'professional.chat.index' : 'client.chat.index';
@endphp

<a href="{{ route($__route) }}" class="tb-icon-btn" title="Messages"
   aria-label="Messages{{ $__unread > 0 ? ' — ' . $__unread . ' unread' : '' }}">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
    @if($__unread > 0)
        <span class="tb-icon-badge">{{ $__unread > 9 ? '9+' : $__unread }}</span>
    @endif
</a>

<style>
    .tb-icon-btn { position: relative; width: 38px; height: 38px; border-radius: 10px; border: none; background: transparent; color: var(--text-secondary); display: inline-flex; align-items: center; justify-content: center; text-decoration: none; flex-shrink: 0; }
    .tb-icon-btn:hover { background: var(--bg-card-hover); }
    .tb-icon-btn svg { width: 18px; height: 18px; }
    .tb-icon-badge { position: absolute; top: 4px; right: 4px; min-width: 16px; height: 16px; padding: 0 4px; border-radius: 999px; background: #ef4444; color: #fff; font-size: 9.5px; font-weight: 800; display: flex; align-items: center; justify-content: center; line-height: 1; border: 2px solid var(--bg-primary); }
</style>
