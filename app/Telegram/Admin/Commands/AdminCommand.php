<?php

namespace App\Telegram\Admin\Commands;

use App\Services\Admin\AdminAuthorizer;
use Illuminate\Support\Facades\Log;
use Telegram\Bot\Commands\Command;

/**
 * Base para todos los comandos del bot de administración.
 *
 * Cada comando arranca su handle() con:
 *
 *     if (! $this->ensureAuthorized()) {
 *         return;
 *     }
 */
abstract class AdminCommand extends Command
{
    protected function ensureAuthorized(): bool
    {
        $userId = $this->getUpdate()?->getMessage()?->getFrom()?->getId();

        if (app(AdminAuthorizer::class)->isAllowed($userId)) {
            return true;
        }

        Log::warning('Admin bot: comando no autorizado', [
            'command' => $this->getName(),
            'telegram_user_id' => $userId,
        ]);

        $this->replyWithMessage([
            'text' => "⛔ No estás autorizado a usar este bot.\nSi creés que es un error, contactá al administrador de la plataforma.",
        ]);

        return false;
    }

    protected function reply(string $text, string $parseMode = 'HTML'): mixed
    {
        return $this->replyWithMessage([
            'text' => $text,
            'parse_mode' => $parseMode,
        ]);
    }
}
