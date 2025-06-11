<?php

namespace App\Telegram\Commands;

use App\Models\Indoor;
use Log;
use Telegram\Bot\Commands\Command;
use App\Telegram\Commands\ChecksTelegramExpiration;

class IndoorDetailsCommand extends Command
{
    use ChecksTelegramExpiration;

    protected string $name = 'indoordetails';
    protected string $description = 'Muestra lista de indoors o detalles de uno específico';

    public function handle()
    {
        try {
            $telegramUserId = $this->getUpdate()->getMessage()->getFrom()->getId();
            
            // Check for valid (non-expired) association
            $existingAssociation = $this->checkTelegramAssociation($telegramUserId);
            if (!$existingAssociation) {
                $this->replyWithMessage([
                    'text' => '❌ Tu asociación ha expirado o no existe. Por favor autentícate nuevamente con /auth TU_TOKEN',
                    'parse_mode' => 'HTML'
                ]);
                return;
            }
            
            $indoors = Indoor::with(['plants', 'actions'])
                ->where('tenant_id', $existingAssociation->tenant_id)
                ->orderBy('name')
                ->get();

            // Debug logging
            Log::debug('IndoorDetailsCommand executed', [
                'update_id' => $this->getUpdate()?->getUpdateId(),
                'user_id' => $telegramUserId,
                'tenant_id' => $existingAssociation->tenant_id,
                'indoors_count' => $indoors->count()
            ]);

            if ($indoors->isEmpty()) {
                $this->replyWithMessage([
                    'text' => '❌ No hay indoors registrados para este tenant',
                    'parse_mode' => 'HTML'
                ]);
                return;
            }

            $message = "🏘️ <b>DETALLES DE INDOORS</b>\n\n";
            
            foreach ($indoors as $indoor) {
                Log::debug('Processing indoor', [
                    'indoor_id' => $indoor->id,
                    'indoor_name' => $indoor->name,
                    'plants_count' => $indoor->plants->count(),
                    'actions_count' => $indoor->actions->count()
                ]);

                $message .= "<strong><b>🏠" . htmlspecialchars($indoor->name, ENT_QUOTES, 'UTF-8') . "</b></strong>\n";
                $message .= "├─📏 <b>Dimensiones:</b> " . htmlspecialchars($indoor->large, ENT_QUOTES, 'UTF-8') . "×" .
                            htmlspecialchars($indoor->width, ENT_QUOTES, 'UTF-8') . "×" .
                            htmlspecialchars($indoor->height, ENT_QUOTES, 'UTF-8') . " cm\n";
                
                $message .= "├─⚙️ <b>Equipamiento</b>\n";
                $message .= "│    ├─ Ventiladores: " . (is_array($indoor->fans) ? count($indoor->fans) : 0) . "\n";
                $message .= "│    ├─💡 Lámparas: " . (is_array($indoor->lamps) ? count($indoor->lamps) : 0) . "\n";
                if (is_array($indoor->lamps) && count($indoor->lamps) > 0) {
                    foreach ($indoor->lamps as $lamp) {
                        $message .= "│    │    ├─ Potencia: " . htmlspecialchars($lamp['power'], ENT_QUOTES, 'UTF-8') . "W\n";
                        $message .= "│    │    └─ Tecnología: " . htmlspecialchars($lamp['technology'], ENT_QUOTES, 'UTF-8') . "\n";
                    }
                }
                $message .= "│    ├─ Higrómetro: " . ($indoor->hygrometer ? '✅' : '❌') . "\n";
                $message .= "│    └─ Humidificador: " . ($indoor->humidifier ? '✅' : '❌') . "\n";
                
                $message .= "⏰ <b>Programación</b>\n";
                $message .= "├─ Apertura por " . ($indoor->scheduled_time ?? 'No configurada') . " min. \n";
                $message .= "├─ Veces x día: " . ($indoor->times_a_day ?? 'No configurado') . "\n";
                $message .= "├─ Picos por planta: " . ($indoor->peak_quantity  ) . "\n";
                $message .= "└─ Días: " . (is_array($indoor->scheduled_days) ? implode(', ', $indoor->scheduled_days) : 'No configurados') . "\n";
                
                $message .= "🌱 <b>Plantas:</b> " . $indoor->plants->count() . "\n";
                $message .= "📝 <b>Acciones:</b> " . $indoor->actions->count() . "\n\n";
            }

            $this->replyWithMessage([
                'text' => $message,
                'parse_mode' => 'HTML'
            ]);

        } catch (\Exception $e) {
            $this->replyWithMessage([
                'text' => '❌ Error: ' . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8'),
                'parse_mode' => 'HTML'
            ]);
        }
    }
}