@extends('layouts.app')

@section('content')
<div class="p-8 space-y-6 bg-gray-50 min-h-screen">
    <!-- Header with Back Button -->
    <div class="flex items-center justify-between mb-6">
        <div class="flex items-center space-x-4">
            <a href="{{ route('lab-results.index') }}" class="text-gray-600 hover:text-gray-800">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                </svg>
            </a>
            <div>
                <h1 class="text-3xl font-bold text-gray-800">Lab Result Details</h1>
                <p class="text-gray-600 mt-1">Result ID: {{ $result->lab_result_id }}</p>
            </div>
        </div>
        <div class="flex items-center space-x-2">
            @can('lab-results.edit')
            <a href="{{ route('lab-results.edit', $result->lab_result_id) }}" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg text-sm font-medium transition-colors">
                Edit Result
            </a>
            @endcan
            @can('lab-results.delete')
            <form action="{{ route('lab-results.destroy', $result) }}" method="POST" class="inline" onsubmit="return confirm('Are you sure you want to delete this lab result?');">
                @csrf
                @method('DELETE')
                <button type="submit" class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white rounded-lg text-sm font-medium transition-colors">
                    Delete Result
                </button>
            </form>
            @endcan
        </div>
    </div>

    <!-- Result Information Card -->
    <div class="bg-white rounded-lg shadow border border-gray-200">
        <div class="p-6 border-b border-gray-200">
            <h3 class="text-lg font-semibold text-gray-900">Result Information</h3>
        </div>
        <div class="p-6">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Patient -->
                <div>
                    <label class="block text-sm font-medium text-gray-600 mb-1">Patient</label>
                    <p class="text-base font-semibold text-gray-900">{{ $result->patient->full_name ?? 'N/A' }}</p>
                </div>

                <!-- Test -->
                <div>
                    <label class="block text-sm font-medium text-gray-600 mb-1">Test</label>
                    <p class="text-base font-semibold text-gray-900">{{ $result->test->label ?? 'N/A' }}</p>
                </div>

                <!-- Result Date -->
                <div>
                    <label class="block text-sm font-medium text-gray-600 mb-1">Result Date</label>
                    <p class="text-base text-gray-900">{{ $result->result_date ? \Carbon\Carbon::parse($result->result_date)->format('F d, Y') : 'N/A' }}</p>
                </div>

                <!-- Status -->
                <div>
                    <label class="block text-sm font-medium text-gray-600 mb-1">Status</label>
                    <span class="inline-flex px-3 py-1 text-sm font-medium rounded-full 
                        {{ $result->status === 'final' ? 'bg-green-100 text-green-800' : ($result->status === 'revised' ? 'bg-blue-100 text-blue-800' : 'bg-yellow-100 text-yellow-800') }}">
                        {{ ucfirst($result->status ?? 'draft') }}
                    </span>
                </div>

                <!-- Result Value -->
                <div>
                    <label class="block text-sm font-medium text-gray-600 mb-1">Result Value</label>
                    <p class="text-base text-gray-900">{{ $result->result_value ?? 'N/A' }}</p>
                </div>

                <!-- Normal Range -->
                <div>
                    <label class="block text-sm font-medium text-gray-600 mb-1">Normal Range</label>
                    <p class="text-base text-gray-900">{{ $result->normal_range ?? 'N/A' }}</p>
                </div>

                <!-- Performed By -->
                <div>
                    <label class="block text-sm font-medium text-gray-600 mb-1">Performed By</label>
                    <p class="text-base text-gray-900">{{ $result->performedBy->full_name ?? 'N/A' }}</p>
                </div>

                <!-- Verified By -->
                <div>
                    <label class="block text-sm font-medium text-gray-600 mb-1">Verified By</label>
                    <p class="text-base text-gray-900">{{ $result->verifiedBy->full_name ?? 'N/A' }}</p>
                </div>

                <!-- Findings (Full Width) -->
                @if($result->findings)
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-600 mb-1">Findings</label>
                    <p class="text-base text-gray-900 whitespace-pre-wrap">{{ $result->findings }}</p>
                </div>
                @endif

                <!-- Remarks (Full Width) -->
                @if($result->remarks)
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-600 mb-1">Remarks</label>
                    <p class="text-base text-gray-900 whitespace-pre-wrap">{{ $result->remarks }}</p>
                </div>
                @endif
            </div>
        </div>
    </div>

    <!-- System Information Card -->
    <div class="bg-white rounded-lg shadow border border-gray-200">
        <div class="p-6 border-b border-gray-200">
            <h3 class="text-lg font-semibold text-gray-900">System Information</h3>
        </div>
        <div class="p-6">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Date Added -->
                <div>
                    <label class="block text-sm font-medium text-gray-600 mb-1">Date Added</label>
                    <p class="text-base text-gray-900">{{ \Carbon\Carbon::parse($result->datetime_added)->format('F d, Y g:i A') }}</p>
                </div>

                <!-- Date Modified -->
                <div>
                    <label class="block text-sm font-medium text-gray-600 mb-1">Last Modified</label>
                    <p class="text-base text-gray-900">{{ $result->datetime_modified ? \Carbon\Carbon::parse($result->datetime_modified)->format('F d, Y g:i A') : '-' }}</p>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
