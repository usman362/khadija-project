<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Privacy Policy - {{ config('app.name', 'GIGS') }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        :root {
            --primary: #3b82f6;
            --primary-dark: #2563eb;
            --accent: #8b5cf6;
            --bg-dark: #0b0f1a;
            --bg-section: #0f1629;
            --bg-card: #151d35;
            --bg-card-hover: #1a2440;
            --text-white: #ffffff;
            --text-light: #c8cdd8;
            --text-muted: #7a829a;
            --border-color: #1e2d4a;
            --gradient-start: #3b82f6;
            --gradient-end: #8b5cf6;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            background: var(--bg-dark);
            color: var(--text-white);
            line-height: 1.6;
            overflow-x: hidden;
        }

        a { text-decoration: none; color: var(--primary); }
        a:hover { text-decoration: underline; }
        img { max-width: 100%; height: auto; }

        .container {
            max-width: 900px;
            margin: 0 auto;
            padding: 0 24px;
        }

        /* Header */
        header {
            position: sticky;
            top: 0;
            z-index: 100;
            background: rgba(11, 15, 26, 0.95);
            backdrop-filter: blur(20px);
            border-bottom: 1px solid var(--border-color);
        }

        header .container {
            display: flex;
            align-items: center;
            justify-content: space-between;
            height: 64px;
        }

        .header-brand {
            font-size: 1.375rem;
            font-weight: 800;
            background: linear-gradient(135deg, var(--gradient-start), var(--gradient-end));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .header-brand a {
            color: inherit;
            -webkit-text-fill-color: transparent;
        }

        .header-brand a:hover {
            text-decoration: none;
        }

        .header-nav a {
            color: var(--text-light);
            font-weight: 500;
            transition: color 0.2s;
        }

        .header-nav a:hover {
            color: var(--text-white);
            text-decoration: none;
        }

        /* Main Content */
        main {
            padding: 48px 0;
        }

        h1 {
            font-size: 2.5rem;
            font-weight: 800;
            margin-bottom: 12px;
            line-height: 1.2;
        }

        .policy-date {
            color: var(--text-muted);
            font-size: 0.95rem;
            margin-bottom: 32px;
        }

        h2 {
            font-size: 1.5rem;
            font-weight: 700;
            margin-top: 32px;
            margin-bottom: 16px;
            color: var(--text-white);
        }

        h3 {
            font-size: 1.125rem;
            font-weight: 600;
            margin-top: 20px;
            margin-bottom: 12px;
            color: var(--text-light);
        }

        p {
            color: var(--text-light);
            margin-bottom: 16px;
        }

        ul, ol {
            margin: 16px 0 16px 24px;
            color: var(--text-light);
        }

        li {
            margin-bottom: 8px;
        }

        .highlight-box {
            background: var(--bg-card);
            border-left: 4px solid var(--primary);
            padding: 20px;
            margin: 24px 0;
            border-radius: 4px;
        }

        .highlight-box p {
            margin: 0;
            color: var(--text-light);
        }

        /* Footer */
        footer {
            background: var(--bg-section);
            border-top: 1px solid var(--border-color);
            padding: 32px 0;
            margin-top: 64px;
        }

        footer .container {
            text-align: center;
        }

        .footer-text {
            color: var(--text-muted);
            font-size: 0.9rem;
        }

        .footer-text a {
            color: var(--primary);
        }

        /* Responsive */
        @media (max-width: 640px) {
            h1 {
                font-size: 1.875rem;
            }

            h2 {
                font-size: 1.25rem;
            }

            main {
                padding: 32px 0;
            }
        }
    </style>
</head>
<body>
    <header>
        <div class="container">
            <div class="header-brand">
                <a href="/">{{ config('app.name', 'GIGS') }}</a>
            </div>
            <nav class="header-nav">
                <a href="/">← Back</a>
            </nav>
        </div>
    </header>

    <main>
        <div class="container">
            <h1>Privacy Policy</h1>
            <p class="policy-date">Last updated: {{ now()->format('F j, Y') }}</p>

            <h2>1. Introduction</h2>
            <p>
                {{ config('app.name', 'GIGS') }} ("we," "us," "our," or "Company") is committed to protecting your privacy. This Privacy Policy explains how we collect, use, disclose, and safeguard your information when you use our platform, including our website and mobile application.
            </p>

            <h2>2. Information We Collect</h2>

            <h3>Account Information</h3>
            <p>
                When you create an account, we collect information such as your name, email address, phone number, profile picture, and business details. For service providers, we may collect information about your services, experience, and certifications.
            </p>

            <h3>Booking and Transaction Information</h3>
            <p>
                We collect information about events you create, bookings you make, and services you request or provide. This includes event details, dates, locations, pricing, and communication records related to transactions.
            </p>

            <h3>Payment Information</h3>
            <p>
                Payment details are processed securely through third-party payment processors. We do not store complete credit card information on our servers. We retain transaction history and billing information for accounting and dispute resolution purposes.
            </p>

            <h3>Communication Information</h3>
            <p>
                We collect and store messages, emails, and chat communications between users on our platform. This includes customer support inquiries, feedback, and any attachments shared during communications.
            </p>

            <h3>Device and Usage Information</h3>
            <p>
                We automatically collect information about your device (including device type, operating system, and unique device identifiers) and how you interact with our platform (including pages visited, features used, and actions taken). This data is collected through cookies, local storage, and similar tracking technologies.
            </p>

            <h3>Location Information</h3>
            <p>
                If you grant permission, we may collect your precise location data. We primarily use this to help you find services in your area and to enable location-based features.
            </p>

            <h2>3. How We Use Your Information</h2>
            <p>We use collected information for the following purposes:</p>
            <ul>
                <li>Providing and improving our services</li>
                <li>Processing transactions and sending related information</li>
                <li>Sending transactional emails (confirmations, receipts, service updates)</li>
                <li>Personalizing your experience and recommendations</li>
                <li>Communicating with you about updates, promotions, and new features</li>
                <li>Responding to inquiries and providing customer support</li>
                <li>Conducting research and analytics to improve our platform</li>
                <li>Enforcing our Terms of Service and other agreements</li>
                <li>Detecting and preventing fraud, abuse, and security incidents</li>
                <li>Complying with legal obligations and regulatory requirements</li>
            </ul>

            <h2>4. Sharing Your Information</h2>

            <h3>Between Users</h3>
            <p>
                To facilitate bookings and transactions, we share relevant information between clients and service providers (such as contact details, event requirements, and service specifications). Users can control the visibility of their profile information through account settings.
            </p>

            <h3>Third-Party Service Providers</h3>
            <p>
                We share information with vendors who assist us in operating our platform, including payment processors, cloud storage providers, email service providers, and analytics companies. These providers are contractually obligated to use your information only as necessary to provide services to us.
            </p>

            <h3>Legal Requirements</h3>
            <p>
                We may disclose information when required by law, government requests, or to protect our legal rights, property, and safety.
            </p>

            <h3>Business Transfers</h3>
            <p>
                In the event of a merger, acquisition, or bankruptcy, your information may be transferred as part of that transaction. We will notify you of any such change and any choices you may have regarding your information.
            </p>

            <h2>5. Cookies and Tracking Technologies</h2>
            <p>
                We use cookies, web beacons, pixels, and similar tracking technologies to enhance your experience, remember your preferences, and analyze platform usage. These include:
            </p>
            <ul>
                <li><strong>Essential Cookies:</strong> Required for basic functionality and security</li>
                <li><strong>Functional Cookies:</strong> Remember your preferences and settings</li>
                <li><strong>Analytics Cookies:</strong> Help us understand how users interact with our platform</li>
                <li><strong>Marketing Cookies:</strong> Used to deliver personalized advertisements</li>
            </ul>
            <p>
                You can control cookie preferences through your browser settings. However, disabling cookies may limit platform functionality.
            </p>

            <h2>6. Data Security</h2>
            <p>
                We implement industry-standard security measures, including SSL/TLS encryption, secure password hashing, and access controls, to protect your information from unauthorized access, alteration, and disclosure. However, no method of transmission over the internet is completely secure, and we cannot guarantee absolute security.
            </p>

            <div class="highlight-box">
                <p><strong>Note:</strong> You are responsible for maintaining the confidentiality of your password. If you believe your account has been compromised, please contact us immediately.</p>
            </div>

            <h2>7. Data Retention</h2>
            <p>
                We retain personal information as long as necessary to provide our services, comply with legal obligations, and resolve disputes. You can request deletion of your account and associated data at any time, subject to legal and operational requirements. Some information may be retained for tax, legal, or business purposes even after account deletion.
            </p>

            <h2>8. Your Privacy Rights</h2>
            <p>Depending on your location, you may have the following rights:</p>
            <ul>
                <li><strong>Right to Access:</strong> Request a copy of your personal information</li>
                <li><strong>Right to Correction:</strong> Update or correct inaccurate information</li>
                <li><strong>Right to Deletion:</strong> Request deletion of your data (subject to legal requirements)</li>
                <li><strong>Right to Opt-Out:</strong> Unsubscribe from marketing communications and data processing</li>
                <li><strong>Right to Data Portability:</strong> Receive your data in a portable format</li>
                <li><strong>Right to Object:</strong> Object to certain types of data processing</li>
            </ul>

            <h2>9. Third-Party Links and Services</h2>
            <p>
                Our platform may contain links to third-party websites and services. This Privacy Policy does not apply to third-party sites, and we are not responsible for their privacy practices. We encourage you to review their privacy policies before providing any personal information.
            </p>

            <h2>10. Children's Privacy</h2>
            <p>
                {{ config('app.name', 'GIGS') }} is not intended for users under 18 years of age. We do not knowingly collect information from children. If we become aware that we have collected information from a child, we will delete it promptly.
            </p>

            <h2>11. International Data Transfers</h2>
            <p>
                Your information may be transferred to, stored in, and processed in countries other than your country of residence. These countries may have different data protection laws. By using our platform, you consent to the transfer of your information as described in this Privacy Policy.
            </p>

            <h2>12. Changes to This Privacy Policy</h2>
            <p>
                We may update this Privacy Policy periodically to reflect changes in our practices, technology, and legal requirements. Your continued use of the platform after changes constitutes your acceptance of the updated Privacy Policy. We will notify you of significant changes via email or a prominent notice on our platform.
            </p>

            <h2>13. Contact Us</h2>
            <p>
                If you have questions about this Privacy Policy, wish to exercise your privacy rights, or have concerns about our privacy practices, please contact us at:
            </p>
            <ul>
                <li><strong>Email:</strong> privacy@{{ parse_url(config('app.url'), PHP_URL_HOST) }}</li>
                <li><strong>Mailing Address:</strong> {{ config('app.name', 'GIGS') }} Support Team</li>
            </ul>

            <div class="highlight-box">
                <p>
                    We will respond to all privacy inquiries within 30 days. For unresolved privacy concerns, you may have the right to lodge a complaint with your local data protection authority.
                </p>
            </div>
        </div>
    </main>

    <footer>
        <div class="container">
            <p class="footer-text">&copy; {{ date('Y') }} {{ config('app.name', 'GIGS') }}. All rights reserved.</p>
        </div>
    </footer>
</body>
</html>
