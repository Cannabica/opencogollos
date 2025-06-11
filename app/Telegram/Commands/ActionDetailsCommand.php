<?php

namespace App\Telegram\Commands;

use App\Models\Action;
use App\Models\Tenant;
use Log;
use Telegram\Bot\Commands\Command;
use App\Telegram\Commands\ChecksTelegramExpiration;

class ActionDetailsCommand extends Command
{
    use ChecksTelegramExpiration;

    protected string $name = 'actiondetails';
    protected string $pattern = '{action_id?:\d+}';
    protected string $description = 'Muestra detalles de una acción específica';

    public function handle()
    {
        $telegramUser = $this->getUpdate()->getMessage()->getFrom();
        $telegramUserId = $this->getUpdate()->getCallbackQuery()
            ? $this->getUpdate()->getCallbackQuery()->getFrom()->getId()
            : $telegramUser->getId();

        Log::info('ActionDetailsCommand triggered', [
            'telegram_user_id' => $telegramUserId,
        ]);

        // Validate tenant association
        $association = $this->checkTelegramAssociation($telegramUserId);
        if (!$association) {
            Log::warning('Unauthorized access attempt', ['telegram_user_id' => $telegramUserId]);
            return;
        }

        $actionId = $this->getUpdate()->getCallbackQuery()
            ? explode(':', $this->getUpdate()->getCallbackQuery()->getData())[1]
            : $this->argument('action_id');
            
        if (!$actionId || !is_numeric($actionId)) {
            $this->replyWithMessage([
                'text' => '❌ <b>Error:</b> Debes especificar un ID de acción válido',
                'parse_mode' => 'HTML'
            ]);
            return;
        }

        $action = Action::where('id', $actionId)
            ->with(['indoor', 'action_type', 'plants'])
            ->first();

        if (!$action) {
            $this->replyWithMessage([
                'text' => "❌ <b>Acción no encontrada</b>\nNo existe una acción con ID: $actionId",
                'parse_mode' => 'HTML',
            ]);
            Log::warning('Action not found', ['action_id' => $actionId]);
            return;
        }

        $indoorName = $action->indoor ? $action->indoor->name : 'Sin ubicación';
        $date = $action->action_date->format('d/m/Y H:i');
        $typeName = $action->action_type->name;

        $messageParts = [
            "== <b>⏱ #$actionId $typeName</b> ==",
            "",
            "<b>📅 Fecha:</b> $date",
            "<b>🏠 Indoor:</b> $indoorName",
            "",
            "<b>🌱 Plantas afectadas:</b>"
        ];

        foreach ($action->plants as $plant) {
            $messageParts[] = "├─ " . htmlspecialchars($plant->name, ENT_QUOTES, 'UTF-8');
            $messageParts[] = "│  └─ <i>Semilla:</i> " . ($plant->seedType?->name ?: 'No especificada');
            $messageParts[] = "│  └─ <i>Tipo:</i> " . ($plant->seedType?->seed_type ?: 'No especificado');
            $messageParts[] = "│  └─ <i>Estado planta:</i> " . ($plant->state ?: 'No especificado');
        }

        // Add action-specific details based on type
        $messageParts[] = "";
        $messageParts[] = "<b>📝 Detalles específicos:</b>";
        
        $data = $action->data;
        $images = []; // Initialize images array
        switch ($action->action_type_id) {
            case 1: // Irrigation
                $irrigationType = $data['irrigation']['irrigation_type'] ?? null;
                if ($irrigationType === 'liters') {
                    $messageParts[] = "├─ " . __('Tipo:') . " Riego por litros";
                    $messageParts[] = "│  └─ " . __('Cantidad:') . " " . ($data['irrigation']['liters'] ?? 0) . " litros";
                } else {
                    $messageParts[] = "├─ " . __('Tipo:') . " Riego por tiempo";
                    $messageParts[] = "│  └─ " . __('Duración:') . " " . ($data['irrigation']['timer'] ?? 0) . " minutos";
                }
                break;
                
            case 2: // Pruning
                $pruningTypes = $data['pruning']['pruning_type'] ?? [];
                $messageParts[] = "├─ " . __('Tipo de poda:') . " " . implode(', ', $pruningTypes);
                break;
                
            case 3: // Product Application
                $messageParts[] = "├─ " . __('Tipo de aplicación:') . " " . ($data['product_application']['application_type'] ?? 'No especificado');
                $messageParts[] = "│  └─ " . __('Observaciones:') . " " . ($data['product_application']['observation'] ?? 'Ninguna');
                break;
                
            case 4: // Transplant
                $messageParts[] = "├─ " . __('Nuevo tamaño de maceta:') . " " . ($data['transplant']['new_pot_size'] ?? 'No especificado');
                break;
                
            case 5: // Observation
                $messageParts[] = "├─ " . __('Comentarios:') . " " . ($data['observation']['comments'] ?? 'Ninguno');
                if (isset($data['observation']['image'])) {
                    $images = is_array($data['observation']['image'])
                        ? $data['observation']['image']
                        : [$data['observation']['image']];
                    
                    $messageParts[] = "│  └─ " . sprintf(__('Incluye %d foto(s) adjunta(s)'), count($images));
                }

                break;
                
            case 7: // Change of State
                $messageParts[] = "├─ " . __('Nuevo estado:') . " " . ($data['change_state']['state'] ?? 'No especificado');
                break;
            
            default:
                $messageParts[] = "├─ " . __('Tipo de acción no especificado');
                break;
        } // End of switch statement

        // Build complete message with creator info
        if ($action->user) {
            $messageParts[] = "";
            $messageParts[] = "<b>👤 Creada por:</b> " . $action->user->name;
        }

        $message = implode("\n", $messageParts);

        // Send message once at beginning
        $this->replyWithMessage([
            'text' => $message,
            'parse_mode' => 'HTML',
        ]);

        // Then send photos if they exist (only for observation type)
        if ($action->action_type_id === 5 && !empty($images)) {
            foreach ($images as $imagePath) {
                        log::debug('Observation photo found', [
                            'action_id' => $actionId,
                            'image' => $imagePath
                        ]);
                        
                        try {
                            $fullPath = (string) storage_path('app/public/' . $imagePath);
                            log::debug('Attempting to send observation photo', [
                                'action_id' => $actionId,
                                'image_path' => $fullPath
                            ]);
                            
                            $storagePath = storage_path('app/public/' . dirname($imagePath));
                            if (!is_dir($storagePath)) {
                                mkdir($storagePath, 0755, true);
                            }
                            
                            if (file_exists($fullPath)) {
                                $this->replyWithPhoto([
                                    'photo' => \Telegram\Bot\FileUpload\InputFile::create(
                                        $fullPath,
                                        basename($imagePath)
                                    ),
                                    'caption' => __('Foto de observación para acción #') . $actionId
                                ]);
                            } else {
                                Log::error('Observation photo not found', [
                                    'action_id' => $actionId,
                                    'image_path' => $fullPath,
                                    'expected_path' => 'storage/app/public/actions/'
                                ]);

                                $this->replyWithMessage([
                                    'text' => __('❌ Foto no encontrada: ') . $imagePath,
                                    'parse_mode' => 'HTML'
                                ]);
                            }
                        } catch (\Exception $e) {
                            Log::error('Failed to send observation photo', [
                                'action_id' => $actionId,
                                'error' => $e->getMessage(),
                                'image_path' => $imagePath
                            ]);
                        }
                    }
                }                

        Log::info('Action details sent', [
            'action_id' => $actionId,
            'tenant_id' => $association->tenant_id
        ]);
    }
}