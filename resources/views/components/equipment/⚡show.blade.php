<?php

use Livewire\Component;
use Livewire\Attributes\Validate;
use App\Models\Equipment;
use App\Models\Employee;
use App\Models\Section;
use App\Models\MaintenanceRecord;

new class extends Component
{
    public $equipmentId;
    public $flashMessage = '';

    // Add Schedule Modal
    public bool $showScheduleModal = false;

    #[Validate('required|in:Weekly,Bi-Weekly,Monthly,Quarterly,Semi-Annually,Annually')]
    public $schedule_frequency = 'Weekly';

    #[Validate('required|date|after_or_equal:today')]
    public $schedule_next_due_date = '';

    #[Validate('nullable|exists:employee,employee_id')]
    public $schedule_employee_id = '';

    #[Validate('nullable|exists:section,section_id')]
    public $schedule_section_id = '';

    // Record Maintenance Modal
    public bool $showMaintenanceModal = false;

    #[Validate('required|date')]
    public $maintenance_date = '';

    #[Validate('required|exists:employee,employee_id')]
    public $maintenance_performed_by = '';

    #[Validate('required|in:Preventive,Corrective,Emergency,Routine')]
    public $maintenance_type = 'Preventive';

    #[Validate('nullable|string|max:1000')]
    public $maintenance_notes = '';

    #[Validate('nullable|date')]
    public $maintenance_next_date = '';

    public function mount($equipmentId)
    {
        $this->equipmentId = $equipmentId;
        $this->maintenance_date = now()->format('Y-m-d');
    }

    // Schedule Modal
    public function openScheduleModal()
    {
        $this->resetValidation();
        $this->schedule_frequency = 'Weekly';
        $this->schedule_next_due_date = '';
        $this->schedule_employee_id = '';
        $this->schedule_section_id = '';
        $this->showScheduleModal = true;
    }

    public function closeScheduleModal()
    {
        $this->showScheduleModal = false;
        $this->resetValidation();
    }

    public function saveSchedule()
    {
        $this->validate([
            'schedule_frequency' => 'required|in:Weekly,Bi-Weekly,Monthly,Quarterly,Semi-Annually,Annually',
            'schedule_next_due_date' => 'required|date|after_or_equal:today',
            'schedule_employee_id' => 'nullable|exists:employee,employee_id',
            'schedule_section_id' => 'nullable|exists:section,section_id',
        ]);

        MaintenanceRecord::create([
            'equipment_id' => $this->equipmentId,
            'performed_date' => now()->format('Y-m-d'),
            'findings' => null,
            'action_taken' => 'Scheduled - ' . $this->schedule_frequency,
            'performed_by' => $this->schedule_employee_id ?: null,
            'next_due_date' => $this->schedule_next_due_date,
            'status' => 'pending',
        ]);

        $this->closeScheduleModal();
        $this->flashMessage = 'Maintenance schedule added successfully.';
    }

    // Maintenance Modal
    public function openMaintenanceModal()
    {
        $this->resetValidation();
        $this->maintenance_date = now()->format('Y-m-d');
        $this->maintenance_performed_by = '';
        $this->maintenance_type = 'Preventive';
        $this->maintenance_notes = '';
        $this->maintenance_next_date = '';
        $this->showMaintenanceModal = true;
    }

    public function closeMaintenanceModal()
    {
        $this->showMaintenanceModal = false;
        $this->resetValidation();
    }

    public function saveMaintenance()
    {
        $this->validate([
            'maintenance_date' => 'required|date',
            'maintenance_performed_by' => 'required|exists:employee,employee_id',
            'maintenance_type' => 'required|in:Preventive,Corrective,Emergency,Routine',
            'maintenance_notes' => 'nullable|string|max:1000',
            'maintenance_next_date' => 'nullable|date',
        ]);

        MaintenanceRecord::create([
            'equipment_id' => $this->equipmentId,
            'performed_date' => $this->maintenance_date,
            'findings' => $this->maintenance_notes,
            'action_taken' => $this->maintenance_type,
            'performed_by' => $this->maintenance_performed_by,
            'next_due_date' => $this->maintenance_next_date ?: null,
            'status' => 'completed',
        ]);

        $this->closeMaintenanceModal();
        $this->flashMessage = 'Maintenance record saved successfully.';
    }

    public function with(): array
    {
        $equipment = Equipment::with(['section', 'maintenanceRecords' => function ($q) {
            $q->where('is_deleted', 0)->orderByDesc('performed_date');
        }, 'maintenanceRecords.performedBy'])->findOrFail($this->equipmentId);

        $employees = Employee::where('is_deleted', 0)->orderBy('firstname')->get();
        $sections = Section::orderBy('label')->get();

        $scheduleRecords = $equipment->maintenanceRecords
            ->filter(fn($r) => $r->next_due_date)
            ->sortBy('next_due_date');

        $historyRecords = $equipment->maintenanceRecords
            ->sortByDesc('performed_date');

        return compact('equipment', 'employees', 'sections', 'scheduleRecords', 'historyRecords');
    }
};
?>

