<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('uic_directory_people', function (Blueprint $table) {
            $table->id();
            $table->string('external_ref_id', 50)->index();
            $table->string('type', 20);                     // student, employee, etc.
            $table->string('first_name', 100);
            $table->string('middle_name', 100)->nullable();
            $table->string('last_name', 100);
            $table->string('email', 150)->nullable()->index();
            $table->string('department_or_course', 255)->nullable();
            $table->json('raw_json')->nullable();
            $table->timestamp('last_synced_at')->nullable();
            $table->timestamps();

            $table->unique(['external_ref_id', 'type'], 'uic_dir_ref_type_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('uic_directory_people');
    }
};
