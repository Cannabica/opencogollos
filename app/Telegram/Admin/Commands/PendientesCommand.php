<?php

namespace App\Telegram\Admin\Commands;

use App\Services\Admin\TenantAdminService;

class PendientesCommand extends AdminCommand
{
    protected string $name = 'pendientes';

    protected string $description = 'Lista tenants inactivos esperando activación';

    public function handle()
    {
        if (! $this->ensureAuthorized()) {
            return;
        }

        $pending = app(TenantAdminService::class)->pendingActivation();

        if ($pending === []) {
            $this->reply('✅ No hay tenants pendientes de activación.');
            return;
        }

        $lines = [];
        foreach ($pending as $tenant) {
            $name = htmlspecialchars((string) $tenant['name'], ENT_QUOTES, 'UTF-8');
            $email = htmlspecialchars((string) $tenant['email'], ENT_QUOTES, 'UTF-8');
            $lines[] = "#{$tenant['id']} — {$name} — {$email}";
        }

        $this->reply(
            "⏳ <b>Tenants pendientes de activación</b>\n\n"
            . implode("\n", $lines)
            . "\n\nPara activar uno: <code>/activar ID</code>"
        );
    }
}
