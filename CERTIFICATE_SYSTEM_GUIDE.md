# Certificate Generation System - Installation & Setup Guide

## 🚀 Installation Steps

### 1. Install DomPDF Package
```bash
composer require barryvdh/laravel-dompdf
```

### 2. Run Migrations
```bash
php artisan migrate
```

This will create:
- `certificate_templates` table
- `certificate_issues` table

### 3. Seed Certificate Templates
```bash
php artisan db:seed --class=CertificateTemplateSeeder
```

This creates 3 default templates:
- **Calibration Certificate** (blue theme)
- **Maintenance Certificate** (green theme)
- **Safety Compliance Certificate** (amber theme)

### 4. Configure Permissions (if not already set)

Add these permissions to your database if needed:
```sql
INSERT INTO user_permissions (name, guard_name) VALUES 
('certificates.access', 'web'),
('certificates.create', 'web'),
('certificates.edit', 'web'),
('certificates.delete', 'web');
```

Assign to Laboratory Manager role.

---

## 📋 Features Overview

### **1. Certificate Templates Management**
- **URL**: `/certificates-templates`
- **Permission**: `certificates.access`
- **Features**:
  - Create/Edit/Delete templates
  - Support for 4 types: calibration, maintenance, safety, test
  - Version control
  - Activate/Deactivate templates
  - HTML template editor with placeholders

### **2. Issued Certificates**
- **URL**: `/certificates-issued`
- **Permission**: `certificates.access`
- **Features**:
  - View all issued certificates
  - Filter by status (Issued/Revoked/Expired)
  - Filter by type
  - Date range filtering
  - Download PDF
  - Revoke certificates
  - Statistics dashboard

### **3. Certificate Verification**
- **URL**: `/certificates-verify`
- **Permission**: Public (no auth required)
- **Features**:
  - Verify certificate by number or verification code
  - Display certificate details
  - Show validity status
  - Public-facing validation

### **4. Generate Certificate from Calibration**
- **Location**: Calibration Records page
- **Action**: Click "Certificate" button in the actions column
- **Process**:
  1. Selects active calibration template
  2. Pulls calibration & equipment data
  3. Generates unique certificate number (CAL-2026-00001)
  4. Creates verification code
  5. Fills template placeholders
  6. Generates & downloads PDF
  7. Stores certificate record in database

---

## 🔧 Available Template Placeholders

### **Calibration Certificates**
```
{{certificate_no}}          - Unique certificate number
{{verification_code}}       - Unique verification code
{{issue_date}}             - Certificate issue date
{{equipment_name}}         - Equipment name
{{equipment_model}}        - Equipment model
{{serial_no}}              - Equipment serial number
{{calibration_date}}       - Calibration performed date
{{due_date}}              - Next calibration due date
{{result}}                - Calibration result (PASS/FAIL/CONDITIONAL)
{{performed_by}}          - Technician who performed calibration
```

### **Maintenance Certificates**
```
{{certificate_no}}
{{verification_code}}
{{issue_date}}
{{equipment_name}}
{{equipment_model}}
{{serial_no}}
{{maintenance_type}}      - Type of maintenance
{{service_date}}          - Date of service
{{next_due_date}}        - Next maintenance due
{{service_provider}}     - Who performed service
{{status}}               - Status
{{remarks}}              - Additional notes
```

---

## 📂 Files Created

### **Migrations**
- `database/migrations/2026_02_06_000001_create_certificate_templates_table.php`
- `database/migrations/2026_02_06_000002_create_certificate_issues_table.php`

### **Models**
- `app/Models/CertificateTemplate.php`
- `app/Models/CertificateIssue.php`

### **Services**
- `app/Services/CertificateService.php` - Certificate generation logic

### **Controllers**
- `app/Http/Controllers/CertificateController.php` (updated)

### **Livewire Components**
- `resources/views/components/certificates/templates/⚡index.blade.php`
- `resources/views/components/certificates/issued/⚡index.blade.php`
- `resources/views/components/certificates/verify/⚡index.blade.php`

