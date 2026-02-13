<?php

use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Validate;
use App\Models\Transaction;
use App\Models\Patient;
use App\Traits\LogsActivity;

new class extends Component
{
    use WithPagination, LogsActivity;

    #[Validate('required|exists:patient,patient_id')]
    public $client_id = '';

    #[Validate('required|string|max:50')]
    public $or_number = '';

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

    public function mount()
    {
        if (session()->has('success')) {
            $this->flashMessage = session('success');
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
        $this->closeEditModal();
    }

    public function toggleForm()
    {
        $this->showForm = !$this->showForm;
        if (!$this->showForm) {
            $this->reset(['client_id', 'or_number']);
            $this->resetErrorBag();
        }
    }

    public function save()
    {
        $this->validate();

        if (Transaction::where('or_number', $this->or_number)->exists()) {
            $this->addError('or_number', 'A transaction with this OR number already exists.');
            return;
        }

        Transaction::create([
            'client_id' => $this->client_id,
            'or_number' => $this->or_number,
            'datetime_added' => now(),
            'status_code' => 1,
        ]);
        $this->logActivity("Created transaction OR#{$this->or_number}");
        $this->flashMessage = 'Transaction added successfully!';

        $this->reset(['client_id', 'or_number']);
        $this->showForm = false;
        $this->resetPage();
    }

    public function showDetails($id)
    {
        $this->viewingTransaction = Transaction::with('patient')->findOrFail($id);
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
            $this->resetPage();
        }
    }

    public function with(): array
    {
        $query = Transaction::with('patient')
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
            'patients' => Patient::active()->orderBy('lastname')->get()
        ];
    }
};
?>

<div class="p-6">
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-gray-900 flex items-center">
            <svg class="w-7 h-7 mr-2 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
            </svg>
            Transaction Management
        </h1>
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

    <div class="bg-white rounded-lg shadow-sm mb-6">
        <div class="px-6 py-4 border-b border-gray-200 flex items-center justify-between">
            <h2 class="text-lg font-semibold text-gray-900">Add New Transaction</h2>
            <button type="button" wire:click="toggleForm" 
                    class="px-4 py-2 rounded-md text-sm font-medium transition-colors {{ $showForm ? 'bg-gray-200 text-gray-700 hover:bg-gray-300' : 'bg-purple-600 text-white hover:bg-purple-700' }}">
                {{ $showForm ? 'Close Form' : 'Add New Transaction' }}
            </button>
        </div>
        @if($showForm)
        <form wire:submit.prevent="save" class="p-6">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Patient *</label>
                    <select wire:model="client_id" 
                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-purple-500">
                        <option value="">Select Patient</option>
                        @foreach($patients as $patient)
                            <option value="{{ $patient->patient_id }}">{{ $patient->full_name }}</option>
                        @endforeach
                    </select>
                    @error('client_id') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">OR Number *</label>
                    <input type="text" wire:model="or_number" 
                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-purple-500">
                    @error('or_number') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                </div>
            </div>
            <div class="flex justify-end mt-4">
                <button type="submit" 
                        class="px-4 py-2 bg-purple-600 text-white rounded-md hover:bg-purple-700 transition-colors">
                    Add Transaction
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
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Search</label>
                    <input type="text" wire:model.live="search" placeholder="Search by OR number or patient name..." 
                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-purple-500">
                </div>
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
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date Added</th>
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
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                                {{ $transaction->datetime_added ? \Carbon\Carbon::parse($transaction->datetime_added)->format('M d, Y') : 'N/A' }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-6 py-4 text-center text-gray-500">No transactions found.</td>
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
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                    Patient <span class="text-red-500">*</span>
                                </label>
                                <select wire:model="client_id" 
                                        class="w-full px-3 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent">
                                    <option value="">Select Patient</option>
                                    @foreach($patients as $patient)
                                        <option value="{{ $patient->patient_id }}">{{ $patient->full_name }}</option>
                                    @endforeach
                                </select>
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
                                class="px-5 py-2.5 bg-purple-600 text-white text-sm rounded-md font-medium hover:bg-purple-700">
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
                        <div class="bg-gray-50 p-4 rounded-lg">
                            <p class="text-xs font-semibold text-gray-500 uppercase mb-1">Date Added</p>
                            <p class="text-sm font-medium text-gray-900">
                                {{ $viewingTransaction->datetime_added ? \Carbon\Carbon::parse($viewingTransaction->datetime_added)->format('M d, Y h:i A') : 'N/A' }}
                            </p>
                        </div>
                    </div>
                </div>
                <div class="px-6 py-4 bg-gray-50 border-t border-gray-200 flex justify-end space-x-3 rounded-b-lg">
                    <button type="button" wire:click="closeViewModal" 
                            class="px-5 py-2.5 border border-gray-300 rounded-md text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">
                        Close
                    </button>
                    <button type="button" wire:click="enableEdit" 
                            class="px-5 py-2.5 bg-orange-500 text-white text-sm rounded-md font-medium hover:bg-orange-600">
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
