{{--
    Voice search — progressive enhancement.

    Any search input flagged with `data-voice-search` gets a microphone
    button injected next to it. Tapping the mic triggers the browser's
    Web Speech API (Chrome / Edge / Safari iOS 14.5+). The transcribed
    text is dropped into the input and the form auto-submits.

    Browsers without SpeechRecognition (Firefox desktop, older Safari)
    silently skip the enhancement — the plain text input still works.

    Include once per page that renders a flagged input. Layouts already
    load it inside @include('partials._voice_search') in the footer.
--}}
<style>
    /* Reset every host-page button rule that might leak in. All declarations
       use !important because some host containers (landing hero, browse glass
       bar) have very high-specificity button styling. The look is tuned to
       work on both light and dark backgrounds via a semi-transparent fill. */
    button.vs-mic-btn,
    .browse-mega-search .search-field button.vs-mic-btn {
        all: unset !important;
        box-sizing: border-box !important;
        width: 36px !important;
        height: 36px !important;
        min-width: 36px !important;
        min-height: 36px !important;
        flex: 0 0 36px !important;
        padding: 0 !important;
        margin: 0 14px 0 12px !important;
        border-radius: 50% !important;
        background: rgba(99, 102, 241, 0.18) !important;
        color: #818cf8 !important;
        display: inline-flex !important;
        align-items: center !important;
        justify-content: center !important;
        cursor: pointer !important;
        transition: background 0.15s, transform 0.15s, color 0.15s !important;
        position: relative !important;
        z-index: 2 !important;
        line-height: 1 !important;
        font: inherit !important;
        vertical-align: middle !important;
    }
    button.vs-mic-btn:hover {
        background: rgba(99, 102, 241, 0.32) !important;
        color: #a5b4fc !important;
    }
    button.vs-mic-btn:focus-visible {
        outline: 2px solid #818cf8 !important;
        outline-offset: 2px !important;
    }
    button.vs-mic-btn.is-listening {
        background: #ef4444 !important;
        color: #fff !important;
        animation: vsMicPulse 1.2s ease-in-out infinite;
    }
    /* Force the mic SVG to inherit our colour even when the host container
       has rules like `.search-field svg { color: var(--text-muted) }`. */
    button.vs-mic-btn > svg,
    .browse-mega-search .search-field button.vs-mic-btn > svg,
    .hero-finder-search button.vs-mic-btn > svg {
        width: 18px !important;
        height: 18px !important;
        stroke: currentColor !important;
        color: currentColor !important;
        fill: none !important;
        display: block !important;
        margin: 0 !important;
        flex-shrink: 0 !important;
    }
    @keyframes vsMicPulse {
        0%, 100% { transform: scale(1);   box-shadow: 0 0 0 0 rgba(239, 68, 68, 0.5); }
        50%      { transform: scale(1.08); box-shadow: 0 0 0 8px rgba(239, 68, 68, 0); }
    }
    .vs-toast {
        position: fixed;
        bottom: 24px;
        left: 50%;
        transform: translateX(-50%);
        background: #0b0f1a;
        color: #fff;
        padding: 10px 18px;
        border-radius: 999px;
        font-size: 14px;
        z-index: 9999;
        opacity: 0;
        pointer-events: none;
        transition: opacity 0.2s;
    }
    .vs-toast.show { opacity: 1; }
    @media (max-width: 640px) {
        .vs-mic-btn { right: 96px; width: 34px; height: 34px; }
    }
