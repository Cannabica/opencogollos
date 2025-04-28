<?php

namespace App\Http\Middleware;

use Closure;
use App\Models\TenantBot;
use Illuminate\Http\Request;

class VerifyTelegramTenant
{
    public function handle(Request $request, Closure $next)
    {
        try {
            $tenantId = $request->route('tenant');
            $signature = $request->header('X-Telegram-Bot-Api-Secret-Token');
            
            if (!$signature) {
                \Log::warning('Missing Telegram signature for tenant: '.$tenantId);
                return response('', 401);
            }

            $bot = TenantBot::where('tenant_id', $tenantId)
                ->where('webhook_secret', $signature)
                ->first();

            if (!$bot) {
                \Log::warning('Invalid bot configuration for tenant: '.$tenantId);
                return response('', 403);
            }

            app()->instance('current.tenant.bot', $bot);
            
            return $next($request);
        } catch (\Exception $e) {
            \Log::error('Telegram tenant verification failed: '.$e->getMessage());
            return response('', 401);
        }
    }
}