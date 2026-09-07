<?php

namespace App\Telegram\Admin\Commands;

use App\Services\Admin\TenantAdminService;

class TenantCommand extends AdminCommand
{
    protected string $name = 'tenant';

    protected string $pattern = '{id}';

    protected string $description = 'Muestra el detalle de un tenant: /tenant ID';

    public function handle()
    {
        if (! $this->ensureAuthorized()) {
            return;
        }

        $id = $this->argument('id');

        if ($id === null || ! ctype_digit((string) $id)) {
            $this->reply('❌ Uso correcto: <code>/tenant ID</code> (ej: /tenant 5)');
            return;
        }

        $detail = app(TenantAdminService::class)->detail((int) $id);

        if ($detail === null) {
            $this->reply("❌ No existe un tenant con id <code>{$id}</code>.");
            return;
        }

        $name = htmlspecialchars((string) $detail['name'], ENT_QUOTES, 'UTF-8');
        $email = htmlspecialchars((string) $detail['email'], ENT_QUOTES, 'UTF-8');
        $owner = htmlspecialchars((string) ($detail['owner'] ?? '—'), ENT_QUOTES, 'UTF-8');
        $state = $detail['active'] ? '✅ Activo' : '⛔ Inactivo';

        $this->reply(
            "🏢 <b>Tenant #{$detail['id']}</b>\n\n"
            . "• Nombre: {$name}\n"
            . "• Email: {$email}\n"
            . "• Estado: {$state}\n"
            . "• Creado: {$detail['created_at']}\n"
            . "• Activado: " . ($detail['activated_at'] ?? 'nunca') . "\n"
            . "• Owner: {$owner}\n\n"
            . "📦 <b>Conteos</b>\n"
            . "• Usuarios web: {$detail['web_users']}\n"
            . "• Usuarios Telegram: {$detail['telegram_users']}\n"
            . "• Indoors: {$detail['indoors']}\n"
            . "• Plantas: {$detail['plants']}\n"
            . "• Semillas: {$detail['seeds']}\n"
            . "• Planes de cultivo: {$detail['crop_plans']}"
        );
    }
}
