<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('test_requests', function (Blueprint $table) {
            $table->integer('id', true, true)->length(10);
            $table->unsignedInteger('patient_id');
            $table->unsignedBigInteger('requested_by_user_id');
            $table->text('purpose')->nullable();
            $table->date('preferred_date')->nullable();
            $table->string('status', 20)->default('PENDING'); // PENDING, APPROVED, REJECTED, CANCELLED
            $table->text('staff_remarks')->nullable();
            $table->unsignedBigInteger('reviewed_by')->nullable();
            $table->dateTime('reviewed_at')->nullable();
            $table->dateTime('datetime_added')->useCurrent();
            $table->dateTime('datetime_updated')->nullable()->useCurrentOnUpdate();

            $table->foreign('patient_id')->references('patient_id')->on('patient');
            $table->foreign('requested_by_user_id')->references('id')->on('users');
            $table->foreign('reviewed_by')->references('id')->on('users');

            $table->index('patient_id');
            $table->index('requested_by_user_id');
            $table->index('status');
        });

        Schema::create('test_request_items', function (Blueprint $table) {
            $table->integer('id', true, true)->length(10);
            $table->unsignedInteger('request_id');
            $table->unsignedInteger('test_id');
            $table->dateTime('datetime_added')->useCurrent();

            $table->foreign('request_id')->references('id')->on('test_requests')->onDelete('cascade');
            $table->foreign('test_id')->references('test_id')->on('test');

            $table->index('request_id');
            $table->index('test_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('test_request_items');
        Schema::dropIfExists('test_requests');
    }
};
