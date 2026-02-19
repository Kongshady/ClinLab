<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Certificate - {{ $certificate_number }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: 'DejaVu Sans', sans-serif;
            color: #1a1a2e;
            background: #fff;
            font-size: 12px;
        }
        .page {
            position: relative;
            width: 100%;
            min-height: 100%;
            padding: 40px;
        }
        .border-frame {
            border: 3px solid #1e40af;
            border-radius: 4px;
            padding: 40px;
            position: relative;
        }
        .border-frame::before {
            content: '';
            position: absolute;
            top: 4px;
            left: 4px;
            right: 4px;
            bottom: 4px;
            border: 1px solid #93c5fd;
            border-radius: 2px;
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
            position: relative;
            z-index: 1;
        }
        .header h1 {
            font-size: 28px;
            color: #1e40af;
            font-weight: bold;
            letter-spacing: 3px;
            text-transform: uppercase;
            margin-bottom: 5px;
        }
        .header .subtitle {
            font-size: 14px;
            color: #64748b;
            letter-spacing: 2px;
            text-transform: uppercase;
        }
        .header .org-name {
            font-size: 16px;
            font-weight: bold;
            color: #334155;
            margin-top: 8px;
        }
        .divider {
            height: 2px;
            background: #d2334c;
            margin: 20px 0;
        }
        .cert-type {
            text-align: center;
            font-size: 18px;
            font-weight: bold;
            color: #1e40af;
            text-transform: uppercase;
            letter-spacing: 2px;
            margin-bottom: 20px;
        }
        .cert-body {
            text-align: center;
            line-height: 1.8;
            font-size: 13px;
            color: #334155;
            margin-bottom: 25px;
            position: relative;
            z-index: 1;
        }
        .cert-body .patient-name {
            font-size: 22px;
            font-weight: bold;
            color: #1a1a2e;
            display: block;
            margin: 10px 0;
            border-bottom: 2px solid #1e40af;
            padding-bottom: 5px;
            display: inline-block;
        }
        .details-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 25px;
            position: relative;
            z-index: 1;
        }
        .details-table td {
            padding: 8px 12px;
            border-bottom: 1px solid #e2e8f0;
            font-size: 12px;
        }
        .details-table td.label {
            font-weight: bold;
            color: #64748b;
            width: 40%;
            text-transform: uppercase;
            font-size: 10px;
            letter-spacing: 1px;
        }
        .details-table td.value {
            color: #1a1a2e;
            font-weight: 600;
        }
        .status-badge {
            display: inline-block;
            padding: 4px 16px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        .status-issued {
            background: #d1fae5;
            color: #065f46;
        }
        .status-revoked {
            background: #fee2e2;
            color: #991b1b;
        }
        .status-expired {
            background: #f3f4f6;
            color: #4b5563;
        }
        .signatures {
            margin-top: 40px;
            position: relative;
            z-index: 1;
        }
        .signatures table {
            width: 100%;
        }
        .signatures td {
            text-align: center;
            padding: 0 20px;
            vertical-align: bottom;
        }
        .sig-line {
            border-top: 2px solid #1a1a2e;
            margin-top: 40px;
            padding-top: 5px;
            font-size: 12px;
            font-weight: bold;
            color: #1a1a2e;
        }
        .sig-title {
            font-size: 10px;
            color: #64748b;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        .footer {
            margin-top: 30px;
            text-align: center;
            font-size: 9px;
            color: #94a3b8;
            position: relative;
            z-index: 1;
        }
        .footer .verify-url {
            font-size: 10px;
            color: #1e40af;
            word-break: break-all;
        }
        .cert-number {
            font-family: 'DejaVu Sans Mono', monospace;
            font-size: 11px;
            color: #1e40af;
            text-align: right;
            margin-bottom: 15px;
        }
        .watermark {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%) rotate(-30deg);
            font-size: 100px;
            font-weight: bold;
            color: rgba(30, 64, 175, 0.04);
            text-transform: uppercase;
            white-space: nowrap;
            z-index: 0;
        }
    </style>
</head>
<body>
    <div class="page">
        <div class="border-frame">
            <div class="watermark">CERTIFICATE</div>

            {{-- Certificate Number --}}
            <div class="cert-number">No. {{ $certificate_number }}</div>

            {{-- Header --}}
            <div class="header">
                <h1>Certificate</h1>
                <div class="subtitle">of {{ $certificate_type }}</div>
                <div class="org-name">ClinLab - Clinical Laboratory</div>
            </div>

            <div class="divider"></div>

            {{-- Body --}}
            <div class="cert-body">
                <p>This is to certify that</p>
                <span class="patient-name">{{ $patient_name }}</span>
                <p>has been issued this certificate based on the records of our clinical laboratory.</p>
            </div>

            {{-- Details --}}
            <table class="details-table">
                <tr>
                    <td class="label">Certificate Number</td>
                    <td class="value">{{ $certificate_number }}</td>
                </tr>
                <tr>
                    <td class="label">Type</td>
                    <td class="value">{{ $certificate_type }}</td>
                </tr>
                @if($equipment_name)
                <tr>
                    <td class="label">Equipment</td>
                    <td class="value">{{ $equipment_name }}</td>
                </tr>
                @endif
                <tr>
                    <td class="label">Date Issued</td>
                    <td class="value">{{ $issue_date }}</td>
                </tr>
                @if($valid_until)
                <tr>
                    <td class="label">Valid Until</td>
                    <td class="value">{{ $valid_until }}</td>
                </tr>
                @endif
                <tr>
                    <td class="label">Status</td>
                    <td class="value">
                        @php
                            $statusClass = match(strtolower($status)) {
                                'issued' => 'status-issued',
                                'revoked' => 'status-revoked',
                                'expired' => 'status-expired',
                                default => 'status-issued',
                            };
                        @endphp
                        <span class="status-badge {{ $statusClass }}">{{ $status }}</span>
                    </td>
                </tr>
                @if($verification_code)
                <tr>
                    <td class="label">Verification Code</td>
                    <td class="value" style="font-family: 'DejaVu Sans Mono', monospace; color: #1e40af;">{{ $verification_code }}</td>
                </tr>
                @endif
            </table>

            {{-- Extra Certificate Data --}}
            @if(!empty($certificate_data))
            <table class="details-table">
                @foreach($certificate_data as $key => $value)
                <tr>
                    <td class="label">{{ ucwords(str_replace('_', ' ', $key)) }}</td>
                    <td class="value">{{ is_array($value) ? json_encode($value) : $value }}</td>
                </tr>
                @endforeach
            </table>
            @endif

            {{-- Signatures --}}
            <div class="signatures">
                <table>
                    <tr>
                        <td>
                            <div class="sig-line">{{ $issued_by }}</div>
                            <div class="sig-title">Issued By</div>
                        </td>
                        @if($verified_by)
                        <td>
                            <div class="sig-line">{{ $verified_by }}</div>
                            <div class="sig-title">Verified By</div>
                        </td>
                        @endif
                    </tr>
                </table>
            </div>

            {{-- Footer --}}
            <div class="footer">
                <div class="divider"></div>
                <p>This certificate was generated electronically by ClinLab System.</p>
                <p>Verify this certificate at:</p>
                <p class="verify-url">{{ $verify_url }}</p>
                <p style="margin-top: 8px; font-size: 8px;">Generated on {{ now()->format('F d, Y h:i A') }}</p>
            </div>
        </div>
    </div>
</body>
</html>
