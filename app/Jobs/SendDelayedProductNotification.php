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

class SendDelayedProductNotification implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        protected string $applicationType,
        protected int $plantsCount,
        protected int $tenantId
    ) {
        Log::info('SendDelayedProductNotification constructor', [
            'applicationType' => $this->applicationType,
            'plantsCount' => $this->plantsCount,
            'tenantId' => $this->tenantId
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
                ->title('Recordatorio de aplicación de producto')
                ->body("Se aplicó un producto de tipo {$this->applicationType} a {$this->plantsCount} planta(s)")
                ->persistent()
                ->sendToDatabase($user);
        }

        Log::info('SendDelayedProductNotification handle - Completed');
    }
} 