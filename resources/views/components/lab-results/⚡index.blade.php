<?php

use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Validate;
use App\Models\LabResult;
use App\Models\Patient;
use App\Models\Test;
use App\Models\Employee;

new class extends Component
{
    use WithPagination;

    #[Validate('required|exists:patient,patient_id')]
    public $patient_id = '';

    #[Validate('required|exists:test,test_id')]
    public $test_id = '';

    #[Validate('nullable|date')]
    public $result_date = '';

    #[Validate('nullable|string')]
    public $findings = '';

    #[Validate('nullable|string|max:100')]
    public $normal_range = '';

    #[Validate('nullable|string|max:100')]
    public $result_value = '';

    #[Validate('nullable|string')]
    public $remarks = '';

    #[Validate('nullable|exists:employee,employee_id')]
    public $performed_by = '';

    #[Validate('nullable|exists:employee,employee_id')]
    public $verified_by = '';

    #[Validate('required|in:draft,final,revised')]
    public $status = 'draft';

    public $search = '';
    public $filterStatus = '';
    public $perPage = 10;
    public $flashMessage = '';

    // Edit Modal Properties
    public $showEditModal = false;
    public $editResultId = '';
    public $editPatientId = '';
    public $editTestId = '';
    public $editResultDate = '';
    public $editFindings = '';
    public $editNormalRange = '';
    public $editResultValue = '';
    public $editRemarks = '';
    public $editPerformedBy = '';
    public $editVerifiedBy = '';
    public $editStatus = 'draft';

    public bool $showForm = false;

    public function mount()
    {
        if (session()->has('success')) {
            $this->flashMessage = session('success');
        }
        $this->result_date = date('Y-m-d');
        $this->status = 'draft';
    }

    public function toggleForm()
    {
        $this->showForm = !$this->showForm;
        if (!$this->showForm) {
            $this->reset([
                'patient_id', 'test_id', 'result_value', 'normal_range',
                'performed_by', 'verified_by', 'findings', 'remarks'
            ]);
            $this->result_date = date('Y-m-d');
            $this->status = 'draft';
        }
    }

    public function openEditModal($resultId)
    {
        $result = LabResult::findOrFail($resultId);
        
        $this->editResultId = $result->lab_result_id;
        $this->editPatientId = $result->patient_id;
        $this->editTestId = $result->test_id;
        $this->editResultDate = $result->result_date ? \Carbon\Carbon::parse($result->result_date)->format('Y-m-d') : '';
        $this->editFindings = $result->findings;
        $this->editNormalRange = $result->normal_range;
        $this->editResultValue = $result->result_value;
        $this->editRemarks = $result->remarks;
        $this->editPerformedBy = $result->performed_by;
        $this->editVerifiedBy = $result->verified_by;
        $this->editStatus = $result->status;
        
        $this->showEditModal = true;
    }

    public function closeEditModal()
    {
        $this->showEditModal = false;
        $this->reset(['editResultId', 'editPatientId', 'editTestId', 'editResultDate', 'editFindings', 'editNormalRange', 'editResultValue', 'editRemarks', 'editPerformedBy', 'editVerifiedBy', 'editStatus']);
    }

    public function updateResult()
    {
        $validated = $this->validate([
            'editPatientId' => 'required|exists:patient,patient_id',
            'editTestId' => 'required|exists:test,test_id',
            'editResultDate' => 'nullable|date',
            'editFindings' => 'nullable|string',
            'editNormalRange' => 'nullable|string|max:100',
            'editResultValue' => 'nullable|string|max:100',
            'editRemarks' => 'nullable|string',
            'editPerformedBy' => 'nullable|exists:employee,employee_id',
            'editVerifiedBy' => 'nullable|exists:employee,employee_id',
            'editStatus' => 'required|in:draft,final,revised',
        ]);

        $result = LabResult::findOrFail($this->editResultId);
        $result->update([
            'patient_id' => $this->editPatientId,
            'test_id' => $this->editTestId,
            'result_date' => $this->editResultDate,
            'findings' => $this->editFindings,
            'normal_range' => $this->editNormalRange,
            'result_value' => $this->editResultValue,
            'remarks' => $this->editRemarks,
            'performed_by' => $this->editPerformedBy,
            'verified_by' => $this->editVerifiedBy,
            'status' => $this->editStatus,
            'datetime_modified' => now(),
        ]);

        $this->flashMessage = 'Lab result updated successfully!';
        $this->closeEditModal();
    }

    public function save()
    {
        $this->validate();

        LabResult::create([
            'patient_id' => $this->patient_id,
            'test_id' => $this->test_id,
            'result_date' => $this->result_date,
            'findings' => $this->findings,
            'normal_range' => $this->normal_range,
            'result_value' => $this->result_value,
            'remarks' => $this->remarks,
            'performed_by' => $this->performed_by,
            'verified_by' => $this->verified_by,
            'status' => $this->status,
            'datetime_added' => now(),
        ]);

        $this->reset(['patient_id', 'test_id', 'findings', 'normal_range', 'result_value', 'remarks', 'performed_by', 'verified_by']);
        $this->result_date = date('Y-m-d');
        $this->status = 'draft';
        $this->flashMessage = 'Lab result added successfully!';
        $this->showForm = false;
        $this->resetPage();
    }

    public function with(): array
    {
        $query = LabResult::with(['patient', 'test', 'performedBy', 'verifiedBy'])
            ->when($this->search, function ($query) {
                $query->whereHas('patient', function($q) {
                    $q->where('firstname', 'like', '%' . $this->search . '%')
                      ->orWhere('lastname', 'like', '%' . $this->search . '%');
                })
                ->orWhereHas('test', function($q) {
                    $q->where('label', 'like', '%' . $this->search . '%');
                });
            })
            ->when($this->filterStatus, function ($query) {
                $query->where('status', $this->filterStatus);
            })
            ->orderBy('result_date', 'desc');

        return [
            'results' => $this->perPage === 'all' ? $query->get() : $query->paginate((int)$this->perPage),
            'patients' => Patient::active()->orderBy('lastname')->get(),
            'tests' => Test::active()->orderBy('label')->get(),
            'employees' => Employee::active()->orderBy('lastname')->get()
        ];
    }
};
?>

