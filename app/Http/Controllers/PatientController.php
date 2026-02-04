<?php

namespace App\Http\Controllers;

use App\Models\Patient;
use Illuminate\Http\Request;

class PatientController extends Controller
{
    /**
     * Display a listing of the patients.
     */
    public function index()
    {
        $patients = Patient::where('is_deleted', 0)
            ->orderBy('datetime_added', 'desc')
            ->paginate(15);
        
        return view('patients.index', compact('patients'));
    }

    /**
     * Show the form for creating a new patient.
     */
    public function create()
    {
        abort_unless(auth()->user()->can('patients.create'), 403);
        
        return view('patients.create');
    }

    /**
     * Store a newly created patient in the database.
     */
    public function store(Request $request)
    {
        abort_unless(auth()->user()->can('patients.create'), 403);
        
        $validated = $request->validate([
            'patient_type' => 'required|in:Internal,External',
            'firstname' => 'required|string|max:50',
            'middlename' => 'nullable|string|max:50',
            'lastname' => 'required|string|max:50',
            'birthdate' => 'required|date',
            'gender' => 'required|string|max:10',
            'contact_number' => 'nullable|string|max:20',
            'address' => 'nullable|string|max:200',
        ]);

        Patient::create($validated);

        return redirect()->route('patients.index')
            ->with('success', 'Patient created successfully.');
    }

    /**
     * Display the specified patient.
     */
    public function show(Patient $patient)
    {
        abort_unless(auth()->user()->can('patients.view'), 403);
        
        if ($patient->is_deleted) {
            abort(404);
        }
        return view('patients.show', compact('patient'));
    }

    /**
     * Show the form for editing the specified patient.
     */
    public function edit(Patient $patient)
    {
        abort_unless(auth()->user()->can('patients.edit'), 403);
        
        if ($patient->is_deleted) {
            abort(404);
        }
        return view('patients.edit', compact('patient'));
    }

    /**
     * Update the specified patient in the database.
     */
    public function update(Request $request, Patient $patient)
    {
        abort_unless(auth()->user()->can('patients.edit'), 403);
        
        if ($patient->is_deleted) {
            abort(404);
        }

        $validated = $request->validate([
            'patient_type' => 'required|in:Internal,External',
            'firstname' => 'required|string|max:50',
            'middlename' => 'nullable|string|max:50',
            'lastname' => 'required|string|max:50',
            'birthdate' => 'required|date',
            'gender' => 'required|string|max:10',
            'contact_number' => 'nullable|string|max:20',
            'address' => 'nullable|string|max:200',
        ]);

        $patient->update($validated);

        return redirect()->route('patients.index')
            ->with('success', 'Patient updated successfully.');
    }

    /**
     * Remove the specified patient from the database (soft delete).
     */
    public function destroy(Patient $patient)
    {
        abort_unless(auth()->user()->can('patients.delete'), 403);
        
        if ($patient->is_deleted) {
            abort(404);
        }

        $patient->softDelete();

        return redirect()->route('patients.index')
            ->with('success', 'Patient deleted successfully.');
    }
}
