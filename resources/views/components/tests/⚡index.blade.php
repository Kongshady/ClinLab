<?php

use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Validate;
use App\Models\Test;
use App\Models\Section;
use App\Models\TestPriceHistory;

new class extends Component
{
    use WithPagination;

    #[Validate('required|exists:section,section_id')]
    public $section_id = '';

    #[Validate('required|string|max:255')]
    public $label = '';

    #[Validate('required|numeric|min:0')]
    public $current_price = '';

    public $search = '';
    public $flashMessage = '';
    public $perPage = 'all';

    // Edit properties
    public $editingTestId = null;
    public $edit_section_id = '';
    public $edit_label = '';

    // Set Price properties
    public $setPriceTestId = null;
    public $setPriceTestName = '';
    public $setPriceCurrentPrice = 0;
    public $new_price = '';

    // View History properties
    public $viewHistoryTestId = null;
    public $viewHistoryTestName = '';
    public $priceHistory = [];

    public function mount()
    {
        if (session()->has('success')) {
            $this->flashMessage = session('success');
        }
    }

    public function save()
    {
        $this->validate();

        Test::create([
            'section_id' => $this->section_id,
            'label' => $this->label,
            'current_price' => $this->current_price,
            'previous_price' => 0,
            'is_deleted' => 0,
        ]);

        $this->reset(['section_id', 'label', 'current_price']);
        $this->flashMessage = 'Test added successfully!';
        $this->resetPage();
    }

    public function editTest($testId)
    {
        $test = Test::find($testId);
        if ($test) {
            $this->editingTestId = $testId;
            $this->edit_section_id = $test->section_id;
            $this->edit_label = $test->label;
        }
    }

    public function updateTest()
    {
        $this->validate([
            'edit_section_id' => 'required|exists:section,section_id',
            'edit_label' => 'required|string|max:255',
        ]);

        $test = Test::find($this->editingTestId);
        if ($test) {
            $test->update([
                'section_id' => $this->edit_section_id,
                'label' => $this->edit_label,
            ]);

            $this->flashMessage = 'Test updated successfully!';
            $this->closeEditModal();
        }
    }

    public function closeEditModal()
    {
        $this->editingTestId = null;
        $this->reset(['edit_section_id', 'edit_label']);
    }

    public function openSetPriceModal($testId)
    {
        $test = Test::find($testId);
        if ($test) {
            $this->setPriceTestId = $testId;
            $this->setPriceTestName = $test->label;
            $this->setPriceCurrentPrice = $test->current_price;
            $this->new_price = '';
        }
    }

    public function updatePrice()
    {
        $this->validate([
            'new_price' => 'required|numeric|min:0',
        ]);

        $test = Test::find($this->setPriceTestId);
        if ($test) {
            $previousPrice = $test->current_price;
            
            // Create price history record
            TestPriceHistory::create([
                'test_id' => $test->test_id,
                'previous_price' => $previousPrice,
                'new_price' => $this->new_price,
                'updated_by' => auth()->user()->employee->employee_id ?? null,
                'updated_at' => now(),
            ]);

            // Update test price
            $test->update([
                'previous_price' => $previousPrice,
                'current_price' => $this->new_price,
            ]);

            $this->flashMessage = 'Price updated successfully!';
            $this->closeSetPriceModal();
        }
    }

    public function closeSetPriceModal()
    {
        $this->setPriceTestId = null;
        $this->reset(['setPriceTestName', 'setPriceCurrentPrice', 'new_price']);
    }

    public function viewHistory($testId)
    {
        $test = Test::with(['priceHistory.updatedByEmployee'])->find($testId);
        if ($test) {
            $this->viewHistoryTestId = $testId;
            $this->viewHistoryTestName = $test->label;
            $this->priceHistory = $test->priceHistory;
        }
    }

    public function closeHistoryModal()
    {
        $this->viewHistoryTestId = null;
        $this->reset(['viewHistoryTestName', 'priceHistory']);
    }

    public function delete($id)
    {
        $test = Test::find($id);
        if ($test) {
            $test->softDelete();
            $this->flashMessage = 'Test deleted successfully!';
        }
    }

    public function with(): array
    {
        $query = Test::active()
            ->with('section')
            ->when($this->search, function ($query) {
                $query->where(function ($q) {
                    $q->where('label', 'like', '%' . $this->search . '%')
                      ->orWhereHas('section', function ($sq) {
                          $sq->where('label', 'like', '%' . $this->search . '%');
                      });
                });
            })
            ->orderBy('test_id', 'desc');

        $tests = $this->perPage === 'all' ? $query->get() : $query->paginate((int)$this->perPage);

        return [
            'tests' => $tests,
            'sections' => Section::active()->orderBy('label')->get()
        ];
    }
};
?>

