# Role-Based Dashboard System Guide

## Overview
This Clinical Lab Application now features a complete Role-Based Access Control (RBAC) dashboard system where each role has a customized dashboard experience with role-specific widgets, metrics, and quick actions.

## Implementation Architecture

### 1. Controller: DashboardController.php
**Location**: `app/Http/Controllers/DashboardController.php`

**Purpose**: Handles role-based dashboard routing and redirection

**Key Methods**:
- `index()` - Smart redirector that checks user's role and redirects to appropriate dashboard
- `manager()` - Displays Laboratory Manager dashboard
- `staff()` - Displays Staff-in-Charge dashboard  
- `mit()` - Displays MIT Staff dashboard
- `secretary()` - Displays Secretary dashboard

**Role Priority** (for users with multiple roles):
1. Laboratory Manager
2. MIT Staff
3. Staff-in-Charge
4. Secretary
5. Default fallback → Staff dashboard

### 2. Routes Configuration
**Location**: `routes/web.php`

**Dashboard Routes**:
```php
// Main dashboard - redirects based on role
Route::get('/dashboard', [DashboardController::class, 'index'])
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

// Role-specific dashboard routes
Route::get('/dashboard/manager', [DashboardController::class, 'manager'])
    ->middleware(['auth', 'verified', 'role:Laboratory Manager'])
    ->name('dashboard.manager');

Route::get('/dashboard/staff', [DashboardController::class, 'staff'])
    ->middleware(['auth', 'verified', 'role:Staff-in-Charge'])
    ->name('dashboard.staff');

Route::get('/dashboard/mit', [DashboardController::class, 'mit'])
    ->middleware(['auth', 'verified', 'role:MIT Staff'])
    ->name('dashboard.mit');

Route::get('/dashboard/secretary', [DashboardController::class, 'secretary'])
    ->middleware(['auth', 'verified', 'role:Secretary'])
    ->name('dashboard.secretary');
```

**Middleware Protection**:
- `auth` - Must be logged in
- `verified` - Email must be verified
- `role:RoleName` - Must have specific role (uses Spatie Laravel Permission)

### 3. Dashboard Views

#### Laboratory Manager Dashboard
**File**: `resources/views/dashboards/manager.blade.php`

**Key Widgets**:
- Low Stock Items count
- Out of Stock count
- Due Soon Maintenance (equipment due within 7 days)
- Certificates Issued this month

**Quick Actions**:
- Reports (with permission check)
- Inventory management
- Equipment management
- Certificates

**Additional Features**:
- Recent Activity feed (last 8 activities)
- Real-time statistics with 5-minute cache
- Permission-based menu visibility using `@can()` directives

#### Staff-in-Charge Dashboard
**File**: `resources/views/dashboards/staff.blade.php`

**Key Widgets**:
- Patients Today count
- Pending Lab Results encoding
- Equipment Alerts (due soon maintenance)
- Total Patients in system

**Quick Actions**:
- Patient management
- Lab Results encoding
- Equipment monitoring
- Inventory shortcuts

**Additional Features**:
- Today's Patients table (last 5 patients)
- Quick patient lookup
- Real-time patient statistics

#### MIT Staff Dashboard
**File**: `resources/views/dashboards/mit.blade.php`

**Key Widgets**:
- Total Tests in system
- Total Sections
- Total Employees
- Total Equipment

**Management Tools** (8 quick actions):
- Manage Tests
- Manage Sections
- Manage Employees
- Equipment management
- Physicians management
- Permissions management
- Roles management

**Additional Features**:
- Recent Tests list (last 5)
- Recent Employees list (last 5)
- System overview with full admin capabilities

#### Secretary Dashboard
**File**: `resources/views/dashboards/secretary.blade.php`

**Key Widgets**:
- Transactions Today count
- Stock In Today count
- Stock Out Today count
- Low Stock Items alert

**Inventory Management Quick Actions**:
- Transactions
- Stock In
- Stock Out
- All Items

**Additional Features**:
- Low Stock Alert table (top 10 low stock items)
- Stock status indicators (Out of Stock / Low Stock)
- Current stock vs threshold comparison

### 4. Caching Strategy

All dashboards implement **5-minute cache** for statistics to improve performance:

