<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', config('app.name'))</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: #f1f5f9;
            color: #1e293b;
            margin: 0;
            padding: 0;
            line-height: 1.6;
        }
        .wrapper {
            width: 100%;
            padding: 40px 20px;
        }
        .container {
            max-width: 600px;
            margin: 0 auto;
            background: #ffffff;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 4px 24px rgba(0,0,0,0.06);
        }
        .header {
            padding: 32px 40px 20px;
            text-align: center;
            border-bottom: 1px solid #e2e8f0;
        }
        .header-logo {
            font-size: 24px;
            font-weight: 800;
            color: #3b82f6;
            letter-spacing: -0.5px;
            text-decoration: none;
        }
        .content {
            padding: 32px 40px;
        }
        .content h1 {
            font-size: 22px;
            font-weight: 700;
            color: #0f172a;
            margin: 0 0 16px;
        }
        .content p {
            font-size: 15px;
            color: #475569;
            margin: 0 0 16px;
        }
        .content p strong { color: #0f172a; }

        .banner {
            padding: 14px 18px;
            border-radius: 10px;
            font-size: 14px;
            font-weight: 600;
            margin-bottom: 24px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .banner-success { background: #ecfdf5; border-left: 4px solid #10b981; color: #047857; }
        .banner-warning { background: #fffbeb; border-left: 4px solid #f59e0b; color: #b45309; }
        .banner-info    { background: #eff6ff; border-left: 4px solid #3b82f6; color: #1d4ed8; }
        .banner-danger  { background: #fef2f2; border-left: 4px solid #ef4444; color: #b91c1c; }

        .details-box {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            padding: 20px 24px;
            margin: 24px 0;
        }
        .details-row {
            display: table;
            width: 100%;
            padding: 8px 0;
            border-bottom: 1px solid #e2e8f0;
        }
        .details-row:last-child { border-bottom: none; }
        .details-label {
            display: table-cell;
            font-size: 13px;
            color: #64748b;
            font-weight: 500;
            width: 45%;
        }
        .details-value {
            display: table-cell;
            font-size: 14px;
            color: #0f172a;
            font-weight: 600;
            text-align: right;
        }
        .details-value-big {
            font-size: 20px;
            color: #3b82f6;
        }

        .cta-button {
            display: inline-block;
            padding: 14px 32px;
            background: linear-gradient(135deg, #3b82f6, #8b5cf6);
            color: #ffffff !important;
            text-decoration: none;
            border-radius: 10px;
            font-weight: 700;
            font-size: 15px;
            margin: 16px 0 8px;
        }

        .footer {
            padding: 24px 40px 32px;
            text-align: center;
            background: #f8fafc;
            border-top: 1px solid #e2e8f0;
        }
        .footer p {
            font-size: 12px;
            color: #94a3b8;
            margin: 0 0 8px;
        }
        .footer a { color: #3b82f6; text-decoration: none; }
    </style>
</head>
<body>
    <div class="wrapper">
        <div class="container">
            <div class="header">
                <a href="{{ url('/') }}" class="header-logo">{{ config('app.name') }}</a>
            </div>

            <div class="content">
                @yield('content')
            </div>

            <div class="footer">
                <p>This is an automated message from {{ config('app.name') }}.</p>
                <p>
                    <a href="{{ url('/') }}">Visit Website</a> &nbsp;·&nbsp;
                    <a href="{{ url('/privacy-policy') }}">Privacy Policy</a>
                </p>
                <p>&copy; {{ date('Y') }} {{ config('app.name') }}. All rights reserved.</p>
            </div>
        </div>
    </div>
</body>
</html>
