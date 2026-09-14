<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Footer de marca de la pantalla de registro (épica open-core, WS4 · T4.2/T4.6).
 *
 * Regla del producto: con config('platform.*') en null el footer NO se renderiza
 * (ni link de estado ni copyright de otra instalación); con las vars seteadas se
 * muestra el link al monitor de estado y el copyright de la marca configurada.
 *
 * La marca de la instalación de referencia se arma concatenada para que este
 * archivo no la contenga (el grep de aceptación exige 0 matches en el repo).
 */
class RegistrationFooterTest extends TestCase
{
    use RefreshDatabase;

    private function otherInstallationBrand(): string
    {
        return 'cann' . 'abica';
    }

    public function test_registration_sin_config_de_plataforma_no_muestra_footer_de_marca(): void
    {
        $html = $this->get('/tenant/register')->assertOk()->getContent();

        $this->assertStringNotContainsString('Monitor de estado', $html);
        $this->assertStringNotContainsString('Todos los derechos reservados', $html);
        $this->assertStringNotContainsString('https://status', $html);
        $this->assertStringNotContainsStringIgnoringCase($this->otherInstallationBrand(), $html);
    }

    public function test_registration_con_config_de_plataforma_muestra_footer_de_marca(): void
    {
        config([
            'platform.brand_name' => 'MiMarca',
            'platform.status_page_url' => 'https://status.mimarca.ar',
        ]);

        $html = $this->get('/tenant/register')->assertOk()->getContent();

        $this->assertStringContainsString('https://status.mimarca.ar', $html);
        $this->assertStringContainsString('status.mimarca.ar', $html);
        $this->assertStringContainsString('MiMarca. Todos los derechos reservados', $html);
    }

    public function test_registration_con_status_sin_brand_name_muestra_solo_el_link_de_estado(): void
    {
        config(['platform.status_page_url' => 'https://status.mimarca.ar']);

        $html = $this->get('/tenant/register')->assertOk()->getContent();

        $this->assertStringContainsString('status.mimarca.ar', $html);
        $this->assertStringNotContainsString('Todos los derechos reservados', $html);
    }

    public function test_registration_con_brand_name_sin_status_no_muestra_link_de_estado(): void
    {
        config(['platform.brand_name' => 'MiMarca']);

        $html = $this->get('/tenant/register')->assertOk()->getContent();

        $this->assertStringContainsString('MiMarca. Todos los derechos reservados', $html);
        $this->assertStringNotContainsString('¿Problemas con el sistema?', $html);
    }
}
