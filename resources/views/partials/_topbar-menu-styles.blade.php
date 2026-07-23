{{-- Styles for the topbar bell / account dropdowns. Shared by the client and
     professional layouts, which theme themselves through the same
     --bg-card / --border-color / --text-* variables. --}}
@once
<style>
    .tbm { position: relative; display: inline-flex; }
    /* The account triggers became <button>s (they were a link and a div with an
       onclick). Buttons don't inherit the page font by default. */
    .tbm > button { font: inherit; }
    /* Floating panels must be OPAQUE. Both layouts set --bg-card to
       rgba(17,24,39,0.7) in the dark theme, so anything using it as a popup
       background lets the page read straight through. These pin an opaque
       surface per theme instead; the text tokens still flip on their own.
       Applies to the row kebab menus too. */
    .tbm-pop, .pr-menu-pop, .mg-menu-pop {
        background: #111827;
        border-color: rgba(255, 255, 255, 0.12);
    }
    [data-theme="light"] .tbm-pop,
    [data-theme="light"] .pr-menu-pop,
    [data-theme="light"] .mg-menu-pop {
        background: #ffffff;
        border-color: #e5e7eb;
    }
    .tbm-pop {
        display: none; position: absolute; right: 0; top: calc(100% + 8px); z-index: 300;
        min-width: 260px; max-width: 340px;
        border: 1px solid;
        border-radius: 12px; box-shadow: 0 16px 40px rgba(15, 23, 42, 0.16);
        padding: 6px; text-align: left;
    }
    .tbm.open .tbm-pop { display: block; }
    .tbm-head {
        font-size: 11px; font-weight: 800; letter-spacing: .4px; text-transform: uppercase;
        color: var(--text-muted, #6b7280); padding: 8px 10px 6px;
    }
    .tbm-item {
        display: block; width: 100%; text-align: left; background: none; border: 0;
        font: inherit; font-size: 13px; font-weight: 600; color: var(--text-primary, #111827);
        text-decoration: none; padding: 9px 10px; border-radius: 8px; cursor: pointer;
    }
    .tbm-item:hover { background: var(--bg-card-hover, rgba(249,115,22,.10)); }
    .tbm-item .sub { display: block; font-size: 11px; font-weight: 500; color: var(--text-muted, #6b7280); margin-top: 2px; }
    .tbm-note { display: block; font-size: 12.5px; font-weight: 500; color: var(--text-secondary, #4b5563); line-height: 1.45; }
    .tbm-note.unread { font-weight: 700; color: var(--text-primary, #111827); }
    .tbm-empty { padding: 14px 10px; font-size: 12.5px; color: var(--text-muted, #6b7280); }
    .tbm-sep { height: 1px; background: var(--border-color, #e5e7eb); margin: 5px 0; }
    .tbm-foot { border-top: 1px solid var(--border-color, #e5e7eb); margin-top: 5px; padding-top: 5px; }
    .tbm-user { padding: 9px 10px 7px; }
    .tbm-user b { display: block; font-size: 13px; color: var(--text-primary, #111827); }
    .tbm-user span { display: block; font-size: 11.5px; color: var(--text-muted, #6b7280); margin-top: 1px; word-break: break-all; }
    .tbm-item.danger { color: #dc2626; }
    .tbm-item.danger:hover { background: rgba(220, 38, 38, .10); }
    .tbm-pop form { margin: 0; }
</style>
@endonce
