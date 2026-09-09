<?php

namespace Database\Seeders;

use App\Models\Action;
use App\Models\ActionType;
use App\Models\CropPlan;
use App\Models\Indoor;
use App\Models\Plant;
use App\Models\Seed;
use App\Models\Tenant;
use App\Models\User;
use App\Models\Scopes\CropPlanScope;
use App\Models\Scopes\TenantScope;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * Data demo FEACIENTE (épica W2) — perfiles de uso reales con reglas de coherencia:
 * edad ↔ etapa, fechas solo del ciclo actual, floración real por genética, sin lorem ipsum,
 * riegos/podas/fertis con valores de cultivador argentino.
 *
 * Perfiles: P1a novato automáticas · P1b perpetuo automáticas · P2a madres + esquejes ·
 * P3 club (con plantas listas para cosecha / caso lotes) · P5 productor multi-sala.
 *
 * NOTA: los seeders viejos (ExampleDataSeeder/PlantSeeder/ActionSeeder/SeedsSeeder) quedan
 * sin llamar desde DatabaseSeeder cuando se activa este; no se borran.
 */
class RealisticDemoSeeder extends Seeder
{
    // Tipos de acción (id fijo según ActionTypesSeeder)
    const T_RIEGO = 1;
    const T_PODA = 2;
    const T_APLICACION = 3;
    const T_TRANSPLANTE = 4;
    const T_OBSERVACION = 5;
    const T_MUERTE = 6;
    const T_CAMBIO_ESTADO = 7;

    private array $estados = [
        'germinacion' => 'Etapa de Germinación',
        'plantula' => 'Etapa de Plantula',
        'vegetativa' => 'Etapa Vegetativa',
        'floracion' => 'Etapa Floracion',
    ];

    /** Crop plans globales creados (clave => modelo) */
    private array $planes = [];
    /** Semillas globales creadas (clave nombre => modelo) */
    private array $globalSeeds = [];

    /** Registro "en vivo": para no crear acciones futuras */
    private Carbon $hoy;

    public function run(): void
    {
        $this->hoy = Carbon::now()->startOfDay();

        $this->crearPlanesDeCultivoGlobales();
        $this->crearSemillasGlobales();

        $this->crearPerfilP1aNovatoAutomaticas();
        $this->crearPerfilP1bPerpetuoAutomaticas();
        $this->crearPerfilP2aMadresEsquejes();
        $this->crearPerfilP3Club();
        $this->crearPerfilP5Productor();
    }

    // ------------------------------------------------------------------ catálogo global

    private function crearPlanesDeCultivoGlobales(): void
    {
        $planes = [
            // [clave, nombre, rest_pruning, rest_fert, stop_fert, irrigation,
            //  germ[since,until,luz,osc,hum_i,hum_u,temp_i,temp_u],
            //  plantula[...], vege[...], flora[...]]
            ['auto20', 'Automáticas 20/4', 7, 3, 14, 10,
                [0, 10, 20, 4, 60, 70, 24, 26],
                [10, 25, 20, 4, 60, 65, 22, 25],
                [25, 45, 20, 4, 55, 65, 22, 26],
                [45, 75, 20, 4, 40, 50, 20, 24]],
            ['foto1812', 'Fotoperiódicas 18/6 + 12/12', 7, 3, 14, 10,
                [0, 14, 18, 6, 65, 75, 24, 26],
                [14, 30, 18, 6, 60, 70, 22, 25],
                [30, 60, 18, 6, 55, 65, 22, 26],
                [60, 90, 12, 12, 40, 50, 20, 24]],
            ['madres186', 'Madres 18/6', 14, 3, 0, 8,
                [0, 14, 18, 6, 65, 75, 24, 26],
                [14, 30, 18, 6, 60, 70, 22, 25],
                [30, 365, 18, 6, 55, 65, 22, 26],
                [60, 90, 12, 12, 40, 50, 20, 24]],
            ['vege204', 'Fotoperiódicas vege extendido 20/4', 7, 3, 14, 10,
                [0, 14, 20, 4, 65, 75, 24, 26],
                [14, 30, 20, 4, 60, 70, 22, 25],
                [30, 120, 20, 4, 55, 65, 22, 26],
                [120, 150, 12, 12, 40, 50, 20, 24]],
        ];

        foreach ($planes as [$key, $name, $rp, $rf, $sf, $irr, $g, $p, $v, $f]) {
            $plan = CropPlan::withoutGlobalScope(CropPlanScope::class)->firstOrCreate(
                ['tenant_id' => null, 'name' => $name],
                $this->payloadPlan($name, $rp, $rf, $sf, $irr, $g, $p, $v, $f)
            );
            $this->planes[$key] = $plan;
        }
    }

