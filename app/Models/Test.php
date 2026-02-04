<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Test extends Model
{
    protected $table = 'test';
    protected $primaryKey = 'test_id';
    public $timestamps = false;

    protected $fillable = [
        'section_id',
        'label',
        'current_price',
        'previous_price',
    ];

    protected $casts = [
        'current_price' => 'decimal:2',
        'deleted_at' => 'datetime',
    ];

    // Scope to exclude soft deleted records
    public function scopeActive($query)
    {
        return $query->where('is_deleted', 0);
    }

    // Relationship with section
    public function section()
    {
        return $this->belongsTo(Section::class, 'section_id', 'section_id');
    }

    // Relationship with price history
    public function priceHistory()
    {
        return $this->hasMany(TestPriceHistory::class, 'test_id', 'test_id')->orderBy('updated_at', 'desc');
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
