<?php

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\TestRequest;
use App\Models\TestRequestItem;
use App\Models\LabTestOrder;
use App\Models\OrderTest;
use App\Models\Patient;
use App\Models\Test;
use App\Traits\LogsActivity;
use Carbon\Carbon;

new class extends Component
{
    use WithPagination, LogsActivity;

    // Filters
    public $search = '';
    public $filterStatus = '';
    public $filterDate = '';
    public $perPage = 10;
    public $flashMessage = '';

    // View Detail Modal
    public bool $showDetail = false;
    public $viewingRequest = null;

    // Reject Modal
    public bool $showRejectModal = false;
    public $rejectRequestId = null;
    public $rejectRemarks = '';

    public function mount()
    {
        if (session()->has('success')) {
            $this->flashMessage = session('success');
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

    public function updatingFilterDate()
    {
        $this->resetPage();
    }

    // View request detail
    public function viewRequest($requestId)
    {
        $this->viewingRequest = TestRequest::with([
            'patient',
            'requestedBy',
            'reviewer',
            'items.test.section',
        ])->find($requestId);
        $this->showDetail = true;
    }

    public function closeDetail()
    {
        $this->showDetail = false;
        $this->viewingRequest = null;
    }

    // Approve request
    public function approveRequest($requestId)
    {
        $request = TestRequest::with(['items.test', 'patient'])->find($requestId);

        if (!$request || $request->status !== 'PENDING') {
            $this->flashMessage = 'Request cannot be approved (already processed).';
            return;
        }

        // Create official lab test order
        $order = LabTestOrder::create([
            'patient_id' => $request->patient_id,
            'physician_id' => null,
            'order_date' => now(),
            'status' => 'pending',
            'remarks' => 'Created from patient test request #' . $request->id,
        ]);

        // Create order tests from request items
        foreach ($request->items as $item) {
            OrderTest::create([
                'order_id' => $order->lab_test_order_id,
                'test_id' => $item->test_id,
                'status' => 'pending',
                'datetime_added' => now(),
            ]);
        }

        // Update request status
        $request->update([
            'status' => 'APPROVED',
            'reviewed_by' => auth()->id(),
            'reviewed_at' => now(),
            'datetime_updated' => now(),
        ]);

        $patientName = $request->patient ? $request->patient->full_name : 'Unknown';
        $this->logActivity("Approved test request #{$request->id} for patient {$patientName} — Lab Test Order #{$order->lab_test_order_id} created");

        $this->flashMessage = "Request #{$request->id} approved! Lab Test Order #{$order->lab_test_order_id} created.";

        // Refresh detail if open
        if ($this->showDetail && $this->viewingRequest && $this->viewingRequest->id == $requestId) {
            $this->viewRequest($requestId);
        }
    }

    // Open reject modal
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

        // Refresh detail if open
        if ($this->showDetail && $this->viewingRequest && $this->viewingRequest->id == $this->rejectRequestId) {
            $this->viewRequest($this->rejectRequestId);
        }
    }

    public function with(): array
    {
        $query = TestRequest::with(['patient', 'requestedBy', 'reviewer', 'items.test'])
            ->when($this->search, function ($query) {
                $query->whereHas('patient', function ($q) {
                    $q->where('firstname', 'like', '%' . $this->search . '%')
                      ->orWhere('lastname', 'like', '%' . $this->search . '%')
                      ->orWhere('email', 'like', '%' . $this->search . '%');
                });
            })
            ->when($this->filterStatus, function ($query) {
                $query->where('status', $this->filterStatus);
            })
            ->when($this->filterDate, function ($query) {
                $query->whereDate('datetime_added', $this->filterDate);
            })
            ->orderByRaw("CASE WHEN status = 'PENDING' THEN 0 ELSE 1 END")
            ->orderBy('datetime_added', 'desc');

        $pendingCount = TestRequest::where('status', 'PENDING')->count();
        $todayCount = TestRequest::whereDate('datetime_added', Carbon::today())->count();
        $approvedCount = TestRequest::where('status', 'APPROVED')->count();
        $rejectedCount = TestRequest::where('status', 'REJECTED')->count();

        return [
            'requests' => $this->perPage === 'all' ? $query->get() : $query->paginate((int)$this->perPage),
            'pendingCount' => $pendingCount,
            'todayCount' => $todayCount,
            'approvedCount' => $approvedCount,
            'rejectedCount' => $rejectedCount,
        ];
    }
};
?>

<div class="p-6">
    {{-- Header --}}
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-gray-900 flex items-center">
            <svg class="w-7 h-7 mr-2 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"/>
            </svg>
            Patient Test Requests
        </h1>
        <p class="text-sm text-gray-500 mt-1">Review, approve, or reject patient laboratory test requests</p>
    </div>

    {{-- Flash Message --}}
    @if($flashMessage)
    <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 4000)"
         x-transition:leave="transition ease-in duration-300" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
         class="mb-6 bg-white border-l-4 border-green-500 shadow-sm rounded-lg p-4 flex items-center justify-between">
        <div class="flex items-center">
            <svg class="w-5 h-5 text-green-500 mr-3" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
            </svg>
            <span class="text-sm font-medium text-gray-900">{{ $flashMessage }}</span>
        </div>
        <button @click="show = false" class="text-gray-400 hover:text-gray-600"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg></button>
    </div>
    @endif

    {{-- Stats Cards --}}
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-5">
            <div class="flex items-center">
                <div class="flex-shrink-0 p-3 bg-amber-100 rounded-lg">
                    <svg class="w-6 h-6 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-500">Pending</p>
                    <p class="text-2xl font-bold text-gray-900">{{ $pendingCount }}</p>
                </div>
            </div>
        </div>
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-5">
            <div class="flex items-center">
                <div class="flex-shrink-0 p-3 bg-blue-100 rounded-lg">
                    <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                    </svg>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-500">Today</p>
                    <p class="text-2xl font-bold text-gray-900">{{ $todayCount }}</p>
                </div>
            </div>
        </div>
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-5">
            <div class="flex items-center">
                <div class="flex-shrink-0 p-3 bg-emerald-100 rounded-lg">
                    <svg class="w-6 h-6 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-500">Approved</p>
                    <p class="text-2xl font-bold text-gray-900">{{ $approvedCount }}</p>
                </div>
            </div>
        </div>
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-5">
            <div class="flex items-center">
                <div class="flex-shrink-0 p-3 bg-red-100 rounded-lg">
                    <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-500">Rejected</p>
                    <p class="text-2xl font-bold text-gray-900">{{ $rejectedCount }}</p>
                </div>
            </div>
        </div>
    </div>

    {{-- Main Table Card --}}
    <div class="bg-white rounded-lg shadow-sm border border-gray-200">
        {{-- Filters --}}
        <div class="px-6 py-4 border-b border-gray-200 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
            <h2 class="text-lg font-semibold text-gray-900">All Requests</h2>
            <div class="flex items-center gap-3">
                <select wire:model.live="filterStatus" class="rounded-lg border-gray-200 text-sm focus:border-blue-500 focus:ring-blue-500 py-2">
                    <option value="">All Status</option>
                    <option value="PENDING">Pending</option>
                    <option value="APPROVED">Approved</option>
                    <option value="REJECTED">Rejected</option>
                    <option value="CANCELLED">Cancelled</option>
                </select>
                <input type="date" wire:model.live="filterDate" class="rounded-lg border-gray-200 text-sm focus:border-blue-500 focus:ring-blue-500 py-2">
                <div class="relative">
                    <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                    <input wire:model.live.debounce.300ms="search" type="text" placeholder="Search patient..."
                        class="pl-10 pr-4 py-2 rounded-lg border-gray-200 text-sm focus:border-blue-500 focus:ring-blue-500 w-52">
                </div>
                <select wire:model.live="perPage" class="rounded-lg border-gray-200 text-sm focus:border-blue-500 focus:ring-blue-500 py-2 w-20">
                    <option value="10">10</option>
                    <option value="25">25</option>
                    <option value="50">50</option>
                    <option value="all">All</option>
                </select>
            </div>
        </div>

        {{-- Table --}}
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Request</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Patient</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Tests</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Purpose</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Preferred Date</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Status</th>
                        <th class="px-6 py-3 text-center text-xs font-semibold text-gray-500 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($requests as $request)
                    <tr class="hover:bg-gray-50 transition-colors {{ $request->status === 'PENDING' ? 'bg-amber-50/30' : '' }}">
                        <td class="px-6 py-4 whitespace-nowrap">
                            <p class="text-sm font-semibold text-gray-900">#{{ $request->id }}</p>
                            <p class="text-xs text-gray-500">{{ $request->datetime_added->format('M d, Y') }}</p>
                            <p class="text-xs text-gray-400">{{ $request->datetime_added->format('h:i A') }}</p>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            @if($request->patient)
                            <div class="flex items-center gap-3">
                                <div class="w-9 h-9 rounded-full bg-blue-100 flex items-center justify-center text-blue-600 text-xs font-bold">
                                    {{ strtoupper(substr($request->patient->firstname, 0, 1) . substr($request->patient->lastname, 0, 1)) }}
                                </div>
                                <div>
                                    <p class="text-sm font-medium text-gray-900">{{ $request->patient->full_name }}</p>
                                    <p class="text-xs text-gray-500">{{ $request->patient->patient_type ?? '' }}</p>
                                </div>
                            </div>
                            @else
                            <span class="text-sm text-gray-400">Unknown</span>
                            @endif
                        </td>
                        <td class="px-6 py-4">
                            <div class="flex flex-wrap gap-1 max-w-xs">
                                @foreach($request->items->take(3) as $item)
                                <span class="inline-flex px-2 py-0.5 bg-gray-100 text-gray-700 text-xs rounded-md font-medium">{{ $item->test->label ?? 'N/A' }}</span>
                                @endforeach
                                @if($request->items->count() > 3)
                                <span class="inline-flex px-2 py-0.5 bg-blue-50 text-blue-600 text-xs rounded-md font-medium">+{{ $request->items->count() - 3 }} more</span>
                                @endif
                            </div>
                        </td>
                        <td class="px-6 py-4">
                            <p class="text-sm text-gray-600 max-w-xs truncate">{{ $request->purpose ?: '—' }}</p>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <p class="text-sm text-gray-600">{{ $request->preferred_date ? $request->preferred_date->format('M d, Y') : '—' }}</p>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="inline-flex px-2.5 py-1 text-xs font-semibold rounded-full
                                {{ $request->status === 'PENDING' ? 'bg-amber-100 text-amber-700' : '' }}
                                {{ $request->status === 'APPROVED' ? 'bg-emerald-100 text-emerald-700' : '' }}
                                {{ $request->status === 'REJECTED' ? 'bg-red-100 text-red-700' : '' }}
                                {{ $request->status === 'CANCELLED' ? 'bg-gray-100 text-gray-500' : '' }}">
                                {{ $request->status }}
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-center">
                            <div class="flex items-center justify-center gap-1">
                                <button wire:click="viewRequest({{ $request->id }})"
                                        class="p-2 text-gray-400 hover:text-blue-600 hover:bg-blue-50 rounded-lg transition-colors"
                                        title="View Details">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                </button>
                                @if($request->status === 'PENDING')
                                <button wire:click="approveRequest({{ $request->id }})"
                                        wire:confirm="Approve this request? This will create an official lab test order."
                                        class="p-2 text-gray-400 hover:text-emerald-600 hover:bg-emerald-50 rounded-lg transition-colors"
                                        title="Approve">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                </button>
                                <button wire:click="openRejectModal({{ $request->id }})"
                                        class="p-2 text-gray-400 hover:text-red-600 hover:bg-red-50 rounded-lg transition-colors"
                                        title="Reject">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                </button>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="px-6 py-12 text-center">
                            <div class="flex flex-col items-center">
                                <svg class="w-12 h-12 text-gray-300 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                                <p class="text-sm text-gray-500 font-medium">No test requests found</p>
                                <p class="text-xs text-gray-400 mt-1">Requests submitted by patients will appear here</p>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Pagination --}}
        @if($perPage !== 'all' && $requests instanceof \Illuminate\Pagination\LengthAwarePaginator && $requests->hasPages())
        <div class="px-6 py-4 border-t border-gray-200">
            {{ $requests->links() }}
        </div>
        @endif
    </div>

    {{-- ===== VIEW DETAIL MODAL ===== --}}
    @if($showDetail && $viewingRequest)
    <div class="fixed inset-0 bg-black/40 backdrop-blur-sm flex items-center justify-center z-50 p-4" wire:click.self="closeDetail">
        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-2xl max-h-[90vh] flex flex-col overflow-hidden">
            {{-- Header --}}
            <div class="px-6 py-4 flex items-center justify-between flex-shrink-0
                {{ $viewingRequest->status === 'PENDING' ? 'bg-amber-500' : '' }}
                {{ $viewingRequest->status === 'APPROVED' ? 'bg-emerald-500' : '' }}
                {{ $viewingRequest->status === 'REJECTED' ? 'bg-red-500' : '' }}
                {{ $viewingRequest->status === 'CANCELLED' ? 'bg-gray-500' : '' }}">
                <div class="flex items-center gap-3">
                    <div class="w-9 h-9 rounded-xl bg-white/20 flex items-center justify-center">
                        <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                    </div>
                    <div>
                        <h3 class="text-lg font-bold text-white">Request #{{ $viewingRequest->id }}</h3>
                        <p class="text-sm text-white/70">{{ $viewingRequest->status }}</p>
                    </div>
                </div>
                <button wire:click="closeDetail" class="w-8 h-8 flex items-center justify-center rounded-lg text-white/60 hover:text-white hover:bg-white/20 transition-colors">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>

            {{-- Body --}}
            <div class="flex-1 overflow-y-auto p-6 space-y-5">
                {{-- Patient Info --}}
                <div class="bg-gray-50 rounded-xl p-4">
                    <h4 class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-3">Patient Information</h4>
                    @if($viewingRequest->patient)
                    <div class="flex items-center gap-3 mb-3">
                        <div class="w-11 h-11 rounded-full bg-blue-100 flex items-center justify-center text-blue-600 text-sm font-bold">
                            {{ strtoupper(substr($viewingRequest->patient->firstname, 0, 1) . substr($viewingRequest->patient->lastname, 0, 1)) }}
                        </div>
                        <div>
                            <p class="text-sm font-semibold text-gray-900">{{ $viewingRequest->patient->full_name }}</p>
                            <p class="text-xs text-gray-500">{{ $viewingRequest->patient->patient_type ?? '' }} &middot; {{ $viewingRequest->patient->email ?? '' }}</p>
                        </div>
                    </div>
                    <div class="grid grid-cols-2 gap-3 text-sm">
                        <div>
                            <span class="text-gray-500">Gender:</span>
                            <span class="text-gray-900 font-medium ml-1">{{ $viewingRequest->patient->gender ?: 'N/A' }}</span>
                        </div>
                        <div>
                            <span class="text-gray-500">Contact:</span>
                            <span class="text-gray-900 font-medium ml-1">{{ $viewingRequest->patient->contact_number ?: 'N/A' }}</span>
                        </div>
                    </div>
                    @endif
                </div>

                {{-- Request Details --}}
                <div class="grid grid-cols-2 gap-4 text-sm">
                    <div>
                        <span class="text-gray-500">Submitted:</span>
                        <p class="text-gray-900 font-medium">{{ $viewingRequest->datetime_added->format('M d, Y h:i A') }}</p>
                    </div>
                    <div>
                        <span class="text-gray-500">Preferred Date:</span>
                        <p class="text-gray-900 font-medium">{{ $viewingRequest->preferred_date ? $viewingRequest->preferred_date->format('M d, Y') : 'Not specified' }}</p>
                    </div>
                </div>

                @if($viewingRequest->purpose)
                <div>
                    <span class="text-sm text-gray-500">Purpose:</span>
                    <p class="text-sm text-gray-900 mt-1">{{ $viewingRequest->purpose }}</p>
                </div>
                @endif

                {{-- Requested Tests --}}
                <div>
                    <h4 class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-3">Requested Tests ({{ $viewingRequest->items->count() }})</h4>
                    <div class="border border-gray-200 rounded-xl overflow-hidden">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-4 py-2.5 text-left text-xs font-semibold text-gray-500">#</th>
                                    <th class="px-4 py-2.5 text-left text-xs font-semibold text-gray-500">Test Name</th>
                                    <th class="px-4 py-2.5 text-left text-xs font-semibold text-gray-500">Section</th>
                                    <th class="px-4 py-2.5 text-right text-xs font-semibold text-gray-500">Price</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                @foreach($viewingRequest->items as $idx => $item)
                                <tr>
                                    <td class="px-4 py-2.5 text-sm text-gray-500">{{ $idx + 1 }}</td>
                                    <td class="px-4 py-2.5 text-sm font-medium text-gray-900">{{ $item->test->label ?? 'Unknown' }}</td>
                                    <td class="px-4 py-2.5 text-sm text-gray-500">{{ $item->test->section->section_name ?? '—' }}</td>
                                    <td class="px-4 py-2.5 text-sm text-gray-600 text-right">{{ $item->test->current_price ? '₱' . number_format($item->test->current_price, 2) : '—' }}</td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>

                {{-- Staff Remarks --}}
                @if($viewingRequest->staff_remarks)
                <div class="bg-red-50 border border-red-100 rounded-xl p-4">
                    <h4 class="text-xs font-semibold text-red-600 uppercase tracking-wider mb-1">Staff Remarks</h4>
                    <p class="text-sm text-red-700">{{ $viewingRequest->staff_remarks }}</p>
                </div>
                @endif

                {{-- Review Info --}}
                @if($viewingRequest->reviewer)
                <div class="text-xs text-gray-400 flex items-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                    Reviewed by {{ $viewingRequest->reviewer->name }} on {{ $viewingRequest->reviewed_at->format('M d, Y h:i A') }}
                </div>
                @endif
            </div>

            {{-- Footer --}}
            <div class="px-6 py-4 bg-gray-50 border-t border-gray-200 flex items-center justify-between flex-shrink-0">
                <button wire:click="closeDetail" class="px-5 py-2.5 text-sm font-medium text-gray-700 bg-white border border-gray-200 hover:bg-gray-100 rounded-xl transition-colors">
                    Close
                </button>
                @if($viewingRequest->status === 'PENDING')
                <div class="flex items-center gap-2">
                    <button wire:click="openRejectModal({{ $viewingRequest->id }})"
                            class="inline-flex items-center gap-2 px-4 py-2.5 text-sm font-medium text-red-600 bg-red-50 hover:bg-red-100 rounded-xl transition-colors">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        Reject
                    </button>
                    <button wire:click="approveRequest({{ $viewingRequest->id }})"
                            wire:confirm="Approve this request? This will create an official lab test order."
                            class="inline-flex items-center gap-2 px-5 py-2.5 text-sm font-medium text-white bg-emerald-500 hover:bg-emerald-600 rounded-xl shadow-sm shadow-emerald-500/25 transition-all">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                        Approve & Create Order
                    </button>
                </div>
                @endif
            </div>
        </div>
    </div>
    @endif

    {{-- ===== REJECT MODAL ===== --}}
    @if($showRejectModal)
    <div class="fixed inset-0 bg-black/40 backdrop-blur-sm flex items-center justify-center z-[60] p-4" wire:click.self="closeRejectModal">
        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md overflow-hidden">
            <div class="px-6 py-4 bg-red-500 flex items-center gap-3">
                <div class="w-9 h-9 rounded-xl bg-white/20 flex items-center justify-center">
                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </div>
                <h3 class="text-lg font-bold text-white">Reject Request</h3>
            </div>
            <div class="p-6">
                <p class="text-sm text-gray-600 mb-4">Please provide a reason for rejecting this request. The patient will see this message.</p>
                <textarea wire:model="rejectRemarks" rows="4" placeholder="Enter reason for rejection..."
                    class="w-full rounded-xl border-gray-200 focus:border-red-500 focus:ring-red-500 text-sm px-4 py-3 placeholder-gray-400"></textarea>
                @error('rejectRemarks') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
            </div>
            <div class="px-6 py-4 bg-gray-50 border-t border-gray-200 flex items-center justify-end gap-2">
                <button wire:click="closeRejectModal" class="px-4 py-2.5 text-sm font-medium text-gray-700 bg-white border border-gray-200 hover:bg-gray-100 rounded-xl transition-colors">
                    Cancel
                </button>
                <button wire:click="rejectRequest"
                        class="inline-flex items-center gap-2 px-5 py-2.5 text-sm font-medium text-white bg-red-500 hover:bg-red-600 rounded-xl shadow-sm shadow-red-500/25 transition-all"
                        wire:loading.attr="disabled">
                    <svg wire:loading wire:target="rejectRequest" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
                    Reject Request
                </button>
            </div>
        </div>
    </div>
    @endif
</div>
