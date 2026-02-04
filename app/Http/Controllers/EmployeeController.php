<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\Section;
use Illuminate\Http\Request;

class EmployeeController extends Controller
{
    public function index()
    {
        $employees = Employee::with('section')
            ->where('is_deleted', 0)
            ->orderBy('lastname')
            ->paginate(15);
        
        return view('employees.index', compact('employees'));
    }

    public function create()
    {
        $sections = Section::active()->orderBy('label')->get(['section_id', 'label']);
        return view('employees.create', compact('sections'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'section_id' => 'nullable|exists:section,section_id',
            'firstname' => 'required|string|max:20',
            'middlename' => 'nullable|string|max:20',
            'lastname' => 'required|string|max:20',
            'username' => 'required|string|max:20|unique:employee,username',
            'password' => 'required|string|min:6',
            'position' => 'required|string|max:20',
            'role' => 'required|string|max:20',
        ]);

        $validated['password'] = bcrypt($validated['password']);
        $validated['status_code'] = 1;

        Employee::create($validated);
        return redirect()->route('employees.index')->with('success', 'Employee created successfully.');
    }

    public function edit(Employee $employee)
    {
        if ($employee->is_deleted) abort(404);
        $sections = Section::active()->orderBy('label')->get(['section_id', 'label']);
        $employee->load('section');
        return view('employees.edit', compact('employee', 'sections'));
    }

    public function update(Request $request, Employee $employee)
    {
        if ($employee->is_deleted) abort(404);
        
        $validated = $request->validate([
            'section_id' => 'nullable|exists:section,section_id',
            'firstname' => 'required|string|max:20',
            'middlename' => 'nullable|string|max:20',
            'lastname' => 'required|string|max:20',
            'username' => 'required|string|max:20|unique:employee,username,' . $employee->employee_id . ',employee_id',
            'position' => 'required|string|max:20',
            'role' => 'required|string|max:20',
        ]);

        if ($request->filled('password')) {
            $validated['password'] = bcrypt($request->password);
        }

        $employee->update($validated);
        return redirect()->route('employees.index')->with('success', 'Employee updated successfully.');
    }

    public function destroy(Employee $employee)
    {
        if ($employee->is_deleted) abort(404);
        $employee->softDelete();
        return redirect()->route('employees.index')->with('success', 'Employee deleted successfully.');
    }
}
