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
    .vs-mic-btn {
        position: absolute;
        right: 110px;
        top: 50%;
        transform: translateY(-50%);
        width: 38px;
        height: 38px;
        border-radius: 50%;
        background: rgba(99, 102, 241, 0.1);
        border: none;
        color: #6366f1;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        transition: background 0.15s, transform 0.15s;
        z-index: 2;
    }
    .vs-mic-btn:hover { background: rgba(99, 102, 241, 0.18); }
    .vs-mic-btn:focus-visible {
        outline: 2px solid #6366f1;
        outline-offset: 2px;
    }
    .vs-mic-btn.is-listening {
        background: #ef4444;
        color: #fff;
        animation: vsMicPulse 1.2s ease-in-out infinite;
    }
    .vs-mic-btn svg { width: 18px; height: 18px; }
    @keyframes vsMicPulse {
        0%, 100% { transform: translateY(-50%) scale(1); box-shadow: 0 0 0 0 rgba(239, 68, 68, 0.5); }
        50%      { transform: translateY(-50%) scale(1.08); box-shadow: 0 0 0 8px rgba(239, 68, 68, 0); }
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

        var rec = new SR();
        rec.lang = document.documentElement.lang || 'en-US';
        rec.interimResults = false;
        rec.maxAlternatives = 1;

        var listening = false;
        btn.addEventListener('click', function () {
            if (listening) { rec.stop(); return; }
            try { rec.start(); } catch (e) { /* already running */ }
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
            if (e.error === 'not-allowed' || e.error === 'service-not-allowed') {
                showToast('Microphone access denied');
            } else if (e.error === 'no-speech') {
                showToast("Didn't catch that — try again");
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
