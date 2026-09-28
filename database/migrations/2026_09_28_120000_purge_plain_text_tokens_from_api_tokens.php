<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Purga el token en claro de `api_tokens.metadata`.
 *
 * Contexto (2026-09-28): `TenantTokenService` guardaba `plain_text_token` en `metadata`, o sea el
 * secreto en claro al lado de su propio hash (`token_hash`), que es lo único que hace falta para
 * autenticar. `getCurrentToken()` lo leía pero no lo llamaba nadie. El servicio dejó de escribirlo en
 * el mismo hotfix; esta migración limpia lo que ya esté guardado en instalaciones existentes.
 *
 * No hay vuelta atrás (`down()` no puede inventar el token): el token en claro no se puede reconstruir
 * desde el hash, y nadie lo necesita — los chats ya autenticados guardan su asociación aparte
 * (`telegram_user_tenant`).
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::table('api_tokens')
            ->whereNotNull('metadata')
            ->orderBy('id')
            ->chunkById(100, function ($rows): void {
                foreach ($rows as $row) {
                    $metadata = json_decode((string) $row->metadata, true);

                    if (! is_array($metadata) || ! array_key_exists('plain_text_token', $metadata)) {
                        continue;
                    }

                    unset($metadata['plain_text_token']);

                    DB::table('api_tokens')
                        ->where('id', $row->id)
                        ->update(['metadata' => json_encode($metadata)]);
                }
            });
    }

    public function down(): void
    {
        // Irreversible a propósito: no se puede reconstruir el token en claro desde su hash.
    }
};
