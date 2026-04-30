{{--
    Inline form-validation utility — self-contained.
    Drop this partial into any page that wants live client-side validation:

        @include('partials._form_validation')

    Mark inputs with data attributes — no per-form JS needed:

        <input type="email" name="email"
               data-validate="required|email"
               data-error-required="Email is required"
               data-error-email="Enter a valid email address">

    Supported rules (pipe-separated):
      required             — non-empty after trim
      email                — basic RFC-style email regex
      min:N                — min character length
      max:N                — max character length
      match:<otherName>    — must equal value of input[name=otherName]
      url                  — http/https URL
      tel                  — phone number (digits + spaces + - + ( ) + at least 7 digits)
      pattern:<regex>      — custom regex

    Behavior:
      • On blur, validate the field and show red border + message
      • On input, clear errors so the user sees the field "heal"
      • On submit, validate every field — if any fail, prevent submit + focus first
      • aria-invalid + aria-describedby wired automatically for screen readers
--}}

<style>
    /* Inline validation styling — works for any input/textarea/select that
       carries a `data-validate` attribute. */
    .fv-field { position: relative; }
    .fv-error-message {
        display: none;
        align-items: center;
        gap: 6px;
        margin-top: 6px;
        padding-left: 4px;
        font-size: 12.5px;
        font-weight: 600;
        color: #f87171;
        line-height: 1.4;
    }
    .fv-error-message svg {
        width: 13px; height: 13px;
        flex-shrink: 0;
    }
    .fv-field.is-invalid .fv-error-message { display: inline-flex; }

    /* Bring red border into common input classes when JS marks them invalid.
       The selectors are intentionally broad so this works on auth forms,
       profile forms, AI tool forms, etc. without per-form CSS. */
    .fv-field.is-invalid input,
    .fv-field.is-invalid textarea,
    .fv-field.is-invalid select {
        border-color: #ef4444 !important;
        box-shadow: 0 0 0 3px rgba(239, 68, 68, 0.10) !important;
    }
    .fv-field.is-valid input,
    .fv-field.is-valid textarea,
    .fv-field.is-valid select {
        border-color: rgba(34, 197, 94, 0.45) !important;
    }
</style>

<script>
/* ─── Inline form validation ──────────────────────────────────────
   Runs independently of any framework. Looks for [data-validate]
   inputs on the page, then handles blur/input/submit lifecycle.
   Server-side validation still runs on submit — this is purely UX. */
