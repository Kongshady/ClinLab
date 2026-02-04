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
    public $perPage = 'all';
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
        $query = CalibrationRecord::with(['equipment', 'performedBy'])
            ->when($this->search, function ($query) {
                $query->whereHas('equipment', function($q) {
                    $q->where('name', 'like', '%' . $this->search . '%');
                });
            })
            ->when($this->filterStatus, function ($query) {
                $query->where('result_status', $this->filterStatus);
            })
            ->orderBy('calibration_date', 'desc');

        return [
            'records' => $this->perPage === 'all' ? $query->get() : $query->paginate((int)$this->perPage),
            'equipment' => Equipment::active()->orderBy('name')->get(),
            'employees' => Employee::active()->orderBy('lastname')->get()
        ];
    }
};
?>

<div class="p-6">
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-gray-900 flex items-center">
            <svg class="w-7 h-7 mr-2 text-teal-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/>
            </svg>
            Calibration Records
        </h1>
    </div>

    @if($flashMessage)
        <div class="mb-6 p-4 bg-green-50 border-l-4 border-green-500 rounded">
            <p class="text-green-800">{{ $flashMessage }}</p>
        </div>
    @endif

    <div class="bg-white rounded-lg shadow-sm mb-6">
        <div class="px-6 py-4 border-b border-gray-200">
            <h2 class="text-lg font-semibold text-gray-900">Add Calibration Record</h2>
        </div>
        <form wire:submit.prevent="save" class="p-6">
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
                        <label class="block text-sm font-medium text-gray-700 mb-1">Calibration Date *</label>
                        <input type="date" wire:model="calibration_date" 
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-teal-500">
                        @error('calibration_date') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Performed By *</label>
                        <select wire:model="performed_by" 
                                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-teal-500">
                            <option value="">Select Employee</option>
                            @foreach($employees as $employee)
                                <option value="{{ $employee->employee_id }}">{{ $employee->full_name }}</option>
                            @endforeach
                        </select>
                        @error('performed_by') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                    </div>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Result Status *</label>
                        <select wire:model="result_status" 
                                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-teal-500">
                            <option value="pass">Pass</option>
                            <option value="fail">Fail</option>
                            <option value="conditional">Conditional</option>
                        </select>
                        @error('result_status') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Next Calibration Date</label>
                        <input type="date" wire:model="next_calibration_date" 
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-teal-500">
                        @error('next_calibration_date') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                    </div>
                </div>
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Notes</label>
                    <textarea wire:model="notes" rows="3"
                              class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-teal-500"></textarea>
                    @error('notes') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                </div>
                <div class="flex justify-end">
                    <button type="submit" 
                            class="px-4 py-2 bg-teal-600 text-white rounded-md hover:bg-teal-700 transition-colors">
                        Add Record
                    </button>
                </div>
            </form>
        </div>

    <div class="bg-white rounded-lg shadow-sm">
        <div class="px-6 py-4 border-b border-gray-200">
            <h2 class="text-lg font-semibold text-gray-900">Calibration Records List</h2>
        </div>
        <div class="p-6">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Search Equipment</label>
                    <input type="text" wire:model.live="search" placeholder="Search equipment..." 
                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-teal-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Filter by Status</label>
                    <select wire:model.live="filterStatus" 
                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-teal-500">
                        <option value="">All Status</option>
                        <option value="pass">Pass</option>
                        <option value="fail">Fail</option>
                        <option value="conditional">Conditional</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Rows per page</label>
                    <select wire:model.live="perPage" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-teal-500">
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
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ID</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Equipment</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Performed By</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Next Date</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
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
            
            @if($perPage !== 'all' && method_exists($records, 'hasPages') && $records->hasPages())
                <div class="px-6 py-4 border-t border-gray-200">
                    {{ $records->links() }}
                </div>
            @endif
        </div>
    </div>
</div>
