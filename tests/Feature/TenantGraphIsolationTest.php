<?php

namespace Tests\Feature;

use App\Filament\Tenant\Widgets\ActionsChartWidget;
use App\Filament\Tenant\Widgets\ActionsTimelineWidget;
use App\Filament\Tenant\Widgets\ActionTypesLineWidget;
use App\Filament\Tenant\Widgets\ActionTypesPieWidget;
use App\Filament\Tenant\Widgets\ActivityHeatmapWidget;
use App\Filament\Tenant\Widgets\PlantStatesByIndoorWidget;
use App\Models\Action;
use App\Models\ActionType;
use App\Models\Indoor;
use App\Models\Plant;
use App\Models\Seed;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Aislamiento multi-tenant de los gráficos del panel tenant.
 *
 * Origen: reporte de producción (2026-09-28) "un tenant nuevo ve datos de otros".
 * Se midió con el snapshot real de la base de producción: los gráficos filtran por
 * el tenant del usuario. Este test congela ese comportamiento para que una query
 * nueva sin filtro (el patrón que ya causó la fuga del 2026-09-18, ver comentarios en
 * ActionTypesLineWidget y ActivityHeatmapWidget) rompa el CI en vez de llegar a prod.
 *
 * Cómo se obtiene la data del gráfico: se instancia el widget REAL y se invoca su
 * getData() por reflexión. Es la misma query que corre el panel, sin recortar el
 * global scope ni reescribir el SQL en el test.
 */
class TenantGraphIsolationTest extends TestCase
{
    use RefreshDatabase;

    private function crearTenantConDatos(string $nombre, string $email): array
    {
        $tenant = Tenant::withoutGlobalScopes()->create([
            'name' => $nombre,
            'email' => $email,
            'active' => true,
        ]);

        $user = User::withoutGlobalScopes()->create([
            'name' => $email,
            'email' => $email,
            'password' => bcrypt('password'),
            'tenant_id' => $tenant->id,
            'email_verified_at' => now(),
        ]);

        $seed = Seed::withoutGlobalScopes()->create([
            'name' => 'Semilla de ' . $nombre,
            'tenant_id' => $tenant->id,
            'seed_type' => 'feminizada',
            'flowering_time' => 60,
            'ratio_thc' => 20,
            'ratio_cbd' => 1,
        ]);

        $indoor = Indoor::withoutGlobalScopes()->create([
            'name' => 'Indoor de ' . $nombre,
            'large' => 100,
            'width' => 100,
            'height' => 200,
            'tenant_id' => $tenant->id,
        ]);

        $plant = Plant::create([
            'name' => 'Planta de ' . $nombre,
            'seed_id' => $seed->id,
            'indoor_id' => $indoor->id,
            'state' => 'Etapa Vegetativa',
            'germination_date' => now()->subDays(20)->toDateString(),
            'flowerpot' => '11L',
            'capacity' => 11,
        ]);

        $tipo = ActionType::create([
            'name' => 'Registrar Riego',
            'action_class' => 'x',
            'tenant_id' => null,
        ]);

        $action = Action::withoutGlobalScopes()->create([
            'action_type_id' => $tipo->id,
            'action_date' => now(),
            'indoor_id' => $indoor->id,
            'tenant_id' => $tenant->id,
        ]);
        $action->plants()->attach($plant->id);

        return [$tenant, $user];
    }

    private function crearUsuarioSinDatos(string $nombre, string $email): User
    {
        $tenant = Tenant::withoutGlobalScopes()->create([
            'name' => $nombre,
            'email' => $email,
            'active' => true,
        ]);

        return User::withoutGlobalScopes()->create([
            'name' => $email,
            'email' => $email,
            'password' => bcrypt('password'),
            'tenant_id' => $tenant->id,
            'email_verified_at' => now(),
        ]);
    }

    /** Suma recursiva de todo valor numérico que devuelve un widget de gráfico. */
    private function totalDeDatos(array $data): float
    {
        $suma = 0.0;
        foreach ($data as $valor) {
            if (is_array($valor) || $valor instanceof \Illuminate\Support\Collection) {
                $suma += $this->totalDeDatos((array) $valor);
            } elseif (is_numeric($valor)) {
                $suma += (float) $valor;
            }
        }

        return $suma;
    }

