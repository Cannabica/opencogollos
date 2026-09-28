<?php

namespace App\Telegram\Commands;

use Telegram\Bot\Commands\Command;
use Illuminate\Support\Facades\Http;

class TestForbiddenCommand extends Command
{
    protected string $name = 'test403';
    protected string $description = 'Prueba acceso no autorizado a plantas de otro tenant';

    public function handle()
    {
        // El token de OTRO tenant y la URL de la API salen de config: nunca hardcodeados.
        // Hasta 2026-09 este comando tenía fijos un token real de un tenant de una instalación
        // concreta y una URL de desarrollo, los dos visibles en el repo público desde 2025-06
        // (hallazgo del guard de fugas, job `fugas` de ci.yml). Sin configurar, no adivina:
        // avisa y no llama a nada.
        $otherTenantToken = (string) config('telegram.test403_token', '');
        $apiBase = (string) config('telegram.test403_api_base', '');

        if ($otherTenantToken === '') {
            $this->replyWithMessage([
                'text' => '⚙️ *Falta configuración*: `TELEGRAM_TEST403_TOKEN` (token de OTRO tenant con el que se prueba el 403). '
                    . 'No se hardcodea: este repo es público.',
                'parse_mode' => 'Markdown',
            ]);

            return;
        }

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $otherTenantToken,
                'Accept' => 'application/json',
            ])->get(rtrim($apiBase, '/') . '/api/plants');

            if ($response->status() === 403) {
                $this->replyWithMessage([
                    'text' => '✅ *Prueba exitosa*: Se bloqueó correctamente el acceso no autorizado (403 Forbidden)',
                    'parse_mode' => 'Markdown',
                ]);
            } else {
                $this->replyWithMessage([
                    'text' => '⚠️ *Resultado inesperado*: Código ' . $response->status() . ' en lugar de 403',
                    'parse_mode' => 'Markdown',
                ]);
            }
        } catch (\Exception $e) {
            $this->replyWithMessage([
                'text' => '❌ Error de conexión: ' . $e->getMessage(),
                'parse_mode' => 'Markdown',
            ]);
        }
    }
}
