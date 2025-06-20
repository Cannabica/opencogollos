<?php

namespace App\Telegram\Commands;

use App\Models\TelegramUserTenant;

trait ChecksTelegramExpiration
{
    /**
     * Check if Telegram user association exists and is not expired
     */
    protected function checkTelegramAssociation(int $telegramUserId): ?TelegramUserTenant
    {
        $association = TelegramUserTenant::where('telegram_user_id', $telegramUserId)
            ->where(function($query) {
                $query->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            })
            ->first();

        if (!$association) {
            $this->replyWithMessage([
                'text' => '❌ Tu asociación ha expirado o no existe. Por favor autentícate nuevamente con /auth TU_TOKEN',
                'parse_mode' => 'HTML'
            ]);
            return null;
        }

        return $association;
    }
}