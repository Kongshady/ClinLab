<?php

use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Validate;
use App\Models\Certificate;
use App\Models\Patient;
use App\Models\Equipment;
use App\Models\Employee;

new class extends Component
{
    use WithPagination;

    #[Validate('required|string|max:50|unique:certificate,certificate_number')]
    public $certificate_number = '';

    #[Validate('required|in:lab_result,calibration,compliance,safety,other')]
    public $certificate_type = 'lab_result';

    #[Validate('nullable|exists:patient,patient_id')]
    public $patient_id = '';

    #[Validate('nullable|exists:equipment,equipment_id')]
    public $equipment_id = '';

    #[Validate('required|date')]
    public $issue_date = '';

    #[Validate('required|exists:employee,employee_id')]
    public $issued_by = '';

    #[Validate('nullable|exists:employee,employee_id')]
    public $verified_by = '';

    #[Validate('required|in:draft,issued,revoked')]
    public $status = 'draft';

    public $search = '';
    public $filterType = '';
    public $filterStatus = '';
    public $flashMessage = '';

    // Edit Modal Properties
    public $showEditModal = false;
    public $editCertificateId = '';
    public $editCertificateNumber = '';
    public $editCertificateType = 'lab_result';
    public $editPatientId = '';
    public $editEquipmentId = '';
    public $editIssueDate = '';
    public $editIssuedBy = '';
    public $editVerifiedBy = '';
    public $editStatus = 'draft';

    public function mount()
    {
        if (session()->has('success')) {
            $this->flashMessage = session('success');
        }
        $this->issue_date = date('Y-m-d');
    }

    public function openEditModal($certificateId)
    {
        $certificate = Certificate::findOrFail($certificateId);
        
        $this->editCertificateId = $certificate->certificate_id;
        $this->editCertificateNumber = $certificate->certificate_number;
        $this->editCertificateType = $certificate->certificate_type;
        $this->editPatientId = $certificate->patient_id;
        $this->editEquipmentId = $certificate->equipment_id;
        $this->editIssueDate = $certificate->issue_date ? \Carbon\Carbon::parse($certificate->issue_date)->format('Y-m-d') : '';
        $this->editIssuedBy = $certificate->issued_by;
        $this->editVerifiedBy = $certificate->verified_by;
        $this->editStatus = $certificate->status;
        
        $this->showEditModal = true;
    }

    public function closeEditModal()
    {
        $this->showEditModal = false;
        $this->reset(['editCertificateId', 'editCertificateNumber', 'editCertificateType', 'editPatientId', 'editEquipmentId', 'editIssueDate', 'editIssuedBy', 'editVerifiedBy', 'editStatus']);
    }

    public function updateCertificate()
    {
        $validated = $this->validate([
            'editCertificateNumber' => 'required|string|max:50',
            'editCertificateType' => 'required|in:lab_result,calibration,compliance,safety,other',
            'editPatientId' => 'nullable|exists:patient,patient_id',
            'editEquipmentId' => 'nullable|exists:equipment,equipment_id',
            'editIssueDate' => 'required|date',
            'editIssuedBy' => 'required|exists:employee,employee_id',
            'editVerifiedBy' => 'nullable|exists:employee,employee_id',
            'editStatus' => 'required|in:draft,issued,revoked',
        ]);

        $certificate = Certificate::findOrFail($this->editCertificateId);
        $certificate->update([
            'certificate_number' => $this->editCertificateNumber,
            'certificate_type' => $this->editCertificateType,
            'patient_id' => $this->editPatientId,
            'equipment_id' => $this->editEquipmentId,
            'issue_date' => $this->editIssueDate,
            'issued_by' => $this->editIssuedBy,
            'verified_by' => $this->editVerifiedBy,
            'status' => $this->editStatus,
            'datetime_modified' => now(),
        ]);

        $this->flashMessage = 'Certificate updated successfully!';
        $this->closeEditModal();
    }

    public function save()
    {
        $this->validate();

        Certificate::create([
            'certificate_number' => $this->certificate_number,
            'certificate_type' => $this->certificate_type,
            'patient_id' => $this->patient_id,
            'equipment_id' => $this->equipment_id,
            'issue_date' => $this->issue_date,
            'issued_by' => $this->issued_by,
            'verified_by' => $this->verified_by,
            'status' => $this->status,
            'template_id' => 1,
            'datetime_added' => now(),
        ]);

        $this->reset(['certificate_number', 'patient_id', 'equipment_id', 'issued_by', 'verified_by']);
        $this->certificate_type = 'lab_result';
        $this->issue_date = date('Y-m-d');
        $this->status = 'draft';
        $this->flashMessage = 'Certificate added successfully!';
        $this->resetPage();
    }

    public function delete($id)
    {
        $certificate = Certificate::find($id);
        if ($certificate) {
            $certificate->delete();
            $this->flashMessage = 'Certificate deleted successfully!';
        }
    }

    public function with(): array
    {
        return [
            'certificates' => Certificate::with(['patient', 'equipment', 'issuedBy', 'verifiedBy'])
                ->when($this->search, function ($query) {
                    $query->where('certificate_number', 'like', '%' . $this->search . '%')
                          ->orWhereHas('patient', function($q) {
                              $q->where('firstname', 'like', '%' . $this->search . '%')
                                ->orWhere('lastname', 'like', '%' . $this->search . '%');
                          });
                })
                ->when($this->filterType, function ($query) {
                    $query->where('certificate_type', $this->filterType);
                })
                ->when($this->filterStatus, function ($query) {
                    $query->where('status', $this->filterStatus);
                })
                ->orderBy('issue_date', 'desc')
                ->paginate(50),
            'patients' => Patient::active()->orderBy('lastname')->get(),
            'equipment' => Equipment::active()->orderBy('name')->get(),
            'employees' => Employee::active()->orderBy('lastname')->get()
        ];
    }
};
?>

