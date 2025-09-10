<x-filament-panels::page>
    <x-filament-panels::form wire:submit="create">
        {{ $this->form }}
    </x-filament-panels::form>

    {{-- Enlaces adicionales --}}
    <div class="mt-8 text-center">
        <div class="space-y-3 text-sm text-gray-600">
            <div>
                <a href="https://status.cannabica.ar/" target="_blank" class="text-blue-600 hover:text-blue-800 underline">
                    🤖 Monitor de estado de plataforma Cannabica
                </a>
            </div>
            <div>
                <a href="https://discord.com/invite/jN9Tje3eJe" target="_blank" class="text-blue-600 hover:text-blue-800 underline">
                    👥 Invitación al server de discord
                </a>
            </div>
            <div>
                <a href="https://docs.google.com/forms/d/e/1FAIpQLSfVRNMzMdEGFDOuDlWzQgkDBAhOr9bXtlIGdhsQSxB0h7aqkw/viewform" target="_blank" class="text-blue-600 hover:text-blue-800 underline">
                    🐞 Reportar un bug
                </a>
            </div>
        </div>
    </div>

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