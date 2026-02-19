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
use App\Exports\ReportExport;
use Maatwebsite\Excel\Facades\Excel;
use Carbon\Carbon;

new class extends Component
{
    use WithPagination;

    public $reportType = '';
    public $startDate = '';
    public $endDate = '';
    public $sectionId = '';

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
            default => 'Report',
        };

        $filename = $typeName . '_' . $this->startDate . '_to_' . $this->endDate . '.xlsx';

        return Excel::download(
            new ReportExport($this->reportType, $this->startDate, $this->endDate, $this->sectionId ?: null),
            $filename
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
            }
        }

        return [
            'reportData' => $reportData,
            'sections' => Section::active()->orderBy('label')->get(),
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
                        <option value="equipment_maintenance">Equipment Maintenance</option>
                        <option value="calibration_records">Calibration Records</option>
                        <option value="inventory_movement">Inventory Movement</option>
                        <option value="low_stock_alert">Low Stock Alert</option>
                        <option value="laboratory_results">Laboratory Results</option>
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