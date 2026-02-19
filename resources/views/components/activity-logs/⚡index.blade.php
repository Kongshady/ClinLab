<?php

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\ActivityLog;
use App\Models\Employee;

new class extends Component
{
    use WithPagination;

    public $search = '';
    public $filterEmployee = '';
    public $dateFrom = '';
    public $dateTo = '';
    public $perPage = 50;
    public $flashMessage = '';

    public function mount()
    {
        if (session()->has('success')) {
            $this->flashMessage = session('success');
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
                ->paginate($this->perPage),
            'employees' => Employee::active()->orderBy('lastname')->get()
        ];
    }
};
?>

<div class="min-h-screen bg-gray-50">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <!-- Success Message -->
        @if($flashMessage)
            <div class="mb-6 bg-white border-l-4 border-green-500 shadow-sm rounded-lg p-4 flex items-center justify-between">
                <div class="flex items-center">
                    <svg class="w-5 h-5 text-green-500 mr-3" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                    </svg>
                    <span class="text-sm font-medium text-gray-900">{{ $flashMessage }}</span>
                </div>
            </div>
        @endif

        <!-- Header -->
        <div class="mb-8">
            <h1 class="text-3xl font-bold text-gray-900">Activity Logs</h1>
            <p class="mt-1 text-sm text-gray-500">Audit trail of all employee actions in the system</p>
        </div>

        <!-- Filters Card -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden mb-8">
            <div class="px-8 py-6 border-b border-gray-200">
                <h2 class="text-lg font-bold text-gray-900">Filters</h2>
                <p class="mt-1 text-sm text-gray-500">Filter activity logs by employee, date range, or search</p>
            </div>
            <div class="px-8 py-6 bg-gray-50">
                <div class="grid grid-cols-1 md:grid-cols-5 gap-4">
                    <div class="space-y-2">
                        <label class="text-xs font-medium text-gray-700 uppercase tracking-wide">Search</label>
                        <input type="text" wire:model.live="search" placeholder="Activity description..." 
                               class="w-full px-4 py-2.5 bg-white border border-gray-200 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all text-sm">
                    </div>
                    <div class="space-y-2">
                        <label class="text-xs font-medium text-gray-700 uppercase tracking-wide">Employee</label>
                        <select wire:model.live="filterEmployee" 
                                class="w-full px-4 py-2.5 bg-white border border-gray-200 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all text-sm">
                            <option value="">All Employees</option>
                            @foreach($employees as $employee)
                                <option value="{{ $employee->employee_id }}">{{ $employee->firstname }} {{ $employee->lastname }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="space-y-2">
                        <label class="text-xs font-medium text-gray-700 uppercase tracking-wide">Date From</label>
                        <input type="date" wire:model.live="dateFrom" 
                               class="w-full px-4 py-2.5 bg-white border border-gray-200 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all text-sm">
                    </div>
                    <div class="space-y-2">
                        <label class="text-xs font-medium text-gray-700 uppercase tracking-wide">Date To</label>
                        <input type="date" wire:model.live="dateTo" 
                               class="w-full px-4 py-2.5 bg-white border border-gray-200 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all text-sm">
                    </div>
                    <div class="space-y-2">
                        <label class="text-xs font-medium text-gray-700 uppercase tracking-wide">Per Page</label>
                        <select wire:model.live="perPage" 
                                class="w-full px-4 py-2.5 bg-white border border-gray-200 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all text-sm">
                            <option value="25">25</option>
                            <option value="50">50</option>
                            <option value="100">100</option>
                            <option value="200">200</option>
                        </select>
                    </div>
                </div>
            </div>
        </div>

        <!-- Activity Logs Table -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
            <div class="px-8 py-6 border-b border-gray-200">
                <h2 class="text-xl font-bold text-gray-900">Activity History</h2>
                <p class="mt-1 text-sm text-gray-500">Showing {{ $logs->count() }} of {{ $logs->total() }} activities</p>
            </div>

            <!-- Table -->
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead>
                        <tr class="bg-gray-50 border-y border-gray-200">
                            <th class="px-4 py-3 text-left text-xs font-bold text-gray-700 uppercase tracking-wide">ID</th>
                            <th class="px-4 py-3 text-left text-xs font-bold text-gray-700 uppercase tracking-wide">Date & Time</th>
                            <th class="px-4 py-3 text-left text-xs font-bold text-gray-700 uppercase tracking-wide">Employee</th>
                            <th class="px-4 py-3 text-left text-xs font-bold text-gray-700 uppercase tracking-wide">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 bg-white">
                        @forelse($logs as $log)
                            <tr class="hover:bg-gray-50 transition-colors">
                                <td class="px-4 py-3 whitespace-nowrap">
                                    <span class="text-sm font-medium text-gray-900">#{{ $log->activity_log_id }}</span>
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap">
                                    <div class="text-sm font-medium text-gray-900">{{ $log->datetime_added->format('M d, Y') }}</div>
                                    <div class="text-xs text-gray-500">{{ $log->datetime_added->format('h:i A') }}</div>
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap">
                                    <div class="flex items-center">
                                        <div class="h-8 w-8 rounded-full bg-blue-500 flex items-center justify-center text-white font-semibold text-sm">
                                            {{ substr($log->employee->firstname ?? 'U', 0, 1) }}{{ substr($log->employee->lastname ?? 'N', 0, 1) }}
                                        </div>
                                        <div class="ml-3">
                                            <div class="text-sm font-medium text-gray-900">
                                                {{ $log->employee->firstname ?? 'Unknown' }} {{ $log->employee->lastname ?? '' }}
                                            </div>
                                            @if($log->employee->position)
                                                <div class="text-xs text-gray-500">{{ $log->employee->position }}</div>
                                            @endif
                                        </div>
                                    </div>
                                </td>
                                <td class="px-4 py-3">
                                    <div class="text-sm text-gray-900">{{ $log->description }}</div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-4 py-12 text-center">
                                    <svg class="mx-auto h-10 w-10 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                    </svg>
                                    <p class="mt-2 text-sm text-gray-500">No activity logs found</p>
                                    <p class="text-xs text-gray-400">Try adjusting your filters</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div class="px-8 py-4 border-t border-gray-200 bg-gray-50">
                {{ $logs->links() }}
            </div>
        </div>
    </div>
</div>
