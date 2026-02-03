<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Patient extends Model
{
    protected $table = 'patient';
    protected $primaryKey = 'patient_id';
    public $timestamps = false;

    protected $fillable = [
        'patient_type',
        'firstname',
        'middlename',
        'lastname',
        'birthdate',
        'gender',
        'contact_number',
        'address',
        'status_code',
    ];

    protected $casts = [
        'birthdate' => 'date',
        'datetime_added' => 'datetime',
        'datetime_updated' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    // Scope to exclude soft deleted records
    public function scopeActive($query)
    {
        return $query->where('is_deleted', 0);
    }

    // Soft delete method
    public function softDelete($employeeId = null)
    {
        $this->is_deleted = 1;
        $this->deleted_at = now();
        $this->deleted_by = $employeeId;
        return $this->save();
    }

    // Get full name
    public function getFullNameAttribute()
    {
        return trim("{$this->firstname} {$this->middlename} {$this->lastname}");
    }
}
