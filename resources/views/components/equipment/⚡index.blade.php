<?php

use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Validate;
use App\Models\Equipment;
use App\Models\Employee;
use App\Models\Section;
use App\Models\EquipmentUsage;
use App\Models\MaintenanceRecord;
use App\Models\CalibrationRecord;
use App\Traits\LogsActivity;

new class extends Component
{
    use WithPagination, LogsActivity;

    // Equipment Properties
    #[Validate('required|string|max:255')]
    public $name = '';

    #[Validate('nullable|string|max:100')]
    public $model = '';

    #[Validate('nullable|string|max:100')]
    public $serial_no = '';

    #[Validate('required|exists:section,section_id')]
    public $section_id = '';

    #[Validate('nullable|string|max:50')]
    public $status = 'operational';

    #[Validate('nullable|date')]
    public $purchase_date = '';

    #[Validate('nullable|string|max:255')]
    public $supplier = '';

    #[Validate('nullable|string|max:500')]
    public $remarks = '';

    public $search = '';
    public $perPage = 10;
    public $flashMessage = '';

    // Maintenance Alerts Filters
    public $filterSection = '';
    public $filterStatus = '';
    public $alertsPerPage = 10;

    // Equipment Usage Properties
    #[Validate('required|exists:equipment,equipment_id')]
    public $usage_equipment_id = '';

    #[Validate('required|date')]
    public $usage_date_used = '';

    public $usage_employee_id = '';
    public $usage_user_name = '';

    #[Validate('required|string|max:200')]
    public $usage_item_name = '';

    #[Validate('required|integer|min:1')]
    public $usage_quantity = 1;

    #[Validate('required|string|max:500')]
    public $usage_purpose = '';

    #[Validate('nullable|string|max:50')]
    public $usage_or_number = '';

    #[Validate('required|in:functional,not_functional')]
    public $usage_status = 'functional';

    #[Validate('nullable|string|max:500')]
    public $usage_remarks = '';

    // Edit mode
    public $editMode = false;
    public $editingEquipmentId = null;

    public bool $showUsageModal = false;
    public bool $showEquipmentModal = false;

    // Details Modal
    public bool $showDetailsModal = false;
    public $detailsEquipment = null;
    public array $detailsMaintenanceHistory = [];
    public array $detailsCalibrationHistory = [];
    public array $detailsUsageHistory = [];

    // Maintenance Record Properties
    public bool $showMaintenanceModal = false;
    public $maint_equipment_id = '';
    public $maint_equipment_name = '';
    public $maint_performed_date = '';
    public $maint_performed_by = '';
    public $maint_findings = '';
    public $maint_action_taken = '';
    public $maint_next_due_date = '';
    public $maint_status = 'completed';

    // Delete Confirmation Modal
    public bool $showDeleteModal = false;
    public $deleteEquipmentId = null;
    public $deleteEquipmentName = '';

    // Equipment list section filter
    public $filterEquipmentSection = '';

    public function mount()
    {
        if (session()->has('success')) {
            $this->flashMessage = session('success');
        }
        $this->usage_date_used = now()->format('Y-m-d');
    }

    public function openUsageModal()
    {
        $this->reset(['usage_equipment_id', 'usage_employee_id', 'usage_user_name', 'usage_item_name', 'usage_quantity', 'usage_purpose', 'usage_or_number', 'usage_remarks']);
        $this->usage_status = 'functional';
        $this->usage_date_used = now()->format('Y-m-d');
        $this->usage_quantity = 1;
        $this->resetValidation();
        $this->showUsageModal = true;
    }

    public function closeUsageModal()
    {
        $this->showUsageModal = false;
        $this->resetValidation();
    }

    public function openEquipmentModal()
    {
        $this->reset(['name', 'model', 'serial_no', 'section_id', 'purchase_date', 'supplier', 'remarks']);
        $this->status = 'operational';
        $this->resetValidation();
        $this->showEquipmentModal = true;
    }

    public function closeEquipmentModal()
    {
        $this->showEquipmentModal = false;
        $this->resetValidation();
    }

    public function save()
    {
        $this->validate([
            'name' => 'required|string|max:255',
            'model' => 'nullable|string|max:100',
            'serial_no' => 'nullable|string|max:100',
            'section_id' => 'required|exists:section,section_id',
            'status' => 'nullable|string|max:50',
            'purchase_date' => 'nullable|date',
            'supplier' => 'nullable|string|max:255',
            'remarks' => 'nullable|string|max:500',
        ]);

        $savedName = $this->name;

        Equipment::create([
            'name' => $this->name,
            'model' => $this->model,
            'serial_no' => $this->serial_no,
            'section_id' => $this->section_id,
            'status' => $this->status,
            'purchase_date' => $this->purchase_date ?: null,
            'supplier' => $this->supplier,
            'remarks' => $this->remarks,
            'is_deleted' => 0,
            'datetime_added' => now(),
        ]);

        $this->logActivity("Created equipment: {$savedName}");
        $this->reset(['name', 'model', 'serial_no', 'section_id', 'purchase_date', 'supplier', 'remarks']);
        $this->status = 'operational';
        $this->flashMessage = 'Equipment added successfully!';
        $this->showEquipmentModal = false;
        $this->resetPage();
    }

    public function edit($id)
    {
        $equipment = Equipment::findOrFail($id);
        $this->editingEquipmentId = $id;
        $this->name = $equipment->name;
        $this->model = $equipment->model ?? '';
        $this->serial_no = $equipment->serial_no ?? '';
        $this->section_id = $equipment->section_id;
        $this->status = $equipment->status ?? 'operational';
        $this->purchase_date = $equipment->purchase_date ? $equipment->purchase_date->format('Y-m-d') : '';
        $this->supplier = $equipment->supplier ?? '';
        $this->remarks = $equipment->remarks ?? '';
        $this->editMode = true;
    }

    public function update()
    {
        $this->validate([
            'name' => 'required|string|max:255',
            'model' => 'nullable|string|max:100',
            'serial_no' => 'nullable|string|max:100',
            'section_id' => 'required|exists:section,section_id',
            'status' => 'nullable|string|max:50',
            'purchase_date' => 'nullable|date',
            'supplier' => 'nullable|string|max:255',
            'remarks' => 'nullable|string|max:500',
        ]);

        $equipment = Equipment::findOrFail($this->editingEquipmentId);
        $equipment->update([
            'name' => $this->name,
            'model' => $this->model,
            'serial_no' => $this->serial_no,
            'section_id' => $this->section_id,
            'status' => $this->status,
            'purchase_date' => $this->purchase_date ?: null,
            'supplier' => $this->supplier,
            'remarks' => $this->remarks,
        ]);

        $this->logActivity("Updated equipment ID {$this->editingEquipmentId}: {$this->name}");
        $this->flashMessage = 'Equipment updated successfully!';
        $this->cancelEdit();
    }

    public function cancelEdit()
    {
        $this->reset(['name', 'model', 'serial_no', 'section_id', 'purchase_date', 'supplier', 'remarks', 'editMode', 'editingEquipmentId']);
        $this->status = 'operational';
    }

    public function confirmDelete($id)
    {
        $equipment = Equipment::find($id);
        if ($equipment) {
            $this->deleteEquipmentId = $id;
            $this->deleteEquipmentName = $equipment->name;
            $this->showDeleteModal = true;
        }
    }

    public function cancelDelete()
    {
        $this->showDeleteModal = false;
        $this->deleteEquipmentId = null;
        $this->deleteEquipmentName = '';
    }

    public function deleteConfirmed()
    {
        $equipment = Equipment::find($this->deleteEquipmentId);
        if ($equipment) {
            $equipment->softDelete();
            $this->logActivity("Deleted equipment ID {$this->deleteEquipmentId}: {$equipment->name}");
            $this->flashMessage = "Equipment '{$equipment->name}' deleted successfully!";
            $this->resetPage();
        }
        $this->showDeleteModal = false;
        $this->deleteEquipmentId = null;
        $this->deleteEquipmentName = '';
    }

    public function openDetailsModal($id)
    {
        $equipment = Equipment::with(['section'])->find($id);
        if (!$equipment) return;

        $this->detailsEquipment = $equipment;

        // Load maintenance history
        $this->detailsMaintenanceHistory = MaintenanceRecord::where('maintenance_record.equipment_id', $id)
            ->where('maintenance_record.is_deleted', 0)
            ->leftJoin('employee', 'maintenance_record.performed_by', '=', 'employee.employee_id')
            ->select(
                'maintenance_record.*',
                \Illuminate\Support\Facades\DB::raw("CONCAT(employee.firstname, ' ', employee.lastname) as performed_by_name")
            )
            ->orderBy('performed_date', 'desc')
            ->limit(20)
            ->get()
            ->toArray();

        // Load calibration history
        $this->detailsCalibrationHistory = CalibrationRecord::where('calibration_record.equipment_id', $id)
            ->leftJoin('employee', 'calibration_record.performed_by', '=', 'employee.employee_id')
            ->leftJoin('calibration_procedure', 'calibration_record.procedure_id', '=', 'calibration_procedure.procedure_id')
            ->select(
                'calibration_record.*',
                \Illuminate\Support\Facades\DB::raw("CONCAT(employee.firstname, ' ', employee.lastname) as performed_by_name"),
                'calibration_procedure.procedure_name'
            )
            ->orderBy('calibration_date', 'desc')
            ->limit(20)
            ->get()
            ->toArray();

        // Load usage history
        $this->detailsUsageHistory = EquipmentUsage::where('equipment_id', $id)
            ->orderBy('date_used', 'desc')
            ->limit(20)
            ->get()
            ->toArray();

        $this->showDetailsModal = true;
    }

    public function closeDetailsModal()
    {
        $this->showDetailsModal = false;
        $this->detailsEquipment = null;
        $this->detailsMaintenanceHistory = [];
        $this->detailsCalibrationHistory = [];
        $this->detailsUsageHistory = [];
    }

    public function saveUsage()
    {
        $this->validate([
            'usage_equipment_id' => 'required|exists:equipment,equipment_id',
            'usage_date_used' => 'required|date',
            'usage_employee_id' => 'required|exists:employee,employee_id',
            'usage_item_name' => 'required|string|max:200',
            'usage_quantity' => 'required|integer|min:1',
            'usage_purpose' => 'required|string|max:500',
            'usage_or_number' => 'nullable|string|max:50',
            'usage_status' => 'required|in:functional,not_functional',
            'usage_remarks' => 'nullable|string|max:500',
        ], [
            'usage_employee_id.required' => 'Please select the employee who used the equipment.',
            'usage_employee_id.exists' => 'Selected employee does not exist.',
        ]);

        // Derive user_name from the selected employee
        $employee = Employee::findOrFail($this->usage_employee_id);
        $this->usage_user_name = $employee->firstname . ' ' . $employee->lastname;

        $equipmentId = $this->usage_equipment_id;

        EquipmentUsage::create([
            'equipment_id' => $equipmentId,
            'date_used' => $this->usage_date_used,
            'user_name' => $this->usage_user_name,
            'item_name' => $this->usage_item_name,
            'quantity' => $this->usage_quantity,
            'purpose' => $this->usage_purpose,
            'or_number' => $this->usage_or_number,
            'status' => $this->usage_status,
            'remarks' => $this->usage_remarks,
            'datetime_added' => now(),
        ]);

        // If equipment is reported not functional, update its status to under_maintenance
        if ($this->usage_status === 'not_functional') {
            Equipment::where('equipment_id', $equipmentId)
                ->update(['status' => 'under_maintenance']);
        }

        $this->logActivity("Recorded equipment usage for equipment: {$employee->firstname} {$employee->lastname} used equipment ID {$equipmentId}");
        $this->reset(['usage_equipment_id', 'usage_employee_id', 'usage_user_name', 'usage_item_name', 'usage_quantity', 'usage_purpose', 'usage_or_number', 'usage_remarks']);
        $this->usage_status = 'functional';
        $this->usage_date_used = now()->format('Y-m-d');
        $this->flashMessage = 'Equipment usage recorded successfully!';
        $this->showUsageModal = false;
        $this->resetPage();
    }

    // Maintenance Record Methods
    public function openMaintenanceModal($equipmentId = null)
    {
        $this->reset(['maint_equipment_id', 'maint_equipment_name', 'maint_performed_date', 'maint_performed_by', 'maint_findings', 'maint_action_taken', 'maint_next_due_date']);
        $this->maint_status = 'completed';
        $this->maint_performed_date = now()->format('Y-m-d');
        $this->resetValidation();

        if ($equipmentId) {
            $equipment = Equipment::find($equipmentId);
            if ($equipment) {
                $this->maint_equipment_id = $equipmentId;
                $this->maint_equipment_name = $equipment->name;
            }
        }

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
            'maint_equipment_id' => 'required|exists:equipment,equipment_id',
            'maint_performed_date' => 'required|date',
            'maint_performed_by' => 'required|exists:employee,employee_id',
            'maint_findings' => 'nullable|string|max:2000',
            'maint_action_taken' => 'nullable|string|max:2000',
            'maint_next_due_date' => 'nullable|date|after_or_equal:maint_performed_date',
            'maint_status' => 'required|in:completed,pending,overdue',
        ], [
            'maint_equipment_id.required' => 'Please select the equipment.',
            'maint_performed_by.required' => 'Please select the employee who performed maintenance.',
            'maint_next_due_date.after_or_equal' => 'Next due date must be on or after the performed date.',
        ]);

        $equipment = Equipment::find($this->maint_equipment_id);

        MaintenanceRecord::create([
            'equipment_id' => $this->maint_equipment_id,
            'performed_date' => $this->maint_performed_date,
            'performed_by' => $this->maint_performed_by,
            'findings' => $this->maint_findings,
            'action_taken' => $this->maint_action_taken,
            'next_due_date' => $this->maint_next_due_date ?: null,
            'status' => $this->maint_status,
            'is_deleted' => 0,
            'datetime_added' => now(),
        ]);

        // If maintenance is completed, set equipment back to operational
        if ($this->maint_status === 'completed' && $equipment && $equipment->status === 'under_maintenance') {
            $equipment->update(['status' => 'operational']);
        }

        $this->logActivity("Recorded maintenance for equipment: {$equipment->name} (ID: {$this->maint_equipment_id})");
        $this->reset(['maint_equipment_id', 'maint_equipment_name', 'maint_performed_date', 'maint_performed_by', 'maint_findings', 'maint_action_taken', 'maint_next_due_date']);
        $this->maint_status = 'completed';
        $this->flashMessage = 'Maintenance record saved successfully!';
        $this->showMaintenanceModal = false;
        $this->resetValidation();
    }

    public function getMaintenanceAlerts()
    {
        $query = Equipment::active()
            ->with(['section', 'latestMaintenance'])
            ->whereHas('latestMaintenance', function($q) {
                $q->where('is_deleted', 0)
                  ->whereNotNull('next_due_date');
            })
            ->when($this->filterSection, function ($query) {
                $query->where('section_id', $this->filterSection);
            });

        $alerts = $query->get()->map(function ($equipment) {
            $maintenance = $equipment->latestMaintenance;
            if (!$maintenance || !$maintenance->next_due_date) {
                return null;
            }

            $nextDueDate = $maintenance->next_due_date;
            $daysUntilDue = (int) now()->startOfDay()->diffInDays(\Carbon\Carbon::parse($nextDueDate)->startOfDay(), false);

            if ($daysUntilDue < 0) {
                $status = 'Overdue';
            } elseif ($daysUntilDue <= 7) {
                $status = 'Due Soon';
            } else {
                $status = 'Scheduled';
            }

            return [
                'equipment_id' => $equipment->equipment_id,
                'name' => $equipment->name,
                'model' => $equipment->model ?? 'N/A',
                'section' => $equipment->section->label ?? 'N/A',
                'next_due_date' => $nextDueDate->format('M d, Y'),
                'days_until_due' => $daysUntilDue < 0 ? abs($daysUntilDue) . ' days overdue' : $daysUntilDue . ' days',
                'raw_days' => $daysUntilDue,
                'status' => $status,
            ];
        })->filter();

        // Apply status filter
        if ($this->filterStatus) {
            $alerts = $alerts->where('status', $this->filterStatus);
        }

        // Sort by days until due (overdue first, then due soon, then scheduled)
        $alerts = $alerts->sortBy(function($alert) {
            return $alert['raw_days'];
        });

        return $alerts->take($this->alertsPerPage);
    }

    public function with(): array
    {
        $query = Equipment::active()
            ->with('section')
            ->when($this->search, function ($query) {
                $query->where(function ($q) {
                    $q->where('name', 'like', '%' . $this->search . '%')
                      ->orWhere('model', 'like', '%' . $this->search . '%')
                      ->orWhere('serial_no', 'like', '%' . $this->search . '%');
                });
            })
            ->when($this->filterEquipmentSection, function ($query) {
                $query->where('section_id', $this->filterEquipmentSection);
            })
            ->orderBy('equipment_id', 'desc');

        return [
            'equipment' => $this->perPage === 'all' ? $query->get() : $query->paginate((int)$this->perPage),
            'sections' => Section::active()->orderBy('label')->get(),
            'employees' => Employee::where('is_deleted', 0)->orderBy('firstname')->get(),
            'maintenanceAlerts' => $this->getMaintenanceAlerts(),
            'allEquipment' => Equipment::active()->orderBy('name')->get(),
        ];
    }
};
?>

