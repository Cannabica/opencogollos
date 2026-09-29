<?php

namespace App\Telegram\Commands;

use App\Models\TelegramUserTenant;
use App\Support\TenantContext;

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

        if (!$association->tenant->active) {
            $this->replyWithMessage([
                'text' => '❌ El tenant asociado a tu cuenta se encuentra inactivo. Por favor contacta al administrador.',
                'parse_mode' => 'HTML'
            ]);
            return null;
        }

        // El webhook NO tiene sesión: fijamos el contexto del tenant de la asociación para que el
        // `TenantScope` filtre TODAS las consultas del comando (antes, sin usuario, no filtraba nada:
        // era la puerta de la fuga entre grupos).
        TenantContext::use((int) $association->tenant_id);

        return $association;
    }
}