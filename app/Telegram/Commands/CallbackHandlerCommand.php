<?php

namespace App\Telegram\Commands;

use App\Models\Indoor;
use App\Models\Plant;
use App\Models\Action;
use App\Models\ActionType;
use Telegram\Bot\Commands\Command;
use Telegram\Bot\Keyboard\Keyboard;
use Illuminate\Support\Facades\Log;
use App\Telegram\Commands\ChecksTelegramExpiration;
use Telegram\Bot\Laravel\Facades\Telegram;

class CallbackHandlerCommand extends Command
{
    use ChecksTelegramExpiration;

    protected string $name = 'callback';
    protected string $description = 'Maneja callback queries del teclado inline';

    public function handle()
    {
        try {
            $update = $this->getUpdate();
            
            if ($update->has('callback_query')) {
                $callbackQuery = $update->callback_query;
                $data = $callbackQuery->data;
                $message = $callbackQuery->message;
                $chatId = $message->chat->id;
                $messageId = $message->message_id;
                $telegramUserId = $callbackQuery->from->id;

                Log::debug('Callback received', [
                    'callback_data' => $data,
                    'chat_id' => $chatId,
                    'message_id' => $messageId,
                    'user_id' => $telegramUserId
                ]);

                // Check authentication first
                $existingAssociation = $this->checkTelegramAssociation($telegramUserId);
                if (!$existingAssociation) {
                    return;
                }

                switch (true) {
                    case strpos($data, 'select_indoor:') === 0:
                        $this->handleIndoorSelection($callbackQuery, $data, $existingAssociation);
                        break;
                    
                    case strpos($data, 'select_plants:') === 0:
                        $this->handlePlantSelection($callbackQuery, $data, $existingAssociation);
                        break;
                    
                    case strpos($data, 'plant_page:') === 0:
                        $this->handlePlantPageNavigation($callbackQuery, $data, $existingAssociation);
                        break;
                    
                    case strpos($data, 'plants_page:') === 0:
                        $this->handlePlantsPageNavigation($callbackQuery, $data, $existingAssociation);
                        break;
                    
                    case strpos($data, 'confirm_observation:') === 0:
                        $this->handleObservationConfirmation($callbackQuery, $data, $existingAssociation);
                        break;
                    
                    case strpos($data, 'repeat_irrigation:') === 0:
                        $this->handleRepeatIrrigation($callbackQuery, $data, $existingAssociation);
                        break;
                    
                    case $data === 'cancel_photo':
                        $this->handleCancelPhoto($callbackQuery);
                        break;
                    
                    case $data === 'cancel_repeat_irrigation':
                        $this->handleCancelRepeatIrrigation($callbackQuery);
                        break;
                    
                    default:
                        $this->telegram->answerCallbackQuery([
                            'callback_query_id' => $callbackQuery->id,
                            'text' => '❌ Acción no reconocida'
                        ]);
                        break;
                }
            }

        } catch (\Exception $e) {
            Log::error('CallbackHandlerCommand error', [
                'error' => $e->getMessage(),
                'exception' => $e
            ]);
            
            if (isset($callbackQuery)) {
                $this->telegram->answerCallbackQuery([
                    'callback_query_id' => $callbackQuery->id,
                    'text' => '❌ Error procesando la acción'
                ]);
            }
        }
    }

