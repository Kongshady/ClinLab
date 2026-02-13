<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LabTestOrder extends Model
{
    protected $table = 'lab_test_order';
    protected $primaryKey = 'lab_test_order_id';
    public $timestamps = false;

    protected $fillable = [
        'patient_id', 
        'physician_id',
        'test_id',
        'order_date',
        'status',
        'remarks',
    ];

    protected $casts = [
        'order_date' => 'datetime',
    ];

    public function getRouteKeyName()
    {
        return 'lab_test_order_id';
    }

    public function patient()
    {
        return $this->belongsTo(Patient::class, 'patient_id', 'patient_id');
    }

    public function physician()
    {
        return $this->belongsTo(Physician::class, 'physician_id', 'physician_id');
    }

    public function orderTests()
    {
        return $this->hasMany(OrderTest::class, 'order_id', 'lab_test_order_id');
    }

    public function labResults()
    {
        return $this->hasMany(LabResult::class, 'lab_test_order_id', 'lab_test_order_id');
    }

    /**
     * Count of completed test results vs total tests.
     */
    public function getCompletedCountAttribute()
    {
        return $this->labResults()->whereIn('status', ['final', 'revised'])->count();
    }

    public function getTotalTestsCountAttribute()
    {
        return $this->orderTests()->count();
    }

    /**
     * Check if all tests are completed.
     */
    public function getIsCompletedAttribute()
    {
        $total = $this->orderTests()->count();
        if ($total === 0) return false;
        return $this->orderTests()->where('status', 'completed')->count() >= $total;
    }

    /**
     * Auto-update order status based on test statuses.
     */
    public function updateStatusFromTests()
    {
        $total = $this->orderTests()->count();
        if ($total === 0) return;

        $completed = $this->orderTests()->where('status', 'completed')->count();
        $cancelled = $this->orderTests()->where('status', 'cancelled')->count();

        if ($completed + $cancelled >= $total && $completed > 0) {
            $this->update(['status' => 'completed']);
        } elseif ($cancelled >= $total) {
            $this->update(['status' => 'cancelled']);
        }
    }
}
