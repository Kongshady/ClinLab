<?php

namespace App\Http\Controllers;

class PhysicianController extends Controller
{
    public function index()
    {
        return view('physicians.index');
    }
<<<<<<< Updated upstream

    public function create()
    {
        return view('physicians.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'physician_name' => 'required|string|max:100',
            'specialization' => 'nullable|string|max:100',
            'contact_number' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:100',
        ]);

        Physician::create($validated);
        return redirect()->route('physicians.index')->with('success', 'Physician created successfully.');
    }

    public function edit(Physician $physician)
    {
        if ($physician->is_deleted) abort(404);
        return view('physicians.edit', compact('physician'));
    }

    public function update(Request $request, Physician $physician)
    {
        if ($physician->is_deleted) abort(404);
        
        $validated = $request->validate([
            'physician_name' => 'required|string|max:100',
            'specialization' => 'nullable|string|max:100',
            'contact_number' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:100',
        ]);

        $physician->update($validated);
        return redirect()->route('physicians.index')->with('success', 'Physician updated successfully.');
    }

    public function destroy(Physician $physician)
    {
        if ($physician->is_deleted) abort(404);
        $physician->softDelete();
        return redirect()->route('physicians.index')->with('success', 'Physician deleted successfully.');
    }
=======
>>>>>>> Stashed changes
}
