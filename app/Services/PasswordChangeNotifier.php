<?php

namespace App\Services;

use App\Models\TelegramUserTenant;
use App\Models\User;
use App\Notifications\PasswordChangedNotification;
use Illuminate\Support\Facades\Log;
use Telegram\Bot\Laravel\Facades\Telegram;

/**
 * Avisa que la contraseña de una cuenta cambió: **mail siempre** y **Telegram si el grupo tiene un
 * chat asociado** (y vigente).
 *
 * Por qué existe: es la única forma de que un cambio NO autorizado se note. Si alguien entra con la
 * credencial filtrada y la cambia, el dueño se entera en el momento, por dos canales distintos, y
 * con el método de recuperación a mano.
 *
 * Este servicio NO cierra la sesión ni registra el evento de seguridad: eso lo hace la página, y el
 * evento se registra ANTES de avisar (si el aviso falla, la trazabilidad ya quedó).
 */
class PasswordChangeNotifier
{
    public function notify(User $user, string $context): void
    {
        $this->byMail($user, $context);
        $this->byTelegram($user, $context);
    }

    /**
     * Mail a la cuenta (el `email` del usuario que cambió la contraseña).
     */
    private function byMail(User $user, string $context): void
    {
        try {
            $user->notify(new PasswordChangedNotification($context));
        } catch (\Throwable $e) {
            // El aviso no puede tumbar un cambio que ya está hecho: se registra y sigue.
            Log::error('No se pudo avisar por mail el cambio de contraseña', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Chats de Telegram del grupo a los que corresponde avisar: los **vigentes** (un chat vencido no
     * es un canal válido).
     *
     * Es público a propósito: el `BotsManager` del SDK está marcado `final`, así que el facade no se
     * puede mockear y el envío no es testeable a nivel HTTP. Lo que sí se testea —y es la lógica que
     * importa— es **a quién** se avisa.
     *
     * @return \Illuminate\Support\Collection<int, TelegramUserTenant>
     */
    public function chatsDelGrupo(User $user): \Illuminate\Support\Collection
    {
        if ($user->tenant_id === null) {
            return collect();
        }

        return TelegramUserTenant::query()
            ->where('tenant_id', $user->tenant_id)
            ->where(function ($query) {
                $query->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            })
            ->get();
    }

    /**
     * Telegram: los chats del GRUPO (la tabla `telegram_user_tenant` asocia el chat al tenant, no a
     * un usuario).
     */
    private function byTelegram(User $user, string $context): void
    {
        $chats = $this->chatsDelGrupo($user);

        if ($chats->isEmpty()) {
            return;
        }

        $text = $this->telegramText($user, $context);

        foreach ($chats as $chat) {
            try {
                Telegram::sendMessage([
                    'chat_id' => $chat->telegram_user_id,
                    'text' => $text,
                    'parse_mode' => 'Markdown',
                    'disable_web_page_preview' => true,
                ]);
            } catch (\Throwable $e) {
                // Sin bot configurado o chat inválido: se registra y sigue (el mail ya salió).
                Log::error('No se pudo avisar por Telegram el cambio de contraseña', [
                    'user_id' => $user->id,
                    'telegram_user_id' => $chat->telegram_user_id,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }

    private function telegramText(User $user, string $context): string
    {
        $brandName = config('platform.brand_name');

        $text = "🔐 *Cambio de contraseña*\n\n";
        $text .= "La contraseña de *{$user->name}* cambió el " . now()->format('d/m/Y') . " a las " . now()->format('H:i') . ".\n\n";
        $text .= $context === PasswordChangedNotification::CONTEXT_FORCED
            ? "Fue un cambio obligatorio: la plataforma lo pidió al ingresar.\n\n"
            : "El cambio se hizo desde la cuenta.\n\n";
        $text .= "Si NO reconocés este cambio, restablecé la contraseña ya:\n";
        $text .= url('/tenant/password-reset/request');

        if (filled($brandName)) {
            $text .= "\n\n_🤖 {$brandName}_";
        }

        return $text;
    }
}
