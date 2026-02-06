<?php

use Livewire\Volt\Component;
use Livewire\WithPagination;
use App\Models\CertificateIssue;
use Barryvdh\DomPDF\Facade\Pdf;

new class extends Component
{
    use WithPagination;

    // Search and filters
    public $search = '';
    public $filterStatus = '';
    public $filterType = '';
    public $dateFrom = '';
    public $dateTo = '';
    public $perPage = 25;

    public $flashMessage = '';

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function updatingFilterStatus()
    {
        $this->resetPage();
    }

    public function updatingFilterType()
    {
        $this->resetPage();
    }

    public function downloadPdf($id)
    {
        $certificate = CertificateIssue::with(['template', 'equipment', 'calibration', 'generator'])->findOrFail($id);
        
        // Generate placeholders data
        $data = $this->prepareCertificateData($certificate);
        
        // Replace placeholders in template
        $html = $certificate->template->body_html;
        foreach ($data as $key => $value) {
            $html = str_replace('{{' . $key . '}}', $value, $html);
        }

        // Generate PDF
        $pdf = Pdf::loadHTML($html)
            ->setPaper('a4', 'portrait');

        return response()->streamDownload(function() use ($pdf) {
            echo $pdf->output();
        }, $certificate->certificate_no . '.pdf');
    }

    public function revoke($id)
    {
        $certificate = CertificateIssue::findOrFail($id);
        $certificate->update(['status' => 'Revoked']);
        $this->flashMessage = 'Certificate revoked successfully!';
    }

    private function prepareCertificateData($certificate)
    {
        $data = [
            'certificate_no' => $certificate->certificate_no,
            'verification_code' => $certificate->verification_code,
            'issue_date' => $certificate->issued_at->format('F d, Y'),
            'valid_until' => $certificate->valid_until ? $certificate->valid_until->format('F d, Y') : 'N/A',
        ];

        // Equipment data
        if ($certificate->equipment) {
            $data['equipment_name'] = $certificate->equipment->name ?? 'N/A';
            $data['equipment_model'] = $certificate->equipment->model ?? 'N/A';
            $data['serial_no'] = $certificate->equipment->serial_number ?? 'N/A';
        }

        // Calibration data
        if ($certificate->calibration) {
            $data['calibration_date'] = $certificate->calibration->calibration_date ?? 'N/A';
            $data['due_date'] = $certificate->calibration->next_calibration_date ?? 'N/A';
            $data['result'] = $certificate->calibration->result ?? 'PASSED';
            $data['performed_by'] = $certificate->calibration->performed_by ?? 'N/A';
        }

        return $data;
    }

    public function with(): array
    {
        $query = CertificateIssue::with(['template', 'equipment', 'generator'])
            ->when($this->search, function($q) {
                $q->where('certificate_no', 'like', '%' . $this->search . '%')
                  ->orWhere('verification_code', 'like', '%' . $this->search . '%');
            })
            ->when($this->filterStatus, function($q) {
                $q->where('status', $this->filterStatus);
            })
            ->when($this->filterType, function($q) {
                $q->whereHas('template', function($query) {
                    $query->where('type', $this->filterType);
                });
            })
            ->when($this->dateFrom, function($q) {
                $q->where('issued_at', '>=', $this->dateFrom);
            })
            ->when($this->dateTo, function($q) {
                $q->where('issued_at', '<=', $this->dateTo . ' 23:59:59');
            })
            ->orderBy('issued_at', 'desc');

        return [
            'certificates' => $query->paginate($this->perPage),
            'stats' => [
                'total' => CertificateIssue::count(),
                'issued' => CertificateIssue::where('status', 'Issued')->count(),
                'revoked' => CertificateIssue::where('status', 'Revoked')->count(),
                'expired' => CertificateIssue::where('status', 'Expired')->count(),
            ],
        ];
    }
}; ?>

