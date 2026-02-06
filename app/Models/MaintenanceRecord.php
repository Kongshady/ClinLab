<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Traits\LogsActivity;

class MaintenanceRecord extends Model
{
    use LogsActivity;

    protected $table = 'maintenance_record';
    protected $primaryKey = 'maintenance_id';
    public $timestamps = false;

    protected $fillable = [
        'equipment_id',
        'performed_date',
        'findings',
        'action_taken',
        'performed_by',
        'next_due_date',
        'status',
        'datetime_added',
        'datetime_updated',
    ];

    protected $casts = [
        'performed_date' => 'date',
        'next_due_date' => 'date',
        'datetime_added' => 'datetime',
        'datetime_updated' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            $model->datetime_added = now();
        });

        static::updating(function ($model) {
            $model->datetime_updated = now();
        });
    }

    public function equipment(): BelongsTo
    {
        return $this->belongsTo(Equipment::class, 'equipment_id', 'equipment_id');
    }

    public function performedBy(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'performed_by', 'employee_id');
    }

    public function scopeActive($query)
    {
        return $query->where('is_deleted', 0);
    }

    public function softDelete($employeeId = null)
    {
        $this->is_deleted = 1;
        $this->deleted_at = now();
        $this->deleted_by = $employeeId;
        $this->save();
    }

    public function isDueSoon($days = 7): bool
    {
        if (!$this->next_due_date) return false;
        $daysUntilDue = now()->diffInDays($this->next_due_date, false);
        return $daysUntilDue >= 0 && $daysUntilDue <= $days;
    }

    public function isOverdue(): bool
    {
        if (!$this->next_due_date) return false;
        return $this->next_due_date < now()->toDateString();
    }
}
