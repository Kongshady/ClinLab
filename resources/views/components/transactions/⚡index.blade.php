<?php

use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Validate;
use App\Models\Transaction;
use App\Models\Patient;

new class extends Component
{
    use WithPagination;

    #[Validate('required|exists:patient,patient_id')]
    public $client_id = '';

    #[Validate('required|string|max:50')]
    public $or_number = '';

    public $search = '';
    public $perPage = 'all';
    public $flashMessage = '';

    // Edit mode
    public $editMode = false;
    public $editingTransactionId = null;

    // Form visibility toggle
    public bool $showForm = false;

    // View modal
    public $showViewModal = false;
    public $viewingTransaction = null;

    public function mount()
    {
        if (session()->has('success')) {
            $this->flashMessage = session('success');
        }
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

        Transaction::create([
            'client_id' => $this->client_id,
            'or_number' => $this->or_number,
            'datetime_added' => now(),
            'status_code' => 1,
        ]);
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

        $transaction = Transaction::findOrFail($this->editingTransactionId);
        $transaction->update([
            'client_id' => $this->client_id,
            'or_number' => $this->or_number,
        ]);

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
            $this->flashMessage = 'Transaction deleted successfully!';
            $this->resetPage();
        }
    }

    public function deleteSelected($ids)
    {
        if (empty($ids)) return;
        
        $count = Transaction::whereIn('transaction_id', $ids)->delete();
        $this->flashMessage = $count . ' transaction(s) deleted successfully!';
        $this->resetPage();
        $this->dispatch('selection-cleared');
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

<div class="p-6" x-data="{ 
    selectedIds: [],
    selectAll: false,
    toggleAll(ids) {
        if (this.selectAll) {
            this.selectedIds = ids;
        } else {
            this.selectedIds = [];
        }
    },
    toggleOne(id) {
        const idx = this.selectedIds.indexOf(id);
        if (idx > -1) {
            this.selectedIds.splice(idx, 1);
        } else {
            this.selectedIds.push(id);
        }
    }
}" @selection-cleared.window="selectedIds = []; selectAll = false">
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-gray-900 flex items-center">
            <svg class="w-7 h-7 mr-2 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
            </svg>
            Transaction Management
        </h1>
    </div>

    @if($flashMessage)
        <div class="mb-6 p-4 bg-green-50 border-l-4 border-green-500 rounded">
            <p class="text-green-800">{{ $flashMessage }}</p>
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
                <!-- Delete Selected Button -->
                <div x-show="selectedIds.length > 0" x-cloak x-transition>
                    <button type="button" 
                            @click="if(confirm('Are you sure you want to delete ' + selectedIds.length + ' selected transaction(s)?')) { $wire.deleteSelected(selectedIds) }"
                            class="inline-flex items-center gap-2 px-4 py-2 bg-red-500 hover:bg-red-600 text-white text-sm font-medium rounded-lg transition-colors">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                        </svg>
                        Delete Selected (<span x-text="selectedIds.length"></span>)
                    </button>
                </div>
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
                            <input type="checkbox" x-model="selectAll" 
                                   @change="toggleAll([{{ $transactions instanceof \Illuminate\Pagination\LengthAwarePaginator ? $transactions->pluck('transaction_id')->implode(',') : $transactions->pluck('transaction_id')->implode(',') }}])"
                                   class="rounded border-gray-300 text-purple-600 focus:ring-purple-500">
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">OR Number</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Patient</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date Added</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($transactions as $transaction)
                        <tr wire:key="transaction-{{ $transaction->transaction_id }}" 
                            class="hover:bg-gray-50 cursor-pointer transition-colors"
                            wire:click="showDetails({{ $transaction->transaction_id }})">
                            <td class="px-6 py-4" wire:click.stop>
                                <input type="checkbox" value="{{ $transaction->transaction_id }}" 
                                       @change="toggleOne({{ $transaction->transaction_id }})"
                                       :checked="selectedIds.includes({{ $transaction->transaction_id }})"
                                       class="rounded border-gray-300 text-purple-600 focus:ring-purple-500">
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
</div>
