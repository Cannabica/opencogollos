<?php

namespace Tests\Feature;

use App\Notifications\RegistrationConfirmationNotification;
use App\Notifications\TeamUserActivationNotification;
use App\Notifications\TenantActivationNotification;
use Tests\TestCase;

/**
 * Líneas condicionales de los emails de activación (épica open-core, WS4 · T4.3).
 *
 * Regla: con config('platform.*') en null, ningún email menciona el monitor de
 * estado ni la comunidad de otra instalación. Con las vars seteadas, el texto
 * vuelve a ser el de siempre.
 */
class StatusPageLineTest extends TestCase
{
    private function notifiable(): object
    {
        return new class
        {
            public string $name = 'Tester';

            public string $email = 'tester@example.com';

            public object $tenant;

            public function __construct()
            {
                $this->tenant = (object) ['name' => 'MiTenant'];
            }
        };
    }

    private function render(object $notification): string
    {
        return (string) $notification->toMail($this->notifiable())->render();
    }

    public function test_team_activation_sin_config_no_menciona_monitor(): void
    {
        $html = $this->render(new TeamUserActivationNotification('secreta'));

        $this->assertStringNotContainsString('estado del sistema', $html);
        $this->assertStringNotContainsString('https://', $html);
    }

    public function test_team_activation_con_config_muestra_monitor(): void
    {
        config(['platform.status_page_url' => 'https://status.mimarca.ar/']);

        $html = $this->render(new TeamUserActivationNotification('secreta'));

        $this->assertStringContainsString('estado del sistema', $html);
        $this->assertStringContainsString('status.mimarca.ar', $html);
    }

    public function test_tenant_activation_sin_config_no_menciona_monitor(): void
    {
        $html = $this->render(new TenantActivationNotification('MiTenant'));

        $this->assertStringNotContainsString('Monitor del Sistema', $html);
        $this->assertStringNotContainsString('https://', $html);
    }

    public function test_tenant_activation_con_config_muestra_monitor(): void
    {
        config(['platform.status_page_url' => 'https://status.mimarca.ar']);

        $html = $this->render(new TenantActivationNotification('MiTenant'));

        $this->assertStringContainsString('Monitor del Sistema', $html);
        $this->assertStringContainsString('status.mimarca.ar', $html);
    }

    public function test_registration_confirmation_sin_config_no_invita_a_discord(): void
    {
        $html = $this->render(new RegistrationConfirmationNotification('MiTenant'));

        $this->assertStringNotContainsString('discord', $html);
        $this->assertStringContainsString('cuando esté lista.', $html);
    }

    public function test_registration_confirmation_con_config_invita_a_discord(): void
    {
        config(['platform.community.discord_url' => 'https://discord.gg/mimarca']);

        $html = $this->render(new RegistrationConfirmationNotification('MiTenant'));

        $this->assertStringContainsString('sumate al servidor de discord', $html);
        $this->assertStringContainsString('cuando esté lista,', $html);
    }
}
