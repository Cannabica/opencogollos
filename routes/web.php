<?php

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

// Protected storage files - auth + tenant ownership check
Route::get('/storage/{path}', [StorageController::class, 'show'])
    ->where('path', '.*')
    ->name('storage.protected');

// El perfil de Breeze (/profile) se eliminó (2026-09-14): duplicaba el perfil del panel
// Filament y además incluía el borrado de cuenta, que dejaba el tenant huérfano.
// Ver las tarjetas T10.x del board para el reemplazo in-house.

require __DIR__ . '/auth.php';
require __DIR__ . '/test_403.php';