    protected function handleIndoorSelection($callbackQuery, $data, $association)
    {
        $parts = explode(':', $data);
        $indoorId = end($parts);
        
        // Obtener plantas no muertas del indoor
        $plants = Plant::where('indoor_id', $indoorId)
            ->where('state', '!=', 'muerta')
            ->orderBy('name')
            ->get();

        $indoor = Indoor::find($indoorId);
        
        // Recuperar información de la foto usando el message_id del callback
        // El mensaje original de la foto debería ser el mensaje al que responde este callback
        $originalMessageId = $callbackQuery->message->reply_to_message->message_id ?? null;
        
        if (!$originalMessageId) {
            Log::warning('No reply_to_message found in callback', [
                'callback_message_id' => $callbackQuery->message->message_id,
                'user_id' => $callbackQuery->from->id
            ]);
            Telegram::answerCallbackQuery([
                'callback_query_id' => $callbackQuery->id,
                'text' => '❌ No se pudo encontrar la foto original. Por favor, envía la foto nuevamente.',
                'show_alert' => true
            ]);
            return;
        }
        
        $photoCacheKey = 'telegram_photo_' . $callbackQuery->from->id . '_' . $originalMessageId;
        $photoInfo = cache()->get($photoCacheKey);
        
        if (!$photoInfo) {
            Log::warning('Photo info not found in cache', [
                'cache_key' => $photoCacheKey,
                'user_id' => $callbackQuery->from->id,
                'original_message_id' => $originalMessageId
            ]);
            Telegram::answerCallbackQuery([
                'callback_query_id' => $callbackQuery->id,
                'text' => '❌ La información de la foto ha expirado. Por favor, envía la foto nuevamente.',
                'show_alert' => true
            ]);
            return;
        }
        
        // Si es un álbum, obtener todas las fotos del álbum
        if (!empty($photoInfo['is_album']) && !empty($photoInfo['album_photos_key'])) {
            $albumPhotos = cache()->get($photoInfo['album_photos_key'], []);
            Log::debug('Álbum completo obtenido para procesamiento', [
                'media_group_id' => $photoInfo['media_group_id'],
                'total_photos_in_album' => count($albumPhotos),
                'photo_ids' => array_map(function($p) { return $p['file_id']; }, $albumPhotos)
            ]);
            
            // Reemplazar la foto individual con información del álbum completo
            $photoInfo['album_photos'] = $albumPhotos;
            $photoInfo['total_album_photos'] = count($albumPhotos);
        }

        // Guardar información para el siguiente paso
        $nextStepData = [
            'indoor_id' => $indoorId,
            'tenant_id' => $association->tenant_id,
            'photo_info' => $photoInfo,
            'plants_count' => $plants->count(),
            'step' => 'select_plants'
        ];
        
        cache()->put('observation_step_' . $callbackQuery->from->id, $nextStepData, now()->addHours(24));

        $this->telegram->answerCallbackQuery([
            'callback_query_id' => $callbackQuery->id,
            'text' => '✅ Indoor seleccionado'
        ]);

        // Crear teclado para selección de plantas
        $keyboard = Keyboard::make()->inline();
        
        // Botón para "Todas las plantas"
        $keyboard->row([
            Keyboard::inlineButton([
                'text' => '🌿 Todas las plantas',
                'callback_data' => 'select_plants:all'
            ])
        ]);

        // Agrupar plantas en páginas de 8 (4 filas de 2 columnas)
        $plantPages = array_chunk($plants->toArray(), 8);
        $currentPage = 0;
        
        // Organizar plantas en 2 columnas
        $plantButtons = [];
        foreach ($plantPages[$currentPage] as $plant) {
            $plantButtons[] = Keyboard::inlineButton([
                'text' => '🌱 ' . htmlspecialchars($plant['name'], ENT_QUOTES, 'UTF-8'),
                'callback_data' => 'select_plants:' . $plant['id']
            ]);
        }
        
        // Dividir en grupos de 2 para 2 columnas
        $buttonGroups = array_chunk($plantButtons, 2);
        foreach ($buttonGroups as $group) {
            $keyboard->row($group);
        }

        // Botones de navegación si hay múltiples páginas
        if (count($plantPages) > 1) {
            $navButtons = [];
            if ($currentPage > 0) {
                $navButtons[] = Keyboard::inlineButton([
                    'text' => '⬅️ Anterior',
                    'callback_data' => 'plant_page:' . ($currentPage - 1)
                ]);
            }
            if ($currentPage < count($plantPages) - 1) {
                $navButtons[] = Keyboard::inlineButton([
                    'text' => 'Siguiente ➡️',
                    'callback_data' => 'plant_page:' . ($currentPage + 1)
                ]);
            }
            if (!empty($navButtons)) {
                $keyboard->row($navButtons);
            }
        }

        $messageText = "🏠 *Indoor seleccionado:* " . htmlspecialchars($indoor->name, ENT_QUOTES, 'UTF-8') . "\n";
        $messageText .= "🌱 *Plantas activas:* " . $plants->count() . "\n\n";
        $messageText .= "📋 *Selecciona las plantas para esta observación:*\n";
        $messageText .= "_Puedes seleccionar 'Todas las plantas' o selecciona la planta individualmente. Actualmente el bot no soporta selección múltiple._";

        $this->telegram->editMessageText([
            'chat_id' => $callbackQuery->message->chat->id,
            'message_id' => $callbackQuery->message->message_id,
            'text' => $messageText,
            'reply_markup' => $keyboard,
            'parse_mode' => 'Markdown'
        ]);

        Log::info('Indoor selected for observation', [
            'indoor_id' => $indoorId,
            'indoor_name' => $indoor->name,
            'user_id' => $callbackQuery->from->id,
            'active_plants' => $plants->count(),
            'photo_info' => $photoInfo
        ]);
    }

