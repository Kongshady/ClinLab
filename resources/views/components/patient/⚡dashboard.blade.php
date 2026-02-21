<?php

use Livewire\Component;
use App\Models\Patient;
use App\Models\LabResult;
use App\Models\LabTestOrder;
use App\Models\Certificate;
use App\Models\CertificateIssue;
use App\Models\TestRequest;
use App\Models\TestRequestItem;
use App\Models\Test;
use App\Models\UicDirectoryPerson;
use Barryvdh\DomPDF\Facade\Pdf;

new class extends Component
{
    public $patient = null;
    public $selectedResult = null;
    public $labOrders = [];
    public $activeTab = 'results';
    public $selectedOrder = null;

    // Lab results search/filter
    public $orderSearch = '';
    public $orderStatusFilter = '';
    public $ordersPage = 1;
    public $ordersPerPage = 5;

    // Profile edit fields
    public $editMode = false;
    public $editFirstname = '';
    public $editMiddlename = '';
    public $editLastname = '';
    public $editGender = '';
    public $editBirthdate = '';
    public $editContact = '';
    public $editAddress = '';

    // Certificates
    public $certificates = [];
    public $selectedCertificate = null;
    public $certFilterType = '';
    public $certFilterStatus = '';
    public $certSearch = '';

    // Verification
    public $verifyCode = '';
    public $verifyResult = null;
    public $verifyLoading = false;

    // Test Requests
    public $testRequests = [];
    public $showRequestForm = false;
    public $requestSelectedTests = [];
    public $requestPurpose = '';
    public $requestPreferredDate = '';
    public $requestTestSearch = '';
    public $viewingRequest = null;

    // UIC Directory data (read-only)
    public $directoryRecord = null;

    public function mount()
    {
        $user = auth()->user();
        $this->patient = Patient::where('user_id', $user->id)->first();

        // Load linked UIC directory record
        if ($this->patient && $this->patient->external_ref_id) {
            $this->directoryRecord = UicDirectoryPerson::where('external_ref_id', $this->patient->external_ref_id)->first();
        } elseif ($this->patient && $this->patient->email) {
            $this->directoryRecord = UicDirectoryPerson::where('email', strtolower($this->patient->email))->first();
        }

        $this->loadResults();
        $this->loadCertificates();
        $this->loadTestRequests();
    }

    public function loadResults()
    {
        if ($this->patient) {
            $this->labOrders = LabTestOrder::with([
                    'physician',
                    'orderTests.test.section',
                    'orderTests.labResult.performedBy',
                    'orderTests.labResult.verifiedBy',
                ])
                ->where('patient_id', $this->patient->patient_id)
                ->orderBy('order_date', 'desc')
                ->get();
        }
    }

    public function loadCertificates()
    {
        if (!$this->patient) return;

        // Load from certificate table (directly linked to patient)
        $query = Certificate::with(['equipment', 'issuedBy', 'verifiedBy'])
            ->where('patient_id', $this->patient->patient_id);

        if ($this->certFilterType) {
            $query->where('certificate_type', $this->certFilterType);
        }
        if ($this->certFilterStatus) {
            $query->whereRaw('LOWER(status) = ?', [strtolower($this->certFilterStatus)]);
        }
        if ($this->certSearch) {
            $query->where(function ($q) {
                $q->where('certificate_number', 'like', '%' . $this->certSearch . '%')
                  ->orWhere('certificate_type', 'like', '%' . $this->certSearch . '%');
            });
        }

        $certs = $query->orderBy('issue_date', 'desc')->get();

        // Also load from certificate_issues linked via lab_result_id
        $labResultIds = LabResult::where('patient_id', $this->patient->patient_id)
            ->pluck('lab_result_id');

        if ($labResultIds->isNotEmpty()) {
            $issueQuery = CertificateIssue::with(['template', 'generator', 'equipment'])
                ->whereIn('lab_result_id', $labResultIds);

            if ($this->certFilterStatus) {
                $issueQuery->whereRaw('LOWER(status) = ?', [strtolower($this->certFilterStatus)]);
            }
            if ($this->certSearch) {
                $issueQuery->where('certificate_no', 'like', '%' . $this->certSearch . '%');
            }

            $issues = $issueQuery->orderBy('issued_at', 'desc')->get();
        } else {
            $issues = collect();
        }

        // Merge both sources into a unified list
        $merged = collect();

        foreach ($certs as $cert) {
            $merged->push([
                'source' => 'certificate',
                'id' => $cert->certificate_id,
                'number' => $cert->certificate_number,
                'type' => ucfirst($cert->certificate_type),
                'status' => $cert->status,
                'issue_date' => $cert->issue_date ? $cert->issue_date->format('M d, Y') : null,
                'valid_until' => null,
                'issued_by' => $cert->issuedBy ? ($cert->issuedBy->firstname . ' ' . $cert->issuedBy->lastname) : null,
                'verified_by' => $cert->verifiedBy ? ($cert->verifiedBy->firstname . ' ' . $cert->verifiedBy->lastname) : null,
                'equipment' => $cert->equipment ? $cert->equipment->equipment_name : null,
                'verification_code' => null,
                'pdf_path' => $cert->pdf_path,
                'raw_date' => $cert->issue_date,
            ]);
        }

        foreach ($issues as $issue) {
            $type = $issue->template ? $issue->template->type : 'general';
            $merged->push([
                'source' => 'certificate_issue',
                'id' => $issue->id,
                'number' => $issue->certificate_no,
                'type' => ucfirst($type),
                'status' => $issue->status,
                'issue_date' => $issue->issued_at ? $issue->issued_at->format('M d, Y') : null,
                'valid_until' => $issue->valid_until ? $issue->valid_until->format('M d, Y') : null,
                'issued_by' => $issue->generator ? $issue->generator->name : null,
                'verified_by' => null,
                'equipment' => $issue->equipment ? $issue->equipment->equipment_name : null,
                'verification_code' => $issue->verification_code,
                'pdf_path' => $issue->pdf_path,
                'raw_date' => $issue->issued_at,
            ]);
        }

        $this->certificates = $merged->sortByDesc('raw_date')->values()->toArray();
    }

    public function updatedOrderSearch()
    {
        $this->ordersPage = 1;
    }

    public function updatedOrderStatusFilter()
    {
        $this->ordersPage = 1;
    }

    public function nextOrdersPage()
    {
        $this->ordersPage++;
    }

    public function prevOrdersPage()
    {
        if ($this->ordersPage > 1) {
            $this->ordersPage--;
        }
    }

    public function updatedCertFilterType()
    {
        $this->loadCertificates();
    }

    public function updatedCertFilterStatus()
    {
        $this->loadCertificates();
    }

    public function updatedCertSearch()
    {
        $this->loadCertificates();
    }

    public function viewCertificate($source, $id)
    {
        if ($source === 'certificate') {
            $cert = Certificate::with(['equipment', 'issuedBy', 'verifiedBy', 'patient'])
                ->where('certificate_id', $id)
                ->where('patient_id', $this->patient->patient_id)
                ->first();

            if ($cert) {
                $this->selectedCertificate = [
                    'source' => 'certificate',
                    'id' => $cert->certificate_id,
                    'number' => $cert->certificate_number,
                    'type' => ucfirst($cert->certificate_type),
                    'status' => $cert->status,
                    'issue_date' => $cert->issue_date ? $cert->issue_date->format('F d, Y') : null,
                    'valid_until' => null,
                    'issued_by' => $cert->issuedBy ? ($cert->issuedBy->firstname . ' ' . $cert->issuedBy->lastname) : null,
                    'verified_by' => $cert->verifiedBy ? ($cert->verifiedBy->firstname . ' ' . $cert->verifiedBy->lastname) : null,
                    'equipment' => $cert->equipment ? $cert->equipment->equipment_name : null,
                    'verification_code' => null,
                    'pdf_path' => $cert->pdf_path,
                    'certificate_data' => $cert->certificate_data,
                    'patient_name' => $cert->patient ? $cert->patient->full_name : null,
                ];
            }
        } else {
            $labResultIds = LabResult::where('patient_id', $this->patient->patient_id)
                ->pluck('lab_result_id');

            $issue = CertificateIssue::with(['template', 'generator', 'equipment'])
                ->where('id', $id)
                ->whereIn('lab_result_id', $labResultIds)
                ->first();

            if ($issue) {
                $this->selectedCertificate = [
                    'source' => 'certificate_issue',
                    'id' => $issue->id,
                    'number' => $issue->certificate_no,
                    'type' => $issue->template ? ucfirst($issue->template->type) : 'General',
                    'status' => $issue->status,
                    'issue_date' => $issue->issued_at ? $issue->issued_at->format('F d, Y') : null,
                    'valid_until' => $issue->valid_until ? $issue->valid_until->format('F d, Y') : null,
                    'issued_by' => $issue->generator ? $issue->generator->name : null,
                    'verified_by' => null,
                    'equipment' => $issue->equipment ? $issue->equipment->equipment_name : null,
                    'verification_code' => $issue->verification_code,
                    'pdf_path' => $issue->pdf_path,
                    'certificate_data' => null,
                    'patient_name' => $this->patient->full_name,
                ];
            }
        }
    }

    public function closeCertificate()
    {
        $this->selectedCertificate = null;
    }

    public function verifyCertificate()
    {
        $this->verifyResult = null;

        if (empty(trim($this->verifyCode))) {
            $this->verifyResult = ['status' => 'error', 'message' => 'Please enter a certificate number or verification code.'];
            return;
        }

        $code = trim($this->verifyCode);

        // Search in certificate table
        $cert = Certificate::with(['patient', 'issuedBy', 'verifiedBy'])
            ->where('certificate_number', $code)
            ->first();

        if ($cert) {
            $this->verifyResult = [
                'status' => 'found',
                'valid' => strtolower($cert->status) === 'issued',
                'number' => $cert->certificate_number,
                'type' => ucfirst($cert->certificate_type),
                'cert_status' => $cert->status,
                'issue_date' => $cert->issue_date ? $cert->issue_date->format('F d, Y') : null,
                'valid_until' => null,
                'patient' => $cert->patient ? $cert->patient->full_name : null,
                'issued_by' => $cert->issuedBy ? ($cert->issuedBy->firstname . ' ' . $cert->issuedBy->lastname) : null,
            ];
            return;
        }

        // Search in certificate_issues table (by certificate_no or verification_code)
        $issue = CertificateIssue::with(['template', 'generator'])
            ->where('certificate_no', $code)
            ->orWhere('verification_code', $code)
            ->first();

        if ($issue) {
            $this->verifyResult = [
                'status' => 'found',
                'valid' => $issue->isValid(),
                'number' => $issue->certificate_no,
                'type' => $issue->template ? ucfirst($issue->template->type) : 'General',
                'cert_status' => $issue->status,
                'issue_date' => $issue->issued_at ? $issue->issued_at->format('F d, Y') : null,
                'valid_until' => $issue->valid_until ? $issue->valid_until->format('F d, Y') : null,
                'patient' => null,
                'issued_by' => $issue->generator ? $issue->generator->name : null,
            ];
            return;
        }

        $this->verifyResult = ['status' => 'not_found', 'message' => 'No certificate found with this number or code.'];
    }

    public function clearVerification()
    {
        $this->verifyCode = '';
        $this->verifyResult = null;
    }

    // ========== TEST REQUEST METHODS ==========

    public function loadTestRequests()
    {
        if ($this->patient) {
            $this->testRequests = TestRequest::with(['items.test.section', 'reviewer'])
                ->where('patient_id', $this->patient->patient_id)
                ->orderBy('datetime_added', 'desc')
                ->get()
                ->toArray();
        }
    }

    public function openRequestForm()
    {
        $this->reset(['requestSelectedTests', 'requestPurpose', 'requestPreferredDate', 'requestTestSearch']);
        $this->showRequestForm = true;
    }

    public function closeRequestForm()
    {
        $this->showRequestForm = false;
        $this->reset(['requestSelectedTests', 'requestPurpose', 'requestPreferredDate', 'requestTestSearch']);
        $this->resetValidation();
    }

    public function toggleRequestTest($testId)
    {
        if (in_array($testId, $this->requestSelectedTests)) {
            $this->requestSelectedTests = array_values(array_diff($this->requestSelectedTests, [$testId]));
        } else {
            $this->requestSelectedTests[] = $testId;
        }
    }

    public function submitTestRequest()
    {
        $this->validate([
            'requestSelectedTests' => 'required|array|min:1',
            'requestPurpose' => 'nullable|string|max:500',
            'requestPreferredDate' => 'nullable|date|after_or_equal:today',
        ], [
            'requestSelectedTests.required' => 'Please select at least one test.',
            'requestSelectedTests.min' => 'Please select at least one test.',
            'requestPreferredDate.after_or_equal' => 'Preferred date must be today or later.',
        ]);

        $request = TestRequest::create([
            'patient_id' => $this->patient->patient_id,
            'requested_by_user_id' => auth()->id(),
            'purpose' => $this->requestPurpose ?: null,
            'preferred_date' => $this->requestPreferredDate ?: null,
            'status' => 'PENDING',
            'datetime_added' => now(),
        ]);

        foreach ($this->requestSelectedTests as $testId) {
            TestRequestItem::create([
                'request_id' => $request->id,
                'test_id' => $testId,
                'datetime_added' => now(),
            ]);
        }

        $this->closeRequestForm();
        $this->loadTestRequests();
        session()->flash('request_submitted', 'Test request submitted successfully! Staff will review your request.');
    }

    public function cancelTestRequest($requestId)
    {
        $request = TestRequest::where('id', $requestId)
            ->where('patient_id', $this->patient->patient_id)
            ->where('status', 'PENDING')
            ->first();

        if ($request) {
            $request->update([
                'status' => 'CANCELLED',
                'datetime_updated' => now(),
            ]);
            $this->loadTestRequests();
            session()->flash('request_cancelled', 'Request cancelled successfully.');
        }
    }

    public function viewRequestDetail($requestId)
    {
        $this->viewingRequest = TestRequest::with(['items.test.section', 'reviewer'])
            ->where('id', $requestId)
            ->where('patient_id', $this->patient->patient_id)
            ->first();
    }

    public function closeRequestDetail()
    {
        $this->viewingRequest = null;
    }

    public function switchTab($tab)
    {
        $this->activeTab = $tab;
        $this->selectedResult = null;
        $this->selectedCertificate = null;
        $this->selectedOrder = null;
        $this->editMode = false;
        $this->verifyResult = null;
        $this->viewingRequest = null;
        $this->showRequestForm = false;
    }

    public function viewResult($id)
    {
        $this->selectedResult = LabResult::with(['test', 'performedBy', 'verifiedBy'])
            ->where('lab_result_id', $id)
            ->where('patient_id', $this->patient->patient_id)
            ->first();
    }

    public function closeResult()
    {
        $this->selectedResult = null;
    }

    public function viewOrder($orderId)
    {
        $this->selectedOrder = LabTestOrder::with([
            'physician',
            'orderTests.test.section',
            'orderTests.labResult.performedBy',
            'orderTests.labResult.verifiedBy',
        ])
        ->where('lab_test_order_id', $orderId)
        ->where('patient_id', $this->patient->patient_id)
        ->first();
    }

    public function closeOrder()
    {
        $this->selectedOrder = null;
    }

    public function downloadOrderPdf()
    {
        if (!$this->selectedOrder) return;

        $order = LabTestOrder::with([
            'patient',
            'physician',
            'orderTests.test.section',
            'orderTests.labResult.performedBy',
            'orderTests.labResult.verifiedBy',
        ])
        ->where('lab_test_order_id', $this->selectedOrder->lab_test_order_id)
        ->where('patient_id', $this->patient->patient_id)
        ->first();

        if (!$order) return;

        // Generate serial numbers and QR codes for final results
        $serialNumbers = [];
        $qrCodes = [];

        foreach ($order->orderTests as $orderTest) {
            if ($orderTest->labResult && $orderTest->labResult->status === 'final' && !$orderTest->labResult->is_revoked) {
                $serial = $orderTest->labResult->assignSerialNumber();
                $testName = $orderTest->test->label ?? 'Unknown';
                $serialNumbers[$testName] = $serial;
                $qrCodes[$serial] = $orderTest->labResult->generateQrCodeBase64();
                $orderTest->labResult->markAsPrinted();
            }
        }

        $pdf = Pdf::loadView('pdf.lab-result', [
            'order' => $order,
            'serialNumbers' => $serialNumbers,
            'qrCodes' => $qrCodes,
        ])->setPaper('a4', 'portrait');

        $filename = 'LabResult_Order_' . $order->lab_test_order_id . '_' . now()->format('Ymd') . '.pdf';

        return response()->streamDownload(function () use ($pdf) {
            echo $pdf->output();
        }, $filename);
    }

    public function startEdit()
    {
        $this->editFirstname = $this->patient->firstname;
        $this->editMiddlename = $this->patient->middlename ?? '';
        $this->editLastname = $this->patient->lastname;
        $this->editGender = $this->patient->gender;
        $this->editBirthdate = $this->patient->birthdate ? $this->patient->birthdate->format('Y-m-d') : '';
        $this->editContact = $this->patient->contact_number ?? '';
        $this->editAddress = $this->patient->address ?? '';
        $this->editMode = true;
    }

    public function cancelEdit()
    {
        $this->editMode = false;
        $this->resetValidation();
    }

    public function saveProfile()
    {
        $this->validate([
            'editFirstname' => 'required|string|max:100',
            'editLastname' => 'required|string|max:100',
            'editMiddlename' => 'nullable|string|max:100',
            'editGender' => 'required|in:Male,Female',
            'editBirthdate' => 'required|date|before:today',
            'editContact' => 'nullable|string|max:20',
            'editAddress' => 'nullable|string|max:255',
        ]);

        $this->patient->update([
            'firstname' => $this->editFirstname,
            'middlename' => $this->editMiddlename ?: null,
            'lastname' => $this->editLastname,
            'gender' => $this->editGender,
            'birthdate' => $this->editBirthdate,
            'contact_number' => $this->editContact ?: null,
            'address' => $this->editAddress ?: null,
            'datetime_updated' => now(),
        ]);

        $this->patient->refresh();
        $this->editMode = false;

        session()->flash('profile_saved', 'Profile updated successfully!');
    }

    public function with(): array
    {
        $availableTests = collect();
        if ($this->showRequestForm) {
            $query = Test::with('section')
                ->where('is_deleted', 0)
                ->orderBy('label');
            if ($this->requestTestSearch) {
                $query->where(function($q) {
                    $q->where('label', 'like', '%' . $this->requestTestSearch . '%')
                      ->orWhereHas('section', function($sq) {
                          $sq->where('section_name', 'like', '%' . $this->requestTestSearch . '%');
                      });
                });
            }
            $availableTests = $query->get();
        }
        return [
            'availableTests' => $availableTests,
        ];
    }
};
?>

