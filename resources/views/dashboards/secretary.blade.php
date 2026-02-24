@php
use App\Models\Transaction;
use App\Models\StockIn;
use App\Models\StockOut;
use App\Models\Item;
use Illuminate\Support\Facades\Cache;

$stats = Cache::remember('secretary_dashboard_stats', 300, function() {
    return [
        'transactions_today' => Transaction::whereDate('datetime_added', today())->count(),
        'stock_in_today'     => StockIn::whereDate('datetime_added', today())->count(),
        'stock_out_today'    => StockOut::whereDate('datetime_added', today())->count(),
        'total_items'        => Item::where('is_deleted', 0)->count(),
    ];
});

// Weekly transactions  last 7 days
$weeklyData = collect(range(6, 0))->map(function($i) {
    $date = now()->subDays($i);
    return [
        'label'    => $date->format('D'),
        'txn'      => Transaction::whereDate('datetime_added', $date)->count(),
        'paid'     => Transaction::whereDate('datetime_added', $date)->whereNotNull('paid_at')->count(),
    ];
});

// Stock movement donut (today)
$stockInTotal  = StockIn::whereDate('datetime_added', today())->count();
$stockOutTotal = StockOut::whereDate('datetime_added', today())->count();

// Recent items
$recentItems = Item::where('is_deleted', 0)->orderBy('label', 'asc')->limit(8)->get();
@endphp

@extends('layouts.app')

@section('content')
<style>
@keyframes fadeInUp {
    from { opacity: 0; transform: translateY(18px); }
    to   { opacity: 1; transform: translateY(0); }
}
.afu { animation: fadeInUp 0.5s ease both; }
</style>

