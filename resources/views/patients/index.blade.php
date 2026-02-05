@extends('layouts.app')

@section('content')
<div class="p-8 space-y-6 bg-gray-50 min-h-screen">
    <!-- Header -->
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-3xl font-bold text-gray-800">Patient Management</h1>
            <p class="text-gray-600 mt-1">Manage all patient records</p>
        </div>
        @can('patients.create')
        <a href="{{ route('patients.create') }}" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg text-sm font-medium transition-colors">
            + Add Patient
        </a>
        @endcan
    </div>

    <!-- Table Card -->
    <div class="bg-white rounded-lg shadow border border-gray-200">
        <div class="p-6 border-b border-gray-200">
            <div class="flex items-center justify-between">
                <h3 class="text-lg font-semibold text-gray-900">All Patients</h3>
                <div class="text-sm text-gray-600">Total: <span class="font-semibold">{{ $patients->total() }}</span></div>
            </div>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-50 border-b border-gray-200">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">ID</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Name</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Type</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Gender</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Age</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Contact</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @forelse($patients as $patient)
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ $patient->patient_id }}</td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm font-medium text-gray-900">{{ $patient->firstname }} {{ $patient->lastname }}</div>
                            @if($patient->middlename)
                            <div class="text-xs text-gray-500">{{ $patient->middlename }}</div>
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="px-2 py-1 text-xs font-medium rounded-full {{ $patient->patient_type === 'Internal' ? 'bg-blue-100 text-blue-800' : 'bg-green-100 text-green-800' }}">
                                {{ $patient->patient_type }}
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ $patient->gender }}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ \Carbon\Carbon::parse($patient->birthdate)->age }} yrs</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ $patient->contact_number ?: '-' }}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm">
                            <div class="flex items-center space-x-2">
                                @can('patients.view')
                                <a href="{{ route('patients.show', $patient) }}" class="text-blue-600 hover:text-blue-800" title="View">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                    </svg>
                                </a>
                                @endcan
                                @can('patients.edit')
                                <button onclick="openEditModal({{ json_encode($patient) }})" class="text-green-600 hover:text-green-800" title="Edit">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                    </svg>
                                </button>
                                @endcan
                                @can('patients.delete')
                                <form action="{{ route('patients.destroy', $patient) }}" method="POST" class="inline" onsubmit="return confirm('Are you sure you want to delete this patient?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-red-600 hover:text-red-800" title="Delete">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                        </svg>
                                    </button>
                                </form>
                                @endcan
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="px-6 py-12 text-center">
                            <svg class="w-16 h-16 mx-auto mb-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                            </svg>
                            <p class="text-gray-500">No patients found</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($patients->hasPages())
        <div class="px-6 py-4 border-t border-gray-200 bg-gray-50">
            {{ $patients->links() }}
        </div>
        @endif
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
            <form id="editPatientForm" method="POST">
                @csrf
                @method('PUT')
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <!-- Patient Type -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Patient Type <span class="text-red-600">*</span></label>
                        <select name="patient_type" id="edit_patient_type" required class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <option value="">Select Type</option>
                            <option value="Internal">Internal</option>
                            <option value="External">External</option>
                        </select>
                    </div>

                    <!-- First Name -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">First Name <span class="text-red-600">*</span></label>
                        <input type="text" name="firstname" id="edit_firstname" required maxlength="50" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>

                    <!-- Middle Name -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Middle Name</label>
                        <input type="text" name="middlename" id="edit_middlename" maxlength="50" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>

                    <!-- Last Name -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Last Name <span class="text-red-600">*</span></label>
                        <input type="text" name="lastname" id="edit_lastname" required maxlength="50" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>

                    <!-- Birthdate -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Birthdate <span class="text-red-600">*</span></label>
                        <input type="date" name="birthdate" id="edit_birthdate" required class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>

                    <!-- Gender -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Gender <span class="text-red-600">*</span></label>
                        <select name="gender" id="edit_gender" required class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <option value="">Select Gender</option>
                            <option value="Male">Male</option>
                            <option value="Female">Female</option>
                        </select>
                    </div>

                    <!-- Contact Number -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Contact Number</label>
                        <input type="text" name="contact_number" id="edit_contact_number" maxlength="11" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="09123456789">
                        <p id="edit_contact_error" class="text-red-500 text-xs mt-1 hidden"></p>
                        <p class="text-gray-500 text-xs mt-1">Must be exactly 11 digits (09 format)</p>
                    </div>

                    <!-- Address (Full Width) -->
                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Address</label>
                        <textarea name="address" id="edit_address" rows="3" maxlength="200" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"></textarea>
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
function openEditModal(patient) {
    // Set form action
    document.getElementById('editPatientForm').action = `/patients/${patient.patient_id}`;
    
    // Populate form fields
    document.getElementById('edit_patient_type').value = patient.patient_type;
    document.getElementById('edit_firstname').value = patient.firstname;
    document.getElementById('edit_middlename').value = patient.middlename || '';
    document.getElementById('edit_lastname').value = patient.lastname;
    document.getElementById('edit_birthdate').value = patient.birthdate.split('T')[0];
    document.getElementById('edit_gender').value = patient.gender;
    document.getElementById('edit_contact_number').value = patient.contact_number || '';
    document.getElementById('edit_address').value = patient.address || '';
    
    // Show modal
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
document.getElementById('editPatientForm')?.addEventListener('submit', function(e) {
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
