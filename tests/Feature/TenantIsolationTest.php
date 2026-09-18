<?php

namespace Tests\Feature;

use App\Filament\Tenant\Widgets\ActionsChartWidget;
use App\Filament\Tenant\Widgets\ActionTypesLineWidget;
use App\Filament\Tenant\Widgets\ActionTypesPieWidget;
use App\Filament\Tenant\Widgets\ActionsTimelineWidget;
use App\Filament\Tenant\Widgets\IndoorData;
use App\Filament\Tenant\Widgets\IndoorWidget;
use App\Filament\Tenant\Widgets\PlantList;
use App\Filament\Tenant\Widgets\PlantStatesByIndoorWidget;
use App\Filament\Tenant\Widgets\ProductNotificationsWidget;
use App\Filament\Tenant\Widgets\RecentNotificationsWidget;
use App\Filament\Tenant\Widgets\UpcomingNotificationsWidget;
use App\Models\Action;
use App\Models\ActionType;
use App\Models\Indoor;
use App\Models\Plant;
use App\Models\Seed;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * AISLAMIENTO ENTRE TENANTS EN EL DASHBOARD (WS2, 2026-09-18).
 *
 * Nace de una revisión medida: un tenant SIN datos veía datos de otro tenant en el dashboard
 * (`ActionTypesLineWidget` filtraba con `when(Filament::getTenant(), …)` y `Filament::getTenant()` es
 * NULL porque el panel NO usa la tenancy de Filament → el `when` nunca aplicaba). Además
 * `IndoorWidget` no renderizaba (la vista no recibía el dato) y `PlantList` importaba una clase
 * inexistente. Acá se fija el comportamiento para que no vuelva: se prueba EN LAS DOS DIRECCIONES
 * (el tenant con datos los ve; el vacío no ve nada ajeno).
 *
 * GAP CONOCIDO: `ActivityHeatmapWidget` no se puede ejercitar en esta suite porque usa SQL exclusivo de
 * Postgres (`EXTRACT(DOW FROM …)`) y los tests corren en sqlite. Su filtro por tenant se corrigió
 * (se le sacó un `orWhereNull('tenant_id')`), pero queda sin test automático.
 *
 * La deuda ESTRUCTURAL del aislamiento (el `TenantScope` que no exime al superadmin, `Plant` que lo
 * importa sin registrarlo, `Action` que no lo tiene) NO se cubre acá: es la tarjeta aparte
 * (`board/epicas/2026-09-18-revision-fugas-dashboard-widgets.md` §5.2).
 */
class TenantIsolationTest extends TestCase
{
    use RefreshDatabase;

    private function tenantConDatos(string $nombre = 'TENANT-A'): array
    {
        $tenant = Tenant::factory()->create(['name' => $nombre]);
        $user = User::factory()->create(['tenant_id' => $tenant->id]);

        $indoor = Indoor::create([
            'name' => 'INDOOR-DE-'.$nombre, 'large' => 2, 'width' => 2, 'height' => 2,
            'tenant_id' => $tenant->id, 'data' => ['fans' => ['f1'], 'lamps' => ['l1']],
        ]);
        $seed = Seed::create([
            'name' => 'SEED-DE-'.$nombre, 'seed_type' => 'auto', 'flowering_time' => 60,
            'ratio_thc' => 20, 'ratio_cbd' => 1,
        ]);
        $plant = Plant::create([
            'name' => 'PLANTA-DE-'.$nombre, 'seed_id' => $seed->id, 'indoor_id' => $indoor->id,
            'state' => 'Vegetativa', 'flowerpot' => '11L', 'capacity' => 11,
        ]);
        $tipo = ActionType::create([
            'name' => 'TIPO-DE-'.$nombre,
            'action_class' => 'App\\Utilities\\PlantActions\\RegisterState',
        ]);
        $action = Action::create([
            'action_date' => now()->toDateString(), 'indoor_id' => $indoor->id,
            'action_type_id' => $tipo->id, 'tenant_id' => $tenant->id,
        ]);

        return compact('tenant', 'user', 'indoor', 'plant', 'tipo', 'action');
    }

    /** Devuelve los data de un widget (getData() es protected en los ChartWidget). */
    private function dataDelWidget(string $clase): array
    {
        $instancia = Livewire::actingAs(auth()->user())->test($clase)->instance();
        $m = new \ReflectionMethod($instancia, 'getData');
        $m->setAccessible(true);

        return (array) $m->invoke($instancia);
    }

