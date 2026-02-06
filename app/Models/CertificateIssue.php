<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CertificateIssue extends Model
{
    protected $fillable = [
        'template_id',
        'certificate_no',
        'verification_code',
        'issued_at',
        'valid_until',
        'generated_by',
        'status',
        'equipment_id',
        'calibration_id',
        'maintenance_id',
        'lab_result_id',
        'pdf_path',
    ];

    protected $casts = [
        'issued_at' => 'datetime',
        'valid_until' => 'datetime',
    ];

    /**
     * Get the template used for this certificate.
     */
    public function template()
    {
        return $this->belongsTo(CertificateTemplate::class, 'template_id');
    }

    /**
     * Get the user who generated this certificate.
     */
    public function generator()
    {
        return $this->belongsTo(User::class, 'generated_by');
    }

    /**
     * Get the equipment related to this certificate.
     */
    public function equipment()
    {
        return $this->belongsTo(Equipment::class, 'equipment_id', 'equipment_id');
    }

    /**
     * Get the calibration record (if applicable).
     */
    public function calibration()
    {
        return $this->belongsTo(CalibrationRecord::class, 'calibration_id');
    }

    /**
     * Scope to get only issued certificates.
     */
    public function scopeIssued($query)
    {
        return $query->where('status', 'Issued');
    }

    /**
     * Scope to filter by status.
     */
    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Check if certificate is valid.
     */
    public function isValid(): bool
    {
        if ($this->status !== 'Issued') {
            return false;
        }

        if ($this->valid_until && $this->valid_until->isPast()) {
            return false;
        }

        return true;
    }
}
