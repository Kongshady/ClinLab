# RBAC Implementation Guide

This document explains how the Role-Based Access Control (RBAC) system is implemented in the ClinLab application using Spatie Laravel Permission.

## Overview

The application uses 4 roles with specific module access:

1. **Laboratory Manager** - Full access to all 14 modules
2. **Staff-in-Charge** - Access to 10 modules (patient management, lab operations, inventory)
3. **MIT Staff** - Access to 3 modules (sections, employees, activity logs)
4. **Secretary** - Access to 2 modules (patients, physicians)

## Demo User Accounts

```
Laboratory Manager: manager@clinlab.test / password
Staff-in-Charge: staff@clinlab.test / password
MIT Staff: mit@clinlab.test / password
Secretary: secretary@clinlab.test / password
```

## Permission Structure

Permissions follow the `module.action` naming convention:

### Module Access Permissions
- `patients.access`
- `physicians.access`
- `lab-results.access`
- `tests.access`
- `certificates.access`
- `transactions.access`
- `items.access`
- `inventory.access`
- `equipment.access`
- `calibration.access`
- `sections.access`
- `employees.access`
- `reports.access`
- `activity-logs.access`

### CRUD Permissions (for each module)
- `module.create`
- `module.edit`
- `module.delete`
- `module.view`

Example for patients:
- `patients.create`
- `patients.edit`
- `patients.delete`
- `patients.view`

## Implementation Layers

### 1. Route Protection (✅ COMPLETED)

Routes are protected using middleware in `routes/web.php`:

```php
Route::middleware(['auth'])->group(function () {
    Route::middleware(['permission:patients.access'])->group(function () {
        Route::get('/patients', [PatientController::class, 'index'])->name('patients.index');
        Route::get('/patients/create', [PatientController::class, 'create'])->name('patients.create');
        // ... more routes
    });
});
```

### 2. Controller Protection

Add authorization checks in your controller methods:

```php
<?php

namespace App\Http\Controllers;

use App\Models\Patient;
use Illuminate\Http\Request;

class PatientController extends Controller
{
    public function __construct()
    {
        // Require module access for all methods
        $this->middleware('permission:patients.access');
    }

    public function index()
    {
        // List patients
        $patients = Patient::paginate(15);
        return view('patients.index', compact('patients'));
    }

    public function create()
    {
        // Check if user can create
        abort_unless(auth()->user()->can('patients.create'), 403);
        
        return view('patients.create');
    }

    public function store(Request $request)
    {
        abort_unless(auth()->user()->can('patients.create'), 403);
        
        // Validation and creation logic
        $validated = $request->validate([...]);
        Patient::create($validated);
        
        return redirect()->route('patients.index');
    }

    public function edit(Patient $patient)
    {
        abort_unless(auth()->user()->can('patients.edit'), 403);
        
        return view('patients.edit', compact('patient'));
    }

    public function update(Request $request, Patient $patient)
    {
        abort_unless(auth()->user()->can('patients.edit'), 403);
        
        $validated = $request->validate([...]);
        $patient->update($validated);
        
        return redirect()->route('patients.index');
    }

    public function destroy(Patient $patient)
    {
        abort_unless(auth()->user()->can('patients.delete'), 403);
        
        $patient->delete();
        
        return redirect()->route('patients.index');
    }
}
```

### 3. Livewire Component Protection

If you're using Livewire components, protect them in the component class:

```php
<?php

namespace App\Livewire\Patients;

use Livewire\Component;
use App\Models\Patient;

class Index extends Component
{
    public $search = '';
    public $patients;

    public function mount()
    {
        // Check module access
        abort_unless(auth()->user()->can('patients.access'), 403);
        
        $this->loadPatients();
    }

    public function loadPatients()
    {
        $this->patients = Patient::where('name', 'like', "%{$this->search}%")
            ->orderBy('created_at', 'desc')
            ->paginate(15);
    }

    public function deletePatient($id)
    {
        // Check delete permission
        abort_unless(auth()->user()->can('patients.delete'), 403);
        
        Patient::findOrFail($id)->delete();
        $this->loadPatients();
        
        session()->flash('message', 'Patient deleted successfully.');
    }

    public function render()
    {
        return view('livewire.patients.index');
    }
}
```

### 4. UI Protection in Blade Views (✅ COMPLETED)

#### Sidebar Navigation

Menu items are hidden using `@can` directives in `resources/views/layouts/app.blade.php`:

```blade
@can('patients.access')
<a href="/patients" class="nav-link">
    <svg>...</svg>
    <span>Patients</span>
</a>
@endcan
```

#### Action Buttons in Views

Hide create/edit/delete buttons based on permissions:

