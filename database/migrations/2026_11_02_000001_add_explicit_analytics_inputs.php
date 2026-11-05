<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ict_inventory', function (Blueprint $table): void {
            // Explicit survey response only. Historical unknowns remain NULL.
            $table->unsignedInteger('defective_active_count')->nullable();
        });
        Schema::table('institution_internet_connections', function (Blueprint $table): void {
            $table->decimal('advertised_download_mbps', 14, 6)->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('institution_internet_connections', fn (Blueprint $table) => $table->dropColumn('advertised_download_mbps'));
        Schema::table('ict_inventory', fn (Blueprint $table) => $table->dropColumn('defective_active_count'));
    }
};
