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
            $table->foreignId('institution_id')->constrained('institutions')->onDelete('cascade');
            $table->string('category');
            $table->string('condition'); 
            $table->integer('acquisition_year');
            $table->string('status');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ict_inventory');
    }
};