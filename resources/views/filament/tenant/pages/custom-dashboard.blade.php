<x-filament::page>
    @push('styles')
        <link href="{{ asset('css/custom.css') }}" rel="stylesheet">
        <link href="{{ asset('css/filament/tenant/theme.css') }}" rel="stylesheet">
    @endpush

    <div class="dashboard-container">
        {{-- Alerta de Bug --}}
        <div x-data="{ show: true }" x-show="show"
            class="dashboard-alert warning">
            <div class="flex items-center justify-between gap-4">
                <div class="flex items-center gap-4">
                    <div class="flex-shrink-0">
                        <x-icon name="heroicon-o-exclamation-triangle" class="w-12 h-12 text-orange-600"/>
                    </div>
                    <div class="flex-1">
                        <h3 class="text-xl font-bold text-orange-600 mb-1">¡Plataforma en Desarrollo!</h3>
                        <p class="text-lg text-orange-900">
                            Esta es una versión <span class="font-bold">ALFA</span> y puede presentar inestabilidades. 
                            Si encuentras algún error, por favor
                            <a href="https://forms.gle/ExezFvDNXfAJLbW4A" target="_blank"
                                class="underline font-medium text-orange-600 hover:text-orange-800">
                                mandalo acá
                            </a>.
                        </p>
                    </div>
                    <img src="/images/bughunter.png" class="w-24 h-24 object-cover rounded">
                </div>
                <button @click="show = false" class="text-orange-600 hover:text-orange-800">
                    <x-icon name="heroicon-o-x-mark" class="w-8 h-8"/>
                </button>
            </div>
        </div>

        {{-- Tutorial Steps --}}
        <div x-data="{ show: true }" x-show="show"
            class="dashboard-alert">
            <div class="flex justify-between items-start mb-4">
            <div class="text-center">
                <h2 class="text-2xl font-bold">¡Bienvenido! Podés comenzar por acá</h2>
                <p class="text-sm text-gray-600 dark:text-gray-400">Este es un proyecto comunitario, te invito a sumarte al server de discord, seguirnos en instagram, twitter, podes ver revisar todo en <a href="http://cannabica.ar" class="underline">Cannabica.ar</a></p>
            </div>    
                <button @click="show = false" class="text-gray-500 hover:text-gray-700">
                    <x-icon name="heroicon-o-x-mark" class="w-6 h-6"/>
                </button>
            </div>

            <div class="tutorial-grid">
                <a href="/tenant/tutorials/telegram-bot" class="card-tutorial">
                    <img src="/images/tutorials/telegram-bot.png" class="w-full aspect-video object-cover rounded-t-lg">
                    <div class="p-4">
                        <h3 class="font-bold text-lg mb-2">Configurar Bot Telegram</h3>
                        <p class="text-sm text-gray-600 dark:text-gray-400">Recibe notificaciones y alertas en tiempo real.</p>
                    </div>
                </a>
                <a href="/tenant/indoors/create" class="card-tutorial">
                    <img src="/images/tutorial01.png" class="w-full aspect-video object-cover rounded-t-lg">
                    <div class="p-4">
                        <h3 class="font-bold text-lg mb-2">Configurar tu indoor</h3>
                        <p class="text-sm text-gray-600 dark:text-gray-400">Carga dimensiones, potencia de luces, ventiladores, etc.</p>
                    </div>
                </a>

                <a href="/tenant/seeds" class="card-tutorial">
                    <img src="/images/tutorial02.png" class="w-full aspect-video object-cover rounded-t-lg">
                    <div class="p-4">
                        <h3 class="font-bold text-lg mb-2">Revisa nuestras semillas</h3>
                        <p class="text-sm text-gray-600 dark:text-gray-400">Listado pre cargado, filtra por tipo y ratios CBD/THC.</p>
                    </div>
                </a>

                <a href="/tenant/seeds/create" class="card-tutorial">
                    <img src="/images/tutorial03.png" class="w-full aspect-video object-cover rounded-t-lg">
                    <div class="p-4">
                        <h3 class="font-bold text-lg mb-2">Cargar tus semillas</h3>
                        <p class="text-sm text-gray-600 dark:text-gray-400">Registra tipo (feminizadas, automáticas), floración y ratios CBD/THC.</p>
                    </div>
                </a>

                <a href="/tenant/plants/create" class="card-tutorial">
                    <img src="/images/tutorial04.png" class="w-full aspect-video object-cover rounded-t-lg">
                    <div class="p-4">
                        <h3 class="font-bold text-lg mb-2">Registrar tus plantas</h3>
                        <p class="text-sm text-gray-600 dark:text-gray-400">Usa tus semillas registradas, agrega fechas, macetas y sustratos.</p>
                    </div>
                </a>

                <a href="/tenant/actions/create" class="card-tutorial">
                    <img src="/images/tutorial05.png" class="w-full aspect-video object-cover rounded-t-lg">
                    <div class="p-4">
                        <h3 class="font-bold text-lg mb-2">Primer cuidado de plantas</h3>
                        <p class="text-sm text-gray-600 dark:text-gray-400">Registra riegos, podas o aplicaciones de productos.</p>
                    </div>
                </a>
            </div>
        </div>

        <div class="mb-6">
            {{ $this->filtersForm }}
        </div>

        {{-- Indoors Grid --}}
        <div class="grid gap-6">
            @foreach($this->getIndoors() as $indoor)
                <div class="indoor-card">
                    <div class="indoor-header">
                        <h2 class="text-2xl font-bold text-center">{{ $indoor->name }} ({{ $indoor->plants->count() }} plantas)</h2>
                    </div>

                    <div class="indoor-content">
                        {{-- Lamps Section --}}
                        <div class="lamp-section">
                            <div class="flex items-center gap-2 w-full mb-4">
                                <x-icon name="heroicon-o-light-bulb" class="w-6 h-6 text-cadetblue"/>
                                <h3 class="font-semibold">Lámparas</h3>
                            </div>
                            
                            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                                @if(!empty($indoor->lamps) && is_array($indoor->lamps))
                                    @foreach($indoor->lamps as $lamp)
                                        <div class="lamp-item">
                                            <x-icon name="heroicon-o-light-bulb" class="w-5 h-5 text-cadetblue"/>
                                            <div>
                                                <p class="font-medium">{{ $lamp['power'] }}W - {{ $lamp['technology'] ?? 'N/A' }}</p>
                                                @if(!empty($lamp['observations']))
                                                    <p class="text-sm text-gray-500">{{ $lamp['observations'] }}</p>
                                                @endif
                                            </div>
                                        </div>
                                    @endforeach
                                @else
                                    <p class="text-sm text-gray-500">No hay información sobre lámparas.</p>
                                @endif
                            </div>
                        </div>

                        {{-- Plants Grid --}}
                        <div class="plants-grid">
                            @foreach($this->getPlants($indoor->id) as $plant)
                                <div class="plant-content-dashboard border border-gray-200 dark:border-white/10 rounded-xl">
                                    <div class="space-y-2">
                                        <div class="flex items-center justify-between">
                                            <h3 class="font-semibold truncate">{{ $plant->name }}</h3>
                                            <div class="state-badge state-badge-{{ strtolower(str_replace(['Etapa de ', 'Etapa '], '', $plant->state)) }}">
                                                {{ str_replace(['Etapa de ', 'Etapa '], '', $plant->state) }}
                                            </div>
                                        </div>
                                        
                                        <p class="text-sm text-gray-600 dark:text-gray-400 truncate">
                                            @if($plant->seedType)
                                                {{ $plant->seedType->name }} ({{ $plant->seedType->seed_type }})
                                            @else
                                                No seed information available
                                            @endif
                                        </p>

                                        <div class="flex items-center gap-2 text-sm text-gray-500">
                                            <x-icon name="heroicon-o-clock" class="w-4 h-4"/>
                                            <span>{{ now()->diffInDays($plant->germination_date) }} días de vida</span>
                                        </div>

                                        <div class="flex items-center gap-2 text-sm text-gray-500">
                                            <x-icon name="heroicon-o-beaker" class="w-4 h-4"/>
                                            <span>{{"Maceta ". $plant->flowerpot ?? 'N/A' }}: {{ $plant->capacity ?? '00' }}L</span>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>

                        {{-- Actions History --}}
                        <div class="action-history">
                            <h3 class="text-lg font-bold mb-4">Últimas acciones</h3>
                            @php
                                $actions = $this->getLastActionsForIndoor($indoor->id);
                            @endphp
                            
                            @if($actions->isNotEmpty())
                                <div class="space-y-2">
                                    @foreach($actions as $action)
                                        <div class="action-item">
                                            <span class="action-date">
                                                {{ \Carbon\Carbon::parse($action->action_date)->format('d/m/y') }}
                                            </span>
                                            <div>
                                                <span class="font-medium">{{ $action->action_type->name ?? 'Acción desconocida' }}</span>
                                                <p class="text-sm text-gray-500">{{ $action->getDetalleAccionAttribute() ?? 'Sin detalles' }}</p>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            @else
                                <p class="text-sm text-gray-500">No hay acciones recientes.</p>
                            @endif
                        </div>

                        {{-- Indoor Info Footer --}}
                        <div class="mt-6 text-center text-sm text-gray-500 border-t border-gray-200 dark:border-white/10 pt-4">
                            <p>{{__('carpa_cultivo') . ' ' . $indoor->width ?? '80' }}cm x {{ $indoor->large ?? '80' }}cm</p>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        {{-- Floating Action Button --}}
        @php
            $lastIrrigation = \App\Models\Action::where('tenant_id', auth()->user()->tenant_id)
                ->where('action_type_id', 1)
                ->latest()
                ->first();
        @endphp

        @if($lastIrrigation)
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
            class="floating-button">
                <x-icon name="heroicon-o-beaker" class="w-6 h-6"/>
                <span class="font-medium">Repetir último riego</span>
            </a>
        @endif
    </div>
</x-filament::page>