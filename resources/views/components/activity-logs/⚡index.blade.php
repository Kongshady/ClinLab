<?php

use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Validate;
use App\Models\ActivityLog;
use App\Models\Employee;

new class extends Component
{
    use WithPagination;

    #[Validate('required|exists:employee,employee_id')]
    public $employee_id = '';

    #[Validate('required|string')]
    public $description = '';

    public $search = '';
    public $filterEmployee = '';
    public $dateFrom = '';
    public $dateTo = '';
    public $flashMessage = '';

    public function mount()
    {
        if (session()->has('success')) {
            $this->flashMessage = session('success');
        }
    }

    public function save()
    {
        $this->validate();

        ActivityLog::create([
            'employee_id' => $this->employee_id,
            'description' => $this->description,
            'datetime_added' => now(),
            'status_code' => 1,
        ]);

        $this->reset(['employee_id', 'description']);
        $this->flashMessage = 'Activity log added successfully!';
        $this->resetPage();
    }

    public function delete($id)
    {
        $log = ActivityLog::find($id);
        if ($log) {
            $log->delete();
            $this->flashMessage = 'Activity log deleted successfully!';
        }
    }

    public function with(): array
    {
        return [
            'logs' => ActivityLog::with('employee')
                ->when($this->search, function ($query) {
                    $query->where('description', 'like', '%' . $this->search . '%')
                          ->orWhereHas('employee', function($q) {
                              $q->where('firstname', 'like', '%' . $this->search . '%')
                                ->orWhere('lastname', 'like', '%' . $this->search . '%');
                          });
                })
                ->when($this->filterEmployee, function ($query) {
                    $query->where('employee_id', $this->filterEmployee);
                })
                ->when($this->dateFrom, function ($query) {
                    $query->whereDate('datetime_added', '>=', $this->dateFrom);
                })
                ->when($this->dateTo, function ($query) {
                    $query->whereDate('datetime_added', '<=', $this->dateTo);
                })
                ->orderBy('datetime_added', 'desc')
                ->paginate(50),
            'employees' => Employee::active()->orderBy('lastname')->get()
        ];
    }
};
?>

<div class="min-h-screen bg-gradient-to-br from-pink-50 to-purple-50 p-6">
    <div class="max-w-7xl mx-auto">
        <div class="mb-8">
            <h1 class="text-4xl font-bold text-gray-800 mb-2">Activity Logs</h1>
            <p class="text-gray-600">Track employee activities and system events</p>
        </div>

        @if($flashMessage)
            <div class="mb-6 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative" role="alert">
                <span class="block sm:inline">{{ $flashMessage }}</span>
            </div>
        @endif

        <div class="bg-white rounded-xl shadow-lg p-6 mb-8">
            <h2 class="text-2xl font-bold text-gray-800 mb-6">Add Activity Log</h2>
            <form wire:submit.prevent="save">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Employee *</label>
                        <select wire:model="employee_id" 
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-pink-500 focus:border-transparent">
                            <option value="">Select Employee</option>
                            @foreach($employees as $employee)
                                <option value="{{ $employee->employee_id }}">{{ $employee->full_name }}</option>
                            @endforeach
                        </select>
                        @error('employee_id') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                    </div>
                </div>
                <div class="mb-6">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Description *</label>
                    <textarea wire:model="description" rows="3" placeholder="Enter activity description..."
                              class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-pink-500 focus:border-transparent"></textarea>
                    @error('description') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                </div>
                <div>
                    <button type="submit" 
                            class="bg-gradient-to-r from-pink-500 to-purple-500 text-white px-6 py-3 rounded-lg hover:from-pink-600 hover:to-purple-600 transition duration-200 font-medium">
                        Add Log
                    </button>
                </div>
            </form>
        </div>

        <!-- Filters -->
        <div class="bg-white rounded-xl shadow-lg p-6 mb-8">
            <h2 class="text-xl font-bold text-gray-800 mb-4">Filters</h2>
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Search</label>
                    <input type="text" wire:model.live="search" placeholder="Search description or employee..." 
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-pink-500 focus:border-transparent">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Employee</label>
                    <select wire:model.live="filterEmployee" 
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-pink-500 focus:border-transparent">
                        <option value="">All</option>
                        @foreach($employees as $employee)
                            <option value="{{ $employee->employee_id }}">{{ $employee->full_name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Date From</label>
                    <input type="date" wire:model.live="dateFrom" 
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-pink-500 focus:border-transparent">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Date To</label>
                    <input type="date" wire:model.live="dateTo" 
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-pink-500 focus:border-transparent">
                </div>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-lg p-6">
            <h2 class="text-xl font-bold text-gray-800 mb-4">Activity History</h2>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gradient-to-r from-pink-50 to-purple-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">ID</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">Date & Time</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">Employee</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">Description</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @forelse($logs as $log)
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ $log->activity_log_id }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                                    {{ $log->datetime_added ? \Carbon\Carbon::parse($log->datetime_added)->format('m/d/Y h:i A') : 'N/A' }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                    {{ $log->employee->full_name ?? 'N/A' }}
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-600">{{ $log->description }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium space-x-2">
                                    <button wire:click="delete({{ $log->activity_log_id }})" 
                                            wire:confirm="Are you sure you want to delete this log?"
                                            class="text-red-600 hover:text-red-900">Delete</button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-6 py-4 text-center text-gray-500">No activity logs found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="mt-6">
                {{ $logs->links() }}
            </div>
        </div>
    </div>
</div>
