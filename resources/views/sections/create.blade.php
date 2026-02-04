@extends('layouts.app')

@section('content')
<div class="p-8 space-y-6 bg-gray-50 min-h-screen">
    <!-- Header -->
    <div class="mb-6">
        <h1 class="text-3xl font-bold text-gray-800">Add New Section</h1>
        <p class="text-gray-600 mt-1">Create a new laboratory section</p>
    </div>

    <!-- Form Card -->
    <div class="bg-white rounded-lg shadow border border-gray-200 max-w-2xl">
        <div class="p-6">
            <form action="{{ route('sections.store') }}" method="POST">
                @csrf
                
                <div class="mb-6">
                    <label for="label" class="block text-sm font-semibold text-gray-700 mb-2">Section Label <span class="text-red-500">*</span></label>
                    <input 
                        type="text" 
                        name="label" 
                        id="label" 
                        value="{{ old('label') }}"
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                        required
                        maxlength="50"
                    >
                    @error('label')
                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div class="flex items-center gap-3 pt-6 border-t border-gray-200">
                    <button 
                        type="submit"
                        class="px-6 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg font-medium transition-colors"
                    >
                        Create Section
                    </button>
                    <a 
                        href="{{ route('sections.index') }}"
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
