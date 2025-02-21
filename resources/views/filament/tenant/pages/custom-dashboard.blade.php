<x-filament::page>

    @push('styles')
        <link href="{{ asset('css/custom.css') }}" rel="stylesheet">
        <link href="{{ asset('css/filament/tenant/theme.css') }}" rel="stylesheet">
    @endpush

    <div x-data="{ show: true }" x-show="show" 
         class="dimiss-alert bug-notification bg-yellow-200 border-l-4 border-yellow-600 text-yellow-900 p-4 mb-4 rounded-lg shadow-lg relative flex">

        <div class="flex-1">
            <p class="text-sm">
                La plataforma se encuentra en una fase alfa. <b>Puede ser inestable o presentar problemas</b>.
                Si encuentras un error, por favor <a href="https://forms.gle/ExezFvDNXfAJLbW4A" Target="_blank"  class="underline font-medium text-yellow-900 hover:text-yellow-700">repórtalo aquí</a>.
            </p>
        </div>

        {{-- Botón de cierre --}}
        <button @click="show = false" class=" text-yellow-900 hover:text-yellow-700">
            ✖
        </button>
    </div>

    <div x-data="{ show: true }" x-show="show" 
     class="dimiss-alert first-steps bg-blue-100 border-l-4 border-blue-600 text-blue-900 p-4 mb-4 rounded-lg shadow-lg relative flex">

    <div class="flex-1">
        <p class="text-sm font-semibold mb-2">¡Bienvenido! Te sugerimos estos primeros pasos:</p>
        <ul class="list-disc list-inside space-y-2">
            <li>
                <strong>Configurar tu indoor:</strong><br>
                <a href="/tenant/indoors/create" class="underline hover:text-blue-700">
                    /tenant/indoors/create
                </a> - Carga dimensiones, potencia de luces, ventiladores, etc.
            </li>
            <li>
                <strong>Revisa nuestras semillas ya cargadas:</strong><br>
                <a href="/tenant/seeds" class="underline hover:text-blue-700">
                    /tenant/seeds
                </a> - Listado pre cargado, filtra por tipo y ratios CBD/THC
            </li>
            <li>
                <strong>Cargar tus semillas:</strong><br>
                <a href="/tenant/seeds/create" class="underline hover:text-blue-700">
                    /tenant/seeds/create
                </a> - Registra tipo (feminizadas, automáticas), floración y ratios CBD/THC
            </li>
            <li>
                <strong>Registrar tus plantas:</strong><br>
                <a href="/tenant/plants/create" class="underline hover:text-blue-700">
                    /tenant/plants/create
                </a> - Usa tus semillas registradas, agrega fechas, macetas y sustratos
            </li>
            <li>
                <strong>Primer cuidado de plantas:</strong><br>
                <a href="/tenant/actions/create" class="underline hover:text-blue-700">
                    /tenant/actions/create
                </a> - Registra riegos, podas o aplicaciones de productos
            </li>
        </ul>
    </div>

    {{-- Botón de cierre --}}
    <button @click="show = false" class="text-blue-900 hover:text-blue-700 ml-4 self-start">
        ✖
    </button>
