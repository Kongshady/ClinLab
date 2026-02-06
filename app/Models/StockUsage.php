<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StockUsage extends Model
{
    protected $table = 'stock_usage';
    protected $primaryKey = 'stock_usage_id';
    public $timestamps = false;

    protected $fillable = [
        'item_id',
        'quantity',
        'employee_id',
        'firstname',
        'middlename',
        'lastname',
        'purpose',
        'datetime_added',
        'or_number',
    ];

    protected $casts = [
        'datetime_added' => 'datetime',
    ];

    public function item()
    {
        return $this->belongsTo(Item::class, 'item_id', 'item_id');
    }

    public function employee()
    {
        return $this->belongsTo(Employee::class, 'employee_id', 'employee_id');
    }
}
