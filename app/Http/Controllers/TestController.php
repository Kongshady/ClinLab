<?php

namespace App\Http\Controllers;

use App\Models\Test;
use App\Models\Section;
use Illuminate\Http\Request;
use Inertia\Inertia;

class TestController extends Controller
{
    /**
     * Display a listing of the tests.
     */
    public function index()
    {
        $tests = Test::with('section')
            ->active()
            ->orderBy('test_id', 'desc')
            ->paginate(15);
        
        $sections = Section::active()->orderBy('label')->get();

        return Inertia::render('Tests/Index', [
            'tests' => $tests,
            'sections' => $sections
        ]);
    }

    /**
     * Show the form for creating a new test.
     */
    public function create()
    {
        $sections = Section::active()
            ->orderBy('label')
            ->get(['section_id', 'label']);

        return Inertia::render('Tests/Create', [
            'sections' => $sections
        ]);
    }

    /**
     * Store a newly created test in the database.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'section_id' => 'required|exists:section,section_id',
            'label' => 'required|string|max:20',
            'current_price' => 'required|numeric|min:0|max:99999999.99',
        ]);

        Test::create($validated);

        return redirect()->route('tests.index')
            ->with('success', 'Lab test created successfully.');
    }

    /**
     * Display the specified test.
     */
    public function show(Test $test)
    {
        if ($test->is_deleted) {
            abort(404);
        }

        $test->load('section');

        return Inertia::render('Tests/Show', [
            'test' => $test
        ]);
    }

    /**
     * Show the form for editing the specified test.
     */
    public function edit(Test $test)
    {
        if ($test->is_deleted) {
            abort(404);
        }

        $sections = Section::active()
            ->orderBy('label')
            ->get(['section_id', 'label']);

        $test->load('section');

        return Inertia::render('Tests/Edit', [
            'test' => $test,
            'sections' => $sections
        ]);
    }

    /**
     * Update the specified test in the database.
     */
    public function update(Request $request, Test $test)
    {
        if ($test->is_deleted) {
            abort(404);
        }

        $validated = $request->validate([
            'section_id' => 'required|exists:section,section_id',
            'label' => 'required|string|max:20',
            'current_price' => 'required|numeric|min:0|max:99999999.99',
        ]);

        $test->update($validated);

        return redirect()->route('tests.index')
            ->with('success', 'Lab test updated successfully.');
    }

    /**
     * Remove the specified test from the database (soft delete).
     */
    public function destroy(Test $test)
    {
        if ($test->is_deleted) {
            abort(404);
        }

        $test->softDelete();

        return redirect()->route('tests.index')
            ->with('success', 'Lab test deleted successfully.');
    }
}
