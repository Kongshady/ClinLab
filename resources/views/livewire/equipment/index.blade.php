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
    public $perPage = 'all';
    public $flashMessage = '';

    // Edit Modal Properties
    public $showEditModal = false;
    public $editEquipmentId = '';
    public $editName = '';
    public $editModel = '';
    public $editSerialNo = '';
    public $editSectionId = '';
    public $editStatus = '';
    public $editPurchaseDate = '';
    public $editSupplier = '';
    public $editRemarks = '';

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

    public function openEditModal($equipmentId)
    {
        $equipment = Equipment::findOrFail($equipmentId);
        
        $this->editEquipmentId = $equipmentId;
        $this->editName = $equipment->name;
        $this->editModel = $equipment->model ?? '';
        $this->editSerialNo = $equipment->serial_no ?? '';
        $this->editSectionId = $equipment->section_id;
        $this->editStatus = $equipment->status ?? 'Operational';
        $this->editPurchaseDate = $equipment->purchase_date ?? '';
        $this->editSupplier = $equipment->supplier ?? '';
        $this->editRemarks = $equipment->remarks ?? '';
        
        $this->showEditModal = true;
    }

    public function closeEditModal()
    {
        $this->showEditModal = false;
        $this->reset(['editEquipmentId', 'editName', 'editModel', 'editSerialNo', 'editSectionId', 'editStatus', 'editPurchaseDate', 'editSupplier', 'editRemarks']);
    }

    public function updateEquipment()
    {
        $validated = $this->validate([
            'editName' => 'required|string|max:255',
            'editModel' => 'nullable|string|max:100',
            'editSerialNo' => 'nullable|string|max:100',
            'editSectionId' => 'required|exists:section,section_id',
            'editStatus' => 'nullable|string|max:50',
            'editPurchaseDate' => 'nullable|date',
            'editSupplier' => 'nullable|string|max:255',
            'editRemarks' => 'nullable|string|max:500',
        ]);

        $equipment = Equipment::findOrFail($this->editEquipmentId);
        $equipment->update([
            'name' => $this->editName,
            'model' => $this->editModel,
            'serial_no' => $this->editSerialNo,
            'section_id' => $this->editSectionId,
            'status' => $this->editStatus,
            'purchase_date' => $this->editPurchaseDate,
            'supplier' => $this->editSupplier,
            'remarks' => $this->editRemarks,
        ]);

        $this->flashMessage = 'Equipment updated successfully!';
        $this->closeEditModal();
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
        $query = Equipment::active()
            ->with('section')
            ->when($this->search, function ($query) {
                $query->where(function ($q) {
                    $q->where('name', 'like', '%' . $this->search . '%')
                      ->orWhere('model', 'like', '%' . $this->search . '%')
                      ->orWhere('serial_no', 'like', '%' . $this->search . '%');
                });
            })
            ->orderBy('equipment_id', 'desc');

        return [
            'equipment' => $this->perPage === 'all' ? $query->get() : $query->paginate((int)$this->perPage),
            'sections' => Section::active()->orderBy('label')->get()
        ];
    }
};
?>

