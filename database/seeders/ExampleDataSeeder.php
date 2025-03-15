<?php

namespace Database\Seeders;

use App\Models\Tenant;
use App\Models\User;
use App\Models\Indoor;
use App\Models\Scopes\TenantScope;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;

class ExampleDataSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('Iniciando ExampleDataSeeder...');

        // Crear tenant
        $tenant = Tenant::withoutGlobalScope(TenantScope::class)->create([
            'id' => 1,
            'name' => 'Tenant Example',
            'email' => 'tenant@example.com',
            'active' => 1,
        ]);

        if (!$tenant) {
            $this->command->error('Error al crear el tenant');
            return;
        }
        $this->command->info('Tenant creado: ' . $tenant->name);

        // Crear usuario
        $user = User::withoutGlobalScope(TenantScope::class)->create([
            'name' => 'User Tenant',
            'email' => 'user@tenant.com',
            'tenant_id' => $tenant->id,
            'password' => Hash::make('password'),
        ]);

        if (!$user) {
            $this->command->error('Error al crear el usuario');
            return;
        }
        $this->command->info('Usuario creado: ' . $user->name);

        // Crear indoor
        // Crear tres indoors con diferentes configuraciones
        $indoorConfigs = [
            [
                'name' => 'Indoor Principal',
                'large' => 12.0,
                'width' => 8.0,
                'height' => 6.0,
                'fans' => [
                    ['inches' => 16],
                    ['inches' => 16],
                    ['inches' => 12]
                ],
                'lamps' => [
                    [
                        'power' => 100,
                        'technology' => 'led',
                        'coverage_area' => 25,
                    ],
                    [
                        'power' => 100,
                        'technology' => 'led',
                        'coverage_area' => 25,
                    ],
                ],
                'hygometer' => true,
                'humidifier' => true,
                'peak_quantity' => 150,
                'scheduled_time' => 6,
                'times_a_day' => 4,
                'scheduled_days' => ['lunes', 'martes', 'miércoles', 'jueves', 'viernes'],
            ],
            [
                'name' => 'Indoor Vegetativo',
                'large' => 8.0,
                'width' => 6.0,
                'height' => 4.0,
                'fans' => [
                    ['inches' => 12],
                    ['inches' => 12]
                ],
                'lamps' => [
                    [
                        'power' => 60,
                        'technology' => 'led',
                        'coverage_area' => 15,
                    ],
                ],
                'hygometer' => true,
                'humidifier' => true,
                'peak_quantity' => 80,
                'scheduled_time' => 4,
                'times_a_day' => 3,
                'scheduled_days' => ['lunes', 'miércoles', 'viernes', 'domingo'],
            ],
            [
                'name' => 'Indoor Experimental',
                'large' => 6.0,
                'width' => 4.0,
                'height' => 3.0,
                'fans' => [
                    ['inches' => 8],
                    ['inches' => 8]
                ],
                'lamps' => [
                    [
                        'power' => 40,
                        'technology' => 'sodio',
                        'coverage_area' => 10,
                    ],
                ],
                'hygometer' => false,
                'humidifier' => false,
                'peak_quantity' => 40,
                'scheduled_time' => 3,
                'times_a_day' => 2,
                'scheduled_days' => ['martes', 'jueves', 'sábado'],
            ],
        ];

        foreach ($indoorConfigs as $config) {
            $indoor = Indoor::withoutGlobalScope(TenantScope::class)->create(array_merge(
                $config,
                ['tenant_id' => $tenant->id]
            ));

            if (!$indoor) {
                $this->command->error('Error al crear el indoor: ' . $config['name']);
                return;
            }
            $this->command->info('Indoor creado: ' . $indoor->name);
        }

        // Verificar que todo se creó correctamente
        $this->command->info('Verificando datos creados...');
        $this->command->info("- Tenant ID {$tenant->id}: " . Tenant::withoutGlobalScope(TenantScope::class)->find($tenant->id)?->name ?? 'No encontrado');
        $indoors = Indoor::withoutGlobalScope(TenantScope::class)
            ->where('tenant_id', $tenant->id)
            ->get();
        $this->command->info("- Indoors creados: " . $indoors->pluck('name')->implode(', '));

        if ($indoors->isEmpty()) {
            $this->command->error('¡Advertencia! No se pueden encontrar los indoors creados. Esto podría afectar a los siguientes seeders.');
        }

    }
}
