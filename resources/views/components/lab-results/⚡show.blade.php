<?php

use Livewire\Component;
use App\Models\LabResult;
use App\Models\Patient;
use App\Models\Test;
use App\Models\Employee;

new class extends Component
{
    public $result;
    public $showEditModal = false;

    // Edit Modal Properties
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

    public function mount($labResult)
    {
        $this->result = $labResult;
        $this->result->load(['patient', 'test', 'performedBy', 'verifiedBy']);
    }

    public function openEditModal()
    {
        $this->editPatientId = $this->result->patient_id;
        $this->editTestId = $this->result->test_id;
        $this->editResultDate = $this->result->result_date ? \Carbon\Carbon::parse($this->result->result_date)->format('Y-m-d') : '';
        $this->editFindings = $this->result->findings;
        $this->editNormalRange = $this->result->normal_range;
        $this->editResultValue = $this->result->result_value;
        $this->editRemarks = $this->result->remarks;
        $this->editPerformedBy = $this->result->performed_by;
        $this->editVerifiedBy = $this->result->verified_by;
        $this->editStatus = $this->result->status;

        $this->showEditModal = true;
    }

    public function closeEditModal()
    {
        $this->showEditModal = false;
        $this->reset(['editPatientId', 'editTestId', 'editResultDate', 'editFindings', 'editNormalRange', 'editResultValue', 'editRemarks', 'editPerformedBy', 'editVerifiedBy', 'editStatus']);
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

        $this->result->update([
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

        // Reload the result with relationships
        $this->result->load(['patient', 'test', 'performedBy', 'verifiedBy']);

        session()->flash('success', 'Lab result updated successfully!');
        $this->closeEditModal();
    }

    public function with(): array
    {
        return [
            'patients' => Patient::active()->orderBy('lastname')->get(),
            'tests' => Test::active()->orderBy('label')->get(),
            'employees' => Employee::active()->orderBy('lastname')->get()
        ];
    }
};
?>

<div class="p-8 space-y-6 bg-gray-50 min-h-screen">
    <!-- Header with Back Button -->
    <div class="flex items-center justify-between mb-6">
        <div class="flex items-center space-x-4">
            <a href="{{ route('lab-results.index') }}" class="text-gray-600 hover:text-gray-800">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                </svg>
            </a>
            <div>
                <h1 class="text-3xl font-bold text-gray-800">Lab Result Details</h1>
                <p class="text-gray-600 mt-1">Result ID: {{ $result->lab_result_id }}</p>
            </div>
        </div>
        <div class="flex items-center space-x-2">
            @can('lab-results.edit')
            <button type="button" wire:click="openEditModal" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg text-sm font-medium transition-colors">
                Edit Result
            </button>
            @endcan
            @can('lab-results.delete')
            <button type="button" onclick="openDeleteModal()" class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white rounded-lg text-sm font-medium transition-colors">
                Delete Result
            </button>
            @endcan
        </div>
    </div>

    <!-- Success Message -->
    @if(session()->has('success'))
        <div class="mb-6 bg-white border-l-4 border-green-500 shadow-sm rounded-lg p-4 flex items-center justify-between">
            <div class="flex items-center">
                <svg class="w-5 h-5 text-green-500 mr-3" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                </svg>
                <span class="text-sm font-medium text-gray-900">{{ session('success') }}</span>
            </div>
        </div>
    @endif

    <!-- Result Information Card -->
    <div class="bg-white rounded-lg shadow border border-gray-200">
        <div class="p-6 border-b border-gray-200">
            <h3 class="text-lg font-semibold text-gray-900">Result Information</h3>
        </div>
        <div class="p-6">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Patient -->
                <div>
                    <label class="block text-sm font-medium text-gray-600 mb-1">Patient</label>
                    <p class="text-base font-semibold text-gray-900">{{ $result->patient->full_name ?? 'N/A' }}</p>
                </div>

                <!-- Test -->
                <div>
                    <label class="block text-sm font-medium text-gray-600 mb-1">Test</label>
                    <p class="text-base font-semibold text-gray-900">{{ $result->test->label ?? 'N/A' }}</p>
                </div>

                <!-- Result Date -->
                <div>
                    <label class="block text-sm font-medium text-gray-600 mb-1">Result Date</label>
                    <p class="text-base text-gray-900">{{ $result->result_date ? \Carbon\Carbon::parse($result->result_date)->format('F d, Y') : 'N/A' }}</p>
                </div>

                <!-- Status -->
                <div>
                    <label class="block text-sm font-medium text-gray-600 mb-1">Status</label>
                    <span class="inline-flex px-3 py-1 text-sm font-medium rounded-full
                        {{ $result->status === 'final' ? 'bg-green-100 text-green-800' : ($result->status === 'revised' ? 'bg-blue-100 text-blue-800' : 'bg-yellow-100 text-yellow-800') }}">
                        {{ ucfirst($result->status ?? 'draft') }}
                    </span>
                </div>

                <!-- Result Value -->
                <div>
                    <label class="block text-sm font-medium text-gray-600 mb-1">Result Value</label>
                    <p class="text-base text-gray-900">{{ $result->result_value ?? 'N/A' }}</p>
                </div>

                <!-- Normal Range -->
                <div>
                    <label class="block text-sm font-medium text-gray-600 mb-1">Normal Range</label>
                    <p class="text-base text-gray-900">{{ $result->normal_range ?? 'N/A' }}</p>
                </div>

                <!-- Performed By -->
                <div>
                    <label class="block text-sm font-medium text-gray-600 mb-1">Performed By</label>
                    <p class="text-base text-gray-900">{{ $result->performedBy->full_name ?? 'N/A' }}</p>
                </div>

                <!-- Verified By -->
                <div>
                    <label class="block text-sm font-medium text-gray-600 mb-1">Verified By</label>
                    <p class="text-base text-gray-900">{{ $result->verifiedBy->full_name ?? 'N/A' }}</p>
                </div>

                <!-- Findings (Full Width) -->
                @if($result->findings)
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-600 mb-1">Findings</label>
                    <p class="text-base text-gray-900 whitespace-pre-wrap">{{ $result->findings }}</p>
                </div>
                @endif

                <!-- Remarks (Full Width) -->
                @if($result->remarks)
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-600 mb-1">Remarks</label>
                    <p class="text-base text-gray-900 whitespace-pre-wrap">{{ $result->remarks }}</p>
                </div>
                @endif
            </div>
        </div>
    </div>

    <!-- System Information Card -->
    <div class="bg-white rounded-lg shadow border border-gray-200">
        <div class="p-6 border-b border-gray-200">
            <h3 class="text-lg font-semibold text-gray-900">System Information</h3>
        </div>
        <div class="p-6">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Date Added -->
                <div>
                    <label class="block text-sm font-medium text-gray-600 mb-1">Date Added</label>
                    <p class="text-base text-gray-900">{{ \Carbon\Carbon::parse($result->datetime_added)->format('F d, Y g:i A') }}</p>
                </div>

                <!-- Date Modified -->
                <div>
                    <label class="block text-sm font-medium text-gray-600 mb-1">Last Modified</label>
                    <p class="text-base text-gray-900">{{ $result->datetime_modified ? \Carbon\Carbon::parse($result->datetime_modified)->format('F d, Y g:i A') : '-' }}</p>
                </div>
            </div>
        </div>
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

    {{-- Delete Confirmation Modal --}}
    <div id="deleteModal" class="fixed inset-0 z-50 overflow-y-auto hidden" style="background-color: rgba(0, 0, 0, 0.5);">
        <div class="flex items-center justify-center min-h-screen p-4">
            <div class="bg-white rounded-lg shadow-xl max-w-md w-full">
                <!-- Modal Header -->
                <div class="px-6 py-4 border-b border-gray-200">
                    <div class="flex items-center justify-between">
                        <h3 class="text-xl font-semibold text-gray-900">
                            Confirm Deletion
                        </h3>
                        <button type="button" onclick="closeDeleteModal()" class="text-gray-400 hover:text-gray-600">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </button>
                    </div>
                </div>

                <!-- Modal Body -->
                <div class="p-6">
                    <div class="flex items-start space-x-3">
                        <div class="flex-shrink-0">
                            <svg class="w-6 h-6 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z"/>
                            </svg>
                        </div>
                        <div class="flex-1">
                            <p class="text-sm text-gray-700">
                                Are you sure you want to delete this lab result for <strong>{{ $result->patient->full_name ?? 'Unknown Patient' }}</strong> - <strong>{{ $result->test->label ?? 'Unknown Test' }}</strong>? This action cannot be undone.
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Modal Footer -->
                <div class="px-6 py-4 bg-gray-50 border-t border-gray-200 flex justify-end space-x-3 rounded-b-lg">
                    <button type="button" onclick="closeDeleteModal()"
                            class="px-5 py-2.5 border border-gray-300 rounded-md text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2">
                        Cancel
                    </button>
                    <form action="{{ route('lab-results.destroy', $result) }}" method="POST" class="inline-block">
                        @csrf
                        @method('DELETE')
                        <button type="submit"
                                class="px-5 py-2.5 bg-red-600 text-white text-sm rounded-md font-medium hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2">
                            Delete
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function openDeleteModal() {
    document.getElementById('deleteModal').classList.remove('hidden');
}

function closeDeleteModal() {
    document.getElementById('deleteModal').classList.add('hidden');
}

// Close modal when clicking outside
document.getElementById('deleteModal').addEventListener('click', function(event) {
    if (event.target === this) {
        closeDeleteModal();
    }
});

// Close modal on Escape key
document.addEventListener('keydown', function(event) {
    if (event.key === 'Escape' && !document.getElementById('deleteModal').classList.contains('hidden')) {
        closeDeleteModal();
    }
});
</script>