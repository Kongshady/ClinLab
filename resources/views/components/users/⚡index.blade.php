<?php

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\User;
use App\Models\Employee;

new class extends Component
{
    use WithPagination;

    public $search = '';
    public $filterRole = '';

    public function with(): array
    {
        return [
            'users' => User::with(['employee.section', 'roles'])
                ->when($this->search, function ($query) {
                    $query->where(function ($q) {
                        $q->where('name', 'like', '%' . $this->search . '%')
                          ->orWhere('email', 'like', '%' . $this->search . '%')
                          ->orWhereHas('employee', function($eq) {
                              $eq->where('firstname', 'like', '%' . $this->search . '%')
                                 ->orWhere('lastname', 'like', '%' . $this->search . '%')
                                 ->orWhere('position', 'like', '%' . $this->search . '%');
                          });
                    });
                })
                ->when($this->filterRole, function ($query) {
                    $query->whereHas('roles', function($q) {
                        $q->where('name', $this->filterRole);
                    });
                })
                ->orderBy('created_at', 'desc')
                ->paginate(50),
            'roles' => \Spatie\Permission\Models\Role::all()
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
                        <div class="w-10 h-10 mr-3 bg-gradient-to-br from-purple-500 to-pink-400 rounded-xl flex items-center justify-center shadow-lg">
                            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/>
                            </svg>
                        </div>
                        User Accounts
                    </h1>
                    <p class="mt-2 text-sm text-gray-600">View all system users and their associated employee records</p>
                </div>
                <a href="/employees" class="btn-primary">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                    </svg>
                    Create New Employee
                </a>
            </div>
        </div>

        <!-- User List -->
        <div class="card">
            <div class="px-6 py-5 border-b border-gray-200 bg-gradient-to-r from-gray-50 to-white">
                <div class="flex items-center justify-between">
                    <h2 class="text-lg font-semibold text-gray-900 flex items-center">
                        <svg class="w-5 h-5 mr-2 text-purple-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                        </svg>
                        All System Users
                    </h2>
                    <span class="text-sm text-gray-600">{{ $users->total() }} users</span>
                </div>
            </div>
            <div class="p-6">
                <div class="mb-6 grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                            </svg>
                        </div>
                        <input type="text" wire:model.live="search" placeholder="Search by name, email, or position..." 
                               class="input-field pl-10">
                    </div>
                    <div>
                        <select wire:model.live="filterRole" class="input-field">
                            <option value="">All Roles</option>
                            @foreach($roles as $role)
                                <option value="{{ $role->name }}">{{ $role->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="overflow-x-auto">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>User ID</th>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Employee Info</th>
                                <th>Section</th>
                                <th>Role</th>
                                <th>Registered</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($users as $user)
                                <tr>
                                    <td class="text-gray-600">#{{ $user->id }}</td>
                                    <td>
                                        <div class="flex items-center">
                                            <div class="w-8 h-8 bg-gradient-to-br from-purple-500 to-pink-400 rounded-lg flex items-center justify-center text-white text-xs font-bold mr-3">
                                                {{ strtoupper(substr($user->name, 0, 2)) }}
                                            </div>
                                            <div class="font-medium text-gray-900">{{ $user->name }}</div>
                                        </div>
                                    </td>
                                    <td class="text-gray-600">{{ $user->email }}</td>
                                    <td>
                                        @if($user->employee)
                                            <div class="text-sm">
                                                <div class="font-medium text-gray-900">{{ $user->employee->full_name }}</div>
                                                <div class="text-gray-500 text-xs">{{ $user->employee->position }}</div>
                                            </div>
                                        @else
                                            <span class="text-gray-400 text-sm italic">No employee record</span>
                                        @endif
                                    </td>
                                    <td class="text-gray-600">
                                        @if($user->employee && $user->employee->section)
                                            {{ $user->employee->section->label }}
                                        @else
                                            <span class="text-gray-400">-</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($user->roles->isNotEmpty())
                                            @foreach($user->roles as $role)
                                                <span class="badge badge-primary text-xs mb-1">{{ $role->name }}</span>
                                            @endforeach
                                        @else
                                            <span class="badge badge-secondary text-xs">No Role</span>
                                        @endif
                                    </td>
                                    <td class="text-gray-600 text-sm">
                                        {{ $user->created_at->format('M d, Y') }}
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="px-6 py-12 text-center">
                                        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/>
                                        </svg>
                                        <p class="mt-4 text-sm text-gray-600 font-medium">No users found</p>
                                        <p class="text-sm text-gray-500">Try adjusting your search criteria</p>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                
                @if($users->hasPages())
                    <div class="mt-6 pt-4 border-t border-gray-200">
                        {{ $users->links() }}
                    </div>
                @endif
            </div>
        </div>

        <!-- Info Card -->
        <div class="mt-6 bg-blue-50 border-l-4 border-blue-500 p-4 rounded-r-lg">
            <div class="flex">
                <div class="flex-shrink-0">
                    <svg class="h-5 w-5 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                <div class="ml-3">
                    <h3 class="text-sm font-medium text-blue-800">About User Management</h3>
                    <div class="mt-2 text-sm text-blue-700">
                        <p>This page displays all user accounts in the system. Each user is linked to an employee record which contains their personal and work information. To create new users, use the <a href="/employees" class="font-semibold underline">Employee Management</a> page.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
