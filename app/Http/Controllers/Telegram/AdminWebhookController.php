<?php

namespace App\Http\Controllers\Telegram;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Telegram\Bot\Laravel\Facades\Telegram;
use Throwable;

/**
 * Webhook del bot de administración (superadmin).
 *
 * Seguridad:
 * - Sin TELEGRAM_ADMIN_SECRET_TOKEN configurado, rechaza todo con 503
 *   (fail closed: un deploy mal configurado no queda abierto).
 * - Exige el header X-Telegram-Bot-Api-Secret-Token (que Telegram envía
 *   cuando el webhook se registra con secret_token) y lo compara en
 *   tiempo constante.
 * - La autorización por usuario (allowlist) la aplican los comandos.
 */
class AdminWebhookController
{
    public function __invoke(Request $request)
    {
        $expectedSecret = (string) config('telegram.admin_secret', '');

        if ($expectedSecret === '') {
            Log::error('Admin bot: TELEGRAM_ADMIN_SECRET_TOKEN no está configurado. Webhook rechazado (fail closed).');
            return response('Service Unavailable', 503);
        }

        $receivedSecret = (string) $request->header('X-Telegram-Bot-Api-Secret-Token', '');

        if (! hash_equals($expectedSecret, $receivedSecret)) {
            Log::warning('Admin bot: secret token inválido en webhook', [
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);
            return response('Unauthorized', 401);
        }

        try {
            $bot = Telegram::bot('admin');
            $update = $bot->getWebhookUpdate();

            $message = $update->getMessage();
            $userId = $message instanceof \Telegram\Bot\Objects\Message
                ? $message->getFrom()?->getId()
                : null;

            Log::info('Admin bot: update recibido', [
                'update_id' => $update->getUpdateId(),
                'type' => $update->objectType(),
                'telegram_user_id' => $userId,
            ]);

            // Solo se procesan mensajes de texto (comandos). Los callback
            // queries se ignoran por ahora y responden ok para que Telegram
            // no reintente.
            if ($update->has('message') && $update->getMessage()->has('text')) {
                $bot->commandsHandler(true);
            }

            return response()->json(['status' => 'ok']);
        } catch (Throwable $e) {
            Log::error('Admin bot: error procesando webhook', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
            ]);
            return response('Error', 500);
        }
    }
}
