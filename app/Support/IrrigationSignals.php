<?php

namespace App\Support;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Señales de trabajo del panel del cultivador.
 *
 * Todo lo que expone sale de datos que YA existen en la base:
 *  - el último riego, de las acciones de tipo 1 (`action_types.id = 1`) vinculadas
 *    a las plantas por el pivote `action_plant` y a su espacio por `actions.indoor_id`;
 *  - la frecuencia esperada, de la programación que el espacio ya guarda
 *    (`times_a_day` / `scheduled_days`).
 *
 * No inventa métricas: si el espacio no tiene frecuencia configurada, informa el
 * último riego pero no juzga si está atrasado.
 */
class IrrigationSignals
{
    /** Nunca se registró un riego para esa planta/espacio. */
    public const SIN_RIEGO = 'sin_riego';

    /** Hay frecuencia configurada y pasaron días de más. */
    public const ATRASADO = 'atrasado';

    /** Se regó hoy. */
    public const HOY = 'hoy';

    /** Al día según la frecuencia configurada (o sin frecuencia que juzgar). */
    public const AL_DIA = 'al_dia';

    /** Sin frecuencia configurada y ya pasaron varios días: se informa, no se afirma que esté bien. */
    public const SIN_FRECUENCIA = 'sin_frecuencia';

    /** Cantidad de días a partir de la cual se considera atrasado (con frecuencia diaria). */
    private const DIAS_ATRASO = 2;

    /**
     * Días a partir de los cuales, sin frecuencia configurada, se deja de mostrar en verde.
     *
     * El tope sale de la práctica documentada del proyecto (wiki `cultivo/riego`): en indoor
     * controlado se riega cada 2–4 días en vegetación y cada 3–5 en flora. Con 5 días se cubre
     * el rango más laxo: antes de eso no corresponde marcar nada como fuera de lo normal.
     */
    private const DIAS_SIN_FRECUENCIA = 5;

    /**
     * Último riego por planta del tenant: [plant_id => Carbon].
     *
     * Una sola consulta para todo el panel (evita N+1).
     *
     * @return array<int, Carbon>
     */
    public static function lastIrrigationByPlant(int $tenantId): array
    {
        return DB::table('action_plant')
            ->join('actions', 'actions.id', '=', 'action_plant.action_id')
            ->join('plants', 'plants.id', '=', 'action_plant.plant_id')
            ->join('indoors', 'indoors.id', '=', 'plants.indoor_id')
            ->where('indoors.tenant_id', $tenantId)
            ->where('actions.action_type_id', 1)
            ->groupBy('action_plant.plant_id')
            ->selectRaw('action_plant.plant_id as plant_id, max(actions.action_date) as ultimo')
            ->pluck('ultimo', 'plant_id')
            ->map(fn ($fecha) => Carbon::parse($fecha)->startOfDay())
            ->all();
    }

    /**
     * Último riego por espacio del tenant: [indoor_id => Carbon].
     *
     * Va por query builder y no por el modelo `Action`: `Action` tiene el `TenantScope`,
     * que **falla cerrado** cuando no hay usuario en contexto (cola, consola, tests)
     * y devolvería vacío sin avisar. Acá el tenant se filtra explícito.
     *
     * @return array<int, Carbon>
     */
    public static function lastIrrigationBySpace(int $tenantId): array
    {
        return DB::table('actions')
            ->where('tenant_id', $tenantId)
            ->where('action_type_id', 1)
            ->groupBy('indoor_id')
            ->selectRaw('indoor_id, max(action_date) as ultimo')
            ->pluck('ultimo', 'indoor_id')
            ->map(fn ($fecha) => Carbon::parse($fecha)->startOfDay())
            ->all();
    }

    /**
     * Estado de riego de una planta o espacio.
     *
     * @param  Carbon|null  $ultimoRiego  Fecha del último riego (null = nunca).
     * @param  int|null  $vecesPorDia  Frecuencia configurada en el espacio, si la hay.
     * @return array{estado: string, dias: int|null, etiqueta: string}
     */
    public static function state(?Carbon $ultimoRiego, ?int $vecesPorDia = null): array
    {
        if (! $ultimoRiego) {
            return [
                'estado' => self::SIN_RIEGO,
                'dias' => null,
                'etiqueta' => 'Sin riegos registrados',
            ];
        }

        $dias = (int) $ultimoRiego->diffInDays(Carbon::now()->startOfDay());

        $etiqueta = match (true) {
            $dias === 0 => 'Regado hoy',
            $dias === 1 => 'Regado ayer',
            default => "Último riego hace {$dias} días",
        };

        // Sin frecuencia configurada no se puede juzgar atraso: sólo se informa.
        if (empty($vecesPorDia)) {
            // Con varios días encima tampoco se afirma que esté al día: color neutro.
            if ($dias >= self::DIAS_SIN_FRECUENCIA) {
                return [
                    'estado' => self::SIN_FRECUENCIA,
                    'dias' => $dias,
                    'etiqueta' => "Sin riego hace {$dias} días",
                ];
            }

            return ['estado' => self::AL_DIA, 'dias' => $dias, 'etiqueta' => $etiqueta];
        }

        if ($dias >= self::DIAS_ATRASO) {
            return [
                'estado' => self::ATRASADO,
                'dias' => $dias,
                'etiqueta' => "Riego atrasado: {$dias} días sin regar",
            ];
        }

        return [
            'estado' => $dias === 0 ? self::HOY : self::AL_DIA,
            'dias' => $dias,
            'etiqueta' => $etiqueta,
        ];
    }

    /**
     * Resumen de riego de un conjunto de estados: cuántos atrasados y cuántos sin registrar.
     *
     * @param  array<int, array{estado: string}>  $estados
     * @return array{total: int, sin_riego: int, atrasados: int}
     */
    public static function summary(array $estados): array
    {
        $sinRiego = count(array_filter($estados, fn ($e) => $e['estado'] === self::SIN_RIEGO));
        $atrasados = count(array_filter($estados, fn ($e) => $e['estado'] === self::ATRASADO));

        return ['total' => count($estados), 'sin_riego' => $sinRiego, 'atrasados' => $atrasados];
    }
}
