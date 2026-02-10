<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('patient', function (Blueprint $table) {
            $table->unsignedBigInteger('user_id')->nullable()->after('patient_id');
            $table->string('email', 100)->nullable()->after('address');

            $table->index('user_id', 'idx_patient_user_id');
            $table->index('email', 'idx_patient_email');
        });
    }

    public function down(): void
    {
        Schema::table('patient', function (Blueprint $table) {
            $table->dropIndex('idx_patient_user_id');
            $table->dropIndex('idx_patient_email');
            $table->dropColumn(['user_id', 'email']);
        });
    }
};