<div class="p-6 space-y-6">
    <!-- Flash Message -->
    @if($flashMessage)
        <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 3000)"
             class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-lg text-sm flex items-center justify-between">
            <span>{{ $flashMessage }}</span>
            <button @click="show = false" class="text-green-500 hover:text-green-700">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>
    @endif

    <!-- Header -->
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">{{ $equipment->name }}</h1>
            <p class="text-sm text-gray-500 mt-0.5">Equipment ID: #{{ $equipment->equipment_id }}</p>
        </div>
        <span class="px-3 py-1.5 text-sm font-semibold rounded-full
            {{ $equipment->status == 'Operational' ? 'bg-green-100 text-green-800' : '' }}
            {{ $equipment->status == 'Under Maintenance' ? 'bg-yellow-100 text-yellow-800' : '' }}
            {{ $equipment->status == 'Broken' ? 'bg-red-100 text-red-800' : '' }}
            {{ $equipment->status == 'Decommissioned' ? 'bg-gray-100 text-gray-600' : '' }}">
            {{ $equipment->status }}
        </span>
    </div>

    <!-- Equipment Information -->
    <div class="bg-white rounded-lg border border-gray-200 overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200">
            <h2 class="text-base font-semibold text-gray-900">Equipment Information</h2>
        </div>
        <div class="p-6">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-x-8 gap-y-5">
                <div>
                    <p class="text-xs font-medium text-gray-400 uppercase tracking-wider mb-1">Equipment Name</p>
                    <p class="text-sm font-medium text-gray-900">{{ $equipment->name }}</p>
                </div>
                <div>
                    <p class="text-xs font-medium text-gray-400 uppercase tracking-wider mb-1">Model</p>
                    <p class="text-sm font-medium text-gray-900">{{ $equipment->model ?? '—' }}</p>
                </div>
                <div>
                    <p class="text-xs font-medium text-gray-400 uppercase tracking-wider mb-1">Serial Number</p>
                    <p class="text-sm font-medium text-gray-900">{{ $equipment->serial_no ?? '—' }}</p>
                </div>
                <div>
                    <p class="text-xs font-medium text-gray-400 uppercase tracking-wider mb-1">Section</p>
                    <p class="text-sm font-medium text-gray-900">{{ $equipment->section->label ?? '—' }}</p>
                </div>
                <div>
                    <p class="text-xs font-medium text-gray-400 uppercase tracking-wider mb-1">Purchase Date</p>
                    <p class="text-sm font-medium text-gray-900">{{ $equipment->purchase_date ? $equipment->purchase_date->format('M d, Y') : '—' }}</p>
                </div>
                <div>
                    <p class="text-xs font-medium text-gray-400 uppercase tracking-wider mb-1">Supplier</p>
                    <p class="text-sm font-medium text-gray-900">{{ $equipment->supplier ?? '—' }}</p>
                </div>
                <div class="md:col-span-2 lg:col-span-3">
                    <p class="text-xs font-medium text-gray-400 uppercase tracking-wider mb-1">Remarks</p>
                    <p class="text-sm text-gray-600">{{ $equipment->remarks ?? 'No remarks' }}</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Maintenance Schedule -->
    <div class="bg-white rounded-lg border border-gray-200 overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200 flex items-center justify-between">
            <h2 class="text-base font-semibold text-gray-900">Maintenance Schedule</h2>
            <button wire:click="openScheduleModal" class="inline-flex items-center px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-medium rounded-lg transition-colors">
                <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                Add Schedule
            </button>
        </div>
        <div class="p-6">
            @if($scheduleRecords->count() > 0)
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead>
                            <tr class="bg-gray-50">
                                <th class="px-5 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Frequency</th>
                                <th class="px-5 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Next Due Date</th>
                                <th class="px-5 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Responsible Employee</th>
                                <th class="px-5 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @foreach($scheduleRecords as $record)
                                @php
                                    $daysRemaining = $record->next_due_date ? (int) now()->diffInDays($record->next_due_date, false) : null;
                                    $isOverdue = $daysRemaining !== null && $daysRemaining < 0;
                                    $isDueSoon = $daysRemaining !== null && $daysRemaining >= 0 && $daysRemaining <= 7;
                                    $frequency = str_replace('Scheduled - ', '', $record->action_taken ?? '');
                                @endphp
                                <tr class="hover:bg-gray-50">
                                    <td class="px-5 py-3.5 text-sm text-gray-900">{{ $frequency ?: '—' }}</td>
                                    <td class="px-5 py-3.5 text-sm text-gray-900">{{ $record->next_due_date->format('M d, Y') }}</td>
                                    <td class="px-5 py-3.5 text-sm text-gray-600">{{ $record->performedBy->full_name ?? '—' }}</td>
                                    <td class="px-5 py-3.5 text-sm">
                                        @if($isOverdue)
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-700">Overdue</span>
                                        @elseif($isDueSoon)
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-700">Due Soon</span>
                                        @else
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-700">Scheduled</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="text-center py-8">
                    <p class="text-gray-400 text-sm">No maintenance schedules found.</p>
                </div>
            @endif
        </div>
    </div>

    <!-- Maintenance History -->
    <div class="bg-white rounded-lg border border-gray-200 overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200 flex items-center justify-between">
            <h2 class="text-base font-semibold text-gray-900">Maintenance History</h2>
            <button wire:click="openMaintenanceModal" class="inline-flex items-center px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-medium rounded-lg transition-colors">
                <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                Record Maintenance
            </button>
        </div>
        <div class="p-6">
            @if($historyRecords->count() > 0)
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead>
                            <tr class="bg-gray-50">
                                <th class="px-5 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">#</th>
                                <th class="px-5 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date Performed</th>
                                <th class="px-5 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Type</th>
                                <th class="px-5 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Notes</th>
                                <th class="px-5 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Performed By</th>
                                <th class="px-5 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Next Due Date</th>
                                <th class="px-5 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @foreach($historyRecords as $index => $record)
                                <tr class="hover:bg-gray-50">
                                    <td class="px-5 py-3.5 text-sm text-gray-500">{{ $index + 1 }}</td>
                                    <td class="px-5 py-3.5 text-sm text-gray-900">{{ $record->performed_date->format('M d, Y') }}</td>
                                    <td class="px-5 py-3.5 text-sm text-gray-600">{{ $record->action_taken ?? '—' }}</td>
                                    <td class="px-5 py-3.5 text-sm text-gray-600 max-w-xs truncate" title="{{ $record->findings }}">{{ Str::limit($record->findings, 40) ?? '—' }}</td>
                                    <td class="px-5 py-3.5 text-sm text-gray-600">{{ $record->performedBy->full_name ?? '—' }}</td>
                                    <td class="px-5 py-3.5 text-sm text-gray-600">{{ $record->next_due_date ? $record->next_due_date->format('M d, Y') : '—' }}</td>
                                    <td class="px-5 py-3.5 text-sm">
                                        @if($record->status == 'completed')
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-700">Completed</span>
                                        @elseif($record->status == 'pending')
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-700">Pending</span>
                                        @elseif($record->status == 'overdue')
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-700">Overdue</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="text-center py-8">
                    <p class="text-gray-400 text-sm">No maintenance records found.</p>
                </div>
            @endif
        </div>
    </div>

    <!-- Back Button -->
    <div class="pt-2">
        <a href="{{ route('equipment.index') }}" class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-lg text-sm font-medium text-gray-700 hover:bg-gray-50 transition-colors">
            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
            </svg>
            Back to Equipment
        </a>
    </div>

    <!-- Add Schedule Modal -->
    @if($showScheduleModal)
    <div class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50 flex items-start justify-center pt-20">
        <div class="bg-white rounded-xl shadow-xl w-full max-w-lg mx-4 p-6" @click.outside="$wire.closeScheduleModal()">
            <h3 class="text-lg font-bold text-gray-900 mb-5">Add Maintenance Schedule</h3>

            <form wire:submit.prevent="saveSchedule" class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Frequency *</label>
                    <select wire:model="schedule_frequency" class="w-full px-3 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-transparent text-sm">
                        <option value="Weekly">Weekly</option>
                        <option value="Bi-Weekly">Bi-Weekly</option>
                        <option value="Monthly">Monthly</option>
                        <option value="Quarterly">Quarterly</option>
                        <option value="Semi-Annually">Semi-Annually</option>
                        <option value="Annually">Annually</option>
                    </select>
                    @error('schedule_frequency') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Next Due Date *</label>
                    <input type="date" wire:model="schedule_next_due_date" class="w-full px-3 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-transparent text-sm">
                    @error('schedule_next_due_date') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Responsible Employee</label>
                    <select wire:model="schedule_employee_id" class="w-full px-3 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-transparent text-sm">
                        <option value="">Select Employee</option>
                        @foreach($employees as $emp)
                            <option value="{{ $emp->employee_id }}">{{ $emp->full_name }}</option>
                        @endforeach
                    </select>
                    @error('schedule_employee_id') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Responsible Section</label>
                    <select wire:model="schedule_section_id" class="w-full px-3 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-transparent text-sm">
                        <option value="">Select Section</option>
                        @foreach($sections as $sec)
                            <option value="{{ $sec->section_id }}">{{ $sec->label }}</option>
                        @endforeach
                    </select>
                    @error('schedule_section_id') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                </div>

                <div class="flex items-center space-x-3 pt-2">
                    <button type="submit" class="px-5 py-2 bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-medium rounded-lg transition-colors">
                        Add Schedule
                    </button>
                    <button type="button" wire:click="closeScheduleModal" class="px-5 py-2 bg-red-500 hover:bg-red-600 text-white text-sm font-medium rounded-lg transition-colors">
                        Cancel
                    </button>
                </div>
            </form>
        </div>
    </div>
    @endif

    <!-- Record Maintenance Modal -->
    @if($showMaintenanceModal)
    <div class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50 flex items-start justify-center pt-20">
        <div class="bg-white rounded-xl shadow-xl w-full max-w-lg mx-4 p-6" @click.outside="$wire.closeMaintenanceModal()">
            <h3 class="text-lg font-bold text-gray-900 mb-5">Record Maintenance</h3>

            <form wire:submit.prevent="saveMaintenance" class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Maintenance Date *</label>
                    <input type="date" wire:model="maintenance_date" class="w-full px-3 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-transparent text-sm">
                    @error('maintenance_date') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Performed By *</label>
                    <select wire:model="maintenance_performed_by" class="w-full px-3 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-transparent text-sm">
                        <option value="">Select Employee</option>
                        @foreach($employees as $emp)
                            <option value="{{ $emp->employee_id }}">{{ $emp->full_name }}</option>
                        @endforeach
                    </select>
                    @error('maintenance_performed_by') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Maintenance Type *</label>
                    <select wire:model="maintenance_type" class="w-full px-3 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-transparent text-sm">
                        <option value="Preventive">Preventive</option>
                        <option value="Corrective">Corrective</option>
                        <option value="Emergency">Emergency</option>
                        <option value="Routine">Routine</option>
                    </select>
                    @error('maintenance_type') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Notes</label>
                    <textarea wire:model="maintenance_notes" rows="3" class="w-full px-3 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-transparent text-sm resize-y" placeholder="Enter maintenance notes..."></textarea>
                    @error('maintenance_notes') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Next Maintenance Date</label>
                    <input type="date" wire:model="maintenance_next_date" class="w-full px-3 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-transparent text-sm">
                    @error('maintenance_next_date') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                </div>

                <div class="flex items-center space-x-3 pt-2">
                    <button type="submit" class="px-5 py-2 bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-medium rounded-lg transition-colors">
                        Record Maintenance
                    </button>
                    <button type="button" wire:click="closeMaintenanceModal" class="px-5 py-2 bg-red-500 hover:bg-red-600 text-white text-sm font-medium rounded-lg transition-colors">
                        Cancel
                    </button>
                </div>
            </form>
        </div>
    </div>
    @endif
</div>
