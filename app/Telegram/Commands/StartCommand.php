<?php

namespace App\Telegram\Commands;

use Telegram\Bot\Commands\Command;
use Telegram\Bot\Keyboard\Keyboard;

class StartCommand extends Command
{
    protected string $name = 'start';
    protected string $description = 'Inicia la interacción con el bot';

    public function handle()
    {
        $message = "¡Bienvenido al Bot de OpenIndoor! 🌱\n\n";
        $message .= "Por favor autentícate usando el comando /auth seguido de tu token de tenant.\n";
        $message .= "Ejemplo: /auth TU_TOKEN_AQUI";

        $this->replyWithMessage([
            'text' => $message,
            'parse_mode' => 'Markdown'
        ]);
    }
}