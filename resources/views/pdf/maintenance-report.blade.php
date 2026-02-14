<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Maintenance Report</title>
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

        .status { display: inline-block; padding: 1px 8px; border-radius: 8px; font-size: 9px; font-weight: bold; }
        .status-active, .status-operational { background: #d4edda; color: #155724; }
        .status-maintenance, .status-under_maintenance { background: #fff3cd; color: #856404; }
        .status-retired, .status-decommissioned { background: #f8d7da; color: #721c24; }

        .summary-box { background: #f8f8f8; border: 1px solid #e0e0e0; border-radius: 4px; padding: 10px 14px; margin-bottom: 18px; }
        .summary-box .stat { display: inline-block; margin-right: 25px; }
        .summary-box .stat-val { font-size: 16px; font-weight: bold; color: #d1324a; }
        .summary-box .stat-label { font-size: 9px; color: #888; text-transform: uppercase; }

        .footer { margin-top: 30px; padding-top: 10px; border-top: 1px solid #ddd; text-align: center; font-size: 9px; color: #999; }
        .footer .generated { font-style: italic; }

        .equip-card { border: 1px solid #e0e0e0; border-radius: 4px; margin-bottom: 15px; page-break-inside: avoid; }
        .equip-header { background: #f8f8f8; padding: 8px 12px; border-bottom: 1px solid #e0e0e0; }
        .equip-header .name { font-size: 12px; font-weight: bold; color: #222; }
        .equip-header .serial { font-size: 9px; color: #888; }
        .equip-body { padding: 8px 12px; }
        .equip-info { display: inline-block; margin-right: 20px; margin-bottom: 5px; }
        .equip-info .lbl { font-size: 8px; text-transform: uppercase; color: #aaa; font-weight: bold; }
        .equip-info .val { font-size: 10px; color: #222; }
    </style>
</head>
<body>
    <div class="page">
        <div class="header">
            <h1>UNIVERSITY IMMACULATE CONCEPTION</h1>
            <h2>Clinical Laboratory</h2>
            <div class="subtitle">Equipment & Maintenance Report</div>
        </div>

        <div class="meta">
            <table>
                <tr>
                    <td><span class="label">Date Range:</span> <span class="value">{{ \Carbon\Carbon::parse($startDate)->format('M d, Y') }} — {{ \Carbon\Carbon::parse($endDate)->format('M d, Y') }}</span></td>
                    <td><span class="label">Section:</span> <span class="value">{{ $sectionName ?? 'All Sections' }}</span></td>
                    <td style="text-align: right;"><span class="label">Total Equipment:</span> <span class="value">{{ $data->count() }}</span></td>
                </tr>
            </table>
        </div>

        @if($reportType === 'equipment_maintenance')
            <div class="section-title">Equipment List</div>
            <table class="data">
                <thead>
                    <tr>
                        <th>Equipment Name</th>
                        <th>Model</th>
                        <th>Serial No.</th>
                        <th>Section</th>
                        <th>Status</th>
                        <th>Supplier</th>
                        <th>Purchase Date</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($data as $equipment)
                    <tr>
                        <td style="font-weight: bold;">{{ $equipment->name }}</td>
                        <td>{{ $equipment->model ?? '—' }}</td>
                        <td>{{ $equipment->serial_no ?? '—' }}</td>
                        <td>{{ $equipment->section->label ?? '—' }}</td>
                        <td>
                            @php
                                $sc = strtolower(str_replace(' ', '_', $equipment->status ?? ''));
                            @endphp
                            <span class="status status-{{ $sc }}">{{ $equipment->status ?? '—' }}</span>
                        </td>
                        <td>{{ $equipment->supplier ?? '—' }}</td>
                        <td>{{ $equipment->purchase_date ? \Carbon\Carbon::parse($equipment->purchase_date)->format('M d, Y') : '—' }}</td>
                    </tr>
                    @empty
                    <tr><td colspan="7" style="text-align: center; color: #999; padding: 20px;">No equipment records found.</td></tr>
                    @endforelse
                </tbody>
            </table>

        @elseif($reportType === 'calibration_records')
            <div class="section-title">Calibration Records</div>
            <table class="data">
                <thead>
                    <tr>
                        <th>Equipment</th>
                        <th>Section</th>
                        <th>Calibration Date</th>
                        <th>Result</th>
                        <th>Performed By</th>
                        <th>Next Calibration</th>
                        <th>Notes</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($data as $record)
                    <tr>
                        <td style="font-weight: bold;">{{ $record->equipment->name ?? '—' }}</td>
                        <td>{{ $record->equipment->section->label ?? '—' }}</td>
                        <td>{{ $record->calibration_date ? \Carbon\Carbon::parse($record->calibration_date)->format('M d, Y') : '—' }}</td>
                        <td>
                            @php
                                $rs = strtolower($record->result_status ?? '');
                            @endphp
                            <span class="status {{ $rs === 'pass' || $rs === 'passed' ? 'status-active' : 'status-retired' }}">
                                {{ ucfirst($record->result_status ?? '—') }}
                            </span>
                        </td>
                        <td>{{ $record->performedBy ? $record->performedBy->full_name : '—' }}</td>
                        <td>{{ $record->next_calibration_date ? \Carbon\Carbon::parse($record->next_calibration_date)->format('M d, Y') : '—' }}</td>
                        <td>{{ \Illuminate\Support\Str::limit($record->notes, 40) ?? '—' }}</td>
                    </tr>
                    @empty
                    <tr><td colspan="7" style="text-align: center; color: #999; padding: 20px;">No calibration records found.</td></tr>
                    @endforelse
                </tbody>
            </table>

        @elseif($reportType === 'laboratory_results')
            <div class="section-title">Laboratory Results</div>
            <table class="data">
                <thead>
                    <tr>
                        <th>Patient</th>
                        <th>Test</th>
                        <th>Result</th>
                        <th>Normal Range</th>
                        <th>Status</th>
                        <th>Date</th>
                        <th>Performed By</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($data as $result)
                    <tr>
                        <td style="font-weight: bold;">{{ $result->patient->full_name ?? '—' }}</td>
                        <td>{{ $result->test->label ?? '—' }}</td>
                        <td style="font-weight: bold;">{{ $result->result_value ?? '—' }}</td>
                        <td>{{ $result->normal_range ?? '—' }}</td>
                        <td>{{ ucfirst($result->status ?? '—') }}</td>
                        <td>{{ $result->result_date ? \Carbon\Carbon::parse($result->result_date)->format('M d, Y') : '—' }}</td>
                        <td>{{ $result->performedBy ? $result->performedBy->full_name : '—' }}</td>
                    </tr>
                    @empty
                    <tr><td colspan="7" style="text-align: center; color: #999; padding: 20px;">No laboratory results found.</td></tr>
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
