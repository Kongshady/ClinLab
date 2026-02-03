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

    public function softDelete($employeeId = null)
    {
        $this->is_deleted = 1;
        $this->deleted_at = now();
        $this->deleted_by = $employeeId;
        return $this->save();
    }
}
