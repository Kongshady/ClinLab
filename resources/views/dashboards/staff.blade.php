@php
use App\Models\Patient;
use App\Models\LabResult;
use App\Models\Equipment;
use App\Models\Transaction;
use Illuminate\Support\Facades\Cache;

$stats = Cache::remember('staff_dashboard_stats', 300, function() {
    return [
        'patients_today'     => Patient::where('is_deleted', 0)->whereDate('datetime_added', today())->count(),
        'pending_results'    => LabResult::where('status', 'Pending')->count(),
        'due_soon_equipment' => Equipment::where('is_deleted', 0)->where('status', 'under_maintenance')->count(),
        'total_patients'     => Patient::where('is_deleted', 0)->count(),
    ];
});

$weeklyData = collect(range(6, 0))->map(function($i) {
    $date = now()->subDays($i);
    return [
        'label'     => $date->format('D'),
        'ordered'   => LabResult::whereDate('datetime_added', $date)->count(),
        'completed' => LabResult::whereDate('datetime_added', $date)->where('status', 'Completed')->count(),
    ];
});

$paid   = Transaction::whereNotNull('paid_at')->whereDate('datetime_added', today())->count();
$unpaid = Transaction::whereNull('paid_at')->whereDate('datetime_added', today())->count();

$todaysPatients = Patient::where('is_deleted', 0)->whereDate('datetime_added', today())
    ->orderByDesc('datetime_added')->limit(6)->get();

