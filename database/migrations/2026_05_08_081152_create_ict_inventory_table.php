<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('ict_inventories', function (Blueprint $table) {
            $table->id('asset_id');
            $table->foreignId('institution_id')->constrained('institutions')->onDelete('cascade');
            $table->string('category'); // e.g., 'computer', 'projector', 'smart_tv'
            $table->string('condition'); // e.g., 'new', 'good', 'needs_repair'
            $table->integer('acquisition_year');
            $table->string('status'); // e.g., 'active', 'retired'
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ict_inventories');
    }
};
