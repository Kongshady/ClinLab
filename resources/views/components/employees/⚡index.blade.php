<?php

use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Validate;
use App\Models\Employee;
use App\Models\Section;
use App\Models\User;
use Spatie\Permission\Models\Role;

new class extends Component
{
    use WithPagination;

    #[Validate('required|exists:section,section_id')]
    public $section_id = '';

    #[Validate('required|string|max:50')]
    public $firstname = '';

    #[Validate('nullable|string|max:50')]
    public $middlename = '';

    #[Validate('required|string|max:50')]
    public $lastname = '';

    #[Validate('required|email|unique:users,email')]
    public $email = '';

    #[Validate('required|string|min:6')]
    public $password = '';

    #[Validate('required|string|max:100')]
    public $position = '';

    #[Validate('required|exists:user_roles,id')]
    public $role_id = '';

    public $search = '';
    public $flashMessage = '';

    public function mount()
    {
        if (session()->has('success')) {
            $this->flashMessage = session('success');
        }
    }

    public function save()
    {
        $this->validate();

        // Create User account for login
        $user = User::create([
            'name' => trim("{$this->firstname} {$this->middlename} {$this->lastname}"),
            'email' => $this->email,
            'password' => bcrypt($this->password),
        ]);

        // Assign role
        $role = Role::find($this->role_id);
        if ($role) {
            $user->assignRole($role->name);
        }

        // Create Employee record linked to User
        Employee::create([
            'user_id' => $user->id,
            'section_id' => $this->section_id,
            'firstname' => $this->firstname,
            'middlename' => $this->middlename,
            'lastname' => $this->lastname,
            'username' => $this->email,
            'password' => bcrypt($this->password),
            'position' => $this->position,
            // Role is managed through Spatie's user_roles table, not employee.role_id
            'role_id' => null,
            'status_code' => 1,
            'is_deleted' => 0,
        ]);

        $this->reset(['section_id', 'firstname', 'middlename', 'lastname', 'email', 'password', 'position', 'role_id']);
        $this->flashMessage = 'Employee account created successfully! They can now login with their email.';
        $this->resetPage();
    }

    public function delete($id)
    {
        $employee = Employee::find($id);
        if ($employee) {
            // Delete the linked User account
            if ($employee->user_id && $employee->user) {
                $employee->user->delete();
            }
            
            $employee->softDelete();
            $this->flashMessage = 'Employee account deactivated successfully!';
        }
    }

    public function with(): array
    {
        return [
            'employees' => Employee::active()
                ->with(['section', 'user.roles'])
                ->when($this->search, function ($query) {
                    $query->where(function ($q) {
                        $q->where('firstname', 'like', '%' . $this->search . '%')
                          ->orWhere('lastname', 'like', '%' . $this->search . '%')
                          ->orWhere('username', 'like', '%' . $this->search . '%')
                          ->orWhere('position', 'like', '%' . $this->search . '%');
                    });
                })
                ->orderBy('employee_id', 'desc')
                ->paginate(50),
            'sections' => Section::active()->orderBy('label')->get(),
            'roles' => Role::all()
        ];
    }
};
?>

