<table class="min-w-full divide-y divide-gray-200">
    <thead class="bg-gray-50">
        <tr>
            <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Equipment</th>
            <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Cal. Date</th>
            <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Calibrated By</th>
            <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Result</th>
            <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Next Cal. Date</th>
            <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Notes</th>
        </tr>
    </thead>
    <tbody class="bg-white divide-y divide-gray-200">
        @forelse($data as $record)
            <tr class="hover:bg-gray-50">
                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                    {{ $record->equipment->name ?? 'N/A' }}
                    @if($record->equipment)
                        <div class="text-xs text-gray-500">{{ $record->equipment->serial_no ?? '' }}</div>
                    @endif
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                    {{ $record->calibration_date ? \Carbon\Carbon::parse($record->calibration_date)->format('M d, Y') : 'N/A' }}
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                    {{ $record->calibratedBy->full_name ?? 'N/A' }}
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm">
                    <span class="px-2 py-1 text-xs rounded-full font-medium
                        {{ $record->result_status == 'Pass' ? 'bg-green-100 text-green-800' : '' }}
                        {{ $record->result_status == 'Fail' ? 'bg-red-100 text-red-800' : '' }}
                        {{ $record->result_status == 'Conditional' ? 'bg-yellow-100 text-yellow-800' : '' }}">
                        {{ $record->result_status ?? 'N/A' }}
                    </span>
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                    {{ $record->next_calibration_date ? \Carbon\Carbon::parse($record->next_calibration_date)->format('M d, Y') : 'N/A' }}
                </td>
                <td class="px-6 py-4 text-sm text-gray-600 max-w-xs truncate">
                    {{ $record->notes ?? '-' }}
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="6" class="px-6 py-12 text-center">
                    <svg class="w-16 h-16 mx-auto mb-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                    </svg>
                    <p class="text-gray-500 font-medium">No calibration records found for the selected period</p>
                </td>
            </tr>
        @endforelse
    </tbody>
</table>
