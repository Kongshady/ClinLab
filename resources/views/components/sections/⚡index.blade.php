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

    public function delete($id)
    {
        $section = Section::find($id);
        if ($section) {
            $section->softDelete();
            $this->logActivity("Deleted section ID {$id}: {$section->label}");
            $this->flashMessage = 'Section deleted successfully!';
        }
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
        <div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-lg" role="alert">
            <span class="block sm:inline">{{ $flashMessage }}</span>
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
            <h2 class="text-lg font-semibold text-gray-900 mb-4">Sections List</h2>
            
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead>
                        <tr class="bg-gray-50">
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Section Name</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        @forelse($sections as $section)
                            <tr class="hover:bg-gray-50">
                                <td class="px-4 py-3 text-sm text-gray-900 font-medium">{{ $section->label }}</td>
                                <td class="px-4 py-3">
                                    <div class="flex items-center space-x-2">
                                        <button wire:click="editSection({{ $section->section_id }})"
                                                type="button"
                                                class="px-4 py-1.5 bg-orange-500 hover:bg-orange-600 text-white text-sm font-medium rounded-lg transition-colors">
                                            Edit
                                        </button>
                                        <button wire:click="delete({{ $section->section_id }})" 
                                                wire:confirm="Are you sure you want to delete this section?"
                                                class="px-4 py-1.5 bg-red-500 hover:bg-red-600 text-white text-sm font-medium rounded-lg transition-colors">
                                            Delete
                                        </button>
                                    </div>
                                </td>
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
                            class="px-4 py-2 bg-orange-500 hover:bg-orange-600 text-white font-medium rounded-lg transition-colors">
                        Update Section
                    </button>
                </div>
            </form>
        </div>
    </div>
    @endif
</div>