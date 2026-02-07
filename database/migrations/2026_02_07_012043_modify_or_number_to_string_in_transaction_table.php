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
        Schema::table('transaction', function (Blueprint $table) {
            // Drop index if it exists
            $table->dropIndex('transaction_or_number_index');
        });
        
        Schema::table('transaction', function (Blueprint $table) {
            // Change column type
            $table->string('or_number', 50)->change();
        });
        
        Schema::table('transaction', function (Blueprint $table) {
            // Recreate index
            $table->index('or_number');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('transaction', function (Blueprint $table) {
            $table->dropIndex('transaction_or_number_index');
        });
        
        Schema::table('transaction', function (Blueprint $table) {
            $table->integer('or_number')->length(10)->change();
        });
        
        Schema::table('transaction', function (Blueprint $table) {
            $table->index('or_number');
        });
    }
};
