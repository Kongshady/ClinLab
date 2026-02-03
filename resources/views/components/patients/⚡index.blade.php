<?php

use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Validate;
use App\Models\Patient;

new class extends Component
{
    use WithPagination;

    // Form properties with validation
    #[Validate('required|in:Internal,External')]
    public $patient_type = 'External';
    
    #[Validate('required|string|max:50')]
    public $firstname = '';
    
    #[Validate('nullable|string|max:50')]
    public $middlename = '';
    
    #[Validate('required|string|max:50')]
    public $lastname = '';
    
    #[Validate('required|date')]
    public $birthdate = '';
    
    #[Validate('required|string|max:10')]
    public $gender = '';
    
    #[Validate('nullable|string|max:20')]
    public $contact_number = '';
    
    #[Validate('nullable|string|max:200')]
    public $address = '';

    // Search property
    public $search = '';

    // Flash message
    public $flashMessage = '';

    public function mount()
    {
        if (session('success')) {
            $this->flashMessage = session('success');
        }
    }

    public function save()
    {
        $this->validate();

        Patient::create([
            'patient_type' => $this->patient_type,
            'firstname' => $this->firstname,
            'middlename' => $this->middlename,
            'lastname' => $this->lastname,
            'birthdate' => $this->birthdate,
            'gender' => $this->gender,
            'contact_number' => $this->contact_number,
            'address' => $this->address,
        ]);

        $this->reset([
            'patient_type', 'firstname', 'middlename', 'lastname', 
            'birthdate', 'gender', 'contact_number', 'address'
        ]);
        
        $this->patient_type = 'External';
        $this->flashMessage = 'Patient added successfully.';
    }

    public function delete($id)
    {
        $patient = Patient::findOrFail($id);
        $patient->delete();
        $this->flashMessage = 'Patient deleted successfully.';
    }

    public function render()
    {
        $patients = Patient::active()
            ->when($this->search, function($query) {
                $query->where(function($q) {
                    $q->where('firstname', 'like', '%' . $this->search . '%')
                      ->orWhere('lastname', 'like', '%' . $this->search . '%')
                      ->orWhere('contact_number', 'like', '%' . $this->search . '%');
                });
            })
            ->orderBy('patient_id', 'desc')
            ->paginate(50);

        return view('livewire.patients-index', compact('patients'));
    }
};
?>

<div>
    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
        <div class="p-8">
            @if($flashMessage)
                <div class="mb-6 p-4 bg-green-50 border border-green-200 text-green-800 rounded-lg">
                    {{ $flashMessage }}
                </div>
            @endif

            <!-- Add New Patient Form -->
            <div class="mb-8">
                <h2 class="text-xl font-semibold text-gray-800 mb-4">Add New Patient</h2>
                <form wire:submit.prevent="save">
                    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Patient Type *</label>
                            <select wire:model="patient_type" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-pink-500 focus:border-transparent" required>
                                <option value="Internal">Internal</option>
                                <option value="External">External</option>
                            </select>
                            @error('patient_type') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">First Name *</label>
                            <input type="text" wire:model="firstname" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-pink-500 focus:border-transparent" required>
                            @error('firstname') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Middle Name</label>
                            <input type="text" wire:model="middlename" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-pink-500 focus:border-transparent">
                            @error('middlename') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Last Name *</label>
                            <input type="text" wire:model="lastname" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-pink-500 focus:border-transparent" required>
                            @error('lastname') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                        </div>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Date of Birth *</label>
                            <input type="date" wire:model="birthdate" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-pink-500 focus:border-transparent" required>
                            @error('birthdate') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Gender *</label>
                            <select wire:model="gender" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-pink-500 focus:border-transparent" required>
                                <option value="">Select Gender</option>
                                <option value="Male">Male</option>
                                <option value="Female">Female</option>
                            </select>
                            @error('gender') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Contact Number</label>
                            <input type="text" wire:model="contact_number" placeholder="09171234567" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-pink-500 focus:border-transparent">
                            @error('contact_number') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Address</label>
                            <input type="text" wire:model="address" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-pink-500 focus:border-transparent">
                            @error('address') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                        </div>
                    </div>
                    <button type="submit" class="px-6 py-2 bg-gradient-to-r from-pink-500 to-pink-600 text-white rounded-lg hover:from-pink-600 hover:to-pink-700 font-medium">
                        Add Patient
                    </button>
                </form>
            </div>

            <!-- Search Bar -->
            <div class="mb-6">
                <label class="block text-sm font-medium text-gray-700 mb-1">Search</label>
                <input type="text" wire:model.live="search" placeholder="Search by name or contact..." class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-pink-500 focus:border-transparent">
            </div>

            <!-- Patients List -->
            <div>
                <h3 class="text-lg font-semibold text-gray-800 mb-4">Patients List</h3>
                <div class="overflow-x-auto">
                    <table class="min-w-full">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Type</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Name</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Birthdate</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Gender</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Contact</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Address</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @forelse($patients as $patient)
                                <tr class="hover:bg-gray-50">
                                    <td class="px-6 py-4 text-sm text-gray-900">{{ $patient->patient_type }}</td>
                                    <td class="px-6 py-4 text-sm text-gray-900">{{ $patient->full_name }}</td>
                                    <td class="px-6 py-4 text-sm text-gray-600">{{ \Carbon\Carbon::parse($patient->birthdate)->format('m/d/Y') }}</td>
                                    <td class="px-6 py-4 text-sm text-gray-600">{{ $patient->gender }}</td>
                                    <td class="px-6 py-4 text-sm text-gray-600">{{ $patient->contact_number ?: '-' }}</td>
                                    <td class="px-6 py-4 text-sm text-gray-600">{{ $patient->address ?: '-' }}</td>
                                    <td class="px-6 py-4 text-sm space-x-2">
                                        <a href="{{ route('patients.edit', $patient->patient_id) }}" class="px-4 py-1.5 bg-orange-500 text-white rounded hover:bg-orange-600 font-medium inline-block">
                                            Edit
                                        </a>
                                        <button wire:click="delete({{ $patient->patient_id }})" wire:confirm="Are you sure you want to delete this patient?" class="px-4 py-1.5 bg-red-500 text-white rounded hover:bg-red-600 font-medium">
                                            Delete
                                        </button>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="px-6 py-8 text-center text-sm text-gray-500">No patients found.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                
                <!-- Pagination -->
                <div class="mt-4">
                    {{ $patients->links() }}
                </div>
            </div>
        </div>
    </div>
</div>