    /** Ejecuta el getData() REAL del widget (misma query que sirve el panel). */
    private function dataDelWidget(string $clase): array
    {
        $widget = new $clase();
        $metodo = new \ReflectionMethod($widget, 'getData');
        $metodo->setAccessible(true);

        return (array) $metodo->invoke($widget);
    }

    /**
     * Widgets de gráfico cubiertos en cualquier driver (el heatmap va aparte:
     * su SQL es Postgres-only, ver test_el_mapa_de_calor_no_ve_datos_de_otro_tenant).
     *
     * @return array<int, string>
     */
    private function widgetsDeGraficos(): array
    {
        return [
            ActionsChartWidget::class,
            ActionTypesLineWidget::class,
            ActionTypesPieWidget::class,
            ActionsTimelineWidget::class,
            PlantStatesByIndoorWidget::class,
        ];
    }

    public function test_un_tenant_vacio_no_ve_datos_de_otro_tenant_en_los_graficos(): void
    {
        [$otroTenant, $otroUser] = $this->crearTenantConDatos('Ajeno', 'ajeno@ejemplo.test');
        $usuarioNuevo = $this->crearUsuarioSinDatos('Nuevo', 'nuevo@ejemplo.test');

        // Control: el tenant ajeno tiene datos (si no, el test pasaría por vacío).
        $this->actingAs($otroUser);
        $this->assertGreaterThan(
            0,
            $this->totalDeDatos($this->dataDelWidget(ActionsChartWidget::class)),
            'El tenant ajeno debe tener acciones: es el control del test.'
        );

        $this->actingAs($usuarioNuevo);

        foreach ($this->widgetsDeGraficos() as $widget) {
            $total = $this->totalDeDatos($this->dataDelWidget($widget));

            $this->assertSame(
                0.0,
                $total,
                class_basename($widget) . " devolvió datos de otro tenant para un tenant vacío (total={$total})."
            );
        }
    }

    /**
     * El heatmap queda fuera del test anterior porque usa
     * `EXTRACT(DOW FROM action_date::timestamp)`: SQL Postgres-only, así que en el
     * driver del CI (sqlite :memory:) tira QueryException. Deuda abierta: hacerlo
     * driver-aware como UsageStats::activityMatrix() y cubrirlo también en sqlite.
     */
    public function test_el_mapa_de_calor_no_ve_datos_de_otro_tenant(): void
    {
        if (DB::connection()->getDriverName() !== 'pgsql') {
            $this->markTestSkipped('ActivityHeatmapWidget usa SQL Postgres-only (ver comentario del test).');
        }

        [$otroTenant, $otroUser] = $this->crearTenantConDatos('Ajeno', 'ajeno@ejemplo.test');
        $usuarioNuevo = $this->crearUsuarioSinDatos('Nuevo', 'nuevo@ejemplo.test');

        $this->actingAs($usuarioNuevo);

        $this->assertSame(
            0.0,
            $this->totalDeDatos($this->dataDelWidget(ActivityHeatmapWidget::class)),
            'El mapa de calor devolvió datos de otro tenant.'
        );
    }

    public function test_un_tenant_ve_sus_propios_datos_en_los_graficos(): void
    {
        [$tenant, $user] = $this->crearTenantConDatos('Propio', 'propio@ejemplo.test');

        $this->actingAs($user);

        $this->assertGreaterThan(
            0,
            $this->totalDeDatos($this->dataDelWidget(ActionsChartWidget::class)),
            'El dueño del tenant tiene que seguir viendo sus acciones.'
        );

        $this->assertGreaterThan(
            0,
            $this->totalDeDatos($this->dataDelWidget(PlantStatesByIndoorWidget::class)),
            'El dueño del tenant tiene que seguir viendo sus plantas.'
        );

        $data = $this->dataDelWidget(PlantStatesByIndoorWidget::class);
        $this->assertSame(
            ['Indoor de Propio'],
            array_values((array) $data['labels']),
            'Los labels del gráfico tienen que ser los indoors del propio tenant.'
        );
    }
}
