<div class="p-4 space-y-4">
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
    @endif
</div>