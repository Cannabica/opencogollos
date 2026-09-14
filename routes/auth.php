<?php

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Rutas de auth — sólo el logout
|--------------------------------------------------------------------------
|
| Acá no vive nada más, a propósito. Este archivo era el scaffold de Breeze
| completo: login, registro, reset de password, confirmación de password,
| verificación de email y el perfil (con borrado de cuenta). El 2026-09-14 se
| podó esa superficie entera: autenticación, registro, reset, cambio de
| password y perfil los sirve el panel Filament (/tenant/* y /superadmin/*),
| que además rate-limita el intento de login (Login::rateLimit(5)).
|
| Por qué se BORRÓ en vez de dejarlo apagado (el bloque `guest` estaba
| comentado): cada ruta viva es una puerta. Lo que quedaba abierto era, en
| particular, un perfil paralelo al de Filament que podía BORRAR LA CUENTA
| (DELETE /profile, dejando el tenant huérfano) y un cambio de email que
| desverificaba al usuario sin forma de volver (el mail de verificación no sale).
| Nada de eso vuelve sin decidir antes las reglas: ver las tarjetas T10.x del
| board (perfil, baja y verificación in-house con Filament).
|
| Regla al tocar este archivo: toda ruta que (a) acepte una credencial,
| (b) cambie credenciales o (c) dispare un envío, va con `throttle`.
|
*/

Route::middleware('auth')->group(function () {
    Route::post('logout', [AuthenticatedSessionController::class, 'destroy'])
        ->name('logout');
});