    private function payloadPlan(string $name, int $rp, int $rf, int $sf, int $irr, array $g, array $p, array $v, array $f): array
    {
        return [
            'name' => $name,
            'tenant_id' => null,
            'rest_pruning' => $rp,
            'rest_fert' => $rf,
            'stop_fert' => $sf,
            'irrigation' => $irr,
            'germination_since' => $g[0], 'germination_until' => $g[1],
            'germination_light' => $g[2], 'germination_darkness' => $g[3],
            'germination_humidity_since' => $g[4], 'germination_humidity_until' => $g[5],
            'germination_temp_since' => $g[6], 'germination_temp_until' => $g[7],
            'plantula_since' => $p[0], 'plantula_until' => $p[1],
            'plantula_light' => $p[2], 'plantula_darkness' => $p[3],
            'plantula_humidity_since' => $p[4], 'plantula_humidity_until' => $p[5],
            'plantula_temp_since' => $p[6], 'plantula_temp_until' => $p[7],
            'vegetative_since' => $v[0], 'vegetative_until' => $v[1],
            'vegetative_light' => $v[2], 'vegetative_darkness' => $v[3],
            'vegetative_humidity_since' => $v[4], 'vegetative_humidity_until' => $v[5],
            'vegetative_temp_since' => $v[6], 'vegetative_temp_until' => $v[7],
            'flowering_since' => $f[0], 'flowering_until' => $f[1],
            'flowering_light' => $f[2], 'flowering_darkness' => $f[3],
            'flowering_humidity_since' => $f[4], 'flowering_humidity_until' => $f[5],
            'flowering_temp_since' => $f[6], 'flowering_temp_until' => $f[7],
        ];
    }

    /** Catálogo global de semillas (tenant_id null) con genéticas y floración real. */
    private function crearSemillasGlobales(): void
    {
        $lista = [
            ['Tropicana WFC', 'Fotoperiodica feminizada', 9.5, 24, 0, 'Sweed Lab'],
            ['Sundae Grape', 'Fotoperiodica feminizada', 8.5, 22, 0, 'Secret File'],
            ['Diamond Kush', 'Fotoperiodica feminizada', 8, 20, 0, 'Secret File'],
            ['Skywalker OG', 'Fotoperiodica feminizada', 9, 22, 0, 'Dutch Passion'],
            ['Amnesia Haze', 'Fotoperiodica feminizada', 11, 21, 1, 'Del Plata Seeds'],
            ['Sour Diesel', 'Fotoperiodica feminizada', 10, 20, 0, 'Del Plata Seeds'],
            ['Gorilla Glue #4', 'Fotoperiodica feminizada', 9, 25, 0, 'Del Plata Seeds'],
            ['Critical', 'Fotoperiodica feminizada', 8, 18, 1, 'Del Plata Seeds'],
            ['Blue Dream', 'Fotoperiodica feminizada', 9.5, 20, 0, 'Seedsman'],
            ['AK-47', 'Fotoperiodica regular', 9, 19, 1, 'Seedsman'],
            ['Northern Lights', 'Fotoperiodica regular', 8, 16, 1, 'Seedsman'],
            ['Purple Haze', 'Fotoperiodica regular', 10, 17, 0, 'Dutch Passion'],
            ['Critical Auto', 'Automatica', 10, 18, 0, 'Del Plata Seeds'],
            ['Gorilla Glue Auto', 'Automatica', 10, 24, 0, 'Seedsman'],
            ['Amnesia Haze Auto', 'Automatica', 11, 20, 0, 'Seedsman'],
            ['Northern Lights Auto', 'Automatica', 9, 16, 1, 'Del Plata Seeds'],
        ];
        foreach ($lista as [$nombre, $tipo, $flora, $thc, $cbd, $prov]) {
            $semilla = Seed::withoutGlobalScope(TenantScope::class)->firstOrCreate(
                ['tenant_id' => null, 'name' => $nombre],
                [
                    'seed_type' => $tipo,
                    'flowering_time' => $flora,
                    'ratio_thc' => $thc,
                    'ratio_cbd' => $cbd,
                    'aprobado_inase' => false,
                    'provider' => $prov,
                ]
            );
            $this->globalSeeds[$nombre] = $semilla;
        }
    }

