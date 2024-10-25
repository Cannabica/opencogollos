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
        Schema::create('plants', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->foreignId('seed_id')->constrained('seeds');
            $table->foreignId('indoor_id')->constrained('indoors');
            $table->foreignId('plant_state_id')->nullable()->constrained('plant_states');
            $table->date('germination_date')->nullable();
            $table->string('flowerpot');
            $table->integer('capacity');
            $table->json('base_floor')->nullable();
            $table->json('soil_enrichment')->nullable();
            $table->softDeletes();
            $table->timestamps();

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('plants');
    }
};
