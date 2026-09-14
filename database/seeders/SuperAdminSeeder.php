<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

class SuperAdminSeeder extends Seeder
{
    public function run(): void
    {
        // El email del superadmin sale de config('platform.admin_email')
        // (PLATFORM_ADMIN_EMAIL). Si no está, cae al bootstrap ADMIN_EMAIL
        // (config('platform.admin_email_fallback')): redundancia barata en el único
        // punto que, si falta, te deja SIN acceso a /superadmin en un recovery
        // (decisión C3 de WS9/T9.5). Sin ninguno de los dos NO se crea un admin con
        // el email de una instalación ajena: se avisa y el seed sigue sin romper.
        $adminEmail = config('platform.admin_email') ?: config('platform.admin_email_fallback');

        if (blank($adminEmail)) {
            Log::warning('SuperAdminSeeder: ni platform.admin_email (PLATFORM_ADMIN_EMAIL) ni ADMIN_EMAIL están configurados — no se crea el superadmin.');

            return;
        }

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
            // existente no se pisa aunque platform.admin_email cambie).
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