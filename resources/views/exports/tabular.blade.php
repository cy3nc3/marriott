<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>{{ $title ?? 'Report' }}</title>
    <style>
        @page { margin: 14mm 10mm; }
        * { box-sizing: border-box; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 9px; color: #111827; margin: 0; }
        h1 { margin: 0 0 8px 0; font-size: 15px; line-height: 1.25; }
        .meta { margin-bottom: 10px; }
        .meta-row { margin: 2px 0; font-size: 8.5px; }
        .meta-label { font-weight: 700; display: inline-block; min-width: 108px; }
        table { width: 100%; border-collapse: collapse; table-layout: fixed; }
        th, td {
            border: 1px solid #d1d5db;
            padding: 4px 5px;
            vertical-align: top;
            word-break: break-word;
            overflow-wrap: anywhere;
            white-space: normal;
            line-height: 1.28;
        }
        th {
            background: #f3f4f6;
            text-align: left;
            font-size: 8.5px;
        }
    </style>
</head>
<body>
    <h1>{{ $title ?? 'Report' }}</h1>
    @if(!empty($metadata) && is_array($metadata))
        <div class="meta">
            @foreach($metadata as $label => $value)
                <div class="meta-row">
                    <span class="meta-label">{{ $label }}:</span>
                    <span>{{ $value }}</span>
                </div>
            @endforeach
        </div>
    @endif

    <div class="meta" style="margin-top: 6px;">
        <div class="meta-row"><span class="meta-label">Row Count:</span><span>{{ count($rows ?? []) }}</span></div>
        <div class="meta-row"><span class="meta-label">Column Count:</span><span>{{ count($columns ?? []) }}</span></div>
    </div>

    <table>
        <thead>
            <tr>
                @foreach(($columns ?? []) as $column)
                    <th>{{ $column }}</th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @forelse(($rows ?? []) as $row)
                <tr>
                    @foreach($row as $value)
                        <td>{{ $value }}</td>
                    @endforeach
                </tr>
            @empty
                <tr>
                    <td colspan="{{ max(count($columns ?? []), 1) }}">No records found.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>
