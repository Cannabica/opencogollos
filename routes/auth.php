<?php

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\ConfirmablePasswordController;
use App\Http\Controllers\Auth\EmailVerificationNotificationController;
use App\Http\Controllers\Auth\EmailVerificationPromptController;
use App\Http\Controllers\Auth\PasswordController;
use App\Http\Controllers\Auth\VerifyEmailController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Rutas de auth — superficie reducida a propósito
|--------------------------------------------------------------------------
|
| El login, el registro, el olvido y el reset de contraseña NO viven acá: los
| sirve el panel Filament (`/tenant/login`, `/tenant/register`,
| `/tenant/password-reset/*`, `/superadmin/login`) y ese login YA rate-limita
| el intento (`Login::rateLimit(5)` → notificación "throttled").
|
| El bloque `guest` del scaffold de Breeze (register / login / forgot-password /
| reset-password) se ELIMINÓ en vez de dejarlo comentado. Motivo de seguridad:
| dejarlo ahí era una trampa — descomentarlo habilitaría un SEGUNDO login
| paralelo al de Filament y **sin throttle** (el `POST /login` del scaffold no
| tiene rate limiting), que es exactamente el vector de fuerza bruta que este
| archivo acota. Los controladores y las vistas del scaffold siguen en el repo
| porque `/profile` los usa (ver abajo), pero sus rutas no vuelven sin decidir
| antes qué pasa con el rate limiting.
|
| Regla al tocar este archivo: toda ruta que (a) acepte una credencial o
| (b) dispare un envío de mail, va con `throttle`.
|
*/

Route::middleware('auth')->group(function () {
    Route::get('verify-email', EmailVerificationPromptController::class)
        ->name('verification.notice');

    Route::get('verify-email/{id}/{hash}', VerifyEmailController::class)
        ->middleware(['signed', 'throttle:6,1'])
        ->name('verification.verify');

    Route::post('email/verification-notification', [EmailVerificationNotificationController::class, 'store'])
        ->middleware('throttle:6,1')
        ->name('verification.send');

    Route::get('confirm-password', [ConfirmablePasswordController::class, 'show'])
        ->name('password.confirm');

    // Sin throttle, esta ruta deja probar contraseñas contra la del usuario
    // autenticado (fuerza bruta sobre `current_password`). Mismo criterio que
    // `verification.send`.
    Route::post('confirm-password', [ConfirmablePasswordController::class, 'store'])
        ->middleware('throttle:6,1');

    // Igual que la anterior: `PasswordController@update` valida `current_password`
    // antes de cambiarla (la usa el formulario de /profile).
    Route::put('password', [PasswordController::class, 'update'])
        ->middleware('throttle:6,1')
        ->name('password.update');

    Route::post('logout', [AuthenticatedSessionController::class, 'destroy'])
        ->name('logout');
});
