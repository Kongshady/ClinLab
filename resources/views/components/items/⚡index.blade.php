<?php

use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Validate;
use App\Models\Item;
use App\Models\Section;

new class extends Component
{
    use WithPagination;

    #[Validate('required|exists:section,section_id')]
    public $section_id = '';

    #[Validate('required|string|max:255')]
    public $label = '';

    #[Validate('required|string|max:50')]
    public $item_type = '';

    public $search = '';
    public $flashMessage = '';
    public $perPage = 'all';

    // Bulk delete
    public $selectedItems = [];
    public $selectAll = false;

    // Edit Modal Properties
    public $showEditModal = false;
    public $editItemId = '';
    public $editSectionId = '';
    public $editLabel = '';
    public $editItemType = '';

    public function mount()
    {
        if (session()->has('success')) {
            $this->flashMessage = session('success');
        }
    }

    public function save()
    {
        $this->validate();

        // Find or create item_type
        $itemType = \DB::table('item_type')
            ->where('label', $this->item_type)
            ->first();

        if (!$itemType) {
            $itemTypeId = \DB::table('item_type')->insertGetId([
                'label' => $this->item_type
            ]);
        } else {
            $itemTypeId = $itemType->item_type_id;
        }

        Item::create([
            'section_id' => $this->section_id,
            'label' => $this->label,
            'item_type_id' => $itemTypeId,
            'status_code' => 1,
            'is_deleted' => 0,
        ]);
        $this->reset(['section_id', 'label', 'item_type']);
        $this->flashMessage = 'Item added successfully!';
        
        $this->resetPage();
    }

    public function openEditModal($itemId)
    {
        $item = Item::findOrFail($itemId);
        
        // Get item type label from join
        $itemTypeData = \DB::table('item_type')
            ->where('item_type_id', $item->item_type_id)
            ->first();
        
        $this->editItemId = $itemId;
        $this->editSectionId = $item->section_id;
        $this->editLabel = $item->label;
        $this->editItemType = $itemTypeData->label ?? '';
        $this->showEditModal = true;
    }

    public function closeEditModal()
    {
        $this->showEditModal = false;
        $this->reset(['editItemId', 'editSectionId', 'editLabel', 'editItemType']);
    }

    public function updateItem()
    {
        $validated = $this->validate([
            'editSectionId' => 'required|exists:section,section_id',
            'editLabel' => 'required|string|max:255',
            'editItemType' => 'required|string|max:50',
        ]);

        // Find or create item_type
        $itemType = \DB::table('item_type')
            ->where('label', $this->editItemType)
            ->first();

        if (!$itemType) {
            $itemTypeId = \DB::table('item_type')->insertGetId([
                'label' => $this->editItemType
            ]);
        } else {
            $itemTypeId = $itemType->item_type_id;
        }

        $item = Item::findOrFail($this->editItemId);
        $item->update([
            'section_id' => $this->editSectionId,
            'label' => $this->editLabel,
            'item_type_id' => $itemTypeId,
        ]);

        $this->flashMessage = 'Item updated successfully!';
        $this->closeEditModal();
    }

    public function updatedSelectAll($value)
    {
        if ($value) {
            $this->selectedItems = $this->getFilteredQuery()
                ->pluck('item.item_id')
                ->map(fn($id) => (string) $id)
                ->toArray();
        } else {
            $this->selectedItems = [];
        }
    }

    public function updatedSelectedItems()
    {
        $this->selectAll = false;
    }

    public function updatedSearch()
    {
        $this->selectedItems = [];
        $this->selectAll = false;
        $this->resetPage();
    }

    public function deleteSelected()
    {
        if (empty($this->selectedItems)) return;

        $count = 0;
        foreach ($this->selectedItems as $id) {
            $item = Item::find($id);
            if ($item) {
                $item->softDelete();
                $count++;
            }
        }

        $this->selectedItems = [];
        $this->selectAll = false;
        $this->flashMessage = $count . ' item(s) deleted successfully!';
        $this->resetPage();
    }

    private function getFilteredQuery()
    {
        return Item::active()
            ->with('section')
            ->leftJoin('item_type', 'item.item_type_id', '=', 'item_type.item_type_id')
            ->select('item.*', 'item_type.label as item_type_label')
            ->when($this->search, function ($query) {
                $query->where(function ($q) {
                    $q->where('item.label', 'like', '%' . $this->search . '%')
                      ->orWhere('item_type.label', 'like', '%' . $this->search . '%')
                      ->orWhereHas('section', function ($sq) {
                          $sq->where('label', 'like', '%' . $this->search . '%');
                      });
                });
            })
            ->orderBy('item.item_id', 'desc');
    }

    public function with(): array
    {
        $query = $this->getFilteredQuery();
        $items = $this->perPage === 'all' ? $query->get() : $query->paginate((int)$this->perPage);

        return [
            'items' => $items,
            'sections' => Section::active()->orderBy('label')->get()
        ];
    }
};
?>

