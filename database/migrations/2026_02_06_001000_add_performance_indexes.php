<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Add performance indexes for common queries
        
        // Equipment table indexes (only if columns exist)
        if (Schema::hasColumn('equipment', 'is_deleted')) {
            Schema::table('equipment', function (Blueprint $table) {
                $table->index(['is_deleted', 'status']); // For active equipment filtering
                $table->index(['section_id', 'is_deleted']); // For section-based queries
                $table->index(['name', 'is_deleted']); // For search queries
                $table->index(['model', 'is_deleted']); // For model search
            });
        } else {
            Schema::table('equipment', function (Blueprint $table) {
                if (Schema::hasColumn('equipment', 'section_id')) {
                    $table->index(['section_id']); // For section-based queries
                }
                if (Schema::hasColumn('equipment', 'name')) {
                    $table->index(['name']); // For search queries
                }
                if (Schema::hasColumn('equipment', 'model')) {
                    $table->index(['model']); // For model search
                }
            });
        }
        
        if (Schema::hasColumn('equipment', 'serial_no')) {
            Schema::table('equipment', function (Blueprint $table) {
                $table->index(['serial_no']); // For serial number search
            });
        }

        // Patient table indexes
        if (Schema::hasColumn('patient', 'is_deleted')) {
            Schema::table('patient', function (Blueprint $table) {
                $table->index(['is_deleted', 'datetime_added']); // For listing with date
                $table->index(['firstname', 'lastname', 'is_deleted']); // For name search
            });
        }

        // Item table indexes
        if (Schema::hasColumn('item', 'is_deleted')) {
            Schema::table('item', function (Blueprint $table) {
                $table->index(['is_deleted', 'label']); // For active items listing
                $table->index(['section_id', 'is_deleted']); // For section filtering
            });
        }

        // Transaction table indexes
        if (Schema::hasColumn('transaction', 'datetime_added')) {
            Schema::table('transaction', function (Blueprint $table) {
                $table->index(['datetime_added']); // For date-based queries
            });
        }
        
        if (Schema::hasColumn('transaction', 'or_number')) {
            Schema::table('transaction', function (Blueprint $table) {
                $table->index(['or_number']); // For OR number search
            });
        }
        
        if (Schema::hasColumn('transaction', 'client_id')) {
            Schema::table('transaction', function (Blueprint $table) {
                $table->index(['client_id']); // For client lookup
            });
        }

        // Employee table indexes
        if (Schema::hasColumn('employee', 'is_deleted')) {
            Schema::table('employee', function (Blueprint $table) {
                $table->index(['is_deleted', 'lastname', 'firstname']); // For name-based listing
                $table->index(['section_id', 'is_deleted']); // For section queries
            });
        }
        
        if (Schema::hasColumn('employee', 'username')) {
            Schema::table('employee', function (Blueprint $table) {
                $table->index(['username']); // For login queries
            });
        }

        // Section table indexes
        if (Schema::hasColumn('section', 'is_deleted')) {
            Schema::table('section', function (Blueprint $table) {
                $table->index(['is_deleted', 'label']); // For active sections
            });
        }

        // Activity Log indexes
        if (Schema::hasColumn('activity_log', 'datetime_added')) {
            Schema::table('activity_log', function (Blueprint $table) {
                $table->index(['datetime_added']); // For chronological queries
            });
        }
        
        if (Schema::hasColumn('activity_log', 'employee_id')) {
            Schema::table('activity_log', function (Blueprint $table) {
                $table->index(['employee_id']); // For user activity lookup
            });
        }

        // Calibration Record indexes (without is_deleted if it doesn't exist)
        if (Schema::hasTable('calibration_record')) {
            Schema::table('calibration_record', function (Blueprint $table) {
                if (Schema::hasColumn('calibration_record', 'equipment_id')) {
                    $table->index(['equipment_id', 'calibration_date']); // For equipment history
                }
                if (Schema::hasColumn('calibration_record', 'next_calibration_date')) {
                    $table->index(['next_calibration_date']); // For due date queries
                }
            });
        }

        // Stock tables indexes
        if (Schema::hasTable('stock_in')) {
            Schema::table('stock_in', function (Blueprint $table) {
                if (Schema::hasColumn('stock_in', 'item_id')) {
                    $table->index(['item_id', 'datetime_added']); // For item history
                }
                if (Schema::hasColumn('stock_in', 'datetime_added')) {
                    $table->index(['datetime_added']); // For date-based queries
                }
            });
        }

        if (Schema::hasTable('stock_out')) {
            Schema::table('stock_out', function (Blueprint $table) {
                if (Schema::hasColumn('stock_out', 'item_id')) {
                    $table->index(['item_id', 'datetime_added']); // For item history
                }
                if (Schema::hasColumn('stock_out', 'datetime_added')) {
                    $table->index(['datetime_added']); // For date-based queries
                }
            });
        }

        if (Schema::hasTable('stock_usage')) {
            Schema::table('stock_usage', function (Blueprint $table) {
                if (Schema::hasColumn('stock_usage', 'item_id')) {
                    $table->index(['item_id', 'datetime_used']); // For item usage history
                }
                if (Schema::hasColumn('stock_usage', 'datetime_used')) {
                    $table->index(['datetime_used']); // For date-based queries
                }
            });
        }
    }

    public function down(): void
    {
        // Drop indexes in reverse order
        Schema::table('stock_usage', function (Blueprint $table) {
            $table->dropIndex(['item_id', 'datetime_used']);
            $table->dropIndex(['datetime_used']);
        });

        Schema::table('stock_out', function (Blueprint $table) {
            $table->dropIndex(['item_id', 'datetime_added']);
            $table->dropIndex(['datetime_added']);
        });

        Schema::table('stock_in', function (Blueprint $table) {
            $table->dropIndex(['item_id', 'datetime_added']);
            $table->dropIndex(['datetime_added']);
        });

        Schema::table('calibration_record', function (Blueprint $table) {
            $table->dropIndex(['equipment_id', 'calibration_date']);
            $table->dropIndex(['next_calibration_date']);
            $table->dropIndex(['is_deleted', 'calibration_date']);
        });

        Schema::table('activity_log', function (Blueprint $table) {
            $table->dropIndex(['datetime_added']);
            $table->dropIndex(['employee_id']);
        });

        if (Schema::hasTable('test')) {
            Schema::table('test', function (Blueprint $table) {
                $table->dropIndex(['is_deleted', 'label']);
                $table->dropIndex(['section_id', 'is_deleted']);
            });
        }

        Schema::table('section', function (Blueprint $table) {
            $table->dropIndex(['is_deleted', 'label']);
        });

        Schema::table('employee', function (Blueprint $table) {
            $table->dropIndex(['is_deleted', 'lastname', 'firstname']);
            $table->dropIndex(['section_id', 'is_deleted']);
            $table->dropIndex(['username']);
        });

        Schema::table('transaction', function (Blueprint $table) {
            $table->dropIndex(['datetime_added']);
            $table->dropIndex(['or_number']);
            $table->dropIndex(['client_id']);
        });

        Schema::table('item', function (Blueprint $table) {
            $table->dropIndex(['is_deleted', 'label']);
            $table->dropIndex(['section_id', 'is_deleted']);
        });

        Schema::table('patient', function (Blueprint $table) {
            $table->dropIndex(['is_deleted', 'datetime_added']);
            $table->dropIndex(['firstname', 'lastname', 'is_deleted']);
        });

        Schema::table('equipment', function (Blueprint $table) {
            $table->dropIndex(['is_deleted', 'status']);
            $table->dropIndex(['section_id', 'is_deleted']);
            $table->dropIndex(['name', 'is_deleted']);
            $table->dropIndex(['model', 'is_deleted']);
            $table->dropIndex(['serial_no']);
        });
    }
};