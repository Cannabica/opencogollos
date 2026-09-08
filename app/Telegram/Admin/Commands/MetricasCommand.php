<?php

namespace App\Telegram\Admin\Commands;

use App\Services\Admin\TenantAdminService;

class MetricasCommand extends AdminCommand
{
    protected string $name = 'metricas';

    protected string $description = 'Muestra métricas globales de la plataforma';

    public function handle()
    {
        if (! $this->ensureAuthorized()) {
            return;
        }

        $m = app(TenantAdminService::class)->globalMetrics();

        $this->reply(
            "📊 <b>Métricas globales</b>\n\n"
            . "• Tenants: {$m['tenants']} ({$m['tenants_active']} activos)\n"
            . "• Usuarios web: {$m['users']}\n"
            . "• Usuarios Telegram: {$m['telegram_users']}\n"
            . "• Indoors: {$m['indoors']}\n"
            . "• Plantas: {$m['plants']}\n"
            . "• Semillas: {$m['seeds']}\n"
            . "• Planes de cultivo: {$m['crop_plans']}\n"
            . "• Acciones: {$m['actions']}"
        );
    }
}
