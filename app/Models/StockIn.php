<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StockIn extends Model
{
    protected $table = 'stock_in';
    protected $primaryKey = 'stock_in_id';
    public $timestamps = false;

    protected $fillable = [
        'item_id',
        'quantity',
        'performed_by',
        'supplier',
        'reference_number',
        'expiry_date',
        'remarks',
        'datetime_added',
    ];

    protected $casts = [
        'expiry_date' => 'date',
        'datetime_added' => 'datetime',
    ];

    public function item()
    {
        return $this->belongsTo(Item::class, 'item_id', 'item_id');
    }

    public function employee()
    {
        return $this->belongsTo(Employee::class, 'performed_by', 'employee_id');
    }

    public function performedByEmployee()
    {
        return $this->belongsTo(Employee::class, 'performed_by', 'employee_id');
    }
}
