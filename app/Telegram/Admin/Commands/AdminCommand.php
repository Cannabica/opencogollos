<?php

namespace App\Telegram\Admin\Commands;

use App\Services\Admin\AdminAuthorizer;
use Illuminate\Support\Facades\Log;
use Telegram\Bot\Commands\Command;
use Telegram\Bot\Objects\Message;

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
        $userId = $this->currentUserId();

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

    protected function currentUserId(): ?int
    {
        $message = $this->getUpdate()->getMessage();

        if (! $message instanceof Message) {
            return null;
        }

        $from = $message->get('from');
        $id = null;

        if (is_array($from)) {
            $id = $from['id'] ?? null;
        } elseif (is_object($from) && method_exists($from, 'get')) {
            $id = $from->get('id');
        }

        return is_numeric($id) ? (int) $id : null;
    }

    protected function reply(string $text, string $parseMode = 'HTML'): mixed
    {
        return $this->replyWithMessage([
            'text' => $text,
            'parse_mode' => $parseMode,
        ]);
    }
}
