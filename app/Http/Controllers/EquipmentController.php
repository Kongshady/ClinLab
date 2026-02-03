<?php

namespace App\Http\Controllers;

use App\Models\Equipment;
use App\Models\Section;
use Illuminate\Http\Request;
use Inertia\Inertia;

class EquipmentController extends Controller
{
    public function index()
    {
        $equipment = Equipment::with('section')->active()->orderBy('equipment_id', 'desc')->paginate(15);
        $sections = Section::active()->orderBy('label')->get();
        return Inertia::render('Equipment/Index', ['equipment' => $equipment, 'sections' => $sections]);
    }

    public function create()
    {
        $sections = Section::active()->orderBy('label')->get(['section_id', 'label']);
        return Inertia::render('Equipment/Create', ['sections' => $sections]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'model' => 'nullable|string|max:100',
            'serial_no' => 'nullable|string|max:100',
            'section_id' => 'nullable|exists:section,section_id',
            'status' => 'required|in:operational,under_maintenance,decommissioned',
            'purchase_date' => 'nullable|date',
            'supplier' => 'nullable|string|max:200',
            'remarks' => 'nullable|string',
        ]);

        Equipment::create($validated);
        return redirect()->route('equipment.index')->with('success', 'Equipment created successfully.');
    }

    public function edit(Equipment $equipment)
    {
        if ($equipment->is_deleted) abort(404);
        $sections = Section::active()->orderBy('label')->get(['section_id', 'label']);
        $equipment->load('section');
        return Inertia::render('Equipment/Edit', ['equipment' => $equipment, 'sections' => $sections]);
    }

    public function update(Request $request, Equipment $equipment)
    {
        if ($equipment->is_deleted) abort(404);
        
        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'model' => 'nullable|string|max:100',
            'serial_no' => 'nullable|string|max:100',
            'section_id' => 'nullable|exists:section,section_id',
            'status' => 'required|in:operational,under_maintenance,decommissioned',
            'purchase_date' => 'nullable|date',
            'supplier' => 'nullable|string|max:200',
            'remarks' => 'nullable|string',
        ]);

        $equipment->update($validated);
        return redirect()->route('equipment.index')->with('success', 'Equipment updated successfully.');
    }

    public function destroy(Equipment $equipment)
    {
        if ($equipment->is_deleted) abort(404);
        $equipment->softDelete();
        return redirect()->route('equipment.index')->with('success', 'Equipment deleted successfully.');
    }
}
