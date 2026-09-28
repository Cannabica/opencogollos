<x-filament::page>
    <div class="dashboard-container" x-data>

        @php
            $indoors = $this->getIndoors();
            $totalPlants = $indoors->sum(fn ($i) => $i->plants->count());
            $plantasConEspacio = $indoors->flatMap(
                fn ($indoor) => $this->getPlants($indoor->id)->map(fn ($plant) => ['plant' => $plant, 'indoor' => $indoor])
            );
        @endphp

        <div class="muro-head">
            <h3 class="muro-title">Tu cultivo</h3>
            <div class="muro-nav">
                <span class="muro-hint">Desplazate a la derecha para ver más</span>
                <button type="button" title="Desplazar a la izquierda"
                    @click="$refs.muro.scrollBy({ left: -680, behavior: 'smooth' })">
                    <x-icon name="heroicon-o-chevron-left" class="w-5 h-5" />
                </button>
                <button type="button" title="Desplazar a la derecha"
                    @click="$refs.muro.scrollBy({ left: 680, behavior: 'smooth' })">
                    <x-icon name="heroicon-o-chevron-right" class="w-5 h-5" />
                </button>
            </div>
        </div>

        {{-- MURO: bloques de ancho distinto, desplazamiento horizontal.
             La rueda del mouse mueve el muro de costado mientras quede
             contenido a los lados; en las puntas vuelve a la página. --}}
        <div class="muro" x-ref="muro"
            x-init="$nextTick(() => {
                const el = $refs.muro;
                el.addEventListener('wheel', (e) => {
                    if (Math.abs(e.deltaY) <= Math.abs(e.deltaX)) return;
                    const max = el.scrollWidth - el.clientWidth;
                    if (max <= 0) return;
                    const abajo = e.deltaY > 0;
                    const puede = abajo ? el.scrollLeft < max - 1 : el.scrollLeft > 1;
                    if (puede) {
                        e.preventDefault();
                        el.scrollLeft += e.deltaY;
                    }
                }, { passive: false });
            })">

            {{-- ── Bloque ancho: estado de la plataforma + tu cultivo + primeros pasos ── --}}
            <div class="muro-bloque muro-bloque--ancha">

                <div class="muro-card muro-card--resumen">
                    <div class="muro-aviso">
                        <x-icon name="heroicon-o-exclamation-triangle" class="w-6 h-6 flex-shrink-0" />
                        <p>
                            <span class="font-bold">¡Plataforma en Desarrollo!</span>
                            Esta es una versión <span class="font-bold">ALFA</span> y puede presentar inestabilidades.
                            @if (filled(config('platform.community.feedback_url')))
                                Si encontras algún error,
                                <a href="{{ config('platform.community.feedback_url') }}" target="_blank" class="underline">mandalo acá</a>.
                            @endif
                        </p>
                    </div>

                    <div class="muro-resumen-body">
                        <div class="muro-metricas">
                            <div class="muro-metrica">
                                <strong>{{ $indoors->count() }}</strong>
                                <span>{{ \Illuminate\Support\Str::plural('espacio', $indoors->count()) }}</span>
                            </div>
                            <div class="muro-metrica">
                                <strong>{{ $totalPlants }}</strong>
                                <span>{{ \Illuminate\Support\Str::plural('planta', $totalPlants) }}</span>
                            </div>
                        </div>

                        <div class="muro-filtro">
                            {{ $this->filtersForm }}
                        </div>
                    </div>
                </div>

                <div x-data="{ show: true }" x-show="show" class="muro-card muro-card--onboarding">
                    @php
                        $siteUrl = config('platform.site_url');
                        $discordUrl = config('platform.community.discord_url');
                        $brandName = config('platform.brand_name') ?: (filled($siteUrl) ? parse_url($siteUrl, PHP_URL_HOST) : null);
                    @endphp

                    <div class="muro-card-head">
                        <div>
                            <h3>¡Bienvenido!</h3>
                            @if (filled($siteUrl) || filled($discordUrl))
                                <p class="muro-sub">
                                    Este es un proyecto comunitario
                                    @if (filled($discordUrl))
                                        · te invito a sumarte al server de discord
                                    @endif
                                    @if (filled($siteUrl))
                                        · <a href="{{ $siteUrl }}" class="underline">{{ $brandName }}</a>
                                    @endif
                                </p>
                            @endif
                        </div>
                        <button @click="show = false" class="card-close" title="Cerrar">
                            <x-icon name="heroicon-o-x-mark" class="w-5 h-5" />
                        </button>
                    </div>

                    <div class="muro-shortcuts">
                        <a href="/tenant/tutorials/telegram-bot" class="muro-shortcut">
                            <img src="/images/tutorials/telegram-bot.png" alt="">
                            <div class="muro-shortcut-body">
                                <h4>Configurar Bot Telegram</h4>
                                <p>Recibí notificaciones y alertas en tiempo real.</p>
                            </div>
                        </a>
                        <a href="/tenant/indoors/create" class="muro-shortcut">
                            <img src="/images/tutorial01.png" alt="">
                            <div class="muro-shortcut-body">
                                <h4>Configurar tu indoor</h4>
                                <p>Cargá dimensiones, potencia de luces, ventiladores, etc.</p>
                            </div>
                        </a>
                        <a href="/tenant/seeds" class="muro-shortcut">
                            <img src="/images/tutorial02.png" alt="">
                            <div class="muro-shortcut-body">
                                <h4>Revisá nuestras semillas</h4>
                                <p>Listado precargado, filtrá por tipo y ratios CBD/THC.</p>
                            </div>
                        </a>
                        <a href="/tenant/seeds/create" class="muro-shortcut">
                            <img src="/images/tutorial03.png" alt="">
                            <div class="muro-shortcut-body">
                                <h4>Cargar tus semillas</h4>
                                <p>Registrá tipo, floración y ratios CBD/THC.</p>
                            </div>
                        </a>
                        <a href="/tenant/plants/create" class="muro-shortcut">
                            <img src="/images/tutorial04.png" alt="">
                            <div class="muro-shortcut-body">
                                <h4>Registrar tus plantas</h4>
                                <p>Usá tus semillas, agregá fechas, macetas y sustratos.</p>
                            </div>
                        </a>
                        <a href="/tenant/actions/create" class="muro-shortcut">
                            <img src="/images/tutorial05.png" alt="">
                            <div class="muro-shortcut-body">
                                <h4>Primer cuidado de plantas</h4>
                                <p>Registrá riegos, podas o aplicaciones de productos.</p>
                            </div>
                        </a>
                    </div>
                </div>
            </div>

            @if ($indoors->isEmpty())
                {{-- ── Primer espacio todavía vacío ── --}}
                <div class="muro-bloque muro-bloque--media">
                    <div class="muro-card muro-card--empty">
                        <x-icon name="heroicon-o-home-modern" class="w-12 h-12" />
                        <h3>Todavía no cargaste ningún espacio</h3>
                        <p>El espacio es el lugar donde crecen tus plantas: una carpa, una habitación o el patio.</p>
                        <a href="/tenant/indoors/create" class="muro-cta">Crear mi primer espacio</a>
                    </div>
                </div>
            @else
                {{-- ── Bloque medio: los espacios, con la info que los identifica ── --}}
                <div class="muro-bloque muro-bloque--media">
                    @foreach ($indoors as $indoor)
                        @php
                            $plantas = $this->getPlants($indoor->id);
                            $estados = $plantas->groupBy('state');
                        @endphp

                        <div class="muro-card muro-card--espacio">
                            <div class="muro-card-head">
                                <h3>{{ $indoor->name }}</h3>
                                <span class="muro-count">{{ $plantas->count() }}
                                    {{ \Illuminate\Support\Str::plural('planta', $plantas->count()) }}</span>
                            </div>
                            <div class="muro-card-body">
                                <div class="muro-states">
                                    @forelse ($estados as $estado => $grupo)
                                        <span class="state-badge state-badge-{{ strtolower(str_replace(['Etapa de ', 'Etapa '], '', $estado)) }}">
                                            {{ str_replace(['Etapa de ', 'Etapa '], '', $estado) }}: {{ $grupo->count() }}
                                        </span>
                                    @empty
                                        <span class="muro-chip muro-chip--muted">Sin plantas cargadas</span>
                                    @endforelse
                                </div>

                                <div class="muro-chips">
                                    @if (!empty($indoor->lamps) && is_array($indoor->lamps))
                                        @foreach ($indoor->lamps as $lamp)
                                            <span class="muro-chip">
                                                <x-icon name="heroicon-o-light-bulb" class="w-3.5 h-3.5" />
                                                {{ $lamp['power'] }}W {{ $lamp['technology'] ?? '' }}
                                            </span>
                                        @endforeach
                                    @else
                                        <span class="muro-chip muro-chip--muted">Sin lámparas cargadas</span>
                                    @endif
                                    <span class="muro-chip muro-chip--muted">
                                        {{ $indoor->width ?? '—' }} × {{ $indoor->large ?? '—' }} cm
                                    </span>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>

                {{-- ── Bloques angostos: las plantas, en tarjetas compactas de a 6 por columna ── --}}
                @foreach ($plantasConEspacio->chunk(6) as $grupo)
                    <div class="muro-bloque muro-bloque--angosta">
                        @foreach ($grupo as $item)
                            @php $plant = $item['plant']; $espacio = $item['indoor']; @endphp

                            <div class="muro-card muro-card--planta">
                                <div class="muro-card-body">
                                    <div class="muro-plant-top">
                                        <h4>{{ $plant->name }}</h4>
                                        <span class="state-badge state-badge-{{ strtolower(str_replace(['Etapa de ', 'Etapa '], '', $plant->state)) }}">
                                            {{ str_replace(['Etapa de ', 'Etapa '], '', $plant->state) }}
                                        </span>
                                    </div>

                                    <p class="muro-plant-seed">
                                        @if ($plant->seedType)
                                            {{ $plant->seedType->name }} ({{ $plant->seedType->seed_type }})
                                        @else
                                            Sin semilla cargada
                                        @endif
                                    </p>

                                    <div class="plant-meta">
                                        @if ($plant->germination_date)
                                            <span>
                                                <x-icon name="heroicon-o-clock" class="w-3.5 h-3.5" />
                                                {{ now()->diffInDays($plant->germination_date) }} días
                                            </span>
                                        @endif
                                        <span>
                                            <x-icon name="heroicon-o-beaker" class="w-3.5 h-3.5" />
                                            {{ 'Maceta ' . $plant->flowerpot ?? 'N/A' }}: {{ $plant->capacity ?? '00' }}L
                                        </span>
                                    </div>

                                    <div class="muro-chips">
                                        <span class="muro-chip muro-chip--space">{{ $espacio->name }}</span>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endforeach

                {{-- ── Bloque medio: lo último que pasó en cada espacio ── --}}
                <div class="muro-bloque muro-bloque--media">
                    @foreach ($indoors as $indoor)
                        @php $actions = $this->getLastActionsForIndoor($indoor->id); @endphp

                        <div class="muro-card muro-card--acciones">
                            <div class="muro-card-head">
                                <h3>Últimas acciones · {{ $indoor->name }}</h3>
                            </div>
                            <div class="muro-card-body">
                                @if ($actions->isNotEmpty())
                                    <div class="action-list">
                                        @foreach ($actions as $action)
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
                        </div>
                    @endforeach
                </div>
            @endif
        </div>

        {{-- Acción rápida: repetir el último riego --}}
        @php
            $lastIrrigation = \App\Models\Action::where('tenant_id', auth()->user()->tenant_id)
                ->where('action_type_id', 1)
                ->latest()
                ->first();
        @endphp

        @if ($lastIrrigation)
            <a href="{{ \App\Filament\Tenant\Resources\ActionsResource::getUrl('create', [
                'indoor_id' => $lastIrrigation->indoor_id,
                'action_type_id' => 1,
                'irrigation_type' => $lastIrrigation->data['irrigation']['irrigation_type'] ?? '',
                'irrigation_value' => isset($lastIrrigation->data['irrigation']['irrigation_type'])
                    ? ($lastIrrigation->data['irrigation']['irrigation_type'] === 'liters'
                        ? ($lastIrrigation->data['irrigation']['liters'] ?? '')
                        : ($lastIrrigation->data['irrigation']['timer'] ?? ''))
                    : '',
                'selected_plants' => json_encode($lastIrrigation->plants->pluck('id')->toArray()),
            ]) }}"
                class="floating-button">
                <x-icon name="heroicon-o-beaker" class="w-6 h-6" />
                <span class="font-medium">Repetir último riego</span>
            </a>
        @endif
    </div>
</x-filament::page>
