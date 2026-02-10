<?php

namespace App\Http\Controllers;

use App\Models\LabResult;
use App\Models\Equipment;
use App\Models\CalibrationRecord;
use App\Models\StockIn;
use App\Models\StockOut;
use App\Models\Item;
use App\Models\Section;
use App\Models\Test;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReportsController extends Controller
{
    public function index(Request $request)
    {
        $reportData = null;
        $sections = Section::where('is_deleted', 0)->orderBy('label')->get();
        
        if ($request->filled('report_type') && $request->filled('start_date') && $request->filled('end_date')) {
            switch ($request->report_type) {
                case 'equipment_maintenance':
                    $reportData = Equipment::with(['section'])
                        ->when($request->filled('section_id'), function ($query) use ($request) {
                            $query->where('section_id', $request->section_id);
                        })
                        ->when($request->filled('start_date') && $request->filled('end_date'), function ($query) use ($request) {
                            $query->whereBetween('purchase_date', [$request->start_date, $request->end_date]);
                        })
                        ->orderBy('purchase_date', 'desc')
                        ->paginate(20);
                    break;
                    
                case 'calibration_records':
                    $reportData = CalibrationRecord::with(['equipment', 'equipment.section'])
                        ->when($request->filled('section_id'), function ($query) use ($request) {
                            $query->whereHas('equipment', function($q) use ($request) {
                                $q->where('section_id', $request->section_id);
                            });
                        })
                        ->when($request->filled('start_date') && $request->filled('end_date'), function ($query) use ($request) {
                            $query->whereBetween('calibration_date', [$request->start_date, $request->end_date]);
                        })
                        ->orderBy('calibration_date', 'desc')
                        ->paginate(20);
                    break;
                    
                case 'inventory_movement':
                    $reportData = StockIn::with(['item', 'item.section'])
                        ->when($request->filled('section_id'), function ($query) use ($request) {
                            $query->whereHas('item', function($q) use ($request) {
                                $q->where('section_id', $request->section_id);
                            });
                        })
                        ->when($request->filled('start_date') && $request->filled('end_date'), function ($query) use ($request) {
                            $query->whereBetween('datetime_added', [$request->start_date, $request->end_date]);
                        })
                        ->orderBy('datetime_added', 'desc')
                        ->paginate(20);
                    break;
                    
                case 'low_stock_alert':
                    $reportData = Item::with(['section'])
                        ->leftJoin('stock_in', 'item.item_id', '=', 'stock_in.item_id')
                        ->leftJoin('stock_out', 'item.item_id', '=', 'stock_out.item_id')
                        ->select('item.*',
                            \DB::raw('COALESCE(SUM(stock_in.quantity), 0) - COALESCE(SUM(stock_out.quantity), 0) as current_stock'))
                        ->groupBy('item.item_id', 'item.section_id', 'item.item_type_id', 'item.label', 'item.status_code', 'item.unit', 'item.reorder_level', 'item.is_deleted', 'item.deleted_at', 'item.deleted_by')
                        ->when($request->filled('section_id'), function ($query) use ($request) {
                            $query->where('item.section_id', $request->section_id);
                        })
                        ->where('item.is_deleted', 0)
                        ->havingRaw('(COALESCE(SUM(stock_in.quantity), 0) - COALESCE(SUM(stock_out.quantity), 0)) <= item.reorder_level')
                        ->orderByRaw('(COALESCE(SUM(stock_in.quantity), 0) - COALESCE(SUM(stock_out.quantity), 0)) ASC')
                        ->paginate(20);
                    break;
                    
                case 'laboratory_results':
                    $reportData = LabResult::with(['patient', 'test'])
                        ->when($request->filled('start_date') && $request->filled('end_date'), function ($query) use ($request) {
                            $query->whereBetween('result_date', [$request->start_date, $request->end_date]);
                        })
                        ->orderBy('result_date', 'desc')
                        ->paginate(20);
                    break;
            }
        }

        return view('reports.index', compact('reportData', 'sections'));
    }
}
