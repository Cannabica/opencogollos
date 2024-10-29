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
        Schema::create('plant_states', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->integer('days_since');
            $table->integer('days_until');
            $table->float('min_daylight_hours');
            $table->float('max_daylight_hours');
            $table->float('min_humidity');
            $table->float('max_humidity');
            $table->json('actions');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('plant_states');
    }
};