```php
$stats = Cache::remember('role_dashboard_stats', 300, function() {
    // Expensive database queries here
    return [...];
});
```

**Cache Keys by Role**:
- `manager_dashboard_stats` - Laboratory Manager statistics
- `staff_dashboard_stats` - Staff-in-Charge statistics
- `mit_dashboard_stats` - MIT Staff statistics
- `secretary_dashboard_stats` - Secretary statistics
- `manager_recent_activities` - Recent activities for managers

**Clear Cache**: Run `php artisan optimize:clear` after any changes

## User Experience Flow

### Login Flow
1. User logs in via authentication system
2. Laravel authentication redirects to `/dashboard`
3. DashboardController `index()` method checks user roles
4. User is redirected to their role-specific dashboard
5. Middleware verifies user has permission to access that dashboard

### Example Scenarios

**Scenario 1**: Laboratory Manager Login
- User: `manager@clinlab.com` with role "Laboratory Manager"
- Login → `/dashboard` → Redirected to `/dashboard/manager`
- Sees: Low stock alerts, maintenance schedules, certificates, reports shortcuts

**Scenario 2**: MIT Staff Login  
- User: `mit@clinlab.com` with role "MIT Staff"
- Login → `/dashboard` → Redirected to `/dashboard/mit`
- Sees: Tests, Sections, Employees management, System overview

**Scenario 3**: Secretary Login
- User: `secretary@clinlab.com` with role "Secretary"
- Login → `/dashboard` → Redirected to `/dashboard/secretary`
- Sees: Transactions, Stock In/Out, Low stock inventory alerts

**Scenario 4**: User with Multiple Roles
- User has both "MIT Staff" and "Secretary" roles
- Login → `/dashboard` → Redirected to `/dashboard/mit` (MIT Staff has higher priority)

## Security Features

### Role-Based Middleware Protection
Each dashboard route is protected by the `role:RoleName` middleware:
- Prevents unauthorized access to other role dashboards
- Returns 403 Forbidden if user doesn't have required role
- Uses Spatie Laravel Permission package for role checking

### Permission-Based UI Elements
All quick action buttons use `@can()` directive:
```blade
@can('reports.access')
    <a href="{{ route('reports.index') }}">Reports</a>
@endcan
```

This ensures:
- Users only see menu items they have permission to access
- No broken links to restricted areas
- Clean, relevant UI for each role

## Design Features

### Modern UI Components
- **Gradient Cards**: Color-coded metrics (blue, green, yellow, red, purple, orange)
- **SVG Icons**: Heroicons for all widgets and actions
- **Responsive Grid**: 1 col mobile, 2 cols tablet, 4 cols desktop
- **Hover States**: Interactive button effects on quick actions
- **Status Badges**: Color-coded status indicators (green=active, yellow=low stock, red=out of stock)

### Color Coding by Dashboard
- **Manager**: Focus on alerts (yellow/orange warnings)
- **Staff**: Focus on operations (blue patient-centric)
- **MIT**: Focus on system management (purple/indigo admin controls)
- **Secretary**: Focus on inventory (green transactions, yellow alerts)

## Testing the System

### Test Each Role Dashboard

1. **Test Laboratory Manager**:
   ```bash
   # Login as manager user
   # Should redirect to /dashboard/manager
   # Verify: Low stock widget, maintenance alerts, certificates widget
   ```

2. **Test Staff-in-Charge**:
   ```bash
   # Login as staff user
   # Should redirect to /dashboard/staff
   # Verify: Patients today, pending results, today's patients table
   ```

3. **Test MIT Staff**:
   ```bash
   # Login as MIT user
   # Should redirect to /dashboard/mit
   # Verify: Management tools, tests/sections/employees counts
   ```

4. **Test Secretary**:
   ```bash
   # Login as secretary user
   # Should redirect to /dashboard/secretary
   # Verify: Transactions today, stock in/out, low stock alert table
   ```

### Test Role Middleware Protection

Try accessing dashboard URLs directly without proper role:
```
# As Secretary, try to access Manager dashboard
GET /dashboard/manager
Expected: 403 Forbidden or redirect

# As Staff, try to access MIT dashboard  
GET /dashboard/mit
Expected: 403 Forbidden or redirect
```

## Database Requirements

Make sure these models exist and relationships are properly defined:

