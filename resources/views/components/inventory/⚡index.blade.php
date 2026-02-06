<?php

use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Validate;
use App\Models\StockIn;
use App\Models\StockOut;
use App\Models\StockUsage;
use App\Models\Item;
use App\Models\Employee;
use App\Models\Section;
use App\Traits\LogsActivity;

new class extends Component
{
    use WithPagination, LogsActivity;

    public $activeTab = 'stock_in';
    
    // Stock In Properties
    #[Validate('required|exists:item,item_id')]
    public $stock_in_item_id = '';
    
    #[Validate('required|integer|min:1')]
    public $stock_in_quantity = '';
    
    #[Validate('nullable|string|max:100')]
    public $stock_in_supplier = '';
    
    #[Validate('nullable|string|max:50')]
    public $stock_in_reference = '';
    
    #[Validate('nullable|date')]
    public $stock_in_expiry = '';
    
    #[Validate('nullable|string|max:500')]
    public $stock_in_remarks = '';

    // Stock Out Properties
    #[Validate('required|exists:item,item_id')]
    public $stock_out_item_id = '';
    
    #[Validate('required|integer|min:1')]
    public $stock_out_quantity = '';
    
    #[Validate('nullable|string|max:50')]
    public $stock_out_reference = '';
    
    #[Validate('required|string|max:500')]
    public $stock_out_remarks = '';

    // Stock Usage Properties
    #[Validate('required|exists:item,item_id')]
    public $stock_usage_item_id = '';
    
    #[Validate('required|integer|min:1')]
    public $stock_usage_quantity = 1;
    
    #[Validate('required|exists:employee,employee_id')]
    public $stock_usage_employee_id = '';
    
    #[Validate('required|string|max:30')]
    public $stock_usage_purpose = '';
    
    #[Validate('nullable|integer')]
    public $stock_usage_or_number = '';

    // Inventory Table Filters
    public $search = '';
    public $filterSection = '';
    public $filterStatus = '';
    public $perPage = 10;

    public $flashMessage = '';
    public $movementPerPage = 10;

    public function mount()
    {
        if (session('success')) {
            $this->flashMessage = session('success');
        }
    }

    public function setTab($tab)
    {
        $this->activeTab = $tab;
        $this->resetValidation();
    }

    public function addStock()
    {
        $this->validate([
            'stock_in_item_id' => 'required|exists:item,item_id',
            'stock_in_quantity' => 'required|integer|min:1',
            'stock_in_supplier' => 'nullable|string|max:100',
            'stock_in_reference' => 'nullable|string|max:50',
            'stock_in_expiry' => 'nullable|date',
            'stock_in_remarks' => 'nullable|string|max:500',
        ]);

        StockIn::create([
            'item_id' => $this->stock_in_item_id,
            'quantity' => $this->stock_in_quantity,
            'performed_by' => auth()->user()->employee->employee_id ?? null,
            'supplier' => $this->stock_in_supplier,
            'reference_number' => $this->stock_in_reference,
            'expiry_date' => $this->stock_in_expiry,
            'remarks' => $this->stock_in_remarks,
            'datetime_added' => now(),
        ]);

        // Log activity
        $item = Item::find($this->stock_in_item_id);
        $this->logActivity("Added {$this->stock_in_quantity} units of {$item->label} to stock" . 
            ($this->stock_in_supplier ? " from {$this->stock_in_supplier}" : ""));

        // Clear cache
        cache()->forget('items_with_stock');

        $this->reset(['stock_in_item_id', 'stock_in_quantity', 'stock_in_supplier', 'stock_in_reference', 'stock_in_expiry', 'stock_in_remarks']);
        $this->flashMessage = 'Stock added successfully!';
    }

    public function removeStock()
    {
        $this->validate([
            'stock_out_item_id' => 'required|exists:item,item_id',
            'stock_out_quantity' => 'required|integer|min:1',
            'stock_out_reference' => 'nullable|string|max:50',
            'stock_out_remarks' => 'required|string|max:500',
        ]);

        // Check if item has sufficient stock
        $item = Item::find($this->stock_out_item_id);
        $totalIn = StockIn::where('item_id', $this->stock_out_item_id)->sum('quantity');
        $totalOut = StockOut::where('item_id', $this->stock_out_item_id)->sum('quantity');
        $totalUsage = StockUsage::where('item_id', $this->stock_out_item_id)->sum('quantity');
        $currentStock = $totalIn - $totalOut - $totalUsage;

        if ($currentStock < $this->stock_out_quantity) {
            $this->addError('stock_out_quantity', 'Insufficient stock. Available: ' . number_format($currentStock));
            return;
        }

        StockOut::create([
            'item_id' => $this->stock_out_item_id,
            'quantity' => $this->stock_out_quantity,
            'performed_by' => auth()->user()->employee->employee_id ?? null,
            'reference_number' => $this->stock_out_reference,
            'remarks' => $this->stock_out_remarks,
            'datetime_added' => now(),
        ]);

        // Log activity
        $item = Item::find($this->stock_out_item_id);
        $this->logActivity("Removed {$this->stock_out_quantity} units of {$item->label} from stock - {$this->stock_out_remarks}");

        // Clear cache
        cache()->forget('items_with_stock');

        $this->reset(['stock_out_item_id', 'stock_out_quantity', 'stock_out_reference', 'stock_out_remarks']);
        $this->flashMessage = 'Stock removed successfully!';
    }

    public function recordUsage()
    {
        $this->validate([
            'stock_usage_item_id' => 'required|exists:item,item_id',
            'stock_usage_quantity' => 'required|integer|min:1',
            'stock_usage_employee_id' => 'required|exists:employee,employee_id',
            'stock_usage_purpose' => 'required|string|max:30',
            'stock_usage_or_number' => 'nullable|integer',
        ]);

        // Check if item has sufficient stock
        $item = Item::find($this->stock_usage_item_id);
        $totalIn = StockIn::where('item_id', $this->stock_usage_item_id)->sum('quantity');
        $totalOut = StockOut::where('item_id', $this->stock_usage_item_id)->sum('quantity');
        $totalUsage = StockUsage::where('item_id', $this->stock_usage_item_id)->sum('quantity');
        $currentStock = $totalIn - $totalOut - $totalUsage;

        if ($currentStock < $this->stock_usage_quantity) {
            $this->addError('stock_usage_quantity', 'Insufficient stock. Available: ' . number_format($currentStock));
            return;
        }

        $employee = Employee::find($this->stock_usage_employee_id);

        StockUsage::create([
            'item_id' => $this->stock_usage_item_id,
            'quantity' => $this->stock_usage_quantity,
            'employee_id' => $this->stock_usage_employee_id,
            'firstname' => $employee->firstname,
            'middlename' => $employee->middlename,
            'lastname' => $employee->lastname,
            'purpose' => $this->stock_usage_purpose,
            'datetime_added' => now(),
            'or_number' => $this->stock_usage_or_number ?? 0,
        ]);

        // Log activity
        $item = Item::find($this->stock_usage_item_id);
        $this->logActivity("Recorded usage of {$this->stock_usage_quantity} units of {$item->label} for {$this->stock_usage_purpose}");

        // Clear cache
        cache()->forget('items_with_stock');

        $this->reset(['stock_usage_item_id', 'stock_usage_quantity', 'stock_usage_employee_id', 'stock_usage_purpose', 'stock_usage_or_number']);
        $this->stock_usage_quantity = 1;
        $this->flashMessage = 'Stock usage recorded successfully!';
    }

    public function with(): array
    {
        // Calculate inventory levels - only show items that have been stocked in
        // Optimized: Use pagination directly instead of get()->map()
        $inventoryQuery = Item::active()
            ->with('section:section_id,label')
            ->leftJoin('item_type', 'item.item_type_id', '=', 'item_type.item_type_id')
            ->select('item.item_id', 'item.label', 'item.section_id', 'item.unit', 'item.reorder_level', 'item_type.label as item_type_label')
            ->selectRaw('(SELECT COALESCE(SUM(quantity), 0) FROM stock_in WHERE stock_in.item_id = item.item_id) as total_in')
            ->selectRaw('(SELECT COALESCE(SUM(quantity), 0) FROM stock_out WHERE stock_out.item_id = item.item_id) as total_out')
            ->selectRaw('(SELECT COALESCE(SUM(quantity), 0) FROM stock_usage WHERE stock_usage.item_id = item.item_id) as total_usage')
            ->whereRaw('(SELECT COALESCE(SUM(quantity), 0) FROM stock_in WHERE stock_in.item_id = item.item_id) > 0')
            ->when($this->search, function($query) {
                $query->where('item.label', 'like', '%' . $this->search . '%');
            })
            ->when($this->filterSection, function($query) {
                $query->where('item.section_id', $this->filterSection);
            });

        // Apply status filter at database level
        if ($this->filterStatus === 'low') {
            $inventoryQuery->whereRaw('(SELECT COALESCE(SUM(quantity), 0) FROM stock_in WHERE stock_in.item_id = item.item_id) - 
                                       (SELECT COALESCE(SUM(quantity), 0) FROM stock_out WHERE stock_out.item_id = item.item_id) - 
                                       (SELECT COALESCE(SUM(quantity), 0) FROM stock_usage WHERE stock_usage.item_id = item.item_id) <= item.reorder_level');
        } elseif ($this->filterStatus === 'in_stock') {
            $inventoryQuery->whereRaw('(SELECT COALESCE(SUM(quantity), 0) FROM stock_in WHERE stock_in.item_id = item.item_id) - 
                                       (SELECT COALESCE(SUM(quantity), 0) FROM stock_out WHERE stock_out.item_id = item.item_id) - 
                                       (SELECT COALESCE(SUM(quantity), 0) FROM stock_usage WHERE stock_usage.item_id = item.item_id) > COALESCE(item.reorder_level, 0)');
        }

        $paginatedInventory = $inventoryQuery->paginate($this->perPage);

        // Get recent stock movements - optimized with limit at database level
        $stockInMovements = StockIn::with(['item:item_id,label,section_id', 'item.section:section_id,label', 'performedByEmployee:employee_id,firstname,lastname'])
            ->select('stock_in_id as id', 'item_id', 'quantity', 'datetime_added', 'remarks', 'performed_by', 'supplier')
            ->selectRaw("'IN' as type")
            ->orderBy('datetime_added', 'desc')
            ->limit(50)
            ->get();

        $stockOutMovements = StockOut::with(['item:item_id,label,section_id', 'item.section:section_id,label', 'performedByEmployee:employee_id,firstname,lastname'])
            ->select('stock_out_id as id', 'item_id', 'quantity', 'datetime_added', 'remarks', 'performed_by', 'reference_number')
            ->selectRaw("'OUT' as type")
            ->orderBy('datetime_added', 'desc')
            ->limit(50)
            ->get();

        $stockUsageMovements = StockUsage::with(['item:item_id,label,section_id', 'item.section:section_id,label', 'employee:employee_id,firstname,lastname'])
            ->select('stock_usage_id as id', 'item_id', 'quantity', 'datetime_added', 'purpose as remarks', 'employee_id as performed_by')
            ->selectRaw("'USAGE' as type")
            ->selectRaw("CONCAT(firstname, ' ', lastname) as reference")
            ->orderBy('datetime_added', 'desc')
            ->limit(50)
            ->get();

        $movements = $stockInMovements
            ->concat($stockOutMovements)
            ->concat($stockUsageMovements)
            ->sortByDesc('datetime_added')
            ->take(100);

        // Paginate movements
        $movementPage = request()->get('movement_page', 1);
        $movementOffset = ($movementPage - 1) * $this->movementPerPage;
        
        $paginatedMovements = new \Illuminate\Pagination\LengthAwarePaginator(
            $movements->slice($movementOffset, $this->movementPerPage)->all(),
            $movements->count(),
            $this->movementPerPage,
            $movementPage,
            ['path' => request()->url(), 'query' => request()->query(), 'pageName' => 'movement_page']
        );

        // Get items with stock for Stock Out and Stock Usage dropdowns - optimized with caching
        $itemsWithStock = cache()->remember('items_with_stock', 60, function() {
            return Item::active()
                ->select('item.item_id', 'item.label')
                ->selectRaw('(SELECT COALESCE(SUM(quantity), 0) FROM stock_in WHERE stock_in.item_id = item.item_id) as total_in')
                ->selectRaw('(SELECT COALESCE(SUM(quantity), 0) FROM stock_out WHERE stock_out.item_id = item.item_id) as total_out')
                ->selectRaw('(SELECT COALESCE(SUM(quantity), 0) FROM stock_usage WHERE stock_usage.item_id = item.item_id) as total_usage')
                ->whereRaw('((SELECT COALESCE(SUM(quantity), 0) FROM stock_in WHERE stock_in.item_id = item.item_id) - 
                             (SELECT COALESCE(SUM(quantity), 0) FROM stock_out WHERE stock_out.item_id = item.item_id) - 
                             (SELECT COALESCE(SUM(quantity), 0) FROM stock_usage WHERE stock_usage.item_id = item.item_id)) > 0')
                ->orderBy('label')
                ->get()
                ->map(function($item) {
                    $item->current_stock = $item->total_in - $item->total_out - $item->total_usage;
                    return $item;
                });
        });

        return [
            'items' => cache()->remember('items_dropdown', 300, function() {
                return Item::active()->select('item_id', 'label')->orderBy('label')->get();
            }),
            'itemsWithStock' => $itemsWithStock,
            'employees' => cache()->remember('employees_dropdown', 300, function() {
                return Employee::active()->select('employee_id', 'firstname', 'lastname')->orderBy('firstname')->get();
            }),
            'sections' => cache()->remember('sections_dropdown', 300, function() {
                return Section::active()->select('section_id', 'label')->orderBy('label')->get();
            }),
            'inventory' => $paginatedInventory,
            'movements' => $paginatedMovements,
        ];
    }
}; ?>

<div class="min-h-screen bg-gray-50">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <!-- Success Message -->
        @if($flashMessage)
            <div class="mb-6 bg-white border-l-4 border-green-500 shadow-sm rounded-lg p-4 flex items-center justify-between">
                <div class="flex items-center">
                    <svg class="w-5 h-5 text-green-500 mr-3" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                    </svg>
                    <span class="text-sm font-medium text-gray-900">{{ $flashMessage }}</span>
                </div>
            </div>
        @endif

        <!-- Header -->
        <div class="mb-8">
            <h1 class="text-3xl font-bold text-gray-900">Inventory Management</h1>
            <p class="mt-1 text-sm text-gray-500">Track and manage your laboratory inventory</p>
        </div>

        <!-- Tab Navigation - Modern Minimal -->
        <div class="mb-8">
            <div class="border-b border-gray-200">
                <nav class="-mb-px flex space-x-8">
                    <button wire:click="setTab('stock_in')" 
                            class="group inline-flex items-center py-4 px-1 border-b-2 font-medium text-sm transition-all {{ $activeTab === 'stock_in' ? 'border-emerald-500 text-emerald-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }}">
                        <svg class="mr-2 h-5 w-5 {{ $activeTab === 'stock_in' ? 'text-emerald-500' : 'text-gray-400 group-hover:text-gray-500' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 11l5-5m0 0l5 5m-5-5v12"/>
                        </svg>
                        Stock In
                    </button>
                    <button wire:click="setTab('stock_out')" 
                            class="group inline-flex items-center py-4 px-1 border-b-2 font-medium text-sm transition-all {{ $activeTab === 'stock_out' ? 'border-rose-500 text-rose-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }}">
                        <svg class="mr-2 h-5 w-5 {{ $activeTab === 'stock_out' ? 'text-rose-500' : 'text-gray-400 group-hover:text-gray-500' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 13l-5 5m0 0l-5-5m5 5V6"/>
                        </svg>
                        Stock Out
                    </button>
                    <button wire:click="setTab('stock_usage')" 
                            class="group inline-flex items-center py-4 px-1 border-b-2 font-medium text-sm transition-all {{ $activeTab === 'stock_usage' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }}">
                        <svg class="mr-2 h-5 w-5 {{ $activeTab === 'stock_usage' ? 'text-blue-500' : 'text-gray-400 group-hover:text-gray-500' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                        </svg>
                        Stock Usage
                    </button>
                </nav>
            </div>
        </div>

        <!-- Forms Card -->
        <!-- Forms Card -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
            <div class="p-8">
                <!-- Stock In Form -->
                @if($activeTab === 'stock_in')
                <form wire:submit.prevent="addStock" class="space-y-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="space-y-2">
                            <label class="text-sm font-medium text-gray-700">Item <span class="text-rose-500">*</span></label>
                            <select wire:model="stock_in_item_id" class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-transparent transition-all" required>
                                <option value="">Select Item</option>
                                @foreach($items as $item)
                                    <option value="{{ $item->item_id }}">{{ $item->label }}</option>
                                @endforeach
                            </select>
                            @error('stock_in_item_id') <span class="text-xs text-rose-600">{{ $message }}</span> @enderror
                        </div>
                        <div class="space-y-2">
                            <label class="text-sm font-medium text-gray-700">Quantity <span class="text-rose-500">*</span></label>
                            <input type="number" wire:model="stock_in_quantity" min="1" placeholder="0" class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-transparent transition-all" required>
                            @error('stock_in_quantity') <span class="text-xs text-rose-600">{{ $message }}</span> @enderror
                        </div>
                        <div class="space-y-2">
                            <label class="text-sm font-medium text-gray-700">Supplier</label>
                            <input type="text" wire:model="stock_in_supplier" placeholder="Supplier name" class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-transparent transition-all">
                            @error('stock_in_supplier') <span class="text-xs text-rose-600">{{ $message }}</span> @enderror
                        </div>
                        <div class="space-y-2">
                            <label class="text-sm font-medium text-gray-700">Reference No.</label>
                            <input type="text" wire:model="stock_in_reference" placeholder="Invoice/PO number" class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-transparent transition-all">
                            @error('stock_in_reference') <span class="text-xs text-rose-600">{{ $message }}</span> @enderror
                        </div>
                        <div class="space-y-2">
                            <label class="text-sm font-medium text-gray-700">Expiry Date</label>
                            <input type="date" wire:model="stock_in_expiry" class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-transparent transition-all">
                            @error('stock_in_expiry') <span class="text-xs text-rose-600">{{ $message }}</span> @enderror
                        </div>
                        <div class="space-y-2">
                            <label class="text-sm font-medium text-gray-700">Remarks</label>
                            <input type="text" wire:model="stock_in_remarks" placeholder="Optional notes" class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-transparent transition-all">
                            @error('stock_in_remarks') <span class="text-xs text-rose-600">{{ $message }}</span> @enderror
                        </div>
                    </div>
                    <div class="flex justify-end pt-4 border-t border-gray-100">
                        <button type="submit" class="px-8 py-3 bg-emerald-600 hover:bg-emerald-700 text-white font-medium rounded-lg transition-all shadow-sm hover:shadow flex items-center">
                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                            </svg>
                            Add Stock
                        </button>
                    </div>
                </form>
                @endif

                <!-- Stock Out Form -->
                @if($activeTab === 'stock_out')
                <form wire:submit.prevent="removeStock" class="space-y-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="space-y-2">
                            <label class="text-sm font-medium text-gray-700">Item <span class="text-rose-500">*</span></label>
                            <select wire:model="stock_out_item_id" class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-lg focus:ring-2 focus:ring-rose-500 focus:border-transparent transition-all" required>
                                <option value="">Select Item</option>
                                @foreach($itemsWithStock as $item)
                                    <option value="{{ $item->item_id }}">{{ $item->label }} (Available: {{ number_format($item->current_stock) }})</option>
                                @endforeach
                            </select>
                            @error('stock_out_item_id') <span class="text-xs text-rose-600">{{ $message }}</span> @enderror
                        </div>
                        <div class="space-y-2">
                            <label class="text-sm font-medium text-gray-700">Quantity <span class="text-rose-500">*</span></label>
                            <input type="number" wire:model="stock_out_quantity" min="1" placeholder="0" class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-lg focus:ring-2 focus:ring-rose-500 focus:border-transparent transition-all" required>
                            @error('stock_out_quantity') <span class="text-xs text-rose-600">{{ $message }}</span> @enderror
                        </div>
                        <div class="space-y-2">
                            <label class="text-sm font-medium text-gray-700">Reference No.</label>
                            <input type="text" wire:model="stock_out_reference" placeholder="Requisition number" class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-lg focus:ring-2 focus:ring-rose-500 focus:border-transparent transition-all">
                            @error('stock_out_reference') <span class="text-xs text-rose-600">{{ $message }}</span> @enderror
                        </div>
                        <div class="space-y-2">
                            <label class="text-sm font-medium text-gray-700">Remarks <span class="text-rose-500">*</span></label>
                            <input type="text" wire:model="stock_out_remarks" placeholder="Reason for removal" class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-lg focus:ring-2 focus:ring-rose-500 focus:border-transparent transition-all" required>
                            @error('stock_out_remarks') <span class="text-xs text-rose-600">{{ $message }}</span> @enderror
                        </div>
                    </div>
                    <div class="flex justify-end pt-4 border-t border-gray-100">
                        <button type="submit" class="px-8 py-3 bg-rose-600 hover:bg-rose-700 text-white font-medium rounded-lg transition-all shadow-sm hover:shadow flex items-center">
                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 12H4"/>
                            </svg>
                            Remove Stock
                        </button>
                    </div>
                </form>
                @endif

                <!-- Stock Usage Form -->
                @if($activeTab === 'stock_usage')
                <form wire:submit.prevent="recordUsage" class="space-y-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="space-y-2">
                            <label class="text-sm font-medium text-gray-700">Item <span class="text-rose-500">*</span></label>
                            <select wire:model="stock_usage_item_id" class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all" required>
                                <option value="">Select Item</option>
                                @foreach($itemsWithStock as $item)
                                    <option value="{{ $item->item_id }}">{{ $item->label }} (Available: {{ number_format($item->current_stock) }})</option>
                                @endforeach
                            </select>
                            @error('stock_usage_item_id') <span class="text-xs text-rose-600">{{ $message }}</span> @enderror
                        </div>
                        <div class="space-y-2">
                            <label class="text-sm font-medium text-gray-700">Quantity <span class="text-rose-500">*</span></label>
                            <input type="number" wire:model="stock_usage_quantity" min="1" placeholder="1" class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all" required>
                            @error('stock_usage_quantity') <span class="text-xs text-rose-600">{{ $message }}</span> @enderror
                        </div>
                        <div class="space-y-2">
                            <label class="text-sm font-medium text-gray-700">Employee <span class="text-rose-500">*</span></label>
                            <select wire:model="stock_usage_employee_id" class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all" required>
                                <option value="">Select Employee</option>
                                @foreach($employees as $employee)
                                    <option value="{{ $employee->employee_id }}">{{ $employee->firstname }} {{ $employee->lastname }}</option>
                                @endforeach
                            </select>
                            @error('stock_usage_employee_id') <span class="text-xs text-rose-600">{{ $message }}</span> @enderror
                        </div>
                        <div class="space-y-2">
                            <label class="text-sm font-medium text-gray-700">Purpose <span class="text-rose-500">*</span></label>
                            <input type="text" wire:model="stock_usage_purpose" placeholder="Purpose of use" class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all" required>
                            @error('stock_usage_purpose') <span class="text-xs text-rose-600">{{ $message }}</span> @enderror
                        </div>
                        <div class="space-y-2">
                            <label class="text-sm font-medium text-gray-700">OR Number</label>
                            <input type="number" wire:model="stock_usage_or_number" placeholder="Official receipt number" class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all">
                            @error('stock_usage_or_number') <span class="text-xs text-rose-600">{{ $message }}</span> @enderror
                        </div>
                    </div>
                    <div class="flex justify-end pt-4 border-t border-gray-100">
                        <button type="submit" class="px-8 py-3 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-lg transition-all shadow-sm hover:shadow flex items-center">
                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                            </svg>
                            Record Usage
                        </button>
                    </div>
                </form>
                @endif
            </div>
        </div>

        </div>

        <!-- Current Inventory Levels -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden mt-8">
            <div class="px-8 py-6 border-b border-gray-200">
                <h2 class="text-xl font-bold text-gray-900">Current Inventory Levels</h2>
                <p class="mt-1 text-sm text-gray-500">Track your stock levels in real-time</p>
            </div>
            
            <!-- Filters -->
            <div class="px-8 py-6 bg-gray-50 border-b border-gray-200">
                <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                    <div class="space-y-2">
                        <label class="text-xs font-medium text-gray-700 uppercase tracking-wide">Search</label>
                        <input type="text" wire:model.live="search" placeholder="Item name..." class="w-full px-4 py-2.5 bg-white border border-gray-200 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all text-sm">
                    </div>
                    <div class="space-y-2">
                        <label class="text-xs font-medium text-gray-700 uppercase tracking-wide">Section</label>
                        <select wire:model.live="filterSection" class="w-full px-4 py-2.5 bg-white border border-gray-200 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all text-sm">
                            <option value="">All Sections</option>
                            @foreach($sections as $section)
                                <option value="{{ $section->section_id }}">{{ $section->label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="space-y-2">
                        <label class="text-xs font-medium text-gray-700 uppercase tracking-wide">Status</label>
                        <select wire:model.live="filterStatus" class="w-full px-4 py-2.5 bg-white border border-gray-200 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all text-sm">
                            <option value="">All Status</option>
                            <option value="low">Low Stock</option>
                            <option value="in_stock">In Stock</option>
                        </select>
                    </div>
                    <div class="space-y-2">
                        <label class="text-xs font-medium text-gray-700 uppercase tracking-wide">Per Page</label>
                        <select wire:model.live="perPage" class="w-full px-4 py-2.5 bg-white border border-gray-200 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all text-sm">
                            <option value="10">10</option>
                            <option value="25">25</option>
                            <option value="50">50</option>
                            <option value="100">100</option>
                        </select>
                    </div>
                </div>
            </div>

            <div class="px-8 py-4 bg-white">
                <p class="text-sm text-gray-600">Showing {{ $inventory->count() }} of {{ $inventory->total() }} items</p>
            </div>

            <!-- Inventory Table -->
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead>
                        <tr class="bg-gray-50 border-y border-gray-200">
                            <th class="px-4 py-3 text-left text-xs font-bold text-gray-700 uppercase tracking-wide">Item Name</th>
                            <th class="px-4 py-3 text-left text-xs font-bold text-gray-700 uppercase tracking-wide">Section</th>
                            <th class="px-3 py-3 text-center text-xs font-bold text-gray-700 uppercase tracking-wide">Unit</th>
                            <th class="px-3 py-3 text-center text-xs font-bold text-gray-700 uppercase tracking-wide">Total In</th>
                            <th class="px-3 py-3 text-center text-xs font-bold text-gray-700 uppercase tracking-wide">Total Out</th>
                            <th class="px-3 py-3 text-center text-xs font-bold text-gray-700 uppercase tracking-wide">Current Stock</th>
                            <th class="px-3 py-3 text-center text-xs font-bold text-gray-700 uppercase tracking-wide">Reorder Level</th>
                            <th class="px-4 py-3 text-left text-xs font-bold text-gray-700 uppercase tracking-wide">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 bg-white">
                        @forelse($inventory as $item)
                            @php
                                $currentStock = $item->total_in - $item->total_out - $item->total_usage;
                                $reorderLevel = $item->reorder_level ?? 0;
                                $isLowStock = $currentStock <= $reorderLevel;
                            @endphp
                            <tr class="hover:bg-gray-50 transition-colors {{ $isLowStock ? 'bg-orange-50' : '' }}">
                                <td class="px-4 py-3">
                                    <div class="font-medium text-sm text-gray-900">{{ $item->label }}</div>
                                </td>
                                <td class="px-4 py-3">
                                    <span class="text-sm text-gray-700">{{ $item->section->label ?? 'N/A' }}</span>
                                </td>
                                <td class="px-3 py-3 text-center">
                                    <span class="text-sm text-gray-700">{{ $item->unit ?? 'pcs' }}</span>
                                </td>
                                <td class="px-3 py-3 text-center">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-md bg-emerald-50 text-emerald-700 text-xs font-medium">
                                        {{ number_format($item->total_in) }}
                                    </span>
                                </td>
                                <td class="px-3 py-3 text-center">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-md bg-gray-100 text-gray-700 text-xs font-medium">
                                        {{ number_format($item->total_out + $item->total_usage) }}
                                    </span>
                                </td>
                                <td class="px-3 py-3 text-center">
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-md text-sm font-bold {{ $isLowStock ? 'bg-orange-100 text-orange-700' : 'bg-blue-50 text-blue-700' }}">
                                        {{ number_format($currentStock) }}
                                    </span>
                                </td>
                                <td class="px-3 py-3 text-center">
                                    <span class="text-sm text-gray-600">{{ number_format($reorderLevel) }}</span>
                                </td>
                                <td class="px-4 py-3">
                                    @if($isLowStock)
                                        <span class="inline-flex items-center px-3 py-1 text-xs font-semibold rounded-full bg-orange-100 text-orange-700">
                                            <svg class="w-3 h-3 mr-1.5" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                                            </svg>
                                            LOW STOCK
                                        </span>
                                    @else
                                        <span class="inline-flex items-center px-3 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-700">
                                            <svg class="w-3 h-3 mr-1.5" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                            </svg>
                                            IN STOCK
                                        </span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="px-4 py-8 text-center">
                                    <svg class="mx-auto h-10 w-10 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"/>
                                    </svg>
                                    <p class="mt-2 text-sm text-gray-500">No items found</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div class="px-8 py-4 border-t border-gray-200 bg-gray-50">
                {{ $inventory->links() }}
            </div>
        </div>

        <!-- Stock Movement History -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden mt-8">
            <div class="px-8 py-6 border-b border-gray-200">
                <h2 class="text-xl font-bold text-gray-900">Stock Movement History</h2>
                <p class="mt-1 text-sm text-gray-500">Recent stock transactions and activities</p>
            </div>

            <!-- Movement Table -->
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead>
                        <tr class="bg-gray-50 border-y border-gray-200">
                            <th class="px-4 py-3 text-left text-xs font-bold text-gray-700 uppercase tracking-wide">Date & Time</th>
                            <th class="px-4 py-3 text-left text-xs font-bold text-gray-700 uppercase tracking-wide">Type</th>
                            <th class="px-4 py-3 text-left text-xs font-bold text-gray-700 uppercase tracking-wide">Item</th>
                            <th class="px-3 py-3 text-center text-xs font-bold text-gray-700 uppercase tracking-wide">Quantity</th>
                            <th class="px-4 py-3 text-left text-xs font-bold text-gray-700 uppercase tracking-wide">Reference</th>
                            <th class="px-4 py-3 text-left text-xs font-bold text-gray-700 uppercase tracking-wide">Performed By</th>
                            <th class="px-4 py-3 text-left text-xs font-bold text-gray-700 uppercase tracking-wide">Remarks</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 bg-white">
                        @forelse($movements as $movement)
                            <tr class="hover:bg-gray-50 transition-colors">
                                <td class="px-4 py-3 whitespace-nowrap">
                                    <div class="text-sm font-medium text-gray-900">{{ $movement->datetime_added->format('M d, Y') }}</div>
                                    <div class="text-xs text-gray-500">{{ $movement->datetime_added->format('h:i A') }}</div>
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap">
                                    @if($movement->type === 'IN')
                                        <span class="inline-flex items-center px-2.5 py-1 rounded-md bg-emerald-100 text-emerald-700 text-xs font-semibold">
                                            <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 11l5-5m0 0l5 5m-5-5v12"/>
                                            </svg>
                                            STOCK IN
                                        </span>
                                    @elseif($movement->type === 'OUT')
                                        <span class="inline-flex items-center px-2.5 py-1 rounded-md bg-rose-100 text-rose-700 text-xs font-semibold">
                                            <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 13l-5 5m0 0l-5-5m5 5V6"/>
                                            </svg>
                                            STOCK OUT
                                        </span>
                                    @else
                                        <span class="inline-flex items-center px-2.5 py-1 rounded-md bg-blue-100 text-blue-700 text-xs font-semibold">
                                            <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                                            </svg>
                                            USAGE
                                        </span>
                                    @endif
                                </td>
                                <td class="px-4 py-3">
                                    <div class="font-medium text-sm text-gray-900">{{ $movement->item->label ?? 'N/A' }}</div>
                                    <div class="text-xs text-gray-500">{{ $movement->item->section->label ?? '' }}</div>
                                </td>
                                <td class="px-3 py-3 text-center">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-md text-xs font-bold
                                        {{ $movement->type === 'IN' ? 'bg-emerald-50 text-emerald-700' : 'bg-gray-100 text-gray-700' }}">
                                        {{ $movement->type === 'IN' ? '+' : '-' }}{{ number_format($movement->quantity) }}
                                    </span>
                                </td>
                                <td class="px-4 py-3">
                                    <span class="text-sm text-gray-700">{{ $movement->reference ?? '—' }}</span>
                                </td>
                                <td class="px-4 py-3">
                                    <span class="text-sm text-gray-700">
                                        @if($movement->type === 'USAGE' && $movement->employee)
                                            {{ $movement->employee->firstname }} {{ $movement->employee->lastname }}
                                        @elseif($movement->performedByEmployee)
                                            {{ $movement->performedByEmployee->firstname }} {{ $movement->performedByEmployee->lastname }}
                                        @else
                                            System
                                        @endif
                                    </span>
                                </td>
                                <td class="px-4 py-3">
                                    <span class="text-sm text-gray-600">{{ Str::limit($movement->remarks ?? '—', 40) }}</span>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-4 py-8 text-center">
                                    <svg class="mx-auto h-10 w-10 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                    </svg>
                                    <p class="mt-2 text-sm text-gray-500">No stock movements recorded yet</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div class="px-8 py-4 border-t border-gray-200 bg-gray-50">
                {{ $movements->links() }}
            </div>
        </div>
    </div>
</div>
