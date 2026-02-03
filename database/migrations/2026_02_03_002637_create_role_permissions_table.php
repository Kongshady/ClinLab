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
        Schema::create('role_permissions', function (Blueprint $table) {
            $table->integer('role_permission_id', true, true)->length(10);
            $table->integer('role_id')->length(10);
            $table->integer('permission_id')->length(10);
            $table->timestamp('created_at')->useCurrent();
            
            $table->unique(['role_id', 'permission_id'], 'unique_role_permission');
            $table->foreign('role_id')->references('role_id')->on('roles')->onDelete('cascade');
            $table->foreign('permission_id')->references('permission_id')->on('permissions')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('role_permissions');
    }
};
