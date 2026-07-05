<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>SF9 - Learner's Progress Report Card</title>
    <style>
        @page { margin: 9mm 8mm; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 9px; color: #111827; margin: 0; }
        .header { text-align: center; margin-bottom: 8px; }
        .header .title { font-weight: 700; font-size: 11px; margin: 0; }
        .header .subtitle { font-size: 9px; margin: 2px 0 0; }
        .meta-table, .grades-table, .attendance-table { width: 100%; border-collapse: collapse; table-layout: fixed; }
        .meta-table td { border: 1px solid #374151; padding: 3px 4px; vertical-align: top; }
        .label { font-size: 9px; color: #4b5563; }
        .value { font-size: 9px; font-weight: 600; }
        .section-title { font-size: 9px; font-weight: 700; margin: 8px 0 3px; }
        .grades-table th, .grades-table td, .attendance-table th, .attendance-table td {
            border: 1px solid #374151;
            padding: 2px 3px;
            text-align: center;
            vertical-align: middle;
            word-wrap: break-word;
        }
        .grades-table th:first-child, .grades-table td:first-child { text-align: left; }
        .grades-table thead th, .attendance-table thead th { background: #f3f4f6; font-weight: 700; }
        .small { font-size: 8px; }
    </style>
</head>
<body>
    <div class="header">
        <p class="title">REPORT ON LEARNING PROGRESS AND ACHIEVEMENT (SF9)</p>
        <p class="subtitle">{{ $metadata['school'] ?? 'School' }} | SY {{ $metadata['school_year'] ?? 'N/A' }}</p>
    </div>

    <table class="meta-table">
        <tr>
            <td colspan="2"><div class="label">Learner Name</div><div class="value">{{ $metadata['name'] ?? '' }}</div></td>
            <td><div class="label">LRN</div><div class="value">{{ $metadata['lrn'] ?? '' }}</div></td>
            <td><div class="label">Sex</div><div class="value">{{ $metadata['sex'] ?? '' }}</div></td>
        </tr>
        <tr>
            <td><div class="label">Grade Level</div><div class="value">{{ $metadata['grade_level'] ?? '' }}</div></td>
            <td><div class="label">Section</div><div class="value">{{ $metadata['section'] ?? '' }}</div></td>
            <td><div class="label">Adviser</div><div class="value">{{ $metadata['adviser'] ?? '' }}</div></td>
            <td><div class="label">Principal</div><div class="value">{{ $metadata['principal'] ?? '' }}</div></td>
        </tr>
    </table>

    <div class="section-title">REPORT ON LEARNING PROGRESS AND ACHIEVEMENT</div>
    <table class="grades-table">
        <thead>
            <tr>
                <th style="width:42%;">Learning Area</th>
                <th style="width:8%;">Q1</th>
                <th style="width:8%;">Q2</th>
                <th style="width:8%;">Q3</th>
                <th style="width:8%;">Q4</th>
                <th style="width:12%;">Final Rating</th>
                <th style="width:14%;">Remarks</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($learningAreas as $area)
                <tr>
                    <td>{{ $area['subject'] ?? '' }}</td>
                    <td>{{ $area['q1'] ?? '' }}</td>
                    <td>{{ $area['q2'] ?? '' }}</td>
                    <td>{{ $area['q3'] ?? '' }}</td>
                    <td>{{ $area['q4'] ?? '' }}</td>
                    <td>{{ $area['final'] ?? '' }}</td>
                    <td>{{ $area['remarks'] ?? '' }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" class="small">No grade records available.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div class="section-title">REPORT ON ATTENDANCE</div>
    <table class="attendance-table">
        <thead>
            <tr>
                <th style="width:24%;">Attendance</th>
                <th>Jun</th><th>Jul</th><th>Aug</th><th>Sep</th><th>Oct</th><th>Nov</th><th>Dec</th><th>Jan</th><th>Feb</th><th>Mar</th><th>Total</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>No. of School Days</td>
                @foreach (($attendance['school_days'] ?? []) as $value)
                    <td>{{ $value }}</td>
                @endforeach
            </tr>
            <tr>
                <td>No. of Days Present</td>
                @foreach (($attendance['present'] ?? []) as $value)
                    <td>{{ $value }}</td>
                @endforeach
            </tr>
            <tr>
                <td>No. of Days Absent</td>
                @foreach (($attendance['absent'] ?? []) as $value)
                    <td>{{ $value }}</td>
                @endforeach
            </tr>
        </tbody>
    </table>
</body>
</html>
