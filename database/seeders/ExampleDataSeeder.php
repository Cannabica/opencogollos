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

        // Insertar en la tabla Indoors
        // DB::table('indoors')->insert([
        //     'name' => 'Indoor Example',
        //     'tenant_id' => 1,
        //     'created_at' => Carbon::now(),
        //     'updated_at' => Carbon::now(),
        // ]);
    }
}
