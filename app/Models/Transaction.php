<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Transaction extends Model
{
    protected $table = 'transaction';
    protected $primaryKey = 'transaction_id';
    public $timestamps = false;

    protected $fillable = [
        'client_id',
        'or_number',
        'client_designation',
        'datetime_added',
        'status_code',
        'lab_test_order_id',
        'amount',
        'payment_method',
        'processed_by',
        'paid_at',
    ];

    protected $casts = [
        'datetime_added' => 'datetime',
        'paid_at' => 'datetime',
        'amount' => 'decimal:2',
    ];

    /**
     * Get the route key name for Laravel route model binding.
     */
    public function getRouteKeyName()
    {
        return 'transaction_id';
    }

    public function patient()
    {
        return $this->belongsTo(Patient::class, 'client_id', 'patient_id');
    }

    /**
     * The lab test order this transaction pays for.
     */
    public function labTestOrder()
    {
        return $this->belongsTo(LabTestOrder::class, 'lab_test_order_id', 'lab_test_order_id');
    }

    /**
     * The employee who processed this transaction.
     */
    public function processedByEmployee()
    {
        return $this->belongsTo(Employee::class, 'processed_by', 'employee_id');
    }
}
