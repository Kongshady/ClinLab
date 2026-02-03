<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ActivityLog extends Model
{
    protected $table = 'activity_log';
    protected $primaryKey = 'activity_log_id';
    public $timestamps = false;

    protected $fillable = [
        'employee_id',
        'datetime_added',
        'description',
        'status_code',
    ];

    protected $casts = [
        'datetime_added' => 'datetime',
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class, 'employee_id', 'employee_id');
    }
}
