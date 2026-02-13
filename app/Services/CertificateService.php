<?php

namespace App\Services;

use App\Models\CertificateTemplate;
use App\Models\CertificateIssue;
use App\Models\Equipment;
use App\Models\CalibrationRecord;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Str;

class CertificateService
{
    /**
     * Generate a certificate from a calibration record.
     */
    public function generateFromCalibration($calibrationId, $equipmentId)
    {
        $template = CertificateTemplate::active()
            ->ofType('calibration')
            ->first();

        if (!$template) {
            throw new \Exception('No active calibration certificate template found');
        }

        $calibration = CalibrationRecord::with('performedBy')->find($calibrationId);
        $equipment = Equipment::find($equipmentId);

        if (!$calibration || !$equipment) {
            throw new \Exception('Calibration record or equipment not found');
        }

        // Generate unique certificate number and verification code
        $certificateNo = $this->generateCertificateNumber('CAL');
        $verificationCode = $this->generateVerificationCode();

        // Calculate valid until date (1 year from issue date)
        $validUntil = now()->addYear();

        // Create certificate issue record
        $certificate = CertificateIssue::create([
            'template_id' => $template->id,
            'certificate_no' => $certificateNo,
            'verification_code' => $verificationCode,
            'issued_at' => now(),
            'valid_until' => $validUntil,
            'generated_by' => auth()->id(),
            'status' => 'Pending',
            'equipment_id' => $equipmentId,
            'calibration_id' => $calibrationId,
        ]);

        // Generate PDF
        $data = $this->prepareCalibrationData($certificate, $calibration, $equipment);
        $html = $this->fillTemplate($template->body_html, $data);
        
        $pdf = Pdf::loadHTML($html)->setPaper('a4', 'portrait');

        return [
            'certificate' => $certificate,
            'pdf' => $pdf,
        ];
    }

    /**
     * Generate a certificate from a maintenance record.
     */
    public function generateFromMaintenance($maintenanceId, $equipmentId)
    {
        $template = CertificateTemplate::active()
            ->ofType('maintenance')
            ->first();

        if (!$template) {
            throw new \Exception('No active maintenance certificate template found');
        }

        $certificateNo = $this->generateCertificateNumber('MAINT');
        $verificationCode = $this->generateVerificationCode();

        $certificate = CertificateIssue::create([
            'template_id' => $template->id,
            'certificate_no' => $certificateNo,
            'verification_code' => $verificationCode,
            'issued_at' => now(),
            'valid_until' => now()->addYear(),
            'generated_by' => auth()->id(),
            'status' => 'Pending',
            'equipment_id' => $equipmentId,
            'maintenance_id' => $maintenanceId,
        ]);

        return $certificate;
    }

    /**
     * Prepare data placeholders for calibration certificate.
     */
    private function prepareCalibrationData($certificate, $calibration, $equipment)
    {
        return [
            'certificate_no' => $certificate->certificate_no,
            'verification_code' => $certificate->verification_code,
            'issue_date' => $certificate->issued_at->format('F d, Y'),
            'equipment_name' => $equipment->name ?? 'N/A',
            'equipment_model' => $equipment->model ?? 'N/A',
            'serial_no' => $equipment->serial_no ?? 'N/A',
            'calibration_date' => $calibration->calibration_date ? date('F d, Y', strtotime($calibration->calibration_date)) : 'N/A',
            'due_date' => $calibration->next_calibration_date ? date('F d, Y', strtotime($calibration->next_calibration_date)) : 'N/A',
            'result' => strtoupper($calibration->result_status ?? 'PASSED'),
            'performed_by' => $calibration->performedBy ? ($calibration->performedBy->firstname . ' ' . $calibration->performedBy->lastname) : 'N/A',
        ];
    }

    /**
     * Fill template with data.
     */
    private function fillTemplate($html, $data)
    {
        foreach ($data as $key => $value) {
            $html = str_replace('{{' . $key . '}}', $value, $html);
        }
        return $html;
    }

    /**
     * Generate unique certificate number.
     */
    private function generateCertificateNumber($prefix)
    {
        $year = date('Y');
        $latest = CertificateIssue::where('certificate_no', 'like', $prefix . '-' . $year . '-%')
            ->latest('id')
            ->first();

        $number = $latest ? ((int)substr($latest->certificate_no, -5)) + 1 : 1;

        return sprintf('%s-%s-%05d', $prefix, $year, $number);
    }

    /**
     * Generate unique verification code.
     */
    private function generateVerificationCode()
    {
        do {
            $code = strtoupper(Str::random(16));
        } while (CertificateIssue::where('verification_code', $code)->exists());

        return $code;
    }
}
