<?php

namespace App\Http\Controllers;

use App\Models\EmailChangeRequest;
use App\Models\SecurityEvent;
use App\Models\User;
use App\Notifications\EmailChangedNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;

/**
 * Confirma (o rechaza) un cambio de email pedido desde la cuenta.
 *
 * Es la segunda mitad del doble opt-in: el cambio NO se aplica cuando se pide, se aplica acá, cuando
 * alguien abre el link que llegó a la dirección nueva. Al aplicarse:
 *
 *   1. se registra en `security_events` (acción sobre la cuenta, no telemetría de uso),
 *   2. se avisa a la dirección VIEJA y a la NUEVA (a la vieja es lo que hace visible el abuso),
 *   3. se cierra la sesión: la credencial cambió, hay que volver a entrar con la nueva.
 */
class EmailChangeController extends Controller
{
    public function show(Request $request, string $token)
    {
        $solicitud = EmailChangeRequest::findByToken($token);

        if (! $solicitud || ! $solicitud->estaVigente()) {
            return redirect()
                ->route('filament.tenant.auth.login')
                ->with('status', 'El link de confirmación no es válido o ya venció. Volvé a pedir el cambio desde tu cuenta.');
        }

        /** @var User $user */
        $user = $solicitud->user;

        // La dirección nueva podría haberse ocupado entre el pedido y la confirmación.
        if (User::query()->where('email', $solicitud->new_email)->whereKeyNot($user->id)->exists()) {
            $solicitud->delete();

            return redirect()
                ->route('filament.tenant.auth.login')
                ->with('status', 'Esa dirección ya está en uso. El cambio no se aplicó y seguís entrando con tu email actual.');
        }

        $emailViejo = $user->email;
        $emailNuevo = $solicitud->new_email;

        $user->email = $emailNuevo;
        $user->save();

        // Si esta persona administra un grupo cuyo email de contacto ES la dirección vieja, el grupo
        // quedaría apuntando a una dirección que ya no existe (reportado por Frankie, 2026-09-27: "quedó
        // desvinculado, no hay usuario que tenga el correo del admin del tenant"). El contacto acompaña:
        // sigue siendo la misma persona.
        //
        // Se busca el grupo por id en vez de usar `$user->tenant`: la relación del modelo no está tipada
        // (PHPStan no la resuelve) y tiparla es una tarea aparte del modelo.
        $tenant = \App\Models\Tenant::find($user->tenant_id);

        if ($tenant && (int) $tenant->owner_user_id === (int) $user->id && $tenant->email === $emailViejo) {
            $tenant->update(['email' => $emailNuevo]);

            SecurityEvent::record($user, SecurityEvent::TENANT_EMAIL_CHANGED, 'acompanado');
        }

        $solicitud->update(['confirmed_at' => now()]);

        // Trazabilidad de seguridad (tabla propia, separada de la telemetría de uso).
        SecurityEvent::record($user, SecurityEvent::EMAIL_CHANGED, null);

        $this->avisar($user, $emailViejo, $emailNuevo);

        // La credencial cambió: la sesión que estaba abierta se abrió con la vieja.
        Auth::logout();
        if ($request->hasSession()) {
            $request->session()->invalidate();
            $request->session()->regenerateToken();
        }

        return redirect()
            ->route('filament.tenant.auth.login')
            ->with('status', 'Listo: tu email quedó actualizado. Entrá con la dirección nueva.');
    }

    /**
     * Avisa a las DOS direcciones. Si el aviso falla no se deshace nada: el cambio ya está aplicado y
     * registrado; lo que no puede pasar es que un error de mail deje la cuenta en un estado raro.
     */
    private function avisar(User $user, string $emailViejo, string $emailNuevo): void
    {
        foreach ([
            EmailChangedNotification::DESTINATION_OLD => $emailViejo,
            EmailChangedNotification::DESTINATION_NEW => $emailNuevo,
        ] as $destino => $direccion) {
            try {
                Notification::route('mail', $direccion)
                    ->notify(new EmailChangedNotification($emailViejo, $emailNuevo, (string) $destino, $user->name));
            } catch (\Throwable $e) {
                Log::error('No se pudo avisar del cambio de email', [
                    'user_id' => $user->id,
                    'destino' => $destino,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }
}
