# ClinLab Implementation Roadmap

## Current Status

### ✅ Completed
1. **Authentication** - Laravel Breeze with custom dark theme login
2. **RBAC** - Spatie Laravel Permission with 4 roles and permissions
3. **Layout** - Light theme sidebar with role-based navigation
4. **Patients** - Basic CRUD (needs enhancement for spec compliance)
5. **Physicians** - Basic CRUD (ready)
6. **Tests** - Basic CRUD (needs enhancement)
7. **Sections** - Basic CRUD (ready)
8. **Employees** - Basic CRUD (needs role assignment UI)

### 🔄 Partially Complete
- **Dashboard** - Basic layout exists, needs real data widgets
- **Certificates** - Component exists, needs templates + issuance logic
- **Calibration** - Component exists, needs full implementation
- **Transactions** - Component exists, needs enhancement

### ❌ Not Started
- **Lab Results** - Patient detail + results entry
- **Items** - Supplies master list
- **Inventory** - Stock In/Out/Usage tracking
- **Equipment** - Equipment registry
- **Maintenance** - Maintenance scheduling and records
- **Reports** - Comprehensive reporting module
- **Activity Logs** - Audit trail viewer

---

## Implementation Priority

### Phase 1: Core Laboratory Operations (Week 1-2)
1. **Lab Results Module** - Critical for lab operations
   - Patient detail page
   - Result header + items
   - Result entry with tests selection
   
2. **Transactions Module** - Revenue tracking
   - Transaction header + details
   - OR number tracking
   - Receipt printing

3. **Dashboard Enhancement** - Management overview
   - Real-time statistics
   - Alerts and notifications
   - Recent activity feed

### Phase 2: Inventory Management (Week 3)
4. **Items Master** - Supplies catalog
5. **Inventory Tracking** - Stock movements
6. **Low Stock Alerts** - Automated notifications

### Phase 3: Equipment & Quality (Week 4)
7. **Equipment Registry** - Asset management
8. **Maintenance Module** - Preventive maintenance
9. **Calibration Module** - Quality assurance
10. **Certificates** - Certificate generation

### Phase 4: Reporting & Audit (Week 5)
11. **Reports Module** - Business intelligence
12. **Activity Logs** - Audit trail

---

## Database Schema Requirements

### Patients Enhancement
```sql
ALTER TABLE patient ADD COLUMN person_type VARCHAR(20); -- Student/Employee/Walk-in
ALTER TABLE patient ADD COLUMN external_ref_id VARCHAR(50);
```

### Lab Results Tables
```sql
CREATE TABLE lab_results (
    result_id BIGINT PRIMARY KEY,
    patient_id BIGINT,
    physician_id BIGINT NULL,
    result_date DATE,
    remarks TEXT,
    created_by BIGINT,
    status_code INT,
    is_deleted BIT DEFAULT 0
);

CREATE TABLE lab_result_items (
    result_item_id BIGINT PRIMARY KEY,
    result_id BIGINT,
    lab_test_id BIGINT,
    result_value VARCHAR(255),
    unit_used VARCHAR(50),
    reference_range_used VARCHAR(100),
    flag VARCHAR(20), -- Normal/High/Low
    notes TEXT
);
```

### Inventory Tables
```sql
CREATE TABLE items (
    item_id BIGINT PRIMARY KEY,
    item_name VARCHAR(255),
    section_id BIGINT,
    unit VARCHAR(50),
    reorder_level INT,
    expiry_tracking BIT DEFAULT 0,
    supplier_id BIGINT NULL,
    status_code INT,
    is_deleted BIT DEFAULT 0
);

CREATE TABLE inventory_txns (
    txn_id BIGINT PRIMARY KEY,
    item_id BIGINT,
    txn_type VARCHAR(20), -- IN/OUT/ADJUST/USAGE
    quantity DECIMAL(10,2),
    txn_date DATETIME,
    reference_no VARCHAR(100),
    performed_by BIGINT,
    remarks TEXT
);
```

### Equipment Tables
```sql
CREATE TABLE equipment (
    equipment_id BIGINT PRIMARY KEY,
    section_id BIGINT,
    supplier_id BIGINT NULL,
    asset_tag VARCHAR(100) UNIQUE,
    equipment_name VARCHAR(255),
    model VARCHAR(100),
    serial_no VARCHAR(100),
    status_code INT,
    is_deleted BIT DEFAULT 0
);

CREATE TABLE maintenance_schedule (
    schedule_id BIGINT PRIMARY KEY,
    equipment_id BIGINT,
    maintenance_type VARCHAR(100),
    frequency_days INT,
    next_due_date DATE
);

CREATE TABLE maintenance_record (
    record_id BIGINT PRIMARY KEY,
    equipment_id BIGINT,
    performed_date DATE,
    findings TEXT,
    action_taken TEXT,
    performed_by BIGINT,
    status VARCHAR(50)
);
```

### Certificates Tables  
```sql
CREATE TABLE certificate_template (
    template_id BIGINT PRIMARY KEY,
    name VARCHAR(255),
    type VARCHAR(50),
    body_html TEXT,
    version INT,
    is_active BIT DEFAULT 1
);

CREATE TABLE certificate_issue (
    certificate_id BIGINT PRIMARY KEY,
    template_id BIGINT,
    certificate_no VARCHAR(100) UNIQUE,
    verification_code VARCHAR(100) UNIQUE,
    issue_date DATE,
    valid_until DATE,
    equipment_id BIGINT NULL,
    calibration_id BIGINT NULL,
    maintenance_id BIGINT NULL,
    generated_by BIGINT
);
```

---

## Next Steps

1. Review existing database schema in `database/migrations/`
2. Identify which tables already exist vs need creation
3. Create missing Livewire components
4. Implement critical workflows first (Lab Results, Transactions)
5. Add validation and permission checks
6. Create reports and dashboards
7. Test with real data
8. Deploy and train users

---

## Development Guidelines

- Use Livewire Volt-style components for consistency
- Apply `.card`, `.input-field`, `.table`, `.btn-primary` CSS classes
- Implement soft deletes everywhere
- Add permission checks using `@can` directives
- Log all critical actions to activity_log table
- Optimize queries with eager loading
- Add search and pagination to all list views
- Validate to prevent negative stock
- Generate unique codes for certificates
- Make reports exportable (PDF/CSV)

