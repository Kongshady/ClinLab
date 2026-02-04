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
    
    #[Validate('nullable|string|max:20')]
    public $contact_number = '';
    
    #[Validate('nullable|string|max:200')]
    public $address = '';

    // Search and filter properties
    public $search = '';
    public $filterType = '';
    public $filterGender = '';

    // Flash message
    public $flashMessage = '';

    public function mount()
    {
        if (session('success')) {
            $this->flashMessage = session('success');
        }
    }

    public function save()
    {
        $this->validate();

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

    public function delete($id)
    {
        $patient = Patient::findOrFail($id);
        $patient->delete();
        $this->flashMessage = 'Patient deleted successfully.';
    }

    public function with(): array
    {
        return [
            'patients' => Patient::active()
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
                ->orderBy('patient_id', 'desc')
                ->paginate(50)
        ];
    }
}; ?>

<div class="p-8">
    <!-- Page Header -->
    <div class="mb-8">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-3xl font-bold text-slate-900 flex items-center">
                    <div class="w-10 h-10 mr-3 bg-gradient-to-br from-blue-500 to-cyan-400 rounded-xl flex items-center justify-center shadow-lg">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                        </svg>
                    </div>
                    Patient Management
                </h1>
                <p class="mt-2 text-sm text-slate-600">Manage patient profiles and information</p>
            </div>
            <div class="flex items-center space-x-3">
                <button class="btn-secondary flex items-center">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                    </svg>
                    Export
                </button>
                <button class="btn-primary flex items-center">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                    </svg>
                    Quick Add
                </button>
            </div>
        </div>
    </div>

    @if($flashMessage)
        <div class="mb-6 p-4 bg-emerald-50 border-l-4 border-emerald-500 rounded-r-lg shadow-sm animate-fade-in">
            <div class="flex items-center">
                <svg class="w-5 h-5 text-emerald-500 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <span class="text-emerald-800 font-medium">{{ $flashMessage }}</span>
            </div>
        </div>
    @endif

    <!-- Add New Patient Card -->
    <div class="card card-hover mb-8 overflow-hidden">
        <div class="px-6 py-5 border-b border-slate-200 bg-gradient-to-r from-slate-50 to-white">
            <h2 class="text-lg font-semibold text-slate-900 flex items-center">
                <svg class="w-5 h-5 mr-2 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"/>
                </svg>
                Add New Patient
            </h2>
        </div>
        <form wire:submit.prevent="save" class="p-6">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-5 mb-6">
                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-2">Patient Type *</label>
                    <select wire:model="patient_type" class="input-field" required>
                        <option value="">Select Type</option>
                        <option value="Internal">Internal</option>
                        <option value="External">External</option>
                    </select>
                    @error('patient_type') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                </div>
                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-2">First Name *</label>
                    <input type="text" wire:model="firstname" placeholder="Juan" class="input-field" required>
                    @error('firstname') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                </div>
                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-2">Middle Name</label>
                    <input type="text" wire:model="middlename" placeholder="Santos" class="input-field">
                    @error('middlename') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                </div>
                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-2">Last Name *</label>
                    <input type="text" wire:model="lastname" placeholder="Dela Cruz" class="input-field" required>
                    @error('lastname') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                </div>
                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-2">Date of Birth *</label>
                    <input type="date" wire:model="birthdate" class="input-field" required>
                    @error('birthdate') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                </div>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-5 mb-6">
                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-2">Gender *</label>
                    <select wire:model="gender" class="input-field" required>
                        <option value="">Select Gender</option>
                        <option value="Male">Male</option>
                        <option value="Female">Female</option>
                    </select>
                    @error('gender') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                </div>
                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-2">Contact Number</label>
                    <input type="text" wire:model="contact_number" placeholder="09123456789" class="input-field">
                    @error('contact_number') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                </div>
                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-2">Address</label>
                    <input type="text" wire:model="address" placeholder="123 Street, City" class="input-field">
                    @error('address') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                </div>
            </div>
            <div class="flex justify-end">
                <button type="submit" class="btn-primary flex items-center">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                    </svg>
                    Add Patient
                </button>
            </div>
        </form>
    </div>

    <!-- Search and Filters Card -->
    <div class="card card-hover mb-8">
        <div class="p-6">
            <div class="grid grid-cols-1 md:grid-cols-12 gap-4">
                <div class="md:col-span-6">
                    <label class="block text-sm font-semibold text-slate-700 mb-2">Search Patients</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <svg class="w-5 h-5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                            </svg>
                        </div>
                        <input type="text" wire:model.live="search" placeholder="Search by name or patient ID..." class="input-field pl-10">
                    </div>
                </div>
                <div class="md:col-span-2">
                    <label class="block text-sm font-semibold text-slate-700 mb-2">Type</label>
                    <select wire:model.live="filterType" class="input-field">
                        <option value="">All Types</option>
                        <option value="Internal">Internal</option>
                        <option value="External">External</option>
                    </select>
                </div>
                <div class="md:col-span-2">
                    <label class="block text-sm font-semibold text-slate-700 mb-2">Gender</label>
                    <select wire:model.live="filterGender" class="input-field">
                        <option value="">All Genders</option>
                        <option value="Male">Male</option>
                        <option value="Female">Female</option>
                    </select>
                </div>
                <div class="md:col-span-2 flex items-end">
                    <button class="btn-secondary w-full flex items-center justify-center">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4"/>
                        </svg>
                        Clear Filters
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Patients List Card -->
    <div class="card">
        <div class="px-6 py-5 border-b border-slate-200 bg-gradient-to-r from-slate-50 to-white">
            <div class="flex items-center justify-between">
                <h2 class="text-lg font-semibold text-slate-900 flex items-center">
                    <svg class="w-5 h-5 mr-2 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
                    </svg>
                    Patients Directory
                </h2>
                <span class="text-sm text-slate-600">{{ $patients->total() }} total</span>
            </div>
        </div>
        <div class="overflow-x-auto">
            <table class="table">
                <thead class="table-header">
                    <tr>
                        <th class="table-th">Type</th>
                        <th class="table-th">Full Name</th>
                        <th class="table-th">Birthdate</th>
                        <th class="table-th">Gender</th>
                        <th class="table-th">Contact</th>
                        <th class="table-th">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200">
                    @forelse($patients as $patient)
                        <tr class="table-row">
                            <td class="table-td">
                                <span class="badge {{ $patient->patient_type == 'Internal' ? 'badge-blue' : 'badge-green' }}">
                                    {{ $patient->patient_type }}
                                </span>
                            </td>
                            <td class="table-td">
                                <div class="flex items-center">
                                    <div class="w-8 h-8 bg-gradient-to-br from-blue-500 to-cyan-400 rounded-lg flex items-center justify-center text-white text-xs font-bold mr-3">
                                        {{ strtoupper(substr($patient->firstname, 0, 1) . substr($patient->lastname, 0, 1)) }}
                                    </div>
                                    <div>
                                        <div class="font-semibold text-slate-900">{{ $patient->full_name }}</div>
                                        <div class="text-xs text-slate-500">ID: {{ $patient->patient_id }}</div>
                                    </div>
                                </div>
                            </td>
                            <td class="table-td text-slate-600">{{ \Carbon\Carbon::parse($patient->birthdate)->format('M d, Y') }}</td>
                            <td class="table-td">
                                <span class="inline-flex items-center">
                                    @if($patient->gender == 'Male')
                                        <svg class="w-4 h-4 mr-1 text-blue-500" fill="currentColor" viewBox="0 0 20 20">
                                            <path d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z"/>
                                        </svg>
                                    @else
                                        <svg class="w-4 h-4 mr-1 text-pink-500" fill="currentColor" viewBox="0 0 20 20">
                                            <path d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z"/>
                                        </svg>
                                    @endif
                                    {{ $patient->gender }}
                                </span>
                            </td>
                            <td class="table-td text-slate-600">{{ $patient->contact_number ?: 'N/A' }}</td>
                            <td class="table-td">
                                <div class="flex items-center space-x-2">
                                    <a href="{{ route('patients.show', $patient->patient_id) }}" 
                                       class="inline-flex items-center px-3 py-1.5 bg-blue-50 text-blue-700 text-xs font-medium rounded-lg hover:bg-blue-100 transition-colors">
                                        <svg class="w-3.5 h-3.5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                        </svg>
                                        View
                                    </a>
                                    <a href="{{ route('patients.edit', $patient->patient_id) }}" 
                                       class="inline-flex items-center px-3 py-1.5 bg-amber-50 text-amber-700 text-xs font-medium rounded-lg hover:bg-amber-100 transition-colors">
                                        <svg class="w-3.5 h-3.5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                        </svg>
                                        Edit
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-12 text-center">
                                <svg class="mx-auto h-12 w-12 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"/>
                                </svg>
                                <p class="mt-4 text-sm text-slate-600 font-medium">No patients found</p>
                                <p class="text-sm text-slate-500">Try adjusting your search or filter to find what you're looking for.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        
        @if($patients->hasPages())
            <div class="px-6 py-4 border-t border-slate-200 bg-slate-50">
                {{ $patients->links() }}
            </div>
        @endif
    </div>
</div>