```blade
{{-- In patients/index.blade.php --}}

<div class="flex justify-between items-center mb-6">
    <h2 class="text-2xl font-semibold text-white">Patients</h2>
    
    @can('patients.create')
    <a href="{{ route('patients.create') }}" class="btn-primary">
        <svg class="w-5 h-5 mr-2">...</svg>
        Add New Patient
    </a>
    @endcan
</div>

{{-- In table actions --}}
<td class="px-6 py-4 text-right space-x-2">
    @can('patients.view')
    <a href="{{ route('patients.show', $patient) }}" class="text-blue-400 hover:text-blue-300">
        View
    </a>
    @endcan
    
    @can('patients.edit')
    <a href="{{ route('patients.edit', $patient) }}" class="text-cyan-400 hover:text-cyan-300">
        Edit
    </a>
    @endcan
    
    @can('patients.delete')
    <form action="{{ route('patients.destroy', $patient) }}" method="POST" class="inline">
        @csrf
        @method('DELETE')
        <button type="submit" class="text-red-400 hover:text-red-300" 
                onclick="return confirm('Are you sure?')">
            Delete
        </button>
    </form>
    @endcan
</td>
```

#### Conditional Sections

Show/hide entire sections based on permissions:

```blade
@can('patients.access')
<div class="card">
    <h3>Patient Statistics</h3>
    {{-- Patient stats content --}}
</div>
@endcan

@canany(['reports.access', 'activity-logs.access'])
<div class="analytics-section">
    @can('reports.access')
        {{-- Reports content --}}
    @endcan
    
    @can('activity-logs.access')
        {{-- Activity logs content --}}
    @endcan
</div>
@endcanany
```

### 5. 403 Error Page (✅ COMPLETED)

A custom 403 error page has been created at `resources/views/errors/403.blade.php` that matches the modern design aesthetic.

## Testing RBAC

### Test Different Roles

1. **Login as Laboratory Manager** (manager@clinlab.test)
   - Should see all 14 modules in sidebar
   - Should have full CRUD access to all modules

2. **Login as Staff-in-Charge** (staff@clinlab.test)
   - Should see 10 modules: Patients, Physicians, Lab Results, Tests, Certificates, Transactions, Items, Inventory, Equipment, Reports
   - Sections, Employees, Calibration, Activity Logs should be hidden

3. **Login as MIT Staff** (mit@clinlab.test)
   - Should only see: Sections, Employees, Activity Logs
   - All other modules should be hidden

4. **Login as Secretary** (secretary@clinlab.test)
   - Should only see: Patients, Physicians
   - All other modules should be hidden

### Test Permission Enforcement

1. Try accessing a route directly without permission (should show 403)
2. Try to view action buttons that require specific permissions
3. Verify sidebar only shows modules the user has access to

## Additional Helper Methods

### Check Multiple Permissions

```php
// Check if user has ANY of the permissions
if (auth()->user()->canAny(['patients.edit', 'patients.delete'])) {
    // Show actions dropdown
}

// Check if user has ALL permissions
if (auth()->user()->hasAllPermissions(['patients.edit', 'patients.delete'])) {
    // Show bulk actions
}
```

### Check Roles

```php
// Check specific role
if (auth()->user()->hasRole('Laboratory Manager')) {
    // Manager-specific functionality
}

// Check multiple roles
if (auth()->user()->hasAnyRole(['Laboratory Manager', 'Staff-in-Charge'])) {
    // Show advanced features
}
```

### Assign Permissions to Users

```php
// Assign a role to a user
$user->assignRole('Staff-in-Charge');

// Give a specific permission
$user->givePermissionTo('patients.access');

// Remove permission
$user->revokePermissionTo('patients.delete');

// Sync permissions (replaces all existing permissions)
$user->syncPermissions(['patients.access', 'patients.view']);
```

## Database Tables

The following Spatie Permission tables are used (renamed to avoid conflicts):

- `user_roles` - Stores role definitions
- `user_permissions` - Stores permission definitions
- `model_has_roles` - Links users to roles
- `model_has_permissions` - Links users to direct permissions
- `role_has_permissions` - Links roles to permissions

## Best Practices

1. **Always check module access first** - Use `module.access` permission before checking CRUD permissions
2. **Use middleware for route protection** - Don't rely solely on UI hiding
3. **Combine route + controller protection** - Defense in depth
4. **Test with different roles** - Ensure each role sees only what they should
5. **Use @can directives liberally** - Hide UI elements users can't use
6. **Clear permission cache** - Run `php artisan permission:cache-reset` after changing permissions

## Common Commands

```bash
# Clear permission cache
php artisan permission:cache-reset

# Reseed roles and permissions
php artisan db:seed --class=RolesAndPermissionsSeeder

# Create a new permission
php artisan tinker
> Permission::create(['name' => 'new-module.access']);

# Assign role to user
php artisan tinker
> $user = User::find(1);
> $user->assignRole('Laboratory Manager');
```

## Troubleshooting

### User sees "403 Access Denied"
- Check if user has the required role assigned
- Verify the role has the required permissions
- Clear permission cache: `php artisan permission:cache-reset`

### Sidebar doesn't update after permission change
- Clear permission cache
- Log out and log back in
- Check browser cache

### Permission checks not working
- Ensure User model has `use HasRoles;` trait
- Verify permission names match exactly (case-sensitive)
- Check database tables are properly created

## Next Steps

1. Update all controllers with permission checks in constructor and methods
2. Add permission checks to all Livewire components (if used)
3. Review all Blade views and add @can directives for action buttons
4. Test with all 4 demo user accounts
5. Create actual user accounts and assign appropriate roles
6. Consider implementing audit logging for permission changes
