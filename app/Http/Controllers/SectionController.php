<?php

namespace App\Http\Controllers;

use App\Models\Section;
use Illuminate\Http\Request;

class SectionController extends Controller
{
    public function index()
    {
        $sections = Section::where('is_deleted', 0)
            ->orderBy('label')
            ->paginate(15);
        
        return view('sections.index', compact('sections'));
    }

    public function create()
    {
        return view('sections.create');
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
        return view('sections.edit', compact('section'));
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
