<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CalibrationRecord extends Model
{
    protected $table = 'calibration_record';
    protected $primaryKey = 'record_id';
    public $timestamps = false;

    protected $fillable = [
        'procedure_id',
        'equipment_id',
        'calibration_date',
        'performed_by',
        'result_status',
        'notes',
        'next_calibration_date',
        'datetime_added',
    ];

    protected $casts = [
        'calibration_date' => 'date',
        'next_calibration_date' => 'date',
        'datetime_added' => 'datetime',
    ];

    public function equipment()
    {
        return $this->belongsTo(Equipment::class, 'equipment_id', 'equipment_id');
    }

    public function performedBy()
    {
        return $this->belongsTo(Employee::class, 'performed_by', 'employee_id');
    }
}
