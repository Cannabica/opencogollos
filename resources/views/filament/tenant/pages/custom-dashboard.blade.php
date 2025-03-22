<x-filament::page>

    @push('styles')
        <link href="{{ asset('css/custom.css') }}" rel="stylesheet">
        <link href="{{ asset('css/filament/tenant/theme.css') }}" rel="stylesheet">
        <style>
            /* Asegurar que el botón flotante esté siempre visible */
            .fixed {
                position: fixed;
                z-index: 50;
            }
            
            /* Añadir efecto de hover suave */
            .hover\:shadow-xl:hover {
                transition: all 0.2s ease-in-out;
                transform: translateY(-2px);
            }
        </style>
    @endpush
    <div x-data="{ show: true }" x-show="show" 
     class="dimiss-alert bug-notification bg-yellow-200 p-4 mb-4 rounded-lg shadow-lg relative flex items-center">
    
    <div class="flex flex-col md:flex-row gap-4 flex-1">
        <!-- Columna de texto -->
        <div class="flex-1 flex items-center">
            <p class="text-xl">
                La plataforma se encuentra en una fase alfa. <b>Puede ser inestable o presentar problemas</b>.
                Si encuentras un error, por favor 
                <a href="https://forms.gle/ExezFvDNXfAJLbW4A" target="_blank"  
                   class="underline font-medium text-yellow-900 hover:text-yellow-700">
                    repórtalo aquí
                </a>.
            </p>
        </div>
        <!-- Columna de imagen -->
        <div class="flex justify-center">
            <img src="/images/bughunter.png" class="w-32 h-auto object-cover rounded">
        </div>
    </div>

    <!-- Botón de cierre -->
    <button @click="show = false" aria-label="Cerrar alerta" 
            class="hover:text-blue-700 ml-4 self-start">
        ✖
    </button>
</div>


    <div x-data="{ show: true }" x-show="show" 
     class="dimiss-alert first-steps bg-blue-100 p-4 mb-4 rounded-lg shadow-lg relative flex">

     <div class="flex-1">
        <div class="mt-3">
            <p class="text-xl text-center font-semibold mb-4">¡Bienvenido! Podés comenzar por acá</p>
        </div>

  <div class="flex flex-wrap justify-center gap-4 overflow-x-auto">
    <!-- Card 1 -->
    <div class="flex-shrink card-tutorial rounded shadow p-4">
        <a href="/tenant/indoors/create" class="hover:text-blue-700 block">
            <img src="/images/tutorial01.png" class="w-full h-50 object-cover rounded mb-2 max-w-xs">
            <h3 class="font-bold text-lg text-center mb-1">Configurar tu indoor</h3>
            <p class="text-sm">Carga dimensiones, potencia de luces, ventiladores, etc.</p>
        </a>
    </div>
    <!-- Card 2 -->
    <div class="flex-shrink-0 card-tutorial rounded shadow p-4">
        <a href="/tenant/seeds" class="hover:text-blue-700 block">
        <img src="/images/tutorial02.png" class="w-full object-cover rounded mb-2 max-w-xs">
        <h3 class="font-bold text-lg text-center mb-1">Revisa nuestras semillas</h3>
        <p class="text-sm">Listado pre cargado, filtra por tipo y ratios CBD/THC.</p>
      </a>
    </div>
    <!-- Card 3 -->
    <div class="flex-shrink-0 card-tutorial rounded shadow p-4">
        <a href="/tenant/seeds/create" class="hover:text-blue-700 block">
            <img src="/images/tutorial03.png" class="w-full object-cover rounded mb-2 max-w-xs">
            <h3 class="font-bold text-lg text-center mb-1">Cargar tus semillas</h3>
            <p class="text-sm">Registra tipo (feminizadas, automáticas), floración y ratios CBD/THC.</p>
      </a>
    </div>
    <!-- Card 4 -->
    <div class="flex-shrink-0 card-tutorial rounded shadow p-4">
        <a href="/tenant/plants/create" class="hover:text-blue-700 block">
            <img src="/images/tutorial04.png" class="w-full object-cover rounded mb-2 max-w-xs">
            <h3 class="font-bold text-lg text-center mb-1">Registrar tus plantas</h3>
            <p class="text-sm">Usa tus semillas registradas, agrega fechas, macetas y sustratos.</p>
        </a>
    </div>
    <!-- Card 5 -->
    <div class="flex-shrink-0 card-tutorial rounded shadow p-4">
        <a href="/tenant/actions/create" class="hover:text-blue-700 block">
            <img src="/images/tutorial05.png" class="w-full object-cover rounded mb-2 max-w-xs">
            <h3 class="font-bold text-lg text-center mb-1">Primer cuidado de plantas</h3>
            <p class="text-sm">Registra riegos, podas o aplicaciones de productos.</p>
        </a>
    </div>
  </div>
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

    {{-- Botón flotante de repetir riego --}}
    @php
        $lastIrrigation = \App\Models\Action::where('tenant_id', auth()->user()->tenant_id)
            ->where('action_type_id', 1)
            ->latest()
            ->first();
    @endphp

    @if($lastIrrigation)
        <div class="fixed bottom-6 right-6 z-50">
            <a href="{{ \App\Filament\Tenant\Resources\ActionsResource::getUrl('create', [
                'indoor_id' => $lastIrrigation->indoor_id,
                'action_type_id' => 1,
                'irrigation_type' => $lastIrrigation->data['irrigation']['irrigation_type'] ?? '',
                'irrigation_value' => isset($lastIrrigation->data['irrigation']['irrigation_type']) 
                    ? ($lastIrrigation->data['irrigation']['irrigation_type'] === 'liters' 
                        ? ($lastIrrigation->data['irrigation']['liters'] ?? '')
                        : ($lastIrrigation->data['irrigation']['timer'] ?? ''))
                    : '',
                'selected_plants' => json_encode($lastIrrigation->plants->pluck('id')->toArray())
            ]) }}"
               class="inline-flex items-center justify-center gap-2 rounded-full bg-primary-600 px-4 py-4 text-white hover:bg-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-600 focus:ring-offset-2 shadow-lg hover:shadow-xl transition-all duration-200">
                <svg class="w-6 h-6" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M12 21C15.866 21 19 17.866 19 14C19 10.5067 15.9333 6.71333 13.4667 4.26667C12.6667 3.46667 11.3333 3.46667 10.5333 4.26667C8.06667 6.71333 5 10.5067 5 14C5 17.866 8.13401 21 12 21Z" 
                          stroke="currentColor" 
                          stroke-width="2" 
                          stroke-linecap="round" 
                          stroke-linejoin="round"
                          fill="currentColor"
                          fill-opacity="0.2"/>
                </svg>
                <span class="text-sm font-medium">Repetir <br> último riego</span>
            </a>
        </div>
    @endif

</x-filament::page>
