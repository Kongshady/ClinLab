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

    public function closeDeleteModal()
    {
        $this->showDeleteModal = false;
        $this->deleteMessage = '';
        $this->deleteAction = '';
        $this->testToDelete = null;
    }

    // UPDATED: Selection methods
    public function updatedSelectAll($value)
    {
        if ($value) {
            $query = Test::with('section')
                ->when($this->search, function ($query) {
                    $query->where('label', 'like', '%' . $this->search . '%')
                          ->orWhereHas('section', function($q) {
                              $q->where('label', 'like', '%' . $this->search . '%');
                          });
                })
                ->where('is_deleted', 0)
                ->orderBy('label');

            $tests = $this->perPage === 'all' ? $query->get() : $query->paginate((int)$this->perPage);
            $this->selectedTests = $tests->pluck('test_id')
                ->map(fn($id) => (string)$id)->toArray();
        } else {
            $this->selectedTests = [];
        }
    }

    public function updatedSelectedTests()
    {
        $this->selectAll = false;
    }

    public function updatedSearch()
    {
        $this->selectedTests = [];
        $this->selectAll = false;
        $this->resetPage();
    }

    // UPDATED: Delete selected method
    public function deleteSelected()
    {
        if (empty($this->selectedTests)) return;
        
        $count = count($this->selectedTests);
        $this->deleteMessage = "Are you sure you want to delete {$count} selected test(s)? This action cannot be undone.";
        $this->deleteAction = 'confirmDeleteSelected';
        $this->testToDelete = $this->selectedTests;
        $this->showDeleteModal = true;
    }

    // UPDATED: Confirm delete selected method
    public function confirmDeleteSelected()
    {
        if (empty($this->testToDelete)) return;
        
        $count = Test::whereIn('test_id', $this->testToDelete)->update(['is_deleted' => 1]);
        $this->flashMessage = $count . ' test(s) deleted successfully!';
        $this->selectedTests = [];
        $this->selectAll = false;
        $this->resetPage();
        $this->closeDeleteModal();
    }

    // UPDATED: Web search modal methods
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
        if (strlen($this->searchQuery) > 2) {
            // Your search logic here - placeholder
            $this->searchResults = collect([
                (object)['title' => 'Sample Result 1', 'snippet' => 'This is a sample search result for ' . $this->searchQuery],
                (object)['title' => 'Sample Result 2', 'snippet' => 'Another sample result related to your query']
            ]);
        }
    }

    // Delete confirmation modal
    public $showDeleteModal = false;
    public $deleteMessage = '';
    public $deleteAction = '';
    public $testToDelete = null;

    // UPDATED: Selection properties
    public $selectedTests = [];
    public $selectAll = false;

    // UPDATED: Web search modal properties
    public $showSearchModal = false;
    public $searchQuery = '';
    public $searchResults = null;

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
        $this->testToDelete = $id;
        $test = Test::findOrFail($id);
        $this->deleteMessage = "Are you sure you want to delete test '{$test->label}'? This action cannot be undone.";
        $this->deleteAction = 'confirmDelete';
        $this->showDeleteModal = true;
    }

    public function confirmDelete()
    {
        if ($this->testToDelete) {
            $test = Test::find($this->testToDelete);
            if ($test) {
                $test->softDelete();
                $this->flashMessage = 'Test deleted successfully!';
            }
        }
        $this->closeDeleteModal();
    }

    public function clearFlashMessage()
    {
        $this->flashMessage = null;
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
        <div class="mb-6 bg-white border-l-4 border-green-500 shadow-sm rounded-lg p-4 flex items-center justify-between">
            <div class="flex items-center">
                <svg class="w-5 h-5 text-green-500 mr-3" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                </svg>
                <span class="text-sm font-medium text-gray-900">{{ $flashMessage }}</span>
            </div>
            <button wire:click="clearFlashMessage" class="text-gray-400 hover:text-gray-600 transition-colors">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
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

    <!-- Tests List Card -->
    <div class="bg-white rounded-lg shadow-sm">
        <div class="px-6 py-4 border-b border-gray-200">
            <div class="flex items-center justify-between">
                <h2 class="text-lg font-semibold text-gray-900">Tests Directory</h2>
                <!-- Delete Selected Button -->
                <div x-show="$wire.selectedTests.length > 0" x-cloak x-transition>
                    <button type="button" 
                            @click="if(confirm('Are you sure you want to delete ' + $wire.selectedTests.length + ' selected test(s)?')) { $wire.deleteSelected() }"
                            class="inline-flex items-center gap-2 px-4 py-2 bg-red-500 hover:bg-red-600 text-white text-sm font-medium rounded-lg transition-colors">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                        </svg>
                        Delete Selected (<span x-text="$wire.selectedTests.length"></span>)
                    </button>
                </div>
            </div>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left w-10">
                            <input type="checkbox" wire:model.live="selectAll"
                                   class="h-4 w-4 rounded border-gray-300 text-pink-600 focus:ring-pink-500">
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Test Name</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Section</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Current Price</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($tests as $test)
                        <tr wire:key="test-{{ $test->test_id }}" 
                            class="hover:bg-gray-50 cursor-pointer transition-colors {{ in_array((string) $test->test_id, $selectedTests) ? 'bg-pink-50' : '' }}">
                            <td class="px-6 py-4" wire:click.stop>
                                <input type="checkbox" wire:model.live="selectedTests" value="{{ $test->test_id }}"
                                       class="h-4 w-4 rounded border-gray-300 text-pink-600 focus:ring-pink-500">
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex items-center">
                                    <div class="w-8 h-8 bg-pink-500 rounded-full flex items-center justify-center text-white text-xs font-bold mr-3">
                                        {{ strtoupper(substr($test->label, 0, 2)) }}
                                    </div>
                                    <div>
                                        <div class="font-medium text-gray-900">{{ $test->label }}</div>
                                        <div class="text-xs text-gray-500">ID: {{ $test->test_id }}</div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                {{ $test->section->label ?? 'N/A' }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                ₱{{ number_format($test->current_price, 2) }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                <div class="flex items-center space-x-2">
                                    <button wire:click.stop="editTest({{ $test->test_id }})"
                                            class="inline-flex items-center px-3 py-1.5 border border-gray-300 rounded-md text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-pink-500">
                                        Edit
                                    </button>
                                    <button wire:click.stop="openSetPriceModal({{ $test->test_id }})"
                                            class="inline-flex items-center px-3 py-1.5 border border-gray-300 rounded-md text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-pink-500">
                                        Set Price
                                    </button>
                                    <button wire:click.stop="viewHistory({{ $test->test_id }})"
                                            class="inline-flex items-center px-3 py-1.5 border border-gray-300 rounded-md text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-pink-500">
                                        History
                                    </button>
                                    <button wire:click.stop="delete({{ $test->test_id }})"
                                            class="inline-flex items-center px-3 py-1.5 border border-red-300 rounded-md text-sm font-medium text-red-700 bg-white hover:bg-red-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500">
                                        Delete
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-6 py-12 text-center text-gray-500">
                                No tests found
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        
        @if($perPage !== 'all' && $tests instanceof \Illuminate\Pagination\LengthAwarePaginator && $tests->hasPages())
            <div class="px-6 py-4 border-t border-gray-200">
                {{ $tests->links() }}
            </div>
        @endif
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
                            class="px-4 py-2 bg-[#DC143C] hover:bg-[#C41E3A] text-white font-medium rounded-lg transition-colors">
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
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
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
                            class="px-4 py-2 bg-blue-500 hover:bg-blue-600 text-white font-medium rounded-lg transition-colors">
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

    {{-- UPDATED: Web Search Modal --}}
    @if($showSearchModal)
    <div class="fixed inset-0 z-50 overflow-y-auto" style="background-color: rgba(0, 0, 0, 0.5);">
        <div class="flex items-center justify-center min-h-screen p-4">
            <div class="bg-white rounded-lg shadow-xl max-w-2xl w-full">
                <div class="px-6 py-4 border-b border-gray-200">
                    <div class="flex items-center justify-between">
                        <h3 class="text-xl font-semibold text-gray-900">Web Search</h3>
                        <button type="button" wire:click="closeSearchModal" class="text-gray-400 hover:text-gray-600">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </button>
                    </div>
                </div>
                <div class="p-6">
                    <input type="text" wire:model.live.debounce.500ms="searchQuery" 
                           placeholder="Enter search query..." 
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 mb-4">
                    
                    @if($searchResults)
                    <div class="space-y-3 max-h-96 overflow-y-auto">
                        @foreach($searchResults as $result)
                        <div class="p-3 border border-gray-200 rounded-lg hover:bg-gray-50">
                            <h4 class="font-semibold text-gray-900">{{ $result->title }}</h4>
                            <p class="text-sm text-gray-600">{{ $result->snippet }}</p>
                        </div>
                        @endforeach
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
    @endif
</div>