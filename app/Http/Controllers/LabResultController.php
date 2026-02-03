<?php

namespace App\Http\Controllers;

use App\Models\LabResult;
use App\Models\Patient;
use App\Models\Test;
use App\Models\Employee;
use Illuminate\Http\Request;
use Inertia\Inertia;

class LabResultController extends Controller
{
    /**
     * Display a listing of the lab results.
     */
    public function index()
    {
        $labResults = LabResult::with(['patient', 'test', 'performedBy', 'verifiedBy'])
            ->orderBy('lab_result_id', 'desc')
            ->paginate(50);

        $patients = Patient::active()->orderBy('lastname')->get();
        $tests = Test::active()->orderBy('label')->get();
        $employees = Employee::active()->orderBy('lastname')->get();

        return Inertia::render('LabResult/Index', [
            'labResults' => $labResults,
            'patients' => $patients,
            'tests' => $tests,
            'employees' => $employees,
        ]);
    }

    /**
     * Show the form for creating a new lab result.
     */
    public function create()
    {
        $patients = Patient::active()->orderBy('lastname')->get();
        $tests = Test::active()->orderBy('label')->get();
        $employees = Employee::active()->orderBy('lastname')->get();

        return Inertia::render('LabResult/Create', [
            'patients' => $patients,
            'tests' => $tests,
            'employees' => $employees,
        ]);
    }

    /**
     * Store a newly created lab result in the database.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'patient_id' => 'required|exists:patient,patient_id',
            'test_id' => 'required|exists:test,test_id',
            'result_date' => 'nullable|date',
            'findings' => 'nullable|string',
            'normal_range' => 'nullable|string|max:100',
            'result_value' => 'nullable|string|max:100',
            'remarks' => 'nullable|string',
            'performed_by' => 'nullable|exists:employee,employee_id',
            'verified_by' => 'nullable|exists:employee,employee_id',
            'status' => 'required|in:draft,final,revised',
        ]);

        LabResult::create($validated);

        return redirect()->route('lab-results.index')
            ->with('success', 'Lab result created successfully.');
    }

    /**
     * Display the specified lab result.
     */
    public function show(LabResult $labResult)
    {
        $labResult->load(['patient', 'test', 'performedBy', 'verifiedBy']);
        return Inertia::render('LabResult/Show', compact('labResult'));
    }

    /**
     * Show the form for editing the specified lab result.
     */
    public function edit(LabResult $labResult)
    {
        $labResult->load(['patient', 'test', 'performedBy', 'verifiedBy']);
        $patients = Patient::active()->orderBy('lastname')->get();
        $tests = Test::active()->orderBy('label')->get();
        $employees = Employee::active()->orderBy('lastname')->get();

        return Inertia::render('LabResult/Edit', [
            'labResult' => $labResult,
            'patients' => $patients,
            'tests' => $tests,
            'employees' => $employees,
        ]);
    }

    /**
     * Update the specified lab result in the database.
     */
    public function update(Request $request, LabResult $labResult)
    {
        $validated = $request->validate([
            'patient_id' => 'required|exists:patient,patient_id',
            'test_id' => 'required|exists:test,test_id',
            'result_date' => 'nullable|date',
            'findings' => 'nullable|string',
            'normal_range' => 'nullable|string|max:100',
            'result_value' => 'nullable|string|max:100',
            'remarks' => 'nullable|string',
            'performed_by' => 'nullable|exists:employee,employee_id',
            'verified_by' => 'nullable|exists:employee,employee_id',
            'status' => 'required|in:draft,final,revised',
        ]);

        $labResult->update($validated);

        return redirect()->route('lab-results.index')
            ->with('success', 'Lab result updated successfully.');
    }

    /**
     * Remove the specified lab result from the database.
     */
    public function destroy(LabResult $labResult)
    {
        $labResult->delete();

        return redirect()->route('lab-results.index')
            ->with('success', 'Lab result deleted successfully.');
    }
}
