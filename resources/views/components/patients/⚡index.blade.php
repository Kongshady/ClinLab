<?php

use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Validate;
use App\Models\Patient;
use App\Models\LabResult;
use App\Models\UicDirectoryPerson;
use App\Traits\LogsActivity;

new class extends Component
{
    use WithPagination, LogsActivity;

    // Form properties with validation
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

    #[Validate('required|email|max:100')]
    public $email = '';

    public $patient_type = 'External';

    // Search and filter properties
    public $search = '';
    public $filterGender = '';
    public $filterType = 'all'; // Tab filter: all, Internal, External
    public $sortBy = 'recent'; // Default to recently added
    public $perPage = 10;

    // Flash message
    public $flashMessage = '';

    // Edit mode
    public $editMode = false;
    public $editingPatientId = null;

    // View mode
    public $viewMode = false;
    public $viewingPatient = null;

    // Modal inline edit
    public $modalEditMode = false;
    public $modalFirstname = '';
    public $modalMiddlename = '';
    public $modalLastname = '';
    public $modalBirthdate = '';
    public $modalGender = '';
    public $modalContact = '';
    public $modalAddress = '';
    public $modalEmail = '';
    public $modalPatientType = '';

    // Form visibility toggle
    public $showForm = false;

    // UIC Directory lookup
    public $uicSearch = '';
    public $uicResults = [];
    public $selectedUicPerson = null;

    public function searchUicDirectory()
    {
        if (strlen($this->uicSearch) < 2) {
            $this->uicResults = [];
            return;
        }
        $this->uicResults = UicDirectoryPerson::search($this->uicSearch)
            ->take(10)
            ->get()
            ->toArray();
    }

    public function selectUicPerson($personId)
    {
        $person = UicDirectoryPerson::find($personId);
        if (!$person) return;

        $this->selectedUicPerson = $person->toArray();
        $this->firstname = $person->first_name;
        $this->middlename = $person->middle_name ?? '';
        $this->lastname = $person->last_name;
        $this->email = $person->email ?? '';
        $this->birthdate = $person->birth_date ? $person->birth_date->format('Y-m-d') : '';
        $this->gender = $person->gender ?? '';
        $this->address = $person->home_address ?? '';
        $this->patient_type = 'Internal';
        $this->uicResults = [];
        $this->showForm = true;
    }

    public function clearUicSelection()
    {
        $this->selectedUicPerson = null;
        $this->uicSearch = '';
        $this->uicResults = [];
    }

    public function mount()
    {
        if (session('success')) {
            $this->flashMessage = session('success');
        }
    }

    public function toggleForm()
    {
        $this->showForm = !$this->showForm;
        
        // Reset form when hiding
        if (!$this->showForm) {
            $this->reset([
                'firstname', 'middlename', 'lastname', 
                'birthdate', 'gender', 'contact_number', 'address',
                'email', 'patient_type', 'selectedUicPerson', 'uicSearch', 'uicResults'
            ]);
            $this->resetErrorBag();
        }
    }

    public function toggleSort()
    {
        $sorts = ['recent', 'name_asc', 'name_desc'];
        $currentIndex = array_search($this->sortBy, $sorts);
        $nextIndex = ($currentIndex + 1) % count($sorts);
        $this->sortBy = $sorts[$nextIndex];
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
            'email' => $this->email,
            'external_ref_id' => $this->selectedUicPerson['external_ref_id'] ?? null,
        ]);

        $this->reset([
            'firstname', 'middlename', 'lastname', 
            'birthdate', 'gender', 'contact_number', 'address',
            'email', 'patient_type', 'selectedUicPerson', 'uicSearch', 'uicResults'
        ]);
        
        $this->logActivity("Created patient: {$this->firstname} {$this->lastname}");
        $this->flashMessage = 'Patient added successfully.';
        $this->dispatch('patient-saved');
        $this->resetPage(); // Reset pagination to show new patient
    }

    public function edit($id)
    {
        $patient = Patient::findOrFail($id);
        $this->editingPatientId = $id;
        $this->firstname = $patient->firstname;
        $this->middlename = $patient->middlename;
        $this->lastname = $patient->lastname;
        $this->birthdate = $patient->birthdate ? \Carbon\Carbon::parse($patient->birthdate)->format('Y-m-d') : '';
        $this->gender = $patient->gender;
        $this->contact_number = $patient->contact_number;
        $this->address = $patient->address;
        $this->email = $patient->email ?? '';
        $this->patient_type = $patient->patient_type ?? 'External';
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
            'email' => $this->email,
        ]);

        $this->cancelEdit();
        $this->logActivity("Updated patient ID {$patient->patient_id}: {$this->firstname} {$this->lastname}");
        $this->flashMessage = 'Patient updated successfully.';
        $this->dispatch('patient-saved');
    }

    public function cancelEdit()
    {
        $this->reset([
            'firstname', 'middlename', 'lastname', 
            'birthdate', 'gender', 'contact_number', 'address',
            'email', 'patient_type',
            'editMode', 'editingPatientId'
        ]);
    }

    public $patientLabResults = [];

    public function viewPatient($id)
    {
        $this->viewingPatient = Patient::findOrFail($id);
        $this->patientLabResults = LabResult::with(['test', 'performedBy', 'verifiedBy'])
            ->where('patient_id', $id)
            ->orderBy('result_date', 'desc')
            ->get();
        $this->viewMode = true;
    }

    public function closeView()
    {
        $this->viewMode = false;
        $this->viewingPatient = null;
        $this->patientLabResults = [];
        $this->modalEditMode = false;
        $this->resetModalEditFields();
    }

    public function startModalEdit()
    {
        if (!$this->viewingPatient) return;
        $this->modalFirstname = $this->viewingPatient->firstname;
        $this->modalMiddlename = $this->viewingPatient->middlename ?? '';
        $this->modalLastname = $this->viewingPatient->lastname;
        $this->modalBirthdate = $this->viewingPatient->birthdate ? \Carbon\Carbon::parse($this->viewingPatient->birthdate)->format('Y-m-d') : '';
        $this->modalGender = $this->viewingPatient->gender;
        $this->modalContact = $this->viewingPatient->contact_number ?? '';
        $this->modalAddress = $this->viewingPatient->address ?? '';
        $this->modalEmail = $this->viewingPatient->email ?? '';
        $this->modalPatientType = $this->viewingPatient->patient_type ?? 'External';
        $this->modalEditMode = true;
    }

    public function cancelModalEdit()
    {
        $this->modalEditMode = false;
        $this->resetModalEditFields();
        $this->resetErrorBag();
    }

    public function saveModalEdit()
    {
        $this->validate([
            'modalFirstname' => 'required|string|max:20',
            'modalMiddlename' => 'nullable|string|max:20',
            'modalLastname' => 'required|string|max:50',
            'modalBirthdate' => 'required|date',
            'modalGender' => 'required|string|max:10',
            'modalContact' => 'nullable|numeric|digits:11',
            'modalAddress' => 'nullable|string|max:200',
            'modalEmail' => 'required|email|max:100',
            'modalPatientType' => 'required|in:Internal,External',
        ]);

        $patient = Patient::findOrFail($this->viewingPatient->patient_id);
        $patient->update([
            'patient_type' => $this->modalPatientType,
            'firstname' => $this->modalFirstname,
            'middlename' => $this->modalMiddlename ?: null,
            'lastname' => $this->modalLastname,
            'birthdate' => $this->modalBirthdate,
            'gender' => $this->modalGender,
            'contact_number' => $this->modalContact ?: null,
            'address' => $this->modalAddress ?: null,
            'email' => $this->modalEmail,
        ]);

        $this->logActivity("Updated patient ID {$patient->patient_id}: {$this->modalFirstname} {$this->modalLastname}");
        $this->flashMessage = 'Patient updated successfully.';
        $this->dispatch('patient-saved');

        // Refresh the viewing patient data
        $this->viewingPatient = Patient::findOrFail($patient->patient_id);
        $this->modalEditMode = false;
        $this->resetModalEditFields();
    }

    protected function resetModalEditFields()
    {
        $this->modalFirstname = '';
        $this->modalMiddlename = '';
        $this->modalLastname = '';
        $this->modalBirthdate = '';
        $this->modalGender = '';
        $this->modalContact = '';
        $this->modalAddress = '';
        $this->modalEmail = '';
        $this->modalPatientType = '';
    }

    public function delete($id)
    {
        $patient = Patient::findOrFail($id);
        $patient->softDelete();
        $this->logActivity("Deleted patient ID {$id}: {$patient->firstname} {$patient->lastname}");
        $this->flashMessage = 'Patient deleted successfully.';
        $this->dispatch('patient-saved');
        $this->resetPage();
    }

    public function deleteSelected($ids)
    {
        if (empty($ids)) return;

        $count = 0;
        foreach ($ids as $id) {
            $patient = Patient::find($id);
            if ($patient) {
                $patient->softDelete();
                $count++;
            }
        }
        $this->logActivity("Bulk deleted {$count} patient(s)");
        $this->flashMessage = $count . ' patient(s) deleted successfully!';
        $this->dispatch('patient-saved');
        $this->resetPage();
        $this->dispatch('selection-cleared');
    }

    public function with(): array
    {
        $query = Patient::active()
            ->when($this->search, function($query) {
                $query->where(function($q) {
                    $q->where('firstname', 'like', '%' . $this->search . '%')
                      ->orWhere('lastname', 'like', '%' . $this->search . '%')
                      ->orWhere('patient_id', 'like', '%' . $this->search . '%')
                      ->orWhere('email', 'like', '%' . $this->search . '%');
                });
            })
            ->when($this->filterGender, function($query) {
                $query->where('gender', $this->filterGender);
            })
            ->when($this->filterType !== 'all', function($query) {
                $query->where('patient_type', $this->filterType);
            })
            ->when($this->sortBy === 'name_asc', function($query) {
                $query->orderBy('firstname')->orderBy('lastname');
            })
            ->when($this->sortBy === 'name_desc', function($query) {
                $query->orderBy('firstname', 'desc')->orderBy('lastname', 'desc');
            })
            ->when($this->sortBy === 'recent', function($query) {
                $query->orderBy('patient_id', 'desc');
            });
            
        return [
            'patients' => $this->perPage === 'all' ? $query->get() : $query->paginate((int)$this->perPage)
        ];
    }
}; ?>

