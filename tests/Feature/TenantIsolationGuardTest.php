<?php

namespace Tests\Feature;

use App\Models\Scopes\TenantScope;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * GATE ESTRUCTURAL DEL AISLAMIENTO POR TENANT (2026-09-28).
 *
 * Por qué existe: el hotfix del 2026-09-28 arregló una fuga entre grupos que se había colado por
 * **tres omisiones silenciosas** que ningún gate veía:
 *   1. `TenantScope` hacía `return` cuando no había usuario (o sea NO filtraba) y ese camino sólo
 *      corre en producción (webhook/job): los tests siempre usan `actingAs`, así que el suite estaba
 *      verde. → Ese comportamiento se fija en `TenantScopeTest` (falla cerrado).
 *   2. `Plant` importaba `TenantScope` y **nunca registraba** el `addGlobalScope` (el `use` quedó
 *      huérfano). No es un error de sintaxis, ni de PHPStan, ni de ningún test.
 *   3. `Action` no tenía scope.
 * y una cuarta que apareció auditando: `ActionType` (catálogo global + tipos propios) tampoco lo tenía,
 * así que el panel tenant listaba los tipos personalizados de OTROS grupos.
 *
 * Este test corre dentro del job `phpunit` (cero CI extra) y convierte ese tipo de omisión en ROJO:
 * obliga a que todo modelo con datos de grupo tenga el scope o figure como exento CON MOTIVO.
 *
 * ⚠️ Si agregás un modelo de datos de grupo: registrale el scope (o sumalo a `EXENTOS` explicando por
 * qué). Si tocás `TenantScope`, mirá también `TenantScopeTest` (semántica) y
 * `tests/Feature/Telegram/AislamientoTenantsBotTest.php` (el mismo aislamiento por el camino real).
 */
class TenantIsolationGuardTest extends TestCase
{
    use RefreshDatabase;

    /** Modelos de datos de grupo: DEBEN tener el `TenantScope` registrado. */
    private const CON_SCOPE = [
        'Indoor' => 'tenant_id propio',
        'Action' => 'tenant_id propio',
        'Seed' => 'propio + globales (allowGlobal)',
        'ActionType' => 'propio + globales (allowGlobal)',
        'CropPlan' => 'propio + globales (allowGlobal)',
        'Plant' => 'byIndoor: plants NO tiene tenant_id, el tenant sale del indoor',
    ];

    /** Modelos con columna `tenant_id` que NO llevan scope a propósito: el motivo es la decisión. */
    private const EXENTOS = [
        'Tenant' => 'es la tabla de grupos (el superadmin los lista todos)',
        'User' => 'se consulta por autenticación y por la relación del grupo',
        'ApiToken' => 'se accede siempre por Tenant::apiTokens()',
        'TelegramUserTenant' => 'el webhook la busca por telegram_user_id ANTES de conocer el tenant',
        'SecurityEvent' => 'trazabilidad de plataforma (la lee el superadmin)',
        'UsageEvent' => 'telemetría de plataforma (el embudo mide todos los grupos)',
    ];

    /** @return array<string, class-string<Model>> nombre corto => clase, de app/Models (sin Scopes). */
    private function modelosDeLaApp(): array
    {
        $modelos = [];

        foreach (glob(app_path('Models/*.php')) as $archivo) {
            $clase = 'App\\Models\\'.basename($archivo, '.php');

            if (! class_exists($clase) || ! is_subclass_of($clase, Model::class)) {
                continue;
            }

            $modelos[class_basename($clase)] = $clase;
        }

        return $modelos;
    }

    public function test_los_modelos_de_datos_de_grupo_tienen_el_scope(): void
    {
        foreach (self::CON_SCOPE as $nombre => $comoFiltra) {
            $clase = 'App\\Models\\'.$nombre;

            $this->assertTrue(class_exists($clase), "El modelo {$nombre} de la lista CON_SCOPE no existe");

            $tieneScope = collect((new $clase)->getGlobalScopes())
                ->contains(fn ($scope) => $scope instanceof TenantScope);

            $this->assertTrue(
                $tieneScope,
                "El modelo {$nombre} perdió el `TenantScope` ({$comoFiltra}): sin el scope, cualquier ".
                'consulta sin sesión (el webhook del bot, un job) devuelve datos de OTROS grupos.'
            );
        }
    }

    public function test_ningun_modelo_con_tenant_id_queda_sin_decision_explicita(): void
    {
        $sinDecision = [];

        foreach ($this->modelosDeLaApp() as $nombre => $clase) {
            if (! Schema::hasColumn((new $clase)->getTable(), 'tenant_id')) {
                continue; // no es un modelo de datos de grupo
            }

            if (isset(self::CON_SCOPE[$nombre]) || isset(self::EXENTOS[$nombre])) {
                continue; // ya está decidido (con scope, o exento y fundamentado)
            }

            $sinDecision[] = $nombre;
        }

        $this->assertSame(
            [],
            $sinDecision,
            'Estos modelos tienen `tenant_id` y no están en ninguna lista de TenantIsolationGuardTest: '.
            'o les registrás el TenantScope, o los sumás a EXENTOS con el motivo. Modelos: '.implode(', ', $sinDecision)
        );
    }

    public function test_los_modelos_exentos_estan_fundamentados(): void
    {
        foreach (self::EXENTOS as $nombre => $motivo) {
            $this->assertNotSame('', trim($motivo), "El modelo exento {$nombre} no tiene motivo escrito");
        }
    }
}
