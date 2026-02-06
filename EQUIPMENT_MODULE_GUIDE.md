# Equipment Module - Implementation Guide

## Overview
Complete Equipment Management Module with maintenance and calibration tracking for clinical laboratory compliance.

## Database Tables

### 1. equipment (existing)
- asset_tag, name, model, serial_no
- section_id, supplier, purchase_date, status
- Custom soft delete: is_deleted, deleted_at, deleted_by

### 2. maintenance_record (new)
- equipment_id, performed_date, findings, action_taken
- performed_by, next_due_date, status

### 3. calibration_record (existing)
- equipment_id, calibration_date, next_calibration_date
- result_status, performed_by, notes

## Models & Relationships

### Equipment Model
```php
// Relationships added:
- hasMany maintenanceRecords
- hasMany calibrationRecords
- hasOne latestMaintenance (latest by performed_date)
- hasOne latestCalibration (latest by calibration_date)
```

### MaintenanceRecord Model
- belongsTo Equipment
- belongsTo Employee (performedBy)
- Methods: isDueSoon($days), isOverdue()

### CalibrationRecord Model
- belongsTo Equipment
- belongsTo Employee (performedBy)
- Methods: isDueSoon($days), isOverdue()

## Key Features Implemented

### 1. Alerts System
**Due Soon**: Records due within 7 days (configurable)
**Overdue**: Records past due date
- Separate panels for maintenance and calibration
- Color-coded badges (red=overdue, yellow=due soon)
- No duplicate alerts (uses latestMaintenance/latestCalibration)

### 2. Equipment List
Columns:
- Equipment (name, model, serial)
- Section
- Status (Active/Under Repair/Retired)
- Last Maintenance Date
- Next Maintenance Due (color-coded)
- Last Calibration Date
- Next Calibration Due (color-coded)
- Action buttons

### 3. Action Buttons
Each equipment row has:
- Log Maintenance (blue wrench icon)
- Log Calibration (purple checkmark icon)
- View Maintenance History (clock icon)
- View Calibration History (clipboard icon)

### 4. Logging Modals

**Maintenance Modal:**
- Performed Date (required)
- Findings (textarea)
- Action Taken (textarea)
- Next Due Date
- Status dropdown

**Calibration Modal:**
- Calibration Date (required)
- Next Calibration Date
- Result (pass/fail/conditional)
- Notes (textarea)

### 5. History Modals
Full-screen modals showing historical records in table format

### 6. Filters & Search
- Search: name, model, serial_no
- Section filter
- Status filter
- Alert filter (maintenance due / calibration due)
- Per page (10/15/25/50)

## Permissions (RBAC)

### Required Permissions:
```
equipment.access - View equipment page
equipment.create - Add equipment
equipment.manage - Edit equipment
maintenance.manage - Log maintenance records
calibration.manage - Log calibration records
```

### Permission Checks:
- Page access: `abort_unless(auth()->user()->can('equipment.access'), 403)`
- Conditional buttons: `@can('maintenance.manage')`

## Query Optimization

**Prevent N+1:**
- Eager load: section, latestMaintenance, latestCalibration

**Alert Queries:**
- Use whereHas + filter to get only equipment with due/overdue records
- Single latest record per equipment (no duplicates)

**Indexes:**
- equipment_id, performed_date, next_due_date on maintenance_record
- equipment_id, calibration_date, next_calibration_date on calibration_record

## Audit Trail Support

### Activity Logging:
- Uses LogsActivity trait
- Logs create/update/delete operations
- Tracks performed_by (employee_id)

### Soft Delete:
- Never hard delete equipment or records
- Preserve history for compliance
- Use is_deleted flag + deleted_at + deleted_by

## Migration Required

Run:
```bash
php artisan migrate
```

This creates the `maintenance_record` table.

## Route Configuration

Add to `routes/web.php`:
```php
Route::middleware(['auth', 'permission:equipment.access'])->group(function () {
    Route::get('/equipment', [EquipmentController::class, 'index'])->name('equipment.index');
});
```

Controller already exists and returns view('equipment.index').

## Livewire Component

File: `resources/views/components/equipment/⚡index.blade.php`
- Inline anonymous Livewire component (Volt)
- Follows project pattern
- Full CRUD via modals
- Real-time search and filtering

## Usage

1. Navigate to /equipment
2. View alerts panel at top (if any due/overdue)
3. Use filters to narrow down equipment
4. Click action buttons to:
   - Log new maintenance
   - Log new calibration
   - View historical records
5. Color-coded dates indicate urgency

## Compliance Features

✅ Complete audit trail
✅ No data loss (soft delete)
✅ Alert system prevents missed schedules
✅ Historical tracking for ISO 15189
✅ Permission-based access control
✅ Searchable and filterable records

## Next Steps

Optional enhancements:
1. PDF export of maintenance/calibration reports
2. Email notifications for due equipment
3. Dashboard widgets showing upcoming due dates
4. Batch operations (mark multiple as completed)
5. Equipment QR code generation for quick access
