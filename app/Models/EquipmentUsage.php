<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EquipmentUsage extends Model
{
    protected $table = 'equipment_usage';
    protected $primaryKey = 'usage_id';
    public $timestamps = false;

    protected $fillable = [
        'equipment_id',
        'date_used',
        'user_name',
        'item_name',
        'quantity',
        'purpose',
        'or_number',
        'status',
        'remarks',
        'datetime_added',
    ];

    protected $casts = [
        'date_used' => 'date',
        'datetime_added' => 'datetime',
    ];

    public function equipment(): BelongsTo
    {
        return $this->belongsTo(Equipment::class, 'equipment_id', 'equipment_id');
    }
}
