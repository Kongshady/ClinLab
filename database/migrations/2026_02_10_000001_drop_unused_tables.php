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
        // Drop foreign key constraint on client table first
        Schema::table('client', function (Blueprint $table) {
            $table->dropForeign('fk_client_clienttype');
        });

        // Drop the unused tables
        Schema::dropIfExists('client');
        Schema::dropIfExists('client_type');
        Schema::dropIfExists('blood_chemistry');
        Schema::dropIfExists('fecalysis');
        Schema::dropIfExists('user_sessions');

        // Drop the old duplicate certificate_template (singular) table
        // The app uses certificate_templates (plural) via CertificateTemplate model
        Schema::dropIfExists('certificate_template');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Recreate client_type table
        Schema::create('client_type', function (Blueprint $table) {
            $table->integer('client_type_id', true, true)->length(8);
            $table->string('client_type', 50);
            
            $table->primary('client_type_id');
        });

        // Recreate client table
        Schema::create('client', function (Blueprint $table) {
            $table->integer('client_id', true, true)->length(10);
            $table->integer('client_type_id')->length(10);
            $table->string('firstname', 50);
            $table->string('lastname', 50);
            
            $table->primary('client_id');
            $table->index('client_type_id', 'idx_client_type');
            
            $table->foreign('client_type_id', 'fk_client_clienttype')
                  ->references('client_type_id')->on('client_type');
        });

        // Recreate blood_chemistry table
        Schema::create('blood_chemistry', function (Blueprint $table) {
            $table->integer('blood_chemistry_id', true, true)->length(10);
            $table->integer('lab_result_id')->length(10);
            $table->decimal('fbs', 5, 2)->nullable();
            $table->decimal('cholesterol', 5, 2)->nullable();
            $table->decimal('triglycerides', 5, 2)->nullable();
            $table->decimal('uric_acid', 5, 2)->nullable();
            $table->decimal('creatinine', 5, 2)->nullable();
            
            $table->primary('blood_chemistry_id');
        });

        // Recreate fecalysis table
        Schema::create('fecalysis', function (Blueprint $table) {
            $table->integer('fecalysis_id', true, true)->length(11);
            $table->integer('lab_result_id')->length(10);
            $table->string('color', 20)->nullable();
            $table->string('consistency', 20)->nullable();
            $table->text('parasites')->nullable();
            $table->text('bacteria')->nullable();
            
            $table->primary('fecalysis_id');
        });

        // Recreate user_sessions table
        Schema::create('user_sessions', function (Blueprint $table) {
            $table->integer('session_id', true, true)->length(10);
            $table->integer('user_id')->length(10);
            $table->string('session_token', 255);
            $table->string('ip_address', 45);
            $table->text('user_agent');
            $table->dateTime('created_at')->useCurrent();
            $table->dateTime('expires_at');
            $table->tinyInteger('is_active')->default(1);
            
            $table->primary('session_id');
            $table->index(['user_id', 'is_active'], 'idx_user_sessions_active');
            $table->index('expires_at', 'idx_user_sessions_expires');
        });
    }
};