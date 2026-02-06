<?php

namespace Database\Seeders;

use App\Models\CertificateTemplate;
use App\Models\User;
use Illuminate\Database\Seeder;

class CertificateTemplateSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $admin = User::first(); // Get first user (adjust as needed)

        // Calibration Certificate Template
        CertificateTemplate::create([
            'name' => 'Standard Calibration Certificate',
            'type' => 'calibration',
            'version' => '1.0',
            'is_active' => true,
            'created_by' => $admin->id ?? 1,
            'body_html' => '
<div style="font-family: Poppins, sans-serif; padding: 40px; max-width: 800px; margin: 0 auto;">
    <div style="text-align: center; margin-bottom: 30px;">
        <h1 style="color: #2563eb; margin: 0;">CALIBRATION CERTIFICATE</h1>
        <p style="color: #64748b; margin: 5px 0;">Certificate No: {{certificate_no}}</p>
    </div>

    <div style="border: 2px solid #2563eb; padding: 30px; border-radius: 8px;">
        <table style="width: 100%; border-collapse: collapse;">
            <tr>
                <td style="padding: 10px; width: 40%; font-weight: 600;">Equipment Name:</td>
                <td style="padding: 10px;">{{equipment_name}}</td>
            </tr>
            <tr style="background: #f8fafc;">
                <td style="padding: 10px; font-weight: 600;">Model:</td>
                <td style="padding: 10px;">{{equipment_model}}</td>
            </tr>
            <tr>
                <td style="padding: 10px; font-weight: 600;">Serial Number:</td>
                <td style="padding: 10px;">{{serial_no}}</td>
            </tr>
            <tr style="background: #f8fafc;">
                <td style="padding: 10px; font-weight: 600;">Calibration Date:</td>
                <td style="padding: 10px;">{{calibration_date}}</td>
            </tr>
            <tr>
                <td style="padding: 10px; font-weight: 600;">Due Date:</td>
                <td style="padding: 10px;">{{due_date}}</td>
            </tr>
            <tr style="background: #f8fafc;">
                <td style="padding: 10px; font-weight: 600;">Result:</td>
                <td style="padding: 10px;"><strong style="color: #10b981;">{{result}}</strong></td>
            </tr>
            <tr>
                <td style="padding: 10px; font-weight: 600;">Performed By:</td>
                <td style="padding: 10px;">{{performed_by}}</td>
            </tr>
            <tr style="background: #f8fafc;">
                <td style="padding: 10px; font-weight: 600;">Issue Date:</td>
                <td style="padding: 10px;">{{issue_date}}</td>
            </tr>
        </table>

        <div style="margin-top: 30px; padding-top: 20px; border-top: 1px solid #e2e8f0;">
            <p style="font-size: 12px; color: #64748b; margin: 5px 0;">Verification Code: {{verification_code}}</p>
            <p style="font-size: 12px; color: #64748b; margin: 5px 0;">This certificate is issued by the Clinical Laboratory Management System</p>
        </div>
    </div>

    <div style="margin-top: 40px; text-align: center;">
        <div style="display: inline-block; border-top: 2px solid #000; padding-top: 10px; min-width: 300px;">
            <p style="margin: 0; font-weight: 600;">Authorized Signatory</p>
            <p style="margin: 5px 0; color: #64748b; font-size: 14px;">Laboratory Manager</p>
        </div>
    </div>
</div>
            ',
        ]);

        // Maintenance Certificate Template
        CertificateTemplate::create([
            'name' => 'Standard Maintenance Certificate',
            'type' => 'maintenance',
            'version' => '1.0',
            'is_active' => true,
            'created_by' => $admin->id ?? 1,
            'body_html' => '
<div style="font-family: Poppins, sans-serif; padding: 40px; max-width: 800px; margin: 0 auto;">
    <div style="text-align: center; margin-bottom: 30px;">
        <h1 style="color: #10b981; margin: 0;">MAINTENANCE CERTIFICATE</h1>
        <p style="color: #64748b; margin: 5px 0;">Certificate No: {{certificate_no}}</p>
    </div>

    <div style="border: 2px solid #10b981; padding: 30px; border-radius: 8px;">
        <table style="width: 100%; border-collapse: collapse;">
            <tr>
                <td style="padding: 10px; width: 40%; font-weight: 600;">Equipment Name:</td>
                <td style="padding: 10px;">{{equipment_name}}</td>
            </tr>
            <tr style="background: #f8fafc;">
                <td style="padding: 10px; font-weight: 600;">Model:</td>
                <td style="padding: 10px;">{{equipment_model}}</td>
            </tr>
            <tr>
                <td style="padding: 10px; font-weight: 600;">Serial Number:</td>
                <td style="padding: 10px;">{{serial_no}}</td>
            </tr>
            <tr style="background: #f8fafc;">
                <td style="padding: 10px; font-weight: 600;">Maintenance Type:</td>
                <td style="padding: 10px;">{{maintenance_type}}</td>
            </tr>
            <tr>
                <td style="padding: 10px; font-weight: 600;">Service Date:</td>
                <td style="padding: 10px;">{{service_date}}</td>
            </tr>
            <tr style="background: #f8fafc;">
                <td style="padding: 10px; font-weight: 600;">Next Service Due:</td>
                <td style="padding: 10px;">{{next_due_date}}</td>
            </tr>
            <tr>
                <td style="padding: 10px; font-weight: 600;">Service Provider:</td>
                <td style="padding: 10px;">{{service_provider}}</td>
            </tr>
            <tr style="background: #f8fafc;">
                <td style="padding: 10px; font-weight: 600;">Status:</td>
                <td style="padding: 10px;"><strong style="color: #10b981;">{{status}}</strong></td>
            </tr>
            <tr>
                <td style="padding: 10px; font-weight: 600;">Issue Date:</td>
                <td style="padding: 10px;">{{issue_date}}</td>
            </tr>
        </table>

        <div style="margin-top: 20px; padding: 15px; background: #f0fdf4; border-radius: 6px;">
            <p style="margin: 0; font-weight: 600;">Remarks:</p>
            <p style="margin: 5px 0; color: #64748b;">{{remarks}}</p>
        </div>

        <div style="margin-top: 30px; padding-top: 20px; border-top: 1px solid #e2e8f0;">
            <p style="font-size: 12px; color: #64748b; margin: 5px 0;">Verification Code: {{verification_code}}</p>
            <p style="font-size: 12px; color: #64748b; margin: 5px 0;">This certificate is issued by the Clinical Laboratory Management System</p>
        </div>
    </div>

    <div style="margin-top: 40px; text-align: center;">
        <div style="display: inline-block; border-top: 2px solid #000; padding-top: 10px; min-width: 300px;">
            <p style="margin: 0; font-weight: 600;">Authorized Signatory</p>
            <p style="margin: 5px 0; color: #64748b; font-size: 14px;">Laboratory Manager</p>
        </div>
    </div>
</div>
            ',
        ]);

        // Safety Compliance Certificate Template
        CertificateTemplate::create([
            'name' => 'Standard Safety Compliance Certificate',
            'type' => 'safety',
            'version' => '1.0',
            'is_active' => true,
            'created_by' => $admin->id ?? 1,
            'body_html' => '
<div style="font-family: Poppins, sans-serif; padding: 40px; max-width: 800px; margin: 0 auto;">
    <div style="text-align: center; margin-bottom: 30px;">
        <h1 style="color: #f59e0b; margin: 0;">SAFETY COMPLIANCE CERTIFICATE</h1>
        <p style="color: #64748b; margin: 5px 0;">Certificate No: {{certificate_no}}</p>
    </div>

    <div style="border: 2px solid #f59e0b; padding: 30px; border-radius: 8px;">
        <table style="width: 100%; border-collapse: collapse;">
            <tr>
                <td style="padding: 10px; width: 40%; font-weight: 600;">Equipment Name:</td>
                <td style="padding: 10px;">{{equipment_name}}</td>
            </tr>
            <tr style="background: #f8fafc;">
                <td style="padding: 10px; font-weight: 600;">Model:</td>
                <td style="padding: 10px;">{{equipment_model}}</td>
            </tr>
            <tr>
                <td style="padding: 10px; font-weight: 600;">Serial Number:</td>
                <td style="padding: 10px;">{{serial_no}}</td>
            </tr>
            <tr style="background: #f8fafc;">
                <td style="padding: 10px; font-weight: 600;">Compliance Status:</td>
                <td style="padding: 10px;"><strong style="color: #10b981;">COMPLIANT</strong></td>
            </tr>
            <tr>
                <td style="padding: 10px; font-weight: 600;">Issue Date:</td>
                <td style="padding: 10px;">{{issue_date}}</td>
            </tr>
            <tr style="background: #f8fafc;">
                <td style="padding: 10px; font-weight: 600;">Valid Until:</td>
                <td style="padding: 10px;">{{valid_until}}</td>
            </tr>
        </table>

        <div style="margin-top: 30px; padding-top: 20px; border-top: 1px solid #e2e8f0;">
            <p style="font-size: 12px; color: #64748b; margin: 5px 0;">Verification Code: {{verification_code}}</p>
        </div>
    </div>

    <div style="margin-top: 40px; text-align: center;">
        <div style="display: inline-block; border-top: 2px solid #000; padding-top: 10px; min-width: 300px;">
            <p style="margin: 0; font-weight: 600;">Authorized Signatory</p>
        </div>
    </div>
</div>
            ',
        ]);
    }
}