<div class="p-6" x-data="{ 
    selectedIds: [],
    selectAll: false,
    showToast: false,
    toastTimeout: null,
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
    },
    showToastMessage() {
        this.showToast = true;
        if (this.toastTimeout) {
            clearTimeout(this.toastTimeout);
        }
        this.toastTimeout = setTimeout(() => {
            this.showToast = false;
        }, 3000);
    }
}" @selection-cleared.window="selectedIds = []; selectAll = false" x-init="$wire.on('patient-saved', () => showToastMessage())">
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
        <div class="mb-6 p-4 bg-green-50 border-l-4 border-green-500 rounded" x-show="showToast" x-transition>
            <p class="text-green-800">{{ $flashMessage }}</p>
        </div>
    @endif

    @error('duplicate')
        <div class="mb-6 p-4 bg-red-50 border-l-4 border-red-500 rounded">
            <p class="text-red-800">{{ $message }}</p>
        </div>
    @enderror

    <!-- Add New Patient Card -->
    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm mb-6">

        {{-- Card Header --}}
        <div class="px-6 py-5 flex items-center justify-between">
            <div class="flex items-center gap-4">
                <div class="w-10 h-10 rounded-2xl bg-red-50 flex items-center justify-center flex-shrink-0">
                    <svg class="w-5 h-5 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                    </svg>
                </div>
                <div>
                    <h2 class="text-base font-bold text-gray-900 leading-tight">Add New Patient</h2>
                    <p class="text-xs text-gray-400 mt-0.5">Fill in patient details or search the UIC directory</p>
                </div>
            </div>
            <button wire:click="toggleForm" type="button"
                    class="inline-flex items-center gap-2 px-4 py-2 border rounded-xl text-sm font-medium transition-colors
                        {{ $showForm ? 'border-gray-200 text-gray-600 hover:bg-gray-50' : 'border-red-500 bg-red-500 text-white hover:bg-red-600' }}">
                @if($showForm)
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                    Close Form
                @else
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                    </svg>
                    Add New Patient
                @endif
            </button>
        </div>

        @if($showForm)

        {{-- UIC Directory Search --}}
        <div class="mx-6 mb-2 rounded-xl bg-red-50 border border-red-100 px-5 py-4">
            <p class="text-[10px] font-bold text-red-500 uppercase tracking-widest mb-3 flex items-center gap-1.5">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                </svg>
                Search UIC Directory
            </p>
            @if($selectedUicPerson)
                <div class="flex items-center justify-between bg-white rounded-xl border border-red-200 px-4 py-2.5">
                    <div>
                        <span class="text-sm font-semibold text-gray-900">{{ $selectedUicPerson['first_name'] }} {{ $selectedUicPerson['middle_name'] ?? '' }} {{ $selectedUicPerson['last_name'] }}</span>
                        <span class="ml-2 inline-flex items-center px-2 py-0.5 rounded-md text-xs font-medium {{ $selectedUicPerson['type'] === 'Student' ? 'bg-blue-100 text-blue-700' : 'bg-green-100 text-green-700' }}">
                            {{ $selectedUicPerson['type'] }}
                        </span>
                        @if($selectedUicPerson['email'])
                            <span class="ml-2 text-xs text-gray-400">{{ $selectedUicPerson['email'] }}</span>
                        @endif
                    </div>
                    <button type="button" wire:click="clearUicSelection" class="text-xs font-semibold text-red-500 hover:text-red-700 transition-colors">
                        Clear
                    </button>
                </div>
            @else
                <div class="relative" x-data="{ open: false }" @click.outside="open = false">
                    <div class="relative">
                        <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                        </svg>
                        <input type="text" wire:model.live.debounce.300ms="uicSearch" wire:keyup="searchUicDirectory"
                               @focus="open = true"
                               placeholder="Search by name, email, or ID number..."
                               class="w-full pl-9 pr-4 py-2.5 bg-white border border-gray-200 rounded-xl text-sm placeholder-gray-300 focus:outline-none focus:ring-2 focus:ring-red-400 focus:border-transparent transition-colors">
                    </div>
                    @if(count($uicResults) > 0)
                        <div class="absolute z-20 w-full mt-1 bg-white border border-gray-200 rounded-xl shadow-lg max-h-60 overflow-y-auto" x-show="open">
                            @foreach($uicResults as $person)
                                <button type="button" wire:click="selectUicPerson({{ $person['id'] }})"
                                        class="w-full text-left px-4 py-2.5 hover:bg-red-50 border-b border-gray-50 last:border-0 flex items-center justify-between transition-colors">
                                    <div>
                                        <span class="text-sm font-semibold text-gray-900">{{ $person['first_name'] }} {{ $person['middle_name'] ?? '' }} {{ $person['last_name'] }}</span>
                                        @if($person['email'])
                                            <span class="block text-xs text-gray-400">{{ $person['email'] }}</span>
                                        @endif
                                    </div>
                                    <div class="flex items-center gap-2">
                                        @if($person['department_or_course'])
                                            <span class="text-xs text-gray-400">{{ $person['department_or_course'] }}</span>
                                        @endif
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-md text-xs font-medium {{ $person['type'] === 'Student' ? 'bg-blue-100 text-blue-700' : 'bg-green-100 text-green-700' }}">
                                            {{ $person['type'] }}
                                        </span>
                                    </div>
                                </button>
                            @endforeach
                        </div>
                    @elseif(strlen($uicSearch) >= 2 && count($uicResults) === 0)
                        <div class="absolute z-20 w-full mt-1 bg-white border border-gray-200 rounded-xl shadow-lg" x-show="open">
                            <div class="px-4 py-3 text-sm text-gray-400 text-center">No UIC records found. Enter details manually below.</div>
                        </div>
                    @endif
                </div>
            @endif
            <p class="text-xs text-red-400 mt-2 flex items-center gap-1">
                <svg class="w-3.5 h-3.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                Search the local UIC directory to auto-fill patient details, or enter manually below.
            </p>
        </div>

        {{-- Form --}}
        <form wire:submit.prevent="save" class="px-6 pt-4 pb-6">

            {{-- PERSONAL INFORMATION divider --}}
            <div class="flex items-center gap-3 mb-4">
                <div class="flex-1 h-px bg-gray-100"></div>
                <span class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">Personal Information</span>
                <div class="flex-1 h-px bg-gray-100"></div>
            </div>

            <div class="grid grid-cols-4 gap-4 mb-5">
                <div>
                    <label class="block text-[10px] font-semibold text-gray-400 uppercase tracking-widest mb-1.5">
                        First Name <span class="text-red-500">*</span>
                    </label>
                    <input type="text" wire:model="firstname" placeholder="Juan"
                           class="w-full px-3 py-2.5 border border-gray-200 rounded-xl text-sm placeholder-gray-300 focus:outline-none focus:ring-2 focus:ring-red-400 focus:border-transparent transition-colors" required>
                    @error('firstname') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                </div>
                <div>
                    <label class="block text-[10px] font-semibold text-gray-400 uppercase tracking-widest mb-1.5">Middle Name</label>
                    <input type="text" wire:model="middlename" placeholder="Santos"
                           class="w-full px-3 py-2.5 border border-gray-200 rounded-xl text-sm placeholder-gray-300 focus:outline-none focus:ring-2 focus:ring-red-400 focus:border-transparent transition-colors">
                    @error('middlename') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                </div>
                <div>
                    <label class="block text-[10px] font-semibold text-gray-400 uppercase tracking-widest mb-1.5">
                        Last Name <span class="text-red-500">*</span>
                    </label>
                    <input type="text" wire:model="lastname" placeholder="Dela Cruz"
                           class="w-full px-3 py-2.5 border border-gray-200 rounded-xl text-sm placeholder-gray-300 focus:outline-none focus:ring-2 focus:ring-red-400 focus:border-transparent transition-colors" required>
                    @error('lastname') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                </div>
                <div>
                    <label class="block text-[10px] font-semibold text-gray-400 uppercase tracking-widest mb-1.5">
                        Date of Birth <span class="text-red-500">*</span>
                    </label>
                    <input type="date" wire:model="birthdate"
                           class="w-full px-3 py-2.5 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-red-400 focus:border-transparent transition-colors" required>
                    @error('birthdate') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                </div>
            </div>

            {{-- CONTACT DETAILS divider --}}
            <div class="flex items-center gap-3 mb-4">
                <div class="flex-1 h-px bg-gray-100"></div>
                <span class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">Contact Details</span>
                <div class="flex-1 h-px bg-gray-100"></div>
            </div>

            <div class="grid grid-cols-4 gap-4 mb-6">
                <div>
                    <label class="block text-[10px] font-semibold text-gray-400 uppercase tracking-widest mb-1.5">
                        Gender <span class="text-red-500">*</span>
                    </label>
                    <select wire:model="gender"
                            class="w-full px-3 py-2.5 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-red-400 focus:border-transparent transition-colors appearance-none bg-white" required>
                        <option value="">Select Gender</option>
                        <option value="Male">Male</option>
                        <option value="Female">Female</option>
                    </select>
                    @error('gender') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                </div>
                <div x-data="{ val: $wire.entangle('contact_number'), get missing() { return this.val ? 11 - this.val.length : 0 } }">
                    <label class="block text-[10px] font-semibold text-gray-400 uppercase tracking-widest mb-1.5">Contact Number</label>
                    <input type="text" wire:model="contact_number" placeholder="09123456789" maxlength="11"
                           @input="val = $event.target.value = $event.target.value.replace(/[^0-9]/g, '').slice(0, 11)"
                           :class="val && val.length > 0 && val.length < 11 ? 'border-red-300 focus:ring-red-400' : 'border-gray-200 focus:ring-red-400'"
                           class="w-full px-3 py-2.5 border rounded-xl text-sm placeholder-gray-300 focus:outline-none focus:ring-2 focus:border-transparent transition-colors">
                    <template x-if="val && val.length > 0 && val.length < 11">
                        <span class="text-red-400 text-xs mt-1 block" x-text="'You\'re missing ' + missing + (missing === 1 ? ' number' : ' numbers')"></span>
                    </template>
                    @error('contact_number') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                    <span class="text-xs text-gray-300 mt-1 block">11 digits only</span>
                </div>
                <div>
                    <label class="block text-[10px] font-semibold text-gray-400 uppercase tracking-widest mb-1.5">
                        Email Address <span class="text-red-500">*</span>
                    </label>
                    <input type="email" wire:model="email" placeholder="juan@example.com"
                           class="w-full px-3 py-2.5 border border-gray-200 rounded-xl text-sm placeholder-gray-300 focus:outline-none focus:ring-2 focus:ring-red-400 focus:border-transparent transition-colors" required>
                    @error('email') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                </div>
                <div>
                    <label class="block text-[10px] font-semibold text-gray-400 uppercase tracking-widest mb-1.5">Address</label>
                    <input type="text" wire:model="address" placeholder="123 Street, City"
                           class="w-full px-3 py-2.5 border border-gray-200 rounded-xl text-sm placeholder-gray-300 focus:outline-none focus:ring-2 focus:ring-red-400 focus:border-transparent transition-colors">
                    @error('address') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                </div>
            </div>

            {{-- Footer --}}
            <div class="flex items-center justify-between pt-4 border-t border-gray-100">
                <p class="text-xs text-gray-400">
                    Fields marked with <span class="text-red-500 font-semibold">*</span> are required
                </p>
                <button type="submit"
                        class="inline-flex items-center gap-2 px-5 py-2.5 bg-red-500 hover:bg-red-600 text-white text-sm font-semibold rounded-xl transition-colors">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                    </svg>
                    Add Patient
                </button>
            </div>
        </form>
        @endif
    </div>

    <!-- Search and Filters Card -->
    <div class="bg-white rounded-lg shadow-sm mb-6">
        <div class="p-6">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Gender</label>
                    <select wire:model.live="filterGender" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-pink-500">
                        <option value="">All Genders</option>
                        <option value="Male">Male</option>
                        <option value="Female">Female</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Sort By</label>
                    <button wire:click="toggleSort" type="button"
                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-pink-500 hover:bg-gray-50 transition-colors flex items-center justify-center gap-2">
                        @if($sortBy === 'recent')
                            <svg class="w-4 h-4 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            <span class="text-sm">Recently Added</span>
                        @elseif($sortBy === 'name_asc')
                            <svg class="w-4 h-4 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4h14M3 8h14M3 12h6"/>
                            </svg>
                            <span class="text-sm">A-Z</span>
                        @else
                            <svg class="w-4 h-4 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4h14M3 8h14M3 12h6"/>
                            </svg>
                            <span class="text-sm">Z-A</span>
                        @endif
                    </button>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Rows per page</label>
                    <select wire:model.live="perPage" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-pink-500">
                        <option value="10">10</option>
                        <option value="25">25</option>
                        <option value="50">50</option>
                        <option value="100">100</option>
                        <option value="all">All</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Search Patients</label>
                    <input type="text" wire:model.live="search" placeholder="Search by name or patient ID..." class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-pink-500" style="min-width: 280px;">
                </div>
            </div>
        </div>
    </div>

    <!-- Patients List Card -->
    <div class="bg-white rounded-lg shadow-sm mb-6">
        <div class="px-6 py-4 border-b border-gray-200">
            <div class="flex items-center justify-between">
                <h2 class="text-lg font-semibold text-gray-900">Patients Directory</h2>
                <!-- Delete Selected Button -->
                <div x-show="selectedIds.length > 0" x-cloak x-transition>
                    <button type="button" 
                            @click="if(confirm('Are you sure you want to delete ' + selectedIds.length + ' selected patient(s)?')) { $wire.deleteSelected(selectedIds) }"
                            class="inline-flex items-center gap-2 px-4 py-2 bg-red-500 hover:bg-red-600 text-white text-sm font-medium rounded-lg transition-colors">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                        </svg>
                        Delete Selected (<span x-text="selectedIds.length"></span>)
                    </button>
                </div>
            </div>
        </div>

        <!-- Tab Bar -->
        <div class="border-b border-gray-200 bg-gray-50">
            <nav class="flex px-6 -mb-px" aria-label="Tabs">
                <button wire:click="$set('filterType', 'all')" type="button"
                        class="py-3 px-4 text-sm font-medium border-b-2 transition-colors {{ $filterType === 'all' ? 'border-pink-500 text-pink-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }}">
                    All
                </button>
                <button wire:click="$set('filterType', 'Internal')" type="button"
                        class="py-3 px-4 text-sm font-medium border-b-2 transition-colors {{ $filterType === 'Internal' ? 'border-pink-500 text-pink-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }}">
                    Internal
                </button>
                <button wire:click="$set('filterType', 'External')" type="button"
                        class="py-3 px-4 text-sm font-medium border-b-2 transition-colors {{ $filterType === 'External' ? 'border-pink-500 text-pink-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }}">
                    External
                </button>
            </nav>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left w-10">
                            <input type="checkbox" x-model="selectAll" 
                                   @change="toggleAll([{{ $patients instanceof \Illuminate\Pagination\LengthAwarePaginator ? $patients->pluck('patient_id')->implode(',') : $patients->pluck('patient_id')->implode(',') }}])"
                                   class="h-4 w-4 rounded border-gray-300 text-pink-600 focus:ring-pink-500">
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Full Name</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Type</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Birthdate</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Gender</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Contact</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($patients as $patient)
                        <tr wire:key="patient-{{ $patient->patient_id }}" 
                            class="hover:bg-gray-50 cursor-pointer transition-colors"
                            wire:click="viewPatient({{ $patient->patient_id }})">
                            <td class="px-6 py-4" wire:click.stop>
                                <input type="checkbox" value="{{ $patient->patient_id }}" 
                                       @change="toggleOne({{ $patient->patient_id }})"
                                       :checked="selectedIds.includes({{ $patient->patient_id }})"
                                       class="h-4 w-4 rounded border-gray-300 text-pink-600 focus:ring-pink-500">
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
                            <td class="px-6 py-4 whitespace-nowrap text-sm">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $patient->patient_type === 'Internal' ? 'bg-blue-100 text-blue-800' : 'bg-green-100 text-green-800' }}">
                                    {{ $patient->patient_type ?? 'N/A' }}
                                </span>
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
        
        @if($perPage !== 'all' && $patients instanceof \Illuminate\Pagination\LengthAwarePaginator && $patients->hasPages())
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
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-4">
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
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Gender *</label>
                        <select wire:model="gender" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-pink-500" required>
                            <option value="">Select Gender</option>
                            <option value="Male">Male</option>
                            <option value="Female">Female</option>
                        </select>
                        @error('gender') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                    </div>
                    <div x-data="{ val: $wire.entangle('contact_number'), get missing() { return this.val ? 11 - this.val.length : 0 } }">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Contact Number</label>
                        <input type="text" wire:model="contact_number" placeholder="09123456789" maxlength="11"
                               @input="val = $event.target.value = $event.target.value.replace(/[^0-9]/g, '').slice(0, 11)"
                               :class="val && val.length > 0 && val.length < 11 ? 'border-red-400 focus:ring-red-500' : 'border-gray-300 focus:ring-pink-500'"
                               class="w-full px-3 py-2 border rounded-md focus:outline-none focus:ring-2">
                        <template x-if="val && val.length > 0 && val.length < 11">
                            <span class="text-red-500 text-xs mt-1 block" x-text="'You\'re missing ' + missing + (missing === 1 ? ' number' : ' numbers')"></span>
                        </template>
                        @error('contact_number') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                        <span class="text-xs text-gray-400 mt-1 block">11 digits only</span>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Email Address *</label>
                        <input type="email" wire:model="email" placeholder="juan@example.com" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-pink-500" required>
                        @error('email') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
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
        <div class="relative top-10 mx-auto p-6 border w-full max-w-5xl shadow-lg rounded-xl bg-white">
            <div class="flex items-center justify-between mb-6">
                <h3 class="text-2xl font-bold text-gray-900">Patient Details</h3>
                <div class="flex items-center gap-2">
                    @if(!$modalEditMode)
                        <button wire:click="startModalEdit" class="inline-flex items-center gap-1.5 px-4 py-2 text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 rounded-lg transition-colors">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                            </svg>
                            Edit
                        </button>
                    @endif
                    <button wire:click="closeView" class="text-gray-400 hover:text-gray-600 p-1">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>
            </div>
            
            <div class="space-y-4">
                <div class="flex items-center mb-6">
                    <div class="w-16 h-16 bg-blue-500 rounded-full flex items-center justify-center text-white text-2xl font-bold mr-4">
                        {{ strtoupper(substr($viewingPatient->firstname, 0, 1) . substr($viewingPatient->lastname, 0, 1)) }}
                    </div>
                    <div>
                        <h4 class="text-xl font-bold text-gray-900">{{ $viewingPatient->full_name }}</h4>
                        <p class="text-sm text-gray-500">Patient ID: {{ $viewingPatient->patient_id }}</p>
                    </div>
                </div>

                @if($modalEditMode)
                {{-- Inline Edit Form --}}
                <form wire:submit.prevent="saveModalEdit">
                    <div class="bg-blue-50 border border-blue-200 rounded-lg p-3 mb-4 flex items-center gap-2">
                        <svg class="w-5 h-5 text-blue-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        <p class="text-sm text-blue-700">You are editing this patient's information. Make your changes and click <strong>Save Changes</strong>.</p>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
                        <div>
                            <label class="block text-xs font-semibold text-gray-500 uppercase mb-1.5">First Name <span class="text-red-400">*</span></label>
                            <input type="text" wire:model="modalFirstname" class="w-full px-3 py-2.5 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent text-sm" required>
                            @error('modalFirstname') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-gray-500 uppercase mb-1.5">Middle Name</label>
                            <input type="text" wire:model="modalMiddlename" class="w-full px-3 py-2.5 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent text-sm">
                            @error('modalMiddlename') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-gray-500 uppercase mb-1.5">Last Name <span class="text-red-400">*</span></label>
                            <input type="text" wire:model="modalLastname" class="w-full px-3 py-2.5 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent text-sm" required>
                            @error('modalLastname') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
                        <div>
                            <label class="block text-xs font-semibold text-gray-500 uppercase mb-1.5">Date of Birth <span class="text-red-400">*</span></label>
                            <input type="date" wire:model="modalBirthdate" class="w-full px-3 py-2.5 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent text-sm" required>
                            @error('modalBirthdate') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-gray-500 uppercase mb-1.5">Gender <span class="text-red-400">*</span></label>
                            <select wire:model="modalGender" class="w-full px-3 py-2.5 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent text-sm" required>
                                <option value="">Select Gender</option>
                                <option value="Male">Male</option>
                                <option value="Female">Female</option>
                            </select>
                            @error('modalGender') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                        </div>
                        <div x-data="{ val: $wire.entangle('modalContact'), get missing() { return this.val ? 11 - this.val.length : 0 } }">
                            <label class="block text-xs font-semibold text-gray-500 uppercase mb-1.5">Contact Number</label>
                            <input type="text" wire:model="modalContact" placeholder="09123456789" maxlength="11"
                                   @input="val = $event.target.value = $event.target.value.replace(/[^0-9]/g, '').slice(0, 11)"
                                   :class="val && val.length > 0 && val.length < 11 ? 'border-red-400 focus:ring-red-500' : 'border-gray-300 focus:ring-blue-500'"
                                   class="w-full px-3 py-2.5 border rounded-lg focus:outline-none focus:ring-2 focus:border-transparent text-sm">
                            <template x-if="val && val.length > 0 && val.length < 11">
                                <span class="text-red-500 text-xs mt-1 block" x-text="'Missing ' + missing + (missing === 1 ? ' digit' : ' digits')"></span>
                            </template>
                            @error('modalContact') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                        </div>
                    </div>

                    <div class="mb-4">
                        <label class="block text-xs font-semibold text-gray-500 uppercase mb-1.5">Address</label>
                        <input type="text" wire:model="modalAddress" placeholder="123 Street, City" class="w-full px-3 py-2.5 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent text-sm">
                        @error('modalAddress') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                        <div>
                            <label class="block text-xs font-semibold text-gray-500 uppercase mb-1.5">Email Address <span class="text-red-400">*</span></label>
                            <input type="email" wire:model="modalEmail" placeholder="juan@example.com" class="w-full px-3 py-2.5 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent text-sm" required>
                            @error('modalEmail') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-gray-500 uppercase mb-1.5">Patient Type <span class="text-red-400">*</span></label>
                            <select wire:model="modalPatientType" class="w-full px-3 py-2.5 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent text-sm" required>
                                <option value="External">External</option>
                                <option value="Internal">Internal</option>
                            </select>
                            @error('modalPatientType') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                        </div>
                    </div>

                    <div class="flex items-center justify-end gap-3 pt-4 border-t border-gray-200">
                        <button type="button" wire:click="cancelModalEdit" class="px-5 py-2.5 text-sm font-medium text-gray-700 bg-gray-100 hover:bg-gray-200 rounded-lg transition-colors">
                            Cancel
                        </button>
                        <button type="submit" class="inline-flex items-center gap-1.5 px-5 py-2.5 text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 rounded-lg transition-colors">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                            </svg>
                            Save Changes
                        </button>
                    </div>
                </form>
                @else
                {{-- View Mode --}}
                <div class="grid grid-cols-2 gap-4">
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
                    <div class="bg-gray-50 p-4 rounded-lg">
                        <p class="text-xs font-semibold text-gray-500 uppercase mb-1">Contact Number</p>
                        <p class="text-sm font-medium text-gray-900">{{ $viewingPatient->contact_number ?: 'N/A' }}</p>
                    </div>
                    <div class="bg-gray-50 p-4 rounded-lg col-span-2">
                        <p class="text-xs font-semibold text-gray-500 uppercase mb-1">Address</p>
                        <p class="text-sm font-medium text-gray-900">{{ $viewingPatient->address ?: 'N/A' }}</p>
                    </div>
                    <div class="bg-gray-50 p-4 rounded-lg">
                        <p class="text-xs font-semibold text-gray-500 uppercase mb-1">Email Address</p>
                        <p class="text-sm font-medium text-gray-900">{{ $viewingPatient->email ?: 'N/A' }}</p>
                    </div>
                    <div class="bg-gray-50 p-4 rounded-lg">
                        <p class="text-xs font-semibold text-gray-500 uppercase mb-1">Patient Type</p>
                        <p class="text-sm font-medium">
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $viewingPatient->patient_type === 'Internal' ? 'bg-blue-100 text-blue-800' : 'bg-green-100 text-green-800' }}">
                                {{ $viewingPatient->patient_type ?? 'N/A' }}
                            </span>
                        </p>
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
                @endif

                <!-- Lab Results Section -->
                <div class="mt-6">
                    <h4 class="text-lg font-semibold text-gray-900 mb-3 flex items-center">
                        <svg class="w-5 h-5 mr-2 text-pink-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"/>
                        </svg>
                        Lab Results
                    </h4>
                    @if(count($patientLabResults) > 0)
                    <div class="overflow-x-auto border border-gray-200 rounded-lg">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Test</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Result</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Normal Range</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Findings</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Date</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Performed By</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Action</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @foreach($patientLabResults as $result)
                                <tr class="hover:bg-gray-50">
                                    <td class="px-4 py-3 text-sm font-medium text-gray-900">{{ $result->test->label ?? 'N/A' }}</td>
                                    <td class="px-4 py-3 text-sm text-gray-700">{{ $result->result_value ?? 'N/A' }}</td>
                                    <td class="px-4 py-3 text-sm text-gray-500">{{ $result->normal_range ?? 'N/A' }}</td>
                                    <td class="px-4 py-3 text-sm text-gray-500">{{ $result->findings ?? '-' }}</td>
                                    <td class="px-4 py-3 text-sm text-gray-500 whitespace-nowrap">{{ $result->result_date ? $result->result_date->format('M d, Y') : 'N/A' }}</td>
                                    <td class="px-4 py-3 whitespace-nowrap">
                                        <span class="px-2 py-1 text-xs rounded-full {{ $result->status_badge_class }}">
                                            {{ ucfirst($result->status) }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 text-sm text-gray-500">
                                        {{ $result->performedBy->firstname ?? '' }} {{ $result->performedBy->lastname ?? 'N/A' }}
                                    </td>
                                    <td class="px-4 py-3 whitespace-nowrap">
                                        @if($result->status === 'final')
                                        <a href="{{ route('lab-results.show', $result->lab_result_id) }}" target="_blank"
                                           class="inline-flex items-center px-3 py-1.5 bg-green-600 hover:bg-green-700 text-white text-xs font-medium rounded-lg transition-colors">
                                            <svg class="w-3.5 h-3.5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/>
                                            </svg>
                                            Print
                                        </a>
                                        @else
                                        <span class="text-xs text-gray-400 italic">Pending</span>
                                        @endif
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    @else
                    <div class="bg-gray-50 border border-gray-200 rounded-lg p-6 text-center">
                        <svg class="w-10 h-10 text-gray-300 mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                        </svg>
                        <p class="text-sm text-gray-500">No lab results found for this patient.</p>
                    </div>
                    @endif
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