<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class TelegramServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__ . '/../../config/telegram.php',
            'telegram'
        );

        // T2.6 (2026-09-19): aca vivian 3 bindings (singleton BotsManager -> App\Services\TelegramBotManager,
        // alias 'telegram' y bind 'telegram.bot') que quedaban SOMBREADOS: el provider del SDK se registra
        // despues y gana, asi que nunca resolvieron a lo de la app (verificado: `app('telegram')` =
        // Telegram\Bot\BotsManager). Y la clase que instanciaban heredaba de BotsManager, que el SDK 3.16
        // declara `final` => no cargaba, era un landmine. Se borro la clase + los bindings + la
        // `registerBindings()` privada (nunca llamada) con la decision A de Frankie (2026-09-19).
        // El binding de `telegram`/BotsManager lo sigue aportando el provider del SDK.
    }

    public function boot(): void
    {
        $this->publishes([
            __DIR__ . '/../../config/telegram.php' => config_path('telegram.php'),
        ], 'config');

        // Register Telegram commands
        $this->registerCommands();

        // T2.6 (2026-09-19): aca vivia un `$this->app->booted(...)` con el auto-registro del webhook
        // que NUNCA podia ejecutarse: el `return` temprano de `runningInConsole()` dejaba el try/catch
        // inalcanzable, y en un request web la closure no hacia nada. Ademas ese bloque usaba `env()`
        // fuera de config/ (rompe `config:cache`). El webhook se registra a proposito con
        // `php artisan telegram:webhook:setup` (ver docs/TELEGRAM.md), no al bootear.
    }

    /**
     * Register the Artisan commands.
     */
    private function registerCommands(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                \App\Console\Commands\Telegram\CommandsListCommand::class,
                \App\Console\Commands\Telegram\WebhookSetupCommand::class,
                \Telegram\Bot\Laravel\Artisan\WebhookCommand::class,
            ]);
        }
    }
}