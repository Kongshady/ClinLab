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

    // Edit mode
    public $editMode = false;
    public $editingItemId = null;

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

        if ($this->editMode) {
            $item = Item::findOrFail($this->editingItemId);
            $item->update([
                'section_id' => $this->section_id,
                'label' => $this->label,
                'item_type_id' => $itemTypeId,
            ]);
            $this->flashMessage = 'Item updated successfully!';
            $this->cancelEdit();
        } else {
            Item::create([
                'section_id' => $this->section_id,
                'label' => $this->label,
                'item_type_id' => $itemTypeId,
                'status_code' => 1,
                'is_deleted' => 0,
            ]);
            $this->reset(['section_id', 'label', 'item_type']);
            $this->flashMessage = 'Item added successfully!';
        }
        
        $this->resetPage();
    }

    public function edit($id)
    {
        $item = Item::with('itemType')->findOrFail($id);
        $this->editingItemId = $id;
        $this->section_id = $item->section_id;
        $this->label = $item->label;
        $this->item_type = $item->itemType->label ?? '';
        $this->editMode = true;
    }

    public function cancelEdit()
    {
        $this->reset(['section_id', 'label', 'item_type', 'editMode', 'editingItemId']);
    }

    public function delete($id)
    {
        $item = Item::find($id);
        if ($item) {
            $item->softDelete();
            $this->flashMessage = 'Item deleted successfully!';
        }
    }

    public function with(): array
    {
        $query = Item::active()
            ->with('section')
            ->leftJoin('item_type', 'item.item_type_id', '=', 'item_type.item_type_id')
            ->select('item.*', 'item_type.label as item_type_label')
            ->when($this->search, function ($query) {
                $query->where(function ($q) {
                    $q->where('item.label', 'like', '%' . $this->search . '%');
                });
            })
            ->orderBy('item.item_id', 'desc');

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
        <input type="text" wire:model.live="search" placeholder="Search by item name..." 
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
            <h2 class="text-lg font-semibold text-gray-900 mb-4">Items List</h2>
            
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead>
                        <tr class="bg-gray-50">
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Item Name</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Item Type</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Section</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        @forelse($items as $item)
                            <tr class="hover:bg-gray-50">
                                <td class="px-4 py-3 text-sm text-gray-900 font-medium">{{ $item->label }}</td>
                                <td class="px-4 py-3 text-sm text-gray-700">{{ $item->item_type_label ?? 'N/A' }}</td>
                                <td class="px-4 py-3 text-sm text-gray-700">{{ $item->section->label ?? 'N/A' }}</td>
                                <td class="px-4 py-3">
                                    <div class="flex items-center space-x-2">
                                        <button wire:click="edit({{ $item->item_id }})" 
                                           class="px-4 py-1.5 bg-orange-500 hover:bg-orange-600 text-white text-sm font-medium rounded-lg transition-colors">Edit</button>
                                        <button wire:click="delete({{ $item->item_id }})" 
                                                wire:confirm="Are you sure you want to delete this item?"
                                                class="px-4 py-1.5 bg-red-500 hover:bg-red-600 text-white text-sm font-medium rounded-lg transition-colors">Delete</button>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-4 py-8 text-center text-gray-500">No items found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($perPage !== 'all')
            <div class="mt-6">
                {{ $items->links() }}
            </div>
            @endif
        </div>
    </div>

    <!-- Edit Item Modal -->
    @if($editMode)
    <div class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50 p-4">
        <div class="relative top-10 mx-auto p-6 border w-full max-w-2xl shadow-lg rounded-xl bg-white">
            <div class="flex items-center justify-between mb-6">
                <h3 class="text-2xl font-bold text-gray-900">Edit Item</h3>
                <button wire:click="cancelEdit" class="text-gray-400 hover:text-gray-600">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>

            <form wire:submit.prevent="save">
                <div class="grid grid-cols-1 gap-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Section *</label>
                        <select wire:model="section_id" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-pink-500 focus:border-transparent" required>
                            <option value="">Select Section</option>
                            @foreach($sections as $section)
                                <option value="{{ $section->section_id }}">{{ $section->label }}</option>
                            @endforeach
                        </select>
                        @error('section_id') <span class="text-red-500 text-sm mt-1 block">{{ $message }}</span> @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Item Name *</label>
                        <input type="text" wire:model="label" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-pink-500 focus:border-transparent" required>
                        @error('label') <span class="text-red-500 text-sm mt-1 block">{{ $message }}</span> @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Item Type *</label>
                        <input type="text" wire:model="item_type" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-pink-500 focus:border-transparent" required>
                        @error('item_type') <span class="text-red-500 text-sm mt-1 block">{{ $message }}</span> @enderror
                    </div>
                </div>
                <div class="flex justify-end space-x-3 pt-4 border-t mt-6">
                    <button type="button" wire:click="cancelEdit" class="px-6 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition-colors">
                        Cancel
                    </button>
                    <button type="submit" class="px-6 py-2 bg-pink-600 text-white rounded-lg hover:bg-pink-700 transition-colors">
                        Update Item
                    </button>
                </div>
            </form>
        </div>
    </div>
    @endif
</div>