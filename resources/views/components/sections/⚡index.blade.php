<?php

use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Validate;
use App\Models\Section;

new class extends Component
{
    use WithPagination;

    #[Validate('required|string|max:255|unique:section,label')]
    public $label = '';

    public $search = '';
    public $flashMessage = '';

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
        $this->flashMessage = 'Section added successfully!';
        $this->resetPage();
    }

    public function delete($id)
    {
        $section = Section::find($id);
        if ($section) {
            $section->softDelete();
            $this->flashMessage = 'Section deleted successfully!';
        }
    }

    public function with(): array
    {
        return [
            'sections' => Section::active()
                ->withCount(['tests' => function($query) {
                    $query->where('is_deleted', 0);
                }])
                ->when($this->search, function ($query) {
                    $query->where('label', 'like', '%' . $this->search . '%');
                })
                ->orderBy('label')
                ->paginate(50)
        ];
    }
};
?>

<div class="p-6">
    <div class="max-w-7xl mx-auto">
        @if($flashMessage)
            <div class="mb-6 bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-xl" role="alert">
                <span class="block sm:inline">{{ $flashMessage }}</span>
            </div>
        @endif

        <div class="card mb-6 p-6">
            <h2 class="text-xl font-semibold text-gray-900 mb-6">Add New Section</h2>
            <form wire:submit.prevent="save">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Section Name *</label>
                        <input type="text" wire:model="label" placeholder="e.g., Hematology, Chemistry, Microbiology"
                               class="input-field">
                        @error('label') <span class="text-red-600 text-sm">{{ $message }}</span> @enderror
                    </div>
                </div>
                <div class="mt-6">
                    <button type="submit" class="btn-primary">
                        Add Section
                    </button>
                </div>
            </form>
        </div>

        <div class="card p-6">
            <div class="mb-6">
                <input type="text" wire:model.live="search" placeholder="Search sections..." 
                       class="input-field">
            </div>
            <div class="overflow-x-auto">
                <table class="table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Section Name</th>
                            <th>Tests Count</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($sections as $section)
                            <tr>
                                <td>{{ $section->section_id }}</td>
                                <td class="font-medium text-gray-900">{{ $section->label }}</td>
                                <td>{{ $section->tests_count }} tests</td>
                                <td class="space-x-2">
                                    <a href="/sections/{{ $section->section_id }}/edit" 
                                       class="text-blue-600 hover:text-blue-700 font-medium">Edit</a>
                                    <button wire:click="delete({{ $section->section_id }})" 
                                            wire:confirm="Are you sure you want to delete this section?"
                                            class="text-red-600 hover:text-red-700 font-medium">Delete</button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="text-center text-gray-500">No sections found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="mt-6">
                {{ $sections->links() }}
            </div>
        </div>
    </div>
</div>