<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Section extends Model
{
    protected $table = 'section';
    protected $primaryKey = 'section_id';
    public $timestamps = false;

    protected $fillable = [
        'label',
    ];

    protected $casts = [
        'deleted_at' => 'datetime',
    ];

    // Scope to exclude soft deleted records
    public function scopeActive($query)
    {
        return $query->where('is_deleted', 0);
    }

    // Relationship with tests
    public function tests()
    {
        return $this->hasMany(Test::class, 'section_id', 'section_id');
    }

    // Soft delete method
    public function softDelete($employeeId = null)
    {
        $this->is_deleted = 1;
        $this->deleted_at = now();
        $this->deleted_by = $employeeId;
        return $this->save();
    }
}
