<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ict_inventory', function (Blueprint $table) {
            $table->id('asset_id');

            $table->foreignId('institution_id')
                ->constrained('institutions')
                ->cascadeOnDelete();

            // Asset / grouped-inventory identity.
            // Category remains a string so new ICT types do not require a migration.
            $table->string('category', 100);
            $table->string('asset_name', 150)->nullable();
            $table->string('brand', 100)->nullable();
            $table->string('model', 150)->nullable();

            // One row may represent one item or a group of identical/similar items.
            $table->unsignedInteger('quantity')->default(1);

            // Operational state.
            $table->string('condition', 50);
            $table->unsignedSmallInteger('acquisition_year')->nullable();
            $table->string('status', 50);

            // Category-specific technical details.
            // Examples: processor/RAM/storage for computers, Wi-Fi standard for
            // routers, resolution/brightness for projectors, etc.
            $table->jsonb('specifications')->nullable();

            // Maintenance information.
            $table->boolean('needs_maintenance')->default(false);
            $table->text('notes')->nullable();

            $table->timestamps();

            $table->index(['institution_id', 'category']);
            $table->index(['institution_id', 'status']);
            $table->index('condition');
            $table->index('acquisition_year');
            $table->index('needs_maintenance');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ict_inventory');
    }
};
