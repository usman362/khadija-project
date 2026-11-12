{{-- The look of a row "more actions" menu, in one place.

     Three professional pages drew a three-dot control and made it an <a> to
     the same place as the button beside it, so it read as a menu and behaved
     as a redirect. A fourth built its own menu and had it clipped by the
     card it scrolls inside. They share this now: these styles for the look,
     partials/_row-menu-script for the behaviour, which positions the popup
     FIXED so no scrolling ancestor can cut it off.

     Markup:
       <span class="rowmenu" data-row-menu>
         <button type="button" class="rowmenu-btn" aria-haspopup="true" aria-expanded="false">&#8942;</button>
         <div class="rowmenu-pop" data-row-menu-pop> …links/buttons… </div>
       </span> --}}
@once
@push('styles')
<style>
    .rowmenu { position: relative; display: inline-flex; }
    .rowmenu-btn { width: 26px; height: 27px; border-radius: 7px; border: 1px solid transparent;
        background: none; color: var(--text-muted); cursor: pointer; font-size: 15px; line-height: 1;
        padding: 0; display: inline-flex; align-items: center; justify-content: center; font-family: inherit; }
    .rowmenu-btn:hover,
    .rowmenu.open .rowmenu-btn { background: var(--bg-card-hover, rgba(128,128,128,.1));
        border-color: var(--border-color); color: var(--text-primary); }
    /* --bg-card is translucent in dark mode, which is right for a card sitting
       on the page and wrong for a menu sitting on top of a table row: the row
       read through it. The blur is what makes it opaque enough to read. */
    .rowmenu-pop { display: none; min-width: 190px; padding: 5px; z-index: 60;
        background: var(--bg-card, #fff); border: 1px solid var(--border-color, #e5e7eb);
        border-radius: 10px; box-shadow: 0 12px 30px rgba(15, 23, 42, .16);
        backdrop-filter: blur(14px); -webkit-backdrop-filter: blur(14px); }
    .rowmenu.open .rowmenu-pop { display: block; }
    .rowmenu-pop a, .rowmenu-pop button { display: block; width: 100%; text-align: left; font: inherit;
        font-size: 12.5px; font-weight: 600; padding: 8px 10px; border: 0; border-radius: 7px;
        background: none; color: var(--text-primary); text-decoration: none; cursor: pointer; }
    .rowmenu-pop a:hover, .rowmenu-pop button:hover { background: var(--bg-page, rgba(128,128,128,.1)); }
    .rowmenu-pop form { margin: 0; }
    .rowmenu-pop .danger { color: var(--bad-text, #dc2626); }
</style>
@endpush
@endonce
