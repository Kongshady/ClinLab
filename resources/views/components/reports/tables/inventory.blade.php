<table class="w-full">
    <thead class="bg-gray-50 border-b border-gray-200">
        <tr>
            <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Date</th>
            <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Type</th>
            <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Item</th>
            <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Section</th>
            <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Qty In</th>
            <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Qty Out</th>
            <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Unit</th>
            <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Performed By</th>
            <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Purpose</th>
            <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Reference</th>
        </tr>
    </thead>
    <tbody class="divide-y divide-gray-200">
        @forelse($reportData as $record)
            <tr class="hover:bg-gray-50">
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                    {{ $record->transaction_date ? \Carbon\Carbon::parse($record->transaction_date)->format('M d, Y') : 'N/A' }}
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm">
                    <span class="px-2 py-1 text-xs rounded-full font-medium
                        {{ $record->transaction_type == 'Stock In' ? 'bg-green-100 text-green-800' : '' }}
                        {{ $record->transaction_type == 'Stock Out' ? 'bg-red-100 text-red-800' : '' }}
                        {{ $record->transaction_type == 'Usage' ? 'bg-blue-100 text-blue-800' : '' }}">
                        {{ $record->transaction_type }}
                    </span>
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">{{ $record->item_name }}</td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">{{ $record->section ?? 'N/A' }}</td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-green-600 font-medium">{{ $record->qty_in > 0 ? '+' . $record->qty_in : '-' }}</td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-red-600 font-medium">{{ $record->qty_out > 0 ? '-' . $record->qty_out : '-' }}</td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">{{ $record->unit }}</td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">{{ $record->performed_by ?? '-' }}</td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">{{ $record->purpose ?? '-' }}</td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">{{ $record->reference ?? '-' }}</td>
            </tr>
        @empty
            <tr>
                <td colspan="10" class="px-6 py-12 text-center">
                    <svg class="w-16 h-16 mx-auto mb-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                    </svg>
                    <p class="text-gray-500 font-medium">No inventory movements found</p>
                    <p class="text-gray-400 text-sm mt-1">Try adjusting your date range</p>
                </td>
            </tr>
        @endforelse
    </tbody>
</table>
