<?php

namespace Tests\Feature;

use App\Livewire\ActionShortcuts;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Mail\Markdown;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Parametrización de marca (épica open-core, WS4 · T4.1/T4.2).
 *
 * Regla del producto: con config('platform.*') en null NO se renderiza ningún
 * dato de una instalación concreta (marca, status, comunidad, logo de email).
 * Con las vars seteadas, la UX vuelve a ser la de siempre.
 *
 * Los tests usan una marca ficticia ("MiMarca") a propósito: el repo no debe
 * clavar el nombre/URLs de ninguna instalación.
 */
class PlatformBrandingTest extends TestCase
{
    use RefreshDatabase;

    private function tenantUser(bool $tenantActive = true): User
    {
        $tenant = Tenant::factory()->state(['active' => $tenantActive])->create();

        return User::factory()->create(['tenant_id' => $tenant->id]);
    }

    private function configureBrand(): void
    {
        config([
            'platform.brand_name' => 'MiMarca',
            'platform.site_url' => 'https://mimarca.ar',
            'platform.status_page_url' => 'https://status.mimarca.ar',
            'platform.community.discord_url' => 'https://discord.gg/mimarca',
            'platform.community.feedback_url' => 'https://mimarca.ar/feedback',
        ]);
    }

    private function renderMailHeader(): string
    {
        return (string) app(Markdown::class)->render('mail::header', ['url' => 'http://localhost']);
    }

    public function test_registration_sin_config_de_plataforma_no_muestra_footer_de_marca(): void
    {
        $html = $this->get('/tenant/register')->assertOk()->getContent();

        $this->assertStringNotContainsString('Monitor de estado', $html);
        $this->assertStringNotContainsString('Todos los derechos reservados', $html);
    }

    public function test_registration_con_config_de_plataforma_muestra_footer_de_marca(): void
    {
        $this->configureBrand();

        $html = $this->get('/tenant/register')->assertOk()->getContent();

        $this->assertStringContainsString('https://status.mimarca.ar', $html);
        $this->assertStringContainsString('status.mimarca.ar', $html);
        $this->assertStringContainsString('MiMarca. Todos los derechos reservados', $html);
    }

    public function test_activation_pending_sin_config_de_plataforma_no_muestra_links_de_marca(): void
    {
        $html = $this->actingAs($this->tenantUser(tenantActive: false))
            ->get('/tenant/activation-pending')
            ->assertOk()
            ->getContent();

        $this->assertStringNotContainsString('Monitor de estado', $html);
        $this->assertStringNotContainsString('server de discord', $html);
        $this->assertStringNotContainsString('Reportar un bug', $html);
        $this->assertStringContainsString('cuando esté lista.', $html);
    }

    public function test_activation_pending_con_config_de_plataforma_muestra_links_configurados(): void
    {
        $this->configureBrand();

        $html = $this->actingAs($this->tenantUser(tenantActive: false))
            ->get('/tenant/activation-pending')
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('https://status.mimarca.ar', $html);
        $this->assertStringContainsString('https://discord.gg/mimarca', $html);
        $this->assertStringContainsString('https://mimarca.ar/feedback', $html);
        $this->assertStringContainsString('Monitor de estado de plataforma MiMarca', $html);
        $this->assertStringContainsString('sumate al servidor de discord', $html);
    }

    public function test_mail_header_sin_config_no_muestra_logo_de_marca(): void
    {
        $html = $this->renderMailHeader();

        $this->assertStringNotContainsString('<img', $html);
        $this->assertStringContainsString(config('app.name'), $html);
    }

    public function test_mail_header_con_config_muestra_logo_configurado(): void
    {
        config([
            'platform.brand_name' => 'MiMarca',
            'platform.mail_header_logo' => '/images/mi-logo.png',
        ]);

        $html = $this->renderMailHeader();

        $this->assertStringContainsString(url('/images/mi-logo.png'), $html);
        $this->assertStringContainsString('alt="MiMarca"', $html);
    }

    public function test_action_shortcuts_sin_comunidad_configurada_no_renderiza_nada(): void
    {
        Livewire::test(ActionShortcuts::class)
            ->assertDontSee('Reportar un bug')
            ->assertDontSee('Sumate a discord');
    }

    public function test_action_shortcuts_con_comunidad_usa_las_urls_configuradas(): void
    {
        $this->configureBrand();

        $html = Livewire::test(ActionShortcuts::class)->html();

        $this->assertStringContainsString('https://mimarca.ar/feedback', $html);
        $this->assertStringContainsString('https://discord.gg/mimarca', $html);
    }

    public function test_dashboard_sin_config_no_muestra_invitacion_a_la_comunidad(): void
    {
        $html = $this->actingAs($this->tenantUser())->get('/tenant')->assertOk()->getContent();

        $this->assertStringNotContainsString('proyecto comunitario', $html);
        $this->assertStringNotContainsString('mandalo acá', $html);
    }

    public function test_dashboard_con_config_muestra_invitacion_a_la_comunidad(): void
    {
        $this->configureBrand();

        $html = $this->actingAs($this->tenantUser())->get('/tenant')->assertOk()->getContent();

        $this->assertStringContainsString('proyecto comunitario', $html);
        $this->assertStringContainsString('https://mimarca.ar', $html);
        $this->assertStringContainsString('https://mimarca.ar/feedback', $html);
    }
}
