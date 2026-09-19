<?php

namespace App\Http\Controllers;

use App\Jobs\SendDelayedProductNotification;
use App\Models\User;
use Filament\Notifications\Notification as FilamentNotification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Reprograma el recordatorio de aplicación de producto: la acción "Posponer" de la
 * notificación (T2.8, 2026-09-19).
 *
 * POR QUÉ EXISTE ESTE CONTROLADOR: la acción de una notificación de base de datos no puede
 * llevar una closure. Filament guarda la notificación serializando la acción en la columna
 * `data` (`Filament\Notifications\Actions\Action::toArray()` → name/color/url/event/
 * shouldMarkAsRead/... pero NO la closure) y al rehidratarla (`Action::fromArray()`) el
 * handler desaparece: el botón quedaba mudo, sin error visible. `url` SÍ sobrevive, así que
 * el botón apunta a una URL FIRMADA (la firma es la autorización: no se puede forjar) que
 * reencola el mismo job con delay.
 */
class PostponeProductReminderController extends Controller
{
    /** Horas que se posterga el recordatorio. */
    public const POSTPONE_HOURS = 24;

    public function __invoke(Request $request, string $type, int $count, int $action): RedirectResponse
    {
        $user = $request->user();

        abort_if(! $user instanceof User, 403);
        // La URL se firma por destinatario: el id va dentro de la firma.
        abort_unless((int) $request->query('user') === (int) $user->getKey(), 403);
        abort_if(empty($user->tenant_id), 403);

        dispatch(new SendDelayedProductNotification(
            $type,
            $count,
            (int) $user->tenant_id,
            $action
        ))->delay(now()->addHours(self::POSTPONE_HOURS));

        FilamentNotification::make()
            ->success()
            ->title('Recordatorio pospuesto')
            ->body('Te lo vuelvo a recordar en ' . self::POSTPONE_HOURS . ' horas.')
            ->send();

        return redirect('/tenant');
    }
}
