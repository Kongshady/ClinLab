<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class LabResult extends Model
{
    protected $table = 'lab_result';
    protected $primaryKey = 'lab_result_id';
    public $timestamps = false;

    protected $fillable = [
        'order_test_id',
        'lab_test_order_id',
        'patient_id',
        'test_id',
        'result_date',
        'findings',
        'normal_range',
        'result_value',
        'remarks',
        'performed_by',
        'verified_by',
        'status',
        'serial_number',
        'verification_code',
        'is_revoked',
        'printed_at',
    ];

    protected $casts = [
        'result_date' => 'datetime',
        'datetime_added' => 'datetime',
        'datetime_modified' => 'datetime',
        'printed_at' => 'datetime',
        'is_revoked' => 'boolean',
    ];

    protected $appends = ['status_badge_class'];

    /**
     * Get the route key name for Laravel route model binding.
     */
    public function getRouteKeyName()
    {
        return 'lab_result_id';
    }

    // Relationship with Patient
    public function patient()
    {
        return $this->belongsTo(Patient::class, 'patient_id', 'patient_id');
    }

    // Relationship with Test
    public function test()
    {
        return $this->belongsTo(Test::class, 'test_id', 'test_id');
    }

    // Relationship with Employee who performed the test
    public function performedBy()
    {
        return $this->belongsTo(Employee::class, 'performed_by', 'employee_id');
    }

    // Relationship with Employee who verified the test
    public function verifiedBy()
    {
        return $this->belongsTo(Employee::class, 'verified_by', 'employee_id');
    }

    // Relationship with Lab Test Order
    public function labTestOrder()
    {
        return $this->belongsTo(LabTestOrder::class, 'lab_test_order_id', 'lab_test_order_id');
    }

    // Relationship with Order Test
    public function orderTest()
    {
        return $this->belongsTo(OrderTest::class, 'order_test_id', 'order_test_id');
    }

    // Accessor for status badge class
    public function getStatusBadgeClassAttribute()
    {
        return match($this->status) {
            'draft' => 'bg-yellow-100 text-yellow-800',
            'final' => 'bg-green-100 text-green-800',
            'revised' => 'bg-blue-100 text-blue-800',
            default => 'bg-gray-100 text-gray-800',
        };
    }

    /**
     * Generate a unique serial number for a lab result.
     * Format: LR-YYYY-NNNNNN (e.g. LR-2026-000001)
     */
    public static function generateSerialNumber(): string
    {
        $year = now()->year;
        $prefix = "LR-{$year}-";

        // Get the max serial number for this year
        $latest = static::where('serial_number', 'like', $prefix . '%')
            ->orderByRaw("CAST(RIGHT(serial_number, 6) AS INT) DESC")
            ->value('serial_number');

        if ($latest) {
            $lastNumber = (int) substr($latest, -6);
            $nextNumber = $lastNumber + 1;
        } else {
            $nextNumber = 1;
        }

        return $prefix . str_pad($nextNumber, 6, '0', STR_PAD_LEFT);
    }

    /**
     * Generate a verification code (random hash for extra security).
     */
    public static function generateVerificationCode(): string
    {
        return Str::random(32);
    }

    /**
     * Assign serial number and verification code if not already set.
     * Called when result becomes 'final' or when printing.
     */
    public function assignSerialNumber(): string
    {
        if (!$this->serial_number) {
            $this->serial_number = static::generateSerialNumber();
            $this->verification_code = static::generateVerificationCode();
            $this->save();
        }

        return $this->serial_number;
    }

    /**
     * Revoke this lab result.
     */
    public function revoke(): void
    {
        $this->is_revoked = true;
        $this->save();
    }

    /**
     * Mark as printed.
     */
    public function markAsPrinted(): void
    {
        if (!$this->printed_at) {
            $this->printed_at = now();
            $this->save();
        }
    }

    /**
     * Get the public verification URL for this result.
     */
    public function getVerificationUrl(): string
    {
        return url("/verify/lab-result/{$this->serial_number}");
    }

    /**
     * Generate QR code as base64 PNG for embedding in PDFs.
     */
    public function generateQrCodeBase64(): string
    {
        $url = $this->getVerificationUrl();

        // Generate QR code using chillerlan/php-qrcode (no external service needed)
        $qrCode = new \chillerlan\QRCode\QRCode(new \chillerlan\QRCode\QROptions([
            'outputType' => \chillerlan\QRCode\QRCode::OUTPUT_IMAGE_PNG,
            'eccLevel' => \chillerlan\QRCode\Common\EccLevel::M,
            'scale' => 5,
            'imageBase64' => false,
        ]));

        $imageData = $qrCode->render($url);

        return base64_encode($imageData);
    }
}
