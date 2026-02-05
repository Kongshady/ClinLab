@extends('layouts.app')

@section('content')
<div class="p-8 space-y-6 bg-gray-50 min-h-screen">
    <!-- Header with Back Button -->
    <div class="flex items-center justify-between mb-6">
        <div class="flex items-center space-x-4">
            <a href="{{ route('patients.index') }}" class="text-gray-600 hover:text-gray-800">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                </svg>
            </a>
            <div>
                <h1 class="text-3xl font-bold text-gray-800">Patient Details</h1>
                <p class="text-gray-600 mt-1">Patient ID: {{ $patient->patient_id }}</p>
            </div>
        </div>
        <div class="flex items-center space-x-2">
            @can('patients.edit')
            <button onclick="openEditModal()" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg text-sm font-medium transition-colors">
                Edit Patient
            </button>
            @endcan
            @can('patients.delete')
            <form action="{{ route('patients.destroy', $patient) }}" method="POST" class="inline" onsubmit="return confirm('Are you sure you want to delete this patient?');">
                @csrf
                @method('DELETE')
                <button type="submit" class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white rounded-lg text-sm font-medium transition-colors">
                    Delete Patient
                </button>
            </form>
            @endcan
        </div>
    </div>

    <!-- Patient Information Card -->
    <div class="bg-white rounded-lg shadow border border-gray-200">
        <div class="p-6 border-b border-gray-200">
            <h3 class="text-lg font-semibold text-gray-900">Personal Information</h3>
        </div>
        <div class="p-6">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Patient Type -->
                <div>
                    <label class="block text-sm font-medium text-gray-600 mb-1">Patient Type</label>
                    <span class="inline-flex px-3 py-1 text-sm font-medium rounded-full {{ $patient->patient_type === 'Internal' ? 'bg-blue-100 text-blue-800' : 'bg-green-100 text-green-800' }}">
                        {{ $patient->patient_type }}
                    </span>
                </div>

                <!-- Status -->
                <div>
                    <label class="block text-sm font-medium text-gray-600 mb-1">Status</label>
                    <span class="inline-flex px-3 py-1 text-sm font-medium rounded-full {{ $patient->status_code == 1 ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800' }}">
                        {{ $patient->status_code == 1 ? 'Active' : 'Inactive' }}
                    </span>
                </div>

                <!-- First Name -->
                <div>
                    <label class="block text-sm font-medium text-gray-600 mb-1">First Name</label>
                    <p class="text-base text-gray-900">{{ $patient->firstname }}</p>
                </div>

                <!-- Middle Name -->
                <div>
                    <label class="block text-sm font-medium text-gray-600 mb-1">Middle Name</label>
                    <p class="text-base text-gray-900">{{ $patient->middlename ?: '-' }}</p>
                </div>

                <!-- Last Name -->
                <div>
                    <label class="block text-sm font-medium text-gray-600 mb-1">Last Name</label>
                    <p class="text-base text-gray-900">{{ $patient->lastname }}</p>
                </div>

                <!-- Full Name -->
                <div>
                    <label class="block text-sm font-medium text-gray-600 mb-1">Full Name</label>
                    <p class="text-base font-semibold text-gray-900">{{ $patient->full_name }}</p>
                </div>

                <!-- Gender -->
                <div>
                    <label class="block text-sm font-medium text-gray-600 mb-1">Gender</label>
                    <p class="text-base text-gray-900">{{ $patient->gender }}</p>
                </div>

                <!-- Birthdate -->
                <div>
                    <label class="block text-sm font-medium text-gray-600 mb-1">Birthdate</label>
                    <p class="text-base text-gray-900">{{ \Carbon\Carbon::parse($patient->birthdate)->format('F d, Y') }}</p>
                </div>

                <!-- Age -->
                <div>
                    <label class="block text-sm font-medium text-gray-600 mb-1">Age</label>
                    <p class="text-base text-gray-900">{{ \Carbon\Carbon::parse($patient->birthdate)->age }} years old</p>
                </div>

                <!-- Contact Number -->
                <div>
                    <label class="block text-sm font-medium text-gray-600 mb-1">Contact Number</label>
                    <p class="text-base text-gray-900">{{ $patient->contact_number ?: '-' }}</p>
                </div>

                <!-- Address (Full Width) -->
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-600 mb-1">Address</label>
                    <p class="text-base text-gray-900">{{ $patient->address ?: '-' }}</p>
                </div>
            </div>
        </div>
    </div>

    <!-- System Information Card -->
    <div class="bg-white rounded-lg shadow border border-gray-200">
        <div class="p-6 border-b border-gray-200">
            <h3 class="text-lg font-semibold text-gray-900">System Information</h3>
        </div>
        <div class="p-6">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Date Added -->
                <div>
                    <label class="block text-sm font-medium text-gray-600 mb-1">Date Added</label>
                    <p class="text-base text-gray-900">{{ \Carbon\Carbon::parse($patient->datetime_added)->format('F d, Y g:i A') }}</p>
                </div>

                <!-- Date Updated -->
                <div>
                    <label class="block text-sm font-medium text-gray-600 mb-1">Last Updated</label>
                    <p class="text-base text-gray-900">{{ $patient->datetime_updated ? \Carbon\Carbon::parse($patient->datetime_updated)->format('F d, Y g:i A') : '-' }}</p>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Edit Patient Modal -->
