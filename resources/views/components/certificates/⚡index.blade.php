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

    public function mount()
    {
        if (session()->has('success')) {
            $this->flashMessage = session('success');
        }
        $this->issue_date = date('Y-m-d');
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

<div class="p-8">
    <!-- Page Header -->
    <div class="mb-8">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-3xl font-bold text-slate-900 flex items-center">
                    <div class="w-10 h-10 mr-3 bg-gradient-to-br from-blue-500 to-cyan-400 rounded-xl flex items-center justify-center shadow-lg">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                        </svg>
                    </div>
                    Certificates Management
                </h1>
                <p class="mt-2 text-sm text-slate-600">Issue and manage laboratory certificates</p>
            </div>
            <div class="flex items-center space-x-3">
                <button class="btn-secondary flex items-center">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/>
                    </svg>
                    Print Report
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

    <!-- Issue New Certificate Card -->
    <div class="card card-hover mb-8 overflow-hidden">
        <div class="px-6 py-5 border-b border-slate-200 bg-gradient-to-r from-slate-50 to-white">
            <h2 class="text-lg font-semibold text-slate-900 flex items-center">
                <svg class="w-5 h-5 mr-2 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                Issue New Certificate
            </h2>
        </div>
        <form wire:submit.prevent="save" class="p-6">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-5 mb-6">
                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-2">Certificate Number *</label>
                    <input type="text" wire:model="certificate_number" placeholder="e.g., CERT-2026-001" class="input-field">
                    @error('certificate_number') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                </div>
                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-2">Type *</label>
                    <select wire:model="certificate_type" class="input-field">
                        <option value="lab_result">Lab Result</option>
                        <option value="calibration">Calibration</option>
                        <option value="compliance">Compliance</option>
                        <option value="safety">Safety</option>
                        <option value="other">Other</option>
                    </select>
                    @error('certificate_type') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                </div>
                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-2">Issue Date *</label>
                    <input type="date" wire:model="issue_date" class="input-field">
                    @error('issue_date') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                </div>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-5 mb-6">
                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-2">Patient (if applicable)</label>
                    <select wire:model="patient_id" class="input-field">
                        <option value="">Select Patient</option>
                        @foreach($patients as $patient)
                            <option value="{{ $patient->patient_id }}">{{ $patient->full_name }}</option>
                        @endforeach
                    </select>
                    @error('patient_id') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                </div>
                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-2">Equipment (if applicable)</label>
                    <select wire:model="equipment_id" class="input-field">
                        <option value="">Select Equipment</option>
                        @foreach($equipment as $item)
                            <option value="{{ $item->equipment_id }}">{{ $item->name }}</option>
                        @endforeach
                    </select>
                    @error('equipment_id') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                </div>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-5 mb-6">
                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-2">Issued By *</label>
                    <select wire:model="issued_by" class="input-field">
                        <option value="">Select Employee</option>
                        @foreach($employees as $employee)
                            <option value="{{ $employee->employee_id }}">{{ $employee->full_name }}</option>
                        @endforeach
                    </select>
                    @error('issued_by') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                </div>
                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-2">Verified By</label>
                    <select wire:model="verified_by" class="input-field">
                        <option value="">Select Employee</option>
                        @foreach($employees as $employee)
                            <option value="{{ $employee->employee_id }}">{{ $employee->full_name }}</option>
                        @endforeach
                    </select>
                    @error('verified_by') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                </div>
                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-2">Status *</label>
                    <select wire:model="status" class="input-field">
                        <option value="draft">Draft</option>
                        <option value="issued">Issued</option>
                        <option value="revoked">Revoked</option>
                    </select>
                    @error('status') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                </div>
            </div>
            <div class="flex justify-end">
                <button type="submit" class="btn-primary flex items-center">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    Issue Certificate
                </button>
            </div>
        </form>
    </div>

    <!-- Filters Card -->
    <div class="card card-hover mb-8">
        <div class="p-6">
            <div class="grid grid-cols-1 md:grid-cols-10 gap-4">
                <div class="md:col-span-4">
                    <label class="block text-sm font-semibold text-slate-700 mb-2">Search Certificates</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <svg class="w-5 h-5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                            </svg>
                        </div>
                        <input type="text" wire:model.live="search" placeholder="Search by number or patient..." class="input-field pl-10">
                    </div>
                </div>
                <div class="md:col-span-3">
                    <label class="block text-sm font-semibold text-slate-700 mb-2">Type</label>
                    <select wire:model.live="filterType" class="input-field">
                        <option value="">All Types</option>
                        <option value="lab_result">Lab Result</option>
                        <option value="calibration">Calibration</option>
                        <option value="compliance">Compliance</option>
                        <option value="safety">Safety</option>
                        <option value="other">Other</option>
                    </select>
                </div>
                <div class="md:col-span-3">
                    <label class="block text-sm font-semibold text-slate-700 mb-2">Status</label>
                    <select wire:model.live="filterStatus" class="input-field">
                        <option value="">All Status</option>
                        <option value="draft">Draft</option>
                        <option value="issued">Issued</option>
                        <option value="revoked">Revoked</option>
                    </select>
                </div>
            </div>
        </div>
    </div>

    <!-- Certificates List Card -->
    <div class="card">
        <div class="px-6 py-5 border-b border-slate-200 bg-gradient-to-r from-slate-50 to-white">
            <div class="flex items-center justify-between">
                <h2 class="text-lg font-semibold text-slate-900 flex items-center">
                    <svg class="w-5 h-5 mr-2 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                    Issued Certificates
                </h2>
                <span class="text-sm text-slate-600">{{ $certificates->total() }} total</span>
            </div>
        </div>
        <div class="overflow-x-auto">
            <table class="table">
                <thead class="table-header">
                    <tr>
                        <th class="table-th">Certificate #</th>
                        <th class="table-th">Type</th>
                        <th class="table-th">Patient/Equipment</th>
                        <th class="table-th">Issue Date</th>
                        <th class="table-th">Issued By</th>
                        <th class="table-th">Status</th>
                        <th class="table-th">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200">
                    @forelse($certificates as $cert)
                        <tr class="table-row">
                            <td class="table-td">
                                <div class="font-semibold text-slate-900">{{ $cert->certificate_number }}</div>
                                <div class="text-xs text-slate-500">ID: {{ $cert->certificate_id }}</div>
                            </td>
                            <td class="table-td">
                                <span class="badge badge-blue">
                                    {{ ucwords(str_replace('_', ' ', $cert->certificate_type)) }}
                                </span>
                            </td>
                            <td class="table-td text-slate-600">
                                @if($cert->patient)
                                    <div class="flex items-center">
                                        <svg class="w-4 h-4 mr-1 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                                        </svg>
                                        {{ $cert->patient->full_name }}
                                    </div>
                                @elseif($cert->equipment)
                                    <div class="flex items-center">
                                        <svg class="w-4 h-4 mr-1 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                                        </svg>
                                        {{ $cert->equipment->name }}
                                    </div>
                                @else
                                    <span class="text-slate-400">N/A</span>
                                @endif
                            </td>
                            <td class="table-td text-slate-600">
                                {{ $cert->issue_date ? \Carbon\Carbon::parse($cert->issue_date)->format('M d, Y') : 'N/A' }}
                            </td>
                            <td class="table-td text-slate-600">
                                {{ $cert->issuedBy->full_name ?? 'N/A' }}
                            </td>
                            <td class="table-td">
                                @if($cert->status == 'issued')
                                    <span class="badge badge-green">
                                        <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                        </svg>
                                        Issued
                                    </span>
                                @elseif($cert->status == 'draft')
                                    <span class="badge badge-yellow">
                                        <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                                        </svg>
                                        Draft
                                    </span>
                                @else
                                    <span class="badge badge-red">
                                        <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                                        </svg>
                                        Revoked
                                    </span>
                                @endif
                            </td>
                            <td class="table-td">
                                <div class="flex items-center space-x-2">
                                    <a href="/certificates/{{ $cert->certificate_id }}/edit" 
                                       class="inline-flex items-center px-3 py-1.5 bg-amber-50 text-amber-700 text-xs font-medium rounded-lg hover:bg-amber-100 transition-colors">
                                        <svg class="w-3.5 h-3.5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                        </svg>
                                        Edit
                                    </a>
                                    <button wire:click="delete({{ $cert->certificate_id }})" 
                                            wire:confirm="Are you sure you want to delete this certificate?"
                                            class="inline-flex items-center px-3 py-1.5 bg-red-50 text-red-700 text-xs font-medium rounded-lg hover:bg-red-100 transition-colors">
                                        <svg class="w-3.5 h-3.5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                        </svg>
                                        Delete
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-6 py-12 text-center">
                                <svg class="mx-auto h-12 w-12 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                </svg>
                                <p class="mt-4 text-sm text-slate-600 font-medium">No certificates found</p>
                                <p class="text-sm text-slate-500">Try adjusting your search or filter to find what you're looking for.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        
        @if($certificates->hasPages())
            <div class="px-6 py-4 border-t border-slate-200 bg-slate-50">
                {{ $certificates->links() }}
            </div>
        @endif
    </div>
</div>
