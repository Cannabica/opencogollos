<?php

use Illuminate\Support\Facades\Route;
use App\Jobs\SendDelayedProductNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\TelegramController;
use Telegram\Bot\Laravel\Facades\Telegram;

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

// Telegram webhook route
Route::post('/api/telegram/webhook', function () {

    // log::debug('Received Telegram webhook request', [
    //     'headers' => request()->headers->all(),
    //     'body' => request()->all()
    // ]);

    $update = Telegram::getWebhookUpdate();

    if ($update->has('callback_query')) {
        $callbackData = $update->callbackQuery->data;

        if (strpos($callbackData, 'plantdetails:') === 0) {
            $parts = explode(':', $callbackData);
            $plantId = end($parts);
            $command = 'plantdetails';

            try {
                Telegram::triggerCommand($command, $update);
            } catch (\Exception $e) {
                Log::error('Error triggering command', [
                    'error' => $e->getMessage(),
                    'exception' => $e
                ]);
            }
        } elseif (strpos($callbackData, 'actiondetails:') === 0) {
            $parts = explode(':', $callbackData);
            $actionId = end($parts);
            $command = 'actiondetails';

            try {
                Telegram::triggerCommand($command, $update);
            } catch (\Exception $e) {
                Log::error('Error triggering command: ' . $e->getMessage());
            }
        }
    } else {
        Telegram::commandsHandler(true);
    }

    return response()->json(['status' => 'ok']);
})->withoutMiddleware(['web']);
