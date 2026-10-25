{{--
    One listener for every password eye on the page.

    Delegated from the document, so a field rendered later still works, and
    written once so three pages cannot drift into three behaviours.
--}}
<script>
    document.addEventListener('click', function (e) {
        var b = e.target.closest('[data-eye]');
        if (!b) return;

        var input = document.getElementById(b.getAttribute('data-eye'));
        if (!input) return;

        var showing = input.type !== 'password';

        input.type = showing ? 'password' : 'text';
        b.setAttribute('aria-pressed', showing ? 'false' : 'true');
        b.setAttribute('title', showing ? 'Show password' : 'Hide password');

        // Keep the caret where they left it: changing type sends it to the
        // start, so a half-typed password loses its place.
        if (input === document.activeElement) {
            var at = input.value.length;
            try { input.setSelectionRange(at, at); } catch (err) { /* some types throw */ }
        }
    });
</script>
