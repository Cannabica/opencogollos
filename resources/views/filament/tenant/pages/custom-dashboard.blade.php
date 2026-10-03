<x-filament::page>
    <div class="dashboard-container">

        @php
            $spaces = $this->getSpaces();
            $resumen = $this->getWorkSummary($spaces);
        @endphp

        {{-- Cabecera: estado de la plataforma + lo que pide atención hoy + controles --}}
        <div class="carta carta--resumen">
            <div class="aviso-plataforma">
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

            <div class="resumen-cuerpo">
                <div class="metricas">
                    <div class="metrica">
                        <strong>{{ $resumen['espacios'] }}</strong>
                        <span>{{ $resumen['espacios'] === 1 ? 'lugar' : 'lugares' }}</span>
                    </div>
                    <div class="metrica">
                        <strong>{{ $resumen['plantas'] }}</strong>
                        <span>{{ \Illuminate\Support\Str::plural('planta', $resumen['plantas']) }}</span>
                    </div>

                    @if ($resumen['plantas_sin_riego'] > 0)
                        <div class="metrica metrica--alerta">
                            <strong>{{ $resumen['plantas_sin_riego'] }}</strong>
                            <span>{{ $resumen['plantas_sin_riego'] === 1 ? 'planta sin riego' : 'plantas sin riego' }}</span>
                        </div>
                    @endif

                    @if ($resumen['espacios_atrasados'] > 0)
                        <div class="metrica metrica--alerta">
                            <strong>{{ $resumen['espacios_atrasados'] }}</strong>
                            <span>{{ $resumen['espacios_atrasados'] === 1 ? 'lugar atrasado' : 'lugares atrasados' }}</span>
                        </div>
                    @endif
                </div>

                <div class="controles">
                    <div class="filtro-lugar">
                        {{ $this->filtersForm }}
                    </div>

                    <a href="{{ \App\Filament\Tenant\Resources\ActionsResource::getUrl('create') }}"
                        class="boton-principal">
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
                <p class="nota-comunidad">
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
            <div class="carta carta--vacia">
                <x-icon name="heroicon-o-home-modern" class="w-12 h-12" />
                <h3>Todavía no cargaste ningún lugar</h3>
                <p>El lugar donde crecen tus plantas puede ser una carpa, una habitación o el patio.</p>
                <a href="/tenant/indoors/create" class="boton-principal">Crear mi primer lugar</a>
            </div>
        @else
            <div class="grilla-lugares">
                @foreach ($spaces as $space)
                    @include('filament.tenant.pages.partials.space-block', ['space' => $space])
                @endforeach
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
