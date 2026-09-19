<?php

namespace App\Providers;

use Telegram\Bot\BotsManager;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Log;

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

        $this->app->booted(function () {
            if (app()->runningInConsole()) {
                Log::info('Skipping Telegram webhook registration on boot - running in console or already registered');
                return;
                try {
                    Log::info('Registering Telegram webhook on boot');
    
                    cache()->put('telegram_webhook_registered', true, now()->addDay());
                    $token = env('TELEGRAM_BOT_TOKEN');
                    $appUrl = env('APP_URL');
    
                    if (empty($token) || empty($appUrl)) {
                        \Log::warning('Telegram webhook registration skipped - Missing required environment variables');
                        return;
                    }
    
                    $webhookUrl = rtrim($appUrl, '/') . '/api/telegram/webhook';
    
                    $this->app[BotsManager::class]->bot()->setWebhook([
                        'url' => $webhookUrl,
                        'max_connections' => 40,
                        'drop_pending_updates' => true
                    ]);
                } catch (\Exception $e) {
                    \Log::error('Telegram webhook registration failed: ' . $e->getMessage());
                }
            }
        });
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