<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Add only essential performance indexes that we know exist
        
        try {
            // Activity Log indexes - most important for dashboard
            if (Schema::hasTable('activity_log')) {
                Schema::table('activity_log', function (Blueprint $table) {
                    $table->index(['datetime_added']); // For chronological queries
                    $table->index(['employee_id']); // For user activity lookup
                });
            }
        } catch (Exception $e) {
            // Ignore if index already exists
        }

        try {
            // Equipment table basic indexes
            if (Schema::hasTable('equipment')) {
                Schema::table('equipment', function (Blueprint $table) {
                    $table->index(['equipment_id']); // Primary key optimization
                });
            }
        } catch (Exception $e) {
            // Ignore if index already exists
        }

        try {
            // Patient table basic indexes
            if (Schema::hasTable('patient')) {
                Schema::table('patient', function (Blueprint $table) {
                    $table->index(['patient_id']); // Primary key optimization
                    $table->index(['datetime_added']); // For date-based queries
                });
            }
        } catch (Exception $e) {
            // Ignore if index already exists
        }

        try {
            // Transaction table basic indexes
            if (Schema::hasTable('transaction')) {
                Schema::table('transaction', function (Blueprint $table) {
                    $table->index(['transaction_id']); // Primary key optimization
                    $table->index(['datetime_added']); // For date-based queries
                });
            }
        } catch (Exception $e) {
            // Ignore if index already exists
        }
    }

    public function down(): void
    {
        try {
            Schema::table('activity_log', function (Blueprint $table) {
                $table->dropIndex(['datetime_added']);
                $table->dropIndex(['employee_id']);
            });
        } catch (Exception $e) {
            // Ignore if index doesn't exist
        }

        try {
            Schema::table('equipment', function (Blueprint $table) {
                $table->dropIndex(['equipment_id']);
            });
        } catch (Exception $e) {
            // Ignore if index doesn't exist
        }

        try {
            Schema::table('patient', function (Blueprint $table) {
                $table->dropIndex(['patient_id']);
                $table->dropIndex(['datetime_added']);
            });
        } catch (Exception $e) {
            // Ignore if index doesn't exist
        }

        try {
            Schema::table('transaction', function (Blueprint $table) {
                $table->dropIndex(['transaction_id']);
                $table->dropIndex(['datetime_added']);
            });
        } catch (Exception $e) {
            // Ignore if index doesn't exist
        }
    }
};