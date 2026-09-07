<?php

namespace App\Telegram\Admin\Commands;

use App\Services\Admin\TenantAdminService;

class TenantsCommand extends AdminCommand
{
    protected string $name = 'tenants';

    protected string $description = 'Lista los tenants de la plataforma';

    public function handle()
    {
        if (! $this->ensureAuthorized()) {
            return;
        }

        $service = app(TenantAdminService::class);
        $tenants = $service->list();
        $total = $service->totalTenants();

        if ($tenants === []) {
            $this->reply('🏢 No hay tenants todavía.');
            return;
        }

        $lines = [];
        foreach ($tenants as $tenant) {
            $state = $tenant['active'] ? '✅ activo' : '⛔ inactivo';
            $name = htmlspecialchars((string) $tenant['name'], ENT_QUOTES, 'UTF-8');
            $email = htmlspecialchars((string) $tenant['email'], ENT_QUOTES, 'UTF-8');
            $lines[] = "#{$tenant['id']} — {$name} — {$email} — {$state} ({$tenant['users']} usuarios web)";
        }

        $more = $total > count($tenants)
            ? "\n\n…y {$total} tenants en total (mostrando los primeros " . count($tenants) . ').'
            : '';

        $this->reply("🏢 <b>Tenants</b>\n\n" . implode("\n", $lines) . $more);
    }
}
