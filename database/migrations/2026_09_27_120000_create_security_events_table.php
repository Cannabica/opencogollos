<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Trazabilidad de SEGURIDAD, separada de `usage_events`.
     *
     * Por qué una tabla aparte y no una fila más en `usage_events`: esa tabla es telemetría de
     * producto y su semántica es "una fila por página vista" (la escribe `LogUsageMiddleware` sólo
     * para GET 2xx). Un cambio de contraseña no es una pageview: es una acción con consecuencia
     * sobre la cuenta. Mezclarlos rompería las dos cosas (los conteos de uso y la lectura de
     * seguridad, que se consulta por otro motivo y con otra retención).
     *
     * El evento es inmutable y guarda el **contexto** (voluntario vs forzado), que es la diferencia
     * que importa para auditar: no es lo mismo que un usuario haya elegido cambiarla a que el sistema
     * lo haya obligado.
     */
    public function up(): void
    {
        Schema::create('security_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('event', 50);
            $table->string('context', 20)->nullable();
            $table->timestamp('created_at');

            $table->index(['tenant_id', 'created_at']);
            $table->index(['user_id', 'created_at']);
            $table->index(['event', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('security_events');
    }
};
