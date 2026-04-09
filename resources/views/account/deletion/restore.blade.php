<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Account Scheduled for Deletion - {{ config('app.name') }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            background: #0a0e1a;
            color: #ffffff;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
            line-height: 1.6;
        }
        .restore-card {
            max-width: 560px;
            width: 100%;
            background: #111827;
            border: 1px solid rgba(239,68,68,0.25);
            border-radius: 20px;
            padding: 48px 44px;
            text-align: center;
            box-shadow: 0 20px 60px rgba(0,0,0,0.4);
        }
        .warn-icon {
            width: 80px; height: 80px;
            margin: 0 auto 24px;
            border-radius: 50%;
            background: rgba(239,68,68,0.12);
            border: 2px solid rgba(239,68,68,0.3);
            display: flex; align-items: center; justify-content: center;
            color: #ef4444;
        }
        .warn-icon svg { width: 42px; height: 42px; }
        h1 {
            font-size: 1.75rem;
            font-weight: 800;
            margin-bottom: 12px;
            color: #fff;
        }
        .sub {
            color: #94a3b8;
            margin-bottom: 28px;
            font-size: 0.95rem;
        }
        .countdown-box {
            background: rgba(239,68,68,0.08);
            border: 1px solid rgba(239,68,68,0.2);
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 24px;
        }
        .countdown-big {
            font-size: 3rem;
            font-weight: 800;
            color: #ef4444;
            line-height: 1;
            margin-bottom: 4px;
        }
        .countdown-label {
            font-size: 0.82rem;
            color: #94a3b8;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        .scheduled-date {
            font-size: 0.85rem;
            color: #cbd5e1;
            margin-top: 10px;
        }
        .scheduled-date strong { color: #fff; }
        .info-list {
            background: rgba(255,255,255,0.03);
            border: 1px solid rgba(255,255,255,0.08);
            border-radius: 12px;
            padding: 20px 24px;
            margin-bottom: 28px;
            text-align: left;
        }
        .info-list p {
            font-size: 0.85rem;
            color: #cbd5e1;
            margin-bottom: 10px;
            display: flex;
            align-items: flex-start;
            gap: 10px;
        }
        .info-list p:last-child { margin-bottom: 0; }
        .info-list svg { width: 16px; height: 16px; color: #6366f1; flex-shrink: 0; margin-top: 2px; }
        .actions { display: flex; gap: 12px; flex-direction: column; }
        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            padding: 14px 28px;
            border-radius: 10px;
            font-size: 0.95rem;
            font-weight: 700;
            cursor: pointer;
            border: none;
            transition: all 0.2s;
            text-decoration: none;
            font-family: inherit;
        }
        .btn-restore {
            background: linear-gradient(135deg, #10b981, #059669);
            color: #fff;
            box-shadow: 0 4px 20px rgba(16,185,129,0.25);
        }
        .btn-restore:hover { transform: translateY(-2px); box-shadow: 0 8px 30px rgba(16,185,129,0.35); }
        .btn-logout {
            background: transparent;
            border: 1.5px solid rgba(255,255,255,0.15);
            color: #cbd5e1;
        }
        .btn-logout:hover { border-color: rgba(255,255,255,0.3); background: rgba(255,255,255,0.05); }
        .btn svg { width: 18px; height: 18px; }
        .status-flash {
            background: rgba(16,185,129,0.1);
            border: 1px solid rgba(16,185,129,0.3);
            color: #10b981;
            padding: 12px 16px;
            border-radius: 10px;
            font-size: 0.88rem;
            margin-bottom: 20px;
        }
        .error-flash {
            background: rgba(239,68,68,0.1);
            border: 1px solid rgba(239,68,68,0.3);
            color: #f87171;
            padding: 12px 16px;
            border-radius: 10px;
            font-size: 0.88rem;
            margin-bottom: 20px;
        }
        /* ── Reactivation Fee Box ── */
        .fee-box {
            background: rgba(99,102,241,0.08);
            border: 1px solid rgba(99,102,241,0.25);
            border-radius: 12px;
            padding: 20px 24px;
            margin-bottom: 24px;
            text-align: center;
        }
        .fee-label {
            font-size: 0.78rem;
            color: #94a3b8;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 6px;
        }
        .fee-amount {
            font-size: 2rem;
            font-weight: 800;
            color: #a5b4fc;
            line-height: 1;
        }
        .fee-desc {
            font-size: 0.82rem;
            color: #94a3b8;
            margin-top: 10px;
            line-height: 1.5;
        }
        /* Payment method buttons */
        .btn-stripe {
            background: #635bff;
            color: #fff;
            border: none;
            box-shadow: 0 4px 20px rgba(99,91,255,0.25);
        }
        .btn-stripe:hover { background: #524bdb; transform: translateY(-2px); box-shadow: 0 8px 30px rgba(99,91,255,0.35); }
        .btn-paypal {
            background: #ffc439;
            color: #003087;
            border: none;
            font-weight: 800;
        }
        .btn-paypal:hover { background: #ffb300; transform: translateY(-2px); }
        .pay-divider {
            text-align: center;
            font-size: 0.75rem;
            color: #64748b;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin: 4px 0;
            position: relative;
        }
        .pay-divider::before,
        .pay-divider::after {
            content: '';
            position: absolute;
            top: 50%;
            width: 40%;
            height: 1px;
            background: rgba(255,255,255,0.08);
        }
        .pay-divider::before { left: 0; }
        .pay-divider::after  { right: 0; }
    </style>
</head>
<body>

<div class="restore-card">
    @if(session('status'))
        <div class="status-flash">{{ session('status') }}</div>
    @endif

    <div class="warn-icon">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/>
            <line x1="12" y1="9" x2="12" y2="13"/>
            <line x1="12" y1="17" x2="12.01" y2="17"/>
        </svg>
    </div>

    <h1>Your Account is Scheduled for Deletion</h1>
    <p class="sub">Hi <strong style="color:#fff;">{{ $user->name }}</strong> — you have some time to change your mind.</p>

    <div class="countdown-box">
        <div class="countdown-big">{{ $user->daysUntilDeletion() }}</div>
        <div class="countdown-label">Days Remaining</div>
        <div class="scheduled-date">
            Permanent deletion on <strong>{{ $user->deletion_scheduled_at->format('F j, Y \a\t g:i A') }}</strong>
        </div>
    </div>

    <div class="info-list">
        <p>
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
            During this period your account is locked — you can only restore or sign out.
        </p>
        <p>
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
            No new messages, bookings, or actions will be possible until you restore.
        </p>
        <p>
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/></svg>
            After {{ $user->daysUntilDeletion() }} days, your profile, avatar, and personal data will be permanently removed.
        </p>
    </div>

    @if(session('error'))
        <div class="error-flash">{{ session('error') }}</div>
    @endif

    @if($feeEnabled)
        <div class="fee-box">
            <div class="fee-label">Reactivation Fee</div>
            <div class="fee-amount">{{ $currency }} {{ number_format($reactivationFee, 2) }}</div>
            <div class="fee-desc">
                A one-time fee is required to cancel deletion and restore your account.<br>
                Choose your preferred payment method below.
            </div>
        </div>
    @endif

    <div class="actions">
        @if($feeEnabled)
            {{-- Stripe payment --}}
            <form action="{{ route('account.deletion.restore') }}" method="POST">
                @csrf
                <input type="hidden" name="gateway" value="stripe">
                <button type="submit" class="btn btn-stripe" style="width:100%;">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="5" width="20" height="14" rx="2"/><line x1="2" y1="10" x2="22" y2="10"/></svg>
                    Pay with Card (Stripe)
                </button>
            </form>

            <div class="pay-divider">or</div>

            {{-- PayPal payment --}}
            <form action="{{ route('account.deletion.restore') }}" method="POST">
                @csrf
                <input type="hidden" name="gateway" value="paypal">
                <button type="submit" class="btn btn-paypal" style="width:100%;">
                    Pay with PayPal
                </button>
            </form>
        @else
            {{-- Free restore (fee disabled by admin) --}}
            <form action="{{ route('account.deletion.restore') }}" method="POST">
                @csrf
                <button type="submit" class="btn btn-restore" style="width:100%;">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="23 4 23 10 17 10"/><path d="M20.49 15a9 9 0 1 1-2.12-9.36L23 10"/></svg>
                    Restore My Account
                </button>
            </form>
        @endif

        <form action="{{ route('logout') }}" method="POST">
            @csrf
            <button type="submit" class="btn btn-logout" style="width:100%;">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
                Sign Out
            </button>
        </form>
    </div>
</div>

</body>
</html>
