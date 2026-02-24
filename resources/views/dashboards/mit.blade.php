@php
use App\Models\Test;
use App\Models\Section;
use App\Models\Employee;
use App\Models\Equipment;
use Illuminate\Support\Facades\Cache;

$stats = Cache::remember('mit_dashboard_stats', 300, function() {
    return [
        'total_tests'     => Test::where('is_deleted', 0)->count(),
        'total_sections'  => Section::where('is_deleted', 0)->count(),
        'total_employees' => Employee::where('is_deleted', 0)->count(),
        'total_equipment' => Equipment::where('is_deleted', 0)->count(),
    ];
});

// Tests per section (top 7)
$testsBySection = Section::where('is_deleted', 0)->withCount(['tests' => fn($q) => $q->where('is_deleted', 0)])->orderByDesc('tests_count')->limit(7)->get();

// Equipment status donut
$equipOk      = Equipment::where('is_deleted', 0)->where('status', 'operational')->count();
$equipWarning = Equipment::where('is_deleted', 0)->where('status', 'under_maintenance')->count();
$equipOverdue = Equipment::where('is_deleted', 0)->where('status', 'decommissioned')->count();

// Recent Tests & Employees
$recentTests     = Test::where('is_deleted', 0)->orderByDesc('test_id')->limit(5)->get();
$recentEmployees = Employee::where('is_deleted', 0)->orderByDesc('employee_id')->limit(5)->get();