<div class="p-6">
    <div class="mb-6 flex items-center justify-between">
        <h1 class="text-2xl font-bold text-gray-900 flex items-center">
            <svg class="w-7 h-7 mr-2" style="color:#d2334c" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z"/>
            </svg>
            Equipment Management
        </h1>
        <div class="flex gap-3">
            <button type="button" wire:click="openMaintenanceModal()" 
                    class="px-4 py-2 rounded-lg text-sm font-semibold transition-colors text-white" style="background-color:#d2334c" onmouseover="this.style.backgroundColor='#9f1239'" onmouseout="this.style.backgroundColor='#d2334c'">
                Record Maintenance
            </button>
            <button type="button" wire:click="openUsageModal" 
                    class="px-4 py-2 rounded-lg text-sm font-semibold transition-colors text-white" style="background-color:#d2334c" onmouseover="this.style.backgroundColor='#9f1239'" onmouseout="this.style.backgroundColor='#d2334c'">
                Record Equipment Usage
            </button>
            <button type="button" wire:click="openEquipmentModal" 
                    class="px-4 py-2 rounded-lg text-sm font-semibold transition-colors border-2 hover:bg-gray-50" style="border-color:#d2334c; color:#d2334c">
                Add Equipment
            </button>
        </div>
    </div>

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

    <!-- Maintenance Alerts Section -->
    <div class="bg-white rounded-xl shadow-sm mb-6">
        <div class="px-6 py-4 border-b border-gray-200 flex items-center gap-3">
            <div class="p-1.5 rounded-lg" style="background-color:#fef2f2">
                <svg class="w-5 h-5" style="color:#d2334c" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                </svg>
            </div>
            <h2 class="text-lg font-bold text-gray-900">Maintenance Alerts</h2>
        </div>
        <div class="p-6">
            <!-- Status Legend -->
            <div class="mb-6 flex items-center space-x-6 text-sm">
                <span class="font-semibold text-gray-700">Status Legend:</span>
                <div class="flex items-center">
                    <span class="inline-block w-2 h-2 rounded-full bg-red-500 mr-2"></span>
                    <span class="text-gray-600"><strong class="text-red-600">Overdue</strong> (Past due date)</span>
                </div>
                <div class="flex items-center">
                    <span class="inline-block w-2 h-2 rounded-full bg-orange-500 mr-2"></span>
                    <span class="text-gray-600"><strong class="text-orange-600">Due Soon</strong> (Within 7 days)</span>
                </div>
                <div class="flex items-center">
                    <span class="inline-block w-2 h-2 rounded-full bg-green-500 mr-2"></span>
                    <span class="text-gray-600"><strong class="text-green-600">Scheduled</strong> (More than 7 days)</span>
                </div>
            </div>

            <!-- Filters -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Filter by Section</label>
                    <select wire:model.live="filterSection" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500">
                        <option value="">All Sections</option>
                        @foreach($sections as $section)
                            <option value="{{ $section->section_id }}">{{ $section->label }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Filter by Status</label>
                    <select wire:model.live="filterStatus" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500">
                        <option value="">All Statuses</option>
                        <option value="Overdue">Overdue</option>
                        <option value="Due Soon">Due Soon</option>
                        <option value="Scheduled">Scheduled</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Rows per page</label>
                    <select wire:model.live="alertsPerPage" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500">
                        <option value="10">10</option>
                        <option value="25">25</option>
                        <option value="50">50</option>
                        <option value="100">100</option>
                    </select>
                </div>
            </div>

            <!-- Maintenance Alerts Table -->
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Equipment</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Model</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Section</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Next Due Date</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Days Until Due</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @forelse($maintenanceAlerts as $alert)
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">{{ $alert['name'] }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">{{ $alert['model'] }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">{{ $alert['section'] }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">{{ $alert['next_due_date'] }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm">
                                    <span class="font-semibold {{ $alert['status'] == 'Overdue' ? 'text-red-600' : ($alert['status'] == 'Due Soon' ? 'text-orange-600' : 'text-gray-700') }}">
                                        {{ $alert['days_until_due'] }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm">
                                    <span class="inline-flex items-center gap-1 px-2.5 py-1 text-xs font-semibold rounded-full
                                        {{ $alert['status'] == 'Overdue' ? 'bg-red-100 text-red-700' : '' }}
                                        {{ $alert['status'] == 'Due Soon' ? 'bg-orange-100 text-orange-700' : '' }}
                                        {{ $alert['status'] == 'Scheduled' ? 'bg-green-100 text-green-700' : '' }}">
                                        <span class="w-1.5 h-1.5 rounded-full inline-block
                                            {{ $alert['status'] == 'Overdue' ? 'bg-red-500' : '' }}
                                            {{ $alert['status'] == 'Due Soon' ? 'bg-orange-500' : '' }}
                                            {{ $alert['status'] == 'Scheduled' ? 'bg-green-500' : '' }}"></span>
                                        {{ $alert['status'] }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm">
                                    <button type="button" wire:click="openMaintenanceModal({{ $alert['equipment_id'] }})" class="px-3 py-1.5 text-white text-xs font-semibold rounded-lg transition-colors" style="background-color:#d2334c" onmouseover="this.style.backgroundColor='#9f1239'" onmouseout="this.style.backgroundColor='#d2334c'">
                                        Record
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-6 py-8 text-center text-gray-500">No maintenance alerts at this time.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Equipment List Section -->
    <div class="bg-white rounded-xl shadow-sm overflow-hidden">
        <div class="p-6">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Search Equipment</label>
                        <input type="text" wire:model.live="search" placeholder="Search by name, model, or serial..." class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Filter by Section</label>
                        <select wire:model.live="filterEquipmentSection" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500">
                            <option value="">All Sections</option>
                            @foreach($sections as $section)
                                <option value="{{ $section->section_id }}">{{ $section->label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Rows per page</label>
                        <select wire:model.live="perPage" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500">
                            <option value="10">10</option>
                            <option value="25">25</option>
                            <option value="50">50</option>
                            <option value="100">100</option>
                            <option value="all">All</option>
                        </select>
                    </div>
                </div>
                
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <!-- <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ID</th> -->
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Name</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Model</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Serial No</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Section</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @forelse($equipment as $item)
                                <tr wire:key="equipment-{{ $item->equipment_id }}" class="hover:bg-gray-50">
                                    <!-- <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ $item->equipment_id }}</td> -->
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">{{ $item->name }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">{{ $item->model ?? 'N/A' }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">{{ $item->serial_no ?? 'N/A' }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">{{ $item->section->label ?? 'N/A' }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm">
                                        <span class="px-2 py-1 text-xs rounded-full 
                                            {{ $item->status == 'operational' ? 'bg-green-100 text-green-800' : '' }}
                                            {{ $item->status == 'under_maintenance' ? 'bg-yellow-100 text-yellow-800' : '' }}
                                            {{ $item->status == 'decommissioned' ? 'bg-gray-100 text-gray-800' : '' }}">
                                            {{ match($item->status) { 'operational' => 'Operational', 'under_maintenance' => 'Under Maintenance', 'decommissioned' => 'Decommissioned', default => ucfirst($item->status) } }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium space-x-2">
                                        <button type="button" wire:click="openDetailsModal({{ $item->equipment_id }})" class="inline-block px-4 py-1.5 text-white text-sm font-medium rounded-lg transition-colors" style="background-color:#d2334c" onmouseover="this.style.backgroundColor='#9f1239'" onmouseout="this.style.backgroundColor='#d2334c'">Details</button>
                                        <button type="button" wire:click="edit({{ $item->equipment_id }})" class="px-4 py-1.5 text-white text-sm font-medium rounded-lg transition-colors" style="background-color:#be123c" onmouseover="this.style.backgroundColor='#881337'" onmouseout="this.style.backgroundColor='#be123c'">Edit</button>
                                        <button type="button" wire:click="confirmDelete({{ $item->equipment_id }})" class="px-4 py-1.5 bg-red-500 hover:bg-red-600 text-white text-sm font-medium rounded-lg transition-colors">Delete</button>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="px-6 py-4 text-center text-gray-500">No equipment found.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                
                @if($perPage !== 'all' && method_exists($equipment, 'hasPages') && $equipment->hasPages())
                    <div class="px-6 py-4 border-t border-gray-200">
                        {{ $equipment->links() }}
                    </div>
                @endif
        </div>
    </div>

    <!-- Record Equipment Usage Modal -->
    @if($showUsageModal)
    <div class="fixed inset-0 z-50 overflow-y-auto flex items-center justify-center p-4" style="background-color: rgba(15,23,42,0.75); backdrop-filter: blur(4px);">
        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-2xl max-h-[90vh] flex flex-col" wire:click.stop>
            {{-- Header — Crimson --}}
            <div class="flex items-center justify-between px-7 py-5 rounded-t-2xl" style="background: #d2334c;">
                <div class="flex items-center gap-3">
                    <div class="bg-white/20 rounded-xl p-2.5">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-lg font-bold text-white">Record Equipment Usage</h3>
                        <p class="text-red-200 text-xs mt-0.5">Log equipment usage details</p>
                    </div>
                </div>
                <button wire:click="closeUsageModal" class="text-white/70 hover:text-white transition-colors">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>

            <form wire:submit.prevent="saveUsage" class="p-7 overflow-y-auto flex-1 space-y-5">
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1.5">Equipment *</label>
                        <div x-data="{
                            open: false,
                            search: '',
                            selectedLabel: '',
                            anchorRect: null,
                            items: @js($allEquipment->map(fn($eq) => ['id' => $eq->equipment_id, 'label' => $eq->name])->values()),
                            get filtered() {
                                return this.items.filter(i => i.label.toLowerCase().includes(this.search.toLowerCase()));
                            },
                            select(item) {
                                this.selectedLabel = item.label;
                                this.search = '';
                                this.open = false;
                                $wire.set('usage_equipment_id', item.id);
                            },
                            clear() {
                                this.selectedLabel = '';
                                this.open = false;
                                $wire.set('usage_equipment_id', '');
                            },
                            toggle() {
                                this.open = !this.open;
                                if (this.open) {
                                    this.$nextTick(() => {
                                        const rect = this.$refs.trigger.getBoundingClientRect();
                                        this.anchorRect = { top: rect.bottom + window.scrollY, left: rect.left + window.scrollX, width: rect.width };
                                    });
                                }
                            },
                            init() {
                                this.$watch('$wire.usage_equipment_id', val => {
                                    if (!val) { this.selectedLabel = ''; return; }
                                    const found = this.items.find(i => i.id == val);
                                    if (found) this.selectedLabel = found.label;
                                });
                            }
                        }" class="relative" @click.away="open = false">
                            <div x-ref="trigger" @click="toggle()" class="w-full px-3.5 py-2.5 border border-gray-200 rounded-lg text-sm cursor-pointer flex items-center justify-between bg-gray-50 hover:border-red-400 transition" :class="open ? 'ring-2 ring-red-500 border-transparent bg-white' : ''">
                                <span x-text="selectedLabel || 'Select Equipment'" :class="selectedLabel ? 'text-gray-900' : 'text-gray-400'" class="truncate"></span>
                                <div class="flex items-center gap-1 ml-2 shrink-0">
                                    <button type="button" x-show="selectedLabel" @click.stop="clear()" class="text-gray-400 hover:text-gray-600">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                    </button>
                                    <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                                </div>
                            </div>
                            <template x-teleport="body">
                                <div x-show="open" x-transition.opacity
                                     :style="anchorRect ? `position:absolute;top:${anchorRect.top+4}px;left:${anchorRect.left}px;width:${anchorRect.width}px;z-index:9999` : ''"
                                     class="bg-white border border-gray-200 rounded-lg shadow-lg overflow-hidden">
                                    <div class="p-2 border-b border-gray-100">
                                        <input type="text" x-model="search" @click.stop placeholder="Search equipment..." class="w-full px-3 py-1.5 text-sm border border-gray-200 rounded-md focus:ring-2 focus:ring-red-500 focus:border-transparent outline-none" @keydown.escape="open = false">
                                    </div>
                                    <ul class="max-h-48 overflow-y-auto py-1">
                                        <template x-for="item in filtered" :key="item.id">
                                            <li @click="select(item)" class="px-4 py-2 text-sm text-gray-700 hover:bg-red-50 hover:text-red-700 cursor-pointer truncate" x-text="item.label"></li>
                                        </template>
                                        <li x-show="filtered.length === 0" class="px-4 py-2 text-sm text-gray-400 text-center">No results</li>
                                    </ul>
                                </div>
                            </template>
                        </div>
                        @error('usage_equipment_id') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1.5">Date Used *</label>
                        <input type="date" wire:model="usage_date_used" class="w-full px-3.5 py-2.5 border border-gray-200 rounded-lg text-sm focus:ring-2 focus:ring-red-500 focus:border-transparent bg-gray-50 focus:bg-white transition">
                        @error('usage_date_used') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                    </div>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1.5">Used By (Employee) *</label>
                        <div x-data="{
                            open: false,
                            search: '',
                            selectedLabel: '',
                            anchorRect: null,
                            items: @js($employees->map(fn($e) => ['id' => $e->employee_id, 'label' => $e->firstname . ' ' . $e->lastname])->values()),
                            get filtered() {
                                return this.items.filter(i => i.label.toLowerCase().includes(this.search.toLowerCase()));
                            },
                            select(item) {
                                this.selectedLabel = item.label;
                                this.search = '';
                                this.open = false;
                                $wire.set('usage_employee_id', item.id);
                            },
                            clear() {
                                this.selectedLabel = '';
                                this.open = false;
                                $wire.set('usage_employee_id', '');
                            },
                            toggle() {
                                this.open = !this.open;
                                if (this.open) {
                                    this.$nextTick(() => {
                                        const rect = this.$refs.trigger.getBoundingClientRect();
                                        this.anchorRect = { top: rect.bottom + window.scrollY, left: rect.left + window.scrollX, width: rect.width };
                                    });
                                }
                            },
                            init() {
                                this.$watch('$wire.usage_employee_id', val => {
                                    if (!val) { this.selectedLabel = ''; return; }
                                    const found = this.items.find(i => i.id == val);
                                    if (found) this.selectedLabel = found.label;
                                });
                            }
                        }" class="relative" @click.away="open = false">
                            <div x-ref="trigger" @click="toggle()" class="w-full px-3.5 py-2.5 border border-gray-200 rounded-lg text-sm cursor-pointer flex items-center justify-between bg-gray-50 hover:border-red-400 transition" :class="open ? 'ring-2 ring-red-500 border-transparent bg-white' : ''">
                                <span x-text="selectedLabel || 'Select Employee'" :class="selectedLabel ? 'text-gray-900' : 'text-gray-400'" class="truncate"></span>
                                <div class="flex items-center gap-1 ml-2 shrink-0">
                                    <button type="button" x-show="selectedLabel" @click.stop="clear()" class="text-gray-400 hover:text-gray-600">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                    </button>
                                    <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                                </div>
                            </div>
                            <template x-teleport="body">
                                <div x-show="open" x-transition.opacity
                                     :style="anchorRect ? `position:absolute;top:${anchorRect.top+4}px;left:${anchorRect.left}px;width:${anchorRect.width}px;z-index:9999` : ''"
                                     class="bg-white border border-gray-200 rounded-lg shadow-lg overflow-hidden">
                                    <div class="p-2 border-b border-gray-100">
                                        <input type="text" x-model="search" @click.stop placeholder="Search employee..." class="w-full px-3 py-1.5 text-sm border border-gray-200 rounded-md focus:ring-2 focus:ring-red-500 focus:border-transparent outline-none" @keydown.escape="open = false">
                                    </div>
                                    <ul class="max-h-48 overflow-y-auto py-1">
                                        <template x-for="item in filtered" :key="item.id">
                                            <li @click="select(item)" class="px-4 py-2 text-sm text-gray-700 hover:bg-red-50 hover:text-red-700 cursor-pointer truncate" x-text="item.label"></li>
                                        </template>
                                        <li x-show="filtered.length === 0" class="px-4 py-2 text-sm text-gray-400 text-center">No results</li>
                                    </ul>
                                </div>
                            </template>
                        </div>
                        @error('usage_employee_id') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1.5">Specimen / Reagent *</label>
                        <input type="text" wire:model="usage_item_name" placeholder="e.g. Blood sample, Reagent X" class="w-full px-3.5 py-2.5 border border-gray-200 rounded-lg text-sm focus:ring-2 focus:ring-red-500 focus:border-transparent bg-gray-50 focus:bg-white transition">
                        @error('usage_item_name') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                    </div>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1.5">No. of Samples / Runs *</label>
                        <input type="number" wire:model="usage_quantity" min="1" class="w-full px-3.5 py-2.5 border border-gray-200 rounded-lg text-sm focus:ring-2 focus:ring-red-500 focus:border-transparent bg-gray-50 focus:bg-white transition">
                        @error('usage_quantity') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1.5">OR Number</label>
                        <input type="text" wire:model="usage_or_number" placeholder="Official Receipt Number" class="w-full px-3.5 py-2.5 border border-gray-200 rounded-lg text-sm focus:ring-2 focus:ring-red-500 focus:border-transparent bg-gray-50 focus:bg-white transition">
                        @error('usage_or_number') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                    </div>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1.5">Purpose *</label>
                    <textarea wire:model="usage_purpose" rows="2" placeholder="Describe the purpose of equipment usage" class="w-full px-3.5 py-2.5 border border-gray-200 rounded-lg text-sm focus:ring-2 focus:ring-red-500 focus:border-transparent bg-gray-50 focus:bg-white transition"></textarea>
                    @error('usage_purpose') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1.5">Status *</label>
                        <select wire:model="usage_status" class="w-full px-3.5 py-2.5 border border-gray-200 rounded-lg text-sm focus:ring-2 focus:ring-red-500 focus:border-transparent bg-gray-50 focus:bg-white transition">
                            <option value="functional">Functional</option>
                            <option value="not_functional">Not Functional</option>
                        </select>
                        @error('usage_status') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1.5">Remarks</label>
                        <textarea wire:model="usage_remarks" rows="2" placeholder="Additional notes" class="w-full px-3.5 py-2.5 border border-gray-200 rounded-lg text-sm focus:ring-2 focus:ring-red-500 focus:border-transparent bg-gray-50 focus:bg-white transition"></textarea>
                        @error('usage_remarks') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                    </div>
                </div>
                <div class="flex justify-end gap-3 pt-4 border-t border-gray-100">
                    <button type="button" wire:click="closeUsageModal" class="px-5 py-2.5 border border-gray-200 hover:bg-gray-50 text-gray-600 text-sm font-semibold rounded-xl transition-colors">Cancel</button>
                    <button type="submit" class="px-5 py-2.5 text-white text-sm font-semibold rounded-xl transition-colors" style="background-color:#d2334c" onmouseover="this.style.backgroundColor='#9f1239'" onmouseout="this.style.backgroundColor='#d2334c'">Record Usage</button>
                </div>
            </form>
        </div>
    </div>
    @endif

    <!-- Add Equipment Modal -->
    @if($showEquipmentModal)
    <div class="fixed inset-0 z-50 overflow-y-auto flex items-center justify-center p-4" style="background-color: rgba(15,23,42,0.75); backdrop-filter: blur(4px);">
        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-2xl max-h-[90vh] flex flex-col" wire:click.stop>
            {{-- Header — Crimson --}}
            <div class="flex items-center justify-between px-7 py-5 rounded-t-2xl" style="background: #d2334c;">
                <div class="flex items-center gap-3">
                    <div class="bg-white/20 rounded-xl p-2.5">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-lg font-bold text-white">Add New Equipment</h3>
                        <p class="text-red-200 text-xs mt-0.5">Register new laboratory equipment</p>
                    </div>
                </div>
                <button wire:click="closeEquipmentModal" class="text-white/70 hover:text-white transition-colors">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>

            <form wire:submit.prevent="save" class="p-7 overflow-y-auto flex-1 space-y-5">
                <div>
                    <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1.5">Equipment Name *</label>
                    <input type="text" wire:model="name" class="w-full px-3.5 py-2.5 border border-gray-200 rounded-lg text-sm focus:ring-2 focus:ring-red-500 focus:border-transparent bg-gray-50 focus:bg-white transition" required>
                    @error('name') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1.5">Model</label>
                        <input type="text" wire:model="model" class="w-full px-3.5 py-2.5 border border-gray-200 rounded-lg text-sm focus:ring-2 focus:ring-red-500 focus:border-transparent bg-gray-50 focus:bg-white transition">
                        @error('model') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1.5">Serial Number</label>
                        <input type="text" wire:model="serial_no" class="w-full px-3.5 py-2.5 border border-gray-200 rounded-lg text-sm focus:ring-2 focus:ring-red-500 focus:border-transparent bg-gray-50 focus:bg-white transition">
                        @error('serial_no') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                    </div>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1.5">Section *</label>
                        <select wire:model="section_id" class="w-full px-3.5 py-2.5 border border-gray-200 rounded-lg text-sm focus:ring-2 focus:ring-red-500 focus:border-transparent bg-gray-50 focus:bg-white transition" required>
                            <option value="">Select Section</option>
                            @foreach($sections as $section)
                                <option value="{{ $section->section_id }}">{{ $section->label }}</option>
                            @endforeach
                        </select>
                        @error('section_id') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1.5">Status</label>
                        <select wire:model="status" class="w-full px-3.5 py-2.5 border border-gray-200 rounded-lg text-sm focus:ring-2 focus:ring-red-500 focus:border-transparent bg-gray-50 focus:bg-white transition">
                            <option value="operational">Operational</option>
                            <option value="under_maintenance">Under Maintenance</option>
                            <option value="decommissioned">Decommissioned</option>
                        </select>
                    </div>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1.5">Purchase Date</label>
                        <input type="date" wire:model="purchase_date" class="w-full px-3.5 py-2.5 border border-gray-200 rounded-lg text-sm focus:ring-2 focus:ring-red-500 focus:border-transparent bg-gray-50 focus:bg-white transition">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1.5">Supplier</label>
                        <input type="text" wire:model="supplier" class="w-full px-3.5 py-2.5 border border-gray-200 rounded-lg text-sm focus:ring-2 focus:ring-red-500 focus:border-transparent bg-gray-50 focus:bg-white transition">
                    </div>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1.5">Remarks</label>
                    <textarea wire:model="remarks" rows="3" class="w-full px-3.5 py-2.5 border border-gray-200 rounded-lg text-sm focus:ring-2 focus:ring-red-500 focus:border-transparent bg-gray-50 focus:bg-white transition"></textarea>
                </div>
                <div class="flex justify-end gap-3 pt-4 border-t border-gray-100">
                    <button type="button" wire:click="closeEquipmentModal" class="px-5 py-2.5 border border-gray-200 hover:bg-gray-50 text-gray-600 text-sm font-semibold rounded-xl transition-colors">Cancel</button>
                    <button type="submit" class="px-5 py-2.5 text-white text-sm font-semibold rounded-xl transition-colors" style="background-color:#d2334c" onmouseover="this.style.backgroundColor='#9f1239'" onmouseout="this.style.backgroundColor='#d2334c'">Add Equipment</button>
                </div>
            </form>
        </div>
    </div>
    @endif

    <!-- Edit Equipment Modal -->
    @if($editMode)
    <div class="fixed inset-0 z-50 overflow-y-auto flex items-center justify-center p-4" style="background-color: rgba(15,23,42,0.75); backdrop-filter: blur(4px);">
        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-2xl max-h-[90vh] flex flex-col" wire:click.stop>
            {{-- Header — Crimson Dark --}}
            <div class="flex items-center justify-between px-7 py-5 rounded-t-2xl" style="background: #d2334c;">
                <div class="flex items-center gap-3">
                    <div class="bg-white/20 rounded-xl p-2.5">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-lg font-bold text-white">Edit Equipment</h3>
                        <p class="text-red-200 text-xs mt-0.5">Update equipment information</p>
                    </div>
                </div>
                <button wire:click="cancelEdit" class="text-white/70 hover:text-white transition-colors">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>

            <form wire:submit.prevent="update" class="p-7 overflow-y-auto flex-1 space-y-5">
                <div>
                    <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1.5">Equipment Name *</label>
                    <input type="text" wire:model="name" class="w-full px-3.5 py-2.5 border border-gray-200 rounded-lg text-sm focus:ring-2 focus:ring-red-500 focus:border-transparent bg-gray-50 focus:bg-white transition" required>
                    @error('name') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1.5">Model</label>
                        <input type="text" wire:model="model" class="w-full px-3.5 py-2.5 border border-gray-200 rounded-lg text-sm focus:ring-2 focus:ring-red-500 focus:border-transparent bg-gray-50 focus:bg-white transition">
                        @error('model') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1.5">Serial Number</label>
                        <input type="text" wire:model="serial_no" class="w-full px-3.5 py-2.5 border border-gray-200 rounded-lg text-sm focus:ring-2 focus:ring-red-500 focus:border-transparent bg-gray-50 focus:bg-white transition">
                        @error('serial_no') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                    </div>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1.5">Section *</label>
                        <select wire:model="section_id" class="w-full px-3.5 py-2.5 border border-gray-200 rounded-lg text-sm focus:ring-2 focus:ring-red-500 focus:border-transparent bg-gray-50 focus:bg-white transition" required>
                            <option value="">Select Section</option>
                            @foreach($sections as $section)
                                <option value="{{ $section->section_id }}">{{ $section->label }}</option>
                            @endforeach
                        </select>
                        @error('section_id') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1.5">Status</label>
                        <select wire:model="status" class="w-full px-3.5 py-2.5 border border-gray-200 rounded-lg text-sm focus:ring-2 focus:ring-red-500 focus:border-transparent bg-gray-50 focus:bg-white transition">
                            <option value="operational">Operational</option>
                            <option value="under_maintenance">Under Maintenance</option>
                            <option value="decommissioned">Decommissioned</option>
                        </select>
                    </div>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1.5">Purchase Date</label>
                        <input type="date" wire:model="purchase_date" class="w-full px-3.5 py-2.5 border border-gray-200 rounded-lg text-sm focus:ring-2 focus:ring-red-500 focus:border-transparent bg-gray-50 focus:bg-white transition">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1.5">Supplier</label>
                        <input type="text" wire:model="supplier" class="w-full px-3.5 py-2.5 border border-gray-200 rounded-lg text-sm focus:ring-2 focus:ring-red-500 focus:border-transparent bg-gray-50 focus:bg-white transition">
                    </div>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1.5">Remarks</label>
                    <textarea wire:model="remarks" rows="3" class="w-full px-3.5 py-2.5 border border-gray-200 rounded-lg text-sm focus:ring-2 focus:ring-red-500 focus:border-transparent bg-gray-50 focus:bg-white transition"></textarea>
                </div>
                <div class="flex justify-end gap-3 pt-4 border-t border-gray-100">
                    <button type="button" wire:click="cancelEdit" class="px-5 py-2.5 border border-gray-200 hover:bg-gray-50 text-gray-600 text-sm font-semibold rounded-xl transition-colors">Cancel</button>
                    <button type="submit" class="px-5 py-2.5 text-white text-sm font-semibold rounded-xl transition-colors" style="background-color:#be123c" onmouseover="this.style.backgroundColor='#881337'" onmouseout="this.style.backgroundColor='#be123c'">Update Equipment</button>
                </div>
            </form>
        </div>
    </div>
    @endif

    <!-- Details Modal -->
    @if($showDetailsModal && $detailsEquipment)
    <div class="fixed inset-0 z-50 overflow-y-auto flex items-center justify-center p-4" style="background-color: rgba(15,23,42,0.75); backdrop-filter: blur(4px);">
        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-5xl max-h-[90vh] flex flex-col" wire:click.stop>
            {{-- Header — Crimson --}}
            <div class="flex items-center justify-between px-7 py-5 rounded-t-2xl" style="background: #d2334c;">
                <div class="flex items-center gap-3">
                    <div class="bg-white/20 rounded-xl p-2.5">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z"/>
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-lg font-bold text-white">{{ $detailsEquipment->name }}</h3>
                        <p class="text-red-200 text-xs mt-0.5">Equipment details, maintenance, calibration &amp; usage history</p>
                    </div>
                </div>
                <button wire:click="closeDetailsModal" class="text-white/70 hover:text-white transition-colors">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>

            <div class="p-7 overflow-y-auto flex-1 space-y-6">
                {{-- Equipment Info Strip --}}
                <div class="rounded-xl p-5" style="background-color:#fef2f2; border: 1px solid #fecaca;">
                    <div class="grid grid-cols-2 lg:grid-cols-4 gap-x-6 gap-y-3">
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-wider" style="color:#d2334c">Model</p>
                            <p class="text-sm font-bold text-gray-900 mt-0.5">{{ $detailsEquipment->model ?? 'N/A' }}</p>
                        </div>
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-wider" style="color:#d2334c">Serial No.</p>
                            <p class="text-sm font-bold text-gray-900 mt-0.5">{{ $detailsEquipment->serial_no ?? 'N/A' }}</p>
                        </div>
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-wider" style="color:#d2334c">Section</p>
                            <p class="text-sm font-bold text-gray-900 mt-0.5">{{ $detailsEquipment->section->label ?? 'N/A' }}</p>
                        </div>
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-wider" style="color:#d2334c">Status</p>
                            <p class="text-sm font-bold mt-0.5 {{ $detailsEquipment->status === 'operational' ? 'text-emerald-700' : ($detailsEquipment->status === 'under_maintenance' ? 'text-amber-700' : 'text-red-700') }}">{{ match($detailsEquipment->status) { 'operational' => 'Operational', 'under_maintenance' => 'Under Maintenance', 'decommissioned' => 'Decommissioned', default => ucfirst($detailsEquipment->status) } }}</p>
                        </div>
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-wider" style="color:#d2334c">Purchase Date</p>
                            <p class="text-sm text-gray-900 mt-0.5">{{ $detailsEquipment->purchase_date ? $detailsEquipment->purchase_date->format('M d, Y') : 'N/A' }}</p>
                        </div>
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-wider" style="color:#d2334c">Supplier</p>
                            <p class="text-sm text-gray-900 mt-0.5">{{ $detailsEquipment->supplier ?? 'N/A' }}</p>
                        </div>
                        <div class="lg:col-span-2">
                            <p class="text-xs font-semibold uppercase tracking-wider" style="color:#d2334c">Remarks</p>
                            <p class="text-sm text-gray-900 mt-0.5">{{ $detailsEquipment->remarks ?? 'N/A' }}</p>
                        </div>
                    </div>
                </div>

                {{-- Maintenance History --}}
                <div>
                    <h4 class="text-sm font-bold text-gray-700 mb-3 flex items-center gap-2">
                        <svg class="w-4 h-4" style="color: #d2334c" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.066 2.573c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.573 1.066c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.066-2.573c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                        Maintenance History ({{ count($detailsMaintenanceHistory) }})
                    </h4>
                    @if(count($detailsMaintenanceHistory) > 0)
                        <div class="overflow-x-auto rounded-xl border border-gray-200">
                            <table class="w-full text-sm">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-4 py-2.5 text-left text-xs font-semibold text-gray-500 uppercase">Date</th>
                                        <th class="px-4 py-2.5 text-left text-xs font-semibold text-gray-500 uppercase">Performed By</th>
                                        <th class="px-4 py-2.5 text-left text-xs font-semibold text-gray-500 uppercase">Findings</th>
                                        <th class="px-4 py-2.5 text-left text-xs font-semibold text-gray-500 uppercase">Action Taken</th>
                                        <th class="px-4 py-2.5 text-left text-xs font-semibold text-gray-500 uppercase">Status</th>
                                        <th class="px-4 py-2.5 text-left text-xs font-semibold text-gray-500 uppercase">Next Due</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100">
                                    @foreach($detailsMaintenanceHistory as $maint)
                                        @php $m = (object)$maint; @endphp
                                        <tr class="hover:bg-gray-50">
                                            <td class="px-4 py-2.5 text-gray-900 font-medium whitespace-nowrap">{{ \Carbon\Carbon::parse($m->performed_date)->format('M d, Y') }}</td>
                                            <td class="px-4 py-2.5 text-gray-600">{{ $m->performed_by_name ?? 'N/A' }}</td>
                                            <td class="px-4 py-2.5 text-gray-600 max-w-xs truncate" title="{{ $m->findings }}">{{ \Illuminate\Support\Str::limit($m->findings, 40) }}</td>
                                            <td class="px-4 py-2.5 text-gray-600 max-w-xs truncate" title="{{ $m->action_taken }}">{{ \Illuminate\Support\Str::limit($m->action_taken, 40) }}</td>
                                            <td class="px-4 py-2.5">
                                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold
                                                    {{ ($m->status ?? '') === 'completed' ? 'bg-green-100 text-green-700' : (($m->status ?? '') === 'overdue' ? 'bg-red-100 text-red-700' : 'bg-amber-100 text-amber-700') }}">
                                                    {{ strtoupper($m->status ?? 'N/A') }}
                                                </span>
                                            </td>
                                            <td class="px-4 py-2.5 text-gray-600 whitespace-nowrap">{{ $m->next_due_date ? \Carbon\Carbon::parse($m->next_due_date)->format('M d, Y') : '—' }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="text-center py-6 bg-gray-50 rounded-xl">
                            <p class="text-gray-400 text-sm">No maintenance records found</p>
                        </div>
                    @endif
                </div>

                {{-- Calibration History --}}
                <div>
                    <h4 class="text-sm font-bold text-gray-700 mb-3 flex items-center gap-2">
                        <svg class="w-4 h-4" style="color: #d2334c" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
                        Calibration History ({{ count($detailsCalibrationHistory) }})
                    </h4>
                    @if(count($detailsCalibrationHistory) > 0)
                        <div class="overflow-x-auto rounded-xl border border-gray-200">
                            <table class="w-full text-sm">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-4 py-2.5 text-left text-xs font-semibold text-gray-500 uppercase">Date</th>
                                        <th class="px-4 py-2.5 text-left text-xs font-semibold text-gray-500 uppercase">Procedure</th>
                                        <th class="px-4 py-2.5 text-left text-xs font-semibold text-gray-500 uppercase">Performed By</th>
                                        <th class="px-4 py-2.5 text-left text-xs font-semibold text-gray-500 uppercase">Result</th>
                                        <th class="px-4 py-2.5 text-left text-xs font-semibold text-gray-500 uppercase">Notes</th>
                                        <th class="px-4 py-2.5 text-left text-xs font-semibold text-gray-500 uppercase">Next Due</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100">
                                    @foreach($detailsCalibrationHistory as $cal)
                                        @php $c = (object)$cal; @endphp
                                        <tr class="hover:bg-gray-50">
                                            <td class="px-4 py-2.5 text-gray-900 font-medium whitespace-nowrap">{{ \Carbon\Carbon::parse($c->calibration_date)->format('M d, Y') }}</td>
                                            <td class="px-4 py-2.5 text-gray-600">{{ $c->procedure_name ?? 'N/A' }}</td>
                                            <td class="px-4 py-2.5 text-gray-600">{{ $c->performed_by_name ?? 'N/A' }}</td>
                                            <td class="px-4 py-2.5">
                                                @if($c->result_status === 'pass')
                                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold bg-emerald-100 text-emerald-700">PASS</span>
                                                @elseif($c->result_status === 'fail')
                                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold bg-red-100 text-red-700">FAIL</span>
                                                @else
                                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold bg-amber-100 text-amber-700">{{ strtoupper($c->result_status ?? 'N/A') }}</span>
                                                @endif
                                            </td>
                                            <td class="px-4 py-2.5 text-gray-600 max-w-xs truncate" title="{{ $c->notes }}">{{ \Illuminate\Support\Str::limit($c->notes, 30) ?? '—' }}</td>
                                            <td class="px-4 py-2.5 text-gray-600 whitespace-nowrap">{{ $c->next_calibration_date ? \Carbon\Carbon::parse($c->next_calibration_date)->format('M d, Y') : '—' }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="text-center py-6 bg-gray-50 rounded-xl">
                            <p class="text-gray-400 text-sm">No calibration records found</p>
                        </div>
                    @endif
                </div>

                {{-- Usage History --}}
                <div>
                    <h4 class="text-sm font-bold text-gray-700 mb-3 flex items-center gap-2">
                        <svg class="w-4 h-4" style="color: #d2334c" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                        Usage History ({{ count($detailsUsageHistory) }})
                    </h4>
                    @if(count($detailsUsageHistory) > 0)
                        <div class="overflow-x-auto rounded-xl border border-gray-200">
                            <table class="w-full text-sm">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-4 py-2.5 text-left text-xs font-semibold text-gray-500 uppercase">Date</th>
                                        <th class="px-4 py-2.5 text-left text-xs font-semibold text-gray-500 uppercase">Used By</th>
                                        <th class="px-4 py-2.5 text-left text-xs font-semibold text-gray-500 uppercase">Specimen/Reagent</th>
                                        <th class="px-4 py-2.5 text-left text-xs font-semibold text-gray-500 uppercase">Samples</th>
                                        <th class="px-4 py-2.5 text-left text-xs font-semibold text-gray-500 uppercase">Purpose</th>
                                        <th class="px-4 py-2.5 text-left text-xs font-semibold text-gray-500 uppercase">Status</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100">
                                    @foreach($detailsUsageHistory as $usg)
                                        @php $u = (object)$usg; @endphp
                                        <tr class="hover:bg-gray-50">
                                            <td class="px-4 py-2.5 text-gray-900 font-medium whitespace-nowrap">{{ \Carbon\Carbon::parse($u->date_used)->format('M d, Y') }}</td>
                                            <td class="px-4 py-2.5 text-gray-600">{{ $u->user_name }}</td>
                                            <td class="px-4 py-2.5 text-gray-600">{{ $u->item_name }}</td>
                                            <td class="px-4 py-2.5 text-gray-600 text-center">{{ $u->quantity }}</td>
                                            <td class="px-4 py-2.5 text-gray-600 max-w-xs truncate" title="{{ $u->purpose }}">{{ \Illuminate\Support\Str::limit($u->purpose, 35) }}</td>
                                            <td class="px-4 py-2.5">
                                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold {{ $u->status === 'functional' ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700' }}">
                                                    {{ strtoupper($u->status) }}
                                                </span>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="text-center py-6 bg-gray-50 rounded-xl">
                            <p class="text-gray-400 text-sm">No usage records found</p>
                        </div>
                    @endif
                </div>

                {{-- Footer --}}
                <div class="flex items-center justify-between pt-4 border-t border-gray-100">
                    <p class="text-xs text-gray-400">Added: {{ $detailsEquipment->datetime_added ? $detailsEquipment->datetime_added->format('M d, Y h:i A') : 'N/A' }}</p>
                    <button wire:click="closeDetailsModal" class="px-5 py-2.5 border border-gray-200 hover:bg-gray-50 text-gray-600 text-sm font-semibold rounded-xl transition-colors">Close</button>
                </div>
            </div>
        </div>
    </div>
    @endif

    <!-- Record Maintenance Modal -->
    @if($showMaintenanceModal)
    <div class="fixed inset-0 z-50 overflow-y-auto flex items-center justify-center p-4" style="background-color: rgba(15,23,42,0.75); backdrop-filter: blur(4px);">
        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-2xl max-h-[90vh] flex flex-col" wire:click.stop>
            {{-- Header — Crimson --}}
            <div class="flex items-center justify-between px-7 py-5 rounded-t-2xl" style="background: #d2334c;">
                <div class="flex items-center gap-3">
                    <div class="bg-white/20 rounded-xl p-2.5">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.066 2.573c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.573 1.066c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.066-2.573c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-lg font-bold text-white">Record Maintenance</h3>
                        <p class="text-red-200 text-xs mt-0.5">
                            @if($maint_equipment_name)
                                {{ $maint_equipment_name }}
                            @else
                                Log maintenance activity for equipment
                            @endif
                        </p>
                    </div>
                </div>
                <button wire:click="closeMaintenanceModal" class="text-white/70 hover:text-white transition-colors">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>

            <form wire:submit.prevent="saveMaintenance" class="p-7 overflow-y-auto flex-1 space-y-5">
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1.5">Equipment *</label>
                        @if($maint_equipment_name)
                            <div class="w-full px-3.5 py-2.5 border border-gray-200 rounded-lg text-sm bg-gray-100 text-gray-700">{{ $maint_equipment_name }}</div>
                            <input type="hidden" wire:model="maint_equipment_id">
                        @else
                            <select wire:model="maint_equipment_id" class="w-full px-3.5 py-2.5 border border-gray-200 rounded-lg text-sm focus:ring-2 focus:ring-red-500 focus:border-transparent bg-gray-50 focus:bg-white transition">
                                <option value="">Select Equipment</option>
                                @foreach($allEquipment as $eq)
                                    <option value="{{ $eq->equipment_id }}">{{ $eq->name }}</option>
                                @endforeach
                            </select>
                        @endif
                        @error('maint_equipment_id') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1.5">Date Performed *</label>
                        <input type="date" wire:model="maint_performed_date" class="w-full px-3.5 py-2.5 border border-gray-200 rounded-lg text-sm focus:ring-2 focus:ring-red-500 focus:border-transparent bg-gray-50 focus:bg-white transition">
                        @error('maint_performed_date') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                    </div>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1.5">Performed By *</label>
                        <select wire:model="maint_performed_by" class="w-full px-3.5 py-2.5 border border-gray-200 rounded-lg text-sm focus:ring-2 focus:ring-red-500 focus:border-transparent bg-gray-50 focus:bg-white transition">
                            <option value="">Select Employee</option>
                            @foreach($employees as $emp)
                                <option value="{{ $emp->employee_id }}">{{ $emp->firstname }} {{ $emp->lastname }}</option>
                            @endforeach
                        </select>
                        @error('maint_performed_by') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1.5">Status *</label>
                        <select wire:model="maint_status" class="w-full px-3.5 py-2.5 border border-gray-200 rounded-lg text-sm focus:ring-2 focus:ring-red-500 focus:border-transparent bg-gray-50 focus:bg-white transition">
                            <option value="completed">Completed</option>
                            <option value="pending">Pending</option>
                            <option value="overdue">Overdue</option>
                        </select>
                        @error('maint_status') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                    </div>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1.5">Findings</label>
                    <textarea wire:model="maint_findings" rows="3" placeholder="Describe findings during maintenance..." class="w-full px-3.5 py-2.5 border border-gray-200 rounded-lg text-sm focus:ring-2 focus:ring-red-500 focus:border-transparent bg-gray-50 focus:bg-white transition"></textarea>
                    @error('maint_findings') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1.5">Action Taken</label>
                    <textarea wire:model="maint_action_taken" rows="3" placeholder="Describe actions taken..." class="w-full px-3.5 py-2.5 border border-gray-200 rounded-lg text-sm focus:ring-2 focus:ring-red-500 focus:border-transparent bg-gray-50 focus:bg-white transition"></textarea>
                    @error('maint_action_taken') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1.5">Next Due Date</label>
                    <input type="date" wire:model="maint_next_due_date" class="w-full px-3.5 py-2.5 border border-gray-200 rounded-lg text-sm focus:ring-2 focus:ring-red-500 focus:border-transparent bg-gray-50 focus:bg-white transition">
                    <p class="text-xs text-gray-400 mt-1">Set the next scheduled maintenance date</p>
                    @error('maint_next_due_date') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                </div>
                <div class="flex justify-end gap-3 pt-4 border-t border-gray-100">
                    <button type="button" wire:click="closeMaintenanceModal" class="px-5 py-2.5 border border-gray-200 hover:bg-gray-50 text-gray-600 text-sm font-semibold rounded-xl transition-colors">Cancel</button>
                    <button type="submit" class="px-5 py-2.5 text-white text-sm font-semibold rounded-xl transition-colors" style="background-color:#d2334c" onmouseover="this.style.backgroundColor='#9f1239'" onmouseout="this.style.backgroundColor='#d2334c'">Save Maintenance Record</button>
                </div>
            </form>
        </div>
    </div>
    @endif

    <!-- Delete Confirmation Modal -->
    @if($showDeleteModal)
    <div class="fixed inset-0 z-[60] overflow-y-auto flex items-center justify-center p-4" style="background-color: rgba(15,23,42,0.75); backdrop-filter: blur(4px);">
        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md" wire:click.stop>
            <div class="p-8 text-center">
                <div class="mx-auto mb-4 w-14 h-14 rounded-full bg-red-100 flex items-center justify-center">
                    <svg class="w-7 h-7 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z"/>
                    </svg>
                </div>
                <h3 class="text-lg font-bold text-gray-900 mb-2">Delete Equipment</h3>
                <p class="text-sm text-gray-600 mb-6">
                    Are you sure you want to delete <strong class="text-gray-900">{{ $deleteEquipmentName }}</strong>?
                    This action will remove it from the equipment list and maintenance alerts.
                </p>
                <div class="flex justify-center gap-3">
                    <button wire:click="cancelDelete" class="px-5 py-2.5 border border-gray-200 hover:bg-gray-50 text-gray-600 text-sm font-semibold rounded-xl transition-colors">Cancel</button>
                    <button wire:click="deleteConfirmed" class="px-5 py-2.5 bg-red-600 hover:bg-red-700 text-white text-sm font-semibold rounded-xl transition-colors">Delete Equipment</button>
                </div>
            </div>
        </div>
    </div>
    @endif
</div>