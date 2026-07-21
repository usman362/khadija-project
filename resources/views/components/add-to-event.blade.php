@props([
    'toolKey',
    'toolName',
    'eventId' => null,
])

@php
    $user = auth()->user();
    $events = $user
        ? \App\Models\Event::where(fn ($q) => $q->where('client_id', $user->id)->orWhere('created_by', $user->id))
            ->latest()->take(25)->get(['id', 'title'])
        : collect();
    $level = $user ? \App\Domain\AiFeatures\AiAccess::level($user, $toolKey) : 'none';
    $auto  = in_array($level, ['semi', 'maximum'], true);
@endphp

@if($user && $events->isNotEmpty())
@once
@push('styles')
<style>
    .ate { border: 1.5px solid var(--accent-orange, #f97316); background: rgba(249,115,22,.05); border-radius: 14px; padding: 15px 16px; margin: 18px 0; }
    .ate-head { display: flex; align-items: center; gap: 8px; font-size: 13.5px; font-weight: 800; color: var(--text-primary, #111827); margin-bottom: 4px; }
    .ate-mode { margin-left: auto; font-size: 10px; font-weight: 800; letter-spacing: .4px; text-transform: uppercase; color: #c2410c; background: rgba(249,115,22,.14); border-radius: 999px; padding: 2px 9px; }
    .ate-note { font-size: 11.5px; color: var(--text-muted, #6b7280); margin-bottom: 11px; line-height: 1.4; }
    .ate-form { display: flex; gap: 9px; flex-wrap: wrap; }
    .ate-select { flex: 1; min-width: 180px; border: 1px solid var(--border-color, #e5e7eb); border-radius: 10px; padding: 9px 11px; font-size: 13px; font-family: inherit; color: var(--text-primary, #111827); background: var(--bg-card, #fff); }
    .ate-btn { border: none; border-radius: 10px; padding: 9px 18px; font-size: 13px; font-weight: 800; color: #fff; background: linear-gradient(135deg, #fb923c, #ea580c); cursor: pointer; white-space: nowrap; }
    .ate-btn:disabled { opacity: .5; cursor: not-allowed; }
</style>
@endpush
@endonce

<div class="ate" data-ate>
    <div class="ate-head">📌 Add this to your event <span class="ate-mode">{{ $auto ? 'Auto-attach' : 'Manual' }}</span></div>
    <div class="ate-note">{{ $auto
        ? 'Your plan supports one-click auto-attach — save this AI result straight to your event.'
        : 'Free plan: generate a result, then save it to your event below.' }}</div>
    <form method="POST" action="{{ route('client.ai-artifacts.store') }}" class="ate-form">
        @csrf
        <input type="hidden" name="tool_key" value="{{ $toolKey }}">
        <input type="hidden" name="tool_name" value="{{ $toolName }}">
        <input type="hidden" name="title" id="ateTitle" value="">
        <input type="hidden" name="payload" id="atePayload" value="">
        <select name="event_id" class="ate-select" aria-label="Choose event">
            @foreach($events as $e)
                <option value="{{ $e->id }}" @selected((string) $eventId === (string) $e->id)>{{ $e->title }}</option>
            @endforeach
        </select>
        <button type="submit" class="ate-btn" id="ateBtn" disabled>Add to event</button>
    </form>
</div>

<script>
    // Tools call window.aiAttachSet(title, payloadObject) once a result is ready.
    window.aiAttachSet = function (title, payload) {
        var t = document.getElementById('ateTitle'),
            p = document.getElementById('atePayload'),
            b = document.getElementById('ateBtn');
        if (!t) return;
        t.value = title || @json($toolName . ' result');
        p.value = payload ? JSON.stringify(payload) : '';
        if (b) b.disabled = false;
    };
</script>
@endif
