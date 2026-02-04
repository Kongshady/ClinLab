@extends('layouts.app')

@php
    $title = 'Create Item';
@endphp

@section('content')
<div class="container mx-auto px-4 py-6">
    <div class="max-w-2xl mx-auto">
        <div class="bg-white rounded-lg shadow-md p-6">
            <h1 class="text-2xl font-bold mb-6">Add New Item</h1>
            
            <form action="{{ route('items.store') }}" method="POST">
                @csrf
                
                <div class="mb-4">
                    <label for="section_id" class="block text-sm font-medium text-gray-700 mb-2">Section *</label>
                    <select 
                        name="section_id" 
                        id="section_id"
                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                        required
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

                <div class="mb-4">
                    <label for="label" class="block text-sm font-medium text-gray-700 mb-2">Item Name *</label>
                    <input 
                        type="text" 
                        name="label" 
                        id="label" 
                        value="{{ old('label') }}"
                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                        required
                        maxlength="20"
                    >
                    @error('label')
                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div class="mb-4">
                    <label for="unit" class="block text-sm font-medium text-gray-700 mb-2">Unit</label>
                    <input 
                        type="text" 
                        name="unit" 
                        id="unit" 
                        value="{{ old('unit') }}"
                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                        maxlength="20"
                    >
                    @error('unit')
                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div class="mb-4">
                    <label for="reorder_level" class="block text-sm font-medium text-gray-700 mb-2">Reorder Level</label>
                    <input 
                        type="number" 
                        name="reorder_level" 
                        id="reorder_level" 
                        value="{{ old('reorder_level') }}"
                        min="0"
                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                    >
                    @error('reorder_level')
                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div class="flex gap-3">
                    <button 
                        type="submit"
                        class="bg-blue-600 text-white px-6 py-2 rounded-md hover:bg-blue-700 transition"
                    >
                        Create Item
                    </button>
                    <a 
                        href="{{ route('items.index') }}"
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
