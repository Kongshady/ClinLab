<?php

use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Validate;
use App\Models\CalibrationRecord;
use App\Models\Equipment;
use App\Models\Employee;
use App\Services\CertificateService;

new class extends Component
{
    use WithPagination;

    public $showModal = false;
    public $showProcedureModal = false;
    public $showDetailsModal = false;
    public $selectedEquipment = null;
    public $selectedProcedure = null;
    public $selectedProcedureDetails = null;

    #[Validate('required|date')]
    public $calibration_date = '';

    // New Procedure Form Fields
    #[Validate('required|exists:equipment,equipment_id')]
    public $procedure_equipment_id = '';

    #[Validate('required|string|max:255')]
    public $procedure_name = '';

    #[Validate('nullable|string|max:255')]
    public $standard_reference = '';

    #[Validate('required|in:daily,weekly,monthly,quarterly,annual')]
    public $frequency = 'annual';

    #[Validate('required|date')]
    public $procedure_next_due_date = '';

    #[Validate('required|exists:employee,employee_id')]
    public $performed_by = '';

    #[Validate('required|in:pass,fail,conditional')]
    public $result_status = 'pass';

    #[Validate('nullable|string')]
    public $notes = '';

    #[Validate('nullable|date')]
    public $next_calibration_date = '';

    public $flashMessage = '';

    public function mount()
    {
        if (session()->has('success')) {
            $this->flashMessage = session('success');
        }
        $this->calibration_date = date('Y-m-d');
    }

    public function openModal($equipmentId, $procedureId)
    {
        $this->showModal = true;
        $this->selectedEquipment = Equipment::find($equipmentId);
        $this->selectedProcedure = DB::table('calibration_procedure')->where('procedure_id', $procedureId)->first();
        $this->calibration_date = date('Y-m-d');
        $this->reset(['performed_by', 'notes', 'next_calibration_date']);
        $this->result_status = 'pass';
    }

    public function closeModal()
    {
        $this->showModal = false;
        $this->selectedEquipment = null;
        $this->selectedProcedure = null;
    }

    public function openProcedureModal()
    {
        $this->showProcedureModal = true;
        $this->reset(['procedure_equipment_id', 'procedure_name', 'standard_reference', 'frequency', 'procedure_next_due_date']);
        $this->frequency = 'annual';
    }

    public function closeProcedureModal()
    {
        $this->showProcedureModal = false;
    }

    public function saveProcedure()
    {
        try {
            $this->validate([
                'procedure_equipment_id' => 'required|exists:equipment,equipment_id',
                'procedure_name' => 'required|string|max:255',
                'standard_reference' => 'nullable|string|max:255',
                'frequency' => 'required|in:daily,weekly,monthly,quarterly,annual',
                'procedure_next_due_date' => 'required|date',
            ]);

            DB::table('calibration_procedure')->insert([
                'equipment_id' => $this->procedure_equipment_id,
                'procedure_name' => $this->procedure_name,
                'standard_reference' => $this->standard_reference,
                'frequency' => $this->frequency,
                'next_due_date' => $this->procedure_next_due_date,
                'is_active' => 1,
                'datetime_added' => now(),
            ]);

            $this->flashMessage = 'Calibration procedure added successfully!';
            $this->closeProcedureModal();
            $this->resetPage();
        } catch (\Exception $e) {
            session()->flash('error', 'Failed to add procedure: ' . $e->getMessage());
        }
    }

    public function openDetailsModal($procedureId)
    {
        $this->showDetailsModal = true;
        $this->selectedProcedureDetails = DB::table('calibration_procedure as cp')
            ->join('equipment as e', 'cp.equipment_id', '=', 'e.equipment_id')
            ->where('cp.procedure_id', $procedureId)
            ->select(
                'cp.*',
                'e.name as equipment_name',
                'e.model as equipment_model'
            )
            ->first();
    }

    public function closeDetailsModal()
    {
        $this->showDetailsModal = false;
        $this->selectedProcedureDetails = null;
    }

    public function save()
    {
        try {
            $this->validate([
                'calibration_date' => 'required|date',
                'performed_by' => 'required|exists:employee,employee_id',
                'result_status' => 'required|in:pass,fail,conditional',
                'notes' => 'nullable|string',
                'next_calibration_date' => 'nullable|date',
            ]);

            CalibrationRecord::create([
                'procedure_id' => $this->selectedProcedure->procedure_id,
                'equipment_id' => $this->selectedEquipment->equipment_id,
                'calibration_date' => $this->calibration_date,
                'performed_by' => $this->performed_by,
                'result_status' => $this->result_status,
                'notes' => $this->notes,
                'next_calibration_date' => $this->next_calibration_date,
                'datetime_added' => now(),
            ]);

            $this->flashMessage = 'Calibration recorded successfully!';
            $this->closeModal();
            $this->resetPage();
        } catch (\Exception $e) {
            session()->flash('error', 'Failed to record calibration: ' . $e->getMessage());
        }
    }

    public function generateCertificate($calibrationId, $equipmentId)
    {
        try {
            $certificateService = new CertificateService();
            $result = $certificateService->generateFromCalibration($calibrationId, $equipmentId);

            // Download PDF
            return response()->streamDownload(function() use ($result) {
                echo $result['pdf']->output();
            }, $result['certificate']->certificate_no . '.pdf');

        } catch (\Exception $e) {
            $this->flashMessage = 'Error: ' . $e->getMessage();
        }
    }

    public function with(): array
    {
        // Get calibration procedures that are due or near due (within 90 days)
        $alerts = DB::table('calibration_procedure as cp')
            ->join('equipment as e', 'cp.equipment_id', '=', 'e.equipment_id')
            ->where('cp.is_active', 1)
            ->whereRaw('DATEDIFF(day, GETDATE(), cp.next_due_date) <= 90')
            ->select(
                'cp.procedure_id',
                'cp.equipment_id',
                'e.name as equipment_name',
                'cp.procedure_name',
                'cp.standard_reference',
                'cp.next_due_date',
                DB::raw('DATEDIFF(day, GETDATE(), cp.next_due_date) as days_until_due')
            )
            ->orderBy('cp.next_due_date')
            ->get();

        // Get all active calibration procedures
        $procedures = DB::table('calibration_procedure as cp')
            ->join('equipment as e', 'cp.equipment_id', '=', 'e.equipment_id')
            ->where('cp.is_active', 1)
            ->select(
                'cp.procedure_id',
                'cp.equipment_id',
                'e.name as equipment_name',
                'e.model as equipment_model',
                'cp.procedure_name',
                'cp.standard_reference',
                'cp.frequency',
                'cp.next_due_date'
            )
            ->orderBy('e.name')
            ->get();

        // Get recent calibration records
        $records = DB::table('calibration_record as cr')
            ->join('equipment as e', 'cr.equipment_id', '=', 'e.equipment_id')
            ->join('employee as emp', 'cr.performed_by', '=', 'emp.employee_id')
            ->leftJoin('calibration_procedure as cp', 'cr.procedure_id', '=', 'cp.procedure_id')
            ->select(
                'cr.record_id',
                'cr.calibration_date',
                'e.name as equipment_name',
                'cp.procedure_name',
                DB::raw("CONCAT(emp.firstname, ' ', emp.lastname) as performed_by_name"),
                'cr.result_status',
                'cr.notes',
                'cr.next_calibration_date'
            )
            ->orderBy('cr.calibration_date', 'desc')
            ->limit(50)
            ->get();

        return [
            'alerts' => $alerts,
            'procedures' => $procedures,
            'records' => $records,
            'employees' => Employee::active()->orderBy('lastname')->get(),
            'equipment' => Equipment::active()->orderBy('name')->get()
        ];
    }
};
?>

