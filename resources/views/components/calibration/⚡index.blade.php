<?php

use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Validate;
use App\Models\CalibrationRecord;
use App\Models\Equipment;
use App\Models\Employee;

new class extends Component
{
    use WithPagination;

    #[Validate('required|exists:equipment,equipment_id')]
    public $equipment_id = '';

    #[Validate('required|date')]
    public $calibration_date = '';

    #[Validate('required|exists:employee,employee_id')]
    public $performed_by = '';

    #[Validate('required|in:pass,fail,conditional')]
    public $result_status = 'pass';

    #[Validate('nullable|string')]
    public $notes = '';

    #[Validate('nullable|date')]
    public $next_calibration_date = '';

    public $search = '';
    public $filterStatus = '';
    public $flashMessage = '';

    public function mount()
    {
        if (session()->has('success')) {
            $this->flashMessage = session('success');
        }
        $this->calibration_date = date('Y-m-d');
    }

    public function save()
    {
        $this->validate();

        CalibrationRecord::create([
            'equipment_id' => $this->equipment_id,
            'calibration_date' => $this->calibration_date,
            'performed_by' => $this->performed_by,
            'result_status' => $this->result_status,
            'notes' => $this->notes,
            'next_calibration_date' => $this->next_calibration_date,
            'datetime_added' => now(),
        ]);

        $this->reset(['equipment_id', 'performed_by', 'notes', 'next_calibration_date']);
        $this->calibration_date = date('Y-m-d');
        $this->result_status = 'pass';
        $this->flashMessage = 'Calibration record added successfully!';
        $this->resetPage();
    }

    public function delete($id)
    {
        $record = CalibrationRecord::find($id);
        if ($record) {
            $record->delete();
            $this->flashMessage = 'Calibration record deleted successfully!';
        }
    }

    public function with(): array
    {
        return [
            'records' => CalibrationRecord::with(['equipment', 'performedBy'])
                ->when($this->search, function ($query) {
                    $query->whereHas('equipment', function($q) {
                        $q->where('name', 'like', '%' . $this->search . '%');
                    });
                })
                ->when($this->filterStatus, function ($query) {
                    $query->where('result_status', $this->filterStatus);
                })
                ->orderBy('calibration_date', 'desc')
                ->paginate(50),
            'equipment' => Equipment::active()->orderBy('name')->get(),
            'employees' => Employee::active()->orderBy('lastname')->get()
        ];
    }
};
?>

<div class="p-6">
    <div class="max-w-7xl mx-auto">
        <div class="mb-8">
            <h1 class="text-4xl font-bold text-gray-800 mb-2">Calibration Records</h1>
            <p class="text-gray-600">Manage equipment calibration and maintenance records</p>
        </div>

        @if($flashMessage)
            <div class="mb-6 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative" role="alert">
                <span class="block sm:inline">{{ $flashMessage }}</span>
            </div>
        @endif

        <div class="bg-white rounded-xl shadow-lg p-6 mb-8">
            <h2 class="text-2xl font-bold text-gray-800 mb-6">Add Calibration Record</h2>
            <form wire:submit.prevent="save">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Equipment *</label>
                        <select wire:model="equipment_id" 
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-pink-500 focus:border-transparent">
                            <option value="">Select Equipment</option>
                            @foreach($equipment as $item)
                                <option value="{{ $item->equipment_id }}">{{ $item->name }}</option>
                            @endforeach
                        </select>
                        @error('equipment_id') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Calibration Date *</label>
                        <input type="date" wire:model="calibration_date" 
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-pink-500 focus:border-transparent">
                        @error('calibration_date') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Performed By *</label>
                        <select wire:model="performed_by" 
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-pink-500 focus:border-transparent">
                            <option value="">Select Employee</option>
                            @foreach($employees as $employee)
                                <option value="{{ $employee->employee_id }}">{{ $employee->full_name }}</option>
                            @endforeach
                        </select>
                        @error('performed_by') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                    </div>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Result Status *</label>
                        <select wire:model="result_status" 
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-pink-500 focus:border-transparent">
                            <option value="pass">Pass</option>
                            <option value="fail">Fail</option>
                            <option value="conditional">Conditional</option>
                        </select>
                        @error('result_status') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Next Calibration Date</label>
                        <input type="date" wire:model="next_calibration_date" 
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-pink-500 focus:border-transparent">
                        @error('next_calibration_date') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                    </div>
                </div>
                <div class="mb-6">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Notes</label>
                    <textarea wire:model="notes" rows="3"
                              class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-pink-500 focus:border-transparent"></textarea>
                    @error('notes') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                </div>
                <div>
                    <button type="submit" 
                            class="bg-gradient-to-r from-pink-500 to-purple-500 text-white px-6 py-3 rounded-lg hover:from-pink-600 hover:to-purple-600 transition duration-200 font-medium">
                        Add Record
                    </button>
                </div>
            </form>
        </div>

        <div class="bg-white rounded-xl shadow-lg p-6">
            <div class="mb-6 grid grid-cols-1 md:grid-cols-2 gap-4">
                <input type="text" wire:model.live="search" placeholder="Search equipment..." 
                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-pink-500 focus:border-transparent">
                <select wire:model.live="filterStatus" 
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-pink-500 focus:border-transparent">
                    <option value="">All Status</option>
                    <option value="pass">Pass</option>
                    <option value="fail">Fail</option>
                    <option value="conditional">Conditional</option>
                </select>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gradient-to-r from-pink-50 to-purple-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">ID</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">Equipment</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">Date</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">Performed By</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">Status</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">Next Date</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @forelse($records as $record)
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ $record->record_id }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                    {{ $record->equipment->name ?? 'N/A' }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                                    {{ $record->calibration_date ? \Carbon\Carbon::parse($record->calibration_date)->format('m/d/Y') : 'N/A' }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                                    {{ $record->performedBy->full_name ?? 'N/A' }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm">
                                    <span class="px-2 py-1 text-xs rounded-full 
                                        {{ $record->result_status == 'pass' ? 'bg-green-100 text-green-800' : '' }}
                                        {{ $record->result_status == 'fail' ? 'bg-red-100 text-red-800' : '' }}
                                        {{ $record->result_status == 'conditional' ? 'bg-yellow-100 text-yellow-800' : '' }}">
                                        {{ ucfirst($record->result_status) }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                                    {{ $record->next_calibration_date ? \Carbon\Carbon::parse($record->next_calibration_date)->format('m/d/Y') : 'N/A' }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium space-x-2">
                                    <a href="/calibration/{{ $record->record_id }}/edit" 
                                       class="text-orange-600 hover:text-orange-900">Edit</a>
                                    <button wire:click="delete({{ $record->record_id }})" 
                                            wire:confirm="Are you sure you want to delete this record?"
                                            class="text-red-600 hover:text-red-900">Delete</button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-6 py-4 text-center text-gray-500">No calibration records found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="mt-6">
                {{ $records->links() }}
            </div>
        </div>
    </div>
</div>
