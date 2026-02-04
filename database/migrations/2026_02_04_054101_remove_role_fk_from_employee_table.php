<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('employee', function (Blueprint $table) {
            // Drop foreign key constraint that references legacy roles table
            $table->dropForeign('fk_employee_role');
            
            // Drop the index as well
            $table->dropIndex('idx_role_id');
            
            // Make role_id nullable since we're using Spatie's user_roles instead
            $table->integer('role_id')->length(10)->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('employee', function (Blueprint $table) {
            // Re-add the foreign key constraint
            $table->foreign('role_id', 'fk_employee_role')
                  ->references('role_id')->on('roles')->onDelete('set null');
            
            // Re-add the index
            $table->index('role_id', 'idx_role_id');
        });
    }
};
