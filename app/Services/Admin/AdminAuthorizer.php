<?php

namespace App\Services\Admin;

use Illuminate\Support\Facades\Config;

/**
 * Autoriza usuarios de Telegram para el bot de administración.
 *
 * La allowlist vive en TELEGRAM_ADMIN_ALLOWED_USER_IDS (lista de ids
 * numéricos separados por coma). Solo el superadmin de la plataforma
 * debería estar listado.
 */
class AdminAuthorizer
{
    /**
     * @return list<int>
     */
    public function allowedUserIds(): array
    {
        $raw = (string) Config::get('telegram.admin_allowed_user_ids', '');

        $ids = array_filter(
            array_map('trim', explode(',', $raw)),
            static fn (string $value): bool => $value !== ''
        );

        $parsed = [];
        foreach ($ids as $id) {
            if (ctype_digit($id)) {
                $parsed[] = (int) $id;
            }
        }

        return array_values(array_unique($parsed));
    }

    public function isAllowed(?int $telegramUserId): bool
    {
        if ($telegramUserId === null) {
            return false;
        }

        return in_array($telegramUserId, $this->allowedUserIds(), true);
    }
}
