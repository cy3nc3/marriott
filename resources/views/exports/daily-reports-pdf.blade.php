<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Daily Collection Summary Report</title>
    <style>
        @page { margin: 14mm 10mm; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 9px; color: #111827; margin: 0; }
        h1 { margin: 0; font-size: 16px; }
        .sub { color: #4b5563; margin: 2px 0 10px 0; }
        .meta { width: 100%; border-collapse: collapse; margin-bottom: 10px; }
        .meta td { border: 1px solid #d1d5db; padding: 4px 6px; }
        .meta .k { width: 20%; background: #f9fafb; font-weight: 700; }
        .cards { width: 100%; border-collapse: collapse; margin-bottom: 10px; }
        .cards td { border: 1px solid #d1d5db; padding: 6px; width: 25%; vertical-align: top; }
        .cards .label { font-size: 8px; color: #6b7280; }
        .cards .val { font-size: 12px; font-weight: 700; margin-top: 2px; }
        .table { width: 100%; border-collapse: collapse; table-layout: fixed; }
        .table th, .table td { border: 1px solid #d1d5db; padding: 4px 5px; word-break: break-word; }
        .table th { background: #f3f4f6; text-align: left; font-size: 8px; }
        .right { text-align: right; }
    </style>
</head>
<body>
    @php
        $detailRows = collect($rows ?? [])->take(20)->values();
        $omittedCount = max(collect($rows ?? [])->count() - $detailRows->count(), 0);
    @endphp

    <h1>Daily Collection Report</h1>
    <div class="sub">Finance summary report for operational reconciliation and cashflow monitoring</div>

    <table class="meta">
        <tr><td class="k">Generated At</td><td>{{ $metadata['generated_at'] ?? '' }}</td><td class="k">School Year</td><td>{{ $metadata['school_year'] ?? '' }}</td></tr>
        <tr><td class="k">Cashier</td><td>{{ $metadata['cashier'] ?? '' }}</td><td class="k">Payment Mode</td><td>{{ $metadata['payment_mode'] ?? '' }}</td></tr>
    </table>

    <table class="cards">
        <tr>
            <td><div class="label">Transactions</div><div class="val">{{ (int) ($summary['transaction_count'] ?? 0) }}</div></td>
            <td><div class="label">Gross Collections</div><div class="val">PHP {{ number_format((float) ($summary['gross_amount'] ?? 0), 2) }}</div></td>
            <td><div class="label">Net Collections</div><div class="val">PHP {{ number_format((float) ($summary['net_amount'] ?? 0), 2) }}</div></td>
            <td><div class="label">Corrected / Voided</div><div class="val">PHP {{ number_format((float) ($summary['corrected_amount'] ?? 0), 2) }}</div></td>
        </tr>
    </table>

    @php
        $statusBreakdown = collect($rows ?? [])->groupBy('status')->map->count()->sortDesc();
        $paymentTypeBreakdown = collect($rows ?? [])->groupBy('payment_type')->map->count()->sortDesc();
    @endphp

    <table class="meta">
        <tr>
            <td class="k">Summary Insights</td>
            <td>
                {{ $statusBreakdown->isNotEmpty() ? 'Top Status: '.$statusBreakdown->keys()->first().' ('.$statusBreakdown->values()->first().')' : 'No status trends available.' }}
                |
                {{ $paymentTypeBreakdown->isNotEmpty() ? 'Top Payment Type: '.$paymentTypeBreakdown->keys()->first().' ('.$paymentTypeBreakdown->values()->first().')' : 'No payment type trends available.' }}
            </td>
        </tr>
    </table>

    <table class="table" style="margin-bottom: 10px;">
        <thead>
            <tr>
                <th style="width:50%">Status Mix</th>
                <th style="width:50%">Payment Type Mix</th>
            </tr>
        </thead>
        <tbody>
            @for($i = 0; $i < max($statusBreakdown->count(), $paymentTypeBreakdown->count(), 1); $i++)
                <tr>
                    <td>
                        @if($statusBreakdown->values()->get($i) !== null)
                            {{ $statusBreakdown->keys()->get($i) }}: {{ $statusBreakdown->values()->get($i) }}
                        @endif
                    </td>
                    <td>
                        @if($paymentTypeBreakdown->values()->get($i) !== null)
                            {{ $paymentTypeBreakdown->keys()->get($i) }}: {{ $paymentTypeBreakdown->values()->get($i) }}
                        @endif
                    </td>
                </tr>
            @endfor
        </tbody>
    </table>

    <table class="table">
        <thead>
            <tr>
                <th style="width:10%">OR Number</th>
                <th style="width:17%">Student</th>
                <th style="width:16%">Payment Type</th>
                <th style="width:10%">Mode</th>
                <th style="width:9%">Status</th>
                <th style="width:10%">Amount</th>
                <th style="width:12%">Cashier</th>
                <th style="width:16%">Posted At</th>
            </tr>
        </thead>
        <tbody>
            @forelse($detailRows as $row)
                <tr>
                    <td>{{ $row['or_number'] ?? '' }}</td>
                    <td>{{ $row['student_name'] ?? '' }}</td>
                    <td>{{ $row['payment_type'] ?? '' }}</td>
                    <td>{{ $row['payment_mode_label'] ?? '' }}</td>
                    <td>{{ $row['status'] ?? '' }}</td>
                    <td class="right">{{ number_format((float) ($row['amount'] ?? 0), 2) }}</td>
                    <td>{{ $row['cashier_name'] ?? '' }}</td>
                    <td>{{ $row['posted_at'] ?? '' }}</td>
                </tr>
            @empty
                <tr><td colspan="8">No records found.</td></tr>
            @endforelse
            @if($omittedCount > 0)
                <tr>
                    <td colspan="8"><em>{{ $omittedCount }} additional entries omitted in PDF summary. Use XLSX export for full detail.</em></td>
                </tr>
            @endif
        </tbody>
    </table>
</body>
</html>
