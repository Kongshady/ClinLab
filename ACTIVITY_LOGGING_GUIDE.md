# Activity Logging Implementation Guide

## Overview
The Activity Logs system automatically tracks all employee actions for audit purposes. This document explains how the system works and how to add logging to new features.

## How It Works

### 1. LogsActivity Trait
Location: `app/Traits/LogsActivity.php`

This trait provides a simple method to log activities:
```php
$this->logActivity("Description of the action");
```

The trait automatically:
- Gets the current logged-in employee ID
- Creates an activity log entry with timestamp
- Stores the description of the action

### 2. Database Table
Table: `activity_log`
Columns:
- `activity_log_id` - Primary key
- `employee_id` - Foreign key to employee table
- `datetime_added` - Timestamp of the activity
- `description` - Description of what happened (max 70 characters)
- `status_code` - Status code (default: 1 for active)

### 3. Activity Logs Page
Location: `/activity-logs`
Features:
- View all employee activities in chronological order
- Filter by employee, date range, or search description
- Displays employee avatar, name, position, and action description
- Pagination support (25, 50, 100, 200 per page)
- Modern, clean UI matching the inventory design

## How to Add Logging to Your Components

### Step 1: Import the Trait
In your Livewire component PHP section:

```php
use App\Traits\LogsActivity;

new class extends Component
{
    use LogsActivity; // Add this line
    
    // ... rest of your component
}
```

### Step 2: Call logActivity() Method
Add logging after successful operations:

```php
public function save()
{
    // Your save logic here
    $item = Item::create([...]);
    
    // Log the activity
    $this->logActivity("Created new item: {$item->label}");
    
    $this->flashMessage = 'Item created successfully!';
}
```

## Examples of Activity Logging

### Inventory Management (Already Implemented)
```php
// Stock In
$this->logActivity("Added {$quantity} units of {$itemName} to stock from {$supplier}");

// Stock Out
$this->logActivity("Removed {$quantity} units of {$itemName} from stock - {$reason}");

// Stock Usage
$this->logActivity("Recorded usage of {$quantity} units of {$itemName} for {$purpose}");
```

### Patient Management (Example to Implement)
```php
// Add Patient
$this->logActivity("Registered new patient: {$patient->firstname} {$patient->lastname}");

// Edit Patient
$this->logActivity("Updated patient information for {$patient->firstname} {$patient->lastname}");

// Delete Patient
$this->logActivity("Deleted patient: {$patient->firstname} {$patient->lastname}");
```

### Transaction Management (Example to Implement)
```php
// Create Transaction
$this->logActivity("Created transaction #{$transaction->transaction_id} for patient {$patientName}");

// Update Transaction
$this->logActivity("Updated transaction #{$transaction->transaction_id}");

// Void Transaction
$this->logActivity("Voided transaction #{$transaction->transaction_id}");
```

### Employee Management (Example to Implement)
```php
// Add Employee
$this->logActivity("Added new employee: {$employee->firstname} {$employee->lastname} as {$position}");

// Update Employee
$this->logActivity("Updated employee information for {$employee->firstname} {$employee->lastname}");

// Deactivate Employee
$this->logActivity("Deactivated employee: {$employee->firstname} {$employee->lastname}");
```

### Equipment Management (Example to Implement)
```php
// Add Equipment
$this->logActivity("Registered new equipment: {$equipment->label}");

// Calibrate Equipment
$this->logActivity("Calibrated equipment: {$equipment->label} - next due {$nextDate}");

// Retire Equipment
$this->logActivity("Retired equipment: {$equipment->label}");
```

## Best Practices

### 1. Be Descriptive
Good: "Added 50 units of Glucose Test Strips to stock from MedSupply Inc."
Bad: "Added stock"

### 2. Include Key Information
- Item names, quantities, dates
- Patient names or IDs
- Transaction IDs
- Equipment names
- Reasons for actions

### 3. Keep It Concise
Maximum 70 characters for the description field. Focus on the essential information.

### 4. Log Important Actions Only
Log these types of actions:
- ✅ Create, Update, Delete operations
- ✅ Status changes
- ✅ Stock movements
- ✅ Important configuration changes

Don't log these:
- ❌ Simple read/view operations
- ❌ Filter/search actions
- ❌ Navigation between pages

### 5. Format Consistently
Use consistent formatting:
- "Created [type]: [name]"
- "Updated [type]: [name]"
- "Deleted [type]: [name]"
- "Added [quantity] units of [item]"

## Viewing Activity Logs

1. Navigate to `/activity-logs` (Analytics > Activity Logs in sidebar)
2. Use filters to find specific activities:
   - Search by description or employee name
   - Filter by specific employee
   - Filter by date range
3. Adjust items per page (25, 50, 100, 200)

## Security & Permissions

- Activity Logs page requires `activity-logs.access` permission
- Only shows logs for active employees
- Logs cannot be edited (audit trail integrity)
- Logs are automatically timestamped

## Next Steps

To fully implement activity logging across the system:

1. Add LogsActivity trait to:
   - Patient management component
   - Transaction management component
   - Employee management component
   - Equipment management component
   - Physician management component
   - Test management component
   - Section management component
   - Item management component

2. Add appropriate logActivity() calls after each CRUD operation

3. Test by performing actions and checking Activity Logs page

## Troubleshooting

**Issue**: Logs not appearing
- Ensure LogsActivity trait is imported and used
- Verify employee_id is available (user must be logged in and linked to employee)
- Check that status_code exists (default is 1)

**Issue**: Error when logging
- Ensure description is under 70 characters
- Verify employee_id exists in employee table
- Check database connection

**Issue**: Cannot see Activity Logs page
- Verify user has `activity-logs.access` permission
- Check route is registered in web.php
