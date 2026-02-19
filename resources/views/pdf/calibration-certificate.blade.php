<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Calibration Certificate - {{ $record->equipment->name ?? 'Equipment' }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #1a1a1a; line-height: 1.5; }
        .page { padding: 30px 40px; position: relative; }

        /* Border */
        .border-frame { border: 3px double #d2334c; padding: 25px 30px; min-height: 700px; }

        /* Header */
        .header { text-align: center; margin-bottom: 25px; }
        .header h1 { font-size: 18px; color: #d2334c; letter-spacing: 2px; margin-bottom: 2px; }
        .header h2 { font-size: 13px; color: #444; font-weight: normal; margin-bottom: 5px; }
        .header .cert-title { font-size: 20px; font-weight: bold; color: #d2334c; text-transform: uppercase; letter-spacing: 3px; margin-top: 10px; border-top: 2px solid #d2334c; border-bottom: 2px solid #d2334c; padding: 8px 0; }
        .header .cert-no { font-size: 11px; color: #888; margin-top: 8px; font-weight: bold; }

        /* Info Sections */
        .section-title { font-size: 11px; font-weight: bold; color: #d2334c; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 8px; border-bottom: 1px solid #eee; padding-bottom: 3px; margin-top: 18px; }

        .info-table { width: 100%; margin-bottom: 15px; }
        .info-table td { padding: 4px 8px; font-size: 10px; border: none; vertical-align: top; }
        .info-table .label { color: #888; font-weight: bold; text-transform: uppercase; font-size: 9px; width: 30%; }
        .info-table .value { color: #222; font-weight: bold; }

        /* Result */
        .result-box { border: 2px solid #d2334c; border-radius: 4px; padding: 12px 15px; text-align: center; margin: 18px 0; }
        .result-label { font-size: 9px; text-transform: uppercase; color: #888; font-weight: bold; }
        .result-value { font-size: 18px; font-weight: bold; margin-top: 3px; }
        .result-pass { color: #155724; }
        .result-fail { color: #721c24; }

        /* Notes */
        .notes-box { background: #f8f8f8; border: 1px solid #e0e0e0; border-radius: 4px; padding: 10px 12px; margin-bottom: 18px; }
        .notes-box .label { font-size: 9px; text-transform: uppercase; color: #888; font-weight: bold; }
        .notes-box .text { font-size: 10px; color: #333; margin-top: 3px; }

        /* Signatures */
        .signatures { margin-top: 50px; }
        .signatures table { width: 100%; }
        .signatures td { border: none; width: 50%; text-align: center; padding: 0 25px; vertical-align: bottom; }
        .sig-line { border-top: 1px solid #333; margin-top: 50px; padding-top: 5px; }
        .sig-name { font-size: 11px; font-weight: bold; color: #222; }
        .sig-title { font-size: 9px; color: #888; }

        /* Footer */
        .footer { margin-top: 25px; text-align: center; font-size: 8px; color: #aaa; border-top: 1px solid #ddd; padding-top: 8px; }
    </style>
</head>
<body>
    <div class="page">
        <div class="border-frame">
            {{-- Header --}}
            <div class="header">
                <h1>UNIVERSITY IMMACULATE CONCEPTION</h1>
                <h2>Clinical Laboratory</h2>
                <div class="cert-title">Certificate of Calibration</div>
                @if(isset($certificateNo))
                    <div class="cert-no">Certificate No: {{ $certificateNo }}</div>
                @endif
            </div>

            {{-- Equipment Info --}}
            <div class="section-title">Equipment Information</div>
            <table class="info-table">
                <tr>
                    <td class="label">Equipment Name</td>
                    <td class="value">{{ $record->equipment->name ?? 'N/A' }}</td>
                    <td class="label">Model</td>
                    <td class="value">{{ $record->equipment->model ?? 'N/A' }}</td>
                </tr>
                <tr>
                    <td class="label">Serial Number</td>
                    <td class="value">{{ $record->equipment->serial_no ?? 'N/A' }}</td>
                    <td class="label">Section</td>
                    <td class="value">{{ $record->equipment->section->label ?? 'N/A' }}</td>
                </tr>
                <tr>
                    <td class="label">Supplier</td>
                    <td class="value">{{ $record->equipment->supplier ?? 'N/A' }}</td>
                    <td class="label">Status</td>
                    <td class="value">{{ $record->equipment->status ?? 'N/A' }}</td>
                </tr>
            </table>

            {{-- Calibration Details --}}
            <div class="section-title">Calibration Details</div>
            <table class="info-table">
                <tr>
                    <td class="label">Calibration Date</td>
                    <td class="value">{{ $record->calibration_date ? \Carbon\Carbon::parse($record->calibration_date)->format('F d, Y') : 'N/A' }}</td>
                    <td class="label">Next Calibration</td>
                    <td class="value">{{ $record->next_calibration_date ? \Carbon\Carbon::parse($record->next_calibration_date)->format('F d, Y') : 'N/A' }}</td>
                </tr>
                <tr>
                    <td class="label">Performed By</td>
                    <td class="value">{{ $record->performedBy ? $record->performedBy->full_name : 'N/A' }}</td>
                    <td class="label">Procedure</td>
                    <td class="value">{{ $record->procedure->name ?? 'N/A' }}</td>
                </tr>
            </table>

            {{-- Result --}}
            <div class="result-box">
                <div class="result-label">Calibration Result</div>
                @php
                    $rs = strtolower($record->result_status ?? '');
                    $isPass = in_array($rs, ['pass', 'passed', 'within tolerance']);
                @endphp
                <div class="result-value {{ $isPass ? 'result-pass' : 'result-fail' }}">
                    {{ strtoupper($record->result_status ?? 'N/A') }}
                </div>
            </div>

            {{-- Notes --}}
            @if($record->notes)
            <div class="notes-box">
                <div class="label">Notes / Observations</div>
                <div class="text">{{ $record->notes }}</div>
            </div>
            @endif

            {{-- Signatures --}}
            <div class="signatures">
                <table>
                    <tr>
                        <td>
                            <div class="sig-line">
                                <div class="sig-name">{{ $record->performedBy ? $record->performedBy->full_name : '________________________' }}</div>
                                <div class="sig-title">Calibration Technician</div>
                            </div>
                        </td>
                        <td>
                            <div class="sig-line">
                                <div class="sig-name">________________________</div>
                                <div class="sig-title">Laboratory Manager</div>
                            </div>
                        </td>
                    </tr>
                </table>
            </div>

            {{-- Footer --}}
            <div class="footer">
                <p>This certificate is computer-generated and is valid without a wet signature.</p>
                <p>Generated on {{ now()->format('F d, Y h:i A') }}</p>
                @if(isset($verificationCode))
                    <p>Verification Code: {{ $verificationCode }}</p>
                @endif
            </div>
        </div>
    </div>
</body>
</html>
