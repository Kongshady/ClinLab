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
        Schema::create('certificate_issues', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('template_id');
            $table->string('certificate_no')->unique();
            $table->string('verification_code')->unique();
            $table->dateTime('issued_at');
            $table->dateTime('valid_until')->nullable();
            $table->unsignedBigInteger('generated_by');
            $table->enum('status', ['Issued', 'Revoked', 'Expired'])->default('Issued');
            
            // Nullable links to source records
            $table->unsignedInteger('equipment_id')->nullable();
            $table->unsignedBigInteger('calibration_id')->nullable();
            $table->unsignedBigInteger('maintenance_id')->nullable();
            $table->unsignedBigInteger('lab_result_id')->nullable();
            
            $table->string('pdf_path')->nullable();
            $table->timestamps();

            $table->foreign('template_id')->references('id')->on('certificate_templates')->onDelete('cascade');
            $table->foreign('generated_by')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('equipment_id')->references('equipment_id')->on('equipment')->onDelete('set null');
            
            $table->index(['certificate_no', 'verification_code']);
            $table->index(['status', 'issued_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('certificate_issues');
    }
};
