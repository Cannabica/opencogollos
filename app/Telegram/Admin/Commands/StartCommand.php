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

        // El nombre de marca es opcional: una instalación sin config('platform.brand_name')
        // se presenta solo con el nombre del producto.
        $brandName = config('platform.brand_name');

        $lines = [];
        $lines[] = '👋 ¡Buenas, admin!';
        $lines[] = '';
        $lines[] = filled($brandName)
            ? "Soy el bot de administración de {$brandName} / OpenIndoor."
            : 'Soy el bot de administración de OpenIndoor.';
        $lines[] = 'Escribí /help para ver los comandos disponibles.';

        $this->reply(implode("\n", $lines));
    }
}
