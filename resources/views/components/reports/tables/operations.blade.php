<table class="w-full">
    <thead class="bg-gray-50 border-b border-gray-200">
        <tr>
            <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">OR Number</th>
            <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Client/Patient</th>
            <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Transaction Date</th>
        </tr>
    </thead>
    <tbody class="divide-y divide-gray-200">
        @forelse($reportData as $record)
            <tr class="hover:bg-gray-50">
                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-blue-600">{{ $record->or_number }}</td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ $record->client_name ?? 'N/A' }}</td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                    {{ $record->transaction_date ? \Carbon\Carbon::parse($record->transaction_date)->format('M d, Y h:i A') : 'N/A' }}
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="3" class="px-6 py-12 text-center">
                    <svg class="w-16 h-16 mx-auto mb-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                    </svg>
                    <p class="text-gray-500 font-medium">No transactions found</p>
                    <p class="text-gray-400 text-sm mt-1">Try adjusting your date range</p>
                </td>
            </tr>
        @endforelse
    </tbody>
</table>
