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
        } else {
            // Handle other callback patterns with the general callback command
            try {
                Telegram::triggerCommand('callback', $update);
            } catch (\Exception $e) {
                Log::error('Error triggering callback command', [
                    'error' => $e->getMessage(),
                    'exception' => $e
                ]);
            }
        }
    } else {
        // Si es un mensaje con foto, procesarlo con el comando photo
        if ($update->has('message') && $update->message->has('photo')) {
            try {
                Telegram::triggerCommand('photo', $update);
            } catch (\Exception $e) {
                Log::error('Error triggering photo command', [
                    'error' => $e->getMessage(),
                    'exception' => $e
                ]);
                Telegram::commandsHandler(true); // Fallback to default handler
            }
        }
        // Si es un mensaje de texto y el usuario está en modo descripción de observación
        elseif ($update->has('message') && $update->message->has('text')) {
            $text = $update->message->text;
            $userId = $update->message->from->id;
            
            // Verificar si el usuario está en el flujo de observación
            $observationData = cache()->get('observation_step_' . $userId);
            
            if ($observationData && $observationData['step'] === 'ask_description') {
                try {
                    // Procesar la descripción y crear la observación
                    \App\Services\TelegramObservationService::processObservationDescription($update, $observationData, $text);
                } catch (\Exception $e) {
                    Log::error('Error processing observation description', [
                        'error' => $e->getMessage(),
                        'exception' => $e
                    ]);
                    Telegram::commandsHandler(true); // Fallback to default handler
                }
            } else {
                Telegram::commandsHandler(true);
            }
        } else {
            Telegram::commandsHandler(true);
        }
    }

    return response()->json(['status' => 'ok']);
})->withoutMiddleware(['web']);
