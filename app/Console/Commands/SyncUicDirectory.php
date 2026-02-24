<?php

namespace App\Console\Commands;

use App\Models\UicDirectoryPerson;
use App\Services\UicDirectoryApi;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class SyncUicDirectory extends Command
{
    protected $signature = 'uic:sync-directory';
    protected $description = 'Fetch the UIC unified student/employee list and upsert into local directory table';

    public function handle(UicDirectoryApi $api): int
    {
        $this->info('Starting UIC directory sync...');
        $start = now();

        try {   
            $records = $api->fetchUnifiedList();
        } catch (\Throwable $e) {
            $this->error('API fetch failed: ' . $e->getMessage());
            Log::error('[UIC Sync] ' . $e->getMessage());
            return self::FAILURE;
        }

        $count = count($records);
        $this->info("Fetched {$count} records from API. Upserting...");

        $inserted = 0;
        $updated = 0;
        $skipped = 0;

        foreach ($records as $record) {
            // Map actual API fields to our schema
            $refId   = $record['student_number'] ?? $record['employee_id'] ?? $record['id'] ?? null;
            $type    = isset($record['student_number']) ? 'student' : 'employee';
            $first   = $record['first_name'] ?? null;
            $middle  = $record['middle_name'] ?? null;
            $last    = $record['last_name'] ?? null;
            $email   = isset($record['email_address']) ? strtolower(trim($record['email_address'])) : null;
            $dept    = $record['department'] ?? $record['course'] ?? $record['department_or_course'] ?? null;

            // Trim empty strings to null
            $middle = ($middle && trim($middle) !== '') ? trim($middle) : null;
            $email  = ($email && trim($email) !== '') ? $email : null;

            if (!$refId || !$first || !$last) {
                $skipped++;
                continue;
            }

            $existing = UicDirectoryPerson::where('external_ref_id', (string) $refId)
                ->where('type', strtolower($type))
                ->first();

            $gender  = $record['gender'] ?? null;
            $bdate   = $record['birth_date'] ?? null;
            $address = $record['home_address'] ?? null;

            // Trim empty strings to null
            $gender  = ($gender && trim($gender) !== '') ? trim($gender) : null;
            $bdate   = ($bdate && trim($bdate) !== '') ? trim($bdate) : null;
            $address = ($address && trim($address) !== '') ? trim($address) : null;

            if ($existing) {
                $existing->update([
                    'first_name'           => $first,
                    'middle_name'          => $middle,
                    'last_name'            => $last,
                    'gender'               => $gender,
                    'birth_date'           => $bdate,
                    'home_address'         => $address,
                    'email'                => $email,
                    'department_or_course' => $dept,
                    'raw_json'             => $record,
                    'last_synced_at'       => now(),
                ]);
                $updated++;
            } else {
                UicDirectoryPerson::create([
                    'external_ref_id'      => (string) $refId,
                    'type'                 => strtolower($type),
                    'first_name'           => $first,
                    'middle_name'          => $middle,
                    'last_name'            => $last,
                    'gender'               => $gender,
                    'birth_date'           => $bdate,
                    'home_address'         => $address,
                    'email'                => $email,
                    'department_or_course' => $dept,
                    'raw_json'             => $record,
                    'last_synced_at'       => now(),
                ]);
                $inserted++;
            }
        }

        $elapsed = $start->diffInSeconds(now());

        $summary = "[UIC Sync] Done in {$elapsed}s — fetched: {$count}, inserted: {$inserted}, updated: {$updated}, skipped: {$skipped}";
        $this->info($summary);
        Log::info($summary);

        return self::SUCCESS;
    }
}
