<?php

namespace App\Services\Admin;

use App\Models\Tenant;
use Illuminate\Support\Facades\Log;
use Telegram\Bot\Laravel\Facades\Telegram;
use Throwable;

/**
 * Envía mensajes proactivos del bot de administración al superadmin
 * (nuevo tenant registrado, etc.). Nunca tira excepciones: si falla el
 * envío, lo loguea y sigue.
 */
class AdminNotifierService
{
    public static function notifyNewTenant(Tenant $tenant): int
    {
        $name = htmlspecialchars((string) $tenant->name, ENT_QUOTES, 'UTF-8');
        $email = htmlspecialchars((string) $tenant->email, ENT_QUOTES, 'UTF-8');

        $text = "🆕 <b>Nuevo tenant registrado</b> (pendiente de activación)\n\n"
            . "• #{$tenant->id} — {$name}\n"
            . "• Email: {$email}\n\n"
            . "Activá con <code>/activar {$tenant->id}</code> o revisá <code>/pendientes</code>.";

        return (new self())->notify($text);
    }

    /**
     * @return int Cantidad de chats a los que se logró enviar.
     */
    public function notify(string $text, string $parseMode = 'HTML'): int
    {
        $chatIds = app(AdminAuthorizer::class)->allowedUserIds();
        $sent = 0;

        foreach ($chatIds as $chatId) {
            try {
                Telegram::bot('admin')->sendMessage([
                    'chat_id' => $chatId,
                    'text' => $text,
                    'parse_mode' => $parseMode,
                ]);
                $sent++;
            } catch (Throwable $e) {
                Log::warning('Admin bot: no se pudo enviar notificación', [
                    'chat_id' => $chatId,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $sent;
    }
}
