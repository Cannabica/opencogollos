<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Plant;
use App\Models\Seed;
use App\Models\Indoor;
use App\Models\Tenant;
use App\Models\Scopes\TenantScope;
use Faker\Factory;

class PlantSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $faker = Factory::create('es_ES');
        
        // Verificar la existencia del tenant
        $tenant = Tenant::withoutGlobalScope(TenantScope::class)->find(1);
        if (!$tenant) {
            $this->command->error('No se encontró el tenant ID 1. Ejecuta ExampleDataSeeder primero.');
            return;
        }

        // Obtener indoors del tenant
        $indoors = Indoor::withoutGlobalScope(TenantScope::class)
            ->where('tenant_id', $tenant->id)
            ->get();
        if ($indoors->isEmpty()) {
            $this->command->error('No hay indoors creados para el tenant ' . $tenant->name);
            return;
        }

        $this->command->info("Creando plantas para tenant: " . $tenant->name);
        $this->command->info("Indoors disponibles: " . $indoors->pluck('name')->implode(', '));

        $capacidades = ['N10', 'N12', 'N14', '3L', '5L', '7L', '10L', '12L', '15L', '20L', '30L', '40L', '50L', '75L'];
        $macetas = ['Geotextiles', 'Plásticas', 'Bolsones'];
        $etapas = ['Etapa de Germinación', 'Etapa de Plantula', 'Etapa Vegetativa', 'Etapa Floracion'];
        $adjetivos = [
            'Chamuyero',
            'Pibe',
            'Tumbero',
            'Fumanchu',
            'Quemero',
            'Fierrero',
            'Trucho',
            'Gatero',
            'Sarasa',
            'Mina',
            'Transa',
            'Faso',
            'Porro',
            'Japi',
            'Pistola',
            'Cogollo',
            'Cana',
            'Yuta',
            'Merca',
            'Fumarola'
        ];

        $plantas = [
            'María Juana',
            'Crippa',
            'Faso Sativa',
            'Indica Trucha',
            'Haze Paternal',
            'Skunk de La Boca',
            'Gorilla Glue de Palermo',
            'OG Kush Porteña',
            'Durban Poison de Mataderos',
            'Churro Diesel',
            'Mango Kush Cordobesa',
            'AK-47 Rosarina',
            'Blue Dream Chacarita',
            'White Widow Santafesina',
            'Peyote Cumbiero',
            'Hongos del Subte',
            'Acido del Conurbano',
            'Mistongo Húmedo',
            'Quemero Criollo',
            'Porro Patrio'
        ];

        $suelos = ['Turba', 'Guano', 'Estiércol', 'Polvo de roca', 'Arena', 'Fibra de coco', 'Abono naturales', 'Corteza de pino', 'Perlita', 'Vermiculita'];
        $enriquecimientos = ['Posos de café y/o te', 'Cascaras de huevo', 'Humus de lombriz', 'Pieles de frutas y verd', 'Abono', 'Fibra de coco', 'Perlita', 'Vermiculita', 'Arena', 'Harina de huesos', 'Harina de sangre', 'Roca fosfórica', 'Cal'];

        // Verificar disponibilidad de semillas
        $seeds = Seed::withoutGlobalScope(TenantScope::class)
            ->where('tenant_id', $tenant->id)
            ->get();
        if ($seeds->isEmpty()) {
            $this->command->error('No hay semillas disponibles para el tenant ' . $tenant->name);
            return;
        }

        $plantsPerIndoor = ceil(20 / $indoors->count()); // Distribuir plantas equitativamente
        $plantsCreated = 0;

        foreach ($indoors as $indoor) {
            $this->command->info("Creando plantas para indoor: " . $indoor->name);
            
            for ($i = 0; $i < $plantsPerIndoor && $plantsCreated < 20; $i++) {
                // Seleccionar elementos aleatorios
                $suelosSeleccionados = $faker->randomElements($suelos, 3);
                $enriquecimientosSeleccionados = $faker->randomElements($enriquecimientos, 3);

                $plant = Plant::withoutGlobalScope(TenantScope::class)->create([
                    'name' => $faker->randomElement($adjetivos) . ' ' .
                        $faker->randomElement($plantas),
                    'indoor_id' => $indoor->id,
                    'seed_id' => $seeds->random()->id,
                    'germination_date' => $faker->dateTimeBetween('-6 months', 'now'),
                    'flowerpot' => $faker->randomElement($macetas),
                    'state' => $faker->randomElement($etapas),
                    'capacity' => $faker->randomElement($capacidades),
                    'base_floor' => json_encode($suelosSeleccionados),
                    'soil_enrichment' => json_encode($enriquecimientosSeleccionados),
                ]);

                $plantsCreated++;
                $this->command->info("  - Planta creada: {$plant->name} ({$plant->state})");
            }
        }
    }
}