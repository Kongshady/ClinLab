<?php

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\LabTestOrder;
use App\Models\OrderTest;
use App\Models\LabResult;
use App\Models\Patient;
use App\Models\Test;
use App\Models\Section;
use App\Models\Physician;
use App\Models\Employee;
use Carbon\Carbon;
use App\Traits\LogsActivity;

new class extends Component
{
    use WithPagination, LogsActivity;

    // Filters
    public $search = '';
    public $filterStatus = '';
    public $filterDate = '';
    public $perPage = 10;
    public $flashMessage = '';

    // Create Order Modal
    public bool $showCreateModal = false;
    public $orderPatientId = '';
    public $orderPhysicianId = '';
    public $orderRemarks = '';
    public $selectedTests = [];

    // View Order Detail
    public bool $showOrderDetail = false;
    public $viewingOrder = null;

    // Add Result Modal
    public bool $showResultModal = false;
    public $resultOrderTestId = '';
    public $resultValue = '';
    public $resultNormalRange = '';
    public $resultFindings = '';
    public $resultRemarks = '';
    public $resultPerformedBy = '';
    public $resultVerifiedBy = '';
    public $resultStatus = 'draft';
    public $resultDate = '';

    // Edit Result Modal
    public bool $showEditResultModal = false;
    public $editResultId = '';
    public $editResultValue = '';
    public $editResultNormalRange = '';
    public $editResultFindings = '';
    public $editResultRemarks = '';
    public $editResultPerformedBy = '';
    public $editResultVerifiedBy = '';
    public $editResultStatus = 'draft';
    public $editResultDate = '';

    public function mount()
    {
        if (session()->has('success')) {
            $this->flashMessage = session('success');
        }
        $this->resultDate = date('Y-m-d');
        $this->editResultDate = date('Y-m-d');
    }

    // Create Order
    public function openCreateModal()
    {
        $this->reset(['orderPatientId', 'orderPhysicianId', 'orderRemarks', 'selectedTests']);
        $this->showCreateModal = true;
    }

    public function closeCreateModal()
    {
        $this->showCreateModal = false;
        $this->reset(['orderPatientId', 'orderPhysicianId', 'orderRemarks', 'selectedTests']);
    }

    public function createOrder()
    {
        $this->validate([
            'orderPatientId' => 'required|exists:patient,patient_id',
            'orderPhysicianId' => 'nullable',
            'orderRemarks' => 'nullable|string|max:200',
            'selectedTests' => 'required|array|min:1',
            'selectedTests.*' => 'exists:test,test_id',
        ], [
            'selectedTests.required' => 'Please select at least one test.',
            'selectedTests.min' => 'Please select at least one test.',
        ]);

        $order = LabTestOrder::create([
            'patient_id' => $this->orderPatientId,
            'physician_id' => $this->orderPhysicianId ?: null,
            'order_date' => now(),
            'status' => 'pending',
            'remarks' => $this->orderRemarks,
        ]);

        foreach ($this->selectedTests as $testId) {
            OrderTest::create([
                'order_id' => $order->lab_test_order_id,
                'test_id' => $testId,
                'status' => 'pending',
                'datetime_added' => now(),
            ]);
        }

        $this->logActivity("Created lab test order for patient ID {$this->orderPatientId}");
        $this->flashMessage = 'Test order created successfully!';
        $this->closeCreateModal();
        $this->resetPage();
    }

    // View Order Detail
    public function viewOrder($orderId)
    {
        $this->viewingOrder = LabTestOrder::with([
            'patient', 'physician',
            'orderTests.test.section',
            'orderTests.labResult.performedBy',
            'orderTests.labResult.verifiedBy',
            'orderTests.assignedTo'
        ])->find($orderId);
        $this->showOrderDetail = true;
    }

    public function closeOrderDetail()
    {
        $this->showOrderDetail = false;
        $this->viewingOrder = null;
    }

    // Cancel Order
    public function cancelOrder($orderId)
    {
        $order = LabTestOrder::findOrFail($orderId);
        $order->update(['status' => 'cancelled']);
        $order->orderTests()->where('status', '!=', 'completed')->update(['status' => 'cancelled']);
        $this->logActivity("Cancelled lab test order ID {$orderId}");
        $this->flashMessage = 'Order cancelled successfully.';
        
        if ($this->showOrderDetail && $this->viewingOrder && $this->viewingOrder->lab_test_order_id == $orderId) {
            $this->viewOrder($orderId);
        }
    }

    // Add Result
    public function openResultModal($orderTestId)
    {
        $this->reset(['resultValue', 'resultNormalRange', 'resultFindings', 'resultRemarks', 'resultPerformedBy', 'resultVerifiedBy']);
        $this->resultOrderTestId = $orderTestId;
        $this->resultDate = date('Y-m-d');
        $this->resultStatus = 'draft';
        // Auto-set performed by to current logged-in user's employee
        $employee = auth()->user()?->employee;
        if ($employee) {
            $this->resultPerformedBy = $employee->employee_id;
        }
        $this->showResultModal = true;
    }

    public function closeResultModal()
    {
        $this->showResultModal = false;
        $this->reset(['resultOrderTestId', 'resultValue', 'resultNormalRange', 'resultFindings', 'resultRemarks', 'resultPerformedBy', 'resultVerifiedBy']);
    }

    public function saveResult()
    {
        $this->validate([
            'resultValue' => 'nullable|string|max:100',
            'resultNormalRange' => 'nullable|string|max:100',
            'resultFindings' => 'nullable|string',
            'resultRemarks' => 'nullable|string',
            'resultPerformedBy' => 'nullable|exists:employee,employee_id',
            'resultVerifiedBy' => 'nullable|exists:employee,employee_id',
            'resultStatus' => 'required|in:draft,final,revised',
            'resultDate' => 'required|date',
        ]);

        $orderTest = OrderTest::with('order')->findOrFail($this->resultOrderTestId);

        LabResult::create([
            'order_test_id' => $orderTest->order_test_id,
            'lab_test_order_id' => $orderTest->order_id,
            'patient_id' => $orderTest->order->patient_id,
            'test_id' => $orderTest->test_id,
            'result_date' => $this->resultDate,
            'result_value' => $this->resultValue,
            'normal_range' => $this->resultNormalRange,
            'findings' => $this->resultFindings,
            'remarks' => $this->resultRemarks,
            'performed_by' => $this->resultPerformedBy ?: null,
            'verified_by' => $this->resultVerifiedBy ?: null,
            'status' => $this->resultStatus,
            'datetime_added' => now(),
        ]);

        // Update order_test status
        $orderTest->update(['status' => 'completed']);

        // Auto-update order status
        $orderTest->order->updateStatusFromTests();

        $this->logActivity("Added lab result for order test ID {$this->resultOrderTestId}");
        $this->flashMessage = 'Result added successfully!';
        $this->closeResultModal();

        // Refresh the order detail
        if ($this->viewingOrder) {
            $this->viewOrder($this->viewingOrder->lab_test_order_id);
        }
    }

    // Edit Result
    public function openEditResultModal($resultId)
    {
        $result = LabResult::findOrFail($resultId);
        $this->editResultId = $result->lab_result_id;
        $this->editResultValue = $result->result_value;
        $this->editResultNormalRange = $result->normal_range;
        $this->editResultFindings = $result->findings;
        $this->editResultRemarks = $result->remarks;
        $this->editResultPerformedBy = $result->performed_by;
        $this->editResultVerifiedBy = $result->verified_by;
        $this->editResultStatus = $result->status;
        $this->editResultDate = $result->result_date ? Carbon::parse($result->result_date)->format('Y-m-d') : date('Y-m-d');
        $this->showEditResultModal = true;
    }

    public function closeEditResultModal()
    {
        $this->showEditResultModal = false;
        $this->reset(['editResultId', 'editResultValue', 'editResultNormalRange', 'editResultFindings', 'editResultRemarks', 'editResultPerformedBy', 'editResultVerifiedBy', 'editResultStatus', 'editResultDate']);
    }

    public function updateResult()
    {
        $this->validate([
            'editResultValue' => 'nullable|string|max:100',
            'editResultNormalRange' => 'nullable|string|max:100',
            'editResultFindings' => 'nullable|string',
            'editResultRemarks' => 'nullable|string',
            'editResultPerformedBy' => 'nullable|exists:employee,employee_id',
            'editResultVerifiedBy' => 'nullable|exists:employee,employee_id',
            'editResultStatus' => 'required|in:draft,final,revised',
            'editResultDate' => 'required|date',
        ]);

        $result = LabResult::findOrFail($this->editResultId);
        $result->update([
            'result_value' => $this->editResultValue,
            'normal_range' => $this->editResultNormalRange,
            'findings' => $this->editResultFindings,
            'remarks' => $this->editResultRemarks,
            'performed_by' => $this->editResultPerformedBy ?: null,
            'verified_by' => $this->editResultVerifiedBy ?: null,
            'status' => $this->editResultStatus,
            'result_date' => $this->editResultDate,
            'datetime_modified' => now(),
        ]);

        $this->logActivity("Updated lab result ID {$this->editResultId}");
        $this->flashMessage = 'Result updated successfully!';
        $this->closeEditResultModal();

        if ($this->viewingOrder) {
            $this->viewOrder($this->viewingOrder->lab_test_order_id);
        }
    }

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function updatingFilterStatus()
    {
        $this->resetPage();
    }

    public function with(): array
    {
        $query = LabTestOrder::with(['patient', 'physician', 'orderTests.test', 'orderTests.labResult'])
            ->when($this->search, function ($query) {
                $query->whereHas('patient', function($q) {
                    $q->where('firstname', 'like', '%' . $this->search . '%')
                      ->orWhere('lastname', 'like', '%' . $this->search . '%');
                });
            })
            ->when($this->filterStatus, function ($query) {
                $query->where('status', $this->filterStatus);
            })
            ->when($this->filterDate, function ($query) {
                $query->whereDate('order_date', $this->filterDate);
            })
            ->orderBy('order_date', 'desc');

        // Stats
        $todayStart = Carbon::today();
        $pendingCount = LabTestOrder::where('status', 'pending')->count();
        $todayCount = LabTestOrder::whereDate('order_date', $todayStart)->count();
        $completedTodayCount = LabTestOrder::where('status', 'completed')
            ->whereDate('order_date', $todayStart)->count();

        // Tests grouped by section for create modal
        $sections = Section::active()->with(['tests' => function($q) {
            $q->active()->orderBy('label');
        }])->orderBy('label')->get();

        return [
            'orders' => $this->perPage === 'all' ? $query->get() : $query->paginate((int)$this->perPage),
            'patients' => Patient::active()->orderBy('lastname')->get(),
            'physicians' => Physician::active()->orderBy('physician_name')->get(),
            'employees' => Employee::active()->orderBy('lastname')->get(),
            'sections' => $sections,
            'pendingCount' => $pendingCount,
            'todayCount' => $todayCount,
            'completedTodayCount' => $completedTodayCount,
        ];
    }
};
?>

