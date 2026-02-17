<?php

use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Validate;
use App\Models\Employee;
use App\Models\Section;
use App\Models\User;
use App\Models\Role;
use Spatie\Permission\Models\Role as SpatieRole;
use Illuminate\Support\Facades\Hash;
use App\Traits\LogsActivity;

new class extends Component
{
    use WithPagination, LogsActivity;

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

    #[Validate('nullable|string|max:100')]
    public $position = '';

    #[Validate('required|exists:roles,role_id')]
    public $role_id = '';

    // Edit mode password fields for MIT Staff
    public $new_password = '';
    public $new_password_confirmation = '';
    public $change_password = false;

    public $search = '';
    public $flashMessage = '';
    public $perPage = 'all';

    // Delete confirmation modal properties
    public $showDeleteModal = false;
    public $deleteMessage = '';
    public $itemToDelete = null;
    public $itemName = '';

    // Edit mode
    public $editMode = false;
    public $editingEmployeeId = null;

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

        // Assign Spatie role for permissions
        $role = Role::find($this->role_id);
        if ($role) {
            $spatieRole = SpatieRole::where('name', $role->role_name)->first();
            if ($spatieRole) {
                $user->assignRole($spatieRole->name);
            }
        }

        // Create Employee record linked to User
        Employee::create([
            'user_id' => $user->id,
            'firstname' => $this->firstname,
            'middlename' => $this->middlename,
            'lastname' => $this->lastname,
            'username' => $this->email,
            'password' => bcrypt($this->password),
            'position' => $this->position,
            'role_id' => $this->role_id,
            'status_code' => 1,
            'is_deleted' => 0,
        ]);

        $this->reset(['firstname', 'middlename', 'lastname', 'email', 'password', 'position', 'role_id']);
        $this->logActivity("Created employee account: {$this->firstname} {$this->lastname}");
        $this->flashMessage = 'Employee account created successfully!';
        $this->resetPage();
    }

    public function edit($id)
    {
        $employee = Employee::with('user.roles', 'role')->findOrFail($id);
        $this->editingEmployeeId = $id;
        $this->firstname = $employee->firstname;
        $this->middlename = $employee->middlename ?? '';
        $this->lastname = $employee->lastname;
        $this->email = $employee->username;
        $this->position = $employee->position ?? '';
        $this->role_id = $employee->role_id ?? '';
        
        // Reset password fields
        $this->new_password = '';
        $this->new_password_confirmation = '';
        $this->change_password = false;
        
        $this->editMode = true;
    }

    public function update()
    {
        $validationRules = [
            'firstname' => 'required|string|max:50',
            'middlename' => 'nullable|string|max:50',
            'lastname' => 'required|string|max:50',
            'position' => 'nullable|string|max:100',
            'role_id' => 'required|exists:roles,role_id',
        ];

        // Add password validation if current logged-in user can edit passwords (MIT or Admin)
        $currentUserIsMitStaff = auth()->user() && auth()->user()->hasRole('MIT Staff');
        $currentUserIsAdmin = auth()->user() && (
            auth()->user()->hasRole('admin') || 
            auth()->user()->hasRole('super admin') ||
            auth()->user()->hasRole('Super Admin') ||
            auth()->user()->hasRole('Administrator')
        );
        $canEditPassword = $currentUserIsMitStaff || $currentUserIsAdmin;
        
        if ($canEditPassword && $this->change_password) {
            $validationRules['new_password'] = 'required|string|min:6|confirmed';
            $validationRules['new_password_confirmation'] = 'required';
        }

        $this->validate($validationRules);

        $employee = Employee::findOrFail($this->editingEmployeeId);
        $employee->update([
            'firstname' => $this->firstname,
            'middlename' => $this->middlename,
            'lastname' => $this->lastname,
            'position' => $this->position,
            'role_id' => $this->role_id,
        ]);

        // Update user name and role
        if ($employee->user) {
            $userUpdates = [
                'name' => trim("{$this->firstname} {$this->middlename} {$this->lastname}"),
            ];

            // Update password if current user can edit passwords and password change is requested
            if ($canEditPassword && $this->change_password && $this->new_password) {
                $userUpdates['password'] = Hash::make($this->new_password);
                // Also update employee password
                $employee->update(['password' => Hash::make($this->new_password)]);
            }

            $employee->user->update($userUpdates);

            // Update role
            $role = Role::find($this->role_id);
            if ($role) {
                $spatieRole = SpatieRole::where('name', $role->role_name)->first();
                if ($spatieRole) {
                    $employee->user->syncRoles([$spatieRole->name]);
                }
            }
        }

        $message = 'Employee updated successfully!';
        if ($canEditPassword && $this->change_password) {
            $message .= ' Password has been updated.';
        }
        
        $this->logActivity("Updated employee ID {$this->editingEmployeeId}: {$this->firstname} {$this->lastname}");
        $this->flashMessage = $message;
        $this->cancelEdit();
    }

    public function cancelEdit()
    {
        $this->reset(['firstname', 'middlename', 'lastname', 'email', 'position', 'role_id', 'editMode', 'editingEmployeeId', 'new_password', 'new_password_confirmation', 'change_password']);
    }

    public function delete($id)
    {
        $employee = Employee::find($id);
        if ($employee) {
            $this->itemToDelete = $id;
            $this->itemName = $employee->full_name;
            $this->deleteMessage = "Are you sure you want to delete the employee '{$this->itemName}'? This action cannot be undone.";
            $this->showDeleteModal = true;
        }
    }

    public function confirmDelete()
    {
        if ($this->itemToDelete) {
            $employee = Employee::find($this->itemToDelete);
            if ($employee) {
                // Only soft delete the employee to maintain data integrity
                // The associated user account remains for audit trail purposes
                $employee->softDelete();
                $this->logActivity("Deleted employee ID {$this->itemToDelete}: {$employee->firstname} {$employee->lastname}");
                $this->flashMessage = 'Employee account deleted successfully!';
            }
            $this->closeDeleteModal();
        }
    }

    public function closeDeleteModal()
    {
        $this->showDeleteModal = false;
        $this->deleteMessage = '';
        $this->itemToDelete = null;
        $this->itemName = '';
    }

    public function updatedChangePassword($value)
    {
        if (!$value) {
            $this->reset(['new_password', 'new_password_confirmation']);
        }
    }

    public function with(): array
    {
        $query = Employee::active()
            ->with(['user.roles', 'role'])
            ->when($this->search, function ($query) {
                $query->where(function ($q) {
                    $q->where('firstname', 'like', '%' . $this->search . '%')
                      ->orWhere('lastname', 'like', '%' . $this->search . '%')
                      ->orWhere('username', 'like', '%' . $this->search . '%')
                      ->orWhere('position', 'like', '%' . $this->search . '%');
                });
            })
            ->orderBy('employee_id', 'desc');

        $employees = $this->perPage === 'all' ? $query->get() : $query->paginate((int)$this->perPage);

        return [
            'employees' => $employees,
            'roles' => Role::where('status_code', 1)->where('role_name', '!=', 'Patient')->get()
        ];
    }
};
?>

