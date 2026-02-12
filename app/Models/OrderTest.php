<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OrderTest extends Model
{
    protected $table = 'order_tests';
    protected $primaryKey = 'order_test_id';
    public $timestamps = false;

    protected $fillable = [
        'order_id',
        'test_id',
        'status',
        'assigned_to',
        'datetime_added',
    ];

    protected $casts = [
        'datetime_added' => 'datetime',
    ];

    public function order()
    {
        return $this->belongsTo(LabTestOrder::class, 'order_id', 'lab_test_order_id');
    }

    public function test()
    {
        return $this->belongsTo(Test::class, 'test_id', 'test_id');
    }

    public function assignedTo()
    {
        return $this->belongsTo(Employee::class, 'assigned_to', 'employee_id');
    }

    public function labResult()
    {
        return $this->hasOne(LabResult::class, 'order_test_id', 'order_test_id');
    }
}
