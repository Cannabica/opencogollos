<?php

namespace App\Providers;

use App\Services\TelegramBotManager;
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

        $this->app->singleton(BotsManager::class, function ($app) {
            return new TelegramBotManager(config('telegram'));
        });

        $this->app->alias(BotsManager::class, 'telegram');

        $this->app->bind('telegram.bot', function ($app) {
            return $app[BotsManager::class]->bot();
        });
    }
    private function registerBindings(): void
    {
        $this->app->singleton(BotsManager::class, static fn($app): BotsManager => (new TelegramBotManager(config('telegram')))->setContainer($app));
        $this->app->alias(BotsManager::class, 'telegram');

        $this->app->bind(Api::class, static fn($app) => $app[BotsManager::class]->bot());
        $this->app->alias(Api::class, 'telegram.bot');
    }

    public function boot(): void
    {

        $this->publishes([
            __DIR__ . '/../../config/telegram.php' => config_path('telegram.php'),
        ], 'config');

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
}