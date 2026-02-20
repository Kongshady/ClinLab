<?php

use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Validate;
use App\Models\Item;
use App\Models\Section;
use App\Traits\LogsActivity;

new class extends Component
{
    use WithPagination, LogsActivity;

    #[Validate('required|exists:section,section_id')]
    public $section_id = '';

    #[Validate('required|string|max:255')]
    public $label = '';

    #[Validate('required|string|max:50')]
    public $item_type = '';

    public $search = '';
    public $flashMessage = '';
    public $perPage = 'all';

    // Item Type Combobox properties
    public $itemTypeQuery = '';
    public $showItemTypeDropdown = false;
    public $selectedItemType = '';

    // Edit Modal Combobox properties
    public $editItemTypeQuery = '';
    public $showEditItemTypeDropdown = false;

    // Bulk delete
    public $selectedItems = [];
    public $selectAll = false;

    // Edit Modal Properties
    public $showEditModal = false;
    public $editItemId = '';
    public $editSectionId = '';
    public $editLabel = '';
    public $editItemType = '';

    // Delete confirmation modal properties
    public $showDeleteModal = false;
    public $deleteMessage = '';
    public $deleteAction = '';
    public $itemsToDelete = [];

    public function mount()
    {
        if (session()->has('success')) {
            $this->flashMessage = session('success');
        }
    }

    public function save()
    {
        $this->validate();

        if (Item::active()->where('label', $this->label)->where('section_id', $this->section_id)->exists()) {
            $this->addError('label', 'An item with this name already exists in the selected section.');
            return;
        }

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
        $this->logActivity("Created item: {$this->label}");
        $this->flashMessage = 'Item added successfully!';
        $this->dispatch('item-saved');
        
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
        $this->editItemTypeQuery = $this->editItemType;
        $this->showEditModal = true;
    }

    public function closeEditModal()
    {
        $this->showEditModal = false;
        $this->reset(['editItemId', 'editSectionId', 'editLabel', 'editItemType', 'editItemTypeQuery']);
    }

    public function updateItem()
    {
        $validated = $this->validate([
            'editSectionId' => 'required|exists:section,section_id',
            'editLabel' => 'required|string|max:255',
            'editItemType' => 'required|string|max:50',
        ]);

        if (Item::active()->where('label', $this->editLabel)
            ->where('section_id', $this->editSectionId)
            ->where('item_id', '!=', $this->editItemId)
            ->exists()) {
            $this->addError('editLabel', 'An item with this name already exists in the selected section.');
            return;
        }

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

        $this->logActivity("Updated item ID {$this->editItemId}: {$this->editLabel}");
        $this->flashMessage = 'Item updated successfully!';
        $this->dispatch('item-saved');
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

    // Item Type Combobox Methods
    public function selectItemType($typeLabel)
    {
        $this->item_type = $typeLabel;
        $this->itemTypeQuery = $typeLabel;
        $this->showItemTypeDropdown = false;
    }

    public function updatedItemTypeQuery()
    {
        $this->showItemTypeDropdown = true;
        $this->item_type = $this->itemTypeQuery;
    }

    public function deleteItemType($itemTypeId)
    {
        // Check if any items are using this type
        $itemsCount = Item::where('item_type_id', $itemTypeId)->count();
        
        if ($itemsCount > 0) {
            $this->flashMessage = "Cannot delete item type. It is being used by {$itemsCount} item(s).";
            return;
        }

        \DB::table('item_type')->where('item_type_id', $itemTypeId)->delete();
        $this->flashMessage = 'Item type deleted successfully!';
        $this->dispatch('item-saved');
        
        // Reset form if the deleted type was selected
        if ($this->item_type === \DB::table('item_type')->where('item_type_id', $itemTypeId)->value('label')) {
            $this->item_type = '';
            $this->itemTypeQuery = '';
        }
    }

    // Edit Modal Combobox Methods
    public function selectEditItemType($typeLabel)
    {
        $this->editItemType = $typeLabel;
        $this->editItemTypeQuery = $typeLabel;
        $this->showEditItemTypeDropdown = false;
    }

    public function updatedEditItemTypeQuery()
    {
        $this->showEditItemTypeDropdown = true;
        $this->editItemType = $this->editItemTypeQuery;
    }

    public function deleteSelected()
    {
        if (empty($this->selectedItems)) return;
        
        $count = count($this->selectedItems);
        $this->deleteMessage = "Are you sure you want to delete {$count} selected item(s)? This action cannot be undone.";
        $this->deleteAction = 'confirmDeleteSelected';
        $this->itemsToDelete = $this->selectedItems;
        $this->showDeleteModal = true;
    }

    // New method to confirm delete
    public function confirmDeleteSelected()
    {
        if (empty($this->itemsToDelete)) return;
        
        $count = 0;
        foreach ($this->itemsToDelete as $id) {
            $item = Item::find($id);
            if ($item) {
                $item->softDelete();
                $count++;
            }
        }
        
        $this->logActivity("Bulk deleted {$count} item(s)");
        $this->flashMessage = $count . ' item(s) deleted successfully!';
        $this->dispatch('item-saved');
        $this->selectedItems = [];
        $this->selectAll = false;
        $this->resetPage();
        $this->closeDeleteModal();
    }

    // New method to close delete modal
    public function closeDeleteModal()
    {
        $this->showDeleteModal = false;
        $this->deleteMessage = '';
        $this->deleteAction = '';
        $this->itemsToDelete = [];
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

        // Get filtered item types for combobox
        $itemTypesQuery = \DB::table('item_type');
        if ($this->itemTypeQuery) {
            $itemTypesQuery->where('label', 'like', '%' . $this->itemTypeQuery . '%');
        }
        $filteredItemTypes = $itemTypesQuery->orderBy('label')->get();

        // Get filtered item types for edit modal
        $editItemTypesQuery = \DB::table('item_type');
        if ($this->editItemTypeQuery) {
            $editItemTypesQuery->where('label', 'like', '%' . $this->editItemTypeQuery . '%');
        }
        $filteredEditItemTypes = $editItemTypesQuery->orderBy('label')->get();

        return [
            'items' => $items,
            'sections' => Section::active()->orderBy('label')->get(),
            'itemTypes' => \DB::table('item_type')->orderBy('label')->get(),
            'filteredItemTypes' => $filteredItemTypes,
            'filteredEditItemTypes' => $filteredEditItemTypes,
        ];
    }
};
?>

<div class="p-6" x-data="{ 
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
}" x-init="$wire.on('item-saved', () => showToastMessage())">
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
    <div class="flex items-center space-x-3 mb-6">
        <svg class="w-8 h-8 text-gray-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
        </svg>
        <h1 class="text-2xl font-bold text-gray-900">Items Management</h1>
    </div>

    <!-- Add New Item Form -->
    <div class="bg-white rounded-lg shadow-sm p-6 mb-6">
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
                    <div class="relative" x-data="{ open: false }">
                        <input type="text" 
                               wire:model.live.debounce.300ms="itemTypeQuery"
                               wire:keydown.enter.prevent
                               wire:keydown.arrow-down.prevent="open = true"
                               wire:keydown.escape.prevent="open = false"
                               @keydown.arrow-down.window="if ($event.target === $el) open = true"
                               @keydown.escape.window="open = false"
                               @focus="open = true"
                               @click.away="open = false"
                               placeholder="Type or select item type..."
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-pink-500 focus:border-transparent"
                               autocomplete="off">
                        
                        <!-- Dropdown Arrow -->
                        <div class="absolute inset-y-0 right-0 flex items-center pr-2 pointer-events-none">
                            <svg class="h-5 w-5 text-gray-400" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd"/>
                            </svg>
                        </div>

                        <!-- Dropdown -->
                        <div x-show="open" 
                             x-transition:enter="transition ease-out duration-100"
                             x-transition:enter-start="opacity-0"
                             x-transition:enter-end="opacity-100"
                             x-transition:leave="transition ease-in duration-75"
                             x-transition:leave-start="opacity-100"
                             x-transition:leave-end="opacity-0"
                             class="absolute z-10 mt-1 w-full bg-white shadow-lg max-h-60 rounded-md py-1 text-base ring-1 ring-black ring-opacity-5 overflow-auto focus:outline-none sm:text-sm">
                            
                            <!-- Show "Create new" option if query doesn't match existing -->
                            @if($itemTypeQuery && !collect($itemTypes)->contains('label', $itemTypeQuery))
                                <div wire:click="selectItemType('{{ $itemTypeQuery }}')" 
                                     class="cursor-pointer select-none relative py-2 pl-3 pr-9 hover:bg-gray-100">
                                    <div class="flex items-center">
                                        <span class="font-normal ml-3 block truncate">Create "{{ $itemTypeQuery }}"</span>
                                    </div>
                                </div>
                            @endif

                            <!-- Existing item types -->
                            @foreach($filteredItemTypes as $type)
                                <div class="group flex items-center justify-between px-3 py-2 hover:bg-gray-100 cursor-pointer"
                                     wire:click="selectItemType('{{ $type->label }}')">
                                    <span class="block truncate">{{ $type->label }}</span>
                                    <button type="button" 
                                            wire:click.stop="deleteItemType({{ $type->item_type_id }})"
                                            wire:confirm="Are you sure you want to delete this item type?"
                                            class="opacity-0 group-hover:opacity-100 text-red-500 hover:text-red-700 p-1 rounded transition-opacity">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                        </svg>
                                    </button>
                                </div>
                            @endforeach
                        </div>
                    </div>
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

    <!-- Search and Filters -->
    <div class="bg-white rounded-lg shadow-sm mb-6">
        <div class="p-6">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Rows per page</label>
                    <select wire:model.live="perPage" 
                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-pink-500">
                        <option value="10">10</option>
                        <option value="25">25</option>
                        <option value="50">50</option>
                        <option value="100">100</option>
                        <option value="all">All</option>
                    </select>
                </div>
                <div class="md:col-span-2">
                    <!-- Empty space for layout balance -->
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Search Items</label>
                    <input type="text" wire:model.live.debounce.300ms="search" placeholder="Search by item name, type, or section..." 
                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-pink-500" style="min-width: 280px;">
                </div>
            </div>
        </div>
    </div>

    <!-- Items List -->
    <div class="bg-white rounded-lg shadow-sm mb-6">
        <div class="px-6 py-4 border-b border-gray-200">
            <div class="flex items-center justify-between">
                <h2 class="text-lg font-semibold text-gray-900">Items Directory</h2>
                @if(count($selectedItems) > 0)
                <button wire:click="deleteSelected" 
                        class="inline-flex items-center gap-2 px-4 py-2 bg-red-500 hover:bg-red-600 text-white text-sm font-medium rounded-lg transition-colors">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                    </svg>
                    Delete Selected ({{ count($selectedItems) }})
                </button>
                @endif
            </div>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left w-10"> 
                            <input type="checkbox" wire:model.live="selectAll"
                                   class="h-4 w-4 rounded border-gray-300 text-pink-600 focus:ring-pink-500">
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Item Name</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Item Type</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Section</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($items as $item)
                        <tr wire:key="item-{{ $item->item_id }}" 
                            wire:click="openEditModal({{ $item->item_id }})" 
                            class="hover:bg-gray-50 cursor-pointer transition-colors {{ in_array((string) $item->item_id, $selectedItems) ? 'bg-pink-50' : '' }}">
                            <td class="px-6 py-4" wire:click.stop>
                                <input type="checkbox" wire:model.live="selectedItems" value="{{ $item->item_id }}"
                                       class="h-4 w-4 rounded border-gray-300 text-pink-600 focus:ring-pink-500">
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex items-center">
                                    <div class="w-8 h-8 bg-pink-500 rounded-full flex items-center justify-center text-white text-xs font-bold mr-3">
                                        {{ strtoupper(substr($item->label, 0, 2)) }}
                                    </div>
                                    <div>
                                        <div class="font-medium text-gray-900">{{ $item->label }}</div>
                                        <div class="text-xs text-gray-500">ID: {{ $item->item_id }}</div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $item->item_type_label ?? 'N/A' }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm">
                                @if($item->section)
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">{{ $item->section->label }}</span>
                                @else
                                    <span class="text-gray-400">Unassigned</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-6 py-12 text-center text-gray-500">No items found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($perPage !== 'all' && method_exists($items, 'hasPages') && $items->hasPages())
            <div class="px-6 py-4 border-t border-gray-200">
                {{ $items->links() }}
            </div>
        @endif
    </div>

    <!-- Edit Item Modal -->
    @if($showEditModal)
    <div class="fixed inset-0 z-50 overflow-y-auto flex items-center justify-center p-4" style="background-color: rgba(15,23,42,0.75); backdrop-filter: blur(4px);">
        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-lg max-h-[90vh] flex flex-col" wire:click.stop>
            {{-- Header --}}
            <div class="flex items-center justify-between px-7 py-5 rounded-t-2xl" style="background: #d2334c;">
                <div class="flex items-center gap-3">
                    <div class="bg-white/20 rounded-xl p-2.5">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-lg font-bold text-white">Edit Item</h3>
                        <p class="text-red-200 text-xs mt-0.5">Update item information</p>
                    </div>
                </div>
                <button wire:click="closeEditModal" class="text-white/70 hover:text-white transition-colors">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>

            <div class="p-7 overflow-y-auto flex-1 space-y-5">
                {{-- Item Name --}}
                <div>
                    <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1.5">Item Name *</label>
                    <input type="text" wire:model="editLabel"
                        class="w-full px-3.5 py-2.5 border border-gray-200 rounded-lg text-sm focus:ring-2 focus:ring-red-500 focus:border-transparent bg-gray-50 focus:bg-white transition"
                        placeholder="Enter item name">
                    @error('editLabel') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                </div>

                {{-- Item Type Combobox --}}
                <div>
                    <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1.5">Item Type</label>
                    <div class="relative">
                        @if($editItemType)
                            <div class="flex items-center gap-2 px-3.5 py-2.5 border border-gray-200 rounded-lg bg-gray-50 text-sm">
                                <span class="flex-1 text-gray-900">{{ $editItemType }}</span>
                                <button type="button" wire:click="$set('editItemType', '')" class="text-gray-400 hover:text-gray-600 shrink-0">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                </button>
                            </div>
                        @else
                            <input type="text"
                                wire:model.live.debounce.300ms="editItemTypeQuery"
                                wire:focus="$set('showEditItemTypeDropdown', true)"
                                class="w-full px-3.5 py-2.5 border border-gray-200 rounded-lg text-sm focus:ring-2 focus:ring-red-500 focus:border-transparent bg-gray-50 focus:bg-white transition"
                                placeholder="Search or create item type...">
                            @if($showEditItemTypeDropdown)
                            <div class="absolute z-10 w-full mt-1 bg-white border border-gray-200 rounded-lg shadow-lg overflow-hidden">
                                <ul class="max-h-48 overflow-y-auto py-1">
                                    @foreach($filteredEditItemTypes as $type)
                                        <li wire:click="selectEditItemType('{{ $type->label }}')"
                                            class="px-4 py-2 text-sm text-gray-700 hover:bg-red-50 hover:text-red-700 cursor-pointer">
                                            {{ $type->label }}
                                        </li>
                                    @endforeach
                                    @if($editItemTypeQuery)
                                        <li wire:click="selectEditItemType('{{ $editItemTypeQuery }}')"
                                            class="px-4 py-2 text-sm font-medium cursor-pointer flex items-center gap-2"
                                            style="color:#d2334c">
                                            <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                                            Create "{{ $editItemTypeQuery }}"
                                        </li>
                                    @endif
                                    @if($filteredEditItemTypes->isEmpty() && !$editItemTypeQuery)
                                        <li class="px-4 py-2 text-sm text-gray-400 text-center">No item types found</li>
                                    @endif
                                </ul>
                            </div>
                            @endif
                        @endif
                    </div>
                </div>

                {{-- Section --}}
                <div>
                    <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1.5">Section</label>
                    <select wire:model="editSectionId"
                        class="w-full px-3.5 py-2.5 border border-gray-200 rounded-lg text-sm focus:ring-2 focus:ring-red-500 focus:border-transparent bg-gray-50 focus:bg-white transition">
                        <option value="">— No Section —</option>
                        @foreach($sections as $section)
                            <option value="{{ $section->section_id }}">{{ $section->label }}</option>
                        @endforeach
                    </select>
                    @error('editSectionId') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                </div>

                {{-- Footer --}}
                <div class="flex justify-end gap-3 pt-4 border-t border-gray-100">
                    <button type="button" wire:click="closeEditModal"
                        class="px-5 py-2.5 border border-gray-200 hover:bg-gray-50 text-gray-600 text-sm font-semibold rounded-xl transition-colors">
                        Cancel
                    </button>
                    <button type="button" wire:click="updateItem"
                        class="px-5 py-2.5 text-white text-sm font-semibold rounded-xl transition-colors"
                        style="background-color:#be123c"
                        onmouseover="this.style.backgroundColor='#881337'"
                        onmouseout="this.style.backgroundColor='#be123c'">
                        Save Changes
                    </button>
                </div>
            </div>
        </div>
    </div>
    @endif

    <!-- Delete Confirmation Modal -->
    @if($showDeleteModal)
    <div class="fixed inset-0 z-[60] overflow-y-auto flex items-center justify-center p-4" style="background-color: rgba(15,23,42,0.75); backdrop-filter: blur(4px);">
        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md" wire:click.stop>
            <div class="p-8 text-center">
                <div class="mx-auto mb-4 w-14 h-14 rounded-full bg-red-100 flex items-center justify-center">
                    <svg class="w-7 h-7 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z"/>
                    </svg>
                </div>
                <h3 class="text-lg font-bold text-gray-900 mb-2">Confirm Deletion</h3>
                <p class="text-sm text-gray-600 mb-6">{{ $deleteMessage }}</p>
                <div class="flex justify-center gap-3">
                    <button wire:click="closeDeleteModal"
                        class="px-5 py-2.5 border border-gray-200 hover:bg-gray-50 text-gray-600 text-sm font-semibold rounded-xl transition-colors">
                        Cancel
                    </button>
                    <button wire:click="{{ $deleteAction }}"
                        class="px-5 py-2.5 bg-red-600 hover:bg-red-700 text-white text-sm font-semibold rounded-xl transition-colors">
                        Delete
                    </button>
                </div>
            </div>
        </div>
    </div>
    @endif

</div>
