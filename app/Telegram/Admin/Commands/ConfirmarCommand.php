<?php

namespace App\Telegram\Admin\Commands;

use App\Services\Admin\AdminPendingActionService;
use App\Services\Admin\TenantAdminService;

class ConfirmarCommand extends AdminCommand
{
    protected string $name = 'confirmar';

    protected string $description = 'Ejecuta la acción pendiente de activar/desactivar';

    public function handle()
    {
        if (! $this->ensureAuthorized()) {
            return;
        }

        $userId = $this->getUpdate()->getMessage()->getFrom()->getId();
        $pending = app(AdminPendingActionService::class)->get($userId);

        if ($pending === null) {
            $this->reply('❌ No hay ninguna acción pendiente. Usá /activar ID o /desactivar ID primero.');
            return;
        }

        app(AdminPendingActionService::class)->clear($userId);

        $service = app(TenantAdminService::class);
        $tenant = $service->setActive($pending['tenant_id'], $pending['action'] === 'activate');

        if ($tenant === null) {
            $this->reply('❌ El tenant ya no existe. Acción cancelada.');
            return;
        }

        $name = htmlspecialchars((string) $tenant['name'], ENT_QUOTES, 'UTF-8');
        $verb = $pending['action'] === 'activate' ? 'activado' : 'desactivado';

        $this->reply("✅ Tenant #{$tenant['id']} ({$name}) fue {$verb} correctamente.");
    }
}
