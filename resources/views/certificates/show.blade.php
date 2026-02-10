@extends('layouts.app')

@section('content')
<div class="p-6 space-y-6">
    <!-- Header -->
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Certificate Details</h1>
            <p class="text-sm text-gray-500 mt-0.5">{{ $certificate->certificate_number }}</p>
        </div>
        <span class="px-3 py-1.5 text-sm font-semibold rounded-full
            {{ $certificate->status == 'active' ? 'bg-green-100 text-green-800' : '' }}
            {{ $certificate->status == 'revoked' ? 'bg-red-100 text-red-800' : '' }}
            {{ $certificate->status == 'expired' ? 'bg-gray-100 text-gray-600' : '' }}">
            {{ ucfirst($certificate->status) }}
        </span>
    </div>

    <!-- Certificate Information -->
    <div class="bg-white rounded-lg border border-gray-200 overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200">
            <h2 class="text-base font-semibold text-gray-900">Certificate Information</h2>
        </div>
        <div class="p-6">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-x-8 gap-y-5">
                <div>
                    <p class="text-xs font-medium text-gray-400 uppercase tracking-wider mb-1">Certificate Number</p>
                    <p class="text-sm font-medium text-gray-900">{{ $certificate->certificate_number }}</p>
                </div>
                <div>
                    <p class="text-xs font-medium text-gray-400 uppercase tracking-wider mb-1">Certificate Type</p>
                    <p class="text-sm font-medium text-gray-900">{{ ucfirst(str_replace('_', ' ', $certificate->certificate_type ?? '—')) }}</p>
                </div>
                <div>
                    <p class="text-xs font-medium text-gray-400 uppercase tracking-wider mb-1">Issue Date</p>
                    <p class="text-sm font-medium text-gray-900">{{ $certificate->issue_date ? $certificate->issue_date->format('M d, Y') : '—' }}</p>
                </div>
                <div>
                    <p class="text-xs font-medium text-gray-400 uppercase tracking-wider mb-1">Patient</p>
                    <p class="text-sm font-medium text-gray-900">{{ $certificate->patient ? $certificate->patient->firstname . ' ' . $certificate->patient->lastname : '—' }}</p>
                </div>
                <div>
                    <p class="text-xs font-medium text-gray-400 uppercase tracking-wider mb-1">Equipment</p>
                    <p class="text-sm font-medium text-gray-900">{{ $certificate->equipment->name ?? '—' }}</p>
                </div>
                <div>
                    <p class="text-xs font-medium text-gray-400 uppercase tracking-wider mb-1">Status</p>
                    <p class="text-sm font-medium text-gray-900">{{ ucfirst($certificate->status) }}</p>
                </div>
                <div>
                    <p class="text-xs font-medium text-gray-400 uppercase tracking-wider mb-1">Issued By</p>
                    <p class="text-sm font-medium text-gray-900">{{ $certificate->issuedBy ? $certificate->issuedBy->firstname . ' ' . $certificate->issuedBy->lastname : '—' }}</p>
                </div>
                <div>
                    <p class="text-xs font-medium text-gray-400 uppercase tracking-wider mb-1">Verified By</p>
                    <p class="text-sm font-medium text-gray-900">{{ $certificate->verifiedBy ? $certificate->verifiedBy->firstname . ' ' . $certificate->verifiedBy->lastname : '—' }}</p>
                </div>
                <div>
                    <p class="text-xs font-medium text-gray-400 uppercase tracking-wider mb-1">Date Added</p>
                    <p class="text-sm font-medium text-gray-900">{{ $certificate->datetime_added ? $certificate->datetime_added->format('M d, Y h:i A') : '—' }}</p>
                </div>
            </div>
        </div>
    </div>

    @if($certificate->certificate_data)
    <div class="bg-white rounded-lg border border-gray-200 overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200">
            <h2 class="text-base font-semibold text-gray-900">Certificate Data</h2>
        </div>
        <div class="p-6">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-x-8 gap-y-4">
                @foreach($certificate->certificate_data as $key => $value)
                    <div>
                        <p class="text-xs font-medium text-gray-400 uppercase tracking-wider mb-1">{{ ucfirst(str_replace('_', ' ', $key)) }}</p>
                        <p class="text-sm font-medium text-gray-900">{{ is_array($value) ? json_encode($value) : $value }}</p>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
    @endif

    <!-- Back Button -->
    <div class="pt-2">
        <a href="{{ route('certificates.index') }}" class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-lg text-sm font-medium text-gray-700 hover:bg-gray-50 transition-colors">
            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
            </svg>
            Back to Certificates
        </a>
    </div>
</div>
@endsection
