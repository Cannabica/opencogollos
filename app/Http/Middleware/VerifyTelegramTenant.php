<?php

namespace App\Http\Middleware;

use Closure;
use App\Services\TenantTokenService;
use Illuminate\Http\Request;

class VerifyTelegramTenant
{
    public function handle(Request $request, Closure $next)
    {
        try {
            // Para webhooks de Telegram, verificar el secret token si está configurado
            $telegramSecretToken = $request->header('X-Telegram-Bot-Api-Secret-Token');
            
            // Si usas secret token de Telegram (recomendado)
            if ($telegramSecretToken) {
                $expectedToken = config('telegram.webhook_secret'); // Configurar en config
                if ($telegramSecretToken !== $expectedToken) {
                    \Log::warning('Invalid Telegram secret token', [
                        'received' => $telegramSecretToken,
                        'ip' => $request->ip()
                    ]);
                    return response('Unauthorized', 401);
                }
            }
            
            // Verificación alternativa por token de API personalizado
            $token = $request->header('X-API-Token');
            
            if (!$token && !$telegramSecretToken) {
                \Log::warning('Missing authentication headers', [
                    'ip' => $request->ip(),
                    'user_agent' => $request->userAgent()
                ]);
                return response('Unauthorized', 401);
            }

            if ($token) {
                // Verify token and get tenant ID.
                // OJO: getTenantIdFromToken() NO devuelve un id, devuelve un array
                // ['tenant_id' => int, 'expires_at' => ...] (o null). Tratarlo como id hacia
                // Tenant::find(array) => Collection => $tenant->active siempre vacio => 403
                // en todos los casos. Mismo patron que AuthCommand (que si lo usa bien).
                $tokenData = app(TenantTokenService::class)->getTenantIdFromToken($token);

                if (!$tokenData) {
                    \Log::warning("Invalid API token", [
                        'token_prefix' => substr($token, 0, 8) . '...',
                        'ip' => $request->ip()
                    ]);
                    return response('Forbidden', 403);
                }

                $tenantId = $tokenData['tenant_id'];

                // Bind tenant instance to container
                $tenant = \App\Models\Tenant::find($tenantId);
                if (!$tenant) {
                    \Log::error("Tenant not found", ['tenant_id' => $tenantId]);
                    return response('Tenant not found', 404);
                }

                if (!$tenant->active) {
                    \Log::warning("Inactive tenant attempted to use Telegram API token", ['tenant_id' => $tenantId]);
                    return response('Forbidden: Tenant inactive', 403);
                }
                
                app()->instance('current.tenant', $tenant);
            }

            return $next($request);
            
        } catch (\Exception $e) {
            \Log::error('Telegram tenant verification failed', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'ip' => $request->ip(),
                'request_data' => $request->except(['password', 'token'])
            ]);
            
            return response('Internal Server Error', 500);
        }
    }
}