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

<div class="p-6">
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
    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm mb-6">

        {{-- Card Header --}}
        <div class="px-6 py-5 flex items-center gap-4">
            <div class="w-10 h-10 rounded-2xl bg-red-50 flex items-center justify-center flex-shrink-0">
                <svg class="w-5 h-5 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z"/>
                </svg>
            </div>
            <div>
                <h2 class="text-base font-bold text-gray-900 leading-tight">Add New Test</h2>
                <p class="text-xs text-gray-400 mt-0.5">Register a new laboratory test with pricing</p>
            </div>
        </div>

        <form wire:submit.prevent="save" class="px-6 pb-6">

            {{-- TEST DETAILS divider --}}
            <div class="flex items-center gap-3 mb-4">
                <div class="flex-1 h-px bg-gray-100"></div>
                <span class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">Test Details</span>
                <div class="flex-1 h-px bg-gray-100"></div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
                <div>
                    <label class="block text-[10px] font-semibold text-gray-400 uppercase tracking-widest mb-1.5">
                        Section <span class="text-red-500">*</span>
                    </label>
                    <select wire:model="section_id"
                            class="w-full px-3 py-2.5 border border-gray-200 rounded-xl text-sm bg-white focus:outline-none focus:ring-2 focus:ring-red-400 focus:border-transparent transition-colors appearance-none">
                        <option value="">Select Section</option>
                        @foreach($sections as $section)
                            <option value="{{ $section->section_id }}">{{ $section->label }}</option>
                        @endforeach
                    </select>
                    @error('section_id') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                </div>
                <div>
                    <label class="block text-[10px] font-semibold text-gray-400 uppercase tracking-widest mb-1.5">
                        Test Name <span class="text-red-500">*</span>
                    </label>
                    <input type="text" wire:model="label" placeholder="e.g., Complete Blood Count, Urinalysis"
                           class="w-full px-3 py-2.5 border border-gray-200 rounded-xl text-sm placeholder-gray-300 focus:outline-none focus:ring-2 focus:ring-red-400 focus:border-transparent transition-colors">
                    @error('label') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                </div>
                <div>
                    <label class="block text-[10px] font-semibold text-gray-400 uppercase tracking-widest mb-1.5">Initial Price (₱)</label>
                    <div class="relative">
                        <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-gray-400 text-sm">₱</span>
                        <input type="number" step="0.01" wire:model="current_price" placeholder="0.00"
                               class="w-full pl-7 pr-3 py-2.5 border border-gray-200 rounded-xl text-sm placeholder-gray-300 focus:outline-none focus:ring-2 focus:ring-red-400 focus:border-transparent transition-colors">
                    </div>
                    @error('current_price') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                </div>
            </div>

            {{-- Footer --}}
            <div class="flex items-center justify-between pt-4 border-t border-gray-100">
                <p class="text-xs text-gray-400">
                    Fields marked with <span class="text-red-500 font-semibold">*</span> are required
                </p>
                <button type="submit"
                        class="inline-flex items-center gap-2 px-5 py-2.5 bg-red-500 hover:bg-red-600 text-white text-sm font-semibold rounded-xl transition-colors">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                    </svg>
                    Add Test
                </button>
            </div>
        </form>
    </div>

    <!-- Rows per page -->
    <div class="flex items-center space-x-3 mb-6">
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
    <div class="bg-white rounded-lg shadow-sm mb-6">
        <div class="p-6">
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-lg font-semibold text-gray-900">Tests List</h2>
                <div class="flex items-center gap-3">
                    {{-- UPDATED: Delete button --}}
                    @if(count($selectedTests) > 0)
                    <button wire:click="deleteSelected" 
                            class="inline-flex items-center gap-2 px-4 py-2 bg-red-500 hover:bg-red-600 text-white text-sm font-medium rounded-lg transition-colors">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                        </svg>
                        Delete Selected ({{ count($selectedTests) }})
                    </button>
                    @endif
                    {{-- Search bar --}}
                    <div class="relative">
                        <svg class="w-4 h-4 absolute left-3 top-1/2 -translate-y-1/2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                        </svg>
                        <input type="text" wire:model.live="search" placeholder="Search tests..." 
                               class="pl-9 pr-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-pink-500 focus:border-transparent w-64">
                    </div>
                </div>
            </div>
            
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead>
                        <tr class="bg-gray-50">
                            <th class="px-4 py-3 text-left w-10">
                                <input type="checkbox" wire:model.live="selectAll"
                                       class="rounded border-gray-300 text-green-600 focus:ring-green-500 h-4 w-4">
                            </th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Test Name</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Section</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Previous Price</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Current Price</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        @forelse($tests as $test)
                            <tr class="hover:bg-gray-50 {{ in_array((string) $test->test_id, $selectedTests) ? 'bg-green-50' : '' }}">
                                <td class="px-4 py-3">
                                    <input type="checkbox" wire:model.live="selectedTests" value="{{ $test->test_id }}"
                                           class="rounded border-gray-300 text-green-600 focus:ring-green-500 h-4 w-4">
                                </td>
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
                                                class="px-4 py-1.5 bg-blue-500 hover:bg-blue-600 text-white text-sm font-medium rounded-lg transition-colors">
                                            Set Price
                                        </button>
                                        <button wire:click="viewHistory({{ $test->test_id }})"
                                                type="button"
                                                class="px-4 py-1.5 bg-blue-500 hover:bg-blue-600 text-white text-sm font-medium rounded-lg transition-colors">
                                            View History
                                        </button>
                                        <button wire:click="delete({{ $test->test_id }})" 
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

    {{-- Edit Test Modal --}}
    @if($editingTestId)
    <div class="fixed inset-0 bg-black/40 flex items-center justify-center z-50 p-4" wire:click.self="closeEditModal">
        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-lg max-h-[90vh] flex flex-col overflow-hidden">
            <div class="flex items-center justify-between px-7 py-5 rounded-t-2xl" style="background: #d2334c;">
                <div class="flex items-center gap-3">
                    <div class="bg-white/20 rounded-xl p-2.5">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-lg font-bold text-white">Edit Test</h3>
                        <p class="text-red-200 text-xs mt-0.5">Update test information</p>
                    </div>
                </div>
                <button wire:click="closeEditModal" class="text-white/70 hover:text-white transition-colors">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>

            <form wire:submit.prevent="updateTest" class="p-7 overflow-y-auto flex-1 space-y-5">
                <div>
                    <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1.5">Section *</label>
                    <select wire:model="edit_section_id" class="w-full px-3.5 py-2.5 border border-gray-200 rounded-lg text-sm focus:ring-2 focus:ring-red-500 focus:border-transparent bg-gray-50 focus:bg-white transition" required>
                        <option value="">Select Section</option>
                        @foreach($sections as $section)
                            <option value="{{ $section->section_id }}">{{ $section->label }}</option>
                        @endforeach
                    </select>
                    @error('edit_section_id') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1.5">Test Name *</label>
                    <input type="text" wire:model="edit_label" placeholder="e.g., Complete Blood Count" class="w-full px-3.5 py-2.5 border border-gray-200 rounded-lg text-sm focus:ring-2 focus:ring-red-500 focus:border-transparent bg-gray-50 focus:bg-white transition" required>
                    @error('edit_label') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                </div>
                <div class="flex justify-end gap-3 pt-4 border-t border-gray-100">
                    <button type="button" wire:click="closeEditModal" class="px-5 py-2.5 border border-gray-200 hover:bg-gray-50 text-gray-600 text-sm font-semibold rounded-xl transition-colors">Cancel</button>
                    <button type="submit" class="px-5 py-2.5 text-white text-sm font-semibold rounded-xl transition-colors" style="background-color:#d2334c" onmouseover="this.style.backgroundColor='#9f1239'" onmouseout="this.style.backgroundColor='#d2334c'">Update Test</button>
                </div>
            </form>
        </div>
    </div>
    @endif

    {{-- Set Price Modal --}}
    @if($setPriceTestId)
    <div class="fixed inset-0 bg-black/40 flex items-center justify-center z-50 p-4" wire:click.self="closeSetPriceModal">
        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-lg max-h-[90vh] flex flex-col overflow-hidden">
            <div class="flex items-center justify-between px-7 py-5 rounded-t-2xl" style="background: #2563eb;">
                <div class="flex items-center gap-3">
                    <div class="bg-white/20 rounded-xl p-2.5">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-lg font-bold text-white">Set Price</h3>
                        <p class="text-blue-200 text-xs mt-0.5">{{ $setPriceTestName }}</p>
                    </div>
                </div>
                <button wire:click="closeSetPriceModal" class="text-white/70 hover:text-white transition-colors">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>

            <form wire:submit.prevent="updatePrice" class="p-7 overflow-y-auto flex-1 space-y-5">
                <div class="rounded-xl p-5" style="background-color:#eff6ff; border: 1px solid #bfdbfe;">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-wider text-blue-600">Current Price</p>
                            <p class="text-2xl font-bold text-gray-900 mt-1">₱{{ number_format($setPriceCurrentPrice, 2) }}</p>
                        </div>
                        <div class="bg-blue-100 rounded-full p-3">
                            <svg class="w-8 h-8 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/>
                            </svg>
                        </div>
                    </div>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1.5">New Price (₱) *</label>
                    <input type="number" step="0.01" wire:model="new_price" placeholder="0.00" class="w-full px-3.5 py-2.5 border border-gray-200 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent bg-gray-50 focus:bg-white transition" required>
                    @error('new_price') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                </div>
                <div class="flex justify-end gap-3 pt-4 border-t border-gray-100">
                    <button type="button" wire:click="closeSetPriceModal" class="px-5 py-2.5 border border-gray-200 hover:bg-gray-50 text-gray-600 text-sm font-semibold rounded-xl transition-colors">Cancel</button>
                    <button type="submit" class="px-5 py-2.5 text-white text-sm font-semibold rounded-xl transition-colors" style="background-color:#2563eb" onmouseover="this.style.backgroundColor='#1d4ed8'" onmouseout="this.style.backgroundColor='#2563eb'">Update Price</button>
                </div>
            </form>
        </div>
    </div>
    @endif

    {{-- View Price History Modal --}}
    @if($viewHistoryTestId)
    <div class="fixed inset-0 bg-black/40 flex items-center justify-center z-50 p-4" wire:click.self="closeHistoryModal">
        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-3xl max-h-[90vh] flex flex-col overflow-hidden">
            <div class="flex items-center justify-between px-7 py-5 rounded-t-2xl" style="background: #2563eb;">
                <div class="flex items-center gap-3">
                    <div class="bg-white/20 rounded-xl p-2.5">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-lg font-bold text-white">Price History</h3>
                        <p class="text-blue-200 text-xs mt-0.5">{{ $viewHistoryTestName }}</p>
                    </div>
                </div>
                <button wire:click="closeHistoryModal" class="text-white/70 hover:text-white transition-colors">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>

            <div class="p-7 overflow-y-auto flex-1">
                @if($priceHistory && count($priceHistory) > 0)
                    <div class="overflow-x-auto rounded-xl border border-gray-200">
                        <table class="w-full text-sm">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-4 py-2.5 text-left text-xs font-semibold text-gray-500 uppercase">#</th>
                                    <th class="px-4 py-2.5 text-left text-xs font-semibold text-gray-500 uppercase">Previous Price</th>
                                    <th class="px-4 py-2.5 text-left text-xs font-semibold text-gray-500 uppercase">New Price</th>
                                    <th class="px-4 py-2.5 text-left text-xs font-semibold text-gray-500 uppercase">Change</th>
                                    <th class="px-4 py-2.5 text-left text-xs font-semibold text-gray-500 uppercase">Updated By</th>
                                    <th class="px-4 py-2.5 text-left text-xs font-semibold text-gray-500 uppercase">Date</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                @foreach($priceHistory as $index => $history)
                                    @php
                                        $diff = $history->new_price - $history->previous_price;
                                        $isIncrease = $diff > 0;
                                    @endphp
                                    <tr class="hover:bg-gray-50">
                                        <td class="px-4 py-3 text-gray-500">{{ $index + 1 }}</td>
                                        <td class="px-4 py-3 text-gray-700">₱{{ number_format($history->previous_price, 2) }}</td>
                                        <td class="px-4 py-3 font-semibold text-gray-900">₱{{ number_format($history->new_price, 2) }}</td>
                                        <td class="px-4 py-3">
                                            <span class="inline-flex items-center gap-1 text-xs font-semibold px-2 py-1 rounded-full {{ $isIncrease ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700' }}">
                                                @if($isIncrease)
                                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7"/></svg>
                                                @else
                                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                                                @endif
                                                {{ $isIncrease ? '+' : '' }}₱{{ number_format($diff, 2) }}
                                            </span>
                                        </td>
                                        <td class="px-4 py-3 text-gray-700">
                                            {{ $history->updatedByEmployee ? $history->updatedByEmployee->firstname . ' ' . $history->updatedByEmployee->lastname : 'N/A' }}
                                        </td>
                                        <td class="px-4 py-3 text-gray-500">{{ $history->updated_at ? \Carbon\Carbon::parse($history->updated_at)->format('M d, Y h:i A') : 'N/A' }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="text-center py-12">
                        <div class="mx-auto mb-4 w-14 h-14 rounded-full bg-gray-100 flex items-center justify-center">
                            <svg class="w-7 h-7 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                        </div>
                        <p class="text-sm text-gray-500 font-medium">No price history available for this test.</p>
                        <p class="text-xs text-gray-400 mt-1">Price changes will appear here once updated.</p>
                    </div>
                @endif

                <div class="flex justify-end pt-5 mt-5 border-t border-gray-100">
                    <button wire:click="closeHistoryModal" class="px-5 py-2.5 border border-gray-200 hover:bg-gray-50 text-gray-600 text-sm font-semibold rounded-xl transition-colors">Close</button>
                </div>
            </div>
        </div>
    </div>
    @endif

    {{-- Delete Confirmation Modal --}}
    @if($showDeleteModal)
    <div class="fixed inset-0 bg-black/40 flex items-center justify-center z-[60] p-4" wire:click.self="closeDeleteModal">
        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md overflow-hidden">
            <div class="p-8 text-center">
                <div class="mx-auto mb-4 w-14 h-14 rounded-full bg-red-100 flex items-center justify-center">
                    <svg class="w-7 h-7 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z"/>
                    </svg>
                </div>
                <h3 class="text-lg font-bold text-gray-900 mb-2">Delete Test</h3>
                <p class="text-sm text-gray-600 mb-6">{{ $deleteMessage }}</p>
                <div class="flex justify-center gap-3">
                    <button wire:click="closeDeleteModal" class="px-5 py-2.5 border border-gray-200 hover:bg-gray-50 text-gray-600 text-sm font-semibold rounded-xl transition-colors">Cancel</button>
                    <button wire:click="{{ $deleteAction }}" class="px-5 py-2.5 bg-red-600 hover:bg-red-700 text-white text-sm font-semibold rounded-xl transition-colors">Confirm Delete</button>
                </div>
            </div>
        </div>
    </div>
    @endif
</div>