<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('ict_inventory', 'functional_count')) {
            Schema::table('ict_inventory', function (Blueprint $table): void {
                $table->unsignedInteger('functional_count')->nullable()->after('retired_count');
            });
        } else {
            Schema::table('ict_inventory', function (Blueprint $table): void {
                $table->unsignedInteger('functional_count')->nullable()->default(null)->change();
            });
        }

        if (!Schema::hasColumn('ict_inventory', 'functional_count_recorded')) {
            Schema::table('ict_inventory', function (Blueprint $table): void {
                // Existing values were derived, not recorded survey responses.
                $table->boolean('functional_count_recorded')->default(false);
            });
        }
    }

    public function down(): void
    {
        // Preserve the nullable count and its provenance on rollback. Dropping
        // either would lose survey data or make derived values look reported.
    }
};
