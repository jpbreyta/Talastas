<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('institutions', 'is_pilot_site')) {
            return;
        }

        Schema::table('institutions', function (Blueprint $table): void {
            $table->dropIndex(['is_active', 'is_pilot_site']);
            $table->dropColumn('is_pilot_site');
        });
    }

    public function down(): void
    {
        if (Schema::hasColumn('institutions', 'is_pilot_site')) {
            return;
        }

        Schema::table('institutions', function (Blueprint $table): void {
            $table->boolean('is_pilot_site')
                ->default(true)
                ->after('notes');

            $table->index(['is_active', 'is_pilot_site']);
        });
    }
};
