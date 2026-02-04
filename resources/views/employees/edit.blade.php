@extends('layouts.app')

@php
    $title = 'Edit Employee';
@endphp

@section('content')
<div class="container mx-auto px-4 py-6">
    <div class="max-w-3xl mx-auto">
        <div class="bg-white rounded-lg shadow-md p-6">
            <h1 class="text-2xl font-bold mb-6">Edit Employee</h1>
            
            <form action="{{ route('employees.update', $employee) }}" method="POST">
                @csrf
                @method('PUT')
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                    <div>
                        <label for="firstname" class="block text-sm font-medium text-gray-700 mb-2">First Name *</label>
                        <input 
                            type="text" 
                            name="firstname" 
                            id="firstname" 
                            value="{{ old('firstname', $employee->firstname) }}"
                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                            required
                            maxlength="20"
                        >
                        @error('firstname')
                            <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="middlename" class="block text-sm font-medium text-gray-700 mb-2">Middle Name</label>
                        <input 
                            type="text" 
                            name="middlename" 
                            id="middlename" 
                            value="{{ old('middlename', $employee->middlename) }}"
                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                            maxlength="20"
                        >
                        @error('middlename')
                            <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="lastname" class="block text-sm font-medium text-gray-700 mb-2">Last Name *</label>
                        <input 
                            type="text" 
                            name="lastname" 
                            id="lastname" 
                            value="{{ old('lastname', $employee->lastname) }}"
                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                            required
                            maxlength="20"
                        >
                        @error('lastname')
                            <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="section_id" class="block text-sm font-medium text-gray-700 mb-2">Section</label>
                        <select 
                            name="section_id" 
                            id="section_id"
                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                        >
                            <option value="">-- Select Section --</option>
                            @foreach($sections as $section)
                                <option value="{{ $section->section_id }}" {{ old('section_id', $employee->section_id) == $section->section_id ? 'selected' : '' }}>
                                    {{ $section->label }}
                                </option>
                            @endforeach
                        </select>
                        @error('section_id')
                            <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="username" class="block text-sm font-medium text-gray-700 mb-2">Username *</label>
                        <input 
                            type="text" 
                            name="username" 
                            id="username" 
                            value="{{ old('username', $employee->username) }}"
                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                            required
                            maxlength="20"
                        >
                        @error('username')
                            <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="password" class="block text-sm font-medium text-gray-700 mb-2">Password (leave blank to keep current)</label>
                        <input 
                            type="password" 
                            name="password" 
                            id="password"
                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                            minlength="6"
                        >
                        @error('password')
                            <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="position" class="block text-sm font-medium text-gray-700 mb-2">Position *</label>
                        <input 
                            type="text" 
                            name="position" 
                            id="position" 
                            value="{{ old('position', $employee->position) }}"
                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                            required
                            maxlength="20"
                        >
                        @error('position')
                            <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="role" class="block text-sm font-medium text-gray-700 mb-2">Role *</label>
                        <input 
                            type="text" 
                            name="role" 
                            id="role" 
                            value="{{ old('role', $employee->role) }}"
                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                            required
                            maxlength="20"
                        >
                        @error('role')
                            <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <div class="flex gap-3 mt-6">
                    <button 
                        type="submit"
                        class="bg-blue-600 text-white px-6 py-2 rounded-md hover:bg-blue-700 transition"
                    >
                        Update Employee
                    </button>
                    <a 
                        href="{{ route('employees.index') }}"
                        class="bg-gray-500 text-white px-6 py-2 rounded-md hover:bg-gray-600 transition"
                    >
                        Cancel
                    </a>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
