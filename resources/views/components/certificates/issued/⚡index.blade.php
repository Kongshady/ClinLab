<?php

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\CertificateIssue;
use App\Models\Certificate;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Traits\LogsActivity;
use Illuminate\Pagination\LengthAwarePaginator;

new class extends Component
{
    use WithPagination, LogsActivity;

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
        $certificate = CertificateIssue::with(['template', 'equipment', 'calibration.performedBy', 'generator'])->findOrFail($id);

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

    public function revoke($id, $source = 'generated')
    {
        if ($source === 'legacy') {
            $certificate = Certificate::findOrFail($id);
            $certificate->update(['status' => 'revoked']);
            $this->logActivity("Revoked certificate ID {$id}: {$certificate->certificate_number}");
        } else {
            $certificate = CertificateIssue::findOrFail($id);
            $certificate->update(['status' => 'Revoked']);
            $this->logActivity("Revoked certificate ID {$id}: {$certificate->certificate_no}");
        }
        $this->flashMessage = 'Certificate revoked successfully!';
    }

    public function approve($id)
    {
        $certificate = CertificateIssue::findOrFail($id);
        if ($certificate->status !== 'Pending') {
            $this->flashMessage = 'Only pending certificates can be approved.';
            return;
        }
        $certificate->update([
            'status' => 'Issued',
            'approved_by' => auth()->id(),
            'approved_at' => now(),
        ]);
        $this->logActivity("Approved certificate ID {$id}: {$certificate->certificate_no}");
        $this->flashMessage = 'Certificate approved successfully!';
    }

    public function reject($id)
    {
        $certificate = CertificateIssue::findOrFail($id);
        if ($certificate->status !== 'Pending') {
            $this->flashMessage = 'Only pending certificates can be rejected.';
            return;
        }
        $certificate->update(['status' => 'Revoked']);
        $this->logActivity("Rejected certificate ID {$id}: {$certificate->certificate_no}");
        $this->flashMessage = 'Certificate rejected.';
    }

    public function markIssued($id)
    {
        $certificate = Certificate::findOrFail($id);
        if ($certificate->status !== 'draft') {
            $this->flashMessage = 'Only draft certificates can be marked as issued.';
            return;
        }
        $certificate->update(['status' => 'issued']);
        $this->logActivity("Marked certificate ID {$id} as issued: {$certificate->certificate_number}");
        $this->flashMessage = 'Certificate marked as issued!';
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
            $data['serial_no'] = $certificate->equipment->serial_no ?? 'N/A';
        }

        // Calibration data
        if ($certificate->calibration) {
            $data['calibration_date'] = $certificate->calibration->calibration_date ? date('F d, Y', strtotime($certificate->calibration->calibration_date)) : 'N/A';
            $data['due_date'] = $certificate->calibration->next_calibration_date ? date('F d, Y', strtotime($certificate->calibration->next_calibration_date)) : 'N/A';
            $data['result'] = strtoupper($certificate->calibration->result_status ?? 'PASSED');
            $data['performed_by'] = $certificate->calibration->performedBy ? ($certificate->calibration->performedBy->firstname . ' ' . $certificate->calibration->performedBy->lastname) : 'N/A';
        }

        return $data;
    }

    /**
     * Normalize status: legacy uses lowercase (draft, issued, revoked),
     * generated uses capitalized (Pending, Issued, Revoked, Expired).
     * We normalize everything to capitalized form for display.
     */
    private function normalizeStatus($status)
    {
        return ucfirst(strtolower($status));
    }

    public function with(): array
    {
        $search = $this->search;
        $filterStatus = $this->filterStatus;
        $filterType = $this->filterType;
        $dateFrom = $this->dateFrom;
        $dateTo = $this->dateTo;

        // --- Auto-generated certificates (certificate_issues table) ---
        $generatedQuery = CertificateIssue::with(['template', 'equipment', 'generator'])
            ->when($search, function($q) use ($search) {
                $q->where('certificate_no', 'like', "%{$search}%")
                  ->orWhere('verification_code', 'like', "%{$search}%");
            })
            ->when($filterStatus, function($q) use ($filterStatus) {
                $q->where('status', $filterStatus);
            })
            ->when($filterType, function($q) use ($filterType) {
                $q->whereHas('template', function($query) use ($filterType) {
                    $query->where('type', $filterType);
                });
            })
            ->when($dateFrom, function($q) use ($dateFrom) {
                $q->where('issued_at', '>=', $dateFrom);
            })
            ->when($dateTo, function($q) use ($dateTo) {
                $q->where('issued_at', '<=', $dateTo . ' 23:59:59');
            });

        $generated = collect($generatedQuery->get()->map(function($cert) {
            return (object) [
                'id' => $cert->id,
                'source' => 'generated',
                'certificate_no' => $cert->certificate_no,
                'type' => $cert->template->type ?? 'N/A',
                'equipment_name' => $cert->equipment->name ?? 'N/A',
                'issued_at' => $cert->issued_at,
                'status' => $cert->status,
                'generated_by' => $cert->generator->name ?? 'N/A',
                'verification_code' => $cert->verification_code ?? null,
            ];
        })->all());

        // --- Legacy/manual certificates (certificate table) ---
        $legacyQuery = Certificate::with(['equipment', 'issuedBy', 'patient'])
            ->when($search, function($q) use ($search) {
                $q->where('certificate_number', 'like', "%{$search}%");
            })
            ->when($filterStatus, function($q) use ($filterStatus) {
                // Map display status back to legacy status
                $q->where('status', strtolower($filterStatus));
            })
            ->when($filterType, function($q) use ($filterType) {
                $q->where('certificate_type', $filterType);
            })
            ->when($dateFrom, function($q) use ($dateFrom) {
                $q->where('issue_date', '>=', $dateFrom);
            })
            ->when($dateTo, function($q) use ($dateTo) {
                $q->where('issue_date', '<=', $dateTo);
            });

        $legacy = collect($legacyQuery->get()->map(function($cert) {
            return (object) [
                'id' => $cert->certificate_id,
                'source' => 'legacy',
                'certificate_no' => $cert->certificate_number,
                'type' => $cert->certificate_type,
                'equipment_name' => $cert->equipment->name ?? 'N/A',
                'issued_at' => $cert->issue_date,
                'status' => ucfirst($cert->status),
                'generated_by' => $cert->issuedBy ? ($cert->issuedBy->firstname . ' ' . $cert->issuedBy->lastname) : 'N/A',
                'verification_code' => null,
            ];
        })->all());

        // Combine, sort by date desc
        $all = $generated->merge($legacy)->sortByDesc(function($cert) {
            return $cert->issued_at ? $cert->issued_at->timestamp : 0;
        })->values();

        // Manual pagination
        $page = LengthAwarePaginator::resolveCurrentPage();
        $items = $all->slice(($page - 1) * $this->perPage, $this->perPage)->values();
        $paginator = new LengthAwarePaginator($items, $all->count(), $this->perPage, $page, [
            'path' => request()->url(),
            'query' => request()->query(),
        ]);

        // Combined stats
        $generatedTotal = CertificateIssue::count();
        $legacyTotal = Certificate::count();

        return [
            'certificates' => $paginator,
            'stats' => [
                'total' => $generatedTotal + $legacyTotal,
                'pending' => CertificateIssue::where('status', 'Pending')->count(),
                'draft' => Certificate::where('status', 'draft')->count(),
                'issued' => CertificateIssue::where('status', 'Issued')->count() + Certificate::where('status', 'issued')->count(),
                'revoked' => CertificateIssue::where('status', 'Revoked')->count() + Certificate::where('status', 'revoked')->count(),
                'expired' => CertificateIssue::where('status', 'Expired')->count(),
            ],
        ];
    }
}; ?>