### **Views**
- `resources/views/certificates/templates/index.blade.php`
- `resources/views/certificates/issued/index.blade.php`
- `resources/views/certificates/verify/index.blade.php`

### **Seeders**
- `database/seeders/CertificateTemplateSeeder.php`

### **Routes**
Updated `routes/web.php` with:
```php
Route::get('certificates-templates', [CertificateController::class, 'templates'])->name('certificates.templates');
Route::get('certificates-issued', [CertificateController::class, 'issued'])->name('certificates.issued');
Route::get('certificates-verify', [CertificateController::class, 'verify'])->name('certificates.verify'); // Public
```

### **Updated Files**
- `resources/views/components/calibration/⚡index.blade.php` - Added generate certificate button

---

## 🎯 Usage Examples

### **1. Create Custom Template**
1. Go to `/certificates-templates`
2. Click "New Template"
3. Fill in:
   - **Name**: "Premium Calibration Certificate"
   - **Type**: Calibration
   - **Version**: 2.0
   - **Active**: ✓
   - **HTML**: Paste your custom HTML with placeholders
4. Click "Create Template"

### **2. Generate Certificate**
1. Go to calibration records
2. Find the calibration record
3. Click "Certificate" button
4. PDF downloads automatically
5. Certificate record saved in database

### **3. Verify Certificate**
1. Go to `/certificates-verify`
2. Enter certificate number (e.g., CAL-2026-00001)
3. Click "Verify Certificate"
4. See validation status and details

### **4. Revoke Certificate**
1. Go to `/certificates-issued`
2. Find certificate
3. Click "Revoke"
4. Certificate marked as revoked (cannot be un-revoked)

---

## 🔒 Security Features

1. **Unique Certificate Numbers**: Auto-generated (CAL-2026-00001)
2. **Verification Codes**: Random 16-character codes
3. **Immutable Records**: Issued certificates cannot be edited
4. **Revocation System**: Certificates can be revoked but not deleted
5. **Audit Trail**: Tracks who generated each certificate
6. **Public Verification**: Anyone can verify certificate authenticity

---

## 📊 Certificate Number Format

```
CAL-2026-00001
 |    |    |
 |    |    └── Sequential number (5 digits)
 |    └── Year
 └── Prefix (CAL = Calibration, MAINT = Maintenance)
```

---

## ⚠️ Important Notes

1. **Templates**: At least one active template per type must exist before generating certificates
2. **Permissions**: Make sure Laboratory Manager role has `certificates.access` permission
3. **DomPDF**: Required for PDF generation - install via composer
4. **Verification**: The verify page is public and does not require authentication
5. **Storage**: PDFs are generated on-the-fly, not stored (can be modified to store if needed)

---

## 🛠️ Troubleshooting

### "No active template found"
- Go to `/certificates-templates`
- Ensure template for the type (calibration/maintenance) is marked as Active
- Run seeder if no templates exist: `php artisan db:seed --class=CertificateTemplateSeeder`

### "Certificate not found" on verify page
- Check certificate number spelling
- Ensure certificate exists in `certificate_issues` table
- Try verification code instead of certificate number

### PDF not generating
- Ensure DomPDF is installed: `composer require barryvdh/laravel-dompdf`
- Check storage permissions
- Verify template HTML is valid

---

## 🎨 Customization

### **Custom Template Design**
Edit template HTML in the template editor. Use:
- Inline CSS styles (DomPDF doesn't support external CSS well)
- Tables for layout
- Placeholders in double curly braces: `{{placeholder_name}}`

### **Add New Certificate Type**
1. Update `type` enum in migration
2. Add new template in seeder
3. Create generation method in `CertificateService.php`
4. Add generate button in relevant module

### **Customize Certificate Number Format**
Edit `generateCertificateNumber()` method in `CertificateService.php`

---

## ✅ System Complete

Your certificate generation system is now fully integrated and ready to use!

**Next Steps:**
1. Install DomPDF package
2. Run migrations
3. Seed templates
4. Test certificate generation from calibration records
5. Customize templates as needed

**Questions?** Check the code comments or review the implementation files listed above.
