<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Services\TenantTokenService;

Route::middleware('tenant.token')->group(function () {
    // All tenant API routes go here
    Route::get('/test', function (Request $request) {
        return response()->json(['message' => 'Token valid for tenant: '.$request->tenant->name]);
    });

    Route::post('/renew-token', function (Request $request) {
        $newToken = app(TenantTokenService::class)->renewToken($request->bearerToken());
        
        if (!$newToken) {
            return response()->json(['error' => 'Token cannot be renewed'], 400);
        }

        return response()->json(['token' => $newToken]);
    });
});