**Models Used**:
- Patient (with `is_deleted`, `datetime_added`)
- Item (with `stock_quantity`, `low_stock_threshold`, `is_deleted`)
- Equipment (with `is_deleted`, `maintenanceRecords` relationship)
- Transaction (with `is_deleted`, `datetime_added`)
- StockIn (with `is_deleted`, `datetime_added`)
- StockOut (with `is_deleted`, `datetime_added`)
- LabResult (with `status` field)
- Certificate (with `datetime_added`)
- ActivityLog (with `employee` relationship, `action`, `datetime_added`)
- Test (with `is_deleted`, `section` relationship)
- Section (with `is_deleted`)
- Employee (with `is_deleted`, `role` relationship)

**Relationships Required**:
- Equipment → hasMany MaintenanceRecords
- ActivityLog → belongsTo Employee
- Test → belongsTo Section
- Employee → belongsTo Role

## Performance Considerations

### Cache Strategy
- All dashboard statistics cached for **5 minutes** (300 seconds)
- Reduces database load significantly
- Cache keys are role-specific to avoid conflicts

### Query Optimization
- Use `select()` to limit fields retrieved
- Eager load relationships with `with()`
- Add `limit()` to recent items queries
- Use `whereRaw()` for low stock comparisons

### Recommended Indexes
Add these indexes for better performance:
```sql
-- Items table
CREATE INDEX idx_items_stock ON items(stock_quantity, low_stock_threshold, is_deleted);

-- Patients table  
CREATE INDEX idx_patients_datetime ON patients(datetime_added, is_deleted);

-- Transactions table
CREATE INDEX idx_transactions_datetime ON transactions(datetime_added, is_deleted);

-- Equipment maintenance
CREATE INDEX idx_maintenance_next_due ON maintenance_records(next_due_date);
```

## Next Steps

### Optional Enhancements

1. **Add Charts/Graphs** using Chart.js or ApexCharts:
   - Monthly transaction trends
   - Patient visit patterns
   - Stock movement analytics

2. **Real-time Updates** using Laravel Broadcast:
   - Live patient counter
   - Real-time low stock alerts
   - Activity feed auto-refresh

3. **Export Capabilities**:
   - Export dashboard statistics to PDF
   - Generate weekly/monthly reports
   - Email digest for managers

4. **Customizable Widgets**:
   - Allow users to show/hide widgets
   - Reorder dashboard components
   - Set custom date ranges for statistics

5. **Dark Mode Toggle**:
   - Add theme switcher
   - Save preference in user settings

## Troubleshooting

### Issue: Redirects to wrong dashboard
**Solution**: Check user's role assignment in database. Verify role priority in DashboardController `index()` method.

### Issue: 403 Forbidden on dashboard access
**Solution**: 
- Verify user has the required role
- Check middleware is properly configured
- Run `php artisan permission:cache-reset`

### Issue: Statistics showing incorrect data
**Solution**:
- Clear cache: `php artisan cache:clear`
- Verify database relationships are working
- Check model scope for `is_deleted` filters

### Issue: Quick action links not appearing
**Solution**:
- Verify user has required permissions
- Check route names match in `@can()` directives
- Ensure routes are defined in web.php

### Issue: Dashboard loading slowly
**Solution**:
- Verify cache is working (5-minute cache should be active)
- Check database indexes are created
- Consider increasing cache duration for less critical stats

## Files Modified/Created

**Created**:
- `app/Http/Controllers/DashboardController.php` ✅
- `resources/views/dashboards/manager.blade.php` ✅
- `resources/views/dashboards/staff.blade.php` ✅
- `resources/views/dashboards/mit.blade.php` ✅
- `resources/views/dashboards/secretary.blade.php` ✅

**Modified**:
- `routes/web.php` - Added DashboardController import and 5 dashboard routes ✅

## Summary

The role-based dashboard system is **fully implemented** with:
- ✅ Smart role-based routing
- ✅ 4 unique dashboard experiences
- ✅ Permission-based UI elements
- ✅ 5-minute caching for performance
- ✅ Modern, responsive design
- ✅ Security middleware protection
- ✅ Quick action shortcuts per role
- ✅ Real-time statistics and metrics

Each role now has a tailored dashboard experience that shows only the information and tools relevant to their responsibilities in the Clinical Laboratory workflow.
