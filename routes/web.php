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
    $user = auth()->user();
    if($user != null){
        return redirect($user->tenant_id != null ? '/tenant' : '/superadmin');
    }
    return redirect('/tenant');
});
