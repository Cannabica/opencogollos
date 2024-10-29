<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ActionTypesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $actionsTypes = [
            'Registrar riego',
            'Registrar poda',
            'Registrar aplique producto',
            'Registrar transplante',
            'Observación con foto',
            'Muerte de la planta',
            'Cambio de Estado'
        ];

        foreach ($actionsTypes as $type) {
            DB::table('action_types')->insert([
                'name' => $type,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}