// Equipment alerts
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
            <h1 class="text-2xl font-bold text-gray-900">MIT Staff Dashboard</h1>
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
            ['label'=>'Total Tests',     'value'=>$stats['total_tests'],     'sub'=>'Active test catalog',   'icon'=>'M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z', 'color'=>'red'],
            ['label'=>'Total Sections',  'value'=>$stats['total_sections'],  'sub'=>'Lab sections',          'icon'=>'M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z', 'color'=>'blue'],
            ['label'=>'Total Employees', 'value'=>$stats['total_employees'], 'sub'=>'Lab personnel',         'icon'=>'M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z', 'color'=>'purple'],
            ['label'=>'Total Equipment', 'value'=>$stats['total_equipment'], 'sub'=>'Registered equipment',  'icon'=>'M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065zM15 12a3 3 0 11-6 0 3 3 0 016 0z', 'color'=>'green'],
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
            <p class="text-[11px] font-semibold text-gray-400 uppercase tracking-widest mb-1">Catalog</p>
            <h2 class="text-sm font-bold text-gray-800 mb-4">Tests by Section</h2>
            <canvas id="sectionChart" height="120"></canvas>
        </div>
        <div class="lg:col-span-2 bg-white rounded-2xl border border-gray-100 shadow-sm p-6 afu" style="animation-delay:0.5s">
            <p class="text-[11px] font-semibold text-gray-400 uppercase tracking-widest mb-1">Equipment</p>
            <h2 class="text-sm font-bold text-gray-800 mb-4">Equipment Status</h2>
            <canvas id="equipChart" height="160"></canvas>
            <div class="flex items-center justify-center gap-4 mt-4 text-xs text-gray-500 flex-wrap">
                <span class="flex items-center gap-1.5"><span class="w-2.5 h-2.5 rounded-full bg-green-500 inline-block"></span>Operational</span>
                <span class="flex items-center gap-1.5"><span class="w-2.5 h-2.5 rounded-full bg-amber-400 inline-block"></span>Maintenance</span>
                <span class="flex items-center gap-1.5"><span class="w-2.5 h-2.5 rounded-full bg-red-400 inline-block"></span>Decommissioned</span>
            </div>
        </div>
    </div>

    {{-- Quick Actions --}}
    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-6 afu" style="animation-delay:0.55s">
        <p class="text-[11px] font-semibold text-gray-400 uppercase tracking-widest mb-1">Navigation</p>
        <h2 class="text-sm font-bold text-gray-800 mb-4">Quick Actions</h2>
        <div class="grid grid-cols-2 sm:grid-cols-4 lg:grid-cols-7 gap-3">
            @php
            $actions = [
                ['can'=>'tests.access',       'route'=>'tests.index',       'label'=>'Tests',       'sub'=>'Test catalog',    'color'=>'red',    'icon'=>'M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z'],
                ['can'=>'sections.access',    'route'=>'sections.index',    'label'=>'Sections',    'sub'=>'Lab sections',    'color'=>'blue',   'icon'=>'M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z'],
                ['can'=>'employees.access',   'route'=>'employees.index',   'label'=>'Employees',   'sub'=>'Personnel',       'color'=>'purple', 'icon'=>'M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z'],
                ['can'=>'equipment.access',   'route'=>'equipment.index',   'label'=>'Equipment',   'sub'=>'Devices',         'color'=>'green',  'icon'=>'M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065zM15 12a3 3 0 11-6 0 3 3 0 016 0z'],
                ['can'=>'physicians.access',  'route'=>'physicians.index',  'label'=>'Physicians',  'sub'=>'Doctors',         'color'=>'amber',  'icon'=>'M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z'],
                ['can'=>'permissions.access', 'route'=>'permissions.index', 'label'=>'Permissions', 'sub'=>'Access control',  'color'=>'orange', 'icon'=>'M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z'],
                ['can'=>'roles.access',       'route'=>'roles.index',       'label'=>'Roles',       'sub'=>'User roles',      'color'=>'teal',   'icon'=>'M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z'],
            ];
            $colorMap2 = [
                'red'    => ['bg'=>'bg-red-50',    'icon'=>'text-red-500',    'hover'=>'hover:border-red-200 hover:bg-red-50'],
                'blue'   => ['bg'=>'bg-blue-50',   'icon'=>'text-blue-500',   'hover'=>'hover:border-blue-200 hover:bg-blue-50'],
                'purple' => ['bg'=>'bg-purple-50', 'icon'=>'text-purple-500', 'hover'=>'hover:border-purple-200 hover:bg-purple-50'],
                'green'  => ['bg'=>'bg-green-50',  'icon'=>'text-green-500',  'hover'=>'hover:border-green-200 hover:bg-green-50'],
                'amber'  => ['bg'=>'bg-amber-50',  'icon'=>'text-amber-500',  'hover'=>'hover:border-amber-200 hover:bg-amber-50'],
                'orange' => ['bg'=>'bg-orange-50', 'icon'=>'text-orange-500', 'hover'=>'hover:border-orange-200 hover:bg-orange-50'],
                'teal'   => ['bg'=>'bg-teal-50',   'icon'=>'text-teal-500',   'hover'=>'hover:border-teal-200 hover:bg-teal-50'],
            ];
            @endphp
            @foreach($actions as $action)
            @php $c2 = $colorMap2[$action['color']]; @endphp
            @can($action['can'])
            <a href="{{ route($action['route']) }}" class="flex flex-col items-center gap-2 p-4 rounded-xl border border-gray-100 {{ $c2['hover'] }} transition-all text-center group">
                <div class="w-9 h-9 rounded-lg {{ $c2['bg'] }} flex items-center justify-center shrink-0 transition-colors">
                    <svg class="w-4 h-4 {{ $c2['icon'] }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $action['icon'] }}"/></svg>
                </div>
                <div><p class="text-xs font-semibold text-gray-700">{{ $action['label'] }}</p><p class="text-[10px] text-gray-400">{{ $action['sub'] }}</p></div>
            </a>
            @endcan
            @endforeach
        </div>
    </div>

    {{-- System Overview --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
        {{-- Recent Tests --}}
        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-6 afu" style="animation-delay:0.6s">
            <p class="text-[11px] font-semibold text-gray-400 uppercase tracking-widest mb-1">Catalog</p>
            <h2 class="text-sm font-bold text-gray-800 mb-4">Recent Tests</h2>
            <div class="space-y-2">
                @forelse($recentTests as $test)
                <div class="flex items-center justify-between py-2 border-b border-gray-50 last:border-0">
                    <div class="flex items-center gap-3">
                        <div class="w-7 h-7 rounded-lg bg-red-50 flex items-center justify-center shrink-0">
                            <svg class="w-3.5 h-3.5 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z"/></svg>
                        </div>
                        <p class="text-xs font-medium text-gray-800">{{ $test->label }}</p>
                    </div>
                    <p class="text-[10px] text-gray-400">₱{{ number_format($test->current_price, 2) }}</p>
                </div>
                @empty
                <p class="text-xs text-gray-400 py-4 text-center">No tests found</p>
                @endforelse
            </div>
        </div>

        {{-- Recent Employees --}}
        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-6 afu" style="animation-delay:0.65s">
            <p class="text-[11px] font-semibold text-gray-400 uppercase tracking-widest mb-1">Personnel</p>
            <h2 class="text-sm font-bold text-gray-800 mb-4">Recent Employees</h2>
            <div class="space-y-2">
                @forelse($recentEmployees as $emp)
                <div class="flex items-center gap-3 py-2 border-b border-gray-50 last:border-0">
                    <div class="w-7 h-7 rounded-full bg-purple-50 flex items-center justify-center shrink-0">
                        <span class="text-[10px] font-bold text-purple-500">{{ strtoupper(substr($emp->firstname, 0, 1)) }}</span>
                    </div>
                    <div class="min-w-0">
                        <p class="text-xs font-medium text-gray-800 truncate">{{ $emp->firstname }} {{ $emp->lastname }}</p>
                        <p class="text-[10px] text-gray-400">{{ $emp->position ?? 'Employee' }}</p>
                    </div>
                </div>
                @empty
                <p class="text-xs text-gray-400 py-4 text-center">No employees found</p>
                @endforelse
            </div>
        </div>
    </div>

    {{-- Equipment Alerts Strip --}}
    @if($equipmentAlerts->isNotEmpty())
    <div class="bg-orange-50 border border-orange-100 rounded-2xl p-5 afu" style="animation-delay:0.7s">
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

new Chart(document.getElementById('sectionChart'), {
    type: 'bar',
    data: {
        labels: @json($testsBySection->pluck('label')),
        datasets: [{ label: 'Tests', data: @json($testsBySection->pluck('tests_count')), backgroundColor: 'rgba(239,68,68,0.8)', borderRadius: 6 }]
    },
    options: {
        indexAxis: 'y',
        responsive: true,
        plugins: { legend: { display: false } },
        scales: {
            x: { beginAtZero: true, grid: { color: '#f3f4f6' }, ticks: { font: { size: 10 }, stepSize: 1 } },
            y: { grid: { display: false }, ticks: { font: { size: 10 } } }
        },
        animation: { duration: 800, easing: 'easeOutQuart' }
    }
});

new Chart(document.getElementById('equipChart'), {
    type: 'doughnut',
    data: {
        labels: ['Operational', 'Under Maintenance', 'Decommissioned'],
        datasets: [{
            data: [{{ $equipOk }}, {{ $equipWarning }}, {{ $equipOverdue }}],
            backgroundColor: ['rgba(34,197,94,0.85)', 'rgba(251,191,36,0.85)', 'rgba(239,68,68,0.85)'],
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
