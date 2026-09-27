<x-filament-panels::page>
    <x-filament-panels::form wire:submit="create">
        {{ $this->form }}
    </x-filament-panels::form>

    {{-- Enlaces adicionales: solo los que la instalación configuró en platform.* --}}
    @php
        $statusUrl = config('platform.status_page_url');
        $discordUrl = config('platform.community.discord_url');
        $feedbackUrl = config('platform.community.feedback_url');
        $brandName = config('platform.brand_name');
    @endphp

    @if (filled($statusUrl) || filled($discordUrl) || filled($feedbackUrl))
        <div class="mt-8 text-center">
            <div class="space-y-3 text-sm text-gray-600">
                @if (filled($statusUrl))
                    <div>
                        <a href="{{ $statusUrl }}" target="_blank" class="text-blue-600 hover:text-blue-800 underline">
                            🤖 Monitor de estado{{ filled($brandName) ? ' de plataforma ' . $brandName : ' de la plataforma' }}
                        </a>
                    </div>
                @endif
                @if (filled($discordUrl))
                    <div>
                        <a href="{{ $discordUrl }}" target="_blank" class="text-blue-600 hover:text-blue-800 underline">
                            👥 Invitación al server de discord
                        </a>
                    </div>
                @endif
                @if (filled($feedbackUrl))
                    <div>
                        <a href="{{ $feedbackUrl }}" target="_blank" class="text-blue-600 hover:text-blue-800 underline">
                            🐞 Reportar un bug
                        </a>
                    </div>
                @endif
            </div>
        </div>
    @endif

    <style>
        .fi-simple-main-ctn .fi-header{
            margin: auto;
            display: block;
        }
        .fi-simple-page .fi-header {
            display: none !important;
        }
        
        .fi-simple-page .fi-page > div:first-child {
            display: none !important;
        }
        
        .fi-simple-page .fi-main-ctn {
            padding-top: 0 !important;
        }
    </style>
</x-filament-panels::page>