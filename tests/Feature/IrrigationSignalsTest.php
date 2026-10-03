<?php

namespace Tests\Feature;

use App\Support\IrrigationSignals;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Señales de trabajo del panel: el último riego sale del pivote action_plant y de
 * actions.indoor_id, y el atraso sólo se juzga si el espacio tiene frecuencia cargada.
 */
class IrrigationSignalsTest extends TestCase
{
    use RefreshDatabase;

    private function crearTenant(string $nombre): int
    {
        return DB::table('tenants')->insertGetId([
            'name' => $nombre,
            'email' => strtolower(str_replace(' ', '.', $nombre)) . '@test.local',
            'active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function crearEspacio(int $tenantId, string $nombre, ?int $vecesPorDia = null): int
    {
        return DB::table('indoors')->insertGetId([
            'name' => $nombre,
            'large' => 100,
            'width' => 100,
            'height' => 180,
            'tenant_id' => $tenantId,
            'times_a_day' => $vecesPorDia,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function crearPlanta(int $indoorId, string $nombre): int
    {
        $seedId = DB::table('seeds')->insertGetId([
            'name' => 'Semilla de prueba',
            'seed_type' => 'Automatica',
            'ratio_thc' => 10,
            'ratio_cbd' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return DB::table('plants')->insertGetId([
            'name' => $nombre,
            'seed_id' => $seedId,
            'indoor_id' => $indoorId,
            'state' => 'Etapa de Vegetativa',
            'flowerpot' => 'Geotextiles',
            'capacity' => 10,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /** Crea el tipo de acción de riego con id 1 (es el que usan las señales). */
    private function crearTipoRiego(): void
    {
        DB::table('action_types')->insert([
            'id' => 1,
            'name' => 'Registrar Riego',
            'action_class' => 'App\\Actions\\RegisterIrrigation',
        ]);
    }

    private function crearRiego(int $tenantId, int $indoorId, array $plantIds, string $fecha): int
    {
        $actionId = DB::table('actions')->insertGetId([
            'action_date' => $fecha,
            'indoor_id' => $indoorId,
            'action_type_id' => 1,
            'tenant_id' => $tenantId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        foreach ($plantIds as $plantId) {
            DB::table('action_plant')->insert([
                'action_id' => $actionId,
                'plant_id' => $plantId,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        return $actionId;
    }

    public function test_una_planta_sin_riego_no_tiene_ultimo_riego_y_la_senal_lo_dice(): void
    {
        $this->crearTipoRiego();
        $tenant = $this->crearTenant('Sin Riego');
        $espacio = $this->crearEspacio($tenant, 'Carpa 60x60');
        $planta = $this->crearPlanta($espacio, 'Critical Auto #1');

        $ultimos = IrrigationSignals::lastIrrigationByPlant($tenant);

        $this->assertArrayNotHasKey($planta, $ultimos);

        $estado = IrrigationSignals::state($ultimos[$planta] ?? null, 1);

        $this->assertSame(IrrigationSignals::SIN_RIEGO, $estado['estado']);
        $this->assertSame('Sin riegos registrados', $estado['etiqueta']);
    }

    public function test_el_ultimo_riego_por_planta_sale_del_pivote_de_acciones(): void
    {
        $this->crearTipoRiego();
        $tenant = $this->crearTenant('Con Riegos');
        $espacio = $this->crearEspacio($tenant, 'Carpa 60x60', 1);
        $planta = $this->crearPlanta($espacio, 'Critical Auto #1');

        $this->crearRiego($tenant, $espacio, [$planta], Carbon::now()->subDays(5)->toDateString());
        $this->crearRiego($tenant, $espacio, [$planta], Carbon::now()->subDays(2)->toDateString());

        $ultimos = IrrigationSignals::lastIrrigationByPlant($tenant);

        $this->assertSame(2, (int) $ultimos[$planta]->diffInDays(Carbon::now()->startOfDay()));

        $estado = IrrigationSignals::state($ultimos[$planta], 1);

        $this->assertSame(IrrigationSignals::ATRASADO, $estado['estado']);
        $this->assertSame(2, $estado['dias']);
    }

    public function test_el_ultimo_riego_por_espacio_no_mezcla_tenants(): void
    {
        $this->crearTipoRiego();
        $tenantA = $this->crearTenant('Tenant A');
        $tenantB = $this->crearTenant('Tenant B');

        $espacioA = $this->crearEspacio($tenantA, 'Carpa A');
        $espacioB = $this->crearEspacio($tenantB, 'Carpa B');

        $plantaA = $this->crearPlanta($espacioA, 'Planta A');
        $plantaB = $this->crearPlanta($espacioB, 'Planta B');

        $this->crearRiego($tenantA, $espacioA, [$plantaA], Carbon::now()->subDay()->toDateString());
        $this->crearRiego($tenantB, $espacioB, [$plantaB], Carbon::now()->subDays(9)->toDateString());

        $deA = IrrigationSignals::lastIrrigationBySpace($tenantA);
        $deB = IrrigationSignals::lastIrrigationBySpace($tenantB);

        $this->assertCount(1, $deA);
        $this->assertCount(1, $deB);
        $this->assertSame(1, (int) $deA[$espacioA]->diffInDays(Carbon::now()->startOfDay()));
        $this->assertSame(9, (int) $deB[$espacioB]->diffInDays(Carbon::now()->startOfDay()));

        $this->assertArrayNotHasKey($espacioB, $deA);
        $this->assertArrayNotHasKey($espacioA, $deB);
    }

    public function test_el_atraso_solo_se_marca_si_el_espacio_tiene_frecuencia_cargada(): void
    {
        $hace3 = Carbon::now()->subDays(3)->startOfDay();

        $conFrecuencia = IrrigationSignals::state($hace3, 2);
        $this->assertSame(IrrigationSignals::ATRASADO, $conFrecuencia['estado']);
        $this->assertSame('Riego atrasado: 3 días sin regar', $conFrecuencia['etiqueta']);

        $sinFrecuencia = IrrigationSignals::state($hace3, null);
        $this->assertSame(IrrigationSignals::SIN_FRECUENCIA, $sinFrecuencia['estado']);
        $this->assertSame('Sin riego hace 3 días', $sinFrecuencia['etiqueta']);
    }

    public function test_sin_frecuencia_configurada_los_primeros_dias_quedan_en_verde(): void
    {
        $hace2 = Carbon::now()->subDays(2)->startOfDay();
        $senal = IrrigationSignals::state($hace2, null);

        $this->assertSame(IrrigationSignals::AL_DIA, $senal['estado']);
        $this->assertSame('Último riego hace 2 días', $senal['etiqueta']);
    }

    public function test_el_riego_de_hoy_y_de_ayer_se_informan_con_su_etiqueta(): void
    {
        $hoy = IrrigationSignals::state(Carbon::now()->startOfDay(), 1);
        $this->assertSame(IrrigationSignals::HOY, $hoy['estado']);
        $this->assertSame('Regado hoy', $hoy['etiqueta']);

        $ayer = IrrigationSignals::state(Carbon::now()->subDay()->startOfDay(), 1);
        $this->assertSame(IrrigationSignals::AL_DIA, $ayer['estado']);
        $this->assertSame('Regado ayer', $ayer['etiqueta']);
    }

    public function test_el_resumen_cuenta_las_plantas_sin_riego_y_las_atrasadas(): void
    {
        $resumen = IrrigationSignals::summary([
            IrrigationSignals::state(null, 1),
            IrrigationSignals::state(Carbon::now()->subDays(4)->startOfDay(), 1),
            IrrigationSignals::state(Carbon::now()->subDay()->startOfDay(), 1),
            IrrigationSignals::state(Carbon::now()->startOfDay(), 1),
        ]);

        $this->assertSame(4, $resumen['total']);
        $this->assertSame(1, $resumen['sin_riego']);
        $this->assertSame(1, $resumen['atrasados']);
    }
}