<div class="p-6">
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-gray-900 flex items-center">
            <svg class="w-7 h-7 mr-2 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
            </svg>
            Certificates Management
        </h1>
        <p class="text-sm text-gray-600 mt-1">Issue and manage laboratory certificates</p>
    </div>

    @if($flashMessage)
        <div class="mb-6 p-4 bg-green-50 border-l-4 border-green-500 rounded">
            <p class="text-green-800">{{ $flashMessage }}</p>
        </div>
    @endif

    <div class="bg-white rounded-lg shadow-sm mb-6">
        <div class="px-6 py-4 border-b border-gray-200">
            <h2 class="text-lg font-semibold text-gray-900">Issue New Certificate</h2>
        </div>
        <form wire:submit.prevent="save" class="p-6">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Certificate Number *</label>
                    <input type="text" wire:model="certificate_number" placeholder="e.g., CERT-2026-001" 
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    @error('certificate_number') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Type *</label>
                    <select wire:model="certificate_type" 
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        <option value="lab_result">Lab Result</option>
                        <option value="calibration">Calibration</option>
                        <option value="compliance">Compliance</option>
                        <option value="safety">Safety</option>
                        <option value="other">Other</option>
                    </select>
                    @error('certificate_type') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Issue Date *</label>
                    <input type="date" wire:model="issue_date" 
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    @error('issue_date') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                </div>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Patient (if applicable)</label>
                    <select wire:model="patient_id" 
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        <option value="">Select Patient</option>
                        @foreach($patients as $patient)
                            <option value="{{ $patient->patient_id }}">{{ $patient->full_name }}</option>
                        @endforeach
                    </select>
                    @error('patient_id') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Equipment (if applicable)</label>
                    <select wire:model="equipment_id" 
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        <option value="">Select Equipment</option>
                        @foreach($equipment as $item)
                            <option value="{{ $item->equipment_id }}">{{ $item->name }}</option>
                        @endforeach
                    </select>
                    @error('equipment_id') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                </div>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Issued By *</label>
                    <select wire:model="issued_by" 
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        <option value="">Select Employee</option>
                        @foreach($employees as $employee)
                            <option value="{{ $employee->employee_id }}">{{ $employee->full_name }}</option>
                        @endforeach
                    </select>
                    @error('issued_by') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Verified By</label>
                    <select wire:model="verified_by" 
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        <option value="">Select Employee</option>
                        @foreach($employees as $employee)
                            <option value="{{ $employee->employee_id }}">{{ $employee->full_name }}</option>
                        @endforeach
                    </select>
                    @error('verified_by') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Status *</label>
                    <select wire:model="status" 
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        <option value="draft">Draft</option>
                        <option value="issued">Issued</option>
                        <option value="revoked">Revoked</option>
                    </select>
                    @error('status') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                </div>
            </div>
            <div class="flex justify-end">
                <button type="submit" 
                        class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 transition-colors flex items-center">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    Issue Certificate
                </button>
            </div>
        </form>
    </div>

    <div class="bg-white rounded-lg shadow-sm mb-6">
        <div class="p-6">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Search Certificates</label>
                    <input type="text" wire:model.live="search" placeholder="Search by number or pa..." 
                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Type</label>
                    <select wire:model.live="filterType" 
                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="">All Types</option>
                        <option value="lab_result">Lab Result</option>
                        <option value="calibration">Calibration</option>
                        <option value="compliance">Compliance</option>
                        <option value="safety">Safety</option>
                        <option value="other">Other</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                    <select wire:model.live="filterStatus" 
                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="">All Status</option>
                        <option value="draft">Draft</option>
                        <option value="issued">Issued</option>
                        <option value="revoked">Revoked</option>
                    </select>
                </div>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-lg shadow-sm">
        <div class="px-6 py-4 border-b border-gray-200">
            <div class="flex items-center justify-between">
                <h2 class="text-lg font-semibold text-gray-900">Issued Certificates</h2>
                <span class="text-sm text-gray-600">{{ $certificates->total() }} total</span>
            </div>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Certificate #</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Type</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Patient/Equipment</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Issue Date</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Issued By</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($certificates as $cert)
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                <div class="font-medium">{{ $cert->certificate_number }}</div>
                                <div class="text-xs text-gray-500">ID: {{ $cert->certificate_id }}</div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm">
                                <span class="px-2 py-1 text-xs rounded-full bg-blue-100 text-blue-800">
                                    {{ ucwords(str_replace('_', ' ', $cert->certificate_type)) }}
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                                @if($cert->patient)
                                    <div class="flex items-center">
                                        <svg class="w-4 h-4 mr-1 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                                        </svg>
                                        {{ $cert->patient->full_name }}
                                    </div>
                                @elseif($cert->equipment)
                                    <div class="flex items-center">
                                        <svg class="w-4 h-4 mr-1 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                                        </svg>
                                        {{ $cert->equipment->name }}
                                    </div>
                                @else
                                    <span class="text-gray-400">N/A</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                                {{ $cert->issue_date ? \Carbon\Carbon::parse($cert->issue_date)->format('M d, Y') : 'N/A' }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                                {{ $cert->issuedBy->full_name ?? 'N/A' }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm">
                                @if($cert->status == 'issued')
                                    <span class="px-2 py-1 text-xs rounded-full bg-green-100 text-green-800 flex items-center w-fit">
                                        <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                        </svg>
                                        Issued
                                    </span>
                                @elseif($cert->status == 'draft')
                                    <span class="px-2 py-1 text-xs rounded-full bg-yellow-100 text-yellow-800 flex items-center w-fit">
                                        <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                                        </svg>
                                        Draft
                                    </span>
                                @else
                                    <span class="px-2 py-1 text-xs rounded-full bg-red-100 text-red-800 flex items-center w-fit">
                                        <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                                        </svg>
                                        Revoked
                                    </span>
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium space-x-2">
                                <button type="button" wire:click="openEditModal({{ $cert->certificate_id }})" 
                                        class="text-orange-600 hover:text-orange-900">Edit</button>
                                <button wire:click="delete({{ $cert->certificate_id }})" 
                                        wire:confirm="Are you sure you want to delete this certificate?"
                                        class="text-red-600 hover:text-red-900">Delete</button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-6 py-12 text-center">
                                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                </svg>
                                <p class="mt-4 text-sm text-gray-600">No certificates found</p>
                                <p class="text-sm text-gray-500">Try adjusting your search or filter to find what you're looking for.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        
        @if($certificates->hasPages())
            <div class="px-6 py-4 border-t border-gray-200">
                {{ $certificates->links() }}
            </div>
        @endif
    </div>

    <!-- Edit Certificate Modal -->
    @if($showEditModal)
    <div class="fixed inset-0 z-50 overflow-y-auto" style="background-color: rgba(0, 0, 0, 0.5);">
        <div class="flex items-center justify-center min-h-screen p-4">
            <div class="bg-white rounded-lg shadow-xl max-w-3xl w-full">
                <!-- Modal Header -->
                <div class="px-6 py-4 border-b border-gray-200">
                    <div class="flex items-center justify-between">
                        <h3 class="text-xl font-semibold text-gray-900">Edit Certificate</h3>
                        <button type="button" wire:click="closeEditModal" class="text-gray-400 hover:text-gray-600">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </button>
                    </div>
                </div>

                <!-- Modal Body -->
                <form wire:submit.prevent="updateCertificate">
                    <div class="p-6 max-h-[70vh] overflow-y-auto">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                            <!-- Certificate Number -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                    Certificate Number <span class="text-red-500">*</span>
                                </label>
                                <input type="text" wire:model="editCertificateNumber" 
                                       class="w-full px-3 py-2.5 border border-gray-300 rounded-md focus:outline-none focus:ring-1 focus:ring-blue-500 focus:border-blue-500"
                                       placeholder="e.g., CERT-2026-001">
                                @error('editCertificateNumber') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                            </div>

                            <!-- Certificate Type -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">
                                    Type <span class="text-red-500">*</span>
                                </label>
                                <select wire:model="editCertificateType" 
                                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                                    <option value="lab_result">Lab Result</option>
                                    <option value="calibration">Calibration</option>
                                    <option value="compliance">Compliance</option>
                                    <option value="safety">Safety</option>
                                    <option value="other">Other</option>
                                </select>
                                @error('editCertificateType') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                            </div>

                            <!-- Issue Date -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">
                                    Issue Date <span class="text-red-500">*</span>
                                </label>
                                <input type="date" wire:model="editIssueDate" 
                                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                                @error('editIssueDate') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                            </div>

                            <!-- Status -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">
                                    Status <span class="text-red-500">*</span>
                                </label>
                                <select wire:model="editStatus" 
                                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                                    <option value="draft">Draft</option>
                                    <option value="issued">Issued</option>
                                    <option value="revoked">Revoked</option>
                                </select>
                                @error('editStatus') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                            </div>

                            <!-- Patient -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Patient (if applicable)</label>
                                <select wire:model="editPatientId" 
                                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                                    <option value="">Select Patient</option>
                                    @foreach($patients as $patient)
                                        <option value="{{ $patient->patient_id }}">{{ $patient->full_name }}</option>
                                    @endforeach
                                </select>
                                @error('editPatientId') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                            </div>

                            <!-- Equipment -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Equipment (if applicable)</label>
                                <select wire:model="editEquipmentId" 
                                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                                    <option value="">Select Equipment</option>
                                    @foreach($equipment as $item)
                                        <option value="{{ $item->equipment_id }}">{{ $item->name }}</option>
                                    @endforeach
                                </select>
                                @error('editEquipmentId') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                            </div>

                            <!-- Issued By -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">
                                    Issued By <span class="text-red-500">*</span>
                                </label>
                                <select wire:model="editIssuedBy" 
                                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                                    <option value="">Select Employee</option>
                                    @foreach($employees as $employee)
                                        <option value="{{ $employee->employee_id }}">{{ $employee->full_name }}</option>
                                    @endforeach
                                </select>
                                @error('editIssuedBy') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                            </div>

                            <!-- Verified By -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Verified By</label>
                                <select wire:model="editVerifiedBy" 
                                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                                    <option value="">Select Employee</option>
                                    @foreach($employees as $employee)
                                        <option value="{{ $employee->employee_id }}">{{ $employee->full_name }}</option>
                                    @endforeach
                                </select>
                                @error('editVerifiedBy') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
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
                            Update Certificate
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
