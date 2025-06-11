<?php

namespace App\Telegram\Commands;

use App\Models\Tenant;
use Log;
use Telegram\Bot\Commands\Command;
use App\Telegram\Commands\ChecksTelegramExpiration;
use App\Models\Seed;
use Telegram\Bot\Keyboard\Keyboard;
use Telegram\Bot\Keyboard\Button;
use Telegram\Bot\Commands\CommandBus;

class SeedsListCommand extends Command
{
    use ChecksTelegramExpiration;

    protected string $name = 'seedslist';
    protected string $description = 'Lista todas las semillas del tenant';

    public function getCommandBus(): CommandBus
    {
        return $this->telegram->getCommandBus();
    }

    public function handle()
    {
        try {
            $telegramUserId = $this->getUpdate()->getMessage()->getFrom()->getId();
            
            // Check for valid (non-expired) association
            $existingAssociation = $this->checkTelegramAssociation($telegramUserId);
            if (!$existingAssociation) {
                $this->replyWithMessage([
                    'text' => '🤔 Porque no estas asociado a ningún grupo de trabajo? e.e \n\n',
                    'parse_mode' => 'HTML'
                ]);
                return;
            }

            $tenant = Tenant::find($existingAssociation->tenant_id);

            if (!$tenant) {
                $this->replyWithMessage([
                    'text' => '❌ Primero debes autenticarte con /auth TU_TOKEN',
                    'parse_mode' => 'HTML'
                ]);
                return;
            }
        
            $seeds = Seed::where('tenant_id', $tenant->id)->get();
            $totalSeeds = $seeds->count();

            if ($totalSeeds === 0) {
                $this->replyWithMessage([
                    'text' => 'ℹ️ No hay semillas registradas en este tenant.',
                    'parse_mode' => 'HTML'
                ]);
                return;
            }

            $message = "🌱 <b>Listado de Semillas</b> ($totalSeeds)\n\n";
            
            foreach ($seeds as $seed) {
                $message .= "🔹 <b>" . htmlspecialchars($seed->name, ENT_QUOTES, 'UTF-8') . "</b>\n";
                $message .= "│  ├─ Tipo: " . htmlspecialchars($seed->seed_type, ENT_QUOTES, 'UTF-8') . "\n";
                $message .= "│  ├─ THC: " . $seed->ratio_thc . "%\n";
                $message .= "│  ├─ CBD: " . $seed->ratio_cbd . "%\n";
                $message .= "│  ├─ Floración: " . $seed->flowering_time . " semanas\n";
                $message .= "│  ├─ Proveedor: " . htmlspecialchars($seed->provider, ENT_QUOTES, 'UTF-8') . "\n";
                $message .= "│  └─ INASE: " . ($seed->aprobado_inase ? '✅ Aprobado' : '❌ No aprobado') . "\n\n";
            }

            $message .= "✅ Total: $totalSeeds semillas\n\n";

            $this->replyWithMessage([
                'text' => $message,
                'parse_mode' => 'HTML'
            ]);

        } catch (\Exception $e) {
            $this->replyWithMessage([
                'text' => '❌ Error: ' . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8'),
                'parse_mode' => 'HTML'
            ]);
            Log::error('SeedsListCommand error: ' . $e->getMessage());
        }
    }
}