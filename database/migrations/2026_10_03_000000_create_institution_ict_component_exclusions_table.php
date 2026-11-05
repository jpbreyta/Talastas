<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create(
            'institution_ict_component_exclusions',
            function (Blueprint $table): void {
                $table->id();

                $table->foreignId('institution_id')
                    ->constrained('institutions')
                    ->cascadeOnDelete();

                $table->foreignId('component_id')
                    ->constrained('ict_components')
                    ->cascadeOnDelete();

                $table->timestamps();

                $table->unique(
                    ['institution_id', 'component_id'],
                    'institution_component_exclusion_unique'
                );
            }
        );
    }

    public function down(): void
    {
        Schema::dropIfExists(
            'institution_ict_component_exclusions'
        );
    }
};
