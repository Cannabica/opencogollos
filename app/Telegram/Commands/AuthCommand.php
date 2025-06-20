<?php

namespace App\Telegram\Commands;

use Telegram\Bot\Commands\Command;
use App\Services\TenantTokenService;
use App\Models\Tenant;
use Illuminate\Support\Facades\Log;
use Throwable;

class AuthCommand extends Command
{
    protected string $name = 'auth';
    protected string $pattern = '{token}';
    protected string $description = 'Autentica al usuario con un token de tenant';

    public function handle()
    {
        try {
            $token = $this->argument('token');
            Log::info('AuthCommand executed', [
                'update_id' => $this->getUpdate()?->getUpdateId(),
                'user_id' => $this->getUpdate()?->getMessage()?->getFrom()?->getId(),
                'token' => substr($token, 0, 4) . '...'
            ]);       

            if (empty($token)) {
                $this->replyWithMessage([
                    'text' => '❌ No se recibió token. Por favor usa: /auth TOKEN',
                    'parse_mode' => 'Markdown'
                ]);
                return;
            }

            $tokenData = app(TenantTokenService::class)->getTenantIdFromToken($token);
            if (!$tokenData) {
                $this->replyWithMessage([
                    'text' => "❌ Token inválido: `{$token}`. Por favor verifica e intenta nuevamente.",
                    'parse_mode' => 'Markdown'
                ]);
                return;
            }
            $tenant = Tenant::find($tokenData['tenant_id']);
            $telegramUserId = $this->getUpdate()->getMessage()->getFrom()->getId();
            $existingAssociation = \App\Models\TelegramUserTenant::where('telegram_user_id', $telegramUserId)->first();

            if ($existingAssociation && $existingAssociation->tenant_id != $telegramUserId) {
                $this->replyWithMessage([
                    'text' => "⚠️ Ya estás asociado al tenant *{$existingAssociation->tenant->name}*. Tu asociación será actualizada al tenant *{$tenant->name}*.",
                    'parse_mode' => 'Markdown'
                ]);
            }

            $this->saveUserTenantAssociation($telegramUserId, $tenant->id, $tokenData['expires_at']);

            $this->replyWithMessage([
                'text' => "✅ Autenticado correctamente con el tenant *{$tenant->name}* usando token: `{$token}`\n\n👤 Usuario: *{$this->getUpdate()->getMessage()->getFrom()->getFirstName()}* (ID: `{$this->getUpdate()->getMessage()->getFrom()->getId()}`)\n\n📅 El token expira el: *" . $tokenData['expires_at']->format('d/m/Y H:i') . "*",
                'parse_mode' => 'Markdown'
            ]);

        } catch (Throwable $e) {
            $this->logError($e);
            $this->replyWithMessage([
                'text' => '⚠️ Ocurrió un error inesperado. Por favor intenta nuevamente.',
                'parse_mode' => 'Markdown'
            ]);
        }
    }

    protected function saveUserTenantAssociation($telegramUserId, $tenantId, $expiresAt)
    {
        try {
            Log::info('Saving user-tenant association', [
                'telegram_user_id' => $telegramUserId,
                'tenant_id' => $tenantId,
                'expires_at' => $expiresAt
            ]);
            $username = $this->getUpdate()->getMessage()->getFrom()->getUsername();
            
            return \App\Models\TelegramUserTenant::updateOrCreate(
                ['telegram_user_id' => $telegramUserId],
                [
                    'tenant_id' => $tenantId,
                    'telegram_username' => $username,
                    'expires_at' => $expiresAt
                ]
            );
        } catch (Throwable $e) {
            $this->logError($e, 'Failed to save user-tenant association');
            throw $e;
        }
    }

    protected function logError(Throwable $e, string $context = null): void
    {
        Log::error($context ?? 'AuthCommand error', [
            'error' => $e->getMessage(),
            'exception' => get_class($e),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'update_id' => $this->getUpdate()?->getUpdateId(),
            'command' => $this->getName(),
            'user_id' => $this->getUpdate()?->getMessage()?->getFrom()?->getId(),
            'trace' => config('app.debug') ? $e->getTraceAsString() : null,
        ]);
    }
}
