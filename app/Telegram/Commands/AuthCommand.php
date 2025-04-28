<?php

namespace App\Telegram\Commands;

use Telegram\Bot\Commands\Command;
use App\Services\TenantTokenService;
use App\Models\Tenant;

class AuthCommand extends Command
{
    protected string $name = 'auth';
    protected string $description = 'Autentica al usuario con un token de tenant';

    public function handle()
    {
        $token = trim($this->getArguments()[0] ?? '');

        if (empty($token)) {
            $this->replyWithMessage([
                'text' => 'Por favor proporciona un token válido. Ejemplo: /auth TU_TOKEN_AQUI',
                'parse_mode' => 'Markdown'
            ]);
            return;
        }

        // Verificar el token con el TenantTokenService
        $tenant = app(TenantTokenService::class)->getTenantByToken($token);

        if (!$tenant) {
            $this->replyWithMessage([
                'text' => '❌ Token inválido. Por favor verifica e intenta nuevamente.',
                'parse_mode' => 'Markdown'
            ]);
            return;
        }

        // Guardar la asociación usuario-tenant (implementar según tu sistema)
        $this->saveUserTenantAssociation($this->getUpdate()->getChat()->id, $tenant->id);

        $this->replyWithMessage([
            'text' => "✅ Autenticado correctamente con el tenant: *{$tenant->name}*",
            'parse_mode' => 'Markdown'
        ]);
    }

    protected function saveUserTenantAssociation($telegramUserId, $tenantId)
    {
        // Implementar lógica para guardar la asociación usuario-tenant
        // Por ejemplo, en una tabla user_tenant_mappings
    }
}