    protected function handleObservationConfirmation($callbackQuery, $data, $association)
    {
        $parts = explode(':', $data);
        $indoorId = $parts[1];
        $description = urldecode($parts[2] ?? '');

        // Obtener información de observación del cache
        $observationData = cache()->get('observation_step_' . $callbackQuery->from->id);
        if (!$observationData || !isset($observationData['photo_info'])) {
            Log::error('Observation data not found in cache', [
                'user_id' => $callbackQuery->from->id
            ]);
            $this->telegram->answerCallbackQuery([
                'callback_query_id' => $callbackQuery->id,
                'text' => '❌ Error: No se encontró la información de la observación',
                'show_alert' => true
            ]);
            return;
        }

        $photoInfo = $observationData['photo_info'];
        
        // Crear acción de observación con foto
        // Descargar y almacenar la(s) foto(s)
        $imagePaths = [];
        $photoFileIds = [];

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
                    $photoFileIds[] = $albumPhoto['file_id'];
                }
            }
            Log::info('Álbum completo procesado', [
                'total_photos' => count($photoInfo['album_photos']),
                'downloaded_photos' => count($imagePaths),
                'media_group_id' => $photoInfo['media_group_id']
            ]);
        } else {
            // Procesar foto individual
            $imagePath = \App\Services\TelegramPhotoService::downloadAndStorePhoto(
                $photoInfo['file_id'],
                'observation'
            );
            if ($imagePath) {
                $imagePaths[] = $imagePath;
                $photoFileIds[] = $photoInfo['file_id'];
            }
        }

        $action = Action::create([
            'action_type_id' => 5, // Observación con foto
            'action_date' => now(),
            'indoor_id' => $indoorId,
            'tenant_id' => $association->tenant_id,
            'data' => [
                'observation' => [
                    'comments' => $description,
                    'photo' => [
                        'telegram_file_ids' => $photoFileIds,
                        'timestamp' => now()->toISOString(),
                        'is_album' => !empty($photoInfo['is_album']),
                        'media_group_id' => $photoInfo['media_group_id'] ?? null,
                        'total_photos' => count($photoFileIds)
                    ],
                    'image' => $imagePaths // Campo requerido por el formulario Filament
                ]
            ]
        ]);

        $this->telegram->answerCallbackQuery([
            'callback_query_id' => $callbackQuery->id,
            'text' => '✅ Observación creada'
        ]);

        $messageText = "✅ *Observación " . (!empty($photoInfo['is_album']) ? "con álbum" : "con foto") . " creada exitosamente*\n\n";
        $messageText .= "🏠 *Indoor:* " . Indoor::find($indoorId)->name . "\n";
        $messageText .= "📝 *Descripción:* " . ($description ?: 'Sin descripción') . "\n";
        $messageText .= "🖼️ *" . (!empty($photoInfo['is_album']) ? "Álbum" : "Foto") . ":* " . count($photoFileIds) . " " . (!empty($photoInfo['is_album']) ? "fotos adjuntadas" : "adjuntada") . "\n";
        $messageText .= "📅 *Fecha:* " . now()->format('d/m/Y H:i');

        $this->telegram->editMessageText([
            'chat_id' => $callbackQuery->message->chat->id,
            'message_id' => $callbackQuery->message->message_id,
            'text' => $messageText,
            'parse_mode' => 'Markdown'
        ]);

        Log::info('Observation action created', [
            'action_id' => $action->id,
            'indoor_id' => $indoorId,
            'user_id' => $callbackQuery->from->id,
            'description' => $description
        ]);
    }

    protected function handleCancelPhoto($callbackQuery)
    {
        // Limpiar cache de foto
        $photoCacheKey = 'telegram_photo_' . $callbackQuery->from->id . '_' . ($callbackQuery->message->reply_to_message->message_id ?? 'unknown');
        cache()->forget($photoCacheKey);
        cache()->forget('observation_step_' . $callbackQuery->from->id);

        $this->telegram->answerCallbackQuery([
            'callback_query_id' => $callbackQuery->id,
            'text' => '❌ Operación cancelada'
        ]);

        $this->telegram->editMessageText([
            'chat_id' => $callbackQuery->message->chat->id,
            'message_id' => $callbackQuery->message->message_id,
            'text' => '❌ Operación cancelada. La foto no fue asociada a ningún indoor.',
            'parse_mode' => 'HTML'
        ]);

        Log::info('Photo association cancelled', [
            'user_id' => $callbackQuery->from->id,
            'message_id' => $callbackQuery->message->message_id
        ]);
    }

    protected function handlePlantSelection($callbackQuery, $data, $association)
    {
        $parts = explode(':', $data);
        $plantSelection = $parts[1];
        
        $this->telegram->answerCallbackQuery([
            'callback_query_id' => $callbackQuery->id,
            'text' => '✅ Plantas seleccionadas'
        ]);

        // Obtener información de observación actual
        $observationData = cache()->get('observation_step_' . $callbackQuery->from->id);
        $indoorId = $observationData['indoor_id'] ?? null;

        // Obtener información de la planta si se seleccionó una específica
        $plantName = 'Todas las plantas';
        $selectedPlantIds = [];

        if ($plantSelection !== 'all') {
            $plant = Plant::find($plantSelection);
            $plantName = $plant ? htmlspecialchars($plant->name, ENT_QUOTES, 'UTF-8') : 'Planta específica';
            $selectedPlantIds = [$plantSelection];
        } else {
            // Obtener todas las plantas activas del indoor
            if ($indoorId) {
                $plants = Plant::where('indoor_id', $indoorId)
                    ->where('state', '!=', 'muerta')
                    ->orderBy('name')
                    ->get();
                
                $selectedPlantIds = $plants->pluck('id')->toArray();
                $plantName = 'Todas las plantas (' . count($selectedPlantIds) . ')';
            }
        }

        // Actualizar datos de observación con plantas seleccionadas
        $observationData['selected_plants'] = $selectedPlantIds;
        $observationData['step'] = 'ask_description';
        cache()->put('observation_step_' . $callbackQuery->from->id, $observationData, now()->addHours(24));

        $messageText = "🌿 *Plantas seleccionadas:* " . $plantName . "\n\n";
        $messageText .= "📝 *¿Quieres agregar un texto descriptivo para esta observación?*\n";
        $messageText .= "_Responde con el texto que quieras agregar o escribe /skip para omitir._";

        $this->telegram->editMessageText([
            'chat_id' => $callbackQuery->message->chat->id,
            'message_id' => $callbackQuery->message->message_id,
            'text' => $messageText,
            'parse_mode' => 'Markdown'
        ]);

        Log::info('Plants selected for observation', [
            'user_id' => $callbackQuery->from->id,
            'plant_selection' => $plantSelection,
            'plant_name' => $plantName,
            'selected_plant_ids' => $selectedPlantIds,
            'indoor_id' => $indoorId
        ]);
    }

    protected function handlePlantPageNavigation($callbackQuery, $data, $association)
    {
        // Extraer el número de página del callback_data
        $parts = explode(':', $data);
        $requestedPage = (int) end($parts);
        
        $this->telegram->answerCallbackQuery([
            'callback_query_id' => $callbackQuery->id,
            'text' => '🔃 Cambiando página...'
        ]);

        // Obtener información del mensaje actual para reconstruir el contexto
        $currentMessage = $callbackQuery->message;
        $currentText = $currentMessage->text;
        
        // Extraer el indoor_id del texto del mensaje o del cache
        $indoorId = $this->extractIndoorIdFromMessage($currentText);
        
        if (!$indoorId) {
            // Intentar obtener del cache como fallback
            $observationData = cache()->get('observation_step_' . $callbackQuery->from->id);
            $indoorId = $observationData['indoor_id'] ?? null;
        }

        if (!$indoorId) {
            Log::error('Could not determine indoor_id for plant page navigation', [
                'user_id' => $callbackQuery->from->id,
                'callback_data' => $data,
                'message_text' => $currentText
            ]);
            
            $this->telegram->answerCallbackQuery([
                'callback_query_id' => $callbackQuery->id,
                'text' => '❌ Error: No se pudo determinar el indoor',
                'show_alert' => true
            ]);
            return;
        }

        // Obtener plantas no muertas del indoor
        $plants = Plant::where('indoor_id', $indoorId)
            ->where('state', '!=', 'muerta')
            ->orderBy('name')
            ->get();

        $indoor = Indoor::find($indoorId);
        
        // Crear teclado para selección de plantas
        $keyboard = Keyboard::make()->inline();
        
        // Botón para "Todas las plantas"
        $keyboard->row([
            Keyboard::inlineButton([
                'text' => '🌿 Todas las plantas',
                'callback_data' => 'select_plants:all'
            ])
        ]);

        // Agrupar plantas en páginas de 8 (4 filas de 2 columnas)
        $plantPages = array_chunk($plants->toArray(), 8);
        
        // Validar que la página solicitada existe
        if ($requestedPage < 0 || $requestedPage >= count($plantPages)) {
            $requestedPage = 0; // Volver a la primera página si es inválida
        }

        // Organizar plantas en 2 columnas para la página solicitada
        $plantButtons = [];
        foreach ($plantPages[$requestedPage] as $plant) {
            $plantButtons[] = Keyboard::inlineButton([
                'text' => '🌱 ' . htmlspecialchars($plant['name'], ENT_QUOTES, 'UTF-8'),
                'callback_data' => 'select_plants:' . $plant['id']
            ]);
        }
        
        // Dividir en grupos de 2 para 2 columnas
        $buttonGroups = array_chunk($plantButtons, 2);
        foreach ($buttonGroups as $group) {
            $keyboard->row($group);
        }

        // Botones de navegación si hay múltiples páginas
        if (count($plantPages) > 1) {
            $navButtons = [];
            if ($requestedPage > 0) {
                $navButtons[] = Keyboard::inlineButton([
                    'text' => '⬅️ Anterior',
                    'callback_data' => 'plant_page:' . ($requestedPage - 1)
                ]);
            }
            if ($requestedPage < count($plantPages) - 1) {
                $navButtons[] = Keyboard::inlineButton([
                    'text' => 'Siguiente ➡️',
                    'callback_data' => 'plant_page:' . ($requestedPage + 1)
                ]);
            }
            if (!empty($navButtons)) {
                $keyboard->row($navButtons);
            }
        }

        // Actualizar el mensaje con la nueva página
        $this->telegram->editMessageReplyMarkup([
            'chat_id' => $currentMessage->chat->id,
            'message_id' => $currentMessage->message_id,
            'reply_markup' => $keyboard
        ]);

        Log::info('Plant page navigation completed', [
            'user_id' => $callbackQuery->from->id,
            'callback_data' => $data,
            'requested_page' => $requestedPage,
            'total_pages' => count($plantPages),
            'indoor_id' => $indoorId
        ]);
    }
    
    protected function extractIndoorIdFromMessage($messageText)
    {
        // Buscar el indoor_id en el texto del mensaje
        // El formato esperado es: "🏠 *Indoor seleccionado:* Nombre del Indoor"
        // Podemos buscar el nombre del indoor y luego obtener su ID
        
        if (preg_match('/🏠 \*Indoor seleccionado:\* (.+?)(?:\n|$)/', $messageText, $matches)) {
            $indoorName = trim($matches[1]);
            
            // Buscar el indoor por nombre
            $indoor = Indoor::where('name', $indoorName)->first();
            
            if ($indoor) {
                return $indoor->id;
            }
        }
        
        // También intentar buscar por ID si está almacenado en cache
        // Esta función es un helper, la lógica principal está en handlePlantPageNavigation
        return null; // Devolver null para que se use el cache como fallback
    }

    protected function getPhotoFileId($telegramUserId)
    {
        // Implementar lógica para obtener el file_id de la foto almacenada
        // Esto debería recuperarse del cache basado en el usuario
        $photoCacheKey = 'telegram_photo_' . $telegramUserId . '_*';
        // Buscar todas las keys que coincidan y obtener la más reciente
        // Por simplicidad, asumimos que hay una única foto en cache para el usuario
        $keys = cache()->getStore()->getPrefix() . 'telegram_photo_' . $telegramUserId . '_*';
        // Esta es una implementación simplificada
        return 'file_id_placeholder';
    }

    protected function handlePlantsPageNavigation($callbackQuery, $data, $association)
    {
        // Extraer el número de página del callback_data
        $parts = explode(':', $data);
        $requestedPage = (int) end($parts);
        
        $this->telegram->answerCallbackQuery([
            'callback_query_id' => $callbackQuery->id,
            'text' => '🔃 Cambiando página...'
        ]);

        // Obtener información del mensaje actual
        $currentMessage = $callbackQuery->message;
        $currentText = $currentMessage->text;

        // Obtener todas las plantas del tenant usando el tenant_id de la asociación
        $indoors = Indoor::where('tenant_id', $association->tenant_id)
            ->with(['plants.seedType'])
            ->get();
        $allPlants = [];
        
        foreach ($indoors as $indoor) {
            foreach ($indoor->plants->where('state', '!=', 'muerta') as $plant) {
                $allPlants[] = Keyboard::inlineButton([
                    'text'          => htmlspecialchars($plant->name, ENT_QUOTES, 'UTF-8'),
                    'callback_data' => 'plantdetails:'.$plant->id,
                ]);
            }
        }

        // Group plants into pages of 6 (3 rows of 2 columns)
        $plantPages = array_chunk($allPlants, 6);
        
        // Validar que la página solicitada existe
        if ($requestedPage < 0 || $requestedPage >= count($plantPages)) {
            $requestedPage = 0; // Volver a la primera página si es inválida
        }

        // Crear teclado para la página solicitada
        $keyboard = Keyboard::make()->inline();

        // Add plants for requested page
        if (isset($plantPages[$requestedPage])) {
            $buttonGroups = array_chunk($plantPages[$requestedPage], 2);
            foreach ($buttonGroups as $group) {
                $keyboard->row($group);
            }
        }

        // Add navigation buttons if there are multiple pages
        if (count($plantPages) > 1) {
            $navButtons = [];
            if ($requestedPage > 0) {
                $navButtons[] = Keyboard::inlineButton([
                    'text' => '⬅️ Anterior',
                    'callback_data' => 'plants_page:' . ($requestedPage - 1)
                ]);
            }
            if ($requestedPage < count($plantPages) - 1) {
                $navButtons[] = Keyboard::inlineButton([
                    'text' => 'Siguiente ➡️',
                    'callback_data' => 'plants_page:' . ($requestedPage + 1)
                ]);
            }
            if (!empty($navButtons)) {
                $keyboard->row($navButtons);
            }
        }

        $this->telegram->editMessageReplyMarkup([
            'chat_id' => $callbackQuery->message->chat->id,
            'message_id' => $callbackQuery->message->message_id,
            'reply_markup' => $keyboard
        ]);
    }

    protected function handleRepeatIrrigation($callbackQuery, $data, $association)
    {
        $parts = explode(':', $data);
        $actionId = $parts[1];

        // Get the original irrigation action
        $originalAction = Action::with('plants')->find($actionId);

        if (!$originalAction || $originalAction->action_type_id !== 1) {
            $this->telegram->answerCallbackQuery([
                'callback_query_id' => $callbackQuery->id,
                'text' => '❌ No se pudo encontrar el riego original',
                'show_alert' => true
            ]);
            return;
        }

        // Create a new irrigation action with the same data
        $newAction = Action::create([
            'action_type_id' => 1, // Irrigation
            'action_date' => now(),
            'indoor_id' => $originalAction->indoor_id,
            'tenant_id' => $association->tenant_id,
            'data' => $originalAction->data
        ]);

        // Attach the same plants
        if ($originalAction->plants->count() > 0) {
            $newAction->plants()->attach($originalAction->plants->pluck('id'));
        }

        $this->telegram->answerCallbackQuery([
            'callback_query_id' => $callbackQuery->id,
            'text' => '✅ Riego repetido exitosamente'
        ]);

        $messageText = "💧 *Riego repetido exitosamente*\n\n";
        $messageText .= "🏠 *Indoor:* " . ($originalAction->indoor->name ?? 'N/A') . "\n";
        
        $irrigationData = $originalAction->data['irrigation'] ?? [];
        $irrigationType = $irrigationData['irrigation_type'] ?? '';
        
        if ($irrigationType === 'liters') {
            $messageText .= "💦 *Tipo:* Litros\n";
            $messageText .= "📊 *Cantidad:* " . ($irrigationData['liters'] ?? '0') . " litros\n";
        } elseif ($irrigationType === 'timer') {
            $messageText .= "💦 *Tipo:* Temporizador\n";
            $messageText .= "⏰ *Tiempo:* " . ($irrigationData['timer'] ?? '0') . " minutos\n";
        }
        
        $messageText .= "🌱 *Plantas seleccionadas:* " . $originalAction->plants->count() . "\n";
        
        // List the plants if there are not too many
        if ($originalAction->plants->count() <= 10) {
            foreach ($originalAction->plants as $plant) {
                $messageText .= "   • " . $plant->name . "\n";
            }
        } else {
            $messageText .= "   • " . $originalAction->plants->count() . " plantas en total\n";
        }
        
        $messageText .= "📅 *Fecha:* " . now()->format('d/m/Y H:i');

        $this->telegram->editMessageText([
            'chat_id' => $callbackQuery->message->chat->id,
            'message_id' => $callbackQuery->message->message_id,
            'text' => $messageText,
            'parse_mode' => 'Markdown'
        ]);

        Log::info('Irrigation repeated', [
            'original_action_id' => $originalAction->id,
            'new_action_id' => $newAction->id,
            'user_id' => $callbackQuery->from->id,
            'plants_count' => $originalAction->plants->count()
        ]);
    }

    protected function handleCancelRepeatIrrigation($callbackQuery)
    {
        $this->telegram->answerCallbackQuery([
            'callback_query_id' => $callbackQuery->id,
            'text' => '❌ Repetición de riego cancelada'
        ]);

        $this->telegram->editMessageText([
            'chat_id' => $callbackQuery->message->chat->id,
            'message_id' => $callbackQuery->message->message_id,
            'text' => '❌ Repetición de riego cancelada.',
            'parse_mode' => 'HTML'
        ]);

        Log::info('Irrigation repetition cancelled', [
            'user_id' => $callbackQuery->from->id
        ]);
    }
}