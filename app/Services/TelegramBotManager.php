<?php

namespace App\Services;

use App\Models\TenantBot;
use Telegram\Bot\BotsManager;
use Illuminate\Support\Facades\Log;
use Telegram\Bot\Commands\CommandBus;

class TelegramBotManager extends BotsManager
{
    protected static bool $initialized = false;

    public function __construct(private array $config)
    {
        $bots = config('telegram.bots', []);
        if (empty($botToken)) {
            Log::error('TELEGRAM_BOT_TOKEN is not set in .env file.');
        }

        parent::__construct($config);

        log::info('Initializing TelegramBotManager', [
            'bots' => array_keys($bots),
            'default_bot' => config('telegram.default'),
            'environment' => env('APP_ENV', 'unknown')
        ]);
        
        if (!self::$initialized) {
            $this->initializeBotManager();
            self::$initialized = true;
        }
    }

    protected function initializeBotManager(): void
    {
        
        $webhookUrl = env('APP_URL', config('app.url').'/api/telegram/webhook/');
        $botToken = env('TELEGRAM_BOT_TOKEN');
        $maskedToken = $botToken ? substr($botToken, 0, 4) . '...' . substr($botToken, -4) : 'not set';

        Log::info('Initializing TelegramBotManager', [
            'bots' => array_keys(config('telegram.bots')),
            'webhook_url' => $webhookUrl ? 'set' : 'not set',
            'bot_token' => $maskedToken,
            'environment' => env('APP_ENV', 'unknown')
        ]);

        if ($webhookUrl) {
            Log::debug('Attempting tuo register webhook', [
                'url' => $webhookUrl,
                'bot' => config('telegram.default'),
                'secret_token' => env('TELEGRAM_SECRET_TOKEN') ? 'set' : 'not set'
            ]);
        }

        $this->loadTenantBots();
    }

    protected function loadTenantBots()
    {
        try {
            $count = TenantBot::count();
            Log::info("Loading $count tenant bots");

            TenantBot::all()->each(function ($bot) {
                $token = $bot->getDecryptedToken();
                $maskedToken = substr($token, 0, 4) . '...' . substr($token, -4);

                Log::debug("Registering bot for tenant {$bot->tenant_id}", [
                    'bot_username' => $bot->bot_username,
                    'token' => $maskedToken,
                    'webhook_url' => str_replace(
                        '{tenant}',
                        $bot->tenant_id,
                        config('telegram.bots.dynamic_bot.webhook_url')
                    ),
                    'commands_count' => count($bot->commands_config ?? [])
                ]);

                $this->addBot([
                    'token' => $token,
                    'webhook_url' => str_replace(
                        '{tenant}',
                        $bot->tenant_id,
                        config('telegram.bots.dynamic_bot.webhook_url')
                    ),
                    'commands' => $bot->commands_config ?? []
                ], $bot->bot_username);
            });
        } catch (\Exception $e) {
            Log::error('Failed to load tenant bots: '.$e->getMessage());
        }
    }
    public function getBotForTenant($tenantId)
    {
        $bot = TenantBot::where('tenant_id', $tenantId)->first();
        
        if ($bot) {
            Log::debug("Retrieved bot for tenant $tenantId", [
                'bot_username' => $bot->bot_username,
                'commands_count' => count($bot->commands_config ?? [])
            ]);
        } else {
            Log::warning("No bot found for tenant $tenantId");
        }
        
        return $bot ? $this->bot($bot->bot_username) : null;
    }
}