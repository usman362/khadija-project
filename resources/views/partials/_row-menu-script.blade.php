{{-- Shared behaviour for row "more actions" kebab menus.

     Markup contract:
       <div data-row-menu>
         <button aria-haspopup="true" aria-expanded="false">⋯</button>
         <div class="…-menu-pop" data-row-menu-pop> …links/buttons… </div>
       </div>

     The popup is positioned FIXED rather than absolute: these menus live in
     tables that scroll horizontally inside an overflow:hidden card, so an
     absolutely positioned popup gets clipped at the card edge. Fixed
     positioning escapes every clipping ancestor; the trade-off is we place it
     by hand and re-place it on scroll/resize. --}}
@once
@push('scripts')
<script>
(function () {
    var openMenu = null;

    function place(menu) {
        var pop = menu.querySelector('[data-row-menu-pop]');
        var btn = menu.querySelector('button[aria-haspopup]');
        var b = btn.getBoundingClientRect();

        pop.style.position = 'fixed';
        pop.style.right = 'auto';
        pop.style.top = '0px';
        pop.style.left = '0px';

        var w = pop.offsetWidth, h = pop.offsetHeight, pad = 8;
        var vw = window.innerWidth, vh = window.innerHeight;

        // Right-align to the trigger, then pull back inside the viewport.
        // maxLeft floors at pad so a viewport narrower than the popup (or a
        // zero-size one) can't push it off-screen to the left.
        var maxLeft = Math.max(pad, vw - w - pad);
        var left = Math.min(Math.max(pad, b.right - w), maxLeft);

        // Below the trigger, flipping above when there isn't room.
        var top = (b.bottom + h + pad > vh && b.top - h - 5 > pad)
            ? b.top - h - 5
            : b.bottom + 5;

        pop.style.left = Math.round(left) + 'px';
        pop.style.top = Math.round(Math.max(pad, top)) + 'px';
    }

    function close() {
        if (!openMenu) return;
        openMenu.classList.remove('open');
        openMenu.querySelector('button[aria-haspopup]').setAttribute('aria-expanded', 'false');
        openMenu = null;
    }

    /*
     * One listener on the document, not one per menu.
     *
     * Each menu used to get its own listener when the page loaded. My Events
     * now redraws its list in place (partials/_live_regions), and the fresh
     * rows arrived with no listener — the ⋯ did nothing after the first
     * search. Listening at the document catches menus that did not exist yet.
     */
    document.addEventListener('click', function (e) {
        var trigger = e.target.closest ? e.target.closest('[data-row-menu] button[aria-haspopup]') : null;

        if (trigger) {
            var menu = trigger.closest('[data-row-menu]');
            var wasOpen = menu === openMenu;
            close();
            if (wasOpen) return;
            menu.classList.add('open');
            trigger.setAttribute('aria-expanded', 'true');
            openMenu = menu;
            place(menu);
            return;
        }

        // Anywhere else — including an item in the menu — closes it; the
        // link or submit that was clicked still runs.
        close();
    });

    // A redraw replaced the rows; the menu that was open went with them.
    document.addEventListener('live:swapped', function () { openMenu = null; });

    document.addEventListener('keydown', function (e) { if (e.key === 'Escape') close(); });
    // Fixed popups don't travel with their trigger — put them back.
    window.addEventListener('resize', function () { if (openMenu) place(openMenu); });
    window.addEventListener('scroll', function () { if (openMenu) place(openMenu); }, true);
})();
</script>
@endpush
@endonce
