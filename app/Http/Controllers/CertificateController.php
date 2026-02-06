<?php

namespace App\Http\Controllers;

use App\Models\Certificate;
use Illuminate\Http\Request;

class CertificateController extends Controller
{
    /**
     * Display a listing of certificates.
     */
    public function index()
    {
        return view('certificates.index', ['title' => 'Certificates Management']);
    }

    /**
<<<<<<< Updated upstream
     * Show the form for creating a new certificate.
     */
    public function create()
    {
        return redirect()->route('certificates.index');
    }

    /**
     * Store a newly created certificate in the database.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'certificate_number' => 'required|string|max:50',
            'type' => 'required|string|max:50',
            'issue_date' => 'required|date',
            'patient_id' => 'nullable|exists:patient,patient_id',
            'equipment_id' => 'nullable|exists:equipment,equipment_id',
            'issued_by' => 'nullable|exists:employee,employee_id',
            'verified_by' => 'nullable|exists:employee,employee_id',
            'status' => 'required|in:draft,issued,revoked',
            'remarks' => 'nullable|string',
        ]);

        Certificate::create(array_merge($validated, [
            'datetime_added' => now(),
        ]));

        return redirect()->route('certificates.index')
            ->with('success', 'Certificate created successfully.');
    }

    /**
     * Display the specified certificate.
     */
    public function show(Certificate $certificate)
    {
        return redirect()->route('certificates.index');
    }

    /**
     * Show the form for editing the specified certificate.
     */
    public function edit(Certificate $certificate)
    {
        return redirect()->route('certificates.index');
    }

    /**
     * Update the specified certificate in the database.
     */
    public function update(Request $request, Certificate $certificate)
    {
        $validated = $request->validate([
            'certificate_number' => 'required|string|max:50',
            'type' => 'required|string|max:50',
            'issue_date' => 'required|date',
            'patient_id' => 'nullable|exists:patient,patient_id',
            'equipment_id' => 'nullable|exists:equipment,equipment_id',
            'issued_by' => 'nullable|exists:employee,employee_id',
            'verified_by' => 'nullable|exists:employee,employee_id',
            'status' => 'required|in:draft,issued,revoked',
            'remarks' => 'nullable|string',
        ]);

        $certificate->update(array_merge($validated, [
            'datetime_modified' => now(),
        ]));

        return redirect()->route('certificates.index')
            ->with('success', 'Certificate updated successfully.');
    }

    /**
     * Remove the specified certificate from the database.
     */
    public function destroy(Certificate $certificate)
    {
        $certificate->delete();

        return redirect()->route('certificates.index')
            ->with('success', 'Certificate deleted successfully.');
=======
     * Display certificate templates page.
     */
    public function templates()
    {
        return view('certificates.templates.index');
    }

    /**
     * Display issued certificates page.
     */
    public function issued()
    {
        return view('certificates.issued.index');
    }

    /**
     * Display certificate verification page.
     */
    public function verify()
    {
        return view('certificates.verify.index');
>>>>>>> Stashed changes
    }
}
