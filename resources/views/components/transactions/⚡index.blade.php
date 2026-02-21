<?php

use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Validate;
use App\Models\Transaction;
use App\Models\Patient;
use App\Models\LabTestOrder;
use App\Models\Employee;
use App\Traits\LogsActivity;

new class extends Component
{
    use WithPagination, LogsActivity;

    #[Validate('required|exists:patient,patient_id')]
    public $client_id = '';

    #[Validate('required|string|max:50')]
    public $or_number = '';

    // PAY-FIRST: New payment fields
    public $selected_order_id = '';
    public $payment_method = 'cash';
    public $amount = '';
    public $unpaidOrders = [];

    public $search = '';
    public $perPage = 'all';
    public $flashMessage = '';

    // UPDATED: New delete confirmation modal properties
    public $showDeleteModal = false;
    public $deleteMessage = '';
    public $deleteAction = '';
    public $transactionsToDelete = [];

    // Selection properties
    public $selectedTransactions = [];
    public $selectAll = false;

    // UPDATED: Changed to showEditModal and new property names
    public $showEditModal = false;
    public $editingTransactionId = null;

    // Form visibility toggle
    public bool $showForm = false;

    // View modal
    public $showViewModal = false;
    public $viewingTransaction = null;
    public $editMode = false;

    // Edit modal payment fields
    public $editClientId = '';
    public $editOrNumber = '';
    public $editPaymentMethod = 'cash';
    public $editAmount = '';
    public $editSelectedOrderId = '';

    public function mount()
    {
        if (session()->has('success')) {
            $this->flashMessage = session('success');
        }
    }

    /**
     * PAY-FIRST: When patient changes, load their unpaid orders.
     */
    public function updatedClientId($value)
    {
        $this->selected_order_id = '';
        $this->amount = '';
        $this->unpaidOrders = [];

        if ($value) {
            $this->unpaidOrders = LabTestOrder::with('orderTests.test')
                ->where('patient_id', $value)
                ->where('payment_status', 'PENDING_PAYMENT')
                ->where('status', '!=', 'cancelled')
                ->orderBy('order_date', 'desc')
                ->get()
                ->toArray();
        }
    }

    /**
     * PAY-FIRST: When order is selected, auto-fill the amount.
     */
    public function updatedSelectedOrderId($value)
    {
        if ($value) {
            $order = LabTestOrder::find($value);
            if ($order) {
                $this->amount = $order->total_amount ?? $order->calculateTotalAmount();
            }
        } else {
            $this->amount = '';
        }
    }

public function updatedSelectAll($value)
    {
        if ($value) {
            $query = Transaction::with('patient')
                ->when($this->search, function ($query) {
                    $query->where('or_number', 'like', '%' . $this->search . '%')
                          ->orWhereHas('patient', function($q) {
                              $q->where('firstname', 'like', '%' . $this->search . '%')
                                ->orWhere('lastname', 'like', '%' . $this->search . '%');
                          });
                })
                ->orderBy('transaction_id', 'desc');

            $transactions = $this->perPage === 'all' ? $query->get() : $query->paginate((int)$this->perPage);
            $this->selectedTransactions = $transactions->pluck('transaction_id')
                ->map(fn($id) => (string)$id)->toArray();
        } else {
            $this->selectedTransactions = [];
        }
    }

    public function updatedSelectedTransactions()
    {
        $this->selectAll = false;
    }

    public function updatedSearch()
    {
        $this->selectedTransactions = [];
        $this->selectAll = false;
        $this->resetPage();
    }

    // UPDATED: Delete selected using selectedTransactions - now shows modal
    public function deleteSelected()
    {
        if (empty($this->selectedTransactions)) return;
        
        $count = count($this->selectedTransactions);
        $this->deleteMessage = "Are you sure you want to delete {$count} selected transaction(s)? This action cannot be undone.";
        $this->deleteAction = 'confirmDeleteSelected';
        $this->transactionsToDelete = $this->selectedTransactions;
        $this->showDeleteModal = true;
    }

    // UPDATED: New method to confirm delete
    public function confirmDeleteSelected()
    {
        if (empty($this->transactionsToDelete)) return;
        
        $count = Transaction::whereIn('transaction_id', $this->transactionsToDelete)->delete();
        $this->flashMessage = $count . ' transaction(s) deleted successfully!';
        $this->dispatch('transaction-saved');
        $this->selectedTransactions = [];
        $this->selectAll = false;
        $this->resetPage();
        $this->closeDeleteModal();
    }

    // UPDATED: New method to close delete modal
    public function closeDeleteModal()
    {
        $this->showDeleteModal = false;
        $this->deleteMessage = '';
        $this->deleteAction = '';
        $this->transactionsToDelete = [];
    }

    // UPDATED: New openEditModal method
    public function openEditModal($transactionId)
    {
        $transaction = Transaction::with('patient')->findOrFail($transactionId);
        $this->editingTransactionId = $transactionId;
        $this->editClientId = $transaction->client_id;
        $this->editOrNumber = $transaction->or_number;
        $this->showEditModal = true;
        $this->resetErrorBag();
    }

    // UPDATED: New closeEditModal method
    public function closeEditModal()
    {
        $this->showEditModal = false;
        $this->editingTransactionId = null;
        $this->editClientId = '';
        $this->editOrNumber = '';
        $this->resetErrorBag();
    }

    // UPDATED: New updateTransaction method
    public function updateTransaction()
    {
        $this->validate([
            'editClientId' => 'required|exists:patient,patient_id',
            'editOrNumber' => 'required|string|max:50',
        ], [
            'editClientId.required' => 'The patient field is required.',
            'editClientId.exists' => 'The selected patient is invalid.',
            'editOrNumber.required' => 'The OR number field is required.',
        ]);

        $transaction = Transaction::findOrFail($this->editingTransactionId);
        $transaction->update([
            'client_id' => $this->editClientId,
            'or_number' => $this->editOrNumber,
        ]);

        $this->flashMessage = 'Transaction updated successfully!';
        $this->dispatch('transaction-saved');
        $this->closeEditModal();
    }

    public function toggleForm()
    {
        $this->showForm = !$this->showForm;
        if (!$this->showForm) {
            $this->reset(['client_id', 'or_number', 'selected_order_id', 'payment_method', 'amount', 'unpaidOrders']);
            $this->resetErrorBag();
        }
    }

    public function save()
    {
        $rules = [
            'client_id' => 'required|exists:patient,patient_id',
            'or_number' => 'required|string|max:50',
            'payment_method' => 'required|in:cash,gcash,bank_transfer,check,other',
        ];

        // If an order is selected, amount is required
        if ($this->selected_order_id) {
            $rules['amount'] = 'required|numeric|min:0';
            $rules['selected_order_id'] = 'exists:lab_test_order,lab_test_order_id';
        } else {
            $rules['amount'] = 'nullable|numeric|min:0';
        }

        $this->validate($rules);

        if (Transaction::where('or_number', $this->or_number)->exists()) {
            $this->addError('or_number', 'A transaction with this OR number already exists.');
            return;
        }

        // Get the current employee (processed_by)
        $processedBy = auth()->user()?->employee?->employee_id;

        $transaction = Transaction::create([
            'client_id' => $this->client_id,
            'or_number' => $this->or_number,
            'datetime_added' => now(),
            'status_code' => 1,
            'lab_test_order_id' => $this->selected_order_id ?: null,
            'amount' => $this->amount ?: null,
            'payment_method' => $this->payment_method,
            'processed_by' => $processedBy,
            'paid_at' => now(),
        ]);

        // PAY-FIRST: If a test order was selected, mark it as PAID
        if ($this->selected_order_id) {
            $order = LabTestOrder::find($this->selected_order_id);
            if ($order) {
                $order->markAsPaid($transaction->transaction_id);
                $this->logActivity("Recorded payment for Lab Test Order #{$this->selected_order_id} — OR#{$this->or_number}, Amount: ₱" . number_format($this->amount, 2) . ", Method: {$this->payment_method}");
            }
        } else {
            $this->logActivity("Created transaction OR#{$this->or_number}");
        }

        $this->flashMessage = $this->selected_order_id 
            ? 'Payment recorded successfully! Lab results can now be encoded for this order.'
            : 'Transaction added successfully!';
        $this->dispatch('transaction-saved');

        $this->reset(['client_id', 'or_number', 'selected_order_id', 'payment_method', 'amount', 'unpaidOrders']);
        $this->showForm = false;
        $this->resetPage();
    }

    public function showDetails($id)
    {
        $this->viewingTransaction = Transaction::with(['patient', 'labTestOrder.orderTests.test', 'processedByEmployee'])->findOrFail($id);
        $this->showViewModal = true;
        $this->editMode = false;
        $this->resetErrorBag();
    }

    public function enableEdit()
    {
        $transaction = $this->viewingTransaction;
        $this->editMode = true;
        $this->editingTransactionId = $transaction->transaction_id;
        $this->client_id = $transaction->client_id;
        $this->or_number = $transaction->or_number;
    }

    public function update()
    {
        $this->validate();

        if (Transaction::where('or_number', $this->or_number)
            ->where('transaction_id', '!=', $this->editingTransactionId)
            ->exists()) {
            $this->addError('or_number', 'A transaction with this OR number already exists.');
            return;
        }

        $transaction = Transaction::findOrFail($this->editingTransactionId);
        $transaction->update([
            'client_id' => $this->client_id,
            'or_number' => $this->or_number,
        ]);

        $this->logActivity("Updated transaction ID {$this->editingTransactionId}: OR#{$this->or_number}");
        $this->flashMessage = 'Transaction updated successfully!';
        $this->dispatch('transaction-saved');
        $this->viewingTransaction = $transaction->fresh()->load('patient');
        $this->editMode = false;
        $this->editingTransactionId = null;
        $this->reset(['client_id', 'or_number']);
        $this->resetErrorBag();
    }

    public function cancelEdit()
    {
        $this->editMode = false;
        $this->editingTransactionId = null;
        $this->reset(['client_id', 'or_number']);
        $this->resetErrorBag();
    }

    public function closeViewModal()
    {
        $this->showViewModal = false;
        $this->viewingTransaction = null;
        $this->editMode = false;
        $this->editingTransactionId = null;
        $this->reset(['client_id', 'or_number']);
        $this->resetErrorBag();
    }

    public function delete($id)
    {
        $transaction = Transaction::find($id);
        if ($transaction) {
            $transaction->delete();
            $this->logActivity("Deleted transaction ID {$id}");
            $this->flashMessage = 'Transaction deleted successfully!';
            $this->dispatch('transaction-saved');
            $this->resetPage();
        }
    }

    public function with(): array
    {
        $query = Transaction::with(['patient', 'labTestOrder', 'processedByEmployee'])
            ->when($this->search, function ($query) {
                $query->where('or_number', 'like', '%' . $this->search . '%')
                      ->orWhereHas('patient', function($q) {
                          $q->where('firstname', 'like', '%' . $this->search . '%')
                            ->orWhere('lastname', 'like', '%' . $this->search . '%');
                      });
            })
            ->orderBy('transaction_id', 'desc');

        return [
            'transactions' => $this->perPage === 'all' ? $query->get() : $query->paginate((int)$this->perPage),
            'patients' => Patient::active()->orderBy('lastname')->get(),
        ];
    }
};
?>

