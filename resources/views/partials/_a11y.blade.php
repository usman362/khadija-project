{{--
    Site-wide accessibility (WCAG 2.1 AA) baseline.

    Applies four hardening passes to every page that includes this partial:

      1. Visible focus rings on every interactive element (keyboard nav)
      2. 44 × 44 px minimum hit target on touch screens (WCAG 2.5.5)
      3. Honours prefers-reduced-motion (vestibular safety, WCAG 2.3.3)
      4. Adds an .sr-only utility so partials can label icon-only buttons

    Layouts already include this once globally — don't re-include per page.
--}}
<style>
    /* ── Focus ring (WCAG 2.4.7 Focus Visible) ───────────────────
       Apply to every focusable element. Uses :focus-visible so mouse
       clicks don't show the ring — only keyboard / programmatic focus.
       Falls back to :focus for older browsers. */
    :focus { outline: none; }
    :focus-visible {
        outline: 3px solid #6366f1 !important;
        outline-offset: 2px !important;
        border-radius: 4px;
    }
    /* Dark-on-dark exception — buttons on coloured backgrounds get a
       contrasting halo so the ring is visible. */
    .btn-primary:focus-visible,
    .hero-finder button:focus-visible {
        outline-color: #fff !important;
        box-shadow: 0 0 0 4px rgba(99, 102, 241, 0.6) !important;
    }

    /* ── Touch targets (WCAG 2.5.5) ──────────────────────────────
       Bump small icon buttons up to 44 × 44 px on touch screens.
       Desktop pointer users keep the tighter visual density. */
    @media (pointer: coarse) {
        button, a[role="button"], .icon-btn, .pp-icon-btn {
            min-width: 44px;
            min-height: 44px;
        }
    }

    /* ── Reduced motion (WCAG 2.3.3) ─────────────────────────────
       Drop or shorten every animation/transition for users who've
       requested less motion (vestibular disorders, motion sickness). */
    @media (prefers-reduced-motion: reduce) {
        *, *::before, *::after {
            animation-duration: 0.01ms !important;
            animation-iteration-count: 1 !important;
            transition-duration: 0.01ms !important;
            scroll-behavior: auto !important;
        }
    }

    /* ── Screen-reader only utility ──────────────────────────────
       Visually hides text but keeps it readable to assistive tech.
       Use for icon-only button labels, table captions, form hints. */
    .sr-only {
        position: absolute !important;
        width: 1px !important;
        height: 1px !important;
        padding: 0 !important;
        margin: -1px !important;
        overflow: hidden !important;
        clip: rect(0, 0, 0, 0) !important;
        white-space: nowrap !important;
        border: 0 !important;
    }
    /* Visible-on-focus variant — useful for "skip to content" links. */
    .sr-only-focusable:focus,
    .sr-only-focusable:focus-visible {
        position: static !important;
        width: auto !important;
        height: auto !important;
        padding: inherit !important;
        margin: inherit !important;
        overflow: visible !important;
        clip: auto !important;
        white-space: normal !important;
    }

    /* ── Link contrast hardening ─────────────────────────────────
       Plain underlined links inside body content need 4.5:1 contrast
       (WCAG 1.4.3). Scope this to article/prose containers only —
       applying it sitewide bricked the navbar Pricing link which
       lacks a class attribute. */
    article a:not([class]):not(:hover),
    .prose a:not([class]):not(:hover),
    .blog-content a:not([class]):not(:hover) { color: #4338ca; }

    /* ── Focus inside dialogs ────────────────────────────────────
       Trap-style modals get a heavier ring so screen-reader users
       can tell where focus jumped to after the dialog opened. */
    [role="dialog"] :focus-visible,
    .modal :focus-visible {
        outline-width: 4px !important;
    }
</style>
<script>
    /* aria-live announcer — page-level toasts can call window.a11yAnnounce("Saved")
       to speak a message politely without stealing focus. Falls back to a
       no-op if assistive tech isn't running. */
    (function () {
        if (window.a11yAnnounce) return;
        var live = document.createElement('div');
        live.setAttribute('aria-live', 'polite');
        live.setAttribute('aria-atomic', 'true');
        live.className = 'sr-only';
        document.addEventListener('DOMContentLoaded', function () {
            document.body.appendChild(live);
        });
        window.a11yAnnounce = function (msg) {
            if (!live.isConnected) document.body.appendChild(live);
            live.textContent = '';
            // Slight delay so screen readers re-announce identical strings.
            setTimeout(function () { live.textContent = msg; }, 50);
        };
    })();
</script>
