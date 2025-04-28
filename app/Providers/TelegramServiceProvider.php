<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Services\TelegramBotManager;
use Telegram\Bot\Laravel\Facades\Telegram;

class TelegramServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->app->singleton('telegram', function ($app) {
            return new TelegramBotManager();
        });

        $this->app->alias('telegram', Telegram::class);
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        $this->publishes([
            __DIR__.'/../../config/telegram.php' => config_path('telegram.php'),
        ], 'telegram-config');
    }
}
