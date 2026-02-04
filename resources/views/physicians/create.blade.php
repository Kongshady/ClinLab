@extends('layouts.app')

@php
    $title = 'Create Physician';
@endphp

@section('content')
<div class="container mx-auto px-4 py-6">
    <div class="max-w-2xl mx-auto">
        <div class="bg-white rounded-lg shadow-md p-6">
            <h1 class="text-2xl font-bold mb-6">Add New Physician</h1>
            
            <form action="{{ route('physicians.store') }}" method="POST">
                @csrf
                
                <div class="mb-4">
                    <label for="physician_name" class="block text-sm font-medium text-gray-700 mb-2">Physician Name *</label>
                    <input 
                        type="text" 
                        name="physician_name" 
                        id="physician_name" 
                        value="{{ old('physician_name') }}"
                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                        required
                        maxlength="100"
                    >
                    @error('physician_name')
                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div class="mb-4">
                    <label for="specialization" class="block text-sm font-medium text-gray-700 mb-2">Specialization</label>
                    <input 
                        type="text" 
                        name="specialization" 
                        id="specialization" 
                        value="{{ old('specialization') }}"
                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                        maxlength="100"
                    >
                    @error('specialization')
                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div class="mb-4">
                    <label for="contact_number" class="block text-sm font-medium text-gray-700 mb-2">Contact Number</label>
                    <input 
                        type="text" 
                        name="contact_number" 
                        id="contact_number" 
                        value="{{ old('contact_number') }}"
                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                        maxlength="20"
                    >
                    @error('contact_number')
                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div class="mb-4">
                    <label for="email" class="block text-sm font-medium text-gray-700 mb-2">Email</label>
                    <input 
                        type="email" 
                        name="email" 
                        id="email" 
                        value="{{ old('email') }}"
                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                        maxlength="100"
                    >
                    @error('email')
                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div class="flex gap-3">
                    <button 
                        type="submit"
                        class="bg-blue-600 text-white px-6 py-2 rounded-md hover:bg-blue-700 transition"
                    >
                        Create Physician
                    </button>
                    <a 
                        href="{{ route('physicians.index') }}"
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
