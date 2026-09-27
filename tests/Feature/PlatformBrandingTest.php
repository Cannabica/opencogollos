<?php

namespace Tests\Feature;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Mail\Markdown;
use Tests\TestCase;

/**
 * Parametrización de marca (épica open-core, WS4 · T4.1/T4.2/T4.6).
 *
 * Regla del producto: con config('platform.*') en null NO se renderiza ningún
 * dato de una instalación concreta (marca, status, comunidad, logo de email,
 * tutoriales). Con las vars seteadas, la UX vuelve a ser la de siempre.
 *
 * El footer de registro vive en RegistrationFooterTest y los atajos de comunidad
 * en ActionShortcutsTest; los tests usan una marca ficticia ("MiMarca") a
 * propósito: el repo no debe clavar el nombre/URLs de ninguna instalación.
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
            'platform.platform_url' => 'https://plataforma.mimarca.ar',
            'platform.telegram_bot_username' => 'mimarca_bot',
            'platform.community.discord_url' => 'https://discord.gg/mimarca',
            'platform.community.feedback_url' => 'https://mimarca.ar/feedback',
        ]);
    }

    private function renderMailHeader(): string
    {
        return (string) app(Markdown::class)->render('mail::header', ['url' => 'http://localhost']);
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

    public function test_tutorial_telegram_sin_config_no_menciona_plataforma_ni_bot_de_una_instalacion(): void
    {
        $html = $this->actingAs($this->tenantUser())
            ->get('/tenant/tutorials/telegram-bot')
            ->assertOk()
            ->getContent();

        $this->assertStringNotContainsString('t.me', $html);
        $this->assertStringNotContainsString('{{ plataforma }}', $html);
        $this->assertStringNotContainsString('{{ bot }}', $html);
        $this->assertStringContainsString('Ingresa a tu cuenta en la plataforma', $html);
        $this->assertStringContainsString('busca el bot de tu instalación en Telegram', $html);
    }

    public function test_tutorial_telegram_con_config_muestra_la_plataforma_y_el_bot_configurados(): void
    {
        $this->configureBrand();

        $html = $this->actingAs($this->tenantUser())
            ->get('/tenant/tutorials/telegram-bot')
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('https://plataforma.mimarca.ar', $html);
        $this->assertStringContainsString('https://t.me/mimarca_bot', $html);
        $this->assertStringContainsString('busca nuestro bot oficial en Telegram', $html);
    }
}
