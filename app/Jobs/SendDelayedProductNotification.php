<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Models\User;
use App\Models\TelegramUserTenant;
use App\Models\Action as ActionModel;
use App\Notifications\ProductApplicationReminder;
use Illuminate\Support\Facades\Log;
use Filament\Notifications\Notification as FilamentNotification;
use Filament\Notifications\Actions\Action as FilamentAction;
use Livewire\Component;
use Telegram\Bot\Laravel\Facades\Telegram;

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

        // Enviar mensajes de Telegram a todos los usuarios asociados al tenant
        $telegramUsers = TelegramUserTenant::where('tenant_id', $this->tenantId)->get();
        
        foreach ($telegramUsers as $telegramUser) {
            try {
                // Obtener información del indoor si hay una acción asociada
                $indoorName = 'tu indoor';
                $actionUrl = null;
                
                if ($this->actionId) {
                    try {
                        $action = ActionModel::find($this->actionId);
                        if ($action) {
                            if ($action->indoor) {
                                $indoorName = $action->indoor->name;
                            }
                            
                            // Obtener observaciones/comentarios si existen
                            $observations = '';
                            if (!empty($action->data['product_application']['observations'])) {
                                $observations = "\n📝 *Observaciones:* " . $action->data['product_application']['observations'] . "\n";
                            }
                        }
                        
                        // Generar URL de la acción
                        $actionUrl = url("/tenant/actions/{$this->actionId}/edit");
                    } catch (\Exception $e) {
                        Log::warning('Error getting action details for Telegram message', [
                            'actionId' => $this->actionId,
                            'error' => $e->getMessage()
                        ]);
                    }
                }

                $message = "🌱 *¡Es momento de aplicar!* 🌱\n\n";
                $message .= "Es momento de aplicar un producto de *{$this->applicationType}* sobre *{$this->plantsCount} plantas* en {$indoorName}.\n\n";
                $message .= "⏰ *Recordatorio programado:* " . now()->format('d/m/Y H:i') . "\n";
                
                // Agregar observaciones si existen
                if (!empty($observations)) {
                    $message .= $observations;
                }
                
                if ($actionUrl) {
                    $message .= "\n📋 [Ver detalles de la aplicación]($actionUrl)";
                }
                
                $message .= "\n\n_🤖 Este es un recordatorio automático_";

                Telegram::sendMessage([
                    'chat_id' => $telegramUser->telegram_user_id,
                    'text' => $message,
                    'parse_mode' => 'Markdown',
                    'disable_web_page_preview' => true
                ]);

                Log::info('SendDelayedProductNotification - Telegram message sent', [
                    'telegram_user_id' => $telegramUser->telegram_user_id,
                    'tenant_id' => $this->tenantId
                ]);
            } catch (\Exception $e) {
                Log::error('SendDelayedProductNotification - Error sending Telegram message', [
                    'telegram_user_id' => $telegramUser->telegram_user_id,
                    'error' => $e->getMessage()
                ]);
            }
        }

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
                    FilamentAction::make('postpone')
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
                    FilamentAction::make('goToAction')
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