    // ------------------------------------------------------------------ helpers

    private function crearTenant(string $nombre, string $email, string $userName, string $userEmail): Tenant
    {
        $tenant = Tenant::withoutGlobalScope(TenantScope::class)->firstOrCreate(
            ['email' => $email],
            ['name' => $nombre, 'active' => 1]
        );
        User::withoutGlobalScope(TenantScope::class)->firstOrCreate(
            ['email' => $userEmail],
            ['name' => $userName, 'tenant_id' => $tenant->id, 'password' => Hash::make('password')]
        );
        return $tenant;
    }

    private function crearIndoor(Tenant $tenant, string $nombre, float $largo, float $ancho, float $alto, array $lamps = [], array $fans = [], ?string $planKey = null): Indoor
    {
        return Indoor::withoutGlobalScope(TenantScope::class)->create([
            'tenant_id' => $tenant->id,
            'name' => $nombre,
            'large' => $largo,
            'width' => $ancho,
            'height' => $alto,
            'lamps' => $lamps,
            'fans' => $fans,
            'hygometer' => true,
            'humidifier' => false,
            'crop_plan_id' => ($planKey && isset($this->planes[$planKey])) ? $this->planes[$planKey]->id : null,
        ]);
    }

    /** Crea (si no existe) una semilla del tenant; si ya está en el catálogo global, la reutiliza. */
    private function crearSemilla(Tenant $tenant, string $nombre, string $tipo, float $floracionSemanas, int $thc, float $cbd, string $proveedor): Seed
    {
        if (isset($this->globalSeeds[$nombre])) {
            return $this->globalSeeds[$nombre];
        }
        return Seed::withoutGlobalScope(TenantScope::class)->firstOrCreate(
            ['tenant_id' => $tenant->id, 'name' => $nombre],
            [
                'seed_type' => $tipo,
                'flowering_time' => $floracionSemanas,
                'ratio_thc' => $thc,
                'ratio_cbd' => $cbd,
                'aprobado_inase' => false,
                'provider' => $proveedor,
            ]
        );
    }

    /**
     * Crea una planta con acciones lógicas según edad/etapa.
     * $edadDias = días desde germinación (hoy - edad). $floraDias = días que lleva en floración (0 si no flora).
     * $macetaFinal = [tipo, litros]. Auto si $auto.
     */
    private function crearPlanta(Indoor $indoor, Seed $semilla, string $nombre, int $edadDias, int $floraDias, array $macetaFinal, bool $auto = false, ?string $nota = null): Plant
    {
        // etapa derivada de la edad (auto: germ 0-10, plántula 10-25, vege 25-45; flora cuando $floraDias>0)
        if ($auto) {
            $etapa = match (true) {
                $floraDias > 0 => $this->estados['floracion'],
                $edadDias <= 10 => $this->estados['germinacion'],
                $edadDias <= 25 => $this->estados['plantula'],
                default => $this->estados['vegetativa'],
            };
        } else {
            $etapa = match (true) {
                $edadDias <= 14 => $this->estados['germinacion'],
                $edadDias <= 30 => $this->estados['plantula'],
                $floraDias > 0 => $this->estados['floracion'],
                default => $this->estados['vegetativa'],
            };
        }

        // Maceta coherente con el momento: las chicas arrancan en chica y transplantan
        $macetaInicial = $edadDias <= 20 ? ['Plásticas', 3] : $macetaFinal;
        $plant = Plant::withoutGlobalScope(TenantScope::class)->create([
            'name' => $nombre,
            'indoor_id' => $indoor->id,
            'seed_id' => $semilla->id,
            'state' => $etapa,
            'germination_date' => $this->hoy->copy()->subDays($edadDias),
            'flowerpot' => $macetaInicial[0],
            'capacity' => $macetaInicial[1],
            'base_floor' => ['Turba', 'Perlita', 'Humus de lombriz'],
            'soil_enrichment' => ['Humus de lombriz', 'Roca fosfórica'],
        ]);

        $tenantId = $indoor->tenant_id;
        $this->generarHistorial($plant, $indoor, $tenantId, $edadDias, $floraDias, $macetaFinal, $auto, $nota);
        return $plant;
    }

