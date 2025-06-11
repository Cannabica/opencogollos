<?php

namespace Database\Seeders;

use App\Models\Tenant;
use App\Models\User;
use App\Models\Indoor;
use App\Models\Plant;
use App\Models\Seed;
use App\Models\Action;
use App\Models\Scopes\TenantScope;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Faker\Factory;

class ExampleDataSeeder extends Seeder
{
    private $seedTypes = [
        'Critical',
        'Amnesia',
        'White Widow',
        'Northern Lights',
        'OG Kush',
        'Purple Haze',
        'Sour Diesel',
        'Blue Dream',
        'Girl Scout Cookies',
        'AK-47'
    ];

    private $plantStates = [
        'Etapa de Germinación',
        'Etapa de Plantula',
        'Etapa Vegetativa',
        'Etapa Floracion',
        'muerta',
    ];

    private $potTypes = [
        'Geotextiles',
        'Plásticas',
        'Bolsones'
    ];

    private $potCapacities = [
        3, 5, 7, 10, 12, 15, 20, 30, 40, 50, 75
    ];

    private $suelos = ['Turba', 'Guano', 'Estiércol', 'Polvo de roca', 'Arena', 'Fibra de coco', 'Abono naturales', 'Corteza de pino', 'Perlita', 'Vermiculita'];
    private $enriquecimientos = ['Posos de café y/o te', 'Cascaras de huevo', 'Humus de lombriz', 'Pieles de frutas y verd', 'Abono', 'Fibra de coco', 'Perlita', 'Vermiculita', 'Arena', 'Harina de huesos', 'Harina de sangre', 'Roca fosfórica', 'Cal'];


