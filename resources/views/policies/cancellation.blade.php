<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cancellation and Refund Policy - {{ config('app.name', 'GIGS') }}</title>
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
            --success: #22c55e;
            --warning: #f59e0b;
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

        .warning-box {
            background: var(--bg-card);
            border-left: 4px solid var(--warning);
            padding: 20px;
            margin: 24px 0;
            border-radius: 4px;
        }

        .warning-box p {
            margin: 0;
            color: var(--text-light);
        }

        .success-box {
            background: var(--bg-card);
            border-left: 4px solid var(--success);
            padding: 20px;
            margin: 24px 0;
            border-radius: 4px;
        }

        .success-box p {
            margin: 0;
            color: var(--text-light);
        }

        .timeline {
            margin: 24px 0;
        }

        .timeline-item {
            display: flex;
            margin-bottom: 20px;
        }

        .timeline-marker {
            width: 24px;
            height: 24px;
            border-radius: 50%;
            background: var(--primary);
            margin-right: 20px;
            flex-shrink: 0;
            margin-top: 2px;
        }

        .timeline-content h4 {
            color: var(--text-white);
            font-weight: 600;
            margin-bottom: 4px;
        }

        .timeline-content p {
            margin: 0;
            font-size: 0.95rem;
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
            <h1>Cancellation and Refund Policy</h1>
            <p class="policy-date">Last updated: {{ now()->format('F j, Y') }}</p>

            <h2>1. Policy Overview</h2>
            <p>
                This Cancellation and Refund Policy outlines the terms and conditions for canceling bookings and requesting refunds on the {{ config('app.name', 'GIGS') }} platform. This policy applies to all users, including event clients and service providers, and covers all booking types including events, services, and subscriptions.
            </p>

            <h2>2. Cancellation Windows</h2>
            <p>
                Cancellation rights depend on how far in advance you cancel before the scheduled service date:
            </p>

            <div class="timeline">
                <div class="timeline-item">
                    <div class="timeline-marker"></div>
                    <div class="timeline-content">
                        <h4>More than 30 days before service date</h4>
                        <p>Full refund minus platform fees (10% of booking amount)</p>
                    </div>
                </div>
                <div class="timeline-item">
                    <div class="timeline-marker"></div>
                    <div class="timeline-content">
                        <h4>15-30 days before service date</h4>
                        <p>70% refund of service cost; platform fees non-refundable</p>
                    </div>
                </div>
                <div class="timeline-item">
                    <div class="timeline-marker"></div>
                    <div class="timeline-content">
                        <h4>7-14 days before service date</h4>
                        <p>50% refund of service cost; platform fees non-refundable</p>
                    </div>
                </div>
                <div class="timeline-item">
                    <div class="timeline-marker"></div>
                    <div class="timeline-content">
                        <h4>Less than 7 days before service date</h4>
                        <p>No refund; service provider may retain full booking amount</p>
                    </div>
                </div>
            </div>

            <div class="highlight-box">
                <p><strong>Note:</strong> The "service date" refers to the agreed-upon date when the service will be provided. Cancellation windows are calculated from midnight in your local timezone.</p>
            </div>

            <h2>3. Client Cancellations</h2>

            <h3>How to Cancel a Booking</h3>
            <p>
                Clients can cancel bookings through the {{ config('app.name', 'GIGS') }} platform:
            </p>
            <ol>
                <li>Log into your account and navigate to "My Bookings"</li>
                <li>Select the booking you wish to cancel</li>
                <li>Click "Request Cancellation" and provide a reason (optional)</li>
                <li>Review the refund amount you will receive</li>
                <li>Confirm the cancellation</li>
            </ol>

            <h3>Refund Eligibility</h3>
            <p>
                You are eligible for a refund if:
            </p>
            <ul>
                <li>You cancel within the applicable refund window</li>
                <li>The booking has not yet commenced</li>
                <li>The service provider has not already begun work</li>
                <li>You have not violated our Terms of Service</li>
            </ul>

            <h3>Refund Restrictions</h3>
            <p>
                Refunds will NOT be issued if:
            </p>
            <ul>
                <li>The cancellation occurs less than 7 days before the service date</li>
                <li>The service provider is not at fault and has prepared for the service</li>
                <li>You cancel after the service has been provided</li>
                <li>The booking is for a non-refundable package (clearly marked at purchase)</li>
                <li>You cancel due to user error or incorrect booking details</li>
                <li>The cancellation violates any specific service provider terms</li>
            </ul>

            <h2>4. Service Provider Cancellations</h2>

            <h3>How to Cancel as a Service Provider</h3>
            <p>
                Service providers may cancel bookings only in exceptional circumstances:
            </p>
            <ol>
                <li>Log into your account and navigate to "My Bookings"</li>
                <li>Select the booking you need to cancel</li>
                <li>Provide a detailed reason for cancellation</li>
                <li>Submit the cancellation request</li>
            </ol>

            <div class="warning-box">
                <p><strong>Warning:</strong> Service providers should avoid canceling bookings. Excessive cancellations may result in reduced visibility, lower ratings, or account suspension.</p>
            </div>

            <h3>Valid Reasons for Provider Cancellation</h3>
            <p>
                Service providers may cancel without penalty only in these circumstances:
            </p>
            <ul>
                <li>Personal or family emergency</li>
                <li>Serious illness or injury</li>
                <li>Death or bereavement in the family</li>
                <li>Client-initiated cancellation request (with consent)</li>
                <li>Unsafe or inappropriate client behavior</li>
                <li>Force majeure events (natural disasters, government actions)</li>
            </ul>

            <h3>Financial Implications for Providers</h3>
            <p>
                When a service provider cancels:
            </p>
            <ul>
                <li>The full booking amount is refunded to the client</li>
                <li>The service provider forfeits the service fee</li>
                <li>The client receives an additional 10% credit for inconvenience (applied as platform credit)</li>
                <li>Cancellation is noted in the provider's performance history</li>
            </ul>

            <h2>5. Refund Processing</h2>

            <h3>Refund Timeline</h3>
            <p>
                Once a cancellation is approved:
            </p>
            <div class="timeline">
                <div class="timeline-item">
                    <div class="timeline-marker"></div>
                    <div class="timeline-content">
                        <h4>Immediate</h4>
                        <p>Cancellation is recorded and refund is initiated</p>
                    </div>
                </div>
                <div class="timeline-item">
                    <div class="timeline-marker"></div>
                    <div class="timeline-content">
                        <h4>1-2 business days</h4>
                        <p>Refund is processed from our payment processor</p>
                    </div>
                </div>
                <div class="timeline-item">
                    <div class="timeline-marker"></div>
                    <div class="timeline-content">
                        <h4>5-10 business days</h4>
                        <p>Refund appears in your account (depends on your bank)</p>
                    </div>
                </div>
            </div>

            <h3>Refund Method</h3>
            <p>
                Refunds are processed to the original payment method used for the booking. If the original payment method is no longer active or has been closed:
            </p>
            <ul>
                <li>We will attempt to refund to the alternate card if you provided one</li>
                <li>If no alternate method is available, the refund will be held as platform credit</li>
                <li>Contact support to arrange an alternative refund method</li>
            </ul>

            <h3>Refund Status</h3>
            <p>
                You can check the status of your refund:
            </p>
            <ul>
                <li>In your account dashboard under "Transaction History"</li>
                <li>Via email notification sent to your registered email address</li>
                <li>By contacting our support team with your transaction ID</li>
            </ul>

            <div class="success-box">
                <p><strong>Tip:</strong> Keep your banking information current in your account settings to ensure refunds are processed quickly and correctly.</p>
            </div>

            <h2>6. Partial Refunds</h2>

            <h3>When Partial Refunds Apply</h3>
            <p>
                Partial refunds (instead of full refunds) are issued when:
            </p>
            <ul>
                <li>You cancel within the reduced refund window (7-30 days before service)</li>
                <li>The service provider has already incurred costs</li>
                <li>Partial work has been completed</li>
                <li>The service provider agrees to a partial refund settlement</li>
            </ul>

            <h3>Calculating Partial Refunds</h3>
            <p>
                The refund amount is calculated as follows:
            </p>
            <ul>
                <li>Start with the total service cost (excluding platform fees)</li>
                <li>Apply the cancellation window refund percentage</li>
                <li>Deduct any applicable service provider cancellation fees</li>
                <li>Deduct any non-refundable platform fees</li>
            </ul>

            <p><strong>Example:</strong> If you cancel 10 days before service (50% refund window) on a $500 service with a $50 platform fee:</p>
            <ul>
                <li>Service cost: $500</li>
                <li>Refund at 50%: $250</li>
                <li>Platform fee (non-refundable): $50</li>
                <li>Your refund: $250</li>
            </ul>

            <h2>7. Disputes and Appeals</h2>

            <h3>Disputing a Cancellation or Refund Decision</h3>
            <p>
                If you disagree with a cancellation decision or refund amount:
            </p>
            <ol>
                <li>Contact our support team within 14 days of the cancellation</li>
                <li>Provide detailed explanation and supporting documentation</li>
                <li>Include booking ID and any relevant correspondence</li>
                <li>We will review and respond within 7 business days</li>
            </ol>

            <h3>Appeal Process</h3>
            <p>
                If you disagree with our decision after initial review:
            </p>
            <ul>
                <li>Request escalation to our disputes team</li>
                <li>Provide additional evidence or context</li>
                <li>Accept a final decision within 7 business days</li>
                <li>If still unsatisfied, pursue resolution through your payment processor or legal channels</li>
            </ul>

            <h2>8. Special Circumstances</h2>

            <h3>Force Majeure Events</h3>
            <p>
                In cases of force majeure (natural disasters, government actions, pandemics, etc.), the following applies:
            </p>
            <ul>
                <li>If the service becomes impossible to provide: Full refund issued</li>
                <li>If the event affects only part of the service: Prorated refund based on impact</li>
                <li>Clients may reschedule instead of canceling without losing platform fees</li>
                <li>Service provider availability status must be updated in account</li>
            </ul>

            <h3>Client-Initiated Rescheduling</h3>
            <p>
                Instead of canceling, you may request to reschedule:
            </p>
            <ul>
                <li>Reschedules are allowed up to 7 days before service (no fee)</li>
                <li>After 7 days, rescheduling follows standard cancellation terms</li>
                <li>Service provider must approve new date/time</li>
                <li>If provider declines, you receive a full refund</li>
            </ul>

            <h3>Service Provider No-Show</h3>
            <p>
                If a service provider fails to appear for a scheduled service:
            </p>
            <ul>
                <li>Client receives automatic full refund</li>
                <li>Client receives 20% platform credit as compensation</li>
                <li>Service provider receives warning and fee penalty</li>
                <li>Repeated no-shows result in account suspension or termination</li>
            </ul>

            <h3>Unsafe or Inappropriate Conduct</h3>
            <p>
                If you cancel due to safety concerns or inappropriate behavior:
            </p>
            <ul>
                <li>Full refund is issued immediately (regardless of timing)</li>
                <li>Safety incident is documented and investigated</li>
                <li>Offending party may face account suspension or termination</li>
                <li>You may be entitled to additional compensation if applicable</li>
            </ul>

            <h2>9. Subscription Cancellations</h2>

            <h3>Canceling a Membership Subscription</h3>
            <p>
                Service providers can cancel subscriptions at any time:
            </p>
            <ul>
                <li>Go to Account Settings → Subscriptions</li>
                <li>Click "Cancel Subscription"</li>
                <li>Subscription remains active until the next renewal date</li>
                <li>No charges will be made for future periods</li>
                <li>You forfeit subscription benefits immediately upon cancellation</li>
            </ul>

            <h3>Subscription Refunds</h3>
            <p>
                Subscription refunds are governed by these rules:
            </p>
            <ul>
                <li>Monthly subscriptions: No refund for the current month</li>
                <li>Annual subscriptions: Prorated refund for remaining months if canceled within 30 days of purchase</li>
                <li>After 30 days: No refund available (you can cancel to stop future charges)</li>
            </ul>

            <h2>10. Non-Refundable Items</h2>
            <p>
                The following are explicitly non-refundable:
            </p>
            <ul>
                <li>Platform service fees and transaction fees</li>
                <li>Add-on features purchased for a booking</li>
                <li>Rush or expedite fees</li>
                <li>Promotional credits and discount codes applied to completed bookings</li>
                <li>Subscriptions after 30 days of purchase (though future charges can be canceled)</li>
            </ul>

            <h2>11. Tax and Fee Adjustments</h2>
            <p>
                When a refund is processed:
            </p>
            <ul>
                <li>Applicable taxes are refunded proportionally</li>
                <li>Taxes may be recalculated based on the refund amount</li>
                <li>Payment processing fees are generally non-refundable</li>
                <li>You may be required to pay taxes on partial refunds in some jurisdictions</li>
            </ul>

            <h2>12. Chargeback and Unauthorized Refund Requests</h2>

            <h3>Chargebacks</h3>
            <p>
                Filing a chargeback through your bank/card issuer instead of using our refund process:
            </p>
            <ul>
                <li>Violates our Terms of Service</li>
                <li>May result in account termination</li>
                <li>Incurs chargeback fees that will be charged to you</li>
                <li>Damages your platform reputation and credibility</li>
            </ul>

            <div class="warning-box">
                <p><strong>Important:</strong> Always attempt to resolve refund issues through our support process before filing a chargeback.</p>
            </div>

            <h3>Fraudulent Refund Requests</h3>
            <p>
                Attempting to obtain refunds through false claims or fraud:
            </p>
            <ul>
                <li>Results in immediate account suspension</li>
                <li>May result in legal action and claim for damages</li>
                <li>Is reported to payment processors and law enforcement</li>
                <li>Permanently blacklists you from the platform</li>
            </ul>

            <h2>13. Contact and Support</h2>
            <p>
                For questions about cancellations or refunds:
            </p>
            <ul>
                <li><strong>Email:</strong> support@{{ parse_url(config('app.url'), PHP_URL_HOST) }}</li>
                <li><strong>Support Portal:</strong> Submit a ticket through your account dashboard</li>
                <li><strong>Response Time:</strong> We aim to respond within 24 business hours</li>
                <li><strong>Chat Support:</strong> Available during business hours in your timezone</li>
            </ul>

            <h2>14. Changes to This Policy</h2>
            <p>
                We may update this Cancellation and Refund Policy at any time. Changes are effective immediately upon posting. For significant changes, we will notify you via email or platform announcement. Your continued use of the platform indicates acceptance of updated terms.
            </p>

            <div class="highlight-box">
                <p>
                    For the most current version of this policy and any recent updates, visit our help center or contact support. When in doubt about your specific situation, contact us before taking action.
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
