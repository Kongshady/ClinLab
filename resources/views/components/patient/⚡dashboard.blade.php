<?php

use Livewire\Component;
use App\Models\Patient;
use App\Models\LabResult;
use App\Models\Certificate;
use App\Models\CertificateIssue;

new class extends Component
{
    public $patient = null;
    public $selectedResult = null;
    public $groupedResults = [];
    public $activeTab = 'results';

    // Profile edit fields
    public $editMode = false;
    public $editFirstname = '';
    public $editMiddlename = '';
    public $editLastname = '';
    public $editGender = '';
    public $editBirthdate = '';
    public $editContact = '';
    public $editAddress = '';

    // Certificates
    public $certificates = [];
    public $selectedCertificate = null;
    public $certFilterType = '';
    public $certFilterStatus = '';
    public $certSearch = '';

    // Verification
    public $verifyCode = '';
    public $verifyResult = null;
    public $verifyLoading = false;

    public function mount()
    {
        $user = auth()->user();
        $this->patient = Patient::where('user_id', $user->id)->first();
        $this->loadResults();
        $this->loadCertificates();
    }

    public function loadResults()
    {
        if ($this->patient) {
            $results = LabResult::with(['test', 'performedBy', 'verifiedBy'])
                ->where('patient_id', $this->patient->patient_id)
                ->orderBy('result_date', 'desc')
                ->get();

            $this->groupedResults = $results->groupBy(function ($result) {
                return $result->result_date ? $result->result_date->format('Y-m-d') : 'Unknown';
            })->toArray();
        }
    }

    public function loadCertificates()
    {
        if (!$this->patient) return;

        // Load from certificate table (directly linked to patient)
        $query = Certificate::with(['equipment', 'issuedBy', 'verifiedBy'])
            ->where('patient_id', $this->patient->patient_id);

        if ($this->certFilterType) {
            $query->where('certificate_type', $this->certFilterType);
        }
        if ($this->certFilterStatus) {
            $query->whereRaw('LOWER(status) = ?', [strtolower($this->certFilterStatus)]);
        }
        if ($this->certSearch) {
            $query->where(function ($q) {
                $q->where('certificate_number', 'like', '%' . $this->certSearch . '%')
                  ->orWhere('certificate_type', 'like', '%' . $this->certSearch . '%');
            });
        }

        $certs = $query->orderBy('issue_date', 'desc')->get();

        // Also load from certificate_issues linked via lab_result_id
        $labResultIds = LabResult::where('patient_id', $this->patient->patient_id)
            ->pluck('lab_result_id');

        if ($labResultIds->isNotEmpty()) {
            $issueQuery = CertificateIssue::with(['template', 'generator', 'equipment'])
                ->whereIn('lab_result_id', $labResultIds);

            if ($this->certFilterStatus) {
                $issueQuery->whereRaw('LOWER(status) = ?', [strtolower($this->certFilterStatus)]);
            }
            if ($this->certSearch) {
                $issueQuery->where('certificate_no', 'like', '%' . $this->certSearch . '%');
            }

            $issues = $issueQuery->orderBy('issued_at', 'desc')->get();
        } else {
            $issues = collect();
        }

        // Merge both sources into a unified list
        $merged = collect();

        foreach ($certs as $cert) {
            $merged->push([
                'source' => 'certificate',
                'id' => $cert->certificate_id,
                'number' => $cert->certificate_number,
                'type' => ucfirst($cert->certificate_type),
                'status' => $cert->status,
                'issue_date' => $cert->issue_date ? $cert->issue_date->format('M d, Y') : null,
                'valid_until' => null,
                'issued_by' => $cert->issuedBy ? ($cert->issuedBy->firstname . ' ' . $cert->issuedBy->lastname) : null,
                'verified_by' => $cert->verifiedBy ? ($cert->verifiedBy->firstname . ' ' . $cert->verifiedBy->lastname) : null,
                'equipment' => $cert->equipment ? $cert->equipment->equipment_name : null,
                'verification_code' => null,
                'pdf_path' => $cert->pdf_path,
                'raw_date' => $cert->issue_date,
            ]);
        }

        foreach ($issues as $issue) {
            $type = $issue->template ? $issue->template->type : 'general';
            $merged->push([
                'source' => 'certificate_issue',
                'id' => $issue->id,
                'number' => $issue->certificate_no,
                'type' => ucfirst($type),
                'status' => $issue->status,
                'issue_date' => $issue->issued_at ? $issue->issued_at->format('M d, Y') : null,
                'valid_until' => $issue->valid_until ? $issue->valid_until->format('M d, Y') : null,
                'issued_by' => $issue->generator ? $issue->generator->name : null,
                'verified_by' => null,
                'equipment' => $issue->equipment ? $issue->equipment->equipment_name : null,
                'verification_code' => $issue->verification_code,
                'pdf_path' => $issue->pdf_path,
                'raw_date' => $issue->issued_at,
            ]);
        }

        $this->certificates = $merged->sortByDesc('raw_date')->values()->toArray();
    }

    public function updatedCertFilterType()
    {
        $this->loadCertificates();
    }

    public function updatedCertFilterStatus()
    {
        $this->loadCertificates();
    }

    public function updatedCertSearch()
    {
        $this->loadCertificates();
    }

    public function viewCertificate($source, $id)
    {
        if ($source === 'certificate') {
            $cert = Certificate::with(['equipment', 'issuedBy', 'verifiedBy', 'patient'])
                ->where('certificate_id', $id)
                ->where('patient_id', $this->patient->patient_id)
                ->first();

            if ($cert) {
                $this->selectedCertificate = [
                    'source' => 'certificate',
                    'id' => $cert->certificate_id,
                    'number' => $cert->certificate_number,
                    'type' => ucfirst($cert->certificate_type),
                    'status' => $cert->status,
                    'issue_date' => $cert->issue_date ? $cert->issue_date->format('F d, Y') : null,
                    'valid_until' => null,
                    'issued_by' => $cert->issuedBy ? ($cert->issuedBy->firstname . ' ' . $cert->issuedBy->lastname) : null,
                    'verified_by' => $cert->verifiedBy ? ($cert->verifiedBy->firstname . ' ' . $cert->verifiedBy->lastname) : null,
                    'equipment' => $cert->equipment ? $cert->equipment->equipment_name : null,
                    'verification_code' => null,
                    'pdf_path' => $cert->pdf_path,
                    'certificate_data' => $cert->certificate_data,
                    'patient_name' => $cert->patient ? $cert->patient->full_name : null,
                ];
            }
        } else {
            $labResultIds = LabResult::where('patient_id', $this->patient->patient_id)
                ->pluck('lab_result_id');

            $issue = CertificateIssue::with(['template', 'generator', 'equipment'])
                ->where('id', $id)
                ->whereIn('lab_result_id', $labResultIds)
                ->first();

            if ($issue) {
                $this->selectedCertificate = [
                    'source' => 'certificate_issue',
                    'id' => $issue->id,
                    'number' => $issue->certificate_no,
                    'type' => $issue->template ? ucfirst($issue->template->type) : 'General',
                    'status' => $issue->status,
                    'issue_date' => $issue->issued_at ? $issue->issued_at->format('F d, Y') : null,
                    'valid_until' => $issue->valid_until ? $issue->valid_until->format('F d, Y') : null,
                    'issued_by' => $issue->generator ? $issue->generator->name : null,
                    'verified_by' => null,
                    'equipment' => $issue->equipment ? $issue->equipment->equipment_name : null,
                    'verification_code' => $issue->verification_code,
                    'pdf_path' => $issue->pdf_path,
                    'certificate_data' => null,
                    'patient_name' => $this->patient->full_name,
                ];
            }
        }
    }

    public function closeCertificate()
    {
        $this->selectedCertificate = null;
    }

    public function verifyCertificate()
    {
        $this->verifyResult = null;

        if (empty(trim($this->verifyCode))) {
            $this->verifyResult = ['status' => 'error', 'message' => 'Please enter a certificate number or verification code.'];
            return;
        }

        $code = trim($this->verifyCode);

        // Search in certificate table
        $cert = Certificate::with(['patient', 'issuedBy', 'verifiedBy'])
            ->where('certificate_number', $code)
            ->first();

        if ($cert) {
            $this->verifyResult = [
                'status' => 'found',
                'valid' => strtolower($cert->status) === 'issued',
                'number' => $cert->certificate_number,
                'type' => ucfirst($cert->certificate_type),
                'cert_status' => $cert->status,
                'issue_date' => $cert->issue_date ? $cert->issue_date->format('F d, Y') : null,
                'valid_until' => null,
                'patient' => $cert->patient ? $cert->patient->full_name : null,
                'issued_by' => $cert->issuedBy ? ($cert->issuedBy->firstname . ' ' . $cert->issuedBy->lastname) : null,
            ];
            return;
        }

        // Search in certificate_issues table (by certificate_no or verification_code)
        $issue = CertificateIssue::with(['template', 'generator'])
            ->where('certificate_no', $code)
            ->orWhere('verification_code', $code)
            ->first();

        if ($issue) {
            $this->verifyResult = [
                'status' => 'found',
                'valid' => $issue->isValid(),
                'number' => $issue->certificate_no,
                'type' => $issue->template ? ucfirst($issue->template->type) : 'General',
                'cert_status' => $issue->status,
                'issue_date' => $issue->issued_at ? $issue->issued_at->format('F d, Y') : null,
                'valid_until' => $issue->valid_until ? $issue->valid_until->format('F d, Y') : null,
                'patient' => null,
                'issued_by' => $issue->generator ? $issue->generator->name : null,
            ];
            return;
        }

        $this->verifyResult = ['status' => 'not_found', 'message' => 'No certificate found with this number or code.'];
    }

    public function clearVerification()
    {
        $this->verifyCode = '';
        $this->verifyResult = null;
    }

    public function switchTab($tab)
    {
        $this->activeTab = $tab;
        $this->selectedResult = null;
        $this->selectedCertificate = null;
        $this->editMode = false;
        $this->verifyResult = null;
    }

    public function viewResult($id)
    {
        $this->selectedResult = LabResult::with(['test', 'performedBy', 'verifiedBy'])
            ->where('lab_result_id', $id)
            ->where('patient_id', $this->patient->patient_id)
            ->first();
    }

    public function closeResult()
    {
        $this->selectedResult = null;
    }

    public function startEdit()
    {
        $this->editFirstname = $this->patient->firstname;
        $this->editMiddlename = $this->patient->middlename ?? '';
        $this->editLastname = $this->patient->lastname;
        $this->editGender = $this->patient->gender;
        $this->editBirthdate = $this->patient->birthdate ? $this->patient->birthdate->format('Y-m-d') : '';
        $this->editContact = $this->patient->contact_number ?? '';
        $this->editAddress = $this->patient->address ?? '';
        $this->editMode = true;
    }

    public function cancelEdit()
    {
        $this->editMode = false;
        $this->resetValidation();
    }

    public function saveProfile()
    {
        $this->validate([
            'editFirstname' => 'required|string|max:100',
            'editLastname' => 'required|string|max:100',
            'editMiddlename' => 'nullable|string|max:100',
            'editGender' => 'required|in:Male,Female',
            'editBirthdate' => 'required|date|before:today',
            'editContact' => 'nullable|string|max:20',
            'editAddress' => 'nullable|string|max:255',
        ]);

        $this->patient->update([
            'firstname' => $this->editFirstname,
            'middlename' => $this->editMiddlename ?: null,
            'lastname' => $this->editLastname,
            'gender' => $this->editGender,
            'birthdate' => $this->editBirthdate,
            'contact_number' => $this->editContact ?: null,
            'address' => $this->editAddress ?: null,
            'datetime_updated' => now(),
        ]);

        $this->patient->refresh();
        $this->editMode = false;

        session()->flash('profile_saved', 'Profile updated successfully!');
    }

    public function with(): array
    {
        return [];
    }
};
?>

