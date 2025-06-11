<?php

namespace App\Telegram\Commands;

use App\Models\Tenant;
use App\Models\Plant;
use Log;
use Telegram\Bot\Commands\Command;
use App\Telegram\Commands\ChecksTelegramExpiration;
use Telegram\Bot\Keyboard\Keyboard;

class ActionsListCommand extends Command
{
    use ChecksTelegramExpiration;

    protected string $name = 'acciones';
    protected string $description = 'Lista todas las acciones organizadas por indoor';

    public function handle()
    {
        try {
            log::debug('ActionsListCommand started', [
                'update_id' => $this->getUpdate()?->getUpdateId(),
                'user_id' => $this->getUpdate()->getMessage()->getFrom()->getId()
            ]);

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
        
            $recentActions = $tenant->actions()
                ->with(['indoor', 'action_type', 'plants'])
                ->latest()
                ->take(5)
                ->get()
                ->map(function ($action) {
                    // Ensure plants data is properly formatted
                    if (isset($action->data['plants'])) {
                        $action->plants = $action->plants->merge(
                            Plant::findMany($action->data['plants'])
                        )->unique();
                    }
                    return $action;
                });
 
            log::debug('ActionsListCommand executed', [
                'update_id' => $this->getUpdate()?->getUpdateId(),
                'user_id' => $telegramUserId,
                'tenant_id' => $existingAssociation->tenant_id,
                'actions_count' => $recentActions->count()
            ]);

            if ($recentActions->isEmpty()) {
                $this->replyWithMessage([
                    'text' => 'ℹ️ No hay acciones registradas en este tenant.',
                    'parse_mode' => 'HTML'
                ]);
                return;
            }

            $message = "⏱ <b>Últimas 5 Acciones</b>\n\n";
            
            foreach ($recentActions as $action) {
                $date = $action->action_date->format('d/m/Y H:i');
                $indoorName = htmlspecialchars($action->indoor->name ?? 'Sin Indoor', ENT_QUOTES, 'UTF-8');
                $message .= "├─ 🌱 <b> #". htmlspecialchars($action->id, ENT_QUOTES, 'UTF-8').' '. htmlspecialchars($action->action_type->name, ENT_QUOTES, 'UTF-8') . "</b>\n";
                $message .= "│   📋 " . mb_strimwidth(htmlspecialchars($action->detalle_accion, ENT_QUOTES, 'UTF-8'), 0, 40, "...") . "\n";
                if ($action->action_type_id == 5  ) {
                    if (isset($action->data['observation']['image'])){
                        $imageCount = is_array($action->data['observation']['image']) ? count($action->data['observation']['image']) : 1;
                        $message .= "│   📸 " . $imageCount . " foto(s)\n";
                    }
                }
                $message .= "│   🌿 " . $action->plants->count() . " plantas\n";
                $message .= "│   🗓️ $date\n";
                $message .= "│   🏠 Indoor: $indoorName\n";
                $message .= "\n";
            }

            $message .= "Selecciona una acción para ver más detalles.";


            log::debug('ActionsListCommand message prepared', [
                'message_length' => mb_strlen($message),
                'actions_count' => $recentActions->count()
            ]);



            // Create inline keyboard with the 5 recent actions
            $keyboard = Keyboard::make()->inline();
            $buttons = [];
            
            foreach ($recentActions as $action) {
                $buttons[] = Keyboard::inlineButton([
                    'text' => '#' .htmlspecialchars($action->id, ENT_QUOTES, 'UTF-8'). ' '. htmlspecialchars($action->action_type->name . ' - ' . $action->action_date->format('d/m H:i')),
                    'callback_data' => 'actiondetails:'.$action->id
                ]);
            }

            // Split into groups of 2 for 2-column layout
            $buttonGroups = array_chunk($buttons, 2);
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
            Log::error('ActionsListCommand error: ' . $e->getMessage());
        }
    }
}