<div class="p-8 space-y-6 bg-gray-50 min-h-screen">
    <div class="mb-6">
        <h1 class="text-3xl font-bold text-gray-800">Calibration Management</h1>
        <p class="text-gray-600 mt-1">Monitor and manage equipment calibration schedules</p>
    </div>

    @if($flashMessage)
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded-lg" role="alert">
            <span class="block sm:inline">{{ $flashMessage }}</span>
        </div>
    @endif

    <!-- Calibration Alerts Table -->
    <div class="bg-white rounded-lg shadow border border-gray-200">
        <div class="px-6 py-4 border-b border-gray-200 flex items-center gap-3">
            <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
            </svg>
            <h2 class="text-xl font-semibold text-gray-900">Calibration Alerts</h2>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-50 border-b border-gray-200">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Equipment</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Procedure</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Standard Reference</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Due Date</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Days Until Due</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @forelse($alerts as $alert)
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                {{ $alert->equipment_name }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                                {{ $alert->procedure_name }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                                {{ $alert->standard_reference ?? 'N/A' }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                                {{ \Carbon\Carbon::parse($alert->next_due_date)->format('M d, Y') }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm">
                                <span class="font-semibold {{ $alert->days_until_due < 0 ? 'text-red-600' : ($alert->days_until_due <= 30 ? 'text-yellow-600' : 'text-gray-900') }}">
                                    {{ $alert->days_until_due }}
                                </span>
                            </td>
                            <td class="px-8 py-4 whitespace-nowrap text-sm">
                                <button 
                                    wire:click="openModal({{ $alert->equipment_id }}, {{ $alert->procedure_id }})"
                                    class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg font-medium transition-colors">
                                    Record Calibration
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-12 text-center">
                                <svg class="w-16 h-16 mx-auto mb-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                                <p class="text-gray-500 font-medium">No calibration alerts</p>
                                <p class="text-gray-400 text-sm mt-1">All equipment is up to date</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Calibration Procedures Table -->
    <div class="bg-white rounded-lg shadow border border-gray-200">
        <div class="px-6 py-4 border-b border-gray-200 flex items-center justify-between">
            <div class="flex items-center gap-3">
                <svg class="w-6 h-6 text-gray-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                </svg>
                <h2 class="text-xl font-semibold text-gray-900">Calibration Procedures</h2>
            </div>
            <button wire:click="openProcedureModal" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg font-medium transition-colors flex items-center gap-2">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                Add New Procedure
            </button>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-50 border-b border-gray-200">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Equipment</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Procedure Name</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Standard Reference</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Frequency</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Next Due Date</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @forelse($procedures as $procedure)
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                {{ $procedure->equipment_name }}
                                @if($procedure->equipment_model)
                                    <span class="text-gray-500">({{ $procedure->equipment_model }})</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                {{ $procedure->procedure_name }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                                {{ $procedure->standard_reference ?? 'N/A' }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                                {{ strtoupper($procedure->frequency) }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                                {{ \Carbon\Carbon::parse($procedure->next_due_date)->format('M d, Y') }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm">
                                <button wire:click="openDetailsModal({{ $procedure->procedure_id }})" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg font-medium transition-colors">
                                    View Details
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-12 text-center">
                                <svg class="w-16 h-16 mx-auto mb-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                                </svg>
                                <p class="text-gray-500 font-medium">No calibration procedures found</p>
                                <p class="text-gray-400 text-sm mt-1">Add procedures to track equipment calibration schedules</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Recent Calibration Records Table -->
    <div class="bg-white rounded-lg shadow border border-gray-200">
        <div class="px-6 py-4 border-b border-gray-200 flex items-center gap-3">
            <svg class="w-6 h-6 text-gray-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"/>
            </svg>
            <h2 class="text-xl font-semibold text-gray-900">Recent Calibration Records</h2>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-50 border-b border-gray-200">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Date</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Equipment</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Procedure</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Performed By</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Result</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Notes</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Next Due</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @forelse($records as $record)
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                {{ \Carbon\Carbon::parse($record->calibration_date)->format('M d, Y') }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-blue-600 font-medium">
                                {{ $record->equipment_name }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                                {{ $record->procedure_name ?? 'N/A' }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                {{ $record->performed_by_name }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm">
                                @if($record->result_status === 'pass')
                                    <span class="font-semibold text-green-600">PASS</span>
                                @elseif($record->result_status === 'fail')
                                    <span class="font-semibold text-red-600">FAIL</span>
                                @else
                                    <span class="font-semibold text-gray-900">CONDITIONAL</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-600">
                                {{ $record->notes ?? '' }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                @if($record->next_calibration_date)
                                    {{ \Carbon\Carbon::parse($record->next_calibration_date)->format('M d, Y') }}
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-6 py-12 text-center">
                                <svg class="w-16 h-16 mx-auto mb-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"/>
                                </svg>
                                <p class="text-gray-500 font-medium">No calibration records found</p>
                                <p class="text-gray-400 text-sm mt-1">Calibration records will appear here once equipment is calibrated</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Modal -->
    @if($showModal)
        <div class="fixed inset-1 z-20 flex items-center justify-center p-4" wire:click="closeModal" style="background-color: rgba(0, 0, 0, 0.2); backdrop-filter: blur(2px); -webkit-backdrop-filter: blur(2px);">
            <div class="bg-white rounded-lg shadow-2xl w-full max-w-md" wire:click.stop>
                <div class="px-6 py-4 border-b border-gray-200">
                    <h3 class="text-xl font-semibold text-gray-900">Record Calibration</h3>
                </div>
                
                <form wire:submit.prevent="save" class="p-6 space-y-4">
                    @if (session()->has('error'))
                        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-lg">
                            {{ session('error') }}
                        </div>
                    @endif
                    
                    <!-- Equipment and Procedure Info -->
                    <div class="bg-gray-50 rounded-lg p-4 space-y-2">
                        <div>
                            <span class="font-semibold text-gray-700">Equipment:</span>
                            <span class="text-gray-900 ml-2">{{ $selectedEquipment->name ?? '' }}</span>
                        </div>
                        <div>
                            <span class="font-semibold text-gray-700">Procedure:</span>
                            <span class="text-gray-900 ml-2">{{ $selectedProcedure->procedure_name ?? '' }}</span>
                        </div>
                    </div>

                    <!-- Calibration Date -->
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Calibration Date *</label>
                        <input 
                            type="date" 
                            wire:model="calibration_date"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        @error('calibration_date') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                    </div>

                    <!-- Performed By -->
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Performed By *</label>
                        <select 
                            wire:model="performed_by"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                            <option value="">Select Employee</option>
                            @foreach($employees as $employee)
                                <option value="{{ $employee->employee_id }}">{{ $employee->full_name }}</option>
                            @endforeach
                        </select>
                        @error('performed_by') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                    </div>

                    <!-- Result Status -->
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Result Status *</label>
                        <select 
                            wire:model="result_status"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                            <option value="pass">Pass</option>
                            <option value="fail">Fail</option>
                            <option value="conditional">Conditional</option>
                        </select>
                        @error('result_status') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                    </div>

                    <!-- Notes -->
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Notes</label>
                        <textarea 
                            wire:model="notes" 
                            rows="3"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"></textarea>
                        @error('notes') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                    </div>

                    <!-- Next Calibration Date -->
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Next Calibration Date</label>
                        <input 
                            type="date" 
                            wire:model="next_calibration_date"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        @error('next_calibration_date') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                    </div>

                    <!-- Buttons -->
                    <div class="flex items-center gap-3 pt-4">
                        <button 
                            type="submit"
                            wire:loading.attr="disabled"
                            wire:loading.class="opacity-50 cursor-not-allowed"
                            class="flex-1 px-6 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg font-medium transition-colors">
                            <span wire:loading.remove wire:target="save">Record Calibration</span>
                            <span wire:loading wire:target="save">Saving...</span>
                        </button>
                        <button 
                            type="button"
                            wire:click="closeModal"
                            wire:loading.attr="disabled"
                            class="flex-1 px-6 py-2 bg-red-600 hover:bg-red-700 text-white rounded-lg font-medium transition-colors">
                            Cancel
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif

    <!-- Add New Procedure Modal -->
    @if($showProcedureModal)
        <div class="fixed inset-0 z-20 flex items-center justify-center p-4" wire:click="closeProcedureModal" style="background-color: rgba(0, 0, 0, 0.2); backdrop-filter: blur(2px); -webkit-backdrop-filter: blur(2px);">
            <div class="bg-white rounded-lg shadow-2xl w-full max-w-md" wire:click.stop>
                <div class="px-6 py-4 border-b border-gray-200">
                    <h3 class="text-xl font-semibold text-gray-900">Add New Calibration Procedure</h3>
                </div>
                
                <form wire:submit.prevent="saveProcedure" class="p-6 space-y-4">
                    @if (session()->has('error'))
                        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-lg">
                            {{ session('error') }}
                        </div>
                    @endif
                    
                    <!-- Equipment Selection -->
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Equipment *</label>
                        <select 
                            wire:model="procedure_equipment_id"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                            <option value="">Select Equipment</option>
                            @foreach($equipment as $equip)
                                <option value="{{ $equip->equipment_id }}">{{ $equip->name }} @if($equip->model) ({{ $equip->model }}) @endif</option>
                            @endforeach
                        </select>
                        @error('procedure_equipment_id') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                    </div>

                    <!-- Procedure Name -->
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Procedure Name *</label>
                        <input 
                            type="text" 
                            wire:model="procedure_name"
                            placeholder="e.g., General Calibration"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        @error('procedure_name') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                    </div>

                    <!-- Standard Reference -->
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Standard Reference</label>
                        <input 
                            type="text" 
                            wire:model="standard_reference"
                            placeholder="e.g., ISO 17025"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        @error('standard_reference') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                    </div>

                    <!-- Frequency -->
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Frequency *</label>
                        <select 
                            wire:model="frequency"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                            <option value="daily">Daily</option>
                            <option value="weekly">Weekly</option>
                            <option value="monthly">Monthly</option>
                            <option value="quarterly">Quarterly</option>
                            <option value="annual">Annual</option>
                        </select>
                        @error('frequency') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                    </div>

                    <!-- Next Due Date -->
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Next Due Date *</label>
                        <input 
                            type="date" 
                            wire:model="procedure_next_due_date"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        @error('procedure_next_due_date') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                    </div>

                    <!-- Buttons -->
                    <div class="flex items-center gap-3 pt-4">
                        <button 
                            type="submit"
                            wire:loading.attr="disabled"
                            wire:loading.class="opacity-50 cursor-not-allowed"
                            class="flex-1 px-6 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg font-medium transition-colors">
                            <span wire:loading.remove wire:target="saveProcedure">Add Procedure</span>
                            <span wire:loading wire:target="saveProcedure">Saving...</span>
                        </button>
                        <button 
                            type="button"
                            wire:click="closeProcedureModal"
                            wire:loading.attr="disabled"
                            class="flex-1 px-6 py-2 bg-red-600 hover:bg-red-700 text-white rounded-lg font-medium transition-colors">
                            Cancel
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif

    <!-- View Details Modal -->
    @if($showDetailsModal && $selectedProcedureDetails)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4" wire:click="closeDetailsModal" style="background-color: rgba(0, 0, 0, 0.8); backdrop-filter: blur(8px); -webkit-backdrop-filter: blur(8px);">
            <div class="bg-white rounded-lg shadow-2xl w-full max-w-2xl" wire:click.stop>
                <div class="px-6 py-4 border-b border-gray-200">
                    <h3 class="text-xl font-semibold text-gray-900">Calibration Procedure Details</h3>
                </div>
                
                <div class="p-6">
                    <!-- Equipment Information -->
                    <div class="bg-blue-50 rounded-lg p-4 mb-6">
                        <h4 class="font-semibold text-blue-900 mb-3">Equipment Information</h4>
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <span class="text-sm font-medium text-blue-700">Name:</span>
                                <p class="text-blue-900 mt-1">{{ $selectedProcedureDetails->equipment_name }}</p>
                            </div>
                            <div>
                                <span class="text-sm font-medium text-blue-700">Model:</span>
                                <p class="text-blue-900 mt-1">{{ $selectedProcedureDetails->equipment_model ?? 'N/A' }}</p>
                            </div>
                        </div>
                    </div>

                    <!-- Procedure Information -->
                    <div class="space-y-4">
                        <h4 class="font-semibold text-gray-900 mb-3">Procedure Information</h4>
                        
                        <div>
                            <span class="text-sm font-medium text-gray-600">Procedure Name:</span>
                            <p class="text-gray-900 mt-1 text-lg">{{ $selectedProcedureDetails->procedure_name }}</p>
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <span class="text-sm font-medium text-gray-600">Standard Reference:</span>
                                <p class="text-gray-900 mt-1">{{ $selectedProcedureDetails->standard_reference ?? 'N/A' }}</p>
                            </div>
                            <div>
                                <span class="text-sm font-medium text-gray-600">Frequency:</span>
                                <p class="text-gray-900 mt-1">{{ strtoupper($selectedProcedureDetails->frequency) }}</p>
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <span class="text-sm font-medium text-gray-600">Next Due Date:</span>
                                <p class="text-gray-900 mt-1">{{ \Carbon\Carbon::parse($selectedProcedureDetails->next_due_date)->format('M d, Y') }}</p>
                            </div>
                            <div>
                                <span class="text-sm font-medium text-gray-600">Status:</span>
                                <p class="mt-1">
                                    @if($selectedProcedureDetails->is_active)
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                            Active
                                        </span>
                                    @else
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                            Inactive
                                        </span>
                                    @endif
                                </p>
                            </div>
                        </div>

                        <div>
                            <span class="text-sm font-medium text-gray-600">Date Added:</span>
                            <p class="text-gray-900 mt-1">{{ \Carbon\Carbon::parse($selectedProcedureDetails->datetime_added)->format('M d, Y h:i A') }}</p>
                        </div>
                    </div>

                    <!-- Close Button -->
                    <div class="mt-6 pt-4 border-t border-gray-200">
                        <button 
                            type="button"
                            wire:click="closeDetailsModal"
                            class="w-full px-6 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg font-medium transition-colors">
                            Close
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
