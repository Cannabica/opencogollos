<?php

namespace App\Http\Middleware;

use Closure;
use Tymon\JWTAuth\Facades\JWTAuth;
use App\Models\Tenant;
use App\Models\ApiToken;
use Carbon\Carbon;

class TenantTokenMiddleware
{
    public function handle($request, Closure $next)
    {
        try {
            $token = JWTAuth::parseToken();
            $payload = $token->getPayload();
            
            $tenant = Tenant::find($payload['tenant_id']);
            if (!$tenant) {
                return response()->json(['error' => 'Tenant not found'], 403);
            }

            $apiToken = $tenant->apiTokens()
                ->where('token_hash', hash('sha256', $token->getToken()->get()))
                ->first();

            if (!$apiToken || $apiToken->expires_at->lt(Carbon::now())) {
                return response()->json(['error' => 'Invalid or expired token'], 401);
            }

            // Set tenant on request for controller access
            $request->merge(['tenant' => $tenant]);

            return $next($request);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }
    }
}