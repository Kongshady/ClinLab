@extends('layouts.app')

@section('content')
<div class="p-6 space-y-6">
    <!-- Welcome Header -->
    <div class="bg-white rounded-lg shadow-sm p-6">
        <h1 class="text-2xl font-bold text-gray-900 mb-2">Welcome back, {{ auth()->user()->name }}!</h1>
        <p class="text-gray-600">Here's what's happening in your laboratory today</p>
    </div>

    <!-- Statistics Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
        <!-- Total Patients Card -->
        @can('patients.access')
        <div class="bg-white rounded-lg shadow-sm p-6 hover:shadow-md transition-shadow">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-600 mb-1">Total Patients</p>
                    <p class="text-3xl font-bold text-gray-900">{{ \App\Models\Patient::where('is_deleted', 0)->count() }}</p>
                    <p class="text-xs text-gray-500 mt-2">
                        <span class="text-green-600 font-medium">+{{ \App\Models\Patient::where('is_deleted', 0)->whereDate('datetime_added', today())->count() }}</span> today
                    </p>
                </div>
                <div class="w-14 h-14 rounded-lg bg-blue-100 flex items-center justify-center">
                    <svg class="w-7 h-7 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                    </svg>
                </div>
            </div>
        </div>
        @endcan

        <!-- Lab Results Card -->
        @can('lab-results.access')
        <div class="bg-white rounded-lg shadow-sm p-6 hover:shadow-md transition-shadow">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-600 mb-1">Lab Results</p>
                    <p class="text-3xl font-bold text-gray-900">{{ \App\Models\LabResult::count() }}</p>
                    <p class="text-xs text-gray-500 mt-2">
                        <span class="text-cyan-600 font-medium">{{ \App\Models\LabResult::whereDate('result_date', today())->count() }}</span> processed today
                    </p>
                </div>
                <div class="w-14 h-14 rounded-lg bg-cyan-100 flex items-center justify-center">
                    <svg class="w-7 h-7 text-cyan-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                </div>
            </div>
        </div>
        @endcan

        <!-- Tests Available Card -->
        @can('tests.access')
        <div class="bg-white rounded-lg shadow-sm p-6 hover:shadow-md transition-shadow">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-600 mb-1">Available Tests</p>
                    <p class="text-3xl font-bold text-gray-900">{{ \App\Models\Test::where('is_deleted', 0)->count() }}</p>
                    <p class="text-xs text-gray-500 mt-2">
                        <span class="text-purple-600 font-medium">{{ \App\Models\Section::where('is_deleted', 0)->count() }}</span> sections
                    </p>
                </div>
                <div class="w-14 h-14 rounded-lg bg-purple-100 flex items-center justify-center">
                    <svg class="w-7 h-7 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z"/>
                    </svg>
                </div>
            </div>
        </div>
        @endcan

        <!-- Equipment Card -->
        @can('equipment.access')
        <div class="bg-white rounded-lg shadow-sm p-6 hover:shadow-md transition-shadow">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-600 mb-1">Equipment</p>
                    <p class="text-3xl font-bold text-gray-900">{{ \App\Models\Equipment::where('is_deleted', 0)->count() }}</p>
                    <p class="text-xs text-gray-500 mt-2">
                        <span class="text-green-600 font-medium">{{ \App\Models\Equipment::where('is_deleted', 0)->where('status', 'Operational')->count() }}</span> operational
                    </p>
                </div>
                <div class="w-14 h-14 rounded-lg bg-green-100 flex items-center justify-center">
                    <svg class="w-7 h-7 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                    </svg>
                </div>
            </div>
        </div>
        @endcan
    </div>

    <!-- Recent Activity & Quick Actions -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Recent Patients -->
        @can('patients.access')
        <div class="lg:col-span-2 bg-white rounded-lg shadow-sm p-6">
            <div class="flex items-center justify-between mb-6">
                <h3 class="text-lg font-semibold text-gray-900">Recent Patients</h3>
                <a href="/patients" class="text-sm text-blue-600 hover:text-blue-700 font-medium transition-colors">View All →</a>
            </div>
            <div class="space-y-3">
                @forelse(\App\Models\Patient::where('is_deleted', 0)->orderBy('datetime_added', 'desc')->limit(5)->get() as $patient)
                <div class="flex items-center justify-between p-3 border border-gray-200 rounded-lg hover:border-gray-300 hover:bg-gray-50 transition-all">
                    <div class="flex items-center space-x-3">
                        <div class="w-10 h-10 rounded-full bg-gradient-to-br from-blue-500 to-cyan-400 flex items-center justify-center text-white font-semibold text-sm">
                            {{ strtoupper(substr($patient->firstname, 0, 1)) }}{{ strtoupper(substr($patient->lastname, 0, 1)) }}
                        </div>
                        <div>
                            <p class="text-sm font-medium text-gray-900">{{ $patient->firstname }} {{ $patient->lastname }}</p>
                            <p class="text-xs text-gray-500">{{ $patient->patient_type }} • {{ \Carbon\Carbon::parse($patient->birthdate)->age }} years old</p>
                        </div>
                    </div>
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">{{ $patient->gender }}</span>
                </div>
                @empty
                <div class="text-center py-8 text-gray-500">
                    <svg class="w-12 h-12 mx-auto mb-3 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                    </svg>
                    <p class="text-sm">No patients registered yet</p>
                </div>
                @endforelse
            </div>
        </div>
        @endcan

        <!-- Quick Actions -->
        <div class="bg-white rounded-lg shadow-sm p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-6">Quick Actions</h3>
            <div class="space-y-3">
                @can('patients.create')
                <a href="/patients" class="flex items-center p-3 border border-blue-200 bg-blue-50 rounded-lg hover:bg-blue-100 hover:border-blue-300 transition-all group">
                    <div class="w-10 h-10 rounded-lg bg-blue-500 flex items-center justify-center mr-3 group-hover:scale-110 transition-transform">
                        <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"/>
                        </svg>
                    </div>
                    <div>
                        <p class="text-sm font-medium text-gray-900">Add Patient</p>
                        <p class="text-xs text-gray-600">Register new patient</p>
                    </div>
                </a>
                @endcan

                @can('lab-results.create')
                <a href="/lab-results" class="flex items-center p-3 border border-cyan-200 bg-cyan-50 rounded-lg hover:bg-cyan-100 hover:border-cyan-300 transition-all group">
                    <div class="w-10 h-10 rounded-lg bg-cyan-500 flex items-center justify-center mr-3 group-hover:scale-110 transition-transform">
                        <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                        </svg>
                    </div>
                    <div>
                        <p class="text-sm font-medium text-gray-900">Record Result</p>
                        <p class="text-xs text-gray-600">Add lab result</p>
                    </div>
                </a>
                @endcan

                @can('tests.access')
                <a href="/tests" class="flex items-center p-3 border border-purple-200 bg-purple-50 rounded-lg hover:bg-purple-100 hover:border-purple-300 transition-all group">
                    <div class="w-10 h-10 rounded-lg bg-purple-500 flex items-center justify-center mr-3 group-hover:scale-110 transition-transform">
                        <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z"/>
                        </svg>
                    </div>
                    <div>
                        <p class="text-sm font-medium text-gray-900">View Tests</p>
                        <p class="text-xs text-gray-600">Browse lab tests</p>
                    </div>
                </a>
                @endcan

                @can('reports.access')
                <a href="/reports" class="flex items-center p-3 border border-green-200 bg-green-50 rounded-lg hover:bg-green-100 hover:border-green-300 transition-all group">
                    <div class="w-10 h-10 rounded-lg bg-green-500 flex items-center justify-center mr-3 group-hover:scale-110 transition-transform">
                        <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                        </svg>
                    </div>
                    <div>
                        <p class="text-sm font-medium text-gray-900">Generate Report</p>
                        <p class="text-xs text-gray-600">View analytics</p>
                    </div>
                </a>
                @endcan
            </div>
        </div>
    </div>

    <!-- Activity Overview -->
    @can('activity-logs.access')
    <div class="bg-white rounded-lg shadow-sm p-6">
        <div class="flex items-center justify-between mb-6">
            <h3 class="text-lg font-semibold text-gray-900">Recent Activity</h3>
            <a href="/activity-logs" class="text-sm text-blue-600 hover:text-blue-700 font-medium transition-colors">View All →</a>
        </div>
        <div class="space-y-3">
            @forelse(\App\Models\ActivityLog::orderBy('datetime_added', 'desc')->limit(6)->get() as $log)
            <div class="flex items-start space-x-3 p-3 border border-gray-200 rounded-lg">
                <div class="w-2 h-2 rounded-full bg-blue-500 mt-2"></div>
                <div class="flex-1">
                    <p class="text-sm text-gray-700">{{ $log->description }}</p>
                    <p class="text-xs text-gray-500 mt-1">{{ \Carbon\Carbon::parse($log->datetime_added)->diffForHumans() }}</p>
                </div>
            </div>
            @empty
            <div class="text-center py-8 text-gray-500">
                <svg class="w-12 h-12 mx-auto mb-3 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <p class="text-sm">No recent activity</p>
            </div>
            @endforelse
        </div>
    </div>
    @endcan
</div>
@endsection
