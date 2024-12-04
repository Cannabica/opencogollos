<x-filament::page>
    <!-- Mostrar el formulario de filtros -->
    <div class="mb-6">
        {{ $this->filtersForm }}
    </div>

    <!-- Mostrar los datos del indoor y los widgets en columnas -->
    <div class="space-y-6">
        @foreach($this->getIndoors() as $indoor)
            <div class="!bg-gray-900 shadow-lg rounded-lg p-6" style="background-color: #18181b;">
                <!-- Encabezado del Indoor -->
                <h3 class="text-2xl font-semibold mb-4 text-gray-300">{{ $indoor->name }}</h3>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <!-- Información del Indoor -->
                    <div class="bg-gray-900 p-4 rounded-lg shadow-md">
                        <h4 class="text-xl font-semibold mb-4 text-gray-200">{{ __('Información del Indoor') }}</h4>
                        <table class="min-w-full text-sm text-left text-gray-400">
                            <tbody>
                                <tr class="bg-gray-800" style="background-color: #27272a;border-color: #18181b;border-width: medium;">
                                    <td class="py-2 px-4 " style="background-color: #27272a;border-color: #18181b;border-width: medium;"><strong>{{ __('Largo') }}:</strong></td>
                                    <td class="py-2 px-4 " style="background-color: #27272a;border-color: #18181b;border-width: medium;">{{ $indoor->large }}</td>
                                </tr>
                                <tr class="bg-gray-800" style="background-color: #27272a;border-color: #18181b;border-width: medium;">
                                    <td class="py-2 px-4" style="background-color: #27272a;border-color: #18181b;border-width: medium;"><strong>{{ __('Ancho') }}:</strong></td>
                                    <td class="py-2 px-4" style="background-color: #27272a;border-color: #18181b;border-width: medium;">{{ $indoor->width }}</td>
                                </tr>
                                <tr class="bg-gray-800" style="background-color: #27272a;border-color: #18181b;border-width: medium;">
                                    <td class="py-2 px-4" style="background-color: #27272a;border-color: #18181b;border-width: medium;"><strong>{{ __('Alto') }}:</strong></td>
                                    <td class="py-2 px-4" style="background-color: #27272a;border-color: #18181b;border-width: medium;">{{ $indoor->height }}</td>
                                </tr>
                                <tr class="bg-gray-800" style="background-color: #27272a;border-color: #18181b;border-width: medium;">
                                    <td class="py-2 px-4" style="background-color: #27272a;border-color: #18181b;border-width: medium;"><strong>{{ __('Días Programados') }}:</strong></td>
                                    <td class="py-2 px-4" style="background-color: #27272a;border-color: #18181b;border-width: medium;">
                                        @if(is_array($indoor->scheduled_days))
                                            {{ implode(', ', $indoor->scheduled_days) }}
                                        @else
                                            {{ __('No disponible') }}
                                        @endif
                                    </td>
                                </tr>
                                <tr class="bg-gray-800" style="background-color: #27272a;border-color: #18181b;border-width: medium;">
                                    <td class="py-2 px-4" style="background-color: #27272a;border-color: #18181b;border-width: medium;"><strong>{{ __('Ventiladores') }}:</strong></td>
                                    <td class="py-2 px-4" style="background-color: #27272a;border-color: #18181b;border-width: medium;">
                                        @if(is_array($indoor->fans))
                                            {{ count($indoor->fans) }}
                                        @else
                                            {{ __('No disponible') }}
                                        @endif
                                    </td>
                                </tr>
                                <tr class="bg-gray-800" style="background-color: #27272a;border-color: #18181b;border-width: medium;">
                                    <td class="py-2 px-4" style="background-color: #27272a;border-color: #18181b;border-width: medium;"><strong>{{ __('Lámparas') }}:</strong></td>
                                    <td class="py-2 px-4" style="background-color: #27272a;border-color: #18181b;border-width: medium;">
                                        @if(is_array($indoor->lamps))
                                            {{ count($indoor->lamps) }}
                                        @else
                                            {{ __('No disponible') }}
                                        @endif
                                    </td>
                                </tr>
                                <tr class="bg-gray-800" style="background-color: #27272a;border-color: #18181b;border-width: medium;">
                                    <td class="py-2 px-4" style="background-color: #27272a;border-color: #18181b;border-width: medium;"><strong>{{ __('Higrómetro') }}:</strong></td>
                                    <td class="py-2 px-4" style="background-color: #27272a;border-color: #18181b;border-width: medium;">{{ $indoor->hygrometer == 1 ? __('Sí') : __('No') }}</td>
                                </tr>
                                <tr class="bg-gray-800" style="background-color: #27272a;border-color: #18181b;border-width: medium;">
                                    <td class="py-2 px-4" style="background-color: #27272a;border-color: #18181b;border-width: medium;"><strong>{{ __('Humidificador') }}:</strong></td>
                                    <td class="py-2 px-4" style="background-color: #27272a;border-color: #18181b;border-width: medium;">{{ $indoor->humidifier == 1 ? __('Sí') : __('No') }}</td>
                                </tr>
                                <tr class="bg-gray-800" style="background-color: #27272a;border-color: #18181b;border-width: medium;">
                                    <td class="py-2 px-4" style="background-color: #27272a;border-color: #18181b;border-width: medium;"><strong>{{ __('Cantidad de Picos por planta') }}:</strong></td>
                                    <td class="py-2 px-4" style="background-color: #27272a;border-color: #18181b;border-width: medium;">{{ $indoor->peak_quantity }}</td>
                                </tr>
                                <tr class="bg-gray-800" style="background-color: #27272a;border-color: #18181b;border-width: medium;">
                                    <td class="py-2 px-4" style="background-color: #27272a;border-color: #18181b;border-width: medium;"><strong>{{ __('Horas Programadas') }}:</strong></td>
                                    <td class="py-2 px-4" style="background-color: #27272a;border-color: #18181b;border-width: medium;">{{ $indoor->scheduled_time }}</td>
                                </tr>
                                <tr class="bg-gray-800" style="background-color: #27272a;border-color: #18181b;border-width: medium;">
                                    <td class="py-2 px-4" style="background-color: #27272a;border-color: #18181b;border-width: medium;"><strong>{{ __('Veces al día') }}:</strong></td>
                                    <td class="py-2 px-4" style="background-color: #27272a;border-color: #18181b;border-width: medium;">{{ $indoor->times_a_day }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Listado de Plantas del Indoor -->
                    <div class="bg-gray-900 p-4 rounded-lg shadow-md">
                        <h4 class="text-xl font-semibold mb-3 text-gray-200">{{ __('Plantas') }}</h4>

                        @if(count($this->getPlants($indoor->id)) > 0)
                            <table class="min-w-full text-sm text-left text-gray-400">
                                <thead>
                                    <tr class="bg-gray-800" style="background-color: #27272a;border-color: #18181b;border-width: medium;">
                                        <th class="py-2 px-4" style="background-color: #27272a;border-color: #18181b;border-width: medium;">{{ __('Nombre') }}</th>
                                        <th class="py-2 px-4" style="background-color: #27272a;border-color: #18181b;border-width: medium;">{{ __('Estado') }}</th>
                                        <th class="py-2 px-4" style="background-color: #27272a;border-color: #18181b;border-width: medium;">{{ __('Fecha de Germinación') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($this->getPlants($indoor->id) as $plant)
                                        <tr class="bg-gray-800 rounded-lg">
                                            <td class="py-2 px-4" style="background-color: #27272a;border-color: #18181b;border-width: medium;">{{ $plant->name }}</td>
                                            <td class="py-2 px-4" style="background-color: #27272a;border-color: #18181b;border-width: medium;">{{ $plant->state }}</td>
                                            <td class="py-2 px-4" style="background-color: #27272a;border-color: #18181b;border-width: medium;">{{ $plant->germination_date }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        @else
                            <p class="text-gray-500">{{ __('No hay plantas registradas para este indoor.') }}</p>
                        @endif
                    </div>
                </div>
            </div>
        @endforeach
    </div>
</x-filament::page>
