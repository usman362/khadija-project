{{--
    Global datepicker partial.
    Replaces the native browser <input type="date"> with a styled
    Flatpickr instance on every page that includes this partial.

    How to opt out per field:
        <input type="date" data-no-flatpickr>

    How to add time picker (datetime):
        <input type="date" data-flatpickr-time>

    How to set min / max:
        <input type="date" data-flatpickr-min="2024-01-01" data-flatpickr-max="2026-12-31">

    Auto-applies to:
        input[type="date"]
        input[type="datetime-local"]
        input.flatpickr  (manual opt-in for any other input)
--}}

{{-- Flatpickr core CSS + custom dark theme overrides --}}
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr@4.6.13/dist/flatpickr.min.css">

<style>
    /*
       Flatpickr theme.
       ─────────────────────────────────────────────────────────────
       This file used to be a DARK theme with a short light "override"
       bolted on: eleven dark rules, four of them !important, and a
       light block that patched only four selectors.

       The site is light now and stamps data-theme="light" by default, so
       the patch was what ran — and it did not reach the two rules that
       set white text with !important:

           .flatpickr-day.flatpickr-disabled { color: rgba(255,255,255,.20) !important }
           .flatpickr-time input             { color: #fff !important }

       White on white. Every past date rendered invisible, so opening the
       picker on the 27th showed three empty rows above the 25th and looked
       broken, and the time field showed as a stray floating fragment.

       So light is the base now and dark is the override, which is the way
       round the site actually runs. Colours come from the layout's own
       tokens, with literals behind them for any page that does not define
       them — a picker must not depend on a variable to be readable.
    */

    .flatpickr-calendar {
        background: var(--bg-card, #ffffff) !important;
        border: 1px solid var(--border-color, #e2e8f0) !important;
        border-radius: 14px !important;
        box-shadow: 0 20px 50px rgba(15, 23, 42, .16) !important;
        color: var(--text-primary, #1f2937) !important;
    }
    .flatpickr-calendar.arrowTop:before,
    .flatpickr-calendar.arrowTop:after { border-bottom-color: var(--bg-card, #ffffff) !important; }
    .flatpickr-calendar.arrowBottom:before,
    .flatpickr-calendar.arrowBottom:after { border-top-color: var(--bg-card, #ffffff) !important; }

    .flatpickr-months .flatpickr-month {
        color: var(--text-primary, #1f2937) !important;
        fill: var(--text-primary, #1f2937) !important;
        background: transparent;
        border-radius: 14px 14px 0 0;
        height: 46px !important;
    }
    .flatpickr-current-month .flatpickr-monthDropdown-months,
    .flatpickr-current-month input.cur-year {
        color: var(--text-primary, #1f2937) !important;
        font-weight: 700 !important;
    }
    .flatpickr-current-month .flatpickr-monthDropdown-months { background: transparent !important; }
    .flatpickr-monthDropdown-month {
        background: var(--bg-card, #ffffff) !important;
        color: var(--text-primary, #1f2937) !important;
    }

    .flatpickr-prev-month, .flatpickr-next-month {
        color: var(--text-muted, #64748b) !important;
        fill: var(--text-muted, #64748b) !important;
        padding: 12px !important;
    }
    .flatpickr-prev-month:hover svg,
    .flatpickr-next-month:hover svg { fill: var(--brand-text, #f97316) !important; }

    .flatpickr-weekdays { background: transparent; }
    .flatpickr-weekday {
        color: var(--text-muted, #64748b) !important;
        background: transparent !important;
        font-weight: 700 !important;
        font-size: 11px !important;
        text-transform: uppercase;
    }

    .flatpickr-day {
        color: var(--text-primary, #1f2937);
        border-radius: 8px;
        font-weight: 500;
    }
    .flatpickr-day:hover,
    .flatpickr-day.prevMonthDay:hover,
    .flatpickr-day.nextMonthDay:hover {
        background: rgba(249, 115, 22, .12) !important;
        border-color: transparent !important;
        color: var(--text-primary, #1f2937) !important;
    }
    .flatpickr-day.today {
        border-color: var(--brand-text, #f97316) !important;
        color: var(--brand-text, #f97316) !important;
        font-weight: 700;
    }
    .flatpickr-day.today:hover { background: rgba(249, 115, 22, .12) !important; }

    .flatpickr-day.selected,
    .flatpickr-day.startRange,
    .flatpickr-day.endRange,
    .flatpickr-day.selected.inRange {
        background: var(--brand-text, #f97316) !important;
        border-color: transparent !important;
        color: #ffffff !important;
        box-shadow: 0 6px 16px rgba(249, 115, 22, .32) !important;
    }
    .flatpickr-day.inRange {
        background: rgba(249, 115, 22, .10) !important;
        border-color: transparent !important;
        box-shadow: none !important;
        color: var(--text-primary, #1f2937) !important;
    }

    /* A date you cannot pick still has to be READABLE — the client is
       reading the grid to find one they can. Greyed, not erased. */
    .flatpickr-day.flatpickr-disabled,
    .flatpickr-day.flatpickr-disabled:hover {
        color: var(--text-muted, #94a3b8) !important;
        opacity: .45;
        background: transparent !important;
        cursor: not-allowed;
    }
    .flatpickr-day.prevMonthDay,
    .flatpickr-day.nextMonthDay { color: var(--text-muted, #94a3b8); opacity: .7; }

    /* Time picker. */
    .flatpickr-time { border-top: 1px solid var(--border-color, #e2e8f0) !important; background: transparent !important; }
    .flatpickr-time input,
    .flatpickr-time .flatpickr-time-separator,
    .flatpickr-time .flatpickr-am-pm {
        color: var(--text-primary, #1f2937) !important;
        background: transparent !important;
        font-weight: 600;
    }
    .flatpickr-time input:hover,
    .flatpickr-time input:focus,
    .flatpickr-time .flatpickr-am-pm:hover,
    .flatpickr-time .flatpickr-am-pm:focus { background: rgba(249, 115, 22, .10) !important; }
    .flatpickr-time .numInputWrapper span { border-color: var(--border-color, #e2e8f0) !important; }
    .flatpickr-time .numInputWrapper span:after { border-bottom-color: var(--text-muted, #64748b) !important; border-top-color: var(--text-muted, #64748b) !important; }

    /* ─── Dark, for the layouts and users that ask for it ─── */
    [data-theme="dark"] .flatpickr-calendar {
        background: #151d35 !important;
        border-color: rgba(255, 255, 255, .10) !important;
        color: #e2e8f0 !important;
        box-shadow: 0 30px 80px rgba(0, 0, 0, .5) !important;
    }
    [data-theme="dark"] .flatpickr-calendar.arrowTop:before,
    [data-theme="dark"] .flatpickr-calendar.arrowTop:after { border-bottom-color: #151d35 !important; }
    [data-theme="dark"] .flatpickr-calendar.arrowBottom:before,
    [data-theme="dark"] .flatpickr-calendar.arrowBottom:after { border-top-color: #151d35 !important; }

    [data-theme="dark"] .flatpickr-months .flatpickr-month,
    [data-theme="dark"] .flatpickr-current-month .flatpickr-monthDropdown-months,
    [data-theme="dark"] .flatpickr-current-month input.cur-year { color: #fff !important; fill: #fff !important; }
    [data-theme="dark"] .flatpickr-monthDropdown-month { background: #151d35 !important; color: #e2e8f0 !important; }
    [data-theme="dark"] .flatpickr-prev-month,
    [data-theme="dark"] .flatpickr-next-month { color: rgba(255, 255, 255, .85) !important; fill: rgba(255, 255, 255, .85) !important; }
    [data-theme="dark"] .flatpickr-weekday { color: rgba(255, 255, 255, .55) !important; }
    [data-theme="dark"] .flatpickr-day { color: #e2e8f0; }
    [data-theme="dark"] .flatpickr-day:hover { background: rgba(139, 92, 246, .18) !important; color: #fff !important; }
    [data-theme="dark"] .flatpickr-day.today { border-color: #8b5cf6 !important; color: #c4b5fd !important; }
    [data-theme="dark"] .flatpickr-day.selected,
    [data-theme="dark"] .flatpickr-day.startRange,
    [data-theme="dark"] .flatpickr-day.endRange {
        background: linear-gradient(135deg, #3b82f6, #8b5cf6) !important;
        color: #fff !important;
        box-shadow: 0 6px 16px rgba(139, 92, 246, .4) !important;
    }
    /* Still greyed rather than erased — the same rule, in the other palette. */
    [data-theme="dark"] .flatpickr-day.flatpickr-disabled,
    [data-theme="dark"] .flatpickr-day.flatpickr-disabled:hover { color: rgba(255, 255, 255, .45) !important; opacity: 1; }
    [data-theme="dark"] .flatpickr-day.prevMonthDay,
    [data-theme="dark"] .flatpickr-day.nextMonthDay { color: rgba(255, 255, 255, .35); opacity: 1; }
    [data-theme="dark"] .flatpickr-time { border-top-color: rgba(255, 255, 255, .08) !important; }
    [data-theme="dark"] .flatpickr-time input,
    [data-theme="dark"] .flatpickr-time .flatpickr-time-separator,
    [data-theme="dark"] .flatpickr-time .flatpickr-am-pm { color: #fff !important; }
</style>

{{-- Flatpickr JS — defer so it doesn't block parsing --}}
<script defer src="https://cdn.jsdelivr.net/npm/flatpickr@4.6.13/dist/flatpickr.min.js"></script>
<script>
/* Auto-init Flatpickr on every <input type="date"> / <input type="datetime-local">
   on the page. Idempotent — safe to include the partial multiple times. */
(function () {
    if (window.__fpInitialized) return;
    window.__fpInitialized = true;

    function initAll() {
        if (typeof window.flatpickr !== 'function') return;

        document.querySelectorAll(
            'input[type="date"]:not([data-no-flatpickr]):not(.flatpickr-input), ' +
            'input[type="datetime-local"]:not([data-no-flatpickr]):not(.flatpickr-input), ' +
            'input.flatpickr:not(.flatpickr-input)'
        ).forEach(function (input) {
            // Pull per-field overrides off data attributes
            var enableTime = input.type === 'datetime-local' || input.hasAttribute('data-flatpickr-time');

            /* altInput copies the source input's placeholder, and none of the
               27 date fields on this site had one -- so every date on every
               screen rendered as an empty box with no hint that it opens a
               picker. Set here rather than on each field: one prompt, and a
               new date input cannot be added without it. */
            if (!input.getAttribute('placeholder')) {
                input.setAttribute('placeholder', enableTime ? 'Pick a date and time' : 'Pick a date');
            }
            var minDate    = input.getAttribute('data-flatpickr-min') || input.min || null;
            var maxDate    = input.getAttribute('data-flatpickr-max') || input.max || null;

            window.flatpickr(input, {
                dateFormat: enableTime ? 'Y-m-d H:i' : 'Y-m-d',
                altInput:   true,
                altFormat:  enableTime ? 'M j, Y — h:i K' : 'M j, Y',
                allowInput: false,
                enableTime: enableTime,
                minDate:    minDate,
                maxDate:    maxDate,
                disableMobile: true,    // Use Flatpickr UI on mobile too (consistent look)
            });
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function () {
            // Wait for Flatpickr script to load if it hasn't yet
            if (typeof window.flatpickr === 'function') {
                initAll();
            } else {
                var t = setInterval(function () {
                    if (typeof window.flatpickr === 'function') {
                        clearInterval(t);
                        initAll();
                    }
                }, 50);
                setTimeout(function () { clearInterval(t); }, 5000); // safety stop
            }
        });
    } else {
        // DOM already parsed — wait for flatpickr script (deferred) to load
        if (typeof window.flatpickr === 'function') {
            initAll();
        } else {
            var s = document.querySelector('script[src*="flatpickr"]');
            if (s) s.addEventListener('load', initAll);
        }
    }
})();
</script>
