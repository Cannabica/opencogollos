<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\CropPlan;

class CropPlanSeeder extends Seeder
{
    public function run()
    {
        CropPlan::create([
            'tenant_id' => 1,
            'name' => 'Plan de Cultivo Estándar',
            
            // Datos Generales
            'rest_pruning' => 7,        // Ejemplo: días entre podas
            'rest_fert' => 7,           // Ejemplo: días entre fertilizaciones
            'stop_fert' => 7,            // Ejemplo: días antes del corte para dejar de fertilizar
            'irrigation' => 10,           // 10% de irrigacion
            
            // Etapa de Germinación
            'germination_since' => 2,
            'germination_until' => 7,
            'germination_light' => 0,
            'germination_darkness' => 0,
            'germination_humidity_since' => 0,
            'germination_humidity_until' => 0,
            'germination_temp_since' => 0,
            'germination_temp_until' => 0,
            
            // Etapa Plantula
            'plantula_since' => 21,
            'plantula_until' => 25,
            'plantula_light' => 18,
            'plantula_darkness' => 6,
            'plantula_humidity_since' => 65,
            'plantula_humidity_until' => 70,
            'plantula_temp_since' => 23,
            'plantula_temp_until' => 26,
            
            // Etapa Vegetativa
            'vegetative_since' => 105,
            'vegetative_until' => 110,
            'vegetative_light' => 18,
            'vegetative_darkness' => 6,
            'vegetative_humidity_since' => 40,
            'vegetative_humidity_until' => 70,
            'vegetative_temp_since' => 20,
            'vegetative_temp_until' => 30,
            
            // Etapa Floración
            'flowering_since' => 70,
            'flowering_until' => 91,
            'flowering_light' => 12,
            'flowering_darkness' => 12,
            'flowering_humidity_since' => 40,
            'flowering_humidity_until' => 50,
            'flowering_temp_since' => 20,
            'flowering_temp_until' => 26,
        ]);
    }
}