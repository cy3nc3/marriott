<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registration Assessment Form</title>
    <style>
        :root {
            color-scheme: light;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        body {
            margin: 0;
            background: #f3f4f6;
            color: #111827;
        }

        .page {
            max-width: 960px;
            margin: 24px auto;
            background: #fff;
            border: 1px solid #d1d5db;
            padding: 22px 24px;
        }

        .header {
            border-bottom: 2px solid #1f2937;
            padding-bottom: 12px;
            margin-bottom: 16px;
            text-align: center;
        }

        .school-name {
            margin: 0;
            font-size: 18px;
            font-weight: 700;
            letter-spacing: 0.3px;
        }

        .school-meta {
            margin-top: 3px;
            font-size: 12px;
            color: #4b5563;
        }

        .title {
            margin: 8px 0 0;
            font-size: 13px;
            font-weight: 600;
            letter-spacing: 0.2px;
            font-size: 12px;
            text-transform: uppercase;
        }

        .section {
            margin-bottom: 12px;
        }

        .finance-grid {
            display: grid;
            grid-template-columns: 1.15fr 0.85fr;
            gap: 10px;
            align-items: start;
        }

        .finance-grid > div {
            min-width: 0;
        }

        .section h2 {
            margin: 0 0 8px;
            font-size: 14px;
            text-transform: uppercase;
            letter-spacing: 0.4px;
            color: #111827;
        }

        .details-table th {
            width: 18%;
            background: #f9fafb;
            font-weight: 600;
            color: #374151;
        }

        .details-table td {
            width: 32%;
            font-weight: 600;
        }

        .student-info-table th,
        .student-info-table td {
            width: auto;
        }

        .kv-cell {
            padding: 6px 8px;
            text-align: center;
        }

        .kv-label {
            display: block;
            font-size: 10px;
            text-transform: uppercase;
            letter-spacing: 0.3px;
            color: #6b7280;
            margin-bottom: 2px;
            font-weight: 600;
        }

        .kv-value {
            display: block;
            font-size: 12px;
            color: #111827;
            font-weight: 600;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 12px;
            margin: 0;
        }

        th,
        td {
            border: 1px solid #d1d5db;
            padding: 7px 8px;
            text-align: left;
            vertical-align: middle;
        }

        th {
            background: #f3f4f6;
            font-weight: 600;
            color: #1f2937;
        }

        .align-right {
            text-align: right;
        }

        .assessment-table td:first-child {
            font-weight: 600;
        }

        .compact-table th,
        .compact-table td {
            padding-top: 6px;
            padding-bottom: 6px;
        }

        .note {
            font-size: 12px;
            line-height: 1.45;
            color: #374151;
            border: 1px solid #d1d5db;
            border-left: 3px solid #4b5563;
            background: #f9fafb;
            padding: 10px;
        }

        @media print {
            body {
                background: #fff;
            }

            .page {
                margin: 0;
                border: 0;
                padding: 0;
                max-width: none;
            }

            .details-table tr,
            .assessment-table tr,
            .billing-table tr,
            .accounts-table tr,
            .academic-table tr {
                break-inside: avoid;
                page-break-inside: avoid;
            }
        }

        @media (max-width: 920px) {
            .finance-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="page">
        <div class="header">
            <h1 class="school-name">Marriott School of Quezon City</h1>
            <div class="school-meta">Quezon City, Metro Manila, Philippines</div>
            <div class="school-meta">Email: admin@msqc.tech · Phone: +63 921 787 1567</div>
            <h2 class="title">Registration and Assessment Form</h2>
        </div>

        <div class="section">
            <h2>Student Information</h2>
            <table class="details-table compact-table student-info-table">
                <tbody>
                    <tr>
                        <td class="kv-cell" colspan="4">
                            <span class="kv-label">School Year</span>
                            <span class="kv-value">{{ $assessment['enrollment']['school_year'] ?: 'N/A' }}</span>
                        </td>
                        <td class="kv-cell" colspan="4">
                            <span class="kv-label">Grade Level</span>
                            <span class="kv-value">{{ $assessment['enrollment']['grade_level'] ?: 'N/A' }}</span>
                        </td>
                    </tr>
                    <tr>
                        <td class="kv-cell" colspan="2">
                            <span class="kv-label">LRN</span>
                            <span class="kv-value">{{ $assessment['student']['lrn'] ?: 'N/A' }}</span>
                        </td>
                        <td class="kv-cell" colspan="2">
                            <span class="kv-label">Last Name</span>
                            <span class="kv-value">{{ $assessment['student']['last_name'] ?: 'N/A' }}</span>
                        </td>
                        <td class="kv-cell" colspan="2">
                            <span class="kv-label">First Name</span>
                            <span class="kv-value">{{ $assessment['student']['first_name'] ?: 'N/A' }}</span>
                        </td>
                        <td class="kv-cell" colspan="2">
                            <span class="kv-label">Middle Name</span>
                            <span class="kv-value">{{ $assessment['student']['middle_name'] ?: 'N/A' }}</span>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div class="section">
            <h2>Academic</h2>
            <table class="details-table compact-table">
                <tbody>
                    <tr>
                        <th>Grade Level & Section</th>
                        <td>{{ $assessment['enrollment']['grade_level'] ?: 'N/A' }} - {{ $assessment['enrollment']['section'] ?: 'Unassigned' }}</td>
                        <th>Adviser</th>
                        <td>{{ $assessment['enrollment']['adviser'] ?: 'TBA' }}</td>
                    </tr>
                </tbody>
            </table>
            <table class="academic-table compact-table" style="margin-top:8px;">
                <thead>
                    <tr>
                        <th>Subject</th>
                        <th>Subject Teacher</th>
                        <th>Day</th>
                        <th>Time</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($assessment['academic']['schedule_compact_rows'] as $scheduleRow)
                        <tr>
                            <td>{{ $scheduleRow['subject'] }}</td>
                            <td>{{ $scheduleRow['teacher'] }}</td>
                            <td>{{ $scheduleRow['day'] }}</td>
                            <td>{{ $scheduleRow['time'] }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4">No class schedule assigned yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="section">
            <h2>Financial Information</h2>
            <div class="finance-grid">
                <div>
                    <table class="assessment-table compact-table">
                        <thead>
                            <tr>
                                <th>Assessment Breakdown</th>
                                <th class="align-right">Amount</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>Basic Tuition Fee</td>
                                <td class="align-right">PHP {{ number_format((float) $assessment['assessment']['tuition'], 2) }}</td>
                            </tr>
                            @forelse ($assessment['assessment']['breakdown'] as $feeRow)
                                <tr>
                                    <td>{{ $feeRow['name'] }}</td>
                                    <td class="align-right">PHP {{ number_format((float) $feeRow['amount'], 2) }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td>No miscellaneous or other fee items found.</td>
                                    <td class="align-right">PHP 0.00</td>
                                </tr>
                            @endforelse
                            <tr>
                                <td><strong>Total Assessment</strong></td>
                                <td class="align-right"><strong>PHP {{ number_format((float) $assessment['assessment']['total'], 2) }}</strong></td>
                            </tr>
                        </tbody>
                    </table>

                    <table class="assessment-table compact-table" style="margin-top:8px;">
                        <thead>
                            <tr>
                                <th>Other Charges and Adjustments</th>
                                <th class="align-right">Amount</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>Discounts / Scholarships</td>
                                <td class="align-right">(PHP {{ number_format((float) $assessment['assessment']['adjustments']['discounts_scholarships'], 2) }})</td>
                            </tr>
                            <tr>
                                <td>Other Charges</td>
                                <td class="align-right">PHP {{ number_format((float) $assessment['assessment']['adjustments']['other_charges'], 2) }}</td>
                            </tr>
                            <tr>
                                <td>Credit Adjustment</td>
                                <td class="align-right">(PHP {{ number_format((float) $assessment['assessment']['adjustments']['credit_adjustment'], 2) }})</td>
                            </tr>
                            <tr>
                                <td><strong>Net Assessment</strong></td>
                                <td class="align-right"><strong>PHP {{ number_format((float) $assessment['assessment']['net_assessment'], 2) }}</strong></td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div>
                    <table class="billing-table compact-table">
                        <thead>
                            <tr>
                                <th colspan="2">Payment Schedule</th>
                            </tr>
                            <tr>
                                <th>Due Date</th>
                                <th class="align-right">Amount Due</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($assessment['dues']['rows'] as $due)
                                <tr>
                                    <td>{{ $due['due_date_label'] }}</td>
                                    <td class="align-right">PHP {{ number_format((float) $due['amount_due'], 2) }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="2">No installment dues generated for this enrollment.</td>
                                </tr>
                            @endforelse
                        </tbody>
                        <tfoot>
                            <tr>
                                <th>Total Due</th>
                                <th class="align-right">PHP {{ number_format((float) $assessment['dues']['total_due'], 2) }}</th>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>

        <div class="section">
            <h2>How to Claim Your Accounts</h2>
            <div class="note">
                <strong>Student and Parent Account Claiming Steps:</strong><br>
                @if (! empty($assessment['enrollment']['email']))
                1. Check {{ $assessment['enrollment']['email'] }} for the account-claim email.<br>
                @else
                1. Message Marriott School (facebook.com/marriottschool) to register your email and request your account-claim link.<br>
                2. Once your email registration is confirmed in chat, check the email you provided for the account-claim link.<br>
                @endif
                3. Open the claim link and proceed to account verification.<br>
                4. Enter the enrolled mobile number and complete OTP verification.<br>
                5. Set your new password to finish claiming your account.<br>
                6. Use your claimed credentials to sign in to the portal.
            </div>
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
