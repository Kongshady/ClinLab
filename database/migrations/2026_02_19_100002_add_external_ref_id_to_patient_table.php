<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('patient', function (Blueprint $table) {
            // Link to UIC directory
            $table->string('external_ref_id', 50)->nullable()->after('email');
        });
    }

    public function down(): void
    {
        Schema::table('patient', function (Blueprint $table) {
            $table->dropColumn('external_ref_id');
        });
    }
};
