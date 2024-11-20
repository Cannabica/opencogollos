<x-filament::page>
    <!-- Mostrar el formulario de filtros -->
    <div class="mb-6">
        {{ $this->filtersForm }}
    </div>

    <!-- Mostrar los datos del indoor y los widgets en columnas -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        
        <!-- Datos del Indoor -->
        <div class="col-span-1 bg-transparent shadow-md rounded-lg p-6">
            @foreach($this->getIndoors() as $indoor)
                <h3 class="text-xl font-semibold mb-4">{{ $indoor->name }}</h3>

                <div class="space-y-2">
                    <p><strong>{{ __('Largo') }}:</strong> {{ $indoor->large }}</p>
                    <p><strong>{{ __('Ancho') }}:</strong> {{ $indoor->width }}</p>
                    <p><strong>{{ __('Alto') }}:</strong> {{ $indoor->height }}</p>
                    <p><strong>{{ __('Días Programados') }}:</strong> 
                        {{ is_array($indoor->data['scheduled_days'] ?? null) 
                            ? implode(', ', $indoor->data['scheduled_days']) 
                            : $indoor->data['scheduled_days'] ?? '' }}
                    </p>

                    <p><strong>{{ __('Ventiladores') }}:</strong> 
                        @php
                            $fans = $indoor->data['fans'] ?? null;
                            $fanCount = $fans ? count(json_decode($fans, true)) : 0;
                        @endphp
                        {{ $fanCount }}
                    </p>

                    <p><strong>{{ __('Lámparas') }}:</strong> 
                        @php
                            $lamps = $indoor->data['lamps'] ?? null;
                            $lampCount = $lamps ? count(json_decode($lamps, true)) : 0;
                        @endphp
                        {{ $lampCount }}
                    </p>

                    <p><strong>{{ __('Higometro') }}:</strong> {{ $indoor->hygometer }}</p>
                    <p><strong>{{ __('Humidificador') }}:</strong> {{ $indoor->humidifier }}</p>
                    <p><strong>{{ __('Cantidad de Picos por planta') }}:</strong> {{ $indoor->peak_quantity }}</p>
                    <p><strong>{{ __('Horas Programadas') }}:</strong> {{ $indoor->scheduled_time }}</p>
                    <p><strong>{{ __('Veces al día') }}:</strong> {{ $indoor->times_a_day }}</p>
                </div>
            @endforeach
        </div>

        <!-- Widgets -->
        <div class="col-span-1 md:col-span-1 lg:col-span-2 grid grid-cols-1 gap-6">
            @foreach($this->getWidgets() as $widget)
                <div class="bg-transparent shadow rounded-lg p-4">
                    @livewire($widget)
                </div>
            @endforeach
        </div>
    </div>
</x-filament::page>