    /** Genera acciones verosímiles hacia atrás desde la edad de la planta. */
    private function generarHistorial(Plant $plant, Indoor $indoor, int $tenantId, int $edadDias, int $floraDias, array $macetaFinal, bool $auto, ?string $nota): void
    {
        // Riegos: cada 2-3 días desde el día 1 (volumen según etapa/maceta, litros con decimales sensatos)
        $paseFlora = $floraDias > 0 ? $edadDias - $floraDias : PHP_INT_MAX;
        for ($dia = 1; $dia < $edadDias; $dia += rand(2, 3)) {
            $enFlora = $dia >= $paseFlora;
            $litros = $enFlora ? 1.0 : ($dia < 20 ? 0.3 : 0.75);
            $this->crearAccion($plant, $indoor, $tenantId, self::T_RIEGO, $dia, [
                'irrigation' => ['irrigation_type' => 'liters', 'liters' => $litros],
            ], true);
        }

        // Transplante a maceta final alrededor del día 18-22 si hay cambio de tamaño y la planta ya vivió eso
        if ($macetaFinal[1] > 3 && $edadDias >= 22) {
            $this->crearAccion($plant, $indoor, $tenantId, self::T_TRANSPLANTE, 18, [
                'transplant' => ['new_flowerpot' => $macetaFinal[0], 'new_capacity' => $macetaFinal[1]],
            ]);
        }

        // Cambios de etapa con su acción (el historial muestra cuándo pasó)
        if ($auto) {
            if ($edadDias > 10) $this->crearAccion($plant, $indoor, $tenantId, self::T_CAMBIO_ESTADO, 10, ['change_state' => ['state' => $this->estados['plantula']]]);
            if ($edadDias > 25) $this->crearAccion($plant, $indoor, $tenantId, self::T_CAMBIO_ESTADO, 25, ['change_state' => ['state' => $this->estados['vegetativa']]]);
        } else {
            if ($edadDias > 14) $this->crearAccion($plant, $indoor, $tenantId, self::T_CAMBIO_ESTADO, 14, ['change_state' => ['state' => $this->estados['plantula']]]);
        }
        // Pase a flora registrado (foto) o inicio de flora (auto): el hito que importa
        if ($floraDias > 0) {
            $diaPaseFlora = $edadDias - $floraDias;
            $this->crearAccion($plant, $indoor, $tenantId, self::T_CAMBIO_ESTADO, $diaPaseFlora, ['change_state' => ['state' => $this->estados['floracion']]]);
        }

        // Podas: apical en vege temprana (día ~18, foto), bajeras justo antes de flora
        if (!$auto && $floraDias > 0) {
            $this->crearAccion($plant, $indoor, $tenantId, self::T_PODA, $edadDias - $floraDias - 3, ['pruning' => ['pruning_type' => ['bajeras']]]);
        } elseif (!$auto && $edadDias > 18) {
            $this->crearAccion($plant, $indoor, $tenantId, self::T_PODA, 18, ['pruning' => ['pruning_type' => ['apical', 'topping']]]);
        }

        // Aplicaciones de ferti: vege desde día 14 cada ~8 días; flora cada ~8 desde el pase
        if ($floraDias > 0) {
            $pase = $edadDias - $floraDias;
            for ($dia = $pase; $dia < $edadDias; $dia += 8) {
                $this->crearAccion($plant, $indoor, $tenantId, self::T_APLICACION, $dia, [
                    'product_application' => [
                        'application_type' => 'flora',
                        'observation' => 'Ferti de flora 2ml/L',
                        'comments' => 'Según calendario del plan',
                    ],
                ]);
            }
        } else {
            for ($dia = 14; $dia < $edadDias; $dia += 8) {
                $this->crearAccion($plant, $indoor, $tenantId, self::T_APLICACION, $dia, [
                    'product_application' => [
                        'application_type' => 'vege',
                        'observation' => 'Ferti de vege 1.5ml/L',
                        'comments' => 'Dosis media, agua dejada reposar 24h',
                    ],
                ]);
            }
        }

        // Observaciones: 1-3 con notas reales
        $observaciones = $this->observacionesPara($plant->state, $floraDias, $nota);
        $diasObs = $this->diasParaObservaciones($edadDias);
        foreach ($observaciones as $i => $texto) {
            if (isset($diasObs[$i])) {
                $this->crearAccion($plant, $indoor, $tenantId, self::T_OBSERVACION, $diasObs[$i], [
                    'observation' => ['comments' => $texto],
                ], true);
            }
        }
    }

