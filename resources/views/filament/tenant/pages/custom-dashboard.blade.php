<x-filament::page>
    <div class="dashboard-container"
        x-data="{
            vista: 'lista',
            init() {
                const guardada = localStorage.getItem('oi_dashboard_vista');
                if (guardada === 'lista' || guardada === 'mural') this.vista = guardada;
            },
            cambiarVista(nueva) {
                this.vista = nueva;
                localStorage.setItem('oi_dashboard_vista', nueva);
            },
            hayScroll: false,
            sinAnimacion() {
                return window.matchMedia('(prefers-reduced-motion: reduce)').matches ? 'auto' : 'smooth';
            },
        }">

        @php
            $spaces = $this->getSpaces();
            $resumen = $this->getWorkSummary($spaces);
        @endphp

        {{-- Cabecera: estado de la plataforma + lo que pide atención hoy + controles --}}
        <div class="muro-card muro-card--resumen">
            <div class="muro-aviso">
                <x-icon name="heroicon-o-exclamation-triangle" class="w-6 h-6 flex-shrink-0" />
                <p>
                    <span class="font-bold">¡Plataforma en desarrollo!</span>
                    Esta es una versión <span class="font-bold">alfa</span> y puede presentar inestabilidades.
                    @if (filled(config('platform.community.feedback_url')))
                        Si encontras algún error,
                        <a href="{{ config('platform.community.feedback_url') }}" target="_blank" class="underline">mandalo acá</a>.
                    @endif
                </p>
            </div>

            <div class="muro-resumen-body">
                <div class="muro-metricas">
                    <div class="muro-metrica">
                        <strong>{{ $resumen['espacios'] }}</strong>
                        <span>{{ \Illuminate\Support\Str::plural('lugar', $resumen['espacios']) }}</span>
                    </div>
                    <div class="muro-metrica">
                        <strong>{{ $resumen['plantas'] }}</strong>
                        <span>{{ \Illuminate\Support\Str::plural('planta', $resumen['plantas']) }}</span>
                    </div>

                    @if ($resumen['espacios_sin_riego'] > 0 || $resumen['plantas_sin_riego'] > 0)
                        <div class="muro-metrica muro-metrica--alerta">
                            <strong>{{ $resumen['plantas_sin_riego'] }}</strong>
                            <span>{{ $resumen['plantas_sin_riego'] === 1 ? 'planta sin riego' : 'plantas sin riego' }}</span>
                        </div>
                    @endif

                    @if ($resumen['espacios_atrasados'] > 0)
                        <div class="muro-metrica muro-metrica--alerta">
                            <strong>{{ $resumen['espacios_atrasados'] }}</strong>
                            <span>{{ $resumen['espacios_atrasados'] === 1 ? 'lugar atrasado' : 'lugares atrasados' }}</span>
                        </div>
                    @endif
                </div>

                <div class="muro-controles">
                    <div class="muro-filtro">
                        {{ $this->filtersForm }}
                    </div>

                    <div class="vista-toggle" role="group" aria-label="Cómo ver tus lugares">
                        <button type="button" :class="vista === 'lista' && 'is-active'"
                            @click="cambiarVista('lista')">Lista</button>
                        <button type="button" :class="vista === 'mural' && 'is-active'"
                            @click="cambiarVista('mural')">Mural</button>
                    </div>

                    <a href="{{ \App\Filament\Tenant\Resources\ActionsResource::getUrl('create') }}"
                        class="muro-cta">
                        <x-icon name="heroicon-o-plus" class="w-4 h-4" />
                        Registrar acción
                    </a>
                </div>
            </div>

            {{-- Invitación a la comunidad/marca: sólo si la instalación la configuró --}}
            @php
                $siteUrl = config('platform.site_url');
                $discordUrl = config('platform.community.discord_url');
                $brandName = config('platform.brand_name') ?: (filled($siteUrl) ? parse_url($siteUrl, PHP_URL_HOST) : null);
            @endphp

            @if (filled($siteUrl) || filled($discordUrl))
                <p class="muro-comunidad">
                    Este es un proyecto comunitario
                    @if (filled($discordUrl))
                        · te invito a sumarte al servidor de Discord
                    @endif
                    @if (filled($siteUrl))
                        · <a href="{{ $siteUrl }}" class="underline">{{ $brandName }}</a>
                    @endif
                </p>
            @endif
        </div>

        @if ($spaces === [])
            <div class="muro-card muro-card--empty">
                <x-icon name="heroicon-o-home-modern" class="w-12 h-12" />
                <h3>Todavía no cargaste ningún lugar</h3>
                <p>El lugar donde crecen tus plantas puede ser una carpa, una habitación o el patio.</p>
                <a href="/tenant/indoors/create" class="muro-cta">Crear mi primer lugar</a>
            </div>
        @else
            {{-- Vista por defecto: los espacios uno debajo del otro (lectura vertical) --}}
            <div class="vista-lista" x-show="vista === 'lista'">
                @foreach ($spaces as $space)
                    @include('filament.tenant.pages.partials.space-block', ['space' => $space, 'modo' => 'lista'])
                @endforeach
            </div>

            {{-- Vista alternativa: el muro que se desplaza de costado --}}
            <div class="vista-mural" x-show="vista === 'mural'" x-cloak>
                <div class="muro-head">
                    <span class="muro-hint">Desplazate a la derecha para ver más lugares</span>
                    <div class="muro-nav">
                        <button type="button" title="Desplazar a la izquierda"
                            @click="$refs.muro.scrollBy({ left: -680, behavior: sinAnimacion() })">
                            <x-icon name="heroicon-o-chevron-left" class="w-5 h-5" />
                        </button>
                        <button type="button" title="Desplazar a la derecha"
                            @click="$refs.muro.scrollBy({ left: 680, behavior: sinAnimacion() })">
                            <x-icon name="heroicon-o-chevron-right" class="w-5 h-5" />
                        </button>
                    </div>
                </div>

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
                    @foreach ($spaces as $space)
                        @include('filament.tenant.pages.partials.space-block', ['space' => $space, 'modo' => 'mural'])
                    @endforeach
                </div>
            </div>
        @endif

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
