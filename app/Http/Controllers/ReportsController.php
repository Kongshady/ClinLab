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
use App\Models\Transaction;
use App\Models\Certificate;
use App\Models\ActivityLog;
use App\Models\Employee;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

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

                case 'daily_collection':
                    $reportData = Transaction::with(['patient'])
                        ->whereBetween('datetime_added', [$request->start_date, $request->end_date . ' 23:59:59'])
                        ->orderBy('datetime_added', 'desc')
                        ->paginate(20);
                    break;

                case 'revenue_by_test':
                    $reportData = DB::table('transaction_detail')
                        ->join('transaction', 'transaction_detail.transaction_id', '=', 'transaction.transaction_id')
                        ->join('test', 'transaction_detail.test_id', '=', 'test.test_id')
                        ->leftJoin('section', 'test.section_id', '=', 'section.section_id')
                        ->whereBetween('transaction.datetime_added', [$request->start_date, $request->end_date . ' 23:59:59'])
                        ->when($request->filled('section_id'), fn($q) => $q->where('test.section_id', $request->section_id))
                        ->select(
                            'test.test_id', 'test.label as test_name', 'section.label as section_name',
                            'test.current_price',
                            DB::raw('COUNT(*) as total_orders'),
                            DB::raw('SUM(test.current_price) as total_revenue')
                        )
                        ->groupBy('test.test_id', 'test.label', 'section.label', 'test.current_price')
                        ->orderByDesc('total_revenue')
                        ->paginate(20);
                    break;

                case 'test_volume':
                    $reportData = LabResult::with(['test', 'test.section'])
                        ->whereBetween('result_date', [$request->start_date, $request->end_date])
                        ->when($request->filled('section_id'), fn($q) => $q->whereHas('test', fn($sq) => $sq->where('section_id', $request->section_id)))
                        ->select(
                            'test_id',
                            DB::raw('COUNT(*) as total_count'),
                            DB::raw("SUM(CASE WHEN status = 'final' THEN 1 ELSE 0 END) as final_count"),
                            DB::raw("SUM(CASE WHEN status = 'draft' THEN 1 ELSE 0 END) as draft_count")
                        )
                        ->groupBy('test_id')
                        ->orderByDesc('total_count')
                        ->paginate(20);
                    break;

                case 'issued_certificates':
                    $reportData = Certificate::with(['patient', 'issuedBy'])
                        ->whereBetween('issue_date', [$request->start_date, $request->end_date])
                        ->orderBy('issue_date', 'desc')
                        ->paginate(20);
                    break;

                case 'activity_log':
                    $reportData = ActivityLog::with(['employee'])
                        ->whereBetween('datetime_added', [$request->start_date, $request->end_date . ' 23:59:59'])
                        ->when($request->filled('employee_id'), fn($q) => $q->where('employee_id', $request->employee_id))
                        ->orderBy('datetime_added', 'desc')
                        ->paginate(20);
                    break;

                case 'expiring_inventory':
                    $reportData = StockIn::with(['item', 'item.section'])
                        ->whereNotNull('expiry_date')
                        ->where('expiry_date', '<=', Carbon::now()->addDays(90)->toDateString())
                        ->where('expiry_date', '>=', Carbon::now()->toDateString())
                        ->when($request->filled('section_id'), fn($q) => $q->whereHas('item', fn($sq) => $sq->where('section_id', $request->section_id)))
                        ->orderBy('expiry_date', 'asc')
                        ->paginate(20);
                    break;
            }
        }

        // Compute summary totals
        $summaryTotals = [];
        if ($request->filled('report_type') && $request->filled('start_date') && $request->filled('end_date')) {
            if ($request->report_type === 'daily_collection') {
                $totalAmount = DB::table('transaction_detail')
                    ->join('transaction', 'transaction_detail.transaction_id', '=', 'transaction.transaction_id')
                    ->join('test', 'transaction_detail.test_id', '=', 'test.test_id')
                    ->whereBetween('transaction.datetime_added', [$request->start_date, $request->end_date . ' 23:59:59'])
                    ->sum('test.current_price');
                $summaryTotals = [
                    'total_transactions' => $reportData ? $reportData->total() : 0,
                    'total_amount' => $totalAmount,
                ];
            } elseif ($request->report_type === 'revenue_by_test') {
                $revSummary = DB::table('transaction_detail')
                    ->join('transaction', 'transaction_detail.transaction_id', '=', 'transaction.transaction_id')
                    ->join('test', 'transaction_detail.test_id', '=', 'test.test_id')
                    ->whereBetween('transaction.datetime_added', [$request->start_date, $request->end_date . ' 23:59:59'])
                    ->when($request->filled('section_id'), fn($q) => $q->where('test.section_id', $request->section_id))
                    ->selectRaw('COUNT(*) as total_orders, SUM(test.current_price) as grand_revenue')
                    ->first();
                $summaryTotals = [
                    'total_orders' => $revSummary->total_orders ?? 0,
                    'grand_revenue' => $revSummary->grand_revenue ?? 0,
                ];
            } elseif ($request->report_type === 'test_volume') {
                $volSummary = LabResult::whereBetween('result_date', [$request->start_date, $request->end_date])
                    ->when($request->filled('section_id'), fn($q) => $q->whereHas('test', fn($sq) => $sq->where('section_id', $request->section_id)))
                    ->selectRaw("COUNT(*) as total, SUM(CASE WHEN status = 'final' THEN 1 ELSE 0 END) as final_total, SUM(CASE WHEN status = 'draft' THEN 1 ELSE 0 END) as draft_total")
                    ->first();
                $summaryTotals = [
                    'total' => $volSummary->total ?? 0,
                    'final_total' => $volSummary->final_total ?? 0,
                    'draft_total' => $volSummary->draft_total ?? 0,
                ];
            } elseif ($request->report_type === 'issued_certificates') {
                $certSummary = Certificate::whereBetween('issue_date', [$request->start_date, $request->end_date])
                    ->selectRaw("COUNT(*) as total, SUM(CASE WHEN status = 'issued' THEN 1 ELSE 0 END) as issued, SUM(CASE WHEN status = 'draft' THEN 1 ELSE 0 END) as draft, SUM(CASE WHEN status = 'revoked' THEN 1 ELSE 0 END) as revoked")
                    ->first();
                $summaryTotals = [
                    'total' => $certSummary->total ?? 0,
                    'issued' => $certSummary->issued ?? 0,
                    'draft' => $certSummary->draft ?? 0,
                    'revoked' => $certSummary->revoked ?? 0,
                ];
            }
        }

        $employees = Employee::where('is_deleted', 0)->orderBy('firstname')->get();

        return view('reports.index', compact('reportData', 'sections', 'employees', 'summaryTotals'));
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
            'daily_collection'      => 'Daily_Collection',
            'revenue_by_test'       => 'Revenue_By_Test',
            'test_volume'           => 'Test_Volume',
            'issued_certificates'   => 'Issued_Certificates',
            'activity_log'          => 'Activity_Log',
            'expiring_inventory'    => 'Expiring_Inventory',
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

            case 'daily_collection':
                $data = Transaction::with(['patient'])
                    ->whereBetween('datetime_added', [$startDate, $endDate . ' 23:59:59'])
                    ->orderBy('datetime_added', 'desc')
                    ->get();
                $totalAmount = DB::table('transaction_detail')
                    ->join('transaction', 'transaction_detail.transaction_id', '=', 'transaction.transaction_id')
                    ->join('test', 'transaction_detail.test_id', '=', 'test.test_id')
                    ->whereBetween('transaction.datetime_added', [$startDate, $endDate . ' 23:59:59'])
                    ->sum('test.current_price');
                $pdfView = 'pdf.general-report';
                break;

            case 'revenue_by_test':
                $data = DB::table('transaction_detail')
                    ->join('transaction', 'transaction_detail.transaction_id', '=', 'transaction.transaction_id')
                    ->join('test', 'transaction_detail.test_id', '=', 'test.test_id')
                    ->leftJoin('section', 'test.section_id', '=', 'section.section_id')
                    ->whereBetween('transaction.datetime_added', [$startDate, $endDate . ' 23:59:59'])
                    ->when($sectionId, fn($q) => $q->where('test.section_id', $sectionId))
                    ->select(
                        'test.test_id', 'test.label as test_name', 'section.label as section_name',
                        'test.current_price',
                        DB::raw('COUNT(*) as total_orders'),
                        DB::raw('SUM(test.current_price) as total_revenue')
                    )
                    ->groupBy('test.test_id', 'test.label', 'section.label', 'test.current_price')
                    ->orderByDesc('total_revenue')
                    ->get();
                $totalAmount = $data->sum('total_revenue');
                $pdfView = 'pdf.general-report';
                break;

            case 'test_volume':
                $data = LabResult::with(['test', 'test.section'])
                    ->whereBetween('result_date', [$startDate, $endDate])
                    ->when($sectionId, fn($q) => $q->whereHas('test', fn($sq) => $sq->where('section_id', $sectionId)))
                    ->select(
                        'test_id',
                        DB::raw('COUNT(*) as total_count'),
                        DB::raw("SUM(CASE WHEN status = 'final' THEN 1 ELSE 0 END) as final_count"),
                        DB::raw("SUM(CASE WHEN status = 'draft' THEN 1 ELSE 0 END) as draft_count")
                    )
                    ->groupBy('test_id')
                    ->orderByDesc('total_count')
                    ->get();
                $pdfView = 'pdf.general-report';
                break;

            case 'issued_certificates':
                $data = Certificate::with(['patient', 'issuedBy'])
                    ->whereBetween('issue_date', [$startDate, $endDate])
                    ->orderBy('issue_date', 'desc')
                    ->get();
                $pdfView = 'pdf.general-report';
                break;

            case 'activity_log':
                $employeeId = $request->query('employee_id');
                $data = ActivityLog::with(['employee'])
                    ->whereBetween('datetime_added', [$startDate, $endDate . ' 23:59:59'])
                    ->when($employeeId, fn($q) => $q->where('employee_id', $employeeId))
                    ->orderBy('datetime_added', 'desc')
                    ->get();
                $pdfView = 'pdf.general-report';
                break;

            case 'expiring_inventory':
                $data = StockIn::with(['item', 'item.section'])
                    ->whereNotNull('expiry_date')
                    ->where('expiry_date', '<=', Carbon::now()->addDays(90)->toDateString())
                    ->where('expiry_date', '>=', Carbon::now()->toDateString())
                    ->when($sectionId, fn($q) => $q->whereHas('item', fn($sq) => $sq->where('section_id', $sectionId)))
                    ->orderBy('expiry_date', 'asc')
                    ->get();
                $pdfView = 'pdf.general-report';
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
            'totalAmount' => $totalAmount ?? null,
        ])->setPaper('a4', $data->count() > 20 ? 'landscape' : 'portrait');

        $filename = $typeName . '_' . $startDate . '_to_' . $endDate . '.pdf';

        return $pdf->download($filename);
    }
}
