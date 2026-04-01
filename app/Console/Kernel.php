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
        // $schedule->command('inspire')->hourly();
    }

    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');

        // Registrar comandos de Telegram
        $this->load(__DIR__.'/../../vendor/telegram-bot-sdk/src/Laravel/Console/Commands');

        // Registrar comandos personalizados de Telegram
        $this->load(__DIR__.'/Commands/Telegram');
        $this->commands([
            \App\Console\Commands\RunActionExecutionServer::class,
        ]);

        require base_path('routes/console.php');
    }
}
