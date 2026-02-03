<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Employee;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ActivityLogController extends Controller
{
    public function index()
    {
        $logs = ActivityLog::with('employee')->orderBy('datetime_added', 'desc')->paginate(15);
        return Inertia::render('ActivityLogs/Index', ['logs' => $logs]);
    }
}
