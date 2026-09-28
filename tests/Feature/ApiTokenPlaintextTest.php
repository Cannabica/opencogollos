<?php

namespace Tests\Feature;

use App\Models\Tenant;
use App\Services\TenantTokenService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * EL TOKEN DEL BOT NO SE GUARDA EN CLARO (hotfix 2026-09-28).
 *
 * `TenantTokenService` escribía `plain_text_token` en `api_tokens.metadata`: el secreto en claro al lado
 * de su propio hash. Nadie lo leía (`getCurrentToken()` no tenía llamadas) y el token alcanza con que
 * viaje una vez por `/auth` — el chat ya autenticado guarda su asociación en `telegram_user_tenant`.
 * Acá se fija que no vuelva atrás y que la purga limpia las instalaciones que ya lo tengan guardado.
 */
class ApiTokenPlaintextTest extends TestCase
{
    use RefreshDatabase;

    public function test_el_token_en_claro_no_queda_guardado_en_la_base(): void
    {
        $tenant = Tenant::factory()->create();
        $servicio = app(TenantTokenService::class);

        $token = $servicio->generateToken($tenant, 'prueba');

        // El token sigue autenticando al bot (viaja una sola vez por /auth)...
        $datos = $servicio->getTenantIdFromToken($token);
        $this->assertNotNull($datos, 'el token generado no autentica');
        $this->assertSame($tenant->id, $datos['tenant_id']);

        // ...pero en la base queda sólo el hash.
        $fila = DB::table('api_tokens')->first();
        $this->assertNotNull($fila);
        $this->assertSame(hash('sha256', $token), $fila->token_hash);

        $metadata = (string) ($fila->metadata ?? '');
        $this->assertStringNotContainsString($token, $metadata, 'el token en claro quedó guardado en la base');
        $this->assertArrayNotHasKey(
            'plain_text_token',
            json_decode($metadata, true) ?? [],
            'el token en claro quedó guardado en `metadata`'
        );
    }

    public function test_la_purga_saca_el_token_en_claro_de_las_filas_viejas(): void
    {
        $tenant = Tenant::factory()->create();

        // Fila como la escribía el servicio ANTES del hotfix.
        DB::table('api_tokens')->insert([
            'tenant_id' => $tenant->id,
            'token_hash' => hash('sha256', 'token-viejo-de-prueba'),
            'expires_at' => now()->addYear(),
            'metadata' => json_encode([
                'plain_text_token' => 'token-viejo-de-prueba',
                'telegram_chat_id' => 42,
            ]),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // El archivo devuelve una clase anónima que extiende `Migration` (sin `up()` declarado en la
        // clase base), así que se invoca con un callable: PHPStan no puede tipar el método.
        $migracion = require database_path('migrations/2026_09_28_120000_purge_plain_text_tokens_from_api_tokens.php');
        call_user_func([$migracion, 'up']);

        $fila = DB::table('api_tokens')->first();
        $metadata = json_decode((string) $fila->metadata, true);

        $this->assertArrayNotHasKey('plain_text_token', $metadata, 'la purga no sacó el token en claro');
        $this->assertSame(42, $metadata['telegram_chat_id'], 'la purga se llevó datos que no eran el token');
        $this->assertSame(
            hash('sha256', 'token-viejo-de-prueba'),
            $fila->token_hash,
            'la purga tocó el hash (el token viejo tiene que seguir autenticando)'
        );
    }
}
