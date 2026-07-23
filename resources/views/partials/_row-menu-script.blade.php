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

    document.querySelectorAll('[data-row-menu]').forEach(function (menu) {
        var trigger = menu.querySelector('button[aria-haspopup]');
        if (!trigger) return;

        trigger.addEventListener('click', function (e) {
            e.stopPropagation();
            var wasOpen = menu === openMenu;
            close();
            if (wasOpen) return;
            menu.classList.add('open');
            trigger.setAttribute('aria-expanded', 'true');
            openMenu = menu;
            place(menu);
        });

        // Picking an item closes the menu; the link or submit still runs.
        var pop = menu.querySelector('[data-row-menu-pop]');
        if (pop) pop.addEventListener('click', function () { close(); });
    });

    document.addEventListener('click', close);
    document.addEventListener('keydown', function (e) { if (e.key === 'Escape') close(); });
    // Fixed popups don't travel with their trigger — put them back.
    window.addEventListener('resize', function () { if (openMenu) place(openMenu); });
    window.addEventListener('scroll', function () { if (openMenu) place(openMenu); }, true);
})();
</script>
