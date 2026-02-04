<?php

use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Validate;
use App\Models\Equipment;
use App\Models\Section;

new class extends Component
{
    use WithPagination;

    #[Validate('required|string|max:255')]
    public $name = '';

    #[Validate('nullable|string|max:100')]
    public $model = '';

    #[Validate('nullable|string|max:100')]
    public $serial_no = '';

    #[Validate('required|exists:section,section_id')]
    public $section_id = '';

    #[Validate('nullable|string|max:50')]
    public $status = 'Operational';

    #[Validate('nullable|date')]
    public $purchase_date = '';

    #[Validate('nullable|string|max:255')]
    public $supplier = '';

    #[Validate('nullable|string|max:500')]
    public $remarks = '';

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

        Equipment::create([
            'name' => $this->name,
            'model' => $this->model,
            'serial_no' => $this->serial_no,
            'section_id' => $this->section_id,
            'status' => $this->status,
            'purchase_date' => $this->purchase_date,
            'supplier' => $this->supplier,
            'remarks' => $this->remarks,
            'is_deleted' => 0,
            'datetime_added' => now(),
        ]);

        $this->reset(['name', 'model', 'serial_no', 'section_id', 'purchase_date', 'supplier', 'remarks']);
        $this->status = 'Operational';
        $this->flashMessage = 'Equipment added successfully!';
        $this->resetPage();
    }

    public function delete($id)
    {
        $equipment = Equipment::find($id);
        if ($equipment) {
            $equipment->softDelete();
            $this->flashMessage = 'Equipment deleted successfully!';
        }
    }

    public function with(): array
    {
        return [
            'equipment' => Equipment::active()
                ->with('section')
                ->when($this->search, function ($query) {
                    $query->where(function ($q) {
                        $q->where('name', 'like', '%' . $this->search . '%')
                          ->orWhere('model', 'like', '%' . $this->search . '%')
                          ->orWhere('serial_no', 'like', '%' . $this->search . '%');
                    });
                })
                ->orderBy('equipment_id', 'desc')
                ->paginate(50),
            'sections' => Section::active()->orderBy('label')->get()
        ];
    }
};
?>

<div class="min-h-screen bg-gradient-to-br from-pink-50 to-purple-50 p-6">
    <div class="max-w-7xl mx-auto">
        <div class="mb-8">
            <h1 class="text-4xl font-bold text-gray-800 mb-2">Equipment Management</h1>
            <p class="text-gray-600">Manage laboratory equipment and instruments</p>
        </div>

        @if($flashMessage)
            <div class="mb-6 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative" role="alert">
                <span class="block sm:inline">{{ $flashMessage }}</span>
            </div>
        @endif

        <div class="bg-white rounded-xl shadow-lg p-6 mb-8">
            <h2 class="text-2xl font-bold text-gray-800 mb-6">Add New Equipment</h2>
            <form wire:submit.prevent="save">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Equipment Name *</label>
                        <input type="text" wire:model="name" 
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-pink-500 focus:border-transparent">
                        @error('name') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Model</label>
                        <input type="text" wire:model="model" 
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-pink-500 focus:border-transparent">
                        @error('model') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Serial Number</label>
                        <input type="text" wire:model="serial_no" 
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-pink-500 focus:border-transparent">
                        @error('serial_no') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                    </div>
                </div>
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
                        <label class="block text-sm font-medium text-gray-700 mb-2">Status</label>
                        <select wire:model="status" 
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-pink-500 focus:border-transparent">
                            <option value="Operational">Operational</option>
                            <option value="Under Maintenance">Under Maintenance</option>
                            <option value="Broken">Broken</option>
                            <option value="Decommissioned">Decommissioned</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Purchase Date</label>
                        <input type="date" wire:model="purchase_date" 
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-pink-500 focus:border-transparent">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Supplier</label>
                        <input type="text" wire:model="supplier" 
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-pink-500 focus:border-transparent">
                    </div>
                </div>
                <div class="mt-6">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Remarks</label>
                    <textarea wire:model="remarks" rows="3"
                              class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-pink-500 focus:border-transparent"></textarea>
                </div>
                <div class="mt-6">
                    <button type="submit" 
                            class="bg-gradient-to-r from-pink-500 to-purple-500 text-white px-6 py-3 rounded-lg hover:from-pink-600 hover:to-purple-600 transition duration-200 font-medium">
                        Add Equipment
                    </button>
                </div>
            </form>
        </div>

        <div class="bg-white rounded-xl shadow-lg p-6">
            <div class="mb-6">
                <input type="text" wire:model.live="search" placeholder="Search equipment..." 
                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-pink-500 focus:border-transparent">
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gradient-to-r from-pink-50 to-purple-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">ID</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">Name</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">Model</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">Serial No</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">Section</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">Status</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @forelse($equipment as $item)
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ $item->equipment_id }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">{{ $item->name }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">{{ $item->model ?? 'N/A' }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">{{ $item->serial_no ?? 'N/A' }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">{{ $item->section->label ?? 'N/A' }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm">
                                    <span class="px-2 py-1 text-xs rounded-full 
                                        {{ $item->status == 'Operational' ? 'bg-green-100 text-green-800' : '' }}
                                        {{ $item->status == 'Under Maintenance' ? 'bg-yellow-100 text-yellow-800' : '' }}
                                        {{ $item->status == 'Broken' ? 'bg-red-100 text-red-800' : '' }}
                                        {{ $item->status == 'Decommissioned' ? 'bg-gray-100 text-gray-800' : '' }}">
                                        {{ $item->status }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium space-x-2">
                                    <a href="/equipment/{{ $item->equipment_id }}/edit" 
                                       class="text-orange-600 hover:text-orange-900">Edit</a>
                                    <button wire:click="delete({{ $item->equipment_id }})" 
                                            wire:confirm="Are you sure you want to delete this equipment?"
                                            class="text-red-600 hover:text-red-900">Delete</button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-6 py-4 text-center text-gray-500">No equipment found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="mt-6">
                {{ $equipment->links() }}
            </div>
        </div>
    </div>
</div>