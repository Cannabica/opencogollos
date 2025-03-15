<?php

namespace Database\Seeders;

use App\Models\Action;
use App\Models\ActionType;
use App\Models\Indoor;
use App\Models\Plant;
use Illuminate\Database\Seeder;
use Illuminate\Support\Arr;

class ActionSeeder extends Seeder
{
    public function run()
    {
        // Asegurarse de que existen tipos de acción, indoors y plantas
        if (ActionType::count() < 7 || Indoor::count() === 0 || Plant::count() === 0) {
            $this->command->error('Primero crea ActionTypes, Indoors y Plants!');
            return;
        }

        $actionTypes = ActionType::all();

        foreach ($actionTypes as $actionType) {
            for ($i = 0; $i < 12; $i++) { // 5 acciones por tipo
                $indoor = Indoor::inRandomOrder()->first();
                $plants = Plant::where('indoor_id', $indoor->id)->pluck('id')->toArray();

                if (empty($plants)) continue;

                $selectedPlants = Arr::random(
                    $plants, 
                    rand(1, count($plants))
                );

                $data = $this->generateActionData($actionType->id, $selectedPlants);

                Action::create([
                    'action_date' => now()->subDays(rand(0, 90))
                        ->subHours(rand(0, 23))
                        ->subMinutes(rand(0, 59)),
                    'indoor_id' => $indoor->id,
                    'action_type_id' => $actionType->id,
                    'data' => $data,
                ]);
            }
        }
    }

    private function generateActionData($actionTypeId, $selectedPlants): array
    {
        $data = ['plants' => $selectedPlants];

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
                    'new_pot_size' => Arr::random([
                        'N10', 'N12', 'N14', '3L', '5L', 
                        '7L', '10L', '12L', '15L', '20L'
                    ])
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