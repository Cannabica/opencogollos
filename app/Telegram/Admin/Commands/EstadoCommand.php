<?php

namespace App\Telegram\Admin\Commands;

use App\Services\Admin\PlatformStatusService;

class EstadoCommand extends AdminCommand
{
    protected string $name = 'estado';

    protected string $description = 'Muestra el estado del sitio (DB, Redis, cola, jobs fallidos, pendientes)';

    public function handle()
    {
        if (! $this->ensureAuthorized()) {
            return;
        }

        $status = app(PlatformStatusService::class)->summary();

        $db = $status['db'] ? '✅ OK' : '❌ Error';
        $redis = match ($status['redis']) {
            true => '✅ OK',
            false => '❌ Error',
            default => '⚪ No disponible',
        };
        $failedJobs = $status['failed_jobs'] === null
            ? 'n/a (tabla ausente)'
            : (string) $status['failed_jobs'];

        $this->reply(
            "🩺 <b>Estado del sitio</b>\n\n"
            . "• Base de datos: {$db}\n"
            . "• Redis: {$redis}\n"
            . "• Cola (driver): <code>{$status['queue_driver']}</code>\n"
            . "• Jobs fallidos: {$failedJobs}\n"
            . "• Tenants pendientes de activación: {$status['pending_tenants']}"
        );
    }
}
