<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registration Assessment Form</title>
    <style>
        :root {
            color-scheme: light;
            font-family: Arial, Helvetica, sans-serif;
        }

        body {
            margin: 0;
            background: #f4f4f5;
            color: #18181b;
        }

        .page {
            max-width: 880px;
            margin: 24px auto;
            background: #ffffff;
            padding: 24px;
            border: 1px solid #d4d4d8;
        }

        h1,
        h2 {
            margin: 0 0 12px;
        }

        .meta {
            margin-bottom: 16px;
            font-size: 13px;
            color: #52525b;
        }

        .grid {
            display: grid;
            grid-template-columns: 190px 1fr;
            gap: 6px 12px;
            margin-bottom: 20px;
            font-size: 14px;
        }

        .grid .label {
            color: #3f3f46;
            font-weight: 600;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
            margin-bottom: 20px;
            font-size: 13px;
        }

        th,
        td {
            border: 1px solid #d4d4d8;
            padding: 8px;
            text-align: left;
            vertical-align: top;
        }

        th {
            background: #f4f4f5;
            font-weight: 700;
        }

        .align-right {
            text-align: right;
        }

        .note {
            font-size: 12px;
            color: #3f3f46;
            border: 1px solid #d4d4d8;
            background: #fafafa;
            padding: 10px;
        }

        @media print {
            body {
                background: #ffffff;
            }

            .page {
                margin: 0;
                border: 0;
                padding: 0;
                max-width: none;
            }
        }
    </style>
</head>
<body>
    <div class="page">
        <h1>Registration Assessment Form</h1>
        <div class="meta">
            Generated at: {{ $assessment['generated_at'] }}
        </div>

        <div class="grid">
            <div class="label">LRN</div>
            <div>{{ $assessment['student']['lrn'] ?: 'N/A' }}</div>
            <div class="label">Student Name</div>
            <div>{{ $assessment['student']['name'] ?: 'N/A' }}</div>
            <div class="label">Grade Level</div>
            <div>{{ $assessment['enrollment']['grade_level'] ?: 'N/A' }}</div>
            <div class="label">School Year</div>
            <div>{{ $assessment['enrollment']['school_year'] ?: 'N/A' }}</div>
            <div class="label">Section</div>
            <div>{{ $assessment['enrollment']['section'] ?: 'Unassigned' }}</div>
            <div class="label">Adviser</div>
            <div>{{ $assessment['enrollment']['adviser'] ?: 'TBA' }}</div>
            <div class="label">Payment Plan</div>
            <div>{{ $assessment['enrollment']['payment_plan'] ?: 'N/A' }}</div>
            <div class="label">Downpayment</div>
            <div>PHP {{ number_format((float) $assessment['enrollment']['downpayment'], 2) }}</div>
        </div>

        <h2>Tuition Breakdown</h2>
        <table>
            <thead>
                <tr>
                    <th>Item</th>
                    <th class="align-right">Amount</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>Tuition</td>
                    <td class="align-right">PHP {{ number_format((float) $assessment['assessment']['tuition'], 2) }}</td>
                </tr>
                <tr>
                    <td>Miscellaneous</td>
                    <td class="align-right">PHP {{ number_format((float) $assessment['assessment']['miscellaneous'], 2) }}</td>
                </tr>
                <tr>
                    <td><strong>Total Assessment</strong></td>
                    <td class="align-right"><strong>PHP {{ number_format((float) $assessment['assessment']['total'], 2) }}</strong></td>
                </tr>
            </tbody>
        </table>

        <h2>Monthly Dues</h2>
        <table>
            <thead>
                <tr>
                    <th>Description</th>
                    <th>Due Date</th>
                    <th class="align-right">Amount Due</th>
                    <th class="align-right">Amount Paid</th>
                    <th class="align-right">Balance</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($assessment['dues']['rows'] as $due)
+                    <tr>
                        <td>{{ $due['description'] }}</td>
                        <td>{{ $due['due_date_label'] ?: 'N/A' }}</td>
                        <td class="align-right">PHP {{ number_format((float) $due['amount_due'], 2) }}</td>
                        <td class="align-right">PHP {{ number_format((float) $due['amount_paid'], 2) }}</td>
                        <td class="align-right">PHP {{ number_format((float) $due['balance'], 2) }}</td>
                        <td>{{ $due['status'] }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6">No installment dues generated for this enrollment.</td>
                    </tr>
                @endforelse
            </tbody>
            <tfoot>
                <tr>
                    <th colspan="2">Totals</th>
                    <th class="align-right">PHP {{ number_format((float) $assessment['dues']['total_due'], 2) }}</th>
                    <th class="align-right">PHP {{ number_format((float) $assessment['dues']['total_paid'], 2) }}</th>
                    <th class="align-right">PHP {{ number_format((float) $assessment['dues']['balance'], 2) }}</th>
                    <th></th>
                </tr>
            </tfoot>
        </table>

        <h2>Account Credentials</h2>
        <table>
            <thead>
                <tr>
                    <th>Account</th>
                    <th>Username</th>
                    <th>One-Time Activation Code</th>
                    <th>Code Expires At</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>Student</td>
                    <td>{{ $assessment['accounts']['student']['email'] ?: 'N/A' }}</td>
                    <td>{{ $assessment['accounts']['student']['activation_code'] ?: 'No new code issued in this print.' }}</td>
                    <td>{{ $assessment['accounts']['student']['activation_expires_at'] ?: 'N/A' }}</td>
                </tr>
                <tr>
                    <td>Parent</td>
                    <td>{{ $assessment['accounts']['parent']['email'] ?: 'N/A' }}</td>
                    <td>{{ $assessment['accounts']['parent']['activation_code'] ?: 'No new code issued in this print.' }}</td>
                    <td>{{ $assessment['accounts']['parent']['activation_expires_at'] ?: 'N/A' }}</td>
                </tr>
            </tbody>
        </table>

        <div class="note">
            Activation codes are one-time only. After sign-in, immediately set a new password to complete account activation.
        </div>
    </div>

    @if ($autoprint)
        <script>
            window.addEventListener('load', () => {
                window.print();
            });
        </script>
    @endif
</body>
</html>
