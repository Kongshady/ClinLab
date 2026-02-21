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
        'payment_status',
        'total_amount',
        'paid_at',
        'paid_by_transaction_id',
        'remarks',
    ];

    protected $casts = [
        'order_date' => 'datetime',
        'paid_at' => 'datetime',
        'total_amount' => 'decimal:2',
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
     * The transaction that paid for this order.
     */
    public function transaction()
    {
        return $this->hasOne(Transaction::class, 'lab_test_order_id', 'lab_test_order_id');
    }

    /**
     * Check if this order has been paid.
     */
    public function isPaid(): bool
    {
        return ($this->payment_status ?? 'PENDING_PAYMENT') === 'PAID';
    }

    /**
     * Mark this order as paid by a transaction.
     */
    public function markAsPaid(int $transactionId): void
    {
        $this->update([
            'payment_status' => 'PAID',
            'paid_at' => now(),
            'paid_by_transaction_id' => $transactionId,
        ]);
    }

    /**
     * Calculate total amount from ordered tests' current prices.
     */
    public function calculateTotalAmount(): float
    {
        return (float) $this->orderTests()
            ->join('test', 'order_tests.test_id', '=', 'test.test_id')
            ->sum('test.current_price');
    }

    /**
     * Get the payment status badge for display.
     */
    public function getPaymentBadgeAttribute(): array
    {
        return match($this->payment_status ?? 'PENDING_PAYMENT') {
            'PAID' => ['class' => 'bg-green-100 text-green-800', 'label' => 'Paid'],
            default => ['class' => 'bg-orange-100 text-orange-800', 'label' => 'Awaiting Payment'],
        };
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
