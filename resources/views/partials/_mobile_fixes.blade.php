{{--
    Universal mobile-friendly fixes — drop into any dashboard layout.
    Adds:
      • Sidebar backdrop overlay when open on mobile
      • Body scroll lock when sidebar is open
      • Click-outside-to-close
      • Horizontal-scroll wrapper for any table on mobile
      • Better navbar wrap behavior on mobile
      • Form-grid auto-stack at <640px
      • Modal mobile padding fixes
      • Page-content side padding on mobile

    The sidebar selectors are intentionally permissive so this works
    across the client / professional / admin layouts that all use
    slightly different class names (.cl-sidebar / .pf-sidebar / etc.)
--}}

<style>
    /* ─────────────────────────────────────────────
       MOBILE-FIRST DASHBOARD POLISH
       Targets all known dashboard sidebar variants.
       ───────────────────────────────────────────── */

    /* Backdrop that appears when the sidebar slides in on mobile.
       Click closes the sidebar. body has overflow:hidden while open. */
    .mobile-sidebar-backdrop {
        position: fixed;
        inset: 0;
        background: rgba(5, 8, 15, 0.65);
        backdrop-filter: blur(4px);
        -webkit-backdrop-filter: blur(4px);
        z-index: 99;            /* sits between content (low) and sidebar (high) */
        opacity: 0;
        pointer-events: none;
        transition: opacity 0.25s ease;
    }
    .mobile-sidebar-backdrop.is-visible {
        opacity: 1;
        pointer-events: auto;
    }

    @media (max-width: 768px) {
        /* Lock body scroll when any sidebar is open */
        body.mobile-sidebar-open {
            overflow: hidden;
        }

        /* Lift sidebars above the backdrop */
        .cl-sidebar.open,
        aside.cl-sidebar.open,
        .pf-sidebar.open,
        .sidebar.open,
        #sidebar.open {
            z-index: 100 !important;
            box-shadow: 4px 0 30px rgba(0, 0, 0, 0.4);
        }

        /* Tighten common navbar / page header padding so it doesn't
           push content off-screen on small phones */
        .cl-navbar,
        .pf-navbar {
            padding-left: 12px !important;
            padding-right: 12px !important;
            gap: 6px !important;
        }
        .cl-navbar-right > *,
        .pf-navbar-right > * {
            margin-left: 0 !important;
        }

        /* Page title on mobile — truncate gracefully instead of wrapping */
        .cl-page-title,
        .pf-page-title,
        .page-title {
            font-size: 16px !important;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            max-width: 60vw;
        }

        /* Force any 2/3/4 column dashboard grids to stack on mobile */
        .cl-grid-2, .cl-grid-3, .cl-grid-4,
        .pf-form-grid, .pf-grid,
        .row-2, .row-3, .row-4,
        .grid-cols-2, .grid-cols-3, .grid-cols-4 {
            grid-template-columns: 1fr !important;
        }

        /* Page-content side padding on small phones */
        .cl-content,
        .pf-content,
        .dashboard-content,
        main.py-4 > .container,
        .cl-main > main {
            padding-left: 14px !important;
            padding-right: 14px !important;
        }

        /* Cards: tighten padding so content breathes on tiny screens */
        .pf-card,
        .cl-card,
        .card {
            padding: 18px !important;
        }

        /* Hide admin-style .breadcrumb crumbs that often overflow */
        .breadcrumb { font-size: 11px !important; }

        /* Tables: wrap in horizontal scroller via wrapper class.
           Pages can opt-in by wrapping their <table> in
           .table-responsive-mobile, but the global rule below also
           softens any naked <table> that overflows. */
        .table-responsive-mobile,
        .table-wrapper {
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
            margin: 0 -14px;          /* bleed into the side gutters */
            padding: 0 14px;
        }
        .table-responsive-mobile table,
        .table-wrapper table {
            min-width: 540px;          /* keeps columns from collapsing into mush */
        }

        /* Modals: pin to viewport edges on mobile so users can read content */
        .modal-dialog,
        .cl-modal,
        .pf-modal,
        .modal-content {
            margin: 12px !important;
            max-height: calc(100vh - 24px);
            overflow-y: auto;
        }

        /* Buttons in clusters: full-width on mobile so they're tap-friendly */
        .btn-row,
        .pf-actions,
        .cl-actions {
            flex-direction: column !important;
            align-items: stretch !important;
        }
        .btn-row > .btn,
        .pf-actions > .pf-btn,
        .cl-actions > .cl-btn {
            width: 100%;
        }

        /* Form inputs: ensure they never blow out of card width */
        input, select, textarea {
            max-width: 100%;
        }

        /* Avatar / image clusters that often misalign */
        .pf-avatar-card,
        .cl-avatar-card { padding: 18px 16px !important; }
        .pf-avatar-img,
        .cl-avatar-img { width: 96px !important; height: 96px !important; }

        /* Hide the right-side dashboard widgets that don't fit on mobile */
        .hide-on-mobile { display: none !important; }
    }

    /* ─── EXTRA-SMALL PHONES (≤ 420px) ───────────── */
    @media (max-width: 420px) {
        .cl-page-title,
        .pf-page-title,
        .page-title {
            max-width: 50vw;
            font-size: 15px !important;
        }

        /* Even tighter card padding on tiny phones */
        .pf-card,
        .cl-card,
        .card { padding: 14px !important; }

        /* Brand text in sidebar — hide subtitle, only show logo */
        .cl-sidebar-brand .brand-sub,
        .pf-sidebar-brand .brand-sub { display: none; }
    }

    /* ─── PREVENT HORIZONTAL SCROLL anywhere ─── */
    @media (max-width: 768px) {
        html, body {
            overflow-x: hidden;
            max-width: 100vw;
        }
        .container, .container-fluid {
            max-width: 100vw;
            padding-left: 14px;
            padding-right: 14px;
        }
    }
