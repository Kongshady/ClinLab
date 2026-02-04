<?php

use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Validate;
use App\Models\LabResult;
use App\Models\Patient;
use App\Models\Test;

new class extends Component
{
    use WithPagination;

    #[Validate('required|exists:patient,patient_id')]
    public $patient_id = '';

    #[Validate('required|exists:test,test_id')]
    public $test_id = '';

    #[Validate('required|date')]
    public $result_date = '';

    #[Validate('nullable|string')]
    public $result_value = '';

    #[Validate('nullable|string')]
    public $normal_range = '';

    #[Validate('nullable|string')]
    public $findings = '';

    #[Validate('nullable|string')]
    public $remarks = '';

    #[Validate('nullable|string|max:50')]
    public $status = 'Pending';

    public $search = '';
    public $flashMessage = '';

    public function mount()
    {
        if (session()->has('success')) {
            $this->flashMessage = session('success');
        }
        $this->result_date = date('Y-m-d');
    }

    public function save()
    {
        $this->validate();

        LabResult::create([
            'patient_id' => $this->patient_id,
            'test_id' => $this->test_id,
            'result_date' => $this->result_date,
            'result_value' => $this->result_value,
            'normal_range' => $this->normal_range,
            'findings' => $this->findings,
            'remarks' => $this->remarks,
            'status' => $this->status,
            'datetime_added' => now(),
        ]);

        $this->reset(['patient_id', 'test_id', 'result_value', 'normal_range', 'findings', 'remarks']);
        $this->result_date = date('Y-m-d');
        $this->status = 'Pending';
        $this->flashMessage = 'Lab result added successfully!';
        $this->resetPage();
    }

    public function delete($id)
    {
        $labResult = LabResult::find($id);
        if ($labResult) {
            $labResult->delete();
            $this->flashMessage = 'Lab result deleted successfully!';
        }
    }

    public function with(): array
    {
        return [
            'labResults' => LabResult::with(['patient', 'test'])
                ->when($this->search, function ($query) {
                    $query->whereHas('patient', function($q) {
                        $q->where('firstname', 'like', '%' . $this->search . '%')
                          ->orWhere('lastname', 'like', '%' . $this->search . '%');
                    });
                })
                ->orderBy('lab_result_id', 'desc')
                ->paginate(50),
            'patients' => Patient::active()->orderBy('lastname')->get(),
            'tests' => Test::active()->orderBy('label')->get()
        ];
    }
};
?>

<div class="min-h-screen bg-gradient-to-br from-pink-50 to-purple-50 p-6">
    <div class="max-w-7xl mx-auto">
        <div class="mb-8">
            <h1 class="text-4xl font-bold text-gray-800 mb-2">Lab Results Management</h1>
            <p class="text-gray-600">Manage laboratory test results</p>
        </div>

        @if($flashMessage)
            <div class="mb-6 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative" role="alert">
                <span class="block sm:inline">{{ $flashMessage }}</span>
            </div>
        @endif

        <div class="bg-white rounded-xl shadow-lg p-6 mb-8">
            <h2 class="text-2xl font-bold text-gray-800 mb-6">Add New Lab Result</h2>
            <form wire:submit.prevent="save">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Patient *</label>
                        <select wire:model="patient_id" 
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-pink-500 focus:border-transparent">
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
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-pink-500 focus:border-transparent">
                            <option value="">Select Test</option>
                            @foreach($tests as $test)
                                <option value="{{ $test->test_id }}">{{ $test->label }}</option>
                            @endforeach
                        </select>
                        @error('test_id') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Result Date *</label>
                        <input type="date" wire:model="result_date" 
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-pink-500 focus:border-transparent">
                        @error('result_date') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                    </div>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Result Value</label>
                        <input type="text" wire:model="result_value" 
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-pink-500 focus:border-transparent">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Normal Range</label>
                        <input type="text" wire:model="normal_range" placeholder="e.g., 70-100 mg/dL" 
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-pink-500 focus:border-transparent">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Status</label>
                        <select wire:model="status" 
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-pink-500 focus:border-transparent">
                            <option value="Pending">Pending</option>
                            <option value="Completed">Completed</option>
                            <option value="Verified">Verified</option>
                        </select>
                    </div>
                </div>
                <div class="mb-6">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Findings</label>
                    <textarea wire:model="findings" rows="3"
                              class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-pink-500 focus:border-transparent"></textarea>
                </div>
                <div class="mb-6">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Remarks</label>
                    <textarea wire:model="remarks" rows="2"
                              class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-pink-500 focus:border-transparent"></textarea>
                </div>
                <div>
                    <button type="submit" 
                            class="bg-gradient-to-r from-pink-500 to-purple-500 text-white px-6 py-3 rounded-lg hover:from-pink-600 hover:to-purple-600 transition duration-200 font-medium">
                        Add Lab Result
                    </button>
                </div>
            </form>
        </div>

        <div class="bg-white rounded-xl shadow-lg p-6">
            <div class="mb-6">
                <input type="text" wire:model.live="search" placeholder="Search by patient name..." 
                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-pink-500 focus:border-transparent">
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gradient-to-r from-pink-50 to-purple-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">ID</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">Patient</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">Test</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">Result</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">Date</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">Status</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @forelse($labResults as $result)
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ $result->lab_result_id }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                    {{ $result->patient->full_name ?? 'N/A' }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                                    {{ $result->test->label ?? 'N/A' }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">{{ $result->result_value ?? 'N/A' }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                                    {{ $result->result_date ? \Carbon\Carbon::parse($result->result_date)->format('m/d/Y') : 'N/A' }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm">
                                    <span class="px-2 py-1 text-xs rounded-full 
                                        {{ $result->status == 'Completed' ? 'bg-blue-100 text-blue-800' : '' }}
                                        {{ $result->status == 'Verified' ? 'bg-green-100 text-green-800' : '' }}
                                        {{ $result->status == 'Pending' ? 'bg-yellow-100 text-yellow-800' : '' }}">
                                        {{ $result->status }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium space-x-2">
                                    <a href="/lab-results/{{ $result->lab_result_id }}/edit" 
                                       class="text-orange-600 hover:text-orange-900">Edit</a>
                                    <button wire:click="delete({{ $result->lab_result_id }})" 
                                            wire:confirm="Are you sure you want to delete this result?"
                                            class="text-red-600 hover:text-red-900">Delete</button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-6 py-4 text-center text-gray-500">No lab results found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="mt-6">
                {{ $labResults->links() }}
            </div>
        </div>
    </div>
</div>