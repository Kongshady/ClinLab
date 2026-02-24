<?php

use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Validate;
use App\Models\Section;
use App\Traits\LogsActivity;

new class extends Component
{
    use WithPagination, LogsActivity;

    #[Validate('required|string|max:255|unique:section,label')]
    public $label = '';

    public $search = '';
    public $flashMessage = '';
    public $perPage = 'all';

    // Delete confirmation modal properties
    public $showDeleteModal = false;
    public $deleteMessage = '';
    public $deleteAction = '';
    public $itemToDelete = null;
    public $itemName = '';

    // Selection properties
    public $selectedSections = [];
    public $selectAll = false;

    // Edit properties
    public $editingSectionId = null;
    public $edit_label = '';

    public function mount()
    {
        if (session()->has('success')) {
            $this->flashMessage = session('success');
        }
    }

    public function save()
    {
        $this->validate();

        Section::create([
            'label' => $this->label,
            'is_deleted' => 0,
        ]);

        $this->reset(['label']);
        $this->logActivity("Created section: {$this->label}");
        $this->flashMessage = 'Section added successfully!';
        $this->resetPage();
    }

    public function editSection($sectionId)
    {
        $section = Section::find($sectionId);
        if ($section) {
            $this->editingSectionId = $sectionId;
            $this->edit_label = $section->label;
        }
    }

    public function updateSection()
    {
        $this->validate([
            'edit_label' => 'required|string|max:255|unique:section,label,' . $this->editingSectionId . ',section_id',
        ]);

        $section = Section::find($this->editingSectionId);
        if ($section) {
            $section->update([
                'label' => $this->edit_label,
            ]);

            $this->logActivity("Updated section ID {$this->editingSectionId}: {$this->edit_label}");
            $this->flashMessage = 'Section updated successfully!';
            $this->closeEditModal();
        }
    }

    public function closeEditModal()
    {
        $this->editingSectionId = null;
        $this->reset(['edit_label']);
    }

    public function updatedSelectAll($value)
    {
        if ($value) {
            $query = Section::active()
                ->when($this->search, function ($query) {
                    $query->where('label', 'like', '%' . $this->search . '%');
                })
                ->orderBy('label');
            $sections = $this->perPage === 'all' ? $query->get() : $query->paginate((int)$this->perPage);
            $this->selectedSections = $sections->pluck('section_id')->map(fn($id) => (string)$id)->toArray();
        } else {
            $this->selectedSections = [];
        }
    }

    public function updatedSelectedSections()
    {
        $this->selectAll = false;
    }

    public function updatedSearch()
    {
        $this->selectedSections = [];
        $this->selectAll = false;
        $this->resetPage();
    }

    public function deleteSelected()
    {
        if (empty($this->selectedSections)) return;
        $count = count($this->selectedSections);
        $this->deleteMessage = "Are you sure you want to delete {$count} selected section(s)? This action cannot be undone.";
        $this->deleteAction = 'confirmDeleteSelected';
        $this->itemToDelete = $this->selectedSections;
        $this->showDeleteModal = true;
    }

    public function confirmDeleteSelected()
    {
        if (empty($this->itemToDelete)) return;
        $ids = is_array($this->itemToDelete) ? $this->itemToDelete : [$this->itemToDelete];
        foreach ($ids as $id) {
            $section = Section::find($id);
            if ($section) {
                $section->softDelete();
                $this->logActivity("Deleted section ID {$id}: {$section->label}");
            }
        }
        $this->flashMessage = count($ids) . ' section(s) deleted successfully!';
        $this->selectedSections = [];
        $this->selectAll = false;
        $this->resetPage();
        $this->closeDeleteModal();
    }

    public function delete($id)
    {
        $section = Section::find($id);
        if ($section) {
            $this->itemToDelete = $id;
            $this->itemName = $section->label;
            $this->deleteMessage = "Are you sure you want to delete the section '{$this->itemName}'? This action cannot be undone.";
            $this->deleteAction = 'confirmDelete';
            $this->showDeleteModal = true;
        }
    }

    public function confirmDelete()
    {
        if ($this->itemToDelete) {
            $section = Section::find($this->itemToDelete);
            if ($section) {
                $section->softDelete();
                $this->logActivity("Deleted section ID {$this->itemToDelete}: {$section->label}");
                $this->flashMessage = 'Section deleted successfully!';
            }
            $this->closeDeleteModal();
        }
    }

    public function closeDeleteModal()
    {
        $this->showDeleteModal = false;
        $this->deleteMessage = '';
        $this->deleteAction = '';
        $this->itemToDelete = null;
        $this->itemName = '';
    }

    public function with(): array
    {
        $query = Section::active()
            ->withCount(['tests' => function($query) {
                $query->where('is_deleted', 0);
            }])
            ->when($this->search, function ($query) {
                $query->where('label', 'like', '%' . $this->search . '%');
            })
            ->orderBy('label');

        $sections = $this->perPage === 'all' ? $query->get() : $query->paginate((int)$this->perPage);

        return [
            'sections' => $sections
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
        </div>
    @endif

    <!-- Header -->
    <div class="flex items-center space-x-3 mb-6">
        <svg class="w-8 h-8 text-gray-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
        </svg>
        <h1 class="text-2xl font-bold text-gray-900">Section Management</h1>
    </div>

    <!-- Add New Section Form -->
    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm mb-6">

        {{-- Card Header --}}
        <div class="px-6 py-5 flex items-center gap-4">
            <div class="w-10 h-10 rounded-2xl bg-red-50 flex items-center justify-center flex-shrink-0">
                <svg class="w-5 h-5 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"/>
                </svg>
            </div>
            <div>
                <h2 class="text-base font-bold text-gray-900 leading-tight">Add New Section</h2>
                <p class="text-xs text-gray-400 mt-0.5">Create a new laboratory section or department</p>
            </div>
        </div>

        <form wire:submit.prevent="save" class="px-6 pb-6">

            {{-- SECTION DETAILS divider --}}
            <div class="flex items-center gap-3 mb-4">
                <div class="flex-1 h-px bg-gray-100"></div>
                <span class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">Section Details</span>
                <div class="flex-1 h-px bg-gray-100"></div>
            </div>

            <div class="mb-6">
                <label class="block text-[10px] font-semibold text-gray-400 uppercase tracking-widest mb-1.5">
                    Section Name <span class="text-red-500">*</span>
                </label>
                <input type="text" wire:model="label" placeholder="e.g., Hematology, Chemistry, Microbiology"
                       class="w-full px-3 py-2.5 border border-gray-200 rounded-xl text-sm placeholder-gray-300 focus:outline-none focus:ring-2 focus:ring-red-400 focus:border-transparent transition-colors">
                @error('label') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
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
                    Add Section
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

    <!-- Sections List -->
    <div class="bg-white rounded-lg shadow-sm mb-6">
        <div class="px-6 py-4 border-b border-gray-200">
            <div class="flex items-center justify-between">
                <h2 class="text-lg font-semibold text-gray-900">Sections Directory</h2>
                <!-- Delete Selected Button -->
                <div x-show="$wire.selectedSections.length > 0" x-cloak x-transition>
                    <button type="button" 
                            @click="if(confirm('Are you sure you want to delete ' + $wire.selectedSections.length + ' selected section(s)?')) { $wire.deleteSelected() }"
                            class="inline-flex items-center gap-2 px-4 py-2 bg-red-500 hover:bg-red-600 text-white text-sm font-medium rounded-lg transition-colors">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                        </svg>
                        Delete Selected (<span x-text="$wire.selectedSections.length"></span>)
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
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Section Name</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Description</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($sections as $section)
                        <tr wire:key="section-{{ $section->section_id }}" 
                            class="hover:bg-gray-50 cursor-pointer transition-colors {{ in_array((string) $section->section_id, $selectedSections) ? 'bg-pink-50' : '' }}">
                            <td class="px-6 py-4" wire:click.stop>
                                <input type="checkbox" wire:model.live="selectedSections" value="{{ $section->section_id }}"
                                       class="h-4 w-4 rounded border-gray-300 text-pink-600 focus:ring-pink-500">
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex items-center">
                                    <div class="w-8 h-8 bg-pink-500 rounded-full flex items-center justify-center text-white text-xs font-bold mr-3">
                                        {{ strtoupper(substr($section->label, 0, 2)) }}
                                    </div>
                                    <div>
                                        <div class="font-medium text-gray-900">{{ $section->label }}</div>
                                        <div class="text-xs text-gray-500">ID: {{ $section->section_id }}</div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                {{ $section->description ?? 'No description' }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                <div class="flex items-center space-x-2">
                                    <button wire:click.stop="editSection({{ $section->section_id }})"
                                            class="inline-flex items-center px-3 py-1.5 border border-gray-300 rounded-md text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-pink-500">
                                        Edit
                                    </button>
                                    <button wire:click.stop="delete({{ $section->section_id }})"
                                            class="inline-flex items-center px-3 py-1.5 border border-red-300 rounded-md text-sm font-medium text-red-700 bg-white hover:bg-red-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500">
                                        Delete
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-6 py-12 text-center text-gray-500">
                                No sections found
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        
        @if($perPage !== 'all' && $sections instanceof \Illuminate\Pagination\LengthAwarePaginator && $sections->hasPages())
            <div class="px-6 py-4 border-t border-gray-200">
                {{ $sections->links() }}
            </div>
        @endif
    </div>

    <!-- Edit Section Modal -->
    @if($editingSectionId)
    <div class="fixed inset-0 bg-black/40 flex items-center justify-center z-50 p-4" wire:click.self="closeEditModal">
        <div class="bg-white rounded-lg shadow-xl w-full max-w-md overflow-hidden">
            <div class="flex items-center justify-between p-6 border-b">
                <h3 class="text-lg font-semibold text-gray-900">Edit Section</h3>
                <button wire:click="closeEditModal" class="text-gray-400 hover:text-gray-600">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>
            <form wire:submit.prevent="updateSection">
                <div class="p-6 space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Section Name <span class="text-red-500">*</span>
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
                            style="background-color: #DC143C;"
                            class="px-4 py-2 text-white font-medium rounded-lg hover:opacity-90 transition-opacity">
                        Update Section
                    </button>
                </div>
            </form>
        </div>
    </div>
    @endif

    {{-- Delete Confirmation Modal --}}
    @if($showDeleteModal)
    <div class="fixed inset-0 z-[60] overflow-y-auto flex items-center justify-center p-4 bg-black/40" wire:click.self="closeDeleteModal">
        <div class="bg-white rounded-2xl shadow-2xl max-w-md w-full overflow-hidden">
            <div class="p-6 text-center">
                <div class="mx-auto flex items-center justify-center h-14 w-14 rounded-full bg-red-100 mb-4">
                    <svg class="h-7 w-7 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                    </svg>
                </div>
                <h3 class="text-lg font-bold text-gray-900 mb-2">Delete Section</h3>
                <p class="text-sm text-gray-500">{{ $deleteMessage }}</p>
            </div>
            <div class="px-6 py-4 bg-gray-50 flex justify-center gap-3">
                <button wire:click="closeDeleteModal"
                        class="px-5 py-2.5 border border-gray-200 hover:bg-gray-100 text-gray-600 text-sm font-semibold rounded-xl transition-colors">
                    Cancel
                </button>
                <button wire:click="{{ $deleteAction }}"
                        class="px-5 py-2.5 bg-red-600 hover:bg-red-700 text-white text-sm font-semibold rounded-xl transition-colors">
                    Delete
                </button>
            </div>
        </div>
    </div>
    @endif
</div>