<div class="p-6" x-data="{ 
    showToast: false,
    toastTimeout: null,
    showToastMessage() {
        this.showToast = true;
        if (this.toastTimeout) {
            clearTimeout(this.toastTimeout);
        }
        this.toastTimeout = setTimeout(() => {
            this.showToast = false;
        }, 3000);
    }
}" x-init="$wire.on('transaction-saved', () => showToastMessage())">
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-gray-900 flex items-center">
            <svg class="w-7 h-7 mr-2 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
            </svg>
            Transaction Management
        </h1>
    </div>

    @if($flashMessage)
        <div class="mb-6 bg-white border-l-4 border-green-500 shadow-sm rounded-lg p-4 flex items-center justify-between" x-show="showToast" x-transition>
            <div class="flex items-center">
                <svg class="w-5 h-5 text-green-500 mr-3" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                </svg>
                <span class="text-sm font-medium text-gray-900">{{ $flashMessage }}</span>
            </div>
        </div>
    @endif

    <div class="bg-white rounded-lg shadow-sm mb-6">
        <div class="px-6 py-4 border-b border-gray-200 flex items-center justify-between">
            <h2 class="text-lg font-semibold text-gray-900">Add New Transaction</h2>
            <button type="button" wire:click="toggleForm" 
                    class="px-4 py-2 rounded-md text-sm font-medium transition-colors {{ $showForm ? 'bg-gray-200 text-gray-700 hover:bg-gray-300' : 'bg-pink-600 text-white hover:bg-pink-700' }}">
                {{ $showForm ? 'Close Form' : 'Add New Transaction' }}
            </button>
        </div>
        @if($showForm)
        <form wire:submit.prevent="save" class="p-6">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
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
                        $wire.set('client_id', item.id);
                        this.selectedLabel = item.name;
                        this.search = '';
                        this.open = false;
                    },
                    clear() {
                        $wire.set('client_id', '');
                        this.selectedLabel = '';
                        this.search = '';
                    },
                    init() {
                        let val = $wire.get('client_id');
                        if (val) {
                            let found = this.items.find(i => String(i.id) === String(val));
                            if (found) this.selectedLabel = found.name;
                        }
                    }
                }" @click.away="open = false" class="relative">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Patient *</label>
                    <div @click="open = !open" class="w-full px-3 py-2 border border-gray-300 rounded-md focus-within:ring-2 focus-within:ring-purple-500 cursor-pointer bg-white flex items-center justify-between">
                        <span x-show="selectedLabel" x-text="selectedLabel" class="text-gray-900 truncate"></span>
                        <span x-show="!selectedLabel" class="text-gray-400">Select Patient</span>
                        <div class="flex items-center gap-1">
                            <button x-show="selectedLabel" @click.stop="clear()" type="button" class="text-gray-400 hover:text-red-500">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                            </button>
                            <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                        </div>
                    </div>
                    <div x-show="open" x-transition class="absolute z-50 mt-1 w-full bg-white border border-gray-300 rounded-lg shadow-lg max-h-60 overflow-hidden">
                        <div class="p-2 border-b border-gray-200">
                            <input type="text" x-model="search" @click.stop placeholder="Search patients..." 
                                   class="w-full px-3 py-1.5 border border-gray-300 rounded-md text-sm focus:outline-none focus:ring-1 focus:ring-purple-500" autocomplete="off">
                        </div>
                        <ul class="overflow-y-auto max-h-48">
                            <template x-for="item in filtered" :key="item.id">
                                <li @click="select(item)" class="px-4 py-2 text-sm hover:bg-purple-50 cursor-pointer" x-text="item.name"></li>
                            </template>
                            <li x-show="filtered.length === 0" class="px-4 py-2 text-sm text-gray-400">No results found</li>
                        </ul>
                    </div>
                    @error('client_id') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">OR Number *</label>
                    <input type="text" wire:model="or_number" 
                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-purple-500">
                    @error('or_number') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                </div>
            </div>

            {{-- PAY-FIRST: Unpaid Orders Selection --}}
            @if(count($unpaidOrders) > 0)
            <div class="mt-4 p-4 bg-orange-50 border border-orange-200 rounded-lg">
                <label class="block text-sm font-semibold text-orange-800 mb-2">
                    <svg class="w-4 h-4 inline mr-1 -mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    Link to Unpaid Test Order (Pay-First)
                </label>
                <select wire:model.live="selected_order_id"
                        class="w-full px-3 py-2 border border-orange-300 rounded-md focus:outline-none focus:ring-2 focus:ring-purple-500 text-sm bg-white">
                    <option value="">-- No order (general transaction) --</option>
                    @foreach($unpaidOrders as $uo)
                        <option value="{{ $uo['lab_test_order_id'] }}">
                            Order #{{ $uo['lab_test_order_id'] }} — {{ \Carbon\Carbon::parse($uo['order_date'])->format('M d, Y') }} — {{ count($uo['order_tests'] ?? []) }} test(s) — ₱{{ number_format($uo['total_amount'] ?? 0, 2) }}
                        </option>
                    @endforeach
                </select>
                @error('selected_order_id') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
            </div>
            @elseif($client_id)
            <div class="mt-4 p-3 bg-green-50 border border-green-200 rounded-lg">
                <p class="text-sm text-green-700 flex items-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    This patient has no unpaid test orders. You can still create a general transaction.
                </p>
            </div>
            @endif

            {{-- Payment Details --}}
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Payment Method *</label>
                    <select wire:model="payment_method"
                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-purple-500">
                        <option value="cash">Cash</option>
                        <option value="gcash">GCash</option>
                        <option value="bank_transfer">Bank Transfer</option>
                        <option value="check">Check</option>
                        <option value="other">Other</option>
                    </select>
                    @error('payment_method') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Amount{{ $selected_order_id ? ' *' : '' }}</label>
                    <div class="relative">
                        <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-gray-500 text-sm">₱</span>
                        <input type="number" step="0.01" wire:model="amount" placeholder="0.00"
                               class="w-full pl-7 pr-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-purple-500">
                    </div>
                    @error('amount') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                </div>
            </div>

            <div class="flex justify-end mt-4">
                <button type="submit" 
                        class="px-4 py-2 bg-purple-600 text-white rounded-md hover:bg-purple-700 transition-colors flex items-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    {{ $selected_order_id ? 'Record Payment' : 'Add Transaction' }}
                </button>
            </div>
        </form>
        @endif
    </div>

    <div class="bg-white rounded-lg shadow-sm">
        <div class="px-6 py-4 border-b border-gray-200">
            <div class="flex items-center justify-between">
                <h2 class="text-lg font-semibold text-gray-900">Transactions List</h2>
                {{-- UPDATED: Delete button moved next to title --}}
                @if(count($selectedTransactions) > 0)
                <button wire:click="deleteSelected" 
                        class="inline-flex items-center gap-2 px-4 py-2 bg-red-500 hover:bg-red-600 text-white text-sm font-medium rounded-lg transition-colors">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                    </svg>
                    Delete Selected ({{ count($selectedTransactions) }})
                </button>
                @endif
            </div>
        </div>
        <div class="p-6">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Rows per page</label>
                    <select wire:model.live="perPage" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-purple-500">
                        <option value="10">10</option>
                        <option value="25">25</option>
                        <option value="50">50</option>
                        <option value="100">100</option>
                        <option value="all">All</option>
                    </select>
                </div>
                <div class="md:col-span-2">
                    <!-- Empty space for layout balance -->
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Search Transactions</label>
                    <input type="text" wire:model.live="search" placeholder="Search by OR number or patient name..." 
                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-purple-500" style="min-width: 280px;">
                </div>
            </div>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left w-10">
                            <input type="checkbox" wire:model.live="selectAll"
                                   class="rounded border-gray-300 text-purple-600 focus:ring-purple-500 h-4 w-4">
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">OR Number</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Patient</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Order #</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Amount</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Method</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($transactions as $transaction)
                        <tr wire:key="transaction-{{ $transaction->transaction_id }}" 
                            class="hover:bg-gray-50 cursor-pointer transition-colors {{ in_array((string) $transaction->transaction_id, $selectedTransactions) ? 'bg-purple-50' : '' }}"
                            wire:click="showDetails({{ $transaction->transaction_id }})">
                            <td class="px-6 py-4" wire:click.stop>
                                <input type="checkbox" wire:model.live="selectedTransactions" value="{{ $transaction->transaction_id }}"
                                       class="rounded border-gray-300 text-purple-600 focus:ring-purple-500 h-4 w-4">
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">{{ $transaction->or_number }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                                {{ $transaction->patient->full_name ?? 'N/A' }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm">
                                @if($transaction->lab_test_order_id)
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-blue-100 text-blue-800">
                                        #{{ $transaction->lab_test_order_id }}
                                    </span>
                                @else
                                    <span class="text-gray-400">—</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                @if($transaction->amount)
                                    ₱{{ number_format($transaction->amount, 2) }}
                                @else
                                    <span class="text-gray-400">—</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                                @if($transaction->payment_method)
                                    <span class="capitalize">{{ str_replace('_', ' ', $transaction->payment_method) }}</span>
                                @else
                                    <span class="text-gray-400">—</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                                {{ $transaction->datetime_added ? \Carbon\Carbon::parse($transaction->datetime_added)->format('M d, Y') : 'N/A' }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-6 py-4 text-center text-gray-500">No transactions found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
            
        @if($perPage !== 'all' && method_exists($transactions, 'hasPages') && $transactions->hasPages())
            <div class="px-6 py-4 border-t border-gray-200">
                {{ $transactions->links() }}
            </div>
        @endif
    </div>

    <!-- View / Edit Transaction Modal -->
    @if($showViewModal && $viewingTransaction)
    <div class="fixed inset-0 z-50 overflow-y-auto" style="background-color: rgba(0, 0, 0, 0.5);">
        <div class="flex items-center justify-center min-h-screen p-4">
            <div class="bg-white rounded-lg shadow-xl max-w-lg w-full">
                <!-- Modal Header -->
                <div class="px-6 py-4 border-b border-gray-200">
                    <div class="flex items-center justify-between">
                        <h3 class="text-xl font-semibold text-gray-900">
                            {{ $editMode ? 'Edit Transaction' : 'Transaction Details' }}
                        </h3>
                        <button type="button" wire:click="closeViewModal" class="text-gray-400 hover:text-gray-600">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </button>
                    </div>
                </div>

                @if($editMode)
                <!-- Edit Mode -->
                <form wire:submit.prevent="update">
                    <div class="p-6">
                        <div class="grid grid-cols-1 gap-5">
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
                                    $wire.set('client_id', item.id);
                                    this.selectedLabel = item.name;
                                    this.search = '';
                                    this.open = false;
                                },
                                clear() {
                                    $wire.set('client_id', '');
                                    this.selectedLabel = '';
                                    this.search = '';
                                },
                                init() {
                                    let val = $wire.get('client_id');
                                    if (val) {
                                        let found = this.items.find(i => String(i.id) === String(val));
                                        if (found) this.selectedLabel = found.name;
                                    }
                                }
                            }" @click.away="open = false" class="relative">
                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                    Patient <span class="text-red-500">*</span>
                                </label>
                                <div @click="open = !open" class="w-full px-3 py-2.5 border border-gray-300 rounded-lg focus-within:ring-2 focus-within:ring-purple-500 cursor-pointer bg-white flex items-center justify-between">
                                    <span x-show="selectedLabel" x-text="selectedLabel" class="text-gray-900 truncate"></span>
                                    <span x-show="!selectedLabel" class="text-gray-400">Select Patient</span>
                                    <div class="flex items-center gap-1">
                                        <button x-show="selectedLabel" @click.stop="clear()" type="button" class="text-gray-400 hover:text-red-500">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                        </button>
                                        <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                                    </div>
                                </div>
                                <div x-show="open" x-transition class="absolute z-50 mt-1 w-full bg-white border border-gray-300 rounded-lg shadow-lg max-h-60 overflow-hidden">
                                    <div class="p-2 border-b border-gray-200">
                                        <input type="text" x-model="search" @click.stop placeholder="Search patients..." 
                                               class="w-full px-3 py-1.5 border border-gray-300 rounded-md text-sm focus:outline-none focus:ring-1 focus:ring-purple-500" autocomplete="off">
                                    </div>
                                    <ul class="overflow-y-auto max-h-48">
                                        <template x-for="item in filtered" :key="item.id">
                                            <li @click="select(item)" class="px-4 py-2 text-sm hover:bg-purple-50 cursor-pointer" x-text="item.name"></li>
                                        </template>
                                        <li x-show="filtered.length === 0" class="px-4 py-2 text-sm text-gray-400">No results found</li>
                                    </ul>
                                </div>
                                @error('client_id') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                    OR Number <span class="text-red-500">*</span>
                                </label>
                                <input type="text" wire:model="or_number" 
                                       class="w-full px-3 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent"
                                       placeholder="Enter OR number">
                                @error('or_number') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                            </div>
                        </div>
                    </div>
                    <div class="px-6 py-4 bg-gray-50 border-t border-gray-200 flex justify-end space-x-3 rounded-b-lg">
                        <button type="button" wire:click="cancelEdit" 
                                class="px-5 py-2.5 border border-gray-300 rounded-md text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">
                            Cancel
                        </button>
                        <button type="submit" 
                                style="background-color: #DC143C;"
                                class="px-5 py-2.5 text-white text-sm rounded-md font-medium hover:opacity-90 transition-opacity">
                            Update Transaction
                        </button>
                    </div>
                </form>
                @else
                <!-- View Mode (Read-only) -->
                <div class="p-6">
                    <div class="grid grid-cols-1 gap-5">
                        <div class="bg-gray-50 p-4 rounded-lg">
                            <p class="text-xs font-semibold text-gray-500 uppercase mb-1">OR Number</p>
                            <p class="text-sm font-medium text-gray-900">{{ $viewingTransaction->or_number }}</p>
                        </div>
                        <div class="bg-gray-50 p-4 rounded-lg">
                            <p class="text-xs font-semibold text-gray-500 uppercase mb-1">Patient</p>
                            <p class="text-sm font-medium text-gray-900">{{ $viewingTransaction->patient->full_name ?? 'N/A' }}</p>
                        </div>
                        @if($viewingTransaction->lab_test_order_id)
                        <div class="bg-blue-50 p-4 rounded-lg border border-blue-200">
                            <p class="text-xs font-semibold text-blue-600 uppercase mb-1">Linked Test Order</p>
                            <p class="text-sm font-medium text-blue-900">
                                Order #{{ $viewingTransaction->lab_test_order_id }}
                                @if($viewingTransaction->labTestOrder)
                                    — {{ $viewingTransaction->labTestOrder->orderTests->count() }} test(s)
                                @endif
                            </p>
                            @if($viewingTransaction->labTestOrder)
                                <div class="mt-2 space-y-1">
                                    @foreach($viewingTransaction->labTestOrder->orderTests as $ot)
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-blue-100 text-blue-800 mr-1">
                                            {{ $ot->test->label ?? 'Unknown' }}
                                        </span>
                                    @endforeach
                                </div>
                            @endif
                        </div>
                        @endif
                        <div class="grid grid-cols-2 gap-4">
                            <div class="bg-gray-50 p-4 rounded-lg">
                                <p class="text-xs font-semibold text-gray-500 uppercase mb-1">Amount</p>
                                <p class="text-sm font-medium text-gray-900">
                                    @if($viewingTransaction->amount)
                                        ₱{{ number_format($viewingTransaction->amount, 2) }}
                                    @else
                                        —
                                    @endif
                                </p>
                            </div>
                            <div class="bg-gray-50 p-4 rounded-lg">
                                <p class="text-xs font-semibold text-gray-500 uppercase mb-1">Payment Method</p>
                                <p class="text-sm font-medium text-gray-900 capitalize">
                                    {{ $viewingTransaction->payment_method ? str_replace('_', ' ', $viewingTransaction->payment_method) : '—' }}
                                </p>
                            </div>
                        </div>
                        <div class="grid grid-cols-2 gap-4">
                            <div class="bg-gray-50 p-4 rounded-lg">
                                <p class="text-xs font-semibold text-gray-500 uppercase mb-1">Date Added</p>
                                <p class="text-sm font-medium text-gray-900">
                                    {{ $viewingTransaction->datetime_added ? \Carbon\Carbon::parse($viewingTransaction->datetime_added)->format('M d, Y h:i A') : 'N/A' }}
                                </p>
                            </div>
                            <div class="bg-gray-50 p-4 rounded-lg">
                                <p class="text-xs font-semibold text-gray-500 uppercase mb-1">Processed By</p>
                                <p class="text-sm font-medium text-gray-900">
                                    {{ $viewingTransaction->processedByEmployee ? $viewingTransaction->processedByEmployee->full_name : '—' }}
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="px-6 py-4 bg-gray-50 border-t border-gray-200 flex justify-end space-x-3 rounded-b-lg">
                    <button type="button" wire:click="closeViewModal" 
                            class="px-5 py-2.5 border border-gray-300 rounded-md text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">
                        Close
                    </button>
                    <button type="button" wire:click="enableEdit" 
                            style="background-color: #DC143C;"
                            class="px-5 py-2.5 text-white text-sm rounded-md font-medium hover:opacity-90 transition-opacity">
                        Edit
                    </button>
                </div>
                @endif
            </div>
        </div>
    </div>
    @endif

    {{-- Delete Confirmation Modal --}}
    @if($showDeleteModal)
    <div class="fixed inset-0 z-50 overflow-y-auto" style="background-color: rgba(0, 0, 0, 0.5);">
        <div class="flex items-center justify-center min-h-screen p-4">
            <div class="bg-white rounded-lg shadow-xl max-w-md w-full">
                <!-- Modal Header -->
                <div class="px-6 py-4 border-b border-gray-200">
                    <div class="flex items-center justify-between">
                        <h3 class="text-xl font-semibold text-gray-900">
                            Confirm Deletion
                        </h3>
                        <button type="button" wire:click="closeDeleteModal" class="text-gray-400 hover:text-gray-600">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </button>
                    </div>
                </div>

                <!-- Modal Body -->
                <div class="p-6">
                    <div class="flex items-start space-x-3">
                        <div class="flex-shrink-0">
                            <svg class="w-6 h-6 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z"/>
                            </svg>
                        </div>
                        <div class="flex-1">
                            <p class="text-sm text-gray-700">{{ $deleteMessage }}</p>
                        </div>
                    </div>
                </div>

                <!-- Modal Footer -->
                <div class="px-6 py-4 bg-gray-50 border-t border-gray-200 flex justify-end space-x-3 rounded-b-lg">
                    <button type="button" wire:click="closeDeleteModal" 
                            class="px-5 py-2.5 border border-gray-300 rounded-md text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">
                        Cancel
                    </button>
                    <button type="button" wire:click="{{ $deleteAction }}" 
                            class="px-5 py-2.5 bg-red-600 text-white text-sm rounded-md font-medium hover:bg-red-700">
                        Delete
                    </button>
                </div>
            </div>
        </div>
    </div>
    @endif
</div>