<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const LEGACY_COLUMNS = [
        'data_completeness_score',
        'asset_to_user_ratio',
        'users_per_functional_device',
        'functional_availability_rate',
        'defective_rate',
        'retirement_rate',
        'bandwidth_attainment_rate',
        'throughput_attainment_rate',
    ];

    public function up(): void
    {
        $existing = array_values(
            array_filter(
                self::LEGACY_COLUMNS,
                fn (string $column): bool =>
                    Schema::hasColumn(
                        'institution_analytics',
                        $column
                    )
            )
        );

        if ($existing === []) {
            return;
        }

        Schema::table(
            'institution_analytics',
            function (Blueprint $table) use ($existing): void {
                $table->dropColumn($existing);
            }
        );
    }

    public function down(): void
    {
        $missing = array_fill_keys(
            self::LEGACY_COLUMNS,
            false
        );

        foreach (
            self::LEGACY_COLUMNS
            as $column
        ) {
            $missing[$column] =
                !Schema::hasColumn(
                    'institution_analytics',
                    $column
                );
        }

        Schema::table(
            'institution_analytics',
            function (Blueprint $table) use ($missing): void {
                if ($missing['data_completeness_score']) {
                    $table
                        ->decimal(
                            'data_completeness_score',
                            5,
                            2
                        )
                        ->nullable();
                }

                if ($missing['asset_to_user_ratio']) {
                    $table
                        ->double(
                            'asset_to_user_ratio'
                        )
                        ->nullable();
                }

                if (
                    $missing[
                        'users_per_functional_device'
                    ]
                ) {
                    $table
                        ->double(
                            'users_per_functional_device'
                        )
                        ->nullable();
                }

                foreach ([
                    'functional_availability_rate',
                    'defective_rate',
                    'retirement_rate',
                    'bandwidth_attainment_rate',
                    'throughput_attainment_rate',
                ] as $column) {
                    if (!$missing[$column]) {
                        continue;
                    }

                    $table
                        ->decimal($column, 5, 2)
                        ->nullable();
                }
            }
        );
    }
};
