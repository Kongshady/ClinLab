<?php

use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Validate;
use App\Models\Patient;

new class extends Component
{
    use WithPagination;

    // Form properties with validation
    #[Validate('required|in:Internal,External')]
    public $patient_type = 'External';
    
    #[Validate('required|string|max:20')]
    public $firstname = '';
    
    #[Validate('nullable|string|max:20')]
    public $middlename = '';
    
    #[Validate('required|string|max:50')]
    public $lastname = '';
    
    #[Validate('required|date')]
    public $birthdate = '';
    
    #[Validate('required|string|max:10')]
    public $gender = '';
    
    #[Validate('nullable|numeric|digits:11')]
    public $contact_number = '';
    
    #[Validate('nullable|string|max:200')]
    public $address = '';

    // Search and filter properties
    public $search = '';
    public $filterType = '';
    public $filterGender = '';
    public $perPage = 'all';

    // Flash message
    public $flashMessage = '';

    // Edit mode
    public $editMode = false;
    public $editingPatientId = null;

    // View mode
    public $viewMode = false;
    public $viewingPatient = null;

    public function mount()
    {
        if (session('success')) {
            $this->flashMessage = session('success');
        }
    }

    public function save()
    {
        $this->validate();

        // Check for duplicate patient
        $duplicate = Patient::where('firstname', $this->firstname)
            ->where('lastname', $this->lastname)
            ->where('birthdate', $this->birthdate)
            ->where('is_deleted', 0)
            ->first();

        if ($duplicate) {
            $this->addError('duplicate', 'A patient with the same name and birthdate already exists.');
            return;
        }

        Patient::create([
            'patient_type' => $this->patient_type,
            'firstname' => $this->firstname,
            'middlename' => $this->middlename,
            'lastname' => $this->lastname,
            'birthdate' => $this->birthdate,
            'gender' => $this->gender,
            'contact_number' => $this->contact_number,
            'address' => $this->address,
        ]);

        $this->reset([
            'patient_type', 'firstname', 'middlename', 'lastname', 
            'birthdate', 'gender', 'contact_number', 'address'
        ]);
        
        $this->patient_type = 'External';
        $this->flashMessage = 'Patient added successfully.';
    }

    public function edit($id)
    {
        $patient = Patient::findOrFail($id);
        $this->editingPatientId = $id;
        $this->patient_type = $patient->patient_type;
        $this->firstname = $patient->firstname;
        $this->middlename = $patient->middlename;
        $this->lastname = $patient->lastname;
        $this->birthdate = $patient->birthdate;
        $this->gender = $patient->gender;
        $this->contact_number = $patient->contact_number;
        $this->address = $patient->address;
        $this->editMode = true;
    }

    public function update()
    {
        $this->validate();

        $patient = Patient::findOrFail($this->editingPatientId);
        $patient->update([
            'patient_type' => $this->patient_type,
            'firstname' => $this->firstname,
            'middlename' => $this->middlename,
            'lastname' => $this->lastname,
            'birthdate' => $this->birthdate,
            'gender' => $this->gender,
            'contact_number' => $this->contact_number,
            'address' => $this->address,
        ]);

        $this->cancelEdit();
        $this->flashMessage = 'Patient updated successfully.';
    }

    public function cancelEdit()
    {
        $this->reset([
            'patient_type', 'firstname', 'middlename', 'lastname', 
            'birthdate', 'gender', 'contact_number', 'address',
            'editMode', 'editingPatientId'
        ]);
        $this->patient_type = 'External';
    }

    public function viewPatient($id)
    {
        $this->viewingPatient = Patient::findOrFail($id);
        $this->viewMode = true;
    }

    public function closeView()
    {
        $this->viewMode = false;
        $this->viewingPatient = null;
    }

    public function delete($id)
    {
        $patient = Patient::findOrFail($id);
        $patient->softDelete();
        $this->flashMessage = 'Patient deleted successfully.';
    }

    public function with(): array
    {
        $query = Patient::active()
            ->when($this->search, function($query) {
                $query->where(function($q) {
                    $q->where('firstname', 'like', '%' . $this->search . '%')
                      ->orWhere('lastname', 'like', '%' . $this->search . '%')
                      ->orWhere('patient_id', 'like', '%' . $this->search . '%');
                });
            })
            ->when($this->filterType, function($query) {
                $query->where('patient_type', $this->filterType);
            })
            ->when($this->filterGender, function($query) {
                $query->where('gender', $this->filterGender);
            })
            ->orderBy('patient_id', 'desc');
            
        return [
            'patients' => $this->perPage === 'all' ? $query->get() : $query->paginate((int)$this->perPage)
        ];
    }
}; ?>

