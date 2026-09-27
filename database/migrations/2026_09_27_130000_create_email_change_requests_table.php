<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Solicitudes de cambio de email (doble opt-in).
     *
     * El email es la credencial de login, así que el cambio NO se aplica en el momento: se pide la
     * contraseña actual, se manda un link a la dirección NUEVA y recién cuando se confirma se cambia
     * `users.email`. Hasta entonces la credencial sigue siendo la vieja (y se avisa a la vieja que
     * hay una solicitud en curso).
     *
     * Del token se guarda sólo el **hash** (mismo criterio que `api_tokens.token_hash`): el valor que
     * viaja por mail no queda en la base.
     */
    public function up(): void
    {
        Schema::create('email_change_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('new_email', 255);
            $table->string('token_hash', 64)->unique();
            $table->timestamp('expires_at');
            $table->timestamp('confirmed_at')->nullable();
            $table->timestamp('created_at');

            $table->index(['user_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('email_change_requests');
    }
};
