@extends('layouts.app')

@php
    $title = 'View Test';
@endphp

@section('content')
<div class="container mx-auto px-4 py-6">
    <div class="max-w-2xl mx-auto">
        <div class="bg-white rounded-lg shadow-md p-6">
            <div class="flex justify-between items-center mb-6">
                <h1 class="text-2xl font-bold">Lab Test Details</h1>
                <a 
                    href="{{ route('tests.index') }}"
                    class="bg-gray-500 text-white px-4 py-2 rounded-md hover:bg-gray-600 transition"
                >
                    Back to List
                </a>
            </div>
            
            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-500">Test Name</label>
                    <p class="text-lg">{{ $test->label }}</p>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-500">Section</label>
                    <p class="text-lg">{{ $test->section->label ?? 'N/A' }}</p>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-500">Current Price</label>
                    <p class="text-lg">₱{{ number_format($test->current_price, 2) }}</p>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-500">Created At</label>
                    <p class="text-lg">{{ $test->created_at->format('M d, Y H:i:s') }}</p>
                </div>

                @if($test->updated_at)
                <div>
                    <label class="block text-sm font-medium text-gray-500">Last Updated</label>
                    <p class="text-lg">{{ $test->updated_at->format('M d, Y H:i:s') }}</p>
                </div>
                @endif
            </div>

            <div class="flex gap-3 mt-6">
                <a 
                    href="{{ route('tests.edit', $test) }}"
                    class="bg-blue-600 text-white px-6 py-2 rounded-md hover:bg-blue-700 transition"
                >
                    Edit Test
                </a>
            </div>
        </div>
    </div>
</div>
@endsection
