<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Do not silently delete or merge existing institutions and their data.
        $duplicates = DB::table('institutions')
            ->selectRaw('barangay_id, LOWER(TRIM(name)) AS normalized_name, COUNT(*) AS total')
            ->groupByRaw('barangay_id, LOWER(TRIM(name))')
            ->havingRaw('COUNT(*) > 1')
            ->orderBy('barangay_id')
            ->limit(20)
            ->get();

        if ($duplicates->isNotEmpty()) {
            $details = $duplicates->map(function ($duplicate): string {
                $records = DB::table('institutions')
                    ->where('barangay_id', $duplicate->barangay_id)
                    ->whereRaw('LOWER(TRIM(name)) = ?', [$duplicate->normalized_name])
                    ->orderBy('id')
                    ->get(['id', 'name']);

                return 'Barangay '.$duplicate->barangay_id.': '.$records
                    ->map(fn ($record) => '#'.$record->id.' '.json_encode($record->name, JSON_UNESCAPED_UNICODE))
                    ->implode(', ');
            })->implode(PHP_EOL);

            throw new RuntimeException(
                'Duplicate institution names exist within a barangay.'.PHP_EOL
                .$details.PHP_EOL
                .'Showing up to 20 groups. Review these IDs in the institutions table. Correct distinct institution names or consolidate confirmed duplicates and their linked records, then run php artisan migrate again. No records were changed.'
            );
        }

        DB::statement('CREATE UNIQUE INDEX institutions_barangay_name_unique ON institutions (barangay_id, LOWER(TRIM(name)))');
    }

    public function down(): void
    {
        DB::statement('DROP INDEX institutions_barangay_name_unique');
    }
};
