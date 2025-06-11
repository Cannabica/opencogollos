<?php

namespace App\Telegram\Commands;

use App\Models\Tenant;
use Log;
use Telegram\Bot\Commands\Command;
use App\Telegram\Commands\ChecksTelegramExpiration;
use Telegram\Bot\Keyboard\Keyboard;
use Telegram\Bot\Keyboard\Button;
use Telegram\Bot\Commands\CommandBus;


class PlantsListCommand extends Command
{
    use ChecksTelegramExpiration;

    protected string $name = 'plantslist';
    protected string $description = 'Lista todas las plantas del tenant';

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
        
            $indoors = $tenant->indoors()->with(['plants.seedType'])->get();
            $totalPlants = $indoors->sum(fn($indoor) => $indoor->plants->where('state', '!=', 'muerta')->count());

            if ($totalPlants === 0) {
                $this->replyWithMessage([
                    'text' => 'ℹ️ No hay plantas registradas en este tenant.',
                    'parse_mode' => 'HTML'
                ]);
                return;
            }

            $message = "🌱 <b>Listado de Plantas</b> ($totalPlants)\n\n";
            
            foreach ($indoors as $indoor) {
                $activePlants = $indoor->plants->where('state', '!=', 'muerta');
                if ($activePlants->isEmpty()) {
                    continue;
                }

                $message .= "🏠 <b>" . htmlspecialchars($indoor->name, ENT_QUOTES, 'UTF-8') . "</b> (" . $activePlants->count() . " plantas)\n";
                
                // Group plants by state
                $plantsByState = $activePlants->groupBy('state');
                
                foreach ($plantsByState as $state => $plants) {
                    $message .= "➡️ <b>" . ucfirst($state) . "</b> (" . $plants->count() . ")\n";
                    foreach ($plants as $plant) {
                        $message .= "│  ├─ " . htmlspecialchars($plant->name, ENT_QUOTES, 'UTF-8');
                        if ($plant->seedType) {
                            $message .= " (Sema: " . htmlspecialchars($plant->seedType->name, ENT_QUOTES, 'UTF-8') . ")";
                        }
                        $message .= "\n";
                    }
                }
                $message .= "\n";
            }

            $message .= "✅ Total: $totalPlants plantas en " . $indoors->count() . " indoors\n\n";
            $message .= "Selecciona una planta para ver detalles:";

            // Create inline keyboard with plant buttons using InlineKeyboardButton objects
            $keyboard = Keyboard::make()->inline();
            $keyboardRows = [];

            $allPlants = [];
            foreach ($indoors as $indoor) {
                foreach ($indoor->plants->where('state', '!=', 'muerta') as $plant) {
                    Log::debug('Adding plant button', [
                        'plant_id'   => $plant->id,
                        'callback_data' => 'plantdetails:'.$plant->id,
                        'plant_name' => $plant->name
                    ]);
                    $allPlants[] = Keyboard::inlineButton([
                        'text'          => htmlspecialchars($plant->name, ENT_QUOTES, 'UTF-8'),
                        'callback_data' => 'plantdetails:'.$plant->id,
                    ]);
                }
            }

            // Split into groups of 2 for 2-column layout
            $buttonGroups = array_chunk($allPlants, 2);
            foreach ($buttonGroups as $group) {
                $keyboard->row($group);
            }

            $this->replyWithMessage([
                'text' => $message,
                'parse_mode' => 'HTML',
                'reply_markup' => $keyboard
            ]);

        } catch (\Exception $e) {
            $this->replyWithMessage([
                'text' => '❌ Error: ' . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8'),
                'parse_mode' => 'HTML'
            ]);
            Log::error('PlantsListCommand error: ' . $e->getMessage());
        }
    }
}