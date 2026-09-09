<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class SuperAdminSeeder extends Seeder
{
    public function run(): void
    {
        $adminEmail = env('ADMIN_EMAIL', 'administrator@cannabica.app');
        $adminPassword = env('ADMIN_PASSWORD');

        if (!$adminPassword) {
            throw new \RuntimeException('ADMIN_PASSWORD must be set in environment variables');
        }

        // OJO: el email del User está ENCRIPTADO (cast del modelo) → un updateOrCreate por
        // email plano nunca matchea el registro existente y DUPLICA el admin en cada corrida.
        // El superadmin es el user sin tenant (tenant_id null): se busca por eso.
        $admin = User::withoutGlobalScopes()->whereNull('tenant_id')->first();

        if ($admin) {
            // Ya existe un superadmin: refrescar nombre y password (el email encriptado
            // existente no se pisa aunque ADMIN_EMAIL cambie).
            $admin->update([
                'name' => 'Administrator',
                'password' => Hash::make($adminPassword),
            ]);
        } else {
            User::create([
                'name' => 'Administrator',
                'email' => $adminEmail,
                'password' => Hash::make($adminPassword),
                'email_verified_at' => now(),
                'tenant_id' => null,
            ]);
        }
    }
} 