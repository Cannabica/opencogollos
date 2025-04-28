<?php

namespace App\Services;

use App\Models\Tenant;
use App\Models\ApiToken;
use Illuminate\Support\Str;
use Illuminate\Support\Carbon;

class TenantTokenService
{
    public function generateToken(Tenant $tenant, string $reference = null, array $abilities = ['*']): string
    {
        if ($tenant->apiTokens()->count() >= 5) {
            throw new \RuntimeException('No se pueden crear más de 5 tokens activos');
        }

        $plainTextToken = Str::random(40);

        $tenant->apiTokens()->create([
            'token_hash' => hash('sha256', $plainTextToken),
            'expires_at' => Carbon::now()->addYear(),
            'reference' => $reference
        ]);

        return $plainTextToken;
    }

    public function renewToken(string $tokenHash): ?string
    {
        try {
            $apiToken = ApiToken::where('token', $tokenHash)->first();
            if (!$apiToken) {
                \Log::error('No token found for hash', ['token_hash' => $tokenHash]);
                return null;
            }

            \Log::debug('Attempting to renew token', ['token_hash' => substr($tokenHash, 0, 3).'...']);
            
            $tenant = Tenant::find($apiToken->tenant_id);
            if (!$tenant) {
                \Log::error('Tenant not found for token', ['tenant_id' => $apiToken->tenant_id]);
                return null;
            }

            $plainTextToken = Str::random(40);

            $apiToken->update([
                'token_hash' => hash('sha256', $plainTextToken),
                'expires_at' => Carbon::now()->addYear(),
                'last_renewed_at' => now(),
                'renew_count' => $apiToken->renew_count + 1
            ]);

            \Log::debug('Token renewed successfully', [
                'token_id' => $apiToken->id
            ]);

            return $plainTextToken;
        } catch (\Exception $e) {
            \Log::error('Token renewal failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return null;
        }
    }
}