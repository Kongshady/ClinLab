<?php

namespace App\Http\Controllers;

use App\Models\LabResult;
use App\Models\Test;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReportsController extends Controller
{
    public function index(Request $request)
    {
        $query = LabResult::with(['patient', 'test']);

        // Apply filters
        if ($request->filled('search_patient')) {
            $search = $request->search_patient;
            $query->whereHas('patient', function($q) use ($search) {
                $q->where(DB::raw("CONCAT(firstname, ' ', lastname)"), 'like', "%{$search}%");
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('test')) {
            $query->where('test_id', $request->test);
        }

        if ($request->filled('date_from')) {
            $query->whereDate('result_date', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('result_date', '<=', $request->date_to);
        }

        $results = $query->orderBy('result_date', 'desc')->paginate(15);

        // Get statistics
        $stats = [
            'total' => LabResult::count(),
            'draft' => LabResult::where('status', 'draft')->count(),
            'final' => LabResult::where('status', 'final')->count(),
            'revised' => LabResult::where('status', 'revised')->count(),
        ];

        // Get tests for dropdown
        $tests = Test::where('is_deleted', 0)->orderBy('label')->get();

        return view('reports.index', compact('results', 'stats', 'tests'));
    }
}
