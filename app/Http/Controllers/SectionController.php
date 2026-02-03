<?php

namespace App\Http\Controllers;

use App\Models\Section;
use Illuminate\Http\Request;
use Inertia\Inertia;

class SectionController extends Controller
{
    public function index()
    {
        $sections = Section::active()->orderBy('section_id', 'desc')->paginate(15);
        return Inertia::render('Sections/Index', ['sections' => $sections]);
    }

    public function create()
    {
        return Inertia::render('Sections/Create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'label' => 'required|string|max:50',
        ]);

        Section::create($validated);
        return redirect()->route('sections.index')->with('success', 'Section created successfully.');
    }

    public function edit(Section $section)
    {
        if ($section->is_deleted) abort(404);
        return Inertia::render('Sections/Edit', ['section' => $section]);
    }

    public function update(Request $request, Section $section)
    {
        if ($section->is_deleted) abort(404);
        
        $validated = $request->validate([
            'label' => 'required|string|max:50',
        ]);

        $section->update($validated);
        return redirect()->route('sections.index')->with('success', 'Section updated successfully.');
    }

    public function destroy(Section $section)
    {
        if ($section->is_deleted) abort(404);
        $section->softDelete();
        return redirect()->route('sections.index')->with('success', 'Section deleted successfully.');
    }
}
