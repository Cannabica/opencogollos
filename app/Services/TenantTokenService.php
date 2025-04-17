<?php

namespace App\Services;

use App\Models\Tenant;
use App\Models\ApiToken;
use Tymon\JWTAuth\Facades\JWTAuth;
use Illuminate\Support\Carbon;

class TenantTokenService
{
    public function generateToken(Tenant $tenant, string $reference = null): string
    {
        if ($tenant->apiTokens()->count() >= 5) {
            throw new \RuntimeException('No se pueden crear más de 5 tokens activos');
        }

        $token = bin2hex(random_bytes(16)); // Generates a 32-character hex token

        $tenant->apiTokens()->create([
            'token_hash' => hash('sha256', $token),
            'token' => $token,
            'expires_at' => Carbon::now()->addDays(7),
            'renew_count' => 0,
            'reference' => !empty($reference) ? $reference : null
        ]);

        return $token;
    }

    public function renewToken(string $tokenHash): ?string
    {
        try {
            $apiToken = ApiToken::where('token_hash', $tokenHash)->first();
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

            if ($apiToken->renew_count >= 10) {
                \Log::error('Token renewal limit reached', ['token_id' => $apiToken->id]);
                return null;
            }
            $apiToken = $tenant->apiTokens()
                ->where('token_hash', $tokenHash)
                ->first();

            if (!$apiToken) {
                \Log::error('API token not found in database', ['token_hash' => $tokenHash]);
                return null;
            }

            if ($apiToken->renew_count >= 10) {
                \Log::error('Token renewal limit reached', ['token_id' => $apiToken->id]);
                return null;
            }

            $newToken = bin2hex(random_bytes(16)); // Generates 32-character hex token

            $apiToken->update([
                'token_hash' => hash('sha256', $newToken),
                'token' => $newToken,
                'expires_at' => Carbon::now()->addDays(7),
                'renew_count' => $apiToken->renew_count + 1,
                'last_renewed_at' => now()
            ]);

            \Log::debug('Token renewed successfully', [
                'token_id' => $apiToken->id,
                'renew_count' => $apiToken->renew_count
            ]);

            return $newToken;
        } catch (\Exception $e) {
            \Log::error('Token renewal failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return null;
        }
    }
}