@extends('layouts.app')

@php
    $title = 'Edit Equipment';
@endphp

@section('content')
<div class="container mx-auto px-4 py-6">
    <div class="max-w-3xl mx-auto">
        <div class="bg-white rounded-lg shadow-md p-6">
            <h1 class="text-2xl font-bold mb-6">Edit Equipment</h1>
            
            <form action="{{ route('equipment.update', $equipment) }}" method="POST">
                @csrf
                @method('PUT')
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                    <div>
                        <label for="name" class="block text-sm font-medium text-gray-700 mb-2">Equipment Name *</label>
                        <input 
                            type="text" 
                            name="name" 
                            id="name" 
                            value="{{ old('name', $equipment->name) }}"
                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                            required
                            maxlength="100"
                        >
                        @error('name')
                            <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="model" class="block text-sm font-medium text-gray-700 mb-2">Model</label>
                        <input 
                            type="text" 
                            name="model" 
                            id="model" 
                            value="{{ old('model', $equipment->model) }}"
                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                            maxlength="100"
                        >
                        @error('model')
                            <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="serial_no" class="block text-sm font-medium text-gray-700 mb-2">Serial Number</label>
                        <input 
                            type="text" 
                            name="serial_no" 
                            id="serial_no" 
                            value="{{ old('serial_no', $equipment->serial_no) }}"
                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                            maxlength="100"
                        >
                        @error('serial_no')
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
                                <option value="{{ $section->section_id }}" {{ old('section_id', $equipment->section_id) == $section->section_id ? 'selected' : '' }}>
                                    {{ $section->label }}
                                </option>
                            @endforeach
                        </select>
                        @error('section_id')
                            <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="status" class="block text-sm font-medium text-gray-700 mb-2">Status *</label>
                        <select 
                            name="status" 
                            id="status"
                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                            required
                        >
                            <option value="operational" {{ old('status', $equipment->status) == 'operational' ? 'selected' : '' }}>Operational</option>
                            <option value="under_maintenance" {{ old('status', $equipment->status) == 'under_maintenance' ? 'selected' : '' }}>Under Maintenance</option>
                            <option value="decommissioned" {{ old('status', $equipment->status) == 'decommissioned' ? 'selected' : '' }}>Decommissioned</option>
                        </select>
                        @error('status')
                            <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="purchase_date" class="block text-sm font-medium text-gray-700 mb-2">Purchase Date</label>
                        <input 
                            type="date" 
                            name="purchase_date" 
                            id="purchase_date" 
                            value="{{ old('purchase_date', $equipment->purchase_date?->format('Y-m-d')) }}"
                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                        >
                        @error('purchase_date')
                            <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="md:col-span-2">
                        <label for="supplier" class="block text-sm font-medium text-gray-700 mb-2">Supplier</label>
                        <input 
                            type="text" 
                            name="supplier" 
                            id="supplier" 
                            value="{{ old('supplier', $equipment->supplier) }}"
                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                            maxlength="200"
                        >
                        @error('supplier')
                            <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="md:col-span-2">
                        <label for="remarks" class="block text-sm font-medium text-gray-700 mb-2">Remarks</label>
                        <textarea 
                            name="remarks" 
                            id="remarks" 
                            rows="3"
                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                        >{{ old('remarks', $equipment->remarks) }}</textarea>
                        @error('remarks')
                            <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <div class="flex gap-3 mt-6">
                    <button 
                        type="submit"
                        class="bg-blue-600 text-white px-6 py-2 rounded-md hover:bg-blue-700 transition"
                    >
                        Update Equipment
                    </button>
                    <a 
                        href="{{ route('equipment.index') }}"
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
