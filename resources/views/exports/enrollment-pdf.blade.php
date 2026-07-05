<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Enrollment Summary Report</title>
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

    <h1>Enrollment Summary Report</h1>
    <div class="sub">Registrar report for enrollment tracking, section balancing, and payment readiness</div>

    <table class="meta">
        <tr><td class="k">School Year</td><td>{{ $metadata['school_year'] ?? '' }}</td><td class="k">Generated At</td><td>{{ $metadata['generated_at'] ?? '' }}</td></tr>
    </table>

    <table class="cards">
        <tr>
            <td><div class="label">Total Enrollees</div><div class="val">{{ (int) ($summary['total_count'] ?? 0) }}</div></td>
            <td><div class="label">For Cashier Payment</div><div class="val">{{ (int) ($summary['for_cashier_payment_count'] ?? 0) }}</div></td>
            <td><div class="label">Fully Enrolled</div><div class="val">{{ (int) ($summary['enrolled_count'] ?? 0) }}</div></td>
            <td><div class="label">Outstanding Balance</div><div class="val">PHP {{ number_format((float) ($summary['balance_total'] ?? 0), 2) }}</div></td>
        </tr>
    </table>

    @php
        $gradeBreakdown = collect($rows ?? [])->groupBy('grade_level')->map->count()->sortDesc();
        $statusBreakdown = collect($rows ?? [])->groupBy('reservation_status')->map->count()->sortDesc();
    @endphp

    <table class="meta">
        <tr>
            <td class="k">Summary Insights</td>
            <td>
                {{ $gradeBreakdown->isNotEmpty() ? 'Top Grade Level: '.$gradeBreakdown->keys()->first().' ('.$gradeBreakdown->values()->first().')' : 'No grade-level trend available.' }}
                |
                {{ $statusBreakdown->isNotEmpty() ? 'Top Enrollment Status: '.$statusBreakdown->keys()->first().' ('.$statusBreakdown->values()->first().')' : 'No status trend available.' }}
            </td>
        </tr>
    </table>

    <table class="table" style="margin-bottom: 10px;">
        <thead>
            <tr>
                <th style="width:50%">Enrollment Status Mix</th>
                <th style="width:50%">Grade Level Mix</th>
            </tr>
        </thead>
        <tbody>
            @for($i = 0; $i < max($statusBreakdown->count(), $gradeBreakdown->count(), 1); $i++)
                <tr>
                    <td>
                        @if($statusBreakdown->values()->get($i) !== null)
                            {{ $statusBreakdown->keys()->get($i) }}: {{ $statusBreakdown->values()->get($i) }}
                        @endif
                    </td>
                    <td>
                        @if($gradeBreakdown->values()->get($i) !== null)
                            {{ $gradeBreakdown->keys()->get($i) }}: {{ $gradeBreakdown->values()->get($i) }}
                        @endif
                    </td>
                </tr>
            @endfor
        </tbody>
    </table>

    <table class="table">
        <thead>
            <tr>
                <th style="width:20%">Student</th>
                <th style="width:9%">Grade</th>
                <th style="width:12%">Section</th>
                <th style="width:10%">Payment Plan</th>
                <th style="width:10%">Total</th>
                <th style="width:10%">Balance</th>
                <th style="width:10%">Status</th>
                <th style="width:19%">Remarks</th>
            </tr>
        </thead>
        <tbody>
            @forelse($detailRows as $row)
                <tr>
                    <td>{{ $row['name'] ?? '' }}</td>
                    <td>{{ $row['grade_level'] ?? '' }}</td>
                    <td>{{ $row['section'] ?? '' }}</td>
                    <td>{{ $row['tuition_mode'] ?? '' }}</td>
                    <td class="right">{{ number_format((float) ($row['total'] ?? 0), 2) }}</td>
                    <td class="right">{{ number_format((float) ($row['balance'] ?? 0), 2) }}</td>
                    <td>{{ $row['reservation_status'] ?? '' }}</td>
                    <td>{{ $row['remarks'] ?? '' }}</td>
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