</style>

<script>
/* ─── Sidebar backdrop + body scroll lock ──────────────────────
   Watches the open class on any known sidebar selector and:
     1. Inserts a .mobile-sidebar-backdrop element on demand
     2. Locks body scroll while sidebar is open
     3. Adds click-outside-to-close
   Idempotent — safe to include in multiple layouts.
*/
(function () {
    'use strict';
    if (window.__mobileSidebarFix) return;
    window.__mobileSidebarFix = true;

    function getActiveSidebar() {
        return document.querySelector(
            '.cl-sidebar.open, .pf-sidebar.open, #sidebar.open, aside.sidebar.open'
        );
    }

    function ensureBackdrop() {
        var el = document.querySelector('.mobile-sidebar-backdrop');
        if (!el) {
            el = document.createElement('div');
            el.className = 'mobile-sidebar-backdrop';
            el.addEventListener('click', closeAll);
            document.body.appendChild(el);
        }
        return el;
    }

    function closeAll() {
        document.querySelectorAll(
            '.cl-sidebar, .pf-sidebar, #sidebar, aside.sidebar'
        ).forEach(function (s) { s.classList.remove('open'); });
        document.body.classList.remove('mobile-sidebar-open');
        var bd = document.querySelector('.mobile-sidebar-backdrop');
        if (bd) bd.classList.remove('is-visible');
    }

    /* Re-evaluate on every class mutation on common sidebar selectors. */
    function bindObserver() {
        var sidebars = document.querySelectorAll(
            '.cl-sidebar, .pf-sidebar, #sidebar, aside.sidebar'
        );
        if (!sidebars.length) return;

        var bd = ensureBackdrop();

        var sync = function () {
            var open = getActiveSidebar();
            if (open && window.innerWidth <= 768) {
                document.body.classList.add('mobile-sidebar-open');
                bd.classList.add('is-visible');
            } else {
                document.body.classList.remove('mobile-sidebar-open');
                bd.classList.remove('is-visible');
            }
        };

        var mo = new MutationObserver(sync);
        sidebars.forEach(function (s) {
            mo.observe(s, { attributes: true, attributeFilter: ['class'] });
        });

        // Also reset on resize crossing the breakpoint
        window.addEventListener('resize', function () {
            if (window.innerWidth > 768) closeAll();
        });

        // Escape closes
        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape') closeAll();
        });

        sync();
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', bindObserver);
    } else {
        bindObserver();
    }
})();
</script>
