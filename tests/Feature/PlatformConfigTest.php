<?php

namespace Tests\Feature;

use Illuminate\Support\Arr;
use Tests\TestCase;

/**
 * Config de plataforma + identidad del producto (épica open-core, WS4 · T4.1/T4.6).
 *
 * Dos reglas del producto que este archivo vigila:
 *
 *  1. config('platform.*') sale SIEMPRE del entorno (`PLATFORM_*`) y su default
 *     es null: una instalación que clona el repo no hereda datos de otra.
 *  2. El nombre del producto es UNO solo y no es el de una instalación concreta:
 *     `app_name` ya no vive en el lang del producto (es.json) sino en
 *     config('app.name') (`APP_NAME`), y lo consumen los logos del panel, el
 *     manifest de la PWA y el header de los emails.
 *
 * El nombre de la instalación de referencia se arma concatenado para que este
 * archivo no lo contenga (el grep de aceptación exige 0 matches en el repo).
 */
class PlatformConfigTest extends TestCase
{
    /** Nombre del producto open core (decisión T0.1). */
    private const PRODUCT_NAME = 'OpenCogollos';

    /** key de config/platform.php => variable de entorno que la alimenta */
    private const PLATFORM_KEYS = [
        'brand_name' => 'PLATFORM_BRAND_NAME',
        'site_url' => 'PLATFORM_SITE_URL',
        'status_page_url' => 'PLATFORM_STATUS_PAGE_URL',
        'platform_url' => 'PLATFORM_PLATFORM_URL',
        'community.discord_url' => 'PLATFORM_DISCORD_URL',
        'community.feedback_url' => 'PLATFORM_FEEDBACK_URL',
        'admin_email' => 'PLATFORM_ADMIN_EMAIL',
        'telegram_bot_username' => 'PLATFORM_TELEGRAM_BOT_USERNAME',
        'mail_header_logo' => 'PLATFORM_MAIL_HEADER_LOGO',
    ];

    private function otherInstallationBrand(): string
    {
        return 'cann' . 'abica';
    }

    private function clearPlatformEnv(): void
    {
        foreach (self::PLATFORM_KEYS as $env) {
            putenv($env);
            unset($_ENV[$env], $_SERVER[$env]);
        }
    }

    /**
     * config/platform.php tal como lo ve una instalación recién clonada, sin
     * ninguna variable PLATFORM_* en el entorno.
     *
     * @return array<string, mixed>
     */
    private function configFileWithoutEnv(): array
    {
        $this->clearPlatformEnv();

        return require config_path('platform.php');
    }

    public function test_todas_las_keys_de_plataforma_existen(): void
    {
        $config = $this->configFileWithoutEnv();

        foreach (array_keys(self::PLATFORM_KEYS) as $key) {
            $this->assertTrue(Arr::has($config, $key), "Falta la key {$key} en config/platform.php");
        }
    }

    public function test_sin_variables_de_entorno_todas_las_keys_son_null(): void
    {
        $config = $this->configFileWithoutEnv();

        foreach (array_keys(self::PLATFORM_KEYS) as $key) {
            $this->assertNull(Arr::get($config, $key), "{$key} trae un default de una instalación concreta");
        }
    }

    public function test_las_keys_se_alimentan_del_entorno(): void
    {
        $this->clearPlatformEnv();
        putenv('PLATFORM_BRAND_NAME=MiMarca');
        $_ENV['PLATFORM_BRAND_NAME'] = 'MiMarca';

        $config = require config_path('platform.php');
        $this->assertSame('MiMarca', Arr::get($config, 'brand_name'));

        $this->clearPlatformEnv();
    }

    public function test_config_platform_no_declara_defaults_hardcodeados(): void
    {
        $source = (string) file_get_contents(config_path('platform.php'));

        // env('PLATFORM_X', 'valor') sería un default de una instalación concreta.
        $this->assertDoesNotMatchRegularExpression("/env\(\s*'PLATFORM_[A-Z_]+'\s*,/", $source);
    }

    public function test_el_nombre_del_producto_no_vive_en_el_lang(): void
    {
        $langPath = resource_path('lang/es.json');
        $this->assertFileExists($langPath);

        $lang = json_decode((string) file_get_contents($langPath), true);

        $this->assertIsArray($lang);
        $this->assertArrayNotHasKey('app_name', $lang);
        // Sin clave en el lang, el nombre sale siempre de config('app.name').
        $this->assertSame('app_name', __('app_name'));
    }

    public function test_el_default_del_repo_de_app_name_es_el_nombre_del_producto(): void
    {
        $envExample = (string) file_get_contents(base_path('.env.example'));

        $this->assertMatchesRegularExpression(
            '/^APP_NAME=' . preg_quote(self::PRODUCT_NAME, '/') . '$/m',
            $envExample,
            'El APP_NAME default del repo tiene que ser el nombre del producto',
        );
    }

