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
    
    // Stock In Properties - Multiple Items Support
    public $stock_in_items = [];
    
    #[Validate('nullable|string|max:100')]
    public $stock_in_supplier = '';
    
    #[Validate('nullable|string|max:50')]
    public $stock_in_reference = '';
    
    #[Validate('nullable|string|max:500')]
    public $stock_in_remarks = '';

    // Stock Out Properties - Multiple Items Support
    public $stock_out_items = [];
    
    #[Validate('nullable|string|max:50')]
    public $stock_out_reference = '';
    
    #[Validate('nullable|string|max:500')]
    public $stock_out_remarks = '';

    // Stock Usage Properties - Multiple Items Support
    public $stock_usage_items = [];
    
    #[Validate('required|exists:employee,employee_id')]
    public $stock_usage_employee_id = '';
    
    #[Validate('required|string|max:30')]
    public $stock_usage_purpose = '';
    
    #[Validate('nullable|string|max:50')]
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
        // Initialize with one empty row for each form
        $this->addStockInRow();
        $this->addStockOutRow();
        $this->addStockUsageRow();
    }
    
    // Stock In Row Management
    public function addStockInRow()
    {
        $this->stock_in_items[] = ['item_id' => '', 'quantity' => '', 'expiry_date' => ''];
    }
    
    public function removeStockInRow($index)
    {
        unset($this->stock_in_items[$index]);
        $this->stock_in_items = array_values($this->stock_in_items);
        if (count($this->stock_in_items) === 0) {
            $this->addStockInRow();
        }
    }
    
    // Stock Out Row Management
    public function addStockOutRow()
    {
        $this->stock_out_items[] = ['item_id' => '', 'quantity' => ''];
    }
    
    public function removeStockOutRow($index)
    {
        unset($this->stock_out_items[$index]);
        $this->stock_out_items = array_values($this->stock_out_items);
        if (count($this->stock_out_items) === 0) {
            $this->addStockOutRow();
        }
    }
    
    // Stock Usage Row Management
    public function addStockUsageRow()
    {
        $this->stock_usage_items[] = ['item_id' => '', 'quantity' => 1];
    }
    
    public function removeStockUsageRow($index)
    {
        unset($this->stock_usage_items[$index]);
        $this->stock_usage_items = array_values($this->stock_usage_items);
        if (count($this->stock_usage_items) === 0) {
            $this->addStockUsageRow();
        }
    }

    // Get available items for Stock In row, excluding items selected in other rows
    public function getAvailableItemsForStockIn($currentIndex, $allItems)
    {
        $selectedItems = collect($this->stock_in_items)
            ->pluck('item_id')
            ->filter()
            ->reject(function($itemId, $index) use ($currentIndex) {
                return $index == $currentIndex;
            })
            ->toArray();
        
        return $allItems->reject(function($item) use ($selectedItems) {
            return in_array($item->item_id, $selectedItems);
        });
    }

    // Get available items for Stock Out row, excluding items selected in other rows
    public function getAvailableItemsForStockOut($currentIndex, $allItems)
    {
        $selectedItems = collect($this->stock_out_items)
            ->pluck('item_id')
            ->filter()
            ->reject(function($itemId, $index) use ($currentIndex) {
                return $index == $currentIndex;
            })
            ->toArray();
        
        return $allItems->reject(function($item) use ($selectedItems) {
            return in_array($item->item_id, $selectedItems);
        });
    }

    // Get available items for Stock Usage row, excluding items selected in other rows
    public function getAvailableItemsForStockUsage($currentIndex, $allItems)
    {
        $selectedItems = collect($this->stock_usage_items)
            ->pluck('item_id')
            ->filter()
            ->reject(function($itemId, $index) use ($currentIndex) {
                return $index == $currentIndex;
            })
            ->toArray();
        
        return $allItems->reject(function($item) use ($selectedItems) {
            return in_array($item->item_id, $selectedItems);
        });
    }

    public function setTab($tab)
    {
        $this->activeTab = $tab;
        $this->resetValidation();
    }

    public function addStock()
    {
        // Validate items array
        $this->validate([
            'stock_in_items.*.item_id' => 'required|exists:item,item_id',
            'stock_in_items.*.quantity' => 'required|integer|min:1',
            'stock_in_items.*.expiry_date' => 'nullable|date',
            'stock_in_supplier' => 'nullable|string|max:100',
            'stock_in_reference' => 'nullable|string|max:50',
            'stock_in_remarks' => 'nullable|string|max:500',
        ]);

        $itemsAdded = 0;
        foreach ($this->stock_in_items as $item) {
            if (!empty($item['item_id']) && !empty($item['quantity'])) {
                StockIn::create([
                    'item_id' => $item['item_id'],
                    'quantity' => $item['quantity'],
                    'performed_by' => auth()->user()->employee->employee_id ?? null,
                    'supplier' => $this->stock_in_supplier,
                    'reference_number' => $this->stock_in_reference,
                    'expiry_date' => $item['expiry_date'] ?? null,
                    'remarks' => $this->stock_in_remarks,
                    'datetime_added' => now(),
                ]);

                // Log activity
                $itemModel = Item::find($item['item_id']);
                $this->logActivity("Added {$item['quantity']} units of {$itemModel->label} to stock" . 
                    ($this->stock_in_supplier ? " from {$this->stock_in_supplier}" : ""));
                
                $itemsAdded++;
            }
        }

        // Clear cache
        cache()->forget('items_with_stock');

        $this->reset(['stock_in_items', 'stock_in_supplier', 'stock_in_reference', 'stock_in_remarks']);
        $this->addStockInRow();
        $this->resetPage('movement_page'); // Reset movement pagination to show new movements
        $this->flashMessage = $itemsAdded . ' item(s) added to stock successfully!';
        $this->dispatch('stock-updated');
    }

    public function removeStock()
    {
        // Validate items array
        $this->validate([
            'stock_out_items.*.item_id' => 'required|exists:item,item_id',
            'stock_out_items.*.quantity' => 'required|integer|min:1',
            'stock_out_reference' => 'nullable|string|max:50',
            'stock_out_remarks' => 'nullable|string|max:500',
        ]);

        $itemsRemoved = 0;
        $errors = [];
        
        foreach ($this->stock_out_items as $index => $item) {
            if (!empty($item['item_id']) && !empty($item['quantity'])) {
                // Check if item has sufficient stock
                $totalIn = StockIn::where('item_id', $item['item_id'])->sum('quantity');
                $totalOut = StockOut::where('item_id', $item['item_id'])->sum('quantity');
                $totalUsage = StockUsage::where('item_id', $item['item_id'])->sum('quantity');
                $currentStock = $totalIn - $totalOut - $totalUsage;

                if ($currentStock < $item['quantity']) {
                    $itemModel = Item::find($item['item_id']);
                    $this->addError('stock_out_items.'.$index.'.quantity', 'Insufficient stock for ' . $itemModel->label . '. Available: ' . number_format($currentStock));
                    $errors[] = true;
                    continue;
                }

                StockOut::create([
                    'item_id' => $item['item_id'],
                    'quantity' => $item['quantity'],
                    'performed_by' => auth()->user()->employee->employee_id ?? null,
                    'reference_number' => $this->stock_out_reference,
                    'remarks' => $this->stock_out_remarks,
                    'datetime_added' => now(),
                ]);

                // Log activity
                $itemModel = Item::find($item['item_id']);
                $this->logActivity("Removed {$item['quantity']} units of {$itemModel->label} from stock" . 
                    ($this->stock_out_remarks ? " - {$this->stock_out_remarks}" : ""));
                
                $itemsRemoved++;
            }
        }

        if (!empty($errors)) {
            return;
        }

        // Clear cache
        cache()->forget('items_with_stock');

        $this->reset(['stock_out_items', 'stock_out_reference', 'stock_out_remarks']);
        $this->addStockOutRow();
        $this->resetPage('movement_page'); // Reset movement pagination to show new movements
        $this->flashMessage = $itemsRemoved . ' item(s) removed from stock successfully!';
        $this->dispatch('stock-updated');
    }

    public function recordUsage()
    {
        // Validate items array
        $this->validate([
            'stock_usage_items.*.item_id' => 'required|exists:item,item_id',
            'stock_usage_items.*.quantity' => 'required|integer|min:1',
            'stock_usage_employee_id' => 'required|exists:employee,employee_id',
            'stock_usage_purpose' => 'required|string|max:30',
            'stock_usage_or_number' => 'nullable|string|max:50',
        ]);

        $itemsRecorded = 0;
        $errors = [];
        $employee = Employee::find($this->stock_usage_employee_id);
        
        foreach ($this->stock_usage_items as $index => $item) {
            if (!empty($item['item_id']) && !empty($item['quantity'])) {
                // Check if item has sufficient stock
                $totalIn = StockIn::where('item_id', $item['item_id'])->sum('quantity');
                $totalOut = StockOut::where('item_id', $item['item_id'])->sum('quantity');
                $totalUsage = StockUsage::where('item_id', $item['item_id'])->sum('quantity');
                $currentStock = $totalIn - $totalOut - $totalUsage;

                if ($currentStock < $item['quantity']) {
                    $itemModel = Item::find($item['item_id']);
                    $this->addError('stock_usage_items.'.$index.'.quantity', 'Insufficient stock for ' . $itemModel->label . '. Available: ' . number_format($currentStock));
                    $errors[] = true;
                    continue;
                }

                StockUsage::create([
                    'item_id' => $item['item_id'],
                    'quantity' => $item['quantity'],
                    'employee_id' => $this->stock_usage_employee_id,
                    'firstname' => $employee->firstname,
                    'middlename' => $employee->middlename,
                    'lastname' => $employee->lastname,
                    'purpose' => $this->stock_usage_purpose,
                    'datetime_added' => now(),
                    'or_number' => $this->stock_usage_or_number ?? null,
                ]);

                // Log activity
                $itemModel = Item::find($item['item_id']);
                $this->logActivity("Recorded usage of {$item['quantity']} units of {$itemModel->label} for {$this->stock_usage_purpose}");
                
                $itemsRecorded++;
            }
        }

        if (!empty($errors)) {
            return;
        }

        // Clear cache
        cache()->forget('items_with_stock');

        $this->reset(['stock_usage_items', 'stock_usage_employee_id', 'stock_usage_purpose', 'stock_usage_or_number']);
        $this->addStockUsageRow();
        $this->resetPage('movement_page'); // Reset movement pagination to show new movements
        $this->flashMessage = $itemsRecorded . ' item(s) usage recorded successfully!';
        $this->dispatch('stock-updated');
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
            ->values();

        // Use Livewire pagination for movements
        $currentPage = request()->get('movement_page', 1);
        $paginatedMovements = new \Illuminate\Pagination\LengthAwarePaginator(
            $movements->forPage($currentPage, $this->movementPerPage),
            $movements->count(),
            $this->movementPerPage,
            $currentPage,
            [
                'path' => request()->url(),
                'query' => request()->query(),
                'pageName' => 'movement_page'
            ]
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
            'items' => Item::active()->select('item_id', 'label')->orderBy('label')->get(),
            'itemsWithStock' => $itemsWithStock,
            'employees' => Employee::active()->select('employee_id', 'firstname', 'lastname')->orderBy('firstname')->get(),
            'sections' => Section::active()->select('section_id', 'label')->orderBy('label')->get(),
            'inventory' => $paginatedInventory,
            'movements' => $paginatedMovements,
        ];
    }
}; ?>

