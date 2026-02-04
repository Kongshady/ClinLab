<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Certificate extends Model
{
    protected $table = 'certificate';
    protected $primaryKey = 'certificate_id';
    public $timestamps = false;

    protected $fillable = [
        'certificate_number',
        'template_id',
        'certificate_type',
        'linked_record_id',
        'patient_id',
        'equipment_id',
        'issue_date',
        'issued_by',
        'verified_by',
        'status',
        'certificate_data',
        'datetime_added',
    ];

    protected $casts = [
        'certificate_data' => 'array',
        'issue_date' => 'date',
        'datetime_added' => 'datetime',
    ];

    public function patient()
    {
        return $this->belongsTo(Patient::class, 'patient_id', 'patient_id');
    }

    public function equipment()
    {
        return $this->belongsTo(Equipment::class, 'equipment_id', 'equipment_id');
    }

    public function issuedBy()
    {
        return $this->belongsTo(Employee::class, 'issued_by', 'employee_id');
    }

    public function verifiedBy()
    {
        return $this->belongsTo(Employee::class, 'verified_by', 'employee_id');
    }
}
