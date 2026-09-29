<?php

namespace Tests\Support;

use App\Models\Plant;
use App\Support\TenantContext;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * Job de prueba para medir CON QUÉ CONTEXTO corre un job real (encolado y procesado por el worker,
 * no una llamada directa a `handle()`).
 *
 * Por qué existe: `TenantContext` es `scoped`, así que un job encolado NO corre sobre la instancia de
 * la request que lo despachó — y el worker arranca sin sesión. Este job captura el resultado en
 * propiedades ESTÁTICAS porque el objeto que ejecuta la cola es otro (el payload se serializa y se
 * reconstruye), así que una propiedad de instancia no volvería al test.
 */
class SondaDeContexto implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /** Lo que vio `TenantContext::current()` adentro del job (false = cerrado, null = abierto). */
    public static mixed $contexto = 'no corrió';

    /** Cuántas plantas vio el scope con ese contexto. */
    public static ?int $plantas = null;

    public function __construct(public ?int $fijarTenant = null) {}

    public function handle(): void
    {
        if ($this->fijarTenant !== null) {
            TenantContext::use($this->fijarTenant);
        }

        self::$contexto = TenantContext::current();
        self::$plantas = Plant::count();
    }

    /** Deja la sonda limpia entre aserciones. */
    public static function reset(): void
    {
        self::$contexto = 'no corrió';
        self::$plantas = null;
    }
}
