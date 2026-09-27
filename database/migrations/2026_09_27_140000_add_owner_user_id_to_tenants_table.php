<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Señal propia de propiedad del grupo: `tenants.owner_user_id`.
     *
     * Hasta acá "quién es el owner" se resolvía comparando `users.email` con `tenants.email`. Eso tenía
     * dos consecuencias medidas el 2026-09-27:
     *
     *  1. cambiar el email (el propio o el del grupo) **le sacaba el panel al owner**, sin forma de
     *     recuperarlo desde la app (hubo que tocar la base a mano);
     *  2. un grupo cuyo email no coincide con ningún usuario **no tiene administrador**: en la base del
     *     proyecto eso ya pasa con "Green Lab Cultivo" (usuario `martin@greenlab.com.ar`, grupo
     *     `hola@greenlab.com.ar`), así que nadie puede sumar gente ni editar el grupo.
     *
     * La migración es ADITIVA y con backfill: se marca como owner al usuario que hoy matchea por email
     * (los grupos existentes siguen funcionando igual). Los que quedan sin marcar siguen resolviéndose
     * por email —el fallback vive en `User::isTenantOwner()`— y se pueden designar desde la UI.
     */
    public function up(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->foreignId('owner_user_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
        });

        // Backfill: el usuario que hoy es owner *de hecho* (mismo email que el grupo).
        DB::table('tenants')->orderBy('id')->get()->each(function ($tenant) {
            $ownerId = DB::table('users')->where('email', $tenant->email)->value('id');

            // Si nadie matchea por email, el grupo quedaba SIN administrador (caso real: "Green Lab
            // Cultivo"). Se designa al primer miembro: es el único candidato razonable y, si no era el
            // correcto, se puede reasignar desde la UI.
            if (! $ownerId) {
                $ownerId = DB::table('users')
                    ->where('tenant_id', $tenant->id)
                    ->orderBy('id')
                    ->value('id');
            }

            if ($ownerId) {
                DB::table('tenants')->where('id', $tenant->id)->update(['owner_user_id' => $ownerId]);
            }
        });
    }

    public function down(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->dropConstrainedForeignId('owner_user_id');
        });
    }
};
