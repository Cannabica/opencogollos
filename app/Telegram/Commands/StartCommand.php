<?php

namespace App\Telegram\Commands;

use Telegram\Bot\Commands\Command;

class StartCommand extends Command
{
    protected string $name = 'start';
    protected string $description = 'Inicia la interacción con el bot';

    public function handle()
    {
        $this->replyWithMessage([
            'text' => 'hola',
        ]);
    }
}