<div class="p-6" x-data="{ testSearch: '' }">
    {{-- Header --}}
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-gray-900 flex items-center">
            <svg class="w-7 h-7 mr-2 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/>
            </svg>
            Lab Test Orders & Results
        </h1>
    </div>

    {{-- Flash Message --}}
    @if($flashMessage)
        <div class="mb-6 p-4 bg-green-50 border-l-4 border-green-500 rounded" x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 4000)">
            <div class="flex items-center justify-between">
                <p class="text-green-800 font-medium">{{ $flashMessage }}</p>
                <button @click="show = false" class="text-green-600 hover:text-green-800">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>
        </div>
    @endif

    {{-- Stats Cards --}}
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-5">
            <div class="flex items-center">
                <div class="flex-shrink-0 p-3 bg-yellow-100 rounded-lg">
                    <svg class="w-6 h-6 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-500">Pending Orders</p>
                    <p class="text-2xl font-bold text-gray-900">{{ $pendingCount }}</p>
                </div>
            </div>
        </div>
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-5">
            <div class="flex items-center">
                <div class="flex-shrink-0 p-3 bg-blue-100 rounded-lg">
                    <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                    </svg>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-500">Today's Orders</p>
                    <p class="text-2xl font-bold text-gray-900">{{ $todayCount }}</p>
                </div>
            </div>
        </div>
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-5">
            <div class="flex items-center">
                <div class="flex-shrink-0 p-3 bg-green-100 rounded-lg">
                    <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-500">Completed Today</p>
                    <p class="text-2xl font-bold text-gray-900">{{ $completedTodayCount }}</p>
                </div>
            </div>
        </div>
    </div>

    {{-- Orders List Card --}}
    <div class="bg-white rounded-lg shadow-sm">
        <div class="px-6 py-4 border-b border-gray-200 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
            <h2 class="text-lg font-semibold text-gray-900">Test Orders</h2>
            <button wire:click="openCreateModal" 
                    class="px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700 transition-colors flex items-center gap-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                Create Test Order
            </button>
        </div>

        {{-- Filters --}}
        <div class="p-6 border-b border-gray-100">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Search Patient</label>
                    <input type="text" wire:model.live.debounce.300ms="search" placeholder="Search by patient name..." 
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent text-sm">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                    <select wire:model.live="filterStatus" 
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 text-sm">
                        <option value="">All Status</option>
                        <option value="pending">Pending</option>
                        <option value="completed">Completed</option>
                        <option value="cancelled">Cancelled</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Order Date</label>
                    <input type="date" wire:model.live="filterDate" 
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 text-sm">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Rows per page</label>
                    <select wire:model.live="perPage" 
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 text-sm">
                        <option value="10">10</option>
                        <option value="25">25</option>
                        <option value="50">50</option>
                        <option value="all">All</option>
                    </select>
                </div>
            </div>
        </div>

        {{-- Orders Table --}}
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Order #</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Patient</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Physician</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Order Date</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Tests</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Status</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($orders as $order)
                        @php
                            $totalTests = $order->orderTests->count();
                            $completedTests = $order->orderTests->where('status', 'completed')->count();
                        @endphp
                        <tr wire:key="order-{{ $order->lab_test_order_id }}" class="hover:bg-gray-50 transition-colors">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="text-sm font-semibold text-blue-600">#{{ $order->lab_test_order_id }}</span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm font-medium text-gray-900">{{ $order->patient->full_name ?? 'N/A' }}</div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm text-gray-600">{{ $order->physician->physician_name ?? '—' }}</div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm text-gray-600">
                                    {{ $order->order_date ? $order->order_date->format('M d, Y h:i A') : 'N/A' }}
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex items-center gap-1.5">
                                    <span class="text-sm font-medium text-gray-900">{{ $completedTests }}/{{ $totalTests }}</span>
                                    <div class="w-16 h-1.5 bg-gray-200 rounded-full overflow-hidden">
                                        <div class="h-full rounded-full {{ $completedTests >= $totalTests && $totalTests > 0 ? 'bg-green-500' : 'bg-blue-500' }}" 
                                             style="width: {{ $totalTests > 0 ? ($completedTests/$totalTests*100) : 0 }}%"></div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="px-2.5 py-1 inline-flex text-xs leading-5 font-semibold rounded-full 
                                    @if($order->status === 'completed') bg-green-100 text-green-800
                                    @elseif($order->status === 'cancelled') bg-red-100 text-red-800
                                    @else bg-yellow-100 text-yellow-800
                                    @endif">
                                    {{ ucfirst($order->status) }}
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm space-x-1.5">
                                <button wire:click="viewOrder({{ $order->lab_test_order_id }})" 
                                        class="px-3 py-1.5 bg-blue-500 hover:bg-blue-600 text-white text-xs font-medium rounded-lg transition-colors">
                                    View
                                </button>
                                @if($order->status === 'pending')
                                    <button wire:click="cancelOrder({{ $order->lab_test_order_id }})" 
                                            wire:confirm="Are you sure you want to cancel this order?"
                                            class="px-3 py-1.5 bg-red-500 hover:bg-red-600 text-white text-xs font-medium rounded-lg transition-colors">
                                        Cancel
                                    </button>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-6 py-12 text-center">
                                <svg class="w-16 h-16 mx-auto mb-4 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                </svg>
                                <p class="text-gray-500 font-medium">No test orders found</p>
                                <p class="text-gray-400 text-sm mt-1">Create a new test order to get started.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($perPage !== 'all' && method_exists($orders, 'hasPages') && $orders->hasPages())
            <div class="px-6 py-4 border-t border-gray-200">
                {{ $orders->links() }}
            </div>
        @endif
    </div>

    {{-- ==================== CREATE ORDER MODAL ==================== --}}
    @if($showCreateModal)
    <div class="fixed inset-0 z-50 overflow-y-auto" style="background-color: rgba(0, 0, 0, 0.5);">
        <div class="flex items-center justify-center min-h-screen p-4">
            <div class="bg-white rounded-xl shadow-2xl max-w-3xl w-full max-h-[90vh] flex flex-col">
                {{-- Modal Header --}}
                <div class="px-6 py-4 border-b border-gray-200 flex items-center justify-between flex-shrink-0">
                    <div>
                        <h3 class="text-xl font-bold text-gray-900">Create Test Order</h3>
                        <p class="text-sm text-gray-500 mt-0.5">Select a patient and the tests to order</p>
                    </div>
                    <button wire:click="closeCreateModal" class="text-gray-400 hover:text-gray-600 p-1">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>

                {{-- Modal Body --}}
                <form wire:submit.prevent="createOrder" class="flex flex-col flex-1 overflow-hidden">
                    <div class="p-6 overflow-y-auto flex-1 space-y-5">
                        {{-- Patient & Physician --}}
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1.5">Patient <span class="text-red-500">*</span></label>
                                <select wire:model="orderPatientId" 
                                        class="w-full px-3 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent text-sm">
                                    <option value="">Select Patient</option>
                                    @foreach($patients as $patient)
                                        <option value="{{ $patient->patient_id }}">{{ $patient->full_name }}</option>
                                    @endforeach
                                </select>
                                @error('orderPatientId') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1.5">Physician</label>
                                <select wire:model="orderPhysicianId" 
                                        class="w-full px-3 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent text-sm">
                                    <option value="">Select Physician (Optional)</option>
                                    @foreach($physicians as $physician)
                                        <option value="{{ $physician->physician_id }}">{{ $physician->physician_name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        {{-- Remarks --}}
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1.5">Remarks</label>
                            <textarea wire:model="orderRemarks" rows="2" maxlength="200" placeholder="Optional remarks..."
                                      class="w-full px-3 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent text-sm"></textarea>
                        </div>

                        {{-- Test Selection --}}
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1.5">
                                Select Tests <span class="text-red-500">*</span>
                                @if(count($selectedTests) > 0)
                                    <span class="ml-2 text-blue-600 font-normal">({{ count($selectedTests) }} selected)</span>
                                @endif
                            </label>
                            @error('selectedTests') <span class="text-red-500 text-xs mb-2 block">{{ $message }}</span> @enderror

                            {{-- Test Search --}}
                            <div class="mb-3">
                                <input type="text" x-model="testSearch" placeholder="Search tests..."
                                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent text-sm">
                            </div>

                            <div class="border border-gray-200 rounded-lg max-h-64 overflow-y-auto">
                                @foreach($sections as $section)
                                    @if($section->tests->count() > 0)
                                        <div x-data="{ sectionLabel: '{{ strtolower(addslashes($section->label)) }}', testLabels: {{ json_encode($section->tests->pluck('label')->map(fn($l) => strtolower($l))->toArray()) }} }"
                                             x-show="!testSearch || sectionLabel.includes(testSearch.toLowerCase()) || testLabels.some(t => t.includes(testSearch.toLowerCase()))">
                                            <div class="px-4 py-2 bg-gray-50 border-b border-gray-200 sticky top-0">
                                                <h4 class="text-xs font-bold text-gray-600 uppercase tracking-wider">{{ $section->label }}</h4>
                                            </div>
                                            @foreach($section->tests as $test)
                                                <label x-show="!testSearch || '{{ strtolower(addslashes($test->label)) }}'.includes(testSearch.toLowerCase()) || sectionLabel.includes(testSearch.toLowerCase())"
                                                       class="flex items-center px-4 py-2.5 hover:bg-blue-50 cursor-pointer border-b border-gray-100 last:border-0 transition-colors">
                                                    <input type="checkbox" wire:model="selectedTests" value="{{ $test->test_id }}" 
                                                           class="rounded border-gray-300 text-blue-600 focus:ring-blue-500 mr-3">
                                                    <div class="flex-1">
                                                        <span class="text-sm text-gray-800">{{ $test->label }}</span>
                                                    </div>
                                                    @if($test->current_price)
                                                        <span class="text-xs text-gray-400 ml-2">&#8369;{{ number_format($test->current_price, 2) }}</span>
                                                    @endif
                                                </label>
                                            @endforeach
                                        </div>
                                    @endif
                                @endforeach
                            </div>
                        </div>
                    </div>

                    {{-- Modal Footer --}}
                    <div class="px-6 py-4 bg-gray-50 border-t border-gray-200 flex justify-end space-x-3 flex-shrink-0 rounded-b-xl">
                        <button type="button" wire:click="closeCreateModal" 
                                class="px-5 py-2.5 border border-gray-300 rounded-lg text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">
                            Cancel
                        </button>
                        <button type="submit" 
                                class="px-5 py-2.5 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700 transition-colors">
                            Create Order
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    @endif

    {{-- ==================== ORDER DETAIL MODAL ==================== --}}
    @if($showOrderDetail && $viewingOrder)
    <div class="fixed inset-0 z-50 overflow-y-auto" style="background-color: rgba(0, 0, 0, 0.5);">
        <div class="flex items-center justify-center min-h-screen p-4">
            <div class="bg-white rounded-xl shadow-2xl max-w-4xl w-full max-h-[90vh] flex flex-col">
                {{-- Header --}}
                <div class="px-6 py-4 border-b border-gray-200 flex items-center justify-between flex-shrink-0">
                    <div>
                        <h3 class="text-xl font-bold text-gray-900">Order #{{ $viewingOrder->lab_test_order_id }}</h3>
                        <p class="text-sm text-gray-500 mt-0.5">
                            {{ $viewingOrder->order_date ? $viewingOrder->order_date->format('F d, Y h:i A') : '' }}
                        </p>
                    </div>
                    <div class="flex items-center gap-3">
                        <span class="px-3 py-1 text-sm font-semibold rounded-full 
                            @if($viewingOrder->status === 'completed') bg-green-100 text-green-800
                            @elseif($viewingOrder->status === 'cancelled') bg-red-100 text-red-800
                            @else bg-yellow-100 text-yellow-800
                            @endif">
                            {{ ucfirst($viewingOrder->status) }}
                        </span>
                        <button wire:click="closeOrderDetail" class="text-gray-400 hover:text-gray-600 p-1">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </button>
                    </div>
                </div>

                {{-- Body --}}
                <div class="overflow-y-auto flex-1 p-6">
                    {{-- Order Info --}}
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
                        <div class="bg-gray-50 rounded-lg p-4">
                            <p class="text-xs font-medium text-gray-500 uppercase mb-1">Patient</p>
                            <p class="text-sm font-semibold text-gray-900">{{ $viewingOrder->patient->full_name ?? 'N/A' }}</p>
                        </div>
                        <div class="bg-gray-50 rounded-lg p-4">
                            <p class="text-xs font-medium text-gray-500 uppercase mb-1">Physician</p>
                            <p class="text-sm font-semibold text-gray-900">{{ $viewingOrder->physician->physician_name ?? '—' }}</p>
                        </div>
                        <div class="bg-gray-50 rounded-lg p-4">
                            <p class="text-xs font-medium text-gray-500 uppercase mb-1">Remarks</p>
                            <p class="text-sm text-gray-700">{{ $viewingOrder->remarks ?: '—' }}</p>
                        </div>
                    </div>

                    {{-- Progress --}}
                    @php
                        $totalTests = $viewingOrder->orderTests->count();
                        $completedTests = $viewingOrder->orderTests->where('status', 'completed')->count();
                        $progressPct = $totalTests > 0 ? round($completedTests / $totalTests * 100) : 0;
                    @endphp
                    <div class="mb-6">
                        <div class="flex items-center justify-between mb-2">
                            <span class="text-sm font-medium text-gray-700">Test Progress</span>
                            <span class="text-sm font-semibold text-gray-900">{{ $completedTests }}/{{ $totalTests }} completed</span>
                        </div>
                        <div class="w-full h-2.5 bg-gray-200 rounded-full overflow-hidden">
                            <div class="h-full rounded-full transition-all duration-300 {{ $progressPct >= 100 ? 'bg-green-500' : 'bg-blue-500' }}" 
                                 style="width: {{ $progressPct }}%"></div>
                        </div>
                    </div>

                    {{-- Ordered Tests --}}
                    <h4 class="text-sm font-bold text-gray-700 uppercase tracking-wider mb-3">Ordered Tests</h4>
                    <div class="space-y-3">
                        @foreach($viewingOrder->orderTests as $orderTest)
                            <div class="border border-gray-200 rounded-lg overflow-hidden">
                                {{-- Test Header --}}
                                <div class="px-4 py-3 flex items-center justify-between bg-gray-50">
                                    <div class="flex items-center gap-3">
                                        <div class="w-2 h-2 rounded-full 
                                            @if($orderTest->status === 'completed') bg-green-500
                                            @elseif($orderTest->status === 'in_progress') bg-blue-500
                                            @elseif($orderTest->status === 'cancelled') bg-red-500
                                            @else bg-yellow-500
                                            @endif"></div>
                                        <div>
                                            <span class="text-sm font-semibold text-gray-900">{{ $orderTest->test->label ?? 'Unknown Test' }}</span>
                                            @if($orderTest->test && $orderTest->test->section)
                                                <span class="text-xs text-gray-400 ml-2">{{ $orderTest->test->section->label }}</span>
                                            @endif
                                        </div>
                                    </div>
                                    <div class="flex items-center gap-2">
                                        <span class="px-2 py-0.5 text-xs font-medium rounded-full 
                                            @if($orderTest->status === 'completed') bg-green-100 text-green-700
                                            @elseif($orderTest->status === 'in_progress') bg-blue-100 text-blue-700
                                            @elseif($orderTest->status === 'cancelled') bg-red-100 text-red-700
                                            @else bg-yellow-100 text-yellow-700
                                            @endif">
                                            {{ str_replace('_', ' ', ucfirst($orderTest->status)) }}
                                        </span>
                                        @if($orderTest->status !== 'completed' && $orderTest->status !== 'cancelled' && !$orderTest->labResult)
                                            <button wire:click="openResultModal({{ $orderTest->order_test_id }})" 
                                                    class="px-3 py-1 bg-blue-500 hover:bg-blue-600 text-white text-xs font-medium rounded-lg transition-colors">
                                                Add Result
                                            </button>
                                        @endif
                                    </div>
                                </div>

                                {{-- Result Details (if exists) --}}
                                @if($orderTest->labResult)
                                    <div class="px-4 py-3 border-t border-gray-100 bg-white">
                                        <div class="grid grid-cols-2 md:grid-cols-4 gap-3 text-sm">
                                            <div>
                                                <p class="text-xs text-gray-500 font-medium">Result Value</p>
                                                <p class="text-gray-900 font-semibold">{{ $orderTest->labResult->result_value ?: '—' }}</p>
                                            </div>
                                            <div>
                                                <p class="text-xs text-gray-500 font-medium">Normal Range</p>
                                                <p class="text-gray-700">{{ $orderTest->labResult->normal_range ?: '—' }}</p>
                                            </div>
                                            <div>
                                                <p class="text-xs text-gray-500 font-medium">Result Date</p>
                                                <p class="text-gray-700">{{ $orderTest->labResult->result_date ? Carbon::parse($orderTest->labResult->result_date)->format('M d, Y') : '—' }}</p>
                                            </div>
                                            <div>
                                                <p class="text-xs text-gray-500 font-medium">Status</p>
                                                <span class="px-2 py-0.5 text-xs font-medium rounded-full {{ $orderTest->labResult->status_badge_class }}">
                                                    {{ ucfirst($orderTest->labResult->status) }}
                                                </span>
                                            </div>
                                        </div>
                                        @if($orderTest->labResult->findings)
                                            <div class="mt-2">
                                                <p class="text-xs text-gray-500 font-medium">Findings</p>
                                                <p class="text-sm text-gray-700">{{ $orderTest->labResult->findings }}</p>
                                            </div>
                                        @endif
                                        @if($orderTest->labResult->remarks)
                                            <div class="mt-2">
                                                <p class="text-xs text-gray-500 font-medium">Remarks</p>
                                                <p class="text-sm text-gray-700">{{ $orderTest->labResult->remarks }}</p>
                                            </div>
                                        @endif
                                        <div class="mt-2 flex items-center justify-between">
                                            <div class="flex gap-4 text-xs text-gray-500">
                                                @if($orderTest->labResult->performedBy)
                                                    <span>Performed by: <span class="font-medium text-gray-700">{{ $orderTest->labResult->performedBy->full_name }}</span></span>
                                                @endif
                                                @if($orderTest->labResult->verifiedBy)
                                                    <span>Verified by: <span class="font-medium text-gray-700">{{ $orderTest->labResult->verifiedBy->full_name }}</span></span>
                                                @endif
                                            </div>
                                            <button wire:click="openEditResultModal({{ $orderTest->labResult->lab_result_id }})" 
                                                    class="px-3 py-1 bg-orange-500 hover:bg-orange-600 text-white text-xs font-medium rounded-lg transition-colors">
                                                Edit Result
                                            </button>
                                        </div>
                                    </div>
                                @endif
                            </div>
                        @endforeach
                    </div>
                </div>

                {{-- Footer --}}
                <div class="px-6 py-4 bg-gray-50 border-t border-gray-200 flex justify-end rounded-b-xl flex-shrink-0">
                    <button wire:click="closeOrderDetail" 
                            class="px-5 py-2.5 border border-gray-300 rounded-lg text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">
                        Close
                    </button>
                </div>
            </div>
        </div>
    </div>
    @endif

    {{-- ==================== ADD RESULT MODAL ==================== --}}
    @if($showResultModal)
    <div class="fixed inset-0 z-[60] overflow-y-auto" style="background-color: rgba(0, 0, 0, 0.5);">
        <div class="flex items-center justify-center min-h-screen p-4">
            <div class="bg-white rounded-xl shadow-2xl max-w-2xl w-full">
                <div class="px-6 py-4 border-b border-gray-200 flex items-center justify-between">
                    <h3 class="text-lg font-bold text-gray-900">Add Test Result</h3>
                    <button wire:click="closeResultModal" class="text-gray-400 hover:text-gray-600">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>

                <form wire:submit.prevent="saveResult">
                    <div class="p-6 space-y-4">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Result Value</label>
                                <input type="text" wire:model="resultValue" placeholder="e.g., 120 mg/dL"
                                       class="w-full px-3 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent text-sm">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Normal Range</label>
                                <input type="text" wire:model="resultNormalRange" placeholder="e.g., 70-110 mg/dL"
                                       class="w-full px-3 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent text-sm">
                            </div>
                        </div>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Result Date <span class="text-red-500">*</span></label>
                                <input type="date" wire:model="resultDate"
                                       class="w-full px-3 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent text-sm">
                                @error('resultDate') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Status <span class="text-red-500">*</span></label>
                                <select wire:model="resultStatus"
                                        class="w-full px-3 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent text-sm">
                                    <option value="draft">Draft</option>
                                    <option value="final">Final</option>
                                    <option value="revised">Revised</option>
                                </select>
                            </div>
                        </div>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Performed By</label>
                                <input type="text" value="{{ auth()->user()?->employee ? auth()->user()->employee->full_name : 'N/A' }}" readonly
                                       class="w-full px-3 py-2.5 border border-gray-200 rounded-lg bg-gray-50 text-sm text-gray-700 cursor-not-allowed">
                                <input type="hidden" wire:model="resultPerformedBy">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Verified By</label>
                                <select wire:model="resultVerifiedBy"
                                        class="w-full px-3 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent text-sm">
                                    <option value="">Select Employee</option>
                                    @foreach($employees as $employee)
                                        <option value="{{ $employee->employee_id }}">{{ $employee->full_name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Findings</label>
                            <textarea wire:model="resultFindings" rows="2" placeholder="Enter clinical findings..."
                                      class="w-full px-3 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent text-sm"></textarea>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Remarks</label>
                            <textarea wire:model="resultRemarks" rows="2" placeholder="Additional remarks..."
                                      class="w-full px-3 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent text-sm"></textarea>
                        </div>
                    </div>

                    <div class="px-6 py-4 bg-gray-50 border-t border-gray-200 flex justify-end space-x-3 rounded-b-xl">
                        <button type="button" wire:click="closeResultModal"
                                class="px-5 py-2.5 border border-gray-300 rounded-lg text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">
                            Cancel
                        </button>
                        <button type="submit"
                                class="px-5 py-2.5 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700 transition-colors">
                            Save Result
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    @endif

    {{-- ==================== EDIT RESULT MODAL ==================== --}}
    @if($showEditResultModal)
    <div class="fixed inset-0 z-[60] overflow-y-auto" style="background-color: rgba(0, 0, 0, 0.5);">
        <div class="flex items-center justify-center min-h-screen p-4">
            <div class="bg-white rounded-xl shadow-2xl max-w-2xl w-full">
                <div class="px-6 py-4 border-b border-gray-200 flex items-center justify-between">
                    <h3 class="text-lg font-bold text-gray-900">Edit Test Result</h3>
                    <button wire:click="closeEditResultModal" class="text-gray-400 hover:text-gray-600">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>

                <form wire:submit.prevent="updateResult">
                    <div class="p-6 space-y-4">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Result Value</label>
                                <input type="text" wire:model="editResultValue" placeholder="e.g., 120 mg/dL"
                                       class="w-full px-3 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent text-sm">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Normal Range</label>
                                <input type="text" wire:model="editResultNormalRange" placeholder="e.g., 70-110 mg/dL"
                                       class="w-full px-3 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent text-sm">
                            </div>
                        </div>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Result Date <span class="text-red-500">*</span></label>
                                <input type="date" wire:model="editResultDate"
                                       class="w-full px-3 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent text-sm">
                                @error('editResultDate') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Status <span class="text-red-500">*</span></label>
                                <select wire:model="editResultStatus"
                                        class="w-full px-3 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent text-sm">
                                    <option value="draft">Draft</option>
                                    <option value="final">Final</option>
                                    <option value="revised">Revised</option>
                                </select>
                            </div>
                        </div>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Performed By</label>
                                <select wire:model="editResultPerformedBy"
                                        class="w-full px-3 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent text-sm">
                                    <option value="">Select Employee</option>
                                    @foreach($employees as $employee)
                                        <option value="{{ $employee->employee_id }}">{{ $employee->full_name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Verified By</label>
                                <select wire:model="editResultVerifiedBy"
                                        class="w-full px-3 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent text-sm">
                                    <option value="">Select Employee</option>
                                    @foreach($employees as $employee)
                                        <option value="{{ $employee->employee_id }}">{{ $employee->full_name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Findings</label>
                            <textarea wire:model="editResultFindings" rows="2" placeholder="Enter clinical findings..."
                                      class="w-full px-3 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent text-sm"></textarea>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Remarks</label>
                            <textarea wire:model="editResultRemarks" rows="2" placeholder="Additional remarks..."
                                      class="w-full px-3 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent text-sm"></textarea>
                        </div>
                    </div>

                    <div class="px-6 py-4 bg-gray-50 border-t border-gray-200 flex justify-end space-x-3 rounded-b-xl">
                        <button type="button" wire:click="closeEditResultModal"
                                class="px-5 py-2.5 border border-gray-300 rounded-lg text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">
                            Cancel
                        </button>
                        <button type="submit"
                                class="px-5 py-2.5 bg-orange-500 text-white text-sm font-medium rounded-lg hover:bg-orange-600 transition-colors">
                            Update Result
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    @endif
</div>