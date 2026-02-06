<?php

use Livewire\Volt\Component;
use App\Models\CertificateIssue;

new class extends Component
{
    public $searchInput = '';
    public $certificate = null;
    public $verified = false;
    public $notFound = false;

    public function verify()
    {
        $this->reset(['certificate', 'verified', 'notFound']);

        if (empty($this->searchInput)) {
            return;
        }

        $this->certificate = CertificateIssue::with(['template', 'equipment', 'generator'])
            ->where(function($query) {
                $query->where('certificate_no', $this->searchInput)
                      ->orWhere('verification_code', $this->searchInput);
            })
            ->first();

        if ($this->certificate) {
            $this->verified = $this->certificate->isValid();
        } else {
            $this->notFound = true;
        }
    }

    public function reset(...$properties)
    {
        if (empty($properties)) {
            $this->certificate = null;
            $this->verified = false;
            $this->notFound = false;
        } else {
            parent::reset($properties);
        }
    }
}; ?>

<div class="min-h-screen bg-gradient-to-br from-slate-50 via-blue-50 to-indigo-50 p-6">
    <div class="max-w-4xl mx-auto">
        <!-- Header -->
        <div class="text-center mb-8">
            <h1 class="text-4xl font-bold bg-gradient-to-r from-blue-600 to-indigo-600 bg-clip-text text-transparent mb-2">
                Certificate Verification
            </h1>
            <p class="text-slate-600">Enter certificate number or verification code to validate authenticity</p>
        </div>

        <!-- Search Box -->
        <div class="bg-white rounded-xl shadow-lg border border-slate-200 p-8 mb-6">
            <form wire:submit.prevent="verify" class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-2">Certificate Number or Verification Code</label>
                    <input 
                        type="text" 
                        wire:model="searchInput"
                        placeholder="e.g., CAL-2026-00023 or verification code"
                        class="w-full px-4 py-3 border border-slate-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent text-lg"
                        autofocus
                    >
                </div>
                <button 
                    type="submit"
                    class="w-full px-6 py-3 bg-gradient-to-r from-blue-600 to-indigo-600 text-white rounded-lg hover:from-blue-700 hover:to-indigo-700 transition-all shadow-md hover:shadow-lg text-lg font-medium"
                >
                    Verify Certificate
                </button>
            </form>
        </div>

        <!-- Results -->
        @if($certificate)
            <div class="bg-white rounded-xl shadow-lg border-2 {{ $verified ? 'border-emerald-500' : 'border-rose-500' }} p-8">
                <!-- Status Badge -->
                <div class="text-center mb-6">
                    @if($verified)
                        <div class="inline-flex items-center gap-2 px-6 py-3 bg-emerald-100 border-2 border-emerald-500 rounded-full">
                            <svg class="w-8 h-8 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                            <span class="text-xl font-bold text-emerald-700">VALID CERTIFICATE</span>
                        </div>
                    @else
                        <div class="inline-flex items-center gap-2 px-6 py-3 bg-rose-100 border-2 border-rose-500 rounded-full">
                            <svg class="w-8 h-8 text-rose-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                            <span class="text-xl font-bold text-rose-700">INVALID CERTIFICATE</span>
                        </div>
                        <p class="mt-3 text-slate-600">
                            @if($certificate->status === 'Revoked')
                                This certificate has been revoked.
                            @elseif($certificate->status === 'Expired')
                                This certificate has expired.
                            @endif
                        </p>
                    @endif
                </div>

                <!-- Certificate Details -->
                <div class="border-t-2 border-slate-200 pt-6">
                    <h2 class="text-xl font-bold text-slate-900 mb-4">Certificate Details</h2>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="bg-slate-50 rounded-lg p-4">
                            <p class="text-sm text-slate-600 mb-1">Certificate Number</p>
                            <p class="text-lg font-mono font-semibold text-slate-900">{{ $certificate->certificate_no }}</p>
                        </div>

                        <div class="bg-slate-50 rounded-lg p-4">
                            <p class="text-sm text-slate-600 mb-1">Type</p>
                            <p class="text-lg font-semibold text-slate-900">{{ ucfirst($certificate->template->type) }} Certificate</p>
                        </div>

                        @if($certificate->equipment)
                            <div class="bg-slate-50 rounded-lg p-4">
                                <p class="text-sm text-slate-600 mb-1">Equipment</p>
                                <p class="text-lg font-semibold text-slate-900">{{ $certificate->equipment->name }}</p>
                            </div>

                            <div class="bg-slate-50 rounded-lg p-4">
                                <p class="text-sm text-slate-600 mb-1">Serial Number</p>
                                <p class="text-lg font-semibold text-slate-900">{{ $certificate->equipment->serial_number ?? 'N/A' }}</p>
                            </div>
                        @endif

                        <div class="bg-slate-50 rounded-lg p-4">
                            <p class="text-sm text-slate-600 mb-1">Issue Date</p>
                            <p class="text-lg font-semibold text-slate-900">{{ $certificate->issued_at->format('F d, Y') }}</p>
                        </div>

                        @if($certificate->valid_until)
                            <div class="bg-slate-50 rounded-lg p-4">
                                <p class="text-sm text-slate-600 mb-1">Valid Until</p>
                                <p class="text-lg font-semibold text-slate-900">{{ $certificate->valid_until->format('F d, Y') }}</p>
                            </div>
                        @endif

                        <div class="bg-slate-50 rounded-lg p-4">
                            <p class="text-sm text-slate-600 mb-1">Status</p>
                            <p class="text-lg font-semibold 
                                {{ $certificate->status === 'Issued' ? 'text-emerald-600' : '' }}
                                {{ $certificate->status === 'Revoked' ? 'text-rose-600' : '' }}
                                {{ $certificate->status === 'Expired' ? 'text-amber-600' : '' }}
                            ">
                                {{ $certificate->status }}
                            </p>
                        </div>

                        <div class="bg-slate-50 rounded-lg p-4">
                            <p class="text-sm text-slate-600 mb-1">Generated By</p>
                            <p class="text-lg font-semibold text-slate-900">{{ $certificate->generator->name ?? 'N/A' }}</p>
                        </div>
                    </div>

                    <div class="mt-6 p-4 bg-blue-50 border border-blue-200 rounded-lg">
                        <p class="text-sm text-blue-900">
                            <strong>Verification Code:</strong> 
                            <span class="font-mono">{{ $certificate->verification_code }}</span>
                        </p>
                    </div>
                </div>
            </div>
        @elseif($notFound)
            <div class="bg-white rounded-xl shadow-lg border-2 border-amber-500 p-8 text-center">
                <svg class="w-16 h-16 text-amber-500 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                </svg>
                <h3 class="text-2xl font-bold text-slate-900 mb-2">Certificate Not Found</h3>
                <p class="text-slate-600">The certificate number or verification code you entered does not exist in our system.</p>
                <p class="text-sm text-slate-500 mt-3">Please check the number and try again.</p>
            </div>
        @endif
    </div>
</div>
