<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\PatientController;
use App\Http\Controllers\PhysicianController;
use App\Http\Controllers\LabResultController;
use App\Http\Controllers\TestController;
use App\Http\Controllers\CertificateController;
use App\Http\Controllers\TransactionController;
use App\Http\Controllers\ItemController;
use App\Http\Controllers\EquipmentController;
use App\Http\Controllers\CalibrationRecordController;
use App\Http\Controllers\SectionController;
use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\ActivityLogController;
use App\Http\Controllers\AccountSettingsController;
use App\Http\Controllers\InventoryController;
use App\Http\Controllers\ReportsController;
use App\Http\Controllers\PatientCertificateController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect('/dashboard');
});

// Main dashboard - redirects to role-specific dashboard
Route::get('/dashboard', [DashboardController::class, 'index'])
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

// Role-specific dashboards
Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/dashboard/manager', [DashboardController::class, 'manager'])
        ->middleware('role:Laboratory Manager')
        ->name('dashboard.manager');
    
    Route::get('/dashboard/staff', [DashboardController::class, 'staff'])
        ->middleware('role:Staff-in-Charge')
        ->name('dashboard.staff');
    
    Route::get('/dashboard/mit', [DashboardController::class, 'mit'])
        ->middleware('role:MIT Staff')
        ->name('dashboard.mit');
    
    Route::get('/dashboard/secretary', [DashboardController::class, 'secretary'])
        ->middleware('role:Secretary')
        ->name('dashboard.secretary');
});

// Patient Dashboard (role: Patient)
Route::middleware(['auth', 'role:Patient'])->prefix('patient')->group(function () {
    Route::get('/dashboard', function () {
        return view('patient.dashboard');
    })->name('patient.dashboard');

    Route::get('/certificate/download', [PatientCertificateController::class, 'download'])
        ->name('patient.certificate.download');
});

// Public certificate verification (no auth required)
Route::get('/certificates/verify', [PatientCertificateController::class, 'verify'])
    ->name('certificates.public.verify');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
    
    // Account Settings
    Route::get('/account-settings', [AccountSettingsController::class, 'index'])->name('account.settings');
    Route::post('/account-settings/password', [AccountSettingsController::class, 'updatePassword'])->name('account.update-password');
    
    // Patient Management
    Route::middleware(['permission:patients.access'])->group(function () {
        Route::resource('patients', PatientController::class);
    });
    
    Route::middleware(['permission:physicians.access'])->group(function () {
        Route::resource('physicians', PhysicianController::class);
    });
    
    // Laboratory
    Route::middleware(['permission:lab-results.access'])->group(function () {
        Route::resource('lab-results', LabResultController::class);
    });
    
    Route::middleware(['permission:tests.access'])->group(function () {
        Route::resource('tests', TestController::class);
    });
    
    Route::middleware(['permission:certificates.access'])->group(function () {
        Route::resource('certificates', CertificateController::class)->only(['index', 'show']);
        Route::get('certificates-templates', [CertificateController::class, 'templates'])->name('certificates.templates');
        Route::get('certificates-issued', [CertificateController::class, 'issued'])->name('certificates.issued');
    });
    
    // Public certificate verification (no auth required)
    Route::get('certificates-verify', [CertificateController::class, 'verify'])->name('certificates.verify');
    
    // Resources
    Route::middleware(['permission:transactions.access'])->group(function () {
        Route::resource('transactions', TransactionController::class);
    });
    
    Route::middleware(['permission:items.access'])->group(function () {
        Route::resource('items', ItemController::class);
    });
    
    Route::middleware(['permission:equipment.access'])->group(function () {
        Route::resource('equipment', EquipmentController::class);
    });
    
    Route::middleware(['permission:calibration.access'])->group(function () {
        Route::resource('calibration', CalibrationRecordController::class);
    });
    
    // Inventory Management
    Route::middleware(['permission:inventory.access'])->group(function () {
        Route::get('inventory', [InventoryController::class, 'index'])->name('inventory.index');
    });
    
    // MIT Management
    Route::middleware(['permission:sections.access'])->group(function () {
        Route::resource('sections', SectionController::class);
    });
    
    Route::middleware(['permission:employees.access'])->group(function () {
        Route::resource('employees', EmployeeController::class);
        Route::get('users', function () {
            return view('users.index');
        })->name('users.index');
    });
    
    // Analytics
    Route::middleware(['permission:reports.access'])->group(function () {
        Route::get('reports', [ReportsController::class, 'index'])->name('reports.index');
        Route::get('reports/patients', [ReportsController::class, 'patients'])->name('reports.patients');
        Route::get('reports/transactions', [ReportsController::class, 'transactions'])->name('reports.transactions');
        Route::get('reports/inventory', [ReportsController::class, 'inventory'])->name('reports.inventory');
        Route::get('reports/activities', [ReportsController::class, 'activities'])->name('reports.activities');
    });
    
    Route::middleware(['permission:activity-logs.access'])->group(function () {
        Route::resource('activity-logs', ActivityLogController::class);
    });
});

require __DIR__.'/auth.php';
