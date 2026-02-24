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
use App\Models\TestRequest;
use App\Models\TestRequestItem;
use App\Models\Transaction;
use Carbon\Carbon;
use App\Traits\LogsActivity;
use Barryvdh\DomPDF\Facade\Pdf;

new class extends Component
{
    use WithPagination, LogsActivity;

    // Active tab: 'orders' or 'requests'
    public $activeTab = 'orders';

    // Filters
    public $search = '';
    public $filterStatus = '';
    public $filterDate = '';
    public $perPage = 10;
    public $flashMessage = '';

    // Test Request filters (separate so they don't conflict)
    public $reqSearch = '';
    public $reqFilterStatus = '';
    public $reqFilterDate = '';
    public $reqPerPage = 10;

    // Test Request modals
    public bool $showRequestDetail = false;
    public $viewingRequest = null;
    public bool $showRejectModal = false;
    public $rejectRequestId = null;
    public $rejectRemarks = '';

    // UPDATED: Delete confirmation modal properties
    public $showDeleteModal = false;
    public $deleteMessage = '';
    public $deleteAction = '';
    public $labResultsToDelete = [];

    // UPDATED: Web search modal properties (for test search)
    public $showSearchModal = false;
    public $searchQuery = '';
    public $searchResults = null;

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
        $this->result_date = date('Y-m-d');
        $this->status = 'draft';

        // Check if we need to open edit modal from URL parameter
        if (request()->has('edit')) {
            $editId = request()->get('edit');
            $this->openEditModal($editId);
        }
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

        // Calculate total amount from selected test prices
        $totalAmount = Test::active()
            ->whereIn('test_id', $this->selectedTests)
            ->sum('current_price');

        $order = LabTestOrder::create([
            'patient_id' => $this->orderPatientId,
            'physician_id' => $this->orderPhysicianId ?: null,
            'order_date' => now(),
            'status' => 'pending',
            'payment_status' => 'PENDING_PAYMENT',
            'total_amount' => $totalAmount,
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

        $this->logActivity("Created lab test order for patient ID {$this->orderPatientId} (Total: ₱" . number_format($totalAmount, 2) . ", Payment: PENDING)");
        $this->flashMessage = 'Test order created successfully! Payment is required before results can be encoded.';
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

    public function downloadOrderPdf()
    {
        if (!$this->viewingOrder) return;

        $order = LabTestOrder::with([
            'patient',
            'physician',
            'orderTests.test.section',
            'orderTests.labResult.performedBy',
            'orderTests.labResult.verifiedBy',
        ])->find($this->viewingOrder->lab_test_order_id);

        if (!$order) return;

        $pdf = Pdf::loadView('pdf.lab-result', ['order' => $order])
            ->setPaper('a4', 'portrait');

        $filename = 'LabResult_Order_' . $order->lab_test_order_id . '_' . now()->format('Ymd') . '.pdf';

        return response()->streamDownload(function () use ($pdf) {
            echo $pdf->output();
        }, $filename);
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

        // PAY-FIRST: Block result entry if order is not paid
        if ($orderTest->order && !$orderTest->order->isPaid()) {
            $this->addError('resultValue', 'Cannot add results — this order has not been paid yet.');
            return;
        }

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

        // PAY-FIRST: Block result edit if order is not paid
        if ($result->lab_test_order_id) {
            $order = LabTestOrder::find($result->lab_test_order_id);
            if ($order && !$order->isPaid()) {
                $this->addError('editResultValue', 'Cannot edit results — this order has not been paid yet.');
                return;
            }
        }

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

    public function switchTab($tab)
    {
        $this->activeTab = $tab;
    }

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function updatingFilterStatus()
    {
        $this->resetPage();
    }

    public function updatingReqSearch()
    {
        $this->resetPage();
    }

    public function updatingReqFilterStatus()
    {
        $this->resetPage();
    }

    public function updatingReqFilterDate()
    {
        $this->resetPage();
    }

    // ===== TEST REQUEST METHODS =====

    public function viewRequestDetail($requestId)
    {
        $this->viewingRequest = TestRequest::with([
            'patient',
            'requestedBy',
            'reviewer',
            'items.test.section',
        ])->find($requestId);
        $this->showRequestDetail = true;
    }

    public function closeRequestDetail()
    {
        $this->showRequestDetail = false;
        $this->viewingRequest = null;
    }

    public function approveRequest($requestId)
    {
        $request = TestRequest::with(['items.test', 'patient'])->find($requestId);

        if (!$request || $request->status !== 'PENDING') {
            $this->flashMessage = 'Request cannot be approved (already processed).';
            return;
        }

        // Calculate total amount from requested test prices
        $totalAmount = $request->items->sum(fn($item) => (float) ($item->test->current_price ?? 0));

        $order = LabTestOrder::create([
            'patient_id' => $request->patient_id,
            'physician_id' => null,
            'order_date' => now(),
            'status' => 'pending',
            'payment_status' => 'PENDING_PAYMENT',
            'total_amount' => $totalAmount,
            'remarks' => 'Created from patient test request #' . $request->id,
        ]);

        foreach ($request->items as $item) {
            OrderTest::create([
                'order_id' => $order->lab_test_order_id,
                'test_id' => $item->test_id,
                'status' => 'pending',
                'datetime_added' => now(),
            ]);
        }

        $request->update([
            'status' => 'APPROVED',
            'reviewed_by' => auth()->id(),
            'reviewed_at' => now(),
            'datetime_updated' => now(),
        ]);

        $patientName = $request->patient ? $request->patient->full_name : 'Unknown';
        $this->logActivity("Approved test request #{$request->id} for patient {$patientName} — Lab Test Order #{$order->lab_test_order_id} created (Total: ₱" . number_format($totalAmount, 2) . ", Payment: PENDING)");

        $this->flashMessage = "Request #{$request->id} approved! Lab Test Order #{$order->lab_test_order_id} created. Payment required before results can be encoded.";

        if ($this->showRequestDetail && $this->viewingRequest && $this->viewingRequest->id == $requestId) {
            $this->viewRequestDetail($requestId);
        }
    }

    public function openRejectModal($requestId)
    {
        $this->rejectRequestId = $requestId;
        $this->rejectRemarks = '';
        $this->showRejectModal = true;
    }

    public function closeRejectModal()
    {
        $this->showRejectModal = false;
        $this->rejectRequestId = null;
        $this->rejectRemarks = '';
        $this->resetValidation();
    }

    public function rejectRequest()
    {
        $this->validate([
            'rejectRemarks' => 'required|string|max:500',
        ], [
            'rejectRemarks.required' => 'Please provide a reason for rejection.',
        ]);

        $request = TestRequest::with('patient')->find($this->rejectRequestId);

        if (!$request || $request->status !== 'PENDING') {
            $this->flashMessage = 'Request cannot be rejected (already processed).';
            $this->closeRejectModal();
            return;
        }

        $request->update([
            'status' => 'REJECTED',
            'staff_remarks' => $this->rejectRemarks,
            'reviewed_by' => auth()->id(),
            'reviewed_at' => now(),
            'datetime_updated' => now(),
        ]);

        $patientName = $request->patient ? $request->patient->full_name : 'Unknown';
        $this->logActivity("Rejected test request #{$request->id} for patient {$patientName}: {$this->rejectRemarks}");

        $this->flashMessage = "Request #{$request->id} rejected.";
        $this->closeRejectModal();

        if ($this->showRequestDetail && $this->viewingRequest && $this->viewingRequest->id == $this->rejectRequestId) {
            $this->viewRequestDetail($this->rejectRequestId);
        }
    }

    // UPDATED: Delete selected method (placeholder - no selection in this page)
    public function deleteSelected()
    {
        // This page doesn't have selection, but keeping for consistency
    }

    // UPDATED: Close delete modal method
    public function closeDeleteModal()
    {
        $this->showDeleteModal = false;
        $this->deleteMessage = '';
        $this->deleteAction = '';
        $this->labResultsToDelete = [];
    }

    // UPDATED: Web search modal methods (for test search)
    public function openSearchModal()
    {
        $this->showSearchModal = true;
    }

    public function closeSearchModal()
    {
        $this->showSearchModal = false;
        $this->searchQuery = '';
        $this->searchResults = null;
    }

    public function updatedSearchQuery()
    {
        if (strlen($this->searchQuery) >= 1) {
            $this->searchResults = Test::active()
                ->where('label', 'like', '%' . $this->searchQuery . '%')
                ->with('section')
                ->orderBy('label')
                ->get()
                ->map(fn($t) => (object)[
                    'id'      => $t->test_id,
                    'title'   => $t->label,
                    'section' => $t->section?->label ?? 'No Section',
                    'price'   => $t->current_price ? '₱' . number_format($t->current_price, 2) : null,
                ]);
        } else {
            $this->searchResults = null;
        }
    }

    public function addTestFromSearch($testId)
    {
        $testId = (string) $testId;
        if (!in_array($testId, array_map('strval', $this->selectedTests))) {
            $this->selectedTests[] = $testId;
        }
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

        // Test Request query & stats
        $reqQuery = TestRequest::with(['patient', 'requestedBy', 'reviewer', 'items.test'])
            ->when($this->reqSearch, function ($q) {
                $q->whereHas('patient', function ($pq) {
                    $pq->where('firstname', 'like', '%' . $this->reqSearch . '%')
                       ->orWhere('lastname', 'like', '%' . $this->reqSearch . '%');
                });
            })
            ->when($this->reqFilterStatus, function ($q) {
                $q->where('status', $this->reqFilterStatus);
            })
            ->when($this->reqFilterDate, function ($q) {
                $q->whereDate('datetime_added', $this->reqFilterDate);
            })
            ->orderByDesc('datetime_added');

        $reqPendingCount = TestRequest::where('status', 'PENDING')->count();
        $reqTodayCount = TestRequest::whereDate('datetime_added', Carbon::today())->count();
        $reqApprovedCount = TestRequest::where('status', 'APPROVED')->count();
        $reqRejectedCount = TestRequest::where('status', 'REJECTED')->count();

        return [
            'orders' => $this->perPage === 'all' ? $query->get() : $query->paginate((int)$this->perPage),
            'patients' => Patient::active()->orderBy('lastname')->get(),
            'physicians' => Physician::active()->orderBy('physician_name')->get(),
            'employees' => Employee::active()->orderBy('lastname')->get(),
            'sections' => $sections,
            'pendingCount' => $pendingCount,
            'todayCount' => $todayCount,
            'completedTodayCount' => $completedTodayCount,
            // Test Requests
            'testRequests' => $this->reqPerPage === 'all' ? $reqQuery->get() : $reqQuery->paginate((int)$this->reqPerPage, ['*'], 'reqPage'),
            'reqPendingCount' => $reqPendingCount,
            'reqTodayCount' => $reqTodayCount,
            'reqApprovedCount' => $reqApprovedCount,
            'reqRejectedCount' => $reqRejectedCount,
        ];
    }
};
?>

<div class="p-6">
    {{-- Header --}}
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-gray-900 flex items-center gap-2">
            <svg class="w-6 h-6 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/>
            </svg>
            Lab Test Orders & Results
        </h1>
    </div>

    {{-- Flash Message --}}
    <div x-show="$wire.flashMessage" x-transition x-init="$nextTick(() => setTimeout(() => $wire.set('flashMessage', ''), 5000))"
         class="mb-6 bg-white border-l-4 border-green-500 shadow-sm rounded-lg p-4 flex items-center justify-between">
        <div class="flex items-center">
            <svg class="w-5 h-5 text-green-500 mr-3" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
            </svg>
            <span class="text-sm font-medium text-gray-900">{{ $flashMessage }}</span>
        </div>
    </div>

    {{-- Tabs --}}
    <div class="mb-6 border-b border-gray-200">
        <nav class="flex gap-1" aria-label="Tabs">
            <button wire:click="switchTab('orders')"
                    class="flex items-center gap-2 px-4 pb-3 pt-1 text-sm font-semibold border-b-2 transition-colors
                        {{ $activeTab === 'orders' ? 'border-red-500 text-red-600' : 'border-transparent text-gray-400 hover:text-gray-600 hover:border-gray-300' }}">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                </svg>
                Test Orders
            </button>
            @can('test-requests.access')
            <button wire:click="switchTab('requests')"
                    class="flex items-center gap-2 px-4 pb-3 pt-1 text-sm font-semibold border-b-2 transition-colors
                        {{ $activeTab === 'requests' ? 'border-red-500 text-red-600' : 'border-transparent text-gray-400 hover:text-gray-600 hover:border-gray-300' }}">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
                </svg>
                Patient Requests
                @if($reqPendingCount > 0)
                    <span class="inline-flex items-center justify-center w-5 h-5 text-[10px] font-bold text-white bg-red-500 rounded-full">{{ $reqPendingCount }}</span>
                @endif
            </button>
            @endcan
        </nav>
    </div>

    {{-- ==================== TAB: TEST ORDERS ==================== --}}
    @if($activeTab === 'orders')

    {{-- Stats Cards --}}
    <div class="grid grid-cols-3 gap-4 mb-6">
        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm px-6 py-5 flex items-center gap-5">
            <div class="w-12 h-12 rounded-2xl bg-orange-50 flex items-center justify-center flex-shrink-0">
                <svg class="w-6 h-6 text-orange-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </div>
            <div>
                <p class="text-[10px] font-semibold text-gray-400 uppercase tracking-widest mb-0.5">Pending Orders</p>
                <p class="text-3xl font-bold text-gray-900 leading-none">{{ $pendingCount }}</p>
            </div>
        </div>
        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm px-6 py-5 flex items-center gap-5">
            <div class="w-12 h-12 rounded-2xl bg-blue-50 flex items-center justify-center flex-shrink-0">
                <svg class="w-6 h-6 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                </svg>
            </div>
            <div>
                <p class="text-[10px] font-semibold text-gray-400 uppercase tracking-widest mb-0.5">Today's Orders</p>
                <p class="text-3xl font-bold text-gray-900 leading-none">{{ $todayCount }}</p>
            </div>
        </div>
        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm px-6 py-5 flex items-center gap-5">
            <div class="w-12 h-12 rounded-2xl bg-green-50 flex items-center justify-center flex-shrink-0">
                <svg class="w-6 h-6 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </div>
            <div>
                <p class="text-[10px] font-semibold text-gray-400 uppercase tracking-widest mb-0.5">Completed Today</p>
                <p class="text-3xl font-bold text-gray-900 leading-none">{{ $completedTodayCount }}</p>
            </div>
        </div>
    </div>

    {{-- Orders List Card --}}
    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm">

        {{-- Card header: title + button --}}
        <div class="px-6 py-5 flex items-center justify-between">
            <h2 class="text-base font-bold text-gray-900">Test Orders</h2>
            <button wire:click="openCreateModal"
                    class="inline-flex items-center gap-2 px-4 py-2 bg-red-500 hover:bg-red-600 text-white text-sm font-semibold rounded-xl transition-colors">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/>
                </svg>
                Create Test Order
            </button>
        </div>

        {{-- Filters --}}
        <div class="px-6 pb-4 grid grid-cols-4 gap-4 border-b border-gray-100">
            <div>
                <label class="block text-[10px] font-semibold text-gray-400 uppercase tracking-widest mb-1.5">Search Patient</label>
                <input type="text" wire:model.live.debounce.300ms="search" placeholder="Search by patient name..."
                       class="w-full px-3 py-2 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-red-400 focus:border-transparent transition-colors">
            </div>
            <div>
                <label class="block text-[10px] font-semibold text-gray-400 uppercase tracking-widest mb-1.5">Status</label>
                <select wire:model.live="filterStatus"
                        class="w-full px-3 py-2 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-red-400 transition-colors appearance-none bg-white">
                    <option value="">All Status</option>
                    <option value="pending">Pending</option>
                    <option value="completed">Completed</option>
                    <option value="cancelled">Cancelled</option>
                </select>
            </div>
            <div>
                <label class="block text-[10px] font-semibold text-gray-400 uppercase tracking-widest mb-1.5">Order Date</label>
                <input type="date" wire:model.live="filterDate"
                       class="w-full px-3 py-2 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-red-400 transition-colors">
            </div>
            <div>
                <label class="block text-[10px] font-semibold text-gray-400 uppercase tracking-widest mb-1.5">Rows Per Page</label>
                <select wire:model.live="perPage"
                        class="w-full px-3 py-2 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-red-400 transition-colors appearance-none bg-white">
                    <option value="10">10</option>
                    <option value="25">25</option>
                    <option value="50">50</option>
                    <option value="all">All</option>
                </select>
            </div>
        </div>

        {{-- Orders Table --}}
        <div class="overflow-x-auto">
            <table class="min-w-full">
                <thead>
                    <tr class="border-b border-gray-100">
                        <th class="px-6 py-3 text-left text-[10px] font-semibold text-gray-400 uppercase tracking-widest">Order #</th>
                        <th class="px-6 py-3 text-left text-[10px] font-semibold text-gray-400 uppercase tracking-widest">Patient</th>
                        <th class="px-6 py-3 text-left text-[10px] font-semibold text-gray-400 uppercase tracking-widest">Physician</th>
                        <th class="px-6 py-3 text-left text-[10px] font-semibold text-gray-400 uppercase tracking-widest">Order Date</th>
                        <th class="px-6 py-3 text-left text-[10px] font-semibold text-gray-400 uppercase tracking-widest">Amount</th>
                        <th class="px-6 py-3 text-left text-[10px] font-semibold text-gray-400 uppercase tracking-widest">Payment</th>
                        <th class="px-6 py-3 text-left text-[10px] font-semibold text-gray-400 uppercase tracking-widest">Tests</th>
                        <th class="px-6 py-3 text-left text-[10px] font-semibold text-gray-400 uppercase tracking-widest">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    @forelse($orders as $order)
                        @php
                            $totalTests    = $order->orderTests->count();
                            $completedTests = $order->orderTests->where('status', 'completed')->count();
                            $pct           = $totalTests > 0 ? ($completedTests / $totalTests * 100) : 0;
                            $allDone       = $totalTests > 0 && $completedTests >= $totalTests;
                            $payBadge      = $order->payment_badge;
                        @endphp
                        <tr wire:key="order-{{ $order->lab_test_order_id }}"
                            wire:click="viewOrder({{ $order->lab_test_order_id }})"
                            class="hover:bg-gray-50/70 transition-colors cursor-pointer group">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="text-sm font-bold text-red-500">#{{ $order->lab_test_order_id }}</span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="text-sm font-semibold text-gray-900">{{ $order->patient->full_name ?? 'N/A' }}</span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="text-sm text-gray-500">{{ $order->physician->physician_name ?? '—' }}</span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="text-sm text-gray-500">
                                    {{ $order->order_date ? $order->order_date->format('M d, Y · g:i A') : '—' }}
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="text-sm font-bold text-gray-900">
                                    {{ $order->total_amount ? '₱' . number_format($order->total_amount, 2) : '—' }}
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold {{ $payBadge['class'] }}">
                                    <span class="w-1.5 h-1.5 rounded-full {{ $order->payment_status === 'PAID' ? 'bg-green-500' : 'bg-orange-400' }}"></span>
                                    {{ $payBadge['label'] }}
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex items-center gap-2">
                                    <div class="w-20 h-1.5 bg-gray-100 rounded-full overflow-hidden">
                                        <div class="h-full rounded-full transition-all {{ $allDone ? 'bg-green-500' : 'bg-green-400' }}"
                                             style="width: {{ $pct }}%"></div>
                                    </div>
                                    <span class="text-xs text-gray-500 tabular-nums">{{ $completedTests }}/{{ $totalTests }}</span>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="inline-flex px-2.5 py-1 rounded-full text-xs font-semibold
                                    @if($order->status === 'completed') bg-green-100 text-green-700
                                    @elseif($order->status === 'cancelled') bg-red-100 text-red-700
                                    @else bg-yellow-100 text-yellow-700
                                    @endif">
                                    {{ ucfirst($order->status) }}
                                </span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-6 py-16 text-center">
                                <svg class="w-12 h-12 mx-auto mb-3 text-gray-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                </svg>
                                <p class="text-sm font-semibold text-gray-400">No test orders found</p>
                                <p class="text-xs text-gray-300 mt-1">Create a new test order to get started.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Pagination --}}
        @if($perPage !== 'all' && method_exists($orders, 'hasPages'))
        <div class="px-6 py-4 border-t border-gray-100 flex items-center justify-between">
            <p class="text-xs text-gray-400">Showing {{ $orders->firstItem() }}–{{ $orders->lastItem() }} of {{ $orders->total() }} orders</p>
            <div class="flex items-center gap-1">
                {{-- Prev --}}
                @if($orders->onFirstPage())
                    <span class="w-8 h-8 flex items-center justify-center rounded-lg text-gray-300 cursor-not-allowed">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                    </span>
                @else
                    <button wire:click="previousPage" class="w-8 h-8 flex items-center justify-center rounded-lg text-gray-500 hover:bg-gray-100 transition-colors">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                    </button>
                @endif
                {{-- Pages --}}
                @foreach($orders->getUrlRange(1, $orders->lastPage()) as $page => $url)
                    @if($page == $orders->currentPage())
                        <span class="w-8 h-8 flex items-center justify-center rounded-lg bg-red-500 text-white text-xs font-bold">{{ $page }}</span>
                    @else
                        <button wire:click="gotoPage({{ $page }})" class="w-8 h-8 flex items-center justify-center rounded-lg text-gray-500 hover:bg-gray-100 text-xs font-medium transition-colors">{{ $page }}</button>
                    @endif
                @endforeach
                {{-- Next --}}
                @if($orders->hasMorePages())
                    <button wire:click="nextPage" class="w-8 h-8 flex items-center justify-center rounded-lg text-gray-500 hover:bg-gray-100 transition-colors">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                    </button>
                @else
                    <span class="w-8 h-8 flex items-center justify-center rounded-lg text-gray-300 cursor-not-allowed">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                    </span>
                @endif
            </div>
        </div>
        @endif

    </div>

    @endif {{-- END: activeTab === 'orders' --}}

    {{-- ==================== TAB: TEST REQUESTS ==================== --}}
    @if($activeTab === 'requests')

    {{-- Request Stats Cards --}}
    <div class="grid grid-cols-4 gap-4 mb-6">
        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm px-6 py-5 flex items-center gap-5">
            <div class="w-12 h-12 rounded-2xl bg-orange-50 flex items-center justify-center flex-shrink-0">
                <svg class="w-6 h-6 text-orange-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </div>
            <div>
                <p class="text-[10px] font-semibold text-gray-400 uppercase tracking-widest mb-0.5">Pending</p>
                <p class="text-3xl font-bold text-gray-900 leading-none">{{ $reqPendingCount }}</p>
            </div>
        </div>
        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm px-6 py-5 flex items-center gap-5">
            <div class="w-12 h-12 rounded-2xl bg-blue-50 flex items-center justify-center flex-shrink-0">
                <svg class="w-6 h-6 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                </svg>
            </div>
            <div>
                <p class="text-[10px] font-semibold text-gray-400 uppercase tracking-widest mb-0.5">Today</p>
                <p class="text-3xl font-bold text-gray-900 leading-none">{{ $reqTodayCount }}</p>
            </div>
        </div>
        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm px-6 py-5 flex items-center gap-5">
            <div class="w-12 h-12 rounded-2xl bg-green-50 flex items-center justify-center flex-shrink-0">
                <svg class="w-6 h-6 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </div>
            <div>
                <p class="text-[10px] font-semibold text-gray-400 uppercase tracking-widest mb-0.5">Approved</p>
                <p class="text-3xl font-bold text-gray-900 leading-none">{{ $reqApprovedCount }}</p>
            </div>
        </div>
        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm px-6 py-5 flex items-center gap-5">
            <div class="w-12 h-12 rounded-2xl bg-red-50 flex items-center justify-center flex-shrink-0">
                <svg class="w-6 h-6 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </div>
            <div>
                <p class="text-[10px] font-semibold text-gray-400 uppercase tracking-widest mb-0.5">Rejected</p>
                <p class="text-3xl font-bold text-gray-900 leading-none">{{ $reqRejectedCount }}</p>
            </div>
        </div>
    </div>

    {{-- Requests List Card --}}
    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm">

        {{-- Card header --}}
        <div class="px-6 py-5 flex items-center justify-between">
            <h2 class="text-base font-bold text-gray-900">Patient Test Requests</h2>
        </div>

        {{-- Filters --}}
        <div class="px-6 pb-4 grid grid-cols-4 gap-4 border-b border-gray-100">
            <div>
                <label class="block text-[10px] font-semibold text-gray-400 uppercase tracking-widest mb-1.5">Search Patient</label>
                <input type="text" wire:model.live.debounce.300ms="reqSearch" placeholder="Search by patient name..."
                       class="w-full px-3 py-2 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-red-400 focus:border-transparent transition-colors">
            </div>
            <div>
                <label class="block text-[10px] font-semibold text-gray-400 uppercase tracking-widest mb-1.5">Status</label>
                <select wire:model.live="reqFilterStatus"
                        class="w-full px-3 py-2 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-red-400 transition-colors appearance-none bg-white">
                    <option value="">All Status</option>
                    <option value="PENDING">Pending</option>
                    <option value="APPROVED">Approved</option>
                    <option value="REJECTED">Rejected</option>
                    <option value="CANCELLED">Cancelled</option>
                </select>
            </div>
            <div>
                <label class="block text-[10px] font-semibold text-gray-400 uppercase tracking-widest mb-1.5">Request Date</label>
                <input type="date" wire:model.live="reqFilterDate"
                       class="w-full px-3 py-2 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-red-400 transition-colors">
            </div>
            <div>
                <label class="block text-[10px] font-semibold text-gray-400 uppercase tracking-widest mb-1.5">Rows Per Page</label>
                <select wire:model.live="reqPerPage"
                        class="w-full px-3 py-2 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-red-400 transition-colors appearance-none bg-white">
                    <option value="10">10</option>
                    <option value="25">25</option>
                    <option value="50">50</option>
                    <option value="all">All</option>
                </select>
            </div>
        </div>

        {{-- Requests Table --}}
        <div class="overflow-x-auto">
            <table class="min-w-full">
                <thead>
                    <tr class="border-b border-gray-100">
                        <th class="px-6 py-3 text-left text-[10px] font-semibold text-gray-400 uppercase tracking-widest">Request #</th>
                        <th class="px-6 py-3 text-left text-[10px] font-semibold text-gray-400 uppercase tracking-widest">Patient</th>
                        <th class="px-6 py-3 text-left text-[10px] font-semibold text-gray-400 uppercase tracking-widest">Tests</th>
                        <th class="px-6 py-3 text-left text-[10px] font-semibold text-gray-400 uppercase tracking-widest">Preferred Date</th>
                        <th class="px-6 py-3 text-left text-[10px] font-semibold text-gray-400 uppercase tracking-widest">Submitted</th>
                        <th class="px-6 py-3 text-left text-[10px] font-semibold text-gray-400 uppercase tracking-widest">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    @forelse($testRequests as $req)
                        @php $badge = $req->statusBadge; @endphp
                        <tr wire:key="req-{{ $req->id }}"
                            wire:click="viewRequestDetail({{ $req->id }})"
                            class="hover:bg-gray-50/70 transition-colors cursor-pointer">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="text-sm font-bold text-red-500">#{{ $req->id }}</span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="text-sm font-semibold text-gray-900">{{ $req->patient->full_name ?? 'N/A' }}</span>
                            </td>
                            <td class="px-6 py-4">
                                <div class="flex flex-wrap gap-1">
                                    @foreach($req->items->take(3) as $item)
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-lg text-xs font-medium bg-gray-100 text-gray-700">{{ $item->test->label ?? 'N/A' }}</span>
                                    @endforeach
                                    @if($req->items->count() > 3)
                                        <span class="text-xs text-gray-400">+{{ $req->items->count() - 3 }} more</span>
                                    @endif
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="text-sm text-gray-500">
                                    {{ $req->preferred_date ? \Carbon\Carbon::parse($req->preferred_date)->format('M d, Y') : '—' }}
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="text-sm text-gray-500">
                                    {{ $req->datetime_added ? \Carbon\Carbon::parse($req->datetime_added)->format('M d, Y · g:i A') : '—' }}
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="inline-flex px-2.5 py-1 rounded-full text-xs font-semibold {{ $badge['class'] }}">
                                    {{ $badge['label'] }}
                                </span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-16 text-center">
                                <svg class="w-12 h-12 mx-auto mb-3 text-gray-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
                                </svg>
                                <p class="text-sm font-semibold text-gray-400">No patient test requests found</p>
                                <p class="text-xs text-gray-300 mt-1">Patient requests will appear here when submitted.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Pagination --}}
        @if($reqPerPage !== 'all' && method_exists($testRequests, 'hasPages'))
        <div class="px-6 py-4 border-t border-gray-100 flex items-center justify-between">
            <p class="text-xs text-gray-400">Showing {{ $testRequests->firstItem() }}–{{ $testRequests->lastItem() }} of {{ $testRequests->total() }} requests</p>
            <div class="flex items-center gap-1">
                @if($testRequests->onFirstPage())
                    <span class="w-8 h-8 flex items-center justify-center rounded-lg text-gray-300 cursor-not-allowed">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                    </span>
                @else
                    <button wire:click="previousPage('reqPage')" class="w-8 h-8 flex items-center justify-center rounded-lg text-gray-500 hover:bg-gray-100 transition-colors">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                    </button>
                @endif
                @foreach($testRequests->getUrlRange(1, $testRequests->lastPage()) as $page => $url)
                    @if($page == $testRequests->currentPage())
                        <span class="w-8 h-8 flex items-center justify-center rounded-lg bg-red-500 text-white text-xs font-bold">{{ $page }}</span>
                    @else
                        <button wire:click="gotoPage({{ $page }}, 'reqPage')" class="w-8 h-8 flex items-center justify-center rounded-lg text-gray-500 hover:bg-gray-100 text-xs font-medium transition-colors">{{ $page }}</button>
                    @endif
                @endforeach
                @if($testRequests->hasMorePages())
                    <button wire:click="nextPage('reqPage')" class="w-8 h-8 flex items-center justify-center rounded-lg text-gray-500 hover:bg-gray-100 transition-colors">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                    </button>
                @else
                    <span class="w-8 h-8 flex items-center justify-center rounded-lg text-gray-300 cursor-not-allowed">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                    </span>
                @endif
            </div>
        </div>
        @endif

    </div>

    {{-- ===== VIEW REQUEST DETAIL MODAL ===== --}}
    @if($showRequestDetail && $viewingRequest)
    @php $vBadge = $viewingRequest->statusBadge; @endphp
    <div class="fixed inset-0 z-50 overflow-y-auto" style="background-color: rgba(0,0,0,0.5);">
        <div class="flex items-center justify-center min-h-screen p-4">
            <div class="bg-white rounded-2xl shadow-2xl w-full max-w-xl max-h-[90vh] flex flex-col">

                {{-- Header --}}
                <div class="px-6 pt-6 pb-5 flex items-start justify-between gap-4 flex-shrink-0">
                    <div>
                        <div class="flex items-center gap-2 mb-1">
                            <span class="inline-flex items-center px-2 py-0.5 rounded-md bg-gray-900 text-white text-xs font-bold tracking-wide">#{{ $viewingRequest->id }}</span>
                            <h3 class="text-lg font-bold text-gray-900">Test Request</h3>
                        </div>
                        <span class="inline-flex px-2.5 py-1 rounded-full text-xs font-semibold {{ $vBadge['class'] }}">{{ $vBadge['label'] }}</span>
                    </div>
                    <button wire:click="closeRequestDetail"
                            class="w-8 h-8 flex items-center justify-center rounded-full text-gray-400 hover:text-gray-700 hover:bg-gray-100 border border-gray-200 transition-colors flex-shrink-0 mt-1">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>

                {{-- Body --}}
                <div class="px-6 pb-6 overflow-y-auto flex-1 space-y-4">

                    {{-- Patient Info row --}}
                    <div class="rounded-xl border border-gray-200 divide-x divide-gray-200 grid grid-cols-2">
                        <div class="px-4 py-3">
                            <p class="text-[10px] font-semibold text-gray-400 uppercase tracking-widest mb-1">Patient</p>
                            <p class="text-sm font-semibold text-gray-900">{{ $viewingRequest->patient->full_name ?? 'N/A' }}</p>
                        </div>
                        <div class="px-4 py-3">
                            <p class="text-[10px] font-semibold text-gray-400 uppercase tracking-widest mb-1">Submitted</p>
                            <p class="text-sm text-gray-700">{{ $viewingRequest->datetime_added ? \Carbon\Carbon::parse($viewingRequest->datetime_added)->format('M d, Y · g:i A') : '—' }}</p>
                        </div>
                    </div>

                    @if($viewingRequest->preferred_date || $viewingRequest->purpose)
                    <div class="rounded-xl border border-gray-200 divide-x divide-gray-200 grid grid-cols-2">
                        @if($viewingRequest->preferred_date)
                        <div class="px-4 py-3">
                            <p class="text-[10px] font-semibold text-gray-400 uppercase tracking-widest mb-1">Preferred Date</p>
                            <p class="text-sm text-gray-700">{{ \Carbon\Carbon::parse($viewingRequest->preferred_date)->format('M d, Y') }}</p>
                        </div>
                        @endif
                        @if($viewingRequest->purpose)
                        <div class="px-4 py-3 {{ !$viewingRequest->preferred_date ? 'col-span-2' : '' }}">
                            <p class="text-[10px] font-semibold text-gray-400 uppercase tracking-widest mb-1">Purpose</p>
                            <p class="text-sm text-gray-700">{{ $viewingRequest->purpose }}</p>
                        </div>
                        @endif
                    </div>
                    @endif

                    {{-- Requested Tests --}}
                    <div>
                        <p class="text-[10px] font-semibold text-gray-400 uppercase tracking-widest mb-2">Requested Tests ({{ $viewingRequest->items->count() }})</p>
                        <div class="space-y-2">
                            @foreach($viewingRequest->items as $item)
                            <div class="flex items-center justify-between rounded-xl border border-gray-100 px-4 py-3">
                                <div class="flex items-center gap-3">
                                    <span class="w-2 h-2 rounded-full bg-red-400 flex-shrink-0"></span>
                                    <div>
                                        <p class="text-sm font-semibold text-gray-900">{{ $item->test->label ?? 'Unknown Test' }}</p>
                                        @if($item->test && $item->test->section)
                                            <p class="text-xs text-gray-400">{{ $item->test->section->label }}</p>
                                        @endif
                                    </div>
                                </div>
                                @if($item->test && $item->test->price)
                                    <span class="text-sm font-semibold text-gray-700">₱{{ number_format($item->test->price, 2) }}</span>
                                @endif
                            </div>
                            @endforeach
                        </div>
                    </div>

                    {{-- Review Info --}}
                    @if($viewingRequest->reviewed_by)
                    <div>
                        <p class="text-[10px] font-semibold text-gray-400 uppercase tracking-widest mb-2">Review Details</p>
                        <div class="rounded-xl border border-gray-200 divide-x divide-gray-200 grid grid-cols-2">
                            <div class="px-4 py-3">
                                <p class="text-[10px] font-semibold text-gray-400 uppercase tracking-widest mb-1">Reviewed By</p>
                                <p class="text-sm font-semibold text-gray-900">{{ $viewingRequest->reviewer->name ?? 'N/A' }}</p>
                            </div>
                            <div class="px-4 py-3">
                                <p class="text-[10px] font-semibold text-gray-400 uppercase tracking-widest mb-1">Reviewed At</p>
                                <p class="text-sm text-gray-700">{{ $viewingRequest->reviewed_at ? \Carbon\Carbon::parse($viewingRequest->reviewed_at)->format('M d, Y · g:i A') : '—' }}</p>
                            </div>
                            @if($viewingRequest->staff_remarks)
                            <div class="col-span-2 px-4 py-3 border-t border-gray-100">
                                <p class="text-[10px] font-semibold text-gray-400 uppercase tracking-widest mb-1">Remarks</p>
                                <p class="text-sm text-gray-700">{{ $viewingRequest->staff_remarks }}</p>
                            </div>
                            @endif
                        </div>
                    </div>
                    @endif

                </div>

                {{-- Footer / Actions --}}
                <div class="px-6 py-4 border-t border-gray-100 flex items-center justify-between flex-shrink-0">
                    <button wire:click="closeRequestDetail"
                            class="px-5 py-2.5 border border-gray-200 rounded-xl text-sm font-medium text-gray-600 hover:bg-gray-50 transition-colors">
                        Close
                    </button>
                    @if($viewingRequest->status === 'PENDING')
                        @can('test-requests.review')
                        <div class="flex items-center gap-2">
                            <button wire:click="openRejectModal({{ $viewingRequest->id }})"
                                    class="inline-flex items-center gap-2 px-4 py-2.5 border border-red-200 text-red-600 text-sm font-semibold rounded-xl hover:bg-red-50 transition-colors">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                </svg>
                                Reject
                            </button>
                            <button wire:click="approveRequest({{ $viewingRequest->id }})" wire:confirm="Approve this request and create a lab test order?"
                                    class="inline-flex items-center gap-2 px-4 py-2.5 bg-green-600 hover:bg-green-700 text-white text-sm font-semibold rounded-xl transition-colors">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>
                                </svg>
                                Approve & Create Order
                            </button>
                        </div>
                        @endcan
                    @endif
                </div>

            </div>
        </div>
    </div>
    @endif

    {{-- ===== REJECT REQUEST MODAL ===== --}}
    @if($showRejectModal)
    <div class="fixed inset-0 z-[60] overflow-y-auto" style="background-color: rgba(0,0,0,0.5);">
        <div class="flex items-center justify-center min-h-screen p-4">
            <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md">

                {{-- Header --}}
                <div class="px-6 pt-6 pb-5 flex items-start justify-between gap-4">
                    <div class="flex items-center gap-4">
                        <div class="w-12 h-12 rounded-2xl bg-red-50 flex items-center justify-center flex-shrink-0">
                            <svg class="w-6 h-6 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-lg font-bold text-gray-900 leading-tight">Reject Test Request</h3>
                            <p class="text-xs text-gray-400 mt-0.5">Please provide a reason for rejecting.</p>
                        </div>
                    </div>
                    <button wire:click="closeRejectModal"
                            class="w-8 h-8 flex items-center justify-center rounded-full text-gray-400 hover:text-gray-700 hover:bg-gray-100 border border-gray-200 transition-colors flex-shrink-0 mt-1">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>

                {{-- Body --}}
                <div class="px-6 pb-4">
                    <label class="block text-[10px] font-semibold text-gray-400 uppercase tracking-widest mb-1.5">
                        Reason for Rejection <span class="text-red-500">*</span>
                    </label>
                    <textarea wire:model="rejectRemarks" rows="4"
                              class="w-full px-3 py-2.5 border border-gray-200 rounded-xl text-sm text-gray-900 placeholder-gray-300 focus:outline-none focus:ring-2 focus:ring-red-400 focus:border-transparent transition-colors resize-none"
                              placeholder="Explain why this request is being rejected..."></textarea>
                    @error('rejectRemarks') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                </div>

                {{-- Footer --}}
                <div class="px-6 py-4 border-t border-gray-100 flex items-center gap-3">
                    <button wire:click="closeRejectModal"
                            class="flex-1 px-4 py-2.5 border border-gray-200 rounded-xl text-sm font-medium text-gray-600 hover:bg-gray-50 transition-colors">
                        Cancel
                    </button>
                    <button wire:click="rejectRequest"
                            class="flex-1 inline-flex items-center justify-center gap-2 px-4 py-2.5 bg-red-500 hover:bg-red-600 text-white text-sm font-semibold rounded-xl transition-colors">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                        Confirm Rejection
                    </button>
                </div>

            </div>
        </div>
    </div>
    @endif

    @endif {{-- END: activeTab === 'requests' --}}

    {{-- ==================== CREATE ORDER MODAL ==================== --}}
    @if($showCreateModal)
    <div class="fixed inset-0 z-50 overflow-y-auto" style="background-color: rgba(0,0,0,0.5);">
        <div class="flex items-center justify-center min-h-screen p-4">
            <div class="bg-white rounded-2xl shadow-2xl w-full max-w-xl max-h-[90vh] flex flex-col">

                {{-- Header --}}
                <div class="px-6 pt-6 pb-5 flex items-start justify-between gap-4 flex-shrink-0">
                    <div class="flex items-center gap-4">
                        <div class="w-12 h-12 rounded-2xl bg-red-50 flex items-center justify-center flex-shrink-0">
                            <svg class="w-6 h-6 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/>
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-lg font-bold text-gray-900 leading-tight">Create Test Order</h3>
                            <p class="text-xs text-gray-400 mt-0.5">Select a patient and the tests to order</p>
                        </div>
                    </div>
                    <button wire:click="closeCreateModal"
                            class="w-8 h-8 flex items-center justify-center rounded-full text-gray-400 hover:text-gray-700 hover:bg-gray-100 border border-gray-200 transition-colors flex-shrink-0 mt-1">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>

                {{-- Body --}}
                <form wire:submit.prevent="createOrder" class="flex flex-col flex-1 overflow-hidden">
                    <div class="px-6 pb-4 overflow-y-auto flex-1 space-y-4">

                        {{-- Patient & Physician row --}}
                        <div class="grid grid-cols-2 gap-4">
                            <!-- Searchable Patient Dropdown -->
                            <div x-data="{
                                open: false,
                                search: '',
                                selectedLabel: '',
                                items: @js($patients->map(fn($p) => ['id' => $p->patient_id, 'name' => $p->full_name])),
                                get filtered() {
                                    if (!this.search) return this.items;
                                    return this.items.filter(i => i.name.toLowerCase().includes(this.search.toLowerCase()));
                                },
                                select(item) {
                                    $wire.set('orderPatientId', item.id);
                                    this.selectedLabel = item.name;
                                    this.search = '';
                                    this.open = false;
                                },
                                clear() {
                                    $wire.set('orderPatientId', '');
                                    this.selectedLabel = '';
                                    this.search = '';
                                },
                                init() {
                                    let val = $wire.get('orderPatientId');
                                    if (val) {
                                        let found = this.items.find(i => String(i.id) === String(val));
                                        if (found) this.selectedLabel = found.name;
                                    }
                                }
                            }" @click.away="open = false" class="relative">
                                <label class="block text-[10px] font-semibold text-gray-400 uppercase tracking-widest mb-1.5">
                                    Patient <span class="text-red-500">*</span>
                                </label>
                                <div @click="open = !open"
                                     class="w-full px-3 py-2.5 border border-gray-200 rounded-xl cursor-pointer bg-white flex items-center justify-between text-sm focus-within:ring-2 focus-within:ring-red-400 transition-colors">
                                    <span x-show="selectedLabel" x-text="selectedLabel" class="text-gray-900 truncate text-sm"></span>
                                    <span x-show="!selectedLabel" class="text-gray-300 text-sm">Select Patient</span>
                                    <div class="flex items-center gap-1 flex-shrink-0">
                                        <button x-show="selectedLabel" @click.stop="clear()" type="button" class="text-gray-300 hover:text-red-400 transition-colors">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                        </button>
                                        <svg class="w-4 h-4 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                                    </div>
                                </div>
                                <div x-show="open" x-transition class="absolute z-50 mt-1 w-full bg-white border border-gray-200 rounded-xl shadow-lg max-h-60 overflow-hidden">
                                    <div class="p-2 border-b border-gray-100">
                                        <input type="text" x-model="search" @click.stop placeholder="Search patients..."
                                               class="w-full px-3 py-1.5 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-1 focus:ring-red-400" autocomplete="off">
                                    </div>
                                    <ul class="overflow-y-auto max-h-48">
                                        <template x-for="item in filtered" :key="item.id">
                                            <li @click="select(item)" class="px-4 py-2 text-sm hover:bg-red-50 cursor-pointer transition-colors" x-text="item.name"></li>
                                        </template>
                                        <li x-show="filtered.length === 0" class="px-4 py-3 text-sm text-gray-400 text-center">No results found</li>
                                    </ul>
                                </div>
                                @error('orderPatientId') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                            </div>

                            <!-- Searchable Physician Dropdown -->
                            <div x-data="{
                                open: false,
                                search: '',
                                selectedLabel: '',
                                items: @js($physicians->map(fn($p) => ['id' => $p->physician_id, 'name' => $p->physician_name])),
                                get filtered() {
                                    if (!this.search) return this.items;
                                    return this.items.filter(i => i.name.toLowerCase().includes(this.search.toLowerCase()));
                                },
                                select(item) {
                                    $wire.set('orderPhysicianId', item.id);
                                    this.selectedLabel = item.name;
                                    this.search = '';
                                    this.open = false;
                                },
                                clear() {
                                    $wire.set('orderPhysicianId', '');
                                    this.selectedLabel = '';
                                    this.search = '';
                                },
                                init() {
                                    let val = $wire.get('orderPhysicianId');
                                    if (val) {
                                        let found = this.items.find(i => String(i.id) === String(val));
                                        if (found) this.selectedLabel = found.name;
                                    }
                                }
                            }" @click.away="open = false" class="relative">
                                <label class="block text-[10px] font-semibold text-gray-400 uppercase tracking-widest mb-1.5">Physician</label>
                                <div @click="open = !open"
                                     class="w-full px-3 py-2.5 border border-gray-200 rounded-xl cursor-pointer bg-white flex items-center justify-between text-sm focus-within:ring-2 focus-within:ring-red-400 transition-colors">
                                    <span x-show="selectedLabel" x-text="selectedLabel" class="text-gray-900 truncate text-sm"></span>
                                    <span x-show="!selectedLabel" class="text-gray-300 text-sm">Select Physician (Optional)</span>
                                    <div class="flex items-center gap-1 flex-shrink-0">
                                        <button x-show="selectedLabel" @click.stop="clear()" type="button" class="text-gray-300 hover:text-red-400 transition-colors">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                        </button>
                                        <svg class="w-4 h-4 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                                    </div>
                                </div>
                                <div x-show="open" x-transition class="absolute z-50 mt-1 w-full bg-white border border-gray-200 rounded-xl shadow-lg max-h-60 overflow-hidden">
                                    <div class="p-2 border-b border-gray-100">
                                        <input type="text" x-model="search" @click.stop placeholder="Search physicians..."
                                               class="w-full px-3 py-1.5 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-1 focus:ring-red-400" autocomplete="off">
                                    </div>
                                    <ul class="overflow-y-auto max-h-48">
                                        <template x-for="item in filtered" :key="item.id">
                                            <li @click="select(item)" class="px-4 py-2 text-sm hover:bg-red-50 cursor-pointer transition-colors" x-text="item.name"></li>
                                        </template>
                                        <li x-show="filtered.length === 0" class="px-4 py-3 text-sm text-gray-400 text-center">No results found</li>
                                    </ul>
                                </div>
                            </div>
                        </div>

                        {{-- Remarks --}}
                        <div>
                            <label class="block text-[10px] font-semibold text-gray-400 uppercase tracking-widest mb-1.5">Remarks</label>
                            <textarea wire:model="orderRemarks" rows="2" maxlength="200" placeholder="Optional remarks..."
                                      class="w-full px-3 py-2.5 border border-gray-200 rounded-xl text-sm placeholder-gray-300 focus:outline-none focus:ring-2 focus:ring-red-400 focus:border-transparent transition-colors resize-none"></textarea>
                        </div>

                        {{-- Test Selection --}}
                        <div>
                            <div class="flex items-center justify-between mb-2">
                                <label class="text-[10px] font-semibold text-gray-400 uppercase tracking-widest">
                                    Select Tests <span class="text-red-500">*</span>
                                    @if(count($selectedTests) > 0)
                                        <span class="ml-1 text-red-400 font-semibold normal-case">({{ count($selectedTests) }} selected)</span>
                                    @endif
                                </label>
                                <button type="button" wire:click="openSearchModal"
                                        class="inline-flex items-center gap-1.5 text-xs font-semibold text-red-500 hover:text-red-700 transition-colors">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-4.35-4.35M17 11A6 6 0 1 1 5 11a6 6 0 0 1 12 0z"/>
                                    </svg>
                                    Search Tests
                                </button>
                            </div>
                            @error('selectedTests') <span class="text-red-500 text-xs mb-2 block">{{ $message }}</span> @enderror

                            <div class="border border-gray-200 rounded-xl overflow-hidden max-h-64 overflow-y-auto">
                                @foreach($sections as $section)
                                    @if($section->tests->count() > 0)
                                        <div class="px-4 py-2 bg-gray-50 border-b border-gray-100 sticky top-0 z-10">
                                            <span class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">{{ $section->label }}</span>
                                        </div>
                                        @foreach($section->tests as $test)
                                            <label class="flex items-center px-4 py-3 hover:bg-gray-50 cursor-pointer border-b border-gray-50 last:border-0 transition-colors group">
                                                <input type="checkbox" wire:model="selectedTests" value="{{ $test->test_id }}"
                                                       class="rounded border-gray-300 text-red-500 focus:ring-red-400 mr-3 flex-shrink-0">
                                                <span class="flex-1 text-sm text-gray-800 group-hover:text-gray-900">{{ $test->label }}</span>
                                                @if($test->current_price)
                                                    <span class="text-xs font-medium text-gray-400 ml-2 flex-shrink-0">&#8369;{{ number_format($test->current_price, 2) }}</span>
                                                @endif
                                            </label>
                                        @endforeach
                                    @endif
                                @endforeach
                            </div>
                        </div>
                    </div>

                    {{-- Footer --}}
                    <div class="px-6 py-4 border-t border-gray-100 flex items-center justify-between flex-shrink-0">
                        <p class="text-xs text-gray-400">
                            Select at least <span class="font-semibold text-gray-500">1 test</span> to continue
                        </p>
                        <div class="flex items-center gap-3">
                            <button type="button" wire:click="closeCreateModal"
                                    class="px-5 py-2.5 border border-gray-200 rounded-xl text-sm font-medium text-gray-600 hover:bg-gray-50 transition-colors">
                                Cancel
                            </button>
                            <button type="submit"
                                    class="inline-flex items-center gap-2 px-5 py-2.5 bg-red-500 hover:bg-red-600 text-white text-sm font-semibold rounded-xl transition-colors">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/>
                                </svg>
                                Create Order
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
    @endif

    {{-- ==================== ORDER DETAIL MODAL ==================== --}}
    @if($showOrderDetail && $viewingOrder)
    @php
        $payBadge    = $viewingOrder->payment_badge;
        $totalTests  = $viewingOrder->orderTests->count();
        $donTests    = $viewingOrder->orderTests->where('status', 'completed')->count();
    @endphp
    <div class="fixed inset-0 z-50 overflow-y-auto" style="background-color: rgba(0,0,0,0.5);">
        <div class="flex items-center justify-center min-h-screen p-4">
            <div class="bg-white rounded-2xl shadow-2xl w-full max-w-xl max-h-[90vh] flex flex-col">

                {{-- ── Header ── --}}
                <div class="px-6 pt-6 pb-5 flex-shrink-0">
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <h2 class="text-2xl font-bold text-gray-900 leading-tight">Order #{{ $viewingOrder->lab_test_order_id }}</h2>
                            <p class="text-sm text-gray-400 mt-0.5">
                                {{ $viewingOrder->order_date ? $viewingOrder->order_date->format('F d, Y · g:i A') : '' }}
                            </p>
                        </div>
                        <div class="flex items-center gap-2 flex-shrink-0 mt-1">
                            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full border text-xs font-semibold
                                @if($viewingOrder->status === 'completed') border-green-300 bg-green-50 text-green-700
                                @elseif($viewingOrder->status === 'cancelled') border-red-300 bg-red-50 text-red-700
                                @else border-orange-300 bg-orange-50 text-orange-700
                                @endif">
                                <span class="w-1.5 h-1.5 rounded-full
                                    @if($viewingOrder->status === 'completed') bg-green-500
                                    @elseif($viewingOrder->status === 'cancelled') bg-red-500
                                    @else bg-orange-500
                                    @endif"></span>
                                {{ strtoupper($viewingOrder->status) }}
                            </span>
                            <button wire:click="closeOrderDetail"
                                    class="w-8 h-8 flex items-center justify-center rounded-full text-gray-400 hover:text-gray-700 hover:bg-gray-100 transition-colors border border-gray-200">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                </svg>
                            </button>
                        </div>
                    </div>
                </div>

                {{-- ── Scrollable body ── --}}
                <div class="overflow-y-auto flex-1 px-6 pb-6 space-y-4">

                    {{-- Payment warning (unpaid only) --}}
                    @if($viewingOrder->payment_status !== 'PAID')
                    <div class="rounded-xl border border-amber-200 bg-amber-50 p-4 flex items-start gap-3">
                        <div class="w-8 h-8 rounded-lg bg-amber-100 flex items-center justify-center flex-shrink-0 mt-0.5">
                            <svg class="w-4 h-4 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z"/>
                            </svg>
                        </div>
                        <div>
                            <p class="text-sm font-bold text-amber-900">Payment Required</p>
                            <p class="text-xs text-amber-700 mt-0.5 leading-relaxed">Results cannot be encoded or edited until payment is recorded in the Transactions module.</p>
                            @if($viewingOrder->total_amount)
                                <span class="inline-flex mt-2 px-3 py-1 rounded-full bg-amber-800 text-white text-xs font-bold">
                                    ₱{{ number_format($viewingOrder->total_amount, 2) }} due
                                </span>
                            @endif
                        </div>
                    </div>
                    @endif

                    {{-- Row 1: Patient · Physician · Remarks --}}
                    <div class="rounded-xl border border-gray-200 divide-x divide-gray-200 grid grid-cols-3">
                        <div class="px-4 py-3">
                            <p class="text-[10px] font-semibold text-gray-400 uppercase tracking-widest mb-1">Patient</p>
                            <p class="text-sm font-semibold text-gray-900 leading-snug">{{ $viewingOrder->patient->full_name ?? 'N/A' }}</p>
                        </div>
                        <div class="px-4 py-3">
                            <p class="text-[10px] font-semibold text-gray-400 uppercase tracking-widest mb-1">Physician</p>
                            <p class="text-sm {{ $viewingOrder->physician ? 'font-semibold text-gray-900' : 'italic text-gray-400' }} leading-snug">
                                {{ $viewingOrder->physician->physician_name ?? 'Not assigned' }}
                            </p>
                        </div>
                        <div class="px-4 py-3">
                            <p class="text-[10px] font-semibold text-gray-400 uppercase tracking-widest mb-1">Remarks</p>
                            <p class="text-sm text-gray-700 leading-snug">{{ $viewingOrder->remarks ?: '—' }}</p>
                        </div>
                    </div>

                    {{-- Row 2: Payment Status · Total Amount · Paid At --}}
                    <div class="rounded-xl border border-gray-200 divide-x divide-gray-200 grid grid-cols-3">
                        <div class="px-4 py-3">
                            <p class="text-[10px] font-semibold text-gray-400 uppercase tracking-widest mb-1.5">Payment Status</p>
                            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold {{ $payBadge['class'] }}">
                                <span class="w-1.5 h-1.5 rounded-full {{ $viewingOrder->payment_status === 'PAID' ? 'bg-green-500' : 'bg-orange-500' }}"></span>
                                {{ $payBadge['label'] }}
                            </span>
                        </div>
                        <div class="px-4 py-3">
                            <p class="text-[10px] font-semibold text-gray-400 uppercase tracking-widest mb-1">Total Amount</p>
                            <p class="text-sm font-bold {{ $viewingOrder->total_amount ? 'text-gray-900' : 'text-gray-400' }}">
                                {{ $viewingOrder->total_amount ? '₱' . number_format($viewingOrder->total_amount, 2) : '—' }}
                            </p>
                        </div>
                        <div class="px-4 py-3">
                            <p class="text-[10px] font-semibold text-gray-400 uppercase tracking-widest mb-1">Paid At</p>
                            <p class="text-sm text-gray-700 leading-snug">
                                {{ $viewingOrder->paid_at ? $viewingOrder->paid_at->format('M d, Y · g:i A') : '—' }}
                            </p>
                        </div>
                    </div>

                    {{-- Test Progress --}}
                    <div class="flex items-center justify-between">
                        <span class="text-sm font-medium text-gray-700">Test Progress</span>
                        <span class="text-sm text-gray-400">{{ $donTests }} / {{ $totalTests }} completed</span>
                    </div>

                    {{-- Ordered Tests --}}
                    <div>
                        <p class="text-[10px] font-semibold text-gray-400 uppercase tracking-widest mb-2">Ordered Tests</p>
                        <div class="space-y-2">
                            @foreach($viewingOrder->orderTests as $orderTest)
                            <div class="rounded-xl border border-gray-100 bg-white overflow-hidden">
                                {{-- Test row --}}
                                <div class="px-4 py-3 flex items-center justify-between gap-3">
                                    <div class="flex items-center gap-3 min-w-0">
                                        <span class="w-2.5 h-2.5 rounded-full flex-shrink-0
                                            @if($orderTest->status === 'completed') bg-green-500
                                            @elseif($orderTest->status === 'in_progress') bg-blue-500
                                            @elseif($orderTest->status === 'cancelled') bg-red-500
                                            @else bg-orange-400
                                            @endif"></span>
                                        <div class="min-w-0">
                                            <p class="text-sm font-semibold text-gray-900 leading-tight">{{ $orderTest->test->label ?? 'Unknown Test' }}</p>
                                            @if($orderTest->test && $orderTest->test->section)
                                                <p class="text-xs text-gray-400">{{ $orderTest->test->section->label }}</p>
                                            @endif
                                        </div>
                                    </div>
                                    <div class="flex items-center gap-2 flex-shrink-0">
                                        <span class="px-2.5 py-1 text-xs font-medium rounded-full border
                                            @if($orderTest->status === 'completed') border-green-200 bg-green-50 text-green-700
                                            @elseif($orderTest->status === 'in_progress') border-blue-200 bg-blue-50 text-blue-700
                                            @elseif($orderTest->status === 'cancelled') border-red-200 bg-red-50 text-red-700
                                            @else border-yellow-200 bg-yellow-50 text-yellow-700
                                            @endif">
                                            {{ str_replace('_', ' ', ucfirst($orderTest->status)) }}
                                        </span>
                                        @if($orderTest->status !== 'completed' && $orderTest->status !== 'cancelled' && !$orderTest->labResult)
                                            @if($viewingOrder->isPaid())
                                            <button wire:click="openResultModal({{ $orderTest->order_test_id }})"
                                                    class="inline-flex items-center gap-1 px-3 py-1.5 bg-red-500 hover:bg-red-600 text-white text-xs font-semibold rounded-lg transition-colors">
                                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/>
                                                </svg>
                                                Add Result
                                            </button>
                                            @else
                                            <span class="inline-flex items-center gap-1 px-3 py-1.5 bg-gray-100 text-gray-400 text-xs font-semibold rounded-lg cursor-not-allowed" title="Payment required">
                                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                                                </svg>
                                                Add Result
                                            </span>
                                            @endif
                                        @endif
                                    </div>
                                </div>

                                {{-- Result details (if encoded) --}}
                                @if($orderTest->labResult)
                                <div class="px-4 py-3 bg-gray-50 border-t border-gray-100">
                                    <div class="grid grid-cols-2 gap-3 mb-2">
                                        <div>
                                            <p class="text-[10px] text-gray-400 font-semibold uppercase mb-0.5">Result Value</p>
                                            <p class="text-sm font-semibold text-gray-900">{{ $orderTest->labResult->result_value ?: '—' }}</p>
                                        </div>
                                        <div>
                                            <p class="text-[10px] text-gray-400 font-semibold uppercase mb-0.5">Normal Range</p>
                                            <p class="text-sm text-gray-700">{{ $orderTest->labResult->normal_range ?: '—' }}</p>
                                        </div>
                                        <div>
                                            <p class="text-[10px] text-gray-400 font-semibold uppercase mb-0.5">Result Date</p>
                                            <p class="text-sm text-gray-700">{{ $orderTest->labResult->result_date ? Carbon::parse($orderTest->labResult->result_date)->format('M d, Y') : '—' }}</p>
                                        </div>
                                        <div>
                                            <p class="text-[10px] text-gray-400 font-semibold uppercase mb-0.5">Status</p>
                                            <span class="px-2 py-0.5 text-xs font-medium rounded-full {{ $orderTest->labResult->status_badge_class }}">
                                                {{ ucfirst($orderTest->labResult->status) }}
                                            </span>
                                        </div>
                                    </div>
                                    @if($orderTest->labResult->findings)
                                    <div class="mb-1">
                                        <p class="text-[10px] text-gray-400 font-semibold uppercase mb-0.5">Findings</p>
                                        <p class="text-xs text-gray-700">{{ $orderTest->labResult->findings }}</p>
                                    </div>
                                    @endif
                                    @if($orderTest->labResult->remarks)
                                    <div class="mb-2">
                                        <p class="text-[10px] text-gray-400 font-semibold uppercase mb-0.5">Remarks</p>
                                        <p class="text-xs text-gray-700">{{ $orderTest->labResult->remarks }}</p>
                                    </div>
                                    @endif
                                    <div class="flex items-center justify-between pt-2 border-t border-gray-200">
                                        <div class="flex gap-3 text-[10px] text-gray-400">
                                            @if($orderTest->labResult->performedBy)
                                                <span>By: <span class="font-medium text-gray-600">{{ $orderTest->labResult->performedBy->full_name }}</span></span>
                                            @endif
                                            @if($orderTest->labResult->verifiedBy)
                                                <span>Verified: <span class="font-medium text-gray-600">{{ $orderTest->labResult->verifiedBy->full_name }}</span></span>
                                            @endif
                                        </div>
                                        @if($viewingOrder->isPaid())
                                        <button wire:click="openEditResultModal({{ $orderTest->labResult->lab_result_id }})"
                                                class="px-3 py-1.5 bg-orange-500 hover:bg-orange-600 text-white text-xs font-semibold rounded-lg transition-colors">
                                            Edit Result
                                        </button>
                                        @else
                                        <span class="px-3 py-1.5 bg-gray-100 text-gray-400 text-xs font-semibold rounded-lg cursor-not-allowed">
                                            Edit Result
                                        </span>
                                        @endif
                                    </div>
                                </div>
                                @endif
                            </div>
                            @endforeach
                        </div>
                    </div>

                </div>

                {{-- ── Footer ── --}}
                <div class="px-6 py-4 border-t border-gray-100 flex items-center justify-between flex-shrink-0">
                    <button wire:click="closeOrderDetail"
                            class="px-5 py-2.5 border border-gray-200 rounded-xl text-sm font-medium text-gray-600 hover:bg-gray-50 transition-colors">
                        Close
                    </button>
                    <button wire:click="downloadOrderPdf"
                            class="inline-flex items-center gap-2 px-5 py-2.5 text-sm font-semibold text-white bg-gray-900 hover:bg-gray-800 rounded-xl transition-colors">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                        </svg>
                        Download PDF
                    </button>
                </div>

            </div>
        </div>
    </div>
    @endif

    {{-- ==================== ADD RESULT MODAL ==================== --}}
    @if($showResultModal)
    <div class="fixed inset-0 z-[60] overflow-y-auto" style="background-color: rgba(0,0,0,0.5);">
        <div class="flex items-center justify-center min-h-screen p-4">
            <div class="bg-white rounded-2xl shadow-2xl w-full max-w-lg">

                {{-- Header --}}
                <div class="px-6 pt-6 pb-5 flex items-start justify-between gap-4">
                    <div class="flex items-center gap-4">
                        <div class="w-12 h-12 rounded-2xl bg-red-50 flex items-center justify-center flex-shrink-0">
                            <svg class="w-6 h-6 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/>
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-lg font-bold text-gray-900 leading-tight">Add Test Result</h3>
                            <p class="text-xs text-gray-400 mt-0.5">Fill in the details for this lab test</p>
                        </div>
                    </div>
                    <button wire:click="closeResultModal"
                            class="w-8 h-8 flex items-center justify-center rounded-full text-gray-400 hover:text-gray-700 hover:bg-gray-100 border border-gray-200 transition-colors flex-shrink-0 mt-1">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>

                <form wire:submit.prevent="saveResult">
                    <div class="px-6 pb-4 space-y-4">

                        {{-- Row 1: Result Value | Normal Range --}}
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-[10px] font-semibold text-gray-400 uppercase tracking-widest mb-1.5">Result Value</label>
                                <input type="text" wire:model="resultValue" placeholder="e.g., 120 mg/dL"
                                       class="w-full px-3 py-2.5 border border-gray-200 rounded-xl text-sm text-gray-900 placeholder-gray-300 focus:outline-none focus:ring-2 focus:ring-red-400 focus:border-transparent transition-colors">
                            </div>
                            <div>
                                <label class="block text-[10px] font-semibold text-gray-400 uppercase tracking-widest mb-1.5">Normal Range</label>
                                <input type="text" wire:model="resultNormalRange" placeholder="e.g., 70–110 mg/dL"
                                       class="w-full px-3 py-2.5 border border-gray-200 rounded-xl text-sm text-gray-900 placeholder-gray-300 focus:outline-none focus:ring-2 focus:ring-red-400 focus:border-transparent transition-colors">
                            </div>
                        </div>

                        {{-- Row 2: Result Date | Status --}}
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-[10px] font-semibold text-gray-400 uppercase tracking-widest mb-1.5">
                                    Result Date <span class="text-red-500">*</span>
                                </label>
                                <input type="date" wire:model="resultDate"
                                       class="w-full px-3 py-2.5 border border-gray-200 rounded-xl text-sm text-gray-900 focus:outline-none focus:ring-2 focus:ring-red-400 focus:border-transparent transition-colors">
                                @error('resultDate') <span class="text-red-500 text-xs mt-0.5 block">{{ $message }}</span> @enderror
                            </div>
                            <div>
                                <label class="block text-[10px] font-semibold text-gray-400 uppercase tracking-widest mb-1.5">
                                    Status <span class="text-red-500">*</span>
                                </label>
                                <select wire:model="resultStatus"
                                        class="w-full px-3 py-2.5 border border-gray-200 rounded-xl text-sm text-gray-900 focus:outline-none focus:ring-2 focus:ring-red-400 focus:border-transparent transition-colors appearance-none bg-white">
                                    <option value="draft">Draft</option>
                                    <option value="final">Final</option>
                                    <option value="revised">Revised</option>
                                </select>
                            </div>
                        </div>

                        {{-- Row 3: Performed By | Verified By --}}
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-[10px] font-semibold text-gray-400 uppercase tracking-widest mb-1.5">Performed By</label>
                                <div class="relative">
                                    <div class="absolute inset-y-0 left-3 flex items-center pointer-events-none">
                                        <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                                        </svg>
                                    </div>
                                    <input type="text" value="{{ auth()->user()?->employee ? auth()->user()->employee->full_name : 'N/A' }}" readonly
                                           class="w-full pl-9 pr-3 py-2.5 border border-gray-200 rounded-xl bg-gray-50 text-sm text-gray-600 cursor-not-allowed">
                                    <input type="hidden" wire:model="resultPerformedBy">
                                </div>
                            </div>
                            <div>
                                <label class="block text-[10px] font-semibold text-gray-400 uppercase tracking-widest mb-1.5">Verified By</label>
                                <select wire:model="resultVerifiedBy"
                                        class="w-full px-3 py-2.5 border border-gray-200 rounded-xl text-sm text-gray-900 focus:outline-none focus:ring-2 focus:ring-red-400 focus:border-transparent transition-colors appearance-none bg-white">
                                    <option value="">Select employee</option>
                                    @foreach($employees as $employee)
                                        <option value="{{ $employee->employee_id }}">{{ $employee->full_name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        {{-- Divider --}}
                        <div class="relative flex items-center py-1">
                            <div class="flex-1 border-t border-gray-100"></div>
                            <span class="px-3 text-[10px] font-semibold text-gray-300 uppercase tracking-widest">Additional Info</span>
                            <div class="flex-1 border-t border-gray-100"></div>
                        </div>

                        {{-- Findings --}}
                        <div>
                            <label class="block text-[10px] font-semibold text-gray-400 uppercase tracking-widest mb-1.5">Findings</label>
                            <textarea wire:model="resultFindings" rows="3" placeholder="Enter clinical findings..."
                                      class="w-full px-3 py-2.5 border border-gray-200 rounded-xl text-sm text-gray-900 placeholder-gray-300 focus:outline-none focus:ring-2 focus:ring-red-400 focus:border-transparent transition-colors resize-y"></textarea>
                        </div>

                        {{-- Remarks --}}
                        <div>
                            <label class="block text-[10px] font-semibold text-gray-400 uppercase tracking-widest mb-1.5">Remarks</label>
                            <textarea wire:model="resultRemarks" rows="3" placeholder="Additional remarks..."
                                      class="w-full px-3 py-2.5 border border-gray-200 rounded-xl text-sm text-gray-900 placeholder-gray-300 focus:outline-none focus:ring-2 focus:ring-red-400 focus:border-transparent transition-colors resize-y"></textarea>
                        </div>

                    </div>

                    {{-- Footer --}}
                    <div class="px-6 py-4 border-t border-gray-100 flex items-center justify-end gap-3">
                        <button type="button" wire:click="closeResultModal"
                                class="px-5 py-2.5 border border-gray-200 rounded-xl text-sm font-medium text-gray-600 hover:bg-gray-50 transition-colors">
                            Cancel
                        </button>
                        <button type="submit"
                                class="inline-flex items-center gap-2 px-5 py-2.5 bg-red-500 hover:bg-red-600 text-white text-sm font-semibold rounded-xl transition-colors">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>
                            </svg>
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
    <div class="fixed inset-0 z-[60] overflow-y-auto" style="background-color: rgba(0,0,0,0.5);">
        <div class="flex items-center justify-center min-h-screen p-4">
            <div class="bg-white rounded-2xl shadow-2xl w-full max-w-lg">

                {{-- Header --}}
                <div class="px-6 pt-6 pb-5 flex items-start justify-between gap-4">
                    <div class="flex items-center gap-4">
                        <div class="w-12 h-12 rounded-2xl bg-orange-50 flex items-center justify-center flex-shrink-0">
                            <svg class="w-5 h-5 text-orange-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-lg font-bold text-gray-900 leading-tight">Edit Test Result</h3>
                            <p class="text-xs text-gray-400 mt-0.5">Update the details for this lab test</p>
                        </div>
                    </div>
                    <button wire:click="closeEditResultModal"
                            class="w-8 h-8 flex items-center justify-center rounded-full text-gray-400 hover:text-gray-700 hover:bg-gray-100 border border-gray-200 transition-colors flex-shrink-0 mt-1">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>

                <form wire:submit.prevent="updateResult">
                    <div class="px-6 pb-4 space-y-4">

                        {{-- Row 1: Result Value | Normal Range --}}
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-[10px] font-semibold text-gray-400 uppercase tracking-widest mb-1.5">Result Value</label>
                                <input type="text" wire:model="editResultValue" placeholder="e.g., 120 mg/dL"
                                       class="w-full px-3 py-2.5 border border-gray-200 rounded-xl text-sm text-gray-900 placeholder-gray-300 focus:outline-none focus:ring-2 focus:ring-orange-400 focus:border-transparent transition-colors">
                            </div>
                            <div>
                                <label class="block text-[10px] font-semibold text-gray-400 uppercase tracking-widest mb-1.5">Normal Range</label>
                                <input type="text" wire:model="editResultNormalRange" placeholder="e.g., 70–110 mg/dL"
                                       class="w-full px-3 py-2.5 border border-gray-200 rounded-xl text-sm text-gray-900 placeholder-gray-300 focus:outline-none focus:ring-2 focus:ring-orange-400 focus:border-transparent transition-colors">
                            </div>
                        </div>

                        {{-- Row 2: Result Date | Status --}}
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-[10px] font-semibold text-gray-400 uppercase tracking-widest mb-1.5">
                                    Result Date <span class="text-red-500">*</span>
                                </label>
                                <input type="date" wire:model="editResultDate"
                                       class="w-full px-3 py-2.5 border border-gray-200 rounded-xl text-sm text-gray-900 focus:outline-none focus:ring-2 focus:ring-orange-400 focus:border-transparent transition-colors">
                                @error('editResultDate') <span class="text-red-500 text-xs mt-0.5 block">{{ $message }}</span> @enderror
                            </div>
                            <div>
                                <label class="block text-[10px] font-semibold text-gray-400 uppercase tracking-widest mb-1.5">
                                    Status <span class="text-red-500">*</span>
                                </label>
                                <select wire:model="editResultStatus"
                                        class="w-full px-3 py-2.5 border border-gray-200 rounded-xl text-sm text-gray-900 focus:outline-none focus:ring-2 focus:ring-orange-400 focus:border-transparent transition-colors appearance-none bg-white">
                                    <option value="draft">Draft</option>
                                    <option value="final">Final</option>
                                    <option value="revised">Revised</option>
                                </select>
                            </div>
                        </div>

                        {{-- Row 3: Performed By | Verified By --}}
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-[10px] font-semibold text-gray-400 uppercase tracking-widest mb-1.5">Performed By</label>
                                <select wire:model="editResultPerformedBy"
                                        class="w-full px-3 py-2.5 border border-gray-200 rounded-xl text-sm text-gray-900 focus:outline-none focus:ring-2 focus:ring-orange-400 focus:border-transparent transition-colors appearance-none bg-white">
                                    <option value="">Select employee</option>
                                    @foreach($employees as $employee)
                                        <option value="{{ $employee->employee_id }}">{{ $employee->full_name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label class="block text-[10px] font-semibold text-gray-400 uppercase tracking-widest mb-1.5">Verified By</label>
                                <select wire:model="editResultVerifiedBy"
                                        class="w-full px-3 py-2.5 border border-gray-200 rounded-xl text-sm text-gray-900 focus:outline-none focus:ring-2 focus:ring-orange-400 focus:border-transparent transition-colors appearance-none bg-white">
                                    <option value="">Select employee</option>
                                    @foreach($employees as $employee)
                                        <option value="{{ $employee->employee_id }}">{{ $employee->full_name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        {{-- Divider --}}
                        <div class="relative flex items-center py-1">
                            <div class="flex-1 border-t border-gray-100"></div>
                            <span class="px-3 text-[10px] font-semibold text-gray-300 uppercase tracking-widest">Additional Info</span>
                            <div class="flex-1 border-t border-gray-100"></div>
                        </div>

                        {{-- Findings --}}
                        <div>
                            <label class="block text-[10px] font-semibold text-gray-400 uppercase tracking-widest mb-1.5">Findings</label>
                            <textarea wire:model="editResultFindings" rows="3" placeholder="Enter clinical findings..."
                                      class="w-full px-3 py-2.5 border border-gray-200 rounded-xl text-sm text-gray-900 placeholder-gray-300 focus:outline-none focus:ring-2 focus:ring-orange-400 focus:border-transparent transition-colors resize-y"></textarea>
                        </div>

                        {{-- Remarks --}}
                        <div>
                            <label class="block text-[10px] font-semibold text-gray-400 uppercase tracking-widest mb-1.5">Remarks</label>
                            <textarea wire:model="editResultRemarks" rows="3" placeholder="Additional remarks..."
                                      class="w-full px-3 py-2.5 border border-gray-200 rounded-xl text-sm text-gray-900 placeholder-gray-300 focus:outline-none focus:ring-2 focus:ring-orange-400 focus:border-transparent transition-colors resize-y"></textarea>
                        </div>

                    </div>

                    {{-- Footer --}}
                    <div class="px-6 py-4 border-t border-gray-100 flex items-center justify-end gap-3">
                        <button type="button" wire:click="closeEditResultModal"
                                class="px-5 py-2.5 border border-gray-200 rounded-xl text-sm font-medium text-gray-600 hover:bg-gray-50 transition-colors">
                            Cancel
                        </button>
                        <button type="submit"
                                class="inline-flex items-center gap-2 px-5 py-2.5 bg-orange-500 hover:bg-orange-600 text-white text-sm font-semibold rounded-xl transition-colors">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>
                            </svg>
                            Update Result
                        </button>
                    </div>
                </form>

            </div>
        </div>
    </div>
    @endif

    {{-- UPDATED: Web Search Modal --}}
    @if($showSearchModal)
    <div class="fixed inset-0 z-50 overflow-y-auto" style="background-color: rgba(0, 0, 0, 0.5);">
        <div class="flex items-center justify-center min-h-screen p-4">
            <div class="bg-white rounded-lg shadow-xl max-w-2xl w-full">
                <div class="px-6 py-4 border-b border-gray-200">
                    <div class="flex items-center justify-between">
                        <h3 class="text-xl font-semibold text-gray-900">Search Tests</h3>
                        <button type="button" wire:click="closeSearchModal" class="text-gray-400 hover:text-gray-600">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </button>
                    </div>
                </div>
                <div class="p-6">
                    <div class="relative mb-4">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <svg class="h-4 w-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                            </svg>
                        </div>
                        <input type="text" wire:model.live.debounce.300ms="searchQuery"
                               placeholder="Search by test name..."
                               autofocus
                               class="w-full pl-9 pr-4 py-2.5 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent text-sm">
                    </div>

                    @if($searchResults !== null)
                        @if($searchResults->count() > 0)
                        <div class="space-y-2 max-h-80 overflow-y-auto">
                            @foreach($searchResults as $result)
                            @php $alreadyAdded = in_array((string) $result->id, array_map('strval', $selectedTests)); @endphp
                            <div class="flex items-center justify-between p-3 border border-gray-200 rounded-lg {{ $alreadyAdded ? 'bg-blue-50 border-blue-200' : 'hover:bg-gray-50' }}">
                                <div>
                                    <p class="text-sm font-semibold text-gray-900">{{ $result->title }}</p>
                                    <p class="text-xs text-gray-500 mt-0.5">
                                        {{ $result->section }}
                                        @if($result->price)
                                            <span class="ml-2 text-gray-400">{{ $result->price }}</span>
                                        @endif
                                    </p>
                                </div>
                                @if($alreadyAdded)
                                    <span class="inline-flex items-center gap-1 px-3 py-1 text-xs font-medium text-blue-700 bg-blue-100 rounded-lg">
                                        <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                                        Added
                                    </span>
                                @else
                                    <button type="button" wire:click="addTestFromSearch({{ $result->id }})"
                                            class="px-3 py-1 text-xs font-medium text-white bg-blue-500 hover:bg-blue-600 rounded-lg transition-colors">
                                        Add
                                    </button>
                                @endif
                            </div>
                            @endforeach
                        </div>
                        @else
                        <div class="text-center py-8 text-gray-400">
                            <svg class="mx-auto w-10 h-10 mb-2 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                            </svg>
                            <p class="text-sm">No tests found for "{{ $searchQuery }}"</p>
                        </div>
                        @endif
                    @else
                    <div class="text-center py-8 text-gray-400">
                        <svg class="mx-auto w-10 h-10 mb-2 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                        </svg>
                        <p class="text-sm">Start typing to search for tests...</p>
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
    @endif
</div>