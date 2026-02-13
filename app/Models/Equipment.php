<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Equipment extends Model
{
    protected $table = 'equipment';
    protected $primaryKey = 'equipment_id';
    public $timestamps = false;

    protected $fillable = [
        'name',
        'model',
        'serial_no',
        'section_id',
        'status',
        'purchase_date',
        'supplier',
        'remarks',
        'is_deleted',
        'datetime_added',
    ];

    protected $casts = [
        'purchase_date' => 'date',
        'datetime_added' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    public function scopeActive($query)
    {
        return $query->where('is_deleted', 0);
    }

    public function section()
    {
        return $this->belongsTo(Section::class, 'section_id', 'section_id');
    }

    public function maintenanceRecords()
    {
        return $this->hasMany(MaintenanceRecord::class, 'equipment_id', 'equipment_id');
    }

    public function calibrationRecords()
    {
        return $this->hasMany(CalibrationRecord::class, 'equipment_id', 'equipment_id');
    }

    public function latestMaintenance()
    {
        return $this->hasOne(MaintenanceRecord::class, 'equipment_id', 'equipment_id')
            ->where('is_deleted', 0)
            ->latest('performed_date');
    }

    public function latestCalibration()
    {
        return $this->hasOne(CalibrationRecord::class, 'equipment_id', 'equipment_id')
            ->where('is_deleted', 0)
            ->latest('calibration_date');
    }

    public function usageRecords()
    {
        return $this->hasMany(EquipmentUsage::class, 'equipment_id', 'equipment_id');
    }

    public function softDelete($employeeId = null)
    {
        $this->is_deleted = 1;
        $this->deleted_at = now();
        $this->deleted_by = $employeeId;
        return $this->save();
    }
}
