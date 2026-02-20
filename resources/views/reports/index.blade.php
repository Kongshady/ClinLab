@extends('layouts.app')

@section('content')
<div class="p-8 space-y-6 bg-gray-50 min-h-screen">
    <!-- Header -->
    <div class="mb-6">
        <h1 class="text-3xl font-bold text-gray-800">Reports & Analytics</h1>
        <p class="text-gray-600 mt-1">View and generate laboratory reports</p>
    </div>

    <!-- Report Generation Form -->
    <div class="bg-white rounded-lg shadow border border-gray-200">
        <div class="px-6 py-4 border-b border-gray-200">
            <h2 class="text-lg font-semibold text-gray-900 flex items-center">
                <svg class="w-5 h-5 mr-2 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                </svg>
                Generate Report
            </h2>
        </div>
        <div class="p-6">
            <form method="GET" action="{{ route('reports.index') }}">
                <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
                    <div>
                        <label for="report_type" class="block text-sm font-medium text-gray-700 mb-2">
                            Report Type <span class="text-red-500">*</span>
                        </label>
                        <select 
                            name="report_type" 
                            id="report_type"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                        >
                            <option value="">Select Report Type</option>
                            <optgroup label="Equipment & Lab">
                                <option value="equipment_maintenance" {{ request('report_type') == 'equipment_maintenance' ? 'selected' : '' }}>Equipment Maintenance</option>
                                <option value="calibration_records" {{ request('report_type') == 'calibration_records' ? 'selected' : '' }}>Calibration Records</option>
                                <option value="laboratory_results" {{ request('report_type') == 'laboratory_results' ? 'selected' : '' }}>Laboratory Results</option>
                            </optgroup>
                            <optgroup label="Inventory">
                                <option value="inventory_movement" {{ request('report_type') == 'inventory_movement' ? 'selected' : '' }}>Inventory Movement</option>
                                <option value="low_stock_alert" {{ request('report_type') == 'low_stock_alert' ? 'selected' : '' }}>Low Stock Alert</option>
                                <option value="expiring_inventory" {{ request('report_type') == 'expiring_inventory' ? 'selected' : '' }}>Expiring Inventory</option>
                            </optgroup>
                            <optgroup label="Financial">
                                <option value="daily_collection" {{ request('report_type') == 'daily_collection' ? 'selected' : '' }}>Daily Collection</option>
                                <option value="revenue_by_test" {{ request('report_type') == 'revenue_by_test' ? 'selected' : '' }}>Revenue by Test</option>
                            </optgroup>
                            <optgroup label="Analytics">
                                <option value="test_volume" {{ request('report_type') == 'test_volume' ? 'selected' : '' }}>Test Volume</option>
                                <option value="issued_certificates" {{ request('report_type') == 'issued_certificates' ? 'selected' : '' }}>Issued Certificates</option>
                                <option value="activity_log" {{ request('report_type') == 'activity_log' ? 'selected' : '' }}>Activity Log</option>
                            </optgroup>
                        </select>
                    </div>
                    <div>
                        <label for="start_date" class="block text-sm font-medium text-gray-700 mb-2">
                            Start Date <span class="text-red-500">*</span>
                        </label>
                        <input 
                            type="date" 
                            name="start_date" 
                            id="start_date"
                            value="{{ request('start_date', now()->startOfMonth()->format('Y-m-d')) }}"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                        >
                    </div>
                    <div>
                        <label for="end_date" class="block text-sm font-medium text-gray-700 mb-2">
                            End Date <span class="text-red-500">*</span>
                        </label>
                        <input 
                            type="date" 
                            name="end_date" 
                            id="end_date"
                            value="{{ request('end_date', now()->format('Y-m-d')) }}"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                        >
                    </div>
                    <div>
                        <label for="section_id" class="block text-sm font-medium text-gray-700 mb-2">
                            Section (Optional)
                        </label>
                        <select 
                            name="section_id" 
                            id="section_id"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                        >
                            <option value="">All Sections</option>
                            @foreach($sections ?? [] as $section)
                                <option value="{{ $section->section_id }}" {{ request('section_id') == $section->section_id ? 'selected' : '' }}>
                                    {{ $section->label }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div id="employee-filter" style="{{ request('report_type') == 'activity_log' ? '' : 'display:none;' }}">
                        <label for="employee_id" class="block text-sm font-medium text-gray-700 mb-2">
                            Employee (Optional)
                        </label>
                        <select 
                            name="employee_id" 
                            id="employee_id"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                        >
                            <option value="">All Employees</option>
                            @foreach($employees ?? [] as $emp)
                                <option value="{{ $emp->employee_id }}" {{ request('employee_id') == $emp->employee_id ? 'selected' : '' }}>
                                    {{ $emp->full_name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="flex gap-3 mt-6">
                    <button 
                        type="submit"
                        class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors flex items-center"
                    >
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                        </svg>
                        Generate Report
                    </button>
                    <button 
                        type="button"
                        onclick="exportReport()"
                        class="px-6 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors flex items-center"
                    >
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                        </svg>
                        Export Report
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Report Display Section -->
    @if(request('report_type') && isset($reportData) && $reportData->count() > 0)
        <div class="bg-white rounded-lg shadow border border-gray-200">
            <div class="px-6 py-4 border-b border-gray-200 flex items-center justify-between">
                <h2 class="text-lg font-semibold text-gray-900">
                    @switch(request('report_type'))
                        @case('equipment_maintenance')
                            Equipment Maintenance Report
                            @break
                        @case('calibration_records')
                            Calibration Records Report
                            @break
                        @case('inventory_movement')
                            Inventory Movement Report
                            @break
                        @case('low_stock_alert')
                            Low Stock Alert Report
                            @break
                        @case('laboratory_results')
                            Laboratory Results Report
                            @break
                        @case('daily_collection')
                            Daily Collection Report
                            @break
                        @case('revenue_by_test')
                            Revenue by Test Report
                            @break
                        @case('test_volume')
                            Test Volume Report
                            @break
                        @case('issued_certificates')
                            Issued Certificates Report
                            @break
                        @case('activity_log')
                            Activity Log Report
                            @break
                        @case('expiring_inventory')
                            Expiring Inventory Report
                            @break
                    @endswitch
                </h2>
                <span class="text-sm text-gray-500">{{ $reportData->total() }} records found</span>
            </div>
            
            <div class="overflow-x-auto">
                @switch(request('report_type'))
                    @case('equipment_maintenance')
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Equipment Name</th>
                                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Model</th>
                                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Serial Number</th>
                                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Section</th>
                                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Status</th>
                                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Purchase Date</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @forelse($reportData as $equipment)
                                    <tr class="hover:bg-gray-50">
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">{{ $equipment->name }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">{{ $equipment->model ?? 'N/A' }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">{{ $equipment->serial_no ?? 'N/A' }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">{{ $equipment->section->label ?? 'N/A' }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <span class="px-3 py-1 text-xs font-medium rounded-full 
                                                @if($equipment->status === 'active') bg-green-100 text-green-800
                                                @elseif($equipment->status === 'maintenance') bg-yellow-100 text-yellow-800
                                                @else bg-red-100 text-red-800
                                                @endif">
                                                {{ ucfirst($equipment->status ?? 'active') }}
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                                            {{ $equipment->purchase_date ? \Carbon\Carbon::parse($equipment->purchase_date)->format('M d, Y') : 'N/A' }}
                                        </td>
                                    </tr>
                                @empty
                                    <tr><td colspan="6" class="text-center py-8 text-gray-500">No equipment found</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                        @break
                        
                    @case('inventory_movement')
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Date</th>
                                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Item</th>
                                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Type</th>
                                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Quantity</th>
                                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Supplier</th>
                                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Reference</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @forelse($reportData as $movement)
                                    <tr class="hover:bg-gray-50">
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                                            {{ $movement->datetime_added ? \Carbon\Carbon::parse($movement->datetime_added)->format('M d, Y') : 'N/A' }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">{{ $movement->item->label ?? 'N/A' }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <span class="px-3 py-1 text-xs font-medium rounded-full bg-green-100 text-green-800">
                                                Stock In
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">{{ $movement->quantity ?? 0 }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">{{ $movement->supplier ?? 'N/A' }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">{{ $movement->reference_number ?? 'N/A' }}</td>
                                    </tr>
                                @empty
                                    <tr><td colspan="6" class="text-center py-8 text-gray-500">No inventory movements found</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                        @break
                        
                    @case('low_stock_alert')
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Item Name</th>
                                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Current Stock</th>
                                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Reorder Level</th>
                                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Unit</th>
                                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Section</th>
                                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Status</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @forelse($reportData as $item)
                                    <tr class="hover:bg-gray-50">
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">{{ $item->label }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-red-600 font-semibold">{{ $item->current_stock ?? 0 }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">{{ $item->reorder_level ?? 0 }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">{{ $item->unit ?? 'N/A' }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">{{ $item->section->label ?? 'N/A' }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <span class="px-3 py-1 text-xs font-medium rounded-full bg-red-100 text-red-800">
                                                Low Stock
                                            </span>
                                        </td>
                                    </tr>
                                @empty
                                    <tr><td colspan="6" class="text-center py-8 text-gray-500">All items are sufficiently stocked</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                        @break
                        
                    @case('calibration_records')
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Equipment</th>
                                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Calibration Date</th>
                                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Next Due Date</th>
                                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Status</th>
                                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Performed By</th>
                                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Certificate No.</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @forelse($reportData as $calibration)
                                    <tr class="hover:bg-gray-50">
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">{{ $calibration->equipment->name ?? 'N/A' }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                                            {{ $calibration->calibration_date ? \Carbon\Carbon::parse($calibration->calibration_date)->format('M d, Y') : 'N/A' }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                                            {{ $calibration->next_due_date ? \Carbon\Carbon::parse($calibration->next_due_date)->format('M d, Y') : 'N/A' }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <span class="px-3 py-1 text-xs font-medium rounded-full 
                                                @if($calibration->status === 'completed') bg-green-100 text-green-800
                                                @elseif($calibration->status === 'due') bg-yellow-100 text-yellow-800
                                                @else bg-red-100 text-red-800
                                                @endif">
                                                {{ ucfirst($calibration->status ?? 'pending') }}
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">{{ $calibration->performed_by ?? 'N/A' }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">{{ $calibration->certificate_number ?? 'N/A' }}</td>
                                    </tr>
                                @empty
                                    <tr><td colspan="6" class="text-center py-8 text-gray-500">No calibration records found</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                        @break
                        
                    @case('laboratory_results')
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Date</th>
                                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Patient</th>
                                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Test</th>
                                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Result Value</th>
                                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Normal Range</th>
                                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Status</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @forelse($reportData as $result)
                                    <tr class="hover:bg-gray-50">
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                                            {{ $result->result_date ? \Carbon\Carbon::parse($result->result_date)->format('M d, Y') : 'N/A' }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">{{ $result->patient->full_name ?? 'N/A' }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">{{ $result->test->label ?? 'N/A' }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">{{ $result->result_value ?? 'N/A' }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">{{ $result->normal_range ?? 'N/A' }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <span class="px-3 py-1 text-xs font-medium rounded-full 
                                                @if($result->status === 'final') bg-green-100 text-green-800
                                                @elseif($result->status === 'revised') bg-blue-100 text-blue-800
                                                @else bg-yellow-100 text-yellow-800
                                                @endif">
                                                {{ ucfirst($result->status ?? 'draft') }}
                                            </span>
                                        </td>
                                    </tr>
                                @empty
                                    <tr><td colspan="6" class="text-center py-8 text-gray-500">No lab results found</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                        @break
                        
                    @case('daily_collection')
                        @if(isset($summaryTotals) && !empty($summaryTotals))
                        <div class="px-6 py-3 bg-blue-50 border-b border-blue-200 flex gap-8">
                            <div>
                                <span class="text-xs text-blue-600 uppercase font-bold">Total Transactions</span>
                                <p class="text-lg font-bold text-blue-900">{{ number_format($summaryTotals['total_transactions'] ?? 0) }}</p>
                            </div>
                            <div>
                                <span class="text-xs text-blue-600 uppercase font-bold">Total Amount</span>
                                <p class="text-lg font-bold text-blue-900">₱{{ number_format($summaryTotals['total_amount'] ?? 0, 2) }}</p>
                            </div>
                        </div>
                        @endif
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Date & Time</th>
                                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">OR Number</th>
                                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Patient</th>
                                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Designation</th>
                                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Status</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @forelse($reportData as $txn)
                                    <tr class="hover:bg-gray-50">
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">{{ $txn->datetime_added ? \Carbon\Carbon::parse($txn->datetime_added)->format('M d, Y h:i A') : 'N/A' }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">{{ $txn->or_number ?? 'N/A' }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">{{ $txn->patient->full_name ?? 'N/A' }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">{{ $txn->client_designation ?? 'N/A' }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap"><span class="px-3 py-1 text-xs font-medium rounded-full bg-green-100 text-green-800">{{ ucfirst($txn->status_code ?? 'completed') }}</span></td>
                                    </tr>
                                @empty
                                    <tr><td colspan="5" class="text-center py-8 text-gray-500">No transactions found</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                        @break

                    @case('revenue_by_test')
                        @if(isset($summaryTotals) && !empty($summaryTotals))
                        <div class="px-6 py-3 bg-green-50 border-b border-green-200 flex gap-8">
                            <div>
                                <span class="text-xs text-green-600 uppercase font-bold">Total Orders</span>
                                <p class="text-lg font-bold text-green-900">{{ number_format($summaryTotals['total_orders'] ?? 0) }}</p>
                            </div>
                            <div>
                                <span class="text-xs text-green-600 uppercase font-bold">Grand Revenue</span>
                                <p class="text-lg font-bold text-green-900">₱{{ number_format($summaryTotals['grand_revenue'] ?? 0, 2) }}</p>
                            </div>
                        </div>
                        @endif
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Test Name</th>
                                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Section</th>
                                    <th class="px-6 py-3 text-right text-xs font-semibold text-gray-500 uppercase tracking-wider">Unit Price</th>
                                    <th class="px-6 py-3 text-right text-xs font-semibold text-gray-500 uppercase tracking-wider">Total Orders</th>
                                    <th class="px-6 py-3 text-right text-xs font-semibold text-gray-500 uppercase tracking-wider">Total Revenue</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @forelse($reportData as $row)
                                    <tr class="hover:bg-gray-50">
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">{{ $row->test_name }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">{{ $row->section_name ?? 'N/A' }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600 text-right">₱{{ number_format($row->current_price, 2) }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600 text-right">{{ number_format($row->total_orders) }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-semibold text-green-700 text-right">₱{{ number_format($row->total_revenue, 2) }}</td>
                                    </tr>
                                @empty
                                    <tr><td colspan="5" class="text-center py-8 text-gray-500">No revenue data found</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                        @break

                    @case('test_volume')
                        @if(isset($summaryTotals) && !empty($summaryTotals))
                        <div class="px-6 py-3 bg-purple-50 border-b border-purple-200 flex gap-8">
                            <div>
                                <span class="text-xs text-purple-600 uppercase font-bold">Total Tests</span>
                                <p class="text-lg font-bold text-purple-900">{{ number_format($summaryTotals['total'] ?? 0) }}</p>
                            </div>
                            <div>
                                <span class="text-xs text-purple-600 uppercase font-bold">Finalized</span>
                                <p class="text-lg font-bold text-green-700">{{ number_format($summaryTotals['final_total'] ?? 0) }}</p>
                            </div>
                            <div>
                                <span class="text-xs text-purple-600 uppercase font-bold">Draft</span>
                                <p class="text-lg font-bold text-yellow-700">{{ number_format($summaryTotals['draft_total'] ?? 0) }}</p>
                            </div>
                        </div>
                        @endif
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Test Name</th>
                                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Section</th>
                                    <th class="px-6 py-3 text-right text-xs font-semibold text-gray-500 uppercase tracking-wider">Total</th>
                                    <th class="px-6 py-3 text-right text-xs font-semibold text-gray-500 uppercase tracking-wider">Final</th>
                                    <th class="px-6 py-3 text-right text-xs font-semibold text-gray-500 uppercase tracking-wider">Draft</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @forelse($reportData as $vol)
                                    <tr class="hover:bg-gray-50">
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">{{ $vol->test->label ?? 'N/A' }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">{{ $vol->test->section->label ?? 'N/A' }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600 text-right">{{ number_format($vol->total_count) }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-green-700 text-right">{{ number_format($vol->final_count) }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-yellow-700 text-right">{{ number_format($vol->draft_count) }}</td>
                                    </tr>
                                @empty
                                    <tr><td colspan="5" class="text-center py-8 text-gray-500">No test volume data found</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                        @break

                    @case('issued_certificates')
                        @if(isset($summaryTotals) && !empty($summaryTotals))
                        <div class="px-6 py-3 bg-indigo-50 border-b border-indigo-200 flex gap-8">
                            <div>
                                <span class="text-xs text-indigo-600 uppercase font-bold">Total</span>
                                <p class="text-lg font-bold text-indigo-900">{{ number_format($summaryTotals['total'] ?? 0) }}</p>
                            </div>
                            <div>
                                <span class="text-xs text-indigo-600 uppercase font-bold">Issued</span>
                                <p class="text-lg font-bold text-green-700">{{ number_format($summaryTotals['issued'] ?? 0) }}</p>
                            </div>
                            <div>
                                <span class="text-xs text-indigo-600 uppercase font-bold">Draft</span>
                                <p class="text-lg font-bold text-yellow-700">{{ number_format($summaryTotals['draft'] ?? 0) }}</p>
                            </div>
                            <div>
                                <span class="text-xs text-indigo-600 uppercase font-bold">Revoked</span>
                                <p class="text-lg font-bold text-red-700">{{ number_format($summaryTotals['revoked'] ?? 0) }}</p>
                            </div>
                        </div>
                        @endif
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Certificate No.</th>
                                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Type</th>
                                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Patient</th>
                                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Issued By</th>
                                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Issue Date</th>
                                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Status</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @forelse($reportData as $cert)
                                    <tr class="hover:bg-gray-50">
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">{{ $cert->certificate_number ?? 'N/A' }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">{{ ucfirst($cert->certificate_type ?? 'N/A') }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">{{ $cert->patient->full_name ?? 'N/A' }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">{{ $cert->issuedBy->full_name ?? 'N/A' }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">{{ $cert->issue_date ? \Carbon\Carbon::parse($cert->issue_date)->format('M d, Y') : 'N/A' }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap"><span class="px-3 py-1 text-xs font-medium rounded-full @if($cert->status === 'issued') bg-green-100 text-green-800 @elseif($cert->status === 'draft') bg-yellow-100 text-yellow-800 @else bg-red-100 text-red-800 @endif">{{ ucfirst($cert->status ?? 'draft') }}</span></td>
                                    </tr>
                                @empty
                                    <tr><td colspan="6" class="text-center py-8 text-gray-500">No certificates found</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                        @break

                    @case('activity_log')
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Date & Time</th>
                                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Employee</th>
                                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Description</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @forelse($reportData as $log)
                                    <tr class="hover:bg-gray-50">
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">{{ $log->datetime_added ? \Carbon\Carbon::parse($log->datetime_added)->format('M d, Y h:i A') : 'N/A' }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">{{ $log->employee->full_name ?? 'N/A' }}</td>
                                        <td class="px-6 py-4 text-sm text-gray-600">{{ $log->description ?? 'N/A' }}</td>
                                    </tr>
                                @empty
                                    <tr><td colspan="3" class="text-center py-8 text-gray-500">No activity logs found</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                        @break

                    @case('expiring_inventory')
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Item Name</th>
                                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Section</th>
                                    <th class="px-6 py-3 text-right text-xs font-semibold text-gray-500 uppercase tracking-wider">Quantity</th>
                                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Expiry Date</th>
                                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Days Left</th>
                                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Urgency</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @forelse($reportData as $stock)
                                    @php $daysLeft = \Carbon\Carbon::now()->diffInDays(\Carbon\Carbon::parse($stock->expiry_date), false); @endphp
                                    <tr class="hover:bg-gray-50">
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">{{ $stock->item->label ?? 'N/A' }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">{{ $stock->item->section->label ?? 'N/A' }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600 text-right">{{ $stock->quantity ?? 0 }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">{{ $stock->expiry_date ? \Carbon\Carbon::parse($stock->expiry_date)->format('M d, Y') : 'N/A' }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-semibold {{ $daysLeft <= 30 ? 'text-red-600' : ($daysLeft <= 60 ? 'text-yellow-600' : 'text-gray-600') }}">{{ $daysLeft }} days</td>
                                        <td class="px-6 py-4 whitespace-nowrap"><span class="px-3 py-1 text-xs font-medium rounded-full @if($daysLeft <= 30) bg-red-100 text-red-800 @elseif($daysLeft <= 60) bg-yellow-100 text-yellow-800 @else bg-green-100 text-green-800 @endif">@if($daysLeft <= 30) Critical @elseif($daysLeft <= 60) Warning @else OK @endif</span></td>
                                    </tr>
                                @empty
                                    <tr><td colspan="6" class="text-center py-8 text-gray-500">No expiring inventory found</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                        @break

                    @default
                        <div class="p-12 text-center">
                            <p class="text-gray-500">Report type not implemented yet.</p>
                        </div>
                @endswitch
            </div>

            @if(isset($reportData) && $reportData->hasPages())
                <div class="px-6 py-4 border-t border-gray-200">
                    {{ $reportData->appends(request()->input())->links() }}
                </div>
            @endif
        </div>
    @elseif(request('report_type') && isset($reportData) && $reportData->count() === 0)
        <div class="bg-white rounded-lg shadow border border-gray-200 p-12 text-center">
            <svg class="w-16 h-16 mx-auto mb-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
            </svg>
            <p class="text-gray-500 font-medium mb-2">No data found for the selected criteria</p>
            <p class="text-sm text-gray-400">Try adjusting the date range or removing section filter</p>
        </div>
    @else
        <div class="bg-white rounded-lg shadow border border-gray-200 p-12 text-center">
            <svg class="w-16 h-16 mx-auto mb-4 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
            </svg>
            <p class="text-gray-600 font-medium mb-2">Ready to Generate Reports</p>
            <p class="text-sm text-gray-500">Select a report type, date range, and click "Generate Report" to begin</p>
        </div>
    @endif
</div>

<script>
function exportReport() {
    const reportType = document.getElementById('report_type').value;
    if (!reportType) {
        alert('Please select a report type first.');
        return;
    }
    
    // Create a form for export
    const form = document.createElement('form');
    form.method = 'GET';
    form.action = '{{ route("reports.index") }}';
    
    // Add all current parameters
    const params = new URLSearchParams(window.location.search);
    params.set('export', 'true');
    
    params.forEach((value, key) => {
        const input = document.createElement('input');
        input.type = 'hidden';
        input.name = key;
        input.value = value;
        form.appendChild(input);
    });
    
    document.body.appendChild(form);
    form.submit();
    document.body.removeChild(form);
}

// Show/hide employee filter based on report type
document.getElementById('report_type').addEventListener('change', function() {
    const employeeFilter = document.getElementById('employee-filter');
    if (this.value === 'activity_log') {
        employeeFilter.style.display = '';
    } else {
        employeeFilter.style.display = 'none';
    }
});
</script>
@endsection