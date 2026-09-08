<?php

namespace App\Http\Middleware;

use Closure;
use App\Models\Tenant;
use App\Models\ApiToken;
use Illuminate\Support\Str;

class TenantTokenMiddleware
{
    public function handle($request, Closure $next)
    {
        $token = $request->bearerToken();;
        
        if (!$token) {
            return response()->json(['error' => 'Unauthorized - No token provided'], 401);
        }

        $apiToken = ApiToken::where('token_hash', hash('sha256', $token))
            ->where('expires_at', '>', now())
            ->first();

        if (!$apiToken) {
            return response()->json(['error' => 'Unauthorized - Invalid token'], 401);
        }

        $tenant = $apiToken->tenant;
        $request->merge([
            'tenant' => $tenant,
            'apiToken' => $apiToken,
            'token' => $token
        ]);

        // Add macro for backward compatibility
        if (!method_exists($request, 'token')) {
            $request->macro('token', function() use ($token) {
                return $token;
            });
        }

        return $next($request);
    }
}