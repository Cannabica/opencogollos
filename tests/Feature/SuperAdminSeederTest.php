<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\SuperAdminSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

/**
 * Alta de superadmin parametrizada (épica open-core, WS4 · T4.5).
 *
 * El email del superadmin sale de config('platform.admin_email'). Sin configurar,
 * el seeder avisa por log y NO crea un admin con el email de otra instalación
 * (el seed no se rompe).
 */
class SuperAdminSeederTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // El seeder sigue exigiendo la password por entorno.
        putenv('ADMIN_PASSWORD=secreto-de-test');
        $_ENV['ADMIN_PASSWORD'] = 'secreto-de-test';
        $_SERVER['ADMIN_PASSWORD'] = 'secreto-de-test';
    }

    protected function tearDown(): void
    {
        putenv('ADMIN_PASSWORD');
        unset($_ENV['ADMIN_PASSWORD'], $_SERVER['ADMIN_PASSWORD']);

        parent::tearDown();
    }

    public function test_sin_admin_email_configurado_no_crea_superadmin_y_avisa(): void
    {
        config(['platform.admin_email' => null]);
        Log::spy();

        (new SuperAdminSeeder())->run();

        $this->assertSame(0, User::withoutGlobalScopes()->whereNull('tenant_id')->count());
        Log::shouldHaveReceived('warning')->once();
    }

    public function test_con_admin_email_configurado_crea_el_superadmin(): void
    {
        config(['platform.admin_email' => 'admin@mimarca.ar']);

        (new SuperAdminSeeder())->run();

        $admin = User::withoutGlobalScopes()->whereNull('tenant_id')->first();

        $this->assertNotNull($admin);
        $this->assertSame('admin@mimarca.ar', $admin->email);
    }
}
