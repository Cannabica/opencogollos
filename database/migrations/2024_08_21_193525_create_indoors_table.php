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
        Schema::create('indoors', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->decimal('large', 8, 2);
            $table->decimal('width', 8, 2);
            $table->decimal('height', 8, 2);
            $table->foreignId('tenant_id')->constrained('tenants');
            $table->json('fans')->nullable();
            $table->json('lamps')->nullable();
            $table->boolean('hygometer')->default(false);
            $table->boolean('humidifier')->default(false);
            $table->integer('peak_quantity')->nullable();
            $table->integer('scheduled_time')->nullable();
            $table->integer('times_a_day')->nullable();
            $table->json('scheduled_days')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('indoors');
    }
};