    public function test_los_logos_del_panel_usan_la_key_unica_de_nombre(): void
    {
        // C4e (WS9/T9.5): el nombre visible es UNO solo. `platform.brand_name` manda y
        // `app.name` queda como fallback para la instalación que no configura marca.
        config(['platform.brand_name' => 'MiMarca', 'app.name' => 'OtroNombre']);

        foreach (['filament.admin.logo', 'filament.admin.logo-darkmode'] as $view) {
            $html = view($view)->render();

            $this->assertStringContainsString('MiMarca', $html);
            $this->assertStringNotContainsString('OtroNombre', $html);
            $this->assertStringNotContainsStringIgnoringCase($this->otherInstallationBrand(), $html);
        }
    }

    public function test_los_logos_del_panel_caen_al_nombre_de_la_app_sin_marca(): void
    {
        config(['platform.brand_name' => null, 'app.name' => 'NombreDelProducto']);

        foreach (['filament.admin.logo', 'filament.admin.logo-darkmode'] as $view) {
            $this->assertStringContainsString('NombreDelProducto', view($view)->render());
        }
    }

    public function test_el_manifest_pwa_usa_la_key_unica_de_nombre(): void
    {
        // C4e (WS9/T9.5): la PWA sigue la misma key de nombre visible que los logos.
        config(['platform.brand_name' => 'MiMarca', 'app.name' => 'OtroNombre']);

        $response = $this->get('/manifest.json')->assertOk();

        $this->assertSame('MiMarca', $response->json('name'));
        $this->assertSame('MiMarca', $response->json('short_name'));
        $this->assertNotEmpty($response->json('icons'));
    }

    public function test_el_manifest_pwa_cae_al_nombre_de_la_app_sin_marca(): void
    {
        config(['platform.brand_name' => null, 'app.name' => 'NombreDelProducto']);

        $response = $this->get('/manifest.json')->assertOk();

        $this->assertSame('NombreDelProducto', $response->json('name'));
        $this->assertSame('NombreDelProducto', $response->json('short_name'));
    }

    public function test_el_manifest_pwa_no_expone_una_instalacion_concreta(): void
    {
        $body = $this->get('/manifest.json')->assertOk()->getContent();

        $this->assertStringNotContainsStringIgnoringCase($this->otherInstallationBrand(), $body);
    }

    public function test_los_archivos_de_identidad_no_traen_marca_de_otra_instalacion(): void
    {
        $files = [
            'config/platform.php',
            'resources/lang/es.json',
            'resources/css/filament/tenant/theme.css',
            'resources/views/filament/admin/logo.blade.php',
            'resources/views/filament/admin/logo-darkmode.blade.php',
            'resources/markdown/tutorials/telegram-bot.md',
        ];

        foreach ($files as $file) {
            $path = base_path($file);
            $this->assertFileExists($path);

            $this->assertStringNotContainsStringIgnoringCase(
                $this->otherInstallationBrand(),
                (string) file_get_contents($path),
                "{$file} menciona la instalación de referencia",
            );
        }
    }

    /**
     * Las 9 keys tienen que estar documentadas en `.env.example`.
     *
     * Es el guardarraíl de la ventana automática: si una PLATFORM_* nueva se usa pero no se
     * documenta, ni el self-hoster ni el deploy de la instancia se enteran de que existe (en
     * producción la superficie simplemente desaparece, sin error).
     */
    public function test_las_keys_de_plataforma_estan_documentadas_en_env_example(): void
    {
        $envExample = (string) file_get_contents(base_path('.env.example'));

        foreach (self::PLATFORM_KEYS as $env) {
            $this->assertMatchesRegularExpression(
                '/^#?' . preg_quote($env, '/') . '=/m',
                $envExample,
                "{$env} no está en .env.example: una instalación nueva no se entera de que existe",
            );
        }
    }

    /**
     * El mapa de arriba y `config/platform.php` no pueden desincronizarse.
     *
     * Al agregar una `env('PLATFORM_*')` nueva hay que actualizar el mapa: eso obliga a
     * documentarla en `.env.example` (test de arriba) y a cargarla en el deploy de la instancia.
     */
    public function test_el_mapa_de_keys_cubre_todas_las_variables_del_config(): void
    {
        $source = (string) file_get_contents(config_path('platform.php'));

        preg_match_all('/env\(\s*\'(PLATFORM_[A-Z0-9_]+)\'/', $source, $matches);
        $enConfig = array_values(array_unique($matches[1]));
        $enMapa = array_values(self::PLATFORM_KEYS);
        sort($enConfig);
        sort($enMapa);

        $this->assertSame(
            $enMapa,
            $enConfig,
            'El mapa PLATFORM_KEYS y config/platform.php se desincronizaron: actualizá el mapa (y .env.example)',
        );
    }
}
