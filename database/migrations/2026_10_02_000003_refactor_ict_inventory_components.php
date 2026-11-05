<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ict_components', function (Blueprint $table): void {
            $table->id();
            $table->string('key', 100)->unique();
            $table->string('name', 150);
            $table->string('component_type', 20);
            $table->string('subsection', 150)->nullable();
            $table->boolean('is_standard')->default(true);
            $table->boolean('counts_toward_infrastructure')->default(true);
            $table->boolean('is_computing_device')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['component_type', 'is_active']);
        });

        Schema::table('ict_inventory', function (Blueprint $table): void {
            $table->foreignId('component_id')
                ->nullable()
                ->after('institution_id')
                ->constrained('ict_components')
                ->nullOnDelete();

            $table->string('component_type', 20)
                ->default('hardware')
                ->after('category');

            $table->boolean('is_computing_device')
                ->default(false)
                ->after('component_type');
        });

        $softwareCategories = [
            'windows_os',
            'linux_os',
            'mac_os',
            'productivity_suite',
            'collaboration_account',
            'enterprise_suite',
            'gis_software_license',
            'database_management_license',
            'software_development_tool',
            'anti_virus',
            'utilities',
            'network_monitoring_tool',
            'inventory_system',
            'appointment_system',
            'billing_system',
            'payment_processing_system',
            'queue_system',
        ];

        $networkCategories = [
            'modem',
            'router',
            'wireless_access_point',
            'network_switch',
            'network_hub',
            'firewall',
            'load_balancer',
            'vpn',
            'ids_ips',
            'lan_cable',
            'fiber_optic_cable',
            'network_patch_panel',
            'network_server_room',
        ];

        $computingCategories = [
            'computer',
            'desktop_computer',
            'laptop',
            'tablet',
            'smart_phone',
            'workstation',
            'pda',
        ];

        DB::table('ict_inventory')
            ->whereIn('category', $softwareCategories)
            ->update([
                'component_type' => 'software',
                'is_computing_device' => false,
            ]);

        DB::table('ict_inventory')
            ->whereIn('category', $networkCategories)
            ->update([
                'component_type' => 'network',
                'is_computing_device' => false,
            ]);

        DB::table('ict_inventory')
            ->whereIn('category', $computingCategories)
            ->update([
                'component_type' => 'hardware',
                'is_computing_device' => true,
            ]);

        DB::table('ict_inventory')
            ->whereNull('asset_name')
            ->orderBy('asset_id')
            ->chunkById(
                200,
                function ($rows): void {
                    foreach ($rows as $row) {
                        DB::table('ict_inventory')
                            ->where(
                                'asset_id',
                                $row->asset_id
                            )
                            ->update([
                                'asset_name' =>
                                    Str::of(
                                        (string) $row->category
                                    )
                                        ->replace('_', ' ')
                                        ->headline()
                                        ->toString(),
                            ]);
                    }
                },
                'asset_id'
            );

        if (Schema::hasColumn('ict_inventory', 'location')) {
            Schema::table('ict_inventory', function (Blueprint $table): void {
                $table->dropColumn('location');
            });
        }
    }

    public function down(): void
    {
        Schema::table('ict_inventory', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('component_id');
            $table->dropColumn([
                'component_type',
                'is_computing_device',
            ]);
        });

        Schema::dropIfExists('ict_components');
    }
};
