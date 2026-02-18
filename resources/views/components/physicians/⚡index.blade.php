<?php

use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Validate;
use App\Models\Physician;
use App\Models\Section;
use App\Traits\LogsActivity;

new class extends Component
{
    use WithPagination, LogsActivity;

    #[Validate('required|string|max:255')]
    public $physician_name = '';

    #[Validate('nullable|string|max:255')]
    public $specialization = '';

    #[Validate('nullable|numeric|digits:11')]
    public $contact_number = '';

    #[Validate('nullable|email|max:255')]
    public $email = '';

    #[Validate('nullable|exists:section,section_id')]
    public $section_id = '';

    public $search = '';
    public $filterSpecialization = '';
    public $flashMessage = '';
    public $perPage = 'all';
    
    public $editMode = false;
    public $editId = null;
    public $showEditModal = false;
    public $showForm = false;

    // View modal
    public $showViewModal = false;
    public $viewingPhysician = null;

    public function mount()
    {
        if (session()->has('success')) {
            $this->flashMessage = session('success');
        }
    }

    public function toggleForm()
    {
        $this->showForm = !$this->showForm;
        
        if (!$this->showForm) {
            $this->reset(['physician_name', 'specialization', 'contact_number', 'email', 'section_id']);
            $this->resetErrorBag();
        }
    }

    public function save()
    {
        $this->validate();

        // Duplicate check
        $duplicateQuery = Physician::active()
            ->where('physician_name', $this->physician_name);

        if ($this->editMode) {
            $duplicateQuery->where('physician_id', '!=', $this->editId);
        }

        if ($duplicateQuery->exists()) {
            $this->addError('physician_name', 'A physician with this name already exists.');
            return;
        }

        if ($this->contact_number) {
            $contactQuery = Physician::active()
                ->where('contact_number', $this->contact_number);
            if ($this->editMode) {
                $contactQuery->where('physician_id', '!=', $this->editId);
            }
            if ($contactQuery->exists()) {
                $this->addError('contact_number', 'This contact number is already registered to another physician.');
                return;
            }
        }

        if ($this->email) {
            $emailQuery = Physician::active()
                ->where('email', $this->email);
            if ($this->editMode) {
                $emailQuery->where('physician_id', '!=', $this->editId);
            }
            if ($emailQuery->exists()) {
                $this->addError('email', 'This email is already registered to another physician.');
                return;
            }
        }

        if ($this->editMode) {
            $physician = Physician::find($this->editId);
            $physician->update([
                'physician_name' => $this->physician_name,
                'specialization' => $this->specialization,
                'contact_number' => $this->contact_number,
                'email' => $this->email,
                'section_id' => $this->section_id ?: null,
            ]);
            $this->logActivity("Updated physician ID {$this->editId}: {$this->physician_name}");
            $this->flashMessage = 'Physician updated successfully!';
            $this->editMode = false;
            $this->editId = null;
            $this->showEditModal = false;
            $this->showViewModal = false;
        } else {
            Physician::create([
                'physician_name' => $this->physician_name,
                'specialization' => $this->specialization,
                'contact_number' => $this->contact_number,
                'email' => $this->email,
                'section_id' => $this->section_id ?: null,
                'status_code' => 1,
                'is_deleted' => 0,
                'datetime_added' => now(),
            ]);
            $this->logActivity("Created physician: {$this->physician_name}");
            $this->flashMessage = 'Physician added successfully!';
            $this->showForm = false;
        }

        $this->reset(['physician_name', 'specialization', 'contact_number', 'email', 'section_id']);
        $this->resetPage();
    }

    public function showDetails($id)
    {
        $this->viewingPhysician = Physician::with('section')->find($id);
        $this->showViewModal = true;
        $this->editMode = false;
        $this->resetErrorBag();
    }

    public function enableEdit()
    {
        $physician = $this->viewingPhysician;
        $this->editMode = true;
        $this->editId = $physician->physician_id;
        $this->physician_name = $physician->physician_name;
        $this->specialization = $physician->specialization;
        $this->contact_number = $physician->contact_number;
        $this->email = $physician->email;
        $this->section_id = $physician->section_id;
    }

    public function cancelEdit()
    {
        $this->editMode = false;
        $this->editId = null;
        $this->reset(['physician_name', 'specialization', 'contact_number', 'email', 'section_id']);
        $this->resetErrorBag();
    }

    public function closeViewModal()
    {
        $this->showViewModal = false;
        $this->viewingPhysician = null;
        $this->editMode = false;
        $this->editId = null;
        $this->reset(['physician_name', 'specialization', 'contact_number', 'email', 'section_id']);
        $this->resetErrorBag();
    }

    public function delete($id)
    {
        $physician = Physician::find($id);
        if ($physician) {
            $physician->softDelete();
            $this->logActivity("Deleted physician ID {$id}: {$physician->physician_name}");
            $this->flashMessage = 'Physician deleted successfully!';
            $this->resetPage();
        }
    }

    public function deleteSelected($ids)
    {
        if (empty($ids)) return;
        
        $count = 0;
        foreach ($ids as $id) {
            $physician = Physician::find($id);
            if ($physician) {
                $physician->softDelete();
                $count++;
            }
        }
        $this->logActivity("Bulk deleted {$count} physician(s)");
        $this->flashMessage = $count . ' physician(s) deleted successfully!';
        $this->resetPage();
        $this->dispatch('selection-cleared');
    }

    public function with(): array
    {
        $query = Physician::active()->with('section')
            ->when($this->search, function ($query) {
                $query->where(function ($q) {
                    $q->where('physician_name', 'like', '%' . $this->search . '%')
                      ->orWhere('specialization', 'like', '%' . $this->search . '%')
                      ->orWhere('email', 'like', '%' . $this->search . '%')
                      ->orWhereHas('section', function($sq) {
                          $sq->where('label', 'like', '%' . $this->search . '%');
                      });
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

        $sections = Section::active()->orderBy('label')->get();

        return [
            'physicians' => $physicians,
            'specializations' => $specializations,
            'sections' => $sections
        ];
    }
};
?>

<div class="p-6 space-y-6" x-data="{ 
    selectedIds: [],
    selectAll: false,
    toggleAll(ids) {
        if (this.selectAll) {
            this.selectedIds = ids;
        } else {
            this.selectedIds = [];
        }
    },
    toggleOne(id) {
        const idx = this.selectedIds.indexOf(id);
        if (idx > -1) {
            this.selectedIds.splice(idx, 1);
        } else {
            this.selectedIds.push(id);
        }
    }
}" @selection-cleared.window="selectedIds = []; selectAll = false">
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
    <div class="bg-white rounded-lg shadow-sm">
        <div class="px-6 py-4 border-b border-gray-200 flex items-center justify-between">
            <h2 class="text-lg font-semibold text-gray-900">Add New Physician</h2>
            <button wire:click="toggleForm" type="button" class="flex items-center gap-2 px-4 py-2 text-sm font-medium rounded-md transition-colors {{ $showForm ? 'bg-gray-100 text-gray-700 hover:bg-gray-200' : 'bg-pink-600 text-white hover:bg-pink-700' }}">
                @if($showForm)
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                    <span>Close Form</span>
                @else
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                    </svg>
                    <span>Add New Physician</span>
                @endif
            </button>
        </div>
        @if($showForm)
        <form wire:submit.prevent="save" class="p-6">
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
                    <div x-data="{ val: $wire.entangle('contact_number'), get missing() { return this.val ? 11 - this.val.length : 0 } }">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Contact Number</label>
                        <input type="text" wire:model="contact_number" placeholder="09123456789" maxlength="11"
                               @input="val = $event.target.value = $event.target.value.replace(/[^0-9]/g, '').slice(0, 11)"
                               :class="val && val.length > 0 && val.length < 11 ? 'border-red-400 focus:ring-red-500' : 'border-gray-300 focus:ring-pink-500'"
                               class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:border-transparent">
                        <template x-if="val && val.length > 0 && val.length < 11">
                            <span class="text-red-500 text-xs mt-1 block" x-text="'You\'re missing ' + missing + (missing === 1 ? ' number' : ' numbers')"></span>
                        </template>
                        <p class="text-gray-400 text-xs mt-1">Must be exactly 11 digits (09 format)</p>
                        @error('contact_number') <span class="text-red-600 text-sm">{{ $message }}</span> @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Email</label>
                        <input type="email" wire:model="email" 
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-pink-500 focus:border-transparent">
                        @error('email') <span class="text-red-600 text-sm">{{ $message }}</span> @enderror
                    </div>
                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Assigned Section</label>
                        <select wire:model="section_id"
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-pink-500 focus:border-transparent">
                            <option value="">Select Section (Optional)</option>
                            @foreach($sections as $section)
                                <option value="{{ $section->section_id }}">{{ $section->label }}</option>
                            @endforeach
                        </select>
                        @error('section_id') <span class="text-red-600 text-sm">{{ $message }}</span> @enderror
                    </div>
                </div>
                <div class="flex justify-end mt-6">
                    <button type="submit" class="px-6 py-2 bg-pink-500 hover:bg-pink-600 text-white font-medium rounded-lg transition-colors">
                        Add Physician
                    </button>
                </div>
            </form>
        @endif
    </div>

    <!-- Search and Filters -->
    <div class="bg-white rounded-lg shadow-sm mb-6">
        <div class="p-6">
            <div class="grid grid-cols-1 md:grid-cols-12 gap-4">
                <div class="md:col-span-6">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Search Physicians</label>
                    <input type="text" wire:model.live="search" placeholder="Search by name, specialization, or email..." 
                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-pink-500">
                </div>
                <div class="md:col-span-3">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Specialization</label>
                    <select wire:model.live="filterSpecialization" 
                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-pink-500">
                        <option value="">All Specializations</option>
                        @foreach($specializations as $spec)
                            <option value="{{ $spec }}">{{ $spec }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="md:col-span-3">
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
            </div>
        </div>
    </div>

    <!-- Physicians List -->
    <div class="bg-white rounded-lg shadow-sm">
        <div class="px-6 py-4 border-b border-gray-200">
            <div class="flex items-center justify-between">
                <h2 class="text-lg font-semibold text-gray-900">Physicians Directory</h2>
                <!-- Delete Selected Button -->
                <div x-show="selectedIds.length > 0" x-cloak x-transition>
                    <button type="button" 
                            @click="if(confirm('Are you sure you want to delete ' + selectedIds.length + ' selected physician(s)?')) { $wire.deleteSelected(selectedIds) }"
                            class="inline-flex items-center gap-2 px-4 py-2 bg-red-500 hover:bg-red-600 text-white text-sm font-medium rounded-lg transition-colors">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                        </svg>
                        Delete Selected (<span x-text="selectedIds.length"></span>)
                    </button>
                </div>
            </div>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left w-10">
                            <input type="checkbox" x-model="selectAll" 
                                   @change="toggleAll([{{ $physicians instanceof \Illuminate\Pagination\LengthAwarePaginator ? $physicians->pluck('physician_id')->implode(',') : $physicians->pluck('physician_id')->implode(',') }}])"
                                   class="rounded border-gray-300 text-pink-600 focus:ring-pink-500 w-4 h-4">
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Name</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Section</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Specialization</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Contact</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Email</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($physicians as $physician)
                        <tr wire:key="physician-{{ $physician->physician_id }}" 
                            class="hover:bg-gray-50 cursor-pointer transition-colors"
                            wire:click="showDetails({{ $physician->physician_id }})">
                            <td class="px-6 py-4" wire:click.stop>
                                <input type="checkbox" value="{{ $physician->physician_id }}" 
                                       @change="toggleOne({{ $physician->physician_id }})"
                                       :checked="selectedIds.includes({{ $physician->physician_id }})"
                                       class="rounded border-gray-300 text-pink-600 focus:ring-pink-500 w-4 h-4">
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex items-center">
                                    <div class="w-8 h-8 bg-pink-500 rounded-full flex items-center justify-center text-white text-xs font-bold mr-3">
                                        {{ strtoupper(substr($physician->physician_name, 0, 2)) }}
                                    </div>
                                    <div>
                                        <div class="font-medium text-gray-900">{{ $physician->physician_name }}</div>
                                        <div class="text-xs text-gray-500">ID: {{ $physician->physician_id }}</div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm">
                                @if($physician->section)
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">{{ $physician->section->label }}</span>
                                @else
                                    <span class="text-gray-400">Unassigned</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $physician->specialization ?? 'N/A' }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $physician->contact_number ?? 'N/A' }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $physician->email ?? 'N/A' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-12 text-center text-gray-500">
                                No physicians found.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($perPage !== 'all' && $physicians instanceof \Illuminate\Pagination\LengthAwarePaginator && $physicians->hasPages())
            <div class="px-6 py-4 border-t border-gray-200">
                {{ $physicians->links() }}
            </div>
        @endif
    </div>

    <!-- View / Edit Physician Modal -->
    @if($showViewModal && $viewingPhysician)
    <div class="fixed inset-0 bg-black bg-opacity-50 overflow-y-auto h-full w-full z-50">
        <div class="relative top-20 mx-auto p-0 border w-full max-w-2xl shadow-lg rounded-lg bg-white">
            <!-- Modal Header -->
            <div class="px-6 py-4 border-b border-gray-200">
                <div class="flex items-center justify-between">
                    <h3 class="text-xl font-semibold text-gray-900">
                        {{ $editMode ? 'Edit Physician' : 'Physician Details' }}
                    </h3>
                    <button type="button" wire:click="closeViewModal" class="text-gray-400 hover:text-gray-600">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>
            </div>

            @if($editMode)
            <!-- Edit Mode -->
            <form wire:submit.prevent="save">
                <div class="p-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                Physician Name <span class="text-red-500">*</span>
                            </label>
                            <input type="text" wire:model="physician_name" 
                                   class="w-full px-3 py-2.5 border border-gray-300 rounded-md focus:outline-none focus:ring-1 focus:ring-pink-500 focus:border-pink-500">
                            @error('physician_name') <span class="text-red-500 text-sm mt-1 block">{{ $message }}</span> @enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Specialization</label>
                            <input type="text" wire:model="specialization" 
                                   class="w-full px-3 py-2.5 border border-gray-300 rounded-md focus:outline-none focus:ring-1 focus:ring-pink-500 focus:border-pink-500">
                            @error('specialization') <span class="text-red-500 text-sm mt-1 block">{{ $message }}</span> @enderror
                        </div>
                        <div x-data="{ val: $wire.entangle('contact_number'), get missing() { return this.val ? 11 - this.val.length : 0 } }">
                            <label class="block text-sm font-medium text-gray-700 mb-2">Contact Number</label>
                            <input type="text" wire:model="contact_number" placeholder="09123456789" maxlength="11"
                                   @input="val = $event.target.value = $event.target.value.replace(/[^0-9]/g, '').slice(0, 11)"
                                   :class="val && val.length > 0 && val.length < 11 ? 'border-red-400 focus:ring-red-500' : 'border-gray-300 focus:ring-pink-500'"
                                   class="w-full px-3 py-2.5 border rounded-md focus:outline-none focus:ring-1">
                            <template x-if="val && val.length > 0 && val.length < 11">
                                <span class="text-red-500 text-xs mt-1 block" x-text="'You\'re missing ' + missing + (missing === 1 ? ' number' : ' numbers')"></span>
                            </template>
                            <p class="text-gray-400 text-xs mt-1">Must be exactly 11 digits (09 format)</p>
                            @error('contact_number') <span class="text-red-500 text-sm mt-1 block">{{ $message }}</span> @enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Email</label>
                            <input type="email" wire:model="email" 
                                   class="w-full px-3 py-2.5 border border-gray-300 rounded-md focus:outline-none focus:ring-1 focus:ring-pink-500 focus:border-pink-500">
                            @error('email') <span class="text-red-500 text-sm mt-1 block">{{ $message }}</span> @enderror
                        </div>
                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium text-gray-700 mb-2">Assigned Section</label>
                            <select wire:model="section_id"
                                    class="w-full px-3 py-2.5 border border-gray-300 rounded-md focus:outline-none focus:ring-1 focus:ring-pink-500 focus:border-pink-500">
                                <option value="">Select Section (Optional)</option>
                                @foreach($sections as $section)
                                    <option value="{{ $section->section_id }}">{{ $section->label }}</option>
                                @endforeach
                            </select>
                            @error('section_id') <span class="text-red-500 text-sm mt-1 block">{{ $message }}</span> @enderror
                        </div>
                    </div>
                </div>
                <div class="px-6 py-4 bg-gray-50 border-t border-gray-200 flex justify-end space-x-3 rounded-b-lg">
                    <button type="button" wire:click="cancelEdit" 
                            class="px-5 py-2.5 border border-gray-300 rounded-md text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">
                        Cancel
                    </button>
                    <button type="submit" 
                            style="background-color: #DC143C;" 
                            class="px-5 py-2.5 text-white text-sm rounded-md font-medium hover:opacity-90 transition-opacity">
                        Update Physician
                    </button>
                </div>
            </form>
            @else
            <!-- View Mode (Read-only) -->
            <div class="p-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                    <div class="bg-gray-50 p-4 rounded-lg">
                        <p class="text-xs font-semibold text-gray-500 uppercase mb-1">Physician Name</p>
                        <p class="text-sm font-medium text-gray-900">{{ $viewingPhysician->physician_name }}</p>
                    </div>
                    <div class="bg-gray-50 p-4 rounded-lg">
                        <p class="text-xs font-semibold text-gray-500 uppercase mb-1">Assigned Section</p>
                        <p class="text-sm font-medium text-gray-900">
                            @if($viewingPhysician->section)
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">{{ $viewingPhysician->section->label }}</span>
                            @else
                                <span class="text-gray-400">Unassigned</span>
                            @endif
                        </p>
                    </div>
                    <div class="bg-gray-50 p-4 rounded-lg">
                        <p class="text-xs font-semibold text-gray-500 uppercase mb-1">Specialization</p>
                        <p class="text-sm font-medium text-gray-900">{{ $viewingPhysician->specialization ?? 'N/A' }}</p>
                    </div>
                    <div class="bg-gray-50 p-4 rounded-lg">
                        <p class="text-xs font-semibold text-gray-500 uppercase mb-1">Contact Number</p>
                        <p class="text-sm font-medium text-gray-900">{{ $viewingPhysician->contact_number ?? 'N/A' }}</p>
                    </div>
                    <div class="bg-gray-50 p-4 rounded-lg">
                        <p class="text-xs font-semibold text-gray-500 uppercase mb-1">Email</p>
                        <p class="text-sm font-medium text-gray-900">{{ $viewingPhysician->email ?? 'N/A' }}</p>
                    </div>
                </div>
            </div>
            <div class="px-6 py-4 bg-gray-50 border-t border-gray-200 flex justify-end space-x-3 rounded-b-lg">
                <button type="button" wire:click="closeViewModal" 
                        class="px-5 py-2.5 border border-gray-300 rounded-md text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">
                    Close
                </button>
                <button type="button" wire:click="enableEdit" 
                        style="background-color: #DC143C;"
                        class="px-5 py-2.5 text-white text-sm rounded-md font-medium hover:opacity-90 transition-opacity">
                    Edit
                </button>
            </div>
            @endif
        </div>
    </div>
    @endif
</div>