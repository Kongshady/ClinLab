<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name', 'ClinLab') }}</title>
    
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['Poppins', 'system-ui', 'sans-serif'],
                    },
                }
            }
        }
    </script>
    
    <!-- Alpine.js -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    
    @livewireStyles
</head>
<body class="bg-gray-50 font-sans antialiased">
    <div class="flex h-screen overflow-hidden">
        <!-- Sidebar -->
        <aside class="w-64 bg-white border-r border-gray-200 flex flex-col shadow-sm">
            <!-- Logo/Brand -->
            <div class="p-6 border-b border-gray-200">
                <div class="flex items-center space-x-3">
                    
                    <div>
                        <h1 class="text-xl font-bold text-gray-900">ClinLab</h1>
                        <p class="text-xs text-gray-500">Laboratory System</p>
                    </div>
                </div>
            </div>

            <!-- Navigation -->
            <nav class="flex-1 px-4 py-6 overflow-y-auto scrollbar-thin scrollbar-thumb-gray-300 scrollbar-track-transparent">
                <div class="space-y-1.5">
                    <!-- Main Section -->
                    <div class="mb-6">
                        <p class="px-4 mb-3 text-xs font-semibold text-gray-400 uppercase tracking-wider">Main</p>
                        <a href="/dashboard" class="nav-link group flex items-center px-4 py-3 text-sm font-medium rounded-xl transition-all {{ request()->is('dashboard') ? 'bg-gradient-to-r from-blue-500 to-cyan-400 text-white shadow-lg shadow-blue-500/20' : 'text-gray-700 hover:bg-blue-50 hover:text-blue-600' }}">
                            <svg class="w-5 h-5 mr-3 {{ request()->is('dashboard') ? '' : 'text-gray-400 group-hover:text-blue-500' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
                            </svg>
                            <span>Dashboard</span>
                        </a>
                    </div>

                    <!-- Patient Management -->
                    @if(auth()->user()->can('patients.access') || auth()->user()->can('physicians.access'))
                    <div class="mb-6">
                        <p class="px-4 mb-3 text-xs font-semibold text-gray-400 uppercase tracking-wider">Patient Management</p>
                        @can('patients.access')
                        <a href="/patients" class="nav-link group flex items-center px-4 py-3 text-sm font-medium rounded-xl transition-all {{ request()->is('patients*') ? 'bg-gradient-to-r from-blue-500 to-cyan-400 text-white shadow-lg shadow-blue-500/20' : 'text-gray-700 hover:bg-blue-50 hover:text-blue-600' }}">
                            <svg class="w-5 h-5 mr-3 {{ request()->is('patients*') ? '' : 'text-gray-400 group-hover:text-blue-500' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                            </svg>
                            <span>Patients</span>
                        </a>
                        @endcan
                        @can('physicians.access')
                        <a href="/physicians" class="nav-link group flex items-center px-4 py-3 text-sm font-medium rounded-xl transition-all {{ request()->is('physicians*') ? 'bg-gradient-to-r from-blue-500 to-cyan-400 text-white shadow-lg shadow-blue-500/20' : 'text-gray-700 hover:bg-blue-50 hover:text-blue-600' }}">
                            <svg class="w-5 h-5 mr-3 {{ request()->is('physicians*') ? '' : 'text-gray-400 group-hover:text-blue-500' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5.121 17.804A13.937 13.937 0 0112 16c2.5 0 4.847.655 6.879 1.804M15 10a3 3 0 11-6 0 3 3 0 016 0zm6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            <span>Physicians</span>
                        </a>
                        @endcan
                    </div>
                    @endif

                    <!-- Laboratory -->
                    @if(auth()->user()->can('lab-results.access') || auth()->user()->can('tests.access') || auth()->user()->can('certificates.access'))
                    <div class="mb-6">
                        <p class="px-4 mb-3 text-xs font-semibold text-gray-400 uppercase tracking-wider">Laboratory</p>
                        @can('lab-results.access')
                        <a href="/lab-results" class="nav-link group flex items-center px-4 py-3 text-sm font-medium rounded-xl transition-all {{ request()->is('lab-results*') ? 'bg-gradient-to-r from-blue-500 to-cyan-400 text-white shadow-lg shadow-blue-500/20' : 'text-gray-700 hover:bg-blue-50 hover:text-blue-600' }}">
                            <svg class="w-5 h-5 mr-3 {{ request()->is('lab-results*') ? '' : 'text-gray-400 group-hover:text-blue-500' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                            </svg>
                            <span>Lab Results</span>
                        </a>
                        @endcan
                        @can('tests.access')
                        <a href="/tests" class="nav-link group flex items-center px-4 py-3 text-sm font-medium rounded-xl transition-all {{ request()->is('tests*') ? 'bg-gradient-to-r from-blue-500 to-cyan-400 text-white shadow-lg shadow-blue-500/20' : 'text-gray-700 hover:bg-blue-50 hover:text-blue-600' }}">
                            <svg class="w-5 h-5 mr-3 {{ request()->is('tests*') ? '' : 'text-gray-400 group-hover:text-blue-500' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z"/>
                            </svg>
                            <span>Tests</span>
                        </a>
                        @endcan
                        @can('certificates.access')
                        <a href="/certificates" class="nav-link group flex items-center px-4 py-3 text-sm font-medium rounded-xl transition-all {{ request()->is('certificates*') ? 'bg-gradient-to-r from-blue-500 to-cyan-400 text-white shadow-lg shadow-blue-500/20' : 'text-gray-700 hover:bg-blue-50 hover:text-blue-600' }}">
                            <svg class="w-5 h-5 mr-3 {{ request()->is('certificates*') ? '' : 'text-gray-400 group-hover:text-blue-500' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                            </svg>
                            <span>Certificates</span>
                        </a>
                        @endcan
                    </div>
                    @endif

                    <!-- Inventory & Equipment -->
                    @if(auth()->user()->can('transactions.access') || auth()->user()->can('items.access') || auth()->user()->can('equipment.access') || auth()->user()->can('calibration.access') || auth()->user()->can('inventory.access'))
                    <div class="mb-6">
                        <p class="px-4 mb-3 text-xs font-semibold text-gray-400 uppercase tracking-wider">Resources</p>
                        @can('transactions.access')
                        <a href="/transactions" class="nav-link group flex items-center px-4 py-3 text-sm font-medium rounded-xl transition-all {{ request()->is('transactions*') ? 'bg-gradient-to-r from-blue-500 to-cyan-400 text-white shadow-lg shadow-blue-500/20' : 'text-gray-700 hover:bg-blue-50 hover:text-blue-600' }}">
                            <svg class="w-5 h-5 mr-3 {{ request()->is('transactions*') ? '' : 'text-gray-400 group-hover:text-blue-500' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/>
                            </svg>
                            <span>Transactions</span>
                        </a>
                        @endcan
                        @can('items.access')
                        <a href="/items" class="nav-link group flex items-center px-4 py-3 text-sm font-medium rounded-xl transition-all {{ request()->is('items*') ? 'bg-gradient-to-r from-blue-500 to-cyan-400 text-white shadow-lg shadow-blue-500/20' : 'text-gray-700 hover:bg-blue-50 hover:text-blue-600' }}">
                            <svg class="w-5 h-5 mr-3 {{ request()->is('items*') ? '' : 'text-gray-400 group-hover:text-blue-500' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                            </svg>
                            <span>Items</span>
                        </a>
                        @endcan
                        @can('inventory.access')
                        <a href="/inventory" class="nav-link group flex items-center px-4 py-3 text-sm font-medium rounded-xl transition-all {{ request()->is('inventory*') ? 'bg-gradient-to-r from-blue-500 to-cyan-400 text-white shadow-lg shadow-blue-500/20' : 'text-gray-700 hover:bg-blue-50 hover:text-blue-600' }}">
                            <svg class="w-5 h-5 mr-3 {{ request()->is('inventory*') ? '' : 'text-gray-400 group-hover:text-blue-500' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"/>
                            </svg>
                            <span>Inventory</span>
                        </a>
                        @endcan
                        @can('equipment.access')
                        <a href="/equipment" class="nav-link group flex items-center px-4 py-3 text-sm font-medium rounded-xl transition-all {{ request()->is('equipment*') ? 'bg-gradient-to-r from-blue-500 to-cyan-400 text-white shadow-lg shadow-blue-500/20' : 'text-gray-700 hover:bg-blue-50 hover:text-blue-600' }}">
                            <svg class="w-5 h-5 mr-3 {{ request()->is('equipment*') ? '' : 'text-gray-400 group-hover:text-blue-500' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                            </svg>
                            <span>Equipment</span>
                        </a>
                        @endcan
                        @can('calibration.access')
                        <a href="/calibration" class="nav-link group flex items-center px-4 py-3 text-sm font-medium rounded-xl transition-all {{ request()->is('calibration*') ? 'bg-gradient-to-r from-blue-500 to-cyan-400 text-white shadow-lg shadow-blue-500/20' : 'text-gray-700 hover:bg-blue-50 hover:text-blue-600' }}">
                            <svg class="w-5 h-5 mr-3 {{ request()->is('calibration*') ? '' : 'text-gray-400 group-hover:text-blue-500' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                            </svg>
                            <span>Calibration</span>
                        </a>
                        @endcan
                    </div>
                    @endif

                    <!-- MIT Staff Section -->
                    @if(auth()->user()->can('sections.access') || auth()->user()->can('employees.access'))
                    <div class="mb-6">
                        <p class="px-4 mb-3 text-xs font-semibold text-gray-400 uppercase tracking-wider">MIT Management</p>
                        @can('sections.access')
                        <a href="/sections" class="nav-link group flex items-center px-4 py-3 text-sm font-medium rounded-xl transition-all {{ request()->is('sections*') ? 'bg-gradient-to-r from-blue-500 to-cyan-400 text-white shadow-lg shadow-blue-500/20' : 'text-gray-700 hover:bg-blue-50 hover:text-blue-600' }}">
                            <svg class="w-5 h-5 mr-3 {{ request()->is('sections*') ? '' : 'text-gray-400 group-hover:text-blue-500' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
                            </svg>
                            <span>Sections</span>
                        </a>
                        @endcan
                        @can('employees.access')
                        <a href="/employees" class="nav-link group flex items-center px-4 py-3 text-sm font-medium rounded-xl transition-all {{ request()->is('employees*') ? 'bg-gradient-to-r from-blue-500 to-cyan-400 text-white shadow-lg shadow-blue-500/20' : 'text-gray-700 hover:bg-blue-50 hover:text-blue-600' }}">
                            <svg class="w-5 h-5 mr-3 {{ request()->is('employees*') ? '' : 'text-gray-400 group-hover:text-blue-500' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                            </svg>
                            <span>Employees</span>
                        </a>
                        <a href="/users" class="nav-link group flex items-center px-4 py-3 text-sm font-medium rounded-xl transition-all {{ request()->is('users*') ? 'bg-gradient-to-r from-blue-500 to-cyan-400 text-white shadow-lg shadow-blue-500/20' : 'text-gray-700 hover:bg-blue-50 hover:text-blue-600' }}">
                            <svg class="w-5 h-5 mr-3 {{ request()->is('users*') ? '' : 'text-gray-400 group-hover:text-blue-500' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/>
                            </svg>
                            <span>User Accounts</span>
                        </a>
                        @endcan
                    </div>
                    @endif

                    <!-- Reports -->
                    @if(auth()->user()->can('reports.access') || auth()->user()->can('activity-logs.access'))
                    <div>
                        <p class="px-4 mb-3 text-xs font-semibold text-gray-400 uppercase tracking-wider">Analytics</p>
                        @can('reports.access')
                        <a href="/reports" class="nav-link group flex items-center px-4 py-3 text-sm font-medium rounded-xl transition-all {{ request()->is('reports*') ? 'bg-gradient-to-r from-blue-500 to-cyan-400 text-white shadow-lg shadow-blue-500/20' : 'text-gray-700 hover:bg-blue-50 hover:text-blue-600' }}">
                            <svg class="w-5 h-5 mr-3 {{ request()->is('reports*') ? '' : 'text-gray-400 group-hover:text-blue-500' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                            </svg>
                            <span>Reports</span>
                        </a>
                        @endcan
                        @can('activity-logs.access')
                        <a href="/activity-logs" class="nav-link group flex items-center px-4 py-3 text-sm font-medium rounded-xl transition-all {{ request()->is('activity-logs*') ? 'bg-gradient-to-r from-blue-500 to-cyan-400 text-white shadow-lg shadow-blue-500/20' : 'text-gray-700 hover:bg-blue-50 hover:text-blue-600' }}">
                            <svg class="w-5 h-5 mr-3 {{ request()->is('activity-logs*') ? '' : 'text-gray-400 group-hover:text-blue-500' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            <span>Activity Logs</span>
                        </a>
                        @endcan
                    </div>
                    @endif
                </div>
            </nav>

            <!-- User Profile & Settings -->
            <div class="p-4 border-t border-gray-200" x-data="{ open: false }">
                <!-- Dropdown Menu -->
                <div x-show="open" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 translate-y-1" x-transition:enter-end="opacity-100 translate-y-0" x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100 translate-y-0" x-transition:leave-end="opacity-0 translate-y-1" class="mb-2 space-y-1">
                    <!-- Account Settings -->
                    <a href="/account-settings" class="flex items-center justify-between px-4 py-3 text-gray-700 hover:bg-gray-50 rounded-lg transition-colors">
                        <div class="flex items-center space-x-3">
                            <svg class="w-5 h-5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                            </svg>
                            <span class="text-sm font-medium">Account Settings</span>
                        </div>
                        
                    </a>

                    <!-- Log out -->
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="w-full flex items-center justify-between px-4 py-3 text-red-600 hover:bg-red-50 rounded-lg transition-colors">
                            <div class="flex items-center space-x-3">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                                </svg>
                                <span class="text-sm font-medium">Log out</span>
                            </div>
                            
                        </button>
                    </form>
                </div>

                <!-- User Profile Toggle -->
                <button @click="open = !open" class="w-full flex items-center space-x-3 px-3 py-2 hover:bg-gray-50 rounded-lg transition-colors">
                    <div class="w-10 h-10 rounded-full bg-blue-500 flex items-center justify-center text-white font-semibold text-sm">
                        {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
                    </div>
                    <div class="flex-1 text-left">
                        <p class="text-sm font-semibold text-gray-900">{{ auth()->user()->name }}</p>
                        <p class="text-xs text-gray-500">{{ ucfirst(str_replace('_', ' ', auth()->user()->roles->first()->name ?? 'User')) }}</p>
                    </div>
                    <svg class="w-4 h-4 text-gray-400 transition-transform" :class="{ 'rotate-180': open }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7"/>
                    </svg>
                </button>
            </div>
        </aside>

        <!-- Main Content Area -->
        <div class="flex-1 flex flex-col overflow-hidden">
            <!-- Top Header -->
            <header class="bg-white border-b border-gray-200 p-4 shadow-sm">
                <div class="flex items-center justify-between">
                    <div>
                        <h2 class="text-xl font-semibold text-gray-900">{{ $title ?? 'ClinLab System' }}</h2>
                        <p class="text-sm text-gray-500">{{ now()->format('l, F j, Y') }}</p>
                    </div>
                </div>
            </header>

            <!-- Page Content -->
            <main class="flex-1 overflow-y-auto bg-gray-50">
                @yield('content')
            </main>
        </div>
    </div>

    @livewireScripts
</body>
</html>
