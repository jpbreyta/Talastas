<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->index('role', 'users_role_index');
        });

        Schema::table('institutions', function (Blueprint $table): void {
            $table->index(['is_active', 'name'], 'institutions_active_name_index');
            $table->index(['is_active', 'barangay_id'], 'institutions_active_barangay_index');
        });

        Schema::table('ict_inventory', function (Blueprint $table): void {
            $table->index('status', 'ict_inventory_status_index');
            $table->index(
                ['institution_id', 'needs_maintenance'],
                'ict_inventory_institution_maintenance_index'
            );
        });
    }

    public function down(): void
    {
        Schema::table('ict_inventory', function (Blueprint $table): void {
            $table->dropIndex('ict_inventory_institution_maintenance_index');
            $table->dropIndex('ict_inventory_status_index');
        });

        Schema::table('institutions', function (Blueprint $table): void {
            $table->dropIndex('institutions_active_barangay_index');
            $table->dropIndex('institutions_active_name_index');
        });

        Schema::table('users', function (Blueprint $table): void {
            $table->dropIndex('users_role_index');
        });
    }
};
