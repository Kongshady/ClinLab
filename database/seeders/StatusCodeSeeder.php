<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class StatusCodeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $statuses = [
            ['status_code' => 1, 'label' => 'Active'],
            ['status_code' => 2, 'label' => 'Inactive'],
            ['status_code' => 3, 'label' => 'Pending'],
            ['status_code' => 4, 'label' => 'Suspended'],
            ['status_code' => 5, 'label' => 'Archived'],
        ];

        foreach ($statuses as $status) {
            DB::table('status_code')->updateOrInsert(
                ['status_code' => $status['status_code']],
                $status
            );
        }
    }
}