</div>

    <div class="mb-6">
        {{ $this->filtersForm }}
    </div>

    <div class="space-y-6">
        @foreach($this->getIndoors() as $indoor)
            <div class="mx-auto shadow-lg overflow-hidden  rounded-xl border border-gray-200 dark:border-white/10">
                <!-- Header with title and days -->
                <div class="p-4 border text-center border-gray-200 dark:border-white/10">
                    <h2 class="text-2xl font-bold"> {{ $indoor->name }} ({{ $indoor->plants->count() }} plantas)</h2>
                </div>
                <!-- Light info section -->
                <div class="p-4 container-flex border border-gray-200 dark:border-white/10">
                    <div class="flex justify-center items-center mb-4">
                        <svg class="w-8 h-8 mr-2" viewBox="0 0 24 24" fill="currentColor" style="color: var(--secondary);">
                            <path fill-rule="evenodd" d="M7.05 4.05A7 7 0 0 1 19 9c0 2.407-1.197 3.874-2.186 5.084l-.04.048C15.77 15.362 15 16.34 15 18a1 1 0 0 1-1 1h-4a1 1 0 0 1-1-1c0-1.612-.77-2.613-1.78-3.875l-.045-.056C6.193 12.842 5 11.352 5 9a7 7 0 0 1 2.05-4.95ZM9 21a1 1 0 0 1 1-1h4a1 1 0 1 1 0 2h-4a1 1 0 0 1-1-1Zm1.586-13.414A2 2 0 0 1 12 7a1 1 0 1 0 0-2 4 4 0 0 0-4 4 1 1 0 0 0 2 0 2 2 0 0 1 .586-1.414Z" clip-rule="evenodd"/>

                        </svg>
                        <div>
                            <h3 class="font-semibold">Lámparas</h3>
                        </div>
                    </div>
                    @if(!empty($indoor->lamps) && is_array($indoor->lamps))
                        @foreach($indoor->lamps as $lamp)
                            <div class="flex items-center">
                                {{ $lamp['power'] . ' W - ' . $lamp['technology'] ?? 'N/A' }}<br>
                                {{ $lamp['observations'] ?? '' }}
                            </div>
                        @endforeach
                    @else
                        <p class="text-sm text-gray-400">No hay información sobre lámparas.</p>
                    @endif
                </div>


                <!-- Plants info section -->
                <div class="container-flex space-y-2 p-4 ">
                    @foreach($this->getPlants($indoor->id) as $plant)
                        <div class="custom-class p-4 rounded-xl border border-gray-200 dark:border-white/10">
                            <div class="space-y-1">
                                <p class="font-semibold">{{ $plant->name }}</p>
                                <p class="text-sm text-gray-500">
                                    @if($plant->seedType)
                                        {{ $plant->seedType->name }} ({{ $plant->seedType->seed_type }})
                                    @else
                                        No seed information available
                                    @endif
                                </p>
                                <!-- calcula los días en base a la fecha actual con germination_date -->
                                @php
                                    $germinationDate = \Carbon\Carbon::parse($plant->germination_date);
                                    $daysOfLife = $germinationDate->diffInDays(\Carbon\Carbon::now());
                                @endphp
                                <p class="text-sm text-gray-400">{{ $daysOfLife }} días de vida</p>
                                @php
                                    $stateColors = [
                                        'Etapa de Germinación' => 'border-secondary',
                                        'Etapa de Plantula' => 'border-primary',
                                        'Etapa Vegetativa' => 'border-tertiary',
                                        'Etapa Floracion' => 'border-accent',
                                        'muerta' => 'border-grey',
                                    ];
                                @endphp
                                <p class="text-sm {{ $stateColors[$plant->state] ?? '' }}">{{ $plant->state }}</p>
                                @php
                                    $flowerpots = ['Geotextiles', 'Plásticas', 'Bolsones'];
                                @endphp
                                <p class="text-sm text-gray-400">{{ $flowerpots[$plant->flowerpot] ?? 'N/A' }}:
                                    {{ $plant->capacity ?? '00' }}</p>
                            </div>
                        </div>
                    @endforeach
                </div>

                <!-- Last Actions Section -->
                <div class="p-4">
                    <h3 class="text-lg font-bold mb-2">Últimas acciones</h3>
                    @php
                        $actions = $this->getLastActionsForIndoor($indoor->id);
                    @endphp
                    @if($actions->isNotEmpty())
                        <ul class="space-y-2">
                            @foreach($actions as $action)
                                <li class="p-1">
                                    <p class="">
                                        <span class="text-xs">{{ \Carbon\Carbon::parse($action->action_date)->format('d/m/y') }}</span> -
                                        {{ $action->action_type->name ?? 'Acción desconocida' }}:
                                        {{ $action->getDetalleAccionAttribute() ?? 'Sin detalles' }}
                                    </p>
                                </li>
                            @endforeach
                        </ul>
                    @else
                        <p class="text-sm text-gray-500">No hay acciones recientes.</p>
                    @endif
                </div>

                <!-- Cultivation area info -->
                <div
                    class="px-4 py-2 bg-text-gray-400 text-sm text-center border border-gray-200 dark:border-white/10">
                    <p> {{__('carpa_cultivo') . ' ' . $indoor->width ?? '80' }}cm x{{ $indoor->large ?? '80' }}cm</p>
                </div>


            </div>
        @endforeach

    </div>
</x-filament::page>
