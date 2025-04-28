<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Services\TenantTokenService;
use Telegram\Bot\Laravel\Facades\Telegram;

// Telegram Webhook Route
Route::post('/telegram/webhook/{tenant}', function (Request $request, $tenant) {
    try {
        $telegram = app('telegram');
        $telegram->commandsHandler(true);
        
        return response('', 200, [
            'Content-Type' => 'application/json'
        ]);
    } catch (\Exception $e) {
        \Log::error('Telegram webhook error: '.$e->getMessage());
        return response('', 200);
    }
})->middleware(['verify.telegram.tenant']);

// Tenant API Routes
Route::middleware('tenant.token')->group(function () {
    Route::get('/test', function (Request $request) {
        return response()->json([
            'message' => 'Token valid for tenant: '.$request->tenant->name,
            'abilities' => $request->token()->abilities
        ]);
    });

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
