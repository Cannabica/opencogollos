<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
        // Resumen proactivo del bot de administración al superadmin (1 vez por día).
        $schedule->command('admin:digest')
            ->dailyAt('09:00')
            ->withoutOverlapping()
            ->onOneServer();

        // Purga diaria del detalle de uso (telemetría server-side).
        $schedule->command('usage:prune')
            ->daily()
            ->withoutOverlapping()
            ->onOneServer();

        // Guardarraíl de la cola de trabajo: si supera los 50 jobs significa que nadie la está
        // drenando (falta el worker o se murió). El comando avisa y sale con código != 0, así el
        // fallo deja de ser silencioso: en producción eso significaba que las notificaciones
        // encoladas (mail de bienvenida al registrarse, claves del equipo) nunca se entregaban.
        $schedule->command('queue:monitor default --max=50')
            ->everyFiveMinutes()
            ->withoutOverlapping()
            ->onOneServer();
    }

    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');

        // Registrar comandos personalizados de Telegram (ya cargados en Commands por el load arriba, pero por si acaso)
        $this->load(__DIR__.'/Commands/Telegram');
        $this->load(__DIR__.'/Commands/Admin');

        require base_path('routes/console.php');
    }
}