<div class="p-6 space-y-6">
    @if($flashMessage)
        <div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-lg" role="alert">
            <span class="block sm:inline">{{ $flashMessage }}</span>
        </div>
    @endif

    <!-- Header -->
    <div class="flex items-center space-x-3 mb-6">
        <svg class="w-8 h-8 text-gray-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z"/>
        </svg>
        <h1 class="text-2xl font-bold text-gray-900">Test Management</h1>
    </div>

    <!-- Add New Test Form -->
    <div class="bg-white rounded-lg shadow-sm p-6">
        <h2 class="text-lg font-semibold text-gray-900 mb-6">Add New Test</h2>
        <form wire:submit.prevent="save">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4 items-end">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        Section <span class="text-red-500">*</span>
                    </label>
                    <select wire:model="section_id" 
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-pink-500 focus:border-transparent">
                        <option value="">Select Section</option>
                        @foreach($sections as $section)
                            <option value="{{ $section->section_id }}">{{ $section->label }}</option>
                        @endforeach
                    </select>
                    @error('section_id') <span class="text-red-600 text-xs mt-1">{{ $message }}</span> @enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        Test Name <span class="text-red-500">*</span>
                    </label>
                    <input type="text" 
                           wire:model="label" 
                           placeholder="e.g., Complete Blood Count, Urinalysis"
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-pink-500 focus:border-transparent">
                    @error('label') <span class="text-red-600 text-xs mt-1">{{ $message }}</span> @enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        Initial Price (₱)
                    </label>
                    <input type="number" 
                           step="0.01" 
                           wire:model="current_price" 
                           placeholder="0.00"
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-pink-500 focus:border-transparent">
                    @error('current_price') <span class="text-red-600 text-xs mt-1">{{ $message }}</span> @enderror
                </div>
                <div>
                    <button type="submit" 
                            class="w-full px-6 py-2 bg-pink-500 hover:bg-pink-600 text-white font-medium rounded-lg transition-colors">
                        Add Test
                    </button>
                </div>
            </div>
        </form>
    </div>

    <!-- Rows per page -->
    <div class="flex items-center space-x-3">
        <label class="text-sm font-medium text-gray-700">Rows per page:</label>
        <select wire:model.live="perPage" 
                class="px-3 py-1.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-pink-500 focus:border-transparent text-sm">
            <option value="10">10</option>
            <option value="25">25</option>
            <option value="50">50</option>
            <option value="100">100</option>
            <option value="all">All</option>
        </select>
    </div>

    <!-- Tests List -->
    <div class="bg-white rounded-lg shadow-sm">
        <div class="p-6">
            <h2 class="text-lg font-semibold text-gray-900 mb-4">Tests List</h2>
            
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead>
                        <tr class="bg-gray-50">
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Test Name</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Section</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Previous Price</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Current Price</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        @forelse($tests as $test)
                            <tr class="hover:bg-gray-50">
                                <td class="px-4 py-3 text-sm text-gray-900 font-medium">{{ $test->label }}</td>
                                <td class="px-4 py-3 text-sm text-gray-700">{{ $test->section->label ?? 'N/A' }}</td>
                                <td class="px-4 py-3 text-sm text-gray-700">₱{{ number_format($test->previous_price ?? 0, 2) }}</td>
                                <td class="px-4 py-3 text-sm text-gray-900 font-semibold">₱{{ number_format($test->current_price, 2) }}</td>
                                <td class="px-4 py-3">
                                    <div class="flex items-center space-x-2">
                                        <button wire:click="editTest({{ $test->test_id }})"
                                                type="button"
                                                class="px-4 py-1.5 bg-orange-500 hover:bg-orange-600 text-white text-sm font-medium rounded-lg transition-colors">
                                            Edit
                                        </button>
                                        <button wire:click="openSetPriceModal({{ $test->test_id }})"
                                                type="button"
                                                class="px-4 py-1.5 bg-teal-500 hover:bg-teal-600 text-white text-sm font-medium rounded-lg transition-colors">
                                            Set Price
                                        </button>
                                        <button wire:click="viewHistory({{ $test->test_id }})"
                                                type="button"
                                                class="px-4 py-1.5 bg-blue-500 hover:bg-blue-600 text-white text-sm font-medium rounded-lg transition-colors">
                                            View History
                                        </button>
                                        <button wire:click="delete({{ $test->test_id }})" 
                                                wire:confirm="Are you sure you want to delete this test?"
                                                class="px-4 py-1.5 bg-red-500 hover:bg-red-600 text-white text-sm font-medium rounded-lg transition-colors">
                                            Delete
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-4 py-8 text-center text-gray-500">No tests found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($perPage !== 'all')
            <div class="mt-6">
                {{ $tests->links() }}
            </div>
            @endif
        </div>
    </div>

    <!-- Edit Test Modal -->
    @if($editingTestId)
    <div class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
        <div class="bg-white rounded-lg shadow-xl w-full max-w-2xl">
            <div class="flex items-center justify-between p-6 border-b">
                <h3 class="text-lg font-semibold text-gray-900">Edit Test</h3>
                <button wire:click="closeEditModal" class="text-gray-400 hover:text-gray-600">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>
            <form wire:submit.prevent="updateTest">
                <div class="p-6 space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Section <span class="text-red-500">*</span>
                        </label>
                        <select wire:model="edit_section_id" 
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-pink-500 focus:border-transparent">
                            <option value="">Select Section</option>
                            @foreach($sections as $section)
                                <option value="{{ $section->section_id }}">{{ $section->label }}</option>
                            @endforeach
                        </select>
                        @error('edit_section_id') <span class="text-red-600 text-xs mt-1">{{ $message }}</span> @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Test Name <span class="text-red-500">*</span>
                        </label>
                        <input type="text" 
                               wire:model="edit_label" 
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-pink-500 focus:border-transparent">
                        @error('edit_label') <span class="text-red-600 text-xs mt-1">{{ $message }}</span> @enderror
                    </div>
                </div>
                <div class="flex items-center justify-end space-x-3 p-6 border-t bg-gray-50">
                    <button type="button" 
                            wire:click="closeEditModal"
                            class="px-4 py-2 bg-gray-200 hover:bg-gray-300 text-gray-700 font-medium rounded-lg transition-colors">
                        Cancel
                    </button>
                    <button type="submit"
                            class="px-4 py-2 bg-orange-500 hover:bg-orange-600 text-white font-medium rounded-lg transition-colors">
                        Update Test
                    </button>
                </div>
            </form>
        </div>
    </div>
    @endif

    <!-- Set Price Modal -->
    @if($setPriceTestId)
    <div class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
        <div class="bg-white rounded-lg shadow-xl w-full max-w-md">
            <div class="flex items-center justify-between p-6 border-b">
                <h3 class="text-lg font-semibold text-gray-900">Set Price</h3>
                <button wire:click="closeSetPriceModal" class="text-gray-400 hover:text-gray-600">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>
            <form wire:submit.prevent="updatePrice">
                <div class="p-6 space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Test Name</label>
                        <input type="text" 
                               value="{{ $setPriceTestName }}" 
                               disabled
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg bg-gray-50 text-gray-600">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Current Price</label>
                        <input type="text" 
                               value="₱{{ number_format($setPriceCurrentPrice, 2) }}" 
                               disabled
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg bg-gray-50 text-gray-600">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            New Price (₱) <span class="text-red-500">*</span>
                        </label>
                        <input type="number" 
                               step="0.01"
                               wire:model="new_price" 
                               placeholder="0.00"
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-transparent">
                        @error('new_price') <span class="text-red-600 text-xs mt-1">{{ $message }}</span> @enderror
                    </div>
                </div>
                <div class="flex items-center justify-end space-x-3 p-6 border-t bg-gray-50">
                    <button type="button" 
                            wire:click="closeSetPriceModal"
                            class="px-4 py-2 bg-gray-200 hover:bg-gray-300 text-gray-700 font-medium rounded-lg transition-colors">
                        Cancel
                    </button>
                    <button type="submit"
                            class="px-4 py-2 bg-teal-500 hover:bg-teal-600 text-white font-medium rounded-lg transition-colors">
                        Update Price
                    </button>
                </div>
            </form>
        </div>
    </div>
    @endif

    <!-- View History Modal -->
    @if($viewHistoryTestId)
    <div class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
        <div class="bg-white rounded-lg shadow-xl w-full max-w-4xl max-h-[90vh] flex flex-col">
            <div class="flex items-center justify-between p-6 border-b">
                <div>
                    <h3 class="text-lg font-semibold text-gray-900">Price History</h3>
                    <p class="text-sm text-gray-600 mt-1">{{ $viewHistoryTestName }}</p>
                </div>
                <button wire:click="closeHistoryModal" class="text-gray-400 hover:text-gray-600">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>
            <div class="p-6 overflow-y-auto flex-1">
                @if(count($priceHistory) > 0)
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead>
                            <tr class="bg-gray-50">
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Date Updated</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Previous Price</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">New Price</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Updated By</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            @foreach($priceHistory as $history)
                            <tr class="hover:bg-gray-50">
                                <td class="px-4 py-3 text-sm text-gray-900">
                                    {{ \Carbon\Carbon::parse($history->updated_at)->format('M d, Y h:i A') }}
                                </td>
                                <td class="px-4 py-3 text-sm text-gray-900 font-medium">
                                    ₱{{ number_format($history->previous_price, 2) }}
                                </td>
                                <td class="px-4 py-3 text-sm text-gray-900 font-semibold">
                                    ₱{{ number_format($history->new_price, 2) }}
                                </td>
                                <td class="px-4 py-3 text-sm text-gray-700">
                                    {{ $history->updatedByEmployee ? $history->updatedByEmployee->firstname . ' ' . $history->updatedByEmployee->lastname : 'System' }}
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @else
                <div class="text-center py-8 text-gray-500">
                    <svg class="w-12 h-12 mx-auto mb-3 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                    <p>No price history available for this test.</p>
                </div>
                @endif
            </div>
            <div class="flex items-center justify-end p-6 border-t bg-gray-50">
                <button type="button" 
                        wire:click="closeHistoryModal"
                        class="px-4 py-2 bg-gray-200 hover:bg-gray-300 text-gray-700 font-medium rounded-lg transition-colors">
                    Close
                </button>
            </div>
        </div>
    </div>
    @endif
</div>