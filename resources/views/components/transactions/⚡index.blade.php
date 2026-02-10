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

    public function edit($id)
    {
        $transaction = Transaction::findOrFail($id);
        $this->editingTransactionId = $id;
        $this->client_id = $transaction->client_id;
        $this->or_number = $transaction->or_number;
        $this->editMode = true;
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
        $this->cancelEdit();
    }

    public function cancelEdit()
    {
        $this->reset(['client_id', 'or_number', 'editMode', 'editingTransactionId']);
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
                        <tr wire:key="transaction-{{ $transaction->transaction_id }}" class="hover:bg-gray-50">
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">{{ $transaction->or_number }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                                {{ $transaction->patient->full_name ?? 'N/A' }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                                {{ $transaction->datetime_added ? \Carbon\Carbon::parse($transaction->datetime_added)->format('M d, Y') : 'N/A' }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                <div class="flex items-center space-x-2">
                                    <button type="button" wire:click="edit({{ $transaction->transaction_id }})" 
                                            class="px-4 py-1.5 bg-orange-500 hover:bg-orange-600 text-white text-sm font-medium rounded-lg transition-colors">Edit</button>
                                    <button type="button" wire:click="delete({{ $transaction->transaction_id }})" 
                                            wire:confirm="Are you sure you want to delete this transaction?"
                                            class="px-4 py-1.5 bg-red-500 hover:bg-red-600 text-white text-sm font-medium rounded-lg transition-colors">Delete</button>
                                </div>
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

    <!-- Edit Transaction Modal -->
    @if($editMode)
    <div class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50 p-4">
        <div class="relative top-20 mx-auto p-6 border w-full max-w-lg shadow-lg rounded-xl bg-white">
            <div class="flex items-center justify-between mb-6">
                <h3 class="text-2xl font-bold text-gray-900">Edit Transaction</h3>
                <button type="button" wire:click="cancelEdit" class="text-gray-400 hover:text-gray-600">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>

            <form wire:submit.prevent="update">
                <div class="grid grid-cols-1 gap-5">
                    <!-- Patient -->
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

                    <!-- OR Number -->
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

                <div class="flex justify-end space-x-3 pt-4 border-t mt-6">
                    <button type="button" wire:click="cancelEdit" 
                            class="px-6 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition-colors">
                        Cancel
                    </button>
                    <button type="submit" 
                            class="px-6 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700 transition-colors">
                        Update Transaction
                    </button>
                </div>
            </form>
        </div>
    </div>
    @endif
</div>
