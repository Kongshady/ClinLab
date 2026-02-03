<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

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
    ];

    protected $casts = [
        'result_date' => 'datetime',
        'datetime_added' => 'datetime',
        'datetime_modified' => 'datetime',
    ];

    protected $appends = ['status_badge_class'];

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
}
