<?php

namespace App\Http\Controllers;

use App\Models\LabResult;
use Illuminate\Http\Request;

class LabResultVerificationController extends Controller
{
    /**
     * Public verification page for lab results.
     * Accepts a serial number via URL or a search query.
     */
    public function verify(Request $request, $serial = null)
    {
        $code = $serial ?? $request->query('code');
        $result = null;

        if ($code) {
            $labResult = LabResult::with(['patient', 'test.section', 'performedBy', 'verifiedBy'])
                ->where('serial_number', $code)
                ->first();

            if ($labResult) {
                $result = [
                    'found' => true,
                    'valid' => !$labResult->is_revoked,
                    'serial_number' => $labResult->serial_number,
                    'status' => $labResult->is_revoked ? 'REVOKED' : 'VALID',
                    'patient_name' => $labResult->patient
                        ? ($labResult->patient->firstname . ' ' . $labResult->patient->lastname)
                        : 'N/A',
                    'test_name' => $labResult->test->label ?? 'N/A',
                    'section' => $labResult->test->section->label ?? 'N/A',
                    'result_date' => $labResult->result_date
                        ? $labResult->result_date->format('F d, Y')
                        : 'N/A',
                    'result_status' => ucfirst($labResult->status),
                    'performed_by' => $labResult->performedBy
                        ? ($labResult->performedBy->firstname . ' ' . $labResult->performedBy->lastname)
                        : null,
                    'verified_by' => $labResult->verifiedBy
                        ? ($labResult->verifiedBy->firstname . ' ' . $labResult->verifiedBy->lastname)
                        : null,
                    'printed_at' => $labResult->printed_at
                        ? $labResult->printed_at->format('F d, Y h:i A')
                        : null,
                ];
            } else {
                $result = [
                    'found' => false,
                ];
            }
        }

        return view('verify.lab-result', [
            'code' => $code,
            'result' => $result,
        ]);
    }
}
