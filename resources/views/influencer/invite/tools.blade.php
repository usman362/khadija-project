@extends('layouts.influencer-portal')
@section('title', 'Invite Tools')
@push('styles') @include('influencer.invite._styles') @endpush

@php $enc = urlencode($referralUrl); @endphp
@section('content')
<div class="ipx-breadcrumb"><a href="{{ route('influencer.invite.earn') }}">Invite &amp; Earn More</a> <span class="sep">›</span> Invite Tools</div>
<div class="iv-head"><h1>Invite Tools</h1><p>Everything you need to invite, connect, and convert your audience.</p></div>

<div class="iv-layout">
    <div>
        {{-- link generator --}}
        <div class="iv-panel">
            <h3>Your Referral Link</h3>
            <div class="sub">Share this link — you earn a commission every time someone signs up and books through it.</div>
            <div class="iv-linkbox">
                <div class="url"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M10 13a5 5 0 0 0 7.5.5l3-3a5 5 0 0 0-7-7l-1.7 1.7"/><path d="M14 11a5 5 0 0 0-7.5-.5l-3 3a5 5 0 0 0 7 7l1.7-1.7"/></svg><span id="refUrl">{{ $referralUrl }}</span></div>
                <button type="button" class="iv-copy" id="copyBtn" data-url="{{ $referralUrl }}">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="9" y="9" width="13" height="13" rx="2"/><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/></svg>
                    <span>Copy Link</span>
                </button>
            </div>
            <div style="display:flex; align-items:center; gap:8px; margin-top:12px; font-size:12.5px; color:var(--muted);">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M12 16v-4M12 8h.01"/></svg>
                Your unique code: <b style="color:var(--ink); font-family:var(--ff);">{{ $referralCode }}</b>
            </div>
        </div>

        {{-- share --}}
        <div class="iv-panel">
            <h3>Share Your Link Everywhere</h3>
            <div class="sub">Share on social media, messaging apps, and more.</div>
            <div class="iv-share">
                <a href="https://www.facebook.com/sharer/sharer.php?u={{ $enc }}" target="_blank" rel="noopener"><span class="ic" style="background:#1877F2;"><svg viewBox="0 0 24 24" fill="currentColor"><path d="M24 12c0-6.6-5.4-12-12-12S0 5.4 0 12c0 6 4.4 11 10.1 11.9v-8.4H7.1V12h3V9.4c0-3 1.8-4.6 4.5-4.6 1.3 0 2.7.2 2.7.2v2.9h-1.5c-1.5 0-1.9.9-1.9 1.9V12h3.3l-.5 3.5h-2.8v8.4C19.6 23 24 18 24 12z"/></svg></span><span class="nm">Facebook</span></a>
                <a href="https://twitter.com/intent/tweet?url={{ $enc }}&text=Join%20GigResource" target="_blank" rel="noopener"><span class="ic" style="background:#000;"><svg viewBox="0 0 24 24" fill="currentColor"><path d="M18.9 1.2h3.7l-8 9.1L24 22.8h-7.4l-5.8-7.6-6.6 7.6H.5l8.6-9.8L0 1.2h7.6l5.2 6.9 6.1-6.9zm-1.3 19.4h2L6.5 3.3H4.3z"/></svg></span><span class="nm">X</span></a>
                <a href="https://www.linkedin.com/sharing/share-offsite/?url={{ $enc }}" target="_blank" rel="noopener"><span class="ic" style="background:#0A66C2;"><svg viewBox="0 0 24 24" fill="currentColor"><path d="M4.98 3.5a2.5 2.5 0 1 1 0 5 2.5 2.5 0 0 1 0-5zM3 9h4v12H3zM10 9h3.8v1.7h.05c.53-1 1.83-2.05 3.77-2.05 4.03 0 4.78 2.65 4.78 6.1V21h-4v-5.4c0-1.3 0-3-1.8-3s-2.1 1.4-2.1 2.9V21h-4z"/></svg></span><span class="nm">LinkedIn</span></a>
                <a href="https://www.instagram.com/" target="_blank" rel="noopener"><span class="ic" style="background:linear-gradient(45deg,#f09433,#e6683c,#dc2743,#cc2366,#bc1888);"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="2" width="20" height="20" rx="5"/><circle cx="12" cy="12" r="4"/><line x1="17.5" y1="6.5" x2="17.51" y2="6.5"/></svg></span><span class="nm">Instagram</span></a>
                <a href="https://wa.me/?text={{ $enc }}" target="_blank" rel="noopener"><span class="ic" style="background:#25D366;"><svg viewBox="0 0 24 24" fill="currentColor"><path d="M12 2a10 10 0 0 0-8.5 15.3L2 22l4.8-1.4A10 10 0 1 0 12 2zm5.8 14.2c-.2.7-1.4 1.3-2 1.4-.5.1-1.2.1-1.9-.1-.4-.1-1-.3-1.8-.6-3-1.3-5-4.4-5.2-4.6-.1-.2-1.2-1.6-1.2-3s.7-2.1 1-2.4c.2-.3.5-.3.7-.3h.5c.2 0 .4 0 .6.5l.8 2c.1.1.1.3 0 .5l-.4.5-.3.3c-.1.1-.3.3-.1.6.1.3.7 1.1 1.4 1.8.9.8 1.7 1.1 2 1.2.2.1.4.1.5-.1l.6-.7c.2-.2.3-.2.5-.1l1.9.9c.2.1.4.2.4.3.1.1.1.6-.1 1.2z"/></svg></span><span class="nm">WhatsApp</span></a>
                <a href="mailto:?subject=Join%20GigResource&body=Sign%20up%20with%20my%20link:%20{{ $enc }}"><span class="ic" style="background:#ea580c;"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="4" width="20" height="16" rx="2"/><path d="M22 7l-10 7L2 7"/></svg></span><span class="nm">Email</span></a>
                <a href="https://t.me/share/url?url={{ $enc }}" target="_blank" rel="noopener"><span class="ic" style="background:#0088cc;"><svg viewBox="0 0 24 24" fill="currentColor"><path d="M21.9 4.3 18.7 19c-.2 1-.9 1.3-1.8.8l-4.9-3.6-2.4 2.3c-.3.3-.5.5-1 .5l.3-4.9 8.9-8c.4-.3-.1-.5-.6-.2L6.5 12.7l-4.7-1.5c-1-.3-1-1 .2-1.5l18.4-7.1c.9-.3 1.6.2 1.3 1.7z"/></svg></span><span class="nm">Telegram</span></a>
            </div>
        </div>

        {{-- materials --}}
        <div class="iv-panel">
            <h3>Marketing Materials</h3>
            <div class="sub">Ready-made resources to promote your link.</div>
            <div class="iv-mats">
                <a href="{{ route('influencer.invite.promote') }}" class="iv-mat"><span class="ic" style="background:var(--blue-soft);color:var(--blue);"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="4" y1="9" x2="20" y2="9"/><line x1="4" y1="15" x2="20" y2="15"/><line x1="10" y1="3" x2="8" y2="21"/><line x1="16" y1="3" x2="14" y2="21"/></svg></span><div><b>Social Media Posts</b><span>Pre-designed posts &amp; captions</span></div><svg class="arrow" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="9 18 15 12 9 6"/></svg></a>
                <a href="{{ route('influencer.invite.promote') }}" class="iv-mat"><span class="ic" style="background:#fce7f3;color:#db2777;"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="2" width="20" height="20" rx="5"/><circle cx="12" cy="12" r="4"/></svg></span><div><b>Stories &amp; Reels Templates</b><span>Engaging templates for stories</span></div><svg class="arrow" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="9 18 15 12 9 6"/></svg></a>
                <a href="{{ route('influencer.invite.promote') }}" class="iv-mat"><span class="ic" style="background:#ede9fe;color:#7c3aed;"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></svg></span><div><b>Banners &amp; Graphics</b><span>Eye-catching banners</span></div><svg class="arrow" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="9 18 15 12 9 6"/></svg></a>
                <a href="{{ route('influencer.invite.promote') }}" class="iv-mat"><span class="ic" style="background:#dcfce7;color:#16a34a;"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="23 7 16 12 23 17 23 7"/><rect x="1" y="5" width="15" height="14" rx="2"/></svg></span><div><b>Videos &amp; Animations</b><span>Short videos to promote</span></div><svg class="arrow" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="9 18 15 12 9 6"/></svg></a>
            </div>
        </div>
    </div>

    {{-- right rail --}}
    <div>
        <div class="iv-rail-card">
            <h4>Your Invite Impact</h4>
            <div class="iv-rail-stat"><span class="ic" style="background:var(--blue-soft);color:var(--blue);"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/></svg></span><div class="m"><div class="l">Total Referrals</div><div class="v">{{ $influencer->total_referrals }}</div></div></div>
            <div class="iv-rail-stat"><span class="ic" style="background:#dcfce7;color:#16a34a;"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg></span><div class="m"><div class="l">Successful Signups</div><div class="v">{{ $signups }}</div></div></div>
            <div class="iv-rail-stat"><span class="ic" style="background:var(--orange-soft);color:var(--orange);"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg></span><div class="m"><div class="l">Total Earnings</div><div class="v">${{ number_format($influencer->total_earnings, 0) }}</div></div></div>
            <a href="{{ route('influencer.invite.earn') }}" class="iv-rail-cta">View Full Earnings <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg></a>
        </div>
        <div class="iv-rail-card">
            <h4>Best Practices</h4>
            <div class="iv-rail-list">
                <div class="it"><svg viewBox="0 0 24 24" fill="none" stroke="#7c3aed" stroke-width="2.4"><polyline points="20 6 9 17 4 12"/></svg> Personalize your message</div>
                <div class="it"><svg viewBox="0 0 24 24" fill="none" stroke="#16a34a" stroke-width="2.4"><polyline points="20 6 9 17 4 12"/></svg> Share in relevant communities</div>
                <div class="it"><svg viewBox="0 0 24 24" fill="none" stroke="#2563eb" stroke-width="2.4"><polyline points="20 6 9 17 4 12"/></svg> Create content that adds value</div>
                <div class="it"><svg viewBox="0 0 24 24" fill="none" stroke="#db2777" stroke-width="2.4"><polyline points="20 6 9 17 4 12"/></svg> Follow up and engage</div>
                <div class="it"><svg viewBox="0 0 24 24" fill="none" stroke="#f97316" stroke-width="2.4"><polyline points="20 6 9 17 4 12"/></svg> Track your performance</div>
            </div>
        </div>
        <div class="iv-rail-card iv-rail-soft">
            <h4>Need Help?</h4>
            <p style="font-size:12.5px;color:var(--text);line-height:1.55;">Our support team is here to help you make the most of your invite tools.</p>
            <a href="{{ route('public.faq') }}" class="iv-rail-cta">Visit Help Center <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg></a>
        </div>
    </div>
</div>

@push('scripts')
<script>
    document.getElementById('copyBtn').addEventListener('click', function () {
        var btn = this;
        navigator.clipboard.writeText(btn.dataset.url).then(function () {
            btn.classList.add('copied');
            btn.querySelector('span').textContent = 'Copied!';
            setTimeout(function () { btn.classList.remove('copied'); btn.querySelector('span').textContent = 'Copy Link'; }, 1800);
        });
    });
</script>
@endpush
@endsection