    private function crearAccion(Plant $plant, Indoor $indoor, int $tenantId, int $tipoId, int $diasAtras, array $data, bool $silencioso = false): void
    {
        $fecha = $this->hoy->copy()->subDays($diasAtras);
        $action = Action::withoutGlobalScope(TenantScope::class)->create([
            'action_date' => $fecha,
            'indoor_id' => $indoor->id,
            'action_type_id' => $tipoId,
            'tenant_id' => $tenantId,
            'data' => $data,
            'created_at' => $fecha,
            'updated_at' => $fecha,
        ]);
        $action->plants()->attach($plant->id);
    }

    private function observacionesPara(string $estado, int $floraDias, ?string $nota): array
    {
        $obs = [];
        if ($estado === $this->estados['floracion']) {
            $obs[] = 'Arrancando a engordar cogollos, subiendo la resina. Viene linda.';
            $obs[] = $floraDias > 30 ? 'Casi lista: tricomas mayormente lechosos, algunos ámbar.' : 'Controlando temperatura de noche, no pasa de 24°.';
        } else {
            $obs[] = 'Creciendo parejo, hojas con buen color.';
        }
        $obs[] = $nota ?? 'Sin novedades, todo dentro de lo esperado.';
        return $obs;
    }

    private function diasParaObservaciones(int $edadDias): array
    {
        if ($edadDias > 40) return [rand(20, 30), rand(3, 8)];
        if ($edadDias > 15) return [rand(4, 10)];
        return [rand(1, 3)];
    }

    // ------------------------------------------------------------------ perfiles

    private function crearPerfilP1aNovatoAutomaticas(): void
    {
        $tenant = $this->crearTenant('Cultivo de Juan', 'juan@cultivo.com.ar', 'Juan Pérez', 'juan@cultivo.com.ar');
        if (Indoor::withoutGlobalScope(TenantScope::class)->where('tenant_id', $tenant->id)->exists()) return;

        $indoor = $this->crearIndoor($tenant, 'Carpa 60×60', 60, 60, 160, [
            ['power' => 150, 'technology' => 'led', 'coverage_area' => 1, 'observations' => 'LED full spectrum 150W'],
        ], [['inches' => 4]], 'auto20');
        $crit = $this->crearSemilla($tenant, 'Critical Auto', 'Automatica', 10, 18, 1, 'Del Plata Seeds');
        $nl = $this->crearSemilla($tenant, 'Northern Lights Auto', 'Automatica', 9, 16, 1, 'Del Plata Seeds');

        // 58 días: flora avanzada, a ~10 días de cosechar (caso de uso lotes)
        $this->crearPlanta($indoor, $crit, 'Critical Auto #1', 58, 28, ['Geotextiles', 10], true);
        // 40 días: flora media
        $this->crearPlanta($indoor, $crit, 'Critical Auto #2', 40, 12, ['Geotextiles', 10], true);
        // 9 días: plántula (novato)
        $this->crearPlanta($indoor, $nl, 'Northern Lights Auto #1', 9, 0, ['Plásticas', 5], true, 'Me pasé de agua el primer día, la dejé secar bien antes de regar de nuevo.');
        $this->crearNotificaciones($tenant);
    }

