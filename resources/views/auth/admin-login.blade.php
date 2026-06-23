<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Admin Login — GigResource</title>
    <link rel="icon" type="image/png" href="{{ asset('gigresource-logos/gigresource-icon.png') }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    @php
        $recaptchaSettings = app(\App\Domain\Settings\Services\SettingsService::class);
        $showRecaptcha = $recaptchaSettings->isRecaptchaEnabledFor('login');
        $recaptchaSiteKey = $recaptchaSettings->getRecaptchaSiteKey();
        $recaptchaVersion = $recaptchaSettings->get('recaptcha.version', 'v2');
    @endphp
    @if($showRecaptcha && $recaptchaSiteKey)
        @if($recaptchaVersion === 'v3')
            <script src="https://www.google.com/recaptcha/api.js?render={{ $recaptchaSiteKey }}"></script>
        @else
            <script src="https://www.google.com/recaptcha/api.js" async defer></script>
        @endif
    @endif
    <style>
        *,*::before,*::after { box-sizing: border-box; margin: 0; padding: 0; }
        :root { --bg: #0b1220; --card: #131c30; --input: #0f1830; --line: #243247; --text: #e6ebf5; --muted: #8a96ad; --accent: #2563eb; --accent-dark: #1d4ed8; }
        body {
            font-family: 'Inter', system-ui, sans-serif; color: var(--text);
            background:
                radial-gradient(900px 500px at 15% -10%, rgba(37,99,235,0.18), transparent 60%),
                radial-gradient(800px 500px at 100% 110%, rgba(124,58,237,0.16), transparent 60%),
                var(--bg);
            min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 24px;
        }
        a { text-decoration: none; color: inherit; }
        .al-card {
            width: 100%; max-width: 420px; background: var(--card);
            border: 1px solid var(--line); border-radius: 20px;
            box-shadow: 0 30px 70px rgba(0,0,0,.45); padding: 38px 34px;
        }
        .al-brand { display: flex; align-items: center; justify-content: center; margin-bottom: 22px; }
        .al-brand img { height: 42px; }
        .al-badge {
            display: inline-flex; align-items: center; gap: 7px; margin: 0 auto 6px; padding: 5px 13px;
            background: rgba(37,99,235,0.14); border: 1px solid rgba(37,99,235,0.3);
            color: #93b4ff; border-radius: 30px; font-size: 11.5px; font-weight: 700;
            letter-spacing: .06em; text-transform: uppercase;
        }
        .al-head { text-align: center; margin-bottom: 26px; }
        .al-head h1 { font-family: 'Plus Jakarta Sans', sans-serif; font-size: 22px; font-weight: 800; margin-top: 8px; color: #fff; }
        .al-head p { font-size: 13.5px; color: var(--muted); margin-top: 5px; }
        .al-field { margin-bottom: 16px; }
        .al-label { display: block; font-size: 13px; font-weight: 600; color: #c2cbdc; margin-bottom: 7px; }
        .al-wrap { position: relative; }
        .al-ic { position: absolute; left: 13px; top: 50%; transform: translateY(-50%); width: 18px; height: 18px; color: var(--muted); pointer-events: none; }
        .al-input {
            width: 100%; padding: 12px 14px 12px 40px; border: 1.5px solid var(--line);
            border-radius: 11px; background: var(--input); color: var(--text); font-size: 14px; font-family: inherit;
            transition: border-color .15s, box-shadow .15s;
        }
        .al-input::placeholder { color: #5d6b85; }
        .al-input:focus { outline: none; border-color: var(--accent); box-shadow: 0 0 0 3px rgba(37,99,235,0.18); }
        .al-input.is-invalid { border-color: #ef4444; }
        .al-eye { position: absolute; right: 11px; top: 50%; transform: translateY(-50%); background: none; border: none; color: var(--muted); cursor: pointer; padding: 4px; }
        .al-eye:hover { color: var(--text); }
        .al-row { display: flex; align-items: center; justify-content: space-between; margin: 16px 0 20px; font-size: 13px; }
        .al-check { display: flex; align-items: center; gap: 8px; color: #c2cbdc; }
        .al-check input { width: 15px; height: 15px; accent-color: var(--accent); }
        .al-forgot { color: #93b4ff; font-weight: 600; }
        .al-forgot:hover { text-decoration: underline; }
        .al-btn {
            width: 100%; padding: 13px; border: none; border-radius: 11px;
            background: var(--accent); color: #fff; font-family: 'Plus Jakarta Sans', sans-serif;
            font-size: 15px; font-weight: 700; cursor: pointer; transition: background .15s, transform .1s;
        }
        .al-btn:hover { background: var(--accent-dark); transform: translateY(-1px); }
        .al-foot { text-align: center; margin-top: 22px; font-size: 13px; color: var(--muted); }
        .al-foot a { color: #93b4ff; font-weight: 600; }
        .al-foot a:hover { text-decoration: underline; }
        .al-alert { padding: 11px 14px; border-radius: 10px; font-size: 13px; margin-bottom: 18px; }
        .al-alert-error { background: rgba(239,68,68,0.12); border: 1px solid rgba(239,68,68,0.32); color: #fca5a5; }
        .al-alert-success { background: rgba(34,197,94,0.12); border: 1px solid rgba(34,197,94,0.32); color: #86efac; }
        .al-err { color: #fca5a5; font-size: 12.5px; margin-top: 6px; }
    </style>
</head>
<body>
    <div class="al-card">
        <div class="al-brand">
            <a href="{{ url('/') }}"><img src="{{ asset('gigresource-logos/gigresource-logo-dark.png') }}" alt="GigResource"></a>
        </div>
        <div class="al-head">
            <span class="al-badge">
                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
                Admin Portal
            </span>
            <h1>Sign in to Admin</h1>
            <p>Manage the GigResource platform.</p>
        </div>

        @if (session('status'))
            <div class="al-alert al-alert-success">{{ session('status') }}</div>
        @endif
        @if ($errors->any())
            <div class="al-alert al-alert-error">
                @foreach ($errors->all() as $error)
                    <div>{{ $error }}</div>
                @endforeach
            </div>
        @endif

        <form method="POST" action="{{ route('login') }}" id="alForm">
            @csrf

            <div class="al-field">
                <label class="al-label">Email Address</label>
                <div class="al-wrap">
                    <svg class="al-ic" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="4" width="20" height="16" rx="2"/><path d="M22 7l-10 7L2 7"/></svg>
                    <input type="email" name="email" class="al-input {{ $errors->has('email') ? 'is-invalid' : '' }}" value="{{ old('email') }}" placeholder="admin@gigresource.com" required autofocus>
                </div>
            </div>

            <div class="al-field">
                <label class="al-label">Password</label>
                <div class="al-wrap">
                    <svg class="al-ic" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                    <input type="password" name="password" id="alPw" class="al-input" placeholder="Enter your password" required>
                    <button type="button" class="al-eye" onclick="(function(i){i.type=i.type==='password'?'text':'password';})(document.getElementById('alPw'))" aria-label="Show or hide password">
                        <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                    </button>
                </div>
            </div>

            <div class="al-row">
                <label class="al-check"><input type="checkbox" name="remember" {{ old('remember') ? 'checked' : '' }}> Remember me</label>
                @if (Route::has('password.request'))
                    <a href="{{ route('password.request') }}" class="al-forgot">Forgot password?</a>
                @endif
            </div>

            @if($showRecaptcha && $recaptchaSiteKey)
                @if($recaptchaVersion === 'v2')
                    <div style="margin-bottom: 16px;">
                        <div class="g-recaptcha" data-sitekey="{{ $recaptchaSiteKey }}" data-theme="dark"></div>
                        @error('g-recaptcha-response') <div class="al-err">{{ $message }}</div> @enderror
                    </div>
                @else
                    <input type="hidden" name="g-recaptcha-response" id="alRecaptcha">
                    @error('g-recaptcha-response') <div class="al-err">{{ $message }}</div> @enderror
                @endif
            @endif

            <button type="submit" class="al-btn" id="alSubmit">Sign In</button>
        </form>

        <div class="al-foot">
            Not an admin? <a href="{{ route('login') }}">Go to user login</a>
        </div>
    </div>

    @if($showRecaptcha && $recaptchaSiteKey && $recaptchaVersion === 'v3')
    <script>
        document.getElementById('alSubmit').addEventListener('click', function (e) {
            e.preventDefault();
            grecaptcha.ready(function () {
                grecaptcha.execute('{{ $recaptchaSiteKey }}', {action: 'login'}).then(function (token) {
                    document.getElementById('alRecaptcha').value = token;
                    document.getElementById('alForm').submit();
                });
            });
        });
    </script>
    @endif
</body>
</html>