<div id="editModal" class="hidden fixed inset-0 z-50 overflow-y-auto">
    <!-- Background Overlay with 50% gray transparency -->
    <div class="fixed inset-0 bg-gray-500 bg-opacity-50 transition-opacity"></div>
    
    <!-- Modal Content -->
    <div class="flex items-center justify-center min-h-screen p-4">
        <div class="relative bg-white rounded-lg shadow-xl max-w-2xl w-full overflow-hidden">
            <!-- Modal Header with Pink Background -->
            <div class="flex items-center justify-between px-6 py-4 bg-pink-600" style="background-color: #E91E8C;">
                <div class="flex items-center space-x-2">
                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                    </svg>
                    <h2 class="text-xl font-bold text-white">Edit Patient</h2>
                </div>
                <button onclick="closeEditModal()" class="text-white hover:text-gray-200">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>

            <!-- Modal Body -->
            <div class="p-6">
            <!-- Modal Form -->
            <form action="{{ route('patients.update', $patient->patient_id) }}" method="POST">
                @csrf
                @method('PUT')
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <!-- Patient Type -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Patient Type <span class="text-red-600">*</span></label>
                        <select name="patient_type" id="edit_patient_type" required class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <option value="">Select Type</option>
                            <option value="Internal" {{ $patient->patient_type == 'Internal' ? 'selected' : '' }}>Internal</option>
                            <option value="External" {{ $patient->patient_type == 'External' ? 'selected' : '' }}>External</option>
                        </select>
                    </div>

                    <!-- First Name -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">First Name <span class="text-red-600">*</span></label>
                        <input type="text" name="firstname" id="edit_firstname" value="{{ $patient->firstname }}" required maxlength="50" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>

                    <!-- Middle Name -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Middle Name</label>
                        <input type="text" name="middlename" id="edit_middlename" value="{{ $patient->middlename }}" maxlength="50" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>

                    <!-- Last Name -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Last Name <span class="text-red-600">*</span></label>
                        <input type="text" name="lastname" id="edit_lastname" value="{{ $patient->lastname }}" required maxlength="50" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>

                    <!-- Birthdate -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Birthdate <span class="text-red-600">*</span></label>
                        <input type="date" name="birthdate" id="edit_birthdate" value="{{ \Carbon\Carbon::parse($patient->birthdate)->format('Y-m-d') }}" required class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>

                    <!-- Gender -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Gender <span class="text-red-600">*</span></label>
                        <select name="gender" id="edit_gender" required class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <option value="">Select Gender</option>
                            <option value="Male" {{ $patient->gender == 'Male' ? 'selected' : '' }}>Male</option>
                            <option value="Female" {{ $patient->gender == 'Female' ? 'selected' : '' }}>Female</option>
                        </select>
                    </div>

                    <!-- Contact Number -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Contact Number</label>
                        <input type="text" name="contact_number" id="edit_contact_number" value="{{ $patient->contact_number }}" maxlength="11" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="09123456789">
                        <p id="edit_contact_error" class="text-red-500 text-xs mt-1 hidden"></p>
                        <p class="text-gray-500 text-xs mt-1">Must be exactly 11 digits (09 format)</p>
                    </div>

                    <!-- Address (Full Width) -->
                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Address</label>
                        <textarea name="address" id="edit_address" rows="3" maxlength="200" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">{{ $patient->address }}</textarea>
                    </div>
                </div>

                <!-- Modal Actions -->
                <div class="flex items-center justify-end space-x-3 mt-6">
                    <button type="button" onclick="closeEditModal()" class="px-5 py-2 bg-white border border-gray-300 hover:bg-gray-50 text-gray-700 rounded-md text-sm font-medium transition-colors">
                        Cancel
                    </button>
                    <button type="submit" class="px-5 py-2 text-white rounded-md text-sm font-medium transition-colors flex items-center space-x-2" style="background-color: #E91E8C;" onmouseover="this.style.backgroundColor='#D1187A'" onmouseout="this.style.backgroundColor='#E91E8C'">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                        </svg>
                        <span>Update Patient</span>
                    </button>
                </div>
            </form>
            </div>
        </div>
    </div>
