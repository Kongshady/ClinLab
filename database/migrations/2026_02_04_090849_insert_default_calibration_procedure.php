<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::table('calibration_procedure')->insert([
            'procedure_id' => 1,
            'equipment_id' => 1,
            'procedure_name' => 'General Calibration',
            'standard_reference' => 'ISO 17025',
            'frequency' => 'annual',
            'next_due_date' => date('Y-m-d'),
            'is_active' => 1,
            'datetime_added' => now(),
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('calibration_procedure')->where('procedure_id', 1)->delete();
    }
};
