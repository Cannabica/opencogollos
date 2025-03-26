<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class SuperAdminSeeder extends Seeder
{
    public function run(): void
    {
        $adminEmail = env('ADMIN_EMAIL', 'frankie@cannabica.app');
        $adminPassword = env('ADMIN_PASSWORD');

        if (!$adminPassword) {
            throw new \RuntimeException('ADMIN_PASSWORD must be set in environment variables');
        }

        // Crear o actualizar el superadmin
        User::updateOrCreate(
            ['email' => $adminEmail],
            [
                'name' => 'Frankie',
                'password' => Hash::make($adminPassword),
                'email_verified_at' => now(),
                'tenant_id' => null,
            ]
        );
    }
} 