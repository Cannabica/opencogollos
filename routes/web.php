<?php

use App\Http\Controllers\ManifestController;
use App\Http\Controllers\PostponeProductReminderController;
use App\Http\Controllers\StorageController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::get('/', function () {
    return redirect('/tenant/login');
});

// Aliasing para auth de Laravel: los invitados que caen en rutas con
// middleware 'auth' (ej. /dashboard) se redirigen al login del panel tenant.
Route::get('/login', function () {
    return redirect('/tenant/login');
})->name('login');

// El dashboard de Breeze se eliminó (2026-09-14): el panel del tenant es la UI real.
// Se mantiene la ruta por compatibilidad de enlaces, pero redirige al panel.
Route::get('/dashboard', function () {
    return redirect('/tenant');
})->middleware('auth')->name('dashboard');

// Manifest de la PWA. El nombre sale de config('app.name') (el default del repo
// es el nombre del producto; la instalación muestra el suyo vía APP_NAME).
Route::get('/manifest.json', ManifestController::class)->name('manifest');

// Protected storage files - auth + tenant ownership check
Route::get('/storage/{path}', [StorageController::class, 'show'])
    ->where('path', '.*')
    ->name('storage.protected');

// El perfil de Breeze (/profile) se eliminó (2026-09-14): duplicaba el perfil del panel
// Filament y además incluía el borrado de cuenta, que dejaba el tenant huérfano.
// Ver las tarjetas T10.x del board para el reemplazo in-house.

// T2.8 (2026-09-19): acción "Posponer" del recordatorio de aplicación de producto.
// `signed` = la firma es la autorización (no se puede forjar el link); `auth` = tiene que
// haber un usuario logueado y la firma incluye su id. Ver PostponeProductReminderController.
Route::middleware(['auth', 'signed'])->group(function () {
    Route::get('/recordatorios/posponer/{type}/{count}/{action}', PostponeProductReminderController::class)
        ->name('actions.postpone-notification');
});

require __DIR__ . '/auth.php';
