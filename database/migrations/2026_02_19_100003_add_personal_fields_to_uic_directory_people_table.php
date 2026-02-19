<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('uic_directory_people', function (Blueprint $table) {
            $table->string('gender', 10)->nullable()->after('last_name');
            $table->date('birth_date')->nullable()->after('gender');
            $table->string('home_address', 500)->nullable()->after('birth_date');
        });
    }

    public function down(): void
    {
        Schema::table('uic_directory_people', function (Blueprint $table) {
            $table->dropColumn(['gender', 'birth_date', 'home_address']);
        });
    }
};
