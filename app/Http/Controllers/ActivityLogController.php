<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Employee;
use Illuminate\Http\Request;

class ActivityLogController extends Controller
{
    public function index()
    {
        $activityLogs = ActivityLog::orderBy('datetime_added', 'desc')
            ->paginate(20);
        
        return view('activity-logs.index', compact('activityLogs'));
    }
}
