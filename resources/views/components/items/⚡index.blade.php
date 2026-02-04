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
    public $unit = '';

    #[Validate('nullable|integer|min:0')]
    public $reorder_level = 0;

    public $search = '';
    public $flashMessage = '';

    public function mount()
    {
        if (session()->has('success')) {
            $this->flashMessage = session('success');
        }
    }

    public function save()
    {
        $this->validate();

        Item::create([
            'section_id' => $this->section_id,
            'label' => $this->label,
            'unit' => $this->unit,
            'reorder_level' => $this->reorder_level,
            'status_code' => 1,
            'is_deleted' => 0,
        ]);

        $this->reset(['section_id', 'label', 'unit', 'reorder_level']);
        $this->flashMessage = 'Item added successfully!';
        $this->resetPage();
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
        return [
            'items' => Item::active()
                ->with('section')
                ->when($this->search, function ($query) {
                    $query->where(function ($q) {
                        $q->where('label', 'like', '%' . $this->search . '%')
                          ->orWhere('unit', 'like', '%' . $this->search . '%');
                    });
                })
                ->orderBy('item_id', 'desc')
                ->paginate(50),
            'sections' => Section::active()->orderBy('label')->get()
        ];
    }
};
?>

<div class="min-h-screen bg-gradient-to-br from-pink-50 to-purple-50 p-6">
    <div class="max-w-7xl mx-auto">
        <div class="mb-8">
            <h1 class="text-4xl font-bold text-gray-800 mb-2">Inventory Management</h1>
            <p class="text-gray-600">Manage laboratory items and supplies</p>
        </div>

        @if($flashMessage)
            <div class="mb-6 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative" role="alert">
                <span class="block sm:inline">{{ $flashMessage }}</span>
            </div>
        @endif

        <div class="bg-white rounded-xl shadow-lg p-6 mb-8">
            <h2 class="text-2xl font-bold text-gray-800 mb-6">Add New Item</h2>
            <form wire:submit.prevent="save">
                <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Section *</label>
                        <select wire:model="section_id" 
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-pink-500 focus:border-transparent">
                            <option value="">Select Section</option>
                            @foreach($sections as $section)
                                <option value="{{ $section->section_id }}">{{ $section->label }}</option>
                            @endforeach
                        </select>
                        @error('section_id') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Item Name *</label>
                        <input type="text" wire:model="label" 
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-pink-500 focus:border-transparent">
                        @error('label') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Unit *</label>
                        <input type="text" wire:model="unit" placeholder="e.g., pcs, ml, box" 
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-pink-500 focus:border-transparent">
                        @error('unit') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Reorder Level</label>
                        <input type="number" wire:model="reorder_level" 
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-pink-500 focus:border-transparent">
                        @error('reorder_level') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                    </div>
                </div>
                <div class="mt-6">
                    <button type="submit" 
                            class="bg-gradient-to-r from-pink-500 to-purple-500 text-white px-6 py-3 rounded-lg hover:from-pink-600 hover:to-purple-600 transition duration-200 font-medium">
                        Add Item
                    </button>
                </div>
            </form>
        </div>

        <div class="bg-white rounded-xl shadow-lg p-6">
            <div class="mb-6">
                <input type="text" wire:model.live="search" placeholder="Search items..." 
                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-pink-500 focus:border-transparent">
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gradient-to-r from-pink-50 to-purple-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">ID</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">Item Name</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">Section</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">Unit</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">Reorder Level</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @forelse($items as $item)
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ $item->item_id }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">{{ $item->label }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">{{ $item->section->label ?? 'N/A' }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">{{ $item->unit }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">{{ $item->reorder_level ?? 'N/A' }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium space-x-2">
                                    <a href="/items/{{ $item->item_id }}/edit" 
                                       class="text-orange-600 hover:text-orange-900">Edit</a>
                                    <button wire:click="delete({{ $item->item_id }})" 
                                            wire:confirm="Are you sure you want to delete this item?"
                                            class="text-red-600 hover:text-red-900">Delete</button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-6 py-4 text-center text-gray-500">No items found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="mt-6">
                {{ $items->links() }}
            </div>
        </div>
    </div>
</div>