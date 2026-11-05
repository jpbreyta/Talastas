<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('institutions', function (Blueprint $table): void {
            $table->boolean('has_internet_connection')->nullable();
            $table->string('isp_connection_plan', 255)->nullable();
            $table->boolean('has_backup_internet_link')->nullable();
            $table->boolean('has_public_access_network')->nullable();
            $table->decimal('bandwidth_allocation_mbps', 10, 2)->nullable();
            $table->decimal('download_speed_mbps', 10, 2)->nullable();
            $table->decimal('upload_speed_mbps', 10, 2)->nullable();
            $table->decimal('latency_ms', 10, 2)->nullable();
            $table->decimal('throughput_mbps', 10, 2)->nullable();
        });

        Schema::table('ict_inventory', function (Blueprint $table): void {
            $table->unsignedInteger('total_count')->default(0);
            $table->unsignedInteger('active_count')->default(0);
            $table->unsignedInteger('retired_count')->default(0);
            $table->unsignedInteger('functional_count')->default(0);
            $table->unsignedInteger('defective_count')->default(0);
        });

        Schema::table('institution_analytics', function (Blueprint $table): void {
            $table->decimal('functional_availability_rate', 5, 2)->nullable();
            $table->decimal('defective_rate', 5, 2)->nullable();
            $table->decimal('retirement_rate', 5, 2)->nullable();
            $table->decimal('bandwidth_attainment_rate', 5, 2)->nullable();
            $table->decimal('throughput_attainment_rate', 5, 2)->nullable();
            $table->double('users_per_functional_device')->nullable();
        });

        DB::table('ict_inventory')
            ->orderBy('asset_id')
            ->chunkById(200, function ($rows): void {
                foreach ($rows as $row) {
                    $quantity = max(0, (int) ($row->quantity ?? 0));
                    $status = strtolower(str_replace([' ', '-'], '_', trim((string) ($row->status ?? ''))));
                    $condition = strtolower(str_replace([' ', '-'], '_', trim((string) ($row->condition ?? ''))));

                    $retired = in_array($status, ['retired', 'disposed'], true) ? $quantity : 0;
                    $active = max(0, $quantity - $retired);
                    $defective = (
                        $active > 0
                        && (
                            in_array($status, ['under_repair', 'inactive'], true)
                            || in_array($condition, ['poor', 'needs_repair'], true)
                        )
                    ) ? $active : 0;

                    DB::table('ict_inventory')
                        ->where('asset_id', $row->asset_id)
                        ->update([
                            'total_count' => $quantity,
                            'active_count' => $active,
                            'retired_count' => $retired,
                            'functional_count' => max(0, $active - $defective),
                            'defective_count' => $defective,
                        ]);
                }
            }, 'asset_id');

        DB::table('institutions')
            ->whereNotNull('internet_speed')
            ->update(['download_speed_mbps' => DB::raw('internet_speed')]);

        DB::table('institutions')
            ->where(function ($query): void {
                $query->where('internet_speed', '>', 0)
                    ->orWhereNotNull('isp_provider');
            })
            ->update(['has_internet_connection' => true]);
    }

    public function down(): void
    {
        Schema::table('institution_analytics', function (Blueprint $table): void {
            $table->dropColumn([
                'functional_availability_rate',
                'defective_rate',
                'retirement_rate',
                'bandwidth_attainment_rate',
                'throughput_attainment_rate',
                'users_per_functional_device',
            ]);
        });

        Schema::table('ict_inventory', function (Blueprint $table): void {
            $table->dropColumn([
                'total_count',
                'active_count',
                'retired_count',
                'functional_count',
                'defective_count',
            ]);
        });

        Schema::table('institutions', function (Blueprint $table): void {
            $table->dropColumn([
                'has_internet_connection',
                'isp_connection_plan',
                'has_backup_internet_link',
                'has_public_access_network',
                'bandwidth_allocation_mbps',
                'download_speed_mbps',
                'upload_speed_mbps',
                'latency_ms',
                'throughput_mbps',
            ]);
        });
    }
};
