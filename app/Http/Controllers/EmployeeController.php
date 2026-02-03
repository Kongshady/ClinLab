<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\Section;
use Illuminate\Http\Request;
use Inertia\Inertia;

class EmployeeController extends Controller
{
    public function index()
    {
        $employees = Employee::with('section')->active()->orderBy('employee_id', 'desc')->paginate(15);
        $sections = Section::active()->orderBy('label')->get();
        return Inertia::render('Employees/Index', ['employees' => $employees, 'sections' => $sections]);
    }

    public function create()
    {
        $sections = Section::active()->orderBy('label')->get(['section_id', 'label']);
        return Inertia::render('Employees/Create', ['sections' => $sections]);
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
        return Inertia::render('Employees/Edit', ['employee' => $employee, 'sections' => $sections]);
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
