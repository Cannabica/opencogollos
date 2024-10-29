<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Plant;
use App\Models\Seed;
use App\Models\Indoor;

class PlantSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {   
        $indoors = Indoor::all()->pluck('id')->toArray();

        // Generar 10 plantas de ejemplo
        for ($i = 1; $i <= 10; $i++) {
            Plant::create([
                'name' => 'Plant Example ' . $i,
                'seed_id' => 1, 
                'indoor_id' => $indoors[array_rand($indoors)], // Seleccionar un indoor aleatorio
                'germination_date' => now()->subDays(rand(1, 30)), // Fecha de germinación aleatoria
                'flowerpot' => ['Geotextiles', 'Plásticas', 'Bolsones'][array_rand(['Geotextiles', 'Plásticas', 'Bolsones'])],
                'capacity' => rand(1, 20), // Capacidad aleatoria entre 1 y 20
                'base_floor' => json_encode(array_rand(['Turba', 'Guano', 'Estiércol', 'Polvo de roca', 'Arena', 'Fibra de coco', 'Abono naturales', 'Corteza de pino', 'Perlita', 'Vermiculita'], 3)), // Seleccionar 3 elementos aleatorios
                'soil_enrichment' => json_encode(array_rand(['Posos de café y/o te', 'Cascaras de huevo', 'Humus de lombriz', 'Pieles de frutas y verd', 'Abono', 'Fibra de coco', 'Perlita', 'Vermiculita', 'Arena', 'Harina de huesos', 'Harina de sangre', 'Roca fosfórica', 'Cal'], 3)), // Seleccionar 3 elementos aleatorios
            ]);
        }
    }
}
