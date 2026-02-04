# 🎯 RBAC Quick Reference Card

## 🔑 Demo Login Credentials

```
Laboratory Manager: manager@clinlab.test / password
Staff-in-Charge:   staff@clinlab.test / password
MIT Staff:         mit@clinlab.test / password
Secretary:         secretary@clinlab.test / password
```

## 📝 Permission Naming Convention

```
module.access   - View the module
module.create   - Create new records
module.edit     - Edit existing records
module.delete   - Delete records
module.view     - View individual record details
```

## 🛡️ Protection Layers

### 1️⃣ Route Level (routes/web.php)
```php
Route::middleware(['auth'])->group(function () {
    Route::middleware(['permission:patients.access'])->group(function () {
        Route::get('/patients', [PatientController::class, 'index']);
    });
});
```

### 2️⃣ Controller Level
```php
public function __construct()
{
    $this->middleware('permission:patients.access');
}

public function create()
{
    abort_unless(auth()->user()->can('patients.create'), 403);
    // ...
}
```

### 3️⃣ Blade View Level
```blade
@can('patients.create')
    <button>Add New Patient</button>
@endcan

@can('patients.edit')
    <a href="/patients/{{ $id }}/edit">Edit</a>
@endcan
```

### 4️⃣ Livewire Component Level
```php
public function mount()
{
    abort_unless(auth()->user()->can('patients.access'), 403);
}

public function delete($id)
{
    abort_unless(auth()->user()->can('patients.delete'), 403);
}
```

## 🎨 Common Blade Directives

```blade
{{-- Single permission --}}
@can('patients.edit')
    <button>Edit</button>
@endcan

{{-- Multiple permissions (ANY) --}}
@canany(['patients.edit', 'patients.delete'])
    <div class="actions">...</div>
@endcanany

{{-- Check role --}}
@role('Laboratory Manager')
    <div class="admin-panel">...</div>
@endrole

{{-- Negation --}}
@cannot('patients.delete')
    <p>You cannot delete patients</p>
@endcannot

{{-- If/else --}}
@can('patients.edit')
    <button>Edit</button>
@else
    <span class="text-gray-400">View Only</span>
@endcan
```

## 💻 Common PHP Checks

```php
// Check single permission
if (auth()->user()->can('patients.edit')) { }

// Check ANY permission
if (auth()->user()->canAny(['patients.edit', 'patients.delete'])) { }

// Check ALL permissions
if (auth()->user()->hasAllPermissions(['patients.edit', 'patients.delete'])) { }

// Check role
if (auth()->user()->hasRole('Laboratory Manager')) { }

// Check ANY role
if (auth()->user()->hasAnyRole(['Laboratory Manager', 'Staff-in-Charge'])) { }

// Abort if no permission
abort_unless(auth()->user()->can('patients.edit'), 403);

// Throw exception if no permission
$this->authorize('patients.edit');
```

## 🔧 Common Artisan Commands

```bash
# Clear permission cache (IMPORTANT after changes)
php artisan permission:cache-reset

# Reseed all roles and permissions
php artisan db:seed --class=RolesAndPermissionsSeeder

# Create new permission
php artisan tinker
> Permission::create(['name' => 'new-module.access']);

# Assign role to user
php artisan tinker
> $user = User::find(1);
> $user->assignRole('Laboratory Manager');

# Give permission to user
> $user->givePermissionTo('patients.access');

# Remove permission from user
> $user->revokePermissionTo('patients.delete');
```

## 🗄️ Database Tables

```
user_roles              - Role definitions
user_permissions        - Permission definitions
model_has_roles         - User-Role relationships
model_has_permissions   - User-Permission relationships
role_has_permissions    - Role-Permission relationships
```

## 🎭 Role Permissions Summary

| Role | Modules Count | Key Access |
|------|--------------|------------|
| Laboratory Manager | 14 | Everything |
| Staff-in-Charge | 10 | Patient care + Lab + Inventory |
| MIT Staff | 3 | Sections + Employees + Logs |
| Secretary | 2 | Patients + Physicians only |

## 🚨 Common Issues & Fixes

### "403 Access Denied" Error
```bash
# Clear cache
php artisan permission:cache-reset

# Check user role
php artisan tinker
> User::find(1)->roles;
> User::find(1)->permissions;
```

### Sidebar Not Updating
1. Clear permission cache: `php artisan permission:cache-reset`
2. Clear browser cache
3. Log out and log back in

### Permission Not Working
1. Check User model has `use HasRoles;` trait
2. Verify exact permission name (case-sensitive)
3. Clear permission cache

## 📋 Controller Template

```php
<?php

namespace App\Http\Controllers;

use App\Models\YourModel;
use Illuminate\Http\Request;

class YourController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:module.access');
    }

    public function index()
    {
        $items = YourModel::paginate(15);
        return view('module.index', compact('items'));
    }

    public function create()
    {
        abort_unless(auth()->user()->can('module.create'), 403);
        return view('module.create');
    }

    public function store(Request $request)
    {
        abort_unless(auth()->user()->can('module.create'), 403);
        // Validation and creation
    }

    public function edit($id)
    {
        abort_unless(auth()->user()->can('module.edit'), 403);
        $item = YourModel::findOrFail($id);
        return view('module.edit', compact('item'));
    }

    public function update(Request $request, $id)
    {
        abort_unless(auth()->user()->can('module.edit'), 403);
        // Validation and update
    }

    public function destroy($id)
    {
        abort_unless(auth()->user()->can('module.delete'), 403);
        YourModel::findOrFail($id)->delete();
        return redirect()->route('module.index');
    }
}
```

## 📄 Blade View Template

```blade
<div class="card">
    <div class="flex justify-between items-center mb-6">
        <h2 class="text-2xl font-semibold text-white">Module Name</h2>
        
        @can('module.create')
        <a href="{{ route('module.create') }}" class="btn-primary">
            Add New
        </a>
        @endcan
    </div>

    <table class="table">
        <thead>
            <tr>
                <th>ID</th>
                <th>Name</th>
                @canany(['module.edit', 'module.delete'])
                <th>Actions</th>
                @endcanany
            </tr>
        </thead>
        <tbody>
            @foreach($items as $item)
            <tr>
                <td>{{ $item->id }}</td>
                <td>{{ $item->name }}</td>
                @canany(['module.edit', 'module.delete'])
                <td class="space-x-2">
                    @can('module.view')
                    <a href="{{ route('module.show', $item) }}">View</a>
                    @endcan
                    
                    @can('module.edit')
                    <a href="{{ route('module.edit', $item) }}">Edit</a>
                    @endcan
                    
                    @can('module.delete')
                    <form action="{{ route('module.destroy', $item) }}" method="POST" class="inline">
                        @csrf
                        @method('DELETE')
                        <button onclick="return confirm('Delete?')">Delete</button>
                    </form>
                    @endcan
                </td>
                @endcanany
            </tr>
            @endforeach
        </tbody>
    </table>
</div>
```

---

**Quick Tip**: Always test with different user roles after implementing permissions!
