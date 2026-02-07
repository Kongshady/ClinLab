<?php

use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Validate;
use App\Models\Physician;

new class extends Component
{
    use WithPagination;

    #[Validate('required|string|max:255')]
    public $physician_name = '';

    #[Validate('nullable|string|max:255')]
    public $specialization = '';

    #[Validate('nullable|string|max:20')]
    public $contact_number = '';

    #[Validate('nullable|email|max:255')]
    public $email = '';

    public $search = '';
    public $filterSpecialization = '';
    public $flashMessage = '';
    public $perPage = 'all';
    
    public $editMode = false;
    public $editId = null;
    public $showEditModal = false;

    public function mount()
    {
        if (session()->has('success')) {
            $this->flashMessage = session('success');
        }
    }

    public function save()
    {
        $this->validate();

        if ($this->editMode) {
            $physician = Physician::find($this->editId);
            $physician->update([
                'physician_name' => $this->physician_name,
                'specialization' => $this->specialization,
                'contact_number' => $this->contact_number,
                'email' => $this->email,
            ]);
            $this->flashMessage = 'Physician updated successfully!';
            $this->editMode = false;
            $this->editId = null;
            $this->showEditModal = false;
        } else {
            Physician::create([
                'physician_name' => $this->physician_name,
                'specialization' => $this->specialization,
                'contact_number' => $this->contact_number,
                'email' => $this->email,
                'status_code' => 1,
                'is_deleted' => 0,
                'datetime_added' => now(),
            ]);
            $this->flashMessage = 'Physician added successfully!';
        }

        $this->reset(['physician_name', 'specialization', 'contact_number', 'email']);
        $this->resetPage();
    }

    public function edit($id)
    {
        $physician = Physician::find($id);
        $this->editMode = true;
        $this->editId = $id;
        $this->physician_name = $physician->physician_name;
        $this->specialization = $physician->specialization;
        $this->contact_number = $physician->contact_number;
        $this->email = $physician->email;
        $this->showEditModal = true;
    }

    public function cancelEdit()
    {
        $this->editMode = false;
        $this->editId = null;
        $this->showEditModal = false;
        $this->reset(['physician_name', 'specialization', 'contact_number', 'email']);
    }

    public function delete($id)
    {
        $physician = Physician::find($id);
        if ($physician) {
            $physician->softDelete();
            $this->flashMessage = 'Physician deleted successfully!';
        }
    }

    public function with(): array
    {
        $query = Physician::active()
            ->when($this->search, function ($query) {
                $query->where(function ($q) {
                    $q->where('physician_name', 'like', '%' . $this->search . '%')
                      ->orWhere('specialization', 'like', '%' . $this->search . '%')
                      ->orWhere('email', 'like', '%' . $this->search . '%');
                });
            })
            ->when($this->filterSpecialization, function ($query) {
                $query->where('specialization', 'like', '%' . $this->filterSpecialization . '%');
            })
            ->orderBy('physician_id', 'desc');

        $physicians = $this->perPage === 'all' ? $query->get() : $query->paginate((int)$this->perPage);
        
        $specializations = Physician::active()
            ->whereNotNull('specialization')
            ->where('specialization', '!=', '')
            ->distinct()
            ->pluck('specialization');

        return [
            'physicians' => $physicians,
            'specializations' => $specializations
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
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5.121 17.804A13.937 13.937 0 0112 16c2.5 0 4.847.655 6.879 1.804M15 10a3 3 0 11-6 0 3 3 0 016 0zm6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
        </svg>
        <h1 class="text-2xl font-bold text-gray-900">Physician Management</h1>
    </div>

    <!-- Add New Physician Form -->
    <div class="bg-white rounded-lg shadow-sm p-6">
        <h2 class="text-lg font-semibold text-gray-900 mb-6">Add New Physician</h2>
            <form wire:submit.prevent="save">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Physician Name *</label>
                        <input type="text" wire:model="physician_name" 
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-pink-500 focus:border-transparent">
                        @error('physician_name') <span class="text-red-600 text-sm">{{ $message }}</span> @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Specialization</label>
                        <input type="text" wire:model="specialization" 
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-pink-500 focus:border-transparent">
                        @error('specialization') <span class="text-red-600 text-sm">{{ $message }}</span> @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Contact Number</label>
                        <input type="text" wire:model="contact_number" id="add_contact_number"
                               maxlength="11" placeholder="09123456789"
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-pink-500 focus:border-transparent">
                        <p id="add_contact_error" class="text-red-600 text-xs mt-1 hidden"></p>
                        <p class="text-gray-500 text-xs mt-1">Must be exactly 11 digits (09 format)</p>
                        @error('contact_number') <span class="text-red-600 text-sm">{{ $message }}</span> @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Email</label>
                        <input type="email" wire:model="email" 
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-pink-500 focus:border-transparent">
                        @error('email') <span class="text-red-600 text-sm">{{ $message }}</span> @enderror
                    </div>
                </div>
                <div class="flex justify-end mt-6">
                    <button type="submit" class="px-6 py-2 bg-pink-500 hover:bg-pink-600 text-white font-medium rounded-lg transition-colors">
                        Add Physician
                    </button>
                </div>
            </form>
    </div>

    <!-- Search and Filters -->
    <div class="bg-white rounded-lg shadow-sm p-6">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Search</label>
                <input type="text" wire:model.live="search" placeholder="Search by name, specialization, or email..." 
                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-pink-500 focus:border-transparent">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Filter by Specialization</label>
                <select wire:model.live="filterSpecialization" 
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-pink-500 focus:border-transparent">
                    <option value="">All Specializations</option>
                    @foreach($specializations as $spec)
                        <option value="{{ $spec }}">{{ $spec }}</option>
                    @endforeach
                </select>
            </div>
        </div>
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

    <!-- Physicians List -->
    <div class="bg-white rounded-lg shadow-sm">
        <div class="p-6">
            <h2 class="text-lg font-semibold text-gray-900 mb-4">Physicians List</h2>
            
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead>
                        <tr class="bg-gray-50">
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Name</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Specialization</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Contact</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Email</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        @forelse($physicians as $physician)
                            <tr class="hover:bg-gray-50">
                                <td class="px-4 py-3 text-sm text-gray-900 font-medium">{{ $physician->physician_name }}</td>
                                <td class="px-4 py-3 text-sm text-gray-700">{{ $physician->specialization ?? 'N/A' }}</td>
                                <td class="px-4 py-3 text-sm text-gray-700">{{ $physician->contact_number ?? 'N/A' }}</td>
                                <td class="px-4 py-3 text-sm text-gray-700">{{ $physician->email ?? 'N/A' }}</td>
                                <td class="px-4 py-3">
                                    <div class="flex items-center space-x-2">
                                        <button wire:click="edit({{ $physician->physician_id }})" 
                                                class="px-4 py-1.5 bg-orange-500 hover:bg-orange-600 text-white text-sm font-medium rounded-lg transition-colors">Edit</button>
                                        <button wire:click="delete({{ $physician->physician_id }})" 
                                                wire:confirm="Are you sure you want to delete this physician?"
                                                class="px-4 py-1.5 bg-red-500 hover:bg-red-600 text-white text-sm font-medium rounded-lg transition-colors">Delete</button>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-4 py-8 text-center text-gray-500">No physicians found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($perPage !== 'all')
            <div class="mt-6">
                {{ $physicians->links() }}
            </div>
            @endif
        </div>
    </div>

    <!-- Edit Modal -->
    @if($showEditModal)
    <div class="fixed inset-0 z-50 overflow-y-auto" style="background-color: rgba(0, 0, 0, 0.5);">
        <div class="flex items-center justify-center min-h-screen p-4">
            <div class="bg-white rounded-lg shadow-xl max-w-2xl w-full">
                <!-- Modal Header -->
                <div class="px-6 py-4 border-b border-gray-200">
                    <div class="flex items-center justify-between">
                        <h3 class="text-xl font-semibold text-gray-900">Edit Physician</h3>
                        <button wire:click="cancelEdit" class="text-gray-400 hover:text-gray-600">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </button>
                    </div>
                </div>

                <!-- Modal Body -->
                <form wire:submit.prevent="save">
                    <div class="p-6">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                    Physician Name <span class="text-red-500">*</span>
                                </label>
                                <input type="text" wire:model="physician_name" 
                                       class="w-full px-3 py-2.5 border border-gray-300 rounded-md focus:outline-none focus:ring-1 focus:ring-blue-500 focus:border-blue-500">
                                @error('physician_name') <span class="text-red-500 text-sm mt-1 block">{{ $message }}</span> @enderror
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                    Specialization
                                </label>
                                <input type="text" wire:model="specialization" 
                                       class="w-full px-3 py-2.5 border border-gray-300 rounded-md focus:outline-none focus:ring-1 focus:ring-blue-500 focus:border-blue-500">
                                @error('specialization') <span class="text-red-500 text-sm mt-1 block">{{ $message }}</span> @enderror
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                    Contact Number
                                </label>
                                <input type="text" wire:model="contact_number" id="edit_contact_number"
                                       maxlength="11" placeholder="09123456789"
                                       class="w-full px-3 py-2.5 border border-gray-300 rounded-md focus:outline-none focus:ring-1 focus:ring-blue-500 focus:border-blue-500">
                                <p id="edit_contact_error" class="text-red-500 text-xs mt-1 hidden"></p>
                                <p class="text-gray-500 text-xs mt-1">Must be exactly 11 digits (09 format)</p>
                                @error('contact_number') <span class="text-red-500 text-sm mt-1 block">{{ $message }}</span> @enderror
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                    Email
                                </label>
                                <input type="email" wire:model="email" 
                                       class="w-full px-3 py-2.5 border border-gray-300 rounded-md focus:outline-none focus:ring-1 focus:ring-blue-500 focus:border-blue-500">
                                @error('email') <span class="text-red-500 text-sm mt-1 block">{{ $message }}</span> @enderror
                            </div>
                        </div>
                    </div>

                    <!-- Modal Footer -->
                    <div class="px-6 py-4 bg-gray-50 border-t border-gray-200 flex justify-end space-x-3 rounded-b-lg">
                        <button type="button" wire:click="cancelEdit" 
                                class="px-5 py-2.5 border border-gray-300 rounded-md text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none">
                            Cancel
                        </button>
                        <button type="submit" 
                                class="px-5 py-2.5 bg-orange-500 text-white text-sm rounded-md font-medium hover:bg-orange-600 focus:outline-none">
                            Update Physician
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    @endif
</div>

<script>
function validateContactNumber(inputId, errorId) {
    const input = document.getElementById(inputId);
    if (!input) return;
    
    const error = document.getElementById(errorId);
    
    input.addEventListener('input', function(e) {
        let value = e.target.value;
        
        // Remove any characters that are not digits
        value = value.replace(/[^\d]/g, '');
        
        e.target.value = value;
        
        // Trigger Livewire update
        input.dispatchEvent(new Event('input', { bubbles: true }));
        
        // Count digits
        const digitCount = value.length;
        
        if (value && digitCount > 0) {
            if (digitCount < 11) {
                const missing = 11 - digitCount;
                error.textContent = `Missing ${missing} ${missing === 1 ? 'number' : 'numbers'}`;
                error.classList.remove('hidden');
                input.classList.add('border-red-500');
            } else if (digitCount === 11) {
                error.classList.add('hidden');
                input.classList.remove('border-red-500');
            } else {
                error.textContent = 'Contact number must be exactly 11 digits';
                error.classList.remove('hidden');
                input.classList.add('border-red-500');
            }
        } else {
            error.classList.add('hidden');
            input.classList.remove('border-red-500');
        }
    });
}

// Initialize validators
document.addEventListener('DOMContentLoaded', function() {
    validateContactNumber('add_contact_number', 'add_contact_error');
});

// Re-initialize when modal opens (for edit form)
document.addEventListener('livewire:load', function() {
    Livewire.hook('message.processed', (message, component) => {
        setTimeout(() => {
            validateContactNumber('edit_contact_number', 'edit_contact_error');
        }, 100);
    });
});
</script>