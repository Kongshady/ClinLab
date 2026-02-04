<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Item extends Model
{
    protected $table = 'item';
    protected $primaryKey = 'item_id';
    public $timestamps = false;

    protected $fillable = [
        'section_id',
        'item_type_id',
        'label',
        'status_code',
        'unit',
        'reorder_level',
    ];

    protected $casts = [
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

    public function itemType()
    {
        return $this->hasOne(\stdClass::class, 'item_type_id', 'item_type_id')
            ->from('item_type');
    }

    public function softDelete($employeeId = null)
    {
        $this->is_deleted = 1;
        $this->deleted_at = now();
        $this->deleted_by = $employeeId;
        return $this->save();
    }
}
