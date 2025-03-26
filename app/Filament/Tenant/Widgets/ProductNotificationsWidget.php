<?php

namespace App\Filament\Tenant\Widgets;

use Filament\Widgets\Widget;
use Illuminate\Support\Facades\Auth;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Collection;
use Illuminate\Contracts\View\View;

class ProductNotificationsWidget extends Widget
{
    protected static string $view = 'filament.tenant.widgets.product-notifications-widget';

    protected int | string | array $columnSpan = 'full';

    public Collection $notifications;

    protected $listeners = [
        'echo:notification,NotificationSent' => 'loadNotifications',
        'refreshNotifications' => 'loadNotifications'
    ];

    public function mount(): void
    {
        Log::info('ProductNotificationsWidget mount');
        $this->notifications = collect();
        $this->loadNotifications();
    }

    protected function getPollingInterval(): ?string
    {
        return '10s';
    }

    public function loadNotifications(): void
    {
        Log::info('ProductNotificationsWidget loadNotifications - Starting');
        
        try {
            $user = Auth::user();
            
            if (!$user) {
                Log::error('ProductNotificationsWidget loadNotifications - No authenticated user');
                $this->notifications = collect();
                return;
            }

            Log::info('ProductNotificationsWidget loadNotifications - User', [
                'userId' => $user->id,
                'tenantId' => $user->tenant_id
            ]);

            // Verificar directamente en la base de datos
            $dbNotifications = DB::table('notifications')
                ->where('notifiable_id', $user->id)
                ->where('type', 'App\\Notifications\\ProductApplicationReminder')
                ->whereNull('read_at')
                ->orderBy('created_at', 'desc')
                ->get();

            Log::info('ProductNotificationsWidget loadNotifications - Raw DB Notifications', [
                'count' => $dbNotifications->count(),
                'notifications' => $dbNotifications->toArray()
            ]);
            
            $this->notifications = $user
                ->unreadNotifications()
                ->where('type', 'App\\Notifications\\ProductApplicationReminder')
                ->latest()
                ->get();

            Log::info('ProductNotificationsWidget loadNotifications - Completed', [
                'notificationCount' => $this->notifications->count(),
                'notifications' => $this->notifications->toArray()
            ]);

            $this->emit('notificationsLoaded');
        } catch (\Exception $e) {
            Log::error('ProductNotificationsWidget loadNotifications - Error', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            $this->notifications = collect();
        }
    }

    public function markAsRead($notificationId): void
    {
        Log::info('ProductNotificationsWidget markAsRead', [
            'notificationId' => $notificationId
        ]);

        try {
            $notification = Auth::user()
                ->notifications()
                ->find($notificationId);

            if ($notification) {
                $notification->markAsRead();
                
                Notification::make()
                    ->title('Notificación marcada como leída')
                    ->success()
                    ->send();

                $this->loadNotifications();
                $this->emit('notificationMarkedAsRead');
            } else {
                Log::warning('ProductNotificationsWidget markAsRead - Notification not found', [
                    'notificationId' => $notificationId
                ]);
            }
        } catch (\Exception $e) {
            Log::error('ProductNotificationsWidget markAsRead - Error', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }

    public function render(): View
    {
        return view(static::$view, [
            'notifications' => $this->notifications
        ]);
    }
}