<div class="p-6 space-y-6">
    @if($flashMessage)
        <div class="mb-6 bg-white border-l-4 border-green-500 shadow-sm rounded-lg p-4 flex items-center justify-between">
            <div class="flex items-center">
                <svg class="w-5 h-5 text-green-500 mr-3" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                </svg>
                <span class="text-sm font-medium text-gray-900">{{ $flashMessage }}</span>
            </div>
        </div>
    @endif

    <!-- Header -->
    <div class="flex items-center space-x-3 mb-6">
        <svg class="w-8 h-8 text-gray-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
        </svg>
        <h1 class="text-2xl font-bold text-gray-900">Employee Management</h1>
    </div>

    <!-- Add New Employee Form -->
    <div class="bg-white rounded-lg shadow-sm p-6">
        <h2 class="text-lg font-semibold text-gray-900 mb-6">Add New Employee</h2>
        <form wire:submit.prevent="save">
            <!-- Personal Information -->
            <div class="mb-6">
                <h3 class="text-sm font-medium text-gray-700 mb-4">Personal Information</h3>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            First Name <span class="text-red-500">*</span>
                        </label>
                        <input type="text" 
                               wire:model="firstname" 
                               placeholder="Juan"
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-pink-500 focus:border-transparent">
                        @error('firstname') <span class="text-red-600 text-xs mt-1">{{ $message }}</span> @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Middle Name</label>
                        <input type="text" 
                               wire:model="middlename" 
                               placeholder="Santos"
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-pink-500 focus:border-transparent">
                        @error('middlename') <span class="text-red-600 text-xs mt-1">{{ $message }}</span> @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Last Name <span class="text-red-500">*</span>
                        </label>
                        <input type="text" 
                               wire:model="lastname" 
                               placeholder="Dela Cruz"
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-pink-500 focus:border-transparent">
                        @error('lastname') <span class="text-red-600 text-xs mt-1">{{ $message }}</span> @enderror
                    </div>
                </div>
            </div>

            <!-- Work Information -->
            <div class="mb-6">
                <h3 class="text-sm font-medium text-gray-700 mb-4">Work Information</h3>
                <div class="grid grid-cols-1 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Position
                        </label>
                        <input type="text" 
                               wire:model="position" 
                               placeholder="Medical Technologist"
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-pink-500 focus:border-transparent">
                        @error('position') <span class="text-red-600 text-xs mt-1">{{ $message }}</span> @enderror
                    </div>
                </div>
            </div>

            <!-- Account Information -->
            <div class="mb-6">
                <h3 class="text-sm font-medium text-gray-700 mb-4">Account Information</h3>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Email <span class="text-red-500">*</span>
                        </label>
                        <input type="email" 
                               wire:model="email" 
                               placeholder="employee@clinlab.test"
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-pink-500 focus:border-transparent">
                        @error('email') <span class="text-red-600 text-xs mt-1">{{ $message }}</span> @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Password <span class="text-red-500">*</span>
                        </label>
                        <input type="password" 
                               wire:model="password" 
                               placeholder="Min. 6 characters"
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-pink-500 focus:border-transparent">
                        @error('password') <span class="text-red-600 text-xs mt-1">{{ $message }}</span> @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Role <span class="text-red-500">*</span>
                        </label>
                        <select wire:model="role_id" 
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-pink-500 focus:border-transparent">
                            <option value="">Select Role</option>
                            @foreach($roles as $role)
                                <option value="{{ $role->role_id }}">{{ $role->role_name }}</option>
                            @endforeach
                        </select>
                        @error('role_id') <span class="text-red-600 text-xs mt-1">{{ $message }}</span> @enderror
                    </div>
                </div>
            </div>

            <div class="flex justify-end">
                <button type="submit" 
                        class="px-6 py-2 bg-pink-500 hover:bg-pink-600 text-white font-medium rounded-lg transition-colors">
                    Add Employee
                </button>
            </div>
        </form>
    </div>

    <!-- Rows per page -->
    <div class="flex items-center space-x-3">
        <label class="text-sm font-medium text-gray-700">Rows per page:</label>
        <select wire:model.live="perPage" 
                class="px-3 py-1.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-pink-500 focus:border-transparent text-sm">
            <option value="10">10</option>
            <option value="25">25</option>
            <option value="50">50</option>
            <option value="100">100</option>
            <option value="all">All</option>
        </select>
    </div>

    <!-- Employee List -->
    <div class="bg-white rounded-lg shadow-sm">
        <div class="p-6">
            <h2 class="text-lg font-semibold text-gray-900 mb-4">Employees List</h2>
            
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead>
                        <tr class="bg-gray-50">
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Employee Name</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Position</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Email</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Role</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        @forelse($employees as $employee)
                            <tr class="hover:bg-gray-50">
                                <td class="px-4 py-3">
                                    <div class="flex items-center">
                                        <div class="w-8 h-8 bg-gradient-to-br from-blue-500 to-cyan-400 rounded-lg flex items-center justify-center text-white text-xs font-bold mr-3">
                                            {{ strtoupper(substr($employee->firstname, 0, 1) . substr($employee->lastname, 0, 1)) }}
                                        </div>
                                        <span class="text-sm text-gray-900 font-medium">{{ $employee->firstname }} {{ $employee->middlename }} {{ $employee->lastname }}</span>
                                    </div>
                                </td>
                                <td class="px-4 py-3 text-sm text-gray-700">{{ $employee->position ?? 'N/A' }}</td>
                                <td class="px-4 py-3 text-sm text-gray-700">{{ $employee->username }}</td>
                                <td class="px-4 py-3 text-sm text-gray-700">
                                    @if($employee->role)
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                            {{ $employee->role->role_name }}
                                        </span>
                                    @endif
                                </td>
                                <td class="px-4 py-3">
                                    <div class="flex items-center space-x-2">
                                        <button wire:click="edit({{ $employee->employee_id }})"
                                                style="background-color: #DC143C;"
                                                class="px-4 py-1.5 text-white text-sm font-medium rounded-lg hover:opacity-90 transition-opacity">
                                            Edit
                                        </button>
                                        <button wire:click="delete({{ $employee->employee_id }})" 
                                                class="px-4 py-1.5 bg-red-500 hover:bg-red-600 text-white text-sm font-medium rounded-lg transition-colors">
                                            Delete
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-4 py-8 text-center text-gray-500">No employees found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($perPage !== 'all')
            <div class="mt-6">
                {{ $employees->links() }}
            </div>
            @endif
        </div>
    </div>

    <!-- Edit Employee Modal -->
    @if($editMode)
    <div class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50 p-4">
        <div class="relative top-10 mx-auto p-6 border w-full max-w-2xl shadow-lg rounded-xl bg-white">
            <div class="flex items-center justify-between mb-6">
                <h3 class="text-2xl font-bold text-gray-900">Edit Employee</h3>
                <button wire:click="cancelEdit" class="text-gray-400 hover:text-gray-600">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>

            <form wire:submit.prevent="update">
                <div class="grid grid-cols-1 gap-6">
                    <div class="grid grid-cols-3 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">First Name *</label>
                            <input type="text" wire:model="firstname" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-pink-500 focus:border-transparent" required>
                            @error('firstname') <span class="text-red-500 text-sm mt-1 block">{{ $message }}</span> @enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Middle Name</label>
                            <input type="text" wire:model="middlename" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-pink-500 focus:border-transparent">
                            @error('middlename') <span class="text-red-500 text-sm mt-1 block">{{ $message }}</span> @enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Last Name *</label>
                            <input type="text" wire:model="lastname" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-pink-500 focus:border-transparent" required>
                            @error('lastname') <span class="text-red-500 text-sm mt-1 block">{{ $message }}</span> @enderror
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Email (readonly)</label>
                        <input type="email" wire:model="email" class="w-full px-3 py-2 border border-gray-300 rounded-lg bg-gray-100" readonly>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Position</label>
                        <input type="text" wire:model="position" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-pink-500 focus:border-transparent">
                        @error('position') <span class="text-red-500 text-sm mt-1 block">{{ $message }}</span> @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Role *</label>
                        <select wire:model="role_id" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-pink-500 focus:border-transparent" required>
                            <option value="">Select Role</option>
                            @foreach($roles as $role)
                                <option value="{{ $role->role_id }}">{{ $role->role_name }}</option>
                            @endforeach
                        </select>
                        @error('role_id') <span class="text-red-500 text-sm mt-1 block">{{ $message }}</span> @enderror
                    </div>

                    @php
                        // Check if the CURRENT logged-in user has MIT role (not the employee being edited)
                        $currentUserIsMitStaff = auth()->user() && auth()->user()->hasRole('MIT Staff');
                        
                        // Fallback: Also allow admin/super admin users to edit passwords
                        $currentUserIsAdmin = auth()->user() && (
                            auth()->user()->hasRole('admin') || 
                            auth()->user()->hasRole('super admin') ||
                            auth()->user()->hasRole('Super Admin') ||
                            auth()->user()->hasRole('Administrator')
                        );
                        
                        $canEditPassword = $currentUserIsMitStaff || $currentUserIsAdmin;
                        
                        // Debug: Show current user info (remove in production)
                        $currentUserRoles = auth()->user() ? auth()->user()->roles->pluck('name')->toArray() : [];
                        $debugInfo = [
                            'user_id' => auth()->id(),
                            'user_roles' => $currentUserRoles,
                            'has_mit_staff' => $currentUserIsMitStaff,
                            'has_admin' => $currentUserIsAdmin,
                            'can_edit_password' => $canEditPassword
                        ];
                    @endphp

                    {{-- Debug Info (remove in production) --}}
                    <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-3 mb-4">
                        <p class="text-xs text-yellow-700">
                            <strong>Debug Info:</strong> User ID: {{ $debugInfo['user_id'] }} | 
                            Roles: {{ implode(', ', $debugInfo['user_roles']) }} | 
                            MIT Staff: {{ $debugInfo['has_mit_staff'] ? 'Yes' : 'No' }} | 
                            Admin: {{ $debugInfo['has_admin'] ? 'Yes' : 'No' }} |
                            Can Edit Password: {{ $debugInfo['can_edit_password'] ? 'Yes' : 'No' }}
                        </p>
                    </div>

                    @if($canEditPassword)
                        <!-- Password Change Section for MIT Staff -->
                        <div class="border-t pt-6">
                            <div class="flex items-center mb-4">
                                <input type="checkbox" id="change_password" wire:model.live="change_password" class="h-4 w-4 text-pink-600 border-gray-300 rounded focus:ring-pink-500">
                                <label for="change_password" class="ml-2 text-sm font-medium text-gray-700">Change Password</label>
                                <span class="ml-2 text-xs text-blue-600 bg-blue-50 px-2 py-1 rounded-full">MIT Staff Admin</span>
                            </div>

                            @if($change_password)
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-2">New Password *</label>
                                        <input type="password" wire:model="new_password" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-pink-500 focus:border-transparent" placeholder="Enter new password">
                                        @error('new_password') <span class="text-red-500 text-sm mt-1 block">{{ $message }}</span> @enderror
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-2">Confirm New Password *</label>
                                        <input type="password" wire:model="new_password_confirmation" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-pink-500 focus:border-transparent" placeholder="Confirm new password">
                                        @error('new_password_confirmation') <span class="text-red-500 text-sm mt-1 block">{{ $message }}</span> @enderror
                                    </div>
                                </div>
                                <p class="text-xs text-gray-500 mt-2">
                                    <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.732-.833-2.5 0L4.268 16.5c-.77.833.192 2.5 1.732 2.5z"/>
                                    </svg>
                                    MIT Staff can change employee passwords as secondary admin.
                                </p>
                            @endif
                        </div>
                    @endif
                </div>
                <div class="flex justify-end space-x-3 pt-4 border-t mt-6">
                    <button type="button" wire:click="cancelEdit" class="px-6 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition-colors">
                        Cancel
                    </button>
                    <button type="submit"
                            style="background-color: #DC143C;"
                            class="px-6 py-2 text-white rounded-lg hover:opacity-90 transition-opacity">
                        Update Employee
                    </button>
                </div>
            </form>
        </div>
    </div>
    @endif

    {{-- Delete Confirmation Modal --}}
    @if($showDeleteModal)
    <div class="fixed inset-0 z-50 overflow-y-auto" style="background-color: rgba(0, 0, 0, 0.5);">
        <div class="flex items-center justify-center min-h-screen p-4">
            <div class="bg-white rounded-lg shadow-xl max-w-md w-full">
                <!-- Modal Header -->
                <div class="px-6 py-4 border-b border-gray-200">
                    <div class="flex items-center">
                        <svg class="w-6 h-6 text-red-500 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z"/>
                        </svg>
                        <h3 class="text-lg font-semibold text-gray-900">Delete Employee</h3>
                    </div>
                </div>

                <!-- Modal Body -->
                <div class="p-6">
                    <p class="text-sm text-gray-700">{{ $deleteMessage }}</p>
                </div>

                <!-- Modal Footer -->
                <div class="px-6 py-4 bg-gray-50 border-t border-gray-200 flex justify-end space-x-3 rounded-b-lg">
                    <button wire:click="closeDeleteModal"
                            class="px-4 py-2 bg-gray-200 hover:bg-gray-300 text-gray-700 font-medium rounded-lg transition-colors">
                        Cancel
                    </button>
                    <button wire:click="confirmDelete"
                            class="px-4 py-2 bg-red-500 hover:bg-red-600 text-white font-medium rounded-lg transition-colors">
                        Delete Employee
                    </button>
                </div>
            </div>
        </div>
    </div>
    @endif
</div>