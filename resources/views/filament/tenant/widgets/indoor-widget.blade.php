<x-filament-widgets::widget>
    <x-filament::section>
        <div class="flex flex-col space-y-4 p-4 bg-white shadow rounded">
            <h3 class="text-lg font-semibold">Información del Indoor</h3>

            @if ($indoor)
                <div class="flex flex-col space-y-2">
                    <span><strong>Nombre:</strong> {{ $indoor->name }}</span>
                    <span><strong>Ubicación:</strong> {{ $indoor->location }}</span>
                    <span><strong>Estado:</strong> {{ $indoor->status }}</span>
                </div>
            @else
                <p class="text-gray-500">No hay información disponible.</p>
            @endif
        </div>
    </x-filament::section>
</x-filament-widgets::widget>