<div class="p-6">
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-gray-900 flex items-center">
            <svg class="w-7 h-7 mr-2 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/>
            </svg>
            Laboratory Results Management
        </h1>
    </div>

    @if($flashMessage)
        <div class="mb-6 p-4 bg-green-50 border-l-4 border-green-500 rounded">
            <p class="text-green-800">{{ $flashMessage }}</p>
        </div>
    @endif

    <div class="bg-white rounded-lg shadow-sm mb-6">
        <div class="px-6 py-4 border-b border-gray-200 flex items-center justify-between">
            <h2 class="text-lg font-semibold text-gray-900">Add New Lab Result</h2>
            <button type="button" wire:click="toggleForm" 
                    class="px-4 py-2 rounded-md text-sm font-medium transition-colors {{ $showForm ? 'bg-gray-200 text-gray-700 hover:bg-gray-300' : 'bg-pink-600 text-white hover:bg-pink-700' }}">
                {{ $showForm ? 'Close Form' : 'Add New Lab Result' }}
            </button>
        </div>
        @if($showForm)
        <form wire:submit.prevent="save" class="p-6">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Patient *</label>
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
                    <label class="block text-sm font-medium text-gray-700 mb-2">Test *</label>
                    <select wire:model="test_id" 
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        <option value="">Select Test</option>
                        @foreach($tests as $test)
                            <option value="{{ $test->test_id }}">{{ $test->label }}</option>
                        @endforeach
                    </select>
                    @error('test_id') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Result Date</label>
                    <input type="date" wire:model="result_date" 
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    @error('result_date') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                </div>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Result Value</label>
                    <input type="text" wire:model="result_value" placeholder="e.g., 120 mg/dL" 
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    @error('result_value') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Normal Range</label>
                    <input type="text" wire:model="normal_range" placeholder="e.g., 70-110 mg/dL" 
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    @error('normal_range') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                </div>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Performed By</label>
                    <select wire:model="performed_by" 
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        <option value="">Select Employee</option>
                        @foreach($employees as $employee)
                            <option value="{{ $employee->employee_id }}">{{ $employee->full_name }}</option>
                        @endforeach
                    </select>
                    @error('performed_by') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
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
                        <option value="final">Final</option>
                        <option value="revised">Revised</option>
                    </select>
                    @error('status') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                </div>
            </div>
            <div class="mb-6">
                <label class="block text-sm font-medium text-gray-700 mb-2">Findings</label>
                <textarea wire:model="findings" rows="2" placeholder="Enter findings..."
                          class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"></textarea>
                @error('findings') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
            </div>
            <div class="mb-6">
                <label class="block text-sm font-medium text-gray-700 mb-2">Remarks</label>
                <textarea wire:model="remarks" rows="2" placeholder="Enter remarks..."
                          class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"></textarea>
                @error('remarks') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
            </div>
            <div class="flex justify-end">
                <button type="submit" 
                        class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 transition-colors">
                    Add Lab Result
                </button>
            </div>
        </form>
        @endif
    </div>

    <div class="bg-white rounded-lg shadow-sm">
        <div class="px-6 py-4 border-b border-gray-200">
            <h2 class="text-lg font-semibold text-gray-900">Laboratory Results List</h2>
        </div>
        <div class="p-6">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Search Patient/Test</label>
                    <input type="text" wire:model.live="search" placeholder="Search patient or test..." 
                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Filter by Status</label>
                    <select wire:model.live="filterStatus" 
                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="">All Status</option>
                        <option value="draft">Draft</option>
                        <option value="final">Final</option>
                        <option value="revised">Revised</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Rows per page</label>
                    <select wire:model.live="perPage" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
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
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Patient</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Test</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Result Date</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Result Value</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Status</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($results as $result)
                        <tr wire:key="lab-result-{{ $result->lab_result_id }}" class="hover:bg-gray-50">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm font-medium text-gray-900">
                                    {{ $result->patient->full_name ?? 'N/A' }}
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm text-gray-900">{{ $result->test->label ?? 'N/A' }}</div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm text-gray-500">
                                    {{ $result->result_date ? \Carbon\Carbon::parse($result->result_date)->format('M d, Y') : 'N/A' }}
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm text-gray-900">{{ $result->result_value ?? 'N/A' }}</div>
                                @if($result->normal_range)
                                    <div class="text-xs text-gray-500">Range: {{ $result->normal_range }}</div>
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                    @if($result->status === 'final') bg-green-100 text-green-800
                                    @elseif($result->status === 'revised') bg-blue-100 text-blue-800
                                    @else bg-yellow-100 text-yellow-800
                                    @endif">
                                    {{ ucfirst($result->status ?? 'draft') }}
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium space-x-2">
                                <a href="{{ route('lab-results.show', $result->lab_result_id) }}" 
                                   class="inline-block px-4 py-1.5 bg-blue-500 hover:bg-blue-600 text-white text-sm font-medium rounded-lg transition-colors">View</a>
                                <button type="button" wire:click="openEditModal({{ $result->lab_result_id }})" 
                                        class="px-4 py-1.5 bg-orange-500 hover:bg-orange-600 text-white text-sm font-medium rounded-lg transition-colors">Edit</button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-12 text-center">
                                <svg class="w-16 h-16 mx-auto mb-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                </svg>
                                <p class="text-gray-500 font-medium">No lab results found</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        
        @if($perPage !== 'all' && method_exists($results, 'hasPages') && $results->hasPages())
            <div class="px-6 py-4 border-t border-gray-200">
                {{ $results->links() }}
            </div>
        @endif
    </div>

    <!-- Edit Lab Result Modal -->
    @if($showEditModal)
    <div class="fixed inset-0 z-50 overflow-y-auto" style="background-color: rgba(0, 0, 0, 0.5);">
        <div class="flex items-center justify-center min-h-screen p-4">
            <div class="bg-white rounded-lg shadow-xl max-w-3xl w-full">
                <!-- Modal Header -->
                <div class="px-6 py-4 border-b border-gray-200">
                    <div class="flex items-center justify-between">
                        <h3 class="text-xl font-semibold text-gray-900">Edit Lab Result</h3>
                        <button type="button" wire:click="closeEditModal" class="text-gray-400 hover:text-gray-600">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </button>
                    </div>
                </div>

                <!-- Modal Body -->
                <form wire:submit.prevent="updateResult">
                    <div class="p-6 max-h-[70vh] overflow-y-auto">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                            <!-- Patient -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                    Patient <span class="text-red-500">*</span>
                                </label>
                                <select wire:model="editPatientId" 
                                        class="w-full px-3 py-2.5 border border-gray-300 rounded-md focus:outline-none focus:ring-1 focus:ring-blue-500 focus:border-blue-500">
                                    <option value="">Select Patient</option>
                                    @foreach($patients as $patient)
                                        <option value="{{ $patient->patient_id }}">{{ $patient->full_name }}</option>
                                    @endforeach
                                </select>
                                @error('editPatientId') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                            </div>

                            <!-- Test -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">
                                    Test <span class="text-red-500">*</span>
                                </label>
                                <select wire:model="editTestId" 
                                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                                    <option value="">Select Test</option>
                                    @foreach($tests as $test)
                                        <option value="{{ $test->test_id }}">{{ $test->label }}</option>
                                    @endforeach
                                </select>
                                @error('editTestId') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                            </div>

                            <!-- Result Date -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Result Date</label>
                                <input type="date" wire:model="editResultDate" 
                                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                                @error('editResultDate') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                            </div>

                            <!-- Status -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">
                                    Status <span class="text-red-500">*</span>
                                </label>
                                <select wire:model="editStatus" 
                                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                                    <option value="draft">Draft</option>
                                    <option value="final">Final</option>
                                    <option value="revised">Revised</option>
                                </select>
                                @error('editStatus') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                            </div>

                            <!-- Result Value -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Result Value</label>
                                <input type="text" wire:model="editResultValue" 
                                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                                       placeholder="Enter result value">
                                @error('editResultValue') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                            </div>

                            <!-- Normal Range -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Normal Range</label>
                                <input type="text" wire:model="editNormalRange" 
                                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                                       placeholder="e.g., 0-100 mg/dL">
                                @error('editNormalRange') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                            </div>

                            <!-- Performed By -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Performed By</label>
                                <select wire:model="editPerformedBy" 
                                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                                    <option value="">Select Employee</option>
                                    @foreach($employees as $employee)
                                        <option value="{{ $employee->employee_id }}">{{ $employee->full_name }}</option>
                                    @endforeach
                                </select>
                                @error('editPerformedBy') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
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

                            <!-- Findings (Full Width) -->
                            <div class="md:col-span-2">
                                <label class="block text-sm font-medium text-gray-700 mb-1">Findings</label>
                                <textarea wire:model="editFindings" rows="3" 
                                          class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                                          placeholder="Enter clinical findings"></textarea>
                                @error('editFindings') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                            </div>

                            <!-- Remarks (Full Width) -->
                            <div class="md:col-span-2">
                                <label class="block text-sm font-medium text-gray-700 mb-1">Remarks</label>
                                <textarea wire:model="editRemarks" rows="3" 
                                          class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                                          placeholder="Enter additional remarks"></textarea>
                                @error('editRemarks') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
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
                            Update Result
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
