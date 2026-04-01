<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Utilities\PlantActions\RegisterIrrigation;
use App\Utilities\PlantActions\RegisterPruning;
use App\Utilities\PlantActions\RegisterApplication;
use App\Utilities\PlantActions\RegisterTransplant;
use App\Utilities\PlantActions\RegisterObservation;
use App\Utilities\PlantActions\RegisterDeath;
use App\Utilities\PlantActions\RegisterState;

class ActionTypesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {

        $actionsTypes = [
            ['name' => 'Registrar Riego', 'action_class' => RegisterIrrigation::class],
            ['name' => 'Registrar Poda', 'action_class' => RegisterPruning::class],
            ['name' => 'Registrar Aplique producto', 'action_class' => RegisterApplication::class],
            ['name' => 'Registrar Transplante', 'action_class' => RegisterTransplant::class],
            ['name' => 'Registrar Observación con foto', 'action_class' => RegisterObservation::class],
            ['name' => 'Registrar Muerte de la planta', 'action_class' => RegisterDeath::class],
            ['name' => 'Registrar Cambio de Estado', 'action_class' => RegisterState::class],
            //...
        ];

        foreach ($actionsTypes as $type) {
            $existing = DB::table('action_types')
                ->where('action_class', $type['action_class'])
                ->first();

            if ($existing) {
                // Update the existing record and ensure no duplicates remain
                DB::table('action_types')
                    ->where('action_class', $type['action_class'])
                    ->where('id', '!=', $existing->id)
                    ->delete();

                DB::table('action_types')->where('id', $existing->id)->update([
                    'name' => $type['name'],
                    'updated_at' => now(),
                ]);
            } else {
                // Insert new record
                DB::table('action_types')->insert([
                    'name' => $type['name'],
                    'action_class' => $type['action_class'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }
}