    private $adjetivos = [
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

    private $plantas = [
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

    private function createTenantSpecificSeed($tenantId): Seed
    {
        return Seed::create([
            'name' => Arr::random($this->seedTypes),
            'tenant_id' => $tenantId,
            'seed_type' => Arr::random(['Fotoperiodica feminizada', 'Fotoperiodica regular', 'Automatica']),
            'flowering_time' => rand(45, 90),
            'ratio_thc' => rand(5, 25),
            'ratio_cbd' => rand(1, 15),
            'aprobado_inase' => (bool)rand(0, 1),
            'provider' => 'Tenant Specific Provider',
        ]);
    }

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
    
        $this->command->info('Iniciando ExampleDataSeeder...');
        $faker = Factory::create('es_ES');

        // Crear múltiples tenants
        $tenantConfigs = [
            [
                'name' => 'Tenant Example',
                'email' => 'tenant@example.com',
                'user_name' => 'User Tenant',
                'user_email' => 'user@tenant.com',
            ],
            [
                'name' => 'Green Gardens Co.',
                'email' => 'admin@greengardens.com',
                'user_name' => 'Green Gardens Manager',
                'user_email' => 'manager@greengardens.com',
            ],
            [
                'name' => 'Urban Cultivators',
                'email' => 'contact@urbancultivators.com',
                'user_name' => 'Urban Cultivator Admin',
                'user_email' => 'admin@urbancultivators.com',
            ]
        ];

        foreach ($tenantConfigs as $config) {
            // Verificar si el tenant ya existe
            $tenant = Tenant::withoutGlobalScope(TenantScope::class)
                ->whereRaw('LOWER(email) = ?', [strtolower($config['email'])])
                ->first();

            if (!$tenant) {
                
                // Crear tenant con el siguiente ID disponible
                $tenant = Tenant::withoutGlobalScope(TenantScope::class)->create([
                    'name' => $config['name'],
                    'email' => $config['email'],
                    'active' => 1,
                ]);

                if (!$tenant) {
                    $this->command->error("Error al crear el tenant {$config['name']}");
                    continue;
                }
                $this->command->info("Tenant creado: " . $tenant->name . " con ID: " . $tenant->id);
            } else {
                $this->command->info("Tenant existente encontrado: " . $tenant->name);
            }

            // Verificar si el usuario ya existe
            $user = User::withoutGlobalScope(TenantScope::class)
                ->where('email', $config['user_email'])
                ->first();

            if (!$user) {
                // Crear usuario solo si no existe
                $user = User::withoutGlobalScope(TenantScope::class)->create([
                    'name' => $config['user_name'],
                    'email' => $config['user_email'],
                    'tenant_id' => $tenant->id,
                    'password' => Hash::make('password'),
                ]);

                if (!$user) {
                    $this->command->error("Error al crear el usuario para {$config['name']}");
                    continue;
                }
                $this->command->info("Usuario creado: " . $user->name);
            } else {
                $this->command->info("Usuario existente encontrado: " . $user->name);
            }

            // Verificar si ya existen indoors para este tenant
            $existingIndoors = Indoor::withoutGlobalScope(TenantScope::class)
                ->where('tenant_id', $tenant->id)
                ->count();

            if ($existingIndoors > 0) {
                $this->command->info("Ya existen indoors para el tenant {$tenant->name}, saltando creación de indoors...");
                continue;
            }

            // Crear un crop plan para el tenant primero con valores por defecto
            $cropPlan = DB::table('crop_plans')->insertGetId([
                'name' => 'Plan de Cultivo ' . $tenant->name,
                'tenant_id' => $tenant->id,
                'rest_pruning' => 7,
                'rest_fert' => 14,
                'stop_fert' => 7,
                'irrigation' => 2,
                'germination_since' => 0,
                'germination_until' => 14,
                'germination_light' => 18,
                'germination_darkness' => 6,
                'germination_humidity_since' => 70,
                'germination_humidity_until' => 80,
                'germination_temp_since' => 22,
                'germination_temp_until' => 26,
                'plantula_since' => 15,
                'plantula_until' => 30,
                'plantula_light' => 18,
                'plantula_darkness' => 6,
                'plantula_humidity_since' => 60,
                'plantula_humidity_until' => 70,
                'plantula_temp_since' => 20,
                'plantula_temp_until' => 25,
                'vegetative_since' => 31,
                'vegetative_until' => 60,
                'vegetative_light' => 18,
                'vegetative_darkness' => 6,
                'vegetative_humidity_since' => 50,
                'vegetative_humidity_until' => 60,
                'vegetative_temp_since' => 20,
                'vegetative_temp_until' => 25,
                'flowering_since' => 61,
                'flowering_until' => 90,
                'flowering_light' => 12,
                'flowering_darkness' => 12,
                'flowering_humidity_since' => 40,
                'flowering_humidity_until' => 50,
                'flowering_temp_since' => 18,
                'flowering_temp_until' => 24,
                'created_at' => now(),
                'updated_at' => now()
            ]);

            // Crear indoors con configuraciones aleatorias
            $numIndoors = rand(2, 5);
            for ($i = 0; $i < $numIndoors; $i++) {
                $indoorConfig = $this->generateRandomIndoorConfig($cropPlan);
                $indoor = Indoor::withoutGlobalScope(TenantScope::class)->create(array_merge(
                    $indoorConfig,
                    ['tenant_id' => $tenant->id]
                ));

                if (!$indoor) {
                    $this->command->error("Error al crear el indoor {$indoorConfig['name']} para {$tenant->name}");
                    continue;
                }
                $this->command->info("Indoor creado: " . $indoor->name);

                // Crear algunas semillas específicas del tenant
                $numTenantSeeds = rand(2, 4);
                for ($j = 0; $j < $numTenantSeeds; $j++) {
                    $tenantSeed = $this->createTenantSpecificSeed($tenant->id);
                    if ($tenantSeed) {
                        $this->command->info("Semilla específica del tenant creada: " . $tenantSeed->name . " para " . $tenant->name);
                    }
                }

                // Obtener todas las semillas disponibles (globales + locales del tenant)
                $availableSeeds = Seed::withoutGlobalScope(TenantScope::class)
                    ->where(function($query) use ($tenant) {
                        $query->whereNull('tenant_id')
                              ->orWhere('tenant_id', $tenant->id);
                    })->get();

                if ($availableSeeds->isEmpty()) {
                    $this->command->error("No hay semillas disponibles para el tenant " . $tenant->name);
                    continue;
                }

                // Crear plantas usando semillas disponibles
                $numPlants = rand(5, 15);


                for ($k = 0; $k < $numPlants; $k++) {
                    // Seleccionar una semilla aleatoria de las disponibles
                    $randomSeed = $availableSeeds->random();
                    
                    // Verificar que el indoor pertenezca al tenant actual
                    if ($indoor->tenant_id !== $tenant->id) {
                        $this->command->error("El indoor no pertenece al tenant actual");
                        continue;
                    }
                    
                    $suelosSeleccionados = $faker->randomElements($this->suelos, rand(3, 10));
                    $enriquecimientosSeleccionados = $faker->randomElements($this->enriquecimientos, rand(3, 10));


                    $plant = Plant::withoutGlobalScope(TenantScope::class)->create([
                        'name' => $faker->randomElement($this->adjetivos) . ' ' .
                        $faker->randomElement($this->plantas) . ' #' . rand(1, 999),
                        'indoor_id' => $indoor->id,
                        'seed_id' => $randomSeed->id,
                        'state' => Arr::random($this->plantStates),
                        'germination_date' => Carbon::now()->subDays(rand(10, 120)),
                        'flowerpot' => Arr::random($this->potTypes),
                        'capacity' => Arr::random($this->potCapacities),
                        'base_floor' => json_encode($suelosSeleccionados),
                        'soil_enrichment' => json_encode($enriquecimientosSeleccionados)
                    ]);

                    if ($plant) {
                        $this->command->info("Planta creada: " . $plant->name . " en " . $indoor->name);
                        
                        // Crear acciones para esta planta
                        $this->createRandomActions($plant, $indoor->id, $tenant->id);
                    }
                }
            }
        }
    }

    private function generateRandomIndoorConfig(int $cropPlanId = null): array
    {
        $large = rand(4, 15);
        $width = rand(3, 10);
        $height = rand(2, 8);

        $numFans = rand(1, 4);
        $fans = array_map(function() {
            return ['inches' => rand(8, 20)];
        }, range(1, $numFans));

        $numLamps = rand(1, 3);
        $lamps = array_map(function() {
            return [
                'power' => rand(40, 150),
                'technology' => Arr::random(['led', 'sodio', 'led full spectrum']),
                'coverage_area' => rand(10, 30),
            ];
        }, range(1, $numLamps));

        return [
            'crop_plan_id' => $cropPlanId,
            'name' => 'Indoor ' . Arr::random(['Principal', 'Vegetativo', 'Floracion', 'Experimental', 'Madre']) . ' ' . rand(1, 99),
            'large' => $large,
            'width' => $width,
            'height' => $height,
            'fans' => $fans,
            'lamps' => $lamps,
            'hygometer' => (bool)rand(0, 1),
            'humidifier' => (bool)rand(0, 1),
            'peak_quantity' => rand(30, 200),
            'scheduled_time' => rand(2, 8),
            'times_a_day' => rand(1, 6),
            'scheduled_days' => Arr::random(['lunes', 'martes', 'miércoles', 'jueves', 'viernes', 'sábado', 'domingo'], rand(3, 7)),
        ];
    }

    private function createRandomActions(Plant $plant, $indoorId, $tenantId): void
    {
        $numActions = rand(3, 8);
        $actionTypes = range(1, 7); // Los 7 tipos de acciones disponibles

        for ($i = 0; $i < $numActions; $i++) {
            $actionTypeId = Arr::random($actionTypes);
            $actionDate = Carbon::now()->subDays(rand(1, 60));
            
            $data = $this->generateActionData($actionTypeId, [$plant->id]);

            $action = Action::withoutGlobalScope(TenantScope::class)->create([
                'action_date' => $actionDate,
                'indoor_id' => $indoorId,
                'action_type_id' => $actionTypeId,
                'tenant_id' => $tenantId,
                'data' => $data,
            ]);

            if ($action) {
                $action->plants()->attach($plant->id);
                $this->command->info("Acción tipo {$actionTypeId} creada para planta {$plant->name}");
            }
        }
    }

    private function generateActionData($actionTypeId, $plantIds): array
    {
        $data = [];
        switch ($actionTypeId) {
            case 1: // Riego
                $data['irrigation'] = [
                    'irrigation_type' => Arr::random(['liters', 'timer']),
                    'liters' => rand(1, 20),
                    'timer' => rand(5, 60)
                ];
                break;

            case 2: // Poda
                $data['pruning'] = [
                    'pruning_type' => Arr::random([
                        ['excess'],
                        ['dry'],
                        ['apical', 'topping'],
                        ['scrog', 'excess']
                    ], 1)[0]
                ];
                break;

            case 3: // Aplicación de producto
                $data['product_application'] = [
                    'application_type' => Arr::random([
                        'vege',
                        'flora',
                        'plantula',
                        'plague',
                        'other'
                    ]),
                    'observation' => fake()->sentence(),
                    'comments' => fake()->paragraph()
                ];
                break;

            case 4: // Transplante
                $data['transplant'] = [
                    'new_flowerpot' => Arr::random($this->potTypes),
                    'new_capacity' => Arr::random($this->potCapacities),
                ];
                break;

            case 5: // Observación con foto
                $data['observation'] = [
                    'image' => 'dummy/path/to/image.jpg',
                    'comments' => fake()->paragraph()
                ];
                break;

            case 6: // Muerte (no necesita datos adicionales)
                break;

            case 7: // Cambio de estado
                $data['change_state'] = [
                    'state' => Arr::random($this->plantStates)
                ];
                break;
        }

        return $data;
    }
}