<div>
    @if(!$patient)
        {{-- Not Linked State --}}
        <div class="max-w-lg mx-auto mt-16">
            <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-10 text-center">
                <div class="w-16 h-16 rounded-full bg-amber-100 flex items-center justify-center mx-auto mb-4">
                    <svg class="w-8 h-8 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z"/>
                    </svg>
                </div>
                <h3 class="text-xl font-bold text-gray-900 mb-2">Profile Not Yet Linked</h3>
                <p class="text-gray-500 text-sm leading-relaxed">Your account has not been linked to a patient record yet.<br>Please contact the laboratory staff to link your profile.</p>
            </div>
        </div>
    @else

    <div class="flex flex-col lg:flex-row gap-6">

        {{-- ============ LEFT SIDEBAR ============ --}}
        <div class="lg:w-80 flex-shrink-0 space-y-4">

            {{-- Profile Card --}}
            <div class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden">
                <div class="bg-blue-500 px-6 py-8 text-center relative">
                    <div class="absolute inset-0 bg-[url('data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iNDAiIGhlaWdodD0iNDAiIHhtbG5zPSJodHRwOi8vd3d3LnczLm9yZy8yMDAwL3N2ZyI+PGNpcmNsZSBjeD0iMjAiIGN5PSIyMCIgcj0iMSIgZmlsbD0icmdiYSgyNTUsMjU1LDI1NSwwLjA1KSIvPjwvc3ZnPg==')] opacity-50"></div>
                    <div class="relative">
                        @if(auth()->user()->avatar)
                            <img src="{{ auth()->user()->avatar }}" alt="Avatar" class="w-20 h-20 rounded-full mx-auto ring-4 ring-white/30 shadow-lg mb-3">
                        @else
                            <div class="w-20 h-20 rounded-full bg-white/20 backdrop-blur-sm flex items-center justify-center text-white text-2xl font-bold mx-auto ring-4 ring-white/30 mb-3">
                                {{ strtoupper(substr($patient->firstname, 0, 1) . substr($patient->lastname, 0, 1)) }}
                            </div>
                        @endif
                        <h2 class="text-lg font-bold text-white">{{ $patient->full_name }}</h2>
                        <span class="inline-block mt-1.5 px-3 py-0.5 bg-white/20 backdrop-blur-sm rounded-full text-white/90 text-xs font-medium">
                            {{ $patient->patient_type }} Patient
                        </span>
                    </div>
                </div>

                {{-- Quick Info --}}
                <div class="px-5 py-4 space-y-2.5 text-sm">
                    <div class="flex items-center text-gray-600">
                        <svg class="w-4 h-4 text-gray-400 mr-2.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                        <span class="truncate">{{ $patient->email ?? auth()->user()->email }}</span>
                    </div>
                    @if($patient->birthdate)
                    <div class="flex items-center text-gray-600">
                        <svg class="w-4 h-4 text-gray-400 mr-2.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                        <span>{{ $patient->birthdate->format('M d, Y') }} &middot; {{ $patient->birthdate->age }}y</span>
                    </div>
                    @endif
                    <div class="flex items-center text-gray-600">
                        <svg class="w-4 h-4 text-gray-400 mr-2.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                        <span>{{ $patient->gender ?: 'Not set' }}</span>
                    </div>
                </div>
            </div>

            {{-- Navigation --}}
            <nav class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden">
                <div class="p-2 space-y-1">
                    <button wire:click="switchTab('results')"
                            class="w-full flex items-center gap-3 px-4 py-3 rounded-xl text-sm font-medium transition-all
                                {{ $activeTab === 'results' ? 'bg-blue-50 text-blue-700' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900' }}">
                        <svg class="w-5 h-5 {{ $activeTab === 'results' ? 'text-blue-500' : 'text-gray-400' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"/>
                        </svg>
                        Lab Results
                        @if(count($labOrders) > 0)
                            <span class="ml-auto text-xs px-2 py-0.5 rounded-full {{ $activeTab === 'results' ? 'bg-blue-100 text-blue-600' : 'bg-gray-100 text-gray-500' }}">
                                {{ count($labOrders) }}
                            </span>
                        @endif
                    </button>

                    <button wire:click="switchTab('certificates')"
                            class="w-full flex items-center gap-3 px-4 py-3 rounded-xl text-sm font-medium transition-all
                                {{ $activeTab === 'certificates' ? 'bg-blue-50 text-blue-700' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900' }}">
                        <svg class="w-5 h-5 {{ $activeTab === 'certificates' ? 'text-blue-500' : 'text-gray-400' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z"/>
                        </svg>
                        Certificates
                        @if(count($certificates) > 0)
                            <span class="ml-auto text-xs px-2 py-0.5 rounded-full {{ $activeTab === 'certificates' ? 'bg-blue-100 text-blue-600' : 'bg-gray-100 text-gray-500' }}">
                                {{ count($certificates) }}
                            </span>
                        @endif
                    </button>

                    <button wire:click="switchTab('requests')"
                            class="w-full flex items-center gap-3 px-4 py-3 rounded-xl text-sm font-medium transition-all
                                {{ $activeTab === 'requests' ? 'bg-blue-50 text-blue-700' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900' }}">
                        <svg class="w-5 h-5 {{ $activeTab === 'requests' ? 'text-blue-500' : 'text-gray-400' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v3m0 0v3m0-3h3m-3 0H9m12 0a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        Request Test
                        @if(count($testRequests) > 0)
                            <span class="ml-auto text-xs px-2 py-0.5 rounded-full {{ $activeTab === 'requests' ? 'bg-blue-100 text-blue-600' : 'bg-gray-100 text-gray-500' }}">
                                {{ count($testRequests) }}
                            </span>
                        @endif
                    </button>

                    <button wire:click="switchTab('verify')"
                            class="w-full flex items-center gap-3 px-4 py-3 rounded-xl text-sm font-medium transition-all
                                {{ $activeTab === 'verify' ? 'bg-blue-50 text-blue-700' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900' }}">
                        <svg class="w-5 h-5 {{ $activeTab === 'verify' ? 'text-blue-500' : 'text-gray-400' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/>
                        </svg>
                        Verify Certificate
                    </button>

                    <button wire:click="switchTab('profile')"
                            class="w-full flex items-center gap-3 px-4 py-3 rounded-xl text-sm font-medium transition-all
                                {{ $activeTab === 'profile' ? 'bg-blue-50 text-blue-700' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900' }}">
                        <svg class="w-5 h-5 {{ $activeTab === 'profile' ? 'text-blue-500' : 'text-gray-400' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5.121 17.804A13.937 13.937 0 0112 16c2.5 0 4.847.655 6.879 1.804M15 10a3 3 0 11-6 0 3 3 0 016 0zm6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        My Profile
                    </button>
                </div>
            </nav>

            {{-- Quick Stats --}}
            <div class="grid grid-cols-2 gap-3">
                <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-4 text-center">
                    <p class="text-2xl font-bold text-blue-600">{{ count($labOrders) }}</p>
                    <p class="text-xs text-gray-500 mt-0.5">Orders</p>
                </div>
                <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-4 text-center">
                    <p class="text-2xl font-bold text-emerald-600">{{ collect($labOrders)->where('status', 'completed')->count() }}</p>
                    <p class="text-xs text-gray-500 mt-0.5">Completed</p>
                </div>
                <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-4 text-center">
                    <p class="text-2xl font-bold text-amber-600">{{ collect($testRequests)->where('status', 'PENDING')->count() }}</p>
                    <p class="text-xs text-gray-500 mt-0.5">Pending Requests</p>
                </div>
                <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-4 text-center">
                    <p class="text-2xl font-bold text-violet-600">{{ count($certificates) }}</p>
                    <p class="text-xs text-gray-500 mt-0.5">Certificates</p>
                </div>
            </div>
        </div>

        {{-- ============ MAIN CONTENT ============ --}}
        <div class="flex-1 min-w-0">

            {{-- ===== LAB RESULTS TAB ===== --}}
            @if($activeTab === 'results')
            <div>
                {{-- Page Header --}}
                <div class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden mb-5">
                    <div class="bg-blue-500 px-6 py-5">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 rounded-xl bg-white/20 backdrop-blur-sm flex items-center justify-center">
                                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"/>
                                    </svg>
                                </div>
                                <div>
                                    <h2 class="text-lg font-bold text-white">Lab Results</h2>
                                    <p class="text-sm text-white/70">View your lab test orders and results</p>
                                </div>
                            </div>
                            <div class="text-right">
                                <p class="text-2xl font-bold text-white">{{ count($labOrders) }}</p>
                                <p class="text-xs text-white/60">Total Orders</p>
                            </div>
                        </div>
                    </div>

                    @if(count($labOrders) > 0)
                    {{-- Summary Stats Row --}}
                    <div class="grid grid-cols-3 divide-x divide-gray-100">
                        <div class="px-5 py-4 text-center">
                            <div class="flex items-center justify-center gap-2">
                                <div class="w-2.5 h-2.5 rounded-full bg-amber-400"></div>
                                <span class="text-xl font-bold text-gray-900">{{ collect($labOrders)->where('status', 'pending')->count() }}</span>
                            </div>
                            <p class="text-xs text-gray-500 mt-0.5">Pending</p>
                        </div>
                        <div class="px-5 py-4 text-center">
                            <div class="flex items-center justify-center gap-2">
                                <div class="w-2.5 h-2.5 rounded-full bg-blue-400"></div>
                                <span class="text-xl font-bold text-gray-900">{{ collect($labOrders)->filter(fn($o) => !in_array($o->status, ['completed','cancelled','pending']))->count() }}</span>
                            </div>
                            <p class="text-xs text-gray-500 mt-0.5">In Progress</p>
                        </div>
                        <div class="px-5 py-4 text-center">
                            <div class="flex items-center justify-center gap-2">
                                <div class="w-2.5 h-2.5 rounded-full bg-emerald-400"></div>
                                <span class="text-xl font-bold text-gray-900">{{ collect($labOrders)->where('status', 'completed')->count() }}</span>
                            </div>
                            <p class="text-xs text-gray-500 mt-0.5">Completed</p>
                        </div>
                    </div>
                    @endif
                </div>

                @if(count($labOrders) > 0)
                    {{-- Search & Filter Bar --}}
                    <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-4 mb-4">
                        <div class="flex flex-col sm:flex-row gap-3">
                            <div class="flex-1 relative">
                                <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                                </svg>
                                <input type="text" wire:model.live.debounce.300ms="orderSearch"
                                       placeholder="Search by order #, physician, or test name..."
                                       class="w-full pl-10 pr-4 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent focus:bg-white transition-all">
                            </div>
                            <select wire:model.live="orderStatusFilter"
                                    class="px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent focus:bg-white transition-all min-w-[150px]">
                                <option value="">All Statuses</option>
                                <option value="pending">Pending</option>
                                <option value="in_progress">In Progress</option>
                                <option value="completed">Completed</option>
                                <option value="cancelled">Cancelled</option>
                            </select>
                        </div>
                    </div>

                    {{-- Orders List as Cards --}}
                    @php
                        $filtered = collect($labOrders)->filter(function($order) {
                            // Status filter
                            if ($this->orderStatusFilter) {
                                if ($this->orderStatusFilter === 'in_progress') {
                                    if (in_array($order->status, ['completed', 'cancelled', 'pending'])) return false;
                                } else {
                                    if ($order->status !== $this->orderStatusFilter) return false;
                                }
                            }
                            // Search filter
                            if ($this->orderSearch) {
                                $search = strtolower($this->orderSearch);
                                $haystack = strtolower(
                                    $order->lab_test_order_id . ' ' .
                                    ($order->physician->physician_name ?? '') . ' ' .
                                    $order->orderTests->map(fn($ot) => $ot->test->label ?? '')->implode(' ')
                                );
                                if (!str_contains($haystack, $search)) return false;
                            }
                            return true;
                        });
                        $totalFilteredOrders = $filtered->count();
                        $totalOrderPages = (int) ceil($totalFilteredOrders / $ordersPerPage);
                        $paginatedOrders = $filtered->forPage($ordersPage, $ordersPerPage);
                    @endphp

                    @if($filtered->isEmpty())
                        <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-10 text-center">
                            <div class="w-14 h-14 rounded-full bg-gray-100 flex items-center justify-center mx-auto mb-3">
                                <svg class="w-7 h-7 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                            </div>
                            <h3 class="text-gray-600 font-semibold mb-1">No matching orders</h3>
                            <p class="text-gray-400 text-sm">Try adjusting your search or filter criteria.</p>
                        </div>
                    @else
                    <div class="space-y-3">
                        @foreach($paginatedOrders as $order)
                        @php
                            $totalTests = $order->orderTests->count();
                            $completedTests = $order->orderTests->where('status', 'completed')->count();
                            $pctComplete = $totalTests > 0 ? round($completedTests/$totalTests*100) : 0;
                            $releasedDate = $order->orderTests
                                ->filter(fn($ot) => $ot->labResult && $ot->labResult->result_date)
                                ->map(fn($ot) => $ot->labResult->result_date)
                                ->sortDesc()
                                ->first();
                            $testNames = $order->orderTests->map(fn($ot) => $ot->test->label ?? 'Unknown')->take(3);
                            $extraTests = $totalTests - 3;
                            $statusProps = match($order->status) {
                                'completed' => ['class' => 'bg-emerald-50 text-emerald-700 border-emerald-200', 'icon' => 'M5 13l4 4L19 7', 'dot' => 'bg-emerald-400', 'label' => 'Completed', 'accent' => 'border-l-emerald-400'],
                                'cancelled' => ['class' => 'bg-red-50 text-red-700 border-red-200', 'icon' => 'M6 18L18 6M6 6l12 12', 'dot' => 'bg-red-400', 'label' => 'Cancelled', 'accent' => 'border-l-red-400'],
                                'pending' => ['class' => 'bg-amber-50 text-amber-700 border-amber-200', 'icon' => 'M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z', 'dot' => 'bg-amber-400', 'label' => 'Pending', 'accent' => 'border-l-amber-400'],
                                default => ['class' => 'bg-blue-50 text-blue-700 border-blue-200', 'icon' => 'M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15', 'dot' => 'bg-blue-400', 'label' => 'In Progress', 'accent' => 'border-l-blue-400'],
                            };
                        @endphp
                        <div wire:click="viewOrder({{ $order->lab_test_order_id }})"
                             class="bg-white rounded-2xl shadow-sm border border-gray-200 border-l-4 {{ $statusProps['accent'] }} hover:shadow-md hover:border-gray-300 transition-all cursor-pointer group">

                            {{-- Card Top Row --}}
                            <div class="px-5 pt-4 pb-3 flex items-start justify-between gap-4">
                                <div class="flex items-start gap-3.5 min-w-0">
                                    {{-- Order Icon --}}
                                    <div class="w-10 h-10 rounded-xl bg-blue-500 flex items-center justify-center flex-shrink-0 shadow-sm shadow-blue-500/20 group-hover:shadow-md group-hover:shadow-blue-500/30 transition-shadow">
                                        <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                                        </svg>
                                    </div>
                                    <div class="min-w-0">
                                        <div class="flex items-center gap-2 flex-wrap">
                                            <h3 class="text-sm font-bold text-gray-900">Order #{{ $order->lab_test_order_id }}</h3>
                                            <span class="inline-flex items-center gap-1 px-2 py-0.5 text-[11px] font-semibold rounded-full border {{ $statusProps['class'] }}">
                                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $statusProps['icon'] }}"/></svg>
                                                {{ $statusProps['label'] }}
                                            </span>
                                            @php $payBadge = $order->payment_badge; @endphp
                                            <span class="inline-flex items-center gap-1 px-2 py-0.5 text-[11px] font-semibold rounded-full {{ $payBadge['class'] }}">
                                                @if($order->payment_status === 'PAID')
                                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                                @else
                                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z"/></svg>
                                                @endif
                                                {{ $payBadge['label'] }}
                                            </span>
                                        </div>
                                        @if($order->physician)
                                            <p class="text-xs text-gray-500 mt-0.5">
                                                <span class="text-gray-400">Physician:</span>
                                                Dr. {{ $order->physician->physician_name }}
                                            </p>
                                        @endif
                                    </div>
                                </div>
                                {{-- Arrow indicator --}}
                                <svg class="w-5 h-5 text-gray-300 group-hover:text-blue-400 group-hover:translate-x-0.5 transition-all flex-shrink-0 mt-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                                </svg>
                            </div>

                            {{-- Test Names --}}
                            <div class="px-5 pb-3">
                                <div class="flex flex-wrap gap-1.5">
                                    @foreach($testNames as $name)
                                        <span class="inline-flex items-center px-2.5 py-1 bg-gray-50 border border-gray-100 rounded-lg text-xs text-gray-600 font-medium">
                                            <svg class="w-3 h-3 text-gray-400 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z"/></svg>
                                            {{ $name }}
                                        </span>
                                    @endforeach
                                    @if($extraTests > 0)
                                        <span class="inline-flex items-center px-2.5 py-1 bg-blue-50 border border-blue-100 rounded-lg text-xs text-blue-600 font-medium">
                                            +{{ $extraTests }} more
                                        </span>
                                    @endif
                                </div>
                            </div>

                            {{-- Card Bottom Row --}}
                            <div class="px-5 py-3 bg-gray-50/50 border-t border-gray-100 rounded-b-2xl flex items-center justify-between gap-4">
                                <div class="flex items-center gap-4 text-xs text-gray-500">
                                    <div class="flex items-center gap-1.5">
                                        <svg class="w-3.5 h-3.5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                        <span>{{ $order->order_date ? $order->order_date->format('M d, Y') : '—' }}</span>
                                    </div>
                                    @if($releasedDate)
                                    <div class="flex items-center gap-1.5">
                                        <svg class="w-3.5 h-3.5 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                        <span class="text-emerald-600 font-medium">Released {{ $releasedDate->format('M d, Y') }}</span>
                                    </div>
                                    @endif
                                </div>
                                {{-- Progress --}}
                                <div class="flex items-center gap-2">
                                    <span class="text-xs font-semibold {{ $pctComplete === 100 ? 'text-emerald-600' : 'text-gray-500' }}">{{ $completedTests }}/{{ $totalTests }}</span>
                                    <div class="w-20 h-1.5 bg-gray-200 rounded-full overflow-hidden">
                                        <div class="h-full rounded-full transition-all duration-500 {{ $pctComplete === 100 ? 'bg-emerald-400' : 'bg-blue-500' }}" 
                                             style="width: {{ $pctComplete }}%"></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        @endforeach
                    </div>

                    {{-- Pagination --}}
                    @if($totalOrderPages > 1)
                    <div class="flex items-center justify-between mt-4 px-1">
                        <p class="text-sm text-gray-500">
                            Showing {{ ($ordersPage - 1) * $ordersPerPage + 1 }}–{{ min($ordersPage * $ordersPerPage, $totalFilteredOrders) }} of {{ $totalFilteredOrders }} orders
                        </p>
                        <div class="flex items-center gap-2">
                            <button wire:click="prevOrdersPage" @disabled($ordersPage <= 1)
                                    class="inline-flex items-center gap-1.5 px-3 py-2 text-sm font-medium rounded-xl border transition-all
                                        {{ $ordersPage <= 1 ? 'border-gray-100 text-gray-300 bg-gray-50 cursor-not-allowed' : 'border-gray-200 text-gray-600 bg-white hover:bg-gray-50 hover:border-gray-300' }}">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                                Prev
                            </button>
                            <span class="px-3 py-2 text-sm font-semibold text-gray-700 bg-blue-50 border border-blue-100 rounded-xl">
                                {{ $ordersPage }} / {{ $totalOrderPages }}
                            </span>
                            <button wire:click="nextOrdersPage" @disabled($ordersPage >= $totalOrderPages)
                                    class="inline-flex items-center gap-1.5 px-3 py-2 text-sm font-medium rounded-xl border transition-all
                                        {{ $ordersPage >= $totalOrderPages ? 'border-gray-100 text-gray-300 bg-gray-50 cursor-not-allowed' : 'border-gray-200 text-gray-600 bg-white hover:bg-gray-50 hover:border-gray-300' }}">
                                Next
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                            </button>
                        </div>
                    </div>
                    @endif
                    @endif
                @else
                    {{-- Empty State --}}
                    <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-16 text-center">
                        <div class="w-20 h-20 rounded-2xl bg-blue-50 flex items-center justify-center mx-auto mb-5">
                            <svg class="w-10 h-10 text-blue-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"/>
                            </svg>
                        </div>
                        <h3 class="text-lg font-bold text-gray-900 mb-2">No Lab Results Yet</h3>
                        <p class="text-gray-500 text-sm leading-relaxed max-w-sm mx-auto">Your lab test orders and results will appear here once they are processed by the laboratory team.</p>
                    </div>
                @endif
            </div>
            @endif

            {{-- ===== CERTIFICATES TAB ===== --}}
            @if($activeTab === 'certificates')
            <div>
                <div class="flex items-center justify-between mb-5">
                    <h2 class="text-xl font-bold text-gray-900">My Certificates</h2>
                </div>

                {{-- Filters --}}
                <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-4 mb-5">
                    <div class="flex flex-col sm:flex-row gap-3">
                        <div class="flex-1">
                            <div class="relative">
                                <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                                <input type="text" wire:model.live.debounce.300ms="certSearch" placeholder="Search by certificate number..."
                                       class="w-full pl-10 pr-4 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent focus:bg-white transition-all">
                            </div>
                        </div>
                        <select wire:model.live="certFilterType"
                                class="px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent focus:bg-white transition-all">
                            <option value="">All Types</option>
                            <option value="calibration">Calibration</option>
                            <option value="maintenance">Maintenance</option>
                            <option value="lab_result">Lab Result</option>
                            <option value="safety">Safety Compliance</option>
                        </select>
                        <select wire:model.live="certFilterStatus"
                                class="px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent focus:bg-white transition-all">
                            <option value="">All Statuses</option>
                            <option value="issued">Issued</option>
                            <option value="revoked">Revoked</option>
                            <option value="expired">Expired</option>
                            <option value="draft">Draft</option>
                        </select>
                    </div>
                </div>

                @if(count($certificates) > 0)
                    <div class="space-y-3">
                        @foreach($certificates as $cert)
                        <div wire:click="viewCertificate('{{ $cert['source'] }}', {{ $cert['id'] }})"
                             class="bg-white rounded-2xl shadow-sm border border-gray-200 hover:border-blue-300 hover:shadow-md cursor-pointer transition-all overflow-hidden group">
                            <div class="flex items-center gap-4 p-5">
                                {{-- Icon --}}
                                <div class="flex-shrink-0">
                                    @php
                                        $iconBg = match(strtolower($cert['type'])) {
                                            'calibration' => 'bg-violet-100',
                                            'maintenance' => 'bg-amber-100',
                                            'lab_result', 'lab result' => 'bg-emerald-100',
                                            'safety', 'safety compliance' => 'bg-red-100',
                                            default => 'bg-blue-100',
                                        };
                                        $iconColor = match(strtolower($cert['type'])) {
                                            'calibration' => 'text-violet-600',
                                            'maintenance' => 'text-amber-600',
                                            'lab_result', 'lab result' => 'text-emerald-600',
                                            'safety', 'safety compliance' => 'text-red-600',
                                            default => 'text-blue-600',
                                        };
                                    @endphp
                                    <div class="w-12 h-12 rounded-xl {{ $iconBg }} flex items-center justify-center">
                                        <svg class="w-6 h-6 {{ $iconColor }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z"/>
                                        </svg>
                                    </div>
                                </div>

                                {{-- Info --}}
                                <div class="flex-1 min-w-0">
                                    <div class="flex items-center gap-2 mb-1">
                                        <h3 class="text-sm font-semibold text-gray-900 truncate">{{ $cert['number'] }}</h3>
                                        @php
                                            $sBg = match(strtolower($cert['status'])) {
                                                'issued' => 'bg-emerald-100 text-emerald-700',
                                                'revoked' => 'bg-red-100 text-red-700',
                                                'expired' => 'bg-gray-100 text-gray-600',
                                                'draft' => 'bg-amber-100 text-amber-700',
                                                default => 'bg-blue-100 text-blue-700',
                                            };
                                        @endphp
                                        <span class="px-2 py-0.5 text-xs font-medium rounded-full {{ $sBg }}">{{ ucfirst($cert['status']) }}</span>
                                    </div>
                                    <div class="flex items-center gap-4 text-xs text-gray-500">
                                        <span class="flex items-center gap-1">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/></svg>
                                            {{ $cert['type'] }}
                                        </span>
                                        @if($cert['issue_date'])
                                        <span class="flex items-center gap-1">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                            {{ $cert['issue_date'] }}
                                        </span>
                                        @endif
                                        @if($cert['equipment'])
                                        <span class="flex items-center gap-1">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z"/></svg>
                                            {{ $cert['equipment'] }}
                                        </span>
                                        @endif
                                    </div>
                                </div>

                                {{-- Arrow --}}
                                <svg class="w-5 h-5 text-gray-300 group-hover:text-blue-500 transition-colors flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                                </svg>
                            </div>

                            {{-- Valid Until Bar --}}
                            @if($cert['valid_until'])
                            <div class="px-5 py-2 bg-gray-50 border-t border-gray-100 text-xs text-gray-500">
                                <span class="font-medium">Valid Until:</span> {{ $cert['valid_until'] }}
                            </div>
                            @endif
                        </div>
                        @endforeach
                    </div>
                @else
                    <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-12 text-center">
                        <div class="w-16 h-16 rounded-full bg-gray-100 flex items-center justify-center mx-auto mb-4">
                            <svg class="w-8 h-8 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z"/>
                            </svg>
                        </div>
                        <h3 class="text-gray-600 font-semibold mb-1">No Certificates Found</h3>
                        <p class="text-gray-400 text-sm">Your certificates will appear here once they are issued by the laboratory.</p>
                    </div>
                @endif
            </div>
            @endif

            {{-- ===== VERIFY TAB ===== --}}
            @if($activeTab === 'verify')
            <div>
                <div class="flex items-center justify-between mb-5">
                    <h2 class="text-xl font-bold text-gray-900">Verify Certificate</h2>
                </div>

                <div class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden">
                    <div class="p-6">
                        <div class="max-w-lg mx-auto text-center">
                            <div class="w-16 h-16 rounded-full bg-blue-100 flex items-center justify-center mx-auto mb-4">
                                <svg class="w-8 h-8 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                                </svg>
                            </div>
                            <h3 class="text-lg font-semibold text-gray-900 mb-2">Certificate Verification</h3>
                            <p class="text-sm text-gray-500 mb-6">Enter a certificate number or verification code to check its authenticity and validity.</p>

                            <div class="flex gap-3">
                                <div class="flex-1 relative">
                                    <input type="text" wire:model="verifyCode" wire:keydown.enter="verifyCertificate"
                                           placeholder="e.g. CERT-2026-00001 or verification code"
                                           class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent focus:bg-white transition-all">
                                </div>
                                <button wire:click="verifyCertificate"
                                        class="px-6 py-3 bg-blue-500 hover:bg-blue-600 text-white font-medium text-sm rounded-xl shadow-sm shadow-blue-500/25 transition-all flex items-center gap-2">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                                    Verify
                                </button>
                            </div>
                        </div>
                    </div>

                    {{-- Verification Results --}}
                    @if($verifyResult)
                    <div class="border-t border-gray-200">
                        @if($verifyResult['status'] === 'found')
                            <div class="p-6">
                                <div class="max-w-lg mx-auto">
                                    {{-- Status Banner --}}
                                    @if($verifyResult['valid'])
                                    <div class="flex items-center gap-3 p-4 mb-5 rounded-xl bg-emerald-50 border border-emerald-200">
                                        <div class="w-10 h-10 rounded-full bg-emerald-100 flex items-center justify-center flex-shrink-0">
                                            <svg class="w-5 h-5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                        </div>
                                        <div>
                                            <p class="font-semibold text-emerald-800">Certificate is Valid</p>
                                            <p class="text-xs text-emerald-600">This certificate has been verified and is currently active.</p>
                                        </div>
                                    </div>
                                    @else
                                    <div class="flex items-center gap-3 p-4 mb-5 rounded-xl bg-red-50 border border-red-200">
                                        <div class="w-10 h-10 rounded-full bg-red-100 flex items-center justify-center flex-shrink-0">
                                            <svg class="w-5 h-5 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                        </div>
                                        <div>
                                            <p class="font-semibold text-red-800">Certificate is Invalid</p>
                                            <p class="text-xs text-red-600">This certificate is {{ strtolower($verifyResult['cert_status']) }} or has expired.</p>
                                        </div>
                                    </div>
                                    @endif

                                    {{-- Certificate Details --}}
                                    <div class="bg-gray-50 rounded-xl overflow-hidden">
                                        <div class="divide-y divide-gray-200/60">
                                            <div class="flex justify-between items-center px-5 py-3">
                                                <span class="text-sm text-gray-500">Certificate No.</span>
                                                <span class="text-sm font-semibold text-gray-900 font-mono">{{ $verifyResult['number'] }}</span>
                                            </div>
                                            <div class="flex justify-between items-center px-5 py-3">
                                                <span class="text-sm text-gray-500">Type</span>
                                                <span class="text-sm font-medium text-gray-900">{{ $verifyResult['type'] }}</span>
                                            </div>
                                            <div class="flex justify-between items-center px-5 py-3">
                                                <span class="text-sm text-gray-500">Status</span>
                                                @php
                                                    $vsBg = match(strtolower($verifyResult['cert_status'])) {
                                                        'issued' => 'bg-emerald-100 text-emerald-700',
                                                        'revoked' => 'bg-red-100 text-red-700',
                                                        'expired' => 'bg-gray-100 text-gray-600',
                                                        default => 'bg-amber-100 text-amber-700',
                                                    };
                                                @endphp
                                                <span class="px-2.5 py-0.5 text-xs font-medium rounded-full {{ $vsBg }}">{{ ucfirst($verifyResult['cert_status']) }}</span>
                                            </div>
                                            @if($verifyResult['issue_date'])
                                            <div class="flex justify-between items-center px-5 py-3">
                                                <span class="text-sm text-gray-500">Issue Date</span>
                                                <span class="text-sm font-medium text-gray-900">{{ $verifyResult['issue_date'] }}</span>
                                            </div>
                                            @endif
                                            @if($verifyResult['valid_until'])
                                            <div class="flex justify-between items-center px-5 py-3">
                                                <span class="text-sm text-gray-500">Valid Until</span>
                                                <span class="text-sm font-medium text-gray-900">{{ $verifyResult['valid_until'] }}</span>
                                            </div>
                                            @endif
                                            @if($verifyResult['patient'])
                                            <div class="flex justify-between items-center px-5 py-3">
                                                <span class="text-sm text-gray-500">Patient</span>
                                                <span class="text-sm font-medium text-gray-900">{{ $verifyResult['patient'] }}</span>
                                            </div>
                                            @endif
                                            @if($verifyResult['issued_by'])
                                            <div class="flex justify-between items-center px-5 py-3">
                                                <span class="text-sm text-gray-500">Issued By</span>
                                                <span class="text-sm font-medium text-gray-900">{{ $verifyResult['issued_by'] }}</span>
                                            </div>
                                            @endif
                                        </div>
                                    </div>

                                    <div class="mt-4 text-center">
                                        <button wire:click="clearVerification" class="text-sm text-blue-600 hover:text-blue-800 font-medium">
                                            Verify Another Certificate
                                        </button>
                                    </div>
                                </div>
                            </div>
                        @elseif($verifyResult['status'] === 'not_found')
                            <div class="p-6 text-center">
                                <div class="max-w-lg mx-auto">
                                    <div class="flex items-center gap-3 p-4 rounded-xl bg-amber-50 border border-amber-200">
                                        <div class="w-10 h-10 rounded-full bg-amber-100 flex items-center justify-center flex-shrink-0">
                                            <svg class="w-5 h-5 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z"/></svg>
                                        </div>
                                        <div class="text-left">
                                            <p class="font-semibold text-amber-800">Certificate Not Found</p>
                                            <p class="text-xs text-amber-600">{{ $verifyResult['message'] }}</p>
                                        </div>
                                    </div>
                                    <div class="mt-4">
                                        <button wire:click="clearVerification" class="text-sm text-blue-600 hover:text-blue-800 font-medium">
                                            Try Again
                                        </button>
                                    </div>
                                </div>
                            </div>
                        @elseif($verifyResult['status'] === 'error')
                            <div class="p-6 text-center">
                                <div class="max-w-lg mx-auto">
                                    <div class="flex items-center gap-3 p-4 rounded-xl bg-red-50 border border-red-200">
                                        <div class="w-10 h-10 rounded-full bg-red-100 flex items-center justify-center flex-shrink-0">
                                            <svg class="w-5 h-5 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                        </div>
                                        <p class="text-sm text-red-700 text-left">{{ $verifyResult['message'] }}</p>
                                    </div>
                                </div>
                            </div>
                        @endif
                    </div>
                    @endif
                </div>
            </div>
            @endif

            {{-- ===== PROFILE TAB ===== --}}
            @if($activeTab === 'profile')
            <div>
                <div class="flex items-center justify-between mb-5">
                    <h2 class="text-xl font-bold text-gray-900">My Profile</h2>
                    @if(!$editMode)
                        <button wire:click="startEdit"
                                class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium text-blue-600 bg-blue-50 hover:bg-blue-100 rounded-xl transition-colors">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                            Edit Profile
                        </button>
                    @endif
                </div>

                @if(session('profile_saved'))
                    <div class="mb-5 px-4 py-3 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-700 text-sm flex items-center gap-2">
                        <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                        {{ session('profile_saved') }}
                    </div>
                @endif

                {{-- UIC Directory Info (read-only) --}}
                @if($directoryRecord)
                <div class="bg-gradient-to-r from-blue-50 to-indigo-50 rounded-2xl border border-blue-200 overflow-hidden mb-5">
                    <div class="px-6 py-3 bg-blue-100/60 border-b border-blue-200 flex items-center gap-2">
                        <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                        <span class="text-sm font-semibold text-blue-800">UIC Official Record</span>
                        <span class="ml-auto text-xs text-blue-500">Synced from university directory</span>
                    </div>
                    <div class="divide-y divide-blue-100">
                        <div class="flex items-center px-6 py-3">
                            <span class="text-sm text-blue-600 w-44 flex-shrink-0">ID Number</span>
                            <span class="text-sm font-semibold text-gray-900 font-mono">{{ $directoryRecord->external_ref_id }}</span>
                        </div>
                        <div class="flex items-center px-6 py-3">
                            <span class="text-sm text-blue-600 w-44 flex-shrink-0">Full Name</span>
                            <span class="text-sm font-medium text-gray-900">{{ $directoryRecord->full_name }}</span>
                        </div>
                        <div class="flex items-center px-6 py-3">
                            <span class="text-sm text-blue-600 w-44 flex-shrink-0">Email</span>
                            <span class="text-sm font-medium text-gray-900">{{ $directoryRecord->email }}</span>
                        </div>
                        <div class="flex items-center px-6 py-3">
                            <span class="text-sm text-blue-600 w-44 flex-shrink-0">Type</span>
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold {{ $directoryRecord->type === 'student' ? 'bg-indigo-100 text-indigo-800' : 'bg-emerald-100 text-emerald-800' }}">{{ ucfirst($directoryRecord->type) }}</span>
                        </div>
                        @if($directoryRecord->department_or_course)
                        <div class="flex items-center px-6 py-3">
                            <span class="text-sm text-blue-600 w-44 flex-shrink-0">{{ $directoryRecord->type === 'student' ? 'Course' : 'Department' }}</span>
                            <span class="text-sm font-medium text-gray-900">{{ $directoryRecord->department_or_course }}</span>
                        </div>
                        @endif
                    </div>
                </div>
                @endif

                <div class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden">
                    @if(!$editMode)
                        {{-- View Mode --}}
                        <div class="divide-y divide-gray-100">
                            @php
                                $fields = [
                                    ['label' => 'First Name', 'value' => $patient->firstname, 'icon' => 'M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z'],
                                    ['label' => 'Middle Name', 'value' => $patient->middlename ?: '—', 'icon' => 'M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z'],
                                    ['label' => 'Last Name', 'value' => $patient->lastname, 'icon' => 'M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z'],
                                    ['label' => 'Gender', 'value' => $patient->gender ?: '—', 'icon' => 'M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z'],
                                    ['label' => 'Date of Birth', 'value' => $patient->birthdate ? $patient->birthdate->format('F d, Y') . ' (' . $patient->birthdate->age . ' years old)' : '—', 'icon' => 'M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z'],
                                    ['label' => 'Email', 'value' => $patient->email ?? auth()->user()->email, 'icon' => 'M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z'],
                                    ['label' => 'Contact Number', 'value' => $patient->contact_number ?: '—', 'icon' => 'M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z'],
                                    ['label' => 'Address', 'value' => $patient->address ?: '—', 'icon' => 'M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0zM15 11a3 3 0 11-6 0 3 3 0 016 0z'],
                                    ['label' => 'Patient Type', 'value' => $patient->patient_type, 'icon' => 'M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z'],
                                ];
                            @endphp

                            @foreach($fields as $field)
                            <div class="flex items-center px-6 py-4">
                                <div class="flex items-center gap-3 w-44 flex-shrink-0">
                                    <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $field['icon'] }}"/></svg>
                                    <span class="text-sm text-gray-500">{{ $field['label'] }}</span>
                                </div>
                                <span class="text-sm font-medium text-gray-900">{{ $field['value'] }}</span>
                            </div>
                            @endforeach
                        </div>
                    @else
                        {{-- Edit Mode --}}
                        <form wire:submit.prevent="saveProfile" class="p-6 space-y-5">
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1.5">First Name <span class="text-red-400">*</span></label>
                                    <input type="text" wire:model="editFirstname"
                                           class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent focus:bg-white transition-all">
                                    @error('editFirstname') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1.5">Middle Name</label>
                                    <input type="text" wire:model="editMiddlename"
                                           class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent focus:bg-white transition-all">
                                    @error('editMiddlename') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1.5">Last Name <span class="text-red-400">*</span></label>
                                    <input type="text" wire:model="editLastname"
                                           class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent focus:bg-white transition-all">
                                    @error('editLastname') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1.5">Gender <span class="text-red-400">*</span></label>
                                    <select wire:model="editGender"
                                            class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent focus:bg-white transition-all">
                                        <option value="">Select...</option>
                                        <option value="Male">Male</option>
                                        <option value="Female">Female</option>
                                    </select>
                                    @error('editGender') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1.5">Date of Birth <span class="text-red-400">*</span></label>
                                    <input type="date" wire:model="editBirthdate"
                                           class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent focus:bg-white transition-all">
                                    @error('editBirthdate') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1.5">Contact Number</label>
                                    <input type="text" wire:model="editContact" placeholder="09xx-xxx-xxxx"
                                           class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent focus:bg-white transition-all">
                                    @error('editContact') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                                </div>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1.5">Address</label>
                                <textarea wire:model="editAddress" rows="2" placeholder="Enter your complete address"
                                          class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent focus:bg-white transition-all resize-none"></textarea>
                                @error('editAddress') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                            </div>

                            <div class="flex items-center justify-end gap-3 pt-3 border-t border-gray-100">
                                <button type="button" wire:click="cancelEdit"
                                        class="px-5 py-2.5 text-sm font-medium text-gray-700 bg-gray-100 hover:bg-gray-200 rounded-xl transition-colors">
                                    Cancel
                                </button>
                                <button type="submit"
                                        class="px-5 py-2.5 text-sm font-medium text-white bg-blue-500 hover:bg-blue-600 rounded-xl shadow-sm shadow-blue-500/25 transition-all">
                                    Save Changes
                                </button>
                            </div>
                        </form>
                    @endif
                </div>
            </div>
            @endif

            {{-- ===== REQUEST TEST TAB ===== --}}
            @if($activeTab === 'requests')
            <div>
                {{-- Flash Messages --}}
                @if(session('request_submitted'))
                <div class="mb-5 p-4 bg-emerald-50 border border-emerald-200 rounded-xl flex items-center gap-3">
                    <svg class="w-5 h-5 text-emerald-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    <p class="text-sm text-emerald-700 font-medium">{{ session('request_submitted') }}</p>
                </div>
                @endif
                @if(session('request_cancelled'))
                <div class="mb-5 p-4 bg-gray-50 border border-gray-200 rounded-xl flex items-center gap-3">
                    <svg class="w-5 h-5 text-gray-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    <p class="text-sm text-gray-700 font-medium">{{ session('request_cancelled') }}</p>
                </div>
                @endif

                {{-- Page Header --}}
                <div class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden mb-5">
                    <div class="bg-blue-500 px-6 py-5">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 rounded-xl bg-white/20 backdrop-blur-sm flex items-center justify-center">
                                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v3m0 0v3m0-3h3m-3 0H9m12 0a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                    </svg>
                                </div>
                                <div>
                                    <h2 class="text-lg font-bold text-white">Test Requests</h2>
                                    <p class="text-sm text-white/70">Request lab tests for staff review</p>
                                </div>
                            </div>
                            <button wire:click="openRequestForm"
                                    class="inline-flex items-center gap-2 px-4 py-2.5 bg-white/20 hover:bg-white/30 backdrop-blur-sm text-white text-sm font-medium rounded-xl transition-all">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                                New Request
                            </button>
                        </div>
                    </div>

                    {{-- Status Summary --}}
                    @if(count($testRequests) > 0)
                    <div class="grid grid-cols-4 divide-x divide-gray-100">
                        <div class="px-4 py-3 text-center">
                            <span class="text-lg font-bold text-gray-900">{{ count($testRequests) }}</span>
                            <p class="text-xs text-gray-500">Total</p>
                        </div>
                        <div class="px-4 py-3 text-center">
                            <span class="text-lg font-bold text-amber-600">{{ collect($testRequests)->where('status', 'PENDING')->count() }}</span>
                            <p class="text-xs text-gray-500">Pending</p>
                        </div>
                        <div class="px-4 py-3 text-center">
                            <span class="text-lg font-bold text-emerald-600">{{ collect($testRequests)->where('status', 'APPROVED')->count() }}</span>
                            <p class="text-xs text-gray-500">Approved</p>
                        </div>
                        <div class="px-4 py-3 text-center">
                            <span class="text-lg font-bold text-red-600">{{ collect($testRequests)->where('status', 'REJECTED')->count() }}</span>
                            <p class="text-xs text-gray-500">Rejected</p>
                        </div>
                    </div>
                    @endif
                </div>

                {{-- Request Form Modal --}}
                @if($showRequestForm)
                <div class="fixed inset-0 bg-black/40 backdrop-blur-sm flex items-center justify-center z-50 p-4" wire:click.self="closeRequestForm">
                    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-2xl max-h-[90vh] flex flex-col overflow-hidden">
                        {{-- Modal Header --}}
                        <div class="px-6 py-4 bg-blue-500 flex items-center justify-between flex-shrink-0">
                            <div class="flex items-center gap-3">
                                <div class="w-9 h-9 rounded-xl bg-white/20 flex items-center justify-center">
                                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v3m0 0v3m0-3h3m-3 0H9m12 0a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                </div>
                                <h3 class="text-lg font-bold text-white">Request Laboratory Test</h3>
                            </div>
                            <button wire:click="closeRequestForm" class="w-8 h-8 flex items-center justify-center rounded-lg text-white/60 hover:text-white hover:bg-white/20 transition-colors">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                            </button>
                        </div>

                        {{-- Modal Body --}}
                        <div class="flex-1 overflow-y-auto p-6 space-y-5">
                            {{-- Purpose --}}
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-1.5">Purpose <span class="text-gray-400 font-normal">(optional)</span></label>
                                <textarea wire:model="requestPurpose" rows="2" placeholder="e.g., Pre-employment medical, Annual checkup, School requirement..."
                                    class="w-full rounded-xl border-gray-200 focus:border-blue-500 focus:ring-blue-500 text-sm px-4 py-3 placeholder-gray-400"></textarea>
                                @error('requestPurpose') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                            </div>

                            {{-- Preferred Date --}}
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-1.5">Preferred Date <span class="text-gray-400 font-normal">(optional)</span></label>
                                <input type="date" wire:model="requestPreferredDate" min="{{ date('Y-m-d') }}"
                                    class="w-full rounded-xl border-gray-200 focus:border-blue-500 focus:ring-blue-500 text-sm px-4 py-3">
                                @error('requestPreferredDate') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                            </div>

                            {{-- Test Selection --}}
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-1.5">Select Tests <span class="text-red-500">*</span></label>
                                @error('requestSelectedTests') <p class="text-xs text-red-500 mb-2">{{ $message }}</p> @enderror

                                {{-- Selected Tests Preview --}}
                                @if(count($requestSelectedTests) > 0)
                                <div class="mb-3 flex flex-wrap gap-2">
                                    @foreach($availableTests->whereIn('test_id', $requestSelectedTests) as $selectedTest)
                                    <span class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-blue-50 text-blue-700 text-xs font-medium rounded-lg border border-blue-200">
                                        {{ $selectedTest->label }}
                                        <button wire:click="toggleRequestTest({{ $selectedTest->test_id }})" class="text-blue-400 hover:text-blue-600">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                        </button>
                                    </span>
                                    @endforeach
                                    <span class="text-xs text-gray-500 self-center">{{ count($requestSelectedTests) }} selected</span>
                                </div>
                                @endif

                                {{-- Search --}}
                                <div class="relative mb-3">
                                    <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                                    <input type="text" wire:model.live.debounce.300ms="requestTestSearch" placeholder="Search tests..."
                                        class="w-full pl-10 pr-4 py-2.5 rounded-xl border-gray-200 focus:border-blue-500 focus:ring-blue-500 text-sm">
                                </div>

                                {{-- Test List --}}
                                <div class="border border-gray-200 rounded-xl max-h-60 overflow-y-auto divide-y divide-gray-100">
                                    @forelse($availableTests as $test)
                                    <label class="flex items-center gap-3 px-4 py-3 hover:bg-gray-50 cursor-pointer transition-colors {{ in_array($test->test_id, $requestSelectedTests) ? 'bg-blue-50/50' : '' }}">
                                        <input type="checkbox"
                                            wire:click="toggleRequestTest({{ $test->test_id }})"
                                            {{ in_array($test->test_id, $requestSelectedTests) ? 'checked' : '' }}
                                            class="rounded border-gray-300 text-blue-500 focus:ring-blue-500">
                                        <div class="flex-1 min-w-0">
                                            <p class="text-sm font-medium text-gray-900">{{ $test->label }}</p>
                                            <p class="text-xs text-gray-500">{{ $test->section->section_name ?? 'General' }}
                                                @if($test->current_price) &middot; ₱{{ number_format($test->current_price, 2) }} @endif
                                            </p>
                                        </div>
                                    </label>
                                    @empty
                                    <div class="px-4 py-8 text-center text-sm text-gray-500">
                                        @if($requestTestSearch)
                                            No tests found matching "{{ $requestTestSearch }}"
                                        @else
                                            No tests available
                                        @endif
                                    </div>
                                    @endforelse
                                </div>
                            </div>
                        </div>

                        {{-- Modal Footer --}}
                        <div class="px-6 py-4 bg-gray-50 border-t border-gray-200 flex items-center justify-between flex-shrink-0">
                            <button wire:click="closeRequestForm" class="px-5 py-2.5 text-sm font-medium text-gray-700 bg-white border border-gray-200 hover:bg-gray-100 rounded-xl transition-colors">
                                Cancel
                            </button>
                            <button wire:click="submitTestRequest"
                                    class="inline-flex items-center gap-2 px-5 py-2.5 text-sm font-medium text-white bg-blue-500 hover:bg-blue-600 rounded-xl shadow-sm shadow-blue-500/25 transition-all"
                                    wire:loading.attr="disabled">
                                <svg wire:loading wire:target="submitTestRequest" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
                                <svg wire:loading.remove wire:target="submitTestRequest" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/></svg>
                                Submit Request
                            </button>
                        </div>
                    </div>
                </div>
                @endif

                {{-- Requests List --}}
                @if(count($testRequests) > 0)
                <div class="space-y-3">
                    @foreach($testRequests as $request)
                    <div class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden hover:shadow-md transition-shadow">
                        <div class="px-5 py-4">
                            <div class="flex items-start justify-between mb-3">
                                <div class="flex items-center gap-3">
                                    <div class="w-10 h-10 rounded-xl flex items-center justify-center text-sm font-bold
                                        {{ $request['status'] === 'PENDING' ? 'bg-amber-100 text-amber-600' : '' }}
                                        {{ $request['status'] === 'APPROVED' ? 'bg-emerald-100 text-emerald-600' : '' }}
                                        {{ $request['status'] === 'REJECTED' ? 'bg-red-100 text-red-600' : '' }}
                                        {{ $request['status'] === 'CANCELLED' ? 'bg-gray-100 text-gray-500' : '' }}">
                                        @if($request['status'] === 'PENDING')
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                        @elseif($request['status'] === 'APPROVED')
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                        @elseif($request['status'] === 'REJECTED')
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                        @else
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/></svg>
                                        @endif
                                    </div>
                                    <div>
                                        <p class="text-sm font-semibold text-gray-900">Request #{{ $request['id'] }}</p>
                                        <p class="text-xs text-gray-500">{{ \Carbon\Carbon::parse($request['datetime_added'])->format('M d, Y \a\t h:i A') }}</p>
                                    </div>
                                </div>
                                <span class="inline-flex px-2.5 py-1 text-xs font-semibold rounded-full
                                    {{ $request['status'] === 'PENDING' ? 'bg-amber-100 text-amber-700' : '' }}
                                    {{ $request['status'] === 'APPROVED' ? 'bg-emerald-100 text-emerald-700' : '' }}
                                    {{ $request['status'] === 'REJECTED' ? 'bg-red-100 text-red-700' : '' }}
                                    {{ $request['status'] === 'CANCELLED' ? 'bg-gray-100 text-gray-500' : '' }}">
                                    {{ $request['status'] }}
                                </span>
                            </div>

                            {{-- Requested Tests --}}
                            <div class="mb-3">
                                <div class="flex flex-wrap gap-1.5">
                                    @foreach($request['items'] as $item)
                                    <span class="inline-flex px-2.5 py-1 bg-gray-100 text-gray-700 text-xs font-medium rounded-lg">
                                        {{ $item['test']['label'] ?? 'Unknown' }}
                                    </span>
                                    @endforeach
                                </div>
                            </div>

                            {{-- Purpose --}}
                            @if($request['purpose'])
                            <p class="text-sm text-gray-600 mb-3">
                                <span class="font-medium text-gray-500">Purpose:</span> {{ $request['purpose'] }}
                            </p>
                            @endif

                            {{-- Preferred Date --}}
                            @if($request['preferred_date'])
                            <p class="text-xs text-gray-500 mb-3">
                                <span class="font-medium">Preferred date:</span> {{ \Carbon\Carbon::parse($request['preferred_date'])->format('M d, Y') }}
                            </p>
                            @endif

                            {{-- Staff Remarks (if rejected) --}}
                            @if($request['status'] === 'REJECTED' && $request['staff_remarks'])
                            <div class="p-3 bg-red-50 border border-red-100 rounded-xl mb-3">
                                <p class="text-xs font-semibold text-red-600 mb-1">Staff Remarks:</p>
                                <p class="text-sm text-red-700">{{ $request['staff_remarks'] }}</p>
                            </div>
                            @endif

                            {{-- Reviewed Info --}}
                            @if($request['reviewed_at'] && $request['reviewer'])
                            <p class="text-xs text-gray-400">
                                Reviewed by {{ $request['reviewer']['name'] }} on {{ \Carbon\Carbon::parse($request['reviewed_at'])->format('M d, Y \a\t h:i A') }}
                            </p>
                            @endif

                            {{-- Actions --}}
                            @if($request['status'] === 'PENDING')
                            <div class="mt-3 pt-3 border-t border-gray-100 flex items-center justify-end gap-2">
                                <button wire:click="cancelTestRequest({{ $request['id'] }})"
                                        wire:confirm="Are you sure you want to cancel this request?"
                                        class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium text-red-600 bg-red-50 hover:bg-red-100 rounded-lg transition-colors">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                    Cancel Request
                                </button>
                            </div>
                            @endif
                        </div>
                    </div>
                    @endforeach
                </div>
                @else
                {{-- Empty State --}}
                <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-12 text-center">
                    <div class="w-16 h-16 rounded-full bg-blue-50 flex items-center justify-center mx-auto mb-4">
                        <svg class="w-8 h-8 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v3m0 0v3m0-3h3m-3 0H9m12 0a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                    <h3 class="text-lg font-bold text-gray-900 mb-2">No Test Requests Yet</h3>
                    <p class="text-sm text-gray-500 mb-5">Submit a test request for staff review and approval.</p>
                    <button wire:click="openRequestForm"
                            class="inline-flex items-center gap-2 px-5 py-2.5 bg-blue-500 hover:bg-blue-600 text-white text-sm font-medium rounded-xl shadow-sm shadow-blue-500/25 transition-all">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                        Request a Test
                    </button>
                </div>
                @endif
            </div>
            @endif
        </div>
    </div>

    {{-- ===== RESULT DETAIL MODAL ===== --}}
    @if($selectedResult)
    <div class="fixed inset-0 bg-black/40 backdrop-blur-sm flex items-center justify-center z-50 p-4" wire:click.self="closeResult">
        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-lg overflow-hidden">
            <div class="px-6 py-4 bg-gray-50 border-b border-gray-200 flex items-center justify-between">
                <h3 class="text-lg font-bold text-gray-900">Result Details</h3>
                <button wire:click="closeResult" class="w-8 h-8 flex items-center justify-center rounded-lg text-gray-400 hover:text-gray-600 hover:bg-gray-200 transition-colors">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>
            <div class="p-6 space-y-4">
                <div class="grid grid-cols-2 gap-3">
                    <div class="bg-gray-50 p-3.5 rounded-xl">
                        <p class="text-xs font-medium text-gray-500 uppercase mb-1">Test</p>
                        <p class="text-sm font-semibold text-gray-900">{{ $selectedResult->test->label ?? 'N/A' }}</p>
                    </div>
                    <div class="bg-gray-50 p-3.5 rounded-xl">
                        <p class="text-xs font-medium text-gray-500 uppercase mb-1">Status</p>
                        <span class="px-2 py-0.5 text-xs font-medium rounded-full bg-emerald-100 text-emerald-700">{{ ucfirst($selectedResult->status) }}</span>
                    </div>
                    <div class="bg-gray-50 p-3.5 rounded-xl">
                        <p class="text-xs font-medium text-gray-500 uppercase mb-1">Result Value</p>
                        <p class="text-sm font-semibold text-gray-900">{{ $selectedResult->result_value ?? 'N/A' }}</p>
                    </div>
                    <div class="bg-gray-50 p-3.5 rounded-xl">
                        <p class="text-xs font-medium text-gray-500 uppercase mb-1">Normal Range</p>
                        <p class="text-sm font-semibold text-gray-900">{{ $selectedResult->normal_range ?? 'N/A' }}</p>
                    </div>
                    <div class="bg-gray-50 p-3.5 rounded-xl col-span-2">
                        <p class="text-xs font-medium text-gray-500 uppercase mb-1">Findings</p>
                        <p class="text-sm text-gray-900">{{ $selectedResult->findings ?: 'No findings recorded.' }}</p>
                    </div>
                    <div class="bg-gray-50 p-3.5 rounded-xl col-span-2">
                        <p class="text-xs font-medium text-gray-500 uppercase mb-1">Remarks</p>
                        <p class="text-sm text-gray-900">{{ $selectedResult->remarks ?: 'No remarks.' }}</p>
                    </div>
                    <div class="bg-gray-50 p-3.5 rounded-xl">
                        <p class="text-xs font-medium text-gray-500 uppercase mb-1">Result Date</p>
                        <p class="text-sm text-gray-900">{{ $selectedResult->result_date ? $selectedResult->result_date->format('M d, Y') : 'N/A' }}</p>
                    </div>
                    <div class="bg-gray-50 p-3.5 rounded-xl">
                        <p class="text-xs font-medium text-gray-500 uppercase mb-1">Performed By</p>
                        <p class="text-sm text-gray-900">{{ $selectedResult->performedBy ? $selectedResult->performedBy->firstname . ' ' . $selectedResult->performedBy->lastname : 'N/A' }}</p>
                    </div>
                </div>
                <div class="flex justify-end pt-2">
                    <button wire:click="closeResult" class="px-5 py-2.5 text-sm font-medium text-gray-700 bg-gray-100 hover:bg-gray-200 rounded-xl transition-colors">
                        Close
                    </button>
                </div>
            </div>
        </div>
    </div>
    @endif

    {{-- ===== ORDER DETAIL MODAL ===== --}}
    @if($selectedOrder)
    <div class="fixed inset-0 bg-black/40 backdrop-blur-sm flex items-center justify-center z-50 p-4" wire:click.self="closeOrder">
        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-4xl max-h-[90vh] overflow-hidden flex flex-col" id="printable-result">
            {{-- Header --}}
            <div class="bg-blue-500 px-6 py-5 text-white">
                <div class="flex items-center justify-between">
                    <div>
                        <h3 class="text-lg font-bold">Lab Test Order #{{ $selectedOrder->lab_test_order_id }}</h3>
                        <p class="text-blue-100 text-sm mt-0.5">
                            Requested: {{ $selectedOrder->order_date->format('F d, Y - h:i A') }}
                        </p>
                    </div>
                    <div class="flex items-center gap-3">
                        <span class="px-3 py-1.5 text-xs font-bold rounded-full
                            {{ $selectedOrder->status === 'completed' ? 'bg-green-400/20 text-green-100' : ($selectedOrder->status === 'cancelled' ? 'bg-red-400/20 text-red-100' : 'bg-yellow-400/20 text-yellow-100') }}">
                            {{ ucfirst($selectedOrder->status) }}
                        </span>
                        @php $payBadgeModal = $selectedOrder->payment_badge; @endphp
                        <span class="px-3 py-1.5 text-xs font-bold rounded-full {{ $selectedOrder->isPaid() ? 'bg-green-400/20 text-green-100' : 'bg-orange-400/30 text-orange-100' }}">
                            {{ $payBadgeModal['label'] }}
                        </span>
                        <button wire:click="closeOrder" class="p-1.5 hover:bg-white/20 rounded-lg transition-colors print:hidden">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        </button>
                    </div>
                </div>
            </div>

            {{-- Body --}}
            <div class="p-6 overflow-y-auto space-y-5 flex-1">
                {{-- PAY-FIRST: Payment Warning Banner --}}
                @if(!$selectedOrder->isPaid())
                <div class="bg-orange-50 border border-orange-200 rounded-xl p-4 flex items-start gap-3">
                    <svg class="w-5 h-5 text-orange-500 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <div>
                        <p class="text-sm font-semibold text-orange-800">Payment Pending</p>
                        <p class="text-sm text-orange-700 mt-0.5">
                            This order requires payment before results can be viewed. Please proceed to the clinic cashier to complete payment. 
                            @if($selectedOrder->total_amount)
                                <span class="font-bold">Amount Due: ₱{{ number_format($selectedOrder->total_amount, 2) }}</span>
                            @endif
                        </p>
                    </div>
                </div>
                @endif
                {{-- Order Info --}}
                <div class="grid grid-cols-2 sm:grid-cols-3 gap-3">
                    <div class="bg-gray-50 p-3.5 rounded-xl">
                        <p class="text-xs font-medium text-gray-500 uppercase mb-1">Patient</p>
                        <p class="text-sm font-semibold text-gray-900">{{ $patient->full_name }}</p>
                    </div>
                    <div class="bg-gray-50 p-3.5 rounded-xl">
                        <p class="text-xs font-medium text-gray-500 uppercase mb-1">Physician</p>
                        <p class="text-sm font-semibold text-gray-900">{{ $selectedOrder->physician->physician_name ?? 'Not specified' }}</p>
                    </div>
                    <div class="bg-gray-50 p-3.5 rounded-xl">
                        <p class="text-xs font-medium text-gray-500 uppercase mb-1">Progress</p>
                        <div class="flex items-center gap-2 mt-1">
                            @php
                                $completed = $selectedOrder->orderTests->where('status', 'completed')->count();
                                $total = $selectedOrder->orderTests->count();
                                $pct = $total > 0 ? round(($completed / $total) * 100) : 0;
                            @endphp
                            <div class="flex-1 bg-gray-200 rounded-full h-2">
                                <div class="h-2 rounded-full {{ $pct === 100 ? 'bg-green-500' : 'bg-blue-500' }}" style="width: {{ $pct }}%"></div>
                            </div>
                            <span class="text-xs font-semibold text-gray-600">{{ $completed }}/{{ $total }}</span>
                        </div>
                    </div>
                </div>

                {{-- Test Results Table --}}
                <div>
                    <h4 class="text-sm font-bold text-gray-800 mb-3 flex items-center gap-2">
                        <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                        Test Results
                    </h4>

                    <div class="bg-white border border-gray-200 rounded-xl overflow-hidden">
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wide">Test Name</th>
                                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wide">Result</th>
                                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wide">Reference Range</th>
                                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wide">Flag</th>
                                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wide">Remarks</th>
                                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wide">Status</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100">
                                    @foreach($selectedOrder->orderTests as $ot)
                                    @php
                                        $result = $ot->labResult;
                                        // Determine flag from result_value vs normal_range
                                        $flag = null;
                                        $flagClass = '';
                                        if ($result && $result->result_value && $result->normal_range) {
                                            $val = floatval($result->result_value);
                                            $range = $result->normal_range;
                                            // Try to parse ranges like "70-110", "3.5 - 5.5", "<100", ">10"
                                            if (preg_match('/^(\d+\.?\d*)\s*[-–]\s*(\d+\.?\d*)/', $range, $m)) {
                                                $low = floatval($m[1]);
                                                $high = floatval($m[2]);
                                                if (is_numeric($result->result_value)) {
                                                    if ($val < $low) { $flag = 'Low'; $flagClass = 'bg-blue-100 text-blue-700'; }
                                                    elseif ($val > $high) { $flag = 'High'; $flagClass = 'bg-red-100 text-red-700'; }
                                                    else { $flag = 'Normal'; $flagClass = 'bg-emerald-100 text-emerald-700'; }
                                                }
                                            }
                                        }
                                        // Fallback: check remarks/findings for keywords
                                        if (!$flag && $result) {
                                            $text = strtolower(($result->findings ?? '') . ' ' . ($result->remarks ?? ''));
                                            if (str_contains($text, 'high') || str_contains($text, 'elevated')) { $flag = 'High'; $flagClass = 'bg-red-100 text-red-700'; }
                                            elseif (str_contains($text, 'low') || str_contains($text, 'decreased')) { $flag = 'Low'; $flagClass = 'bg-blue-100 text-blue-700'; }
                                            elseif (str_contains($text, 'normal') || str_contains($text, 'within')) { $flag = 'Normal'; $flagClass = 'bg-emerald-100 text-emerald-700'; }
                                        }
                                    @endphp
                                    <tr class="hover:bg-blue-50/30 transition-colors">
                                        <td class="px-4 py-3">
                                            <div class="text-sm font-semibold text-gray-900">{{ $ot->test->label ?? 'Unknown Test' }}</div>
                                            @if($ot->test && $ot->test->section)
                                                <div class="text-xs text-gray-400">{{ $ot->test->section->label }}</div>
                                            @endif
                                        </td>
                                        <td class="px-4 py-3">
                                            @if(!$selectedOrder->isPaid())
                                                <span class="text-xs text-orange-600 italic font-medium">Payment required</span>
                                            @elseif($result && $result->result_value)
                                                <span class="text-sm font-semibold text-gray-900">{{ $result->result_value }}</span>
                                            @else
                                                <span class="text-sm text-gray-400 italic">Pending</span>
                                            @endif
                                        </td>
                                        <td class="px-4 py-3">
                                            @if(!$selectedOrder->isPaid())
                                                <span class="text-xs text-gray-400">—</span>
                                            @else
                                                <span class="text-sm text-gray-600">{{ $result->normal_range ?? '—' }}</span>
                                            @endif
                                        </td>
                                        <td class="px-4 py-3">
                                            @if(!$selectedOrder->isPaid())
                                                <span class="text-xs text-gray-400">—</span>
                                            @elseif($flag)
                                                <span class="inline-flex items-center gap-1 px-2 py-0.5 text-xs font-semibold rounded-full {{ $flagClass }}">
                                                    @if($flag === 'High')
                                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7"/></svg>
                                                    @elseif($flag === 'Low')
                                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                                                    @else
                                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                                    @endif
                                                    {{ $flag }}
                                                </span>
                                            @else
                                                <span class="text-xs text-gray-400">—</span>
                                            @endif
                                        </td>
                                        <td class="px-4 py-3">
                                            @if(!$selectedOrder->isPaid())
                                                <span class="text-xs text-gray-400">—</span>
                                            @else
                                                <span class="text-xs text-gray-600">{{ $result->remarks ?? ($result->findings ?? '—') }}</span>
                                            @endif
                                        </td>
                                        <td class="px-4 py-3">
                                            @if($result)
                                                @php
                                                    $rsc = match($result->status ?? 'draft') {
                                                        'final' => 'bg-emerald-100 text-emerald-700',
                                                        'revised' => 'bg-blue-100 text-blue-700',
                                                        default => 'bg-amber-100 text-amber-700',
                                                    };
                                                @endphp
                                                <span class="px-2 py-0.5 text-xs font-medium rounded-full {{ $rsc }}">{{ ucfirst($result->status) }}</span>
                                            @else
                                                <span class="px-2 py-0.5 text-xs font-medium rounded-full bg-gray-100 text-gray-500">Pending</span>
                                            @endif
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                {{-- Additional Info --}}
                @if($selectedOrder->remarks)
                <div class="bg-amber-50 border border-amber-200 rounded-xl p-4">
                    <p class="text-xs font-semibold text-amber-700 uppercase mb-1">Order Remarks</p>
                    <p class="text-sm text-amber-800">{{ $selectedOrder->remarks }}</p>
                </div>
                @endif

                {{-- Performed/Verified Info --}}
                @php
                    $performers = $selectedOrder->orderTests
                        ->filter(fn($ot) => $ot->labResult && $ot->labResult->performedBy)
                        ->map(fn($ot) => $ot->labResult->performedBy->firstname . ' ' . $ot->labResult->performedBy->lastname)
                        ->unique()->values();
                    $verifiers = $selectedOrder->orderTests
                        ->filter(fn($ot) => $ot->labResult && $ot->labResult->verifiedBy)
                        ->map(fn($ot) => $ot->labResult->verifiedBy->firstname . ' ' . $ot->labResult->verifiedBy->lastname)
                        ->unique()->values();
                @endphp
                @if($performers->isNotEmpty() || $verifiers->isNotEmpty())
                <div class="grid grid-cols-2 gap-3">
                    @if($performers->isNotEmpty())
                    <div class="bg-gray-50 p-3.5 rounded-xl">
                        <p class="text-xs font-medium text-gray-500 uppercase mb-1">Performed By</p>
                        <p class="text-sm font-medium text-gray-900">{{ $performers->implode(', ') }}</p>
                    </div>
                    @endif
                    @if($verifiers->isNotEmpty())
                    <div class="bg-gray-50 p-3.5 rounded-xl">
                        <p class="text-xs font-medium text-gray-500 uppercase mb-1">Verified By</p>
                        <p class="text-sm font-medium text-gray-900">{{ $verifiers->implode(', ') }}</p>
                    </div>
                    @endif
                </div>
                @endif
            </div>

            {{-- Footer --}}
            <div class="px-6 py-4 border-t border-gray-100 flex items-center justify-between print:hidden">
                <button wire:click="closeOrder" class="px-5 py-2.5 text-sm font-medium text-gray-700 bg-gray-100 hover:bg-gray-200 rounded-xl transition-colors">
                    Close
                </button>
                <div class="flex items-center gap-2">
                    @if($selectedOrder->isPaid())
                    <button wire:click="downloadOrderPdf"
                            class="inline-flex items-center gap-2 px-5 py-2.5 text-sm font-medium text-white bg-blue-500 hover:bg-blue-600 rounded-xl shadow-sm shadow-blue-500/25 transition-all">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                        </svg>
                        Download PDF
                    </button>
                    @else
                    <span class="inline-flex items-center gap-2 px-5 py-2.5 text-sm font-medium text-gray-400 bg-gray-100 rounded-xl cursor-not-allowed" title="Payment required to download results">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                        </svg>
                        Pay to Download
                    </span>
                    @endif
                </div>
            </div>
        </div>
    </div>
    @endif

    {{-- ===== CERTIFICATE DETAIL MODAL ===== --}}
    @if($selectedCertificate)
    <div class="fixed inset-0 bg-black/40 backdrop-blur-sm flex items-center justify-center z-50 p-4" wire:click.self="closeCertificate">
        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-xl overflow-hidden">
            {{-- Header --}}
            <div class="px-6 py-4 bg-blue-500 flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-white/20 backdrop-blur-sm flex items-center justify-center">
                        <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z"/>
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-lg font-bold text-white">Certificate Details</h3>
                        <p class="text-sm text-white/80 font-mono">{{ $selectedCertificate['number'] }}</p>
                    </div>
                </div>
                <button wire:click="closeCertificate" class="w-8 h-8 flex items-center justify-center rounded-lg text-white/60 hover:text-white hover:bg-white/20 transition-colors">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>

            {{-- Content --}}
            <div class="p-6 space-y-5 max-h-[60vh] overflow-y-auto">
                {{-- Status Badge --}}
                <div class="flex items-center justify-center">
                    @php
                        $msBg = match(strtolower($selectedCertificate['status'])) {
                            'issued' => 'bg-emerald-50 border-emerald-200 text-emerald-700',
                            'revoked' => 'bg-red-50 border-red-200 text-red-700',
                            'expired' => 'bg-gray-50 border-gray-200 text-gray-600',
                            default => 'bg-amber-50 border-amber-200 text-amber-700',
                        };
                        $msIcon = match(strtolower($selectedCertificate['status'])) {
                            'issued' => 'M5 13l4 4L19 7',
                            'revoked' => 'M6 18L18 6M6 6l12 12',
                            default => 'M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z',
                        };
                    @endphp
                    <span class="inline-flex items-center gap-2 px-4 py-2 rounded-full text-sm font-semibold border {{ $msBg }}">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $msIcon }}"/></svg>
                        {{ ucfirst($selectedCertificate['status']) }}
                    </span>
                </div>

                {{-- Certificate Info --}}
                <div class="bg-gray-50 rounded-xl overflow-hidden divide-y divide-gray-200/60">
                    <div class="flex justify-between items-center px-5 py-3.5">
                        <span class="text-sm text-gray-500">Certificate Number</span>
                        <span class="text-sm font-bold text-gray-900 font-mono">{{ $selectedCertificate['number'] }}</span>
                    </div>
                    <div class="flex justify-between items-center px-5 py-3.5">
                        <span class="text-sm text-gray-500">Type</span>
                        <span class="text-sm font-medium text-gray-900">{{ $selectedCertificate['type'] }}</span>
                    </div>
                    @if($selectedCertificate['patient_name'])
                    <div class="flex justify-between items-center px-5 py-3.5">
                        <span class="text-sm text-gray-500">Patient</span>
                        <span class="text-sm font-medium text-gray-900">{{ $selectedCertificate['patient_name'] }}</span>
                    </div>
                    @endif
                    @if($selectedCertificate['equipment'])
                    <div class="flex justify-between items-center px-5 py-3.5">
                        <span class="text-sm text-gray-500">Equipment</span>
                        <span class="text-sm font-medium text-gray-900">{{ $selectedCertificate['equipment'] }}</span>
                    </div>
                    @endif
                    @if($selectedCertificate['issue_date'])
                    <div class="flex justify-between items-center px-5 py-3.5">
                        <span class="text-sm text-gray-500">Issue Date</span>
                        <span class="text-sm font-medium text-gray-900">{{ $selectedCertificate['issue_date'] }}</span>
                    </div>
                    @endif
                    @if($selectedCertificate['valid_until'])
                    <div class="flex justify-between items-center px-5 py-3.5">
                        <span class="text-sm text-gray-500">Valid Until</span>
                        <span class="text-sm font-medium text-gray-900">{{ $selectedCertificate['valid_until'] }}</span>
                    </div>
                    @endif
                    @if($selectedCertificate['issued_by'])
                    <div class="flex justify-between items-center px-5 py-3.5">
                        <span class="text-sm text-gray-500">Issued By</span>
                        <span class="text-sm font-medium text-gray-900">{{ $selectedCertificate['issued_by'] }}</span>
                    </div>
                    @endif
                    @if($selectedCertificate['verified_by'])
                    <div class="flex justify-between items-center px-5 py-3.5">
                        <span class="text-sm text-gray-500">Verified By</span>
                        <span class="text-sm font-medium text-gray-900">{{ $selectedCertificate['verified_by'] }}</span>
                    </div>
                    @endif
                    @if($selectedCertificate['verification_code'])
                    <div class="flex justify-between items-center px-5 py-3.5">
                        <span class="text-sm text-gray-500">Verification Code</span>
                        <span class="text-sm font-mono font-medium text-blue-700 bg-blue-50 px-2 py-0.5 rounded">{{ $selectedCertificate['verification_code'] }}</span>
                    </div>
                    @endif
                </div>

                {{-- QR Code Section --}}
                @if($selectedCertificate['verification_code'] || $selectedCertificate['number'])
                <div class="text-center">
                    <p class="text-xs text-gray-500 mb-3">Scan to verify this certificate</p>
                    <div class="inline-block bg-white p-3 rounded-xl border border-gray-200 shadow-sm">
                        <div id="cert-qr-{{ $selectedCertificate['id'] }}" class="w-32 h-32 flex items-center justify-center">
                            <img src="https://api.qrserver.com/v1/create-qr-code/?size=128x128&data={{ urlencode(url('/certificates/verify?code=' . ($selectedCertificate['verification_code'] ?: $selectedCertificate['number']))) }}"
                                 alt="QR Code" class="w-32 h-32" loading="lazy">
                        </div>
                    </div>
                </div>
                @endif

                {{-- Extra Certificate Data --}}
                @if(!empty($selectedCertificate['certificate_data']))
                <div>
                    <h4 class="text-sm font-semibold text-gray-700 mb-2">Additional Information</h4>
                    <div class="bg-gray-50 rounded-xl overflow-hidden divide-y divide-gray-200/60">
                        @foreach($selectedCertificate['certificate_data'] as $key => $value)
                        <div class="flex justify-between items-center px-5 py-3">
                            <span class="text-sm text-gray-500">{{ ucwords(str_replace('_', ' ', $key)) }}</span>
                            <span class="text-sm font-medium text-gray-900">{{ is_array($value) ? json_encode($value) : $value }}</span>
                        </div>
                        @endforeach
                    </div>
                </div>
                @endif
            </div>

            {{-- Footer with Actions --}}
            <div class="px-6 py-4 bg-gray-50 border-t border-gray-200 flex items-center justify-between">
                <button wire:click="closeCertificate" class="px-5 py-2.5 text-sm font-medium text-gray-700 bg-white border border-gray-200 hover:bg-gray-100 rounded-xl transition-colors">
                    Close
                </button>
                <div class="flex items-center gap-2">
                    @if($selectedCertificate['pdf_path'] || $selectedCertificate['source'] === 'certificate')
                    <a href="{{ route('patient.certificate.download', ['source' => $selectedCertificate['source'], 'id' => $selectedCertificate['id']]) }}"
                       target="_blank"
                       class="inline-flex items-center gap-2 px-5 py-2.5 text-sm font-medium text-white bg-blue-500 hover:bg-blue-600 rounded-xl shadow-sm shadow-blue-500/25 transition-all">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                        Download PDF
                    </a>
                    @endif
                    <button onclick="window.print()"
                            class="inline-flex items-center gap-2 px-5 py-2.5 text-sm font-medium text-blue-700 bg-blue-50 hover:bg-blue-100 rounded-xl transition-colors">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                        Print
                    </button>
                </div>
            </div>
        </div>
    </div>
    @endif

    @endif
</div>
