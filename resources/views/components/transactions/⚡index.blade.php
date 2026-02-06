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
    public $editingId = null;

    // Edit Modal Properties
    public $showEditModal = false;
    public $editTransactionId = '';
    public $editClientId = '';
    public $editOrNumber = '';
    public $editClientDesignation = '';

    public function mount()
    {
        if (session()->has('success')) {
            $this->flashMessage = session('success');
        }
    }

    public function openEditModal($transactionId)
    {
        $transaction = Transaction::findOrFail($transactionId);
        
        $this->editTransactionId = $transaction->transaction_id;
        $this->editClientId = $transaction->client_id;
        $this->editOrNumber = $transaction->or_number;
        $this->editClientDesignation = $transaction->client_designation;
        
        $this->showEditModal = true;
    }

    public function closeEditModal()
    {
        $this->showEditModal = false;
        $this->reset(['editTransactionId', 'editClientId', 'editOrNumber', 'editClientDesignation']);
    }

    public function updateTransaction()
    {
        $validated = $this->validate([
            'editClientId' => 'required|exists:patient,patient_id',
            'editOrNumber' => 'required|string|max:50',
            'editClientDesignation' => 'nullable|string|max:50',
        ]);

        $transaction = Transaction::findOrFail($this->editTransactionId);
        $transaction->update([
            'client_id' => $this->editClientId,
            'or_number' => $this->editOrNumber,
            'client_designation' => $this->editClientDesignation,
        ]);

        $this->flashMessage = 'Transaction updated successfully!';
        $this->closeEditModal();
    }

    public function save()
    {
        $this->validate();

        if ($this->editingId) {
            $transaction = Transaction::find($this->editingId);
            if ($transaction) {
                $transaction->update([
                    'client_id' => $this->client_id,
                    'or_number' => $this->or_number,
                ]);
                $this->flashMessage = 'Transaction updated successfully!';
            }
        } else {
            Transaction::create([
                'client_id' => $this->client_id,
                'or_number' => $this->or_number,
                'datetime_added' => now(),
                'status_code' => 1,
            ]);
            $this->flashMessage = 'Transaction added successfully!';
        }

        $this->reset(['client_id', 'or_number', 'editingId']);
        $this->resetPage();
    }

    public function edit($id)
    {
        $transaction = Transaction::find($id);
        if ($transaction) {
            $this->editingId = $id;
            $this->client_id = $transaction->client_id;
            $this->or_number = $transaction->or_number;
        }
    }

    public function cancelEdit()
    {
        $this->reset(['client_id', 'or_number', 'editingId']);
    }

    public function delete($id)
    {
        $transaction = Transaction::find($id);
        if ($transaction) {
            $transaction->delete();
            $this->flashMessage = 'Transaction deleted successfully!';
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
        <div class="mb-6 p-4 bg-green-50 border-l-4 border-green-500 rounded">
            <p class="text-green-800">{{ $flashMessage }}</p>
        </div>
    @endif

    <div class="bg-white rounded-lg shadow-sm mb-6">
        <div class="px-6 py-4 border-b border-gray-200">
            <h2 class="text-lg font-semibold text-gray-900">{{ $editingId ? 'Edit Transaction' : 'Add New Transaction' }}</h2>
        </div>
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
                <div class="flex justify-end mt-4 space-x-3">
                    @if($editingId)
                        <button type="button" wire:click="cancelEdit"
                                class="px-4 py-2 border border-gray-300 text-gray-700 rounded-md hover:bg-gray-50 transition-colors">
                            Cancel
                        </button>
                    @endif
                    <button type="submit" 
                            class="px-4 py-2 bg-purple-600 text-white rounded-md hover:bg-purple-700 transition-colors">
                        {{ $editingId ? 'Update Transaction' : 'Add Transaction' }}
                    </button>
                </div>
            </form>
        </div>

    <div class="bg-white rounded-lg shadow-sm">
        <div class="px-6 py-4 border-b border-gray-200">
            <h2 class="text-lg font-semibold text-gray-900">Transactions List</h2>
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
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">OR Number</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Patient</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date Added</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @forelse($transactions as $transaction)
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">{{ $transaction->or_number }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                                    {{ $transaction->patient->full_name ?? 'N/A' }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                                    {{ $transaction->datetime_added ? \Carbon\Carbon::parse($transaction->datetime_added)->format('M d, Y') : 'N/A' }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                    <div class="flex items-center space-x-2">
                                        <button wire:click="openEditModal({{ $transaction->transaction_id }})" 
                                                class="px-3 py-1.5 bg-orange-100 text-orange-700 rounded-lg hover:bg-orange-200 transition-colors">
                                            Edit
                                        </button>
                                        <button wire:click="delete({{ $transaction->transaction_id }})" 
                                                wire:confirm="Are you sure you want to delete this transaction?"
                                                class="px-3 py-1.5 bg-red-100 text-red-700 rounded-lg hover:bg-red-200 transition-colors">
                                            Delete
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-6 py-4 text-center text-gray-500">No transactions found.</td>
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
    </div>

    <!-- Edit Transaction Modal -->
    @if($showEditModal)
    <div class="fixed inset-0 z-50 overflow-y-auto" style="background-color: rgba(0, 0, 0, 0.5);">
        <div class="flex items-center justify-center min-h-screen p-4">
            <div class="bg-white rounded-lg shadow-xl max-w-2xl w-full">
                <!-- Modal Header -->
                <div class="px-6 py-4 border-b" style="background-color: #E91E8C;">
                    <div class="flex items-center justify-between">
                        <h3 class="text-lg font-semibold text-white flex items-center">
                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                            </svg>
                            Edit Transaction
                        </h3>
                        <button type="button" wire:click="closeEditModal" class="text-white hover:text-gray-200">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </button>
                    </div>
                </div>

                <!-- Modal Body -->
                <form wire:submit.prevent="updateTransaction">
                    <div class="p-6">
                        <div class="grid grid-cols-1 gap-4">
                            <!-- Patient -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">
                                    Patient <span class="text-red-500">*</span>
                                </label>
                                <select wire:model="editClientId" 
                                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                                    <option value="">Select Patient</option>
                                    @foreach($patients as $patient)
                                        <option value="{{ $patient->patient_id }}">{{ $patient->full_name }}</option>
                                    @endforeach
                                </select>
                                @error('editClientId') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                            </div>

                            <!-- OR Number -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">
                                    OR Number <span class="text-red-500">*</span>
                                </label>
                                <input type="text" wire:model="editOrNumber" 
                                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                                       placeholder="Enter OR number">
                                @error('editOrNumber') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                            </div>

                            <!-- Client Designation -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Client Designation</label>
                                <input type="text" wire:model="editClientDesignation" 
                                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                                       placeholder="Enter client designation">
                                @error('editClientDesignation') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                            </div>
                        </div>
                    </div>

                    <!-- Modal Footer -->
                    <div class="px-6 py-4 border-t border-gray-200 flex justify-end space-x-3">
                        <button type="button" wire:click="closeEditModal" 
                                class="px-4 py-2 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50 font-medium">
                            Cancel
                        </button>
                        <button type="submit" 
                                class="px-4 py-2 text-white rounded-md font-medium hover:opacity-90 flex items-center"
                                style="background-color: #E91E8C;">
                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                            </svg>
                            Update Transaction
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    @endif
</div>

<script>
    document.addEventListener('livewire:initialized', () => {
        Livewire.on('close-edit-modal', () => {
            @this.closeEditModal();
        });
    });

    // Close modal on Escape key
    document.addEventListener('keydown', function(event) {
        if (event.key === 'Escape' && @this.showEditModal) {
            @this.closeEditModal();
        }
    });
</script>