$equipmentAlerts = Equipment::where('is_deleted', 0)
    ->where('status', 'under_maintenance')
    ->orderBy('datetime_added', 'desc')->limit(4)->get();
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
            <h1 class="text-2xl font-bold text-gray-900">Staff Dashboard</h1>
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
            ['label'=>'Patients Today',    'value'=>$stats['patients_today'],     'sub'=>'Registered today',     'icon'=>'M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z', 'color'=>'red'],
            ['label'=>'Pending Results',   'value'=>$stats['pending_results'],    'sub'=>'Awaiting processing', 'icon'=>'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2', 'color'=>'amber'],
            ['label'=>'Equipment Alerts',  'value'=>$stats['due_soon_equipment'], 'sub'=>'Due within 7 days',   'icon'=>'M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-1.732-1.333-2.5 0L4.268 16c-.77 1.333.192 3 1.732 3z', 'color'=>'orange'],
            ['label'=>'Total Patients',    'value'=>$stats['total_patients'],     'sub'=>'All-time records',    'icon'=>'M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z', 'color'=>'blue'],
        ];
        $delays   = ['0.05s','0.15s','0.25s','0.35s'];
        $colorMap = [
            'red'    => ['bg'=>'bg-red-50',    'icon'=>'text-red-500',    'bar'=>'bg-red-500'],
            'amber'  => ['bg'=>'bg-amber-50',  'icon'=>'text-amber-500',  'bar'=>'bg-amber-500'],
            'orange' => ['bg'=>'bg-orange-50', 'icon'=>'text-orange-500', 'bar'=>'bg-orange-500'],
            'blue'   => ['bg'=>'bg-blue-50',   'icon'=>'text-blue-500',   'bar'=>'bg-blue-500'],
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
            <p class="text-[11px] font-semibold text-gray-400 uppercase tracking-widest mb-1">Lab Activity</p>
            <h2 class="text-sm font-bold text-gray-800 mb-4">Lab Orders  Weekly Overview</h2>
            <canvas id="weeklyChart" height="120"></canvas>
        </div>
        <div class="lg:col-span-2 bg-white rounded-2xl border border-gray-100 shadow-sm p-6 afu" style="animation-delay:0.5s">
            <p class="text-[11px] font-semibold text-gray-400 uppercase tracking-widest mb-1">Today</p>
            <h2 class="text-sm font-bold text-gray-800 mb-4">Payment Status</h2>
            <canvas id="paymentChart" height="160"></canvas>
            <div class="flex items-center justify-center gap-4 mt-4 text-xs text-gray-500">
                <span class="flex items-center gap-1.5"><span class="w-2.5 h-2.5 rounded-full bg-green-500 inline-block"></span>Paid</span>
                <span class="flex items-center gap-1.5"><span class="w-2.5 h-2.5 rounded-full bg-gray-300 inline-block"></span>Unpaid</span>
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
                @can('patients.access')
                <a href="{{ route('patients.index') }}" class="flex items-center gap-3 p-4 rounded-xl border border-gray-100 hover:border-red-200 hover:bg-red-50 transition-all group">
                    <div class="w-9 h-9 rounded-lg bg-red-50 group-hover:bg-red-100 flex items-center justify-center shrink-0 transition-colors">
                        <svg class="w-4 h-4 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                    </div>
                    <div><p class="text-xs font-semibold text-gray-700">Patients</p><p class="text-[10px] text-gray-400">Manage records</p></div>
                </a>
                @endcan
                @can('lab-results.access')
                <a href="{{ route('lab-results.index') }}" class="flex items-center gap-3 p-4 rounded-xl border border-gray-100 hover:border-amber-200 hover:bg-amber-50 transition-all group">
                    <div class="w-9 h-9 rounded-lg bg-amber-50 group-hover:bg-amber-100 flex items-center justify-center shrink-0 transition-colors">
                        <svg class="w-4 h-4 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                    </div>
                    <div><p class="text-xs font-semibold text-gray-700">Lab Results</p><p class="text-[10px] text-gray-400">View & process</p></div>
                </a>
                @endcan
                @can('equipment.access')
                <a href="{{ route('equipment.index') }}" class="flex items-center gap-3 p-4 rounded-xl border border-gray-100 hover:border-orange-200 hover:bg-orange-50 transition-all group">
                    <div class="w-9 h-9 rounded-lg bg-orange-50 group-hover:bg-orange-100 flex items-center justify-center shrink-0 transition-colors">
                        <svg class="w-4 h-4 text-orange-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                    </div>
                    <div><p class="text-xs font-semibold text-gray-700">Equipment</p><p class="text-[10px] text-gray-400">Maintenance logs</p></div>
                </a>
                @endcan
                @can('items.access')
                <a href="{{ route('items.index') }}" class="flex items-center gap-3 p-4 rounded-xl border border-gray-100 hover:border-blue-200 hover:bg-blue-50 transition-all group">
                    <div class="w-9 h-9 rounded-lg bg-blue-50 group-hover:bg-blue-100 flex items-center justify-center shrink-0 transition-colors">
                        <svg class="w-4 h-4 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                    </div>
                    <div><p class="text-xs font-semibold text-gray-700">Inventory</p><p class="text-[10px] text-gray-400">Stock & items</p></div>
                </a>
                @endcan
            </div>
        </div>

        {{-- Today's Patients --}}
        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-6 afu" style="animation-delay:0.6s">
            <p class="text-[11px] font-semibold text-gray-400 uppercase tracking-widest mb-1">Real-time</p>
            <h2 class="text-sm font-bold text-gray-800 mb-4">Today's Patients</h2>
            @if($todaysPatients->isEmpty())
                <div class="flex flex-col items-center justify-center py-10 text-gray-300">
                    <svg class="w-10 h-10 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                    <p class="text-[11px] font-medium">No patients today</p>
                </div>
            @else
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr>
                            <th class="text-left text-[10px] font-semibold text-gray-400 uppercase tracking-widest pb-2">Patient</th>
                            <th class="text-left text-[10px] font-semibold text-gray-400 uppercase tracking-widest pb-2">Time</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50">
                        @foreach($todaysPatients as $p)
                        <tr>
                            <td class="py-2.5 text-xs font-medium text-gray-800">{{ $p->firstname }} {{ $p->lastname }}</td>
                            <td class="py-2.5 text-xs text-gray-400">{{ \Carbon\Carbon::parse($p->datetime_added)->format('g:i A') }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @endif
        </div>
    </div>

    {{-- Equipment Alerts Strip --}}
    @if($equipmentAlerts->isNotEmpty())
    <div class="bg-orange-50 border border-orange-100 rounded-2xl p-5 afu" style="animation-delay:0.65s">
        <div class="flex items-center gap-2 mb-3">
            <svg class="w-4 h-4 text-orange-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-1.732-1.333-2.5 0L4.268 16c-.77 1.333.192 3 1.732 3z"/></svg>
            <p class="text-[11px] font-semibold text-orange-600 uppercase tracking-widest">Equipment Alerts  Maintenance Due</p>
        </div>
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
            @foreach($equipmentAlerts as $eq)
            <div class="bg-white rounded-xl border border-orange-100 px-4 py-3">
                <p class="text-xs font-semibold text-gray-800 truncate">{{ $eq->label ?? $eq->name }}</p>
                <p class="text-[10px] text-orange-500 mt-0.5">Status: Under Maintenance</p>
            </div>
            @endforeach
        </div>
    </div>
    @endif

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
            { label: 'Orders',    data: @json($weeklyData->pluck('ordered')),   backgroundColor: 'rgba(239,68,68,0.8)',  borderRadius: 6 },
            { label: 'Completed', data: @json($weeklyData->pluck('completed')), backgroundColor: 'rgba(34,197,94,0.8)', borderRadius: 6 },
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

new Chart(document.getElementById('paymentChart'), {
    type: 'doughnut',
    data: {
        labels: ['Paid', 'Unpaid'],
        datasets: [{ data: [{{ $paid }}, {{ $unpaid }}], backgroundColor: ['rgba(34,197,94,0.85)', 'rgba(209,213,219,0.85)'], borderWidth: 0, hoverOffset: 6 }]
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
