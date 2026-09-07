<?php

namespace App\Telegram\Admin\Commands;

use App\Services\Admin\AdminPendingActionService;
use App\Services\Admin\TenantAdminService;

class ActivarCommand extends AdminCommand
{
    protected string $name = 'activar';

    protected string $pattern = '{id}';

    protected string $description = 'Pide activar un tenant: /activar ID (pide confirmación)';

    public function handle()
    {
        if (! $this->ensureAuthorized()) {
            return;
        }

        $id = $this->argument('id');

        if ($id === null || ! ctype_digit((string) $id)) {
            $this->reply('❌ Uso correcto: <code>/activar ID</code>');
            return;
        }

        $tenant = app(TenantAdminService::class)->basic((int) $id);

        if ($tenant === null) {
            $this->reply("❌ No existe un tenant con id <code>{$id}</code>.");
            return;
        }

        if ($tenant['active']) {
            $name = htmlspecialchars((string) $tenant['name'], ENT_QUOTES, 'UTF-8');
            $this->reply("ℹ️ El tenant #{$id} ({$name}) ya está activo.");
            return;
        }

        $userId = $this->getUpdate()->getMessage()->getFrom()->getId();
        app(AdminPendingActionService::class)->set('activate', (int) $id, $userId);

        $name = htmlspecialchars((string) $tenant['name'], ENT_QUOTES, 'UTF-8');
        $this->reply(
            "🤔 ¿Confirmás <b>activar</b> al tenant #{$id} ({$name})?\n\n"
            . "Enviá <code>/confirmar</code> para ejecutarlo o <code>/cancelar</code> para descartar.\n"
            . "La confirmación vence en 5 minutos."
        );
    }
}
