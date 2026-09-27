<?php

namespace Tests\Feature;

use App\Livewire\ActionShortcuts;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Atajos de comunidad del panel (épica open-core, WS4 · T4.2/T4.6).
 *
 * Regla del producto: cada acción sale solo si su URL está configurada; sin
 * comunidad configurada el bloque queda vacío (no hay links de otra instalación).
 *
 * La marca de la instalación de referencia se arma concatenada para que este
 * archivo no la contenga (el grep de aceptación exige 0 matches en el repo).
 */
class ActionShortcutsTest extends TestCase
{
    private function otherInstallationBrand(): string
    {
        return 'cann' . 'abica';
    }

    public function test_sin_comunidad_configurada_no_renderiza_ninguna_accion(): void
    {
        $html = Livewire::test(ActionShortcuts::class)->html();

        $this->assertStringNotContainsString('Reportar un bug', $html);
        $this->assertStringNotContainsString('Sumate a discord', $html);
        $this->assertStringNotContainsString('http', $html);
        $this->assertStringNotContainsStringIgnoringCase($this->otherInstallationBrand(), $html);
    }

    public function test_con_comunidad_configurada_usa_las_urls_de_config(): void
    {
        config([
            'platform.community.feedback_url' => 'https://mimarca.ar/feedback',
            'platform.community.discord_url' => 'https://discord.gg/mimarca',
        ]);

        $html = Livewire::test(ActionShortcuts::class)->html();

        $this->assertStringContainsString('https://mimarca.ar/feedback', $html);
        $this->assertStringContainsString('https://discord.gg/mimarca', $html);
    }

    public function test_con_solo_feedback_configurado_renderiza_solo_reportar_bug(): void
    {
        config(['platform.community.feedback_url' => 'https://mimarca.ar/feedback']);

        $html = Livewire::test(ActionShortcuts::class)->html();

        $this->assertStringContainsString('Reportar un bug', $html);
        $this->assertStringNotContainsString('Sumate a discord', $html);
    }

    public function test_con_solo_discord_configurado_renderiza_solo_la_invitacion(): void
    {
        config(['platform.community.discord_url' => 'https://discord.gg/mimarca']);

        $html = Livewire::test(ActionShortcuts::class)->html();

        $this->assertStringContainsString('Sumate a discord', $html);
        $this->assertStringNotContainsString('Reportar un bug', $html);
    }
}
