<?php

namespace App\Telegram\Commands;

use App\Models\Indoor;
use Log;
use App\Services\TelegramLogger;
use Telegram\Bot\Commands\Command;
use Telegram\Bot\Keyboard\Keyboard;
use App\Telegram\Commands\ChecksTelegramExpiration; 

class PhotoHandlerCommand extends Command
{
    use ChecksTelegramExpiration;
    protected string $name = 'photo';
    protected string $description = 'Maneja mensajes con fotos y responde con lista de indoors';

    public function handle()
    {
        try {
            $update = $this->getUpdate();
            $message = $update->getMessage();
            $telegramUserId = $message->getFrom()->getId();
            
            // Check for valid (non-expired) association
            $existingAssociation = $this->checkTelegramAssociation($telegramUserId);
            if (!$existingAssociation) {
                return;
            }

            // Verificar si el mensaje contiene una foto
            if ($message->has('photo')) {
                $photos = $message->photo;
                $photoCount = count($photos);
                
                // Verificar si es parte de un álbum de fotos (media group)
                $mediaGroupId = $message->get('media_group_id');
                $isAlbum = !empty($mediaGroupId);
                
                // Siempre procesamos solo la foto de mayor calidad
                $photo = $photos->last();
                
                // Log para debugging
                TelegramLogger::activity('photo_received', [
                    'user_id' => $telegramUserId,
                    'is_album' => $isAlbum,
                    'media_group_id' => $mediaGroupId,
                    'total_sizes_received' => $photoCount,
                    'selected_highest_quality_size' => [
                        'width' => $photo->width,
                        'height' => $photo->height,
                        'file_size' => $photo->file_size
                    ]
                ]);
                
                // Para álbumes, almacenar todas las fotos pero solo responder una vez
                if ($isAlbum) {
                    $albumProcessingKey = 'telegram_album_processing_' . $mediaGroupId;
                    $albumPhotosKey = 'telegram_album_photos_' . $mediaGroupId;
                    
                    // Primero almacenar esta foto en la colección del álbum
                    $albumPhotoInfo = [
                        'file_id' => $photo->file_id,
                        'file_unique_id' => $photo->file_unique_id,
                        'width' => $photo->width,
                        'height' => $photo->height,
                        'file_size' => $photo->file_size,
                        'message_id' => $message->message_id,
                        'timestamp' => now()->toISOString()
                    ];
                    
                    $albumPhotos = cache()->get($albumPhotosKey, []);
                    
                    // Verificar si esta foto ya existe en el álbum para evitar duplicados
                    $photoExists = false;
                    foreach ($albumPhotos as $existingPhoto) {
                        if ($existingPhoto['file_id'] === $photo->file_id) {
                            $photoExists = true;
                            break;
                        }
                    }
                    
                    if (!$photoExists) {
                        $albumPhotos[] = $albumPhotoInfo;
                        cache()->put($albumPhotosKey, $albumPhotos, now()->addMinutes(5));
                    }
                    
                    $currentAlbumCount = count($albumPhotos);
                    
                    // Si ya estamos procesando este álbum, salir sin mostrar teclado
                    if (cache()->has($albumProcessingKey)) {
                        Log::debug('Álbum ya está siendo procesado - solo almacenando foto', [
                            'media_group_id' => $mediaGroupId,
                            'total_photos' => $currentAlbumCount,
                            'photo_added' => !$photoExists
                        ]);
                        
                        // Para fotos subsiguientes, almacenar pero no procesar
                        $photoInfo['album_photos_count'] = $currentAlbumCount;
                        $photoInfo['is_album_photo_only'] = true;
                        
                        // Guardar la foto individualmente pero salir sin mostrar teclado
                        $cacheKey = 'telegram_photo_' . $telegramUserId . '_' . $message->message_id;
                        cache()->put($cacheKey, $photoInfo, now()->addHours(24));
                        return;
                    }
                    
                    // Marcar que estamos procesando este álbum por 10 segundos
                    cache()->put($albumProcessingKey, true, now()->addSeconds(10));
                    
                    Log::debug('Iniciando procesamiento de álbum', [
                        'media_group_id' => $mediaGroupId,
                        'first_photo_message_id' => $message->message_id,
                        'initial_photos_count' => $currentAlbumCount,
                        'photo_added' => !$photoExists
                    ]);
                    
                    // Para álbumes, almacenar información adicional en la foto principal
                    $photoInfo['album_photos_count'] = $currentAlbumCount;
                    $photoInfo['is_first_album_photo'] = true;
                }
                
                $chatId = $message->chat->id;
                
                // Almacenar información de la foto temporalmente en sesión
                $photoInfo = [
                    'file_id' => $photo->file_id,
                    'file_unique_id' => $photo->file_unique_id,
                    'width' => $photo->width,
                    'height' => $photo->height,
                    'file_size' => $photo->file_size,
                    'message_id' => $message->message_id,
                    'chat_id' => $chatId,
                    'timestamp' => now()->toISOString(),
                    'is_album' => $isAlbum,
                    'media_group_id' => $isAlbum ? $mediaGroupId : null,
                    'photo_count' => $photoCount
                ];
                
                // Para álbumes, agregar información adicional
                if ($isAlbum) {
                    $albumPhotosKey = 'telegram_album_photos_' . $mediaGroupId;
                    $albumPhotos = cache()->get($albumPhotosKey, []);
                    $photoInfo['album_photos_key'] = $albumPhotosKey;
                    $photoInfo['total_album_photos'] = count($albumPhotos);
                    
                    // Log para verificar el contenido del álbum
                    Log::debug('Contenido del álbum almacenado', [
                        'media_group_id' => $mediaGroupId,
                        'total_photos' => count($albumPhotos),
                        'photo_ids' => array_map(function($p) { return $p['file_id']; }, $albumPhotos)
                    ]);
                }
                
                // Guardar en cache temporal (24 horas)
                $cacheKey = 'telegram_photo_' . $telegramUserId . '_' . $message->message_id;
                cache()->put($cacheKey, $photoInfo, now()->addHours(24));
                
                // Obtener lista de indoors del usuario
                $indoors = Indoor::where('tenant_id', $existingAssociation->tenant_id)
                    ->orderBy('name')
                    ->get();

                if ($indoors->isEmpty()) {
                    $this->replyWithMessage([
                        'text' => '❌ No hay indoors registrados para este tenant',
                        'parse_mode' => 'HTML'
                    ]);
                    return;
                }

                // Crear teclado inline con la lista de indoors
                $keyboard = Keyboard::make()->inline();
                
                foreach ($indoors as $indoor) {
                    $keyboard->row([
                        Keyboard::inlineButton([
                            'text' => '🏠 ' . htmlspecialchars($indoor->name, ENT_QUOTES, 'UTF-8'),
                            'callback_data' => 'select_indoor:' . $indoor->id
                        ])
                    ]);
                }

                // Añadir botón de cancelar
                $keyboard->row([
                    Keyboard::inlineButton([
                        'text' => '❌ Cancelar',
                        'callback_data' => 'cancel_photo'
                    ])
                ]);

                // Enviar respuesta con el teclado como respuesta al mensaje de la foto
                $messageText = $isAlbum
                    ? '📸 ¿A qué indoor quieres asociar este álbum de imágenes?'
                    : '📸 ¿A qué indoor quieres asociar esta imagen?';
                
                $this->replyWithMessage([
                    'chat_id' => $chatId,
                    'text' => $messageText,
                    'reply_markup' => $keyboard,
                    'parse_mode' => 'HTML',
                    'reply_to_message_id' => $message->message_id
                ]);

                // Log para debugging
                TelegramLogger::activity('photo_handler_executed', [
                    'update_id' => $update->getUpdateId(),
                    'user_id' => $telegramUserId,
                    'tenant_id' => $existingAssociation->tenant_id,
                    'indoors_count' => $indoors->count(),
                    'photo_count' => $photoCount,
                    'is_album' => $isAlbum,
                    'media_group_id' => $mediaGroupId
                ]);

            } else {
                $this->replyWithMessage([
                    'text' => '❌ Este comando solo funciona con mensajes que contengan fotos.',
                    'parse_mode' => 'HTML'
                ]);
            }

        } catch (\Exception $e) {
            $this->replyWithMessage([
                'text' => '❌ Error: ' . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8'),
                'parse_mode' => 'HTML'
            ]);
        }
    }
}