<div class="p-6">
    <div class="max-w-7xl mx-auto">
        <!-- Page Header -->
        <div class="mb-8">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-3xl font-bold text-gray-900 flex items-center">
                        <div class="w-10 h-10 mr-3 bg-gradient-to-br from-blue-500 to-cyan-400 rounded-xl flex items-center justify-center shadow-lg">
                            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                            </svg>
                        </div>
                        Employee & User Management
                    </h1>
                    <p class="mt-2 text-sm text-gray-600">Create accounts, assign roles, and manage employee information</p>
                </div>
            </div>
        </div>

        @if($flashMessage)
            <div class="mb-6 p-4 bg-emerald-50 border-l-4 border-emerald-500 rounded-r-lg shadow-sm">
                <div class="flex items-center">
                    <svg class="w-5 h-5 text-emerald-500 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <span class="text-emerald-800 font-medium">{{ $flashMessage }}</span>
                </div>
            </div>
        @endif

        <!-- Create Employee Form -->
        <div class="card mb-8 overflow-hidden">
            <div class="px-6 py-5 border-b border-gray-200 bg-gradient-to-r from-gray-50 to-white">
                <h2 class="text-lg font-semibold text-gray-900 flex items-center">
                    <svg class="w-5 h-5 mr-2 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"/>
                    </svg>
                    Create New Employee Account
                </h2>
                <p class="text-sm text-gray-600 mt-1">Fill in employee details and assign role for system access</p>
            </div>
            <form wire:submit.prevent="save" class="p-6">
                <!-- Personal Information -->
                <div class="mb-6">
                    <h3 class="text-sm font-semibold text-gray-700 uppercase tracking-wider mb-4 flex items-center">
                        <svg class="w-4 h-4 mr-2 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                        </svg>
                        Personal Information
                    </h3>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">First Name *</label>
                            <input type="text" wire:model="firstname" placeholder="Juan" 
                                   class="input-field" required>
                            @error('firstname') <span class="text-red-600 text-sm mt-1 block">{{ $message }}</span> @enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Middle Name</label>
                            <input type="text" wire:model="middlename" placeholder="Santos" 
                                   class="input-field">
                            @error('middlename') <span class="text-red-600 text-sm mt-1 block">{{ $message }}</span> @enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Last Name *</label>
                            <input type="text" wire:model="lastname" placeholder="Dela Cruz" 
                                   class="input-field" required>
                            @error('lastname') <span class="text-red-600 text-sm mt-1 block">{{ $message }}</span> @enderror
                        </div>
                    </div>
                </div>

                <!-- Work Information -->
                <div class="mb-6">
                    <h3 class="text-sm font-semibold text-gray-700 uppercase tracking-wider mb-4 flex items-center">
                        <svg class="w-4 h-4 mr-2 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                        </svg>
                        Work Information
                    </h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Section *</label>
                            <select wire:model="section_id" class="input-field" required>
                                <option value="">Select Section</option>
                                @foreach($sections as $section)
                                    <option value="{{ $section->section_id }}">{{ $section->label }}</option>
                                @endforeach
                            </select>
                            @error('section_id') <span class="text-red-600 text-sm mt-1 block">{{ $message }}</span> @enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Position/Job Title *</label>
                            <input type="text" wire:model="position" placeholder="e.g., Medical Technologist" 
                                   class="input-field" required>
                            @error('position') <span class="text-red-600 text-sm mt-1 block">{{ $message }}</span> @enderror
                        </div>
                    </div>
                </div>

                <!-- Account Information -->
                <div class="mb-6">
                    <h3 class="text-sm font-semibold text-gray-700 uppercase tracking-wider mb-4 flex items-center">
                        <svg class="w-4 h-4 mr-2 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/>
                        </svg>
                        Login Account & Role
                    </h3>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Email (Username) *</label>
                            <input type="email" wire:model="email" placeholder="employee@clinlab.test" 
                                   class="input-field" required>
                            @error('email') <span class="text-red-600 text-sm mt-1 block">{{ $message }}</span> @enderror
                            <p class="text-xs text-gray-500 mt-1">Employee will use this to login</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Password *</label>
                            <input type="password" wire:model="password" placeholder="Min. 6 characters" 
                                   class="input-field" required>
                            @error('password') <span class="text-red-600 text-sm mt-1 block">{{ $message }}</span> @enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">System Role *</label>
                            <select wire:model="role_id" class="input-field" required>
                                <option value="">Select Role</option>
                                @foreach($roles as $role)
                                    <option value="{{ $role->id }}">{{ $role->name }}</option>
                                @endforeach
                            </select>
                            @error('role_id') <span class="text-red-600 text-sm mt-1 block">{{ $message }}</span> @enderror
                            <p class="text-xs text-gray-500 mt-1">Determines system access permissions</p>
                        </div>
                    </div>
                </div>

                <div class="flex justify-end pt-4 border-t border-gray-200">
                    <button type="submit" class="btn-primary flex items-center">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                        </svg>
                        Create Employee Account
                    </button>
                </div>
            </form>
        </div>

        <!-- Employee List -->
        <div class="card">
            <div class="px-6 py-5 border-b border-gray-200 bg-gradient-to-r from-gray-50 to-white">
                <div class="flex items-center justify-between">
                    <h2 class="text-lg font-semibold text-gray-900 flex items-center">
                        <svg class="w-5 h-5 mr-2 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                        </svg>
                        Employee Directory
                    </h2>
                    <span class="text-sm text-gray-600">{{ $employees->total() }} employees</span>
                </div>
            </div>
            <div class="p-6">
                <div class="mb-6">
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                            </svg>
                        </div>
                        <input type="text" wire:model.live="search" placeholder="Search by name, username, or position..." 
                               class="input-field pl-10">
                    </div>
                </div>
                <div class="overflow-x-auto">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Employee Name</th>
                                <th>Section</th>
                                <th>Position</th>
                                <th>Email/Username</th>
                                <th>Role & Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($employees as $employee)
                                <tr>
                                    <td class="text-gray-600">{{ $employee->employee_id }}</td>
                                    <td>
                                        <div class="flex items-center">
                                            <div class="w-8 h-8 bg-gradient-to-br from-blue-500 to-cyan-400 rounded-lg flex items-center justify-center text-white text-xs font-bold mr-3">
                                                {{ strtoupper(substr($employee->firstname, 0, 1) . substr($employee->lastname, 0, 1)) }}
                                            </div>
                                            <div class="font-medium text-gray-900">{{ $employee->full_name }}</div>
                                        </div>
                                    </td>
                                    <td class="text-gray-600">{{ $employee->section->label ?? 'N/A' }}</td>
                                    <td class="text-gray-600">{{ $employee->position }}</td>
                                    <td class="text-gray-600">{{ $employee->username }}</td>
                                    <td>
                                        <div class="flex flex-col gap-1">
                                            <span class="badge badge-success text-xs">Active</span>
                                            @if($employee->user && $employee->user->roles->isNotEmpty())
                                                <span class="badge badge-primary text-xs">{{ $employee->user->roles->first()->name }}</span>
                                            @endif
                                        </div>
                                    </td>
                                    <td class="space-x-2">
                                        <a href="/employees/{{ $employee->employee_id }}/edit" 
                                           class="inline-flex items-center px-3 py-1.5 bg-blue-50 text-blue-700 text-xs font-medium rounded-lg hover:bg-blue-100 transition-colors">
                                            <svg class="w-3.5 h-3.5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                            </svg>
                                            Edit
                                        </a>
                                        <button wire:click="delete({{ $employee->employee_id }})" 
                                                wire:confirm="Are you sure you want to deactivate this employee account?"
                                                class="inline-flex items-center px-3 py-1.5 bg-red-50 text-red-700 text-xs font-medium rounded-lg hover:bg-red-100 transition-colors">
                                            <svg class="w-3.5 h-3.5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                            </svg>
                                            Deactivate
                                        </button>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="px-6 py-12 text-center">
                                        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                                        </svg>
                                        <p class="mt-4 text-sm text-gray-600 font-medium">No employees found</p>
                                        <p class="text-sm text-gray-500">Create your first employee account to get started</p>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                
                @if($employees->hasPages())
                    <div class="mt-6 pt-4 border-t border-gray-200">
                        {{ $employees->links() }}
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>