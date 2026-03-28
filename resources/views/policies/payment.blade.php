<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Policy - {{ config('app.name', 'GIGS') }}</title>
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
            display: flex;
            align-items: center;
        }

        .header-brand a {
            display: flex;
            align-items: center;
        }

        .header-brand img {
            height: 32px;
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

        .table-responsive {
            overflow-x: auto;
            margin: 20px 0;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            color: var(--text-light);
        }

        th {
            background: var(--bg-card);
            padding: 12px;
            text-align: left;
            font-weight: 600;
            border: 1px solid var(--border-color);
            color: var(--text-white);
        }

        td {
            padding: 12px;
            border: 1px solid var(--border-color);
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

            table {
                font-size: 0.9rem;
            }

            th, td {
                padding: 8px;
            }
        }
    </style>
</head>
<body>
    <header>
        <div class="container">
            <div class="header-brand">
                <a href="/"><img src="{{ asset('logos/logo-gradient.png') }}" alt="GigResource"></a>
            </div>
            <nav class="header-nav">
                <a href="/">← Back</a>
            </nav>
        </div>
    </header>

    <main>
        <div class="container">
            <h1>Payment Policy</h1>
            <p class="policy-date">Last updated: {{ now()->format('F j, Y') }}</p>

            <h2>1. Payment Overview</h2>
            <p>
                {{ config('app.name', 'GIGS') }} is a marketplace platform that connects event organizers and clients with qualified service providers. This Payment Policy governs all payment transactions conducted through our platform.
            </p>

            <h2>2. Accepted Payment Methods</h2>
            <p>We accept the following payment methods:</p>
            <ul>
                <li>Credit and Debit Cards (Visa, Mastercard, American Express, Discover)</li>
                <li>PayPal</li>
                <li>Bank Transfers (where applicable)</li>
                <li>Digital Wallets (Apple Pay, Google Pay)</li>
                <li>Other local payment methods as available in your region</li>
            </ul>
            <p>
                All payment processing is handled by secure, PCI-compliant third-party payment processors. We do not store full credit card information on our servers.
            </p>

            <h2>3. Pricing and Fees</h2>

            <h3>Service Pricing</h3>
            <p>
                Service providers set their own rates for services offered on the platform. Pricing is displayed clearly before you confirm a booking. All prices are shown inclusive of applicable taxes, or taxes will be calculated and displayed separately.
            </p>

            <h3>Platform Fees</h3>
            <p>
                {{ config('app.name', 'GIGS') }} charges a service fee on bookings processed through the platform. The fee structure is:
            </p>
            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th>Fee Type</th>
                            <th>Amount</th>
                            <th>Who Pays</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>Booking Service Fee</td>
                            <td>10% of service cost</td>
                            <td>Client</td>
                        </tr>
                        <tr>
                            <td>Payment Processing Fee</td>
                            <td>2.9% + $0.30 per transaction</td>
                            <td>Service Provider</td>
                        </tr>
                        <tr>
                            <td>Subscription Service Fee</td>
                            <td>Varies by plan</td>
                            <td>Service Provider</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <h3>Taxes</h3>
            <p>
                You are responsible for paying any applicable sales, use, VAT, or other taxes. We will calculate and add taxes to your invoice where required by law. Tax treatment varies by location and service type.
            </p>

            <h2>4. Booking Payment Process</h2>

            <h3>Payment Authorization</h3>
            <p>
                When you book a service, your payment method is authorized for the full booking amount plus applicable fees and taxes. The authorization holds the funds temporarily but does not immediately charge your account.
            </p>

            <h3>Payment Settlement</h3>
            <p>
                Payments are processed according to the following timeline:
            </p>
            <ul>
                <li><strong>Client Funds:</strong> Charged upon booking confirmation. Funds are held in escrow until the service is completed or the cancellation period expires.</li>
                <li><strong>Service Provider Payments:</strong> Released to the service provider within 5-7 business days after service completion or cancellation deadline, depending on the booking status and any disputes.</li>
                <li><strong>Refunds:</strong> Processed within 10 business days of approval through the original payment method.</li>
            </ul>

            <h2>5. Invoicing and Receipts</h2>
            <p>
                You will receive:
            </p>
            <ul>
                <li>An order confirmation immediately after booking</li>
                <li>An invoice after payment is processed</li>
                <li>A receipt upon service completion or dispute resolution</li>
                <li>Refund confirmations via email</li>
            </ul>
            <p>
                Invoices and receipts can be accessed through your account dashboard at any time. You can download these documents in PDF format for your records.
            </p>

            <h2>6. Billing Disputes and Chargebacks</h2>

            <h3>Disputing a Charge</h3>
            <p>
                If you believe you were incorrectly charged or believe a charge is fraudulent, contact our support team within 30 days of the transaction. We will investigate and respond within 5 business days.
            </p>

            <h3>Chargebacks</h3>
            <p>
                If you file a chargeback with your card issuer without first contacting us, you may be violating this Payment Policy and could face account suspension or termination. We encourage all parties to resolve disputes through our support process first.
            </p>

            <h3>Resolution Process</h3>
            <ul>
                <li>Contact support with transaction details and reason for dispute</li>
                <li>Provide evidence supporting your claim (screenshots, correspondence, etc.)</li>
                <li>We will review and communicate findings within 14 days</li>
                <li>Disputes are resolved through mediation or refund, as appropriate</li>
            </ul>

            <h2>7. Payment Security</h2>

            <h3>Encryption and Data Protection</h3>
            <p>
                All payments are processed through encrypted, secure connections using industry-standard SSL/TLS protocols. Our payment processors are PCI DSS Level 1 certified, the highest level of security certification.
            </p>

            <h3>Fraud Prevention</h3>
            <p>
                We employ advanced fraud detection and prevention systems to protect your account and transactions. We may require additional verification (such as 3D Secure authentication) for high-risk transactions.
            </p>

            <h3>Your Responsibilities</h3>
            <ul>
                <li>Keep your account credentials confidential</li>
                <li>Monitor your account for unauthorized transactions</li>
                <li>Report suspicious activity immediately</li>
                <li>Do not share payment information through insecure channels</li>
            </ul>

            <div class="highlight-box">
                <p><strong>Important:</strong> We will never ask for your full credit card number, CVV, or password via email. Do not provide this information through unsecured channels.</p>
            </div>

            <h2>8. Payment Holds and Escrow</h2>
            <p>
                Client payments are held in escrow during the booking period. Funds are released to the service provider upon:
            </p>
            <ul>
                <li>Service completion (confirmed by client or provider)</li>
                <li>Expiration of the dispute resolution window (typically 14 days after service)</li>
                <li>Mutual agreement between parties</li>
            </ul>
            <p>
                If a dispute is filed during this period, funds remain on hold until the dispute is resolved.
            </p>

            <h2>9. Cancellations and Refunds</h2>
            <p>
                For detailed information about cancellation policies and refund eligibility, please refer to our separate <a href="/cancellation-policy">Cancellation and Refund Policy</a>.
            </p>
            <p>
                Refunds are processed to the original payment method. Depending on your financial institution, refunds may take 5-10 business days to appear in your account.
            </p>

            <h2>10. Subscription and Recurring Payments</h2>

            <h3>Membership Subscriptions</h3>
            <p>
                Service providers may choose to subscribe to membership plans that provide benefits and reduced fees. Subscriptions:
            </p>
            <ul>
                <li>Renew automatically on the same date each month or year (as selected)</li>
                <li>Can be canceled anytime through your account settings</li>
                <li>Will be charged to your saved payment method</li>
                <li>Are non-refundable but will stop future charges upon cancellation</li>
            </ul>

            <h3>Managing Subscriptions</h3>
            <p>
                You can manage your subscription at any time through your account dashboard. Cancellations take effect at the end of your current billing period. You will not be charged for future periods after cancellation.
            </p>

            <h2>11. Currency and Conversion</h2>
            <p>
                Prices are displayed in the currency of your region. If you use a payment method in a different currency, your financial institution's current exchange rate will apply. We do not add additional conversion fees beyond what your bank charges.
            </p>

            <h2>12. Payment Modifications</h2>

            <h3>Booking Modifications</h3>
            <p>
                If you modify a booking (change date, scope, or duration), we will adjust the price and send you a new invoice. You will be charged or refunded the difference, depending on whether the new total is higher or lower.
            </p>

            <h3>Service Provider Rate Changes</h3>
            <p>
                Service providers may change their rates. New rates apply only to future bookings. Existing bookings retain their original quoted price.
            </p>

            <h2>13. Late Payments</h2>
            <p>
                All payments must be completed before the service date or as specified in your booking agreement. Late payments may result in:
            </p>
            <ul>
                <li>Booking cancellation</li>
                <li>Service provider cancellation fees</li>
                <li>Account restrictions or suspension</li>
            </ul>

            <h2>14. Compliance and Legal</h2>

            <h3>Compliance with Laws</h3>
            <p>
                {{ config('app.name', 'GIGS') }} complies with all applicable payment regulations, including anti-money laundering (AML) laws, Know Your Customer (KYC) requirements, and financial services regulations in jurisdictions where we operate.
            </p>

            <h3>Prohibited Activities</h3>
            <p>
                Users must not use the platform to:
            </p>
            <ul>
                <li>Facilitate money laundering or illegal activities</li>
                <li>Process payments through stolen payment methods</li>
                <li>Engage in fraudulent transactions</li>
                <li>Violate sanctions or trade restrictions</li>
                <li>Circumvent payment processors' terms of service</li>
            </ul>

            <h2>15. Changes to Payment Policy</h2>
            <p>
                We may update this Payment Policy to reflect changes in our practices, payment processing partners, or legal requirements. Significant changes will be communicated via email or platform notification. Your continued use of the platform constitutes acceptance of updated terms.
            </p>

            <h2>16. Contact and Support</h2>
            <p>
                For payment-related inquiries, disputes, or questions about invoices:
            </p>
            <ul>
                <li><strong>Email:</strong> payments@{{ parse_url(config('app.url'), PHP_URL_HOST) }}</li>
                <li><strong>Support Portal:</strong> Access through your account dashboard</li>
                <li><strong>Response Time:</strong> We aim to respond within 24 business hours</li>
            </ul>

            <div class="highlight-box">
                <p>
                    For security-related payment concerns, contact us immediately. Do not share sensitive payment information via email. Use our secure support ticket system instead.
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
