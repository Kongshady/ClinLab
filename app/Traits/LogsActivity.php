<?php

namespace App\Traits;

use App\Models\ActivityLog;
use Illuminate\Support\Facades\Auth;

trait LogsActivity
{
    /**
     * Log an activity to the activity log
     *
     * @param string $description
     * @param int|null $employeeId
     * @return void
     */
    protected function logActivity(string $description, ?int $employeeId = null): void
    {
        $employeeId = $employeeId ?? (Auth::user()->employee->employee_id ?? null);

        if ($employeeId) {
            ActivityLog::create([
                'employee_id' => $employeeId,
                'description' => $description,
                'datetime_added' => now(),
                'status_code' => 1, // Active status
            ]);
        }
    }
}
