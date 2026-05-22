{{--
    Centralized SEO meta partial. Include from any layout's <head>.

    Variables (all optional — sensible brand-level defaults are used if a
    page-level variable isn't set):

      $seoTitle        — page-specific title (will be suffixed " | GigResource")
      $seoDescription  — 150–160 char description
      $seoImage        — absolute URL of social-share image (1200×630 ideal)
      $seoKeywords     — comma-separated keywords (optional, mostly Bing/Yandex)
      $seoCanonical    — explicit canonical URL (defaults to current url())
      $seoNoIndex      — true to emit <meta name="robots" content="noindex,nofollow">
      $seoType         — og:type override (default "website"; use "article" on blog/posts)

    Pages set these via Blade @php blocks at the top of their content:

        @php
            $seoTitle       = 'Hire Wedding Photographers';
            $seoDescription = 'Browse vetted wedding photographers...';
        @endphp
--}}
@php
    $brand        = 'GigResource';
    $tagline      = 'Hire trusted event professionals. Bookings, contracts, and payouts in one place.';
    $title        = isset($seoTitle) && $seoTitle ? $seoTitle . ' | ' . $brand : $brand . ' — ' . $tagline;
    $description  = $seoDescription ?? 'GigResource is the marketplace for event professionals — photographers, caterers, DJs, planners and more. Get quotes, sign contracts, and pay securely.';
    $description  = trim(preg_replace('/\s+/', ' ', $description));
    $description  = mb_substr($description, 0, 160);
    $image        = $seoImage ?? asset('images/og-default.jpg');
    $canonical    = $seoCanonical ?? url()->current();
    $ogType       = $seoType ?? 'website';
    $robots       = !empty($seoNoIndex) ? 'noindex, nofollow' : 'index, follow, max-image-preview:large';
@endphp

<title>{{ $title }}</title>
<meta name="description" content="{{ $description }}">
@isset($seoKeywords)
    <meta name="keywords" content="{{ $seoKeywords }}">
@endisset
<meta name="robots" content="{{ $robots }}">
<link rel="canonical" href="{{ $canonical }}">

{{-- Open Graph (Facebook, LinkedIn, WhatsApp previews) --}}
<meta property="og:site_name" content="{{ $brand }}">
<meta property="og:type" content="{{ $ogType }}">
<meta property="og:title" content="{{ $title }}">
<meta property="og:description" content="{{ $description }}">
<meta property="og:url" content="{{ $canonical }}">
<meta property="og:image" content="{{ $image }}">
<meta property="og:image:width" content="1200">
<meta property="og:image:height" content="630">
<meta property="og:locale" content="{{ str_replace('-', '_', app()->getLocale() === 'en' ? 'en_US' : app()->getLocale()) }}">

{{-- Twitter card (also used by Slack, Discord, iMessage) --}}
<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:title" content="{{ $title }}">
<meta name="twitter:description" content="{{ $description }}">
<meta name="twitter:image" content="{{ $image }}">

{{-- Theme color for mobile browser chrome (Chrome Android, Safari iOS) --}}
<meta name="theme-color" content="#0b0f1a">

{{-- JSON-LD Organization schema — helps Google show the rich sitelink box --}}
<script type="application/ld+json">
{
    "@context": "https://schema.org",
    "@type": "Organization",
    "name": "{{ $brand }}",
    "url": "{{ url('/') }}",
    "logo": "{{ asset('images/logo.png') }}",
    "description": "{{ $description }}"
}
</script>