    private function crearPerfilP1bPerpetuoAutomaticas(): void
    {
        $tenant = $this->crearTenant('Cultivo de María', 'maria@cultivo.com.ar', 'María Gómez', 'maria@cultivo.com.ar');
        if (Indoor::withoutGlobalScope(TenantScope::class)->where('tenant_id', $tenant->id)->exists()) return;

        $indoor = $this->crearIndoor($tenant, 'Carpa 120×120', 120, 120, 200, [
            ['power' => 300, 'technology' => 'led', 'coverage_area' => 3, 'observations' => 'LED full spectrum 300W'],
        ], [['inches' => 6]], 'auto20');
        $gg = $this->crearSemilla($tenant, 'Gorilla Glue Auto', 'Automatica', 10, 24, 1, 'Seedsman');
        $am = $this->crearSemilla($tenant, 'Amnesia Haze Auto', 'Automatica', 11, 20, 1, 'Seedsman');

        // Escalonado cada ~2 semanas (perpetuo): 2 en cada banda
        $plan = [
            [3, 0, $gg, 'Gorilla Glue Auto #8'], [6, 0, $am, 'Amnesia Haze Auto #7'],
            [16, 0, $gg, 'Gorilla Glue Auto #6'], [21, 0, $am, 'Amnesia Haze Auto #5'],
            [38, 6, $gg, 'Gorilla Glue Auto #4'], [44, 12, $am, 'Amnesia Haze Auto #3'],
            [58, 24, $gg, 'Gorilla Glue Auto #2'], [66, 32, $am, 'Amnesia Haze Auto #1'],
        ];
        foreach ($plan as [$edad, $flora, $semilla, $nombre]) {
            $this->crearPlanta($indoor, $semilla, $nombre, $edad, $flora, ['Geotextiles', 15], true);
        }
        $this->crearNotificaciones($tenant);
    }

    private function crearPerfilP2aMadresEsquejes(): void
    {
        $tenant = $this->crearTenant('Green Lab Cultivo', 'hola@greenlab.com.ar', 'Martín Ruiz', 'martin@greenlab.com.ar');
        if (Indoor::withoutGlobalScope(TenantScope::class)->where('tenant_id', $tenant->id)->exists()) return;

        $madres = $this->crearIndoor($tenant, 'Cuarto de Madres', 80, 80, 180, [
            ['power' => 100, 'technology' => 'led', 'coverage_area' => 1, 'observations' => 'LED 100W 18/6'],
        ], [['inches' => 4]], 'madres186');
        $flora = $this->crearIndoor($tenant, 'Carpa Flora 120×120', 120, 120, 200, [
            ['power' => 300, 'technology' => 'led', 'coverage_area' => 3, 'observations' => 'LED full spectrum 300W 12/12'],
        ], [['inches' => 6]], 'foto1812');

        $am = $this->crearSemilla($tenant, 'Amnesia Haze', 'Fotoperiodica feminizada', 11, 21, 1, 'Del Plata Seeds');
        $gg = $this->crearSemilla($tenant, 'Gorilla Glue #4', 'Fotoperiodica feminizada', 9, 24, 1, 'Del Plata Seeds');

        // Madres (viven en vege, sin florar)
        $this->crearPlanta($madres, $am, 'Amnesia Haze · Madre', 120, 0, ['Geotextiles', 20], false);
        $this->crearPlanta($madres, $gg, 'Gorilla Glue #4 · Madre', 95, 0, ['Geotextiles', 20], false);

        // Esquejes en flora (misma genética que las madres → caso lote por genética)
        $this->crearPlanta($flora, $am, 'Amnesia Haze · Esqueje 1', 72, 38, ['Geotextiles', 12], false);
        $this->crearPlanta($flora, $am, 'Amnesia Haze · Esqueje 2', 72, 38, ['Geotextiles', 12], false);
        $this->crearPlanta($flora, $gg, 'Gorilla Glue #4 · Esqueje 1', 68, 40, ['Geotextiles', 12], false);
        $this->crearPlanta($flora, $gg, 'Gorilla Glue #4 · Esqueje 2', 68, 40, ['Geotextiles', 12], false);
        // Esquejes enraizando en plantula
        $this->crearPlanta($madres, $gg, 'Gorilla Glue #4 · Esqueje 3', 16, 0, ['Plásticas', 3], false, 'Recién enraizado, alta humedad para que prenda.');
        $this->crearNotificaciones($tenant);
    }

