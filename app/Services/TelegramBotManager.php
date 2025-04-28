<?php

namespace App\Services;

use App\Models\TenantBot;
use Telegram\Bot\BotsManager;
use Illuminate\Support\Facades\Log;

class TelegramBotManager extends BotsManager
{
    public function __construct()
    {
        parent::__construct(config('telegram'));

        $this->loadTenantBots();
    }

    protected function loadTenantBots()
    {
        try {
            TenantBot::all()->each(function ($bot) {
                $this->addBot([
                    'token' => $bot->getDecryptedToken(),
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
        
        return $bot ? $this->bot($bot->bot_username) : null;
    }
}