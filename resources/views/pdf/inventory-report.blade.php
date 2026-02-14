<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Inventory Report</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #1a1a1a; line-height: 1.5; }
        .page { padding: 30px 40px; }

        .header { text-align: center; border-bottom: 3px solid #d1324a; padding-bottom: 15px; margin-bottom: 20px; }
        .header h1 { font-size: 18px; color: #d1324a; letter-spacing: 1px; margin-bottom: 2px; }
        .header h2 { font-size: 13px; color: #444; font-weight: normal; }
        .header .subtitle { font-size: 10px; color: #777; margin-top: 4px; }

        .meta { margin-bottom: 18px; }
        .meta table { width: 100%; }
        .meta td { border: none; padding: 3px 5px; font-size: 10px; }
        .meta .label { color: #888; text-transform: uppercase; font-weight: bold; font-size: 9px; }
        .meta .value { color: #222; font-weight: bold; }

        .section-title { font-size: 13px; font-weight: bold; color: #d1324a; margin-bottom: 8px; border-bottom: 1px solid #eee; padding-bottom: 4px; }

        table.data { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        table.data thead th { background: #d1324a; color: #fff; font-size: 9px; text-transform: uppercase; letter-spacing: 0.5px; padding: 8px 8px; text-align: left; }
        table.data tbody td { padding: 6px 8px; border-bottom: 1px solid #eee; font-size: 10px; }
        table.data tbody tr:nth-child(even) { background: #fafafa; }

        .type-in { display: inline-block; padding: 1px 8px; border-radius: 8px; font-size: 9px; font-weight: bold; background: #d4edda; color: #155724; }
        .type-out { display: inline-block; padding: 1px 8px; border-radius: 8px; font-size: 9px; font-weight: bold; background: #f8d7da; color: #721c24; }

        .alert { display: inline-block; padding: 1px 8px; border-radius: 8px; font-size: 9px; font-weight: bold; background: #f8d7da; color: #721c24; }
        .ok { display: inline-block; padding: 1px 8px; border-radius: 8px; font-size: 9px; font-weight: bold; background: #d4edda; color: #155724; }

        .footer { margin-top: 30px; padding-top: 10px; border-top: 1px solid #ddd; text-align: center; font-size: 9px; color: #999; }
        .footer .generated { font-style: italic; }

        .summary-box { background: #f8f8f8; border: 1px solid #e0e0e0; border-radius: 4px; padding: 10px 14px; margin-bottom: 18px; }
        .summary-box .stat { display: inline-block; margin-right: 25px; }
        .summary-box .stat-val { font-size: 16px; font-weight: bold; color: #d1324a; }
        .summary-box .stat-label { font-size: 9px; color: #888; text-transform: uppercase; }
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

        @if($reportType === 'inventory_movement')
            @php
                $totalIn = $data->where('type', 'Stock In')->sum('quantity');
                $totalOut = $data->where('type', 'Stock Out')->sum('quantity');
            @endphp
            <div class="summary-box">
                <span class="stat"><span class="stat-val">{{ number_format($totalIn) }}</span><br><span class="stat-label">Total Stock In</span></span>
                <span class="stat"><span class="stat-val">{{ number_format($totalOut) }}</span><br><span class="stat-label">Total Stock Out</span></span>
                <span class="stat"><span class="stat-val">{{ number_format($totalIn - $totalOut) }}</span><br><span class="stat-label">Net Movement</span></span>
            </div>

            <div class="section-title">Inventory Movement Details</div>
            <table class="data">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Item</th>
                        <th>Section</th>
                        <th>Type</th>
                        <th>Qty</th>
                        <th>Supplier</th>
                        <th>Reference #</th>
                        <th>Remarks</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($data as $row)
                    <tr>
                        <td>{{ $row->date ? \Carbon\Carbon::parse($row->date)->format('M d, Y') : '—' }}</td>
                        <td style="font-weight: bold;">{{ $row->item->label ?? '—' }}</td>
                        <td>{{ $row->item->section->label ?? '—' }}</td>
                        <td><span class="{{ $row->type === 'Stock In' ? 'type-in' : 'type-out' }}">{{ $row->type }}</span></td>
                        <td style="font-weight: bold;">{{ $row->quantity }}</td>
                        <td>{{ $row->supplier ?? '—' }}</td>
                        <td>{{ $row->reference_number ?? '—' }}</td>
                        <td>{{ \Illuminate\Support\Str::limit($row->remarks, 30) ?? '—' }}</td>
                    </tr>
                    @empty
                    <tr><td colspan="8" style="text-align: center; color: #999; padding: 20px;">No records found.</td></tr>
                    @endforelse
                </tbody>
            </table>

        @elseif($reportType === 'low_stock_alert')
            <div class="section-title">Low Stock Items</div>
            <table class="data">
                <thead>
                    <tr>
                        <th>Item</th>
                        <th>Section</th>
                        <th>Unit</th>
                        <th>Current Stock</th>
                        <th>Reorder Level</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($data as $item)
                    <tr>
                        <td style="font-weight: bold;">{{ $item->label }}</td>
                        <td>{{ $item->section->label ?? '—' }}</td>
                        <td>{{ $item->unit ?? '—' }}</td>
                        <td style="font-weight: bold;">{{ $item->current_stock ?? 0 }}</td>
                        <td>{{ $item->reorder_level }}</td>
                        <td>
                            @if(($item->current_stock ?? 0) <= 0)
                                <span class="alert">Out of Stock</span>
                            @else
                                <span class="alert">Low Stock</span>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="6" style="text-align: center; color: #999; padding: 20px;">No low stock items found.</td></tr>
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
