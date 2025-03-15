<?php

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
    if (auth()->check()) {
        $user = auth()->user();
        return redirect($user->tenant_id != null ? '/tenant' : '/superadmin');
    }
    return redirect('/tenant');
});

Route::get('/health', function () {
    return response()->json(['status' => 'healthy'], 200);
});
