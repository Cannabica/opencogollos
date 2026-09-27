<?php

namespace App\Listeners;

use App\Models\SecurityEvent;
use Illuminate\Auth\Events\PasswordReset;

/**
 * Definir la contraseña cierra el primer acceso.
 *
 * Cuando se da de alta a una persona, queda `force_password_change = true` para que el sistema le pida
 * cambiarla. Pero si la contraseña se define por el **link de la invitación** (o por "olvidé mi
 * contraseña"), ya está definida: si el flag queda en true, el middleware la manda a cambiarla otra vez
 * (loop reportado por Frankie, 2026-09-27).
 *
 * Se hace escuchando el evento `PasswordReset` de Laravel en vez de extender la página de reset: ese
 * evento lo dispara el broker **siempre**, sin importar qué pantalla completó el cambio. La versión
 * anterior vivía en una subclase de la página, y la ruta `password-reset/reset` seguía atendida por la
 * clase del vendor (medido con `route:list`: mi clase quedaba en `.../request`), así que el arreglo no
 * corría nunca.
 */
class LimpiarCambioObligatorioAlDefinirLaContrasena
{
    public function handle(PasswordReset $event): void
    {
        /** @var \App\Models\User $user */
        $user = $event->user;

        if (! $user->force_password_change) {
            return;
        }

        $user->force_password_change = false;
        $user->save();

        SecurityEvent::record(
            $user,
            SecurityEvent::PASSWORD_CHANGED,
            SecurityEvent::CONTEXT_INITIAL
        );
    }
}
