<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <title>Payments Report</title>
    <style>
        @page {
            margin: 15mm;
            size: A4 landscape;
        }

        body {
            font-family: Arial, Helvetica, sans-serif;
            font-size: 11px;
            margin: 0;
            padding: 0;
            line-height: 1.3;
        }

        .header {
            text-align: center;
            margin-bottom: 25px;
            border-bottom: 2px solid #000;
            padding-bottom: 15px;
        }

        .header h1 {
            font-size: 20px;
            margin: 0 0 8px 0;
            color: #000;
            font-weight: bold;
        }

        .header p {
            margin: 3px 0;
            color: #333;
            font-size: 10px;
        }

        .section {
            margin-bottom: 25px;
        }

        .section h2 {
            font-size: 14px;
            margin-bottom: 12px;
            color: #000;
            font-weight: bold;
            border-bottom: 1px solid #666;
            padding-bottom: 3px;
        }

        .summary-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
        }

        .summary-table td {
            border: 1px solid #333;
            padding: 10px;
            text-align: center;
            vertical-align: top;
            width: 20%;
        }

        .summary-value {
            font-size: 16px;
            font-weight: bold;
            margin-bottom: 5px;
            color: #000;
        }

        .summary-label {
            font-size: 10px;
            color: #333;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }

        th,
        td {
            border: 1px solid #333;
            padding: 6px;
            text-align: left;
            vertical-align: top;
        }

        th {
            background-color: #f0f0f0;
            font-weight: bold;
            font-size: 10px;
        }

        td {
            font-size: 10px;
        }

        .text-center {
            text-align: center;
        }

        .text-right {
            text-align: right;
        }

        .footer {
            text-align: center;
            margin-top: 30px;
            padding-top: 15px;
            border-top: 1px solid #666;
            color: #333;
            font-size: 9px;
        }

        .page-break {
            page-break-before: always;
        }

        .no-border {
            border: none;
        }

        .bold {
            font-weight: bold;
        }

        .small {
            font-size: 9px;
        }
    </style>
</head>

<body>
    <!-- Header -->
    <div class="header">
        <h1>Payments Report</h1>
        <p>Generated: {{ $generatedAt }}</p>
        <p>By: {{ $user->name }} ({{ $user->email }})</p>
    </div>

    <!-- Filters -->
    @if (!empty($filterDescription))
        <div class="section">
            <h2>Applied Filters</h2>
            <p><strong>Filter Type:</strong> {{ request('time_filter', 'All Time') }}</p>
            @foreach ($filterDescription as $filter)
                <p class="small">{{ $filter }}</p>
            @endforeach
        </div>
    @endif

    <!-- Summary Statistics -->
    <div class="section">
        <h2>Summary Statistics</h2>
        <table class="summary-table">
            <tr>
                <td>
                    <div class="summary-value">{{ number_format($summary['total_transactions']) }}</div>
                    <div class="summary-label">Total Transactions</div>
                </td>
                <td>
                    <div class="summary-value">N{{ number_format($summary['total_amount'], 0) }}</div>
                    <div class="summary-label">Total Amount</div>
                </td>
                <td>
                    <div class="summary-value">{{ number_format($summary['pending_count']) }}</div>
                    <div class="summary-label">Pending</div>
                </td>
                <td>
                    <div class="summary-value">{{ number_format($summary['successful_count']) }}</div>
                    <div class="summary-label">Successful</div>
                </td>
                <td>
                    <div class="summary-value">{{ number_format($summary['rejected_count']) }}</div>
                    <div class="summary-label">Rejected</div>
                </td>
            </tr>
        </table>
    </div>

    <!-- Transaction Type Analysis -->
    <div class="section">
        <h2>Transaction Type Analysis</h2>
        <table>
            <thead>
                <tr>
                    <th>Type</th>
                    <th class="text-center">Count</th>
                    <th class="text-right">Amount</th>
                    <th class="text-center">Pending</th>
                    <th class="text-center">Successful</th>
                    <th class="text-center">Rejected</th>
                    <th class="text-right">Success Rate</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($typeBreakdown as $type => $data)
                    <tr>
                        <td>
                            @if ($type == 'daily_remittance')
                                Daily Remittance
                            @elseif($type == 'charging_payment' || substr($reference ?? '', 0, 6) == 'CHARGE')
                                Charging Payment
                            @elseif($type == 'maintenance_debit')
                                Maintenance
                            @elseif($type == 'wallet_funding')
                                Wallet Funding
                            @else
                                {{ str_replace('_', ' ', ucfirst($type)) }}
                            @endif
                        </td>
                        <td class="text-center">{{ number_format($data['count']) }}</td>
                        <td class="text-right">N{{ number_format($data['amount'], 2) }}</td>
                        <td class="text-center">{{ $data['pending_count'] }}</td>
                        <td class="text-center">{{ $data['successful_count'] }}</td>
                        <td class="text-center">{{ $data['rejected_count'] }}</td>
                        <td class="text-right">
                            @if ($data['count'] > 0)
                                {{ round(($data['successful_count'] / $data['count']) * 100, 1) }}%
                            @else
                                0%
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <!-- Detailed Transactions -->
    <div class="page-break"></div>
    <div class="section">
        <h2>Detailed Transactions</h2>
        <table>
            <thead>
                <tr>
                    <th>SN</th>
                    <th>Driver</th>
                    <th>Branch</th>
                    <th>Type</th>
                    <th class="text-right">Amount</th>
                    <th class="text-center">Status</th>
                    <th>Processed By</th>
                    <th>Date & Time</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($transactions as $transaction)
                    <tr>
                        <td class="bold">{{ $loop->iteration }}</td>
                        <td>{{ $transaction->driver->full_name ?? 'Unknown Driver' }}</td>
                        <td>{{ $transaction->driver->branch->name ?? 'N/A' }}</td>
                        <td>
                            @if ($transaction->type == 'daily_remittance')
                                Daily Remittance
                            @elseif($transaction->type == 'charging_payment' || substr($transaction->reference ?? '', 0, 6) == 'CHARGE')
                                Charging Payment
                            @elseif($transaction->type == 'maintenance_debit')
                                Maintenance
                            @elseif($transaction->type == 'wallet_funding')
                                Wallet Funding
                            @else
                                {{ str_replace('_', ' ', ucfirst($transaction->type)) }}
                            @endif
                        </td>
                        <td class="text-right bold">N{{ number_format($transaction->amount ?? 0, 2) }}</td>
                        <td class="text-center">{{ $transaction->status ?? 'N/A' }}</td>
                        <td>{{ $transaction->approver?->name ?? 'N/A' }}</td>
                        <td>{{ $transaction->created_at?->format('M d, Y H:i') ?? 'N/A' }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <!-- Footer -->
    <div class="footer">
        <p><strong>eRide System - Payments Report</strong></p>
        <p>Generated on {{ $generatedAt }} | Total Records: {{ $transactions->count() }}</p>
        <p> {{ date('Y') }} eRide System. All rights reserved.</p>
    </div>
</body>

</html>
