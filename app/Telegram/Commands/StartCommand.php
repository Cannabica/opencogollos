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
            'text' => "👥 Buenas!

Con este bot vas a poder interactuar con tu cuenta en cannabica.ar cargar o consultar acciones e info sobre tus plantas e indoors

vas a necesitar autenticarte para eso, dentro de plataforma.cannabica.ar/tenant en el menu selecciona \"Mi grupo\", en la parte inferior de la pagina vas a poder crear un token para autenticarte utilizando el comando `/auth MITOKEN`

después de haberte autenticado te recomiendo que leas /help o ya pruebes con /repetirriego o manda fotos de tus plantas al chat para registrarlas como una observación.

👾 status page:
https://status.cannabica.ar/

🌐 server de discord:
https://discord.com/invite/jN9Tje3eJe",
        ]);
    }
}