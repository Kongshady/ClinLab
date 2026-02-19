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
use Barryvdh\DomPDF\Facade\Pdf;
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

    public function downloadPdf(Request $request)
    {
        $reportType = $request->query('report_type');
        $startDate  = $request->query('start_date');
        $endDate    = $request->query('end_date');
        $sectionId  = $request->query('section_id');

        if (!$reportType || !$startDate || !$endDate) {
            abort(400, 'Missing required parameters.');
        }

        $sectionName = $sectionId
            ? Section::find($sectionId)?->label ?? 'Unknown'
            : 'All Sections';

        $typeName = match ($reportType) {
            'equipment_maintenance' => 'Equipment_Maintenance',
            'calibration_records'   => 'Calibration_Records',
            'inventory_movement'    => 'Inventory_Movement',
            'low_stock_alert'       => 'Low_Stock_Alert',
            'laboratory_results'    => 'Laboratory_Results',
            default => 'Report',
        };

        $reportTitle = str_replace('_', ' ', $typeName) . ' Report';
        $data = collect();

        switch ($reportType) {
            case 'equipment_maintenance':
                $data = Equipment::with(['section'])
                    ->when($sectionId, fn($q) => $q->where('section_id', $sectionId))
                    ->whereBetween('created_at', [$startDate, $endDate])
                    ->orderBy('created_at', 'desc')
                    ->get();
                $pdfView = 'pdf.maintenance-report';
                break;

            case 'calibration_records':
                $data = CalibrationRecord::with(['equipment', 'equipment.section', 'performedBy'])
                    ->when($sectionId, fn($q) => $q->whereHas('equipment', fn($r) => $r->where('section_id', $sectionId)))
                    ->whereBetween('calibration_date', [$startDate, $endDate])
                    ->orderBy('calibration_date', 'desc')
                    ->get();
                $pdfView = 'pdf.maintenance-report';
                break;

            case 'inventory_movement':
                $ins = StockIn::with(['item', 'item.section'])
                    ->when($sectionId, fn($q) => $q->whereHas('item', fn($r) => $r->where('section_id', $sectionId)))
                    ->whereBetween('datetime_added', [$startDate, $endDate . ' 23:59:59'])
                    ->get()
                    ->map(fn($s) => (object)['date' => $s->datetime_added, 'item' => $s->item, 'type' => 'Stock In', 'quantity' => $s->quantity, 'supplier' => $s->supplier, 'reference_number' => $s->reference_number, 'remarks' => $s->remarks]);

                $outs = StockOut::with(['item', 'item.section'])
                    ->when($sectionId, fn($q) => $q->whereHas('item', fn($r) => $r->where('section_id', $sectionId)))
                    ->whereBetween('datetime_added', [$startDate, $endDate . ' 23:59:59'])
                    ->get()
                    ->map(fn($s) => (object)['date' => $s->datetime_added, 'item' => $s->item, 'type' => 'Stock Out', 'quantity' => $s->quantity, 'supplier' => null, 'reference_number' => $s->reference_number, 'remarks' => $s->remarks]);

                $data = $ins->merge($outs)->sortByDesc('date');
                $pdfView = 'pdf.inventory-report';
                break;

            case 'low_stock_alert':
                $data = Item::with(['section'])
                    ->leftJoin('stock_in', 'item.item_id', '=', 'stock_in.item_id')
                    ->leftJoin('stock_out', 'item.item_id', '=', 'stock_out.item_id')
                    ->select('item.*', DB::raw('COALESCE(SUM(stock_in.quantity), 0) - COALESCE(SUM(stock_out.quantity), 0) as current_stock'))
                    ->groupBy('item.item_id', 'item.section_id', 'item.item_type_id', 'item.label', 'item.status_code', 'item.unit', 'item.reorder_level', 'item.is_deleted', 'item.deleted_at', 'item.deleted_by')
                    ->when($sectionId, fn($q) => $q->where('item.section_id', $sectionId))
                    ->where('item.is_deleted', 0)
                    ->havingRaw('(COALESCE(SUM(stock_in.quantity), 0) - COALESCE(SUM(stock_out.quantity), 0)) <= item.reorder_level')
                    ->orderByRaw('(COALESCE(SUM(stock_in.quantity), 0) - COALESCE(SUM(stock_out.quantity), 0)) ASC')
                    ->get();
                $pdfView = 'pdf.inventory-report';
                break;

            case 'laboratory_results':
                $data = LabResult::with(['patient', 'test', 'performedBy'])
                    ->whereBetween('result_date', [$startDate, $endDate])
                    ->orderBy('result_date', 'desc')
                    ->get();
                $pdfView = 'pdf.maintenance-report';
                break;

            default:
                abort(400, 'Invalid report type.');
        }

        $pdf = Pdf::loadView($pdfView, [
            'data'        => $data,
            'reportType'  => $reportType,
            'reportTitle' => $reportTitle,
            'startDate'   => $startDate,
            'endDate'     => $endDate,
            'sectionName' => $sectionName,
        ])->setPaper('a4', $data->count() > 20 ? 'landscape' : 'portrait');

        $filename = $typeName . '_' . $startDate . '_to_' . $endDate . '.pdf';

        return $pdf->download($filename);
    }
}
