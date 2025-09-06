<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Telegram\Bot\Laravel\Facades\Telegram;

class TelegramPhotoService
{
    public static function downloadAndStorePhoto($fileId, $prefix = 'telegram')
    {
        try {
            // Get file info from Telegram
            $file = Telegram::getFile(['file_id' => $fileId]);
            
            if (!$file || !isset($file['file_path'])) {
                Log::error('Failed to get file info from Telegram', ['file_id' => $fileId]);
                return null;
            }

            $filePath = $file['file_path'];
            $fileName = basename($filePath);
            $extension = pathinfo($fileName, PATHINFO_EXTENSION) ?: 'jpg';
            
            // Generate unique filename
            $uniqueFileName = $prefix . '_' . uniqid() . '.' . $extension;
            $storagePath = 'actions/' . $uniqueFileName;
            
            // Download file content
            $fileContent = file_get_contents('https://api.telegram.org/file/bot' . env('TELEGRAM_BOT_TOKEN') . '/' . $filePath);
            
            if (!$fileContent) {
                Log::error('Failed to download file content from Telegram', ['file_path' => $filePath]);
                return null;
            }
            
            // Store file
            Storage::disk('public')->put($storagePath, $fileContent);
            
            Log::info('Telegram photo downloaded and stored', [
                'original_file' => $fileName,
                'stored_path' => $storagePath,
                'file_id' => $fileId
            ]);
            
            return $storagePath;
            
        } catch (\Exception $e) {
            Log::error('Error downloading Telegram photo', [
                'error' => $e->getMessage(),
                'file_id' => $fileId
            ]);
            return null;
        }
    }

    public static function getPhotoFileInfo($fileId)
    {
        try {
            $file = Telegram::getFile(['file_id' => $fileId]);
            return $file ?: null;
        } catch (\Exception $e) {
            Log::error('Error getting Telegram file info', [
                'error' => $e->getMessage(),
                'file_id' => $fileId
            ]);
            return null;
        }
    }
}