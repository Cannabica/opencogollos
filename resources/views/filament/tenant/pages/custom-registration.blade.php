<x-filament-panels::page>
    <div class="fi-main-ctn max-w-2xl mx-auto p-6 bg-white rounded-lg shadow-md">
       
        <!-- Registration form -->
        {{ $this->form }}

        <!-- Footer with status monitoring link -->
        <div class="mt-8 pt-6 border-t border-gray-200 text-center">
            <p class="text-sm text-gray-600">
                ¿Problemas con el sistema? Verifica el estado en 
                <a href="https://status.cannabica.ar" target="_blank" class="text-cadetblue hover:text-cadetblue-700 font-medium">
                    status.cannabica.ar
                </a>
            </p>
            <p class="text-xs text-gray-500 mt-2">
                &copy; {{ date('Y') }} Cannabica. Todos los derechos reservados.
            </p>
        </div>
    </div>

    <style>
        .bg-cadetblue {
            background-color: #5f9ea0;
        }
        .text-cadetblue {
            color: #5f9ea0;
        }
        .hover\:text-cadetblue-700:hover {
            color: #3d7a7c;
        }
    </style>
</x-filament-panels::page>