<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * Default = demo FEACIENTE (W2, épica identidad visual): ActionTypesSeeder (tipos de
     * acción que necesita la demo) + RealisticDemoSeeder (autocontenido: 5 perfiles demo,
     * indoors, plantas, 16 semillas GLOBALES curadas, 4 crop plans globales, 50
     * notificaciones históricas con formato Filament).
     *
     * `php artisan migrate:fresh --seed` deja la demo completa sin pasos extra.
     *
     * Seeders viejos (ExampleDataSeeder/PlantSeeder/ActionSeeder con faker suelto,
     * SeedsSeeder del CSV sucio, CropPlanSeeder) quedaron fuera del default: producían
     * data absurda (plantas de 1991, floraciones de 83 semanas) y el contenido que
     * cubrían (semillas/planes globales) ahora vive en RealisticDemoSeeder. Borrarlos o
     * deprecarlos es decisión pendiente.
     *
     * SuperAdminSeeder NO corre acá: exige ADMIN_EMAIL/ADMIN_PASSWORD del entorno (el
     * deploy lo corre con sus env). Local: crear el superadmin a mano o exportar las
     * variables antes de `db:seed --class=SuperAdminSeeder`.
     */
    public function run(): void
    {
        $this->call(ActionTypesSeeder::class);
        $this->call(RealisticDemoSeeder::class);
    }
}
