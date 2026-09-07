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
        // Purga diaria del detalle de uso (telemetría server-side).
        $schedule->command('usage:prune')
            ->daily()
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

        require base_path('routes/console.php');
    }
}
