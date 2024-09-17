<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AttentionTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $attentionTypes = [
            'Registrar riego',
            'Registrar poda',
            'Registrar aplique producto',
            'Registrar transplante',
            'Observación con foto',
            'Muerte de la planta'
        ];

        foreach ($attentionTypes as $type) {
            DB::table('attention_types')->insert([
                'name' => $type,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}
