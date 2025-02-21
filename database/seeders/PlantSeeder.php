<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Plant;
use App\Models\Seed;
use App\Models\Indoor;
use Faker\Factory;

class PlantSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $faker = Factory::create('es_ES');
        $indoors = Indoor::pluck('id')->toArray();

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

        for ($i = 1; $i <= 10; $i++) {
            Plant::create([
                'name' => $faker->randomElement($adjetivos) . ' ' .
                    $faker->randomElement($plantas),
                'indoor_id' => Indoor::inRandomOrder()->first()->id ?? 1,
                'seed_id' => Seed::inRandomOrder()->first()->id ?? 1,
                'germination_date' => $faker->dateTimeBetween('-6 months', 'now'),
                'flowerpot' => $faker->randomElement($macetas),
                'state' => $faker->randomElement($etapas),
                'capacity' => $faker->randomElement($capacidades),

                'base_floor' => json_encode(array_rand(['Turba', 'Guano', 'Estiércol', 'Polvo de roca', 'Arena', 'Fibra de coco', 'Abono naturales', 'Corteza de pino', 'Perlita', 'Vermiculita'], num: 3)), // Seleccionar 3 elementos aleatorios
                'soil_enrichment' => json_encode(array_rand(['Posos de café y/o te', 'Cascaras de huevo', 'Humus de lombriz', 'Pieles de frutas y verd', 'Abono', 'Fibra de coco', 'Perlita', 'Vermiculita', 'Arena', 'Harina de huesos', 'Harina de sangre', 'Roca fosfórica', 'Cal'], 3)), // Seleccionar 3 elementos aleatorios

            ]);
        }
    }
}