    private function crearPerfilP3Club(): void
    {
        $tenant = $this->crearTenant('Club Semilla Dorada', 'club@semilladorada.org', 'Club Semilla Dorada', 'club@semilladorada.org');
        if (Indoor::withoutGlobalScope(TenantScope::class)->where('tenant_id', $tenant->id)->exists()) return;

        $vege = $this->crearIndoor($tenant, 'Sala Vege', 200, 300, 250, [
            ['power' => 240, 'technology' => 'led', 'coverage_area' => 4, 'observations' => '2× LED 240W 18/6'],
        ], [['inches' => 8], ['inches' => 8]], 'vege204');
        $floraA = $this->crearIndoor($tenant, 'Sala Flora A', 200, 300, 250, [
            ['power' => 300, 'technology' => 'led', 'coverage_area' => 6, 'observations' => '2× LED 300W 12/12'],
        ], [['inches' => 10], ['inches' => 10]], 'foto1812');

        $am = $this->crearSemilla($tenant, 'Amnesia Haze', 'Fotoperiodica feminizada', 11, 21, 1, 'Banco propio');
        $sd = $this->crearSemilla($tenant, 'Sour Diesel', 'Fotoperiodica feminizada', 10, 20, 1, 'Banco propio');
        $crit = $this->crearSemilla($tenant, 'Critical', 'Fotoperiodica feminizada', 8, 17, 1, 'Banco propio');

        // Vege variada
        $this->crearPlanta($vege, $am, 'Amnesia Haze V1', 42, 0, ['Geotextiles', 15], false);
        $this->crearPlanta($vege, $sd, 'Sour Diesel V2', 35, 0, ['Geotextiles', 15], false);
        $this->crearPlanta($vege, $crit, 'Critical V3', 28, 0, ['Geotextiles', 15], false);
        $this->crearPlanta($vege, $am, 'Amnesia Haze V4', 21, 0, ['Geotextiles', 10], false);

        // Flora A — 4 plantas en D+62-68 (caso lotes: listas para cosechar)
        $floraAConfig = [
            [$am, 'Amnesia Haze F1', 88, 68], [$am, 'Amnesia Haze F2', 88, 68],
            [$sd, 'Sour Diesel F3', 80, 62], [$crit, 'Critical F4', 78, 62],
        ];
        foreach ($floraAConfig as [$semilla, $nombre, $edad, $flora]) {
            $this->crearPlanta($floraA, $semilla, $nombre, $edad, $flora, ['Geotextiles', 15], false);
        }
        $this->crearNotificaciones($tenant);
    }

    private function crearPerfilP5Productor(): void
    {
        $tenant = $this->crearTenant('Productor AgroCan', 'admin@agrocan.com.ar', 'AgroCan Producción', 'admin@agrocan.com.ar');
        if (Indoor::withoutGlobalScope(TenantScope::class)->where('tenant_id', $tenant->id)->exists()) return;

        $sala1 = $this->crearIndoor($tenant, 'Sala 1 · Flora', 400, 600, 300, [
            ['power' => 600, 'technology' => 'led', 'coverage_area' => 10, 'observations' => '8× LED 600W'],
        ], [['inches' => 12], ['inches' => 12]], 'foto1812');

        $am = $this->crearSemilla($tenant, 'Amnesia Haze', 'Fotoperiodica feminizada', 11, 21, 1, 'Proveedor INASE');
        $bd = $this->crearSemilla($tenant, 'Blue Dream', 'Fotoperiodica feminizada', 9, 19, 1, 'Proveedor INASE');

        // Tanda uniforme (productor: ciclos parejos por sala)
        for ($i = 1; $i <= 10; $i++) {
            $this->crearPlanta($sala1, $am, "Amnesia Haze P{$i}", 74 + ($i % 3), 60 + ($i % 3), ['Geotextiles', 20], false);
        }
        for ($i = 1; $i <= 5; $i++) {
            $this->crearPlanta($sala1, $bd, "Blue Dream P{$i}", 55 + $i, 42 + $i, ['Geotextiles', 20], false);
        }
        $this->crearNotificaciones($tenant);
    }

