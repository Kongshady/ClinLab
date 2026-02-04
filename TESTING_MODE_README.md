# 🧪 TESTING MODE - Authentication Temporarily Disabled

## ⚠️ Current Status
Authentication and RBAC (Role-Based Access Control) are **TEMPORARILY DISABLED** for testing employee functionality.

## 🌐 Access the Application
**Development Server:** http://127.0.0.1:8000

The application will automatically redirect to `/employees` for testing.

## 📋 What's Available for Testing

### Pages You Can Test:
- **Dashboard** - http://127.0.0.1:8000/dashboard
- **Employees** - http://127.0.0.1:8000/employees (Main testing area)
- **Users** - http://127.0.0.1:8000/users (View created accounts)
- **Sections** - http://127.0.1:8000/sections (Required for employee creation)

### Yellow Banner Alert
All testing pages show a yellow warning banner indicating testing mode is active.

## ✅ What to Test

### 1. Create Employee Account
- Go to `/employees`
- Fill in all required fields:
  - Personal info (first, middle, last name)
  - Work info (section, position)
  - Account info (email, password, role)
- Submit and verify success message

### 2. Verify User Creation
- Go to `/users`
- Check that the new user appears in the list
- Verify employee details are linked correctly
- Confirm role is displayed

### 3. Check Employee List
- Back to `/employees`
- Verify employee shows up with role badge
- Test search functionality
- Verify all data displays correctly

### 4. Test Deletion (Optional)
- Click "Deactivate" on an employee
- Confirm both user and employee records are removed

## 🔧 What Was Changed for Testing

### Routes (routes/web.php)
```php
// Authentication middleware DISABLED
Route::middleware([])->group(function () { // Was: Route::middleware('auth')->group

// Permission middleware DISABLED
Route::middleware([])->group(function () { // Was: Route::middleware(['permission:...'])
```

### Views
- Uses `layouts.app-test` instead of `layouts.app`
- No `@can` directives requiring authentication
- Yellow warning banner on all pages

## 🔄 Re-enabling Authentication After Testing

### Step 1: Restore Routes
In `routes/web.php`, find and replace:

**Change:**
```php
Route::middleware([])->group(function () {
```

**Back to:**
```php
Route::middleware('auth')->group(function () {
```

**And restore all permission checks:**
```php
Route::middleware(['permission:employees.access'])->group(function () {
```

### Step 2: Restore Views
Change back to normal layout:

**In these files:**
- `resources/views/employees/index.blade.php`
- `resources/views/users/index.blade.php`
- `resources/views/dashboard.blade.php`

**Change:**
```blade
@extends('layouts.app-test')
```

**Back to:**
```blade
@extends('layouts.app')
```

**Remove the yellow banner:**
Delete the testing mode banner section from each view.

### Step 3: Restore Dashboard
In `routes/web.php`:

**Change:**
```php
Route::get('/', function () {
    return redirect('/employees');
});

Route::get('/dashboard', function () {
    return view('dashboard');
})->name('dashboard');
```

**Back to:**
```php
Route::get('/', function () {
    return redirect('/dashboard');
});

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');
```

### Step 4: Clear Caches
```bash
php artisan route:clear
php artisan view:clear
php artisan config:clear
php artisan permission:cache-reset
```

### Step 5: Restart Server
```bash
# Stop current server (Ctrl+C)
php artisan serve
```

## 📝 Quick Re-enable Script

Create a file `restore-auth.sh` (or run commands manually):

```bash
#!/bin/bash
echo "Restoring authentication..."

# This is just a reminder - you need to manually edit the files
echo "TODO: Restore routes/web.php"
echo "TODO: Restore view layouts"
echo "TODO: Run: php artisan route:clear"
echo "TODO: Run: php artisan view:clear"
echo "TODO: Run: php artisan config:clear"
echo "TODO: Run: php artisan permission:cache-reset"
echo "TODO: Restart server"

echo "Authentication will be fully restored after completing these steps."
```

## 🎯 Expected Test Results

### Success Indicators:
✅ Employee form submits without errors  
✅ User account is created in `users` table  
✅ Employee record is created in `employee` table  
✅ Both records are linked via `user_id`  
✅ Role is assigned to user account  
✅ Employee shows in employee list with role badge  
✅ User shows in users list with employee details  
✅ Search and filter work correctly  
✅ Deletion removes both records  

### If Something Goes Wrong:
- Check browser console for JavaScript errors
- Check `storage/logs/laravel.log` for backend errors
- Verify sections exist before creating employees
- Ensure email is unique
- Check all required fields are filled

## 💡 Tips for Testing

1. **Create sections first** - Employees require a section assignment
2. **Use test emails** - Use format: `test1@clinlab.test`, `test2@clinlab.test`, etc.
3. **Test different roles** - Try creating employees with each role type
4. **Check both views** - Verify data in both `/employees` and `/users`
5. **Test search** - Try searching by name, position, email
6. **Take notes** - Document any issues you find

## 🚀 Ready to Test!

Your application is now in testing mode. All authentication is bypassed so you can focus purely on testing the employee and user management functionality.

**Start here:** http://127.0.0.1:8000/employees

Happy testing! 🎉
