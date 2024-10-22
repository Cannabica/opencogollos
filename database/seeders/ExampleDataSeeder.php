<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;

class ExampleDataSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Insertar en la tabla Tenants
        DB::table('tenants')->insert([
            'id' => 1,
            'name' => 'Tenant Example',
            'email' => 'tenant@example.com',
            'active' => 1,
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ]);

        // Insertar en la tabla Users
        DB::table('users')->insert([
            'name' => 'User Tenant',
            'email' => 'user@tenant.com',
            'tenant_id' => 1,
            'password' => Hash::make('password'), // Hashea la contraseña
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ]);

        //Insertar en la tabla Indoors
        DB::table('indoors')->insert([
            'name' => 'Indoor Example',
            'large' => 10.5,
            'width' => 8.2,
            'height' => 6.0,
            'tenant_id' => 1,
            'fans' => json_encode([
                ['inches' => 12],
                ['inches' => 16]
            ]),
            'lamps' => json_encode([
                [
                    'power' => 50,
                    'technology' => 'led',
                    'coverage_area' => 20,
                ],
                [
                    'power' => 70,
                    'technology' => 'sodio',
                    'coverage_area' => 30,
                ],
            ]),
            'hygometer' => true,
            'humidifier' => false,
            'peak_quantity' => 100,
            'scheduled_time' => 4,
            'times_a_day' => 3,
            'scheduled_days' => json_encode(['lunes', 'miércoles', 'viernes']),
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ]);

        //Insertar en la tabla Seeds
        DB::table('seeds')->insert([
            [
                'name' => 'Seed Example 1',
                'tenant_id' => 1, 
                'seed_type' => 'Fotoperiodica feminizada',
                'flowering_time' => 10,
                'ratio_ths' => 18,
                'ratio_cbd' => 2,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'name' => 'Seed Example 2',
                'tenant_id' => 1, 
                'seed_type' => 'Automatica',
                'flowering_time' => 8,
                'ratio_ths' => 15,
                'ratio_cbd' => 5,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'name' => 'Seed Example 3',
                'tenant_id' => 1, 
                'seed_type' => 'Fotoperiodica regular',
                'flowering_time' => 12,
                'ratio_ths' => 20,
                'ratio_cbd' => 1,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
        ]);

    }
}