    /** Cuenta los registros que la TABLA de un widget le mostraria al usuario actual. */
    private function registrosDeLaTabla(string $clase): int
    {
        $instancia = Livewire::actingAs(auth()->user())->test($clase)->instance();

        return (int) $instancia->getTable()->getQuery()->count();
    }

    /** Los nombres (columna `name`) que la tabla de un widget le mostraria al usuario actual. */
    private function nombresDeLaTabla(string $clase): array
    {
        $instancia = Livewire::actingAs(auth()->user())->test($clase)->instance();

        return $instancia->getTable()->getQuery()->pluck('name')->all();
    }

    public function test_un_tenant_vacio_no_ve_datos_de_otro_tenant(): void
    {
        $this->tenantConDatos('A');                     // datos que NO se deben filtrar
        $b = Tenant::factory()->create(['name' => 'B']); // este queda VACIO
        $userB = User::factory()->create(['tenant_id' => $b->id]);
        $this->actingAs($userB);

        // --- el caso que era la fuga: el grafico por tipo de accion
        $data = $this->dataDelWidget(ActionTypesLineWidget::class);
        $this->assertSame([], $data['datasets'] ?? null,
            'ActionTypesLineWidget mostro datasets a un tenant vacio: FUGAA de datos de otro tenant');

        // --- los otros graficos, sin datos (ojo: el pie devuelve un dataset VACIO, no [])
        $this->assertSame([], $this->dataDelWidget(ActionsChartWidget::class)['datasets'] ?? null);
        $this->assertSame([], $this->dataDelWidget(PlantStatesByIndoorWidget::class)['datasets'] ?? null);
        $pie = $this->dataDelWidget(ActionTypesPieWidget::class);
        $totalPie = collect($pie['datasets'] ?? [])->flatMap(fn ($ds) => collect($ds['data'] ?? [])->all())->sum();
        $this->assertSame(0, (int) $totalPie, 'ActionTypesPieWidget mostro datos a un tenant vacio');
        $timeline = $this->dataDelWidget(ActionsTimelineWidget::class);
        foreach ($timeline['datasets'] ?? [] as $ds) {
            $this->assertSame(0, (int) collect($ds['data'] ?? [0])->sum(),
                'ActionsTimelineWidget mostro actividad a un tenant vacio');
        }

        // --- widgets que antes NO renderizaban y ahora si (sin datos)
        Livewire::actingAs($userB)->test(IndoorWidget::class)->assertOk();
        Livewire::actingAs($userB)->test(PlantList::class)->assertOk();
        Livewire::actingAs($userB)->test(ProductNotificationsWidget::class)->assertOk();

        // --- las tablas del dashboard, sin registros
        foreach ([IndoorData::class, PlantList::class, RecentNotificationsWidget::class,
                  UpcomingNotificationsWidget::class] as $clase) {
            $this->assertSame(0, $this->registrosDeLaTabla($clase),
                class_basename($clase).' le mostro registros a un tenant vacio');
        }
    }

    public function test_el_tenant_con_datos_si_ve_los_suyos(): void
    {
        $a = $this->tenantConDatos('A');
        $this->actingAs($a['user']);

        // el grafico por tipo de accion le muestra SU tipo de accion (la otra direccion de la fuga)
        $data = $this->dataDelWidget(ActionTypesLineWidget::class);
        $labels = collect($data['datasets'] ?? [])->pluck('label')->all();
        $this->assertContains('TIPO-DE-A', $labels, 'el grafico no le mostro su propio tipo de accion');
        $this->assertSame(1, array_sum(collect($data['datasets'])->flatMap(fn ($ds) => $ds['data'])->all()));

        // las tablas le muestran SUS registros
        $this->assertSame(1, $this->registrosDeLaTabla(IndoorData::class));
        $this->assertContains('INDOOR-DE-A', $this->nombresDeLaTabla(IndoorData::class));
        $this->assertSame(1, $this->registrosDeLaTabla(PlantList::class));

        // el widget de indoor muestra SU indoor
        Livewire::actingAs($a['user'])->test(IndoorWidget::class)
            ->assertOk()
            ->assertSee('INDOOR-DE-A');
    }
}