<div class="min-h-screen bg-gray-50">
<div class="max-w-7xl mx-auto px-6 py-8 space-y-6">

    {{-- Header --}}
    <div class="flex items-start justify-between">
        <div>
            <p class="text-[11px] font-semibold text-gray-400 uppercase tracking-widest mb-1">ClinLab System</p>
            <h1 class="text-2xl font-bold text-gray-900">Secretary Dashboard</h1>
            <p class="text-sm text-gray-500 mt-0.5 flex items-center gap-1.5">
                <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                Welcome back, {{ auth()->user()->name }}
            </p>
        </div>
        <div class="bg-white border border-gray-100 rounded-xl px-4 py-2 text-sm text-gray-500 shadow-sm">
            {{ now()->format('l, F j, Y') }}
        </div>
    </div>

    {{-- Stat Cards --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        @php
        $cards = [
            ['label'=>'Transactions Today', 'value'=>$stats['transactions_today'], 'sub'=>'Processed today',    'icon'=>'M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z', 'color'=>'red'],
            ['label'=>'Stock In Today',     'value'=>$stats['stock_in_today'],     'sub'=>'Items received',     'icon'=>'M3 4h13M3 8h9m-9 4h9m5-4v12m0 0l-4-4m4 4l4-4',                                                                                                                                       'color'=>'blue'],
            ['label'=>'Stock Out Today',    'value'=>$stats['stock_out_today'],    'sub'=>'Items dispatched',   'icon'=>'M3 4h13M3 8h9m-9 4h6m4 0l4-4m0 0l4 4m-4-4v12',                                                                                                                                      'color'=>'purple'],
            ['label'=>'Total Items',        'value'=>$stats['total_items'],        'sub'=>'Active inventory',   'icon'=>'M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4',                                                                                                                   'color'=>'green'],
        ];
        $delays   = ['0.05s','0.15s','0.25s','0.35s'];
        $colorMap = [
            'red'    => ['bg'=>'bg-red-50',    'icon'=>'text-red-500',    'bar'=>'bg-red-500'],
            'blue'   => ['bg'=>'bg-blue-50',   'icon'=>'text-blue-500',   'bar'=>'bg-blue-500'],
            'purple' => ['bg'=>'bg-purple-50', 'icon'=>'text-purple-500', 'bar'=>'bg-purple-500'],
            'green'  => ['bg'=>'bg-green-50',  'icon'=>'text-green-500',  'bar'=>'bg-green-500'],
        ];
        @endphp
        @foreach($cards as $i => $card)
        @php $c = $colorMap[$card['color']]; @endphp
        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-6 afu" style="animation-delay:{{ $delays[$i] }}">
            <div class="flex items-start justify-between">
                <div>
                    <p class="text-[11px] font-semibold text-gray-400 uppercase tracking-widest mb-2">{{ $card['label'] }}</p>
                    <p class="text-3xl font-bold text-gray-900" data-count="{{ $card['value'] }}">0</p>
                    <p class="text-xs text-gray-400 mt-1">{{ $card['sub'] }}</p>
                </div>
                <div class="w-10 h-10 rounded-xl {{ $c['bg'] }} flex items-center justify-center shrink-0">
                    <svg class="w-5 h-5 {{ $c['icon'] }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $card['icon'] }}"/>
                    </svg>
                </div>
            </div>
            <div class="h-1 rounded-full {{ $c['bar'] }} mt-4"></div>
        </div>
        @endforeach
    </div>

    {{-- Charts Row --}}
    <div class="grid grid-cols-1 lg:grid-cols-5 gap-4">
        <div class="lg:col-span-3 bg-white rounded-2xl border border-gray-100 shadow-sm p-6 afu" style="animation-delay:0.4s">
            <p class="text-[11px] font-semibold text-gray-400 uppercase tracking-widest mb-1">Billing</p>
            <h2 class="text-sm font-bold text-gray-800 mb-4">Transactions  Weekly Overview</h2>
            <canvas id="weeklyChart" height="120"></canvas>
        </div>
        <div class="lg:col-span-2 bg-white rounded-2xl border border-gray-100 shadow-sm p-6 afu" style="animation-delay:0.5s">
            <p class="text-[11px] font-semibold text-gray-400 uppercase tracking-widest mb-1">Today</p>
            <h2 class="text-sm font-bold text-gray-800 mb-4">Stock Movement</h2>
            <canvas id="stockChart" height="160"></canvas>
            <div class="flex items-center justify-center gap-4 mt-4 text-xs text-gray-500">
                <span class="flex items-center gap-1.5"><span class="w-2.5 h-2.5 rounded-full bg-blue-500 inline-block"></span>Stock In</span>
                <span class="flex items-center gap-1.5"><span class="w-2.5 h-2.5 rounded-full bg-purple-400 inline-block"></span>Stock Out</span>
            </div>
        </div>
    </div>

    {{-- Bottom Row --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
        {{-- Quick Actions --}}
        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-6 afu" style="animation-delay:0.55s">
            <p class="text-[11px] font-semibold text-gray-400 uppercase tracking-widest mb-1">Navigation</p>
            <h2 class="text-sm font-bold text-gray-800 mb-4">Quick Actions</h2>
            <div class="grid grid-cols-2 gap-3">
                @can('transactions.access')
                <a href="{{ route('transactions.index') }}" class="flex items-center gap-3 p-4 rounded-xl border border-gray-100 hover:border-red-200 hover:bg-red-50 transition-all group">
                    <div class="w-9 h-9 rounded-lg bg-red-50 group-hover:bg-red-100 flex items-center justify-center shrink-0 transition-colors">
                        <svg class="w-4 h-4 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                    </div>
                    <div><p class="text-xs font-semibold text-gray-700">Transactions</p><p class="text-[10px] text-gray-400">Billing & payments</p></div>
                </a>
                @endcan
                @can('stockin.access')
                <a href="{{ route('stockin.index') }}" class="flex items-center gap-3 p-4 rounded-xl border border-gray-100 hover:border-blue-200 hover:bg-blue-50 transition-all group">
                    <div class="w-9 h-9 rounded-lg bg-blue-50 group-hover:bg-blue-100 flex items-center justify-center shrink-0 transition-colors">
                        <svg class="w-4 h-4 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                    </div>
                    <div><p class="text-xs font-semibold text-gray-700">Stock In</p><p class="text-[10px] text-gray-400">Receive items</p></div>
                </a>
                @endcan
                @can('stockout.access')
                <a href="{{ route('stockout.index') }}" class="flex items-center gap-3 p-4 rounded-xl border border-gray-100 hover:border-purple-200 hover:bg-purple-50 transition-all group">
                    <div class="w-9 h-9 rounded-lg bg-purple-50 group-hover:bg-purple-100 flex items-center justify-center shrink-0 transition-colors">
                        <svg class="w-4 h-4 text-purple-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 12H4"/></svg>
                    </div>
                    <div><p class="text-xs font-semibold text-gray-700">Stock Out</p><p class="text-[10px] text-gray-400">Dispatch items</p></div>
                </a>
                @endcan
                @can('items.access')
                <a href="{{ route('items.index') }}" class="flex items-center gap-3 p-4 rounded-xl border border-gray-100 hover:border-green-200 hover:bg-green-50 transition-all group">
                    <div class="w-9 h-9 rounded-lg bg-green-50 group-hover:bg-green-100 flex items-center justify-center shrink-0 transition-colors">
                        <svg class="w-4 h-4 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                    </div>
                    <div><p class="text-xs font-semibold text-gray-700">All Items</p><p class="text-[10px] text-gray-400">Browse catalog</p></div>
                </a>
                @endcan
            </div>
        </div>

        {{-- Recent Items Table --}}
        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-6 afu" style="animation-delay:0.6s">
            <div class="flex items-center justify-between mb-4">
                <div>
                    <p class="text-[11px] font-semibold text-gray-400 uppercase tracking-widest mb-1">Inventory</p>
                    <h2 class="text-sm font-bold text-gray-800">Item Catalog</h2>
                </div>
                @can('items.access')
                <a href="{{ route('items.index') }}" class="text-[11px] font-semibold text-red-500 hover:text-red-600 uppercase tracking-wider">View All </a>
                @endcan
            </div>
            @if($recentItems->isEmpty())
                <div class="flex flex-col items-center justify-center py-10 text-gray-300">
                    <svg class="w-10 h-10 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                    <p class="text-[11px] font-medium">No items found</p>
                </div>
            @else
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-gray-100">
                            <th class="text-left text-[10px] font-semibold text-gray-400 uppercase tracking-widest pb-2">Item</th>
                            <th class="text-left text-[10px] font-semibold text-gray-400 uppercase tracking-widest pb-2">Unit</th>
                            <th class="text-right text-[10px] font-semibold text-gray-400 uppercase tracking-widest pb-2">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50">
                        @foreach($recentItems as $item)
                        <tr>
                            <td class="py-2.5 text-xs font-medium text-gray-800">{{ $item->label }}</td>
                            <td class="py-2.5 text-xs text-gray-400">{{ $item->unit }}</td>
                            <td class="py-2.5 text-right">
                                <span class="inline-flex px-2 py-0.5 text-[10px] font-semibold rounded-full bg-green-100 text-green-700">Active</span>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @endif
        </div>
    </div>

</div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
document.querySelectorAll('[data-count]').forEach(el => {
    const target = parseInt(el.dataset.count) || 0;
    if (target === 0) { el.textContent = '0'; return; }
    let current = 0;
    const step = Math.max(1, Math.ceil(target / 40));
    const timer = setInterval(() => {
        current = Math.min(current + step, target);
        el.textContent = current.toLocaleString();
        if (current >= target) clearInterval(timer);
    }, 30);
});

new Chart(document.getElementById('weeklyChart'), {
    type: 'bar',
    data: {
        labels: @json($weeklyData->pluck('label')),
        datasets: [
            { label: 'Total',  data: @json($weeklyData->pluck('txn')),  backgroundColor: 'rgba(239,68,68,0.8)',  borderRadius: 6 },
            { label: 'Paid',   data: @json($weeklyData->pluck('paid')), backgroundColor: 'rgba(34,197,94,0.8)', borderRadius: 6 },
        ]
    },
    options: {
        responsive: true,
        plugins: { legend: { labels: { font: { size: 11 }, boxWidth: 12 } } },
        scales: {
            x: { grid: { display: false }, ticks: { font: { size: 11 } } },
            y: { beginAtZero: true, grid: { color: '#f3f4f6' }, ticks: { font: { size: 11 }, stepSize: 1 } }
        },
        animation: { duration: 800, easing: 'easeOutQuart' }
    }
});

new Chart(document.getElementById('stockChart'), {
    type: 'doughnut',
    data: {
        labels: ['Stock In', 'Stock Out'],
        datasets: [{
            data: [{{ $stockInTotal }}, {{ $stockOutTotal }}],
            backgroundColor: ['rgba(59,130,246,0.85)', 'rgba(168,85,247,0.85)'],
            borderWidth: 0, hoverOffset: 6
        }]
    },
    options: {
        responsive: true,
        cutout: '68%',
        plugins: { legend: { display: false } },
        animation: { animateRotate: true, duration: 900 }
    }
});
</script>
@endsection
