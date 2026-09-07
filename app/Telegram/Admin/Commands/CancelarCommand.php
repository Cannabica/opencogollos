<?php

namespace App\Telegram\Admin\Commands;

use App\Services\Admin\AdminPendingActionService;

class CancelarCommand extends AdminCommand
{
    protected string $name = 'cancelar';

    protected string $description = 'Descarta la acción pendiente de activar/desactivar';

    public function handle()
    {
        if (! $this->ensureAuthorized()) {
            return;
        }

        $userId = $this->getUpdate()->getMessage()->getFrom()->getId();
        app(AdminPendingActionService::class)->clear($userId);

        $this->reply('✅ Acción pendiente descartada. No se hizo ningún cambio.');
    }
}
