<?php

namespace App\Telegram\Commands;

use Telegram\Bot\Commands\Command;

class StartCommand extends Command
{
    protected string $name = 'start';
    protected string $description = 'Inicia la interacción con el bot';

    public function handle()
    {
        $siteHost = $this->displayHost(config('platform.site_url'));
        $platformUrl = $this->displayUrl(config('platform.platform_url'));
        $statusUrl = config('platform.status_page_url');
        $discordUrl = config('platform.community.discord_url');

        // El texto se arma por líneas: cada bloque que dependa de una instalación
        // concreta (dominio propio, monitor, comunidad) se OMITE si su key está
        // en null. Una instalación sin config ve solo el instructivo genérico.
        $lines = [];
        $lines[] = '👥 Buenas!';
        $lines[] = '';

        $lines[] = $siteHost
            ? "Con este bot vas a poder interactuar con tu cuenta en {$siteHost} cargar o consultar acciones e info sobre tus plantas e indoors"
            : 'Con este bot vas a poder interactuar con tu cuenta cargar o consultar acciones e info sobre tus plantas e indoors';
        $lines[] = '';

        $lines[] = $platformUrl
            ? "vas a necesitar autenticarte para eso, dentro de {$platformUrl}/tenant en el menu selecciona \"Mi grupo\", en la parte inferior de la pagina vas a poder crear un token para autenticarte utilizando el comando `/auth MITOKEN`"
            : 'vas a necesitar autenticarte para eso, desde el menu "Mi grupo" de tu cuenta vas a poder crear un token para autenticarte utilizando el comando `/auth MITOKEN`';
        $lines[] = '';

        $lines[] = 'después de haberte autenticado te recomiendo que leas /help o ya pruebes con /repetirriego o manda fotos de tus plantas al chat para registrarlas como una observación.';

        if (filled($statusUrl)) {
            $lines[] = '';
            $lines[] = '👾 status page:';
            $lines[] = $statusUrl;
        }

        if (filled($discordUrl)) {
            $lines[] = '';
            $lines[] = '🌐 server de discord:';
            $lines[] = $discordUrl;
        }

        $this->replyWithMessage([
            'text' => implode("\n", $lines),
        ]);
    }

    /**
     * Host "mostrable" de un URL en prosa (sin esquema). null si no hay config.
     */
    private function displayHost(?string $url): ?string
    {
        if (blank($url)) {
            return null;
        }

        return parse_url($url, PHP_URL_HOST) ?: preg_replace('#^https?://#i', '', rtrim($url, '/'));
    }

    /**
     * URL "mostrable" en prosa (sin esquema ni barra final). null si no hay config.
     */
    private function displayUrl(?string $url): ?string
    {
        if (blank($url)) {
            return null;
        }

        return rtrim(preg_replace('#^https?://#i', '', $url), '/');
    }
}
