@props(['for'])
{{--
    Show or hide what is typed into a password box.

    Reported 25 October: "yeh eye ka icon daalo isme show password ke liye",
    with the login screen attached. The button was already there, on the login
    page, on register and on the influencer join form. It was invisible on all
    three, and measuring the live page said why: the <svg> inside it carried a
    viewBox and no width or height, and nothing in the stylesheet sized it, so
    it rendered at 0 by 0 and the button came out 8 pixels wide. The lock icon
    on the left of the same field sets 18px explicitly; this one set nothing.

    It was missing outright from five more password fields, including the
    password reset screen, which is the one screen somebody locked out of their
    account has to get right first time.

    So it is one component now. Its own size is on the element, not borrowed
    from a page's stylesheet, because that is the thing that failed.

    The toggle itself is bound once in the layout, on [data-eye]: see
    layouts/partials/_password_eye_script.
--}}
<button type="button"
        {{ $attributes->merge(['class' => 'pw-eye']) }}
        data-eye="{{ $for }}"
        aria-label="Show or hide password"
        aria-pressed="false"
        title="Show password">
    {{-- Two icons, one shown at a time, so the button says which state it is
         in rather than looking identical either way. --}}
    <svg class="pw-eye-on" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
        <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/>
    </svg>
    <svg class="pw-eye-off" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
        <path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"/>
        <line x1="1" y1="1" x2="23" y2="23"/>
    </svg>
</button>

{{--
    The style sits HERE, not in a pushed stack.

    First attempt pushed it to @stack('styles'), which is how the rest of the
    project does it. Sign-in and sign-up have no layout at all: they are
    standalone HTML documents with no stack to push to, so the CSS was dropped
    without a word. On screen that showed as both icons at once and the button
    falling out of the field, because none of the rules below had arrived.

    Rendered once per page by @once, and it needs nothing from the page.
--}}
@once
<style>
    .pw-eye { position: absolute; right: 11px; top: 50%; transform: translateY(-50%);
        display: inline-flex; align-items: center; justify-content: center;
        width: 30px; height: 30px; padding: 0; border: 0; border-radius: 8px;
        background: none; color: var(--text-muted, #9aa6b8); cursor: pointer; }
    .pw-eye:hover { color: var(--text-primary, #111827); background: rgba(0,0,0,.05); }
    /* Belt and braces: the whole bug was an unsized svg inheriting nothing. */
    .pw-eye svg { width: 18px; height: 18px; display: block; flex: none; }
    .pw-eye .pw-eye-off { display: none; }
    .pw-eye[aria-pressed="true"] .pw-eye-on { display: none; }
    .pw-eye[aria-pressed="true"] .pw-eye-off { display: block; }
    /* The field must be a positioned ancestor or the button lands outside it.
       Declared here rather than assumed of every page that uses this. */
    .pw-eye { pointer-events: auto; }
</style>
@endonce