<div>
    @if(!$patient)
        {{-- Not Linked State --}}
        <div class="max-w-lg mx-auto mt-16">
            <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-10 text-center">
                <div class="w-16 h-16 rounded-full bg-amber-100 flex items-center justify-center mx-auto mb-4">
                    <svg class="w-8 h-8 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z"/>
                    </svg>
                </div>
                <h3 class="text-xl font-bold text-gray-900 mb-2">Profile Not Yet Linked</h3>
                <p class="text-gray-500 text-sm leading-relaxed">Your account has not been linked to a patient record yet.<br>Please contact the laboratory staff to link your profile.</p>
            </div>
        </div>
    @else

    <div class="flex flex-col lg:flex-row gap-6">

        {{-- ============ LEFT SIDEBAR ============ --}}
        <div class="lg:w-80 flex-shrink-0 space-y-4">

            {{-- Profile Card --}}
            <div class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden">
                <div class="bg-gradient-to-br from-blue-600 via-blue-500 to-cyan-400 px-6 py-8 text-center relative">
                    <div class="absolute inset-0 bg-[url('data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iNDAiIGhlaWdodD0iNDAiIHhtbG5zPSJodHRwOi8vd3d3LnczLm9yZy8yMDAwL3N2ZyI+PGNpcmNsZSBjeD0iMjAiIGN5PSIyMCIgcj0iMSIgZmlsbD0icmdiYSgyNTUsMjU1LDI1NSwwLjA1KSIvPjwvc3ZnPg==')] opacity-50"></div>
                    <div class="relative">
                        @if(auth()->user()->avatar)
                            <img src="{{ auth()->user()->avatar }}" alt="Avatar" class="w-20 h-20 rounded-full mx-auto ring-4 ring-white/30 shadow-lg mb-3">
                        @else
                            <div class="w-20 h-20 rounded-full bg-white/20 backdrop-blur-sm flex items-center justify-center text-white text-2xl font-bold mx-auto ring-4 ring-white/30 mb-3">
                                {{ strtoupper(substr($patient->firstname, 0, 1) . substr($patient->lastname, 0, 1)) }}
                            </div>
                        @endif
                        <h2 class="text-lg font-bold text-white">{{ $patient->full_name }}</h2>
                        <span class="inline-block mt-1.5 px-3 py-0.5 bg-white/20 backdrop-blur-sm rounded-full text-white/90 text-xs font-medium">
                            {{ $patient->patient_type }} Patient
                        </span>
                    </div>
                </div>

                {{-- Quick Info --}}
                <div class="px-5 py-4 space-y-2.5 text-sm">
                    <div class="flex items-center text-gray-600">
                        <svg class="w-4 h-4 text-gray-400 mr-2.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                        <span class="truncate">{{ $patient->email ?? auth()->user()->email }}</span>
                    </div>
                    @if($patient->birthdate)
                    <div class="flex items-center text-gray-600">
                        <svg class="w-4 h-4 text-gray-400 mr-2.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                        <span>{{ $patient->birthdate->format('M d, Y') }} &middot; {{ $patient->birthdate->age }}y</span>
                    </div>
                    @endif
                    <div class="flex items-center text-gray-600">
                        <svg class="w-4 h-4 text-gray-400 mr-2.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                        <span>{{ $patient->gender ?: 'Not set' }}</span>
                    </div>
                </div>
            </div>

            {{-- Navigation --}}
            <nav class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden">
                <div class="p-2 space-y-1">
                    <button wire:click="switchTab('results')"
                            class="w-full flex items-center gap-3 px-4 py-3 rounded-xl text-sm font-medium transition-all
                                {{ $activeTab === 'results' ? 'bg-blue-50 text-blue-700' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900' }}">
                        <svg class="w-5 h-5 {{ $activeTab === 'results' ? 'text-blue-500' : 'text-gray-400' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"/>
                        </svg>
                        Lab Results
                        @if(count($groupedResults) > 0)
                            <span class="ml-auto text-xs px-2 py-0.5 rounded-full {{ $activeTab === 'results' ? 'bg-blue-100 text-blue-600' : 'bg-gray-100 text-gray-500' }}">
                                {{ collect($groupedResults)->flatten(1)->count() }}
                            </span>
                        @endif
                    </button>

                    <button wire:click="switchTab('certificates')"
                            class="w-full flex items-center gap-3 px-4 py-3 rounded-xl text-sm font-medium transition-all
                                {{ $activeTab === 'certificates' ? 'bg-blue-50 text-blue-700' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900' }}">
                        <svg class="w-5 h-5 {{ $activeTab === 'certificates' ? 'text-blue-500' : 'text-gray-400' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z"/>
                        </svg>
                        Certificates
                        @if(count($certificates) > 0)
                            <span class="ml-auto text-xs px-2 py-0.5 rounded-full {{ $activeTab === 'certificates' ? 'bg-blue-100 text-blue-600' : 'bg-gray-100 text-gray-500' }}">
                                {{ count($certificates) }}
                            </span>
                        @endif
                    </button>

                    <button wire:click="switchTab('verify')"
                            class="w-full flex items-center gap-3 px-4 py-3 rounded-xl text-sm font-medium transition-all
                                {{ $activeTab === 'verify' ? 'bg-blue-50 text-blue-700' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900' }}">
                        <svg class="w-5 h-5 {{ $activeTab === 'verify' ? 'text-blue-500' : 'text-gray-400' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/>
                        </svg>
                        Verify Certificate
                    </button>

                    <button wire:click="switchTab('profile')"
                            class="w-full flex items-center gap-3 px-4 py-3 rounded-xl text-sm font-medium transition-all
                                {{ $activeTab === 'profile' ? 'bg-blue-50 text-blue-700' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900' }}">
                        <svg class="w-5 h-5 {{ $activeTab === 'profile' ? 'text-blue-500' : 'text-gray-400' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5.121 17.804A13.937 13.937 0 0112 16c2.5 0 4.847.655 6.879 1.804M15 10a3 3 0 11-6 0 3 3 0 016 0zm6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        My Profile
                    </button>
                </div>
            </nav>

            {{-- Quick Stats --}}
            <div class="grid grid-cols-3 gap-3">
                <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-4 text-center">
                    <p class="text-2xl font-bold text-blue-600">{{ collect($groupedResults)->flatten(1)->count() }}</p>
                    <p class="text-xs text-gray-500 mt-0.5">Tests</p>
                </div>
                <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-4 text-center">
                    <p class="text-2xl font-bold text-emerald-600">{{ collect($groupedResults)->flatten(1)->where('status', 'final')->count() }}</p>
                    <p class="text-xs text-gray-500 mt-0.5">Finalized</p>
                </div>
                <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-4 text-center">
                    <p class="text-2xl font-bold text-violet-600">{{ count($certificates) }}</p>
                    <p class="text-xs text-gray-500 mt-0.5">Certificates</p>
                </div>
            </div>
        </div>

        {{-- ============ MAIN CONTENT ============ --}}
        <div class="flex-1 min-w-0">

            {{-- ===== LAB RESULTS TAB ===== --}}
            @if($activeTab === 'results')
            <div>
                <div class="flex items-center justify-between mb-5">
                    <h2 class="text-xl font-bold text-gray-900">Lab Results</h2>
                </div>

                @if(count($groupedResults) > 0)
                    <div class="space-y-5">
                    @foreach($groupedResults as $date => $results)
                        <div class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden">
                            <div class="flex items-center gap-3 px-5 py-3 bg-gray-50 border-b border-gray-200">
                                <div class="w-2.5 h-2.5 bg-blue-500 rounded-full"></div>
                                <h3 class="text-sm font-semibold text-gray-700">
                                    {{ $date !== 'Unknown' ? \Carbon\Carbon::parse($date)->format('F d, Y') : 'Date Not Available' }}
                                </h3>
                                <span class="ml-auto text-xs text-gray-400">{{ count($results) }} test(s)</span>
                            </div>
                            <div class="overflow-x-auto">
                                <table class="min-w-full divide-y divide-gray-100">
                                    <thead>
                                        <tr class="text-xs text-gray-500 uppercase">
                                            <th class="px-5 py-2.5 text-left font-medium">Test</th>
                                            <th class="px-5 py-2.5 text-left font-medium">Result</th>
                                            <th class="px-5 py-2.5 text-left font-medium">Normal Range</th>
                                            <th class="px-5 py-2.5 text-left font-medium">Status</th>
                                            <th class="px-5 py-2.5 text-left font-medium">Action</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-50">
                                        @foreach($results as $result)
                                        <tr class="hover:bg-blue-50/40 transition-colors">
                                            <td class="px-5 py-3 text-sm font-medium text-gray-900">{{ $result['test']['label'] ?? 'N/A' }}</td>
                                            <td class="px-5 py-3 text-sm text-gray-700">{{ $result['result_value'] ?? 'Pending' }}</td>
                                            <td class="px-5 py-3 text-sm text-gray-500">{{ $result['normal_range'] ?? '-' }}</td>
                                            <td class="px-5 py-3">
                                                @php
                                                    $sc = match($result['status'] ?? 'draft') {
                                                        'final' => 'bg-emerald-100 text-emerald-700',
                                                        'revised' => 'bg-blue-100 text-blue-700',
                                                        default => 'bg-amber-100 text-amber-700',
                                                    };
                                                @endphp
                                                <span class="px-2 py-0.5 text-xs font-medium rounded-full {{ $sc }}">{{ ucfirst($result['status'] ?? 'draft') }}</span>
                                            </td>
                                            <td class="px-5 py-3">
                                                @if(($result['status'] ?? '') === 'final')
                                                    <button wire:click="viewResult({{ $result['lab_result_id'] }})"
                                                            class="inline-flex items-center gap-1 text-xs text-blue-600 hover:text-blue-800 font-medium">
                                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                                        View
                                                    </button>
                                                @else
                                                    <span class="text-xs text-gray-400 italic">Pending</span>
                                                @endif
                                            </td>
                                        </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    @endforeach
                    </div>
                @else
                    <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-12 text-center">
                        <div class="w-16 h-16 rounded-full bg-gray-100 flex items-center justify-center mx-auto mb-4">
                            <svg class="w-8 h-8 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                        </div>
                        <h3 class="text-gray-600 font-semibold mb-1">No Lab Results Yet</h3>
                        <p class="text-gray-400 text-sm">Your lab results will appear here once they are processed.</p>
                    </div>
                @endif
            </div>
            @endif

            {{-- ===== CERTIFICATES TAB ===== --}}
            @if($activeTab === 'certificates')
            <div>
                <div class="flex items-center justify-between mb-5">
                    <h2 class="text-xl font-bold text-gray-900">My Certificates</h2>
                </div>

                {{-- Filters --}}
                <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-4 mb-5">
                    <div class="flex flex-col sm:flex-row gap-3">
                        <div class="flex-1">
                            <div class="relative">
                                <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                                <input type="text" wire:model.live.debounce.300ms="certSearch" placeholder="Search by certificate number..."
                                       class="w-full pl-10 pr-4 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent focus:bg-white transition-all">
                            </div>
                        </div>
                        <select wire:model.live="certFilterType"
                                class="px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent focus:bg-white transition-all">
                            <option value="">All Types</option>
                            <option value="calibration">Calibration</option>
                            <option value="maintenance">Maintenance</option>
                            <option value="lab_result">Lab Result</option>
                            <option value="safety">Safety Compliance</option>
                        </select>
                        <select wire:model.live="certFilterStatus"
                                class="px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent focus:bg-white transition-all">
                            <option value="">All Statuses</option>
                            <option value="issued">Issued</option>
                            <option value="revoked">Revoked</option>
                            <option value="expired">Expired</option>
                            <option value="draft">Draft</option>
                        </select>
                    </div>
                </div>

                @if(count($certificates) > 0)
                    <div class="space-y-3">
                        @foreach($certificates as $cert)
                        <div wire:click="viewCertificate('{{ $cert['source'] }}', {{ $cert['id'] }})"
                             class="bg-white rounded-2xl shadow-sm border border-gray-200 hover:border-blue-300 hover:shadow-md cursor-pointer transition-all overflow-hidden group">
                            <div class="flex items-center gap-4 p-5">
                                {{-- Icon --}}
                                <div class="flex-shrink-0">
                                    @php
                                        $iconBg = match(strtolower($cert['type'])) {
                                            'calibration' => 'bg-violet-100',
                                            'maintenance' => 'bg-amber-100',
                                            'lab_result', 'lab result' => 'bg-emerald-100',
                                            'safety', 'safety compliance' => 'bg-red-100',
                                            default => 'bg-blue-100',
                                        };
                                        $iconColor = match(strtolower($cert['type'])) {
                                            'calibration' => 'text-violet-600',
                                            'maintenance' => 'text-amber-600',
                                            'lab_result', 'lab result' => 'text-emerald-600',
                                            'safety', 'safety compliance' => 'text-red-600',
                                            default => 'text-blue-600',
                                        };
                                    @endphp
                                    <div class="w-12 h-12 rounded-xl {{ $iconBg }} flex items-center justify-center">
                                        <svg class="w-6 h-6 {{ $iconColor }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z"/>
                                        </svg>
                                    </div>
                                </div>

                                {{-- Info --}}
                                <div class="flex-1 min-w-0">
                                    <div class="flex items-center gap-2 mb-1">
                                        <h3 class="text-sm font-semibold text-gray-900 truncate">{{ $cert['number'] }}</h3>
                                        @php
                                            $sBg = match(strtolower($cert['status'])) {
                                                'issued' => 'bg-emerald-100 text-emerald-700',
                                                'revoked' => 'bg-red-100 text-red-700',
                                                'expired' => 'bg-gray-100 text-gray-600',
                                                'draft' => 'bg-amber-100 text-amber-700',
                                                default => 'bg-blue-100 text-blue-700',
                                            };
                                        @endphp
                                        <span class="px-2 py-0.5 text-xs font-medium rounded-full {{ $sBg }}">{{ ucfirst($cert['status']) }}</span>
                                    </div>
                                    <div class="flex items-center gap-4 text-xs text-gray-500">
                                        <span class="flex items-center gap-1">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/></svg>
                                            {{ $cert['type'] }}
                                        </span>
                                        @if($cert['issue_date'])
                                        <span class="flex items-center gap-1">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                            {{ $cert['issue_date'] }}
                                        </span>
                                        @endif
                                        @if($cert['equipment'])
                                        <span class="flex items-center gap-1">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z"/></svg>
                                            {{ $cert['equipment'] }}
                                        </span>
                                        @endif
                                    </div>
                                </div>

                                {{-- Arrow --}}
                                <svg class="w-5 h-5 text-gray-300 group-hover:text-blue-500 transition-colors flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                                </svg>
                            </div>

                            {{-- Valid Until Bar --}}
                            @if($cert['valid_until'])
                            <div class="px-5 py-2 bg-gray-50 border-t border-gray-100 text-xs text-gray-500">
                                <span class="font-medium">Valid Until:</span> {{ $cert['valid_until'] }}
                            </div>
                            @endif
                        </div>
                        @endforeach
                    </div>
                @else
                    <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-12 text-center">
                        <div class="w-16 h-16 rounded-full bg-gray-100 flex items-center justify-center mx-auto mb-4">
                            <svg class="w-8 h-8 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z"/>
                            </svg>
                        </div>
                        <h3 class="text-gray-600 font-semibold mb-1">No Certificates Found</h3>
                        <p class="text-gray-400 text-sm">Your certificates will appear here once they are issued by the laboratory.</p>
                    </div>
                @endif
            </div>
            @endif

            {{-- ===== VERIFY TAB ===== --}}
            @if($activeTab === 'verify')
            <div>
                <div class="flex items-center justify-between mb-5">
                    <h2 class="text-xl font-bold text-gray-900">Verify Certificate</h2>
                </div>

                <div class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden">
                    <div class="p-6">
                        <div class="max-w-lg mx-auto text-center">
                            <div class="w-16 h-16 rounded-full bg-blue-100 flex items-center justify-center mx-auto mb-4">
                                <svg class="w-8 h-8 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                                </svg>
                            </div>
                            <h3 class="text-lg font-semibold text-gray-900 mb-2">Certificate Verification</h3>
                            <p class="text-sm text-gray-500 mb-6">Enter a certificate number or verification code to check its authenticity and validity.</p>

                            <div class="flex gap-3">
                                <div class="flex-1 relative">
                                    <input type="text" wire:model="verifyCode" wire:keydown.enter="verifyCertificate"
                                           placeholder="e.g. CERT-2026-00001 or verification code"
                                           class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent focus:bg-white transition-all">
                                </div>
                                <button wire:click="verifyCertificate"
                                        class="px-6 py-3 bg-gradient-to-r from-blue-600 to-blue-500 hover:from-blue-700 hover:to-blue-600 text-white font-medium text-sm rounded-xl shadow-sm shadow-blue-500/25 transition-all flex items-center gap-2">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                                    Verify
                                </button>
                            </div>
                        </div>
                    </div>

                    {{-- Verification Results --}}
                    @if($verifyResult)
                    <div class="border-t border-gray-200">
                        @if($verifyResult['status'] === 'found')
                            <div class="p-6">
                                <div class="max-w-lg mx-auto">
                                    {{-- Status Banner --}}
                                    @if($verifyResult['valid'])
                                    <div class="flex items-center gap-3 p-4 mb-5 rounded-xl bg-emerald-50 border border-emerald-200">
                                        <div class="w-10 h-10 rounded-full bg-emerald-100 flex items-center justify-center flex-shrink-0">
                                            <svg class="w-5 h-5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                        </div>
                                        <div>
                                            <p class="font-semibold text-emerald-800">Certificate is Valid</p>
                                            <p class="text-xs text-emerald-600">This certificate has been verified and is currently active.</p>
                                        </div>
                                    </div>
                                    @else
                                    <div class="flex items-center gap-3 p-4 mb-5 rounded-xl bg-red-50 border border-red-200">
                                        <div class="w-10 h-10 rounded-full bg-red-100 flex items-center justify-center flex-shrink-0">
                                            <svg class="w-5 h-5 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                        </div>
                                        <div>
                                            <p class="font-semibold text-red-800">Certificate is Invalid</p>
                                            <p class="text-xs text-red-600">This certificate is {{ strtolower($verifyResult['cert_status']) }} or has expired.</p>
                                        </div>
                                    </div>
                                    @endif

                                    {{-- Certificate Details --}}
                                    <div class="bg-gray-50 rounded-xl overflow-hidden">
                                        <div class="divide-y divide-gray-200/60">
                                            <div class="flex justify-between items-center px-5 py-3">
                                                <span class="text-sm text-gray-500">Certificate No.</span>
                                                <span class="text-sm font-semibold text-gray-900 font-mono">{{ $verifyResult['number'] }}</span>
                                            </div>
                                            <div class="flex justify-between items-center px-5 py-3">
                                                <span class="text-sm text-gray-500">Type</span>
                                                <span class="text-sm font-medium text-gray-900">{{ $verifyResult['type'] }}</span>
                                            </div>
                                            <div class="flex justify-between items-center px-5 py-3">
                                                <span class="text-sm text-gray-500">Status</span>
                                                @php
                                                    $vsBg = match(strtolower($verifyResult['cert_status'])) {
                                                        'issued' => 'bg-emerald-100 text-emerald-700',
                                                        'revoked' => 'bg-red-100 text-red-700',
                                                        'expired' => 'bg-gray-100 text-gray-600',
                                                        default => 'bg-amber-100 text-amber-700',
                                                    };
                                                @endphp
                                                <span class="px-2.5 py-0.5 text-xs font-medium rounded-full {{ $vsBg }}">{{ ucfirst($verifyResult['cert_status']) }}</span>
                                            </div>
                                            @if($verifyResult['issue_date'])
                                            <div class="flex justify-between items-center px-5 py-3">
                                                <span class="text-sm text-gray-500">Issue Date</span>
                                                <span class="text-sm font-medium text-gray-900">{{ $verifyResult['issue_date'] }}</span>
                                            </div>
                                            @endif
                                            @if($verifyResult['valid_until'])
                                            <div class="flex justify-between items-center px-5 py-3">
                                                <span class="text-sm text-gray-500">Valid Until</span>
                                                <span class="text-sm font-medium text-gray-900">{{ $verifyResult['valid_until'] }}</span>
                                            </div>
                                            @endif
                                            @if($verifyResult['patient'])
                                            <div class="flex justify-between items-center px-5 py-3">
                                                <span class="text-sm text-gray-500">Patient</span>
                                                <span class="text-sm font-medium text-gray-900">{{ $verifyResult['patient'] }}</span>
                                            </div>
                                            @endif
                                            @if($verifyResult['issued_by'])
                                            <div class="flex justify-between items-center px-5 py-3">
                                                <span class="text-sm text-gray-500">Issued By</span>
                                                <span class="text-sm font-medium text-gray-900">{{ $verifyResult['issued_by'] }}</span>
                                            </div>
                                            @endif
                                        </div>
                                    </div>

                                    <div class="mt-4 text-center">
                                        <button wire:click="clearVerification" class="text-sm text-blue-600 hover:text-blue-800 font-medium">
                                            Verify Another Certificate
                                        </button>
                                    </div>
                                </div>
                            </div>
                        @elseif($verifyResult['status'] === 'not_found')
                            <div class="p-6 text-center">
                                <div class="max-w-lg mx-auto">
                                    <div class="flex items-center gap-3 p-4 rounded-xl bg-amber-50 border border-amber-200">
                                        <div class="w-10 h-10 rounded-full bg-amber-100 flex items-center justify-center flex-shrink-0">
                                            <svg class="w-5 h-5 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z"/></svg>
                                        </div>
                                        <div class="text-left">
                                            <p class="font-semibold text-amber-800">Certificate Not Found</p>
                                            <p class="text-xs text-amber-600">{{ $verifyResult['message'] }}</p>
                                        </div>
                                    </div>
                                    <div class="mt-4">
                                        <button wire:click="clearVerification" class="text-sm text-blue-600 hover:text-blue-800 font-medium">
                                            Try Again
                                        </button>
                                    </div>
                                </div>
                            </div>
                        @elseif($verifyResult['status'] === 'error')
                            <div class="p-6 text-center">
                                <div class="max-w-lg mx-auto">
                                    <div class="flex items-center gap-3 p-4 rounded-xl bg-red-50 border border-red-200">
                                        <div class="w-10 h-10 rounded-full bg-red-100 flex items-center justify-center flex-shrink-0">
                                            <svg class="w-5 h-5 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                        </div>
                                        <p class="text-sm text-red-700 text-left">{{ $verifyResult['message'] }}</p>
                                    </div>
                                </div>
                            </div>
                        @endif
                    </div>
                    @endif
                </div>
            </div>
            @endif

            {{-- ===== PROFILE TAB ===== --}}
            @if($activeTab === 'profile')
            <div>
                <div class="flex items-center justify-between mb-5">
                    <h2 class="text-xl font-bold text-gray-900">My Profile</h2>
                    @if(!$editMode)
                        <button wire:click="startEdit"
                                class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium text-blue-600 bg-blue-50 hover:bg-blue-100 rounded-xl transition-colors">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                            Edit Profile
                        </button>
                    @endif
                </div>

                @if(session('profile_saved'))
                    <div class="mb-5 px-4 py-3 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-700 text-sm flex items-center gap-2">
                        <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                        {{ session('profile_saved') }}
                    </div>
                @endif

                <div class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden">
                    @if(!$editMode)
                        {{-- View Mode --}}
                        <div class="divide-y divide-gray-100">
                            @php
                                $fields = [
                                    ['label' => 'First Name', 'value' => $patient->firstname, 'icon' => 'M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z'],
                                    ['label' => 'Middle Name', 'value' => $patient->middlename ?: '—', 'icon' => 'M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z'],
                                    ['label' => 'Last Name', 'value' => $patient->lastname, 'icon' => 'M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z'],
                                    ['label' => 'Gender', 'value' => $patient->gender ?: '—', 'icon' => 'M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z'],
                                    ['label' => 'Date of Birth', 'value' => $patient->birthdate ? $patient->birthdate->format('F d, Y') . ' (' . $patient->birthdate->age . ' years old)' : '—', 'icon' => 'M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z'],
                                    ['label' => 'Email', 'value' => $patient->email ?? auth()->user()->email, 'icon' => 'M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z'],
                                    ['label' => 'Contact Number', 'value' => $patient->contact_number ?: '—', 'icon' => 'M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z'],
                                    ['label' => 'Address', 'value' => $patient->address ?: '—', 'icon' => 'M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0zM15 11a3 3 0 11-6 0 3 3 0 016 0z'],
                                    ['label' => 'Patient Type', 'value' => $patient->patient_type, 'icon' => 'M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z'],
                                ];
                            @endphp

                            @foreach($fields as $field)
                            <div class="flex items-center px-6 py-4">
                                <div class="flex items-center gap-3 w-44 flex-shrink-0">
                                    <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $field['icon'] }}"/></svg>
                                    <span class="text-sm text-gray-500">{{ $field['label'] }}</span>
                                </div>
                                <span class="text-sm font-medium text-gray-900">{{ $field['value'] }}</span>
                            </div>
                            @endforeach
                        </div>
                    @else
                        {{-- Edit Mode --}}
                        <form wire:submit.prevent="saveProfile" class="p-6 space-y-5">
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1.5">First Name <span class="text-red-400">*</span></label>
                                    <input type="text" wire:model="editFirstname"
                                           class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent focus:bg-white transition-all">
                                    @error('editFirstname') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1.5">Middle Name</label>
                                    <input type="text" wire:model="editMiddlename"
                                           class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent focus:bg-white transition-all">
                                    @error('editMiddlename') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1.5">Last Name <span class="text-red-400">*</span></label>
                                    <input type="text" wire:model="editLastname"
                                           class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent focus:bg-white transition-all">
                                    @error('editLastname') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1.5">Gender <span class="text-red-400">*</span></label>
                                    <select wire:model="editGender"
                                            class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent focus:bg-white transition-all">
                                        <option value="">Select...</option>
                                        <option value="Male">Male</option>
                                        <option value="Female">Female</option>
                                    </select>
                                    @error('editGender') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1.5">Date of Birth <span class="text-red-400">*</span></label>
                                    <input type="date" wire:model="editBirthdate"
                                           class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent focus:bg-white transition-all">
                                    @error('editBirthdate') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1.5">Contact Number</label>
                                    <input type="text" wire:model="editContact" placeholder="09xx-xxx-xxxx"
                                           class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent focus:bg-white transition-all">
                                    @error('editContact') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                                </div>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1.5">Address</label>
                                <textarea wire:model="editAddress" rows="2" placeholder="Enter your complete address"
                                          class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent focus:bg-white transition-all resize-none"></textarea>
                                @error('editAddress') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                            </div>

                            <div class="flex items-center justify-end gap-3 pt-3 border-t border-gray-100">
                                <button type="button" wire:click="cancelEdit"
                                        class="px-5 py-2.5 text-sm font-medium text-gray-700 bg-gray-100 hover:bg-gray-200 rounded-xl transition-colors">
                                    Cancel
                                </button>
                                <button type="submit"
                                        class="px-5 py-2.5 text-sm font-medium text-white bg-gradient-to-r from-blue-600 to-blue-500 hover:from-blue-700 hover:to-blue-600 rounded-xl shadow-sm shadow-blue-500/25 transition-all">
                                    Save Changes
                                </button>
                            </div>
                        </form>
                    @endif
                </div>
            </div>
            @endif
        </div>
    </div>

    {{-- ===== RESULT DETAIL MODAL ===== --}}
    @if($selectedResult)
    <div class="fixed inset-0 bg-black/40 backdrop-blur-sm flex items-center justify-center z-50 p-4" wire:click.self="closeResult">
        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-lg overflow-hidden">
            <div class="px-6 py-4 bg-gray-50 border-b border-gray-200 flex items-center justify-between">
                <h3 class="text-lg font-bold text-gray-900">Result Details</h3>
                <button wire:click="closeResult" class="w-8 h-8 flex items-center justify-center rounded-lg text-gray-400 hover:text-gray-600 hover:bg-gray-200 transition-colors">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>
            <div class="p-6 space-y-4">
                <div class="grid grid-cols-2 gap-3">
                    <div class="bg-gray-50 p-3.5 rounded-xl">
                        <p class="text-xs font-medium text-gray-500 uppercase mb-1">Test</p>
                        <p class="text-sm font-semibold text-gray-900">{{ $selectedResult->test->label ?? 'N/A' }}</p>
                    </div>
                    <div class="bg-gray-50 p-3.5 rounded-xl">
                        <p class="text-xs font-medium text-gray-500 uppercase mb-1">Status</p>
                        <span class="px-2 py-0.5 text-xs font-medium rounded-full bg-emerald-100 text-emerald-700">{{ ucfirst($selectedResult->status) }}</span>
                    </div>
                    <div class="bg-gray-50 p-3.5 rounded-xl">
                        <p class="text-xs font-medium text-gray-500 uppercase mb-1">Result Value</p>
                        <p class="text-sm font-semibold text-gray-900">{{ $selectedResult->result_value ?? 'N/A' }}</p>
                    </div>
                    <div class="bg-gray-50 p-3.5 rounded-xl">
                        <p class="text-xs font-medium text-gray-500 uppercase mb-1">Normal Range</p>
                        <p class="text-sm font-semibold text-gray-900">{{ $selectedResult->normal_range ?? 'N/A' }}</p>
                    </div>
                    <div class="bg-gray-50 p-3.5 rounded-xl col-span-2">
                        <p class="text-xs font-medium text-gray-500 uppercase mb-1">Findings</p>
                        <p class="text-sm text-gray-900">{{ $selectedResult->findings ?: 'No findings recorded.' }}</p>
                    </div>
                    <div class="bg-gray-50 p-3.5 rounded-xl col-span-2">
                        <p class="text-xs font-medium text-gray-500 uppercase mb-1">Remarks</p>
                        <p class="text-sm text-gray-900">{{ $selectedResult->remarks ?: 'No remarks.' }}</p>
                    </div>
                    <div class="bg-gray-50 p-3.5 rounded-xl">
                        <p class="text-xs font-medium text-gray-500 uppercase mb-1">Result Date</p>
                        <p class="text-sm text-gray-900">{{ $selectedResult->result_date ? $selectedResult->result_date->format('M d, Y') : 'N/A' }}</p>
                    </div>
                    <div class="bg-gray-50 p-3.5 rounded-xl">
                        <p class="text-xs font-medium text-gray-500 uppercase mb-1">Performed By</p>
                        <p class="text-sm text-gray-900">{{ $selectedResult->performedBy ? $selectedResult->performedBy->firstname . ' ' . $selectedResult->performedBy->lastname : 'N/A' }}</p>
                    </div>
                </div>
                <div class="flex justify-end pt-2">
                    <button wire:click="closeResult" class="px-5 py-2.5 text-sm font-medium text-gray-700 bg-gray-100 hover:bg-gray-200 rounded-xl transition-colors">
                        Close
                    </button>
                </div>
            </div>
        </div>
    </div>
    @endif

    {{-- ===== CERTIFICATE DETAIL MODAL ===== --}}
    @if($selectedCertificate)
    <div class="fixed inset-0 bg-black/40 backdrop-blur-sm flex items-center justify-center z-50 p-4" wire:click.self="closeCertificate">
        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-xl overflow-hidden">
            {{-- Header --}}
            <div class="px-6 py-4 bg-gradient-to-r from-blue-600 to-blue-500 flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-white/20 backdrop-blur-sm flex items-center justify-center">
                        <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z"/>
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-lg font-bold text-white">Certificate Details</h3>
                        <p class="text-sm text-white/80 font-mono">{{ $selectedCertificate['number'] }}</p>
                    </div>
                </div>
                <button wire:click="closeCertificate" class="w-8 h-8 flex items-center justify-center rounded-lg text-white/60 hover:text-white hover:bg-white/20 transition-colors">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>

            {{-- Content --}}
            <div class="p-6 space-y-5 max-h-[60vh] overflow-y-auto">
                {{-- Status Badge --}}
                <div class="flex items-center justify-center">
                    @php
                        $msBg = match(strtolower($selectedCertificate['status'])) {
                            'issued' => 'bg-emerald-50 border-emerald-200 text-emerald-700',
                            'revoked' => 'bg-red-50 border-red-200 text-red-700',
                            'expired' => 'bg-gray-50 border-gray-200 text-gray-600',
                            default => 'bg-amber-50 border-amber-200 text-amber-700',
                        };
                        $msIcon = match(strtolower($selectedCertificate['status'])) {
                            'issued' => 'M5 13l4 4L19 7',
                            'revoked' => 'M6 18L18 6M6 6l12 12',
                            default => 'M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z',
                        };
                    @endphp
                    <span class="inline-flex items-center gap-2 px-4 py-2 rounded-full text-sm font-semibold border {{ $msBg }}">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $msIcon }}"/></svg>
                        {{ ucfirst($selectedCertificate['status']) }}
                    </span>
                </div>

                {{-- Certificate Info --}}
                <div class="bg-gray-50 rounded-xl overflow-hidden divide-y divide-gray-200/60">
                    <div class="flex justify-between items-center px-5 py-3.5">
                        <span class="text-sm text-gray-500">Certificate Number</span>
                        <span class="text-sm font-bold text-gray-900 font-mono">{{ $selectedCertificate['number'] }}</span>
                    </div>
                    <div class="flex justify-between items-center px-5 py-3.5">
                        <span class="text-sm text-gray-500">Type</span>
                        <span class="text-sm font-medium text-gray-900">{{ $selectedCertificate['type'] }}</span>
                    </div>
                    @if($selectedCertificate['patient_name'])
                    <div class="flex justify-between items-center px-5 py-3.5">
                        <span class="text-sm text-gray-500">Patient</span>
                        <span class="text-sm font-medium text-gray-900">{{ $selectedCertificate['patient_name'] }}</span>
                    </div>
                    @endif
                    @if($selectedCertificate['equipment'])
                    <div class="flex justify-between items-center px-5 py-3.5">
                        <span class="text-sm text-gray-500">Equipment</span>
                        <span class="text-sm font-medium text-gray-900">{{ $selectedCertificate['equipment'] }}</span>
                    </div>
                    @endif
                    @if($selectedCertificate['issue_date'])
                    <div class="flex justify-between items-center px-5 py-3.5">
                        <span class="text-sm text-gray-500">Issue Date</span>
                        <span class="text-sm font-medium text-gray-900">{{ $selectedCertificate['issue_date'] }}</span>
                    </div>
                    @endif
                    @if($selectedCertificate['valid_until'])
                    <div class="flex justify-between items-center px-5 py-3.5">
                        <span class="text-sm text-gray-500">Valid Until</span>
                        <span class="text-sm font-medium text-gray-900">{{ $selectedCertificate['valid_until'] }}</span>
                    </div>
                    @endif
                    @if($selectedCertificate['issued_by'])
                    <div class="flex justify-between items-center px-5 py-3.5">
                        <span class="text-sm text-gray-500">Issued By</span>
                        <span class="text-sm font-medium text-gray-900">{{ $selectedCertificate['issued_by'] }}</span>
                    </div>
                    @endif
                    @if($selectedCertificate['verified_by'])
                    <div class="flex justify-between items-center px-5 py-3.5">
                        <span class="text-sm text-gray-500">Verified By</span>
                        <span class="text-sm font-medium text-gray-900">{{ $selectedCertificate['verified_by'] }}</span>
                    </div>
                    @endif
                    @if($selectedCertificate['verification_code'])
                    <div class="flex justify-between items-center px-5 py-3.5">
                        <span class="text-sm text-gray-500">Verification Code</span>
                        <span class="text-sm font-mono font-medium text-blue-700 bg-blue-50 px-2 py-0.5 rounded">{{ $selectedCertificate['verification_code'] }}</span>
                    </div>
                    @endif
                </div>

                {{-- QR Code Section --}}
                @if($selectedCertificate['verification_code'] || $selectedCertificate['number'])
                <div class="text-center">
                    <p class="text-xs text-gray-500 mb-3">Scan to verify this certificate</p>
                    <div class="inline-block bg-white p-3 rounded-xl border border-gray-200 shadow-sm">
                        <div id="cert-qr-{{ $selectedCertificate['id'] }}" class="w-32 h-32 flex items-center justify-center">
                            <img src="https://api.qrserver.com/v1/create-qr-code/?size=128x128&data={{ urlencode(url('/certificates/verify?code=' . ($selectedCertificate['verification_code'] ?: $selectedCertificate['number']))) }}"
                                 alt="QR Code" class="w-32 h-32" loading="lazy">
                        </div>
                    </div>
                </div>
                @endif

                {{-- Extra Certificate Data --}}
                @if(!empty($selectedCertificate['certificate_data']))
                <div>
                    <h4 class="text-sm font-semibold text-gray-700 mb-2">Additional Information</h4>
                    <div class="bg-gray-50 rounded-xl overflow-hidden divide-y divide-gray-200/60">
                        @foreach($selectedCertificate['certificate_data'] as $key => $value)
                        <div class="flex justify-between items-center px-5 py-3">
                            <span class="text-sm text-gray-500">{{ ucwords(str_replace('_', ' ', $key)) }}</span>
                            <span class="text-sm font-medium text-gray-900">{{ is_array($value) ? json_encode($value) : $value }}</span>
                        </div>
                        @endforeach
                    </div>
                </div>
                @endif
            </div>

            {{-- Footer with Actions --}}
            <div class="px-6 py-4 bg-gray-50 border-t border-gray-200 flex items-center justify-between">
                <button wire:click="closeCertificate" class="px-5 py-2.5 text-sm font-medium text-gray-700 bg-white border border-gray-200 hover:bg-gray-100 rounded-xl transition-colors">
                    Close
                </button>
                <div class="flex items-center gap-2">
                    @if($selectedCertificate['pdf_path'] || $selectedCertificate['source'] === 'certificate')
                    <a href="{{ route('patient.certificate.download', ['source' => $selectedCertificate['source'], 'id' => $selectedCertificate['id']]) }}"
                       target="_blank"
                       class="inline-flex items-center gap-2 px-5 py-2.5 text-sm font-medium text-white bg-gradient-to-r from-blue-600 to-blue-500 hover:from-blue-700 hover:to-blue-600 rounded-xl shadow-sm shadow-blue-500/25 transition-all">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                        Download PDF
                    </a>
                    @endif
                    <button onclick="window.print()"
                            class="inline-flex items-center gap-2 px-5 py-2.5 text-sm font-medium text-blue-700 bg-blue-50 hover:bg-blue-100 rounded-xl transition-colors">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                        Print
                    </button>
                </div>
            </div>
        </div>
    </div>
    @endif

    @endif
</div>
