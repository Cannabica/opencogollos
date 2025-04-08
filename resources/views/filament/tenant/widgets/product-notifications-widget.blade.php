@php
    \Illuminate\Support\Facades\Log::info('ProductNotificationsWidget view - Rendering', [
        'notificationsCount' => $notifications->count(),
        'notifications' => $notifications->toArray()
    ]);
@endphp

<div class="p-4 space-y-4">
    <!-- Debug info -->
    @if(config('app.debug'))
        <div class="bg-gray-100 dark:bg-gray-700 p-4 rounded-lg mb-4">
            <p class="text-xs">Debug Info:</p>
            <p class="text-xs">Notifications Count: {{ $notifications->count() }}</p>
            <p class="text-xs">Last Check: {{ now() }}</p>
            <p class="text-xs">User ID: {{ auth()->id() }}</p>
            <p class="text-xs">Tenant ID: {{ auth()->user()->tenant_id ?? 'N/A' }}</p>
        </div>
    @endif

    @if($notifications->isNotEmpty())
        @foreach($notifications as $notification)
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4">
                <div class="flex items-start">
                    <div class="flex-shrink-0">
                        <x-heroicon-o-exclamation-triangle class="h-6 w-6 text-warning-500"/>
                    </div>
                    <div class="ml-3 w-0 flex-1">
                        <p class="text-sm font-medium text-gray-900 dark:text-white">
                            {{ $notification->data['title'] ?? 'Sin título' }}
                        </p>
                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                            {{ $notification->data['message'] ?? 'Sin mensaje' }}
                        </p>
                        <div class="mt-2">
                            <button
                                wire:click="$dispatch('markNotificationAsRead', {id: '{{ $notification->id }}'})"
                                type="button"
                                class="inline-flex items-center px-3 py-1.5 border border-transparent text-xs font-medium rounded-full shadow-sm text-white bg-primary-600 hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500"
                            >
                                Marcar como leída
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        @endforeach
    @else
        <div class="text-center text-gray-500 dark:text-gray-400">
            No hay notificaciones pendientes
            <p class="text-sm mt-1">Última actualización: {{ now()->format('H:i:s') }}</p>
        </div>
    @endif
</div>