</style>
<script>
(function () {
    var SR = window.SpeechRecognition || window.webkitSpeechRecognition;
    if (!SR) return; // Firefox / older browsers — graceful no-op.

    function showToast(text) {
        var t = document.createElement('div');
        t.className = 'vs-toast';
        t.textContent = text;
        document.body.appendChild(t);
        requestAnimationFrame(function () { t.classList.add('show'); });
        setTimeout(function () {
            t.classList.remove('show');
            setTimeout(function () { t.remove(); }, 250);
        }, 2200);
    }

    function attach(input) {
        if (input.dataset.vsAttached) return;
        input.dataset.vsAttached = '1';

        // Make sure the parent can position the absolute mic button.
        var parent = input.parentElement;
        if (parent && getComputedStyle(parent).position === 'static') {
            parent.style.position = 'relative';
        }

        var btn = document.createElement('button');
        btn.type = 'button';
        btn.className = 'vs-mic-btn';
        btn.setAttribute('aria-label', 'Search with your voice');
        btn.title = 'Search with your voice';
        btn.innerHTML = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2a3 3 0 0 0-3 3v7a3 3 0 0 0 6 0V5a3 3 0 0 0-3-3z"/><path d="M19 10v2a7 7 0 0 1-14 0v-2"/><line x1="12" y1="19" x2="12" y2="23"/><line x1="8" y1="23" x2="16" y2="23"/></svg>';
        input.insertAdjacentElement('afterend', btn);

        // SpeechRecognition needs a full BCP-47 locale ("en-US"), not just
        // "en" — the latter throws "language-not-supported" silently on
        // some Chrome builds. Map common 2-letter codes to a sensible
        // regional default, fall back to en-US otherwise.
        var pageLang = (document.documentElement.lang || '').toLowerCase();
        var langMap  = { en: 'en-US', es: 'es-ES', fr: 'fr-FR', de: 'de-DE', hi: 'hi-IN', ur: 'ur-PK', ar: 'ar-SA', pt: 'pt-BR' };
        var sttLang  = pageLang.includes('-') ? pageLang : (langMap[pageLang.slice(0, 2)] || 'en-US');

        var rec = new SR();
        rec.lang = sttLang;
        rec.interimResults = false;
        rec.maxAlternatives = 1;
        rec.continuous = false;

        var listening = false;
        btn.addEventListener('click', function (ev) {
            ev.preventDefault();
            ev.stopPropagation();
            if (listening) {
                try { rec.stop(); } catch (e) { /* noop */ }
                return;
            }
            try {
                rec.start();
            } catch (e) {
                // InvalidStateError = already running. Otherwise show toast.
                if (String(e.name) !== 'InvalidStateError') {
                    showToast('Voice search unavailable');
                    console.error('[voice-search]', e);
                }
            }
        });

        rec.onstart = function () {
            listening = true;
            btn.classList.add('is-listening');
            showToast('Listening — speak now');
        };
        rec.onend = function () {
            listening = false;
            btn.classList.remove('is-listening');
        };
        rec.onerror = function (e) {
            listening = false;
            btn.classList.remove('is-listening');
            console.warn('[voice-search] error:', e.error, e.message || '');
            switch (e.error) {
                case 'not-allowed':
                case 'service-not-allowed':
                    showToast('Mic blocked — check browser & macOS settings');
                    break;
                case 'no-speech':
                    showToast("Didn't catch that — try again");
                    break;
                case 'audio-capture':
                    showToast('No microphone detected');
                    break;
                case 'network':
                    showToast('Network error — voice needs internet');
                    break;
                case 'language-not-supported':
                    showToast('Language not supported');
                    break;
                case 'aborted':
                    /* user-initiated, no toast */
                    break;
                default:
                    showToast('Voice search failed: ' + e.error);
            }
        };
        rec.onresult = function (e) {
            var transcript = (e.results[0][0].transcript || '').trim();
            if (!transcript) return;
            input.value = transcript;
            input.dispatchEvent(new Event('input', { bubbles: true }));
            // Auto-submit the form if the input lives inside one.
            var form = input.closest('form');
            if (form) {
                // Slight delay so the user sees the transcribed text first.
                setTimeout(function () { form.submit(); }, 250);
            }
        };
    }

    function init() {
        document.querySelectorAll('input[data-voice-search]').forEach(attach);
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
</script>
