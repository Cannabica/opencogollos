<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Models\User;
use App\Notifications\ProductApplicationReminder;
use Illuminate\Support\Facades\Log;
use Filament\Notifications\Notification as FilamentNotification;
use Filament\Notifications\Actions\Action;
use Livewire\Component;

class SendDelayedProductNotification implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        protected string $applicationType,
        protected int $plantsCount,
        protected int $tenantId,
        protected ?int $actionId = null
    ) {
        Log::info('SendDelayedProductNotification constructor', [
            'applicationType' => $this->applicationType,
            'plantsCount' => $this->plantsCount,
            'tenantId' => $this->tenantId,
            'actionId' => $this->actionId
        ]);
    }

    public function handle(): void
    {
        Log::info('SendDelayedProductNotification handle - Starting', [
            'tenantId' => $this->tenantId
        ]);

        $users = User::where('tenant_id', $this->tenantId)->get();

        Log::info('SendDelayedProductNotification handle - Found users', [
            'userCount' => $users->count()
        ]);

        foreach ($users as $user) {
            Log::info('SendDelayedProductNotification handle - Notifying user', [
                'userId' => $user->id,
                'email' => $user->email
            ]);

            // Enviar notificación de base de datos para el widget
            $user->notify(new ProductApplicationReminder(
                $this->applicationType,
                $this->plantsCount
            ));

            // Enviar notificación de Filament para la campana
            FilamentNotification::make()
                ->success()
                ->title('Recordatorio de aplicación pendiente')
                ->body("Hay que realizar una aplicación de {$this->applicationType} para {$this->plantsCount} plantas")
                ->persistent()
                ->actions([
                    Action::make('postpone')
                        ->label('Posponer 24h')
                        ->button()
                        ->color('gray')
                        ->url(
                            "/tenant/actions/postpone-notification?" . http_build_query([
                                'type' => $this->applicationType,
                                'count' => $this->plantsCount,
                                'tenant' => $this->tenantId,
                                'action' => $this->actionId
                            ]),
                            shouldOpenInNewTab: false
                        )
                        ->close(),
                    Action::make('goToAction')
                        ->label('Aplicación anterior')
                        ->button()
                        ->color('success')
                        ->url($this->actionId
                            ? "/tenant/actions/{$this->actionId}/edit"
                            : "/tenant/actions")
                ])
                ->sendToDatabase($user);
        }

        Log::info('SendDelayedProductNotification handle - Completed');
    }
}

class NotificationHandler extends Component
{
    public function postponeNotification($applicationType, $plantsCount, $tenantId): void
    {
        dispatch(new SendDelayedProductNotification(
            $applicationType,
            $plantsCount,
            $tenantId
        ))->delay(now()->addSeconds(10));
    }
}