<div class="min-h-screen bg-gradient-to-br from-slate-50 via-blue-50 to-indigo-50 p-6">
    <div class="max-w-7xl mx-auto">
        <!-- Header -->
        <div class="mb-6">
            <h1 class="text-3xl font-bold bg-gradient-to-r from-blue-600 to-indigo-600 bg-clip-text text-transparent">
                Issued Certificates
            </h1>
            <p class="text-slate-600 mt-1">View, download, and manage all issued certificates</p>
        </div>

        @if($flashMessage)
            <div class="mb-4 p-4 bg-emerald-50 border border-emerald-200 text-emerald-700 rounded-xl">
                {{ $flashMessage }}
            </div>
        @endif

        <!-- Stats Cards -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
            <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-4">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-slate-600">Total</p>
                        <p class="text-2xl font-bold text-slate-900">{{ $stats['total'] }}</p>
                    </div>
                    <div class="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center">
                        <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                        </svg>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-4">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-slate-600">Issued</p>
                        <p class="text-2xl font-bold text-emerald-600">{{ $stats['issued'] }}</p>
                    </div>
                    <div class="w-12 h-12 bg-emerald-100 rounded-lg flex items-center justify-center">
                        <svg class="w-6 h-6 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-4">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-slate-600">Revoked</p>
                        <p class="text-2xl font-bold text-rose-600">{{ $stats['revoked'] }}</p>
                    </div>
                    <div class="w-12 h-12 bg-rose-100 rounded-lg flex items-center justify-center">
                        <svg class="w-6 h-6 text-rose-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"></path>
                        </svg>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-4">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-slate-600">Expired</p>
                        <p class="text-2xl font-bold text-amber-600">{{ $stats['expired'] }}</p>
                    </div>
                    <div class="w-12 h-12 bg-amber-100 rounded-lg flex items-center justify-center">
                        <svg class="w-6 h-6 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filters -->
        <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-4 mb-6">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-3">
                <input 
                    type="text" 
                    wire:model.live="search" 
                    placeholder="Search certificate no. or code..."
                    class="px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                >
                <select wire:model.live="filterStatus" class="px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                    <option value="">All Status</option>
                    <option value="Issued">Issued</option>
                    <option value="Revoked">Revoked</option>
                    <option value="Expired">Expired</option>
                </select>
                <select wire:model.live="filterType" class="px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                    <option value="">All Types</option>
                    <option value="calibration">Calibration</option>
                    <option value="maintenance">Maintenance</option>
                    <option value="safety">Safety</option>
                    <option value="test">Test</option>
                </select>
                <input 
                    type="date" 
                    wire:model.live="dateFrom"
                    class="px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-blue-500"
                    placeholder="From"
                >
                <input 
                    type="date" 
                    wire:model.live="dateTo"
                    class="px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-blue-500"
                    placeholder="To"
                >
            </div>
        </div>

        <!-- Certificates Table -->
        <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gradient-to-r from-slate-50 to-slate-100">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-slate-700 uppercase tracking-wide">Certificate No</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-slate-700 uppercase tracking-wide">Type</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-slate-700 uppercase tracking-wide">Equipment</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-slate-700 uppercase tracking-wide">Issued Date</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-slate-700 uppercase tracking-wide">Status</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-slate-700 uppercase tracking-wide">Generated By</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-slate-700 uppercase tracking-wide">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200">
                        @forelse($certificates as $certificate)
                            <tr class="hover:bg-slate-50 transition-colors">
                                <td class="px-4 py-3 text-sm font-mono text-slate-900">{{ $certificate->certificate_no }}</td>
                                <td class="px-4 py-3 text-sm">
                                    <span class="px-2 py-1 rounded-full text-xs font-medium
                                        {{ $certificate->template->type === 'calibration' ? 'bg-blue-100 text-blue-700' : '' }}
                                        {{ $certificate->template->type === 'maintenance' ? 'bg-emerald-100 text-emerald-700' : '' }}
                                        {{ $certificate->template->type === 'safety' ? 'bg-amber-100 text-amber-700' : '' }}
                                        {{ $certificate->template->type === 'test' ? 'bg-purple-100 text-purple-700' : '' }}
                                    ">
                                        {{ ucfirst($certificate->template->type) }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-sm text-slate-600">{{ $certificate->equipment->name ?? 'N/A' }}</td>
                                <td class="px-4 py-3 text-sm text-slate-600">{{ $certificate->issued_at->format('M d, Y') }}</td>
                                <td class="px-4 py-3 text-sm">
                                    @if($certificate->status === 'Issued')
                                        <span class="px-2 py-1 bg-emerald-100 text-emerald-700 rounded-full text-xs font-medium">Issued</span>
                                    @elseif($certificate->status === 'Revoked')
                                        <span class="px-2 py-1 bg-rose-100 text-rose-700 rounded-full text-xs font-medium">Revoked</span>
                                    @else
                                        <span class="px-2 py-1 bg-amber-100 text-amber-700 rounded-full text-xs font-medium">Expired</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-sm text-slate-600">{{ $certificate->generator->name ?? 'N/A' }}</td>
                                <td class="px-4 py-3 text-sm">
                                    <div class="flex gap-2">
                                        <button 
                                            wire:click="downloadPdf({{ $certificate->id }})"
                                            class="px-3 py-1 bg-blue-50 text-blue-600 rounded hover:bg-blue-100 transition-colors"
                                        >
                                            Download
                                        </button>
                                        @if($certificate->status === 'Issued')
                                            <button 
                                                wire:click="revoke({{ $certificate->id }})"
                                                wire:confirm="Are you sure you want to revoke this certificate?"
                                                class="px-3 py-1 bg-rose-50 text-rose-600 rounded hover:bg-rose-100 transition-colors"
                                            >
                                                Revoke
                                            </button>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-4 py-8 text-center text-slate-500">
                                    <div class="flex flex-col items-center">
                                        <svg class="w-12 h-12 text-slate-300 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                        </svg>
                                        <p>No certificates found</p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="px-4 py-3 border-t border-slate-200">
                {{ $certificates->links() }}
            </div>
        </div>
    </div>
</div>
