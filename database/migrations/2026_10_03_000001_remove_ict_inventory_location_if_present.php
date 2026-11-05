<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (
            Schema::hasTable('ict_inventory')
            && Schema::hasColumn('ict_inventory', 'location')
        ) {
            Schema::table(
                'ict_inventory',
                function (Blueprint $table): void {
                    $table->dropColumn('location');
                }
            );
        }
    }

    public function down(): void
    {
        // Intentionally not restored.
    }
};