    // ------------------------------------------------------------------ notificaciones demo

    /** Histórico feaciente de notificaciones para el tenant (tabla notifications de Laravel). */
    private function crearNotificaciones(Tenant $tenant): void
    {
        $user = User::withoutGlobalScope(TenantScope::class)
            ->where('tenant_id', $tenant->id)
            ->orderBy('id')
            ->first();
        if (! $user) {
            return;
        }
        if (DB::table('notifications')->where('notifiable_id', $user->id)->exists()) {
            return;
        }

        $plantas = Plant::withoutGlobalScope(TenantScope::class)
            ->whereHas('indoor', fn ($q) => $q->where('tenant_id', $tenant->id))
            ->pluck('name')->take(3);
        $n1 = $plantas[0] ?? 'tus plantas';
        $n2 = $plantas[1] ?? null;

        $indoor = Indoor::withoutGlobalScope(TenantScope::class)
            ->where('tenant_id', $tenant->id)->value('name') ?? 'tu indoor';

        $mensajes = [
            ['Recordatorio de riego', "Hoy toca regar {$n1} en {$indoor}. Revisá el sustrato antes: si está seco a 3 cm, regá."],
            ['Notificaciones de Telegram activadas', 'A partir de ahora las alertas del cultivo te llegan también al bot de Telegram.'],
            ['Cambio de etapa registrado', "{$n1} pasó a una nueva etapa. Quedó registrado en la línea de tiempo de la planta."],
            ['Fertilización de flora', "Arrancó la fertilización de flora para {$n1}. Seguí el calendario del plan de cultivo."],
            ['Bienvenida al espacio', "Tu espacio de cultivo fue activado. Configurá tu indoor y cargá tus primeras semillas."],
            ['Poda sugerida', "Si todavía no lo hiciste, podés aprovechar para hacer una poda de bajeras en {$n1} antes de que avance la flora."],
            ['Revisión de temperatura', "La temperatura de {$indoor} se mantuvo estable los últimos días. Buen trabajo."],
            ['Recordatorio de riego', $n2
                ? "{$n2} puede necesitar agua: revisá el peso de la maceta antes de regar."
                : 'Revisá la humedad del sustrato antes del próximo riego.'],
            ['Actualización del plan de cultivo', 'El plan de cultivo asignado a tu indoor quedó actualizado con las últimas recomendaciones.'],
            ['Control de plagas', 'Hacé una pasada visual por debajo de las hojas: es la mejor forma de frenar cualquier plaga a tiempo.'],
        ];

        $diasAtras = [1, 2, 3, 5, 8, 12, 16, 21, 26, 30];
        $leidas = [true, false, false, true, true, true, false, true, true, true];

        foreach ($mensajes as $i => [$title, $message]) {
            $fecha = $this->hoy->copy()->subDays($diasAtras[$i])->setTime(rand(8, 20), rand(0, 59));
            DB::table('notifications')->insert([
                'id' => (string) Str::uuid(),
                // Type neutro (no ProductApplicationReminder): el job real de recordatorios
                // consume las de ese tipo de a una por corrida y vaciaría la demo.
                'type' => 'App\Notifications\DemoRecordatorio',
                'notifiable_type' => get_class($user),
                'notifiable_id' => $user->id,
                'data' => json_encode([
                    'title' => $title,
                    'message' => $message,
                    'body' => $message, // Filament database modal (campana) usa data.body
                    'format' => 'filament', // sin esto la campana topbar no muestra la notificación
                    'duration' => 'persistent', // sin esto el modal la trata como toast de 5s y la borra (notificationClosed → removeNotification)
                ], JSON_UNESCAPED_UNICODE),
                'read_at' => $leidas[$i] ? $fecha->copy()->addHours(rand(1, 20)) : null,
                'created_at' => $fecha,
                'updated_at' => $fecha,
            ]);
        }
    }
}
