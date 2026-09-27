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
 * El email del superadmin sale de config('platform.admin_email'). Si no está, cae
 * al bootstrap `platform.admin_email_fallback` (ADMIN_EMAIL). Sin ninguno de los dos
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

    public function test_sin_ningun_email_configurado_no_crea_superadmin_y_avisa(): void
    {
        config(['platform.admin_email' => null, 'platform.admin_email_fallback' => null]);
        $log = Log::spy();

        (new SuperAdminSeeder())->run();

        $this->assertSame(0, User::withoutGlobalScopes()->whereNull('tenant_id')->count());
        $log->shouldHaveReceived('warning')->once();
    }

    public function test_sin_platform_admin_email_cae_al_bootstrap_admin_email(): void
    {
        // C3 (WS9/T9.5): si PLATFORM_ADMIN_EMAIL no está, el recovery usa ADMIN_EMAIL
        // en vez de quedarse sin superadmin (y por lo tanto sin acceso a /superadmin).
        config(['platform.admin_email' => null, 'platform.admin_email_fallback' => 'bootstrap@mimarca.ar']);

        (new SuperAdminSeeder())->run();

        $admin = User::withoutGlobalScopes()->whereNull('tenant_id')->first();

        $this->assertNotNull($admin);
        $this->assertSame('bootstrap@mimarca.ar', $admin->email);
    }

    public function test_platform_admin_email_gana_sobre_el_fallback(): void
    {
        config(['platform.admin_email' => 'admin@mimarca.ar', 'platform.admin_email_fallback' => 'bootstrap@mimarca.ar']);

        (new SuperAdminSeeder())->run();

        $admin = User::withoutGlobalScopes()->whereNull('tenant_id')->first();

        $this->assertNotNull($admin);
        $this->assertSame('admin@mimarca.ar', $admin->email);
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
