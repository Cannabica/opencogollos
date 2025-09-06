<?php

namespace App\Telegram\Commands;

use App\Models\Action;
use Log;
use Telegram\Bot\Commands\Command;
use Telegram\Bot\Keyboard\Keyboard;
use App\Telegram\Commands\ChecksTelegramExpiration;

class RepeatLastIrrigationCommand extends Command
{
    use ChecksTelegramExpiration;

    protected string $name = 'repetirriego';
    protected string $description = 'Repite el último riego realizado';

    public function handle()
    {
        try {
            $telegramUserId = $this->getUpdate()->getMessage()->getFrom()->getId();

            // Check for valid (non-expired) association
            $existingAssociation = $this->checkTelegramAssociation($telegramUserId);
            if (!$existingAssociation) {
                return;
            }

            // Get the last irrigation action with plants
            $lastIrrigation = Action::with('plants')->where('tenant_id', $existingAssociation->tenant_id)
                ->where('action_type_id', 1) // Irrigation action type
                ->latest()
                ->first();

            if (!$lastIrrigation) {
                $this->replyWithMessage([
                    'text' => '❌ No se encontraron riegos anteriores para repetir.',
                    'parse_mode' => 'HTML'
                ]);
                return;
            }

            // Prepare confirmation message with details
            $message = "💧 <b>Repetir último riego</b>\n\n";
            $message .= "🏠 <b>Indoor:</b> " . htmlspecialchars($lastIrrigation->indoor->name ?? 'N/A', ENT_QUOTES, 'UTF-8') . "\n";
            
            $irrigationData = $lastIrrigation->data['irrigation'] ?? [];
            $irrigationType = $irrigationData['irrigation_type'] ?? '';
            
            if ($irrigationType === 'liters') {
                $message .= "💦 <b>Tipo:</b> Litros\n";
                $message .= "📊 <b>Cantidad:</b> " . ($irrigationData['liters'] ?? '0') . " litros\n";
            } elseif ($irrigationType === 'timer') {
                $message .= "💦 <b>Tipo:</b> Temporizador\n";
                $message .= "⏰ <b>Tiempo:</b> " . ($irrigationData['timer'] ?? '0') . " minutos\n";
            }
            
            $message .= "🌱 <b>Plantas seleccionadas:</b> " . $lastIrrigation->plants->count() . "\n";
            
            // List the plants if there are not too many
            if ($lastIrrigation->plants->count() <= 10) {
                foreach ($lastIrrigation->plants as $plant) {
                    $message .= "   • " . htmlspecialchars($plant->name, ENT_QUOTES, 'UTF-8') . "\n";
                }
            } else {
                $message .= "   • " . $lastIrrigation->plants->count() . " plantas en total\n";
            }
            
            $message .= "\n¿Deseas repetir este riego?";

            // Create inline keyboard with confirmation buttons
            $keyboard = Keyboard::make()->inline();
            $keyboard->row([
                Keyboard::inlineButton([
                    'text' => '✅ Sí, repetir riego',
                    'callback_data' => 'repeat_irrigation:' . $lastIrrigation->id
                ]),
                Keyboard::inlineButton([
                    'text' => '❌ Cancelar',
                    'callback_data' => 'cancel_repeat_irrigation'
                ])
            ]);

            $this->replyWithMessage([
                'text' => $message,
                'reply_markup' => $keyboard,
                'parse_mode' => 'HTML'
            ]);

        } catch (\Exception $e) {
            Log::error('RepeatLastIrrigationCommand error', [
                'error' => $e->getMessage(),
                'exception' => $e
            ]);
            
            $this->replyWithMessage([
                'text' => '❌ Error al procesar el comando: ' . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8'),
                'parse_mode' => 'HTML'
            ]);
        }
    }
}