@extends('layouts.app')

@section('content')
<div class="p-6">
    <!-- Welcome Header with Action Buttons -->
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-3xl font-bold text-gray-900">Welcome, {{ auth()->user()->name }}!</h1>
            <p class="mt-1 text-sm text-gray-600">Role: <span class="font-medium text-gray-900">{{ strtoupper(str_replace('_', ' ', auth()->user()->roles->first()->name ?? 'User')) }}</span></p>
        </div>
        <div class="flex items-center space-x-3">
            @can('patients.create')
            <a href="/patients" class="px-4 py-2 bg-white border border-gray-300 text-gray-700 text-sm font-medium rounded-lg hover:bg-gray-50 transition-colors">
                Add Patient
            </a>
            @endcan
            @can('items.create')
            <a href="/items" class="px-4 py-2 bg-white border border-gray-300 text-gray-700 text-sm font-medium rounded-lg hover:bg-gray-50 transition-colors">
                Stock In
            </a>
            @endcan
            @can('equipment.create')
            <a href="/equipment" class="px-4 py-2 bg-white border border-gray-300 text-gray-700 text-sm font-medium rounded-lg hover:bg-gray-50 transition-colors">
                Add Equipment
            </a>
            @endcan
            @can('transactions.create')
            <a href="/transactions" class="px-4 py-2 bg-white border border-gray-300 text-gray-700 text-sm font-medium rounded-lg hover:bg-gray-50 transition-colors">
                New Transaction
            </a>
            @endcan
            @can('reports.access')
            <a href="/reports" class="px-4 py-2 bg-white border border-gray-300 text-gray-700 text-sm font-medium rounded-lg hover:bg-gray-50 transition-colors">
                Generate Report
            </a>
            @endcan
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-6">
        <!-- Patients Card -->
        @can('patients.access')
        <div class="bg-white rounded-lg shadow-sm p-6">
            <div class="flex items-center justify-between mb-4">
                <div class="w-12 h-12 rounded-lg bg-blue-100 flex items-center justify-center">
                    <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                    </svg>
                </div>
            </div>
            <h3 class="text-2xl font-bold text-gray-900 mb-1">{{ \App\Models\Patient::where('is_deleted', 0)->count() }}</h3>
            <p class="text-sm text-gray-600 mb-2">Patients</p>
            <div class="flex items-center justify-between">
                <span class="text-xs text-gray-500">Added today: +{{ \App\Models\Patient::where('is_deleted', 0)->whereDate('datetime_added', today())->count() }}</span>
                <a href="/patients" class="text-xs text-blue-600 hover:text-blue-700 font-medium">View</a>
            </div>
        </div>
        @endcan

        <!-- Inventory Card -->
        @can('items.access')
        <div class="bg-white rounded-lg shadow-sm p-6">
            <div class="flex items-center justify-between mb-4">
                <div class="w-12 h-12 rounded-lg bg-green-100 flex items-center justify-center">
                    <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                    </svg>
                </div>
            </div>
            <h3 class="text-2xl font-bold text-gray-900 mb-1">{{ \App\Models\Item::where('is_deleted', 0)->count() }}</h3>
            <p class="text-sm text-gray-600 mb-2">Inventory</p>
            <div class="flex items-center justify-between">
                <span class="text-xs text-gray-500">Added today: +{{ \App\Models\Item::where('is_deleted', 0)->whereDate('datetime_added', today())->count() }}</span>
                <a href="/items" class="text-xs text-blue-600 hover:text-blue-700 font-medium">Manage</a>
            </div>
        </div>
        @endcan

        <!-- Equipment Card -->
        @can('equipment.access')
        <div class="bg-white rounded-lg shadow-sm p-6">
            <div class="flex items-center justify-between mb-4">
                <div class="w-12 h-12 rounded-lg bg-purple-100 flex items-center justify-center">
                    <svg class="w-6 h-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                    </svg>
                </div>
            </div>
            <h3 class="text-2xl font-bold text-gray-900 mb-1">{{ \App\Models\Equipment::where('is_deleted', 0)->count() }}</h3>
            <p class="text-sm text-gray-600 mb-2">Equipment</p>
            <div class="flex items-center justify-between">
                <span class="text-xs text-gray-500">Added today: +{{ \App\Models\Equipment::where('is_deleted', 0)->whereDate('datetime_added', today())->count() }}</span>
                <a href="/equipment" class="text-xs text-blue-600 hover:text-blue-700 font-medium">View</a>
            </div>
        </div>
        @endcan

        <!-- Transactions Card -->
        @can('transactions.access')
        <div class="bg-white rounded-lg shadow-sm p-6">
            <div class="flex items-center justify-between mb-4">
                <div class="w-12 h-12 rounded-lg bg-cyan-100 flex items-center justify-center">
                    <svg class="w-6 h-6 text-cyan-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                </div>
            </div>
            <h3 class="text-2xl font-bold text-gray-900 mb-1">{{ \App\Models\Transaction::where('is_deleted', 0)->count() }}</h3>
            <p class="text-sm text-gray-600 mb-2">Transactions</p>
            <div class="flex items-center justify-between">
                <span class="text-xs text-gray-500">Added today: +{{ \App\Models\Transaction::where('is_deleted', 0)->whereDate('datetime_added', today())->count() }}</span>
                <a href="/transactions" class="text-xs text-blue-600 hover:text-blue-700 font-medium">View</a>
            </div>
        </div>
        @endcan
    </div>

    <!-- Recent Activity Table -->
    @can('activity-logs.access')
    <div class="bg-white rounded-lg shadow-sm">
        <div class="px-6 py-4 border-b border-gray-200">
            <div class="flex items-center justify-between">
                <h3 class="text-lg font-semibold text-gray-900">Recent Activity</h3>
                <a href="/activity-logs" class="text-sm text-blue-600 hover:text-blue-700 font-medium">View All →</a>
            </div>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-50 border-b border-gray-200">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">User</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Activity</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date & Time</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse(\App\Models\ActivityLog::with('employee')->orderBy('datetime_added', 'desc')->limit(10)->get() as $log)
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="flex items-center">
                                <div class="w-8 h-8 rounded-full bg-blue-100 flex items-center justify-center text-blue-600 font-medium text-sm mr-3">
                                    @if($log->employee)
                                        {{ strtoupper(substr($log->employee->firstname ?? 'S', 0, 1)) }}{{ strtoupper(substr($log->employee->lastname ?? 'Y', 0, 1)) }}
                                    @else
                                        S
                                    @endif
                                </div>
                                <span class="text-sm font-medium text-gray-900">
                                    @if($log->employee)
                                        {{ $log->employee->firstname }} {{ $log->employee->lastname }}
                                    @else
                                        System
                                    @endif
                                </span>
                            </div>
                        </td>
                        <td class="px-6 py-4">
                            <span class="text-sm text-gray-700">{{ $log->description }}</span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="text-sm text-gray-500">{{ \Carbon\Carbon::parse($log->datetime_added)->format('M d, Y h:i A') }}</span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                Success
                            </span>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="4" class="px-6 py-12 text-center text-gray-500">
                            <svg class="w-12 h-12 mx-auto mb-3 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            <p class="text-sm">No recent activity</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @endcan
</div>
@endsection
