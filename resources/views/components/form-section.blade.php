{{--
    A numbered section heading.

    Sir Peter's recreated screens, 2026-09-10, number every section down the
    page — 1 Event Details, 2 Service Details, 3 Your Requirements — while ours
    numbered nothing on the emergency and direct forms and used a different
    heading style on each of the three. The wizard already numbers itself,
    because its sections are steps; these two are one page, so the number is
    what tells a client how much of the form is left.

    @props
      n         the section's position on the page
      title     what it asks for
      tag       optional right-hand label ("YOUR INPUT", "AUTO-DRAFTED")
      required  draws the asterisk, so "this one is not optional" reads the
                same on every form
--}}
@props([
    'n'        => null,
    'title'    => '',
    'tag'      => null,
    'required' => false,
])

@once
@push('styles')
<style>
    .fsec-h { display: flex; align-items: center; gap: 10px; margin-bottom: 14px; }
    .fsec-n { flex-shrink: 0; width: 24px; height: 24px; border-radius: 50%;
        background: var(--brand, #f97316); color: #fff; font-size: 12px; font-weight: 800;
        display: flex; align-items: center; justify-content: center; }
    .fsec-t { font-size: 14.5px; font-weight: 800; color: var(--text-primary, #111827); }
    .fsec-req { color: #dc2626; font-weight: 800; }
    .fsec-tag { margin-left: auto; font-size: 9.5px; font-weight: 800; letter-spacing: .3px;
        padding: 3px 9px; border-radius: 999px; color: #fff; background: var(--brand, #f97316); }
</style>
@endpush
@endonce

<div class="fsec-h">
    @if($n)<span class="fsec-n">{{ $n }}</span>@endif
    <span class="fsec-t">{{ $title }}{!! $slot !!}@if($required) <span class="fsec-req">*</span>@endif</span>
    @if($tag)<span class="fsec-tag">{{ $tag }}</span>@endif
</div>
