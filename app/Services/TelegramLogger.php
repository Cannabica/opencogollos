<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Telegram\Bot\Objects\Update;

class TelegramLogger
{
    /**
     * Log a Telegram activity with structured data for Grafana analysis.
     *
     * @param string $event The event name (e.g., 'command_executed', 'webhook_received', 'photo_processed').
     * @param array $data Additional context data.
     * @return void
     */
    public static function activity(string $event, array $data = []): void
    {
        $payload = array_merge([
            'source' => 'telegram_bot',
            'event'  => $event,
            'ts'     => now()->toISOString(),
        ], $data);

        Log::channel('telegram')->info("[Telegram Activity] $event", $payload);
    }

    /**
     * Log a complete update object for debugging.
     *
     * @param Update $update
     * @param array $extra
     * @return void
     */
    public static function logUpdate(Update $update, array $extra = []): void
    {
        $type = 'unknown';
        $chatId = null;
        $userId = null;
        $username = null;

        if ($update->has('message')) {
            $type = $update->message->has('text') ? 'message' : ($update->message->has('photo') ? 'photo' : 'other');
            $chatId = $update->message->chat->id;
            $userId = $update->message->from->id;
            $username = $update->message->from->username;
        } elseif ($update->has('callback_query')) {
            $type = 'callback_query';
            $chatId = $update->callback_query->message->chat->id;
            $userId = $update->callback_query->from->id;
            $username = $update->callback_query->from->username;
        }

        self::activity('incoming_update', array_merge([
            'update_id' => $update->update_id,
            'type'      => $type,
            'chat_id'   => $chatId,
            'user_id'   => $userId,
            'username'  => $username,
        ], $extra));
    }
}
