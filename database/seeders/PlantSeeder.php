<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Plant;
use App\Models\Seed;
use App\Models\Indoor;
use App\Models\Tenant;
use App\Models\Scopes\TenantScope;

class PlantSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */

    private $suelos;
    private $enriquecimientos;

    public function __construct()
    {
        $this->suelos = array_keys(\App\Filament\Tenant\Resources\PlantsResource::BASE_FLOOR_OPTIONS);
        $this->enriquecimientos = array_keys(\App\Filament\Tenant\Resources\PlantsResource::SOIL_ENRICHMENT_OPTIONS);
    }

    public function run(): void
    {
        $faker = fake();

        $tenants = Tenant::all();


        foreach ($tenants as $tenant) {
            $this->command->info("Creando plantas para tenant: " . $tenant->name);

            // Obtener todas las semillas disponibles (globales + locales del tenant)
            $seeds = Seed::where(function ($query) use ($tenant) {
                $query->whereNull('tenant_id')
                    ->orWhere('tenant_id', $tenant->id);
            })->get();

            if ($seeds->isEmpty()) {
                $this->command->info("No hay semillas disponibles para el tenant " . $tenant->name);
                continue;
            }

            // Obtener indoors del tenant
            $indoors = Indoor::withoutGlobalScope(TenantScope::class)
                ->where('tenant_id', $tenant->id)
                ->get();
            if ($indoors->isEmpty()) {
                $this->command->error('No hay indoors creados para el tenant ' . $tenant->name);
                continue;
            }

            $this->command->info("Indoors disponibles: " . $indoors->pluck('name')->implode(', '));

            $capacidades = [3, 5, 7, 10, 12, 15, 20, 30, 40, 50, 75];
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


            $plantsPerIndoor = ceil(20 / $indoors->count()); // Distribuir plantas equitativamente
            $plantsCreated = 0;

            foreach ($indoors as $indoor) {
                $this->command->info("Creando plantas para indoor: " . $indoor->name);

                for ($i = 0; $i < $plantsPerIndoor && $plantsCreated < 20; $i++) {
                    // Seleccionar elementos aleatorios
                    $suelosSeleccionados = $faker->randomElements($this->suelos, rand(1, min(5, count($this->suelos))));
                    $enriquecimientosSeleccionados = $faker->randomElements(
                        $this->enriquecimientos,
                        rand(1, min(5, count($this->enriquecimientos)))
                    );

                    $plant = Plant::withoutGlobalScope(TenantScope::class)->create([
                        'name' => $faker->randomElement($adjetivos) . ' ' .
                            $faker->randomElement($plantas) . ' #' . rand(1, 999),
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
}