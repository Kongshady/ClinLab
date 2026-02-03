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
        Schema::table('lab_result', function (Blueprint $table) {
            $table->integer('lab_test_order_id')->length(10)->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('lab_result', function (Blueprint $table) {
            $table->integer('lab_test_order_id')->length(10)->nullable(false)->change();
        });
    }
};
