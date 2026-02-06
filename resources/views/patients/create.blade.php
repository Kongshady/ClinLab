@extends('layouts.app')

@section('content')
<div class="p-8 space-y-6 bg-gray-50 min-h-screen">
    <!-- Header -->
    <div class="mb-6">
        <h1 class="text-3xl font-bold text-gray-800">Add New Patient</h1>
        <p class="text-gray-600 mt-1">Register a new patient in the system</p>
    </div>

<<<<<<< Updated upstream
    <!-- Form Card -->
    <div class="bg-white rounded-lg shadow border border-gray-200 max-w-4xl">
        <div class="p-6">
=======
        <!-- Success Message -->
        @if(session('success'))
        <div class="mb-6 bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded-lg">
            {{ session('success') }}
        </div>
        @endif

        <!-- Duplicate Error Message -->
        @error('duplicate')
        <div class="mb-6 bg-red-50 border border-red-200 text-red-800 px-4 py-3 rounded-lg">
            {{ $message }}
        </div>
        @enderror

        <!-- Form Card -->
        <div class="bg-white rounded-lg shadow-sm p-6">
>>>>>>> Stashed changes
            <form action="{{ route('patients.store') }}" method="POST">
                @csrf
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label for="patient_type" class="block text-sm font-semibold text-gray-700 mb-2">Patient Type <span class="text-red-500">*</span></label>
                        <select name="patient_type" id="patient_type" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                            <option value="">Select Type</option>
                            <option value="Internal" {{ old('patient_type') == 'Internal' ? 'selected' : '' }}>Internal</option>
                            <option value="External" {{ old('patient_type') == 'External' ? 'selected' : '' }}>External</option>
                        </select>
                        @error('patient_type')
                            <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="gender" class="block text-sm font-semibold text-gray-700 mb-2">Gender <span class="text-red-500">*</span></label>
                        <select name="gender" id="gender" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                            <option value="">Select Gender</option>
                            <option value="Male" {{ old('gender') == 'Male' ? 'selected' : '' }}>Male</option>
                            <option value="Female" {{ old('gender') == 'Female' ? 'selected' : '' }}>Female</option>
                        </select>
                        @error('gender')
                            <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="firstname" class="block text-sm font-semibold text-gray-700 mb-2">First Name <span class="text-red-500">*</span></label>
                        <input type="text" name="firstname" id="firstname" value="{{ old('firstname') }}" required maxlength="50" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        @error('firstname')
                            <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="middlename" class="block text-sm font-semibold text-gray-700 mb-2">Middle Name</label>
                        <input type="text" name="middlename" id="middlename" value="{{ old('middlename') }}" maxlength="50" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        @error('middlename')
                            <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="lastname" class="block text-sm font-semibold text-gray-700 mb-2">Last Name <span class="text-red-500">*</span></label>
                        <input type="text" name="lastname" id="lastname" value="{{ old('lastname') }}" required maxlength="50" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        @error('lastname')
                            <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="birthdate" class="block text-sm font-semibold text-gray-700 mb-2">Birthdate <span class="text-red-500">*</span></label>
                        <input type="date" name="birthdate" id="birthdate" value="{{ old('birthdate') }}" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        @error('birthdate')
                            <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
<<<<<<< Updated upstream
                        <label for="contact_number" class="block text-sm font-semibold text-gray-700 mb-2">Contact Number</label>
                        <input type="text" name="contact_number" id="contact_number" value="{{ old('contact_number') }}" maxlength="11" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent" placeholder="09123456789">
                        <p id="contact_error" class="text-red-500 text-sm mt-1 hidden"></p>
                        <p id="contact_hint" class="text-gray-500 text-xs mt-1">Must be exactly 11 digits (09 format)</p>
=======
                        <label for="contact_number" class="block text-sm font-medium text-gray-700 mb-2">Contact Number</label>
                        <input type="text" name="contact_number" id="contact_number" value="{{ old('contact_number') }}" maxlength="11" pattern="[0-9]{11}" oninput="this.value = this.value.replace(/[^0-9]/g, '').slice(0, 11)" placeholder="09XXXXXXXXX" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
>>>>>>> Stashed changes
                        @error('contact_number')
                            <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                        @enderror
                        <p class="text-xs text-gray-500 mt-1">11 digits only</p>
                    </div>

                    <div class="md:col-span-2">
                        <label for="address" class="block text-sm font-semibold text-gray-700 mb-2">Address</label>
                        <textarea name="address" id="address" rows="3" maxlength="200" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">{{ old('address') }}</textarea>
                        @error('address')
                            <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <div class="flex items-center gap-3 mt-8 pt-6 border-t border-gray-200">
                    <button type="submit" class="px-6 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg font-medium transition-colors">
                        Save Patient
                    </button>
                    <a href="{{ route('patients.index') }}" class="px-6 py-2 bg-gray-500 hover:bg-gray-600 text-white rounded-lg font-medium transition-colors">
                        Cancel
                    </a>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
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
    
    // Validate on form submit
    input.form.addEventListener('submit', function(e) {
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
}

validateContactNumber('contact_number', 'contact_error');
</script>
@endsection
