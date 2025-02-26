<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

use App\Models\Tenant;


class SeedsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $csvRows = [
            [
                'Nombre' => 'Tropicana WFC',
                'Banco/Proveedor' => 'Sweed Lab',
                'Tipo de semilla' => 'Feminizada fotoperiódica',
                'Tiempo de flora (semanas)' => '9-10',
                'THC (%)' => 'Alto',
                'CBD (%)' => '<1',
                'Registro INASE o banco' => 'Sí (fiscalizada)'
            ],
            [
                'Nombre' => 'Sundae Grape',
                'Banco/Proveedor' => 'Secret File',
                'Tipo de semilla' => 'Feminizada fotoperiódica',
                'Tiempo de flora (semanas)' => '8-9',
                'THC (%)' => 'Alto',
                'CBD (%)' => '<1',
                'Registro INASE o banco' => 'No especificado'
            ],
            [
                'Nombre' => 'Sticky Sherbet',
                'Banco/Proveedor' => 'Secret File',
                'Tipo de semilla' => 'Feminizada fotoperiódica',
                'Tiempo de flora (semanas)' => 'No especificado',
                'THC (%)' => 'Alto',
                'CBD (%)' => '<1',
                'Registro INASE o banco' => 'No especificado'
            ],
            [
                'Nombre' => 'Tropilato Chip',
                'Banco/Proveedor' => 'Secret File',
                'Tipo de semilla' => 'Regular',
                'Tiempo de flora (semanas)' => '7-8',
                'THC (%)' => 'Alto',
                'CBD (%)' => '<1',
                'Registro INASE o banco' => 'No especificado'
            ],
            [
                'Nombre' => 'Diamond Kush',
                'Banco/Proveedor' => 'Secret File',
                'Tipo de semilla' => 'Feminizada fotoperiódica',
                'Tiempo de flora (semanas)' => '8',
                'THC (%)' => 'Alto',
                'CBD (%)' => '<1',
                'Registro INASE o banco' => 'No especificado'
            ],
            [
                'Nombre' => 'Skywalker Kush',
                'Banco/Proveedor' => 'Secret File',
                'Tipo de semilla' => 'Feminizada fotoperiódica',
                'Tiempo de flora (semanas)' => 'No especificado',
                'THC (%)' => 'Alto',
                'CBD (%)' => '<1',
                'Registro INASE o banco' => 'No especificado'
            ],
            [
                'Nombre' => 'Miracle Breath',
                'Banco/Proveedor' => 'Sweed Lab',
                'Tipo de semilla' => 'Feminizada fotoperiódica',
                'Tiempo de flora (semanas)' => 'No especificado',
                'THC (%)' => 'Alto',
                'CBD (%)' => '<1',
                'Registro INASE o banco' => 'No especificado'
            ],
            [
                'Nombre' => 'MAC S1',
                'Banco/Proveedor' => 'Sweed Lab',
                'Tipo de semilla' => 'Feminizada fotoperiódica',
                'Tiempo de flora (semanas)' => '7-8',
                'THC (%)' => 'Alto',
                'CBD (%)' => '<1',
                'Registro INASE o banco' => 'No especificado'
            ],
            [
                'Nombre' => 'Old Fashioned Cookies',
                'Banco/Proveedor' => 'Sweed Lab',
                'Tipo de semilla' => 'Feminizada fotoperiódica',
                'Tiempo de flora (semanas)' => 'No especificado',
                'THC (%)' => 'Alto',
                'CBD (%)' => '<1',
                'Registro INASE o banco' => 'No especificado'
            ],
            [
                'Nombre' => 'Bananukis',
                'Banco/Proveedor' => 'R-Kiem Seeds',
                'Tipo de semilla' => 'Feminizada fotoperiódica',
                'Tiempo de flora (semanas)' => 'No especificado',
                'THC (%)' => '25',
                'CBD (%)' => '<1',
                'Registro INASE o banco' => 'No especificado'
            ],
            [
                'Nombre' => 'Petroleum',
                'Banco/Proveedor' => 'Sweed Lab',
                'Tipo de semilla' => 'Feminizada fotoperiódica',
                'Tiempo de flora (semanas)' => 'No especificado',
                'THC (%)' => 'Alto',
                'CBD (%)' => '<1',
                'Registro INASE o banco' => 'No especificado'
            ],
            [
                'Nombre' => 'Choco OG',
                'Banco/Proveedor' => 'No especificado',
                'Tipo de semilla' => 'Feminizada fotoperiódica',
                'Tiempo de flora (semanas)' => '7-8',
                'THC (%)' => '15.8',
                'CBD (%)' => '0.51',
                'Registro INASE o banco' => 'Sí (INASE)'
            ],
            [
                'Nombre' => 'Phantom Ice',
                'Banco/Proveedor' => 'No especificado',
                'Tipo de semilla' => 'Feminizada fotoperiódica',
                'Tiempo de flora (semanas)' => '8',
                'THC (%)' => '17-20',
                'CBD (%)' => '0.5',
                'Registro INASE o banco' => 'No especificado'
            ],
            [
                'Nombre' => 'Sweet Mandarine Zkittlez F1 Fast Version®',
                'Banco/Proveedor' => 'No especificado',
                'Tipo de semilla' => 'Feminizada fotoperiódica',
                'Tiempo de flora (semanas)' => '7',
                'THC (%)' => '19-24',
                'CBD (%)' => '0.5',
                'Registro INASE o banco' => 'No especificado'
            ],
            [
                'Nombre' => 'Moby Dick',
                'Banco/Proveedor' => 'No especificado',
                'Tipo de semilla' => 'Feminizada fotoperiódica',
                'Tiempo de flora (semanas)' => '9-10',
                'THC (%)' => '27',
                'CBD (%)' => '<1',
                'Registro INASE o banco' => 'No especificado'
            ],
            [
                'Nombre' => '9001',
                'Banco/Proveedor' => 'KameSeeds',
                'Tipo de semilla' => 'Feminizada fotoperiódica',
                'Tiempo de flora (semanas)' => 'No especificado',
                'THC (%)' => 'Alto',
                'CBD (%)' => '<1',
                'Registro INASE o banco' => 'Sí (INASE)'
            ],
            [
                'Nombre' => 'Antártida Reg',
                'Banco/Proveedor' => 'El Pampa Seeds',
                'Tipo de semilla' => 'Regular',
                'Tiempo de flora (semanas)' => '8-10',
                'THC (%)' => 'Alto',
                'CBD (%)' => '<1',
                'Registro INASE o banco' => 'No especificado'
            ],
            [
                'Nombre' => 'Bemba',
                'Banco/Proveedor' => 'Sindicato del Kush',
                'Tipo de semilla' => 'Feminizada fotoperiódica',
                'Tiempo de flora (semanas)' => '8-10',
                'THC (%)' => 'Alto',
                'CBD (%)' => 'Bajo',
                'Registro INASE o banco' => 'No especificado'
            ],
            [
                'Nombre' => 'Bengala',
                'Banco/Proveedor' => 'No especificado',
                'Tipo de semilla' => 'Feminizada fotoperiódica',
                'Tiempo de flora (semanas)' => 'No especificado',
                'THC (%)' => '16-20',
                'CBD (%)' => '0.1-0.3',
                'Registro INASE o banco' => 'No especificado'
            ],
            [
                'Nombre' => 'Biscuit Cream',
                'Banco/Proveedor' => 'No especificado',
                'Tipo de semilla' => 'Feminizada fotoperiódica',
                'Tiempo de flora (semanas)' => '8-9',
                'THC (%)' => '20',
                'CBD (%)' => '0.2',
                'Registro INASE o banco' => 'No especificado'
            ],
            [
                'Nombre' => 'Bonavena',
                'Banco/Proveedor' => 'No especificado',
                'Tipo de semilla' => 'Feminizada fotoperiódica',
                'Tiempo de flora (semanas)' => '10-11',
                'THC (%)' => 'Alto',
                'CBD (%)' => '<1',
                'Registro INASE o banco' => 'No especificado'
            ],
            [
                'Nombre' => 'Lemon Ram',
                'Banco/Proveedor' => 'Silver Siver Seeds',
                'Tipo de semilla' => 'Feminizada fotoperiódica',
                'Tiempo de flora (semanas)' => '8',
                'THC (%)' => '26-28',
                'CBD (%)' => '0.6',
                'Registro INASE o banco' => 'No especificado'
            ],
            [
                'Nombre' => 'Fancy Gummy',
                'Banco/Proveedor' => 'R-Kiem Seeds',
                'Tipo de semilla' => 'Feminizada fotoperiódica',
                'Tiempo de flora (semanas)' => '8-9',
                'THC (%)' => '26',
                'CBD (%)' => '<1',
                'Registro INASE o banco' => 'Sí (INASE)'
            ],
            [
                'Nombre' => 'Magnum Cookies',
                'Banco/Proveedor' => 'No especificado',
                'Tipo de semilla' => 'Feminizada fotoperiódica',
                'Tiempo de flora (semanas)' => '12',
                'THC (%)' => '20',
                'CBD (%)' => '1.4',
                'Registro INASE o banco' => 'No especificado'
            ],
            [
                'Nombre' => 'CBD Kush',
                'Banco/Proveedor' => 'Dutch Passion',
                'Tipo de semilla' => 'Feminizada fotoperiódica',
                'Tiempo de flora (semanas)' => 'No especificado',
                'THC (%)' => '8',
                'CBD (%)' => '8',
                'Registro INASE o banco' => 'No especificado'
            ],
            [
                'Nombre' => 'Auto CBG-Force',
                'Banco/Proveedor' => 'No especificado',
                'Tipo de semilla' => 'Automática',
                'Tiempo de flora (semanas)' => 'No especificado',
                'THC (%)' => '<0.2',
                'CBD (%)' => '10-15% CBG',
                'Registro INASE o banco' => 'No especificado'
            ],
            [
                'Nombre' => 'River OG',
                'Banco/Proveedor' => 'No especificado',
                'Tipo de semilla' => 'Feminizada fotoperiódica',
                'Tiempo de flora (semanas)' => '8',
                'THC (%)' => '29',
                'CBD (%)' => '0.5',
                'Registro INASE o banco' => 'No especificado'
            ],
            [
                'Nombre' => 'Auto Girl Scout Cookies',
                'Banco/Proveedor' => 'No especificado',
                'Tipo de semilla' => 'Automática',
                'Tiempo de flora (semanas)' => 'No especificado',
                'THC (%)' => '16-20',
                'CBD (%)' => '<1',
                'Registro INASE o banco' => 'No especificado'
            ],
            [
                'Nombre' => 'Auto Ice Cream',
                'Banco/Proveedor' => 'No especificado',
                'Tipo de semilla' => 'Automática',
                'Tiempo de flora (semanas)' => 'No especificado',
                'THC (%)' => '18-22',
                'CBD (%)' => '<1',
                'Registro INASE o banco' => 'No especificado'
            ],
            [
                'Nombre' => 'Ultra Mohan Ram',
                'Banco/Proveedor' => 'No especificado',
                'Tipo de semilla' => 'Automática',
                'Tiempo de flora (semanas)' => '8.5',
                'THC (%)' => '22',
                'CBD (%)' => '0.8',
                'Registro INASE o banco' => 'No especificado'
            ],
            [
                'Nombre' => 'Crystal Med CBD Auto',
                'Banco/Proveedor' => 'No especificado',
                'Tipo de semilla' => 'Automática',
                'Tiempo de flora (semanas)' => '9.5',
                'THC (%)' => '1.2-2.5',
                'CBD (%)' => '8.5-14',
                'Registro INASE o banco' => 'No especificado'
            ],
            [
                'Nombre' => 'Flash',
                'Banco/Proveedor' => 'No especificado',
                'Tipo de semilla' => 'Feminizada fotoperiódica',
                'Tiempo de flora (semanas)' => '8.5',
                'THC (%)' => '20',
                'CBD (%)' => '0.9',
                'Registro INASE o banco' => 'No especificado'
            ],
            [
                'Nombre' => 'Red Hot Cookies',
                'Banco/Proveedor' => 'No especificado',
                'Tipo de semilla' => 'Feminizada fotoperiódica',
                'Tiempo de flora (semanas)' => '8',
                'THC (%)' => '17-25',
                'CBD (%)' => '0.1',
                'Registro INASE o banco' => 'No especificado'
            ],
            [
                'Nombre' => 'Onora',
                'Banco/Proveedor' => 'No especificado',
                'Tipo de semilla' => 'Feminizada fotoperiódica',
                'Tiempo de flora (semanas)' => 'No especificado',
                'THC (%)' => '19.3',
                'CBD (%)' => '0.6',
                'Registro INASE o banco' => 'No especificado'
            ],
            [
                'Nombre' => 'Douce Nuit Fem',
                'Banco/Proveedor' => 'French Touch Seeds',
                'Tipo de semilla' => 'Feminizada fotoperiódica',
                'Tiempo de flora (semanas)' => '8.5',
                'THC (%)' => '30',
                'CBD (%)' => '0',
                'Registro INASE o banco' => 'No especificado'
            ],
            [
                'Nombre' => 'Island Sweet Skunk Fem',
                'Banco/Proveedor' => 'Mariseeds Seleccion',
                'Tipo de semilla' => 'Feminizada fotoperiódica',
                'Tiempo de flora (semanas)' => '10',
                'THC (%)' => '24',
                'CBD (%)' => '0.8',
                'Registro INASE o banco' => 'No especificado'
            ],
            [
                'Nombre' => 'Delicious Cookies Fem',
                'Banco/Proveedor' => 'Delicious Seeds',
                'Tipo de semilla' => 'Feminizada fotoperiódica',
                'Tiempo de flora (semanas)' => '8-9',
                'THC (%)' => '24',
                'CBD (%)' => '<1',
                'Registro INASE o banco' => 'No especificado'
            ],
            [
                'Nombre' => 'Big Rocket Fem',
                'Banco/Proveedor' => 'Mariseeds Seleccion',
                'Tipo de semilla' => 'Feminizada fotoperiódica',
                'Tiempo de flora (semanas)' => '9',
                'THC (%)' => '22',
                'CBD (%)' => '0.8',
                'Registro INASE o banco' => 'No especificado'
            ],
            [
                'Nombre' => 'King’s Juice Auto',
                'Banco/Proveedor' => 'Green House Seeds',
                'Tipo de semilla' => 'Automática',
                'Tiempo de flora (semanas)' => '10',
                'THC (%)' => '22',
                'CBD (%)' => '0.7',
                'Registro INASE o banco' => 'No especificado'
            ],
            [
                'Nombre' => 'Gorilla XL Auto',
                'Banco/Proveedor' => 'Mariseeds Seleccion',
                'Tipo de semilla' => 'Automática',
                'Tiempo de flora (semanas)' => '10.5',
                'THC (%)' => '24',
                'CBD (%)' => '0.1',
                'Registro INASE o banco' => 'No especificado'
            ],
        ];

        $seedTypeMapping = [
            'Feminizada fotoperiódica' => 'Fotoperiodica feminizada',
            'Regular' => 'Fotoperiodica regular',
            'Automática' => 'Automatica',
        ];

        foreach ($csvRows as $row) {

            $seedType = $seedTypeMapping[$row['Tipo de semilla']] ?? $row['Tipo de semilla'];

            DB::table('seeds')->insert([
                'name' => $row['Nombre'],
                'tenant_id' => 1,
                'seed_type' => $seedType,
                'flowering_time' => $row['Tiempo de flora (semanas)'],
                'ratio_thc' => $row['THC (%)'],
                'ratio_cbd' => $row['CBD (%)'],
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ]);
        }
    }
}