</div>

<script>
function openEditModal() {
    document.getElementById('editModal').classList.remove('hidden');
    document.body.style.overflow = 'hidden';
}

function closeEditModal() {
    document.getElementById('editModal').classList.add('hidden');
    document.body.style.overflow = 'auto';
}

// Close modal on Escape key
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closeEditModal();
    }
});

// Close modal when clicking outside
document.getElementById('editModal')?.addEventListener('click', function(e) {
    if (e.target === this) {
        closeEditModal();
    }
});

// Validate contact number
function validateContactNumber(inputId, errorId) {
    const input = document.getElementById(inputId);
    const error = document.getElementById(errorId);
    
    input.addEventListener('input', function(e) {
        let value = e.target.value;
        
        // Remove any characters that are not digits
        value = value.replace(/[^\d]/g, '');
        
        e.target.value = value;
        
        // Count digits
        const digitCount = value.length;
        
        if (value && digitCount > 0) {
            if (digitCount < 11) {
                const missing = 11 - digitCount;
                error.textContent = `Missing ${missing} ${missing === 1 ? 'number' : 'numbers'}`;
                error.classList.remove('hidden');
                input.classList.add('border-red-500');
            } else if (digitCount === 11) {
                error.classList.add('hidden');
                input.classList.remove('border-red-500');
            } else {
                error.textContent = 'Contact number must be exactly 11 digits';
                error.classList.remove('hidden');
                input.classList.add('border-red-500');
            }
        } else {
            error.classList.add('hidden');
            input.classList.remove('border-red-500');
        }
    });
}

// Validate on form submit
document.querySelector('form[action*="patients"]')?.addEventListener('submit', function(e) {
    const input = document.getElementById('edit_contact_number');
    const error = document.getElementById('edit_contact_error');
    const value = input.value;
    const digitCount = value.length;
    
    if (value && digitCount !== 11) {
        e.preventDefault();
        const missing = 11 - digitCount;
        if (digitCount < 11) {
            error.textContent = `Missing ${missing} ${missing === 1 ? 'number' : 'numbers'}`;
        } else {
            error.textContent = 'Contact number must be exactly 11 digits';
        }
        error.classList.remove('hidden');
        input.classList.add('border-red-500');
        input.focus();
    }
});

validateContactNumber('edit_contact_number', 'edit_contact_error');
</script>
@endsection