(function () {
    'use strict';
    if (window.__fvInitialized) return;        // include once even if partial included twice
    window.__fvInitialized = true;

    /* Available rule implementations. Each gets (value, ruleArg, input)
       and returns true (valid) or false (invalid). */
    var RULES = {
        required: function (v) { return v.trim().length > 0; },
        email:    function (v) { return /^[^\s@]+@[^\s@]+\.[^\s@]{2,}$/.test(v.trim()); },
        url:      function (v) { return /^https?:\/\/.+/i.test(v.trim()); },
        tel:      function (v) {
            var digits = v.replace(/\D/g, '');
            return digits.length >= 7 && /^[\d\s\-+()]+$/.test(v.trim());
        },
        min:      function (v, n) { return v.length >= parseInt(n, 10); },
        max:      function (v, n) { return v.length <= parseInt(n, 10); },
        match:    function (v, name, input) {
            var other = input.form && input.form.querySelector('[name="' + name + '"]');
            return other ? v === other.value : true;
        },
        pattern:  function (v, regex) {
            try { return new RegExp(regex).test(v); } catch (e) { return true; }
        },
    };

    /* Friendly default messages so a form can opt into validation
       without specifying every error string. */
    var DEFAULT_MESSAGES = {
        required: 'This field is required.',
        email:    'Please enter a valid email address.',
        url:      'Please enter a valid URL.',
        tel:      'Please enter a valid phone number.',
        min:      'Must be at least %d characters.',
        max:      'Must be at most %d characters.',
        match:    'Values do not match.',
        pattern:  'Invalid format.',
    };

    function getRules(input) {
        var raw = (input.getAttribute('data-validate') || '').trim();
        if (!raw) return [];
        return raw.split('|').map(function (s) {
            var parts = s.split(':');
            return { name: parts[0].trim(), arg: parts.slice(1).join(':') };
        });
    }

    function getMessage(input, ruleName, ruleArg) {
        var attr = input.getAttribute('data-error-' + ruleName);
        if (attr) return attr;
        var def = DEFAULT_MESSAGES[ruleName] || 'Invalid value.';
        return def.replace('%d', ruleArg);
    }

    /* Wrap each validated input in a .fv-field container (if not already)
       and inject the error-message node. Idempotent. */
    function ensureFieldChrome(input) {
        var field = input.closest('.fv-field');
        if (!field) {
            // If the input is inside a wrapper (like .form-input-wrap or
            // .password-wrapper), use that as the field; otherwise wrap.
            var existingWrap = input.parentElement;
            if (existingWrap && existingWrap.children.length === 1) {
                existingWrap.classList.add('fv-field');
                field = existingWrap;
            } else {
                field = document.createElement('div');
                field.className = 'fv-field';
                input.parentNode.insertBefore(field, input);
                field.appendChild(input);
            }
        }

        if (!field.querySelector('.fv-error-message')) {
            var msg = document.createElement('div');
            msg.className = 'fv-error-message';
            msg.setAttribute('role', 'alert');
            msg.setAttribute('aria-live', 'polite');
            msg.id = 'fv-err-' + Math.random().toString(36).slice(2, 9);
            msg.innerHTML = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg><span></span>';
            field.appendChild(msg);
            input.setAttribute('aria-describedby', (input.getAttribute('aria-describedby') || '').trim() + ' ' + msg.id);
        }
        return field;
    }

    /* Run all rules attached to an input. Returns first error message or null. */
    function validateInput(input) {
        var v = input.value || '';
        var rules = getRules(input);
        for (var i = 0; i < rules.length; i++) {
            var r = rules[i];
            var fn = RULES[r.name];
            if (!fn) continue;
            // 'required' is the only rule that should fire on empty values.
            // Other rules skip empty values so optional fields don't error
            // until the user actually types something.
            if (r.name !== 'required' && v.trim() === '') continue;
            if (!fn(v, r.arg, input)) {
                return getMessage(input, r.name, r.arg);
            }
        }
        return null;
    }

    function setFieldState(input, errorMsg) {
        var field = ensureFieldChrome(input);
        var msgEl = field.querySelector('.fv-error-message span');
        if (errorMsg) {
            field.classList.add('is-invalid');
            field.classList.remove('is-valid');
            if (msgEl) msgEl.textContent = errorMsg;
            input.setAttribute('aria-invalid', 'true');
        } else {
            field.classList.remove('is-invalid');
            // Only mark valid if user has interacted (non-empty)
            if ((input.value || '').trim() !== '') field.classList.add('is-valid');
            input.setAttribute('aria-invalid', 'false');
        }
    }

    function bindInput(input) {
        ensureFieldChrome(input);
        input.addEventListener('blur', function () {
            setFieldState(input, validateInput(input));
        });
        input.addEventListener('input', function () {
            // While typing, clear an existing error eagerly so the field "heals"
            var field = input.closest('.fv-field');
            if (field && field.classList.contains('is-invalid')) {
                if (!validateInput(input)) setFieldState(input, null);
            }
        });
    }

    /* Form-level submit: validate every input; if any fail, block submit
       and focus the first invalid field. */
    function bindForm(form) {
        if (form.__fvBound) return;
        form.__fvBound = true;
        form.addEventListener('submit', function (e) {
            var invalidInputs = [];
            form.querySelectorAll('[data-validate]').forEach(function (input) {
                var err = validateInput(input);
                if (err) {
                    setFieldState(input, err);
                    invalidInputs.push(input);
                }
            });
            if (invalidInputs.length) {
                e.preventDefault();
                invalidInputs[0].focus();
                invalidInputs[0].scrollIntoView({ behavior: 'smooth', block: 'center' });
            }
        });
    }

    function init() {
        var inputs = document.querySelectorAll('[data-validate]');
        var forms  = new Set();
        inputs.forEach(function (input) {
            bindInput(input);
            if (input.form) forms.add(input.form);
        });
        forms.forEach(bindForm);
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
</script>
