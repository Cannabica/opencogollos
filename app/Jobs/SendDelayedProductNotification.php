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
        protected ?int $actionId = null,
        protected ?int $notificationId = null
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
                        ->action(function () {
                            if ($this->notificationId) {
                                Livewire::dispatch('markNotificationAsRead', ['id' => $this->notificationId]);
                            }
                            if (empty($this->applicationType) || empty($this->plantsCount) ||
                                empty($this->tenantId) || empty($this->actionId)) {
                                Log::error('Missing required notification parameters', [
                                    'applicationType' => $this->applicationType,
                                    'plantsCount' => $this->plantsCount,
                                    'tenantId' => $this->tenantId,
                                    'actionId' => $this->actionId
                                ]);
                                return '#';
                            }

                            return redirect()->route('actions.postpone-notification', [
                                'type' => (string)$this->applicationType,
                                'count' => (int)$this->plantsCount,
                                'tenant' => (int)$this->tenantId,
                                'action' => (int)$this->actionId
                            ]);
                        })
                        ->close()
                        ->extraAttributes([]),
                    Action::make('goToAction')
                        ->label('Aplicación que generó el recordatorio')
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
    public function postponeNotification($applicationType, $plantsCount, $tenantId, $notificationId = null): void
    {
        dispatch(new SendDelayedProductNotification(
            $applicationType,
            $plantsCount,
            $tenantId,
            null,
            $notificationId
        ))->delay(now()->addSeconds(10));
    }
}