<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Employee;
use Illuminate\Http\Request;

class ActivityLogController extends Controller
{
    public function index()
    {
        return view('activity-logs.index');
    }
}
