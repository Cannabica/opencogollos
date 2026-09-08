<?php

namespace App\Services;

use App\Models\Action;
use App\Models\Indoor;
use Illuminate\Support\Facades\Log;
use Telegram\Bot\Laravel\Facades\Telegram;

class TelegramObservationService
{
    public static function processObservationDescription($update, $observationData, $descriptionText)
    {
        $message = $update->message;
        $chatId = $message->chat->id;
        $userId = $message->from->id;
        
        try {
            // Si el usuario escribe /skip, usar descripción vacía
            if (trim($descriptionText) === '/skip') {
                $descriptionText = '';
            }
            
            // Descargar y almacenar la(s) foto(s) si está(n) disponible(s)
            $imagePaths = [];
            $photoInfo = $observationData['photo_info'] ?? [];
            
            // Procesar álbumes completos o fotos individuales
            if (!empty($photoInfo['is_album']) && !empty($photoInfo['album_photos'])) {
                // Procesar todas las fotos del álbum
                foreach ($photoInfo['album_photos'] as $albumPhoto) {
                    $imagePath = \App\Services\TelegramPhotoService::downloadAndStorePhoto(
                        $albumPhoto['file_id'],
                        'observation'
                    );
                    if ($imagePath) {
                        $imagePaths[] = $imagePath;
                    }
                }
                Log::info('Álbum completo procesado en observación', [
                    'total_photos' => count($photoInfo['album_photos']),
                    'downloaded_photos' => count($imagePaths),
                    'media_group_id' => $photoInfo['media_group_id'] ?? null
                ]);
            } elseif (isset($photoInfo['file_id'])) {
                // Procesar foto individual
                $imagePath = \App\Services\TelegramPhotoService::downloadAndStorePhoto(
                    $photoInfo['file_id'],
                    'observation'
                );
                if ($imagePath) {
                    $imagePaths[] = $imagePath;
                }
            }

            // Crear acción de observación con foto
            $actionData = [
                'action_type_id' => 5, // Observación con foto
                'action_date' => now(),
                'indoor_id' => $observationData['indoor_id'],
                'tenant_id' => $observationData['tenant_id'] ?? null, // Usar null si no está definido
                'data' => [
                    'observation' => [
                        'comments' => $descriptionText,
                        'photo' => $observationData['photo_info'] ?? [],
                        'image' => $imagePaths // Usar array de imágenes para Filament
                    ]
                ]
            ];
            
            $action = Action::create($actionData);
            
            // Asociar plantas a la acción si se seleccionaron
            if (isset($observationData['selected_plants']) && !empty($observationData['selected_plants'])) {
                $action->plants()->attach($observationData['selected_plants']);
            }
            
            $indoor = Indoor::find($observationData['indoor_id']);
            
            // Enviar mensaje de confirmación
            $isAlbum = !empty($photoInfo['is_album']) && !empty($photoInfo['album_photos']);
            $responseText = "✅ *Observación " . ($isAlbum ? "con álbum" : "con foto") . " creada exitosamente*\n\n";
            $responseText .= "🏠 *Indoor:* " . htmlspecialchars($indoor->name, ENT_QUOTES, 'UTF-8') . "\n";
            $responseText .= "📝 *Descripción:* " . ($descriptionText ?: 'Sin descripción') . "\n";
            $responseText .= "🖼️ *" . ($isAlbum ? "Álbum" : "Foto") . ":* " . count($imagePaths) . " " . ($isAlbum ? "fotos adjuntadas" : "adjuntada") . "\n";
            $responseText .= "📅 *Fecha:* " . now()->format('d/m/Y H:i');
            
            Telegram::sendMessage([
                'chat_id' => $chatId,
                'text' => $responseText,
                'parse_mode' => 'Markdown'
            ]);
            
            // Limpiar cache
            cache()->forget('observation_step_' . $userId);
            
            Log::info('Observation action created from text', [
                'action_id' => $action->id,
                'indoor_id' => $observationData['indoor_id'],
                'user_id' => $userId,
                'description' => $descriptionText
            ]);
            
        } catch (\Exception $e) {
            Log::error('Error creating observation from description', [
                'error' => $e->getMessage(),
                'exception' => $e
            ]);
            
            Telegram::sendMessage([
                'chat_id' => $chatId,
                'text' => '❌ Error creando la observación. Por favor intenta nuevamente.',
                'parse_mode' => 'HTML'
            ]);
        }
    }
}