@php
use App\Models\Item;
use App\Models\Equipment;
use App\Models\CalibrationRecord;
use App\Models\Certificate;
use App\Models\ActivityLog;
use Illuminate\Support\Facades\Cache;

// Cache dashboard statistics for 5 minutes
$stats = Cache::remember('manager_dashboard_stats', 300, function() {
    $totalItems = Item::where('is_deleted', 0)->count();
    
    $totalEquipment = Equipment::where('is_deleted', 0)->count();
    
    $dueSoonMaintenance = Equipment::where('is_deleted', 0)
        ->whereHas('maintenanceRecords', function($q) {
            $q->whereDate('next_due_date', '<=', now()->addDays(7))
              ->whereDate('next_due_date', '>=', now());
        })
        ->count();
    
    $certificatesThisMonth = Certificate::whereMonth('datetime_added', now()->month)
        ->whereYear('datetime_added', now()->year)
        ->count();
    
    return [
        'total_items' => $totalItems,
        'total_equipment' => $totalEquipment,
        'due_soon_maintenance' => $dueSoonMaintenance,
        'certificates_month' => $certificatesThisMonth,
    ];
});

$recentActivities = Cache::remember('manager_recent_activities', 300, function() {
    return ActivityLog::with(['employee' => function($query) {
        $query->select('employee_id', 'firstname', 'lastname');
    }])->orderBy('datetime_added', 'desc')->limit(8)->get();
});
@endphp

@extends('layouts.app')

@section('content')
<div class="min-h-screen bg-gray-50">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <!-- Header -->
        <div class="mb-8">
            <h1 class="text-4xl font-bold text-gray-900 mb-2">Laboratory Manager Dashboard</h1>
            <p class="text-lg text-gray-600">
                <span class="inline-flex items-center">
                    <svg class="w-5 h-5 mr-2 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                    </svg>
                    Welcome, {{ auth()->user()->name }}
                </span>
            </p>
        </div>

        <!-- Key Metrics -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
            <!-- Total Items -->
            <div class="bg-white rounded-xl shadow-sm p-6 border-l-4 border-blue-500">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-600">Total Items</p>
                        <p class="text-3xl font-bold text-gray-900 mt-2">{{ $stats['total_items'] }}</p>
                    </div>
                    <div class="p-3 bg-blue-100 rounded-lg">
                        <svg class="w-8 h-8 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                        </svg>
                    </div>
                </div>
            </div>

            <!-- Total Equipment -->
            <div class="bg-white rounded-xl shadow-sm p-6 border-l-4 border-purple-500">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-600">Total Equipment</p>
                        <p class="text-3xl font-bold text-gray-900 mt-2">{{ $stats['total_equipment'] }}</p>
                    </div>
                    <div class="p-3 bg-purple-100 rounded-lg">
                        <svg class="w-8 h-8 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                        </svg>
                    </div>
                </div>
            </div>

            <!-- Due Soon Maintenance -->
            <div class="bg-white rounded-xl shadow-sm p-6 border-l-4 border-orange-500">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-600">Due Soon Maintenance</p>
                        <p class="text-3xl font-bold text-gray-900 mt-2">{{ $stats['due_soon_maintenance'] }}</p>
                    </div>
                    <div class="p-3 bg-orange-100 rounded-lg">
                        <svg class="w-8 h-8 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                </div>
            </div>

            <!-- Certificates This Month -->
            <div class="bg-white rounded-xl shadow-sm p-6 border-l-4 border-green-500">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-600">Certificates Issued</p>
                        <p class="text-3xl font-bold text-gray-900 mt-2">{{ $stats['certificates_month'] }}</p>
                    </div>
                    <div class="p-3 bg-green-100 rounded-lg">
                        <svg class="w-8 h-8 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                </div>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="bg-white rounded-xl shadow-sm p-6 mb-8">
            <h2 class="text-xl font-bold text-gray-900 mb-4">Quick Actions</h2>
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                @can('reports.access')
                <a href="{{ route('reports.index') }}" class="flex flex-col items-center p-4 bg-blue-50 rounded-lg hover:bg-blue-100 transition-colors">
                    <svg class="w-8 h-8 text-blue-600 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                    <span class="text-sm font-medium text-gray-700">Reports</span>
                </a>
                @endcan

                @can('items.access')
                <a href="{{ route('items.index') }}" class="flex flex-col items-center p-4 bg-purple-50 rounded-lg hover:bg-purple-100 transition-colors">
                    <svg class="w-8 h-8 text-purple-600 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                    </svg>
                    <span class="text-sm font-medium text-gray-700">Inventory</span>
                </a>
                @endcan

                @can('equipment.access')
                <a href="{{ route('equipment.index') }}" class="flex flex-col items-center p-4 bg-blue-50 rounded-lg hover:bg-blue-100 transition-colors">
                    <svg class="w-8 h-8 text-blue-600 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                    </svg>
                    <span class="text-sm font-medium text-gray-700">Equipment</span>
                </a>
                @endcan

                @can('certificates.access')
                <a href="{{ route('certificates.index') }}" class="flex flex-col items-center p-4 bg-green-50 rounded-lg hover:bg-green-100 transition-colors">
                    <svg class="w-8 h-8 text-green-600 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z"/>
                    </svg>
                    <span class="text-sm font-medium text-gray-700">Certificates</span>
                </a>
                @endcan
            </div>
        </div>

        <!-- Recent Activity -->
        <div class="bg-white rounded-xl shadow-sm p-6">
            <h2 class="text-xl font-bold text-gray-900 mb-4">Recent Activity</h2>
            <div class="space-y-3">
                @forelse($recentActivities as $activity)
                <div class="flex items-start space-x-3 py-2 border-b border-gray-100 last:border-0">
                    <div class="flex-shrink-0">
                        <div class="w-10 h-10 bg-blue-500 rounded-full flex items-center justify-center text-white text-sm font-bold">
                            {{ strtoupper(substr($activity->employee->firstname ?? 'S', 0, 1)) }}{{ strtoupper(substr($activity->employee->lastname ?? 'Y', 0, 1)) }}
                        </div>
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-medium text-gray-900">
                            {{ $activity->employee->firstname ?? 'System' }} {{ $activity->employee->lastname ?? '' }}
                        </p>
                        <p class="text-sm text-gray-600">{{ $activity->action ?? 'System activity' }}</p>
                        <p class="text-xs text-gray-400 mt-1">
                            {{ $activity->datetime_added ? \Carbon\Carbon::parse($activity->datetime_added)->diffForHumans() : 'Recently' }}
                        </p>
                    </div>
                </div>
                @empty
                <p class="text-gray-500 text-center py-4">No recent activity</p>
                @endforelse
            </div>
        </div>
    </div>
</div>
@endsection
