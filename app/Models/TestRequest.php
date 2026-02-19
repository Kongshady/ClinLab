<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TestRequest extends Model
{
    protected $table = 'test_requests';
    protected $primaryKey = 'id';
    public $timestamps = false;

    protected $fillable = [
        'patient_id',
        'requested_by_user_id',
        'purpose',
        'preferred_date',
        'status',
        'staff_remarks',
        'reviewed_by',
        'reviewed_at',
        'datetime_added',
        'datetime_updated',
    ];

    protected $casts = [
        'preferred_date' => 'date',
        'reviewed_at' => 'datetime',
        'datetime_added' => 'datetime',
        'datetime_updated' => 'datetime',
    ];

    /**
     * Get the patient who owns this request.
     */
    public function patient()
    {
        return $this->belongsTo(Patient::class, 'patient_id', 'patient_id');
    }

    /**
     * Get the user who submitted this request.
     */
    public function requestedBy()
    {
        return $this->belongsTo(User::class, 'requested_by_user_id', 'id');
    }

    /**
     * Get the staff user who reviewed this request.
     */
    public function reviewer()
    {
        return $this->belongsTo(User::class, 'reviewed_by', 'id');
    }

    /**
     * Get the test items for this request.
     */
    public function items()
    {
        return $this->hasMany(TestRequestItem::class, 'request_id', 'id');
    }

    /**
     * Status badge helper.
     */
    public function getStatusBadgeAttribute(): array
    {
        return match ($this->status) {
            'PENDING'  => ['label' => 'Pending',  'class' => 'bg-amber-100 text-amber-700'],
            'APPROVED' => ['label' => 'Approved', 'class' => 'bg-emerald-100 text-emerald-700'],
            'REJECTED' => ['label' => 'Rejected', 'class' => 'bg-red-100 text-red-700'],
            'CANCELLED' => ['label' => 'Cancelled', 'class' => 'bg-gray-100 text-gray-500'],
            default     => ['label' => $this->status, 'class' => 'bg-gray-100 text-gray-500'],
        };
    }

    /**
     * Scope: only pending requests.
     */
    public function scopePending($query)
    {
        return $query->where('status', 'PENDING');
    }

    /**
     * Scope: filter by patient.
     */
    public function scopeForPatient($query, $patientId)
    {
        return $query->where('patient_id', $patientId);
    }
}