<div class="p-6 space-y-6">
    @if($flashMessage)
        <div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-lg" role="alert">
            <span class="block sm:inline">{{ $flashMessage }}</span>
        </div>
    @endif

    <!-- Header -->
    <div class="flex items-center space-x-3 mb-6">
        <svg class="w-8 h-8 text-gray-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
        </svg>
        <h1 class="text-2xl font-bold text-gray-900">Items Management</h1>
    </div>

    <!-- Add New Item Form -->
    <div class="bg-white rounded-lg shadow-sm p-6">
        <h2 class="text-lg font-semibold text-gray-900 mb-6">Add New Item</h2>
        <form wire:submit.prevent="save">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Item Name <span class="text-red-500">*</span></label>
                    <input type="text" wire:model="label" placeholder="e.g., Test Tubes"
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-pink-500 focus:border-transparent">
                    @error('label') <span class="text-red-600 text-xs mt-1">{{ $message }}</span> @enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Item Type <span class="text-red-500">*</span></label>
                    <input type="text" wire:model="item_type" list="itemTypeList" placeholder="Select or type new item type"
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-pink-500 focus:border-transparent">
                    <datalist id="itemTypeList">
                        <option value="Chemical">
                        <option value="Consumable">
                        <option value="Equipment">
                        <option value="Glassware">
                        <option value="PPE">
                        <option value="Reagent">
                        <option value="Supply">
                    </datalist>
                    @error('item_type') <span class="text-red-600 text-xs mt-1">{{ $message }}</span> @enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Section <span class="text-red-500">*</span></label>
                    <select wire:model="section_id" 
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-pink-500 focus:border-transparent">
                        <option value="">Select Section</option>
                        @foreach($sections as $section)
                            <option value="{{ $section->section_id }}">{{ $section->label }}</option>
                        @endforeach
                    </select>
                    @error('section_id') <span class="text-red-600 text-xs mt-1">{{ $message }}</span> @enderror
                </div>
            </div>
            <div class="flex justify-end mt-6">
                <button type="submit" 
                        class="px-6 py-2 bg-pink-500 hover:bg-pink-600 text-white font-medium rounded-lg transition-colors">
                    Add Item
                </button>
            </div>
        </form>
    </div>

    <!-- Search -->
    <div class="bg-white rounded-lg shadow-sm p-6">
        <input type="text" wire:model.live.debounce.300ms="search" placeholder="Search by item name, type, or section..." 
               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-pink-500 focus:border-transparent">
    </div>

    <!-- Rows per page -->
    <div class="flex items-center space-x-3">
        <label class="text-sm font-medium text-gray-700">Rows per page:</label>
        <select wire:model.live="perPage" 
                class="px-3 py-1.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-pink-500 focus:border-transparent text-sm">
            <option value="10">10</option>
            <option value="25">25</option>
            <option value="50">50</option>
            <option value="100">100</option>
            <option value="all">All</option>
        </select>
    </div>

    <!-- Items List -->
    <div class="bg-white rounded-lg shadow-sm">
        <div class="p-6">
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-lg font-semibold text-gray-900">Items List</h2>
                @if(count($selectedItems) > 0)
                <button wire:click="deleteSelected" 
                        wire:confirm="Are you sure you want to delete {{ count($selectedItems) }} selected item(s)?"
                        class="inline-flex items-center px-4 py-2 bg-red-600 hover:bg-red-700 text-white text-sm font-medium rounded-lg transition-colors">
                    <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                    </svg>
                    Delete Selected ({{ count($selectedItems) }})
                </button>
                @endif
            </div>
            
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead>
                        <tr class="bg-gray-50">
                            <th class="px-4 py-3 w-10"> 
                                <input type="checkbox" wire:model.live="selectAll"
                                       class="rounded border-gray-300 text-pink-600 focus:ring-pink-500 w-4 h-4">
                            </th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Item Name</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Item Type</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Section</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        @forelse($items as $item)
                            <tr wire:key="item-{{ $item->item_id }}" 
                                wire:click="openEditModal({{ $item->item_id }})" 
                                class="hover:bg-gray-100 cursor-pointer transition-colors {{ in_array((string) $item->item_id, $selectedItems) ? 'bg-pink-50' : '' }}">
                                <td class="px-4 py-3" wire:click.stop>
                                    <input type="checkbox" wire:model.live="selectedItems" value="{{ $item->item_id }}"
                                           class="rounded border-gray-300 text-pink-600 focus:ring-pink-500 w-4 h-4">
                                </td>
                                <td class="px-4 py-3 text-sm text-gray-900 font-medium">{{ $item->label }}</td>
                                <td class="px-4 py-3 text-sm text-gray-700">{{ $item->item_type_label ?? 'N/A' }}</td>
                                <td class="px-4 py-3 text-sm text-gray-700">{{ $item->section->label ?? 'N/A' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-4 py-8 text-center text-gray-500">No items found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($perPage !== 'all' && method_exists($items, 'hasPages') && $items->hasPages())
            <div class="mt-6">
                {{ $items->links() }}
            </div>
            @endif
        </div>
    </div>

    <!-- Edit Item Modal -->
    @if($showEditModal)
    <div class="fixed inset-0 z-50 overflow-y-auto" style="background-color: rgba(0, 0, 0, 0.5);">
        <div class="flex items-center justify-center min-h-screen p-4">
            <div class="bg-white rounded-lg shadow-xl max-w-lg w-full">
                <!-- Modal Header -->
                <div class="px-6 py-4 border-b border-gray-200">
                    <div class="flex items-center justify-between">
                        <h3 class="text-xl font-semibold text-gray-900">
                            Edit Item
                        </h3>
                        <button type="button" wire:click="closeEditModal" class="text-gray-400 hover:text-gray-600">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </button>
                    </div>
                </div>

                <!-- Modal Body -->
                <form wire:submit.prevent="updateItem">
                    <div class="p-6 space-y-5">
                        <!-- Section -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                Section <span class="text-red-500">*</span>
                            </label>
                            <select wire:model="editSectionId" 
                                    class="w-full px-3 py-2.5 border border-gray-300 rounded-md focus:outline-none focus:ring-1 focus:ring-blue-500 focus:border-blue-500">
                                <option value="">Select Section</option>
                                @foreach($sections as $section)
                                    <option value="{{ $section->section_id }}">{{ $section->label }}</option>
                                @endforeach
                            </select>
                            @error('editSectionId') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
                        </div>

                        <!-- Item Name -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                Item Name <span class="text-red-500">*</span>
                            </label>
                            <input type="text" wire:model="editLabel" 
                                   class="w-full px-3 py-2.5 border border-gray-300 rounded-md focus:outline-none focus:ring-1 focus:ring-blue-500 focus:border-blue-500"
                                   placeholder="Enter item name">
                            @error('editLabel') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
                        </div>

                        <!-- Item Type -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                Item Type <span class="text-red-500">*</span>
                            </label>
                            <input type="text" wire:model="editItemType" 
                                   class="w-full px-3 py-2.5 border border-gray-300 rounded-md focus:outline-none focus:ring-1 focus:ring-blue-500 focus:border-blue-500"
                                   placeholder="Enter item type">
                            @error('editItemType') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
                        </div>
                    </div>

                    <!-- Modal Footer -->
                    <div class="px-6 py-4 bg-gray-50 border-t border-gray-200 flex justify-end space-x-3 rounded-b-lg">
                        <button type="button" wire:click="closeEditModal" 
                                class="px-5 py-2.5 border border-gray-300 rounded-md text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none">
                            Cancel
                        </button>
                        <button type="submit" 
                                class="px-5 py-2.5 bg-orange-500 text-white text-sm rounded-md font-medium hover:bg-orange-600 focus:outline-none">
                            Update Item
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    @endif
</div>