<div class="p-6">
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
        <div class="grid grid-cols-1 md:grid-cols-6 gap-4 mb-6">
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
                        <p class="text-sm text-slate-600">Draft</p>
                        <p class="text-2xl font-bold text-slate-500">{{ $stats['draft'] }}</p>
                    </div>
                    <div class="w-12 h-12 bg-slate-100 rounded-lg flex items-center justify-center">
                        <svg class="w-6 h-6 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                        </svg>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-4">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-slate-600">Pending</p>
                        <p class="text-2xl font-bold text-orange-600">{{ $stats['pending'] }}</p>
                    </div>
                    <div class="w-12 h-12 bg-orange-100 rounded-lg flex items-center justify-center">
                        <svg class="w-6 h-6 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
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
                    <option value="Draft">Draft</option>
                    <option value="Pending">Pending</option>
                    <option value="Issued">Issued</option>
                    <option value="Revoked">Revoked</option>
                    <option value="Expired">Expired</option>
                </select>
                <select wire:model.live="filterType" class="px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                    <option value="">All Types</option>
                    <option value="calibration">Calibration</option>
                    <option value="maintenance">Maintenance</option>
                    <option value="lab_result">Lab Result</option>
                    <option value="safety">Safety</option>
                    <option value="compliance">Compliance</option>
                    <option value="other">Other</option>
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
                            <th class="px-4 py-3 text-left text-xs font-semibold text-slate-700 uppercase tracking-wide">Source</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-slate-700 uppercase tracking-wide">Issued By</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-slate-700 uppercase tracking-wide">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200">
                        @forelse($certificates as $certificate)
                            <tr class="hover:bg-slate-50 transition-colors">
                                <td class="px-4 py-3 text-sm font-mono text-slate-900">{{ $certificate->certificate_no }}</td>
                                <td class="px-4 py-3 text-sm">
                                    <span class="px-2 py-1 rounded-full text-xs font-medium
                                        {{ $certificate->type === 'calibration' ? 'bg-blue-100 text-blue-700' : '' }}
                                        {{ $certificate->type === 'maintenance' ? 'bg-emerald-100 text-emerald-700' : '' }}
                                        {{ $certificate->type === 'safety' ? 'bg-amber-100 text-amber-700' : '' }}
                                        {{ $certificate->type === 'lab_result' ? 'bg-purple-100 text-purple-700' : '' }}
                                        {{ $certificate->type === 'compliance' ? 'bg-cyan-100 text-cyan-700' : '' }}
                                        {{ $certificate->type === 'other' ? 'bg-slate-100 text-slate-700' : '' }}
                                    ">
                                        {{ ucfirst(str_replace('_', ' ', $certificate->type)) }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-sm text-slate-600">{{ $certificate->equipment_name }}</td>
                                <td class="px-4 py-3 text-sm text-slate-600">{{ $certificate->issued_at ? $certificate->issued_at->format('M d, Y') : 'N/A' }}</td>
                                <td class="px-4 py-3 text-sm">
                                    @if($certificate->status === 'Draft')
                                        <span class="px-2 py-1 bg-slate-100 text-slate-700 rounded-full text-xs font-medium">Draft</span>
                                    @elseif($certificate->status === 'Pending')
                                        <span class="px-2 py-1 bg-orange-100 text-orange-700 rounded-full text-xs font-medium">Pending</span>
                                    @elseif($certificate->status === 'Issued')
                                        <span class="px-2 py-1 bg-emerald-100 text-emerald-700 rounded-full text-xs font-medium">Issued</span>
                                    @elseif($certificate->status === 'Revoked')
                                        <span class="px-2 py-1 bg-rose-100 text-rose-700 rounded-full text-xs font-medium">Revoked</span>
                                    @else
                                        <span class="px-2 py-1 bg-amber-100 text-amber-700 rounded-full text-xs font-medium">Expired</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-sm">
                                    @if($certificate->source === 'generated')
                                        <span class="px-2 py-1 bg-indigo-50 text-indigo-600 rounded-full text-xs font-medium">Auto-Generated</span>
                                    @else
                                        <span class="px-2 py-1 bg-slate-50 text-slate-600 rounded-full text-xs font-medium">Manual</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-sm text-slate-600">{{ $certificate->generated_by }}</td>
                                <td class="px-4 py-3 text-sm">
                                    <div class="flex gap-2">
                                        @if($certificate->source === 'generated')
                                            {{-- Auto-generated certificate actions --}}
                                            <button
                                                wire:click="downloadPdf({{ $certificate->id }})"
                                                class="px-3 py-1 bg-blue-50 text-blue-600 rounded hover:bg-blue-100 transition-colors"
                                            >
                                                Download
                                            </button>
                                            @if($certificate->status === 'Pending')
                                                <button
                                                    wire:click="approve({{ $certificate->id }})"
                                                    wire:confirm="Approve this certificate?"
                                                    class="px-3 py-1 bg-emerald-50 text-emerald-600 rounded hover:bg-emerald-100 transition-colors"
                                                >
                                                    Approve
                                                </button>
                                                <button
                                                    wire:click="reject({{ $certificate->id }})"
                                                    wire:confirm="Are you sure you want to reject this certificate?"
                                                    class="px-3 py-1 bg-rose-50 text-rose-600 rounded hover:bg-rose-100 transition-colors"
                                                >
                                                    Reject
                                                </button>
                                            @endif
                                            @if($certificate->status === 'Issued')
                                                <button
                                                    wire:click="revoke({{ $certificate->id }}, 'generated')"
                                                    wire:confirm="Are you sure you want to revoke this certificate?"
                                                    class="px-3 py-1 bg-rose-50 text-rose-600 rounded hover:bg-rose-100 transition-colors"
                                                >
                                                    Revoke
                                                </button>
                                            @endif
                                        @else
                                            {{-- Legacy/manual certificate actions --}}
                                            @if($certificate->status === 'Draft')
                                                <button
                                                    wire:click="markIssued({{ $certificate->id }})"
                                                    wire:confirm="Mark this certificate as issued?"
                                                    class="px-3 py-1 bg-emerald-50 text-emerald-600 rounded hover:bg-emerald-100 transition-colors"
                                                >
                                                    Mark Issued
                                                </button>
                                            @endif
                                            @if($certificate->status === 'Issued')
                                                <button
                                                    wire:click="revoke({{ $certificate->id }}, 'legacy')"
                                                    wire:confirm="Are you sure you want to revoke this certificate?"
                                                    class="px-3 py-1 bg-rose-50 text-rose-600 rounded hover:bg-rose-100 transition-colors"
                                                >
                                                    Revoke
                                                </button>
                                            @endif
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="px-4 py-8 text-center text-slate-500">
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
