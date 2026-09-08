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
        $otherTenantToken = 'c5h5IneI7prJEiEBiv2DrYV4ycnOPKXumJKOLPKx'; // Token de "tenant example"

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $otherTenantToken,
                'Accept' => 'application/json'
            ])->get('https://localhost:8443/api/plants');

            if ($response->status() === 403) {
                $this->replyWithMessage([
                    'text' => '✅ *Prueba exitosa*: Se bloqueó correctamente el acceso no autorizado (403 Forbidden)',
                    'parse_mode' => 'Markdown'
                ]);
            } else {
                $this->replyWithMessage([
                    'text' => '⚠️ *Resultado inesperado*: Código ' . $response->status() . ' en lugar de 403',
                    'parse_mode' => 'Markdown'
                ]);
            }
        } catch (\Exception $e) {
            $this->replyWithMessage([
                'text' => '❌ Error de conexión: ' . $e->getMessage(),
                'parse_mode' => 'Markdown'
            ]);
        }
    }
}