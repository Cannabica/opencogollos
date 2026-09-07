<?php

namespace App\Telegram\Admin\Commands;

class StartCommand extends AdminCommand
{
    protected string $name = 'start';

    protected string $description = 'Inicia la interacción con el bot de administración';

    public function handle()
    {
        if (! $this->ensureAuthorized()) {
            return;
        }

        $this->reply(
            "👋 ¡Buenas, admin!\n\n"
            . "Soy el bot de administración de Cannabica / OpenIndoor.\n"
            . "Escribí /help para ver los comandos disponibles."
        );
    }
}
