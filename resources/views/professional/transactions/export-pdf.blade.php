{{--
    Printable export for the Transactions page.

    The controller returns this as plain HTML with `Content-Type: text/html`.
    The embedded JS calls `window.print()` on load so the browser opens its
    print dialog immediately — user chooses "Save as PDF" as the destination.
    No external PDF library required; swap for dompdf/Browsershot later if
    server-side PDF generation becomes a hard requirement.
--}}
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Transactions — {{ $generatedAt->format('Y-m-d H:i') }}</title>
    <style>
        * { box-sizing: border-box; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            color: #1f2937;
            margin: 32px;
            font-size: 12px;
        }
        header {
            display: flex;
            justify-content: space-between;
            align-items: flex-end;
            border-bottom: 2px solid #111827;
            padding-bottom: 12px;
            margin-bottom: 20px;
        }
        header h1 {
            font-size: 22px;
            margin: 0 0 4px;
            color: #111827;
        }
        header .meta {
            text-align: right;
            color: #6b7280;
            font-size: 11px;
            line-height: 1.5;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 8px;
        }
        thead th {
            background: #f3f4f6;
            text-align: left;
            padding: 10px 8px;
            border-bottom: 1px solid #d1d5db;
            font-weight: 600;
            text-transform: uppercase;
            font-size: 10px;
            letter-spacing: 0.04em;
            color: #374151;
        }
        tbody td {
            padding: 10px 8px;
            border-bottom: 1px solid #e5e7eb;
            vertical-align: top;
        }
        tbody tr:nth-child(even) td {
            background: #fafafa;
        }
        .amount {
            text-align: right;
            font-variant-numeric: tabular-nums;
            font-weight: 600;
        }
        .status {
            display: inline-block;
            padding: 2px 8px;
            border-radius: 999px;
            font-size: 10px;
            font-weight: 600;
            background: #e5e7eb;
            color: #374151;
        }
        .status.completed { background: #d1fae5; color: #065f46; }
        .status.pending   { background: #fef3c7; color: #92400e; }
        .status.failed    { background: #fee2e2; color: #991b1b; }
        .empty {
            text-align: center;
            padding: 40px 12px;
            color: #9ca3af;
            font-style: italic;
        }
        footer {
            margin-top: 28px;
            padding-top: 12px;
            border-top: 1px solid #e5e7eb;
            color: #9ca3af;
            font-size: 10px;
            text-align: center;
        }
        /* Print overrides: strip margins, force black ink on headings. */
        @media print {
            body { margin: 16px; }
            thead th { background: #f3f4f6 !important; -webkit-print-color-adjust: exact; }
            tbody tr:nth-child(even) td { background: #fafafa !important; -webkit-print-color-adjust: exact; }
            .status { -webkit-print-color-adjust: exact; }
            .no-print { display: none !important; }
        }
        .no-print {
            margin-bottom: 16px;
            padding: 10px 14px;
            background: #eff6ff;
            border: 1px solid #bfdbfe;
            border-radius: 6px;
            color: #1e40af;
            font-size: 11px;
        }
        .no-print button {
            margin-left: 8px;
            padding: 4px 10px;
            background: #2563eb;
            color: #fff;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 11px;
        }
    </style>
</head>
<body>
    <div class="no-print">
        If the print dialog didn't open automatically, click here →
        <button type="button" onclick="window.print()">Open print dialog</button>
    </div>

    <header>
        <div>
            <h1>Transactions Report</h1>
            <div style="color:#6b7280;">{{ config('app.name') }}</div>
        </div>
        <div class="meta">
            Generated {{ $generatedAt->format('M j, Y g:i A') }}<br>
            {{ count($transactions) }} {{ \Illuminate\Support\Str::plural('record', count($transactions)) }}
        </div>
    </header>

    <table>
        <thead>
            <tr>
                <th style="width:80px;">ID</th>
                <th style="width:110px;">Date</th>
                <th style="width:110px;">Type</th>
                <th>Description</th>
                <th style="width:110px;" class="amount">Amount</th>
                <th style="width:90px;">Status</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($transactions as $txn)
                <tr>
                    <td>{{ $txn['id'] ?? '—' }}</td>
                    <td>{{ $txn['date'] ?? '—' }}</td>
                    <td>{{ $txn['type'] ?? '—' }}</td>
                    <td>{{ $txn['description'] ?? '—' }}</td>
                    <td class="amount">{{ $txn['amount'] ?? '—' }}</td>
                    <td>
                        @php $status = strtolower($txn['status'] ?? ''); @endphp
                        <span class="status {{ $status }}">{{ ucfirst($status) ?: '—' }}</span>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" class="empty">
                        No transactions match the current filters.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <footer>
        This document was generated from the Professional Transactions page.
        Use your browser's "Save as PDF" option in the print dialog to store a copy.
    </footer>

    <script>
        // Fire the print dialog once the DOM has painted so the user sees the
        // rendered table before the OS chrome appears on top of it.
        window.addEventListener('load', function () {
            setTimeout(function () { window.print(); }, 250);
        });
    </script>
</body>
</html>
