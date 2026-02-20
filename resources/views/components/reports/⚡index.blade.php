<?php

use Livewire\Component;
use Livewire\WithPagination;
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
use App\Exports\ReportExport;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

new class extends Component
{
    use WithPagination;

    public $reportType = '';
    public $startDate = '';
    public $endDate = '';
    public $sectionId = '';
    public $employeeId = '';

    public function mount()
    {
        $this->startDate = Carbon::now()->startOfMonth()->format('Y-m-d');
        $this->endDate = Carbon::now()->format('Y-m-d');
    }

    public function generateReport()
    {
        $this->resetPage();
        session()->flash('success', 'Report generated successfully.');
    }

    public function export()
    {
        if (!$this->reportType) {
            session()->flash('error', 'Please select a report type first.');
            return;
        }

        if (!$this->startDate || !$this->endDate) {
            session()->flash('error', 'Please select a date range first.');
            return;
        }

        $typeName = match ($this->reportType) {
            'equipment_maintenance' => 'Equipment_Maintenance',
            'calibration_records' => 'Calibration_Records',
            'inventory_movement' => 'Inventory_Movement',
            'low_stock_alert' => 'Low_Stock_Alert',
            'laboratory_results' => 'Laboratory_Results',
            'daily_collection' => 'Daily_Collection',
            'revenue_by_test' => 'Revenue_By_Test',
            'test_volume' => 'Test_Volume',
            'issued_certificates' => 'Issued_Certificates',
            'activity_log' => 'Activity_Log',
            'expiring_inventory' => 'Expiring_Inventory',
            default => 'Report',
        };

        $filename = $typeName . '_' . $this->startDate . '_to_' . $this->endDate . '.xlsx';

        return Excel::download(
            new ReportExport($this->reportType, $this->startDate, $this->endDate, $this->sectionId ?: null),
            $filename
        );
    }

    public function exportCsv()
    {
        if (!$this->reportType) {
            session()->flash('error', 'Please select a report type first.');
            return;
        }

        if (!$this->startDate || !$this->endDate) {
            session()->flash('error', 'Please select a date range first.');
            return;
        }

        $typeName = match ($this->reportType) {
            'equipment_maintenance' => 'Equipment_Maintenance',
            'calibration_records' => 'Calibration_Records',
            'inventory_movement' => 'Inventory_Movement',
            'low_stock_alert' => 'Low_Stock_Alert',
            'laboratory_results' => 'Laboratory_Results',
            'daily_collection' => 'Daily_Collection',
            'revenue_by_test' => 'Revenue_By_Test',
            'test_volume' => 'Test_Volume',
            'issued_certificates' => 'Issued_Certificates',
            'activity_log' => 'Activity_Log',
            'expiring_inventory' => 'Expiring_Inventory',
            default => 'Report',
        };

        $filename = $typeName . '_' . $this->startDate . '_to_' . $this->endDate . '.csv';

        return Excel::download(
            new ReportExport($this->reportType, $this->startDate, $this->endDate, $this->sectionId ?: null),
            $filename,
            \Maatwebsite\Excel\Excel::CSV
        );
    }

    public function downloadPdf()
    {
        if (!$this->reportType) {
            session()->flash('error', 'Please select a report type first.');
            return;
        }

        if (!$this->startDate || !$this->endDate) {
            session()->flash('error', 'Please select a date range first.');
            return;
        }

        $url = route('reports.download-pdf', [
            'report_type' => $this->reportType,
            'start_date'  => $this->startDate,
            'end_date'    => $this->endDate,
            'section_id'  => $this->sectionId ?: '',
            'employee_id' => $this->employeeId ?: '',
        ]);

        return $this->js("window.open('" . $url . "', '_blank')");
    }

    public function updatedReportType()
    {
        $this->resetPage();
    }

    public function updatedStartDate()
    {
        $this->resetPage();
    }

    public function updatedEndDate()
    {
        $this->resetPage();
    }

    public function updatedSectionId()
    {
        $this->resetPage();
    }

    public function updatedEmployeeId()
    {
        $this->resetPage();
    }

    public function with(): array
    {
        $reportData = null;
        
        if ($this->reportType && $this->startDate && $this->endDate) {
            switch ($this->reportType) {
                case 'equipment_maintenance':
                    $reportData = Equipment::with(['section'])
                        ->when($this->sectionId, function ($query) {
                            $query->where('section_id', $this->sectionId);
                        })
                        ->whereBetween('created_at', [$this->startDate, $this->endDate])
                        ->orderBy('created_at', 'desc')
                        ->paginate(20);
                    break;
                    
                case 'calibration_records':
                    $reportData = CalibrationRecord::with(['equipment', 'equipment.section'])
                        ->when($this->sectionId, function ($query) {
                            $query->whereHas('equipment', function($q) {
                                $q->where('section_id', $this->sectionId);
                            });
                        })
                        ->whereBetween('calibration_date', [$this->startDate, $this->endDate])
                        ->orderBy('calibration_date', 'desc')
                        ->paginate(20);
                    break;
                    
                case 'inventory_movement':
                    $ins = StockIn::with(['item', 'item.section'])
                        ->when($this->sectionId, function ($query) {
                            $query->whereHas('item', function($q) {
                                $q->where('section_id', $this->sectionId);
                            });
                        })
                        ->whereBetween('datetime_added', [$this->startDate, $this->endDate . ' 23:59:59'])
                        ->get()
                        ->map(fn($s) => (object)[
                            'date' => $s->datetime_added,
                            'item' => $s->item,
                            'type' => 'Stock In',
                            'quantity' => $s->quantity,
                            'supplier' => $s->supplier,
                            'reference_number' => $s->reference_number,
                            'remarks' => $s->remarks,
                        ]);
                    
                    $outs = StockOut::with(['item', 'item.section'])
                        ->when($this->sectionId, function ($query) {
                            $query->whereHas('item', function($q) {
                                $q->where('section_id', $this->sectionId);
                            });
                        })
                        ->whereBetween('datetime_added', [$this->startDate, $this->endDate . ' 23:59:59'])
                        ->get()
                        ->map(fn($s) => (object)[
                            'date' => $s->datetime_added,
                            'item' => $s->item,
                            'type' => 'Stock Out',
                            'quantity' => $s->quantity,
                            'supplier' => null,
                            'reference_number' => $s->reference_number,
                            'remarks' => $s->remarks,
                        ]);
                    
                    $merged = $ins->merge($outs)->sortByDesc('date');
                    // Simple manual pagination
                    $page = request()->get('page', 1);
                    $perPage = 20;
                    $reportData = new \Illuminate\Pagination\LengthAwarePaginator(
                        $merged->forPage($page, $perPage)->values(),
                        $merged->count(),
                        $perPage,
                        $page,
                        ['path' => request()->url(), 'query' => request()->query()]
                    );
                    break;
                    
                case 'low_stock_alert':
                    $reportData = Item::with(['section'])
                        ->leftJoin('stock_in', 'item.item_id', '=', 'stock_in.item_id')
                        ->leftJoin('stock_out', 'item.item_id', '=', 'stock_out.item_id')
                        ->select('item.*',
                            \DB::raw('COALESCE(SUM(stock_in.quantity), 0) - COALESCE(SUM(stock_out.quantity), 0) as current_stock'))
                        ->groupBy('item.item_id', 'item.section_id', 'item.item_type_id', 'item.label', 'item.status_code', 'item.unit', 'item.reorder_level', 'item.is_deleted', 'item.deleted_at', 'item.deleted_by')
                        ->when($this->sectionId, function ($query) {
                            $query->where('item.section_id', $this->sectionId);
                        })
                        ->where('item.is_deleted', 0)
                        ->havingRaw('(COALESCE(SUM(stock_in.quantity), 0) - COALESCE(SUM(stock_out.quantity), 0)) <= item.reorder_level')
                        ->orderByRaw('(COALESCE(SUM(stock_in.quantity), 0) - COALESCE(SUM(stock_out.quantity), 0)) ASC')
                        ->paginate(20);
                    break;
                    
                case 'laboratory_results':
                    $reportData = LabResult::with(['patient', 'test', 'performedBy'])
                        ->whereBetween('result_date', [$this->startDate, $this->endDate])
                        ->orderBy('result_date', 'desc')
                        ->paginate(20);
                    break;

                case 'daily_collection':
                    $reportData = Transaction::with(['patient'])
                        ->whereBetween('datetime_added', [$this->startDate, $this->endDate . ' 23:59:59'])
                        ->orderBy('datetime_added', 'desc')
                        ->paginate(20);
                    break;

                case 'revenue_by_test':
                    $reportData = DB::table('transaction_detail')
                        ->join('transaction', 'transaction_detail.transaction_id', '=', 'transaction.transaction_id')
                        ->join('test', 'transaction_detail.test_id', '=', 'test.test_id')
                        ->leftJoin('section', 'test.section_id', '=', 'section.section_id')
                        ->whereBetween('transaction.datetime_added', [$this->startDate, $this->endDate . ' 23:59:59'])
                        ->when($this->sectionId, fn($q) => $q->where('test.section_id', $this->sectionId))
                        ->select(
                            'test.test_id',
                            'test.label as test_name',
                            'section.label as section_name',
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
                        ->whereBetween('result_date', [$this->startDate, $this->endDate])
                        ->when($this->sectionId, fn($q) => $q->whereHas('test', fn($sq) => $sq->where('section_id', $this->sectionId)))
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
                        ->whereBetween('issue_date', [$this->startDate, $this->endDate])
                        ->orderBy('issue_date', 'desc')
                        ->paginate(20);
                    break;

                case 'activity_log':
                    $reportData = ActivityLog::with(['employee'])
                        ->whereBetween('datetime_added', [$this->startDate, $this->endDate . ' 23:59:59'])
                        ->when($this->employeeId, fn($q) => $q->where('employee_id', $this->employeeId))
                        ->orderBy('datetime_added', 'desc')
                        ->paginate(20);
                    break;

                case 'expiring_inventory':
                    $reportData = StockIn::with(['item', 'item.section'])
                        ->whereNotNull('expiry_date')
                        ->where('expiry_date', '<=', Carbon::now()->addDays(90)->toDateString())
                        ->where('expiry_date', '>=', Carbon::now()->toDateString())
                        ->when($this->sectionId, fn($q) => $q->whereHas('item', fn($sq) => $sq->where('section_id', $this->sectionId)))
                        ->orderBy('expiry_date', 'asc')
                        ->paginate(20);
                    break;
            }
        }

        // Compute summary totals for applicable report types
        $summaryTotals = [];
        if ($this->reportType === 'daily_collection' && $reportData) {
            $allTransactions = Transaction::whereBetween('datetime_added', [$this->startDate, $this->endDate . ' 23:59:59'])->get();
            $totalAmount = DB::table('transaction_detail')
                ->join('transaction', 'transaction_detail.transaction_id', '=', 'transaction.transaction_id')
                ->join('test', 'transaction_detail.test_id', '=', 'test.test_id')
                ->whereBetween('transaction.datetime_added', [$this->startDate, $this->endDate . ' 23:59:59'])
                ->sum('test.current_price');
            $summaryTotals = [
                'total_transactions' => $allTransactions->count(),
                'total_amount' => $totalAmount,
            ];
        } elseif ($this->reportType === 'revenue_by_test' && $reportData) {
            $revSummary = DB::table('transaction_detail')
                ->join('transaction', 'transaction_detail.transaction_id', '=', 'transaction.transaction_id')
                ->join('test', 'transaction_detail.test_id', '=', 'test.test_id')
                ->whereBetween('transaction.datetime_added', [$this->startDate, $this->endDate . ' 23:59:59'])
                ->when($this->sectionId, fn($q) => $q->where('test.section_id', $this->sectionId))
                ->selectRaw('COUNT(*) as total_orders, SUM(test.current_price) as grand_revenue')
                ->first();
            $summaryTotals = [
                'total_orders' => $revSummary->total_orders ?? 0,
                'grand_revenue' => $revSummary->grand_revenue ?? 0,
            ];
        } elseif ($this->reportType === 'test_volume' && $reportData) {
            $volSummary = LabResult::whereBetween('result_date', [$this->startDate, $this->endDate])
                ->when($this->sectionId, fn($q) => $q->whereHas('test', fn($sq) => $sq->where('section_id', $this->sectionId)))
                ->selectRaw("COUNT(*) as total, SUM(CASE WHEN status = 'final' THEN 1 ELSE 0 END) as final_total, SUM(CASE WHEN status = 'draft' THEN 1 ELSE 0 END) as draft_total")
                ->first();
            $summaryTotals = [
                'total' => $volSummary->total ?? 0,
                'final_total' => $volSummary->final_total ?? 0,
                'draft_total' => $volSummary->draft_total ?? 0,
            ];
        } elseif ($this->reportType === 'issued_certificates' && $reportData) {
            $certSummary = Certificate::whereBetween('issue_date', [$this->startDate, $this->endDate])
                ->selectRaw("COUNT(*) as total, SUM(CASE WHEN status = 'issued' THEN 1 ELSE 0 END) as issued, SUM(CASE WHEN status = 'draft' THEN 1 ELSE 0 END) as draft, SUM(CASE WHEN status = 'revoked' THEN 1 ELSE 0 END) as revoked")
                ->first();
            $summaryTotals = [
                'total' => $certSummary->total ?? 0,
                'issued' => $certSummary->issued ?? 0,
                'draft' => $certSummary->draft ?? 0,
                'revoked' => $certSummary->revoked ?? 0,
            ];
        }

        return [
            'reportData' => $reportData,
            'sections' => Section::active()->orderBy('label')->get(),
            'employees' => Employee::active()->orderBy('firstname')->get(),
            'summaryTotals' => $summaryTotals,
        ];
    }
};
?>

<div class="p-6">
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-gray-900">Reports & Analytics</h1>
        <p class="text-sm text-gray-600 mt-1">View and generate laboratory reports</p>
    </div>

    @if(session('success'))
        <div class="mb-6 p-4 bg-green-50 border-l-4 border-green-500 rounded">
            <p class="text-green-800">{{ session('success') }}</p>
        </div>
    @endif

    <!-- Report Generation Form -->
    <div class="bg-white rounded-lg shadow-sm border mb-6">
        <div class="px-6 py-4 border-b border-gray-200">
            <h2 class="text-lg font-semibold text-gray-900 flex items-center">
                <svg class="w-5 h-5 mr-2 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                </svg>
                Generate Report
            </h2>
        </div>
        <div class="p-6">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        Report Type <span class="text-red-500">*</span>
                    </label>
                    <select wire:model.live="reportType" 
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        <option value="">Select Report Type</option>
                        <optgroup label="Equipment & Lab">
                            <option value="equipment_maintenance">Equipment Maintenance</option>
                            <option value="calibration_records">Calibration Records</option>
                            <option value="laboratory_results">Laboratory Results</option>
                        </optgroup>
                        <optgroup label="Inventory">
                            <option value="inventory_movement">Inventory Movement</option>
                            <option value="low_stock_alert">Low Stock Alert</option>
                            <option value="expiring_inventory">Expiring Inventory</option>
                        </optgroup>
                        <optgroup label="Financial">
                            <option value="daily_collection">Daily Collection</option>
                            <option value="revenue_by_test">Revenue by Test</option>
                        </optgroup>
                        <optgroup label="Analytics">
                            <option value="test_volume">Test Volume</option>
                            <option value="issued_certificates">Issued Certificates</option>
                            <option value="activity_log">Activity Log</option>
                        </optgroup>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        Start Date <span class="text-red-500">*</span>
                    </label>
                    <input type="date" wire:model.live="startDate" 
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        End Date <span class="text-red-500">*</span>
                    </label>
                    <input type="date" wire:model.live="endDate" 
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        Section (Optional)
                    </label>
                    <select wire:model.live="sectionId" 
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        <option value="">All Sections</option>
                        @foreach($sections as $section)
                            <option value="{{ $section->section_id }}">{{ $section->label }}</option>
                        @endforeach
                    </select>
                </div>
                @if($reportType === 'activity_log')
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        Employee (Optional)
                    </label>
                    <select wire:model.live="employeeId" 
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        <option value="">All Employees</option>
                        @foreach($employees as $emp)
                            <option value="{{ $emp->employee_id }}">{{ $emp->full_name }}</option>
                        @endforeach
                    </select>
                </div>
                @endif
            </div>
            <div class="flex gap-3 mt-6">
                <button wire:click="generateReport" 
                        class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors flex items-center"
                        {{ !$reportType || !$startDate || !$endDate ? 'disabled' : '' }}>
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                    Generate Report
                </button>
                <button wire:click="export" 
                        class="px-6 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors flex items-center"
                        {{ !$reportType ? 'disabled' : '' }}>
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                    Export Excel
                </button>
                <button wire:click="downloadPdf" 
                        class="px-6 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition-colors flex items-center"
                        {{ !$reportType ? 'disabled' : '' }}>
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                    </svg>
                    Download PDF
                </button>
                <button wire:click="exportCsv" 
                        class="px-6 py-2 bg-gray-600 text-white rounded-lg hover:bg-gray-700 transition-colors flex items-center"
                        {{ !$reportType ? 'disabled' : '' }}>
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                    Export CSV
                </button>
            </div>
        </div>
    </div>

    <!-- Report Display Section -->
    @if($reportType && $reportData && $reportData->count() > 0)
        <div class="bg-white rounded-lg shadow-sm border">
            <div class="px-6 py-4 border-b border-gray-200 flex items-center justify-between">
                <h2 class="text-lg font-semibold text-gray-900">
                    @switch($reportType)
                        @case('equipment_maintenance')
                            Equipment Maintenance Report
                            @break
                        @case('calibration_records')
                            Calibration Records Report
                            @break
                        @case('inventory_movement')
                            Inventory Movement Report
                            @break
                        @case('low_stock_alert')
                            Low Stock Alert Report
                            @break
                        @case('laboratory_results')
                            Laboratory Results Report
                            @break
                        @case('daily_collection')
                            Daily Collection Report
                            @break
                        @case('revenue_by_test')
                            Revenue by Test Report
                            @break
                        @case('test_volume')
                            Test Volume Report
                            @break
                        @case('issued_certificates')
                            Issued Certificates Report
                            @break
                        @case('activity_log')
                            Activity Log Report
                            @break
                        @case('expiring_inventory')
                            Expiring Inventory Report
                            @break
                    @endswitch
                </h2>
                <span class="text-sm text-gray-500">{{ $reportData->total() }} records found</span>
            </div>
            
            <div class="overflow-x-auto">
                @switch($reportType)
                    @case('equipment_maintenance')
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Equipment Name</th>
                                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Model</th>
                                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Serial Number</th>
                                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Section</th>
                                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Status</th>
                                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Purchase Date</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @forelse($reportData as $equipment)
                                    <tr class="hover:bg-gray-50">
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">{{ $equipment->equipment_name }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">{{ $equipment->model ?? 'N/A' }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">{{ $equipment->serial_number ?? 'N/A' }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">{{ $equipment->section->label ?? 'N/A' }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <span class="px-3 py-1 text-xs font-medium rounded-full 
                                                @if($equipment->status === 'active') bg-green-100 text-green-800
                                                @elseif($equipment->status === 'maintenance') bg-yellow-100 text-yellow-800
                                                @else bg-red-100 text-red-800
                                                @endif">
                                                {{ ucfirst($equipment->status ?? 'active') }}
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                                            {{ $equipment->purchase_date ? \Carbon\Carbon::parse($equipment->purchase_date)->format('M d, Y') : 'N/A' }}
                                        </td>
                                    </tr>
                                @empty
                                    <tr><td colspan="6" class="text-center py-8 text-gray-500">No equipment found</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                        @break
                    
                    @case('calibration_records')
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Equipment</th>
                                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Calibration Date</th>
                                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Next Due Date</th>
                                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Status</th>
                                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Performed By</th>
                                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Certificate No.</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @forelse($reportData as $calibration)
                                    <tr class="hover:bg-gray-50">
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">{{ $calibration->equipment->equipment_name ?? 'N/A' }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                                            {{ $calibration->calibration_date ? \Carbon\Carbon::parse($calibration->calibration_date)->format('M d, Y') : 'N/A' }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                                            {{ $calibration->next_due_date ? \Carbon\Carbon::parse($calibration->next_due_date)->format('M d, Y') : 'N/A' }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <span class="px-3 py-1 text-xs font-medium rounded-full 
                                                @if($calibration->status === 'completed') bg-green-100 text-green-800
                                                @elseif($calibration->status === 'due') bg-yellow-100 text-yellow-800
                                                @else bg-red-100 text-red-800
                                                @endif">
                                                {{ ucfirst($calibration->status ?? 'pending') }}
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">{{ $calibration->performed_by ?? 'N/A' }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">{{ $calibration->certificate_number ?? 'N/A' }}</td>
                                    </tr>
                                @empty
                                    <tr><td colspan="6" class="text-center py-8 text-gray-500">No calibration records found</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                        @break
                    
                    @case('inventory_movement')
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Date</th>
                                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Item</th>
                                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Type</th>
                                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Quantity</th>
                                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Supplier / Reference</th>
                                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Remarks</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @forelse($reportData as $movement)
                                    <tr class="hover:bg-gray-50">
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                                            {{ $movement->date ? \Carbon\Carbon::parse($movement->date)->format('M d, Y') : 'N/A' }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">{{ $movement->item->label ?? 'N/A' }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <span class="px-3 py-1 text-xs font-medium rounded-full 
                                                {{ $movement->type === 'Stock In' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                                {{ $movement->type }}
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                                            {{ $movement->quantity ?? 0 }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">{{ $movement->supplier ?? $movement->reference_number ?? 'N/A' }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">{{ $movement->remarks ?? '—' }}</td>
                                    </tr>
                                @empty
                                    <tr><td colspan="6" class="text-center py-8 text-gray-500">No inventory movements found</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                        @break
                    
                    @case('low_stock_alert')
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Item Name</th>
                                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Current Stock</th>
                                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Reorder Level</th>
                                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Unit</th>
                                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Section</th>
                                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Status</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @forelse($reportData as $item)
                                    <tr class="hover:bg-gray-50">
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">{{ $item->label }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-red-600 font-semibold">{{ $item->current_stock ?? 0 }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">{{ $item->reorder_level ?? 0 }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">{{ $item->unit ?? 'N/A' }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">{{ $item->section->label ?? 'N/A' }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <span class="px-3 py-1 text-xs font-medium rounded-full bg-red-100 text-red-800">
                                                Low Stock
                                            </span>
                                        </td>
                                    </tr>
                                @empty
                                    <tr><td colspan="6" class="text-center py-8 text-gray-500">All items are sufficiently stocked</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                        @break
                    
                    @case('laboratory_results')
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Date</th>
                                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Patient</th>
                                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Test</th>
                                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Result Value</th>
                                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Normal Range</th>
                                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Status</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @forelse($reportData as $result)
                                    <tr class="hover:bg-gray-50">
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                                            {{ $result->result_date ? \Carbon\Carbon::parse($result->result_date)->format('M d, Y') : 'N/A' }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">{{ $result->patient->full_name ?? 'N/A' }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">{{ $result->test->label ?? 'N/A' }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">{{ $result->result_value ?? 'N/A' }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">{{ $result->normal_range ?? 'N/A' }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <span class="px-3 py-1 text-xs font-medium rounded-full 
                                                @if($result->status === 'final') bg-green-100 text-green-800
                                                @elseif($result->status === 'revised') bg-blue-100 text-blue-800
                                                @else bg-yellow-100 text-yellow-800
                                                @endif">
                                                {{ ucfirst($result->status ?? 'draft') }}
                                            </span>
                                        </td>
                                    </tr>
                                @empty
                                    <tr><td colspan="6" class="text-center py-8 text-gray-500">No lab results found</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                        @break

                    @case('daily_collection')
                        @if(!empty($summaryTotals))
                        <div class="px-6 py-3 bg-blue-50 border-b border-blue-200 flex gap-8">
                            <div>
                                <span class="text-xs text-blue-600 uppercase font-bold">Total Transactions</span>
                                <p class="text-lg font-bold text-blue-900">{{ number_format($summaryTotals['total_transactions'] ?? 0) }}</p>
                            </div>
                            <div>
                                <span class="text-xs text-blue-600 uppercase font-bold">Total Amount</span>
                                <p class="text-lg font-bold text-blue-900">₱{{ number_format($summaryTotals['total_amount'] ?? 0, 2) }}</p>
                            </div>
                        </div>
                        @endif
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Date & Time</th>
                                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">OR Number</th>
                                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Patient</th>
                                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Designation</th>
                                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Status</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @forelse($reportData as $txn)
                                    <tr class="hover:bg-gray-50">
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                                            {{ $txn->datetime_added ? \Carbon\Carbon::parse($txn->datetime_added)->format('M d, Y h:i A') : 'N/A' }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">{{ $txn->or_number ?? 'N/A' }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">{{ $txn->patient->full_name ?? 'N/A' }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">{{ $txn->client_designation ?? 'N/A' }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <span class="px-3 py-1 text-xs font-medium rounded-full bg-green-100 text-green-800">
                                                {{ ucfirst($txn->status_code ?? 'completed') }}
                                            </span>
                                        </td>
                                    </tr>
                                @empty
                                    <tr><td colspan="5" class="text-center py-8 text-gray-500">No transactions found</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                        @break

                    @case('revenue_by_test')
                        @if(!empty($summaryTotals))
                        <div class="px-6 py-3 bg-green-50 border-b border-green-200 flex gap-8">
                            <div>
                                <span class="text-xs text-green-600 uppercase font-bold">Total Orders</span>
                                <p class="text-lg font-bold text-green-900">{{ number_format($summaryTotals['total_orders'] ?? 0) }}</p>
                            </div>
                            <div>
                                <span class="text-xs text-green-600 uppercase font-bold">Grand Revenue</span>
                                <p class="text-lg font-bold text-green-900">₱{{ number_format($summaryTotals['grand_revenue'] ?? 0, 2) }}</p>
                            </div>
                        </div>
                        @endif
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Test Name</th>
                                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Section</th>
                                    <th class="px-6 py-3 text-right text-xs font-semibold text-gray-500 uppercase tracking-wider">Unit Price</th>
                                    <th class="px-6 py-3 text-right text-xs font-semibold text-gray-500 uppercase tracking-wider">Total Orders</th>
                                    <th class="px-6 py-3 text-right text-xs font-semibold text-gray-500 uppercase tracking-wider">Total Revenue</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @forelse($reportData as $row)
                                    <tr class="hover:bg-gray-50">
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">{{ $row->test_name }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">{{ $row->section_name ?? 'N/A' }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600 text-right">₱{{ number_format($row->current_price, 2) }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600 text-right">{{ number_format($row->total_orders) }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-semibold text-green-700 text-right">₱{{ number_format($row->total_revenue, 2) }}</td>
                                    </tr>
                                @empty
                                    <tr><td colspan="5" class="text-center py-8 text-gray-500">No revenue data found</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                        @break

                    @case('test_volume')
                        @if(!empty($summaryTotals))
                        <div class="px-6 py-3 bg-purple-50 border-b border-purple-200 flex gap-8">
                            <div>
                                <span class="text-xs text-purple-600 uppercase font-bold">Total Tests</span>
                                <p class="text-lg font-bold text-purple-900">{{ number_format($summaryTotals['total'] ?? 0) }}</p>
                            </div>
                            <div>
                                <span class="text-xs text-purple-600 uppercase font-bold">Finalized</span>
                                <p class="text-lg font-bold text-green-700">{{ number_format($summaryTotals['final_total'] ?? 0) }}</p>
                            </div>
                            <div>
                                <span class="text-xs text-purple-600 uppercase font-bold">Draft</span>
                                <p class="text-lg font-bold text-yellow-700">{{ number_format($summaryTotals['draft_total'] ?? 0) }}</p>
                            </div>
                        </div>
                        @endif
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Test Name</th>
                                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Section</th>
                                    <th class="px-6 py-3 text-right text-xs font-semibold text-gray-500 uppercase tracking-wider">Total</th>
                                    <th class="px-6 py-3 text-right text-xs font-semibold text-gray-500 uppercase tracking-wider">Final</th>
                                    <th class="px-6 py-3 text-right text-xs font-semibold text-gray-500 uppercase tracking-wider">Draft</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @forelse($reportData as $vol)
                                    <tr class="hover:bg-gray-50">
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">{{ $vol->test->label ?? 'N/A' }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">{{ $vol->test->section->label ?? 'N/A' }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600 text-right">{{ number_format($vol->total_count) }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-green-700 text-right">{{ number_format($vol->final_count) }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-yellow-700 text-right">{{ number_format($vol->draft_count) }}</td>
                                    </tr>
                                @empty
                                    <tr><td colspan="5" class="text-center py-8 text-gray-500">No test volume data found</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                        @break

                    @case('issued_certificates')
                        @if(!empty($summaryTotals))
                        <div class="px-6 py-3 bg-indigo-50 border-b border-indigo-200 flex gap-8">
                            <div>
                                <span class="text-xs text-indigo-600 uppercase font-bold">Total</span>
                                <p class="text-lg font-bold text-indigo-900">{{ number_format($summaryTotals['total'] ?? 0) }}</p>
                            </div>
                            <div>
                                <span class="text-xs text-indigo-600 uppercase font-bold">Issued</span>
                                <p class="text-lg font-bold text-green-700">{{ number_format($summaryTotals['issued'] ?? 0) }}</p>
                            </div>
                            <div>
                                <span class="text-xs text-indigo-600 uppercase font-bold">Draft</span>
                                <p class="text-lg font-bold text-yellow-700">{{ number_format($summaryTotals['draft'] ?? 0) }}</p>
                            </div>
                            <div>
                                <span class="text-xs text-indigo-600 uppercase font-bold">Revoked</span>
                                <p class="text-lg font-bold text-red-700">{{ number_format($summaryTotals['revoked'] ?? 0) }}</p>
                            </div>
                        </div>
                        @endif
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Certificate No.</th>
                                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Type</th>
                                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Patient</th>
                                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Issued By</th>
                                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Issue Date</th>
                                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Status</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @forelse($reportData as $cert)
                                    <tr class="hover:bg-gray-50">
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">{{ $cert->certificate_number ?? 'N/A' }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">{{ ucfirst($cert->certificate_type ?? 'N/A') }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">{{ $cert->patient->full_name ?? 'N/A' }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">{{ $cert->issuedBy->full_name ?? 'N/A' }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                                            {{ $cert->issue_date ? \Carbon\Carbon::parse($cert->issue_date)->format('M d, Y') : 'N/A' }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <span class="px-3 py-1 text-xs font-medium rounded-full 
                                                @if($cert->status === 'issued') bg-green-100 text-green-800
                                                @elseif($cert->status === 'draft') bg-yellow-100 text-yellow-800
                                                @else bg-red-100 text-red-800
                                                @endif">
                                                {{ ucfirst($cert->status ?? 'draft') }}
                                            </span>
                                        </td>
                                    </tr>
                                @empty
                                    <tr><td colspan="6" class="text-center py-8 text-gray-500">No certificates found</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                        @break

                    @case('activity_log')
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Date & Time</th>
                                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Employee</th>
                                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Description</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @forelse($reportData as $log)
                                    <tr class="hover:bg-gray-50">
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                                            {{ $log->datetime_added ? \Carbon\Carbon::parse($log->datetime_added)->format('M d, Y h:i A') : 'N/A' }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">{{ $log->employee->full_name ?? 'N/A' }}</td>
                                        <td class="px-6 py-4 text-sm text-gray-600">{{ $log->description ?? 'N/A' }}</td>
                                    </tr>
                                @empty
                                    <tr><td colspan="3" class="text-center py-8 text-gray-500">No activity logs found</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                        @break

                    @case('expiring_inventory')
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Item Name</th>
                                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Section</th>
                                    <th class="px-6 py-3 text-right text-xs font-semibold text-gray-500 uppercase tracking-wider">Quantity</th>
                                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Expiry Date</th>
                                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Days Left</th>
                                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Urgency</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @forelse($reportData as $stock)
                                    @php
                                        $daysLeft = \Carbon\Carbon::now()->diffInDays(\Carbon\Carbon::parse($stock->expiry_date), false);
                                    @endphp
                                    <tr class="hover:bg-gray-50">
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">{{ $stock->item->label ?? 'N/A' }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">{{ $stock->item->section->label ?? 'N/A' }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600 text-right">{{ $stock->quantity ?? 0 }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                                            {{ $stock->expiry_date ? \Carbon\Carbon::parse($stock->expiry_date)->format('M d, Y') : 'N/A' }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-semibold {{ $daysLeft <= 30 ? 'text-red-600' : ($daysLeft <= 60 ? 'text-yellow-600' : 'text-gray-600') }}">
                                            {{ $daysLeft }} days
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <span class="px-3 py-1 text-xs font-medium rounded-full 
                                                @if($daysLeft <= 30) bg-red-100 text-red-800
                                                @elseif($daysLeft <= 60) bg-yellow-100 text-yellow-800
                                                @else bg-green-100 text-green-800
                                                @endif">
                                                @if($daysLeft <= 30) Critical
                                                @elseif($daysLeft <= 60) Warning
                                                @else OK
                                                @endif
                                            </span>
                                        </td>
                                    </tr>
                                @empty
                                    <tr><td colspan="6" class="text-center py-8 text-gray-500">No expiring inventory found</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                        @break
                @endswitch
            </div>

            @if($reportData->hasPages())
                <div class="px-6 py-4 border-t border-gray-200">
                    {{ $reportData->links() }}
                </div>
            @endif
        </div>
    @elseif($reportType && $reportData && $reportData->count() === 0)
        <div class="bg-white rounded-lg shadow-sm border p-12 text-center">
            <svg class="w-16 h-16 mx-auto mb-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
            </svg>
            <p class="text-gray-500 font-medium mb-2">No data found for the selected criteria</p>
            <p class="text-sm text-gray-400">Try adjusting the date range or removing sections filter</p>
        </div>
    @else
        <div class="bg-white rounded-lg shadow-sm border p-12 text-center">
            <svg class="w-16 h-16 mx-auto mb-4 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
            </svg>
            <p class="text-gray-600 font-medium mb-2">Ready to Generate Reports</p>
            <p class="text-sm text-gray-500">Select a report type, date range, and click "Generate Report" to begin</p>
        </div>
    @endif
</div>