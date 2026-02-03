<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Physician extends Model
{
    protected $table = 'physician';
    protected $primaryKey = 'physician_id';
    public $timestamps = false;

    protected $fillable = [
        'physician_name',
        'specialization',
        'contact_number',
        'email',
        'status_code',
    ];

    protected $casts = [
        'datetime_added' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    public function scopeActive($query)
    {
        return $query->where('is_deleted', 0);
    }

    public function softDelete($employeeId = null)
    {
        $this->is_deleted = 1;
        $this->deleted_at = now();
        $this->deleted_by = $employeeId;
        return $this->save();
    }
}
