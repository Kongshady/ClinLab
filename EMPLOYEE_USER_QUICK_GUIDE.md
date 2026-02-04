# 🎯 Quick Guide: Employee & User Management

## 📋 Overview
The ClinLab system now uses the **employee table** as the primary data store for all employee information, with automatic linking to user accounts for login and permissions.

## 🔑 How It Works

### One Form, Two Records
When you create an employee, the system automatically creates:
1. **Employee Record** (in `employee` table) - stores personal and work info
2. **User Account** (in `users` table) - handles login and permissions
3. **Link** between them via `user_id` in employee table

## 📝 Creating a New Employee

### Step 1: Navigate to Employees
- Click **"Employees"** in the sidebar under "MIT Management"
- Or go to: `http://127.0.0.1:8000/employees`

### Step 2: Fill the Form

**Personal Information:**
- First Name *required*
- Middle Name (optional)
- Last Name *required*

**Work Information:**
- Section *required* (e.g., Clinical Chemistry, Hematology)
- Position/Job Title *required* (e.g., Medical Technologist)

**Login Account & Role:**
- Email (Username) *required* - This is their login username
- Password *required* - Minimum 6 characters
- System Role *required* - Choose from:
  - Laboratory Manager (Full access to all modules)
  - Staff-in-Charge (10 modules access)
  - MIT Staff (3 modules: Tests, Sections, Employees)
  - Secretary (2 modules: Transactions, Inventory)

### Step 3: Submit
- Click **"Create Employee Account"**
- System creates both employee record and user account
- Employee can immediately login with their email and password

## 👀 Viewing Data

### Employee View (HR/Admin Focus)
**Location:** `/employees`

Shows:
- Employee ID
- Full name with avatar
- Section assignment
- Position/job title
- Email/username
- Status (Active) + Role badge
- Edit/Deactivate actions

### User View (Account Management Focus)
**Location:** `/users`

Shows:
- User ID
- Account name
- Email
- Linked employee info (name, position)
- Section
- Assigned role(s)
- Registration date

## 🔍 Search & Filter

### On Employees Page:
- Search by: name, username, or position
- Real-time search updates

### On Users Page:
- Search by: name, email, or position
- Filter by: role (all roles, specific role)

## 🗑️ Deactivating an Employee

1. Go to Employees page
2. Find the employee in the list
3. Click **"Deactivate"** button
4. Confirm the action
5. System will:
   - Delete the user account (can't login anymore)
   - Soft delete the employee record (marked as deleted)

## 💡 Important Notes

### Data Storage
- ✅ Employee data is stored in `employee` table
- ✅ User authentication is in `users` table
- ✅ Both are linked via `user_id`

### Relationships
```
User (users table)
  └─ has one Employee (employee table)
      └─ belongs to Section (section table)
```

### Permissions
- Only users with `employees.access` permission can:
  - View employees
  - Create new employees
  - View user accounts
  - Deactivate employees

## 📊 Example Scenarios

### Scenario 1: New Medical Technologist
```
Personal Info:
  First Name: Maria
  Middle Name: Santos
  Last Name: Garcia

Work Info:
  Section: Hematology
  Position: Medical Technologist II

Account:
  Email: maria.garcia@clinlab.test
  Password: SecurePass123
  Role: Staff-in-Charge
```

**Result:**
- Maria can login at `/login` with email and password
- She has access to 10 modules (Staff-in-Charge permissions)
- Her employee record shows section and position
- Her user account has Staff-in-Charge role

### Scenario 2: Laboratory Manager
```
Personal Info:
  First Name: John
  Middle Name: Paul
  Last Name: Reyes

Work Info:
  Section: Clinical Chemistry
  Position: Laboratory Manager

Account:
  Email: john.reyes@clinlab.test
  Password: Manager2026
  Role: Laboratory Manager
```

**Result:**
- John can access all 14 modules
- Full CRUD permissions on all features
- Can manage calibration, certificates, activity logs

## 🎨 Visual Indicators

### Avatar Badges
- Colored circles with employee initials
- Blue gradient for employees
- Purple gradient for users page

### Status Badges
- 🟢 **Green "Active"** - Employee is active
- 🔵 **Blue "Role Name"** - Shows assigned system role

## 📱 Accessing the Pages

### Employees Page
```
URL: http://127.0.0.1:8000/employees
Menu: Sidebar → MIT Management → Employees
```

### Users Page
```
URL: http://127.0.0.1:8000/users
Menu: Sidebar → MIT Management → User Accounts
```

## ⚠️ Things to Remember

1. **Email must be unique** - Cannot create two employees with same email
2. **Role is required** - Every employee must have a system role
3. **Section is required** - Employee must be assigned to a section
4. **Password minimum** - At least 6 characters
5. **Deactivation is permanent** - Deleted users cannot login (you'd need to recreate them)

## 🔄 Data Flow Diagram

```
Employee Form Submission
         │
         ├─→ Create User Account
         │   ├─ Email & Password
         │   ├─ Name (from employee info)
         │   └─ Assign Role
         │
         ├─→ Create Employee Record
         │   ├─ Link to User (user_id)
         │   ├─ Personal Info
         │   ├─ Work Info
         │   └─ Set Active Status
         │
         └─→ Display in Lists
             ├─ Employees Page (with role badge)
             └─ Users Page (with employee details)
```

## 🎓 Quick Tips

- ✅ Always assign appropriate role based on job responsibilities
- ✅ Use descriptive positions (e.g., "Senior Medical Technologist" not just "Staff")
- ✅ Ensure email follows your organization's format
- ✅ Use the Users page to see account-level information
- ✅ Use the Employees page to manage HR information

## 🆘 Troubleshooting

**Problem:** Can't create employee
- Check if email is already used
- Ensure all required fields are filled
- Verify you have `employees.access` permission

**Problem:** Employee not showing in list
- Check if search filter is active
- Verify employee wasn't deactivated
- Refresh the page

**Problem:** Role not displaying
- Check if role was properly assigned during creation
- Verify Spatie permissions are cached correctly
- Run: `php artisan permission:cache-reset`

## 🚀 Server Information

**Development Server:**
```
http://127.0.0.1:8000
```

**Login Page:**
```
http://127.0.0.1:8000/login
```

**Test Accounts:**
```
Laboratory Manager: manager@clinlab.test / password
Staff-in-Charge:   staff@clinlab.test / password
MIT Staff:         mit@clinlab.test / password
Secretary:         secretary@clinlab.test / password
```
