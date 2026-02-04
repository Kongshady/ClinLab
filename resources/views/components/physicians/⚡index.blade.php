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
    public $perPage = 'all';

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
        $query = Physician::active()
            ->when($this->search, function ($query) {
                $query->where(function ($q) {
                    $q->where('physician_name', 'like', '%' . $this->search . '%')
                      ->orWhere('specialization', 'like', '%' . $this->search . '%')
                      ->orWhere('email', 'like', '%' . $this->search . '%');
                });
            })
            ->orderBy('physician_id', 'desc');

        $physicians = $this->perPage === 'all' ? $query->get() : $query->paginate((int)$this->perPage);

        return [
            'physicians' => $physicians
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
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5.121 17.804A13.937 13.937 0 0112 16c2.5 0 4.847.655 6.879 1.804M15 10a3 3 0 11-6 0 3 3 0 016 0zm6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
        </svg>
        <h1 class="text-2xl font-bold text-gray-900">Physician Management</h1>
    </div>

    <!-- Add New Physician Form -->
    <div class="bg-white rounded-lg shadow-sm p-6">
        <h2 class="text-lg font-semibold text-gray-900 mb-6">Add New Physician</h2>
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
                <div class="flex justify-end mt-6">
                    <button type="submit" class="px-6 py-2 bg-pink-500 hover:bg-pink-600 text-white font-medium rounded-lg transition-colors">
                        Add Physician
                    </button>
                </div>
            </form>
    </div>

    <!-- Search -->
    <div class="bg-white rounded-lg shadow-sm p-6">
        <input type="text" wire:model.live="search" placeholder="Search by name, specialization, or email..." 
               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-pink-500 focus:border-transparent">
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

    <!-- Physicians List -->
    <div class="bg-white rounded-lg shadow-sm">
        <div class="p-6">
            <h2 class="text-lg font-semibold text-gray-900 mb-4">Physicians List</h2>
            
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead>
                        <tr class="bg-gray-50">
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Name</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Specialization</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Contact</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Email</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        @forelse($physicians as $physician)
                            <tr class="hover:bg-gray-50">
                                <td class="px-4 py-3 text-sm text-gray-900 font-medium">{{ $physician->physician_name }}</td>
                                <td class="px-4 py-3 text-sm text-gray-700">{{ $physician->specialization ?? 'N/A' }}</td>
                                <td class="px-4 py-3 text-sm text-gray-700">{{ $physician->contact_number ?? 'N/A' }}</td>
                                <td class="px-4 py-3 text-sm text-gray-700">{{ $physician->email ?? 'N/A' }}</td>
                                <td class="px-4 py-3">
                                    <div class="flex items-center space-x-2">
                                        <a href="/physicians/{{ $physician->physician_id }}/edit" 
                                           class="px-4 py-1.5 bg-orange-500 hover:bg-orange-600 text-white text-sm font-medium rounded-lg transition-colors">Edit</a>
                                        <button wire:click="delete({{ $physician->physician_id }})" 
                                                wire:confirm="Are you sure you want to delete this physician?"
                                                class="px-4 py-1.5 bg-red-500 hover:bg-red-600 text-white text-sm font-medium rounded-lg transition-colors">Delete</button>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-4 py-8 text-center text-gray-500">No physicians found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($perPage !== 'all')
            <div class="mt-6">
                {{ $physicians->links() }}
            </div>
            @endif
        </div>
    </div>
</div>