@extends('layouts.app')

@section('content')
<div class="p-8 space-y-6 bg-gray-50 min-h-screen">
    <!-- Header -->
    <div class="mb-6">
        <h1 class="text-3xl font-bold text-gray-800">Add New Employee</h1>
        <p class="text-gray-600 mt-1">Register a new laboratory staff member</p>
    </div>

    <!-- Form Card -->
    <div class="bg-white rounded-lg shadow border border-gray-200 max-w-4xl">
        <div class="p-6">
            <form action="{{ route('employees.store') }}" method="POST">
                @csrf
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label for="firstname" class="block text-sm font-semibold text-gray-700 mb-2">First Name <span class="text-red-500">*</span></label>
                        <input 
                            type="text" 
                            name="firstname" 
                            id="firstname" 
                            value="{{ old('firstname') }}"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                            required
                            maxlength="20"
                        >
                        @error('firstname')
                            <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="middlename" class="block text-sm font-semibold text-gray-700 mb-2">Middle Name</label>
                        <input 
                            type="text" 
                            name="middlename" 
                            id="middlename" 
                            value="{{ old('middlename') }}"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                            maxlength="20"
                        >
                        @error('middlename')
                            <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="lastname" class="block text-sm font-semibold text-gray-700 mb-2">Last Name <span class="text-red-500">*</span></label>
                        <input 
                            type="text" 
                            name="lastname" 
                            id="lastname" 
                            value="{{ old('lastname') }}"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                            required
                            maxlength="20"
                        >
                        @error('lastname')
                            <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="section_id" class="block text-sm font-semibold text-gray-700 mb-2">Section</label>
                        <select 
                            name="section_id" 
                            id="section_id"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                        >
                            <option value="">-- Select Section --</option>
                            @foreach($sections as $section)
                                <option value="{{ $section->section_id }}" {{ old('section_id') == $section->section_id ? 'selected' : '' }}>
                                    {{ $section->label }}
                                </option>
                            @endforeach
                        </select>
                        @error('section_id')
                            <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="username" class="block text-sm font-semibold text-gray-700 mb-2">Username <span class="text-red-500">*</span></label>
                        <input 
                            type="text" 
                            name="username" 
                            id="username" 
                            value="{{ old('username') }}"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                            required
                            maxlength="20"
                        >
                        @error('username')
                            <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="password" class="block text-sm font-semibold text-gray-700 mb-2">Password <span class="text-red-500">*</span></label>
                        <input 
                            type="password" 
                            name="password" 
                            id="password"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                            required
                            minlength="6"
                        >
                        @error('password')
                            <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="position" class="block text-sm font-semibold text-gray-700 mb-2">Position <span class="text-red-500">*</span></label>
                        <input 
                            type="text" 
                            name="position" 
                            id="position" 
                            value="{{ old('position') }}"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                            required
                            maxlength="20"
                        >
                        @error('position')
                            <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="role_id" class="block text-sm font-semibold text-gray-700 mb-2">Role <span class="text-red-500">*</span></label>
                        <select 
                            name="role_id" 
                            id="role_id" 
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                            required
                        >
                            <option value="">Select Role</option>
                            @foreach(\App\Models\Role::where('status_code', 1)->get() as $role)
                                <option value="{{ $role->role_id }}" {{ old('role_id') == $role->role_id ? 'selected' : '' }}>{{ $role->role_name }}</option>
                            @endforeach
                        </select>
                        @error('role_id')
                            <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <div class="flex items-center gap-3 mt-8 pt-6 border-t border-gray-200">
                    <button 
                        type="submit"
                        class="px-6 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg font-medium transition-colors"
                    >
                        Create Employee
                    </button>
                    <a 
                        href="{{ route('employees.index') }}"
                        class="px-6 py-2 bg-gray-500 hover:bg-gray-600 text-white rounded-lg font-medium transition-colors"
                    >
                        Cancel
                    </a>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
