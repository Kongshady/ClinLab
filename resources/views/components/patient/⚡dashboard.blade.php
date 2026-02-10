<?php

use Livewire\Component;
use App\Models\Patient;
use App\Models\LabResult;

new class extends Component
{
    public $patient = null;
    public $labResults = [];
    public $selectedResult = null;

    // Group results by date
    public $groupedResults = [];

    public function mount()
    {
        $user = auth()->user();

        // Load patient profile linked to this user
        $this->patient = Patient::where('user_id', $user->id)->first();

        if ($this->patient) {
            $results = LabResult::with(['test', 'performedBy', 'verifiedBy'])
                ->where('patient_id', $this->patient->patient_id)
                ->orderBy('result_date', 'desc')
                ->get();

            // Group by date
            $this->groupedResults = $results->groupBy(function ($result) {
                return $result->result_date ? $result->result_date->format('Y-m-d') : 'Unknown';
            })->toArray();
        }
    }

    public function viewResult($id)
    {
        $this->selectedResult = LabResult::with(['test', 'performedBy', 'verifiedBy'])
            ->where('lab_result_id', $id)
            ->where('patient_id', $this->patient->patient_id) // security: only own results
            ->first();
    }

    public function closeResult()
    {
        $this->selectedResult = null;
    }

    public function with(): array
    {
        return [];
    }
};
?>

