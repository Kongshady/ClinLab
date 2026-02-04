<?php

use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Validate;
use App\Models\Test;
use App\Models\Section;

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
            'is_deleted' => 0,
        ]);

        $this->reset(['section_id', 'label', 'current_price']);
        $this->flashMessage = 'Test added successfully!';
        $this->resetPage();
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
        return [
            'tests' => Test::active()
                ->with('section')
                ->when($this->search, function ($query) {
                    $query->where(function ($q) {
                        $q->where('label', 'like', '%' . $this->search . '%');
                    });
                })
                ->orderBy('test_id', 'desc')
                ->paginate(50),
            'sections' => Section::active()->orderBy('label')->get()
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
            <h2 class="text-xl font-semibold text-gray-900 mb-6">Add New Test</h2>
            <form wire:submit.prevent="save">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Section *</label>
                        <select wire:model="section_id" 
                                class="input-field">
                            <option value="">Select Section</option>
                            @foreach($sections as $section)
                                <option value="{{ $section->section_id }}">{{ $section->label }}</option>
                            @endforeach
                        </select>
                        @error('section_id') <span class="text-red-600 text-sm">{{ $message }}</span> @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Test Name *</label>
                        <input type="text" wire:model="label" 
                               class="input-field">
                        @error('label') <span class="text-red-600 text-sm">{{ $message }}</span> @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Price *</label>
                        <input type="number" step="0.01" wire:model="current_price" 
                               class="input-field">
                        @error('current_price') <span class="text-red-600 text-sm">{{ $message }}</span> @enderror
                    </div>
                </div>
                <div class="mt-6">
                    <button type="submit" class="btn-primary">
                        Add Test
                    </button>
                </div>
            </form>
        </div>

        <div class="card p-6">
            <div class="mb-6">
                <input type="text" wire:model.live="search" placeholder="Search tests..." 
                       class="input-field">
            </div>
            <div class="overflow-x-auto">
                <table class="table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Test Name</th>
                            <th>Section</th>
                            <th>Price</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($tests as $test)
                            <tr>
                                <td>{{ $test->test_id }}</td>
                                <td class="font-medium">{{ $test->label }}</td>
                                <td>{{ $test->section->label ?? 'N/A' }}</td>
                                <td>₱{{ number_format($test->current_price, 2) }}</td>
                                <td class="space-x-2">
                                    <a href="/tests/{{ $test->test_id }}/edit" 
                                       class="text-blue-600 hover:text-blue-700 font-medium">Edit</a>
                                    <button wire:click="delete({{ $test->test_id }})" 
                                            wire:confirm="Are you sure you want to delete this test?"
                                            class="text-red-600 hover:text-red-700 font-medium">Delete</button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center text-gray-500">No tests found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="mt-6">
                {{ $tests->links() }}
            </div>
        </div>
    </div>
</div>