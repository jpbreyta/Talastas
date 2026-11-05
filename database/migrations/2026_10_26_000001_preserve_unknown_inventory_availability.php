<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Preserve existing quantities; historical zero values have no source provenance.
        Schema::table('ict_inventory', function (Blueprint $table): void {
            foreach (['active_count', 'retired_count', 'defective_count', 'functional_count'] as $field) {
                $table->unsignedInteger($field)->nullable()->default(null)->change();
            }
            $table->boolean('needs_maintenance')->nullable()->default(null)->change();
        });
    }

    public function down(): void
    {
        // Intentionally retain nullable columns: rollback must not invent zero quantities.
    }
};
