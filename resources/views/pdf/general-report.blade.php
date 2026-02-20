<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>{{ $reportTitle }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #1a1a1a; line-height: 1.5; }
        .page { padding: 30px 40px; }

        .header { text-align: center; border-bottom: 3px solid #d2334c; padding-bottom: 15px; margin-bottom: 20px; }
        .header h1 { font-size: 18px; color: #d2334c; letter-spacing: 1px; margin-bottom: 2px; }
        .header h2 { font-size: 13px; color: #444; font-weight: normal; }
        .header .subtitle { font-size: 10px; color: #777; margin-top: 4px; }

        .meta { margin-bottom: 18px; }
        .meta table { width: 100%; }
        .meta td { border: none; padding: 3px 5px; font-size: 10px; }
        .meta .label { color: #888; text-transform: uppercase; font-weight: bold; font-size: 9px; }
        .meta .value { color: #222; font-weight: bold; }

        .section-title { font-size: 13px; font-weight: bold; color: #d2334c; margin-bottom: 8px; border-bottom: 1px solid #eee; padding-bottom: 4px; }

        table.data { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        table.data thead th { background: #d2334c; color: #fff; font-size: 9px; text-transform: uppercase; letter-spacing: 0.5px; padding: 8px 8px; text-align: left; }
        table.data tbody td { padding: 6px 8px; border-bottom: 1px solid #eee; font-size: 10px; }
        table.data tbody tr:nth-child(even) { background: #fafafa; }

        .status { display: inline-block; padding: 1px 8px; border-radius: 8px; font-size: 9px; font-weight: bold; }
        .status-issued, .status-active, .status-final { background: #d4edda; color: #155724; }
        .status-draft, .status-pending { background: #fff3cd; color: #856404; }
        .status-revoked, .status-expired { background: #f8d7da; color: #721c24; }

        .summary-box { background: #f8f8f8; border: 1px solid #e0e0e0; border-radius: 4px; padding: 10px 14px; margin-bottom: 18px; }
        .summary-box .stat { display: inline-block; margin-right: 25px; }
        .summary-box .stat-val { font-size: 16px; font-weight: bold; color: #d2334c; }
        .summary-box .stat-label { font-size: 9px; color: #888; text-transform: uppercase; }

        .text-right { text-align: right; }

        .footer { margin-top: 30px; padding-top: 10px; border-top: 1px solid #ddd; text-align: center; font-size: 9px; color: #999; }
        .footer .generated { font-style: italic; }

        .urgency-critical { background: #f8d7da; color: #721c24; }
        .urgency-warning { background: #fff3cd; color: #856404; }
        .urgency-ok { background: #d4edda; color: #155724; }
    </style>
</head>
<body>
    <div class="page">
        <div class="header">
            <h1>UNIVERSITY IMMACULATE CONCEPTION</h1>
            <h2>Clinical Laboratory</h2>
            <div class="subtitle">{{ $reportTitle }}</div>
        </div>

        <div class="meta">
            <table>
                <tr>
                    <td><span class="label">Date Range:</span> <span class="value">{{ \Carbon\Carbon::parse($startDate)->format('M d, Y') }} — {{ \Carbon\Carbon::parse($endDate)->format('M d, Y') }}</span></td>
                    <td><span class="label">Section:</span> <span class="value">{{ $sectionName ?? 'All Sections' }}</span></td>
                    <td style="text-align: right;"><span class="label">Total Records:</span> <span class="value">{{ $data->count() }}</span></td>
                </tr>
            </table>
        </div>

        @if($reportType === 'daily_collection')
            @if(isset($totalAmount))
            <div class="summary-box">
                <div class="stat">
                    <div class="stat-label">Total Transactions</div>
                    <div class="stat-val">{{ $data->count() }}</div>
                </div>
                <div class="stat">
                    <div class="stat-label">Total Amount</div>
                    <div class="stat-val">₱{{ number_format($totalAmount, 2) }}</div>
                </div>
            </div>
            @endif
            <div class="section-title">Daily Collection</div>
            <table class="data">
                <thead>
                    <tr>
                        <th>Date & Time</th>
                        <th>OR Number</th>
                        <th>Patient</th>
                        <th>Designation</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($data as $txn)
                    <tr>
                        <td>{{ $txn->datetime_added ? \Carbon\Carbon::parse($txn->datetime_added)->format('M d, Y h:i A') : '—' }}</td>
                        <td style="font-weight: bold;">{{ $txn->or_number ?? '—' }}</td>
                        <td>{{ $txn->patient->full_name ?? '—' }}</td>
                        <td>{{ $txn->client_designation ?? '—' }}</td>
                        <td>{{ ucfirst($txn->status_code ?? 'completed') }}</td>
                    </tr>
                    @empty
                    <tr><td colspan="5" style="text-align: center; color: #999; padding: 20px;">No transactions found.</td></tr>
                    @endforelse
                </tbody>
            </table>

        @elseif($reportType === 'revenue_by_test')
            @if(isset($totalAmount))
            <div class="summary-box">
                <div class="stat">
                    <div class="stat-label">Total Test Types</div>
                    <div class="stat-val">{{ $data->count() }}</div>
                </div>
                <div class="stat">
                    <div class="stat-label">Grand Revenue</div>
                    <div class="stat-val">₱{{ number_format($totalAmount, 2) }}</div>
                </div>
            </div>
            @endif
            <div class="section-title">Revenue by Test</div>
            <table class="data">
                <thead>
                    <tr>
                        <th>Test Name</th>
                        <th>Section</th>
                        <th style="text-align: right;">Unit Price</th>
                        <th style="text-align: right;">Total Orders</th>
                        <th style="text-align: right;">Total Revenue</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($data as $row)
                    <tr>
                        <td style="font-weight: bold;">{{ $row->test_name }}</td>
                        <td>{{ $row->section_name ?? '—' }}</td>
                        <td class="text-right">₱{{ number_format($row->current_price, 2) }}</td>
                        <td class="text-right">{{ number_format($row->total_orders) }}</td>
                        <td class="text-right" style="font-weight: bold;">₱{{ number_format($row->total_revenue, 2) }}</td>
                    </tr>
                    @empty
                    <tr><td colspan="5" style="text-align: center; color: #999; padding: 20px;">No revenue data found.</td></tr>
                    @endforelse
                </tbody>
            </table>

        @elseif($reportType === 'test_volume')
            <div class="section-title">Test Volume</div>
            <table class="data">
                <thead>
                    <tr>
                        <th>Test Name</th>
                        <th>Section</th>
                        <th style="text-align: right;">Total</th>
                        <th style="text-align: right;">Final</th>
                        <th style="text-align: right;">Draft</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($data as $vol)
                    <tr>
                        <td style="font-weight: bold;">{{ $vol->test->label ?? '—' }}</td>
                        <td>{{ $vol->test->section->label ?? '—' }}</td>
                        <td class="text-right">{{ number_format($vol->total_count) }}</td>
                        <td class="text-right">{{ number_format($vol->final_count) }}</td>
                        <td class="text-right">{{ number_format($vol->draft_count) }}</td>
                    </tr>
                    @empty
                    <tr><td colspan="5" style="text-align: center; color: #999; padding: 20px;">No test volume data found.</td></tr>
                    @endforelse
                </tbody>
            </table>

        @elseif($reportType === 'issued_certificates')
            <div class="section-title">Issued Certificates</div>
            <table class="data">
                <thead>
                    <tr>
                        <th>Certificate No.</th>
                        <th>Type</th>
                        <th>Patient</th>
                        <th>Issued By</th>
                        <th>Issue Date</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($data as $cert)
                    <tr>
                        <td style="font-weight: bold;">{{ $cert->certificate_number ?? '—' }}</td>
                        <td>{{ ucfirst($cert->certificate_type ?? '—') }}</td>
                        <td>{{ $cert->patient->full_name ?? '—' }}</td>
                        <td>{{ $cert->issuedBy->full_name ?? '—' }}</td>
                        <td>{{ $cert->issue_date ? \Carbon\Carbon::parse($cert->issue_date)->format('M d, Y') : '—' }}</td>
                        <td>
                            @php $cs = strtolower($cert->status ?? 'draft'); @endphp
                            <span class="status status-{{ $cs }}">{{ ucfirst($cert->status ?? 'draft') }}</span>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="6" style="text-align: center; color: #999; padding: 20px;">No certificates found.</td></tr>
                    @endforelse
                </tbody>
            </table>

        @elseif($reportType === 'activity_log')
            <div class="section-title">Activity Log</div>
            <table class="data">
                <thead>
                    <tr>
                        <th>Date & Time</th>
                        <th>Employee</th>
                        <th>Description</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($data as $log)
                    <tr>
                        <td>{{ $log->datetime_added ? \Carbon\Carbon::parse($log->datetime_added)->format('M d, Y h:i A') : '—' }}</td>
                        <td style="font-weight: bold;">{{ $log->employee->full_name ?? '—' }}</td>
                        <td>{{ $log->description ?? '—' }}</td>
                    </tr>
                    @empty
                    <tr><td colspan="3" style="text-align: center; color: #999; padding: 20px;">No activity logs found.</td></tr>
                    @endforelse
                </tbody>
            </table>

        @elseif($reportType === 'expiring_inventory')
            <div class="section-title">Expiring Inventory (Next 90 Days)</div>
            <table class="data">
                <thead>
                    <tr>
                        <th>Item Name</th>
                        <th>Section</th>
                        <th style="text-align: right;">Quantity</th>
                        <th>Expiry Date</th>
                        <th>Days Left</th>
                        <th>Urgency</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($data as $stock)
                    @php
                        $daysLeft = \Carbon\Carbon::now()->diffInDays(\Carbon\Carbon::parse($stock->expiry_date), false);
                        $urgency = $daysLeft <= 30 ? 'critical' : ($daysLeft <= 60 ? 'warning' : 'ok');
                    @endphp
                    <tr>
                        <td style="font-weight: bold;">{{ $stock->item->label ?? '—' }}</td>
                        <td>{{ $stock->item->section->label ?? '—' }}</td>
                        <td class="text-right">{{ $stock->quantity ?? 0 }}</td>
                        <td>{{ $stock->expiry_date ? \Carbon\Carbon::parse($stock->expiry_date)->format('M d, Y') : '—' }}</td>
                        <td>{{ $daysLeft }} days</td>
                        <td><span class="status urgency-{{ $urgency }}">{{ ucfirst($urgency) }}</span></td>
                    </tr>
                    @empty
                    <tr><td colspan="6" style="text-align: center; color: #999; padding: 20px;">No expiring inventory found.</td></tr>
                    @endforelse
                </tbody>
            </table>
        @endif

        <div class="footer">
            <p>This is a computer-generated report. No signature is required.</p>
            <p class="generated">Generated on {{ now()->format('F d, Y h:i A') }}</p>
        </div>
    </div>
</body>
</html>
