<?php

namespace App\Services\Admin;

use Illuminate\Support\Facades\Cache;

/**
 * Acción pendiente de confirmación del superadmin (dos pasos).
 *
 * El flujo es: /activar ID (o /desactivar ID) guarda la acción con
 * vencimiento a 5 minutos y pide confirmación; /confirmar la ejecuta y
 * /cancelar la descarta. La clave está atada al usuario de Telegram, así
 * que solo quien inició la acción puede confirmarla.
 */
class AdminPendingActionService
{
    private const TTL_SECONDS = 300;

    private function cacheKey(int $telegramUserId): string
    {
        return "admin_pending_action_{$telegramUserId}";
    }

    /**
     * @param  'activate'|'deactivate'  $action
     */
    public function set(string $action, int $tenantId, int $telegramUserId): void
    {
        Cache::put($this->cacheKey($telegramUserId), [
            'action' => $action,
            'tenant_id' => $tenantId,
            'set_at' => now()->toIso8601String(),
        ], self::TTL_SECONDS);
    }

    /**
     * @return array{action: 'activate'|'deactivate', tenant_id: int}|null
     */
    public function get(int $telegramUserId): ?array
    {
        $pending = Cache::get($this->cacheKey($telegramUserId));

        if (! is_array($pending) || ! isset($pending['action'], $pending['tenant_id'])) {
            return null;
        }

        return [
            'action' => $pending['action'],
            'tenant_id' => (int) $pending['tenant_id'],
        ];
    }

    public function clear(int $telegramUserId): void
    {
        Cache::forget($this->cacheKey($telegramUserId));
    }
}
