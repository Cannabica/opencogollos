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
        Schema::create('crop_plans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->nullable()->constrained('tenants');

            // Datos Generales
            $table->string('name');
            $table->integer('rest_pruning');  // Descanso entre podas
            $table->integer('rest_fert');        // Descanso entre fertilizaciones
            $table->integer('stop_fert');          // Dejar de fertilizar antes de fecha de corte
            $table->integer('irrigation');    // Irrigación de maceta sugerida
            
            // Etapa de Germinación
            $table->integer('germination_since');  // Período comprendido desde
            $table->integer('germination_until');  // Período comprendido hasta
            $table->integer('germination_light');  // Horas de luz
            $table->integer('germination_darkness'); // Horas de oscuridad
            $table->integer('germination_humidity_since'); // Humedad recomendada desde
            $table->integer('germination_humidity_until'); // Humedad recomendada hasta
            $table->integer('germination_temp_since');  // Temperatura recomendada desde
            $table->integer('germination_temp_until');  // Temperatura recomendada hasta
            
            // Etapa Plantula
            $table->integer('plantula_since');  // Período comprendido desde
            $table->integer('plantula_until');  // Período comprendido hasta
            $table->integer('plantula_light');  // Horas de luz
            $table->integer('plantula_darkness'); // Horas de oscuridad
            $table->integer('plantula_humidity_since'); // Humedad recomendada desde
            $table->integer('plantula_humidity_until'); // Humedad recomendada hasta
            $table->integer('plantula_temp_since');  // Temperatura recomendada desde
            $table->integer('plantula_temp_until');  // Temperatura recomendada hasta
            
            // Etapa Vegetativa
            $table->integer('vegetative_since');  // Período comprendido desde
            $table->integer('vegetative_until');  // Período comprendido hasta
            $table->integer('vegetative_light');  // Horas de luz
            $table->integer('vegetative_darkness'); // Horas de oscuridad
            $table->integer('vegetative_humidity_since'); // Humedad recomendada desde
            $table->integer('vegetative_humidity_until'); // Humedad recomendada hasta
            $table->integer('vegetative_temp_since');  // Temperatura recomendada desde
            $table->integer('vegetative_temp_until');  // Temperatura recomendada hasta
            
            // Etapa Floración
            $table->integer('flowering_since');  // Período comprendido desde
            $table->integer('flowering_until');  // Período comprendido hasta
            $table->integer('flowering_light');  // Horas de luz
            $table->integer('flowering_darkness'); // Horas de oscuridad
            $table->integer('flowering_humidity_since'); // Humedad recomendada desde
            $table->integer('flowering_humidity_until'); // Humedad recomendada hasta
            $table->integer('flowering_temp_since');  // Temperatura recomendada desde
            $table->integer('flowering_temp_until');  // Temperatura recomendada hasta
            
            $table->timestamps();
            $table->softDeletes();  // Soft delete enabled
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('crop_plans');
    }
};
