<?php

use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Validate;
use App\Models\CalibrationRecord;
use App\Models\Equipment;
use App\Models\Employee;
use App\Models\CertificateIssue;
use App\Services\CertificateService;
use App\Traits\LogsActivity;
use Illuminate\Support\Facades\Auth;

new class extends Component
{
    use WithPagination, LogsActivity;

    public $showModal = false;
    public $showProcedureModal = false;
    public $showDetailsModal = false;
    public $selectedEquipment = null;
    public $selectedProcedure = null;
    public $selectedProcedureDetails = null;
    public $detailsCalibrationHistory = [];

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
    public $frequency = 'monthly';

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

    public function computeNextDueDate($frequency, $fromDate = null)
    {
        $from = $fromDate ? \Carbon\Carbon::parse($fromDate) : \Carbon\Carbon::today();
        return match($frequency) {
            'daily'       => $from->addDay(),
            'weekly'      => $from->addWeek(),
            'monthly'     => $from->addMonth(),
            'quarterly'   => $from->addMonths(3),
            'semi-annual' => $from->addMonths(6),
            'annual'      => $from->addYear(),
            default       => $from->addMonth(),
        };
    }

    public function openModal($equipmentId, $procedureId)
    {
        $this->showModal = true;
        $this->selectedEquipment = Equipment::find($equipmentId);
        $this->selectedProcedure = DB::table('calibration_procedure')->where('procedure_id', $procedureId)->first();
        $this->calibration_date = date('Y-m-d');
        $this->reset(['notes']);
        $this->result_status = 'pass';

        // Auto-set performed_by to current logged-in user's employee
        $user = Auth::user();
        if ($user && $user->employee) {
            $this->performed_by = $user->employee->employee_id;
        } else {
            $this->performed_by = '';
        }

        // Auto-compute next calibration date from procedure frequency
        if ($this->selectedProcedure) {
            $this->next_calibration_date = $this->computeNextDueDate(
                $this->selectedProcedure->frequency,
                $this->calibration_date
            )->format('Y-m-d');
        } else {
            $this->next_calibration_date = '';
        }
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
        $this->frequency = 'monthly';
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
                'frequency' => 'required|in:monthly,quarterly,semi-annual,annual',
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

            $this->logActivity("Created calibration procedure: {$this->procedure_name} for equipment ID {$this->procedure_equipment_id}");
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
                'e.model as equipment_model',
                'e.serial_no as equipment_serial',
                'e.status as equipment_status'
            )
            ->first();

        // Load full calibration history for this procedure
        $this->detailsCalibrationHistory = DB::table('calibration_record as cr')
            ->join('employee as emp', 'cr.performed_by', '=', 'emp.employee_id')
            ->leftJoin('certificate_issues as ci', 'ci.calibration_id', '=', 'cr.record_id')
            ->where('cr.procedure_id', $procedureId)
            ->select(
                'cr.record_id',
                'cr.calibration_date',
                DB::raw("CONCAT(emp.firstname, ' ', emp.lastname) as performed_by_name"),
                'cr.result_status',
                'cr.notes',
                'cr.next_calibration_date',
                'ci.certificate_no',
                'ci.id as certificate_issue_id'
            )
            ->orderBy('cr.calibration_date', 'desc')
            ->get()
            ->toArray();
    }

    public function closeDetailsModal()
    {
        $this->showDetailsModal = false;
        $this->selectedProcedureDetails = null;
        $this->detailsCalibrationHistory = [];
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

            $procedureId = $this->selectedProcedure->procedure_id;
            $equipmentId = $this->selectedEquipment->equipment_id;

            // If next_calibration_date is still empty, auto-compute it
            if (empty($this->next_calibration_date) && $this->selectedProcedure) {
                $this->next_calibration_date = $this->computeNextDueDate(
                    $this->selectedProcedure->frequency,
                    $this->calibration_date
                )->format('Y-m-d');
            }

            $record = CalibrationRecord::create([
                'procedure_id' => $procedureId,
                'equipment_id' => $equipmentId,
                'calibration_date' => $this->calibration_date,
                'performed_by' => $this->performed_by,
                'result_status' => $this->result_status,
                'notes' => $this->notes,
                'next_calibration_date' => $this->next_calibration_date,
                'datetime_added' => now(),
            ]);

            // Update the procedure's next_due_date so alerts refresh correctly
            DB::table('calibration_procedure')
                ->where('procedure_id', $procedureId)
                ->update(['next_due_date' => $this->next_calibration_date]);

            $this->logActivity("Recorded calibration ({$this->result_status}) for equipment: {$this->selectedEquipment->name}");
            $this->flashMessage = 'Calibration recorded successfully! Next due date updated to ' . \Carbon\Carbon::parse($this->next_calibration_date)->format('M d, Y') . '.';
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
            ->leftJoin('certificate_issues as ci', 'ci.calibration_id', '=', 'cr.record_id')
            ->select(
                'cr.record_id',
                'cr.equipment_id',
                'cr.calibration_date',
                'e.name as equipment_name',
                'cp.procedure_name',
                DB::raw("CONCAT(emp.firstname, ' ', emp.lastname) as performed_by_name"),
                'cr.result_status',
                'cr.notes',
                'cr.next_calibration_date',
                'ci.certificate_no',
                'ci.id as certificate_issue_id'
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

<div class="p-6 space-y-6">
    <div class="flex items-center justify-between">
        <h1 class="text-2xl font-bold text-gray-900 flex items-center">
            <svg class="w-7 h-7 mr-2" style="color:#d2334c" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            Calibration Management
        </h1>
        <div class="flex gap-3">
            <button type="button" wire:click="openProcedureModal"
                class="px-4 py-2 rounded-md text-sm font-medium transition-colors bg-emerald-600 text-white hover:bg-emerald-700">
                Add Procedure
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

    <!-- Calibration Alerts Table -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-200">
        <div class="px-6 py-4 border-b border-gray-200 flex items-center gap-3">
            <div class="p-1.5 rounded-lg" style="background-color:#fef2f2">
                <svg class="w-5 h-5" style="color:#d2334c" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                </svg>
            </div>
            <h2 class="text-lg font-bold text-gray-900">Calibration Alerts</h2>
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
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Status</th>
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
                                    {{ $alert->days_until_due < 0 ? abs($alert->days_until_due) . ' overdue' : $alert->days_until_due . ' days' }}
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm">
                                @if($alert->days_until_due < 0)
                                    <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-semibold bg-red-100 text-red-700">
                                        <span class="w-1.5 h-1.5 rounded-full bg-red-500 inline-block"></span>
                                        Overdue
                                    </span>
                                @elseif($alert->days_until_due <= 14)
                                    <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-semibold bg-orange-100 text-orange-700">
                                        <span class="w-1.5 h-1.5 rounded-full bg-orange-500 inline-block"></span>
                                        Due Soon
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-semibold bg-green-100 text-green-700">
                                        <span class="w-1.5 h-1.5 rounded-full bg-green-500 inline-block"></span>
                                        Scheduled
                                    </span>
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm">
                                <button 
                                    wire:click="openModal({{ $alert->equipment_id }}, {{ $alert->procedure_id }})"
                                    class="inline-flex items-center gap-1.5 px-4 py-2 text-white rounded-lg font-medium transition-colors text-xs" style="background-color:#d2334c" onmouseover="this.style.backgroundColor='#9f1239'" onmouseout="this.style.backgroundColor='#d2334c'">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                                    Record Calibration
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-6 py-12 text-center">
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
    <div class="bg-white rounded-xl shadow-sm border border-gray-200">
        <div class="px-6 py-4 border-b border-gray-200 flex items-center justify-between">
            <div class="flex items-center gap-3">
                <div class="p-1.5 rounded-lg bg-emerald-50">
                    <svg class="w-5 h-5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                    </svg>
                </div>
                <h2 class="text-lg font-bold text-gray-900">Calibration Procedures</h2>
            </div>
            <button wire:click="openProcedureModal" class="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg font-medium transition-colors flex items-center gap-2">
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
                                <button wire:click="openDetailsModal({{ $procedure->procedure_id }})" class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg font-medium transition-colors">
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
    <div class="bg-white rounded-xl shadow-sm border border-gray-200">
        <div class="px-6 py-4 border-b border-gray-200 flex items-center gap-3">
            <div class="p-1.5 rounded-lg bg-indigo-50">
                <svg class="w-5 h-5 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"/>
                </svg>
            </div>
            <h2 class="text-lg font-bold text-gray-900">Recent Calibration Records</h2>
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
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Certificate</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @forelse($records as $record)
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                {{ \Carbon\Carbon::parse($record->calibration_date)->format('M d, Y') }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium" style="color:#d2334c">
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
                                    <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-semibold bg-emerald-100 text-emerald-700">
                                        <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                                        PASS
                                    </span>
                                @elseif($record->result_status === 'fail')
                                    <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-semibold bg-red-100 text-red-700">
                                        <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"/></svg>
                                        FAIL
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-semibold bg-amber-100 text-amber-700">
                                        <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/></svg>
                                        CONDITIONAL
                                    </span>
                                @endif
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-600 max-w-xs truncate" title="{{ $record->notes }}">
                                {{ \Illuminate\Support\Str::limit($record->notes, 35) ?? '' }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                @if($record->next_calibration_date)
                                    {{ \Carbon\Carbon::parse($record->next_calibration_date)->format('M d, Y') }}
                                @else
                                    <span class="text-gray-400">—</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm">
                                @if($record->certificate_no)
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold bg-blue-100 text-blue-700">
                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                        {{ $record->certificate_no }}
                                    </span>
                                @elseif($record->result_status === 'pass')
                                    <button wire:click="generateCertificate({{ $record->record_id }}, {{ $record->equipment_id }})"
                                        wire:loading.attr="disabled" wire:target="generateCertificate({{ $record->record_id }}, {{ $record->equipment_id }})"
                                        class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-semibold rounded-lg transition-colors">
                                        <svg wire:loading.remove wire:target="generateCertificate({{ $record->record_id }}, {{ $record->equipment_id }})" class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                        <svg wire:loading wire:target="generateCertificate({{ $record->record_id }}, {{ $record->equipment_id }})" class="w-3.5 h-3.5 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"/></svg>
                                        <span wire:loading.remove wire:target="generateCertificate({{ $record->record_id }}, {{ $record->equipment_id }})">Generate</span>
                                        <span wire:loading wire:target="generateCertificate({{ $record->record_id }}, {{ $record->equipment_id }})">...</span>
                                    </button>
                                @else
                                    <span class="text-xs text-gray-400 italic">Pass required</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-6 py-12 text-center">
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

    <!-- Record Calibration Modal -->
    @if($showModal)
        <div class="fixed inset-0 z-50 overflow-y-auto flex items-center justify-center p-4" style="background-color: rgba(15,23,42,0.75); backdrop-filter: blur(4px);">
            <div class="bg-white rounded-2xl shadow-2xl w-full max-w-2xl max-h-[90vh] flex flex-col" wire:click.stop>

                {{-- Modal Header — Crimson --}}
                <div class="flex items-center justify-between px-7 py-5 rounded-t-2xl" style="background: #d2334c;">
                    <div class="flex items-center gap-3">
                        <div class="bg-white/20 rounded-xl p-2.5">
                            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-lg font-bold text-white">Record Calibration</h3>
                            <p class="text-red-200 text-xs mt-0.5">Log a calibration event for the selected procedure</p>
                        </div>
                    </div>
                    <button wire:click="closeModal" class="text-white/70 hover:text-white transition-colors">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>

                <form wire:submit.prevent="save" class="p-7 overflow-y-auto flex-1 space-y-5">
                    @if (session()->has('error'))
                        <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg text-sm flex items-center gap-2">
                            <svg class="w-4 h-4 shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/></svg>
                            {{ session('error') }}
                        </div>
                    @endif

                    {{-- Equipment & Procedure Banner --}}
                    <div class="rounded-xl border p-4 flex gap-4" style="background-color:#fef2f2; border-color:#fecaca;">
                        <div class="flex-1">
                            <p class="text-xs font-semibold uppercase tracking-wider" style="color:#d2334c">Equipment</p>
                            <p class="text-sm font-bold text-gray-900 mt-0.5">{{ $selectedEquipment->name ?? '—' }}</p>
                        </div>
                        <div class="w-px" style="background-color:#fecaca"></div>
                        <div class="flex-1">
                            <p class="text-xs font-semibold uppercase tracking-wider" style="color:#d2334c">Procedure</p>
                            <p class="text-sm font-bold text-gray-900 mt-0.5">{{ $selectedProcedure->procedure_name ?? '—' }}</p>
                        </div>
                        @if($selectedProcedure && $selectedProcedure->standard_reference)
                        <div class="w-px" style="background-color:#fecaca"></div>
                        <div class="flex-1">
                            <p class="text-xs font-semibold uppercase tracking-wider" style="color:#d2334c">Standard</p>
                            <p class="text-sm font-bold text-gray-900 mt-0.5">{{ $selectedProcedure->standard_reference }}</p>
                        </div>
                        @endif
                    </div>

                    {{-- Date + Performed By --}}
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1.5">
                                <svg class="w-3.5 h-3.5 inline-block mr-1 -mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                Calibration Date *
                            </label>
                            <input type="date" wire:model="calibration_date"
                                class="w-full px-3.5 py-2.5 border border-gray-200 rounded-lg text-sm focus:ring-2 focus:border-transparent bg-gray-50 focus:bg-white transition" style="--tw-ring-color:#d2334c">
                            @error('calibration_date') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1.5">
                                <svg class="w-3.5 h-3.5 inline-block mr-1 -mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                                Performed By *
                            </label>
                            <select wire:model="performed_by"
                                class="w-full px-3.5 py-2.5 border border-gray-200 rounded-lg text-sm focus:ring-2 focus:border-transparent bg-gray-50 focus:bg-white transition" style="--tw-ring-color:#d2334c">
                                <option value="">Select Employee</option>
                                @foreach($employees as $employee)
                                    <option value="{{ $employee->employee_id }}">{{ $employee->full_name }}</option>
                                @endforeach
                            </select>
                            @error('performed_by') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                        </div>
                    </div>

                    {{-- Result Status — visual radio cards --}}
                    <div>
                        <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">Calibration Result *</label>
                        <div class="grid grid-cols-3 gap-3">
                            <label class="cursor-pointer">
                                <input type="radio" wire:model="result_status" value="pass" class="sr-only peer">
                                <div class="border-2 rounded-xl p-3 text-center transition-all peer-checked:border-emerald-500 peer-checked:bg-emerald-50 border-gray-200 hover:border-emerald-300">
                                    <svg class="w-7 h-7 mx-auto mb-1 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                    <span class="text-sm font-semibold text-emerald-700">Pass</span>
                                </div>
                            </label>
                            <label class="cursor-pointer">
                                <input type="radio" wire:model="result_status" value="conditional" class="sr-only peer">
                                <div class="border-2 rounded-xl p-3 text-center transition-all peer-checked:border-amber-500 peer-checked:bg-amber-50 border-gray-200 hover:border-amber-300">
                                    <svg class="w-7 h-7 mx-auto mb-1 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                                    <span class="text-sm font-semibold text-amber-700">Conditional</span>
                                </div>
                            </label>
                            <label class="cursor-pointer">
                                <input type="radio" wire:model="result_status" value="fail" class="sr-only peer">
                                <div class="border-2 rounded-xl p-3 text-center transition-all peer-checked:border-red-500 peer-checked:bg-red-50 border-gray-200 hover:border-red-300">
                                    <svg class="w-7 h-7 mx-auto mb-1 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                    <span class="text-sm font-semibold text-red-700">Fail</span>
                                </div>
                            </label>
                        </div>
                        @error('result_status') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                    </div>

                    {{-- Next Calibration Date --}}
                    <div>
                        <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1.5">
                            <svg class="w-3.5 h-3.5 inline-block mr-1 -mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                            Next Calibration Date
                        </label>
                        <input type="date" wire:model="next_calibration_date"
                            class="w-full px-3.5 py-2.5 border border-gray-200 rounded-lg text-sm focus:ring-2 focus:border-transparent bg-gray-50 focus:bg-white transition" style="--tw-ring-color:#d2334c">
                        @if($next_calibration_date)
                            <p class="text-xs mt-1 flex items-center gap-1" style="color:#d2334c">
                                <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/></svg>
                                Auto-calculated from procedure frequency. You may adjust if needed.
                            </p>
                        @endif
                        @error('next_calibration_date') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                    </div>

                    {{-- Notes --}}
                    <div>
                        <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1.5">
                            <svg class="w-3.5 h-3.5 inline-block mr-1 -mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                            Notes / Observations
                        </label>
                        <textarea wire:model="notes" rows="3" placeholder="Describe findings, deviations, or additional observations..."
                            class="w-full px-3.5 py-2.5 border border-gray-200 rounded-lg text-sm focus:ring-2 focus:border-transparent bg-gray-50 focus:bg-white transition resize-none" style="--tw-ring-color:#d2334c"></textarea>
                        @error('notes') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                    </div>

                    {{-- Action Buttons --}}
                    <div class="flex justify-end gap-3 pt-4 border-t border-gray-100">
                        <button type="button" wire:click="closeModal"
                            class="px-5 py-2.5 border border-gray-200 hover:bg-gray-50 text-gray-600 text-sm font-semibold rounded-xl transition-colors">
                            Cancel
                        </button>
                        <button type="submit"
                            wire:loading.attr="disabled" wire:loading.class="opacity-60 cursor-not-allowed"
                            class="inline-flex items-center justify-center gap-2 px-5 py-2.5 text-white text-sm font-semibold rounded-xl transition-colors" style="background-color:#d2334c" onmouseover="this.style.backgroundColor='#9f1239'" onmouseout="this.style.backgroundColor='#d2334c'">
                            <svg wire:loading.remove wire:target="save" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                            <svg wire:loading wire:target="save" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"/></svg>
                            <span wire:loading.remove wire:target="save">Save Calibration Record</span>
                            <span wire:loading wire:target="save">Saving...</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif

    <!-- Add New Procedure Modal -->
    @if($showProcedureModal)
        <div class="fixed inset-0 z-50 overflow-y-auto flex items-center justify-center p-4" style="background-color: rgba(0,0,0,0.5);">
            <div class="bg-white rounded-lg shadow-xl w-full max-w-2xl max-h-[90vh] flex flex-col" wire:click.stop>

                {{-- Modal Header --}}
                <div class="px-6 py-4 border-b border-gray-200">
                    <div class="flex items-center justify-between">
                        <h3 class="text-xl font-semibold text-gray-900">Add Calibration Procedure</h3>
                        <button wire:click="closeProcedureModal" class="text-gray-400 hover:text-gray-600">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        </button>
                    </div>
                </div>

                <form wire:submit.prevent="saveProcedure" class="overflow-y-auto flex-1">
                    <div class="p-6 space-y-5">
                    @if (session()->has('error'))
                        <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg text-sm flex items-center gap-2">
                            <svg class="w-4 h-4 shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/></svg>
                            {{ session('error') }}
                        </div>
                    @endif

                    {{-- Equipment --}}
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Select Equipment <span class="text-red-500">*</span></label>
                        <select wire:model="procedure_equipment_id"
                            class="w-full px-3 py-2.5 border border-gray-300 rounded-md focus:outline-none focus:ring-1 focus:ring-blue-500 focus:border-blue-500">
                            <option value="">— Choose equipment —</option>
                            @foreach($equipment as $equip)
                                <option value="{{ $equip->equipment_id }}">{{ $equip->name }}{{ $equip->model ? '  (' . $equip->model . ')' : '' }}</option>
                            @endforeach
                        </select>
                        @error('procedure_equipment_id') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                    </div>

                    {{-- Procedure Name --}}
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Procedure Name <span class="text-red-500">*</span></label>
                        <input type="text" wire:model="procedure_name" placeholder="e.g., General Calibration Check"
                            class="w-full px-3 py-2.5 border border-gray-300 rounded-md focus:outline-none focus:ring-1 focus:ring-blue-500 focus:border-blue-500">
                        @error('procedure_name') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        {{-- Standard Reference --}}
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Standard Reference</label>
                            <input type="text" wire:model="standard_reference" placeholder="e.g., ISO 17025, CLSI EP06"
                                class="w-full px-3 py-2.5 border border-gray-300 rounded-md focus:outline-none focus:ring-1 focus:ring-blue-500 focus:border-blue-500">
                            @error('standard_reference') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                        </div>
                        {{-- Frequency --}}
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Frequency <span class="text-red-500">*</span></label>
                            <select wire:model="frequency"
                                class="w-full px-3 py-2.5 border border-gray-300 rounded-md focus:outline-none focus:ring-1 focus:ring-blue-500 focus:border-blue-500">
                                <option value="monthly">Monthly</option>
                                <option value="quarterly">Quarterly</option>
                                <option value="semi-annual">Semi-Annual</option>
                                <option value="annual">Annual</option>
                            </select>
                            @error('frequency') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                        </div>
                    </div>

                    {{-- First Calibration Due Date --}}
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">First Calibration Due Date <span class="text-red-500">*</span></label>
                        <input type="date" wire:model="procedure_next_due_date"
                            class="w-full px-3 py-2.5 border border-gray-300 rounded-md focus:outline-none focus:ring-1 focus:ring-blue-500 focus:border-blue-500">
                        @error('procedure_next_due_date') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                    </div>
                    </div>

                    {{-- Footer --}}
                    <div class="px-6 py-4 bg-gray-50 border-t border-gray-200 flex justify-end space-x-3 rounded-b-lg">
                        <button type="button" wire:click="closeProcedureModal"
                            class="px-5 py-2.5 border border-gray-300 rounded-md text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">
                            Cancel
                        </button>
                        <button type="submit"
                            wire:loading.attr="disabled" wire:loading.class="opacity-60 cursor-not-allowed"
                            style="background-color: #DC143C;"
                            class="px-5 py-2.5 text-white text-sm rounded-md font-medium hover:opacity-90 transition-opacity">
                            <span wire:loading.remove wire:target="saveProcedure">Add Procedure</span>
                            <span wire:loading wire:target="saveProcedure">Saving...</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif

    <!-- View Details Modal -->
    @if($showDetailsModal && $selectedProcedureDetails)
        <div class="fixed inset-0 z-50 overflow-y-auto flex items-center justify-center p-4" style="background-color: rgba(15,23,42,0.75); backdrop-filter: blur(4px);">
            <div class="bg-white rounded-2xl shadow-2xl w-full max-w-4xl max-h-[90vh] flex flex-col" wire:click.stop>

                {{-- Modal Header — Indigo --}}
                <div class="flex items-center justify-between px-7 py-5 rounded-t-2xl" style="background: #4f46e5;">
                    <div class="flex items-center gap-3">
                        <div class="bg-white/20 rounded-xl p-2.5">
                            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-lg font-bold text-white">{{ $selectedProcedureDetails->procedure_name }}</h3>
                            <p class="text-indigo-200 text-xs mt-0.5">Procedure details &amp; calibration history</p>
                        </div>
                    </div>
                    <button wire:click="closeDetailsModal" class="text-white/70 hover:text-white transition-colors">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>

                <div class="p-7 overflow-y-auto flex-1 space-y-6">

                    {{-- Equipment + Procedure Info —  top strip --}}
                    <div class="grid grid-cols-2 gap-4">
                        <div class="rounded-xl border border-indigo-100 bg-indigo-50 p-5">
                            <h4 class="text-xs font-bold text-indigo-400 uppercase tracking-widest mb-3">Equipment</h4>
                            <div class="space-y-2">
                                <div class="flex justify-between">
                                    <span class="text-xs text-indigo-500">Name</span>
                                    <span class="text-sm font-semibold text-indigo-900">{{ $selectedProcedureDetails->equipment_name }}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-xs text-indigo-500">Model</span>
                                    <span class="text-sm text-indigo-900">{{ $selectedProcedureDetails->equipment_model ?? '—' }}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-xs text-indigo-500">Serial No.</span>
                                    <span class="text-sm text-indigo-900">{{ $selectedProcedureDetails->equipment_serial ?? '—' }}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-xs text-indigo-500">Status</span>
                                    <span class="text-sm font-semibold {{ ($selectedProcedureDetails->equipment_status ?? '') === 'Operational' ? 'text-emerald-700' : 'text-amber-700' }}">{{ $selectedProcedureDetails->equipment_status ?? '—' }}</span>
                                </div>
                            </div>
                        </div>
                        <div class="rounded-xl border border-gray-100 bg-gray-50 p-5">
                            <h4 class="text-xs font-bold text-gray-400 uppercase tracking-widest mb-3">Procedure</h4>
                            <div class="space-y-2">
                                <div class="flex justify-between">
                                    <span class="text-xs text-gray-500">Standard</span>
                                    <span class="text-sm text-gray-900">{{ $selectedProcedureDetails->standard_reference ?? '—' }}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-xs text-gray-500">Frequency</span>
                                    <span class="text-sm font-semibold text-gray-900">{{ strtoupper($selectedProcedureDetails->frequency) }}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-xs text-gray-500">Next Due Date</span>
                                    <span class="text-sm font-semibold text-gray-900">{{ \Carbon\Carbon::parse($selectedProcedureDetails->next_due_date)->format('M d, Y') }}</span>
                                </div>
                                @php
                                    $detailsDays = (int) now()->diffInDays(\Carbon\Carbon::parse($selectedProcedureDetails->next_due_date), false);
                                @endphp
                                <div class="flex justify-between items-center">
                                    <span class="text-xs text-gray-500">Calibration Status</span>
                                    @if($detailsDays < 0)
                                        <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-semibold bg-red-100 text-red-700">Overdue ({{ abs($detailsDays) }}d)</span>
                                    @elseif($detailsDays <= 14)
                                        <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-semibold bg-orange-100 text-orange-700">Due Soon ({{ $detailsDays }}d)</span>
                                    @else
                                        <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-semibold bg-green-100 text-green-700">Scheduled ({{ $detailsDays }}d)</span>
                                    @endif
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-xs text-gray-500">Active</span>
                                    <span class="text-sm">
                                        @if($selectedProcedureDetails->is_active)
                                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-700">Yes</span>
                                        @else
                                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-700">No</span>
                                        @endif
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Calibration History Table --}}
                    <div>
                        <h4 class="text-sm font-bold text-gray-700 mb-3 flex items-center gap-2">
                            <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            Calibration History ({{ count($detailsCalibrationHistory) }} records)
                        </h4>
                        @if(count($detailsCalibrationHistory) > 0)
                            <div class="overflow-x-auto rounded-xl border border-gray-200">
                                <table class="w-full text-sm">
                                    <thead class="bg-gray-50">
                                        <tr>
                                            <th class="px-4 py-2.5 text-left text-xs font-semibold text-gray-500 uppercase">#</th>
                                            <th class="px-4 py-2.5 text-left text-xs font-semibold text-gray-500 uppercase">Date</th>
                                            <th class="px-4 py-2.5 text-left text-xs font-semibold text-gray-500 uppercase">Performed By</th>
                                            <th class="px-4 py-2.5 text-left text-xs font-semibold text-gray-500 uppercase">Result</th>
                                            <th class="px-4 py-2.5 text-left text-xs font-semibold text-gray-500 uppercase">Notes</th>
                                            <th class="px-4 py-2.5 text-left text-xs font-semibold text-gray-500 uppercase">Next Due</th>
                                            <th class="px-4 py-2.5 text-left text-xs font-semibold text-gray-500 uppercase">Certificate</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-100">
                                        @foreach($detailsCalibrationHistory as $idx => $histEntry)
                                            @php $h = (object) $histEntry; @endphp
                                            <tr class="hover:bg-gray-50">
                                                <td class="px-4 py-2.5 text-gray-400">{{ $idx + 1 }}</td>
                                                <td class="px-4 py-2.5 text-gray-900 font-medium">{{ \Carbon\Carbon::parse($h->calibration_date)->format('M d, Y') }}</td>
                                                <td class="px-4 py-2.5 text-gray-600">{{ $h->performed_by_name }}</td>
                                                <td class="px-4 py-2.5">
                                                    @if($h->result_status === 'pass')
                                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold bg-emerald-100 text-emerald-700">PASS</span>
                                                    @elseif($h->result_status === 'fail')
                                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold bg-red-100 text-red-700">FAIL</span>
                                                    @else
                                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold bg-amber-100 text-amber-700">CONDITIONAL</span>
                                                    @endif
                                                </td>
                                                <td class="px-4 py-2.5 text-gray-600 max-w-xs truncate" title="{{ $h->notes }}">{{ \Illuminate\Support\Str::limit($h->notes, 30) ?? '—' }}</td>
                                                <td class="px-4 py-2.5 text-gray-600">{{ $h->next_calibration_date ? \Carbon\Carbon::parse($h->next_calibration_date)->format('M d, Y') : '—' }}</td>
                                                <td class="px-4 py-2.5">
                                                    @if($h->certificate_no)
                                                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-semibold bg-blue-100 text-blue-700">
                                                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4"/></svg>
                                                            {{ $h->certificate_no }}
                                                        </span>
                                                    @else
                                                        <span class="text-gray-400">—</span>
                                                    @endif
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @else
                            <div class="text-center py-8 bg-gray-50 rounded-xl">
                                <svg class="w-10 h-10 mx-auto mb-2 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                <p class="text-gray-400 text-sm">No calibration records yet for this procedure</p>
                            </div>
                        @endif
                    </div>

                    {{-- Footer --}}
                    <div class="flex items-center justify-between pt-4 border-t border-gray-100">
                        <p class="text-xs text-gray-400">Procedure added: {{ \Carbon\Carbon::parse($selectedProcedureDetails->datetime_added)->format('M d, Y h:i A') }}</p>
                        <button wire:click="closeDetailsModal"
                            class="px-5 py-2.5 border border-gray-200 hover:bg-gray-50 text-gray-600 text-sm font-semibold rounded-xl transition-colors">
                            Close
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
