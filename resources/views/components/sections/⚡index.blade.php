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

<div class="p-6 space-y-6">
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
    <div class="bg-white rounded-lg shadow-sm p-6">
        <h2 class="text-lg font-semibold text-gray-900 mb-6">Add New Section</h2>
        <form wire:submit.prevent="save">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 items-end">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        Section Name <span class="text-red-500">*</span>
                    </label>
                    <input type="text" 
                           wire:model="label" 
                           placeholder="e.g., Hematology, Chemistry, Microbiology"
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-pink-500 focus:border-transparent">
                    @error('label') <span class="text-red-600 text-xs mt-1">{{ $message }}</span> @enderror
                </div>
                <div>
                    <button type="submit" 
                            class="w-full px-6 py-2 bg-pink-500 hover:bg-pink-600 text-white font-medium rounded-lg transition-colors">
                        Add Section
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

    <!-- Sections List -->
    <div class="bg-white rounded-lg shadow-sm">
        <div class="p-6">
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-lg font-semibold text-gray-900">Sections List</h2>
                <div class="flex items-center gap-3">
                    @if(count($selectedSections) > 0)
                    <button wire:click="deleteSelected"
                            class="inline-flex items-center gap-2 px-4 py-2 bg-red-500 hover:bg-red-600 text-white text-sm font-medium rounded-lg transition-colors">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                        </svg>
                        Delete Selected ({{ count($selectedSections) }})
                    </button>
                    @endif
                    <div class="relative">
                        <svg class="w-4 h-4 absolute left-3 top-1/2 -translate-y-1/2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                        </svg>
                        <input type="text" wire:model.live="search" placeholder="Search sections..."
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
                                       class="w-4 h-4 text-pink-500 rounded border-gray-300 focus:ring-pink-500">
                            </th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Section Name</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        @forelse($sections as $section)
                            <tr class="hover:bg-gray-50 cursor-pointer {{ in_array((string) $section->section_id, $selectedSections) ? 'bg-pink-50' : '' }}">
                                <td class="px-4 py-3" wire:click.stop>
                                    <input type="checkbox" wire:model.live="selectedSections"
                                           value="{{ $section->section_id }}"
                                           class="w-4 h-4 text-pink-500 rounded border-gray-300 focus:ring-pink-500">
                                </td>
                                <td class="px-4 py-3 text-sm text-gray-900 font-medium"
                                    wire:click="editSection({{ $section->section_id }})">{{ $section->label }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="2" class="px-4 py-8 text-center text-gray-500">No sections found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($perPage !== 'all')
            <div class="mt-6">
                {{ $sections->links() }}
            </div>
            @endif
        </div>
    </div>

    <!-- Edit Section Modal -->
    @if($editingSectionId)
    <div class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
        <div class="bg-white rounded-lg shadow-xl w-full max-w-md">
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
    <div class="fixed inset-0 z-50 overflow-y-auto" style="background-color: rgba(0, 0, 0, 0.5);">
        <div class="flex items-center justify-center min-h-screen p-4">
            <div class="bg-white rounded-lg shadow-xl max-w-md w-full">
                <!-- Modal Header -->
                <div class="px-6 py-4 border-b border-gray-200">
                    <div class="flex items-center">
                        <svg class="w-6 h-6 text-red-500 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z"/>
                        </svg>
                        <h3 class="text-lg font-semibold text-gray-900">Delete Section</h3>
                    </div>
                </div>

                <!-- Modal Body -->
                <div class="p-6">
                    <p class="text-sm text-gray-700">{{ $deleteMessage }}</p>
                </div>

                <!-- Modal Footer -->
                <div class="px-6 py-4 bg-gray-50 border-t border-gray-200 flex justify-end space-x-3 rounded-b-lg">
                    <button wire:click="closeDeleteModal"
                            class="px-4 py-2 bg-gray-200 hover:bg-gray-300 text-gray-700 font-medium rounded-lg transition-colors">
                        Cancel
                    </button>
                    <button wire:click="{{ $deleteAction }}"
                            class="px-4 py-2 bg-red-500 hover:bg-red-600 text-white font-medium rounded-lg transition-colors">
                        Delete
                    </button>
                </div>
            </div>
        </div>
    </div>
    @endif
</div>