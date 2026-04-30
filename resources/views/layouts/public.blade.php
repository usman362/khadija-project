<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', config('app.name', 'Khadija'))</title>
    @stack('meta')

    {{-- Preconnect to remote origins so the first request to each is
         faster — DNS + TLS already negotiated by the time CSS / images
         resolve. `crossorigin` is required on the fonts.gstatic preconnect
         for the browser to actually reuse the connection. --}}
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="preconnect" href="https://images.unsplash.com">
    <link rel="dns-prefetch" href="https://images.unsplash.com">

    {{-- Trimmed Inter weight set: 400/600/700/800 covers everything we
         actually use. Dropping 300/500/900 saves ~30 KB of font payload.
         &display=swap keeps text visible during font load (CLS-friendly). --}}
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800&display=swap" rel="stylesheet">

    @include('partials._public_styles')
    @stack('styles')
</head>
<body>

{{-- Skip-to-content link — invisible until focused via keyboard.
     First Tab press jumps a screen-reader / keyboard user past the
     navbar straight to the main content. WCAG 2.1 §2.4.1 (Bypass Blocks). --}}
<a href="#main-content" class="skip-to-content">Skip to main content</a>

@include('partials.navbar')

<main id="main-content" tabindex="-1">
    @yield('content')
</main>

@include('partials.footer')

<script>
    /* Bottom-of-body inline script — runs after the DOM is parsed.
       Uses event delegation (single document-level click listener) so
       this stays cheap even if a page renders hundreds of anchors. */
    (function () {
        document.addEventListener('click', function (e) {
            // Close Join dropdown on outside click
            var dd = document.getElementById('joinDropdown');
            if (dd && !dd.contains(e.target)) dd.classList.remove('open');

            // Smooth scroll for in-page anchor links — delegated, no per-element listeners.
            var a = e.target.closest('a[href^="#"]');
            if (a) {
                var hash = a.getAttribute('href');
                if (hash && hash.length > 1) {
                    var target = document.querySelector(hash);
                    if (target) { e.preventDefault(); target.scrollIntoView({ behavior: 'smooth' }); }
                }
            }
        });

        /* Mark every <img> with decoding="async" so the browser decodes
           images off-thread and doesn't block paint. We do this in JS at
           the end so we don't have to touch every <img> tag in markup. */
        document.querySelectorAll('img:not([decoding])').forEach(function (img) {
            img.setAttribute('decoding', 'async');
        });
    })();
</script>
@stack('scripts')

{{-- Inline form validation (live blur/submit messages) — included once
     globally so every public form auto-picks up data-validate attributes
     without the page having to remember to import the partial. --}}
@include('partials._form_validation')

{{-- Styled datepicker (Flatpickr) — replaces native <input type="date">
     across the site for a consistent, branded date-picking UX. --}}
@include('partials._datepicker')
</body>
</html>