<div class="p-6">
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-gray-900 flex items-center">
            <svg class="w-7 h-7 mr-2 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z"/>
            </svg>
            Equipment Management
        </h1>
    </div>

    @if($flashMessage)
        <div class="mb-6 p-4 bg-green-50 border-l-4 border-green-500 rounded">
            <p class="text-green-800">{{ $flashMessage }}</p>
        </div>
    @endif

    <div class="bg-white rounded-lg shadow-sm mb-6">
        <div class="px-6 py-4 border-b border-gray-200">
            <h2 class="text-lg font-semibold text-gray-900">Add New Equipment</h2>
        </div>
        <form wire:submit.prevent="save" class="p-6">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Equipment Name *</label>
                        <input type="text" wire:model="name" 
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-pink-500 focus:border-transparent">
                        @error('name') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Model</label>
                        <input type="text" wire:model="model" 
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500">
                        @error('model') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Serial Number</label>
                        <input type="text" wire:model="serial_no" 
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500">
                        @error('serial_no') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                    </div>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Section *</label>
                        <select wire:model="section_id" 
                                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500">
                            <option value="">Select Section</option>
                            @foreach($sections as $section)
                                <option value="{{ $section->section_id }}">{{ $section->label }}</option>
                            @endforeach
                        </select>
                        @error('section_id') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                        <select wire:model="status" 
                                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500">
                            <option value="Operational">Operational</option>
                            <option value="Under Maintenance">Under Maintenance</option>
                            <option value="Broken">Broken</option>
                            <option value="Decommissioned">Decommissioned</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Purchase Date</label>
                        <input type="date" wire:model="purchase_date" 
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Supplier</label>
                        <input type="text" wire:model="supplier" 
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    </div>
                </div>
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Remarks</label>
                    <textarea wire:model="remarks" rows="3"
                              class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500"></textarea>
                </div>
                <div class="flex justify-end">
                    <button type="submit" 
                            class="px-4 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700 transition-colors">
                        Add Equipment
                    </button>
                </div>
            </form>
        </div>

    <div class="bg-white rounded-lg shadow-sm">
        <div class="px-6 py-4 border-b border-gray-200">
            <h2 class="text-lg font-semibold text-gray-900">Equipment List</h2>
        </div>
        <div class="p-6">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Search Equipment</label>
                    <input type="text" wire:model.live="search" placeholder="Search equipment..." 
                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Rows per page</label>
                    <select wire:model.live="perPage" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500">
                        <option value="10">10</option>
                        <option value="25">25</option>
                        <option value="50">50</option>
                        <option value="100">100</option>
                        <option value="all">All</option>
                    </select>
                </div>
            </div>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ID</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Name</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Model</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Serial No</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Section</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
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
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                    <div class="flex items-center space-x-2">
                                        <button wire:click="openEditModal({{ $item->equipment_id }})" 
                                           class="px-4 py-1.5 bg-orange-500 hover:bg-orange-600 text-white text-sm font-medium rounded-lg transition-colors">Edit</button>
                                        <button wire:click="delete({{ $item->equipment_id }})" 
                                                wire:confirm="Are you sure you want to delete this equipment?"
                                                class="px-4 py-1.5 bg-red-500 hover:bg-red-600 text-white text-sm font-medium rounded-lg transition-colors">Delete</button>
                                    </div>
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
            
            @if($perPage !== 'all' && method_exists($equipment, 'hasPages') && $equipment->hasPages())
                <div class="px-6 py-4 border-t border-gray-200">
                    {{ $equipment->links() }}
                </div>
            @endif
        </div>
    </div>

    <!-- Edit Equipment Modal -->
    @if($showEditModal)
    <div class="fixed inset-0 z-50 overflow-y-auto" style="background-color: rgba(0, 0, 0, 0.5);">
        <div class="flex items-center justify-center min-h-screen p-4">
            <div class="bg-white rounded-lg shadow-xl max-w-3xl w-full">
                <!-- Modal Header -->
                <div class="px-6 py-4 border-b border-gray-200">
                    <div class="flex items-center justify-between">
                        <h3 class="text-xl font-semibold text-gray-900">
                            Edit Equipment
                        </h3>
                        <button type="button" wire:click="closeEditModal" class="text-gray-400 hover:text-gray-600">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </button>
                    </div>
                </div>

                <!-- Modal Body -->
                <form wire:submit.prevent="updateEquipment">
                    <div class="p-6 space-y-5">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                            <!-- Equipment Name -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                    Equipment Name <span class="text-red-500">*</span>
                                </label>
                                <input type="text" wire:model="editName" 
                                       class="w-full px-3 py-2.5 border border-gray-300 rounded-md focus:outline-none focus:ring-1 focus:ring-blue-500 focus:border-blue-500"
                                       placeholder="Enter equipment name">
                                @error('editName') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
                            </div>

                            <!-- Model -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                    Model
                                </label>
                                <input type="text" wire:model="editModel" 
                                       class="w-full px-3 py-2.5 border border-gray-300 rounded-md focus:outline-none focus:ring-1 focus:ring-blue-500 focus:border-blue-500"
                                       placeholder="Enter model">
                                @error('editModel') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
                            </div>

                            <!-- Serial Number -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                    Serial Number
                                </label>
                                <input type="text" wire:model="editSerialNo" 
                                       class="w-full px-3 py-2.5 border border-gray-300 rounded-md focus:outline-none focus:ring-1 focus:ring-blue-500 focus:border-blue-500"
                                       placeholder="Enter serial number">
                                @error('editSerialNo') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
                            </div>

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

                            <!-- Status -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                    Status
                                </label>
                                <select wire:model="editStatus" 
                                        class="w-full px-3 py-2.5 border border-gray-300 rounded-md focus:outline-none focus:ring-1 focus:ring-blue-500 focus:border-blue-500">
                                    <option value="Operational">Operational</option>
                                    <option value="Under Maintenance">Under Maintenance</option>
                                    <option value="Broken">Broken</option>
                                    <option value="Decommissioned">Decommissioned</option>
                                </select>
                                @error('editStatus') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
                            </div>

                            <!-- Purchase Date -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                    Purchase Date
                                </label>
                                <input type="date" wire:model="editPurchaseDate" 
                                       class="w-full px-3 py-2.5 border border-gray-300 rounded-md focus:outline-none focus:ring-1 focus:ring-blue-500 focus:border-blue-500">
                                @error('editPurchaseDate') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
                            </div>

                            <!-- Supplier -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                    Supplier
                                </label>
                                <input type="text" wire:model="editSupplier" 
                                       class="w-full px-3 py-2.5 border border-gray-300 rounded-md focus:outline-none focus:ring-1 focus:ring-blue-500 focus:border-blue-500"
                                       placeholder="Enter supplier">
                                @error('editSupplier') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
                            </div>

                            <!-- Remarks -->
                            <div class="md:col-span-2">
                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                    Remarks
                                </label>
                                <textarea wire:model="editRemarks" rows="3"
                                          class="w-full px-3 py-2.5 border border-gray-300 rounded-md focus:outline-none focus:ring-1 focus:ring-blue-500 focus:border-blue-500"
                                          placeholder="Additional notes..."></textarea>
                                @error('editRemarks') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
                            </div>
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
                            Update Equipment
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    @endif
</div>

<script>
    document.addEventListener('livewire:initialized', () => {
        Livewire.on('close-edit-modal', () => {
            @this.closeEditModal();
        });
    });

    // Close modal on Escape key
    document.addEventListener('keydown', function(event) {
        if (event.key === 'Escape' && @this.showEditModal) {
            @this.closeEditModal();
        }
    });
</script>