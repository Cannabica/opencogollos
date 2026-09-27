<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Services\TenantTokenService;
use App\Services\TelegramLogger;
use Telegram\Bot\Laravel\Facades\Telegram;
use App\Http\Controllers\Telegram\AdminWebhookController;

// Telegram webhook route
Route::post('/telegram/webhook', function (\Illuminate\Http\Request $request) {
    try {
        $update = Telegram::getWebhookUpdate();
        TelegramLogger::logUpdate($update);

        if ($update->has('callback_query')) {
            $callbackData = $update->callbackQuery->data;

            if (strpos($callbackData, 'plantdetails:') === 0) {
                Telegram::triggerCommand('plantdetails', $update);
            } elseif (strpos($callbackData, 'actiondetails:') === 0) {
                Telegram::triggerCommand('actiondetails', $update);
            } else {
                Telegram::triggerCommand('callback', $update);
            }
        } elseif ($update->has('message') && $update->message->has('photo')) {
            Telegram::triggerCommand('photo', $update);
        } else {
            Telegram::commandsHandler(true);
        }

        return response()->json(['status' => 'ok']);
    } catch (\Exception $e) {
        \Illuminate\Support\Facades\Log::error('Telegram webhook processing error', [
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString(),
            'request_data' => $request->all()
        ]);
        return response('Error', 500);
    }
})->middleware('verify.telegram.tenant');

// Webhook del bot de administración (superadmin). Seguridad: exige
// TELEGRAM_ADMIN_SECRET_TOKEN configurado y el header de secret de Telegram.
Route::post('/telegram/admin/webhook', AdminWebhookController::class);

// Tenant API Routes
Route::middleware('tenant.token')->group(function () {
    Route::post('/renew-token', function (Request $request) {
        $newToken = app(TenantTokenService::class)->renewToken(
            hash('sha256', $request->bearerToken())
        );
        
        if (!$newToken) {
            return response()->json(['error' => 'Token cannot be renewed'], 400);
        }

        return response()->json(['token' => $newToken]);
    });

    Route::get('/user', function (Request $request) {
        return $request->tenant;
    });

    Route::post('/notifications', [\App\Http\Controllers\NotificationController::class, 'store']);
    
    // Plant endpoints
    Route::get('/plants', [\App\Http\Controllers\PlantController::class, 'index']);
    Route::get('/plants/{id}', [\App\Http\Controllers\PlantController::class, 'show']);
});