<div class="min-h-screen bg-gray-50 max-w-8xl mx-auto px-4 sm:px-4 lg:px-6 py-8" x-data="{ 
    showToast: false,
    toastTimeout: null,
    showToastMessage() {
        this.showToast = true;
        if (this.toastTimeout) {
            clearTimeout(this.toastTimeout);
        }
        this.toastTimeout = setTimeout(() => {
            this.showToast = false;
        }, 3000);
    }
}" x-init="$wire.on('stock-updated', () => showToastMessage())">
    <div class="max-w-8xl  ">
        <!-- Success Message -->
        @if($flashMessage)
            <div class="mb-6 bg-white border-l-4 border-green-500 shadow-sm rounded-lg p-4 flex items-center justify-between" x-show="showToast" x-transition>
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
                            class="group inline-flex items-center py-4 px-1 border-b-2 font-medium text-sm transition-all {{ $activeTab === 'stock_in' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }}">
                        <svg class="mr-2 h-5 w-5 {{ $activeTab === 'stock_in' ? 'text-blue-500' : 'text-gray-400 group-hover:text-gray-500' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 11l5-5m0 0l5 5m-5-5v12"/>
                        </svg>
                        Stock In
                    </button>
                    <button wire:click="setTab('stock_out')" 
                            class="group inline-flex items-center py-4 px-1 border-b-2 font-medium text-sm transition-all {{ $activeTab === 'stock_out' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }}">
                        <svg class="mr-2 h-5 w-5 {{ $activeTab === 'stock_out' ? 'text-blue-500' : 'text-gray-400 group-hover:text-gray-500' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
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
                    <!-- Multiple Items Section -->
                    <div class="space-y-4">
                        <div class="flex items-center justify-between">
                            <label class="text-sm font-semibold text-gray-700">Items to Add</label>
                            <button type="button" wire:click="addStockInRow" class="px-3 py-1.5 bg-blue-100 hover:bg-blue-200 text-blue-700 text-sm font-medium rounded-lg transition-all flex items-center">
                                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                                </svg>
                                Add Item
                            </button>
                        </div>
                        
                        @foreach($stock_in_items as $index => $item)
                        <div class="bg-gray-50 rounded-lg p-4 border-2 border-gray-200">
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                <div class="space-y-2">
                                    <label class="text-sm font-medium text-gray-700">Item <span class="text-blue-500">*</span></label>
                                    <div x-data="{
                                        open: false,
                                        search: '',
                                        selectedLabel: '',
                                        items: @js($this->getAvailableItemsForStockIn($index, $items)->map(fn($i) => ['id' => $i->item_id, 'label' => $i->label])->values()),
                                        get filtered() {
                                            if (!this.search) return this.items;
                                            return this.items.filter(i => i.label.toLowerCase().includes(this.search.toLowerCase()));
                                        },
                                        select(item) {
                                            this.selectedLabel = item.label;
                                            this.search = '';
                                            this.open = false;
                                            $wire.set('stock_in_items.{{ $index }}.item_id', item.id);
                                        },
                                        clear() {
                                            this.selectedLabel = '';
                                            this.search = '';
                                            $wire.set('stock_in_items.{{ $index }}.item_id', '');
                                        },
                                        init() {
                                            let currentId = $wire.get('stock_in_items.{{ $index }}.item_id');
                                            if (currentId) {
                                                let found = this.items.find(i => i.id == currentId);
                                                if (found) this.selectedLabel = found.label;
                                            }
                                        }
                                    }" class="relative" @click.away="open = false">
                                        <div @click="open = !open" class="w-full px-4 py-2.5 bg-white border border-gray-300 rounded-lg cursor-pointer flex items-center justify-between hover:border-blue-400 transition-all" :class="open ? 'ring-2 ring-blue-500 border-transparent' : ''">
                                            <span x-show="selectedLabel" x-text="selectedLabel" class="text-gray-900 truncate"></span>
                                            <span x-show="!selectedLabel" class="text-gray-400">Select Item</span>
                                            <div class="flex items-center space-x-1">
                                                <button x-show="selectedLabel" @click.stop="clear()" type="button" class="text-gray-400 hover:text-blue-500">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                                </button>
                                                <svg class="w-4 h-4 text-gray-400 transition-transform" :class="open ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                                            </div>
                                        </div>
                                        <div x-show="open" x-transition.opacity class="absolute z-50 mt-1 w-full bg-white border border-gray-200 rounded-lg shadow-lg overflow-hidden">
                                            <div class="p-2 border-b border-gray-100">
                                                <input x-ref="searchInput" x-model="search" @keydown.escape="open = false" type="text" placeholder="Search items..." class="w-full px-3 py-2 text-sm border border-gray-200 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-transparent" @click.stop>
                                            </div>
                                            <ul class="max-h-48 overflow-y-auto">
                                                <template x-for="item in filtered" :key="item.id">
                                                    <li @click="select(item)" class="px-4 py-2.5 text-sm cursor-pointer hover:bg-blue-50 text-gray-700 hover:text-blue-700 transition-colors" x-text="item.label"></li>
                                                </template>
                                                <li x-show="filtered.length === 0" class="px-4 py-3 text-sm text-gray-400 text-center">No items found</li>
                                            </ul>
                                        </div>
                                    </div>
                                    @error('stock_in_items.'.$index.'.item_id') <span class="text-xs text-blue-600">{{ $message }}</span> @enderror
                                </div>
                                <div class="space-y-2">
                                    <label class="text-sm font-medium text-gray-700">Quantity <span class="text-blue-500">*</span></label>
                                    <input type="number" wire:model="stock_in_items.{{ $index }}.quantity" min="1" placeholder="0" class="w-full px-4 py-2.5 bg-white border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all">
                                    @error('stock_in_items.'.$index.'.quantity') <span class="text-xs text-blue-600">{{ $message }}</span> @enderror
                                </div>
                                <div class="space-y-2">
                                    <label class="text-sm font-medium text-gray-700">Expiry Date</label>
                                    <div class="flex gap-2">
                                        <input type="date" wire:model="stock_in_items.{{ $index }}.expiry_date" class="flex-1 px-4 py-2.5 bg-white border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all">
                                        @if(count($stock_in_items) > 1)
                                        <button type="button" wire:click="removeStockInRow({{ $index }})" class="px-3 py-2.5 bg-blue-100 hover:bg-blue-200 text-blue-700 rounded-lg transition-all">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 12H4"/>
                                            </svg>
                                        </button>
                                        @endif
                                    </div>
                                    @error('stock_in_items.'.$index.'.expiry_date') <span class="text-xs text-blue-600">{{ $message }}</span> @enderror
                                </div>
                            </div>
                        </div>
                        @endforeach
                    </div>
                    
                    <!-- Common Fields -->
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 pt-4 border-t border-gray-200">
                        <div class="space-y-2">
                            <label class="text-sm font-medium text-gray-700">Supplier</label>
                            <input type="text" wire:model="stock_in_supplier" placeholder="Supplier name" class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all">
                            @error('stock_in_supplier') <span class="text-xs text-blue-600">{{ $message }}</span> @enderror
                        </div>
                        <div class="space-y-2">
                            <label class="text-sm font-medium text-gray-700">Reference No.</label>
                            <input type="text" wire:model="stock_in_reference" placeholder="Invoice/PO number" class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all">
                            @error('stock_in_reference') <span class="text-xs text-blue-600">{{ $message }}</span> @enderror
                        </div>
                        <div class="space-y-2">
                            <label class="text-sm font-medium text-gray-700">Remarks</label>
                            <input type="text" wire:model="stock_in_remarks" placeholder="Optional notes" class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all">
                            @error('stock_in_remarks') <span class="text-xs text-blue-600">{{ $message }}</span> @enderror
                        </div>
                    </div>
                    
                    <div class="flex justify-end pt-4 border-t border-gray-100">
                        <button type="submit" class="px-8 py-3 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-lg transition-all shadow-sm hover:shadow flex items-center">
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
                    <!-- Multiple Items Section -->
                    <div class="space-y-4">
                        <div class="flex items-center justify-between">
                            <label class="text-sm font-semibold text-gray-700">Items to Remove</label>
                            <button type="button" wire:click="addStockOutRow" class="px-3 py-1.5 bg-blue-100 hover:bg-blue-200 text-blue-700 text-sm font-medium rounded-lg transition-all flex items-center">
                                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                                </svg>
                                Add Item
                            </button>
                        </div>
                        
                        @foreach($stock_out_items as $index => $item)
                        <div class="bg-gray-50 rounded-lg p-4 border-2 border-gray-200">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div class="space-y-2">
                                    <label class="text-sm font-medium text-gray-700">Item <span class="text-blue-500">*</span></label>
                                    <div x-data="{
                                        open: false,
                                        search: '',
                                        selectedLabel: '',
                                        items: @js($this->getAvailableItemsForStockOut($index, $itemsWithStock)->map(fn($i) => ['id' => $i->item_id, 'label' => $i->label, 'stock' => number_format($i->current_stock)])->values()),
                                        get filtered() {
                                            if (!this.search) return this.items;
                                            return this.items.filter(i => i.label.toLowerCase().includes(this.search.toLowerCase()));
                                        },
                                        select(item) {
                                            this.selectedLabel = item.label + ' (Available: ' + item.stock + ')';
                                            this.search = '';
                                            this.open = false;
                                            $wire.set('stock_out_items.{{ $index }}.item_id', item.id);
                                        },
                                        clear() {
                                            this.selectedLabel = '';
                                            this.search = '';
                                            $wire.set('stock_out_items.{{ $index }}.item_id', '');
                                        },
                                        init() {
                                            let currentId = $wire.get('stock_out_items.{{ $index }}.item_id');
                                            if (currentId) {
                                                let found = this.items.find(i => i.id == currentId);
                                                if (found) this.selectedLabel = found.label + ' (Available: ' + found.stock + ')';
                                            }
                                        }
                                    }" class="relative" @click.away="open = false">
                                        <div @click="open = !open" class="w-full px-4 py-2.5 bg-white border border-gray-300 rounded-lg cursor-pointer flex items-center justify-between hover:border-blue-400 transition-all" :class="open ? 'ring-2 ring-blue-500 border-transparent' : ''">
                                            <span x-show="selectedLabel" x-text="selectedLabel" class="text-gray-900 truncate"></span>
                                            <span x-show="!selectedLabel" class="text-gray-400">Select Item</span>
                                            <div class="flex items-center space-x-1">
                                                <button x-show="selectedLabel" @click.stop="clear()" type="button" class="text-gray-400 hover:text-blue-500">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                                </button>
                                                <svg class="w-4 h-4 text-gray-400 transition-transform" :class="open ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                                            </div>
                                        </div>
                                        <div x-show="open" x-transition.opacity class="absolute z-50 mt-1 w-full bg-white border border-gray-200 rounded-lg shadow-lg overflow-hidden">
                                            <div class="p-2 border-b border-gray-100">
                                                <input x-ref="searchInput" x-model="search" @keydown.escape="open = false" type="text" placeholder="Search items..." class="w-full px-3 py-2 text-sm border border-gray-200 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-transparent" @click.stop>
                                            </div>
                                            <ul class="max-h-48 overflow-y-auto">
                                                <template x-for="item in filtered" :key="item.id">
                                                    <li @click="select(item)" class="px-4 py-2.5 text-sm cursor-pointer hover:bg-blue-50 text-gray-700 hover:text-blue-700 transition-colors">
                                                        <span x-text="item.label"></span>
                                                        <span class="text-xs text-gray-400 ml-1" x-text="'(Available: ' + item.stock + ')'"></span>
                                                    </li>
                                                </template>
                                                <li x-show="filtered.length === 0" class="px-4 py-3 text-sm text-gray-400 text-center">No items found</li>
                                            </ul>
                                        </div>
                                    </div>
                                    @error('stock_out_items.'.$index.'.item_id') <span class="text-xs text-blue-600">{{ $message }}</span> @enderror
                                </div>
                                <div class="space-y-2">
                                    <label class="text-sm font-medium text-gray-700">Quantity <span class="text-blue-500">*</span></label>
                                    <div class="flex gap-2">
                                        <input type="number" wire:model="stock_out_items.{{ $index }}.quantity" min="1" placeholder="0" class="flex-1 px-4 py-2.5 bg-white border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all">
                                        @if(count($stock_out_items) > 1)
                                        <button type="button" wire:click="removeStockOutRow({{ $index }})" class="px-3 py-2.5 bg-blue-100 hover:bg-blue-200 text-blue-700 rounded-lg transition-all">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 12H4"/>
                                            </svg>
                                        </button>
                                        @endif
                                    </div>
                                    @error('stock_out_items.'.$index.'.quantity') <span class="text-xs text-blue-600">{{ $message }}</span> @enderror
                                </div>
                            </div>
                        </div>
                        @endforeach
                    </div>
                    
                    <!-- Common Fields -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 pt-4 border-t border-gray-200">
                        <div class="space-y-2">
                            <label class="text-sm font-medium text-gray-700">Reference No.</label>
                            <input type="text" wire:model="stock_out_reference" placeholder="Requisition number" class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all">
                            @error('stock_out_reference') <span class="text-xs text-blue-600">{{ $message }}</span> @enderror
                        </div>
                        <div class="space-y-2">
                            <label class="text-sm font-medium text-gray-700">Remarks</label>
                            <input type="text" wire:model="stock_out_remarks" placeholder="Reason for removal" class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all">
                            @error('stock_out_remarks') <span class="text-xs text-blue-600">{{ $message }}</span> @enderror
                        </div>
                    </div>
                    
                    <div class="flex justify-end pt-4 border-t border-gray-100">
                        <button type="submit" class="px-8 py-3 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-lg transition-all shadow-sm hover:shadow flex items-center">
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
                    <!-- Multiple Items Section -->
                    <div class="space-y-4">
                        <div class="flex items-center justify-between">
                            <label class="text-sm font-semibold text-gray-700">Items to Record</label>
                            <button type="button" wire:click="addStockUsageRow" class="px-3 py-1.5 bg-blue-100 hover:bg-blue-200 text-blue-700 text-sm font-medium rounded-lg transition-all flex items-center">
                                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                                </svg>
                                Add Item
                            </button>
                        </div>
                        
                        @foreach($stock_usage_items as $index => $item)
                        <div class="bg-gray-50 rounded-lg p-4 border-2 border-gray-200">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div class="space-y-2">
                                    <label class="text-sm font-medium text-gray-700">Item <span class="text-blue-500">*</span></label>
                                    <div x-data="{
                                        open: false,
                                        search: '',
                                        selectedLabel: '',
                                        items: @js($this->getAvailableItemsForStockUsage($index, $itemsWithStock)->map(fn($i) => ['id' => $i->item_id, 'label' => $i->label, 'stock' => number_format($i->current_stock)])->values()),
                                        get filtered() {
                                            if (!this.search) return this.items;
                                            return this.items.filter(i => i.label.toLowerCase().includes(this.search.toLowerCase()));
                                        },
                                        select(item) {
                                            this.selectedLabel = item.label + ' (Available: ' + item.stock + ')';
                                            this.search = '';
                                            this.open = false;
                                            $wire.set('stock_usage_items.{{ $index }}.item_id', item.id);
                                        },
                                        clear() {
                                            this.selectedLabel = '';
                                            this.search = '';
                                            $wire.set('stock_usage_items.{{ $index }}.item_id', '');
                                        },
                                        init() {
                                            let currentId = $wire.get('stock_usage_items.{{ $index }}.item_id');
                                            if (currentId) {
                                                let found = this.items.find(i => i.id == currentId);
                                                if (found) this.selectedLabel = found.label + ' (Available: ' + found.stock + ')';
                                            }
                                        }
                                    }" class="relative" @click.away="open = false">
                                        <div @click="open = !open" class="w-full px-4 py-2.5 bg-white border border-gray-300 rounded-lg cursor-pointer flex items-center justify-between hover:border-blue-400 transition-all" :class="open ? 'ring-2 ring-blue-500 border-transparent' : ''">
                                            <span x-show="selectedLabel" x-text="selectedLabel" class="text-gray-900 truncate"></span>
                                            <span x-show="!selectedLabel" class="text-gray-400">Select Item</span>
                                            <div class="flex items-center space-x-1">
                                                <button x-show="selectedLabel" @click.stop="clear()" type="button" class="text-gray-400 hover:text-blue-500">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                                </button>
                                                <svg class="w-4 h-4 text-gray-400 transition-transform" :class="open ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                                            </div>
                                        </div>
                                        <div x-show="open" x-transition.opacity class="absolute z-50 mt-1 w-full bg-white border border-gray-200 rounded-lg shadow-lg overflow-hidden">
                                            <div class="p-2 border-b border-gray-100">
                                                <input x-ref="searchInput" x-model="search" @keydown.escape="open = false" type="text" placeholder="Search items..." class="w-full px-3 py-2 text-sm border border-gray-200 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-transparent" @click.stop>
                                            </div>
                                            <ul class="max-h-48 overflow-y-auto">
                                                <template x-for="item in filtered" :key="item.id">
                                                    <li @click="select(item)" class="px-4 py-2.5 text-sm cursor-pointer hover:bg-blue-50 text-gray-700 hover:text-blue-700 transition-colors">
                                                        <span x-text="item.label"></span>
                                                        <span class="text-xs text-gray-400 ml-1" x-text="'(Available: ' + item.stock + ')'"></span>
                                                    </li>
                                                </template>
                                                <li x-show="filtered.length === 0" class="px-4 py-3 text-sm text-gray-400 text-center">No items found</li>
                                            </ul>
                                        </div>
                                    </div>
                                    @error('stock_usage_items.'.$index.'.item_id') <span class="text-xs text-blue-600">{{ $message }}</span> @enderror
                                </div>
                                <div class="space-y-2">
                                    <label class="text-sm font-medium text-gray-700">Quantity <span class="text-blue-500">*</span></label>
                                    <div class="flex gap-2">
                                        <input type="number" wire:model="stock_usage_items.{{ $index }}.quantity" min="1" placeholder="1" class="flex-1 px-4 py-2.5 bg-white border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all">
                                        @if(count($stock_usage_items) > 1)
                                        <button type="button" wire:click="removeStockUsageRow({{ $index }})" class="px-3 py-2.5 bg-blue-100 hover:bg-blue-200 text-blue-700 rounded-lg transition-all">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 12H4"/>
                                            </svg>
                                        </button>
                                        @endif
                                    </div>
                                    @error('stock_usage_items.'.$index.'.quantity') <span class="text-xs text-blue-600">{{ $message }}</span> @enderror
                                </div>
                            </div>
                        </div>
                        @endforeach
                    </div>
                    
                    <!-- Common Fields -->
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 pt-4 border-t border-gray-200">
                        <div class="space-y-2">
                            <label class="text-sm font-medium text-gray-700">Employee <span class="text-blue-500">*</span></label>
                            <div x-data="{
                                open: false,
                                search: '',
                                selectedLabel: '',
                                anchorRect: null,
                                items: @js($employees->map(fn($e) => ['id' => $e->employee_id, 'label' => $e->firstname . ' ' . $e->lastname])->values()),
                                get filtered() {
                                    return this.items.filter(i => i.label.toLowerCase().includes(this.search.toLowerCase()));
                                },
                                select(item) {
                                    this.selectedLabel = item.label;
                                    this.search = '';
                                    this.open = false;
                                    $wire.set('stock_usage_employee_id', item.id);
                                },
                                clear() {
                                    this.selectedLabel = '';
                                    this.open = false;
                                    $wire.set('stock_usage_employee_id', '');
                                },
                                toggle() {
                                    this.open = !this.open;
                                    if (this.open) {
                                        this.$nextTick(() => {
                                            const rect = this.$refs.trigger.getBoundingClientRect();
                                            this.anchorRect = { top: rect.bottom + window.scrollY, left: rect.left + window.scrollX, width: rect.width };
                                        });
                                    }
                                },
                                init() {
                                    this.$watch('$wire.stock_usage_employee_id', val => {
                                        if (!val) { this.selectedLabel = ''; return; }
                                        const found = this.items.find(i => i.id == val);
                                        if (found) this.selectedLabel = found.label;
                                    });
                                }
                            }" class="relative" @click.away="open = false">
                                <div x-ref="trigger" @click="toggle()" class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-lg cursor-pointer flex items-center justify-between hover:border-blue-400 transition-all" :class="open ? 'ring-2 ring-blue-500 border-transparent' : ''">
                                    <span x-text="selectedLabel || 'Select Employee'" :class="selectedLabel ? 'text-gray-900' : 'text-gray-400'" class="text-sm truncate"></span>
                                    <div class="flex items-center gap-1 ml-2 shrink-0">
                                        <button type="button" x-show="selectedLabel" @click.stop="clear()" class="text-gray-400 hover:text-gray-600">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                        </button>
                                        <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                                    </div>
                                </div>
                                <template x-teleport="body">
                                    <div x-show="open" x-transition.opacity
                                         :style="anchorRect ? `position:absolute;top:${anchorRect.top+4}px;left:${anchorRect.left}px;width:${anchorRect.width}px;z-index:9999` : ''"
                                         class="bg-white border border-gray-200 rounded-lg shadow-lg overflow-hidden">
                                        <div class="p-2 border-b border-gray-100">
                                            <input type="text" x-model="search" @click.stop placeholder="Search employee..." class="w-full px-3 py-1.5 text-sm border border-gray-200 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-transparent outline-none" x-ref="searchInput" @keydown.escape="open = false">
                                        </div>
                                        <ul class="max-h-48 overflow-y-auto py-1">
                                            <template x-for="item in filtered" :key="item.id">
                                                <li @click="select(item)" class="px-4 py-2 text-sm text-gray-700 hover:bg-blue-50 hover:text-blue-700 cursor-pointer truncate" x-text="item.label"></li>
                                            </template>
                                            <li x-show="filtered.length === 0" class="px-4 py-2 text-sm text-gray-400 text-center">No results</li>
                                        </ul>
                                    </div>
                                </template>
                            </div>
                            @error('stock_usage_employee_id') <span class="text-xs text-blue-600">{{ $message }}</span> @enderror
                        </div>
                        <div class="space-y-2">
                            <label class="text-sm font-medium text-gray-700">Purpose <span class="text-blue-500">*</span></label>
                            <input type="text" wire:model="stock_usage_purpose" placeholder="Purpose of use" class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all">
                            @error('stock_usage_purpose') <span class="text-xs text-blue-600">{{ $message }}</span> @enderror
                        </div>
                        <div class="space-y-2">
                            <label class="text-sm font-medium text-gray-700">OR Number</label>
                            <input type="text" wire:model="stock_usage_or_number" placeholder="Official receipt number" class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all">
                            @error('stock_usage_or_number') <span class="text-xs text-blue-600">{{ $message }}</span> @enderror
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
                            <tr class="hover:bg-gray-50 transition-colors">
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
                                    <span class="text-sm text-gray-700">{{ number_format($item->total_in) }}</span>
                                </td>
                                <td class="px-3 py-3 text-center">
                                    <span class="text-sm text-gray-700">{{ number_format($item->total_out + $item->total_usage) }}</span>
                                </td>
                                <td class="px-3 py-3 text-center">
                                    <span class="text-sm font-bold text-gray-900">{{ number_format($currentStock) }}</span>
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
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden mt-8" x-data="{ 
            showSidebar: false, 
            selected: null,
            openDetails(movement) {
                this.selected = movement;
                this.showSidebar = true;
            },
            closeSidebar() {
                this.showSidebar = false;
                setTimeout(() => this.selected = null, 300);
            }
        }">
            <div class="px-8 py-6 border-b border-gray-200">
                <h2 class="text-xl font-bold text-gray-900">Stock Movement History</h2>
                <p class="mt-1 text-sm text-gray-500">Recent stock transactions and activities — click a row for details</p>
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
                            <tr class="hover:bg-gray-50 cursor-pointer transition-colors"
                                @click="openDetails({
                                    date: '{{ $movement->datetime_added->format('M d, Y') }}',
                                    time: '{{ $movement->datetime_added->format('h:i A') }}',
                                    type: '{{ $movement->type }}',
                                    item: '{{ addslashes($movement->item->label ?? 'N/A') }}',
                                    section: '{{ addslashes($movement->item->section->label ?? 'N/A') }}',
                                    quantity: '{{ number_format($movement->quantity) }}',
                                    reference: '{{ addslashes($movement->reference ?? $movement->reference_number ?? '—') }}',
                                    supplier: '{{ addslashes($movement->supplier ?? '—') }}',
                                    performedBy: '{{ $movement->type === 'USAGE' && $movement->employee ? addslashes($movement->employee->firstname . ' ' . $movement->employee->lastname) : ($movement->performedByEmployee ? addslashes($movement->performedByEmployee->firstname . ' ' . $movement->performedByEmployee->lastname) : 'System') }}',
                                    remarks: '{{ addslashes($movement->remarks ?? '—') }}'
                                })">
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
                {{ $movements->appends(request()->query())->links() }}
            </div>

            <!-- Detail Sidebar -->
            <div x-show="showSidebar" x-cloak class="fixed inset-0 z-50 overflow-hidden">
                <!-- Backdrop -->
                <div x-show="showSidebar" 
                     x-transition:enter="transition-opacity ease-out duration-300"
                     x-transition:enter-start="opacity-0"
                     x-transition:enter-end="opacity-100"
                     x-transition:leave="transition-opacity ease-in duration-200"
                     x-transition:leave-start="opacity-100"
                     x-transition:leave-end="opacity-0"
                     @click="closeSidebar()" 
                     class="fixed inset-0 bg-black/40"></div>

                <!-- Sidebar Panel -->
                <div class="fixed inset-y-0 right-0 flex max-w-full">
                    <div x-show="showSidebar"
                         x-transition:enter="transform transition ease-out duration-300"
                         x-transition:enter-start="translate-x-full"
                         x-transition:enter-end="translate-x-0"
                         x-transition:leave="transform transition ease-in duration-200"
                         x-transition:leave-start="translate-x-0"
                         x-transition:leave-end="translate-x-full"
                         class="w-screen max-w-md">
                        <div class="flex h-full flex-col bg-white shadow-2xl">
                            <!-- Sidebar Header -->
                            <div class="px-6 py-5 border-b border-gray-200">
                                <div class="flex items-center justify-between">
                                    <h3 class="text-lg font-semibold text-gray-900">Movement Details</h3>
                                    <button @click="closeSidebar()" class="rounded-lg p-1.5 text-gray-400 hover:text-gray-600 hover:bg-gray-100 transition-colors">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                        </svg>
                                    </button>
                                </div>
                            </div>

                            <!-- Sidebar Body -->
                            <div class="flex-1 overflow-y-auto px-6 py-6" x-show="selected">
                                <!-- Type Badge -->
                                <div class="mb-6 flex items-center space-x-3">
                                    <template x-if="selected?.type === 'IN'">
                                        <span class="inline-flex items-center px-3 py-1.5 rounded-lg bg-emerald-100 text-emerald-700 text-sm font-semibold">
                                            <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 11l5-5m0 0l5 5m-5-5v12"/>
                                            </svg>
                                            STOCK IN
                                        </span>
                                    </template>
                                    <template x-if="selected?.type === 'OUT'">
                                        <span class="inline-flex items-center px-3 py-1.5 rounded-lg bg-rose-100 text-rose-700 text-sm font-semibold">
                                            <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 13l-5 5m0 0l-5-5m5 5V6"/>
                                            </svg>
                                            STOCK OUT
                                        </span>
                                    </template>
                                    <template x-if="selected?.type === 'USAGE'">
                                        <span class="inline-flex items-center px-3 py-1.5 rounded-lg bg-blue-100 text-blue-700 text-sm font-semibold">
                                            <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                                            </svg>
                                            USAGE
                                        </span>
                                    </template>
                                </div>

                                <!-- Details Grid -->
                                <div class="space-y-5">
                                    <!-- Item -->
                                    <div class="bg-gray-50 rounded-lg p-4">
                                        <p class="text-xs font-semibold text-gray-500 uppercase mb-1">Item</p>
                                        <p class="text-sm font-medium text-gray-900" x-text="selected?.item"></p>
                                        <p class="text-xs text-gray-500 mt-0.5" x-text="selected?.section"></p>
                                    </div>

                                    <div class="grid grid-cols-2 gap-4">
                                        <!-- Quantity -->
                                        <div class="bg-gray-50 rounded-lg p-4">
                                            <p class="text-xs font-semibold text-gray-500 uppercase mb-1">Quantity</p>
                                            <p class="text-sm font-medium" 
                                               :class="selected?.type === 'IN' ? 'text-emerald-700' : 'text-gray-900'"
                                               x-text="(selected?.type === 'IN' ? '+' : '-') + selected?.quantity"></p>
                                        </div>

                                        <!-- Date & Time -->
                                        <div class="bg-gray-50 rounded-lg p-4">
                                            <p class="text-xs font-semibold text-gray-500 uppercase mb-1">Date & Time</p>
                                            <p class="text-sm font-medium text-gray-900" x-text="selected?.date"></p>
                                            <p class="text-xs text-gray-500" x-text="selected?.time"></p>
                                        </div>
                                    </div>

                                    <!-- Supplier (IN only) -->
                                    <template x-if="selected?.type === 'IN'">
                                        <div class="bg-gray-50 rounded-lg p-4">
                                            <p class="text-xs font-semibold text-gray-500 uppercase mb-1">Supplier</p>
                                            <p class="text-sm font-medium text-gray-900" x-text="selected?.supplier"></p>
                                        </div>
                                    </template>

                                    <!-- Reference -->
                                    <div class="bg-gray-50 rounded-lg p-4">
                                        <p class="text-xs font-semibold text-gray-500 uppercase mb-1">Reference</p>
                                        <p class="text-sm font-medium text-gray-900" x-text="selected?.reference"></p>
                                    </div>

                                    <!-- Performed By -->
                                    <div class="bg-gray-50 rounded-lg p-4">
                                        <p class="text-xs font-semibold text-gray-500 uppercase mb-1">Performed By</p>
                                        <p class="text-sm font-medium text-gray-900" x-text="selected?.performedBy"></p>
                                    </div>

                                    <!-- Remarks -->
                                    <div class="bg-gray-50 rounded-lg p-4">
                                        <p class="text-xs font-semibold text-gray-500 uppercase mb-1">Remarks</p>
                                        <p class="text-sm text-gray-700 whitespace-pre-wrap" x-text="selected?.remarks"></p>
                                    </div>
                                </div>
                            </div>

                            <!-- Sidebar Footer -->
                            <div class="px-6 py-4 border-t border-gray-200 bg-gray-50">
                                <button @click="closeSidebar()" class="w-full px-4 py-2.5 bg-gray-200 hover:bg-gray-300 text-gray-700 text-sm font-medium rounded-lg transition-colors">
                                    Close
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
