<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TestPriceHistory extends Model
{
    protected $table = 'test_price_history';
    protected $primaryKey = 'price_history_id';
    public $timestamps = false;

    protected $fillable = [
        'test_id',
        'previous_price',
        'new_price',
        'updated_by',
        'updated_at',
    ];

    protected $casts = [
        'previous_price' => 'decimal:2',
        'new_price' => 'decimal:2',
        'updated_at' => 'datetime',
    ];

    // Relationship with test
    public function test()
    {
        return $this->belongsTo(Test::class, 'test_id', 'test_id');
    }

    // Relationship with employee who updated
    public function updatedByEmployee()
    {
        return $this->belongsTo(Employee::class, 'updated_by', 'employee_id');
    }
}
