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
    /* ─── Flatpickr dark theme — matches the GigResource palette ─── */
    .flatpickr-calendar {
        background: #151d35 !important;
        border: 1px solid rgba(255,255,255,0.10) !important;
        border-radius: 14px !important;
        box-shadow: 0 30px 80px rgba(0,0,0,0.50) !important;
        color: #e2e8f0 !important;
        backdrop-filter: blur(20px);
    }
    .flatpickr-calendar.arrowTop:before,
    .flatpickr-calendar.arrowTop:after { border-bottom-color: #151d35 !important; }
    .flatpickr-calendar.arrowBottom:before,
    .flatpickr-calendar.arrowBottom:after { border-top-color: #151d35 !important; }

    .flatpickr-months .flatpickr-month {
        color: #fff !important;
        fill: #fff !important;
        background: linear-gradient(135deg, rgba(59,130,246,0.10), rgba(139,92,246,0.10));
        border-radius: 14px 14px 0 0;
        height: 44px !important;
    }
    .flatpickr-current-month .flatpickr-monthDropdown-months,
    .flatpickr-current-month input.cur-year {
        color: #fff !important;
        font-weight: 700 !important;
    }
    .flatpickr-current-month .flatpickr-monthDropdown-months {
        background: transparent !important;
    }
    .flatpickr-monthDropdown-month {
        background: #151d35 !important;
        color: #e2e8f0 !important;
    }

    .flatpickr-prev-month, .flatpickr-next-month {
        color: rgba(255,255,255,0.85) !important;
        fill: rgba(255,255,255,0.85) !important;
        padding: 12px !important;
    }
    .flatpickr-prev-month:hover svg,
    .flatpickr-next-month:hover svg {
        fill: #a78bfa !important;
    }

    .flatpickr-weekdays { background: transparent; }
    .flatpickr-weekday {
        color: rgba(255,255,255,0.55) !important;
        font-weight: 700 !important;
        font-size: 11px !important;
        text-transform: uppercase;
    }

    .flatpickr-day {
        color: #e2e8f0;
        border-radius: 8px;
        font-weight: 500;
    }
    .flatpickr-day:hover,
    .flatpickr-day.prevMonthDay:hover,
    .flatpickr-day.nextMonthDay:hover {
        background: rgba(139,92,246,0.18) !important;
        border-color: transparent !important;
        color: #fff !important;
    }
    .flatpickr-day.today {
        border-color: #8b5cf6 !important;
        color: #c4b5fd !important;
    }
    .flatpickr-day.today:hover {
        background: rgba(139,92,246,0.20) !important;
        color: #fff !important;
    }
    .flatpickr-day.selected,
    .flatpickr-day.startRange,
    .flatpickr-day.endRange,
    .flatpickr-day.selected.inRange {
        background: linear-gradient(135deg, #3b82f6, #8b5cf6) !important;
        border-color: transparent !important;
        color: #fff !important;
        box-shadow: 0 6px 16px rgba(139,92,246,0.40) !important;
    }
    .flatpickr-day.inRange {
        background: rgba(139,92,246,0.12) !important;
        border-color: transparent !important;
        box-shadow: none !important;
        color: #fff !important;
    }
    .flatpickr-day.flatpickr-disabled,
    .flatpickr-day.flatpickr-disabled:hover {
        color: rgba(255,255,255,0.20) !important;
        background: transparent !important;
    }
    .flatpickr-day.prevMonthDay,
    .flatpickr-day.nextMonthDay {
        color: rgba(255,255,255,0.30);
    }

    /* Time-picker (when data-flatpickr-time is set) */
    .flatpickr-time { background: transparent !important; border-top: 1px solid rgba(255,255,255,0.08) !important; }
    .flatpickr-time input { color: #fff !important; background: transparent !important; }
    .flatpickr-time .flatpickr-am-pm { color: #fff !important; }
    .flatpickr-time input:hover, .flatpickr-time .flatpickr-am-pm:hover { background: rgba(139,92,246,0.10) !important; }

    /* Light-theme override — when user picks light mode the dropdown
       should switch to a paper background to stay legible. */
    [data-theme="light"] .flatpickr-calendar,
    [data-bs-theme="light"] .flatpickr-calendar {
        background: #ffffff !important;
        border-color: rgba(0,0,0,0.10) !important;
        color: #1f2937 !important;
    }
    [data-theme="light"] .flatpickr-day,
    [data-bs-theme="light"] .flatpickr-day { color: #1f2937; }
    [data-theme="light"] .flatpickr-weekday,
    [data-bs-theme="light"] .flatpickr-weekday { color: #64748b !important; }
    [data-theme="light"] .flatpickr-current-month .flatpickr-monthDropdown-months,
    [data-theme="light"] .flatpickr-current-month input.cur-year,
    [data-bs-theme="light"] .flatpickr-current-month .flatpickr-monthDropdown-months,
    [data-bs-theme="light"] .flatpickr-current-month input.cur-year { color: #1f2937 !important; }
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