<div>
    <!-- Welcome Header -->
    <div class="mb-8">
        <h1 class="text-3xl font-bold text-gray-900">
            Welcome, {{ $patient ? $patient->firstname : auth()->user()->name }}!
        </h1>
        <p class="text-gray-500 mt-1">View your lab results and profile information.</p>
    </div>

    @if(!$patient)
        <div class="bg-yellow-50 border border-yellow-200 rounded-xl p-6 text-center">
            <svg class="w-12 h-12 text-yellow-400 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z"/>
            </svg>
            <h3 class="text-lg font-semibold text-yellow-800 mb-1">Profile Not Yet Linked</h3>
            <p class="text-yellow-700 text-sm">Your account has not been linked to a patient record yet. Please contact the laboratory staff to link your profile.</p>
        </div>
    @else

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Profile Card -->
        <div class="lg:col-span-1">
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                <div class="bg-gradient-to-r from-blue-500 to-cyan-400 px-6 py-8 text-center">
                    @if(auth()->user()->avatar)
                        <img src="{{ auth()->user()->avatar }}" alt="Avatar" class="w-20 h-20 rounded-full mx-auto ring-4 ring-white/30 mb-3">
                    @else
                        <div class="w-20 h-20 rounded-full bg-white/20 flex items-center justify-center text-white text-2xl font-bold mx-auto ring-4 ring-white/30 mb-3">
                            {{ strtoupper(substr($patient->firstname, 0, 1) . substr($patient->lastname, 0, 1)) }}
                        </div>
                    @endif
                    <h2 class="text-xl font-bold text-white">{{ $patient->full_name }}</h2>
                    <span class="inline-block mt-2 px-3 py-1 bg-white/20 rounded-full text-white text-xs font-medium">
                        {{ $patient->patient_type }} Patient
                    </span>
                </div>

                <div class="p-6 space-y-4">
                    <div class="flex items-center text-sm">
                        <svg class="w-4 h-4 text-gray-400 mr-3 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                        </svg>
                        <span class="text-gray-700">{{ $patient->email ?? auth()->user()->email }}</span>
                    </div>

                    <div class="flex items-center text-sm">
                        <svg class="w-4 h-4 text-gray-400 mr-3 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                        </svg>
                        <span class="text-gray-700">{{ $patient->gender }}</span>
                    </div>

                    <div class="flex items-center text-sm">
                        <svg class="w-4 h-4 text-gray-400 mr-3 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                        </svg>
                        <span class="text-gray-700">{{ $patient->birthdate ? $patient->birthdate->format('M d, Y') : 'N/A' }}</span>
                    </div>

                    @if($patient->birthdate)
                    <div class="flex items-center text-sm">
                        <svg class="w-4 h-4 text-gray-400 mr-3 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        <span class="text-gray-700">{{ $patient->birthdate->age }} years old</span>
                    </div>
                    @endif

                    <div class="flex items-center text-sm">
                        <svg class="w-4 h-4 text-gray-400 mr-3 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                        </svg>
                        <span class="text-gray-700">{{ $patient->contact_number ?: 'N/A' }}</span>
                    </div>

                    <div class="flex items-start text-sm">
                        <svg class="w-4 h-4 text-gray-400 mr-3 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                        </svg>
                        <span class="text-gray-700">{{ $patient->address ?: 'N/A' }}</span>
                    </div>
                </div>
            </div>

            <!-- Quick Stats -->
            <div class="mt-6 grid grid-cols-2 gap-4">
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4 text-center">
                    <p class="text-2xl font-bold text-blue-600">{{ collect($groupedResults)->flatten(1)->count() }}</p>
                    <p class="text-xs text-gray-500 mt-1">Total Results</p>
                </div>
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4 text-center">
                    <p class="text-2xl font-bold text-green-600">
                        {{ collect($groupedResults)->flatten(1)->where('status', 'final')->count() }}
                    </p>
                    <p class="text-xs text-gray-500 mt-1">Finalized</p>
                </div>
            </div>
        </div>

        <!-- Lab Results Section -->
        <div class="lg:col-span-2">
            <div class="bg-white rounded-xl shadow-sm border border-gray-200">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h2 class="text-lg font-semibold text-gray-900 flex items-center">
                        <svg class="w-5 h-5 mr-2 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"/>
                        </svg>
                        Lab Results History
                    </h2>
                </div>

                <div class="p-6">
                    @if(count($groupedResults) > 0)
                        @foreach($groupedResults as $date => $results)
                            <div class="mb-6 last:mb-0">
                                <div class="flex items-center mb-3">
                                    <div class="w-3 h-3 bg-blue-500 rounded-full mr-3"></div>
                                    <h3 class="text-sm font-semibold text-gray-700">
                                        {{ $date !== 'Unknown' ? \Carbon\Carbon::parse($date)->format('F d, Y') : 'Date Not Available' }}
                                    </h3>
                                </div>

                                <div class="ml-6 overflow-x-auto border border-gray-200 rounded-lg">
                                    <table class="min-w-full divide-y divide-gray-200">
                                        <thead class="bg-gray-50">
                                            <tr>
                                                <th class="px-4 py-2.5 text-left text-xs font-medium text-gray-500 uppercase">Test</th>
                                                <th class="px-4 py-2.5 text-left text-xs font-medium text-gray-500 uppercase">Result</th>
                                                <th class="px-4 py-2.5 text-left text-xs font-medium text-gray-500 uppercase">Normal Range</th>
                                                <th class="px-4 py-2.5 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                                                <th class="px-4 py-2.5 text-left text-xs font-medium text-gray-500 uppercase">Action</th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-gray-200">
                                            @foreach($results as $result)
                                                <tr class="hover:bg-gray-50">
                                                    <td class="px-4 py-3 text-sm font-medium text-gray-900">{{ $result['test']['label'] ?? 'N/A' }}</td>
                                                    <td class="px-4 py-3 text-sm text-gray-700">{{ $result['result_value'] ?? 'Pending' }}</td>
                                                    <td class="px-4 py-3 text-sm text-gray-500">{{ $result['normal_range'] ?? '-' }}</td>
                                                    <td class="px-4 py-3">
                                                        @php
                                                            $statusClass = match($result['status'] ?? 'draft') {
                                                                'final' => 'bg-green-100 text-green-800',
                                                                'revised' => 'bg-blue-100 text-blue-800',
                                                                default => 'bg-yellow-100 text-yellow-800',
                                                            };
                                                        @endphp
                                                        <span class="px-2 py-1 text-xs rounded-full {{ $statusClass }}">
                                                            {{ ucfirst($result['status'] ?? 'draft') }}
                                                        </span>
                                                    </td>
                                                    <td class="px-4 py-3">
                                                        @if(($result['status'] ?? '') === 'final')
                                                            <button wire:click="viewResult({{ $result['lab_result_id'] }})"
                                                                    class="inline-flex items-center text-xs text-blue-600 hover:text-blue-800 font-medium">
                                                                <svg class="w-3.5 h-3.5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                                                </svg>
                                                                View
                                                            </button>
                                                        @else
                                                            <span class="text-xs text-gray-400 italic">Pending</span>
                                                        @endif
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        @endforeach
                    @else
                        <div class="text-center py-12">
                            <svg class="w-16 h-16 text-gray-200 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                            </svg>
                            <h3 class="text-gray-500 font-medium mb-1">No Lab Results Yet</h3>
                            <p class="text-gray-400 text-sm">Your lab results will appear here once they are processed.</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- View Result Detail Modal -->
    @if($selectedResult)
    <div class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50 p-4">
        <div class="relative top-20 mx-auto p-6 border w-full max-w-lg shadow-lg rounded-xl bg-white">
            <div class="flex items-center justify-between mb-6">
                <h3 class="text-xl font-bold text-gray-900">Result Details</h3>
                <button wire:click="closeResult" class="text-gray-400 hover:text-gray-600">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>

            <div class="space-y-4">
                <div class="grid grid-cols-2 gap-4">
                    <div class="bg-gray-50 p-3 rounded-lg">
                        <p class="text-xs font-semibold text-gray-500 uppercase mb-1">Test</p>
                        <p class="text-sm font-medium text-gray-900">{{ $selectedResult->test->label ?? 'N/A' }}</p>
                    </div>
                    <div class="bg-gray-50 p-3 rounded-lg">
                        <p class="text-xs font-semibold text-gray-500 uppercase mb-1">Status</p>
                        <span class="px-2 py-1 text-xs rounded-full bg-green-100 text-green-800">{{ ucfirst($selectedResult->status) }}</span>
                    </div>
                    <div class="bg-gray-50 p-3 rounded-lg">
                        <p class="text-xs font-semibold text-gray-500 uppercase mb-1">Result Value</p>
                        <p class="text-sm font-medium text-gray-900">{{ $selectedResult->result_value ?? 'N/A' }}</p>
                    </div>
                    <div class="bg-gray-50 p-3 rounded-lg">
                        <p class="text-xs font-semibold text-gray-500 uppercase mb-1">Normal Range</p>
                        <p class="text-sm font-medium text-gray-900">{{ $selectedResult->normal_range ?? 'N/A' }}</p>
                    </div>
                    <div class="bg-gray-50 p-3 rounded-lg col-span-2">
                        <p class="text-xs font-semibold text-gray-500 uppercase mb-1">Findings</p>
                        <p class="text-sm text-gray-900">{{ $selectedResult->findings ?: 'No findings recorded.' }}</p>
                    </div>
                    <div class="bg-gray-50 p-3 rounded-lg col-span-2">
                        <p class="text-xs font-semibold text-gray-500 uppercase mb-1">Remarks</p>
                        <p class="text-sm text-gray-900">{{ $selectedResult->remarks ?: 'No remarks.' }}</p>
                    </div>
                    <div class="bg-gray-50 p-3 rounded-lg">
                        <p class="text-xs font-semibold text-gray-500 uppercase mb-1">Result Date</p>
                        <p class="text-sm text-gray-900">{{ $selectedResult->result_date ? $selectedResult->result_date->format('M d, Y') : 'N/A' }}</p>
                    </div>
                    <div class="bg-gray-50 p-3 rounded-lg">
                        <p class="text-xs font-semibold text-gray-500 uppercase mb-1">Performed By</p>
                        <p class="text-sm text-gray-900">
                            {{ $selectedResult->performedBy ? $selectedResult->performedBy->firstname . ' ' . $selectedResult->performedBy->lastname : 'N/A' }}
                        </p>
                    </div>
                </div>

                <div class="flex justify-end pt-4 border-t">
                    <button wire:click="closeResult"
                            class="px-5 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition-colors">
                        Close
                    </button>
                </div>
            </div>
        </div>
    </div>
    @endif

    @endif
</div>
