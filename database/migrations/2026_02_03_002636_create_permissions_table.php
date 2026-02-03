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
        Schema::create('permissions', function (Blueprint $table) {
            $table->integer('permission_id', true, true)->length(10);
            $table->string('permission_key', 100)->unique();
            $table->string('module', 50);
            $table->string('action', 50);
            $table->string('description', 255)->nullable();
            $table->timestamp('created_at')->useCurrent();
            
            $table->index('module', 'idx_module');
            $table->index('permission_key', 'idx_permission_key');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('permissions');
    }
};
