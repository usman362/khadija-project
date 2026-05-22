{{-- Agreement PDF template — rendered by AgreementPdfService via DomPDF.
     Kept self-contained (inline CSS, simple HTML) because DomPDF doesn't
     support modern CSS features (grid, flexbox in some versions, custom
     properties, etc.). All inputs are escaped via {{ }} by default. --}}
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>{{ $agreement->title ?: 'Service Agreement' }} — {{ config('app.name') }}</title>
    <style>
        @page { margin: 22mm 18mm 25mm; }
        body {
            font-family: 'Helvetica', 'Arial', sans-serif;
            color: #1a202c;
            font-size: 11.5px;
            line-height: 1.55;
        }

        /* ─── Header band ─── */
        .doc-header {
            border-bottom: 3px solid #3b82f6;
            padding-bottom: 14px;
            margin-bottom: 22px;
        }
        .brand {
            font-size: 22px;
            font-weight: 900;
            letter-spacing: -1px;
            color: #1a202c;
            margin-bottom: 4px;
        }
        .brand-blue { color: #3b82f6; }
        .doc-title {
            font-size: 16px;
            font-weight: 700;
            margin-top: 6px;
        }
        .doc-meta {
            font-size: 10.5px;
            color: #4a5568;
            margin-top: 4px;
        }
        .doc-meta strong { color: #1a202c; }

        /* ─── Section ─── */
        .section { margin-bottom: 18px; }
        .section h2 {
            font-size: 12px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: #4a5568;
            border-bottom: 1px solid #cbd5e0;
            padding-bottom: 4px;
            margin-bottom: 10px;
        }

        /* ─── Party table ─── */
        .parties {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 14px;
        }
        .parties td {
            width: 50%;
            vertical-align: top;
            padding: 10px 14px;
            border: 1px solid #cbd5e0;
            background: #f7fafc;
        }
        .parties .role {
            font-size: 9.5px;
            font-weight: 800;
            text-transform: uppercase;
            color: #6366f1;
            letter-spacing: 0.8px;
            margin-bottom: 4px;
        }
        .parties .name {
            font-size: 13px;
            font-weight: 800;
            color: #1a202c;
            margin-bottom: 2px;
        }
        .parties .email {
            font-size: 10.5px;
            color: #4a5568;
        }

        /* ─── Event summary table ─── */
        .summary {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 14px;
        }
        .summary td {
            padding: 7px 12px;
            border: 1px solid #cbd5e0;
            font-size: 11px;
        }
        .summary td.label {
            width: 28%;
            background: #f7fafc;
            font-weight: 700;
            color: #4a5568;
        }

        /* ─── Body content ─── */
        .body-content {
            padding: 8px 4px;
            font-size: 11.5px;
            line-height: 1.7;
        }
        .body-content h3 {
            font-size: 13px;
            font-weight: 700;
            color: #1a202c;
            margin: 14px 0 6px;
        }
        .body-content h4 {
            font-size: 12px;
            font-weight: 700;
            margin: 10px 0 4px;
        }
        .body-content p { margin-bottom: 9px; }
        .body-content ul, .body-content ol {
            margin: 6px 0 10px 22px;
        }
        .body-content li { margin-bottom: 4px; }
        .body-content strong { color: #1a202c; font-weight: 700; }

        /* ─── Signature blocks ─── */
        .signatures {
            margin-top: 24px;
            page-break-inside: avoid;
        }
        .sig-table {
            width: 100%;
            border-collapse: collapse;
        }
        .sig-table td {
            width: 50%;
            vertical-align: top;
            padding: 12px;
            border: 1px solid #cbd5e0;
        }
        .sig-line {
            border-bottom: 1.5px solid #1a202c;
            margin-top: 26px;
            margin-bottom: 4px;
        }
        .sig-name {
            font-size: 11.5px;
            font-weight: 700;
            color: #1a202c;
        }
        .sig-role {
            font-size: 10px;
            color: #4a5568;
            margin-top: 1px;
        }
        .sig-date {
            font-size: 10px;
            color: #4a5568;
            margin-top: 8px;
        }
        .sig-date strong { color: #1a202c; }
        .sig-accepted {
            display: inline-block;
            background: #d1fae5;
            color: #065f46;
            font-size: 9.5px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.8px;
            padding: 2px 8px;
            border-radius: 4px;
            margin-top: 6px;
        }

        /* ─── Footer ─── */
        .doc-footer {
            margin-top: 28px;
            padding-top: 12px;
            border-top: 1px solid #cbd5e0;
            font-size: 9.5px;
            color: #718096;
            line-height: 1.5;
        }
        .doc-footer .ref {
            font-family: 'Courier', monospace;
            color: #1a202c;
            font-weight: 700;
        }
    </style>
</head>
<body>

<div class="doc-header">
    <div class="brand">GIG<span class="brand-blue">RESOURCE</span></div>
    <div class="doc-title">{{ $agreement->title ?: 'Service Agreement' }}</div>
    <div class="doc-meta">
        <strong>Agreement ID:</strong> {{ $agreement->id }} &nbsp;·&nbsp;
        <strong>Version:</strong> {{ $agreement->version ?? 1 }} &nbsp;·&nbsp;
        <strong>Generated:</strong> {{ $generatedAt->format('F j, Y g:i A') }} &nbsp;·&nbsp;
        <strong>Status:</strong> {{ $agreement->statusLabel() }}
    </div>
</div>

{{-- ─── PARTIES ────────────────────────────────────────────────── --}}
<div class="section">
    <h2>Parties to this Agreement</h2>
    <table class="parties">
        <tr>
            <td>
                <div class="role">Client</div>
                <div class="name">{{ $client?->name ?? '—' }}</div>
                <div class="email">{{ $client?->email ?? '' }}</div>
            </td>
            <td>
                <div class="role">Professional / Service Provider</div>
                <div class="name">{{ $supplier?->name ?? '—' }}</div>
                <div class="email">{{ $supplier?->email ?? '' }}</div>
            </td>
        </tr>
    </table>
</div>

{{-- ─── EVENT SUMMARY ──────────────────────────────────────────── --}}
@if($event || $agreement->booking)
    <div class="section">
        <h2>Event &amp; Booking Details</h2>
        <table class="summary">
            @if($event?->title)
                <tr>
                    <td class="label">Event</td>
                    <td>{{ $event->title }}</td>
                </tr>
            @endif
            @if($event?->event_date)
                <tr>
                    <td class="label">Event Date</td>
                    <td>{{ \Carbon\Carbon::parse($event->event_date)->format('l, F j, Y') }}</td>
                </tr>
            @endif
            @if($event?->location)
                <tr>
                    <td class="label">Location</td>
                    <td>{{ $event->location }}</td>
                </tr>
            @endif
            @if($agreement->booking?->id)
                <tr>
                    <td class="label">Booking Reference</td>
                    <td>#{{ $agreement->booking->id }}</td>
                </tr>
            @endif
        </table>
    </div>
@endif

{{-- ─── CONTRACT BODY ──────────────────────────────────────────── --}}
<div class="section">
    <h2>Agreement Terms</h2>
    <div class="body-content">
        {!! $agreement->content !!}
    </div>
</div>

{{-- ─── SIGNATURES ─────────────────────────────────────────────── --}}
<div class="section signatures">
    <h2>Signatures &amp; Acceptance</h2>
    <table class="sig-table">
        <tr>
            <td>
                <div class="sig-line"></div>
                <div class="sig-name">{{ $client?->name ?? '—' }}</div>
                <div class="sig-role">Client</div>
                @if($agreement->client_accepted_at)
                    <div class="sig-date">
                        <strong>Accepted:</strong> {{ $agreement->client_accepted_at->format('F j, Y g:i A') }}
                    </div>
                    <div class="sig-accepted">✓ Accepted</div>
                @else
                    <div class="sig-date">Pending acceptance</div>
                @endif
            </td>
            <td>
                <div class="sig-line"></div>
                <div class="sig-name">{{ $supplier?->name ?? '—' }}</div>
                <div class="sig-role">Professional</div>
                @if($agreement->supplier_accepted_at)
                    <div class="sig-date">
                        <strong>Accepted:</strong> {{ $agreement->supplier_accepted_at->format('F j, Y g:i A') }}
                    </div>
                    <div class="sig-accepted">✓ Accepted</div>
                @else
                    <div class="sig-date">Pending acceptance</div>
                @endif
            </td>
        </tr>
    </table>
</div>

<div class="doc-footer">
    This document was generated electronically by {{ config('app.name', 'GigResource') }} on
    <strong>{{ $generatedAt->format('F j, Y \a\t g:i A') }}</strong>.
    Both parties have indicated their acceptance via the platform's e-signature flow.<br>
    Document reference: <span class="ref">GR-AGR-{{ str_pad($agreement->id, 6, '0', STR_PAD_LEFT) }}-v{{ $agreement->version ?? 1 }}</span>
    &nbsp;·&nbsp; Retained for {{ \App\Domain\Agreements\Services\AgreementPdfService::RETENTION_DAYS }} days from generation.
</div>

</body>
</html>
