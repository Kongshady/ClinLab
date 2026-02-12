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
        Schema::table('physician', function (Blueprint $table) {
            $table->integer('section_id')->length(10)->nullable()->after('email');
            $table->foreign('section_id', 'fk_physician_section')
                  ->references('section_id')->on('section');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('physician', function (Blueprint $table) {
            $table->dropForeign('fk_physician_section');
            $table->dropColumn('section_id');
        });
    }
};
