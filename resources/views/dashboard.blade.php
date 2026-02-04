@extends('layouts.app')

@section('content')
<div class="p-8 space-y-6 bg-gray-50 min-h-screen">
    <!-- Header with Quick Actions -->
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-4xl font-bold text-gray-800 mb-1">Welcome, {{ auth()->user()->name }}!</h1>
            <p class="text-gray-600">Role: <span class="font-semibold text-gray-800">{{ strtoupper(str_replace('-', '_', auth()->user()->getRoleNames()->first() ?? 'USER')) }}</span></p>
        </div>
        <div class="flex items-center space-x-3">
            @can('patients.create')
            <a href="/patients" class="px-4 py-2 bg-gray-200 hover:bg-gray-300 text-gray-800 rounded-lg text-sm font-medium transition-colors">
                Add Patient
            </a>
            @endcan
            @can('items.create')
            <a href="/items" class="px-4 py-2 bg-gray-200 hover:bg-gray-300 text-gray-800 rounded-lg text-sm font-medium transition-colors">
                Stock In
            </a>
            @endcan
            @can('equipment.create')
            <a href="/equipment" class="px-4 py-2 bg-gray-200 hover:bg-gray-300 text-gray-800 rounded-lg text-sm font-medium transition-colors">
                Add Equipment
            </a>
            @endcan
            @can('transactions.create')
            <a href="/transactions" class="px-4 py-2 bg-gray-200 hover:bg-gray-300 text-gray-800 rounded-lg text-sm font-medium transition-colors">
                New Transaction
            </a>
            @endcan
            @can('reports.access')
            <a href="/reports" class="px-4 py-2 bg-gray-200 hover:bg-gray-300 text-gray-800 rounded-lg text-sm font-medium transition-colors">
                Generate Report
            </a>
            @endcan
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
        <!-- Total Patients Card -->
        @can('patients.access')
        <div class="bg-white rounded-lg shadow p-6 border border-gray-200">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-sm font-semibold text-gray-600 uppercase">Patients</h3>
                <span class="text-blue-600">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                    </svg>
                </span>
            </div>
            <p class="text-4xl font-bold text-gray-900">{{ \App\Models\Patient::where('is_deleted', 0)->count() }}</p>
            <p class="text-sm text-gray-500 mt-2">
                Added today: <span class="text-blue-600 font-semibold">{{ \App\Models\Patient::where('is_deleted', 0)->whereDate('datetime_added', today())->count() }}</span> patients
            </p>
            <a href="/patients" class="text-sm text-blue-600 hover:text-blue-700 mt-3 inline-flex items-center">
                View Patients →
            </a>
        </div>
        @endcan

        <!-- Inventory Card -->
        @can('items.access')
        <div class="bg-white rounded-lg shadow p-6 border border-gray-200">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-sm font-semibold text-gray-600 uppercase">Inventory</h3>
                <span class="text-green-600">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                    </svg>
                </span>
            </div>
            <p class="text-4xl font-bold text-gray-900">{{ \App\Models\Item::where('is_deleted', 0)->count() }}</p>
            <p class="text-sm text-gray-500 mt-2">
                Total active items in stock
            </p>
            <a href="/items" class="text-sm text-green-600 hover:text-green-700 mt-3 inline-flex items-center">
                Manage Inventory →
            </a>
        </div>
        @endcan

        <!-- Equipment Card -->
        @can('equipment.access')
        <div class="bg-white rounded-lg shadow p-6 border border-gray-200">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-sm font-semibold text-gray-600 uppercase">Equipment</h3>
                <span class="text-purple-600">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                    </svg>
                </span>
            </div>
            <p class="text-4xl font-bold text-gray-900">{{ \App\Models\Equipment::where('is_deleted', 0)->count() }}</p>
            <p class="text-sm text-gray-500 mt-2">
                Added today: <span class="text-purple-600 font-semibold">{{ \App\Models\Equipment::where('is_deleted', 0)->whereDate('datetime_added', today())->count() }}</span> items
            </p>
            <a href="/equipment" class="text-sm text-purple-600 hover:text-purple-700 mt-3 inline-flex items-center">
                View Equipment →
            </a>
        </div>
        @endcan

        <!-- Transactions Card -->
        @can('transactions.access')
        <div class="bg-white rounded-lg shadow p-6 border border-gray-200">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-sm font-semibold text-gray-600 uppercase">Transactions</h3>
                <span class="text-blue-600">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </span>
            </div>
            <p class="text-4xl font-bold text-gray-900">{{ \App\Models\Transaction::count() }}</p>
            <p class="text-sm text-gray-500 mt-2">
                Added today: <span class="text-blue-600 font-semibold">{{ \App\Models\Transaction::whereDate('datetime_added', today())->count() }}</span> transactions
            </p>
            <a href="/transactions" class="text-sm text-blue-600 hover:text-blue-700 mt-3 inline-flex items-center">
                View Transactions →
            </a>
        </div>
        @endcan
    </div>

    <!-- Activity Overview -->
    @can('activity-logs.access')
    <div class="bg-white rounded-lg shadow border border-gray-200">
        <div class="px-6 py-4 border-b border-gray-200">
            <h3 class="text-xl font-bold text-gray-900">Recent Activity</h3>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-50 border-b border-gray-200">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">User</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Activity</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Date & Time</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @forelse(\App\Models\ActivityLog::orderBy('datetime_added', 'desc')->limit(10)->get() as $log)
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="flex items-center">
                                <div class="text-sm font-medium text-gray-900">{{ $log->user_id ? \App\Models\User::find($log->user_id)?->name : 'System' }}</div>
                            </div>
                        </td>
                        <td class="px-6 py-4">
                            <div class="text-sm text-gray-900">{{ $log->description }}</div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm text-gray-600">{{ \Carbon\Carbon::parse($log->datetime_added)->format('M d, Y h:i A') }}</div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="px-3 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">
                                Success
                            </span>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="4" class="px-6 py-8 text-center">
                            <svg class="w-12 h-12 mx-auto mb-3 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            <p class="text-gray-500">No recent activity</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="px-6 py-3 border-t border-gray-200 bg-gray-50">
            <a href="/activity-logs" class="text-sm text-blue-600 hover:text-blue-700 font-medium">View All Activity →</a>
        </div>
    </div>
    @endcan
</div>
@endsection
