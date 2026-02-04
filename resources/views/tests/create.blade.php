@extends('layouts.app')

@php
    $title = 'Create Test';
@endphp

@section('content')
<div class="container mx-auto px-4 py-6">
    <div class="max-w-2xl mx-auto">
        <div class="bg-white rounded-lg shadow-md p-6">
            <h1 class="text-2xl font-bold mb-6">Add New Lab Test</h1>
            
            <form action="{{ route('tests.store') }}" method="POST">
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
                    <label for="label" class="block text-sm font-medium text-gray-700 mb-2">Test Name *</label>
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
                    <label for="current_price" class="block text-sm font-medium text-gray-700 mb-2">Price *</label>
                    <input 
                        type="number" 
                        name="current_price" 
                        id="current_price" 
                        value="{{ old('current_price') }}"
                        step="0.01"
                        min="0"
                        max="99999999.99"
                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                        required
                    >
                    @error('current_price')
                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div class="flex gap-3">
                    <button 
                        type="submit"
                        class="bg-blue-600 text-white px-6 py-2 rounded-md hover:bg-blue-700 transition"
                    >
                        Create Test
                    </button>
                    <a 
                        href="{{ route('tests.index') }}"
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
