<?php

namespace App\Http\Controllers;

use App\Models\Certificate;
use App\Models\CertificateIssue;
use App\Models\LabResult;
use App\Models\Patient;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;

class PatientCertificateController extends Controller
{
    /**
     * Download a certificate PDF for the authenticated patient.
     */
    public function download(Request $request)
    {
        $user = auth()->user();
        $patient = Patient::where('user_id', $user->id)->firstOrFail();

        $source = $request->query('source');
        $id = $request->query('id');

        if ($source === 'certificate') {
            return $this->downloadCertificate($patient, $id);
        } elseif ($source === 'certificate_issue') {
            return $this->downloadCertificateIssue($patient, $id);
        }

        abort(404, 'Certificate not found.');
    }

    /**
     * Download from the certificate table.
     */
    private function downloadCertificate(Patient $patient, $id)
    {
        $cert = Certificate::with(['equipment', 'issuedBy', 'verifiedBy', 'patient'])
            ->where('certificate_id', $id)
            ->where('patient_id', $patient->patient_id)
            ->firstOrFail();

        // If a pre-generated PDF exists, serve it
        if ($cert->pdf_path && file_exists(storage_path('app/' . $cert->pdf_path))) {
            return response()->download(storage_path('app/' . $cert->pdf_path));
        }

        // Generate PDF on the fly
        $data = [
            'certificate_number' => $cert->certificate_number,
            'certificate_type' => ucfirst($cert->certificate_type),
            'patient_name' => $cert->patient ? $cert->patient->full_name : 'N/A',
            'equipment_name' => $cert->equipment ? $cert->equipment->equipment_name : null,
            'issue_date' => $cert->issue_date ? $cert->issue_date->format('F d, Y') : 'N/A',
            'issued_by' => $cert->issuedBy ? ($cert->issuedBy->firstname . ' ' . $cert->issuedBy->lastname) : 'N/A',
            'verified_by' => $cert->verifiedBy ? ($cert->verifiedBy->firstname . ' ' . $cert->verifiedBy->lastname) : null,
            'status' => ucfirst($cert->status),
            'certificate_data' => $cert->certificate_data,
            'valid_until' => null,
            'verification_code' => null,
            'verify_url' => url('/certificates/verify?code=' . $cert->certificate_number),
        ];

        $pdf = Pdf::loadView('pdf.certificate', $data);
        $pdf->setPaper('A4', 'portrait');

        $filename = 'Certificate_' . str_replace([' ', '/'], '_', $cert->certificate_number) . '.pdf';
        return $pdf->download($filename);
    }

    /**
     * Download from the certificate_issues table.
     */
    private function downloadCertificateIssue(Patient $patient, $id)
    {
        $labResultIds = LabResult::where('patient_id', $patient->patient_id)
            ->pluck('lab_result_id');

        $issue = CertificateIssue::with(['template', 'generator', 'equipment'])
            ->where('id', $id)
            ->whereIn('lab_result_id', $labResultIds)
            ->firstOrFail();

        // If a pre-generated PDF exists, serve it
        if ($issue->pdf_path && file_exists(storage_path('app/' . $issue->pdf_path))) {
            return response()->download(storage_path('app/' . $issue->pdf_path));
        }

        // Generate PDF on the fly
        $data = [
            'certificate_number' => $issue->certificate_no,
            'certificate_type' => $issue->template ? ucfirst($issue->template->type) : 'General',
            'patient_name' => $patient->full_name,
            'equipment_name' => $issue->equipment ? $issue->equipment->equipment_name : null,
            'issue_date' => $issue->issued_at ? $issue->issued_at->format('F d, Y') : 'N/A',
            'issued_by' => $issue->generator ? $issue->generator->name : 'N/A',
            'verified_by' => null,
            'status' => ucfirst($issue->status),
            'certificate_data' => null,
            'valid_until' => $issue->valid_until ? $issue->valid_until->format('F d, Y') : null,
            'verification_code' => $issue->verification_code,
            'verify_url' => url('/certificates/verify?code=' . ($issue->verification_code ?: $issue->certificate_no)),
        ];

        $pdf = Pdf::loadView('pdf.certificate', $data);
        $pdf->setPaper('A4', 'portrait');

        $filename = 'Certificate_' . str_replace([' ', '/'], '_', $issue->certificate_no) . '.pdf';
        return $pdf->download($filename);
    }

    /**
     * Public certificate verification endpoint.
     */
    public function verify(Request $request)
    {
        $code = $request->query('code');

        if (!$code) {
            return view('certificates.verify', ['result' => null, 'code' => '']);
        }

        // Search in certificate table
        $cert = Certificate::with(['patient', 'issuedBy'])
            ->where('certificate_number', $code)
            ->first();

        if ($cert) {
            $result = [
                'found' => true,
                'valid' => strtolower($cert->status) === 'issued',
                'number' => $cert->certificate_number,
                'type' => ucfirst($cert->certificate_type),
                'status' => ucfirst($cert->status),
                'issue_date' => $cert->issue_date ? $cert->issue_date->format('F d, Y') : null,
                'valid_until' => null,
                'patient' => $cert->patient ? $cert->patient->full_name : null,
                'issued_by' => $cert->issuedBy ? ($cert->issuedBy->firstname . ' ' . $cert->issuedBy->lastname) : null,
            ];

            return view('certificates.verify', ['result' => $result, 'code' => $code]);
        }

        // Search in certificate_issues table
        $issue = CertificateIssue::with(['template', 'generator'])
            ->where('certificate_no', $code)
            ->orWhere('verification_code', $code)
            ->first();

        if ($issue) {
            $result = [
                'found' => true,
                'valid' => $issue->isValid(),
                'number' => $issue->certificate_no,
                'type' => $issue->template ? ucfirst($issue->template->type) : 'General',
                'status' => ucfirst($issue->status),
                'issue_date' => $issue->issued_at ? $issue->issued_at->format('F d, Y') : null,
                'valid_until' => $issue->valid_until ? $issue->valid_until->format('F d, Y') : null,
                'patient' => null,
                'issued_by' => $issue->generator ? $issue->generator->name : null,
            ];

            return view('certificates.verify', ['result' => $result, 'code' => $code]);
        }

        return view('certificates.verify', [
            'result' => ['found' => false],
            'code' => $code,
        ]);
    }
}
