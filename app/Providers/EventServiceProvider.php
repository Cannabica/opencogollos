<?php

namespace App\Providers;

use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Listeners\SendEmailVerificationNotification;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Event;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event to listener mappings for the application.
     *
     * @var array<class-string, array<int, class-string>>
     */
    protected $listen = [
        Registered::class => [
            SendEmailVerificationNotification::class,
        ],
        \Illuminate\Auth\Events\Failed::class => [
            \App\Listeners\LogFailedLogin::class,
        ],
        // Definir la contraseña por el link de la invitación (o por "olvidé mi contraseña") CIERRA el
        // primer acceso. Se cuelga del evento del broker y no de la pantalla, para que valga sin importar
        // qué clase atienda la ruta (loop reportado por el dueño del repo, 2026-09-27).
        \Illuminate\Auth\Events\PasswordReset::class => [
            \App\Listeners\LimpiarCambioObligatorioAlDefinirLaContrasena::class,
        ],
    ];

    /**
     * Register any events for your application.
     */
    public function boot(): void
    {
        //
    }

    /**
     * Determine if events and listeners should be automatically discovered.
     */
    public function shouldDiscoverEvents(): bool
    {
        return false;
    }
}
