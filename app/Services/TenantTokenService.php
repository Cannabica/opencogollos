<?php

namespace App\Services;

use App\Models\Tenant;
use App\Models\ApiToken;
use Illuminate\Support\Str;
use Illuminate\Support\Carbon;

class TenantTokenService
{
    public function generateToken(Tenant $tenant, string $reference = null, array $abilities = ['*'], int $chatId = null): string
    {
        if ($tenant->apiTokens()->count() >= 5) {
            throw new \RuntimeException('No se pueden crear más de 5 tokens activos');
        }

        $plainTextToken = Str::random(40);

        $metadata = ['plain_text_token' => $plainTextToken];
        if ($chatId) {
            $metadata['telegram_chat_id'] = $chatId;
        }

        $tenant->apiTokens()->create([
            'token_hash' => hash('sha256', $plainTextToken),
            'expires_at' => Carbon::now()->addYear(),
            'reference' => $reference,
            'metadata' => json_encode($metadata)
        ]);

        return $plainTextToken;
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

            $plainTextToken = Str::random(40);

            $metadata = json_decode($apiToken->metadata, true) ?? [];
            $metadata['plain_text_token'] = $plainTextToken;

            $apiToken->update([
                'token_hash' => hash('sha256', $plainTextToken),
                'expires_at' => Carbon::now()->addYear(),
                'last_renewed_at' => now(),
                'renew_count' => $apiToken->renew_count + 1,
                'metadata' => json_encode($metadata)
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

    public function getCurrentToken(int $chatId): ?string
    {
        $apiTokens = ApiToken::where('expires_at', '>', now())
            ->orderBy('created_at', 'desc')
            ->get();

        foreach ($apiTokens as $apiToken) {
            $metadata = json_decode($apiToken->metadata, true);
            if (($metadata['telegram_chat_id'] ?? null) == $chatId) {
                return $metadata['plain_text_token'] ?? null;
            }
        }

        \Log::debug('No active token found for chat', ['chat_id' => $chatId]);
        return null;
    }

    public function getTenantIdFromToken(string $token): ?array
    {
        $apiToken = ApiToken::where('token_hash', hash('sha256', $token))
            ->where('expires_at', '>', now())
            ->first();

        return $apiToken ? [
            'tenant_id' => $apiToken->tenant_id,
            'expires_at' => $apiToken->expires_at
        ] : null;
    }
}