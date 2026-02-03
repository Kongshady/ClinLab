<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PatientController;
use App\Http\Controllers\TestController;
use App\Http\Controllers\PhysicianController;
use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\TransactionController;
use App\Http\Controllers\ItemController;
use App\Http\Controllers\EquipmentController;
use App\Http\Controllers\ActivityLogController;
use App\Http\Controllers\SectionController;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Welcome');
});

// Patient CRUD routes
Route::resource('patients', PatientController::class);

// Lab Test CRUD routes
Route::resource('tests', TestController::class);

// Physician CRUD routes
Route::resource('physicians', PhysicianController::class);

// Employee CRUD routes
Route::resource('employees', EmployeeController::class);

// Transaction CRUD routes
Route::resource('transactions', TransactionController::class);

// Item/Inventory CRUD routes
Route::resource('items', ItemController::class);

// Equipment CRUD routes
Route::resource('equipment', EquipmentController::class);

// Activity Logs routes
Route::get('activity-logs', [ActivityLogController::class, 'index'])->name('activity-logs.index');

// Section CRUD routes
Route::resource('sections', SectionController::class);
