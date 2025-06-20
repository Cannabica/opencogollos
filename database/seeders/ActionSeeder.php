<?php

namespace Database\Seeders;

use App\Models\Action;
use App\Models\ActionType;
use App\Models\Indoor;
use App\Models\Plant;
use App\Models\Tenant;
use App\Models\Scopes\TenantScope;
use Illuminate\Database\Seeder;
use Illuminate\Support\Arr;

class ActionSeeder extends Seeder
{
    public function run()
    {

        // Verificar la existencia del tenant
        $tenant = Tenant::withoutGlobalScope(TenantScope::class)->find(1);
        if (!$tenant) {
            $this->command->error('No se encontró el tenant ID 1. Ejecuta ExampleDataSeeder primero.');
            return;
        }

        // Obtener y verificar los datos necesarios
        $actionTypes = ActionType::withoutGlobalScope(TenantScope::class)->get();
        $actionTypesCount = $actionTypes->count();
        
        // Obtener indoors del tenant
        $indoors = Indoor::withoutGlobalScope(TenantScope::class)
            ->where('tenant_id', $tenant->id)
            ->get();
        $indoorsCount = $indoors->count();
        
        // Verificar plantas por indoor
        $plantsByIndoor = [];
        $totalPlants = 0;
        foreach ($indoors as $indoor) {
            $plants = Plant::withoutGlobalScope(TenantScope::class)
                ->where('indoor_id', $indoor->id)
                ->get();
            $plantsByIndoor[$indoor->id] = $plants;
            $totalPlants += $plants->count();
            $this->command->info("Indoor {$indoor->name}: {$plants->count()} plantas");
        }

        // Verificar requisitos
        if ($actionTypesCount === 0) {
            $this->command->error('No hay tipos de acciones creados.');
            return;
        }

        if ($indoorsCount === 0) {
            $this->command->error('No hay indoors creados para el tenant.');
            $this->command->info('IDs de tenant existentes: ' . Indoor::withoutGlobalScope(TenantScope::class)->distinct('tenant_id')->pluck('tenant_id')->implode(', '));
            return;
        }

        if ($totalPlants === 0) {
            $this->command->error('No hay plantas creadas para el tenant.');
            return;
        }

        $this->command->info("Encontrados para tenant {$tenant->name}:");
        $this->command->info("- {$actionTypesCount} tipos de acciones");
        $this->command->info("- {$indoorsCount} indoors");
        $this->command->info("- {$totalPlants} plantas en total");

        foreach ($actionTypes as $actionType) {
            for ($i = 0; $i < 12; $i++) {
                // Seleccionar un indoor del tenant actual
                $indoor = Indoor::withoutGlobalScope(TenantScope::class)
                    ->where('tenant_id', $tenant->id)
                    ->inRandomOrder()
                    ->first();

                if (!$indoor) {
                    $this->command->error("No se encontró ningún indoor para el tenant {$tenant->name}");
                    return;
                }

                // Obtener plantas del indoor seleccionado
                $plants = Plant::withoutGlobalScope(TenantScope::class)
                    ->where('indoor_id', $indoor->id)
                    ->pluck('id')
                    ->toArray();

                if (empty($plants)) {
                    $this->command->info("Saltando indoor {$indoor->name} - no tiene plantas");
                    continue;
                }

                $selectedPlants = Arr::random(
                    $plants, 
                    rand(1, min(count($plants), 5)) // Máximo 5 plantas por acción
                );

                $data = $this->generateActionData($actionType->id, $selectedPlants);

                $action = Action::withoutGlobalScope(TenantScope::class)->create([
                    'action_date' => now()->subDays(rand(0, 90))
                        ->subHours(rand(0, 23))
                        ->subMinutes(rand(0, 59)),
                    'indoor_id' => $indoor->id,
                    'action_type_id' => $actionType->id,
                    'data' => $data,
                    'tenant_id' => $tenant->id,
                ]);

                // Crear las relaciones en la tabla pivot
                $action->plants()->attach($selectedPlants);
            }
        }
    }

    private function generateActionData($actionTypeId, $selectedPlants): array
    {
        $potTypes = [
            'Geotextiles',
            'Plásticas',
            'Bolsones'
        ];
    
        $potCapacities = [
            3, 5, 7, 10, 12, 15, 20, 30, 40, 50, 75
        ];
    
    
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
                    'new_flowerpot' => Arr::random($potTypes),
                    'new_capacity' => Arr::random($potCapacities),
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
                    'state' => Arr::random([
                        'Etapa de Germinación',
                        'Etapa de Plantula',
                        'Etapa Vegetativa',
                        'Etapa Floracion'
                    ])
                ];
                break;
        }

        return $data;
    }
}