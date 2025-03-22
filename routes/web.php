<?php

use Illuminate\Support\Facades\Route;
use App\Jobs\SendDelayedProductNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

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

Route::get('/tenant/actions/postpone-notification', function (Request $request) {
    Log::info('Postpone notification requested', $request->all());
    
    dispatch(new SendDelayedProductNotification(
        $request->query('type'),
        (int) $request->query('count'),
        (int) $request->query('tenant'),
        $request->query('action') ? (int) $request->query('action') : null
    ))->delay(now()->addDay());

    return redirect()->back();
})->name('actions.postpone-notification');