<div class="p-6">
    <!-- Page Header -->
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-gray-900 flex items-center">
            <svg class="w-7 h-7 mr-2 text-pink-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
            </svg>
            Patient Management
        </h1>
    </div>

    @if($flashMessage)
        <div class="mb-6 p-4 bg-green-50 border-l-4 border-green-500 rounded">
            <p class="text-green-800">{{ $flashMessage }}</p>
        </div>
    @endif

    @error('duplicate')
        <div class="mb-6 p-4 bg-red-50 border-l-4 border-red-500 rounded">
            <p class="text-red-800">{{ $message }}</p>
        </div>
    @enderror

    <!-- Add New Patient Card -->
    <div class="bg-white rounded-lg shadow-sm mb-6">
        <div class="px-6 py-4 border-b border-gray-200">
            <h2 class="text-lg font-semibold text-gray-900">Add New Patient</h2>
        </div>
        <form wire:submit.prevent="save" class="p-6">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4 mb-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Patient Type *</label>
                    <select wire:model="patient_type" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-pink-500" required>
                        <option value="">Select Type</option>
                        <option value="Internal">Internal</option>
                        <option value="External">External</option>
                    </select>
                    @error('patient_type') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">First Name *</label>
                    <input type="text" wire:model="firstname" placeholder="Juan" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-pink-500" required>
                    @error('firstname') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Middle Name</label>
                    <input type="text" wire:model="middlename" placeholder="Santos" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-pink-500">
                    @error('middlename') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Last Name *</label>
                    <input type="text" wire:model="lastname" placeholder="Dela Cruz" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-pink-500" required>
                    @error('lastname') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Date of Birth *</label>
                    <input type="date" wire:model="birthdate" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-pink-500" required>
                    @error('birthdate') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                </div>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Gender *</label>
                    <select wire:model="gender" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-pink-500" required>
                        <option value="">Select Gender</option>
                        <option value="Male">Male</option>
                        <option value="Female">Female</option>
                    </select>
                    @error('gender') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Contact Number</label>
                    <input type="text" wire:model="contact_number" placeholder="09123456789" maxlength="11" oninput="this.value = this.value.replace(/[^0-9]/g, '').slice(0, 11)" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-pink-500">
                    @error('contact_number') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                    <span class="text-xs text-gray-500 mt-1 block">11 digits only</span>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Address</label>
                    <input type="text" wire:model="address" placeholder="123 Street, City" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-pink-500">
                    @error('address') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                </div>
            </div>
            <div class="flex justify-end">
                <button type="submit" class="px-4 py-2 bg-pink-600 text-white rounded-md hover:bg-pink-700 transition-colors">
                    Add Patient
                </button>
            </div>
        </form>
    </div>

    <!-- Search and Filters Card -->
    <div class="bg-white rounded-lg shadow-sm mb-6">
        <div class="p-6">
            <div class="grid grid-cols-1 md:grid-cols-12 gap-4">
                <div class="md:col-span-6">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Search Patients</label>
                    <input type="text" wire:model.live="search" placeholder="Search by name or patient ID..." class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-pink-500">
                </div>
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Type</label>
                    <select wire:model.live="filterType" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-pink-500">
                        <option value="">All Types</option>
                        <option value="Internal">Internal</option>
                        <option value="External">External</option>
                    </select>
                </div>
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Gender</label>
                    <select wire:model.live="filterGender" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-pink-500">
                        <option value="">All Genders</option>
                        <option value="Male">Male</option>
                        <option value="Female">Female</option>
                    </select>
                </div>
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Rows per page</label>
                    <select wire:model.live="perPage" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-pink-500">
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

    <!-- Patients List Card -->
    <div class="bg-white rounded-lg shadow-sm">
        <div class="px-6 py-4 border-b border-gray-200">
            <h2 class="text-lg font-semibold text-gray-900">Patients Directory</h2>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Type</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Full Name</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Birthdate</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Gender</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Contact</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($patients as $patient)
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full {{ $patient->patient_type == 'Internal' ? 'bg-blue-100 text-blue-800' : 'bg-green-100 text-green-800' }}">
                                    {{ $patient->patient_type }}
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex items-center">
                                    <div class="w-8 h-8 bg-pink-500 rounded-full flex items-center justify-center text-white text-xs font-bold mr-3">
                                        {{ strtoupper(substr($patient->firstname, 0, 1) . substr($patient->lastname, 0, 1)) }}
                                    </div>
                                    <div>
                                        <div class="font-medium text-gray-900">{{ $patient->full_name }}</div>
                                        <div class="text-xs text-gray-500">ID: {{ $patient->patient_id }}</div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                {{ \Carbon\Carbon::parse($patient->birthdate)->format('M d, Y') }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                {{ $patient->gender }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                {{ $patient->contact_number ?: 'N/A' }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                <div class="flex space-x-2">
                                    <button wire:click="viewPatient({{ $patient->patient_id }})" 
                                            class="px-3 py-1 bg-blue-100 text-blue-700 rounded-lg hover:bg-blue-200 transition-colors">
                                        View
                                    </button>
                                    <button wire:click="edit({{ $patient->patient_id }})" 
                                            class="px-3 py-1 bg-orange-100 text-orange-700 rounded-lg hover:bg-orange-200 transition-colors">
                                        Edit
                                    </button>
                                    <button wire:click="delete({{ $patient->patient_id }})" 
                                            wire:confirm="Are you sure you want to delete this patient?"
                                            class="px-3 py-1 bg-red-100 text-red-700 rounded-lg hover:bg-red-200 transition-colors">
                                        Delete
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-12 text-center text-gray-500">
                                No patients found
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        
        @if($perPage !== 'all' && $patients->hasPages())
            <div class="px-6 py-4 border-t border-gray-200">
                {{ $patients->links() }}
            </div>
        @endif
    </div>

    <!-- Edit Patient Modal -->
    @if($editMode)
    <div class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50 p-4">
        <div class="relative top-10 mx-auto p-6 border w-full max-w-4xl shadow-lg rounded-xl bg-white">
            <div class="flex items-center justify-between mb-6">
                <h3 class="text-2xl font-bold text-gray-900">Edit Patient</h3>
                <button wire:click="cancelEdit" class="text-gray-400 hover:text-gray-600">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>
            
            <form wire:submit.prevent="update">
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4 mb-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Patient Type *</label>
                        <select wire:model="patient_type" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-pink-500" required>
                            <option value="">Select Type</option>
                            <option value="Internal">Internal</option>
                            <option value="External">External</option>
                        </select>
                        @error('patient_type') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">First Name *</label>
                        <input type="text" wire:model="firstname" placeholder="Juan" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-pink-500" required>
                        @error('firstname') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Middle Name</label>
                        <input type="text" wire:model="middlename" placeholder="Santos" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-pink-500">
                        @error('middlename') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Last Name *</label>
                        <input type="text" wire:model="lastname" placeholder="Dela Cruz" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-pink-500" required>
                        @error('lastname') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Date of Birth *</label>
                        <input type="date" wire:model="birthdate" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-pink-500" required>
                        @error('birthdate') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                    </div>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Gender *</label>
                        <select wire:model="gender" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-pink-500" required>
                            <option value="">Select Gender</option>
                            <option value="Male">Male</option>
                            <option value="Female">Female</option>
                        </select>
                        @error('gender') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Contact Number</label>
                        <input type="text" wire:model="contact_number" placeholder="09123456789" maxlength="11" oninput="this.value = this.value.replace(/[^0-9]/g, '').slice(0, 11)" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-pink-500">
                        @error('contact_number') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                        <span class="text-xs text-gray-500 mt-1 block">11 digits only</span>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Address</label>
                        <input type="text" wire:model="address" placeholder="123 Street, City" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-pink-500">
                        @error('address') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                    </div>
                </div>
                <div class="flex justify-end space-x-3 pt-4 border-t">
                    <button type="button" 
                            wire:click="cancelEdit"
                            class="px-6 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition-colors">
                        Cancel
                    </button>
                    <button type="submit" class="px-6 py-2 bg-pink-600 text-white rounded-lg hover:bg-pink-700 transition-colors">
                        Update Patient
                    </button>
                </div>
            </form>
        </div>
    </div>
    @endif

    <!-- View Patient Modal -->
    @if($viewMode && $viewingPatient)
    <div class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50 p-4">
        <div class="relative top-20 mx-auto p-6 border w-full max-w-2xl shadow-lg rounded-xl bg-white">
            <div class="flex items-center justify-between mb-6">
                <h3 class="text-2xl font-bold text-gray-900">Patient Details</h3>
                <button wire:click="closeView" class="text-gray-400 hover:text-gray-600">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>
            
            <div class="space-y-4">
                <div class="flex items-center mb-6">
                    <div class="w-16 h-16 bg-gradient-to-br from-pink-500 to-purple-500 rounded-full flex items-center justify-center text-white text-2xl font-bold mr-4">
                        {{ strtoupper(substr($viewingPatient->firstname, 0, 1) . substr($viewingPatient->lastname, 0, 1)) }}
                    </div>
                    <div>
                        <h4 class="text-xl font-bold text-gray-900">{{ $viewingPatient->full_name }}</h4>
                        <p class="text-sm text-gray-500">Patient ID: {{ $viewingPatient->patient_id }}</p>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div class="bg-gray-50 p-4 rounded-lg">
                        <p class="text-xs font-semibold text-gray-500 uppercase mb-1">Patient Type</p>
                        <p class="text-sm font-medium text-gray-900">
                            <span class="px-2 py-1 rounded-full text-xs {{ $viewingPatient->patient_type == 'Internal' ? 'bg-blue-100 text-blue-800' : 'bg-green-100 text-green-800' }}">
                                {{ $viewingPatient->patient_type }}
                            </span>
                        </p>
                    </div>
                    <div class="bg-gray-50 p-4 rounded-lg">
                        <p class="text-xs font-semibold text-gray-500 uppercase mb-1">Gender</p>
                        <p class="text-sm font-medium text-gray-900">{{ $viewingPatient->gender }}</p>
                    </div>
                    <div class="bg-gray-50 p-4 rounded-lg">
                        <p class="text-xs font-semibold text-gray-500 uppercase mb-1">Birthdate</p>
                        <p class="text-sm font-medium text-gray-900">{{ \Carbon\Carbon::parse($viewingPatient->birthdate)->format('M d, Y') }}</p>
                    </div>
                    <div class="bg-gray-50 p-4 rounded-lg">
                        <p class="text-xs font-semibold text-gray-500 uppercase mb-1">Age</p>
                        <p class="text-sm font-medium text-gray-900">{{ \Carbon\Carbon::parse($viewingPatient->birthdate)->age }} years old</p>
                    </div>
                    <div class="bg-gray-50 p-4 rounded-lg col-span-2">
                        <p class="text-xs font-semibold text-gray-500 uppercase mb-1">Contact Number</p>
                        <p class="text-sm font-medium text-gray-900">{{ $viewingPatient->contact_number ?: 'N/A' }}</p>
                    </div>
                    <div class="bg-gray-50 p-4 rounded-lg col-span-2">
                        <p class="text-xs font-semibold text-gray-500 uppercase mb-1">Address</p>
                        <p class="text-sm font-medium text-gray-900">{{ $viewingPatient->address ?: 'N/A' }}</p>
                    </div>
                    <div class="bg-gray-50 p-4 rounded-lg">
                        <p class="text-xs font-semibold text-gray-500 uppercase mb-1">Date Added</p>
                        <p class="text-sm font-medium text-gray-900">{{ \Carbon\Carbon::parse($viewingPatient->datetime_added)->format('M d, Y h:i A') }}</p>
                    </div>
                    <div class="bg-gray-50 p-4 rounded-lg">
                        <p class="text-xs font-semibold text-gray-500 uppercase mb-1">Status</p>
                        <p class="text-sm font-medium text-green-600">Active</p>
                    </div>
                </div>

                <div class="flex justify-end pt-4 border-t mt-6">
                    <button wire:click="closeView" class="px-6 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition-colors">
                        Close
                    </button>
                </div>
            </div>
        </div>
    </div>
    @endif
</div>