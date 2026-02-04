<?php

use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Validate;
use App\Models\Physician;

new class extends Component
{
    use WithPagination;

    #[Validate('required|string|max:255')]
    public $physician_name = '';

    #[Validate('nullable|string|max:255')]
    public $specialization = '';

    #[Validate('nullable|string|max:20')]
    public $contact_number = '';

    #[Validate('nullable|email|max:255')]
    public $email = '';

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

        Physician::create([
            'physician_name' => $this->physician_name,
            'specialization' => $this->specialization,
            'contact_number' => $this->contact_number,
            'email' => $this->email,
            'status_code' => 1,
            'is_deleted' => 0,
            'datetime_added' => now(),
        ]);

        $this->reset(['physician_name', 'specialization', 'contact_number', 'email']);
        $this->flashMessage = 'Physician added successfully!';
        $this->resetPage();
    }

    public function delete($id)
    {
        $physician = Physician::find($id);
        if ($physician) {
            $physician->softDelete();
            $this->flashMessage = 'Physician deleted successfully!';
        }
    }

    public function with(): array
    {
        return [
            'physicians' => Physician::active()
                ->when($this->search, function ($query) {
                    $query->where(function ($q) {
                        $q->where('physician_name', 'like', '%' . $this->search . '%')
                          ->orWhere('specialization', 'like', '%' . $this->search . '%')
                          ->orWhere('email', 'like', '%' . $this->search . '%');
                    });
                })
                ->orderBy('physician_id', 'desc')
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
            <h2 class="text-xl font-semibold text-gray-900 mb-6">Add New Physician</h2>
            <form wire:submit.prevent="save">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Physician Name *</label>
                        <input type="text" wire:model="physician_name" 
                               class="input-field">
                        @error('physician_name') <span class="text-red-600 text-sm">{{ $message }}</span> @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Specialization</label>
                        <input type="text" wire:model="specialization" 
                               class="input-field">
                        @error('specialization') <span class="text-red-600 text-sm">{{ $message }}</span> @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Contact Number</label>
                        <input type="text" wire:model="contact_number" 
                               class="input-field">
                        @error('contact_number') <span class="text-red-600 text-sm">{{ $message }}</span> @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Email</label>
                        <input type="email" wire:model="email" 
                               class="input-field">
                        @error('email') <span class="text-red-600 text-sm">{{ $message }}</span> @enderror
                    </div>
                </div>
                <div class="mt-6">
                    <button type="submit" class="btn-primary">
                        Add Physician
                    </button>
                </div>
            </form>
        </div>

        <div class="card p-6">
            <div class="mb-6">
                <input type="text" wire:model.live="search" placeholder="Search physicians..." 
                       class="input-field">
            </div>
            <div class="overflow-x-auto">
                <table class="table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Specialization</th>
                            <th>Contact</th>
                            <th>Email</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($physicians as $physician)
                            <tr>
                                <td>{{ $physician->physician_id }}</td>
                                <td class="font-medium">{{ $physician->physician_name }}</td>
                                <td>{{ $physician->specialization ?? 'N/A' }}</td>
                                <td>{{ $physician->contact_number ?? 'N/A' }}</td>
                                <td>{{ $physician->email ?? 'N/A' }}</td>
                                <td class="space-x-2">
                                    <a href="/physicians/{{ $physician->physician_id }}/edit" 
                                       class="text-blue-600 hover:text-blue-700 font-medium">Edit</a>
                                    <button wire:click="delete({{ $physician->physician_id }})" 
                                            wire:confirm="Are you sure you want to delete this physician?"
                                            class="text-red-600 hover:text-red-700 font-medium">Delete</button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center text-gray-500">No physicians found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="mt-6">
                {{ $physicians->links() }}
            </div>
        </div>
    </div>
</div>