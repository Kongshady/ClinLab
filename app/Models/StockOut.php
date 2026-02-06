<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StockOut extends Model
{
    protected $table = 'stock_out';
    protected $primaryKey = 'stock_out_id';
    public $timestamps = false;

    protected $fillable = [
        'item_id',
        'quantity',
        'performed_by',
        'reference_number',
        'remarks',
        'datetime_added',
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
        return $this->belongsTo(Employee::class, 'performed_by', 'employee_id');
    }

    public function performedByEmployee()
    {
        return $this->belongsTo(Employee::class, 'performed_by', 'employee_id');
    }
}
